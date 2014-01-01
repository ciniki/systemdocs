<?php
//
// Description
// -----------
// This function will update the systemdocs database with a module functions.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// 
// Returns
// -------
// <rsp stat="ok">
// </rsp>
function ciniki_systemdocs_updateModuleDetails($ciniki, $package, $module) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'processMarkdown');

	//
	// Load the table information from the database for this module
	//
	$strsql = "SELECT id, package, module, detail_key, details, html_details, "
		. "CONCAT_WS('_', package, module, detail_key) AS full_name, "
		. "UNIX_TIMESTAMP(last_updated) AS last_updated "
		. "FROM ciniki_systemdocs_api_module_details "
		. "WHERE ciniki_systemdocs_api_module_details.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' " 
		. "AND ciniki_systemdocs_api_module_details.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' " 
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.systemdocs', 'details', 'full_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'813', 'msg'=>'Unable to locate details', 'err'=>$rc['err']));
	}
	$db_details = array();
	if( isset($rc['details']) ) {
		$db_details = $rc['details'];
	}

	//
	// Check the module docs directory for module documentation
	//
	$dirs = array('overview', 'notes', 'description');
	$tz_offset = date('Z');
	foreach($dirs as $file) {
		$filename = $ciniki['config']['core']['root_dir'] . '/' . $package . '-mods/' . $module . '/docs/' . $file . '.txt';
		if( is_file($filename) ) {
			$mtime = filemtime("$filename") - $tz_offset;
			$full_name = "{$package}_{$module}_" . $file;
			if( !isset($db_details[$full_name]) 
				|| $db_details[$full_name]['last_updated'] < $mtime ) {
				// Add to the database
				$content = file($filename);	
				$content = implode($content);
				$rc = ciniki_systemdocs_processMarkdown($ciniki, $content);
				$html_content = $rc['html_content'];
				$strsql = "INSERT INTO ciniki_systemdocs_api_module_details (package, module, "	
					. "detail_key, details, html_details, last_updated ) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $package) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $file) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $content) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $html_content) . "', "
					. "UTC_TIMESTAMP()) "
					. "ON DUPLICATE KEY UPDATE details = '" . ciniki_core_dbQuote($ciniki, $content) . "', "
						. "html_details = '" . ciniki_core_dbQuote($ciniki, $html_content) . "', "
						. "last_updated = UTC_TIMESTAMP() "
					. "";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} 
		}
	}

	//
	// Read in the _info.ini file for the module, and add elements to ciniki_systemdocs_api_module_details
	//
	$filename = $ciniki['config']['core']['root_dir'] . '/' . $package . '-mods/' . $module . '/_info.ini';
	$ini_details = array();
	if( is_file($filename) ) {
		$ini_details = parse_ini_file($filename, false, INI_SCANNER_RAW);
		if( is_array($ini_details) ) {
			foreach($ini_details as $ini_key => $ini_detail) {
				$full_name = "{$package}_{$module}_" . $ini_key;
				if( !array_key_exists($full_name, $db_details) 
					|| $ini_detail != $db_details[$full_name]['details'] ) {
					$strsql = "INSERT INTO ciniki_systemdocs_api_module_details (package, module, "	
						. "detail_key, details, html_details, last_updated ) VALUES ("
						. "'" . ciniki_core_dbQuote($ciniki, $package) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $module) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $ini_key) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $ini_detail) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $ini_detail) . "', "
						. "UTC_TIMESTAMP()) "
						. "ON DUPLICATE KEY UPDATE details = '" . ciniki_core_dbQuote($ciniki, $ini_detail) . "', "
							. "html_details = '" . ciniki_core_dbQuote($ciniki, $ini_detail) . "', "
							. "last_updated = UTC_TIMESTAMP() "
						. "";
					$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	}

	//
	// Check for deleted files
	//
	foreach($db_details as $full_name => $db_detail) {
		$filename = $ciniki['config']['core']['root_dir'] . '/' . $db_detail['package'] . '-mods/' . $db_detail['module'] . '/docs/' . $db_detail['detail_key'] . '.txt';
		if( !is_file($filename) && !array_key_exists($db_detail['detail_key'], $ini_details) ) {
			//
			// Delete the function information
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_module_details "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_detail['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>

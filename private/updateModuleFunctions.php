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
function ciniki_systemdocs_updateModuleFunctions($ciniki, $package, $module) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updateModuleFunction');

	$duperrors = array();

	//
	// Load the table information from the database for this module
	//
	$strsql = "SELECT id, package, module, type, file, name, "
		. "CONCAT_WS('_', package, module, type, file) AS full_name, "
		. "UNIX_TIMESTAMP(last_updated) AS last_updated "
		. "FROM ciniki_systemdocs_api_functions "
		. "WHERE ciniki_systemdocs_api_functions.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' " 
		. "AND ciniki_systemdocs_api_functions.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' " 
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.systemdocs', 'functions', 'full_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'791', 'msg'=>'Unable to locate tables', 'err'=>$rc['err']));
	}
	$db_functions = array();
	if( isset($rc['functions']) ) {
		$db_functions = $rc['functions'];
	}

	//
	// Check for database schema and upgrade files
	//
	$mod_functions = array();
	$dirs = array('scripts', 'public', 'private', 'cron', 'web');
	$tz_offset = date('Z');
	foreach($dirs as $type) {
		$path = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/' . $type;
		if( is_dir($path) ) {
			//
			// Load in the module function timestamp
			//
			$fp = opendir($path);
			while( $file = readdir($fp) ) {
				if( preg_match('/(.*)\.php$/', $file, $matches) ) {
					$file = $matches[1];
					$full_name = "{$package}_{$module}_{$type}_" . $file;
					$mtime = filemtime("$path/$file.php") - $tz_offset;
					$mod_functions[$full_name] = array('package'=>$package, 'module'=>$module, 'type'=>$type, 'file'=>$file,
						'last_updated'=>$mtime);
				}
			}
		}
	}

	//
	// Check for updates
	//
	foreach($mod_functions as $full_name => $mod_function) {
		$dt = NULL;
		$function_id = 0;
		// 
		// Check if the function exists, or the file has a later timestamp than the database
		//
		if( !isset($db_functions[$full_name]) 
			|| $db_functions[$full_name]['last_updated'] < $mod_function['last_updated'] ) {
			$rc = ciniki_systemdocs_updateModuleFunction($ciniki, $mod_function['package'], $mod_function['module'], $mod_function['type'], $mod_function['file'], 'php');
			if( $rc['stat'] != 'ok' ) {	
				return $rc;
			}
			$duperrors = array_merge($duperrors, $rc['duplicate_errors']);
		}
	}

	//
	// Check for deleted function
	//
	foreach($db_functions as $full_name => $db_function) {
		if( !isset($mod_functions[$full_name]) ) {
			//
			// Delete the function information
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_functions "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}

			//
			// Delete the function args
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_function_args "
				. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}

			//
			// Delete the function errors
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_function_errors "
				. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}

			//
			// Delete the function calls
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_function_calls "
				. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok', 'duplicate_errors'=>$duperrors);
}
?>

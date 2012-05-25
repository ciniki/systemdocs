<?php
//
// Description
// -----------
// This method will parse all the code in an API Module.
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
function ciniki_systemdocs_parseModuleCode($ciniki, $package, $module) {

	require_once($ciniki['config']['core']['modules_dir'] . '/systemdocs/private/parseScriptCode.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/systemdocs/private/parseDBCode.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/systemdocs/private/parseMethodCode.php');

	//
	// Setup return hash array
	//
	$rsp = array('package'=>$package, 'name'=>$module, 'errors'=>array());

	//
	// Check for scripts
	//
	$path = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/scripts';
	if( is_dir($path) ) {
		$rsp['scripts'] = array('filename'=>'/' . $package . '-api/' . $module . '/scripts',
			'package'=>$package, 
			'module'=>$module, 
			'type'=>'scripts',
			'scripts'=>array(),
			);
		$fp = opendir($path);
		while( $file = readdir($fp) ) {
			if( preg_match('/(.*)\.php$/', $file, $matches) ) {
				$rc = ciniki_systemdocs_parseScriptCode($ciniki, $package, $module, 'scripts', $matches[1]);
			}
		}	
	}

	//
	// Check for database schema and upgrade files
	//
	$path = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/db';
	if( is_dir($path) ) {
		$rsp['database'] = array('filename'=>'/' . $package . '-api/' . $module . '/db',
			'package'=>$package, 
			'module'=>$module, 
			'tables'=>array(),
			);
		$fp = opendir($path);
		while( $file = readdir($fp) ) {
			if( preg_match('/(.*)\.schema$/', $file, $matches) ) {
				$rc = ciniki_systemdocs_parseDBCode($ciniki, $package, $module, $matches[1]);
				array_push($rsp['database']['tables'], array('table'=>$rc['table']));
			}
			
		}	
	}

	//
	// Check for module documentation
	//


	//
	// Check for methods
	//
	$subdirs = array('scripts', 'public', 'private', 'cron');
	foreach($subdirs as $subdir) {
		$path = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/' . $subdir;
		if( !is_dir($path) ) {
			continue;
		}
		$fp = opendir($path);
		$rsp[$subdir] = array('filename'=>'/' . $package . '-api/' . $module . '/' . $subdir,
			'package'=>$package, 
			'module'=>$module, 
			'type'=>$subdir,
			'methods'=>array(),
			);
		while( $file = readdir($fp) ) {
			if( preg_match('/(.*)\.php$/', $file, $matches) ) {
				$rc = ciniki_systemdocs_parseMethodCode($ciniki, $package, $module, $subdir, $matches[1]);
				if( isset($rc['method']) ) {
					array_push($rsp[$subdir]['methods'], array('method'=>$rc['method']));
				}
				if( isset($rc['method']) && isset($rc['method']['errors']) ) {
					foreach($rc['method']['errors'] as $errid => $err) {
						$err['error']['method'] = $rc['method']['method'];
						$err['error']['type'] = $subdir;
						array_push($rsp['errors'], array('error'=>$err['error']));
					}
				}
			}
		}
	}

	return array('stat'=>'ok', 'module'=>$rsp);
}
?>

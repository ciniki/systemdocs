<?php
//
// Description
// -----------
// This method will parse code found in the scripts/ directory of a module.  These files
// are the entry points to the code, and should be treated differently.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// type:			The directory and type for the method.  (public, private, cron)
// script:			The name of the method to parse.
// 
// Returns
// -------
// <rsp stat="ok">
// </rsp>
function ciniki_systemdocs_parseScriptCode($ciniki, $package, $module, $type, $script) {

	$file = $ciniki['config']['core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $script . '.php';

	$rsp = array('filename'=>'/' . $package . '-mods/' . $module . '/' . $type . '/' . $script . '.php', 
		'package'=>$package, 
		'module'=>$module, 
		'name'=>$script, 
		'errors'=>array(),
		'calls'=>array(),
		'description'=>'',
		);


	$lines = file($file);
	//
	// FIXME: Parse header lines to get description documentation
	//


	//
	// Join the multiple lines of the file into one content block for searching
	//
	$contents = implode("\n", $lines);

	//
	// Find the error codes
	//
	if( preg_match_all('/array\(\'code\'=>\'(.*)\',\s*\'msg\'=>(\'|\")([^\'\"]+)(\'|\")(,\s*\'pmsg\'=>(\'|\")([^\'\"]+)(\'|\"))?/i', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {
			$err = array('code'=>$val[1], 'msg'=>$val[3]);
			if( isset($val[5]) ) {
				$err['pmsg'] = $val[7];
			}
			array_push($rsp['errors'], array('error'=>$err));
		}
	}

	//
	// Find all function calls
	//
	if( preg_match_all('/(([a-zA-Z0-9]+)_([^_]+)_([^_\(]+))\(([^\)]*)\)/', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {	
			// Check if method already exists
			$exists = 0;
			foreach($rsp['calls'] as $callnum => $call) {
				if( $call['call']['method'] == $val[1] ) { $exists = 1; }
			}
			if( $exists == 0 ) {
				$call = array('method'=>$val[1], 'package'=>$val[2], 'module'=>$val[3]);
				if( $val[4] == 'checkAccess' ) {
					$call['args'] = $val[5];
				}
				array_push($rsp['calls'], array('call'=>$call));
			}
		}
	}

	return array('stat'=>'ok', 'script'=>$rsp);
}
?>

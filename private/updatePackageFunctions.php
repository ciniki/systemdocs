<?php
//
// Description
// -----------
// This function will update the systemdocs database with the documentation from a package modules.
//
// Arguments
// ---------
// ciniki:
// package:			The package to parse the code from, eg: ciniki
// 
// Returns
// -------
// <rsp stat="ok"/>
//
function ciniki_systemdocs_updatePackageFunctions($ciniki, $package) {

	//
	// Check if package exists
	//
	if( !is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-api") ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'790', 'msg'=>'Package does not exist'));
	}

	//
	// Find all the modules for the package
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updateModuleFunctions');
	$fp = opendir($ciniki['config']['core']['root_dir'] . "/{$package}-api");
	while( $file = readdir($fp) ) {
		if($file[0] == '.' ) {
			continue;
		}
		if( is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-api/" . $file) ) {
			$rc = ciniki_systemdocs_updateModuleFunctions($ciniki, $package, $file);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>

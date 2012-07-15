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

	$duperrors = array();

	//
	// Find all the modules for the package
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updateModuleFunctions');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updateModuleDetails');
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
			$duperrors = array_merge($duperrors, $rc['duplicate_errors']);
			$rc = ciniki_systemdocs_updateModuleDetails($ciniki, $package, $file);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok', 'duplicate_errors'=>$duperrors);
}
?>

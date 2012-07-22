<?php
//
// Description
// -----------
// This method will parse the module .php files and process them into the systemdocs database.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:		(optional) the package to update the function documentation for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_systemdocs_updateFunctions($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No package specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];	

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.updateFunctions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update all packages, unless a specific package is an argument
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updatePackageFunctions');
	if( !isset($args['package']) && isset($ciniki['config']['core']['packages']) && $ciniki['config']['core']['packages'] != '' ) {
		$packages = preg_split('/,/', $ciniki['config']['core']['packages']);
		foreach($packages as $package) {
			$rc = ciniki_systemdocs_updatePackageFunctions($ciniki, $package);
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'786', 'msg'=>"Unable to update the package '$package'", 'err'=>$rc['err']));
			}
		}
	} else {
		$package = 'ciniki';
		if( isset($args['package']) && $args['package'] != '' ) {
			$package = $args['package'];
		}
		$rc = ciniki_systemdocs_updatePackageFunctions($ciniki, $package);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'787', 'msg'=>"Unable to update the package '$package'", 'err'=>$rc['err']));
		}
	}

	return array('stat'=>'ok');
}
?>

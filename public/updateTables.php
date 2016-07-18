<?php
//
// Description
// -----------
// This method will parse the module .schema files and process them into the systemdocs database.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:         (optional) The package to update the table documentation for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_systemdocs_updateTables($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'default'=>'ciniki', 'blank'=>'no', 'name'=>'Package'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];    

    //
    // Make suee this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.updateTables');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update all packages, unless a specific package is an argument
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updatePackageTables');
    if( !isset($args['package']) && isset($ciniki['config']['core']['packages']) && $ciniki['config']['core']['packages'] != '' ) {
        $packages = preg_split('/,/', $ciniki['config']['core']['packages']);
        foreach($packages as $package) {
            $rc = ciniki_systemdocs_updatePackageTables($ciniki, $package);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'788', 'msg'=>"Unable to update the package '$package'"));
            }
        }
    } else {
        $package = 'ciniki';
        if( isset($args['package']) && $args['package'] != '' ) {
            $package = $args['package'];
        }
        $rc = ciniki_systemdocs_updatePackageTables($ciniki, $package);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'789', 'msg'=>"Unable to update the package '$package'"));
        }
    }

    return array('stat'=>'ok');
}
?>

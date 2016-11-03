<?php
//
// Description
// -----------
// This method will return the list of packages.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
// <packages>
//  <package name="ciniki">
//  </package>
// </packages>
//
function ciniki_systemdocs_packages($ciniki) {
    //
    // Make suee this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.packages');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of packages and modules
    //
    $strsql = "SELECT DISTINCT package "
        . "FROM ciniki_systemdocs_api_functions "
        . "ORDER BY package "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'packages', 'fname'=>'package', 'name'=>'package', 'fields'=>array('name'=>'package')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.25', 'msg'=>'Unable to find any modules', 'err'=>$rc['err']));
    }
    if( !isset($rc['packages']) ) { 
        return array('stat'=>'ok', 'packages'=>array());
    }

    return array('stat'=>'ok', 'packages'=>$rc['packages']);
}
?>

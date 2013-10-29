<?php
//
// Description
// -----------
// This method will clear all documentation from the database.  This can be useful if it gets corrupt and needs
// to be reloaded.  Typically the documentation is only updated when the file changes on disk.
//
// Arguments
// ---------
// api_key:
// auth_token:
// package:			(optional) Only clear the documentation for a certain package.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_systemdocs_clear($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Package'), 
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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.clear');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$strsql = "DELETE FROM ciniki_systemdocs_api_functions ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'805', 'msg'=>'Unable to clear function documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_function_args ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'806', 'msg'=>'Unable to clear function argument documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_function_calls ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'807', 'msg'=>'Unable to clear function call documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_function_errors ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'808', 'msg'=>'Unable to clear function errors documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_tables ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'809', 'msg'=>'Unable to clear table documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_table_fields ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'810', 'msg'=>'Unable to clear table fields documentation', 'err'=>$rc['err']));
	}
	$strsql = "DELETE FROM ciniki_systemdocs_api_module_details ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'815', 'msg'=>'Unable to clear table fields documentation', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>

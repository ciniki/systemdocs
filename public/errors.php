<?php
//
// Description
// -----------
// This method will return a list of error codes and messages in descending order.
// The errors can be from a specific module, package or all errors.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:			(optional) The package to get the errors from.
// module:			(optional) The module to get the errors from.  If specified, the package must also be specified.
//
// Returns
// -------
// <errors>
//	<error package="ciniki" code="155" module="businesses" type="public" file="userRemove" msg="Unable to remove user" pmsg="" />
// </errors>
//
function ciniki_systemdocs_errors($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No package specified'), 
        'module'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No module specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];	

	if( isset($args['modules']) && !isset($args['package']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'817', 'msg'=>'Package not specified with module'));
	}

	//
	// Make suee this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.errors');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of errors
	//
	$strsql = "SELECT CONCAT_WS('-', ciniki_systemdocs_api_functions.package, code) AS eid, "
		. "ciniki_systemdocs_api_functions.id AS function_id, "
		. "ciniki_systemdocs_api_functions.package, "
		. "ciniki_systemdocs_api_functions.module, "
		. "ciniki_systemdocs_api_functions.type, "
		. "ciniki_systemdocs_api_functions.file, "
		. "ciniki_systemdocs_api_function_errors.code, "
		. "ciniki_systemdocs_api_function_errors.msg, "
		. "ciniki_systemdocs_api_function_errors.pmsg "
		. "FROM ciniki_systemdocs_api_functions, ciniki_systemdocs_api_function_errors "
		. "WHERE ciniki_systemdocs_api_functions.id = ciniki_systemdocs_api_function_errors.function_id "
		. "";
	if( isset($args['package']) ) {
		$strsql .= "AND ciniki_systemdocs_api_functions.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
	}
	if( isset($args['module']) ) {
		$strsql .= "AND ciniki_systemdocs_api_functions.package = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_systemdocs_api_functions.package, "
		. "ciniki_systemdocs_api_function_errors.code DESC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'errors', 'fname'=>'eid', 'name'=>'error', 
			'fields'=>array('function_id', 'package', 'code', 'module', 'type', 'file', 'msg', 'pmsg')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'818', 'msg'=>'Unable to find any errors', 'err'=>$rc['err']));
	}
	if( !isset($rc['errors']) ) {	
		return array('stat'=>'ok', 'errors'=>array());
	}

	return array('stat'=>'ok', 'errors'=>$rc['errors']);
}
?>

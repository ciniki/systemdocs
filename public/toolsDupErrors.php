<?php
//
// Description
// -----------
// This method will return a list of duplicate error codes, or errors which have
// the dup flag set to 'yes'.  When there is a duplicate error code in the same
// function the dup flags is set to 'yes', otherwise there will be 2 or more entries
// in the table with the same package and code.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:         (optional) The package to get the errors from.
// module:          (optional) The module to get the errors from.  If specified, the package must also be specified.
//
// Returns
// -------
// <errors>
//  <error package="ciniki" code="155" module="businesses" type="public" file="userRemove" msg="Unable to remove user" pmsg="" />
// </errors>
//
function ciniki_systemdocs_toolsDupErrors($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Package'), 
        'module'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Module'), 
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
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.toolsDupErrors');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT ciniki_systemdocs_api_function_errors.id AS eid, "
        . "ciniki_systemdocs_api_function_errors.package, "
        . "ciniki_systemdocs_api_functions.module, "
        . "ciniki_systemdocs_api_functions.type, "
        . "ciniki_systemdocs_api_functions.file, "
        . "ciniki_systemdocs_api_function_errors.code, "
        . "ciniki_systemdocs_api_function_errors.msg, "
        . "ciniki_systemdocs_api_function_errors.pmsg, "
        . "ciniki_systemdocs_api_function_errors.dup, "
        . "ciniki_systemdocs_api_function_errors.function_id "
        . "FROM ciniki_systemdocs_api_function_errors  "
        . "INNER JOIN (SELECT package, code, dup "
            . "FROM ciniki_systemdocs_api_function_errors "
            . "GROUP BY package, code HAVING COUNT(id) > 1 OR dup = 'yes') d "
        . "ON ciniki_systemdocs_api_function_errors.package = d.package AND ciniki_systemdocs_api_function_errors.code = d.code "
        . "LEFT JOIN ciniki_systemdocs_api_functions ON (ciniki_systemdocs_api_function_errors.function_id = ciniki_systemdocs_api_functions.id) "
        . "";
    $strsql .= "ORDER BY ciniki_systemdocs_api_function_errors.package, "
        . "ciniki_systemdocs_api_function_errors.code ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'errors', 'fname'=>'eid', 'name'=>'error', 
            'fields'=>array('function_id', 'package', 'module', 'type', 'file', 'code', 'msg', 'pmsg', 'dup')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.28', 'msg'=>'Unable to find any errors', 'err'=>$rc['err']));
    }
    if( !isset($rc['errors']) ) {   
        return array('stat'=>'ok', 'errors'=>array());
    }

    return array('stat'=>'ok', 'errors'=>$rc['errors']);
}
?>

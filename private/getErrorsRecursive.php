<?php
//
// Description
// -----------
// This function will recursively get all the errors possible for a function.
//
// Arguments
// ---------
// ciniki:
// function_id:         The database ID of the calling function.
// extended_only_flag:  Only select the errors from referenced functions, not the calling function.
// 
// Returns
// -------
// <errors>
//  <error package="ciniki" code="122" module="core" type="private" file="dbConnect" />
// </errors>
//
function ciniki_systemdocs_getErrorsRecursive($ciniki, $function_id, $extended_only_flag) {

    if( $extended_only_flag == 'yes' ) {
        $function_ids = array();
    } else {
        $function_ids = array($function_id);
    }
    $cur_function_ids = array($function_id);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    while(count($cur_function_ids) > 0 ) {
        $strsql = "SELECT ciniki_systemdocs_api_functions.id "
            . "FROM ciniki_systemdocs_api_function_calls, ciniki_systemdocs_api_functions "
            . "WHERE ciniki_systemdocs_api_function_calls.function_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $cur_function_ids) . ") "
            . "AND ciniki_systemdocs_api_function_calls.package = ciniki_systemdocs_api_functions.package "
            . "AND ciniki_systemdocs_api_function_calls.module = ciniki_systemdocs_api_functions.module "
            . "AND ciniki_systemdocs_api_function_calls.type = ciniki_systemdocs_api_functions.type "
            . "AND ciniki_systemdocs_api_function_calls.name = ciniki_systemdocs_api_functions.file "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.systemdocs', 'functions', 'id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cur_function_ids = array();
        foreach($rc['functions'] as $fid) {
            if( !in_array($fid, $function_ids) ) {
                array_push($function_ids, $fid);
                array_push($cur_function_ids, $fid);
            }
        }
    }

    if( count($function_ids) == 0 ) {
        return array('stat'=>'ok', 'errors'=>array());
    }

    //
    // Get the errors possible
    //
    $strsql = "SELECT CONCAT_WS('-', ciniki_systemdocs_api_functions.package, code) AS eid, "
        . "ciniki_systemdocs_api_functions.id AS function_id, "
        . "ciniki_systemdocs_api_functions.package, "
        . "ciniki_systemdocs_api_functions.module, "
        . "ciniki_systemdocs_api_functions.type, "
        . "ciniki_systemdocs_api_functions.file, "
        . "ciniki_systemdocs_api_functions.name, "
        . "ciniki_systemdocs_api_function_errors.code, "
        . "ciniki_systemdocs_api_function_errors.msg, "
        . "ciniki_systemdocs_api_function_errors.pmsg "
        . "FROM ciniki_systemdocs_api_functions, ciniki_systemdocs_api_function_errors "
        . "WHERE ciniki_systemdocs_api_function_errors.function_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $function_ids) . ") "
        . "AND ciniki_systemdocs_api_function_errors.function_id = ciniki_systemdocs_api_functions.id "
        . "";
    $strsql .= "ORDER BY ciniki_systemdocs_api_functions.package, "
        . "ciniki_systemdocs_api_function_errors.code ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'errors', 'fname'=>'eid', 'name'=>'error', 
            'fields'=>array('function_id', 'package', 'code', 'module', 'type', 'file', 'name', 'msg', 'pmsg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'819', 'msg'=>'Unable to find any errors', 'err'=>$rc['err']));
    }

    return $rc;
}
?>

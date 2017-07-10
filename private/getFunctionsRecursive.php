<?php
//
// Description
// -----------
// This function will recursively get all the function calls required for a function.
//
// Arguments
// ---------
// ciniki:
// function_id:         The database ID of the calling function.
// extended_only_flag:  Only select the errors from referenced functions, not the calling function.
// 
// Returns
// -------
//
function ciniki_systemdocs_getFunctionsRecursive($ciniki, $function_id, $extended_only_flag) {

    if( $extended_only_flag == 'yes' ) {
        $function_ids = array();
    } else {
        $function_ids = array($function_id);
    }
    $cur_function_ids = array($function_id);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    $functions = array();
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
        return array('stat'=>'ok', 'functions'=>array());
    }

    //
    // Get the function information
    //
    $strsql = "SELECT id, name, package, module, type, file, suffix, publish "
        . "FROM ciniki_systemdocs_api_functions "
        . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $function_ids) . ") "
        . "ORDER BY package, module, file "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'strike.documentation', array(
        array('container'=>'functions', 'fname'=>'id', 'fields'=>array('id', 'name', 'package', 'module', 'type', 'file', 'suffix', 'publish')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['functions']) ) {
        $functions = $rc['functions'];
    } else {
        $functions = array();
    }

    return array('stat'=>'ok', 'functions'=>$functions);
}
?>

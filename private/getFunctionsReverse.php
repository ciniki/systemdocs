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
function ciniki_systemdocs_getFunctionsReverse($ciniki, $function_id) {

//    $function_ids = array($function_id);
    $function_ids = array();
    $cur_function_ids = array($function_id);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    $functions = array();
    while(count($cur_function_ids) > 0 ) {
        $strsql = "SELECT DISTINCT c.function_id AS id "
            . "FROM ciniki_systemdocs_api_functions AS f1, ciniki_systemdocs_api_function_calls AS c "
            . "WHERE f1.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $cur_function_ids) . ") "
            . "AND f1.package = c.package "
            . "AND f1.module = c.module "
            . "AND f1.file = c.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.systemdocs', 'functions', 'id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cur_function_ids = array();
        foreach($rc['functions'] as $fid) {
            if( !in_array($fid, $function_ids) ) {
//                if( $fid != function_id ) {
                    array_push($function_ids, $fid);
//                }
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

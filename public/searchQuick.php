<?php
//
// Description
// -----------
// Search the system documentation for the search string submitted.
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:        The search string to look for.
// limit:               The number of results to return.
// 
// Returns
// -------
// <results>
//  <result type="function" id="214" package="ciniki" module="core" name="ciniki_core_dbUpdate" />
//  <result type="table" id="112" package="ciniki" module="core" name="ciniki_core_session_data" />
// </results>
//
function ciniki_systemdocs_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.searchQuick'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // If the search string is a number, then search for error code with that number
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    if( is_numeric($args['start_needle']) ) {
        $strsql = "SELECT ciniki_systemdocs_api_functions.id, "
            . "ciniki_systemdocs_api_functions.package, "
            . "ciniki_systemdocs_api_functions.module, "
            . "ciniki_systemdocs_api_functions.name, "
            . "'function' AS type "
            . "FROM ciniki_systemdocs_api_function_errors, ciniki_systemdocs_api_functions "
            . "WHERE ciniki_systemdocs_api_function_errors.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "AND ciniki_systemdocs_api_function_errors.function_id = ciniki_systemdocs_api_functions.id "
            . "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
            array('container'=>'results', 'fname'=>'id', 'fields'=>array('type', 'id', 'package', 'module', 'name')),
            ));
        return $rc;
    }

    //
    // Search the function names
    //
    $rsp = array('stat'=>'ok', 'results'=>array());
    $strsql = "SELECT ciniki_systemdocs_api_functions.id, "
        . "ciniki_systemdocs_api_functions.package, "
        . "ciniki_systemdocs_api_functions.module, "
        . "ciniki_systemdocs_api_functions.name, "
        . "'function' AS type "
        . "FROM ciniki_systemdocs_api_functions "
        . "WHERE ciniki_systemdocs_api_functions.file LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'results', 'fname'=>'id', 'fields'=>array('type', 'id', 'package', 'module', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['results']) ) {
        $rsp['results'] = $rc['results'];
    }

    //
    // Search the table and field names
    //
    $strsql = "SELECT ciniki_systemdocs_api_tables.id, "
        . "ciniki_systemdocs_api_tables.package, "
        . "ciniki_systemdocs_api_tables.module, "
        . "ciniki_systemdocs_api_tables.name, "
        . "'table' AS type "
        . "FROM ciniki_systemdocs_api_table_fields, ciniki_systemdocs_api_tables "
        . "WHERE ("
            . "ciniki_systemdocs_api_table_fields.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "AND ciniki_systemdocs_api_table_fields.table_id = ciniki_systemdocs_api_tables.id "
            . ") "
        . "OR ciniki_systemdocs_api_tables.name LIKE '%_" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'results', 'fname'=>'id', 'fields'=>array('type', 'id', 'package', 'module', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['results']) ) {
        $rsp['results'] = array_merge($rsp['results'], $rc['results']);
    }

    return $rsp;
}
?>

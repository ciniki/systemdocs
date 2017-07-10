<?php
//
// Description
// -----------
// This method will return the list of table which have a blank field descriptions.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:         (optional) The package to get the errors from.
//
// Returns
// -------
// <tables>
//  <table id="34" package="ciniki" name="ciniki_artcatalog" />
// </tables>
//
function ciniki_systemdocs_toolsTableBlankFields($ciniki) {

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
    // Make suee this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.toolsTableBlankFields');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT ciniki_systemdocs_api_tables.id, "
        . "ciniki_systemdocs_api_tables.package, ciniki_systemdocs_api_tables.name "
        . "FROM ciniki_systemdocs_api_table_fields, ciniki_systemdocs_api_tables "
        . "WHERE ciniki_systemdocs_api_table_fields.description = '' "
        . "AND ciniki_systemdocs_api_table_fields.table_id = ciniki_systemdocs_api_tables.id "
        . "";
    if( isset($args['package']) && $args['package'] != '' ) {
        $strsql .= "AND ciniki_systemdocs_api_tables.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
    }
    $strsql .= "ORDER BY ciniki_systemdocs_api_tables.package, "
        . "ciniki_systemdocs_api_tables.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'tables', 'fname'=>'id', 'fields'=>array('id', 'package', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.35', 'msg'=>'Unable to find any tables', 'err'=>$rc['err']));
    }
    if( !isset($rc['tables']) ) {   
        return array('stat'=>'ok', 'tables'=>array());
    }

    return array('stat'=>'ok', 'tables'=>$rc['tables']);
}
?>

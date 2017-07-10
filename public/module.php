<?php
//
// Description
// -----------
// This method will return a list of attributed for a module.  The list of tables, functions and errors.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:     The package the module is located in.
// module:      The module to return the information for.
//
// Returns
// -------
// <module name='artcatalog'>
//  <tables>
//      <table name="ciniki_artcatalog" />
//  </tables>
//  <scripts>
//      <script name='index' package="ciniki" module="core" type="scripts" file="rest" suffix="php" publish="yes" />
//  </scripts>
//  <public>
//      <function name="ciniki_artcatalog_get" package="ciniki" module="artcatalog" type="public" file="get" suffix="php" />
//  </public>
//  <private>
//      <function name="ciniki_artcatalog_checkAccess" package="ciniki" module="artcatalog" type="private" file="checkAccess" suffix="php" />
//  </private>
//  <cron>
//      <function name="ciniki_artcatalog_cron_emailXLSBackup" package="ciniki" module="artcatalog" type="cron" file="get" suffix="php" />
//  </cron>
//  <web>
//      <function name="ciniki_artcatalog_web_list" package="ciniki" module="artcatalog" type="web" file="list" suffix="php" />
//  </web>
// </module>
//
function ciniki_systemdocs_module($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Package'), 
        'module'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Module'), 
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
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.module');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rsp = array('stat'=>'ok', 'tables'=>array(), 'public'=>array(), 'private'=>array());

    //
    // Get any detail information for the module
    //
    $strsql = "SELECT detail_key, html_details "
        . "FROM ciniki_systemdocs_api_module_details "
        . "WHERE package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
        . "AND module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'details', 'fname'=>'detail_key', 'fields'=>array('details'=>'html_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) ) {
        foreach($rc['details'] as $detail => $details) {
            $rsp[$detail] = $details['html_details'];
        }
    }
    
    //
    // Get the list of tables for this module
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_systemdocs_api_tables "
        . "WHERE package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
        . "AND module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'tables', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tables']) ) {
        $rsp['tables'] = $rc['tables'];
    }

    //
    // Get the list of functions for this module
    //
    $strsql = "SELECT id, name, package, module, type, file, suffix, publish "
        . "FROM ciniki_systemdocs_api_functions "
        . "WHERE package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
        . "AND module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' "
//      . "AND type <> 'scripts' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'types', 'fname'=>'type', 'fields'=>array('name'=>'type')),
        array('container'=>'functions', 'fname'=>'id', 'fields'=>array('id', 'name', 'package', 'module', 'type', 'file', 'suffix', 'publish')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['types']) ) {
        foreach($rc['types'] as $tnum => $type) {
            $rsp[$type['name']] = $type['functions'];
        }
    }

    return $rsp;
}
?>

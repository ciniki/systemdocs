<?php
//
// Description
// -----------
// This method will return a list of modules which do not contain a docs/overview.txt file.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:         (optional) The package to get the errors from.
//
// Returns
// -------
// <modules>
//  <module package="ciniki" name="artcatalog" />
// </modules>
//
function ciniki_systemdocs_toolsNoOverview($ciniki) {

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
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.toolsNoOverview');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT DISTINCT IFNULL(ciniki_systemdocs_api_module_details.details, '') AS overview, "
        . "CONCAT_WS('.', ciniki_systemdocs_api_functions.package, ciniki_systemdocs_api_functions.module) AS mid, "
        . "ciniki_systemdocs_api_functions.package, ciniki_systemdocs_api_functions.module "
        . "FROM ciniki_systemdocs_api_functions "
        . "LEFT JOIN ciniki_systemdocs_api_module_details ON (ciniki_systemdocs_api_functions.package = ciniki_systemdocs_api_module_details.package "
            . "AND ciniki_systemdocs_api_functions.module = ciniki_systemdocs_api_module_details.module "
            . "AND ciniki_systemdocs_api_module_details.detail_key = 'overview' ) "
        . "";
    if( isset($args['package']) && $args['package'] != '' ) {
        $strsql .= "AND ciniki_systemdocs_api_functions.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
    }
    $strsql .= "ORDER BY ciniki_systemdocs_api_functions.package, "
        . "ciniki_systemdocs_api_functions.module, ciniki_systemdocs_api_functions.file "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'modules', 'fname'=>'mid', 'name'=>'module', 
            'fields'=>array('package', 'name'=>'module', 'overview')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'641', 'msg'=>'Unable to find any modules', 'err'=>$rc['err']));
    }
    if( !isset($rc['modules']) ) {  
        return array('stat'=>'ok', 'modules'=>array());
    }

    foreach($rc['modules'] as $mnum => $module) {
        if( $module['module']['overview'] != '' ) {
            unset($rc['modules'][$mnum]);
        }
    }

    return array('stat'=>'ok', 'modules'=>$rc['modules']);
}
?>

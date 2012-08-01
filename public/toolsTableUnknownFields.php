<?php
//
// Description
// -----------
// This method will return a list of tables which have UNKNOWN field types.
//
// Arguments
// ---------
// api_key:
// auth_token:
// package:			(optional) The package to get the errors from.
//
// Returns
// -------
// <tables>
//	<table id="34" package="ciniki" name="ciniki_artcatalog" />
// </tables>
//
function ciniki_systemdocs_toolsTableUnknownFields($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No package specified'), 
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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.toolsTableUnknownFields');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "SELECT ciniki_systemdocs_api_tables.id, "
		. "ciniki_systemdocs_api_tables.package, ciniki_systemdocs_api_tables.name "
		. "FROM ciniki_systemdocs_api_table_fields, ciniki_systemdocs_api_tables "
		. "WHERE (ciniki_systemdocs_api_table_fields.type = 'UNKNOWN' OR ciniki_systemdocs_api_table_fields.type = '') "
		. "AND ciniki_systemdocs_api_table_fields.table_id = ciniki_systemdocs_api_tables.id "
		. "";
	if( isset($args['package']) && $args['package'] != '' ) {
		$strsql .= "AND ciniki_systemdocs_api_tables.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_systemdocs_api_tables.package, "
		. "ciniki_systemdocs_api_tables.name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
		array('container'=>'tables', 'fname'=>'id', 'name'=>'table', 
			'fields'=>array('id', 'package', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'636', 'msg'=>'Unable to find any tables', 'err'=>$rc['err']));
	}
	if( !isset($rc['tables']) ) {	
		return array('stat'=>'ok', 'tables'=>array());
	}

	return array('stat'=>'ok', 'tables'=>$rc['tables']);
}
?>

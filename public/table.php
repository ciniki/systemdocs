<?php
//
// Description
// -----------
// This method will return the information about a database table, and the fields.
// 
// Returns
// -------
// <table name="artcatalog" package="ciniki" module="artcatalog" description="" create_sql="" version="1.01">
//	<fields>
//		<field name="id" description="" type="" indexed=""/>
//	</fields>
// </table>
//
function ciniki_systemdocs_table($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'table_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No table specified'), 
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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.table');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of tables for this module
	//
	$strsql = "SELECT ciniki_systemdocs_api_tables.id AS table_id, "
		. "package, module, ciniki_systemdocs_api_tables.name AS table_name, "
		. "ciniki_systemdocs_api_tables.html_description AS table_description, create_sql, version, "
		. "ciniki_systemdocs_api_table_fields.id AS field_id, "
		. "ciniki_systemdocs_api_table_fields.name AS field_name, "
		. "ciniki_systemdocs_api_table_fields.html_description AS field_description, "
		. "ciniki_systemdocs_api_table_fields.type, "
		. "ciniki_systemdocs_api_table_fields.indexed "
		. "FROM ciniki_systemdocs_api_tables "
		. "LEFT JOIN ciniki_systemdocs_api_table_fields ON (ciniki_systemdocs_api_tables.id = ciniki_systemdocs_api_table_fields.table_id) "
		. "WHERE ciniki_systemdocs_api_tables.id = '" . ciniki_core_dbQuote($ciniki, $args['table_id']) . "' "
		. "ORDER BY ciniki_systemdocs_api_tables.name, ciniki_systemdocs_api_table_fields.sequence "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'tables', 'fname'=>'table_id', 'name'=>'table',
			'fields'=>array('id'=>'table_id', 'name'=>'table_name', 'package', 'module', 'description'=>'table_description', 'create_sql', 'version')),
		array('container'=>'fields', 'fname'=>'field_id', 'name'=>'field',
			'fields'=>array('id'=>'field_id', 'name'=>'field_name', 'description'=>'field_description', 'type', 'indexed')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tables'][0]['table']) ) {
		return array('stat'=>'ok', 'table'=>$rc['tables'][0]['table']);
	}
	
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'802', 'msg'=>'Unable to find table'));
}
?>

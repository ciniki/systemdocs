<?php
//
// Description
// -----------
// This method will return the list of packages and their database tables.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:		(optional) The package to get the tables for.
//
// Returns
// -------
// <packages>
//	<package name="ciniki">
//		<tables>
//			<table id="29" name="ciniki_artcatalog" module="artcatalog" version="v1.01"/>
//		</tables>
//	</package>
// </packages>
//
function ciniki_systemdocs_tables($ciniki) {

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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.tables');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of packages and modules
	//
	$strsql = "SELECT DISTINCT id, package, module, name, version "
		. "FROM ciniki_systemdocs_api_tables "
		. "";
	if( isset($args['package']) ) {
		$strsql .= "WHERE package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
	}
	$strsql .= "ORDER BY package, module, name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'packages', 'fname'=>'package', 'name'=>'package', 'fields'=>array('name'=>'package')),
		array('container'=>'tables', 'fname'=>'name', 'name'=>'table', 'fields'=>array('id', 'name', 'package', 'module', 'version')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'816', 'msg'=>'Unable to find any modules', 'err'=>$rc['err']));
	}
	if( !isset($rc['packages']) ) {	
		return array('stat'=>'ok', 'packages'=>array());
	}

	return array('stat'=>'ok', 'packages'=>$rc['packages']);
}
?>

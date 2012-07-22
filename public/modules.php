<?php
//
// Description
// -----------
// This method will return the list of packages and modules.
//
// Arguments
// ---------
// api_key:
// auth_token:
// package:		(optional) The package to get the modules for.
// 
// Returns
// -------
// <packages>
//	<package name="ciniki">
//		<modules>
//			<module name="artcatalog" />
//		</modules>
//	</package>
// </packages>
//
function ciniki_systemdocs_modules($ciniki) {

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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.modules');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of packages and modules
	//
	$strsql = "SELECT DISTINCT f.package, f.module, d1.details AS proper_name, d2.details AS public "
		. "FROM ciniki_systemdocs_api_functions AS f "
		. "LEFT JOIN ciniki_systemdocs_api_module_details AS d1 ON (f.package = d1.package AND f.module = d1.module AND d1.detail_key = 'name') "
		. "LEFT JOIN ciniki_systemdocs_api_module_details AS d2 ON (f.package = d2.package AND f.module = d2.module AND d2.detail_key = 'public') "
		. "";
	if( isset($args['package']) ) {
		$strsql .= "WHERE f.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
	}
	$strsql .= "ORDER BY f.package, f.module "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'packages', 'fname'=>'package', 'name'=>'package', 'fields'=>array('name'=>'package')),
		array('container'=>'modules', 'fname'=>'module', 'name'=>'module', 'fields'=>array('name'=>'module', 'package', 'proper_name', 'public')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'797', 'msg'=>'Unable to find any modules', 'err'=>$rc['err']));
	}
	if( !isset($rc['packages']) ) {	
		return array('stat'=>'ok', 'packages'=>array());
	}

	return array('stat'=>'ok', 'packages'=>$rc['packages']);
}
?>

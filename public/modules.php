<?php
//
// Description
// -----------
// This method will return the list of packages and modules.
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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.updateTables');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of packages and modules
	//
	$strsql = "SELECT DISTINCT package, module "
		. "FROM ciniki_systemdocs_api_functions "
		. "ORDER BY package, module "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'packages', 'fname'=>'package', 'name'=>'package', 'fields'=>array('name'=>'package')),
		array('container'=>'modules', 'fname'=>'module', 'name'=>'module', 'fields'=>array('name'=>'module', 'package')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'797', 'msg'=>'Unable to find any modules', 'err'=>$rc['err']));
	}
	if( !isset($rc['packages']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'798', 'msg'=>'Unable to find any modules'));
	}

	return array('stat'=>'ok', 'packages'=>$rc['packages']);
}
?>

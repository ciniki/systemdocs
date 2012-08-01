<?php
//
// Description
// -----------
// This method will return a list of available error codes for the packages.
// These are error codes which have not been used in the code.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// package:			(optional) The package to get the errors from. *future*
//
// Returns
// -------
// <packages>
//	<package name="ciniki">
// 		<gaps>
//			<error package="ciniki" code="155" />
// 		</gaps>
//	</package>
// </packages>
//
function ciniki_systemdocs_toolsGapErrors($ciniki) {

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
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.toolsGapErrors');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "SELECT ciniki_systemdocs_api_function_errors.id AS eid, "
		. "ciniki_systemdocs_api_function_errors.package, "
		. "ciniki_systemdocs_api_function_errors.code "
		. "FROM ciniki_systemdocs_api_function_errors  "
		. "";
	if( isset($args['package']) && $args['package'] != '' ) {
		$strsql .= "WHERE ciniki_systemdocs_api_function_errors.package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_systemdocs_api_function_errors.package, "
		. "ciniki_systemdocs_api_function_errors.code ASC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.systemdocs', array(
		array('container'=>'packages', 'fname'=>'package', 'name'=>'package',
			'fields'=>array('name'=>'package')),
		array('container'=>'errors', 'fname'=>'eid', 'name'=>'error', 
			'fields'=>array('package', 'code')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'811', 'msg'=>'Unable to find any errors', 'err'=>$rc['err']));
	}
	if( !isset($rc['packages']) ) {
		return array('stat'=>'ok', 'packages'=>array());
	}
	
	$rsp = array('stat'=>'ok', 'packages'=>array());
	foreach($rc['packages'] as $pnum => $package) {	
		$prev_code = 0;
		$rsp['packages'][$pnum] = array('package'=>array('name'=>$package['package']['name'], 'gaps'=>array()));
		foreach($package['package']['errors'] as $enum => $error) {
			if( ($error['error']['code'] - $prev_code) > 1 ) {
				for($i=$prev_code+1;$i<$error['error']['code'];$i++) {
					array_push($rsp['packages'][$pnum]['package']['gaps'], 
						array('error'=>array('package'=>$package['package']['name'], 'code'=>$i)));
				}
			}
			$prev_code = $error['error']['code'];
		}
	}

	return $rsp;
}
?>

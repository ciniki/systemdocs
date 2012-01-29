<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_systemdocs_parseAll($ciniki) {

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/documentation/private/checkAccess.php');
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.documentation.parseAll');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// FIXME: Add code to check for other packages, and parse them as well
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/documentation/private/parsePackageCode.php');
	$rc = ciniki_systemdocs_parsePackageCode($ciniki, 'ciniki');

	return array('stat'=>'ok', 'packages'=>array('package'=>$rc['package']));
}
?>

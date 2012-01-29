<?php
//
// Description
// -----------
// This method will return a complete list of errors for a package, and 
// 
//
// API Arguments
// -------------
// package:			The package name to get the errors for.  eg: ciniki
// 
// Returns
// -------
// <rsp stat='ok'>
// 	<errors>
// 		<error package='ciniki' code='456' msg='Error message' pmsg='Private error message, for coders' />
// 	</errors>
// </rsp>
//
function ciniki_systemdocs_packageErrors($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'default'=>'ciniki', 'blank'=>'no', 'errmsg'=>'No package specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];	

	//
	// Make suee this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/systemdocs/private/checkAccess.php');
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.packageErrors');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// FIXME: Add code to check for other packages, and parse them as well
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/systemdocs/private/parsePackageCode.php');
	$rc = ciniki_systemdocs_parsePackageCode($ciniki, $args['package']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Sort the errors
	//
	uasort($rc['package']['errors'], function($a, $b) { 
		if( $a['error']['code'] == $b['error']['code'] ) { 
			return 0;
		} 
		return $a['error']['code'] < $b['error']['code'] ? -1 : 1;
	});

	//
	// Find duplicate errors
	//
	$prev_code = '';
	$prev_errnum = 0;
	$rc['package']['duperrors'] = array();
	foreach($rc['package']['errors'] as $errnum => $error) {
		if( $rc['package']['errors'][$errnum]['error']['code'] == $prev_code ) {
			$rc['package']['errors'][$prev_errnum]['error']['dup'] = 'yes';
			$rc['package']['errors'][$errnum]['error']['dup'] = 'yes';
			if( $prev_errnum > -1 ) {
				array_push($rc['package']['duperrors'], $rc['package']['errors'][$prev_errnum]);
			}
			array_push($rc['package']['duperrors'], $rc['package']['errors'][$errnum]);
			$prev_errnum = -1;
		} else {
			$prev_errnum = $errnum;
		}
		
		$prev_code = $error['error']['code'];
	}

	return array('stat'=>'ok', 'errors'=>$rc['package']['errors'], 'duperrors'=>$rc['package']['duperrors']);
}
?>

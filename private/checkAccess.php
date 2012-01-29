<?php
//
// Description
// -----------
// This function will check 
//
// Arguments
// ---------
// ciniki:
// method:			The method making the request.
// 
// Returns
// -------
//
function ciniki_systemdocs_checkAccess($ciniki, $method) {

	//
	// Only sysadmins are allowed right now
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'454', 'msg'=>'Access denied.'));
}
?>

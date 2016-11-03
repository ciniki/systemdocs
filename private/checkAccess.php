<?php
//
// Description
// -----------
// This method is a placeholder for checking the access permissions
// to the systemdocs module.  Currently only sysadmins are allowed access.
//
// Arguments
// ---------
// ciniki:
// method:          The method making the request.
// 
// Returns
// -------
// <rsp stat="ok" />
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
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.1', 'msg'=>'Access denied.'));
}
?>

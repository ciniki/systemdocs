<?php
//
// Description
// -----------
// This method will return a complete list of errors for a package.
// Duplicate errors are any error codes that appear more than once in a package.
//
// Notes
// -----
// This method will parse all the code and does not use the database of parsed code.
// It can act as a double check, or from the command line while coding without having
// to always update the docs first.
//
// Arguments
// ---------
// api_key:
// auth_token:
// package:         (optional) The package name to get the errors for.  eg: ciniki
//                  If no package specified, it defaults to package ciniki.
// 
// Returns
// -------
// <rsp stat='ok'>
//      <errors>
//          <error package='ciniki' code='456' msg='Error message' pmsg='Private error message, for coders' />
//      </errors>
//      <duperrors>
//          <error package='ciniki' code='456' msg='Error message' pmsg='Private error message, for coders' />
//      </duperrors>
// </rsp>
//
function ciniki_systemdocs_packageErrors($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'package'=>array('required'=>'no', 'default'=>'ciniki', 'blank'=>'no', 'name'=>'Package'), 
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
    $rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.packageErrors');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // FIXME: Add code to check for other packages, and parse them as well
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'parsePackageCode');
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

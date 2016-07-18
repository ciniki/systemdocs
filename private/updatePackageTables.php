<?php
//
// Description
// -----------
// This function will parse the database table .schema files and update the database 
// tables ciniki_systemdocs_api_tables, ciniki_systemdocs_api_table_fields.
//
// Arguments
// ---------
// ciniki:
// package:         The package to parse the code from, eg: ciniki
// 
// Returns
// -------
// <rsp stat="ok"/>
//
function ciniki_systemdocs_updatePackageTables($ciniki, $package) {

    //
    // Check if package exists
    //
    if( !is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-mods") ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'784', 'msg'=>'Package does not exist'));
    }

    //
    // Find all the modules for the package
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'updateModuleTables');
    $fp = opendir($ciniki['config']['core']['root_dir'] . "/{$package}-mods");
    while( $file = readdir($fp) ) {
        if($file[0] == '.' ) {
            continue;
        }
        if( is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-mods/" . $file) ) {
            $rc = ciniki_systemdocs_updateModuleTables($ciniki, $package, $file);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>

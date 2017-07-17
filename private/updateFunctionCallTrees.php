<?php
//
// Description
// -----------
// This function updates the calltrees for each function in the documentation.
//
// Arguments
// ---------
// ciniki:
// 
// Returns
// -------
//
function ciniki_systemdocs_updateFunctionCallTrees($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'buildCallTree'); 

    //
    // Load the list of function calls and join to the functions to the called functions id.
    //
    $strsql = "SELECT c.function_id, c.package, c.module, c.type, c.name, f.id, f.name AS called_name "
        . "FROM ciniki_systemdocs_api_function_calls AS c, ciniki_systemdocs_api_functions AS f "
        . "WHERE c.package = f.package "
        . "AND c.module = f.module "
        . "AND c.type = f.type "
        . "AND c.name = f.file "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'functions', 'fname'=>'function_id', 'fields'=>array('id'=>'function_id')),
        array('container'=>'calls', 'fname'=>'id', 'fields'=>array('id', 'package', 'module', 'type', 'name', 'called_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $calls = $rc['functions'];

    //
    // Load the list of functions and their calltrees
    //
    $strsql = "SELECT f.id, f.package, f.module, f.type, f.file, f.name, f.calltree, f.indirectcalls "
        . "FROM ciniki_systemdocs_api_functions AS f "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.systemdocs', array(
        array('container'=>'functions', 'fname'=>'id', 'fields'=>array('id', 'package', 'module', 'type', 'file', 'name', 'calltree', 'indirectcalls')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $functions = $rc['functions'];

    //
    // Build the calltree for each function and update if changed
    //
    $depth = 0;
    foreach($functions as $function) {
        $calltree = array(); 
        $indirect_calls = array(); 
        if( isset($calls[$function['id']]['calls']) ) {
            $rc = ciniki_systemdocs_buildCallTree($ciniki, $depth, $calls[$function['id']]['calls'], $calls, $indirect_calls);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['calltree']) ) {
                $calltree = $rc['calltree'];
            }
        }

//        $indirect_calls = array_unique($indirect_calls);
        uasort($indirect_calls, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        //
        // Check if needs updating
        //
        $update_sql = "";
        $s_calltree = serialize($calltree);
        if( $function['calltree'] != $s_calltree ) {
            $update_sql = "\n, calltree = '" . ciniki_core_dbQuote($ciniki, $s_calltree) . "' ";
        }
        $s_indirect_calls = serialize($indirect_calls);
        if( $function['indirectcalls'] != $s_indirect_calls ) {
            $update_sql = "\n, indirectcalls = '" . ciniki_core_dbQuote($ciniki, $s_indirect_calls) . "' ";
        }
        if( $update_sql != '' ) {
            $strsql = "UPDATE ciniki_systemdocs_api_functions SET last_updated = UTC_TIMESTAMP() " . $update_sql 
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $function['id']) . "' ";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.systemdocs');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>

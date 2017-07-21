<?php
//
// Description
// -----------
// This function recursively builds the call tree for a function.
//
// The calltree is an array of direct function calls for the calling function.
// Each function call in that array contains its details, and subsequenct calls it made if any.
//
// array(
//      array(
//          id
//          name
//          calltree => array(
//              array(id, name, calltree=>...),
//              )
//          ),
//      )
//
// Arguments
// ---------
// ciniki:
// depth:               The current depth of the call tree.
// call:                The function name that is making the calls in function_calls argument.
// function_calls:      The calls made by the function named in call.
// all_calls:           The complete list of all calls from the database.
// indirect_calls:      The list of indirect calls so a master list of required functions is available for functions.
// 
// Returns
// -------
//
function ciniki_systemdocs_buildCallTree($ciniki, $depth, $calling_name, $function_calls, $all_calls, &$indirect_calls) {

    $calltree = array();

    foreach($function_calls as $call) {
        $c = array('id'=>$call['id'], 'type'=>$call['type'], 'name'=>$call['called_name']);

        //
        // Add to the indirect list of not already there
        //
        if( !isset($indirect_calls[$call['id']]) ) {
            $indirect_calls[$call['id']] = array('id'=>$call['id'], 'type'=>$call['type'], 'name'=>$call['called_name']);
        }

        //
        // Skip if recursive
        //
        if( $calling_name == $call['called_name'] ) {
            continue;
        }

        if( $depth < 10 && isset($all_calls[$c['id']]['calls']) ) {
            $rc = ciniki_systemdocs_buildCallTree($ciniki, $depth+1, $call['called_name'], $all_calls[$call['id']]['calls'], $all_calls, $indirect_calls);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['calltree']) ) {
                $c['calls'] = $rc['calltree'];
            }
        }
        $calltree[] = $c;
    }
    
    return array('stat'=>'ok', 'calltree'=>$calltree);
}
?>

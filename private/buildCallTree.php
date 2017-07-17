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
// function_calls:      The calls made by a function.
// all_calls:           The complete list of all calls from the database.
// 
// Returns
// -------
//
function ciniki_systemdocs_buildCallTree($ciniki, $depth, $function_calls, $all_calls, &$indirect_calls) {

    $calltree = array();

    foreach($function_calls as $call) {
        $c = array('id'=>$call['id'], 'type'=>$call['type'], 'name'=>$call['called_name']);
        
        //
        // Add to the indirect list of not already there
        //
        if( !isset($indirect_calls[$call['id']]) ) {
            $indirect_calls[$call['id']] = array('id'=>$call['id'], 'type'=>$call['type'], 'name'=>$call['called_name']);
        }

        if( $depth < 10 && isset($all_calls[$c['id']]['calls']) ) {
            $rc = ciniki_systemdocs_buildCallTree($ciniki, $depth+1, $all_calls[$call['id']]['calls'], $all_calls, $indirect_calls);
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

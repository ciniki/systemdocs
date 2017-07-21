<?php
//
// Description
// -----------
// This function adds the function documentation to a pdf.
//
// Arguments
// ---------
// cdepth:          The current depth of the call tree
//
// Returns
// -------
//
function ciniki_systemdocs_pdfFunctionCallTree($ciniki, $business_id, &$pdf, $depth, $cdepth, $calls, &$referenced_calls, $args) {

    if( $calls === null ) {
        return array('stat'=>'ok');
    }

    $title_outputed = 'no';

    $w = array(0, $pdf->usable_width);
    $prefix = '';
    if( isset($pdf->calltree_indent) && $pdf->calltree_indent == 'blank' ) {
        if( $cdepth > 0 ) {
            $w[0] = ($cdepth * 8);
            $w[1] = $pdf->usable_width - $w[0];
        }
    } else {
        $prefix = substr(' - - - - - - - - - - - -', 0, ($cdepth*2));
    }
    foreach($calls as $call) {
        $skip = 'no';
        if( isset($pdf->calltree_skip) ) {
            foreach($pdf->calltree_skip as $sf) {
                if( strncmp($sf, $call['name'], strlen($sf)) == 0 ) {
                    $skip = 'yes';
                    break;
                }
            }
        }
        if( $skip == 'yes' ) {
            continue;
        }

        //
        // Check if title output yet
        //
        if( $title_outputed == 'no' && $cdepth == 0 && isset($args['title']) && $args['title'] != '' ) {
            $title_outputed = 'yes';
            $pdf->addTitle($depth, $args['title']);
        }

        //
        // Add to the referenced call list
        //
        if( !isset($referenced_calls[$call['id']]) ) {
            $referenced_calls[$call['id']] = array('id'=>$call['id'], 'name'=>$call['name']);
        }
            
        $pdf->SetFont('', '', 10);
        if( $w[0] > 0 ) {
            $pdf->Cell($w[0], 8, '', 0, 0, 'L', false);
        }
        if( isset($args['calltree_descriptions']) 
            && isset($args['calltree_descriptions'][$call['id']]['html_description']) 
            && $args['calltree_descriptions'][$call['id']]['html_description'] != ''
            ) {
//            $html = '<p style="padding-bottom: 0px;">' . $w[0] . '.' . $cdepth . ':' . $call['name'] . '</p>' 
              $html = preg_replace("/<p>/m", '<p style="line-height: 15px;color:#808080; margin:0px;"><span style="color:#000000; padding-bottom: 5px;"><b>' . $call['name'] . "</b></span><br />", $args['calltree_descriptions'][$call['id']]['html_description']);
            $pdf->writeHTMLCell($w[1], '', '', '', $html, 1, 1, false, true);
        } else {
//            $pdf->Cell($w[1], 8, $prefix . $call['type'] . '/' . $call['name'], 1, 1, 'L', false, $call['name']);
            $pdf->writeHTMLCell($w[1], 8, '', '', '<b>' . $call['type'] . '/' . $call['name'], 1, 1, false, true);
        }

        //
        // Check if this is a nofollow and skip immediately to next call
        //
        $follow = 'yes';
        if( isset($pdf->calltree_nofollow) ) {
            foreach($pdf->calltree_nofollow as $nf) {
                if( strncmp($nf, $call['name'], strlen($nf)) == 0 ) {
                    $follow = 'no';
                    break;
                }
            }
        }

        //
        // Check if subtrees
        //
        if( $follow == 'yes' && isset($call['calls']) ) {
            $rc = ciniki_systemdocs_pdfFunctionCallTree($ciniki, $business_id, $pdf, $depth, $cdepth+1, $call['calls'], $referenced_calls, $args);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok', 'is_content'=>$title_outputed);
}
?>

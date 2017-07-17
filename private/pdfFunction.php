<?php
//
// Description
// -----------
// This function adds the function documentation to a pdf.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_systemdocs_pdfFunction($ciniki, $business_id, &$pdf, $depth, $function, $args) {

    if( is_string($function) ) {
        //
        // Get the list of function details and args 
        //
        $strsql = "SELECT f.id, f.name, f.package, f.module, f.type, f.file, f.suffix, f.publish, f.html_description, f.calltree, f.indirectcalls, "
            . "a.id AS aid, a.name AS aname, a.html_description AS ahtml_description "
            . "FROM ciniki_systemdocs_api_functions AS f "
            . "LEFT JOIN ciniki_systemdocs_api_function_args AS a ON ("
                . "f.id = a.function_id "
                . ") ";
        if( is_numeric($function) ) {
            $strsql .= "WHERE f.id = '" . ciniki_core_dbQuote($ciniki, $function) . "' ";
        } else {
            $strsql .= "WHERE f.name = '" . ciniki_core_dbQuote($ciniki, $function) . "' ";
        }
        $strsql .= "ORDER BY a.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'strike.documentation', array(
            array('container'=>'functions', 'fname'=>'name', 'fields'=>array('id', 'name', 'package', 'module', 'type', 'file', 'suffix', 'publish', 'html_description', 'calltree', 'indirectcalls')),
            array('container'=>'args', 'fname'=>'aid', 'fields'=>array('id'=>'aid', 'name'=>'aname', 'html_description'=>'ahtml_description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $function_details = array();
        if( isset($rc['functions']) ) {
            $function = array_shift($rc['functions']);
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.46', 'msg'=>'Unable to find function ' . $function . ' to include'));
        }
    }

    if( isset($function['name']) && $function['name'] != '' ) {
        if( $pdf->getY() > ($pdf->getPageHeight() - $pdf->top_margin - $pdf->bottom_margin - 50) ) {
            $pdf->AddPage();
        }
        $pdf->addTitle($depth, $function['type'] . '/' . $function['name'], 'yes');
    }

    if( isset($function['html_description']) && $function['html_description'] != '' ) {
        $pdf->addHtml($depth, trim($function['html_description']));
    }

    //
    // Output the args
    //
    if( isset($function['args']) ) {
        if( $pdf->getY() > ($pdf->getPageHeight() - $pdf->top_margin - $pdf->bottom_margin - 50) ) {
            $pdf->AddPage();
        }
        $pdf->addTitle($depth + 1, 'Arguments');
        $html = $pdf->table_def;
        $html .= "<thead><tr>"
            . '<th bgcolor="' . $pdf->table_hbg . '" style="' . $pdf->table_hstyle . 'width: 30%;"><b>Argument</b></th>'
            . '<th bgcolor="' . $pdf->table_hbg . '" style="' . $pdf->table_hstyle . 'width: 70%;"><b>Description</b></th>'
            . '</tr></thead>';

        foreach($function['args'] as $arg) {
            $html .= '<tr nobr="true"><td style="' . $pdf->table_cstyle . 'width: 30%;">' . $arg['name'] . '</td>'
                . '<td style="' . $pdf->table_cstyle . 'width: 70%;">' . strip_tags($arg['html_description']) . '</td></tr>';
        }
        $html .= "</table>";

        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);
    }

    //
    // Output the function call tree
    //
    $referenced_calls = array();
    if( isset($function['calltree']) ) {
        $rc = ciniki_systemdocs_pdfFunctionCallTree($ciniki, $business_id, $pdf, $depth + 1, 0, unserialize($function['calltree']), $referenced_calls, array(
            'title'=>'Call Tree',
            'calltree_descriptions'=>(isset($args['calltree_descriptions']) ? $args['calltree_descriptions'] : array()),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['is_content'] == 'yes' ) {
            $pdf->Ln();
        }
    }

    return array('stat'=>'ok', 'referenced_calls'=>$referenced_calls);
}
?>

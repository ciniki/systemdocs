<?php
//
// Description
// -----------
// This function will add the error codes to a pdf document.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_systemdocs_pdfErrors($ciniki, $business_id, &$pdf, $depth, $errors, $args) {

    if( isset($args['title']) && $args['title'] != '' ) {
        $pdf->addTitle($depth, $args['title'], 'yes');
    }

    //
    // Add the errors codes
    //
    $w = array(50, $pdf->usable_width - 50);
    $pdf->SetFillColor($pdf->thead_bg);
    $pdf->SetFont('', 'B');
    $pdf->Cell($w[0], 6, 'Code/File', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Error', 1, 1, 'C', 1);
    $pdf->SetFont('');

    $fill = 0;
    foreach($errors as $err) {
        if( $pdf->getY() > ($pdf->getPageHeight() - $pdf->top_margin - $pdf->bottom_margin - 30) ) {
            $pdf->AddPage();
            if( isset($args['title']) && $args['title'] != '' ) {
                $pdf->addTitle($depth, $args['title'] . ' (continued)', 'no');
            }
            $pdf->SetFillColor($pdf->thead_bg);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Code/File', 1, 0, 'C', 1);
            $pdf->Cell($w[1], 6, 'Error', 1, 1, 'C', 1);
            $pdf->SetFont('');
            $pdf->SetFillColor($pdf->tfill1);
        }
        
        $fill = !$fill;
        $pdf->SetFillColor($fill ? $pdf->tfill1 : $pdf->tfill2);
        $pdf->MultiCell($w[0], 10, $err['eid'] . "\n" . $err['type'], 1, 'L', true, 0, '', '', true, 0, false, true, 0, 'T');
        $pdf->MultiCell($w[1], 10, $err['type'] . '/' . $err['file'], 1, 'L', true, 1, '', '', true, 0, false, true, 0, 'T');
    }

    return array('stat'=>'ok');
}
?>

<?php
//
// Description
// -----------
// This function will output a pdf document as a series of thumbnails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_systemdocs_pdfEnd($ciniki, $tnid, &$pdf, $args) {

    //
    // Add the Table of Contents if specified
    //
    if( isset($pdf->toc) && $pdf->toc == 'yes' ) {
        $pdf->addTOCPage();
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(0);
        $pdf->SetLineWidth(0.15);
        $pdf->SetDrawColor(51);
        $pdf->setCellPaddings(5,1,5,2);
        $pdf->MultiCell($pdf->usable_width, 5, 'Table of Contents', 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
        $pdf->setCellPaddings(0,0,0,0);
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', '', $pdf->member_font_size);
        $pdf->pagenumbers = 'no';
        $pdf->addTOC(($pdf->coverpage=='yes'?2:0), 'courier', '.', 'INDEX', '');
        $pdf->pagenumbers = 'yes';
        $pdf->endTOCPage();
    }

    return array('stat'=>'ok');
}
?>

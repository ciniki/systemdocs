<?php
//
// Description
// -----------
// This function adds the database table documentation to a pdf.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_systemdocs_pdfTables($ciniki, $business_id, &$pdf, $depth, $tables, $args) {

    if( isset($args['title']) && $args['title'] != '' ) {
        $pdf->addTitle($depth, $args['title'], 'yes');
    }

    $w = array(50, 0, $pdf->usable_width - 50);
    foreach($tables as $table) {
        if( $pdf->getY() > ($pdf->getPageHeight() - $pdf->top_margin - $pdf->bottom_margin - 50) ) {
            $pdf->AddPage();
            if( isset($args['title']) && $args['title'] != '' ) {
                $pdf->addTitle($depth, $args['title'] . ' (continued)', 'no');
            }
        }
        $pdf->addTitle($depth + 1, $table['name'], 'yes');
        if( isset($table['html_description']) && $table['html_description'] != '' ) {
            $pdf->addHtml($depth, $table['html_description']);
            $pdf->Ln(5);
        }

        $html = $pdf->table_def;
        $html .= "<thead><tr>"
            . '<th bgcolor="' . $pdf->table_hbg . '" style="' . $pdf->table_hstyle . 'width: 30%;"><b>Field/Type</b></th>'
            . '<th bgcolor="' . $pdf->table_hbg . '" style="' . $pdf->table_hstyle . 'width: 70%;"><b>Description</b></th>'
            . '</tr></thead>';

        foreach($table['fields'] as $field) {
            $html .= '<tr nobr="true"><td style="' . $pdf->table_cstyle . 'width: 30%;"><b>' . $field['name'] . '</b><br>' . $field['type'] . '</td>'
                . '<td style="' . $pdf->table_cstyle . 'width: 70%;">' . strip_tags($field['html_description']) . '</td></tr>';
//                . '<td style="' . $pdf->table_cstyle . 'width: 70%;">' . $field['html_description'] . '</td></tr>';
        }
        $html .= "</table>";

        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);
    }

    return array('stat'=>'ok');
}
?>

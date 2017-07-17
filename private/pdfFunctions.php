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
function ciniki_systemdocs_pdfFunctions($ciniki, $business_id, &$pdf, $depth, $functions, $args) {

    if( isset($args['title']) && $args['title'] != '' ) {
        $pdf->addTitle($depth, $args['title'], 'yes');
    }

    foreach($functions as $function) {
        $rc = ciniki_systemdocs_pdfFunction($ciniki, $business_id, $pdf, $depth + 1, $function, $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>

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
function ciniki_systemdocs_pdfStart($ciniki, $tnid, $args) {

    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheJPEG');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');

    //
    // Load all the PDF section generators
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfModule');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfTables');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunctions');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunction');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunctionCallTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfErrors');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfEnd');
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunctionArgs');
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunctionCalls');
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'pdfFunctionErrors');

    //
    // Load tenant details
    //
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Create a custom class for this document
    //
    class MYPDF extends TCPDF {
        public $tenant_name = '';
        public $title = '';
        public $pagenumbers = 'yes';
        public $coverpage = 'no';
        public $toc = 'no';
        public $toc_categories = 'yes';
        public $top_margin = 15;
        public $footer_height = 12;
        public $bottom_margin = 15;
        public $section_title = '';
        public $ssection_title = '';
        public $sssection_title = '';
        public $header_text = '';
        public $footer_text = '';
        public $usable_width = 180;
        public $fresh_page = 'yes';     // Flag to track if on a fresh page and if title should be at top or bumped down.
        public $section_title_font_size = 18;
        public $member_title_font_size = 14;
        public $member_font_size = 12;
        public $thead_bg = 224;
        public $tfill1 = 255;
        public $tfill2 = 232;
        public $table_hstyle = 'border: 0.1px solid #aaa; background: #ddd;';
        public $table_hbg = '#dddddd';
        public $table_cstyle = 'border: 0.1px solid #aaa;';
        public $table_def = '<table border="0" cellspacing="0" cellpadding="5" style="border: 0.1px solid #aaa;">';
        public $s = 0;
        public $ss = 0;
        public $sss = 0;
        public $calltree_indent = '';
        public $calltree_nofollow = array();
        public $calltree_skip = array();

        public function Header() {
//            if( $this->section_title != '' ) {
//                $this->SetFont('helvetica', 'B', 14);
//                $this->Cell(180, 8, $this->section_title, 'B', false, 'L', 0, '', 0, false, 'T', 'M');
//            }
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            // Set font
            if( $this->pagenumbers == 'yes' ) {
                $this->SetY(-15);
                $this->SetFont('helvetica', '', 10);
                $this->Cell(150, 8, $this->footer_text, 'T', false, 'L', 0, '', 0, false, 'T', 'M');
                $this->Cell(30, 8, $this->pageNo(), 'T', false, 'R', 0, '', 0, false, 'T', 'M');
            }
        }

        public function addTitle($depth, $title, $toc='no') {
            if( $depth == 1 ) {
                $this->section_title = $title;
                $this->ssection_title = '';
                $this->sssection_title = '';
                $this->s++;
                $this->ss = 0;
                $this->sss = 0;
                $this->AddPage();
                $this->SetFont('helvetica', 'B', '18');
                $this->MultiCell($this->usable_width, 14, $this->s . '. ' . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                if( $toc == 'yes' && $this->toc == 'yes' ) { 
                    $this->Bookmark($this->s . '. ' . $title, 0, 0, '', '');
                }
            } elseif( $depth == 2 ) {
                $this->ssection_title = $title;
                $this->sssection_title = '';
                $this->ss++;
                $this->sss = 0;
                if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                    $this->AddPage();
                }
                $this->SetFont('helvetica', 'B', '16');
                $this->MultiCell($this->usable_width, 12, $this->s . '.' . $this->ss . '. ' . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                if( $toc == 'yes' && $this->toc == 'yes' ) { 
                    $this->Bookmark($this->s . '.' . $this->ss . '. ' . $title, 1, 0, '', '');
                }
            } elseif( $depth == 3 ) {
                $this->sssection_title = $title;
                if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                    $this->AddPage();
                }
                $this->sss++;
                $this->SetFont('helvetica', 'B', '14');
                $this->MultiCell($this->usable_width, 10, $this->s . '.' . $this->ss . '.' . $this->sss . '. ' . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                if( $toc == 'yes' && $this->toc == 'yes' ) { 
//                    $this->Bookmark($this->s . '.' . $this->ss . '.' . $this->sss . '. ' . $title, 2, 0, '', '');
                }
            } else {
                if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                    $this->AddPage();
                }
                $this->SetFont('helvetica', 'B', '12');
                $this->MultiCell($this->usable_width, 8, $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            }
        }

        public function addHtml($depth, $content) { 
            $this->SetFont('helvetica', '', '10');
            $content = preg_replace("/<\/dd>/m", '<br></dd>', $content, -1, $c);
            $this->writeHTMLCell($this->usable_width, 10, '', '', '<style>p, ul, dt {color: #000000;} dd {color: #808080;}</style>' . $content, 0, 1, false, true, 'L');
//            $this->writeHTMLCell($this->usable_width, 10, '', '', preg_replace('/<p>/', '<p style="color: #808080;">', $content), 0, 1, false, true, 'L');
        }
    }

    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
    $pdf->setPageOrientation('P', false);
    // set margins
    $pdf->header_height = 0;
    $pdf->footer_height = 12;
    $pdf->top_margin = 15;
    $pdf->left_margin = 18;
    $pdf->right_margin = 18;
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetFooterMargin($pdf->footer_height);
    $pdf->SetHeaderMargin($pdf->header_height);
    $pdf->SetFooterMargin(0);
    $pdf->usable_width = 180;
    $pdf->section_title_font_size = 18;
    
    if( isset($args['title']) ) {
        $pdf->title = $args['title'];
        $pdf->footer_text = $args['title'];
    } else {
        $pdf->title = 'System Documentation';
        $pdf->footer_text = 'System Documenation';
    }
    $pdf->calltree_indent = isset($args['calltree_indent']) ? $args['calltree_indent'] : '';
    $pdf->calltree_nofollow = isset($args['calltree_nofollow']) ? $args['calltree_nofollow'] : array();
    $pdf->calltree_skip = isset($args['calltree_skip']) ? $args['calltree_skip'] : array();

    // Set PDF basics
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
//    $pdf->footer_text = $tenant_details['name'];
    $pdf->SetTitle($args['title']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // Set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(0);

    //
    // Check if coverpage is to be outputed
    //
    if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
        $pdf->coverpage = 'yes';
        $pdf->title = '';
        if( isset($args['title']) && $args['title'] != '' ) {
            $title = $args['title'];
        } else {
            $title = "System Documentation";
        }
        $pdf->pagenumbers = 'no';
        $pdf->AddPage('P');
        
        if( isset($args['coverpage-image']) && $args['coverpage-image'] > 0 ) {
            $img_box_width = $pdf->usable_width;
            if( $pdf->usable_width == 180 ) {
                $img_box_height = 150;
            } else {
                $img_box_height = 100;
            }
            $rc = ciniki_images_loadCacheJPEG($ciniki, $tnid, $args['coverpage-image'], 2000, 2000);
            if( $rc['stat'] == 'ok' ) {
                $image = $rc['image'];
                $pdf->SetLineWidth(0.25);
                $pdf->SetDrawColor(50);
                $img = $pdf->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 0, 'CT');
            }
            $pdf->SetY(-50);
        } else {
            $pdf->SetY(-100);
        }

        $pdf->SetFont('times', 'B', '30');
        $pdf->MultiCell($pdf->usable_width, 5, $title, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
        if( isset($args['subtitle']) ) {
            $pdf->SetFont('times', 'B', '24');
            $pdf->MultiCell($pdf->usable_width, 5, $args['subtitle'], 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
            $pdf->footer_text .= ' - ' . $args['subtitle'];
        }
        $pdf->endPage();
    }
    $pdf->pagenumbers = 'yes';

    //
    // Add the member items
    //
    $page_num = 1;
    $pdf->toc_categories = 'no';
    if( isset($args['toc']) && $args['toc'] == 'yes' ) {
        $pdf->toc = 'yes';
    }
//    if( count($categories) > 1 ) {
//        $pdf->toc_categories = 'yes';
//    }
/*    if( !isset($args['section-pagebreak']) || $args['section-pagebreak'] != 'yes' ) {
        // Start a new page
        $pdf->AddPage('P');
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(51);
        $pdf->SetLineWidth(0.15);
    }
    $pdf->fresh_page = 'yes';
    foreach($categories as $cid => $category) {
        $member_num = 1;
        if( isset($args['section-pagebreak']) && $args['section-pagebreak'] == 'yes' ) {
            // Start a new page
            $pdf->AddPage('P');
            $pdf->SetFillColor(255);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(51);
            $pdf->SetLineWidth(0.15);
            $pdf->fresh_page = 'yes';       // Reset
        }
        
        foreach($category['members'] as $mid => $member) {
            if( $member_num == 1 ) {
                $section_title = $category['name'];
            } else {
                $section_title = '';
            }

            $pdf->AddMember($member, $section_title);
            $member_num++;
            $pdf->fresh_page = 'no';
        }
    } 
    $pdf->endPage();
*/

    $pdf->SetCellPadding(1.5);
    $pdf->SetTextColor(0);
    $pdf->SetLineWidth(0.1);
    $pdf->SetDrawColor(200);
    $pdf->SetAutoPageBreak(TRUE, 18);

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>

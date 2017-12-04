<?php
//
// Description
// -----------
// This function will add the documentation for a module to the PDF.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_systemdocs_pdfModule($ciniki, $tnid, &$pdf, $depth, $package, $module, $args) {

    //
    // Get any detail information for the module
    //
    $mod = array();
    $strsql = "SELECT detail_key, html_details "
        . "FROM ciniki_systemdocs_api_module_details "
        . "WHERE package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
        . "AND module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
        . "ORDER BY package, module, detail_key "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'strike.documentation', array(
        array('container'=>'details', 'fname'=>'detail_key', 'fields'=>array('details'=>'html_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) ) {
        foreach($rc['details'] as $detail => $details) {
            $mod[$detail] = $details['details'];
        }
    }
    
    //
    // Get the list of tables for this module
    //
    $strsql = "SELECT t.id, t.package, t.module, t.name, t.html_description, t.create_sql, t.version, "
        . "f.id AS fid, f.name AS fname, f.html_description AS fhtml_description, f.type AS ftype "
        . "FROM ciniki_systemdocs_api_tables AS t "
        . "LEFT JOIN ciniki_systemdocs_api_table_fields AS f ON ("
            . "t.id = f.table_id "
            . ") "
        . "WHERE t.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
        . "AND t.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
        . "ORDER BY t.package, t.module, t.name, f.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'strike.documentation', array(
        array('container'=>'tables', 'fname'=>'id', 'fields'=>array('id', 'package', 'module', 'name', 'html_description', 'create_sql', 'version')),
        array('container'=>'fields', 'fname'=>'fid', 'fields'=>array('id'=>'fid', 'name'=>'fname', 'html_description'=>'fhtml_description', 'type'=>'ftype')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tables']) ) {
        $mod['tables'] = $rc['tables'];
    }

    //
    // Get the list of functions for this module
    //
    $strsql = "SELECT f.id, f.name, f.package, f.module, f.type, f.file, f.suffix, f.publish, f.html_description, f.calltree, f.indirectcalls, "
        . "a.id AS aid, a.name AS aname, a.flags AS aflags, a.html_description AS ahtml_description "
        . "FROM ciniki_systemdocs_api_functions AS f "
        . "LEFT JOIN ciniki_systemdocs_api_function_args AS a ON ("
            . "f.id = a.function_id "
            . ") "
        . "WHERE f.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
        . "AND f.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
//      . "AND type <> 'scripts' "
        . "ORDER BY f.type, f.name, a.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'strike.documentation', array(
        array('container'=>'types', 'fname'=>'type', 'fields'=>array('name'=>'type')),
        array('container'=>'functions', 'fname'=>'id', 'fields'=>array('id', 'name', 'package', 'module', 'type', 'file', 'suffix', 'publish', 'html_description', 'calltree', 'indirectcalls')),
        array('container'=>'args', 'fname'=>'aid', 'fields'=>array('id'=>'aid', 'name'=>'aname', 'flags'=>'aflags', 'html_description'=>'ahtml_description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $functions = array();
    if( isset($rc['types']) ) {
        foreach($rc['types'] as $tnum => $type) {
            $functions[$type['name']] = $type['functions'];
        }
    }

    //
    // Add the module name to the PDF
    //
    $module_name = (isset($mod['name']) ? $mod['name'] : ucwords($module));
    if( $depth > 0 ) {
        $pdf->addTitle($depth, ucwords($package) . ' - ' . $module_name, 'yes');
    }

    //
    // Add the overview to the PDF
    //
    if( isset($mod['overview']) && $mod['overview'] != '' ) {
        $pdf->addHTML($depth, $mod['overview']);
        $pdf->Ln(5);
    }

    //
    // Add the tables
    //
    if( isset($mod['tables']) ) {
        $rc = ciniki_systemdocs_pdfTables($ciniki, $tnid, $pdf, $depth+1, $mod['tables'], array('title'=>'Database Tables'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Add the public methods
    //
    if( isset($functions['public']) ) {
        $pdf->AddPage();
        $rc = ciniki_systemdocs_pdfFunctions($ciniki, $tnid, $pdf, $depth+1, $functions['public'], array('title'=>'Public Methods'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Add the private methods
    //
    if( isset($functions['private']) ) {
        $rc = ciniki_systemdocs_pdfFunctions($ciniki, $tnid, $pdf, $depth+1, $functions['private'], array('title'=>'Private Functions'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Add the hooks
    //
    if( isset($functions['hooks']) ) {
        $rc = ciniki_systemdocs_pdfFunctions($ciniki, $tnid, $pdf, $depth+1, $functions['hooks'], array('title'=>'Available Hooks'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Add the cron functions
    //
//    if( isset($functions['cron']) ) {
//        $pdf->addTxt($depth + 1, 'Cron ');
//        $rc = ciniki_systemdocs_pdfFunctions($ciniki, $tnid, $pdf, $depth+1, $functions['hooks']);
//        if( $rc['stat'] != 'ok' ) {
//            return $rc;
//        }
//    }

    //
    // Add the web functions
    //
    if( isset($functions['web']) ) {
        $pdf->AddPage();
        $rc = ciniki_systemdocs_pdfFunctions($ciniki, $tnid, $pdf, $depth + 1, $functions['web'], array('title'=>'Website Functions'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    if( isset($args['errors']) && $args['errors'] == 'module' ) {
        //
        // Load the errors
        //
        $strsql = "SELECT CONCAT_WS('.', e.package, e.module, e.code) AS eid, "
            . "f.id AS fid, "
            . "f.package, "
            . "f.module, "
            . "f.type, "
            . "f.file, "
            . "e.code, "
            . "e.msg, "
            . "e.pmsg "
            . "FROM ciniki_systemdocs_api_functions AS f, ciniki_systemdocs_api_function_errors AS e "
            . "WHERE f.id = e.function_id "
            . "AND f.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
            . "AND f.package = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
            . "ORDER BY f.package, f.module, e.code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.systemdocs', array(
            array('container'=>'errors', 'fname'=>'eid',
                'fields'=>array('eid', 'function_id', 'package', 'code', 'module', 'type', 'file', 'msg', 'pmsg')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.45', 'msg'=>'Unable to find any errors', 'err'=>$rc['err']));
        }

        //
        // Add the errors to the PDF
        //
        if( isset($rc['errors']) ) {
            $pdf->AddPage();
            $rc = ciniki_systemdocs_pdfErrors($ciniki, $tnid, $pdf, $depth + 1, $rc['errors'], array('title'=>'Error Codes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>

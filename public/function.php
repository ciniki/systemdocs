<?php
//
// Description
// -----------
// This method will return the attributes of a function, along with arguments, calls and errors.
// 
// Arguments
// ---------
// api_key:
// auth_token:
// function_id:			The database ID of the function.
// 
// Returns
// -------
// <function name="ciniki_artcatalog_get" description="" returns="" size="" lines="">
//	<args>
//		<argument name="artcatalog_id" description="" />
//	</args>
//	<calls>
//		<function name='index' package="ciniki" module="core" type="scripts" file="rest" suffix="php" />
//	</call>
//	<errors>
//		<error package="ciniki" code="121" msg="" pmsg="" />
//	</errors>
// </module>
//
function ciniki_systemdocs_function($ciniki) {

    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'function_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No function specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];	

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'checkAccess');
	$rc = ciniki_systemdocs_checkAccess($ciniki, 'ciniki.systemdocs.function');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$rsp = array('stat'=>'ok', 'tables'=>array(), 'public'=>array(), 'private'=>array());

	//
	// Get the base details for the function
	//
	$strsql = "SELECT id, name, package, module, type, file, suffix, html_description, returns, fsize, flines "
		. "FROM ciniki_systemdocs_api_functions "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['function_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'functions', 'fname'=>'id', 'name'=>'function',
			'fields'=>array('id', 'name', 'package', 'module', 'type', 'file', 'suffix', 
				'description'=>'html_description', 'returns', 'size'=>'fsize', 'lines'=>'flines')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['functions'][0]['function']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'804', 'msg'=>'Unable to find function'));
	}
	$function = $rc['functions'][0]['function'];

	//
	// Get the args for the function
	//
	$strsql = "SELECT id, name, options, html_description "
		. "FROM ciniki_systemdocs_api_function_args "
		. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $args['function_id']) . "' "
		. "ORDER BY sequence "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'args', 'fname'=>'id', 'name'=>'argument',
			'fields'=>array('id', 'name', 'options', 'description'=>'html_description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['args']) ) {
		$function['args'] = $rc['args'];
	} else {
		$function['args'] = array();
	}

	//
	// Get the calls for the function
	//
	$strsql = "SELECT ciniki_systemdocs_api_function_calls.id AS arg_id, "
		. "ciniki_systemdocs_api_function_calls.package, "
		. "ciniki_systemdocs_api_function_calls.module, "
		. "ciniki_systemdocs_api_function_calls.type, "
		. "ciniki_systemdocs_api_function_calls.name, "
		. "ciniki_systemdocs_api_function_calls.args, "
		. "ciniki_systemdocs_api_functions.id AS called_id, "
		. "ciniki_systemdocs_api_functions.name AS called_name "
		. "FROM ciniki_systemdocs_api_function_calls "
		. "LEFT JOIN ciniki_systemdocs_api_functions ON ( "
			. "ciniki_systemdocs_api_function_calls.package = ciniki_systemdocs_api_functions.package "
			. "AND ciniki_systemdocs_api_function_calls.module = ciniki_systemdocs_api_functions.module "
			. "AND ciniki_systemdocs_api_function_calls.type = ciniki_systemdocs_api_functions.type "
			. "AND ciniki_systemdocs_api_function_calls.name = ciniki_systemdocs_api_functions.file "
			. ") "
		. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $args['function_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'calls', 'fname'=>'arg_id', 'name'=>'function',
			'fields'=>array('id'=>'called_id', 'call'=>'called_name', 'args', 'package', 'module', 'type', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['calls']) ) {
		$function['calls'] = $rc['calls'];
	} else {
		$function['calls'] = array();
	}

	//
	// Get the function errors
	//
	$strsql = "SELECT id, package, code, msg, pmsg "
		. "FROM ciniki_systemdocs_api_function_errors "
		. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $args['function_id']) . "' "
		. "ORDER BY code "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'errors', 'fname'=>'id', 'name'=>'error',
			'fields'=>array('id', 'package', 'code', 'msg', 'pmsg')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['errors']) ) {
		$function['errors'] = $rc['errors'];
	} else {
		$function['errors'] = array();
	}

	//
	// Get the extended errors from all the functions which may be referenced directly 
	// or indirectly by the function
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'getErrorsRecursive');
	$rc = ciniki_systemdocs_getErrorsRecursive($ciniki, $args['function_id'], 'yes');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['errors']) ) {
		$function['extended_errors'] = $rc['errors'];
	} else {
		$function['extended_errors'] = array();
	}

	return array('stat'=>'ok', 'function'=>$function);
}
?>

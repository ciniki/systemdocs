<?php
//
// Description
// -----------
// This function will update the systemdocs database tables with a modules tables.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// 
// Returns
// -------
// <rsp stat="ok">
// </rsp>
function ciniki_systemdocs_updateModuleFunction($ciniki, $package, $module, $type, $file, $suffix) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'parseFunctionCode');

	//
	// Load all the function information from the database
	//
	$strsql = "SELECT id, status, package, module, type, file, suffix, name, description, returns, fsize, flines, last_updated "
		. "FROM ciniki_systemdocs_api_functions "
		. "WHERE package = '" . ciniki_core_dbQuote($ciniki, $package) . "' " 
		. "AND module = '" . ciniki_core_dbQuote($ciniki, $module) . "' " 
		. "AND type = '" . ciniki_core_dbQuote($ciniki, $type) . "' " 
		. "AND file = '" . ciniki_core_dbQuote($ciniki, $file) . "' " 
		. "AND suffix = '" . ciniki_core_dbQuote($ciniki, $suffix) . "' " 
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'systemdocs', 'function');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'792', 'msg'=>'Unable to locate function', 'err'=>$rc['err']));
	}
	$db_function = NULL;
	if( isset($rc['function']) ) {
		$db_function = $rc['function'];
		//
		// Get the function args
		//
		$strsql = "SELECT id, name, options, description "
			. "FROM ciniki_systemdocs_api_function_args "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'systemdocs', 'args', 'name');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'794', 'msg'=>'Unable to locate function arguments', 'err'=>$rc['err']));
		}
		$db_function['args'] = $rc['args'];

		//
		// Get the function calls
		//
		$strsql = "SELECT id, CONCAT_WS('_', package, module, type, name) AS fcall, package, module, type, name, args "
			. "FROM ciniki_systemdocs_api_function_calls "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'systemdocs', 'calls', 'fcall');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'795', 'msg'=>'Unable to locate function calls', 'err'=>$rc['err']));
		}
		$db_function['calls'] = $rc['calls'];

		//
		// Get the function errors
		//
		$strsql = "SELECT id, package, code, msg, pmsg "
			. "FROM ciniki_systemdocs_api_function_errors "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'systemdocs', 'errors', 'code');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'796', 'msg'=>'Unable to locate function errors', 'err'=>$rc['err']));
		}
		$db_function['errors'] = $rc['errors'];
	}

	//
	// parse the module file
	//
	$filename = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/' . $type . '/' . $file . '.' . $suffix;
	if( is_file($filename) ) {
		$rc = ciniki_systemdocs_parseFunctionCode($ciniki, $package, $module, $type, $file, $suffix);
		if( $rc['stat'] != 'ok' ) {	
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'793', 'msg'=>'Unable to parse function', 'err'=>$rc['err']));
		}
		$mod_function = $rc['function'];
	} else {
		$mod_function = NULL;
	}



	//
	// Check for updates
	//
	if( $mod_function != NULL ) {
		$function_id = 0;
		//
		// Insert or update the database
		//
		if( $db_function == NULL ) {
//			print "Adding: " . $mod_function['package'] . "_" . $mod_function['module'] . "_" . $mod_function['type'] . "_" . $mod_function['name'] . "\n";
			$strsql = "INSERT INTO ciniki_systemdocs_api_functions (status, package, module, type, file, suffix, name, "
				. "description, returns, fsize, flines, last_updated) VALUES ("
				. "1, "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['package']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['module']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['type']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['file']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['suffix']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['name']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['description']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['returns']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['size']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod_function['lines']) . "', "
				. "UTC_TIMESTAMP() "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$function_id = $rc['insert_id'];
		} else {
//			print "Updating: " . $mod_function['package'] . "_" . $mod_function['module'] . "_" . $mod_function['type'] . "_" . $mod_function['name'] . "\n";
			$function_id = $db_function['id'];
			$strsql = "";
			if( $mod_function['description'] != $db_function['description'] ) {
				$strsql .= ", description = '" . ciniki_core_dbQuote($ciniki, $mod_function['description']) . "' ";
			}
			if( $mod_function['returns'] != $db_function['returns'] ) {
				$strsql .= ", returns = '" . ciniki_core_dbQuote($ciniki, $mod_function['returns']) . "' ";
			}
			$strsql = "UPDATE ciniki_systemdocs_api_functions SET last_updated = UTC_TIMESTAMP()" 
				. $strsql . " "
				. ", fsize = '" . ciniki_core_dbQuote($ciniki, $mod_function['size']) . "' "
				. ", flines = '" . ciniki_core_dbQuote($ciniki, $mod_function['lines']) . "' "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $function_id) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		//
		// Check for additions or updates to arguments
		//
		$db_updated = 0;
		foreach($mod_function['args'] as $mod_arg) {
			// If the function doesn't exist in the database, or the argument doesn't exist, insert
			if( $db_function == NULL || !isset($db_function['args'][$mod_arg['name']]) ) {
				$strsql = "INSERT INTO ciniki_systemdocs_api_function_args (function_id, "
					. "name, options, description) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $function_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_arg['name']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_arg['options']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_arg['description']) . "' "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$db_updated = 1;
			} 
			// Update the exist argument in the database
			else {
				$db_arg = $db_function['args'][$mod_arg['name']];
				$strsql = "";
				if( $mod_arg['options'] != $db_arg['options'] ) {
					$strsql .= ", options = '" . ciniki_core_dbQuote($ciniki, $mod_arg['options']) . "' ";
				}
				if( $mod_arg['description'] != $db_arg['description'] ) {
					$strsql .= ", description = '" . ciniki_core_dbQuote($ciniki, $mod_arg['description']) . "' ";
				}
				if( $strsql != '' ) {
					$strsql = "UPDATE ciniki_systemdocs_api_function_args SET function_id = function_id " 
						. $strsql . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_arg['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}

		//
		// Check for deleted args
		//
		if( $db_function != NULL && isset($db_function['args']) ) {
			foreach($db_function['args'] as $db_arg) {
				if( !isset($mod_function['args'][$db_arg['name']]) ) {
					$strsql = "DELETE FROM ciniki_systemdocs_api_function_args "
						. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $db_arg['id']) . "' "
						. "";
					$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}

		//
		// Check for additions or updates to calls
		//
		$db_updated = 0;
		foreach($mod_function['calls'] as $mod_call) {
			// If the function doesn't exist in the database, or the call doesn't exist, insert
			if( $db_function == NULL || !isset($db_function['calls'][$mod_call['call']]) ) {
				$strsql = "INSERT INTO ciniki_systemdocs_api_function_calls (function_id, "
					. "package, module, type, name, args) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $function_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_call['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_call['module']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_call['type']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_call['name']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_call['args']) . "' "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$db_updated = 1;
			} 
			// Update the existing call in the database
			else {
				$db_call = $db_function['calls'][$mod_call['call']];
				$strsql = "";
				if( $mod_call['args'] != $db_call['args'] ) {
					$strsql .= ", args = '" . ciniki_core_dbQuote($ciniki, $mod_call['args']) . "' ";
				}
				if( $strsql != '' ) {
					$strsql = "UPDATE ciniki_systemdocs_api_function_calls SET function_id = function_id " 
						. $strsql . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_call['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}

		//
		// Check for deleted calls
		//
		if( $db_function != NULL && isset($db_function['calls']) ) {
			foreach($db_function['calls'] as $db_call) {
				if( !isset($mod_function['calls'][$db_call['fcall']]) ) {
					$strsql = "DELETE FROM ciniki_systemdocs_api_function_calls "
						. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $db_call['id']) . "' "
						. "";
					$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}

		//
		// Check for additions or updates to errors
		//
		$db_updated = 0;
		foreach($mod_function['errors'] as $mod_error) {
			// If the function doesn't exist in the database, or the error doesn't exist, insert
			if( $db_function == NULL || !isset($db_function['errors'][$mod_error['code']]) ) {
				$strsql = "INSERT INTO ciniki_systemdocs_api_function_errors (function_id, "
					. "package, code, msg, pmsg) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $function_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_error['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_error['code']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_error['msg']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $mod_error['pmsg']) . "' "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$db_updated = 1;
			} 
			// Update the existing error in the database
			else {
				$db_error = $db_function['errors'][$mod_error['code']];
				$strsql = "";
				if( $mod_error['msg'] != $db_error['msg'] ) {
					$strsql .= ", msg = '" . ciniki_core_dbQuote($ciniki, $mod_error['msg']) . "' ";
				}
				if( $mod_error['pmsg'] != $db_error['pmsg'] ) {
					$strsql .= ", pmsg = '" . ciniki_core_dbQuote($ciniki, $mod_error['pmsg']) . "' ";
				}
				if( $strsql != '' ) {
					$strsql = "UPDATE ciniki_systemdocs_api_function_errors SET function_id = function_id " 
						. $strsql . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_error['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}

		//
		// Check for deleted errors
		//
		if( $db_function != NULL && isset($db_function['errors']) ) {
			foreach($db_function['errors'] as $db_error) {
				if( !isset($mod_function['errors'][$db_error['code']]) ) {
					$strsql = "DELETE FROM ciniki_systemdocs_api_function_errors "
						. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $db_error['id']) . "' "
						. "";
					$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$db_updated = 1;
				}
			}
		}



		//
		// Update the function last_updated if there was an update done to the database
		//
		if( $db_updated > 0 ) {
			//
			// Update the last updated for the table
			//
			$strsql = "UPDATE ciniki_systemdocs_api_functions SET last_updated = UTC_TIMESTAMP() " 
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		
	}

	//
	// Check if the function was removed
	//
	if( $db_function != NULL && $mod_function == NULL ) {
		//
		// Delete the function information
		//
		$strsql = "DELETE FROM ciniki_systemdocs_api_functions "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Delete the function arguments
		//
		$strsql = "DELETE FROM ciniki_systemdocs_api_function_args "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Delete the function arguments
		//
		$strsql = "DELETE FROM ciniki_systemdocs_api_function_calls "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Delete the function arguments
		//
		$strsql = "DELETE FROM ciniki_systemdocs_api_function_errors "
			. "WHERE function_id = '" . ciniki_core_dbQuote($ciniki, $db_function['id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
?>

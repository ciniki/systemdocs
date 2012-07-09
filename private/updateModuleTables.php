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
function ciniki_systemdocs_updateModuleTables($ciniki, $package, $module) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'parseDBCode');

	//
	// Load the table information from the database for this module
	//
	$strsql = "SELECT ciniki_systemdocs_api_tables.id AS table_id, "
		. "package, module, ciniki_systemdocs_api_tables.name AS table_name, ciniki_systemdocs_api_tables.description AS table_description, create_sql, version, "
		. "UNIX_TIMESTAMP(ciniki_systemdocs_api_tables.last_updated) AS table_last_updated, "
		. "ciniki_systemdocs_api_table_fields.id AS field_id, "
		. "ciniki_systemdocs_api_table_fields.name AS field_name, ciniki_systemdocs_api_table_fields.description AS field_description, "
		. "ciniki_systemdocs_api_table_fields.type, ciniki_systemdocs_api_table_fields.indexed, sequence "
		. "FROM ciniki_systemdocs_api_tables "
		. "LEFT JOIN ciniki_systemdocs_api_table_fields ON (ciniki_systemdocs_api_tables.id = ciniki_systemdocs_api_table_fields.table_id ) "
		. "WHERE ciniki_systemdocs_api_tables.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' " 
		. "AND ciniki_systemdocs_api_tables.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' " 
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'systemdocs', array(
		array('container'=>'tables', 'fname'=>'table_id', 'name'=>'table',
			'fields'=>array('id'=>'table_id', 'package', 'module', 'name'=>'table_name', 
				'description'=>'table_description', 'create_sql', 'version', 'last_updated'=>'table_last_updated')),
		array('container'=>'fields', 'fname'=>'field_id', 'name'=>'field',
			'fields'=>array('id'=>'field_id', 'name'=>'field_name', 'description'=>'field_description', 'type', 'indexed', 'sequence')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'785', 'msg'=>'Unable to locate tables', 'err'=>$rc['err']));
	}
	$db_tables = array();
	if( isset($rc['tables']) ) {
		//
		// Reindex to have table name as index
		//
		foreach($rc['tables'] AS $tnum => $dt) {
			$db_tables[$dt['table']['name']] = array(
				'id'=>$dt['table']['id'], 
				'name'=>$dt['table']['name'], 
				'description'=>$dt['table']['description'],
				'create_sql'=>$dt['table']['create_sql'],
				'version'=>$dt['table']['version'],
				'fields'=>array(),
				);
			if( isset($dt['table']['fields']) ) {
				foreach($dt['table']['fields'] as $fnum => $df) {
					$db_tables[$dt['table']['name']]['fields'][$df['field']['name']] = $df['field'];
				}
			}
		}
	}

	//
	// Check for database schema and upgrade files
	//
	$mod_tables = array();
	$path = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/db';
	if( is_dir($path) ) {
		//
		// Load in the module table information
		//
		$fp = opendir($path);
		while( $file = readdir($fp) ) {
			if( preg_match('/(.*)\.schema$/', $file, $matches) ) {
				$rc = ciniki_systemdocs_parseDBCode($ciniki, $package, $module, $matches[1]);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				// Reindex module tables
				if( isset($rc['table']) ) {
					$mod_tables[$rc['table']['name']] = array('name'=>$rc['table']['name'], 
						'description'=>$rc['table']['description'],
						'create_sql'=>$rc['table']['sql'],
						'version'=>$rc['table']['version'],
						'fields'=>array(),
						);
					if( isset($rc['table']['fields']) ) {
						foreach($rc['table']['fields'] as $fnum => $f) {
							if( !isset($f['field']['name']) ) {
								// FIXME: Add logging to capture missing documentation
							} else {
								$mod_tables[$rc['table']['name']]['fields'][$f['field']['name']] = $f['field'];
							}
						}
					}
				}
			}
		}	
	}

	//
	// Check for updates
	// ft = file table, from the module file system
	// dt = database table, from ciniki_systemdocs_api_tables
	//
	foreach($mod_tables as $tnum => $ft) {
		$dt = NULL;
		$table_id = 0;
		if( !isset($db_tables[$ft['name']]) ) {
			$strsql = "INSERT INTO ciniki_systemdocs_api_tables (package, module, name, "
				. "description, create_sql, version, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $package) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $module) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ft['name']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ft['description']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ft['create_sql']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ft['version']) . "', "
				. "UTC_TIMESTAMP()) "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$table_id = $rc['insert_id'];
		} else {
			$dt = $db_tables[$ft['name']];
			$table_id = $dt['id'];
			$strsql = "";
			if( $ft['description'] != $dt['description'] ) {
				$strsql .= ", description = '" . ciniki_core_dbQuote($ciniki, $ft['description']) . "' ";
			}
			if( $ft['create_sql'] != $dt['create_sql'] ) {
				$strsql .= ", create_sql = '" . ciniki_core_dbQuote($ciniki, $ft['create_sql']) . "' ";
			}
			if( $ft['version'] != $dt['version'] ) {
				$strsql .= ", version = '" . ciniki_core_dbQuote($ciniki, $ft['version']) . "' ";
			}
			if( $strsql != '' ) {
				$strsql = "UPDATE ciniki_systemdocs_api_tables SET last_updated = UTC_TIMESTAMP()" 
					. $strsql . " "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $dt['id']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}

		//
		// Check the table fields
		//
		foreach($ft['fields'] as $ff) {
			if( !isset($ff['type']) ) {
				$ff['type'] = 'UNKNOWN';
			}
			if( isset($ff['extras']) && preg_match('/unsigned/', $ff['extras']) ) {
				$ff['type'] .= ' unsigned';
			}
			$ff['indexed'] = '';
			if( isset($ff['index']) && $ff['index'] != '' ) {
				$ff['indexed'] = $ff['index'];
			}
			if( $dt == NULL || !isset($dt['fields'][$ff['name']]) ) {
				$strsql = "INSERT INTO ciniki_systemdocs_api_table_fields (table_id, "
					. "sequence, name, description, type, indexed) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $table_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $ff['sequence']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $ff['name']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $ff['description']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $ff['type']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $ff['indexed']) . "' "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'systemdocs');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}

			} else {
				$df = $dt['fields'][$ff['name']];
				$strsql = "";
				if( $ff['sequence'] != $df['sequence'] ) {
					$strsql .= ", sequence = '" . ciniki_core_dbQuote($ciniki, $ff['sequence']) . "' ";
				}
				if( $ff['description'] != $df['description'] ) {
					$strsql .= ", description = '" . ciniki_core_dbQuote($ciniki, $ff['description']) . "' ";
				}
				if( isset($ff['type']) && $ff['type'] != $df['type'] ) {
					$strsql .= ", type = '" . ciniki_core_dbQuote($ciniki, $ff['type']) . "' ";
				}
				if( $ff['indexed'] != $df['indexed'] ) {
					$strsql .= ", indexed = '" . ciniki_core_dbQuote($ciniki, $ff['indexed']) . "' ";
				}
				if( $strsql != '' ) {
					$strsql = "UPDATE ciniki_systemdocs_api_table_fields SET table_id=table_id " 
						. $strsql . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $df['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					//
					// Update the last updated for the table
					//
					$strsql = "UPDATE ciniki_systemdocs_api_tables SET last_updated = UTC_TIMESTAMP() " 
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $dt['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}

		//
		// Check for deleted fields
		//
		if( isset($dt['fields']) ) {
			foreach($dt['fields'] as $df) {
				if( !isset($ft['fields'][$df['name']]) ) {
					$strsql = "DELETE FROM ciniki_systemdocs_api_table_fields "
						. "WHERE table_id = '" . ciniki_core_dbQuote($ciniki, $dt['id']) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $df['id']) . "' "
						. "";
					$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
		
	}

	//
	// Check for deleted tables
	//
	foreach($db_tables as $tnum => $dt) {
		if( !isset($mod_tables[$dt['name']]) ) {
			//
			// Delete the table information
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_tables "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $dt['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}

			//
			// Delete the field information for the table
			//
			$strsql = "DELETE FROM ciniki_systemdocs_api_table_fields "
				. "WHERE table_id = '" . ciniki_core_dbQuote($ciniki, $dt['id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'systemdocs');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>

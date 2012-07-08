<?php
//
// Description
// -----------
// This method will parse the database schema's and systemdocs for a module.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// table:			The name of the table to parse.
// 
// Returns
// -------
// <rsp stat="ok">
// </rsp>
//
function ciniki_systemdocs_parseDBCode($ciniki, $package, $module, $table) {
	$dbfile = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/db/' . $table . '.schema';

	$rsp = array('filename'=>'/' . $package . '-api/' . $module . '/db/' . $table . '.schema', 
		'package'=>$package, 
		'module'=>$module, 
		'name'=>$table, 
		'description'=>'',
		'sql'=>'',
		'fields'=>array(),
		);
	
	$lines = file($dbfile);

	//
	// Parse the schema file
	//
	$description = array();
	$create_statement = '';
	$fields = array();
	$section = '';
	$cur_field = '';
	$cur_field_line = 0;
	$num_fields = 0;
	for($i=0;$i<count($lines);$i++) {
		if( preg_match('/^\s*\#/', $lines[$i]) ) {
			$cur_line = preg_replace('/^\s*\#\s*(.*)$/', '\1', $lines[$i]);
			if( preg_match('/description/i', $lines[$i]) && preg_match('/[-]{3,}/', $lines[$i+1]) ) {
				$section = 'description';
				$i++;
				continue;
			} elseif( preg_match('/fields/i', $lines[$i]) && preg_match('/[-]{3,}/', $lines[$i+1]) ) {
				$section = 'fields';
				$i++;
				continue;
			}
		
			if( $section == 'description' ) {
				array_push($description, $cur_line);
			}

			elseif( $section == 'fields' ) {
				//print "Field<br/>\n";
				if( preg_match('/^[^\s]+:/', $cur_line) ) {
					$split_line = preg_split('/:/', $cur_line, 2);
					$cur_field = $split_line[0];
					$cur_field_line = 0;
					$split_line[1] = preg_replace('/^\s+/','', $split_line[1]);
					$split_line[1] = preg_replace('/\s+$/','', $split_line[1]);
					$fields[$cur_field]['name'] = $cur_field;
					$fields[$cur_field]['description'] = $split_line[1];
					// $fields[$cur_field]['order'] = $num_fields++;
				} elseif( $cur_field != '' ) {
					$fields[$cur_field]['description'] .= "\n" . $cur_line;
				}
			}
		}
		
		else {
			//
			// Skip blank lines
			//
			if( preg_match('/^\s*$/', $lines[$i]) ) {
				continue;
			}

			if( preg_match('/\s*(?P<field>\S+)\s+(?P<type>blob|bigint|int|tinyint|smallint|text|datetime|date|numeric\(.*\)|char\([0-9]+\)|varchar\([0-9]+\)|decimal\(.*\))\s*(?P<unsigned>unsigned)?\s*(?P<null>\s|not null)?(?P<extras>.*)?,/', $lines[$i], $matches) ) {
				if( !isset($fields[$matches['field']]['name']) ) {
					$fields[$matches['field']]['name'] = $matches['field'];
				}
				if( $matches['null'] == 'not null' ) {
					$fields[$matches['field']]['null'] = 'N';
				} else {
					$fields[$matches['field']]['null'] = '';
				}
				$fields[$matches['field']]['type'] = $matches['type'];
				$fields[$matches['field']]['extras'] = $matches['extras'];
				if( $matches['unsigned'] != '' ) {
					$fields[$matches['field']]['extras'] .= $matches['unsigned'];
				}
				$fields[$matches['field']]['order'] = $num_fields++;
			}

			if( preg_match('/\s*\((?P<pks>.*)\)/', $lines[$i], $matches) ) {
				
			} 
			if( preg_match('/(?P<type>primary key|unique index|unique|index)\s*\((?P<pks>.*)\)/', $lines[$i], $matches) ) {
				$split_line = preg_split('/,\s*/', $matches['pks'], -1);
				foreach($split_line as $field) {
					if( $matches['type'] == 'primary key' ) {
						$fields[$field]['index'] = 'P';
					} elseif( $matches['type'] == 'unique index' ) {
						$fields[$field]['index'] = 'U';
					} elseif( $matches['type'] == 'unique' ) {
						$fields[$field]['index'] = 'U';
					} elseif( $matches['type'] == 'index' ) {
						$fields[$field]['index'] = 'I';
					}
				}
			}

			$create_statement .= $lines[$i];
		}
	}

	$rsp['sql'] = $create_statement;
	$rsp['description'] = implode($description);
	$rsp['version'] = '';
	if( preg_match('/COMMENT.*\'(v[0-9]+\.[0-9]+)\'/', $create_statement, $matches) ) {
		$rsp['version'] = $matches[1];
	}

	//
	// put the fields array into a proper XML structure
	//
	foreach($fields as $field => $val) {
		if( isset($fields[$field]['description']) ) {
			$fields[$field]['description'] = preg_replace('/\s*$/', '', $fields[$field]['description']);
		} else {
			$fields[$field]['description'] = '';
		}
		array_push($rsp['fields'], array('field'=>$fields[$field]));
	}

	return array('stat'=>'ok', 'table'=>$rsp);
}
?>

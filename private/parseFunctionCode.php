<?php
//
// Description
// -----------
// This method will parse through a function file and extract the documentation and relavent information
// for the documentation, such as function calls and error messages.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// type:			The directory and type for the method.  (public, private, cron)
// file:			The name of the method to parse.
// suffix:			The suffix or extension of the filename, typically this should be 'php'.
// 
// Returns
// -------
// <rsp stat="ok">
//		<method filename="/ciniki-api/core/public/echoTest.php" package="ciniki" module="" method="ciniki.core.echoTest" name="echoTest" type="public">
//			<description>This function will return an echo of the arguments received.</description>
//			<notes>Internal notes for developers.</description>
//			<returns>&lt;rsp stat="ok" /&gt;</returns>
//			<args>
//				<ciniki name="ciniki" description="">
//			</args>
//			<calls>
//				<ciniki_core_private_dbInit package="ciniki" module="core" type="" name="dbInit"/>
//			</calls>
//			<errors>
//				<29 code="29" msg="Unable to parse arguments" />
//			</errors>
//		</method>
// </rsp>
function ciniki_systemdocs_parseFunctionCode($ciniki, $package, $module, $type, $file, $suffix) {
	
	$filename = $ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/' . $type . '/' . $file . '.' . $suffix;

	$rsp = array('filename'=>'/' . $package . '-api/' . $module . '/' . $type . '/' . $file . '.' . $suffix, 
		'package'=>$package, 
		'module'=>$module, 
		'type'=>$type,
		'file'=>$file,
		'suffix'=>$suffix,
		'name'=>'',
		'description'=>'',
		'notes'=>'',
		'returns'=>'',
		'size'=>0,
		'lines'=>0,
		'blines'=>0,
		'clines'=>0,
		'plines'=>0,
		'args'=>array(),
		'errors'=>array(),
		'calls'=>array(),
		);

	//
	// Read in the file, into an array of lines
	//
	$st = stat($filename);
	$rsp['size'] = $st['size'];
	$lines = file($filename);
	$rsp['lines'] = count($lines);
	$calls = array();

	//
	// Count line types
	//
	for($i=0;$i<count($lines);$i++) {
		if( preg_match('/^\s*\/\/\s*[^ ]/', $lines[$i]) ) {
			$rsp['clines']++;
		} elseif( preg_match('/^\s*\/\/\s*$/', $lines[$i]) ) {
			$rsp['blines']++;
		} elseif( preg_match('/^\s*$/', $lines[$i]) ) {
			$rsp['blines']++;
		} else {
			$rsp['plines']++;
		}
	}

	//
	// Find the header documentation
	//
	$section = '';
	$cur_arg = '';
	$args = array();
	$info = array();
	$num_fields = 0;
	for($i=0;$i<count($lines);$i++) {
		if( preg_match('/^\s*\/\//', $lines[$i]) ) {
			$cur_line = preg_replace('/^\s*\/\/\s?/', '', $lines[$i]);
			if( preg_match('/^\s*description/i', $cur_line) && preg_match('/[-=][-=]+/', $lines[$i+1]) ) {
				$section = 'description';
				$i++;
				continue;
			} elseif( preg_match('/^\s*notes/i', $cur_line) && preg_match('/[-=][-=]+/', $lines[$i+1]) ) {
				$section = 'notes';
				$i++;
				continue;
			} elseif( preg_match('/^\s*info/i', $cur_line) && preg_match('/[-=][-=]+/', $lines[$i+1]) ) {
				$section = 'info';
				$i++;
				continue;
			} elseif( preg_match('/^\s*arguments/i', $cur_line) && preg_match('/[-=][-=]+/', $lines[$i+1]) ) {
				$section = 'arguments';
				$i++;
				continue;
			} elseif( preg_match('/^\s*returns/i', $cur_line) && preg_match('/[-=][-=]+/', $lines[$i+1]) ) {
				$section = 'returns';
				$i++;
				continue;
			}	

			if( $section == 'description' ) {
				$rsp['description'] .= $cur_line;
			}
			elseif( $section == 'notes' ) {
				$rsp['notes'] .= $cur_line;
			}
			elseif( $section == 'returns' ) {
				$rsp['returns'] .= preg_replace('/\t/', '    ', htmlspecialchars($cur_line));
			}
			elseif( $section == 'info' ) {
				if( preg_match('/^[^\s]+:/', $cur_line) ) {
					$split_line = preg_split('/:/', $cur_line, 2);
					$split_line[1] = preg_replace('/^\s+/','', $split_line[1]);
					$split_line[1] = preg_replace('/\s+$/','', $split_line[1]);
					$info[$split_line[0]] = array('name'=>$split_line[0], 'detail'=>$split_line[1]);
				}
			}
			elseif( $section == 'arguments' ) {
				if( preg_match('/^[^\s]+:/', $cur_line) ) {
					$split_line = preg_split('/:/', $cur_line, 2);
					$cur_arg = $split_line[0];
					$cur_arg_line = 0;
					$args[$cur_arg] = array('name'=>$cur_arg, 'description'=>'', 'options'=>'');
					if( preg_match('/^\s*\(optional\)/', $split_line[1]) ) {
						$args[$cur_arg]['options'] = 'optional';
						$split_line[1] = preg_replace('/^\s*\(optional\)\s*/i', '', $split_line[1]);
					}
					$split_line[1] = preg_replace('/^\s+/','', $split_line[1]);
					$split_line[1] = preg_replace('/\s+$/','', $split_line[1]);
					$args[$cur_arg]['description'] = $split_line[1] . "\n";
					$args[$cur_arg]['sequence'] = $num_fields++;
				} elseif( $cur_arg != '' ) {
					$args[$cur_arg]['description'] .= $cur_line;
				}
			}
		}

		//
		// Break out of the initial comment block
		//
		elseif( $type == 'scripts' && $section == 'description' ) {
			$rsp['name'] = $file . '.' . $suffix;
			break;
		}

		//
		// Check if we've reached the end of the header
		//
		if( $type != 'scripts' && preg_match('/^\s*function\s+(.*)\((.*)\)/', $lines[$i], $matches) ) {
			$rsp['name'] = $matches[1];
			// Only add arguments for non-public calls
			if( $type != 'public' ) {
				$arguments = explode(',', $matches[2]);
				foreach($arguments as $argument) {
					$argument = preg_replace('/^\s*\&?\$/', '', $argument);
					if( !isset($args[$argument]) ) {
						$args[$argument] = array('name'=>$argument, 'description'=>'', 'options'=>'', 'sequence'=>$num_fields++);
					}
				}
			}
			break;
		}
	}

	//
	// Remove the last blank line from the description
	//
	$rsp['description'] = preg_replace('/\s+$/', '', $rsp['description']);
	$rsp['notes'] = preg_replace('/\s+$/', '', $rsp['notes']);
	$rsp['returns'] = preg_replace('/\s*$/', '', $rsp['returns']);
	$rsp['args'] = $args;

	//
	// Join the multiple lines of the file into one content block for searching
	//
	$contents = implode("\n", array_slice($lines, $i+1));

	//
	// Find the error codes
	//
	if( preg_match_all('/array\(\'pkg\'=>\'([^\']+)\',\s*\'code\'=>\'(.*)\',\s*\'msg\'=>(\'|\")([^\'\"]+)(\'|\")(,\s*\'pmsg\'=>(\'|\")([^\'\"]+)(\'|\"))?/i', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {
			if( isset($rsp['errors'][$val[2]]) ) {
				$rsp['errors'][$val[2]]['dup'] = 'yes';
				if( !isset($rsp['duperrors']) ) {
					$rsp['duperrors'] = array();
				}
				array_push($rsp['duperrors'], array('error'=>array('package'=>$val[1], 'module'=>$module, 'type'=>$type, 'file'=>$file, 'code'=>$val[2], 'msg'=>$val[4], 'pmsg'=>'')));
			} else {
				$rsp['errors'][$val[2]] = array('package'=>$val[1], 'code'=>$val[2], 'msg'=>$val[4], 'pmsg'=>'', 'dup'=>'no');
				if( isset($val[6]) ) {
					$rsp['errors'][$val[2]]['pmsg'] = $val[8];
				}
			}
		}
	}

	//
	// Find all function calls
	//
	if( preg_match_all('/(([a-zA-Z0-9]+)_([a-zA-Z0-9]+)_(|([a-zA-Z0-9]+)_)([a-zA-Z0-9]+))\(([^\)]*)\)/', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {	
			if( isset($ciniki['config']['core']['packages']) && !preg_match('/' . $val[2] . '/', $ciniki['config']['core']['packages']) ) {
				continue;
			} elseif( $val[2] != 'ciniki' ) {
				continue;
			}
			// If unknown type
			$ctype = $val[5];
			if( $val[5] == '' ) {
				if( is_file($ciniki['config']['core']['root_dir'] . '/' . $val[2] . '-api/' . $val[3] . '/private/' . $val[6] . '.php') ) {
					$ctype = 'private';
				} elseif( is_file($ciniki['config']['core']['root_dir'] . '/' . $val[2] . '-api/' . $val[3] . '/public/' . $val[6] . '.php') ) {
					$ctype = 'public';
				} elseif( is_file($ciniki['config']['core']['root_dir'] . '/' . $val[2] . '-api/' . $val[3] . '/cron/' . $val[6] . '.php') ) {
					$ctype = 'cron';
				} elseif( is_file($ciniki['config']['core']['root_dir'] . '/' . $val[2] . '-api/' . $val[3] . '/web/' . $val[6] . '.php') ) {
					$ctype = 'web';
				}
			}
			$call = $val[2] . "_" . $val[3] . "_" . $ctype . "_" . $val[6];
			if( !isset($calls[$call]) ) {
				$rsp['calls'][$call] = array(
					'call'=>$call,
					'package'=>$val[2],
					'module'=>$val[3],
					'type'=>$ctype,
					'name'=>$val[6],
					'args'=>$val[7],
					);
			}
		}
	}

	//
	// Check for standard args and fill in the details
	//
	foreach($rsp['args'] as $name => $arg) {
		if( $name == 'api_key' && preg_match('/^\s*$/', $arg['description']) ) {
			$rsp['args'][$name]['description'] = "The unique key assigned to the interface the user is connecting from.";
		} elseif( $name == 'auth_token' && preg_match('/^\s*$/', $arg['description']) ) {
			$rsp['args'][$name]['description'] = "The token returned after the user authenticates.";
		} elseif( $name == 'ciniki' && preg_match('/^\s*$/', $arg['description']) ) {
			$rsp['args'][$name]['description'] = "The ciniki variable which must be passed to every function as it contains config and session information.";
		}
	}

	return array('stat'=>'ok', 'function'=>$rsp);
}
?>

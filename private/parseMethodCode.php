<?php
//
// Description
// -----------
// This method will parse through a method file and extract the documentation and relavent information
// for the documentation, such as function calls and error messages.
//
// Arguments
// ---------
// ciniki:			
// package:			The package the method is part of, eg: ciniki
// module:			The module contained within the package.
// type:			The directory and type for the method.  (public, private, cron)
// method:			The name of the method to parse.
// 
// Returns
// -------
// <rsp stat="ok">
//		<method filename="/ciniki-mods/core/public/echoTest.php" package="ciniki" module="" method="ciniki.core.echoTest" name="echoTest" type="public">
//			<description>This functionw will return an echo of the arguments received.</description>
//			<errors>
//				<error code="29" msg="Unable to parse arguments" />
//			</errors>
//			<returns>&lt;rsp stat="ok" /&gt;</returns>
//			<args>
//				<arg name="ciniki">
//			</args>
// 			<apiargs>
// 				<arg name="business_id" description="API args are only returned for public methods" />
// 			</apiargs>
//			<calls>
//				<method name="ciniki.core.dbInit" package="ciniki" module="core"/>
//			</calls>
//		</method>
// </methods>
function ciniki_systemdocs_parseMethodCode($ciniki, $package, $module, $type, $method) {
	
	$file = $ciniki['config']['core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $method . '.php';

	$rsp = array('filename'=>'/' . $package . '-mods/' . $module . '/' . $type . '/' . $method . '.php', 
		'method'=>"$package.$module.$method", 
		'package'=>$package, 
		'module'=>$module, 
		'name'=>$method, 
		'type'=>$type,
		'errors'=>array(),
		'calls'=>array(),
		'description'=>'',
		'returns'=>'',
		'args'=>array(),
		);
	if( $type == 'public' ) {
		$rsp['apiargs'] = array();
	}


	$lines = file($file);

	//
	// Find the header documentation
	//
	$section = '';
	$cur_arg = '';
	$apiargs = array();
	for($i=0;$i<count($lines);$i++) {
		if( preg_match('/^\s*\/\//', $lines[$i]) ) {
			$cur_line = preg_replace('/^\s*\/\/\s?/', '', $lines[$i]);
			if( preg_match('/^\s*description/i', $cur_line) && preg_match('/[- \t]+/', $lines[$i+1]) ) {
				$section = 'description';
				$i++;
				continue;
			} elseif( preg_match('/^\s*info/i', $cur_line) && preg_match('/---[- \t]+/', $lines[$i+1]) ) {
				$section = 'info';
				$i++;
				continue;
			} elseif( preg_match('/^\s*api arguments/i', $cur_line) && preg_match('/[- \t]+/', $lines[$i+1]) ) {
				$section = 'apiargs';
				$i++;
				continue;
			} elseif( preg_match('/^\s*arguments/i', $cur_line) && preg_match('/[- \t]+/', $lines[$i+1]) ) {
				$section = 'arguments';
				$i++;
				continue;
			} elseif( preg_match('/^\s*returns/i', $cur_line) && preg_match('/[- \t]+/', $lines[$i+1]) ) {
				$section = 'returns';
				$i++;
				continue;
			}	

			if( $section == 'description' ) {
				$rsp['description'] .= $cur_line;
			}
			elseif( $section == 'returns' ) {
				$rsp['returns'] .= htmlspecialchars($cur_line);
			}
			elseif( $section == 'apiargs' ) {
				if( preg_match('/^[^\s]+:/', $cur_line) ) {
					$split_line = preg_split('/:/', $cur_line, 2);
					$cur_arg = $split_line[0];
					$cur_arg_line = 0;
					$apiargs[$cur_arg]['name'] = $cur_arg;
					$apiargs[$cur_arg]['flags'] = '';
					if( preg_match('/^\s*\(optional\)/', $split_line[1]) ) {
						$apiargs[$cur_arg]['flags'] = 'optional';
						$split_line[1] = preg_replace('/^\s*\(optional\)\s*/i', '', $split_line[1]);
					}
					$split_line[1] = preg_replace('/^\s+/','', $split_line[1]);
					$split_line[1] = preg_replace('/\s+$/','', $split_line[1]);
					$apiargs[$cur_arg]['description'] = $split_line[1] . "\n";
				} elseif( $cur_arg != '' ) {
					$apiargs[$cur_arg]['description'] .= $cur_line;
				}
			}
			elseif( $section == 'arguments' ) {
				if( preg_match('/^[^\s]+:/', $cur_line) ) {
					$split_line = preg_split('/:/', $cur_line, 2);
					$cur_arg = $split_line[0];
					$cur_arg_line = 0;
					$args[$cur_arg]['flags'] = '';
					if( preg_match('/^\s*\(optional\)/', $split_line[1]) ) {
						$args[$cur_arg]['flags'] = 'optional';
						$split_line[1] = preg_replace('/^\s*\(optional\)\s*/i', '', $split_line[1]);
					}
					$split_line[1] = preg_replace('/^\s+/','', $split_line[1]);
					$split_line[1] = preg_replace('/\s+$/','', $split_line[1]);
					$args[$cur_arg]['description'] = $split_line[1] . "\n";
				} elseif( $cur_arg != '' ) {
					$args[$cur_arg]['description'] .= $cur_line;
				}
			}
		}
		//
		// Check if we've reached the end of the header
		//
		if( preg_match('/^\s*function\s+.*\((.*)\)/', $lines[$i], $matches) ) {
			$arguments = explode(',', $matches[1]);
			foreach($arguments as $argument) {
				$argument = preg_replace('/^\s*\$/', '', $argument);
				$arg = array('name'=>$argument);
				// If was found in documentation
				if( isset($args[$argument]) ) {
					$arg['description'] = preg_replace('/\s*$/', '', $args[$argument]['description']);
					$arg['flags'] = $args[$argument]['flags'];
				}
				array_push($rsp['args'], array('arg'=>$arg));
			}
			break;
		}
	}

	if( $type == 'public' && count($apiargs) > 0 ) {
		foreach($apiargs as $argument => $argvalue) {
			$apiargs[$argument]['description'] = preg_replace('/\s*$/', '', $apiargs[$argument]['description']);
			array_push($rsp['apiargs'], array('arg'=>$apiargs[$argument]));
		}
	}

	//
	// Remove the last blank line from the description
	//
	$rsp['description'] = preg_replace('/\s+$/', '', $rsp['description']);
	$rsp['returns'] = preg_replace('/\s*$/', '', $rsp['returns']);

	//
	// Join the multiple lines of the file into one content block for searching
	//
	$contents = implode("\n", $lines);

	//
	// Find the error codes
	//
	if( preg_match_all('/array\(\'pkg\'=>\'([^\']+)\',\s*\'code\'=>\'(.*)\',\s*\'msg\'=>(\'|\")([^\'\"]+)(\'|\")(,\s*\'pmsg\'=>(\'|\")([^\'\"]+)(\'|\"))?/i', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {
			$err = array('package'=>$val[1], 'code'=>$val[2], 'msg'=>$val[4]);
			if( isset($val[6]) ) {
				$err['pmsg'] = $val[8];
			}
			array_push($rsp['errors'], array('error'=>$err));
		}
	}

	//
	// Find all function calls
	//
	if( preg_match_all('/(([a-zA-Z0-9]+)_([^_]+)_([^_\(]+))\(([^\)]*)\)/', $contents, $matches, PREG_SET_ORDER) ) {
		foreach($matches as $val) {	
			// Only add calls which are not same as the method
			// This ignores the function definition line
			if( $val[1] != "{$package}_{$module}_$method" ) {
				// Check if method already exists
				$exists = 0;
				foreach($rsp['calls'] as $callnum => $call) {
					if( $call['call']['method'] == $val[1] ) { $exists = 1; }
				}
				if( $exists == 0 ) {
					$call = array('method'=>$val[1], 'package'=>$val[2], 'module'=>$val[3]);
					if( $val[4] == 'checkAccess' ) {
						$call['args'] = $val[5];
					}
					array_push($rsp['calls'], array('call'=>$call));
				}
			}
		}
	}

	return array('stat'=>'ok', 'method'=>$rsp);
}
?>

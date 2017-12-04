<?php
//
// Description
// -----------
// This method is the entry function to parse the documentation for a package, eg "ciniki".
//
// Arguments
// ---------
// ciniki:
// package:         The package to parse the code from, eg: ciniki
// 
// Returns
// -------
// <package name='ciniki'>
//  <errors>
//      <error code="1" msg="All the errors from all methods and scripts are listed here" />
//  </errors>
//  <modules>
//      <module package="ciniki" name="core">
//          <errors>
//          </errors>
//          <database filename="/ciniki-mods/core/db" package="ciniki" module="core">
//              <tables>
//              </tables>
//          </database>
//          <public>
//              <methods>
//                  <method filename="/ciniki-mods/core/public/echoTest.php" package="ciniki" module="" method="ciniki.core.echoTest" name="echoTest" type="public">
//                      <description>This functionw will return an echo of the arguments received.</description>
//                      <errors>
//                          <error code="29" msg="Unable to parse arguments" />
//                      </errors>
//                      <returns>&lt;rsp stat="ok" /&gt;</returns>
//                      <args>
//                          <arg name="ciniki" description="The documentation for this argument">
//                      </args>
//                      <apiargs>
//                          <arg name="tnid" flags="optional" description="The documentation for this argument" />
//                      <apiargs>
//                      <calls>
//                          <method name="ciniki.core.dbInit" />
//                      </calls>
//                  </method>
//              </methods>
//          </public>
//          <private>
//              <methods>
//              </methods>
//          </private>
//          <cron>
//              <methods>
//              </methods>
//          </cron>
//          <scripts>
//              <methods>
//              </methods>
//          </scripts>
//      </module>
//  </modules>
// </package>
//
function ciniki_systemdocs_parsePackageCode($ciniki, $package) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'systemdocs', 'private', 'parseModuleCode');

    $rsp = array('name'=>$package, 'errors'=>array(), 'modules'=>array());

    if( !is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-mods") ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.systemdocs.3', 'msg'=>'Package does not exist'));
    }
    
    $fp = opendir($ciniki['config']['core']['root_dir'] . "/{$package}-mods");
    while( $file = readdir($fp) ) {
        if($file[0] == '.' ) {
            continue;
        }
        if( is_dir($ciniki['config']['core']['root_dir'] . "/{$package}-mods/" . $file) ) {
            $rc = ciniki_systemdocs_parseModuleCode($ciniki, $package, $file);
            array_push($rsp['modules'], array('module'=>$rc['module']));
            if( isset($rc['module']) && isset($rc['module']['errors']) ) {
                foreach($rc['module']['errors'] as $errid => $err) {
                    $err['error']['module'] = $file;
                    array_push($rsp['errors'], array('error'=>$err['error']));
                }
            }
        }
    }

    return array('stat'=>'ok', 'package'=>$rsp);
// Used for debug purposes only
//  return array('stat'=>'ok');
}
?>

<?php
/**
* Execute command given by runcommand plugin
*
* @author Alessandro Celli <aelsantex@gmail.com>
* @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @ChangeLog: 
*     2014/03/20: Added rcObjectId argument as command and outputType selector
*/

function debug($msg) {	// DEBUG
  // file_put_contents("/var/log/apache/error_log", $msg . "\n", FILE_APPEND); // SOLARIS
  file_put_contents("/tmp/runcommand.log", print_r($msg,TRUE) . "\n", FILE_APPEND); 
}

$enableDebug=FALSE;

if ($enableDebug) {
  //require_once '/usr/lib/php/PEAR/file_put_contents.php'; //DEBUG SOLARIS
  require_once '/usr/share/php/./PHP/Compat/Function/file_put_contents.php'; //DEBUG UBUNTU
};

if ($enableDebug) { debug($_POST); };

// Parse arguments
$arrayKey = array_keys($_POST);
$rcObjectId=$_POST['rcObjectId'];

$command=$_POST['command'.$rcObjectId];
$outputType=$_POST['outputType'.$rcObjectId];
foreach ($arrayKey as $element) {
	if ($element == 'command') continue;
	if ($element == 'outputType') continue;
	
	$command=str_replace("$".$element,$_POST[$element],$command);
}
$command=stripslashes($command);

unset($outputValue);
// Eseguo lo script
$lastLine = exec($command, $outputValue, $retVal);

$result = "";
switch ($outputType) {
	case 'text':
		$result .= "<pre>\n";
		foreach ($outputValue as $row){
		  $result .= $row."\n";
		};
		$result .= "</pre>\n";
	break;
	case 'html':
		$result .= "<p>\n";
		foreach ($outputValue as $row){
		  $result .= $row."\n";
		};
		$result .= "</p>\n";
	break;
	case 'wiki':
		define('DOKU_INC','/var/opt/webstack/apache2/2.2/htdocs/dokuwiki/');
		require_once(DOKU_INC.'inc/init.php');
		require_once(DOKU_INC.'inc/parserutils.php');
 
		$info=null;
		$parsedOutput=p_get_instructions(implode("\n", $outputValue));
		$result .= p_render('xhtml',$parsedOutput, $info);
	break;
	case 'binary':
				
	break;
};
if($enableDebug) { debug("RESULT=\n".$result); }
print $result;
?> 

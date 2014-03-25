<?php
/**
* Execute command given by runcommand plugin
*
* @author Alessandro Celli <aelsantex@gmail.com>
* @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @ChangeLog: 
*     2014/03/20: Added rcObjectId argument as command and outputType selector
*/

//---- CONSTANT and INCLUSION ------------------------------------------------------------------------------------------
// must be run within Dokuwiki
if(!defined('DOKU_INC')) {
  define('DOKU_INC',realpath(dirname(__FILE__).'/../../..').'/');
}

function debug($msg,$msgLevel,$rcDebugLevel) {	// DEBUG
  // Write log on data/cache/debug.log
  if ($rcDebugLevel >= $msgLevel) {
    file_put_contents(DOKU_INC."data/cache/debug.log", print_r($msg,TRUE) . "\n", FILE_APPEND); 
  } 
}

$rcDebugLevel=0;

if ($rcDebugLevel > 0) {
  //require_once '/usr/lib/php/PEAR/file_put_contents.php'; //DEBUG SOLARIS
  require_once '/usr/share/php/./PHP/Compat/Function/file_put_contents.php'; //DEBUG UBUNTU
};

debug($_POST, 2, $rcDebugLevel);

// Parse arguments
$arrayKey = array_keys($_POST);
$rcObjectId=$_POST['rcObjectId'];

$command=$_POST['command'.$rcObjectId];
$outputType=$_POST['outputType'.$rcObjectId];
foreach ($arrayKey as $element) {
	if ($element == 'command'.$rcObjectId) continue;
	if ($element == 'outputType'.$rcObjectId) continue;
	if ($element == 'rcObjectId') continue;
	
	$baseElement = preg_replace('/'.$rcObjectId.'$/', '', $element);
	$command=str_replace("$".$baseElement,$_POST[$element],$command);
	debug("Replace: ".$baseElement." -> ".$_POST[$element]." | ".$command, 3, $rcDebugLevel);
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
		//define('DOKU_INC','/var/opt/webstack/apache2/2.2/htdocs/dokuwiki/');
		require_once(DOKU_INC.'inc/init.php');
		require_once(DOKU_INC.'inc/parserutils.php');
 
		$info=null;
		$parsedOutput=p_get_instructions(implode("\n", $outputValue));
		$result .= p_render('xhtml',$parsedOutput, $info);
	break;
	case 'binary':
				
	break;
};
debug("RESULT=\n".$result, 2, $rcDebugLevel);
print $result;
?> 

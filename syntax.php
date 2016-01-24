<?php
/**
 * Plugin runCommand: Give the ability of execute a script and display its output.
 * Working with ajax, required 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Alessandro Celli <aelsantex@gmail.com>
 * @ChangeLog: 
 *     2009/08/19: Added Custom text button from Steve 
 *     2009/08/20: Added Custom text and hidden value for cancel button
 *     2013/05/27: Starting rebuild for Weatherwax release
 *     2013/09/08: Rewrite debug function to use the dokuwiki's debug function.
 *     2014/03/12: Fixed script.js: removed image after execution
 *     2014/03/20: Completed render functions
 *     2014/03/26: Added a newline if the output type is set to choice
 *     2014/03/26: Fixed Slider value passing to post action
 *     2014/03/31: Fixed bad space parse for date field
 */

//---- CONSTANT and INCLUSION ------------------------------------------------------------------------------------------
// must be run within Dokuwiki
if(!defined('DOKU_INC')) {
	define('DOKU_INC','/'.realpath(dirname(__FILE__).'/../../').'/');
}
if(!defined('DOKU_PLUGIN')) {
	define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}

// Run Command Constant
if(!defined('RC_EXP_COMMAND')) define('RC_EXP_COMMAND','command');
if(!defined('RC_EXP_OUTPUT_TYPE')) define('RC_EXP_OUTPUT_TYPE','outputType');
if(!defined('RC_EXP_ARG')) define('RC_EXP_ARG','arg');
if(!defined('RC_EXP_RUNBUTTONTEXT')) define('RC_EXP_RUNBUTTONTEXT','runButtonText');
if(!defined('RC_EXP_CANCELBUTTONTEXT')) define('RC_EXP_CANCELBUTTONTEXT','cancelButtonText');
if(!defined('RC_ARG_HIDDEN')) define('RC_ARG_HIDDEN','hidden');
if(!defined('RC_ARG_TEXT')) define('RC_ARG_TEXT','text');
if(!defined('RC_ARG_LIST')) define('RC_ARG_LIST','list');
if(!defined('RC_ARG_AUTOCOMPLETE')) define('RC_ARG_AUTOCOMPLETE','autocomplete');
if(!defined('RC_ARG_SLIDER')) define('RC_ARG_SLIDER','slider');
if(!defined('RC_ARG_SPINNER')) define('RC_ARG_SPINNER','spinner');
if(!defined('RC_ARG_DATE')) define('RC_ARG_DATE','date');
if(!defined('RC_CONST_NO_CANCEL_BUTTON')) define('RC_CONST_NO_CANCEL_BUTTON','none');

require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_runcommand extends DokuWiki_Syntax_Plugin {
  var $currentCommand = 0;	// Global variable
  var $binaryOutput = False;	// Type of output, as default is not a binary value

  function debug($msg,$msgLevel) {	// DEBUG
    // Write log on data/cache/debug.log
    if ($this->getConf('rc_debug_level') >= $msgLevel) {
      dbglog("RC:".$msg);
    } 
  }


  // Runcommand analyze byself its component; it use only the main tag analyzer by dokuwiki
  // BY_DOC: modes which have a start and end token but inside which no other modes should be applied
  function getType() { return 'protected'; }
  
  // BY_DOC: The plugin output will be inside a paragraph (or another block element), no paragraphs will be inside
  function getPType() { return 'normal'; }
  
  // BY_DOC: Returns a number used to determine in which order modes are added, also see parser, order of adding modes and getSort list.
  function getSort() { return 432; }

  // BY_DOC: This function is inherited from Doku_Parser_Mode 2). Here is the place to register the regular expressions needed to match your syntax.
  function connectTo($mode) {
    $this->Lexer->addSpecialPattern('<runcommand>.*?</runcommand>',$mode,'plugin_runcommand');
  }


  // BY_DOC: to prepare the matched syntax for use in the renderer
  // This plugin works with the state: DOKU_LEXER_ENTER, DOKU_LEXER_UNMATCH and DOKU_LEXER_EXIT 
  function handle($match, $state, $pos, Doku_Handler $handler){
    //if ($state <> DOKU_LEXER_ENTER) { exit; };
    $argId = 1;

    $match = substr($match,12,-13); // remove form wrap
    $this->debug("HANDLE:MATCH=".$match,3);
    $lines = explode("\n",$match);

    $cmds = array();
    // Process all lines from runcommand body
    foreach($lines as $line){
      $line = trim($line);
      // Skip empty lines
      if(!$line) continue;
      $this->debug("HANDLE:LINE=".$line,3);

      //Split a line into its components
      $objLine=explode("|",$line);

      // Identify the base type for this line
      switch($objLine[0]){
	case RC_EXP_COMMAND:
	  $field=array('key' => RC_EXP_COMMAND, 'value' => $objLine[1]);
	break;
	case RC_EXP_OUTPUT_TYPE:
	  $field=array('key' => RC_EXP_OUTPUT_TYPE, 'value' => $objLine[1]);
	  // Check if is a binary output
	  if ($objLine[1] == 'binary') {
	    $binaryOutput = True;
	  }
	break;
	case RC_EXP_RUNBUTTONTEXT:
	  $field=array('key' => RC_EXP_RUNBUTTONTEXT, 'value' => $objLine[1]);
	break;
	case RC_EXP_CANCELBUTTONTEXT:
	  $field=array('key' => RC_EXP_CANCELBUTTONTEXT, 'value' => $objLine[1]);
	break;
	default: // This line is an argument.
	  $argName = $objLine[0];
	  $this->debug("HANDLE:argName=".$argName,2);
	  
	  $argLabel = $objLine[1];
	  $this->debug("HANDLE:argLabel=".$argLabel,2);
	  
	  $argFlags=preg_split("/[\s,]+/", $objLine[2],-1,PREG_SPLIT_NO_EMPTY);
	  $this->debug("HANDLE:argFlags=".$argFlags,2);
	  
	  $argField=explode("=",$objLine[3]);
	  $argType = $argField[0];
	  $this->debug("HANDLE:argType=".$argType,2);
	  
	  switch($argType) {
	    case RC_ARG_HIDDEN:
	    case RC_ARG_TEXT:
	      $argValue = $argField[1];
	    break;
	    case RC_ARG_LIST:
	      $argValue = $this->parseFieldList($argField[1]);
	    break;
	    case RC_ARG_AUTOCOMPLETE:
	      $argValue = $this->parseFieldAutocomplete($argField[1]);
	    break;
	    case RC_ARG_SLIDER:
	      $argValue = $this->parseFieldSlider($argField[1]);
	    break;
	    case RC_ARG_SPINNER:
	      $argValue = $this->parseFieldSpinner($argField[1]);
	    break;
	    case RC_ARG_DATE:
	      $argValue = $this->parseFieldDate($argField[1]);
	    break;
	    default: 
	      return null;
	  };
	  $field=array('key' => RC_EXP_ARG, 'name' => $argName, 'label' => $argLabel, 'flags' => $argFlags, 'type' => $argType, 'value' => $argValue);
	  break;
	};
      array_push($cmds, $field);
      };
      $this->debug("HANDLE:---------- PARSING -------------------------------------",1); 
      $this->debug("HANDLE:".print_r($cmds,true),1); 
    return $cmds;
  }

  function parseFieldList($args){
    $fields = explode(";",$args);
    $result = array();
    foreach ($fields as $field) {
      if(!$field) continue;
      $temp = explode(":",$field);
      if ($temp[0] == 'default') {
	$defaultValue=$temp[1];
      } else {
	$result[] =  array('item' => $temp[0], 'value' => $temp[1], 'default' => '0');
      }
    }
    return $result;
  }

  function parseFieldAutocomplete($args){
    return preg_split('/;/', $args,-1,PREG_SPLIT_NO_EMPTY);
  }
  
  function parseFieldSlider($args){
    preg_match_all("/([^;=]+):([^;=]+)/", $args, $r);
    return array_combine($r[1], $r[2]);
  }
  
  function parseFieldSpinner($args){
    preg_match_all("/([^;=]+):([^;=]+)/", $args, $r);
    return array_combine($r[1], $r[2]);
  }
  
  function parseFieldDate($args){
    preg_match_all("/([^;=]+):([^;=]+)/", $args, $r);
    return array_combine($r[1], $r[2]);
  }
  

  function render($mode, Doku_Renderer $renderer, $data) {
    global $currentCommand;	// Global variable who keep the unique Id of the runcommand form.
    $currentCommand += 1;
    
    // Rendering phase have 2 bocks, the jquery/javascript block and the form block where are defined the field objects.
    $jqueryBlock="<script>\njQuery(function() {\n";					// JQuery block to rendered 
    //$jqueryBlock="<script type=\"text/javascript\">/*<![CDATA[*/\n";
    $htmlBlock="<form id=\"rcform".$currentCommand."\">\n";	// HTML block to rendered 

    if($mode == 'xhtml'){
      if ($data == null) { // If data is null, the user write block wrong.
	$renderer->doc .= $this->renderWrongSyntax();
	return;
      };

      $this->debug("RENDER:MODE=".$mode."\ncurrentCommand=".$currentCommand,2);

      // Extract the default button label from config file.
      $buttonSubmitText = $this->getLang('btn_submit');
      $buttonCancelText = $this->getLang('btn_cancel');
      
      // Rendering data objects
      foreach ($data as $element) {
	$this->debug("RENDER:Element:".print_r($element,true),2);
	switch($element['key']){
	  case RC_EXP_COMMAND:
	    if ($binaryOutput) { // It give back a binary file to download 
	      // TODO
	    } else { // The output is text, html or a wiki syntax code.
	      $htmlBlock .= $this->renderFormHidden('command','','',$this->prepareCommand($element['value']));				    
	    };
	  break;
	  case RC_EXP_OUTPUT_TYPE:
	    if ($element['value'] == 'choice') { // If the type is required to the user it build a list.
	      $htmlBlock .= $this->renderFormListBox('outputType',$this->getLang('lbl_outputFormat'),array('newline'),array(
		array( 'item' => 'text', 'value' => $this->getLang('fld_text')),
		array( 'item' => 'html', 'value' => $this->getLang('fld_html')),
		array( 'item' => 'wiki', 'value' => $this->getLang('fld_wiki'))));
	    } else {
	      $htmlBlock .= $this->renderFormHidden('outputType','outputType','',$element['value']);
	    };
	  break;
	  case RC_EXP_RUNBUTTONTEXT:
	    $buttonSubmitText = $element['value'];
	    $jqueryBlock .= $this->renderJQueryButton("runCommand","ui-icon-check");
	  break;
	  case RC_EXP_CANCELBUTTONTEXT:
	    $buttonCancelText = $element['value'];
	    $jqueryBlock .= $this->renderJQueryButton("clearDiv","ui-icon-arrowreturnthick-1-w");
	  break;
	  case RC_EXP_ARG:
	    switch($element['type']){
	      case RC_ARG_HIDDEN:
		$htmlBlock .= $this->renderFormHidden($element['name'],$element['label'],$element['flags'],$element['value']);
	      break;
	      case RC_ARG_TEXT:
		$htmlBlock .= $this->renderFormTextBox($element['name'],$element['label'],$element['flags'],$element['value']);
	      break;
	      case RC_ARG_LIST:
		$htmlBlock .= $this->renderFormListBox($element['name'],$element['label'],$element['flags'],$element['value']);
	      break;
	      case RC_ARG_AUTOCOMPLETE:
		$htmlBlock .= $this->renderFormAutoComplete($element['name'],$element['label'],$element['flags'],$element['value']);
		$jqueryBlock .= $this->renderJQueryAutoComplete($element['name'],$element['label'],$element['value']);
	      break;
	      case RC_ARG_SLIDER:
		$htmlBlock .= $this->renderFormSlider($element['name'],$element['label'],$element['flags'],$element['value']['value']);
		$jqueryBlock .= $this->renderJQuerySlider($element['name'],$element['value']['min'],$element['value']['max'],$element['value']['value'],$element['value']['step']);
	      break;
	      case RC_ARG_SPINNER:
		$htmlBlock .= $this->renderFormSpinner($element['name'],$element['label'],$element['flags'],$element['value']);
		$jqueryBlock .= $this->renderJQuerySpinner($element['name'],$element['value']['min'],$element['value']['max'],$element['value']['value']);
	      break;
	      case RC_ARG_DATE:
		$htmlBlock .= $this->renderFormDate($element['name'],$element['label'],$element['flags'],$element['value']);
		$jqueryBlock .= $this->renderJQueryDate($element['name'],$element['value']['format']);
	      break;
	    };
	  break;
	};
      };
      if ($binaryOutput) {
	// TODO
	$htmlBlock .= $this->renderFormButton('runCommand',$buttonSubmitText,'',"rcDoDownload");
      } else {
	$htmlBlock .= $this->renderFormButton('runCommand',$buttonSubmitText,'',"rcDoLoad");
      };
      $this->debug("RENDER:buttonCancelText=".$buttonCancelText,3);
      if ($buttonCancelText != RC_CONST_NO_CANCEL_BUTTON) {
	$this->debug("RENDER:Render di buttonCancelText",3);
	$htmlBlock .= $this->renderFormButton('clearDiv',$buttonCancelText,'',"rcDoClear");
      };
      $htmlBlock .= "</form>\n";
      //$jqueryBlock .= "  });\n/*!]]>*/</script>\n";
      $jqueryBlock .= "  });\n</script>\n";

      $renderer->doc .= $jqueryBlock. $htmlBlock;
      $renderer->doc .= "<div id=\"rcResult".$currentCommand."\" class=\"rcResult\">&nbsp;</div><br>";
    }
  }

  //---- Button --------------------------------------------------------------------------------------------------------
  /** Create a button field */
  function renderFormButton($name, $label, $flags, $action) {
    // $flags is actually unused for this field
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMBUTTON:".$name."=".$value,3);
    return "<button type=\"button\" id=\"".$id."\" onclick=\"".$action."(".$currentCommand.")\">".$label."</button>\n";
  }

  /** Define JQuery button config */
  function renderJQueryButton($name, $style) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:JQUERYBUTTON:".$name."=".$value,3);
    return "jQuery( \"#".$id."\" ).button({ icons: { primary: \"".$style."\" }});\n";
  }
  
  //---- Hidden --------------------------------------------------------------------------------------------------------
  /** Create an hidden field */
  function renderFormHidden($name, $label, $flags, $value) {
    // $label and $flags are actually unused for this field
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMHIDDEN:".$name."=".$value,3);
    return "<input type='hidden' id='".$id."' value='".$value."'>\n";
  }

  //---- TextBox -------------------------------------------------------------------------------------------------------
  /** Create a text field */
  function renderFormTextBox($name, $label, $flags, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMTEXTBOX:".$name."=".$value,3);
    $result = "<label>".$label."</label><input type=\"text\" id=\"".$id."\" value=\"".$value."\" />";
    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>\n";
      else $result = $result."\n";
    }
    return $result;
  }

  //---- List ----------------------------------------------------------------------------------------------------------
  /** Create a list field */
  function renderFormListBox($name, $label, $flags, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMLISTBOX:".$name."=".$value,3);
    $result = "<label>".$label."</label><SELECT id='".$id."'>";
    foreach($value as $listObj){
      $result .= "<option value=\"".$listObj['item']."\">".$listObj['value']."</option>";
    };
    $result .= "</SELECT>\n";

    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>\n";
      else $result = $result."\n";
    }
    return $result;
  }  
  
  //---- Autocomplete --------------------------------------------------------------------------------------------------
  function renderFormAutoComplete($name, $label, $flags, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMAUTOCOMPLETE:".$name."=".$value,3);
    $result = "<label>".$label."</label><input id=\"".$id."\" />";

    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>\n";
      else $result = $result."\n";
    }
    return $result;
  }

  function renderJQueryAutoComplete($name, $label, $value) {
    // $flags is actually unused for this field
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $result  = "var availableTags".$currentCommand." = [ ";
    foreach($value as $listObj) {
    	$result .= "\"".$listObj."\", ";
    }
    $result = substr($result, 0, -1)." ];\n";
    $result .= "jQuery(\"#".$id."\" ).autocomplete({ source: availableTags".$currentCommand." });\n";
    
    return $result;
  }

  //---- Slider --------------------------------------------------------------------------------------------------------
  function renderJQuerySlider($name, $min, $max, $value, $step) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;
  
    $result  = "jQuery( \"#".$id."_sli\" ).slider({ ";
    $result .= "min: ".$min.", max: ".$max.", value: ".$value.", step: ".$step.", orientation: \"horizontal\", animate: true, ";
    $result .= "slide: function( event, ui ) { jQuery( \"#".$id."\" ).val(ui.value ); ";
    $result .= "} });\n";
    return $result;
  }
  
  function renderFormSlider($name, $label, $flags, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMSLIDER:".$name."=".$value,3);
    $result  = "<label>".$label."</label>";
    $result .= "<input type=\"text\" id=\"".$id."\" style=\"border: 0; color: #f6931f; font-weight: bold; align: right;\" value=\"".$value."\"/>";
    $result .= "<div id=\"".$id."_sli\" style=\"width: 260px; margin: 15px; \"></div>";

    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>\n";
      else $result = $result."\n";
    }
    return $result;
  }

  //---- Spinner -------------------------------------------------------------------------------------------------------
  function renderJQuerySpinner($name, $min, $max, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;
    return "jQuery( \"#".$id."\" ).spinner({ min: \"".$min."\", max: \"".$max."\" }).val(\"".$value."\");\n";
  }
  
  function renderFormSpinner($name, $label, $flags, $value) {
    // $flags is actually unused for this field
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMSPINNER:".$name."=".$value,3);
    $result  = "<label>".$label."</label>";
    $result .= "<input id=\"".$id."\" name=\"value\"/>";

    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>\n";
      else $result = $result."\n";
    }
    return $result;
  }
  
  //---- Date ----------------------------------------------------------------------------------------------------------
  function renderJQueryDate($name, $dateFormat) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;
    
    if ($dateFormat == null) { // If the user don't give the date format it use the default format.
      $dateFormat = $this->getConf('rc_default_dateformat');
    };
    return "jQuery( \"#".$id."\" ).datepicker({ dateFormat: '".$dateFormat."' }); \n";

  }
  
  function renderFormDate($name, $label, $flags, $value) {
    global $currentCommand;
    $id = str_replace(" ","_",$name).$currentCommand;

    $this->debug("RENDER:FORMDATE:".$name."=".$value,3);
    $result  = "<label>".$label."</label>";
    $result .= "<input type=\"text\" id=\"".$id."\" />";

    foreach ($flags as $flag) {
      if ($flag == "newline") $result = "<p>".$result."</p>";
      else $result = $result."\n";
    }
    return $result;
  }
  
  
	
  /**
  * Prepare the command for execution
  */
  function prepareCommand($cmd) {
    if ($this->getConf('safe_scripts') == 0) {
      return $cmd;
    } else {
      $base_dir= DOKU_INC.$this->getConf('script_dir')."/";
      $result = "";
      $cmdRows = explode(';',$cmd);
      //if ($debugLevel) { $this->debug($cmdRows); };
      foreach ($cmdRows as $cmdRow){
	//if ($debugLevel) { $this->debug("row=".$cmdRow); };
	$cmdRow = ltrim($cmdRow);
	$result .= $base_dir.$cmdRow."; ";
      };
      return substr($result, 0, -2);
    }
  }

// /**
// * Create javascript line that load value of an input box
// */
// function renderScriptInput($id, $name) {
//   //if ($debugLevel) { $this->debug("=> renderScriptInput(".$id.", ".$name.")"); };
//   return "var ".$name." = jQuery(\"form[id='rcform".$id."']>input[id='".$name."']\").val();\n";
// }

//   /**
//   * Create javascript line that load value of a combo box
//   */
//   function renderScriptCombo($id, $name) {
//     if ($debugLevel) { $this->debug("=> renderScriptCombo(".$id.", ".$name.")"); };
//     return "var ".$name." = jQuery(\"form[id='rcform".$id."']>select[id='".$name."']\").val();\n";
//   }
// 
//   function renderWrongSyntax() {
//     return "<div class='error'>".$this->getLang('msg_wrongsyntax')."<br></div>";
//   }

} // End of class


/* RunCommand - script.js */
/**
*  Clear id div contents.
* 
* ChangeLog:
* 2014/03/12: Fixed wait image deactivation after command execution
* 2014/03/20: Added rcObjectId argument to argmap 
* 2014/03/30: Added toolbar facility to simply runcommand use
*/

function rcDoDownload(filename) {
  var url = window.location.href;
  url=url.replace("http://","");
  var begin = url.indexOf("/");
  var end =   url.indexOf("?")-8; // remove 8 characther for "doku.php" text.
  url = url.substring(begin,end)+"lib/plugins/runcommand/";

  jQuery('DIV#rcResult'+id).wrapInner('<IMG SRC="'+url+'wait.gif" width="200px" height="75px">');

  var argmap = new Object();
  jQuery('FORM#rcform'+id+' hidden, FORM#rcform'+id+' input, FORM#rcform'+id+' select').each(
	 function (el) {
		if (this.type != 'button')	argmap[this.id]=this.value;
	 }
  );
  location.href=url+'postaction.php'
}

function rcDoClear(id) {
	jQuery('DIV#rcResult'+id).empty();
	jQuery('DIV#rcResult'+id).append('&nbsp;');
}

function rcDoLoad(id) {
  var url = window.location.href;
  url=url.replace("http://","");
  var begin = url.indexOf("/");
  var end =   url.indexOf("?")-8; // remove 8 characther for "doku.php" text.
  url = url.substring(begin,end)+"lib/plugins/runcommand/";

  jQuery('DIV#rcResult'+id).wrapInner('<IMG SRC="'+url+'wait.gif" width="200px" height="75px">');

  var argmap = new Object();
  argmap['rcObjectId']=id;
  jQuery('FORM#rcform'+id+' hidden, FORM#rcform'+id+' input, FORM#rcform'+id+' select').each(
	 function (el) {
		if (this.type != 'button')	argmap[this.id]=this.value;
	 }
  );
  //alert(JSON.stringify(argmap));
  jQuery.post(url+'postaction.php', argmap, 
	 function(data){ jQuery('DIV#rcResult'+id).empty(); jQuery('DIV#rcResult'+id).wrapInner(data); }
  );
}

if (typeof window.toolbar !== 'undefined') {
    var runcommand_arr = {
        // 'insertion string as key' : '[path/]filename.extension of the icon'
      "<runcommand>\ncommand|<command>\n</runcommand>\n":				'basetag.png',
      "outputType|choice\n":								'outputtype.png',
      "runButtonText|Execute Label\n":							'runbutton.png',
      "cancelButtonText|Cancel Label\n":						'cancelbutton.png',
      "arg_hidden|||hidden=Fixed value\n":						'hidden.png',
      "arg_textbox|TextBox field|newline|text=Default value\n":				'textbox.png',
      "arg_list|List field|newline|list=item1:label1;item2:label2;\n":			'list.png',
      "arg_autocomp|Autocomplete field|newline|autocomplete=value1;value2;\n":		'autocomplete.png',
      "arg_slider|Slider field|newline|slider=min:0;max:100;value:50;step:10;\n":	'slider.png',
      "arg_spinner|Spinner field|newline|spinner=min:0;max:10;value:5;\n":		'spinner.png',
      "arg_date|Date field|newline|date=format:dd/mm/yyyy;\n":				'date.png',
      "<runcommand>\ncommand|<command>\noutputType|choice\nrunButtonText|Execute Label\ncancelButtonText|Cancel Label\narg_hidden|||hidden=Fixed value\narg_textbox|TextBox field|newline|text=Default value\narg_list|List field|newline|list=item1:label1;item2:label2;\narg_autocomp|Autocomplete field|newline|autocomplete=value1;value2;\narg_slider|Slider field|newline|slider=min:0;max:100;value:50;step:10;\narg_spinner|Spinner field|newline|spinner=min:0;max:10;value:5;\narg_date|Date field|newline|date=format:dd/mm/yyyy;\n</runcommand>":		'fullruncommand.png'
    };
 
    toolbar[toolbar.length] = {
        type: "picker",
        title: "RunCommand", // localisation
        icon: '../../plugins/runcommand/images/toolbar_16.png',  //where in lib/images/toolbar/ the images are located
        key: "r", //access key
        list: runcommand_arr,
        icobase: '../plugins/runcommand/images'
    };

} // End - RunCommand script.js
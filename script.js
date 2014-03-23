/* RunCommand - script.js */
/**
*  Clear id div contents.
* 
* ChangeLog:
* 2014/03/12: Fixed wait image deactivation after command execution
* 2014/03/20: Added rcObjectId argument to argmap 
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




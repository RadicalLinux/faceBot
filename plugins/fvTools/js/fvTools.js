/* ************** Function check all checkBoxes ******************* */

function checkAll(what,chName){
	var elemName = document.fvTool.name;
	var frm = document.fvTool
	var el = frm.elements
	var enable = eval( 'document.fvTool.' + chName + '.checked')
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(iPos);
		if(el[i].type == "checkbox" && sResult == what && enable == true) {
			el[i].checked = true;				
		}
		else if(el[i].type == "checkbox" && sResult == what && enable != true) {
			el[i].checked = false;				
		}
	}
}

function checkAllBuilder(what,chName){
	var elemName = document.fvBuilder.name;
	var frm = document.fvBuilder
	var el = frm.elements
	var enable = eval( 'document.fvBuilder.' + chName + '.checked')
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		if(el[i].type == "checkbox" && sResult == what && enable == true) {
			el[i].checked = true;				
		}
		else if(el[i].type == "checkbox" && sResult == what && enable != true) {
			el[i].checked = false;				
		}
	}
}

function checkAllTreesToWater(what,chName){
	var elemName = document.fvWaterTrees.name;
	var frm = document.fvWaterTrees
	var el = frm.elements
	var enable = eval( 'document.fvWaterTrees.' + chName + '.checked')
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		if(el[i].type == "checkbox" && sResult == what && enable == true) {
			el[i].checked = true;				
		}
		else if(el[i].type == "checkbox" && sResult == what && enable != true) {
			el[i].checked = false;				
		}
	}
}

function checkAllTreesNoMaster(what,chName,treeId){
	var elemName = document.fvWaterTrees.name;
	var frm = document.fvWaterTrees
	var el = frm.elements
	var enable = eval( 'document.fvWaterTrees.' + chName + '.checked')
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var myId = el[i].parentNode.id;
		/*alert(myId);*/
		if(el[i].type == "checkbox" && sResult == what && myId == treeId  && enable == true) {			
			el[i].checked = true;				
		}
		else if(el[i].type == "checkbox" && sResult == what && myId == treeId && enable != true) {
			el[i].checked = false;				
		}
	}
}


// JavaScript Document

document.write('<style type="text/css">.tabber{display:none;}<\/style>');
var tabberOptions = {

	'cookie' : "tabber", /* Name to use for the cookie */

	'onLoad' : function(argsObj) {
		var t = argsObj.tabber;
		var i;
		if (t.id) {
			t.cookie = t.id + t.cookie;
		}

		i = parseInt(getCookie(t.cookie));
		if (isNaN(i)) {
			return;
		}
		t.tabShow(i);
	},

	'onClick' : function(argsObj) {
		var c = argsObj.tabber.cookie;
		var i = argsObj.index;
		setCookie(c, i);
	}
};

/*
 * ================================================== Cookie functions
 * ==================================================
 */
function setCookie(name, value, expires, path, domain, secure) {
	document.cookie = name + "=" + escape(value)
			+ ((expires) ? "; expires=" + expires.toGMTString() : "")
			+ ((path) ? "; path=" + path : "")
			+ ((domain) ? "; domain=" + domain : "")
			+ ((secure) ? "; secure" : "");
}

function getCookie(name) {
	var dc = document.cookie;
	var prefix = name + "=";
	var begin = dc.indexOf("; " + prefix);
	if (begin == -1) {
		begin = dc.indexOf(prefix);
		if (begin != 0)
			return null;
	} else {
		begin += 2;
	}
	var end = document.cookie.indexOf(";", begin);
	if (end == -1) {
		end = dc.length;
	}
	return unescape(dc.substring(begin + prefix.length, end));
}
function deleteCookie(name, path, domain) {
	if (getCookie(name)) {
		document.cookie = name + "=" + ((path) ? "; path=" + path : "")
				+ ((domain) ? "; domain=" + domain : "")
				+ "; expires=Thu, 01-Jan-70 00:00:01 GMT";
	}
}

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

function checkSellQuantity(sellItem, sellValue) {
	var myvalue = document.getElementById(sellValue);
	if (parseInt(sellItem.value) > parseInt(myvalue.value)) {
		alert("You are trying to sell more than you currently have");
	}
}

function checkBuy(buyItem, buyCost) {
	var myvalue = document.getElementById(buyCost);
	var coin = document.getElementById('fvcoin');
	var cash = document.getElementById('fvcash');
	var ptype = buyCost.substr(0, 4);
	if (ptype == 'coin') {
		if (parseInt(coin.value) < (parseInt(buyItem.value) * parseInt(myvalue.value))) {
			alert("You don't have enough coins for this purchase");
		} else {
			coin.value = parseInt(coin.value)
					- (parseInt(buyItem.value) * parseInt(myvalue.value));
		}
	} else {
		if (parseInt(cash.value) < (parseInt(buyItem.value) * parseInt(myvalue.value))) {
			alert("You don't have enough FV Cash for this purchase");
		} else {
			cash.value = parseInt(cash.value)
					- (parseInt(buyItem.value) * parseInt(myvalue.value));
		}
	}
}

var http_request = false;
function makeRequest(url, parameters) {
   http_request = false;
   if (window.XMLHttpRequest) { // Mozilla, Safari,...
      http_request = new XMLHttpRequest();
      if (http_request.overrideMimeType) {
      	// set type accordingly to anticipated content type
         //http_request.overrideMimeType('text/xml');
         http_request.overrideMimeType('text/html');
      }
   } else if (window.ActiveXObject) { // IE
      try {
         http_request = new ActiveXObject("Msxml2.XMLHTTP");
      } catch (e) {
         try {
            http_request = new ActiveXObject("Microsoft.XMLHTTP");
         } catch (e) {}
      }
   }
   if (!http_request) {
      alert('Cannot create XMLHTTP instance');
      return false;
   }
   http_request.onreadystatechange = alertContents;
   http_request.open('GET', url + parameters, true);
   http_request.send(null);
}

function alertContents() {
   if (http_request.readyState == 4) {
      if (http_request.status == 200) {
         //alert(http_request.responseText);
         result = http_request.responseText;
         document.getElementById('myspan').innerHTML = result;            
      } else {
         alert('There was a problem with the request.');
      }
   }
}

function get(obj) {
   var getstr = "?";
   for (i=0; i<obj.childNodes.length; i++) {
      if (obj.childNodes[i].tagName == "INPUT") {
         if (obj.childNodes[i].type == "text") {
            getstr += obj.childNodes[i].name + "=" + obj.childNodes[i].value + "&";
         }
         if (obj.childNodes[i].type == "checkbox") {
            if (obj.childNodes[i].checked) {
               getstr += obj.childNodes[i].name + "=" + obj.childNodes[i].value + "&";
            } else {
               getstr += obj.childNodes[i].name + "=&";
            }
         }
         if (obj.childNodes[i].type == "radio") {
            if (obj.childNodes[i].checked) {
               getstr += obj.childNodes[i].name + "=" + obj.childNodes[i].value + "&";
            }
         }
      }   
      if (obj.childNodes[i].tagName == "SELECT") {
         var sel = obj.childNodes[i];
         getstr += sel.name + "=" + sel.options[sel.selectedIndex].value + "&";
      }
      
   }
   makeRequest('main.php', getstr);
}


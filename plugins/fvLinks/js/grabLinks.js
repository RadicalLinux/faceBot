function checkThemAll(what,chName){
	var elemName = document.grablinks.name;
	var frm = document.grablinks
	var el = frm.elements
	var enable = eval( 'document.grablinks.' + chName + '.checked')
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

function checkBoxes(){
	var elemName = document.grablinks.name;
	var frm = document.grablinks
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		if(el[i].type == "checkbox") {
			if(el[i].checked == true && el[i].parentNode.tagName == "TD"){
				el[i].parentNode.className = "rewardGrabNo";	
			}
			else if (el[i].checked == false && el[i].parentNode.tagName == "TD"){
				el[i].parentNode.className = "rewardGrabYes";	
			}
				
		}
	}
}

function checkBox(td){
	var node = td;
	while ( (node = node.parentNode) != null ){
		if ( node.tagName == "TD" ){
		node.className = td.checked ? "rewardGrabNo" : "rewardGrabYes";
		return;
		}
	}
}

function checkAll(id){
	var elemName = document.grablinks.name;
	// set the form to look at (your form is called grablinks)
	var frm = document.grablinks
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		/*alert(node);*/
		if(el[i].type == "checkbox" && el[i].name != id + "[share]") {
			if(sResult == id){
				el[i].checked = true;				
				el[i].parentNode.className = "rewardGrabNo";
				
			}
				
		}
	}
}
function unCheckAll(id){
	// set the form to look at (your form is called grablinks)
	var frm = document.grablinks
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=0;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && el[i].name != id + "[share]") {
			if(sResult == id){
				el[i].checked = false;
				el[i].parentNode.className = "rewardGrabYes";
			}
				
		}
	}
}
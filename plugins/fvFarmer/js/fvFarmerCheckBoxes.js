/* SELECT ALL ANIMALS */
function allAnimals(id){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = true;				
				el[i].parentNode.style.backgroundColor = "#B7DFFD";
			}
			if(document.harvestAnimals.myHorse.checked != true){
				document.harvestAnimals.myHorse.checked = true;
				document.getElementById('horseCol').style.backgroundColor = "#FB8383";
			}
			if(document.harvestAnimals.myFoal.checked != true){
				document.harvestAnimals.myFoal.checked = true;
				document.getElementById('foalCol').style.backgroundColor = "#FDCDB7";
			}
			if(document.harvestAnimals.myCow.checked != true){
				document.harvestAnimals.myCow.checked = true;
				document.getElementById('cowCol').style.backgroundColor = "#B7FDC9";
			}
			if(document.harvestAnimals.myCalf.checked != true){
				document.harvestAnimals.myCalf.checked = true;
				document.getElementById('calfCol').style.backgroundColor = "#F6B7FD";
			}
			if(document.harvestAnimals.myChicken.checked != true){
				document.harvestAnimals.myChicken.checked = true;
				document.getElementById('chickenCol').style.backgroundColor = "#FDFBB7";
			}
				
		}
	}
}

/* DESELECT ALL ANIMALS */
function noAnimals(id){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = false;				
				el[i].parentNode.style.backgroundColor = "#E1E1E1";
			}
			if(document.harvestAnimals.myHorse.checked == true){
				document.harvestAnimals.myHorse.checked = false;
				document.getElementById('horseCol').style.backgroundColor = "#E1E1E1";
			}
			if(document.harvestAnimals.myFoal.checked == true){
				document.harvestAnimals.myFoal.checked = false;
				document.getElementById('foalCol').style.backgroundColor = "#E1E1E1";
			}
			if(document.harvestAnimals.myCow.checked == true){
				document.harvestAnimals.myCow.checked = false;
				document.getElementById('cowCol').style.backgroundColor = "#E1E1E1";
			}
			if(document.harvestAnimals.myCalf.checked == true){
				document.harvestAnimals.myCalf.checked = false;
				document.getElementById('calfCol').style.backgroundColor = "#E1E1E1";
			}
			if(document.harvestAnimals.myChicken.checked == true){
				document.harvestAnimals.myChicken.checked = false;
				document.getElementById('chickenCol').style.backgroundColor = "#E1E1E1";
			}	
		}
	}
}

/* SELECT/DESELECT ALL HORSES, STALLIONS, PONIES AND NOT FOALS */
function allAnimalsHorse(id,typeA,typeB,typeC){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	var enable = document.harvestAnimals.myHorse.checked
	if(document.harvestAnimals.myHorse.checked == true){
		document.getElementById('horseCol').style.backgroundColor = "#FB8383";
	}
	else if(document.harvestAnimals.myHorse.checked != true){
		document.getElementById('horseCol').style.backgroundColor  = "#E1E1E1";
	}
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var animalName = el[i].parentNode.id;
		var animalFoal = animalName.match("Foal");
		var animalTypeA = animalName.match("Horse");
		var animalTypeB = animalName.match("Stallion");
		var animalTypeC = animalName.match("Pony");
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && animalFoal == null && sResult == id) {
			if(animalTypeA == typeA){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#FB8383";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}
			else if(animalTypeB == typeB){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#FB8383";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}
			else if(animalTypeC == typeC){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#FB8383";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}
				
		}
	}
}

/* SELECT/DESELECT ALL FOALS */
function allAnimalsFoal(id,typeF){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	var enable = document.harvestAnimals.myFoal.checked
	if(document.harvestAnimals.myFoal.checked == true){
		document.getElementById('foalCol').style.backgroundColor = "#FDCDB7";
	}
	else if(document.harvestAnimals.myFoal.checked != true){
		document.getElementById('foalCol').style.backgroundColor  = "#E1E1E1";
	}
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var animalName = el[i].parentNode.id;
		var animalTypeF = animalName.match("Foal");
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && sResult == id) {
			if(animalTypeF == typeF){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#FDCDB7";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}	
		}
	}
}

/* SELECT/DESELECT ALL COWS */
function allAnimalsCow(id,typeM){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	var enable = document.harvestAnimals.myCow.checked
	if(document.harvestAnimals.myCow.checked == true){
		document.getElementById('cowCol').style.backgroundColor = "#B7FDC9";
	}
	else if(document.harvestAnimals.myFoal.checked != true){
		document.getElementById('cowCol').style.backgroundColor  = "#E1E1E1";
	}
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var animalName = el[i].parentNode.id;
		var animalTypeM = animalName.match("Cow");
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && sResult == id) {
			if(animalTypeM == typeM){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#B7FDC9";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}	
		}
	}
}

/* SELECT/DESELECT ALL CALVES */
function allAnimalsCalf(id,typeD){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	var enable = document.harvestAnimals.myCalf.checked
	if(document.harvestAnimals.myCalf.checked == true){
		document.getElementById('calfCol').style.backgroundColor = "#F6B7FD";
	}
	else if(document.harvestAnimals.myCalf.checked != true){
		document.getElementById('calfCol').style.backgroundColor  = "#E1E1E1";
	}
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var animalName = el[i].parentNode.id;
		var animalTypeD = animalName.match("Calf");
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && sResult == id) {
			if(animalTypeD == typeD){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#F6B7FD";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}	
		}
	}
}

/* SELECT/DESELECT ALL CHICKEN */
function allAnimalsChicken(id,typeE){
	var elemName = document.harvestAnimals.name;
	// set the form to look at (your form is called harvestAnimals)
	var frm = document.harvestAnimals
	// get the form elements
	var el = frm.elements
	var enable = document.harvestAnimals.myChicken.checked
	if(document.harvestAnimals.myChicken.checked == true){
		document.getElementById('chickenCol').style.backgroundColor = "#FDFBB7";
	}
	else if(document.harvestAnimals.myChicken.checked != true){
		document.getElementById('chickenCol').style.backgroundColor  = "#E1E1E1";
	}
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		var animalName = el[i].parentNode.id;
		var animalTypeE = animalName.match("Chicken");
		// and check if it is a checkbox
		if(el[i].type == "checkbox" && sResult == id) {
			if(animalTypeE == typeE){
				if( el[i].checked == false && enable == true){
					el[i].checked = true;				
					el[i].parentNode.style.backgroundColor = "#FDFBB7";
					
				}
				else if(el[i].checked == true && enable != true){
					el[i].checked = false;				
					el[i].parentNode.style.backgroundColor = "#E1E1E1";
					
				}
			}	
		}
	}
}

/* SELECT ALL TREES */
function allTrees(id){ 
	var elemName = document.harvestTrees.name;
	// set the form to look at (your form is called harvestTrees)
	var frm = document.harvestTrees
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = true;				
				el[i].parentNode.style.backgroundColor = "#B7DFFD";
			}		
		}
	}
}

/* DESELECT ALL TREES */
function noTrees(id){ 
	var elemName = document.harvestTrees.name;
	// set the form to look at (your form is called harvestTrees)
	var frm = document.harvestTrees
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = false;				
				el[i].parentNode.style.backgroundColor = "#E1E1E1";
			}		
		}
	}
}

/* SELECT ALL BULDINGS */
function allBuildings(id){ 
	var elemName = document.harvestBuildings.name;
	// set the form to look at (your form is called harvestBuildings)
	var frm = document.harvestBuildings
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = true;				
				el[i].parentNode.style.backgroundColor = "#B7DFFD";
			}		
		}
	}
}

/* DESELECT ALL BULDINGS */
function noBuildings(id){ 
	var elemName = document.harvestBuildings.name;
	// set the form to look at (your form is called harvestBuildings)
	var frm = document.harvestBuildings
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		var elemName = el[i].name;
		var iPos = elemName.lastIndexOf("[");
		var sResult = elemName.substring(0,iPos);
		// and check if it is a checkbox
		if(el[i].type == "checkbox") {
			if(sResult == id){
				el[i].checked = false;				
				el[i].parentNode.style.backgroundColor = "#E1E1E1";
			}		
		}
	}
}

/* SELECT BACKGROUND COLOR FOR CHECKBOXES WHEN USING SINGLE CHECK */
function bkgColor(elem){ 
	if(elem.checked == true) {elem.parentNode.style.backgroundColor = "#B7DFFD";}
	else if(elem.checked == false) {elem.parentNode.style.backgroundColor = "#E1E1E1";}
}

/* SELECT BACKGROUND COLOR FOR RADIO BUTTONS  */
function bkgColorRadio(tbl){ 
	// set the form to look at (your form is called tbl)
	var frm = eval('document.' + tbl) 
	// get the form elements
	var el = frm.elements
	// loop through the elements...
	for(i=1;i<el.length;i++) {
		if(el[i].type == "radio") {
			if(el[i].checked == true) {
				el[i].parentNode.style.backgroundColor = "#FF7D1A";
				el[i].parentNode.style.color = "#FFFFFF";
			}
			else if(el[i].checked == false) {
				el[i].parentNode.style.backgroundColor = "#E1E1E1";
				el[i].parentNode.style.color = "#000000";
			}					
		}
	}
}


<?php
//########################################################################
// animalmanager functions - faceBot
//########################################################################

function AM_doWalk($itemName = '') {
	global $TB_settings;

	if($TB_settings['AM_move'] == 'true') $str = "allow";
	else $str = "forbid";
	$str = $str . " moving ...";
	AddLog2($str);

	$i = 0;
	$movingAnimals = count(AM_getMovingAnimals($itemName));
	$standingAnimals = count(AM_getAnimals($itemName)) - $movingAnimals;
	$left = $TB_settings['AM_move'] == 'true' ? $standingAnimals : $movingAnimals;
	$animals = array();
	$animals = AM_getAnimals($itemName);
	foreach($animals as $animal) {
		if($TB_settings['AM_move'] == 'true') {
			$amf = CreateMultAMFRequest($amf, $i, 'move', 'WorldService.performAction');
			if( $animal['canWander'] == 0) {
				$amf->_bodys[0]->_value[1][$i]['params'][1]              	= $animal;
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['state']   	= $animal['state'];
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['x']   		= $animal['position']['x'];
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['y']   		= $animal['position']['y'];
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['canWander'] = true;
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['direction']	= $animal['direction'];
				$i++;
				$left--;
			}
		} elseif($TB_settings['AM_move'] == 'false') {
			if( $animal['canWander'] == 1) {
				$amf->_bodys[0]->_value[1][$i]['params'][1]              	= $animal;
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['state']   	= 'bare';
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['x']   		= $animal['position']['x'];
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['y']   		= $animal['position']['y'];
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['canWander'] = false;
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['direction']	= $animal['direction'];
				$i++;
				$left--;
			}
		}
		if($i >= $TB_settings['TB_speedAction']) {
			$res = RequestAMF( $amf );
			if ( $res == 'OK' ) {
				AddLog2( '-> ' . $res . ' ' . $left . ' left');
				$i = 0;
			} else {
				AddLog2( "ERROR - $res" );
				return false;
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return true;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return true;
}

function AM_doRotate($itemName = '') {
	global $TB_settings;
	AddLog2("rotating ...");

	$i = 0;
	$animals = array();
	$animals = AM_getAnimals($itemName);
	$left = count($animals) - count(AM_getDirectionAnimals($TB_settings['AM_direction'], $itemName));

	foreach($animals as $animal) {
		if( $animal['direction'] <> $TB_settings['AM_direction']) {
			$amf = CreateMultAMFRequest($amf, $i, 'move', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][$i]['params'][1]              	= $animal;
			$amf->_bodys[0]->_value[1][$i]['params'][1][0]['direction'] = $TB_settings['AM_direction'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['state']   	= $animal['state'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['x']   		= $animal['position']['x'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['y']   		= $animal['position']['y'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['canWander'] = $animal['canWander'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['direction']	= $TB_settings['AM_direction'];
			$i++;
			$left--;
		}
		if($i >= $TB_settings['TB_speedAction']) {
			$res = RequestAMF( $amf );
			if ( $res == 'OK' ) {
				AddLog2( '-> ' . $res . ' ' . $left . ' left');
				$i = 0;
			} else {
				AddLog2( "ERROR - $res" );
				return false;
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return true;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return true;
}

function AM_getAnimals($itemName = '') {
	$objects = array();
	$objects = TB_loadObjects();
	$arr = array();
	$i = 0;

	foreach($objects as $obj)
	{
		if($obj['className'] == 'Animal') {
			if($itemName == '' || $obj['itemName'] == $itemName) {
				$arr[$i] = $obj;
				$i++;
			}
		}
	}
	return $arr;
}

function AM_getMovingAnimals($itemName = '') {
	$animals = array();
	$animals = AM_getAnimals();

	$arr = array();
	$i = 0;

	foreach($animals as $animal)
	{
		if($animal['canWander'] == '1')	{
			if($itemName == '' || $animal['itemName'] == $itemName) {
				$arr[$i] = $animal;
				$i++;
			}
		}
	}
	return $arr;
}

function AM_getDirectionAnimals($direction, $itemName = '') {
	$animals = array();
	$animals = AM_getAnimals();

	$arr = array();
	$i = 0;

	foreach($animals as $animal)
	{
		if($animal['direction'] == $direction)	{
			if($itemName == '' || $animal['itemName'] == $itemName) {
				$arr[$i] = $animal;
				$i++;
			}
		}
	}
	return $arr;
}

// animal mover +++++++++++++++++++++++++++++++++++++++
// get animals in buildiung ( itemName -> number)
function AM_animalInBuilding($building) {
	$arr = array();
	foreach($building['contents'] as $animal) {
		$arr[GetNameByCode($animal['itemCode'])] = $animal['numItem'];
	}
	ksort($arr);
	return $arr;
}

// load building object
function AM_loadBuildingObject($getClassName, $cutIDIfOne = true) {
	$objs = array();
	$objs = TB_loadObjects();
	$arr = array();

	foreach($objs as $obj)
	{
		if($obj['className'] == $getClassName) {
			$arr[$obj['id']] = $obj;
		}
	}
	if(count($arr) == 1 && $cutIDIfOne ) { //just one object
		$IDs = array_keys($arr);
		return $arr[$IDs[0]];
	}
	return $arr;
}

// get max animal number
function AM_getMaxAnimalsInBuilding($building) {
	$string = $building['itemName'];
	$num =  $building['itemName'];
	switch($num) {
		case "chickencoop":
			return 20;
		case "chickencoop2":
			return 40;
		case "chickencoop3":
			return 60;
		case "chickencoop4":
			return 80;
		case "chickencoop5":
			return 100;
		case "dairyfarm":
			return 20;
		case "dairyfarm2":
			return 30;
		case "dairyfarm3":
			return 40;
		case "horsestablewhite":
			return 40;
		case "nurserybarn_finished":
			return 40;
		case "orchardbuilding":
			return 20;
		case "turkeyroost_finished":
			return 40;
		case "pigpen_finished":
			switch ($building['expansionLevel']) {
				case "1":
					return 20;
				case "2":
					return 30;
				case "3":
					return 40;
			}
			break;
		default :
			return 20;

	}
}

// load animals on farm objects or number
function AM_loadAnimalOnFarmCount($name) {
	global $AM_settings;
	$objs = array();
	$objs = TB_loadObjects();
	$arr = array();

	foreach($objs as $obj)
	{
		if(isset($AM_settings[$name][$obj['itemName']])) {
			if(isset($arr[$obj['itemName']])) {
				$arr[$obj['itemName']]++;
			} else {
				$arr[$obj['itemName']] = 1;
			}
		}
	}
	return $arr;
}

function AM_loadAnimalOnFarmObjects($name) {
	global $AM_settings;
	$objs = array();
	$objs = TB_loadObjects();
	$arr = array();
	$i = 0;

	foreach($objs as $obj)
	{
		if(isset($AM_settings[$name][$obj['itemName']])) {
			$arr[$i] = $obj;
			$i++;
		}
	}
	return $arr;
}

// animal mover
function AM_animalMover($name, $getBuilding, $getFarm) {
	global $TB_settings;
	global $AM_settings;

	$building = $getBuilding;
	$farm = $getFarm;
	$buildingID = $building['id'];
	$workToDo = false;
	$left = 0;
	foreach($AM_settings[$name] as $animal) {
		if($TB_settings[$buildingID]['AM_move_' . $animal] > 0) {
			$workToDo = true;
			$left += $TB_settings[$buildingID]['AM_move_' . $animal];
		}
	}
	if($workToDo) {
		$maxAnimalInBuilding = AM_getMaxAnimalsInBuilding($building);
		$cBuilding = 0;
		foreach(AM_animalInBuilding($building) as $animal => $num) {
			$cBuilding += $num;
		}
		switch($TB_settings[$buildingID]['AM_moveTo']) {
			case $name:
				AddLog2('moving animals to building (' . $name . ')');
				if ($name == 'OrchardBuilding'){
					AddLog2('moving trees to building (' . $name . ')');
				}
				else {
					AddLog2('moving animals to building (' . $name . ')');
				}
				if($cBuilding >= $maxAnimalInBuilding) {
					AddLog2('building is full (' . $name . ')');
					return true;
				}
				$re = AM_moveAnimalsToBuilding($building, $left, $name, $cBuilding, $maxAnimalInBuilding, $farm);
				break;
			case 'farm':
				if ($name == 'OrchardBuilding'){
					AddLog2('moving trees to farm (' . $name . ')');
				}
				else {
					AddLog2('moving animals to farm (' . $name . ')');
				}

				if($cBuilding == 0) {
					AddLog2('building is empty (' . $name . ')');
					return true;
				}
				$re = AM_moveAnimalsToFarm($building, $left, $farm);
				break;
			default:
				$re = null;
		}
	} else {
		AddLog2('-> no work');
		$re = $farm;
	}
	if(is_array($re)) return $re; // = $farm
	else return false;
}

function AM_moveAnimalsToFarm($building, $left, $getFarm) {
	global $TB_settings;
	global $AM_settings;
	$return = array();
	$farm = array();
	$farm = $getFarm;
	$position = array();
	$i = 0;
	$u = 0;

	foreach(AM_animalInBuilding($building) as $animal => $num) {
		while($TB_settings[$building['id']]['AM_move_' . $animal] > 0 && $num > 0) {
			if($building['className'] == 'ChickenCoopBuilding') {
				$position = TB_findEmptySpotSection($animal,$farm,1,1);
				if($position == false) {
					//AddLog2('can\'t find position in section -> will use whole farm');
					$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm,1,1);
				}
			} else {
				$position = TB_findEmptySpotSection($animal,$farm,2,2);
				if($position == false) {
					//AddLog2('can\'t find position in section -> will use whole farm');
					$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm,2,2);
				}
			}
			$amf = CreateMultAMFRequest($amf, $i, 'place', 'WorldService.performAction');

			$amf->_bodys[0]->_value[1][$i]['params'][1]['className'] 		= 'Animal';
			$amf->_bodys[0]->_value[1][$i]['params'][1]['itemName']  		= $animal;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['direction']     	= 1;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['x']   	= $position['x'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['y'] 	= $position['y'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['z'] 	= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['deleted']			= false;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['state']   			= $building['state'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['tempId']    		= -1;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['plantTime'] 		= time() - 82800 - 1;

			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isStorageWithdrawal'] 	= $building['id'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift']      			= false;

			$farm[$position['x']][$position['y']] = false;
			if($building['className'] <> 'ChickenCoopBuilding')	{
				$farm[$position['x'] + 1][$position['y']] = false;
				$farm[$position['x']][$position['y'] + 1] = false;
				$farm[$position['x'] + 1][$position['y'] + 1] = false;
			}

			$num--;
			$left--;
			$i++;
			$TB_settings[$building['id']]['AM_move_' . $animal]--;

			if($i >= $TB_settings['TB_speedAction']) {
				$res = RequestAMF( $amf );
				if ( $res == 'OK' ) {
					AddLog2( '-> ' . $res . ' ' . $left . ' left');
					$i = 0;
				} else {
					AddLog2( "ERROR - $res" );
					return false;
				}
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return $farm;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return $farm;
}

function AM_moveAnimalsToBuilding($building, $left, $name, $cBuilding, $maxBuilding, $getFarm) {
	global $TB_settings;
	global $AM_settings;
	$i = 0;
	$farm = array();
	$farm = $getFarm;

	$animals = array();
	$animals = AM_loadAnimalOnFarmObjects($name);

	foreach($animals as $animal) {
		if($TB_settings[$building['id']]['AM_move_' . $animal['itemName']] > 0 && $cBuilding < $maxBuilding) {
			$amf = CreateMultAMFRequest($amf, $i, 'store', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][$i]['params'][1]['id']              			= $building['id'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['contents'][0]['num']   	= $cBuilding + 1;
			//$amf->_bodys[0]->_value[1][$i]['params'][1]['contents'][0]['item']	= '';
			$amf->_bodys[0]->_value[1][$i]['params'][1]['itemName']          		= $building['itemName'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['direction']              	= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['buildTime']				= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['className']            	= $building['className'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['x']       		= $building['position']['x'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['y']       		= $building['position']['y'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['z']      		= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['deleted']           		= false;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['state']              		= $building['state'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['tempId']             		= 'NaN';
			//$amf->_bodys[0]->_value[1][$i]['params'][1]['plantTime']          		= $time;

			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['resource']   		= $animal['id'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['cameFromLocation'] 	= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedItemCode']   	= TB_GetCodeByName($animal['itemName']);
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedClassName']   = 'animal';
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift']      		= false;
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedItemName'] 	= $animal['itemName'];;

			$farm[$building['position']['x']][$building['position']['y']] = true;
			if($building['className'] <> 'ChickenCoopBuilding')	{
				$farm[$building['position']['x'] + 1][$building['position']['y']] = true;
				$farm[$building['position']['x']][$building['position']['y'] + 1] = true;
				$farm[$building['position']['x'] + 1][$building['position']['y'] + 1] = true;
			}

			$cBuilding++;
			$left--;
			$i++;
			$TB_settings[$building['id']]['AM_move_' . $animal['itemName']]--;

			if($i >= $TB_settings['TB_speedAction']) {
				$res = RequestAMF( $amf );
				if ( $res == 'OK' ) {
					AddLog2( '-> ' . $res . ' ' . $left . ' left');
					$i = 0;
				} else {
					AddLog2( "ERROR - $res" );
					return false;
				}
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return $farm;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return $farm;
}

// farm gold ++++++++++++++++++++++++++++++++++++++++++
// n0m mod: doHarvestBuilding param added --
//			to skip harvesting building
//			if a singlular ( 1 ) cycle requested
function AM_farmGold($name,$building,$getFarm, $doHarvestBuilding=false) {
	if(isset($building['isFullyBuilt'])) {
		if($building['isFullyBuilt'] <> 1) {
			echo "You don\'t have that building (".$name.")! \r\n";
			return false;
		}
	} else {
		echo "You don\'t have that building (".$name.")! \r\n";
		return false;
	}
	$re = array();
	$IDs = array();
	$positions = array();
	$animalsInBuilding = AM_animalInBuilding($building);
	$cInBuilding = 0;
	$farm = $getFarm;
	$itemName = array();

	foreach($animalsInBuilding as $animal => $num) {
		$cInBuilding += $num;
	}
	if($cInBuilding == 0) return $farm;

	$saveInBuilding = $cInBuilding;
	AddLog2('ToolBox: Moving ' . $cInBuilding . ' Animals to Farm');
	$re = AM_farmGold_moveAnimalsToFarm($building, $cInBuilding, $farm);
	if($re == false) {
		return false;
	} else {
		if(@is_array($re['farm']) && @is_array($re['IDs']) && (@is_array($re['itemName']) && @is_array($re['positions']))) {
			$farm = $re['farm'];
			$IDs = $re['IDs'];
			$itemName = $re['itemName'];
			$positions = $re['positions'];
		} else {
			return false;
		}
	}
	//harves
	AddLog2('ToolBox: Harvesting ' . ($saveInBuilding-1) . ' Animals on Farm');
	AM_farmGold_harvestWithoutFirst($IDs, $itemName, $positions);
	//move one in

	AddLog2('ToolBox: Moving ' . ($saveInBuilding - 1) . ' Animals into Building');
	$re = AM_farmGold_moveAnimalsToBuilding($building, ($saveInBuilding - 1), $farm, $IDs, $itemName, $positions);
	if(@is_array($re)) $farm = $re;
	else return false;
	//delet first chicken
	for ($x = 0; $x < $saveInBuilding - 1; $x++)
	{
		unset($IDs[$x]);
		unset($itemName[$x]);
		unset($positions[$x]);
	}
	$IDs = array_values($IDs);
	//
	$itemName = array_values($itemName);
	//
	$positions = array_values($positions);
	//moving rest in
	AddLog2('ToolBox: Moving Final Animal into Building');
	$re = AM_farmGold_moveAnimalsToBuilding($building, 1, $farm, $IDs, $itemName, $positions);
	if(@is_array($re)) $farm = $re;
	else return false;

	//=================================================================
	// n0m mod: to skip stables harvesting (if 1 cycle set in FarmGold)
	if($doHarvestBuilding){
		AddLog2('ToolBox: Harvesting Building');
		Do_Farm_Work(array($building), 'harvest');
	}else{
		AddLog2('ToolBox: SKIPPED Harvesting Building');
	}
	//=================================================================

	AM_farmGold_deletIDs();
	return $farm;
}

function AM_farmGold_moveAnimalsToFarm($building, $left, $getFarm) {
	global $TB_settings;
	global $AM_settings;
	$tmpNames = array();
	$tmpPositions = array();
	$return = array();
	$farm = array();
	$farm = $getFarm;
	$position = array();
	$i = 0;
	$u = 0;

	foreach(AM_animalInBuilding($building) as $animal => $num) {
		while($left > 0 && $num > 0) {
			$position = TB_findEmptySpotSection($animal,$farm,2,2);
			if($position == false) {
				//AddLog2('can\'t find position in section -> will use whole farm');
				$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm,2,2);
			}
			if (empty($animal)) continue;
			$amf = CreateMultAMFRequest($amf, $i, 'place', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][$i]['params'][1]['className'] 		= 'Animal';
			$amf->_bodys[0]->_value[1][$i]['params'][1]['itemName']  		= $animal;
			$tmpNames[$i] = $animal;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['direction']     	= 1;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['x']   	= $position['x'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['y'] 	= $position['y'];
			$tmpPositions[$i]['x'] = $position['x'];
			$tmpPositions[$i]['y'] = $position['y'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['z'] 	= 0;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['deleted']			= false;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['state']   			= $building['state'];
			$amf->_bodys[0]->_value[1][$i]['params'][1]['tempId']    		= -1;
			$amf->_bodys[0]->_value[1][$i]['params'][1]['plantTime'] 		= time() - 82800 - 1;

			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isStorageWithdrawal'] 	= $building['id'];
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift']      			= false;
			$farm[$position['x']][$position['y']] = false;
			if($building['className'] <> 'ChickenCoopBuilding')	{
				$farm[$position['x'] + 1][$position['y']] = false;
				$farm[$position['x']][$position['y'] + 1] = false;
				$farm[$position['x'] + 1][$position['y'] + 1] = false;
			}

			$num--;
			$left--;
			$i++;

			if($i >= $TB_settings['TB_speedAction']) {
				$tmp = TB_sendRequest($amf);
				$amf2 = $tmp['amf2'];
				$res = $tmp['res'];
				$i2 = 0;
				while($i2 < $i) {
					$return['IDs'][$u] = $amf2->_bodys[0]->_value['data'][$i2]['data']['id'];
					$return['itemName'][$u] = $tmpNames[$i2];
					$return['positions'][$u]['x'] = $tmpPositions[$i2]['x'];
					$return['positions'][$u]['y'] = $tmpPositions[$i2]['y'];
					AM_farmGold_addIDs($return['IDs'][$u], $building);
					$i2++;
					$u++;
				}

				if ( $res == 'OK' ) {
					AddLog2( '-> ' . $res . ' ' . $left . ' left');
					$i = 0;
				} else {
					AddLog2( "ERROR - $res" );
					return false;
				}
			}
		}
	}

	$return['farm'] = $farm;

	if($i > 0) {
		$tmp = TB_sendRequest($amf);
		$amf2 = $tmp['amf2'];
		$res = $tmp['res'];

		$i2 = 0;
		while($i2 < $i) {
			$return['IDs'][$u] = $amf2->_bodys[0]->_value['data'][$i2]['data']['id'];
			$return['itemName'][$u] = $tmpNames[$i2];
			$return['positions'][$u]['x'] = $tmpPositions[$i2]['x'];
			$return['positions'][$u]['y'] = $tmpPositions[$i2]['y'];
			AM_farmGold_addIDs($return['IDs'][$u], $building);
			$i2++;
			$u++;
		}

		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return $return;
		} else {
			AddLog2( "ERROR - $res" );
			AddLog2(print_r($amf2,true));
			return false;
		}
	} else return $return;
}

function AM_farmGold_moveAnimalsToBuilding($building, $getLeft, $getFarm, $getIDs , $getNames, $getPositions) {
	global $TB_settings;
	global $AM_settings;
	if ($getLeft == 0) return;
	$i = 0;
	$farm = array();
	$farm = $getFarm;
	$left = $getLeft;
	$index  = 0;
	while($left > 0) {
		if (empty($getNames[$index])) continue;
		$amf = CreateMultAMFRequest($amf, $i, 'store', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][$i]['params'][1]['id']              			= $building['id'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['contents'][0]['num']   	= $index;
		//$amf->_bodys[0]->_value[1][$i]['params'][1]['contents'][0]['item']	= '';
		$amf->_bodys[0]->_value[1][$i]['params'][1]['itemName']          		= $building['itemName'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['direction']              	= 0;
		$amf->_bodys[0]->_value[1][$i]['params'][1]['buildTime']				= 0;
		$amf->_bodys[0]->_value[1][$i]['params'][1]['className']            	= $building['className'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['x']       		= $building['position']['x'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['y']       		= $building['position']['y'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['position']['z']      		= 0;
		$amf->_bodys[0]->_value[1][$i]['params'][1]['deleted']           		= false;
		$amf->_bodys[0]->_value[1][$i]['params'][1]['state']              		= $building['state'];
		$amf->_bodys[0]->_value[1][$i]['params'][1]['tempId']             		= 'NaN';
		//$amf->_bodys[0]->_value[1][$i]['params'][1]['plantTime']          		= $time;
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['resource']   		= $getIDs[$index];
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['cameFromLocation'] 	= 0;
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedItemCode']   	= TB_GetCodeByName($getNames[$index]);
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedClassName']   = 'animal';
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift']      		= false;
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['storedItemName'] 	= $getNames[$index];

		$farm[$getPositions[$index]['x']][$getPositions[$index]['y']] = true;
		$farm[$getPositions[$index]['x'] + 1][$getPositions[$index]['y']] = true;
		$farm[$getPositions[$index]['x']][$getPositions[$index]['y'] + 1] = true;
		$farm[$getPositions[$index]['x'] + 1][$getPositions[$index]['y'] + 1] = true;

		$i++;
		$left--;
		$index++;

		if($i >= $TB_settings['TB_speedAction']) {
			$res = RequestAMF( $amf );
			if ( $res == 'OK' ) {
				AddLog2( '-> ' . $res . ' ' . $left . ' left');
				$i = 0;
			} else {
				AddLog2( "ERROR - $res" );
				return false;
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return $farm;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return $farm;
}

function AM_farmGold_harvestWithoutFirst($getIDs , $getNames, $getPositions) {
	$animals = array();
	$count = count($getIDs) - 1;  //index starts with 0
	for ($x = 0; $x < $count; $x++)
	{
		$animals[$x]['id'] = $getIDs[$x];
		$animals[$x]['itemName'] = $getNames[$x];
		$animals[$x]['className'] = 'animals';
		$animals[$x]['position']['x'] = $getPositions[$x]['x'];
		$animals[$x]['position']['y'] = $getPositions[$x]['y'];
		$count--;
	}
	Do_Farm_Work($animals, 'harvest');
}

//form
function AM_farmGold_showOptions() {
	global $TB_settings;
	global $AM_settings;
	echo '<table width="100%">';
	echo '<tr>';
	$count = -1;
	foreach($AM_settings as $className => $animals) {
		// begin: n0m `version` chkbox output quick fix
		if ($className <> 'version'){

			$count++;
			if($count%4 == 0) {
				echo '</tr>';
				echo '<tr>';
			}
			echo '<td width="25%">';
			echo '<input type="text" name="farmGold_'.$className.'" value="' . @$TB_settings['AM_farmGold_'.$className] . '" size="2" > '.$className.'<br>';
			echo '</td>';
		}	//<-- end: n0m `version` chkbox output quick fix

	}
	echo '</tr>';
	echo '</table>';
}

//restore
function AM_farmGold_showRestore() {
	global $TB_settings;
	$info = AM_farmGold_getIDs();
	$IDs = $info['IDs'];
	$building = $info['building'];
	$checked = $TB_settings['AM_farmGoldRestore'] ? 'checked' : '';
	echo "<input type='checkbox' name='farmGoldRestore' onclick='this.form.submit();' value=true ".$checked." > use restore function? (should be checked)<br>";
	if($IDs == 0) {
		echo 'no IDs! :)';
	} else {
		echo 'found ' . count($IDs) . ' IDs <br>';
		echo 'building: ' . GetNameByItem($building['itemName']) . '<br>';
		$objects = array();
		$objects = TB_loadObjects();
		$i = 1;
		echo '<table border="1px">';
		echo '<tr>';
		foreach($IDs as $ID) {
			$obj = TB_getObject($ID, $objects);
			echo '<td>&raquo;' . $obj['itemName'] . '</td>';
			if($i%8 == 0) echo '</tr><tr>';
			$i++;
		}
		echo '</tr>';
		echo '</table>';
	}
}

function AM_farmGold_restore($getInfo, $getFarm) {
	$IDs = $getInfo['IDs'];
	$building = $getInfo['building'];
	$farm = $getFarm;
	$objects = array();
	$objects = TB_loadObjects();

	$itemName = array();
	$newIDs = array();
	$positions = array();

	Addlog2('ToolBox: Restoring Farm');

	foreach($objects as $object) {
		if(in_array($object['id'], $IDs)) {
			$itemName[] = $object['itemName'];
			$newIDs[] = $object['id'];
			$positions[] = $object['position'];
		}
	}

	$re = AM_farmGold_moveAnimalsToBuilding($building, count($IDs), $farm, $newIDs, $itemName, $positions);
	if(is_array($re)) $farm = $re;
	else return false;
	AM_farmGold_deletIDs();
	//Addlog2('-> OK');
	return $farm;
}

function AM_farmGold_saveIDs($getIDs, $getBuilding) {
	if(is_array($getIDs)) save_array(array('building' => $getBuilding, 'IDs' => $getIDs),AnimalMover_farmGold_IDs);
}

function AM_farmGold_getIDs() {
	$farmGold_IDs = array();
	$farmGold_IDs = load_array(AnimalMover_farmGold_IDs);
	if(!is_array($farmGold_IDs) || count($farmGold_IDs) == 0) return 0;
	else return $farmGold_IDs;
}

function AM_farmGold_deletIDs() {
	@unlink(ToolBox_path . $_SESSION['userId'] . '_' . AnimalMover_farmGold_IDs);
}

function AM_farmGold_addIDs($getIDs, $getBuilding) {
	$info = array();
	$info = AM_farmGold_getIDs();
	if($info == 0) {
		save_array(array('building' => $getBuilding, 'IDs' => (array)$getIDs),AnimalMover_farmGold_IDs);
	} else {
		$IDs = $info['IDs'];
		$building = $info['building'];
		if($building['id'] == $getBuilding['id']) $IDs = array_merge($IDs, (array)$getIDs);
		save_array(array('building' => $building, 'IDs' => $IDs),AnimalMover_farmGold_IDs);
	}
}
?>
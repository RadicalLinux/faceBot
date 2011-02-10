<?php
// ------------------------------------------------------------------------------
// Do_Farmhands_Arborists
// ------------------------------------------------------------------------------
function Do_Farmhands_Arborists($vWhat) {
	global $vCnt63000;
	if($vCnt63000<63000) $vCnt63000=63000;

	$amf = CreateRequestAMF('use', 'WorldService.performAction');

	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']              = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                 = -1;
	if($vWhat=='farmhands') {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']          = 'CHarvestAnimals';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']           = 'consume_farm_hands';
	} else {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']          = 'CHarvestTrees';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']           = 'consume_arborists';
	}
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['id']                     = $vCnt63000 ++;

	$amf->_bodys[0]->_value[1][0]['params'][2]['targetUser']             = $_SESSION['userId'];
	$amf->_bodys[0]->_value[1][0]['params'][2]['isFree']                 = false;
	$amf->_bodys[0]->_value[1][0]['params'][2]['storageID']              = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2]['isGift']                 = true;

	$res=RequestAMF($amf);

	if($res=='OK') {
		AddLog($vWhat." OK");
		AddLog2($vWhat." OK");
	} else {
		AddLog($vWhat." error: ".$res);
		AddLog2($vWhat." error: ".$res);
	}
	return true;

}


// ------------------------------------------------------------------------------
// Do_Biplane_Instantgrow
// ------------------------------------------------------------------------------
// ------------------------------------------------------------------------------
// Do_Check_Lonlyanimals
// ------------------------------------------------------------------------------
function Do_Check_Lonlyanimals() {

	$amf = CreateRequestAMF('', 'LonelyCowService.createLonelyAnimal');
	$amf->_bodys[0]->_value[1][0]['params'][0]    = array();
	$amf->_bodys[0]->_value[2]                    = 0;
	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);

	if($res=='OK') {
		@$vAnimal = $amf2->_bodys[0]->_value['data'][1];
		if(strlen($vAnimal)>0) {
			AddLog("lonlyanimal found: ".$vAnimal);
			AddLog2("lonlyanimal found: ".$vAnimal);
		} else {
			AddLog2("no lonlyanimal found");
		}
	} else {
		AddLog2("lonlyanimal error: ".$res);
	}
	return $res;

}

// ------------------------------------------------------------------------------
// Do_Farm_Work
//  @param array $plots
//  @param string $action (optional)
// ------------------------------------------------------------------------------
function Do_Farm_Work($plots, $action = "harvest") {
	global $need_reload;
	$px_Setopts = LoadSavedSettings();

	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1))
	$px_Setopts['bot_speed'] = 1;


	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED)
	$px_Setopts['bot_speed'] = PARSER_MAX_SPEED;

	$count = count($plots);

	if ($count > 0) {
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();

		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';

		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;
		$i = 0;

		foreach($plots as $plot) {
			$amf->_bodys[0]->_value[1][$i]['functionName'] = "WorldService.performAction";
			$amf->_bodys[0]->_value[1][$i]['params'][0] = $action;
			$amf->_bodys[0]->_value[1][$i]['sequence'] = GetSequense();

			$amf->_bodys[0]->_value[1][$i]['params'][1] = $plot;
			$amf->_bodys[0]->_value[1][$i]['params'][2] = array();

			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 0;

			if (@!$plotsstring)
			$plotsstring = $plot['itemName'] . " " . GetPlotName($plot);
			else
			$plotsstring = $plotsstring . ", " . $plot['itemName'] . " " . GetPlotName($plot);

			if (@!$OKstring)
			$OKstring = $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);
			else
			$OKstring = $OKstring . "\r\n" . $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);

			$i++;

			if (($i == $px_Setopts['bot_speed']) || ($i >= $count)) {
				$count -= $i;
				$i = 0;
				AddLog2($action . " " . $plotsstring);
				$res = RequestAMF($amf);
				AddLog2("result $res");
				unset($amf->_bodys[0]->_value[1]);
				$need_reload = true;

				if ($res === 'OK') {
					AddLog($OKstring);
				} else {
					if ($res) {
						AddLog("Error: $res on " . $OKstring);
						if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
							DoInit();
						}
					}
				}
				unset($plotsstring, $OKstring);
			}
		}
	}
}

// ------------------------------------------------------------------------------
// Do_Farm_Work_Plots
//  @param array $plots
//  @param string $action (optional)
// ------------------------------------------------------------------------------
function Do_Farm_Work_Plots($plots, $action = "harvest") {
	global $vCnt63000;
	list(, , , , , , , , , , $fuel) = explode(';', fBGetDataStore('playerinfo'));
	if(@strlen($vCnt63000)==0) $vCnt63000=63000;
	$px_Setopts = LoadSavedSettings();

	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1))
	$px_Setopts['bot_speed'] = 1;

	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED)
	$px_Setopts['bot_speed'] = PARSER_MAX_SPEED;

	$vMaxEquip=16;

	if ((@!$fuel) || (@$fuel < 0))
	$fuel = 0;

	if ($fuel == 0 && $action == 'tractor') {
		return;
	}
	if ($fuel == 0) {
		Do_Farm_Work($plots, $action);
		return;
	}

	while(count($plots)>0) {
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();

		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';

		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;

		$vCntSpeed=0;
		while(count($plots)>0 && $vCntSpeed<$px_Setopts['bot_speed'] && $fuel>0) {
			$amf->_bodys[0]->_value[1][$vCntSpeed]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vCntSpeed]['functionName'] = "EquipmentWorldService.onUseEquipment";
			if ($action == 'tractor') {
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = 'plow';
			} else {
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = $action;
			}

			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['id'] = -1;
			if ($action == 'harvest')
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'V1:32';  # fully expanded harvester
			if ($action == 'tractor' || $action == 'plow')
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'T1:32';  # fully expanded tractor
			if ($action == 'place')
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'S1:32';  # fully expanded seeder

			$vCntEquip=0; $vSeed=''; $vLastSeed='';
			while(count($plots)>0 && $vCntEquip<$vMaxEquip && $fuel>0) {
				$vPlot=array_pop($plots);
				if ($action == 'place') {
					$vSeed=$vPlot['itemName'];
					if($vLastSeed=='') {
						$vLastSeed=$vSeed;
					} elseif($vLastSeed<>$vSeed) {
						array_push($plots,$vPlot);
						break;
					}
				}

				if (@!$plotsstring)
				$plotsstring = $vPlot['itemName'] . " " . GetPlotName($vPlot);
				else
				$plotsstring = $plotsstring . ", " . $vPlot['itemName'] . " " . GetPlotName($vPlot);

				if (@!$OKstring)
				$OKstring = $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);
				else
				$OKstring = $OKstring . "\r\n" . $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);

				$fuel--;
				if ($action == 'tractor') {
					$vCnt63000++;
					$vPlot['id'] = $vCnt63000;
					$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip] = $vPlot;
				} else {
					$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip]['id'] = $vPlot['id'];
				}

				$vCntEquip++;

			}

			if ($action == 'tractor' || $action == 'harvest' || $action == 'plow')
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'place')
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = $vSeed;

			$vCntSpeed++;
		}

		AddLog2($action . " " . $plotsstring);

		$res = RequestAMF($amf);
		AddLog2("result $res");
		unset($amf->_bodys[0]->_value[1]);

		if ($res === 'OK') {
			AddLog($OKstring);
			$need_reload = true;
		} else {
			if ($res) {
				AddLog("Error: $res on " . $OKstring);
				if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
					DoInit();
				}
			}
		}
		unset($plotsstring, $OKstring);


	}


	$px_Setopts = LoadSavedSettings();

	if ($action == 'plow' || $action == 'tractor')
	$px_Setopts['fuel_plow'] = $fuel;

	if ($action == 'place')
	$px_Setopts['fuel_place'] = $fuel;

	if ($action == 'harvest')
	$px_Setopts['fuel_harvest'] = $fuel;

	SaveSettings($px_Setopts);

}

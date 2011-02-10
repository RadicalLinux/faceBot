<?php
class fvFarmer
{
	private function _refreshWorld()
	{
		$this->_fvGetBotSettings();
		$this->haveWorld = false;
		$this->zErrCGen = array(
		0 => 'OK', 1 => 'Error - Authorization', 2=> 'Error - User Data Missing',
		3 => 'Error - Invalid State', 4 => 'Error - Invalid Data', 5 => 'Error - Missing Data',
		6 => 'Error - Action Class Error', 7 => 'Error - Action Method Error',
		8 => 'Error - Resource Data Missing', 9 => 'Error - Not Enough Money',
		10 => 'Error - Outdated Game Version', 25 => 'Error - General Transport Failure',
		26 => 'Error - No User ID', 27 => 'Error - No Session', 28 => 'Retry Transaction',
		29 => 'Force Reload');
		$this->zErrCBushels = array(
		0 => 'OK', 1 => 'Bushel Expired', 2 => 'User not Friend', 3 => 'Bushel not Found',
		4 => 'Bags Exhausted', 5 => 'Silo Full');
		$this->haveWorld = true;
		$craftstate = unserialize(fBGetDataStore('craftstate'));
		$this->fmBushels = @$craftstate['craftingItems'];
		$this->fmGoodState = @$craftstate['craftingSkillState']['recipeStates'];
		$this->availBushels = unserialize(fBGetDataStore('availbushels'));
		$this->availGoods = unserialize(fBGetDataStore('availgoods'));
		$this->bMaxCap = @$craftstate['maxCapacity'];
		$this->bDailyBags = @$craftstate['shoppingState']['maxDailyBags'];
		$this->bDailyPurch = @$craftstate['shoppingState']['maxDailyPurchases'];
		$this->cDailyBags = @$craftstate['shoppingState']['maxDailyCraftingBags'];
		$this->cDailyPurch = @$craftstate['shoppingState']['maxDailyCraftingPurchases'];
		$this->bConsumed = @$craftstate['shoppingState']['consumedBags'];
		$this->cConsumed = @$craftstate['shoppingState']['consumedCraftingBags'];
		foreach(@$this->fmBushels AS $av)
		{
			$bushels[$av['itemCode']] = $av['quantity'];
		}
		$this->myBushels = array_sum(@$bushels);

	}

	private function _fvFarmer_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = $this->_fvFarmerDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fvFarmerDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "');";
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}
		$q = $this->_fvFarmerDBM->query('SELECT * FROM settings2 LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings2 Table');
			$fvSQL = 'CREATE TABLE
						settings2 (
						settings2_type TEXT,
						settings2_name TEXT,
						settings2_value INTEGER
			)';
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}

		$q = $this->_fvFarmerDBM->query('SELECT * FROM license LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating License Table');
			$fvSQL = 'CREATE TABLE
                		license (
                		license_className CHAR(250),
                		license_duration INTEGER,
                		license_startdate TIMESTAMP,
                		license_enddate TIMESTAMP,
                		license_code CHAR(4),
                		license_type CHAR(250),
                		license_requirement CHAR(250)
			)';
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}
		$q = $this->_fvFarmerDBM->query('SELECT * FROM seedinfo LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Seed Info Table');
			$fvSQL = 'CREATE TABLE
                		seedinfo (
                		seedinfo_name TEXT PRIMARY KEY,
                		seedinfo_code CHAR(2),
                		seedinfo_level INTEGER,
                		seedinfo_startdate TIMESTAMP,
                		seedinfo_enddate TIMESTAMP,
                		seedinfo_masttime INTEGER,
                		seedinfo_growtime INTEGER,
                		seedinfo_profhr DOUBLE,
                		seedinfo_xphr DOUBLE,
                		seedinfo_curramt INTEGER,
                		seedinfo_mastery INTEGER,
                		seedinfo_mastmax INTEGER,
                		seedinfo_license CHAR(5)
			)';
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}

		$q = $this->_fvFarmerDBM->query('SELECT * FROM fmbushels LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Farmers Market Bushels Table');
			$fvSQL = 'CREATE TABLE
              			fmbushels (
                		fmbushels_id INTEGER PRIMARY KEY,
                		fmbushels_className CHAR(250),
                		fmbushels_type CHAR(250),
                		fmbushels_itemName CHAR(25),
                		fmbushels_itemRealName CHAR(250),
                		fmbushels_itemCode CHAR(4),
                		fmbushels_itemCount INTEGER,
                		fmbushels_iconURL CHAR(250)
			)';
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}
	}

	//Function fvFarmer class initializer
	function fvFarmer($inittype = '')
	{
		list($this->level, $this->gold, $this->coin, $this->wsizeX, $this->wsizeY, $this->uname, $locale, $this->tileset, $this->wither, $this->xp, $this->fuel, $this->flashRevision) = explode(';', fBGetDataStore('playerinfo'));
		$this->userId = $_SESSION['userId'];
		$this->error = '';
		$this->haveWorld = false;

		if(!is_numeric($this->userId))
		{
			$this->error = "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fvFarmerDBM = new SQLiteDatabase(fvFarmer_Path . PluginF(fvFarmer_Main));
		if(!$this->_fvFarmerDBM)
		{
			$this->error = 'fvFarmer - Database Error';
			return;
		}
		$this->_fvFarmer_checkDB();//Database doesn't exist, create
		//Get Settings
		$this->settings = $this->fvGetSettings();
		if($inittype == 'formload')
		{
			$this->overrides = $this->fvGetOverRides();
			return;
		}
		//Load the world from Z*
		$this->_refreshWorld();

		if($this->haveWorld === true)
		{
			if($this->settings === false)
			{
				$this->_fvFarmer_checkDB();//Database doesn't exist, create
				$this->_fvUpdateSettings();//Insert initial settings
				$this->_fvUpdateSeedDB();//Update the World
				$this->error = 'Please allow fvFarmer to run a cycle to update all settings';
				return;
			}
			if($this->settings['flashRevision'] != $this->flashRevision ||
			$this->settings['unitversion'] != $this->flashRevision)
			{
				$this->_fvUpdateUnits();//Update the Units file
				$this->settings = $this->fvGetSettings();
			}
			$this->_fvUpdateSeedDB();//Update the World
			$this->_fvUpdateSettings();//Update the settings
			$this->overrides = $this->fvGetOverRides();
			$this->fvBestVehicles();
		}
	}

	function fvGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		$q = $this->_fvFarmerDBM->query($fvSQL);
		if($q !== false)
		{
			$results = $q->fetchAll(SQLITE_ASSOC);
			foreach($results as $item)
			{
				$newresults[$item['settings_name']] = $item['settings_value'];
			}
			return $newresults;
		}
		return false;
	}
	function fvGetSettings2($type='')
	{
		$fvSQL = 'SELECT * FROM settings2 WHERE settings2_type="' . $type . '"';
		$q = $this->_fvFarmerDBM->query($fvSQL);
		if($q !== false)
		{
			$results = $q->fetchAll(SQLITE_ASSOC);
			foreach($results as $item)
			{
				$newresults[$item['settings2_name']] = $item['settings2_value'];
			}
			return @$newresults;
		}
		return false;
	}

	function fvSeedUnits($type = '')
	{
		$results = Units_GetByType('seed', true);
		$mastcnt = unserialize(fBGetDataStore('cropmastery'));
		$maststar = unserialize(fBGetDataStore('cropmasterycnt'));
		foreach ($results as $key=>$seed)
		{
			@$results[$key]['mastcnt'] = $mastcnt[$seed['code']];
			@$results[$key]['maststar'] = $maststar[$seed['code']];
		}
		switch ($type)
		{
			case 'mastered':
				foreach ($results as $key=>$seed)
				{
					if (!isset($seed['mastery']) || $seed['mastery'] != 'true' || @$seed['mastcnt'] < @$seed['masterymax']) {
						unset($results[$key]);
					}
				}
				break;
			case 'unmastered':
				foreach ($results as $key=>$seed)
				{
					if (isset($seed['mastery']) && $seed['mastery'] == 'true') {
						if (@$seed['mastcnt'] >= @$seed['masterymax']) {
							unset($results[$key]);
						}
					} else {
						unset($results[$key]);
					}
				}
				break;
			case 'nomastery':
				foreach ($results as $key=>$seed)
				{
					if (isset($seed['mastery']) && $seed['mastery'] == 'true') {
						unset($results[$key]);
					}
				}
				break;

		}
		$results = $this->subval_sort($results, 'realname');
		return $results;
	}

	function fvAnimalUnits($type = '')
	{
		$results = Units_GetByType('animal', true);
		switch ($type)
		{
			case 'harvest':
				foreach ($results as $key=>$animal)
				{
					$animal['realname'] = !isset($animal['realname']) ? $animal['name'] : $animal['realname'];
					if (@$animal['action'] != 'harvest') {
						unset($results[$key]);
					} else {
						$newarray[$animal['realname']] = $animal;
					}
				}
				break;
			case 'transform':
				foreach ($results as $key=>$animal)
				{
					if (@$animal['action'] != 'transform') {
						unset($results[$key]);
					} else {
						$newarray[$animal['realname']] = $animal;
					}
				}
				break;
		}
		ksort($newarray);
		return $newarray;
	}

	function fvBuildingUnits()
	{
		$results = Units_GetByType('building', true);
		foreach ($results as $key=>$building)
		{
			if (!isset($building['growTime']) || $building['className'] == 'MysterySeedling') {
				unset($results[$key]);
			} else {
				$newarray[$building['name']] = $building;
			}
		}
		ksort($newarray);
		return $newarray;
	}

	function fvTreeUnits()
	{
		$results = Units_GetByType('tree', true);
		foreach ($results as $key=>$tree)
		{
			if (!isset($tree['growTime'])) {
				unset($results[$key]);
			} else {
				$newarray[$tree['realname']] = $tree;
			}
		}
		ksort($newarray);
		return $newarray;
	}

	function fvCraftingUnits()
	{
		$results = Units_GetByClass('CCrafted', true);
		foreach ($results as $key=>$craft)
		{
			$name = Units_GetRealnameByField($craft['subtype'], 'craftType');
			$newarray[$name][] = $craft;
		}
		ksort($newarray);
		return $newarray;
	}

	function fvQuestUnits()
	{
		$results = Quests_GetAll();
		foreach ($results as $key=>$quest)
		{
			if (!isset($quest['id'])) continue;
			if (!isset($quest['deprecated']) || $quest['deprecated'] == 'true') continue;
			$quest['title'] = str_replace("'", "''", $quest['title']);
			$name = Quests_GetRealnameByTitle($quest['title']);
			$quest['title'] = str_replace('_Title', '', $quest['title']);
			$quest['title'] = str_replace('bushelJob_', '(Craft)', $quest['title']);
			$name = (!empty($name)) ? $name : $quest['title'];
			$results[$key]['realname'] = $name;
			$newarray[$name] = $quest;
		}
		ksort($newarray);
		return $newarray;
	}

	private function _fvUpdateSeedDB()
	{
		$this->_fvFarmerDBM->queryExec('DELETE FROM seedinfo');
		$this->_fvFarmerDBM->queryExec('BEGIN TRANSACTION;');
		$results = Units_GetByType('seed', true);
		$mastcnt = unserialize(fBGetDataStore('cropmastery'));
		foreach(GetObjects() as $world)
		{
			if ($world['className'] == 'Plot' && $world['state'] == 'planted')
			{
				if ($world['isJumbo'] == 1) {
					@$seedcnt[$world['itemName']] = $seedcnt[$world['itemName']] + 2;
				} else {
					@$seedcnt[$world['itemName']]++;
				}
			}
		}
		foreach ($results as $key=>$seed)
		{
			$seed['mastcnt'] = @$mastcnt[$seed['code']] + @$seedcnt[$seed['name']];
			$seed['mastery'] = (isset($seed['mastery']) && $seed['mastery'] == 'true') ? 1 : 0;
			$gtime = round($seed['growTime'] * 23);
			$masttime = $gtime * (@$seed['masterymax'] - $seed['mastcnt']);
			$profit = $seed['coinYield'] - $seed['cost'];
			$fvSQL = 'INSERT OR REPLACE INTO seedinfo (seedinfo_name, seedinfo_code, seedinfo_level,';
			$fvSQL .= 'seedinfo_startdate, seedinfo_enddate, seedinfo_masttime, seedinfo_growtime,';
			$fvSQL .= 'seedinfo_profhr, seedinfo_xphr, seedinfo_curramt, seedinfo_mastery, seedinfo_mastmax,';
			$fvSQL .= 'seedinfo_license)';
			$fvSQL .= "values('" . $seed['name'] . "', '" . $seed['code'] . "', '" . @$seed['requiredLevel'] . "', '";
			$fvSQL .= strtotime(@$seed['limitedStart']) . "', '" . strtotime(@$seed['limitedEnd']) . "', '" . $masttime . "', '";
			$fvSQL .= $seed['growTime'] . "', '" . number_format($profit / $gtime, 2) . "', '" . number_format($seed['plantXp'] / $gtime, 2) . "', '";
			$fvSQL .= $seed['mastcnt'] . "', '" . $seed['mastery'] . "', '" . @$seed['masterymax'] . "', '";
			$fvSQL .= @$seed['license'] . "');";
			$this->_fvFarmerDBM->queryExec($fvSQL);

		}
		$this->_fvFarmerDBM->queryExec('COMMIT TRANSACTION;');
		$this->_fvFarmerDBM->queryExec('DELETE FROM fmbushels');
		$this->_fvFarmerDBM->queryExec('BEGIN TRANSACTION;');
		foreach($this->fmBushels  as $key => $FMBushels)
		{
			$result = Units_GetUnitByCode($FMBushels['itemCode']);
			$result['realname'] = str_replace("'", "''", $result['realname']);
			$gfvSQL = "INSERT INTO fmbushels(fmbushels_className, fmbushels_itemName, fmbushels_itemRealName, fmbushels_iconURL,  fmbushels_itemCode, fmbushels_type, fmbushels_itemCount)
							values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . $result['iconurl'] . "', '" . $FMBushels['itemCode'] . "','" . @$result['type'] . "','" . $FMBushels['quantity'] . "');";
			$this->_fvFarmerDBM->queryExec($gfvSQL);
		}
		$this->_fvFarmerDBM->queryExec('COMMIT TRANSACTION;');
		//Database Cleanup Routine
		if(!isset($this->settings['lastclean']) || (time() - (int)$this->settings['lastclean'] >= 14400))
		{
			AddLog2('fvFarmer is doing DB Cleanup');
			$this->_fvFarmerDBM->query('vacuum');

			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('lastclean','" . time() . "')";
			$this->_fvFarmerDBM->queryExec($fvSQL);
		}
	}

	function fvFMCounts($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmarket ';
		if(!empty($itype))
		{
			$fvSQL .= "WHERE fmarket_itemCode = '$itype' ";
		}
		$fvSQL .= 'ORDER BY fmarket_itemRealName';
		$q = $this->_fvFarmerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	function fvFMBCounts($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmbushels ';
		if(!empty($itype))
		{
			$fvSQL .= "WHERE fmbushels_itemCode = '$itype' ";
		}
		$fvSQL .= 'ORDER BY fmbushels_itemRealName';
		$q = $this->_fvFarmerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fvFMBCounts2($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmbushels ';
		$fvSQL .= "WHERE fmbushels_itemCode = '$itype' ";
		$q = $this->_fvFarmerDBM->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return @$results[0]['fmbushels_itemCount'];
	}


	function fvDoSettings()
	{
		$showxml = (isset($_GET['showxml'])) ? 1 : 0;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('showxml','$showxml');";
		$this->_fvFarmerDBM->queryExec($fvSQL);
	}


	private function _fvUpdateUnits()
	{
		$doc3 = file_get_contents('./farmville-xml/' . $this->flashRevision . '_gameSettings.xml');
		if (!$doc3) AddLog2('Unable to get gameSettings.xml');
		if(!$doc3) return;
		AddLog2('fvFarmer is updating licenses');
		//$newimgs = load_array('newimages.txt');
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc3);
		$licenses = $xmlDoc->getElementsByTagName('itemLicense');
		$fvSQL = "DELETE FROM license";
		$this->_fvFarmerDBM->queryExec($fvSQL);
		foreach ($licenses as $lic)
		{
			$ltype = @$lic->getAttribute('type');
			$lcode = @$lic->getAttribute('code');
			$dur = @$lic->getElementsByTagName('duration');
			$duration = @$dur->item(0)->nodeValue;
			$sd = @$lic->getElementsByTagName('startDate');
			$startd = @$sd->item(0)->nodeValue;
			$startd = (!empty($startd)) ? strtotime($startd) : '';
			$ed = @$lic->getElementsByTagName('endDate');
			$endd = @$ed->item(0)->nodeValue;
			$endd = (!empty($startd)) ? strtotime($endd) : '';
			$ul = @$lic->getElementsByTagName('unlock');
			$lcname = @$ul->item(0)->getAttribute('className');
			$lcreq = @$ul->item(0)->getAttribute('requirement');
			$fvSQL = "INSERT OR REPLACE INTO license(license_className, license_code, license_type,";
			$fvSQL .= "license_duration, license_startdate, license_enddate, license_requirement) ";
			$fvSQL .= "values('$lcname', '$lcode', '$ltype', '$duration', '$startd', '$endd', '$lcreq');";
			$this->_fvFarmerDBM->queryExec($fvSQL, $error);
			if (!empty($error)) { AddLog2($error); echo $fvSQL . "\n"; }
		}
		unset($xmlDoc);

		AddLog2('fvFarmer has finished updating units');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('unitversion','". $this->flashRevision . "')";
		$this->_fvFarmerDBM->queryExec($fvSQL);
		unset($xmlDoc);
		error_reporting(0);
		ini_set('display_errors', true);

	}
	private function _fvUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('level','" . $this->level . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('mybushels','" . $this->myBushels . "');";
		$this->_fvFarmerDBM->queryExec($fvSQL);
	}

	private function _fvAMFSend($amf)
	{
		$serializer = new AMFSerializer();
		$result3 = $serializer->serialize($amf);// serialize the data
		$answer = Request('', $result3);
		$amf2 = new AMFObject($answer);
		$deserializer2 = new AMFDeserializer($amf2->rawData);// deserialize the data
		$deserializer2->deserialize($amf2);// run the deserializer
		$doinit = 0;
		if (!isset($amf2->_bodys[0]->_value['data'][0])) { DoInit(); return false; }
		Check4Rewards($amf2);
		foreach (@$amf2->_bodys[0]->_value['data'] as $key => $returned)
		{
			$resp = $returned['errorType'];
			$err = $returned['errorData'];
			if ($resp == 28 || $resp == 29)
			{
				if ($err !='Remaining function') AddLog2('fvFarmer Error: ' . $resp . ' - ' . $err);
				unset($amf2->_bodys[0]->_value['data'][$key]);
				if ($doinit == 0)
				{
					DoInit();
				}
				$doinit = 1;
				if ($key == 0) return false;
			}
		}

		return $amf2;
	}



	private function _fvGetBotSettings()
	{
		//Get Settings From Bot
		if (file_exists(F('settings.txt')))
		{
			$settings_list = @explode(';', trim(file_get_contents(F('settings.txt'))));

			foreach ($settings_list as $setting_option)
			{
				$set_name = @explode(':', $setting_option);

				if (count($set_name) > 2)
				{
					$liststart = explode(':', $setting_option,3);
					$listopt = explode(':', $liststart[2]);
					$tired = count($listopt);

					for ($i=0; $i < $tired; $i=$i+2)
					{
						$tired2 = $i+1;
						$bot_settings[$liststart[0]][$listopt[$i]] = $listopt[$tired2];
					}
				}
				else
				{
					$bot_settings[$set_name[0]] = $set_name[1];
				}
			}
			$this->botspeed = ($bot_settings['bot_speed'] < 1) ? 1 : $bot_settings['bot_speed'];
		}
	}
	private function _fvCreateMultAMFRequest($amf, $cnt, $req = '',$func)
	{
		If ($cnt == 0)
		{
			$amf = new AMFObject("");
			$amf->_bodys[0] = new MessageBody();
			$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
			$amf->_bodys[0]->responseURI = '/1/onStatus';
			$amf->_bodys[0]->responseIndex = '/1';
			$amf->_bodys[0]->_value[0] = GetAMFHeaders();
			$amf->_bodys[0]->_value[2] = 0;
		}
		$amf->_bodys[0]->_value[1][$cnt]['sequence'] = GetSequense();
		$amf->_bodys[0]->_value[1][$cnt]['params'] = array();
		if ($req) $amf->_bodys[0]->_value[1][$cnt]['params'][0] = $req;
		if ($func) $amf->_bodys[0]->_value[1][$cnt]['functionName'] = $func;
		return $amf;
	}


	private function _fvDoBushels($bushcode = '')
	{
		$tmpArray = array();
		foreach ($this->fmBushels as $bushel)
		{
			if ($bushel['itemCode'] == $bushcode) {
				$result = Units_GetUnitByCode($bushel['itemCode']);
				Units_GetUnitByName($Plot['itemName']);
				$this->_fvUseBushel($result);
				break;
			}
		}
		if ($this->currbushel != $bushcode) {
			while($this->bBagsConsumed < $this->bDailyPurch)
			{
				if($this->myBushels >= $this->bMaxCap)
				{
					AddLog2('Max Bushels Reached - Not Buying');
					break;
				}
				$cnt = count($this->availBushels);
				$bcnt = 1;
				foreach($this->availBushels  AS $aBushels)
				{
					$uid = $aBushels['uid'];
					$bcnt++;
					if($this->myBushels >= $this->bMaxCap)
					{
						AddLog2('Max Bushels Reached - Not Buying');
						break 2;
					}
					foreach($aBushels['in'] AS $aInvent)
					{
						$code = $aInvent['ic'];
						if ($code != $bushcode) continue;
						if($aInvent['ts'] <= time())
						{
							AddLog2("Bushel: " . $code . " - Expired");
							continue;
						}
						if(isset($this->actBushel[$code]) && $aInvent['ts'] >= time()) {
							AddLog2("Bushel: " . $code . " - Submitting for Purchase");
							$amf = CreateRequestAMF('', 'CraftingService.onClaimMarketStallItem');
							$amf->_bodys[0]->_value[1][0]['params'][0] = $uid;
							$amf->_bodys[0]->_value[1][0]['params'][1] = trim($code);
							$tmpArray[0]['uid'] = $uid;
							$tmpArray[0]['code'] = $code;
							$amf2 = $this->_fvAMFSend($amf);
							if ($amf2 === false) continue;
							$resp2 = $amf2->_bodys[0]->_value['data'][0]['data']['responseCode'];
							if($resp2 == 0)
							{
								$bush = Units_GetUnitByCode($tmpArray[0]['code']);
								AddLog2("Buy " . $bush['realname'] . " from " . $tmpArray[0]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
								$this->bBagsConsumed++;
								$this->myBushels++;
								$this->_fvUseBushel($tmpArray[0]['code']);
								return;
							}
							else
							{
								AddLog2("Buy from " . $tmpArray[0]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
							}
						}
					}
				}
				break;
			}
		}
	}

	private function _fvUseBushel($bushel)
	{
		$item = array();
		$item['itemName'] = trim($bushel['name']);
		$item['id'] = 63000;
		$item['tempId'] = -1;
		$item['className'] = 'CBushel';
		$item['deleted'] = false;
		$item['direction'] = 0;
		$item['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
		$amf = CreateRequestAMF('use', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1] = $item;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = false;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['targetUser'] = $_SESSION['userId'];
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isFree'] = false;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['storageId'] = -4;
		$amf2 = $this->_fvAMFSend($amf);
		AddLog2('fvFarmer: Used ' . $bushel['name'] . " result: " . $this->zErrCGen[$amf2->_bodys[0]->_value['data'][0]['errorType']]);
		if ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0) $this->currbushel = $bushel['code'];
	}

	function fvMakeTime($ctime)
	{
		$days = $ctime / 86400;
		if ($days > 1)
		{
			$days = intval($days);
			$ctime = $ctime - ($days * 86400);
			$timestr = $days . ' days ';
		}
		$hours = $ctime / 3600;
		if ($hours > 1)
		{
			$hours = intval($hours);
			$ctime = $ctime - ($hours * 3600);
			$timestr .= $hours . ' hrs ';
		}
		$minutes = $ctime / 60;
		if ($minutes > 1)
		{
			$minutes = intval($minutes);
			$timestr .= $minutes . ' min';
		}
		return $timestr;
	}

	function fvSaveSettings($option, $items)
	{

		switch ($option)
		{
			case 'build':
				$fvSQL = "DELETE FROM settings2 WHERE settings2_type='build';";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				foreach ($items as $key => $value)
				{
					$fvSQL = "INSERT INTO settings2(settings2_type, settings2_name, settings2_value)";
					$fvSQL .= " VALUES('build', '$key', '$value');";
					$this->_fvFarmerDBM->queryExec($fvSQL);
				}
				break;
			case 'tree':
				$fvSQL = "DELETE FROM settings2 WHERE settings2_type='tree';";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				foreach ($items as $key => $value)
				{
					$fvSQL = "INSERT INTO settings2(settings2_type, settings2_name, settings2_value)";
					$fvSQL .= " VALUES('tree', '$key', '$value');";
					$this->_fvFarmerDBM->queryExec($fvSQL);
				}
				break;
			case 'animal':
				$fvSQL = "DELETE FROM settings2 WHERE settings2_type='animal';";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				foreach ($items as $key => $value)
				{
					$fvSQL = "INSERT INTO settings2(settings2_type, settings2_name, settings2_value)";
					$fvSQL .= " VALUES('animal', '$key', '$value');";
					$this->_fvFarmerDBM->queryExec($fvSQL);
				}
				break;
			case 'transform':
				$fvSQL = "DELETE FROM settings2 WHERE settings2_type='transform';";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				foreach ($items as $key => $value)
				{
					$fvSQL = "INSERT INTO settings2(settings2_type, settings2_name, settings2_value)";
					$fvSQL .= " VALUES('transform', '$key', '$value');";
					$this->_fvFarmerDBM->queryExec($fvSQL);
				}
				break;
			case 'general':
				$plow = (isset($items['plow'])) ? 1 : 0;
				$plant = (isset($items['plant'])) ? 1 : 0;
				$harvest = (isset($items['harvest'])) ? 1 : 0;
				$flyplane = (isset($items['flyplane'])) ? 1 : 0;
				$automast = (isset($items['automast'])) ? 1 : 0;
				$mastorder = $items['masteryorder'];
				$direction = $items['direction'];
				$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('plow','$plow');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('plant','$plant');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('harvest','$harvest');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flyplane','$flyplane');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('masteryorder','$mastorder');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('direction','$direction');";
				$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('automast','$automast');";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				break;
			case 'coop':
				$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('coop','$items');";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				break;
			case 'craft':
				$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('craft','$items');";
				$this->_fvFarmerDBM->queryExec($fvSQL);
				break;
		}
	}

	function fvFlyPlane()
	{
		if ($this->gold <= 0) {
			AddLog2('fvFarmer: Not Enough FV$ to Fly BiPlane');
			return;
		}
		$biplane = GetObjects('Airplane');
		if(count($biplane) == 0) {
			AddLog2("fvFarmer: No BiPlane Found");
			return;
		}
		$plot_list = GetObjects('Plot'); //get plots
		$cntplots = 0;
		foreach($plot_list as $plot)
		{
			if (($plot['state'] == 'planted'))
			$cntplots++;
		}
		unset($plot_list);

		if ($cntplots > 0)
		{
			unset($cntplots);
			$biplane=$biplane[0];
			AddLog2('fvFarmer: Flying BiPlane');
			$amf = CreateRequestAMF('instantGrow', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                = false;
			$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                 = 'NaN';
			$amf->_bodys[0]->_value[1][0]['params'][1]['className']              = $biplane['className'];
			$amf->_bodys[0]->_value[1][0]['params'][1]['state']                  = $biplane['state'];
			$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']               = $biplane['itemName'];
			$amf->_bodys[0]->_value[1][0]['params'][1]['direction']              = 0;
			$amf->_bodys[0]->_value[1][0]['params'][1]['id']                     = $biplane['id'];
			$amf->_bodys[0]->_value[1][0]['params'][1]['position']               = $biplane['position'];
			$amf->_bodys[0]->_value[1][0]['params'][2]                           = array();
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2->_bodys[0]->_value['data'][0]['success'] == 1) {
				$cost = $amf2->_bodys[0]->_value['data'][0]['cost'];
				AddLog2('fvFarmer: BiPlane Flew - Cost: ' . $cost . ' FV$');
			}
			unset($cntplots);
			DoInit();
			$this->_fvUpdateSeedDB();
		}

	}

	function fvHarvestCrops()
	{
		AddLog2('fvFarmer: Harvesting Crops');
		$allplots = GetObjects('Plot');
		$plots = array();
		foreach($allplots as $plot)
		{
			if (isset($this->overrides[$plot['itemName']]) && $this->overrides[$plot['itemName']][1] == 1) continue;
			if ($plot['state'] == 'grown' || $plot['state'] == 'ripe') $plots[] = $plot;
		}
		$plots = $this->subval_sort($plots,'itemName');
		unset($allplots);
		if (count($plots) > 0) {
			foreach ($plots as $plot)
			{
				@$newplots[$plot['itemName']][] = $plot;
			}
			foreach ($newplots as $iplots)
			{
				$this->currbushel = unserialize(fBGetDataStore('cbushel'));
				$this->_fvPlotWork($iplots, 'harvest');
			}

			DoInit();
			$this->_fvUpdateSeedDB();
		}
		unset($plots);
	}

	function fvHarvestBuildings()
	{
		AddLog2('fvFarmer: Harvesting Buildings');
		$builds = $this->fvGetSettings2('build');
		$plots = array();
		foreach(GetObjects() as $build)
		{
			if (isset($builds[$build['itemName']])) {
				$bUnits = Units_GetUnitByName($build['itemName'], true);
				list($USec,$Sec) = explode(" ", microtime());
				$PlantTime=(string)$Sec.substr((string)$USec, 2, 3);
				if ($build['state'] == 'grown' || $build['state'] == 'ripe' || @$build['m_hasAnimal'] == 1) {
					$plots[] = $build;
					continue;
				} elseif (!isset($build['m_hasAnimal'])) {
					if($build['plantTime'] < ($PlantTime-(86400000 * $bUnits['growTime']))) $plots[] = $build;
				}
			}
		}
		if (count($plots) > 0) 	$this->_fvFarmWork($plots); //harvest buildings
		unset($plots);
	}

	function fvHarvestAnimals()
	{
		AddLog2('fvFarmer: Harvesting Animals');
		$anims = $this->fvGetSettings2('animal');
		$plots = array();
		$animals = GetObjects('Animal');
		$ducks = GetObjects('DucklingAnimal');
		if (!empty($ducks)) {
			$animals = array_merge($animals, $ducks);
		}
		foreach($animals as $animal)
		{
			if (isset($anims[$animal['itemName']])) {
				if ($animal['state'] == 'ripe') {
					$plots[] = $animal;
					continue;
				}
				$aUnits = Units_GetUnitByName($animal['itemName'], true);
				list($USec,$Sec) = explode(" ", microtime());
				$PlantTime=(string)$Sec.substr((string)$USec, 2, 3);
				if($animal['plantTime'] < ($PlantTime-(86400000 * $aUnits['growTime']))) $plots[] = $animal;
			}

		}
		if (count($plots) > 0) 	$this->_fvFarmWork($plots); //harvest buildings
		unset($plots);
	}

	function fvTransformAnimals()
	{
		AddLog2('fvFarmer: Transforming Animals');
		$anims = $this->fvGetSettings2('transform');
		$plots = array();
		$animals = GetObjects('Animal');
		$ducks = GetObjects('DucklingAnimal');
		if (!empty($ducks)) {
			$animals = array_merge($animals, $ducks);
		}
		foreach($animals as $animal)
		{
			if (isset($anims[$animal['itemName']])) {
				if ($animal['state'] == 'grown' || $animal['state'] == 'ripe') $plots[] = $animal;
			}
		}
		if (count($plots) > 0) 	$this->_fvFarmWork($plots, 'transform'); //harvest buildings
		unset($plots);
	}

	function fvHarvestTrees()
	{
		AddLog2('fvFarmer: Harvesting Trees');
		$trees = $this->fvGetSettings2('tree');
		$plots = array();
		foreach(GetObjects('Tree') as $tree)
		{
			if (isset($trees[$tree['itemName']])) {
				if ($tree['state'] == 'grown' || $tree['state'] == 'ripe') $plots[] = $tree;
			}
		}
		if (count($plots) > 0) 	$this->_fvFarmWork($plots); //harvest trees
		unset($plots);
	}

	function fvPlowPlots()
	{
		AddLog2('fvFarmer: Plowing Plots');
		$plots = array();
		foreach(GetObjects('Plot') as $plot)
		{
			if ($plot['state'] == 'withered' || $plot['state'] == 'fallow') $plots[] = $plot;
		}
		if (count($plots) > 0) 	{
			$this->_fvPlotWork($plots, 'plow');
			DoInit();
			$this->haveWorld = false;
			$this->_refreshWorld();
			if ($this->haveWorld === true) $this->_fvUpdateSeedDB();
		}
		unset($plots);
	}

	function fvPlantPlots()
	{
		AddLog2('fvFarmer: Planting Plots');
		$plots = array();
		foreach(GetObjects('Plot') as $plot)
		{
			if ($plot['state'] == 'plowed') $plots[] = $plot;
		}

		if (empty($plots)) return;
		$seed_plots = array();
		//Plant Overrides
		if (is_array($this->overrides) && !empty($this->overrides)) {
			foreach($this->overrides as $key1=>$orSeed)
			{
				if ($orSeed[2] > 0) {
					for ($x = 0; $x < $orSeed[2]; $x++)
					{
						if (empty($plots)) break;
						$tmpplot = array_pop($plots);
						$tmpplot['itemName'] = $orSeed[0];
						$seed_plots[] = $tmpplot;
						$this->overrides[$key1][2]--;
					}
					if ($this->overrides[$key1][2] == 0 && $this->overrides[$key1][1] != 1) unset($this->overrides[$key1]);
				}
			}
			file_put_contents($_SESSION['base_path'] . F('overrideseed.txt'),serialize($this->overrides));
		}
		if (!empty($plots) && $this->settings['automast'] == 1) {
			$maststar = unserialize(fBGetDataStore('cropmasterycnt'));
			$this->settings['masteryorder'] = isset($this->settings['masteryorder']) ? $this->settings['masteryorder'] : 'masttime';
			$this->settings['direction'] = isset($this->settings['direction']) ? $this->settings['direction'] : 'asc';
			switch ($this->settings['masteryorder'])
			{
				case 'masttime':
					$fvSQL = "SELECT * FROM seedinfo WHERE seedinfo_masttime > 0 AND ";
					$fvSQL .= "(seedinfo_level <= '" . $this->level . "' OR seedinfo_level = '') AND ";
					$fvSQL .= "(seedinfo_startdate <= '" . time() . "' OR seedinfo_startdate= '') AND ";
					$fvSQL .= "(seedinfo_enddate >= '" . time() . "' OR seedinfo_enddate = '') AND ";
					$fvSQL .= "seedinfo_mastery = '1' ";
					$fvSQL .= "ORDER BY seedinfo_masttime " . $this->settings['direction'];
					break;
				case 'growtime':
					$fvSQL = "SELECT * FROM seedinfo WHERE seedinfo_masttime > 0 AND ";
					$fvSQL .= "(seedinfo_level <= '" . $this->level . "' OR seedinfo_level = '') AND ";
					$fvSQL .= "(seedinfo_startdate <= '" . time() . "' OR seedinfo_startdate= '') AND ";
					$fvSQL .= "(seedinfo_enddate >= '" . time() . "' OR seedinfo_enddate = '') AND ";
					$fvSQL .= "seedinfo_mastery = '1' ";
					$fvSQL .= "ORDER BY seedinfo_growtime " . $this->settings['direction'];
					break;
				case 'profithr':
					$fvSQL = "SELECT * FROM seedinfo WHERE seedinfo_profhr > 0 AND ";
					$fvSQL .= "(seedinfo_level <= '" . $this->level . "' OR seedinfo_level = '') AND ";
					$fvSQL .= "(seedinfo_startdate <= '" . time() . "' OR seedinfo_startdate= '') AND ";
					$fvSQL .= "(seedinfo_enddate >= '" . time() . "' OR seedinfo_enddate = '') AND ";
					$fvSQL .= "seedinfo_mastery = '1' ";
					$fvSQL .= "ORDER BY seedinfo_profhr "  . $this->settings['direction'];
					break;
				case 'xphr':
					$fvSQL = "SELECT * FROM seedinfo WHERE seedinfo_xphr > 0 AND ";
					$fvSQL .= "(seedinfo_level <= '" . $this->level . "' OR seedinfo_level = '') AND ";
					$fvSQL .= "(seedinfo_startdate <= '" . time() . "' OR seedinfo_startdate= '') AND ";
					$fvSQL .= "(seedinfo_enddate >= '" . time() . "' OR seedinfo_enddate = '') AND ";
					$fvSQL .= "seedinfo_mastery = '1' ";
					$fvSQL .= "ORDER BY seedinfo_xphr " . $this->settings['direction'];
					break;
				case 'nomast':
					$fvSQL = "SELECT * FROM seedinfo WHERE ";
					$fvSQL .= "(seedinfo_level <= '" . $this->level . "' OR seedinfo_level = '') AND ";
					$fvSQL .= "(seedinfo_startdate <= '" . time() . "' OR seedinfo_startdate= '') AND ";
					$fvSQL .= "(seedinfo_enddate >= '" . time() . "' OR seedinfo_enddate = '') AND ";
					$fvSQL .= "seedinfo_mastery = '0' ";
					$fvSQL .= "ORDER BY seedinfo_xphr " . $this->settings['direction'];
					break;
				case 'crafting':
					if (count($seed_plots) > 0) $this->_fvPlotWork($seed_plots, 'place');
					$this->_fvPlantCrafts($plots);
					DoInit();
					return;
					break;
				case 'coop':
					if (count($seed_plots) > 0) $this->_fvPlotWork($seed_plots, 'place');
					$this->_fvPlantQuests($plots);
					DoInit();
					return;
					break;
			}
			$q = $this->_fvFarmerDBM->query($fvSQL);
			$results = $q->fetchAll(SQLITE_ASSOC);
			$seeds = array();
			if (is_array($results)) {
				foreach ($results as $result)
				{
					//Check License
					if (!empty($result['seedinfo_license']))
					If ($this->_fvGetLicense($result['seedinfo_code']) === false) continue;
					$seedUnit = Units_GetUnitByCode($result['seedinfo_code'], true);
					if (isset($seedUnit['seedpackage'])) {
						$seedpkg = Units_GetUnitByName($seedUnit['seedpackage']);
						$conbox = unserialize(fBGetDataStore('inseedbox'));
						$result['seedpackage'] = isset($conbox[$seedpkg['code']]) ? $conbox[$seedpkg['code']] : 0;
					}
					//Check Requirements
					if (isset($seedUnit['requirements']) && !isset($seedUnit['isHybrid'])) {
						$reqs = unserialize($seedUnit['requirements']);
						$mast = 0;
						foreach ($reqs as $req)
						{
							if (!isset($req['@attributes']) && !isset($req[0]) && !isset($req['requirement'])) $req['@attributes'] = $req;
							If (isset($req['@attributes']['className'])) {
								$seedc = Units_GetCodeByName($req['@attributes']['name']);
								if (@$maststar[$seedc] == $req['@attributes']['level']) $mast++;
							}
							if (isset($req[0]['@attributes'])) {
								foreach ($req as $req2) {
									$seedc = Units_GetCodeByName($req2['@attributes']['name']);
									if (@$maststar[$seedc] == $req2['@attributes']['level']) $mast++;
								}
							}
							if (isset($req['requirement'])) {
								foreach ($req as $req2) {
									$seedc = Units_GetCodeByName($req2['@attributes']['name']);
									if (@$maststar[$seedc] == $req2['@attributes']['level']) $mast++;
								}
							}
						}
						if ($mast != count($reqs)) continue;
					};
					$seeds[] = $result;
				}
				foreach($seeds as $skey=>$seed)
				{
					$seed['seedinfo_curramt'] = is_numeric($seed['seedinfo_curramt']) ? $seed['seedinfo_curramt'] : 0;
					$numPlanted = 0;
					if (empty($plots)) break;
					for ($x = 0; $x < ($seed['seedinfo_mastmax'] - $seed['seedinfo_curramt']); $x++)
					{
						if (empty($plots)) break;
						If (isset($seed['seedpackage']))
						if ($x == $seed['seedpackage']) break;
						$seedc = Units_GetCodeByName($seed['seedinfo_name']);
						if (@$maststar[$seedc] == 2) break;
						$tmpplot = array_pop($plots);
						$tmpplot['itemName'] = $seed['seedinfo_name'];
						$seed_plots[] = $tmpplot;

					}
				}
			}
		}
		if (!empty($plots)) {
			$defseeds = unserialize((file_get_contents($_SESSION['base_path'] . F('defaultseed.txt'))));
			if (is_array($defseeds) && !empty($defseeds)) {
				foreach($defseeds as $key1=>$orSeed)
				{
					if ($orSeed[1] > 0) {
						if (empty($plots)) break;
						for ($x = 0; $x < $orSeed[1]; $x++)
						{
							if (empty($plots)) break;
							$tmpplot = array_pop($plots);
							$tmpplot['itemName'] = $orSeed[0];
							$seed_plots[] = $tmpplot;
							$defseeds[$key1][1]--;
						}
						if ($defseeds[$key1][1] == 0) unset($defseeds[$key1]);
					}
				}
				file_put_contents($_SESSION['base_path'] . F('defaultseed.txt'),serialize($defseeds));
			}
		}
		if (count($seed_plots) > 0) {
			foreach ($seed_plots as $seed)
			{
				@$newseed[$seed['itemName']][] = $seed;
			}
			foreach ($newseed as $iplots)
			{
				$this->_fvPlotWork($iplots, 'place');
			}
			DoInit();
		}
		unset($plots, $seed_plots, $newseed);
	}

	function fvGetOverRides()
	{
		$newlist = array();
		if (file_exists($_SESSION['base_path'] . F('overrideseed.txt'))) { // fix infinite loop when no file exists
			$overridelist = unserialize((file_get_contents($_SESSION['base_path'] . F('overrideseed.txt'))));
			foreach ($overridelist as $override)
			{
				$newlist[$override[0]] = $override;
			}
		}
		return $newlist;
	}

	private function _fvPlotWork($items, $action = 'harvest')
	{

		if ($this->fuel == 0 && $action == 'tractor') {
			return;
		}
		if ($this->fuel == 0) {
			$this->_fvFarmWork($items, $action);
			return;
		}
		if (empty($this->bHarvester) && $action == 'harvest') { $this->_fvFarmWork($items, $action); return; }
		if (empty($this->bSeeder) && $action == 'place') { $this->_fvFarmWork($items, $action); return; }
		if (empty($this->bTractor) && $action == 'plow') { $this->_fvFarmWork($items, $action); return; }
		$vCnt63000 = 63000;
		while(count($items) > 0) {
			if ($this->fuel == 0) {
				$this->_fvFarmWork($items, $action);
				return;
			}
			if ($action == 'harvest') {
				$pUnit = Units_GetUnitByName($items[0]['itemName'], true);
				$Seed = $items[0]['itemName'];
				$LastSeed=$Seed;
				if ($this->currbushel != $pUnit['bushelItemCode']) {
					$this->_fvDoBushels($pUnit['bushelItemCode']);
				} else {
					AddLog2('fvFarmer: Bushel Active For - ' . $Seed);
				}
			}
			$amf = new AMFObject("");
			$amf->_bodys[0] = new MessageBody();

			$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
			$amf->_bodys[0]->responseURI = '/1/onStatus';
			$amf->_bodys[0]->responseIndex = '/1';

			$amf->_bodys[0]->_value[0] = GetAMFHeaders();
			$amf->_bodys[0]->_value[2] = 0;

			$cnt = 0;
			while(count($items) > 0 && $cnt < $this->botspeed && $this->fuel > 0) {
				$amf->_bodys[0]->_value[1][$cnt]['sequence'] = GetSequense();
				$amf->_bodys[0]->_value[1][$cnt]['functionName'] = "EquipmentWorldService.onUseEquipment";
				if ($action == 'tractor') {
					$amf->_bodys[0]->_value[1][$cnt]['params'][0] = 'plow';
				} else {
					$amf->_bodys[0]->_value[1][$cnt]['params'][0] = $action;
				}

				$amf->_bodys[0]->_value[1][$cnt]['params'][1]['id'] = -1;
				if ($action == 'harvest') {
					$amf->_bodys[0]->_value[1][$cnt]['params'][1]['key'] = $this->bHarvester['itemCode'] . ':' . $this->bHarvester['numParts'];
					$maxEquipment = $this->bHarvester['numPlots'];
				}
				if ($action == 'tractor' || $action == 'plow') {
					$amf->_bodys[0]->_value[1][$cnt]['params'][1]['key'] = $this->bTractor['itemCode'] . ':' . $this->bTractor['numParts'];
					$maxEquipment = $this->bTractor['numPlots'];
				}
				if ($action == 'place') {
					$amf->_bodys[0]->_value[1][$cnt]['params'][1]['key'] = $this->bSeeder['itemCode'] . ':' . $this->bSeeder['numParts'];
					$maxEquipment = $this->bSeeder['numPlots'];
				}

				$equipCnt = 0; $Seed=''; $LastSeed='';
				while(count($items) > 0 && $equipCnt < $maxEquipment && $this->fuel > 0) {
					$Plot=array_pop($items);
					$pUnit = Units_GetUnitByName($Plot['itemName'], true);
					$prealname = trim($pUnit['realname']);
					if ($action == 'place') {
						$Seed = $Plot['itemName'];
						if($LastSeed=='') {
							$LastSeed=$Seed;
						} elseif($LastSeed != $Seed) {
							array_push($items,$Plot);
							break;
						}
					}
					if ($action == 'harvest') {
						$Seed = $Plot['itemName'];
						if($LastSeed=='') {
							$LastSeed=$Seed;
						} elseif($LastSeed != $Seed) {
							array_push($items,$Plot);
							break 2;
						}
					}

					if (@!$plotsstring)
					$plotsstring = $prealname . " " . $Plot['position']['x'] . '-' . $Plot['position']['y'];
					else
					$plotsstring = $plotsstring . ", " . $prealname . " " . $Plot['position']['x'] . '-' . $Plot['position']['y'];

					if (@!$OKstring) {
						$OKstring = ucfirst($action) . " " . $prealname . " on plot " . $Plot['position']['x'] . '-' . $Plot['position']['y'];
					} else {
						$OKstring = $OKstring . "\r\n" . ucfirst($action) . " " . $prealname . " on plot " . $Plot['position']['x'] . '-' . $Plot['position']['y'];
					}

					$this->fuel--;
					if ($action == 'tractor') {
						$vCnt63000++;
						$Plot['id'] = $vCnt63000;
						$amf->_bodys[0]->_value[1][$cnt]['params'][2][$equipCnt] = $Plot;
					} else {
						$amf->_bodys[0]->_value[1][$cnt]['params'][2][$equipCnt]['id'] = $Plot['id'];
					}

					$equipCnt++;

				}

				if ($action == 'tractor' || $action == 'harvest' || $action == 'plow')
				$amf->_bodys[0]->_value[1][$cnt]['params'][3] = 'plowed';
				if ($action == 'place')
				$amf->_bodys[0]->_value[1][$cnt]['params'][3] = $Seed;
				$cnt++;
			}
			AddLog2('fvFarmer: ' . ucfirst($action) . " " . $plotsstring);
			//AddLog2(print_r($amf,true));
			$res = RequestAMF($amf, true);
			unset($amf->_bodys[0]->_value[1]);
			$amf2 = $res['amf2'];
			if ($res['res'] === 'OK') {
				AddLog2("fvFarmer Result: " . $res['res']);
				foreach ($amf2->_bodys[0]->_value['data'][0]['data'] as $key=>$result)
				{
					if (isset($result['data']['foundBushel']))
					{
						$crop = $result['data']['foundBushel']['bushelCode'];
						$cUnit = Units_GetUnitByCode($crop, true);
						AddLog2('fvFarmer: Found Bushel ' . $cUnit['realname']);
						if ($result['data']['foundBushel']['bushelAddedToStall'] == 1)
						$this->fmBushels[] = $crop;
					}
				}
			} else {
				if ($res) {
					AddLog("fvFarmer Error: " . $res['res'] . " - $OKstring");
					if (intval($res['res']) == 29 || intval($res['res']) == 28) { // Server sequence was reset
						DoInit();
					}
				}
			}
			unset($plotsstring, $OKstring);
		}


	}

	private function _fvFarmWork($items, $action = 'harvest') {

		$count = count($items);

		if ($count > 0) {
			$i = 0;
			$amf = '';
			foreach($items as $plot) {
				if ($action == 'harvest') {
					$pUnit = Units_GetUnitByName($plot['itemName'], true);
					$Seed = $plot['itemName'];
					$LastSeed=$Seed;
					if ($this->currbushel != $pUnit['bushelItemCode']) {
						$this->_fvDoBushels($pUnit['bushelItemCode']);
					}
				}
				$amf = CreateMultAMFRequest($amf, $i, $action, 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][$i]['params'][1] = $plot;
				$amf->_bodys[0]->_value[1][$i]['params'][2] = array();
				$amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 0;
				$prealname = trim(Units_GetRealnameByName($plot['itemName']));
				if (@!$plotsstring) {
					$plotsstring = $prealname . " " . $plot['position']['x'] . '-' . $plot['position']['y'];
				} else {
					$plotsstring = $plotsstring . ", " . $prealname . " " . $plot['position']['x'] . '-' . $plot['position']['y'];
				}

				if (@!$OKstring) {
					$OKstring = ucfirst($action) . " " . $prealname . " " . $plot['position']['x'] . '-' . $plot['position']['y'];
				} else {
					$OKstring = $OKstring . "\r\n" . ucfirst($action) . " " . $plot['itemName'] . " " . $plot['position']['x'] . '-' . $plot['position']['y'];
				}

				$i++;

				if (($i == $this->botspeed) || ($i >= $count)) {
					$count -= $i;
					$i = 0;
					AddLog2('fvFarmer: ' . ucfirst($action) . " " . $plotsstring);
					$res = RequestAMF($amf, true);
					$amf2 = $res['amf2'];
					$amf = '';
					if ($res['res'] === 'OK') {
						AddLog2("fvFarmer Result: " . $res['res']);
						foreach ($amf2->_bodys[0]->_value['data'][0]['data'] as $result)
						{
							if (isset($result['data']['foundBushel']))
							{
								$crop = $result['data']['foundBushel']['bushelCode'];
								$cUnit = Units_GetUnitByCode($crop, true);
								AddLog2('fvFarmer: Found Bushel ' . $cUnit['realname']);
								if ($result['data']['foundBushel']['bushelAddedToStall'] == 1)
								$this->fmBushels[] = $crop;
							}
						}
					} else {
						if ($res['res']) {
							AddLog("fvFarmer Error: " . $res['res'] . " on $OKstring");
							if (intval($res['res']) == 29 || intval($res['res']) == 28) {
								DoInit();
							}
						}
					}
					unset($plotsstring, $OKstring);
				}
			}
		}
	}

	function fvBestVehicles()
	{
		$garage = GetObjects('GarageBuilding');
		//get vehicles in the garage
		if (count($garage) != 0)
		{
			foreach($garage[0]['contents'] as $vehicle)
			{
				$vUnits = Units_GetUnitByCode($vehicle['itemCode']);
				@$veh[$vUnits['className']][$vehicle['itemCode']] =
				!isset($veh[$vUnits['className']][$vehicle['itemCode']]) ||
				$veh[$vUnits['className']][$vehicle['itemCode']] >= $vehicle['numParts'] ?
				$vehicle['numParts'] :
				$veh[$vUnits['className']][$vehicle['itemCode']];
			}
		}
		//get vehicles on the farm
		$harvesters = GetObjects('Harvester');
		foreach ($harvesters as $harv)
		{
			$hUnits = Units_GetUnitByName($harv['itemName']);
			@$veh[$harv['className']][$hUnits['code']] =
			!isset($veh[$harv['className']][$hUnits['code']]) ||
			$veh[$harv['className']][$hUnits['code']] >= $harv['m_equipmentPartsCount'] ?
			$harv['m_equipmentPartsCount'] :
			$veh[$harv['className']][$hUnits['code']];
		}
		$tractors = GetObjects('Tractor');
		foreach ($tractors as $tract)
		{
			$tUnits = Units_GetUnitByName($tract['itemName']);
			@$veh[$tract['className']][$tUnits['code']] =
			!isset($veh[$tract['className']][$tUnits['code']]) ||
			$veh[$tract['className']][$tUnits['code']] >= $tract['m_equipmentPartsCount'] ?
			$tract['m_equipmentPartsCount'] :
			$veh[$tract['className']][$tUnits['code']];
		}
		$seeders = GetObjects('Seeder');
		foreach ($seeders as $seed)
		{
			$sUnits = Units_GetUnitByName($seed['itemName']);
			@$veh[$seed['className']][$sUnits['code']] =
			!isset($veh[$seed['className']][$sUnits['code']]) ||
			$veh[$seed['className']][$sUnits['code']] >= $seed['m_equipmentPartsCount'] ?
			$seed['m_equipmentPartsCount'] :
			$veh[$seed['className']][$sUnits['code']];
		}
		$combines = GetObjects('Combine');
		foreach ($combines as $comb)
		{
			$cUnits = Units_GetUnitByName($comb['itemName']);
			@$veh[$comb['className']][$cUnits['code']] =
			!isset($veh[$comb['className']][$cUnits['code']]) ||
			$veh[$comb['className']][$cUnits['code']] >= $comb['m_equipmentPartsCount'] ?
			$comb['m_equipmentPartsCount'] :
			$veh[$comb['className']][$cUnits['code']];
		}
		unset($combines, $seeders, $tractors, $harvesters, $garage);
		//Get best Harvester
		$harvcount = 0;
		if (is_array($veh['Harvester']))
		foreach ($veh['Harvester'] as $key=>$parts)
		{
			if ($parts >= $harvcount) {
				$harvcount = $parts;
				$numPlots = 1;
				$numPlots = $parts >= 2 ? 6 : $numPlots;
				$numPlots = $parts >= 7 ? 9 : $numPlots;
				$numPlots = $parts >= 17 ? 12 : $numPlots;
				$numPlots = $parts == 32 ? 16 : $numPlots;
				$this->bHarvester = array('itemCode' => $key, 'numParts' => $parts, 'numPlots' => $numPlots);
			}
		}
		//Get Best Tractor
		$harvcount = 0;
		if (is_array($veh['Tractor']))
		foreach ($veh['Tractor'] as $key=>$parts)
		{
			if ($parts >= $harvcount) {
				$harvcount = $parts;
				$numPlots = 1;
				$numPlots = $parts >= 2 ? 6 : $numPlots;
				$numPlots = $parts >= 7 ? 9 : $numPlots;
				$numPlots = $parts >= 17 ? 12 : $numPlots;
				$numPlots = $parts == 32 ? 16 : $numPlots;
				$this->bTractor = array('itemCode' => $key, 'numParts' => $parts, 'numPlots' => $numPlots);
			}
		}
		//Get Best Seeder
		$harvcount = 0;
		if (is_array($veh['Seeder']))
		foreach ($veh['Seeder'] as $key=>$parts)
		{
			if ($parts >= $harvcount) {
				$harvcount = $parts;
				$numPlots = 1;
				$numPlots = $parts >= 2 ? 6 : $numPlots;
				$numPlots = $parts >= 7 ? 9 : $numPlots;
				$numPlots = $parts >= 17 ? 12 : $numPlots;
				$numPlots = $parts == 32 ? 16 : $numPlots;
				$this->bSeeder = array('itemCode' => $key, 'numParts' => $parts, 'numPlots' => $numPlots);
			}
		}
		//Get Best Combine
		$harvcount = 0;
		if (is_array(@$veh['Combine']))
		foreach ($veh['Combine'] as $key=>$parts)
		{
			if ($parts >= $harvcount) {
				$harvcount = $parts;
				$this->bCombine = array('itemCode' => $key, 'numParts' => $parts);
			}
		}
	}

	private function _fvGetLicense($license)
	{
		$licenses = unserialize(fBGetDataStore('licenses'));
		if (empty($licenses)) return false;
		foreach ($licenses as $lic)
		{
			if ($lic['licensedItem'] == $license) {
				$fvSQL = "SELECT license_duration FROM license WHERE license_code='$license'";
				$q = $this->_fvFarmerDBM->query($fvSQL);
				$result = $q->fetchSingle();
				if ($result !== false) {
					$ltime = $result * (24 * 3600);
					if ($lic['timeAcquired'] + $ltime > time()) return true;
				}
				return false;
			}
		}
		return false;
	}

	function subval_sort($a,$subkey) {
		If(empty($a)) return $a;
		$b = array();
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		asort($b);
		foreach($b as $key=>$val) {
			$c[] = $a[$key];
		}
		return $c;
	}

	function _fvPlantCrafts($plots)
	{
		if (empty($plots)) return;
		sort($plots);
		$craft = @$this->settings['craft'];
		if (empty($craft)) return;
		AddLog2("Planting for Crafts");
		$craftinfo = Crafts_GetByCode($craft);
		$seeds = explode(':', $craftinfo['Ingredient_itemCode']);
		foreach ($seeds as $seed)
		{
			$newseed[$seed] = $craftinfo['Ingredient_quantityRequired_' . $seed];
		}
		$seedcnt = array_sum($newseed);

		$seed_plots = array();
		$emptyplots = count($plots);
		$i = 0;
		foreach($newseed as $seed=>$cnt)
		{
			if (empty($plots)) break;
			$seedpercent = ceil($emptyplots * ($cnt/$seedcnt));
			$seedpercent = $seedpercent < 1 ? 1 : $seedpercent;

			for ($x = 0; $x < $seedpercent; $x++) {
				{
					if (empty($plots)) break 2;
					$sinfo = Units_GetUnitByCode($seed, true);
					$plots[$i]['itemName'] = Units_GetNamebyCode($sinfo['crop']);
					$seed_plots[] = $plots[$i];
					unset($plots[$i]);
					$i++;
				}

			}
		}
		if (count($seed_plots) > 0) $this->_fvPlotWork($seed_plots, 'place');
	}


	function _fvPlantQuests($plots)
	{
		if (empty($plots)) return;
		sort($plots);
		$quest = @$this->settings['coop'];
		if (empty($quest)) return;
		AddLog2("Planting for Crafts");
		$questinfo = Quests_GetByCode($quest);
		$qkeys = array_keys($questinfo);
		foreach ($qkeys as $qkey)
		{
			if (stripos($qkey,'completionRequirements_bronze_seed_') !== false) {
				$qseed = str_replace('completionRequirements_bronze_seed_', '', $qkey);
				$qseeds[$qseed] = $questinfo[$qkey];
			}
		}
		$seedcnt = array_sum($qseeds);
		$seed_plots = array();
		$emptyplots = count($plots);
		$i = 0;
		foreach($qseeds as $seed=>$cnt)
		{
			if (empty($plots)) break;
			$seedpercent = ceil($emptyplots * ($cnt/$seedcnt));
			$seedpercent = $seedpercent < 1 ? 1 : $seedpercent;

			for ($x = 0; $x < $seedpercent; $x++) {
				{
					if (empty($plots)) break 2;
					$plots[$i]['itemName'] = $seed;
					$seed_plots[] = $plots[$i];
					unset($plots[$i]);
					$i++;
				}

			}
		}
		if (count($seed_plots) > 0) $this->_fvPlotWork($seed_plots, 'place');
	}

}
?>
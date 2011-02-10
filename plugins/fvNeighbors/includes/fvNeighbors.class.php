<?php
class fvNeighbors
{
	var $userId, $flashRevision, $_token, $_sequence, $_flashSessionKey, $pneighbors, $actionlimits;
	var $xp, $energy, $error, $fvAll, $haveWorld, $units, $_fnNeighborsDBM, $building;
	var $level, $imageRevision, $gold, $coin, $settings, $freespace, $recipe, $zErrCGen;
	var $fmCraft, $fmBushels, $relVersion, $giftBox, $conBox, $availGoods, $fmGoodState;
	var $availBushels, $bMaxCap, $bDailyBags, $bDailyPurch, $cDailyBags, $stBox, $neighborActs;
	var $cDailyPurch, $bConsumed, $cConsumed, $bBagsConsumed, $cBagsConsumed, $myBushels, $botspeed;
	var $fvdebug, $tileset, $wither, $neighbors, $fuel, $uname, $achieve, $achievecnt, $mastery, $masterycnt;
	private function _refreshWorld()
	{

		$this->haveWorld = false;
		$this->zErrCGen = array(
		0 => 'OK', 1 => 'Error - Authorization', 2=> 'Error - User Data Missing',
		3 => 'Error - Invalid State', 4 => 'Error - Invalid Data', 5 => 'Error - Missing Data',
		6 => 'Error - Action Class Error', 7 => 'Error - Action Method Error',
		8 => 'Error - Resource Data Missing', 9 => 'Error - Not Enough Money',
		10 => 'Error - Outdated Game Version', 25 => 'Error - General Transport Failure',
		26 => 'Error - No User ID', 27 => 'Error - No Session', 28 => 'Retry Transaction',
		29 => 'Force Reload');

		$this->haveWorld = true;
		$this->neighborActs = unserialize(fBGetDataStore('nactionqueue'));
		$this->neighbors = unserialize(fBGetDataStore('neighbors'));
		$this->pneighbors = unserialize(fBGetDataStore('pneighbors'));
		@$this->actionlimits = unserialize(fBGetDataStore('nactionlimit'));
	}

	private function _fnNeighbors_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = @$this->_fnNeighborsDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fnNeighborsDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "')";
			$this->_fnNeighborsDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('level','" . $this->level . "')";
			$this->_fnNeighborsDBM->queryExec($fvSQL);

		}
		$q = @$this->_fnNeighborsDBM->query('SELECT * FROM neighbors LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Neighbors Table');
			$fvSQL = 'CREATE TABLE
						neighbors (
						neighbors_fbid CHAR(50) PRIMARY KEY,
						neighbors_timestamp NUMERIC DEFAULT 0,
						neighbors_name CHAR(250),
						neighbors_worldn CHAR(250),
						neighbors_lastseen NUMERIC,
						neighbors_level INTEGER DEFAULT 0,
						neighbors_xp INTEGER DEFAULT 0,
						neighbors_coin INTEGER DEFAULT 0,
						neighbors_cash INTEGER DEFAULT 0,
						neighbors_sizeX INTEGER DEFAULT 0,
						neighbors_sizeY INTEGER DEFAULT 0,
						neighbors_fuel INTEGER DEFAULT 0,
						neighbors_friends INTEGER DEFAULT 0,
						neighbors_objects INTEGER DEFAULT 0,
						neighbors_plots INTEGER DEFAULT 0,
						neighbors_delete INTEGER DEFAULT 0
			)';
			$this->_fnNeighborsDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX neighbors_name ON neighbors(neighbors_name)';
			$this->_fnNeighborsDBM->queryExec($fvSQL);
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('helpcycle','10');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('helptime','3');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('speed','2');";
			$this->_fnNeighborsDBM->queryExec($fvSQL);
		}
		$q = @$this->_fnNeighborsDBM->query('SELECT * FROM neighborsn LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Neighbors Neighbors Table');
			$fvSQL = 'CREATE TABLE
						neighborsn (
						neighborsn_fbid CHAR(50) PRIMARY KEY,
						neighborsn_lastseen NUMERIC DEFAULT 0,
						neighborsn_timestamp NUMERIC DEFAULT 0
			)';
			$this->_fnNeighborsDBM->queryExec($fvSQL);
		}

	}

	//Function fvNeighbors class initializer
	function fvNeighbors($inittype = '')
	{
		list($this->level, $this->gold, $cash, $this->wsizeX, $this->wsizeY, $firstname, $locale, $tileset, $wither, $this->xp, $this->energy) = explode(';', fBGetDataStore('playerinfo'));		$this->userId = $_SESSION['userId'];
		$this->flashRevision = $_SESSION['flashRevision'];
		$this->error = '';
		$this->haveWorld = false;
		$this->fndebug = false;

		if(!is_numeric($this->userId))
		{
			$this->error = "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fnNeighborsDBM = new SQLiteDatabase(fvNeighbors_Path . PluginF(fvNeighbors_Main));
		if(!$this->_fnNeighborsDBM)
		{
			$this->error = 'fvNeighbors - Database Error';
			return;
		}
		$this->_fnNeighborsDBM->queryExec('PRAGMA cache_size=20000');
		$this->_fnNeighborsDBM->queryExec('PRAGMA synchronous=OFF');
		$this->_fnNeighborsDBM->queryExec('PRAGMA count_changes=OFF');
		$this->_fnNeighborsDBM->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fnNeighborsDBM->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fnNeighbors_checkDB();
		//Get Settings
		$this->settings = $this->fnGetSettings();
		if($inittype == 'formload')
		{
			if(empty($this->settings))
			{
				$this->error = 'Please allow fvNeighbors to run a cycle';
			}
			return;
		}
		if($this->settings !== false && (!isset($this->settings['version']) || $this->settings['version'] != fvNeighbors_version))
		{
			$fbSQL = "DROP TABLE neighborsn;";
			$q = $this->_fnNeighborsDBM->query($fbSQL);
			$this->_fnNeighbors_checkDB();//Database doesn't exist, create
			/*$this->_fnUpdateSettings();//Insert initial settings
			AddLog2('fvNeighbors upgrade finished');*/
		}
		//Load the world from Z*
		$this->_refreshWorld();
		if($this->haveWorld === true)
		{
			if($this->settings === false)
			{
				$this->_fnNeighbors_checkDB();//Database doesn't exist, create
				$this->_fnUpdateSettings();//Insert initial settings
				$this->error = 'Please allow fvNeighbors to run a cycle to update all settings';
				return;
			}
			$this->_fnUpdateSettings();//Update the settings
			$this->_fnUpdateWorldDB();//Update the World
		}
	}

	function fnGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		$q = $this->_fnNeighborsDBM->query($fvSQL);
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

	function fnMakeTime($ctime)
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
	private function _fnUpdateWorldDB()
	{
		//Insert Missing Neighbors
		foreach ($this->neighbors as $neigh)
		{
			$fn_SQL = "INSERT OR IGNORE INTO neighbors(neighbors_fbid) ";
			$fn_SQL .= "VALUES('$neigh')";
			$q = $this->_fnNeighborsDBM->query($fn_SQL);
		}
		//Delete Neighbors
		$fn_SQL = "SELECT * FROM neighbors WHERE neighbors_delete=1 ";
		$q = $this->_fnNeighborsDBM->query($fn_SQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $result)
		{
			$this->_fnRemoveNeighbor($result['neighbors_fbid']);
		}

		//Cancel Pending Neighbor Requests
		if (@$this->settings['delpending'] == 1)
		{
			$cnt = 0;
			AddLog2('Pending Count: ' . count($this->pneighbors));
			foreach ($this->pneighbors as $pendn)
			{
				$this->_fnCancelNeighbor($pendn);
				$cnt++;
				if ($cnt == $this->settings['helpcycle']) break;
			}
		}
		//Clear Neighbor Actions
		if (@$this->settings['accepthelp'] == 1)
		{
			AddLog2('Accepting Neighbors Help');
			$amfcount = 0;
			$amf = '';
			$tmpArray = array();
			$this->_fnGetBotSettings();
			foreach ($this->neighborActs as $nActs)
			{
				$nid = $nActs['visitorId'];
				foreach ($nActs['actions'] as $acts)
				{
					$amf = CreateMultAMFRequest($amf, $amfcount, '', 'NeighborActionService.clearNeighborAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][0]    = $nid;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]    = $acts['actionType'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]    = $acts['objectId'];
					$tmpArray[$amfcount]['id'] = $nid;
					$tmpArray[$amfcount]['action'] = $acts['actionType'];
					if ($amfcount < $this->botspeed - 1)
					{
						$amfcount++;
						continue;
					}
					$amf2 = $this->_fnAMFSend($amf);
					$amf = '';
					$amfcount = 0;
					if ($amf2 === false) continue;
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['id'] . ' - Result: ' . $this->zErrCGen[$resp]);
						}
					}

				}
			}
			if ($amf != '') //Still have requests left
			{
				$amf2 = $this->_fnAMFSend($amf);
				if ($amf2 !== false) {
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['id'] . ' - Result: ' . $this->zErrCGen[$resp]);
						}
					}
				}
			}
		}
		//Update Neighbors
		AddLog2('Updating Neighbors');
		$mytime = time() - (3600 * $this->settings['helptime']);
		$fvSQL = "SELECT * FROM neighbors WHERE neighbors_timestamp <= '" . $mytime . "' LIMIT " . $this->settings['helpcycle'];
		$q = $this->_fnNeighborsDBM->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);

		$tmpArray = array();
		$iguser = load_array('ignores.txt');
		foreach ($results as $result)
		{
			if (fBGetNeighborRealName($result['neighbors_fbid']) === false) continue;
			//echo $result['neighbors_timestamp'] . ' - ' . $mytime . ' - ' . ($mytime - $result['neighbors_timestamp']) . "\n";
			if (isset($iguser[$result['neighbors_fbid']])) {
				AddLog2("fvNeighbors: Ignored " . $result['neighbors_fbid'] . ' - ' . $result['neighbors_name']);
				AddLog2("fvNeighbors: Listed in Ignored.txt File");
				continue;
			}
			$amf = CreateRequestAMF('', 'WorldService.loadNeighborWorld');
			$amf->_bodys[0]->_value[1][0]['params'][0]    = trim($result['neighbors_fbid']);
			$amf2 = $this->_fnAMFSend($amf);
			if ($amf2 === false) {
				AddLog2("fvNeighbors Error: " . $result['neighbors_fbid'] . ' - ' . $result['neighbors_name']);
				continue;
			}
			$this->_fnNeighborsDBM->queryExec('BEGIN;');
			foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
			{
				//AddLog2(print_r($returned,true));
				$resp = $returned['errorType'];
				$err = $returned['errorData'];
				if($resp == 0)
				{
					$this->_fnGetFriends($returned, 'neighbor');
				}
			}
			$this->_fnNeighborsDBM->queryExec('COMMIT;');
		}
		AddLog2('Finished Updating Neighbors');
	}
	function fnGetNeighbors()
	{
		$fvSQL = 'SELECT * FROM neighbors WHERE neighbors_delete=0 ORDER BY neighbors_name';
		$q = $this->_fnNeighborsDBM->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fnGetUnits($code)
	{
		$results = Units_GetUnitByCode($code);
		return $results;
	}

	function fnDoSettings($postvars)
	{
		$accepthelp = (isset($postvars['accepthelp'])) ? 1 : 0;
		$delpending = (isset($postvars['delpending'])) ? 1 : 0;
		$htrees = (isset($postvars['htrees'])) ? 1 : 0;
		$hanimals = (isset($postvars['hanimals'])) ? 1 : 0;
		$pplots = (isset($postvars['pplots'])) ? 1 : 0;
		$fplots = (isset($postvars['fplots'])) ? 1 : 0;
		$ucrops = (isset($postvars['ucrops'])) ? 1 : 0;
		$fchickens = (isset($postvars['fchickens'])) ? 1 : 0;
		$hcandy = (isset($postvars['hcandy'])) ? 1 : 0;
		$hval2011 = (isset($postvars['hval2011'])) ? 1 : 0;
		$hgreenhouse = (isset($postvars['hgreenhouse'])) ? 1 : 0;
		$ftrough = (isset($postvars['ftrough'])) ? 1 : 0;
		$fpigpen = (isset($postvars['fpigpen'])) ? 1 : 0;
		$vneighborsn = (isset($postvars['vneighborsn'])) ? 1 : 0;
		$domissions = (isset($postvars['domissions'])) ? 1 : 0;
		$helpcycle = (isset($postvars['helpcycle']) && is_numeric($postvars['helpcycle'])) ? $postvars['helpcycle'] : 0;
		$helptime = (isset($postvars['helptime']) && is_numeric($postvars['helptime'])) ? $postvars['helptime'] : 0;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('accepthelp','$accepthelp');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('delpending','$delpending');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('htrees','$htrees');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('hanimals','$hanimals');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('pplots','$pplots');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fplots','$fplots');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ucrops','$ucrops');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fchickens','$fchickens');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('vneighborsn','$vneighborsn');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('domissions','$domissions');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('hcandy','$hcandy');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ftrough','$ftrough');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fpigpen','$fpigpen');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('hval2011','$hval2011');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('hgreenhouse','$hgreenhouse');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('helpcycle','$helpcycle');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('helptime','$helptime');";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
	}

	private function _fnUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('level','" . $this->level . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('xp','" . $this->xp . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('gold','" . $this->gold . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('coin','" . $this->coin . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeX','" . $this->wsizeX . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeY','" . $this->wsizeY . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('version','" . fvNeighbors_version . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wither','" . $this->wither . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('neighbors','" . serialize($this->neighbors) . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('achieve','" . serialize($this->achieve) . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('mastery','" . serialize($this->mastery) . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('achievecnt','" . serialize($this->achievecnt) . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('masterycnt','" . serialize($this->masterycnt) . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('tileset','" . $this->tileset . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fuel','" . $this->fuel . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$farm1 = Units_GetFarming('higherLevelXp');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelXp','" . $farm1 . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$farm2 = Units_GetFarming('higherLevelBegin');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelBegin','" . $farm2 . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		$farm3 = Units_GetFarming('higherLevelStep');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelStep','" . $farm3 . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);

	}

	private function _fnAMFSend($amf)
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
			//echo $resp . ' - ' . $err . "\n";
			if ($resp == 28 || $resp == 29)
			{
				if (strpos($err,'MC::lock()') !== false)
				{
					//Ignore Quietly Now, even if Debug is on
					$iguser = load_array('ignores.txt');
					preg_match('/rts_USER_(.*)_lock/', $err, $matches);
					$iguser[floatval($matches[1])] = floatval($matches[1]);
					save_array($iguser,'ignores.txt');
				} elseif (strpos($err,'Exceeded action limit') !== false)
				{
					//Ignore Quietly Now, even if Debug is on
				} else if ( strpos($err, 'Invalid data') !== false)
				{
					$user = $amf->_bodys[0]->_value[1][0]['params'][0];
					$iguser = load_array('ignores.txt');
					$iguser[$user] = floatval($user);
					AddLog2('fvNeighbors Error: FBID:' . $user . ' - ' . $resp . ' - ' . $err);
					save_array($iguser,'ignores.txt');
					//Ignore Questly Now, even if Debug is on
				} else {
					if ($err !='Remaining function') AddLog2('fvNeighbors Error: ' . $resp . ' - ' . $err);
				}
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

	private function _fnGetFriends($returned, $friendtype)
	{
		$fr_laston = $returned['data']['user']['lastWorldAction'];
		$fr_fbid = $returned['data']['user']['id'];
		$fr_sizeX = (($returned['data']['world']['sizeX'])-2)/4;
		$fr_sizeY = (($returned['data']['world']['sizeY'])-2)/4;

		if (is_array($this->actionlimits)) {
			foreach ($this->actionlimits as $nkey => $ndata)
			{
				if ($ndata['targetId'] == $fr_fbid) {
					$myactions = $ndata;
					break;
				}
			}
		}
		$fr_objects = $returned['data']['world']['objectsArray'];
		$fr_objectscnt = count($fr_objects);
		if ($friendtype == 'neighbor')
		{
			$fn_SQL = "INSERT OR REPLACE INTO neighbors(neighbors_fbid, neighbors_name, neighbors_worldn, neighbors_lastseen,
								neighbors_level, neighbors_xp, neighbors_coin, neighbors_cash, neighbors_sizeX,
								neighbors_sizeY, neighbors_fuel, neighbors_friends, neighbors_objects, neighbors_plots, neighbors_timestamp) ";
			$fn_SQL .= "VALUES('$fr_fbid', '', '', '$fr_laston', '', '', '', '',
								'$fr_sizeX', '$fr_sizeY', '', '', '$fr_objectscnt', '', '" . time() . "')";
			$q = $this->_fnNeighborsDBM->query($fn_SQL);
		} else {
			$fn_SQL = "UPDATE neighborsn
						SET neighborsn_timestamp='" . time() . "', 
							neighborsn_lastseen='" . $fr_laston . "' WHERE neighborsn_fbid='" . $fr_fbid . "'";
			$q = $this->_fnNeighborsDBM->query($fn_SQL);
		}
		AddLog2($fr_fbid . ' - Updated');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ntracktime','" . time() . "')";
		$this->_fnNeighborsDBM->queryExec($fvSQL);
		//Count My Actions
		$farmActs = 0;
		$feedActs = 0;
		$halloweenActs = 0;
		$animalFeedActs = 0;
		$pigslopActs = 0;
		$Val2011BuildActs = 0;
		$greenHouseBuildActs = 0;
		if (!empty($myactions))
		{
			$farmActs = @$myactions['farm'];
			$feedActs = @$myactions['feed'];
			$halloweenActs = @$myactions['harvesthalloweencandy'];
			$animalFeedActs = @$myactions['animalFeed'];
			$pigslopActs = @$myactions['pigslop'];
			$Val2011BuildActs = @$myactions['Valentines2011Harvest'];
			$greenHouseBuildActs = @$myactions['greenhousebuildable_finished'];
		}
		if ($farmActs < 5 || $feedActs < 1 || $halloweenActs < 1 || $pigslopActs < 1 || $animalFeedActs < 1 || $Val2011BuildActs < 1 || $greenHouseBuildActs < 1)
		{
			foreach (@$fr_objects as $wObjects)
			{
				switch ($wObjects['className'])
				{
					case 'Plot':
						if ($farmActs < 5)
						{
							switch ($wObjects['state'])
							{
								case 'withered':
									if (@$this->settings['ucrops'] == 1)
									{
										$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'unwither');
										$farmActs++;
									}
									break;
								case 'fallow':
									if (@$this->settings['pplots'] == 1)
									{
										$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'plow');
										$farmActs++;
									}
									break;
								default:
									if (@$this->settings['fplots'] == 1 && $wObjects['isJumbo'] === false)
									{
										$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'fert');
										$farmActs++;
									}
							}
						}
						break;
					case 'Animal':
						if ($farmActs < 5)
						{
							if ($wObjects['state'] == 'ripe')
							{
								$uinfo = Units_GetUnitByName($wObjects['itemName']);
								if (@$uinfo['action'] != 'transform' && @$this->settings['hanimals'] == 1) {
									$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvest');
									$farmActs++;
								}
							}
						}
						break;
					case 'Tree':
						if ($farmActs < 5)
						{
							if ($wObjects['state'] == 'ripe' && @$this->settings['htrees'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvest');
								$farmActs++;
							}
						}
						break;
					case 'ChickenCoopBuilding':
						if ($feedActs < 1)
						{
							if ($wObjects['state'] == 'ripe' && @$this->settings['fchickens'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'feedchickens');
								$feedActs++;
							}
						}
						break;
					case 'HalloweenHauntedHouseBuilding':
						if ($halloweenActs < 1)
						{
							if ($wObjects['isFullyBuilt'] === true && @$this->settings['hcandy'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvesthalloweencandy');
								$halloweenActs++;
							}
						}
						break;
					case 'FeedTroughBuilding':
						if ($animalFeedActs < 1)
						{
							if ($wObjects['isFullyBuilt'] === true && @$this->settings['ftrough'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'depositAnimalFeed');
								$animalFeedActs++;
							}
						}
						break;
					case 'PigpenBuilding':
						if ($pigslopActs < 1)
						{
							if ($wObjects['isFullyBuilt'] === true && @$this->settings['fpigpen'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'getVisitPigSlopW2W');
								$pigslopActs++;
							}
						}
					case 'FeatureBuilding':
						if ($Val2011BuildActs < 1 && $wObjects['itemName'] == 'valentines2011_finished')
						{
							if (@$this->settings['hval2011'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'neighborHarvestFeatureBuilding');
								$Val2011BuildActs++;
							}
						}
						if ($greenHouseBuildActs < 1 && $wObjects['itemName'] == 'greenhousebuildable_finished')
						{
							if (@$this->settings['hgreenhouse'] == 1)
							{
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'neighborHarvestFeatureBuilding');
								$greenHouseBuildActs++;
							}
						}													
				}
				if ($farmActs == 5 && $feedActs == 1 && $halloweenActs == 1 && $animalFeedActs == 1 && $pigslopActs == 1 && $Val2011BuildActs == 1 && $greenHouseBuildActs == 1) break;
			}
			//Now Submit Work
			$amf = '';
			$tmpArray = array();
			$amfcount = 0;

			if (!empty($work))
			{
				foreach ($work as $wk)
				{
					if ($wk['action'] != 'depositAnimalFeed' && $wk['action'] != 'getVisitPigSlopW2W') {
						$amf = CreateMultAMFRequest($amf, $amfcount, 'neighborAct', 'WorldService.performAction');
						$amf->_bodys[0]->_value[1][$amfcount]['params'][1]    = $wk['objectArray'];
						$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['actionType']    = $wk['action'];
						$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['hostId']    = $wk['fbid'];
					} else {
						$amf = CreateMultAMFRequest($amf, $amfcount, '', 'NeighborActionService.' . $wk['action']);
						$amf->_bodys[0]->_value[1][$amfcount]['params'][0]   = $wk['fbid'];
					}
					$tmpArray[$amfcount]['id'] = $wk['fbid'];
					$tmpArray[$amfcount]['action'] = $wk['action'];
					$tmpArray[$amfcount]['item'] = $wk['objectArray']['itemName'];
					if ($amfcount < $this->botspeed - 1)
					{
						$amfcount++;
						continue;
					}
					$amf2 = $this->_fnAMFSend($amf);
					$amf = '';
					$amfcount = 0;
					if ($amf2 === false) continue;
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						$harvestItem = @$returned['data']['harvestItem'];
						$rewardLink = @$returned['data']['rewardLink'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' ' . $tmpArray[$key]['item'] . ' - Experience: ' . $returned['data']['xpYield'] . ' - Coins: ' . $returned['data']['goldYield'] . ' - Result: ' . $this->zErrCGen[$resp]);
							if ($harvestItem != '' || $rewardLink != '') {
								AddLog2 ( "Reward: $harvestItem" );
								AddRewardLog($harvestItem, $rewardLink);
							}
						}
					}
				}
			}
			if ($amf != '') //Still have requests left
			{
				$amf2 = $this->_fnAMFSend($amf);
				if ($amf2 !== false) {
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' ' . $tmpArray[$key]['item'] . ' - Experience: ' . $returned['data']['xpYield'] . ' - Coins: ' . $returned['data']['goldYield'] . ' - Result: ' . $this->zErrCGen[$resp]);
						}
					}
				}
			}
		}
		if ($this->settings['domissions'] == 1) {
			//Get A Random Mission
			$amf = CreateRequestAMF('', 'MissionService.getRandomMission');
			$amf->_bodys[0]->_value[1][0]['params'][0] = $fr_fbid;
			$amf2 = $this->_fnAMFSend($amf);
			if ($amf2 !== false)
			{
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$err = $returned['errorData'];
					if($resp == 0 && isset($returned['data']['type']))
					{
						$amf = CreateRequestAMF('', 'MissionService.completeMission');
						$amf->_bodys[0]->_value[1][0]['params'][0] = $fr_fbid;
						$amf->_bodys[0]->_value[1][0]['params'][1] = $returned['data']['type'];
						$amf2 = $this->_fnAMFSend($amf);
						$mission = $returned['data']['type'];
						foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
						{
							if($resp == 0)
							{
								AddLog2('Do Mission: ' . ucfirst($mission) . ' - Result: ' . $this->zErrCGen[$resp]);
							}
						}

					}
				}
			}
		}
		AddLog2('-------------------------------------');

	}
	private function _fnGetBotSettings()
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

	function _fnRemoveNeighbor($neighborid)
	{
		$url = 'http://apps.facebook.com/onthefarm/neighbors.php?zyUid=' . $this->userId . '&zySnuid=' . $this->userId . '&zySnid=1&zySig=' . $this->_token;
		$contents = 'action=removeNeighbor&uid=' . $neighborid;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
		curl_setopt($ch, CURLOPT_COOKIE, $this->_fnGetCookie());
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language: en-US', 'Pragma: no-cache'));
		if ($_SESSION['use_proxy']) {
			curl_setopt($ch, CURLOPT_PROXY, trim($proxy_settings[0]));
			curl_setopt($ch, CURLOPT_PROXYPORT, intval($proxy_settings[1]));
			if (isset($_SESSION['proxy_settings'][2]) && isset($_SESSION['proxy_settings'][3])) { // is set proxy user and password
				$authorization = trim($_SESSION['proxy_settings'][2]) . ':' . trim($_SESSION['proxy_settings'][3]);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $authorization);
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			}
		}
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode == 404) {
			AddLog2("fvNeighbors - Delete: Error 404/Page Not Found");
			return;
		}
		if($httpCode == 500) {
			AddLog2("fvNeighbors - Delete: Error 500/Internal Server Error");
			return;
		}
		if(empty($response)) {
			AddLog2("fvNeighbors - Delete: Empty Response Returned");
			return;
		}
		if ($httpCode == 200)
		{
			$fvSQL = "DELETE FROM neighbors WHERE neighbors_fbid='" . $neighborid . "'";
			$q = $this->_fnNeighborsDBM->query($fvSQL);
			AddLog2('fvNeighbors - Action: Delete - NeighborID: ' . $neighborid . ' - OK');
		}
		curl_close ($ch);
	}

	function _fnCancelNeighbor($neighborid)
	{
		$url = 'http://apps.facebook.com/onthefarm/neighbors.php?zyUid=' . $this->userId . '&zySnuid=' . $this->userId . '&zySnid=1&zySig=' . $this->_token;
		$contents = 'action=cancelRequest&uid=' . $neighborid;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
		curl_setopt($ch, CURLOPT_COOKIE, $this->_fnGetCookie());
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language: en-US', 'Pragma: no-cache'));
		if ($_SESSION['use_proxy']) {
			curl_setopt($ch, CURLOPT_PROXY, trim($_SESSION['proxy_settings'][0]));
			curl_setopt($ch, CURLOPT_PROXYPORT, intval($_SESSION['proxy_settings'][1]));
			if (isset($_SESSION['proxy_settings'][2]) && isset($_SESSION['proxy_settings'][3])) { // is set proxy user and password
				$authorization = trim($_SESSION['proxy_settings'][2]) . ':' . trim($_SESSION['proxy_settings'][3]);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $authorization);
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			}
		}
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode == 404) {
			AddLog2("fvNeighbors - Cancel: Error 404/Page Not Found");
			return;
		}
		if($httpCode == 500) {
			AddLog2("fvNeighbors - Cancel: Error 500/Internal Server Error");
			return;
		}
		if(empty($response)) {
			AddLog2("fvNeighbors - Cancel: Empty Response Returned");
			return;
		}
		if ($httpCode == 200)
		{
			$fvSQL = "DELETE FROM neighbors WHERE neighbors_fbid='" . $neighborid . "'";
			$q = $this->_fnNeighborsDBM->query($fvSQL);
			AddLog2('fvNeighbors - Action: Cancel - NeighborID: ' . $neighborid . ' - OK');
		}
		curl_close ($ch);
		//AddLog2($request);
		//echo $vHTTPResponse;
	}
	function _fnGetCookie()
	{
		$newcookiestr = trim(file_get_contents($_SESSION['base_path'] .  $_SESSION['userId'] . '_cookie.txt'));
		return $newcookiestr;
	}
	function fnDeleteNeigh($fbid)
	{
		$fvSQL = 'UPDATE neighbors ';
		$fvSQL .= 'SET neighbors_delete=1 ';
		$fvSQL .= "WHERE neighbors_fbid='$fbid';";
		$q = $this->_fnNeighborsDBM->queryExec($fvSQL);
	}
	function fnNNCount()
	{
		$fvSQL = "SELECT count(*) as ncnt FROM neighborsn";
		$q = $this->_fnNeighborsDBM->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0]['ncnt'];
	}
}

?>
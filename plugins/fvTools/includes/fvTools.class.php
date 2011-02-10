<?php
class fvTools
{
	var $userId, $newids;
	var $sizeX, $sizeY, $zErrCGen, $reload;
	var $haveWorld, $_fvToolsDB, $building;
	var $settings, $freespace, $botspeed, $objects;

	private function _refreshWorld()
	{
		$this->zErrCGen = array(
		0 => 'OK', 1 => 'Error - Authorization', 2=> 'Error - User Data Missing',
		3 => 'Error - Invalid State', 4 => 'Error - Invalid Data', 5 => 'Error - Missing Data',
		6 => 'Error - Action Class Error', 7 => 'Error - Action Method Error',
		8 => 'Error - Resource Data Missing', 9 => 'Error - Not Enough Money',
		10 => 'Error - Outdated Game Version', 25 => 'Error - General Transport Failure',
		26 => 'Error - No User ID', 27 => 'Error - No Session', 28 => 'Retry Transaction',
		29 => 'Force Reload');
		$this->_fvGetBotSettings();
		$this->haveWorld = true;
	}

	private function _fvTools_checkDB()
	{
		$q = $this->_fvToolsDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fvToolsDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
		$q = $this->_fvToolsDBW->query('SELECT * FROM myworld LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating MyWorld Table');
			$fvSQL = 'CREATE TABLE
						myworld (
                		myworld_id INTEGER PRIMARY KEY,
                		myworld_className CHAR(250),
                		myworld_type CHAR(250),
                		myworld_itemName CHAR(25),
                		myworld_itemRealName CHAR(250),
                		myworld_itemCode CHAR(4),
                		myworld_posx INTEGER,
                		myworld_posy INTEGER,
                		myworld_sizex INTEGER,
                		myworld_sizey INTEGER,
                		myworld_subtype CHAR(250),
                		myworld_direction INTEGER DEFAULT 1,
                		myworld_state CHAR(10),
                		myworld_fullybuilt INTEGER DEFAULT 0,
                		myworld_contents TEXT
			)';
			$this->_fvToolsDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_className ON myworld(myworld_className)';
			$this->_fvToolsDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_itemName ON myworld(myworld_itemName)';
			$this->_fvToolsDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_type ON myworld(myworld_type)';
			$this->_fvToolsDBW->queryExec($fvSQL);
		}

		$q = $this->_fvToolsDBM->query('SELECT * FROM locations LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Empty Locations Table');
			$fvSQL = 'CREATE TABLE
              			locations (
                		loc_id INTEGER PRIMARY KEY,
                		loc_x INTEGER,
                		loc_y INTEGER
			)';
			$this->_fvToolsDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX loc_x ON locations(loc_x)';
			$this->_fvToolsDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX loc_y ON locations(loc_y)';
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
		$q = $this->_fvToolsDBM->query('SELECT * FROM locrect LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Large Areas Table');
			$fvSQL = 'CREATE TABLE
              			locrect (
			 			locrect_id INTEGER PRIMARY KEY,
                		locrect_x INTEGER,
                		locrect_y INTEGER,
                		locrect_sizex INTEGER,
                		locrect_sizey INTEGER
			)';
			$this->_fvToolsDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX locrect_sizex ON locrect(locrect_sizex)';
			$this->_fvToolsDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX locrect_sizey ON locrect(locrect_sizey)';
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
	}

	//Function fvTools class initializer
	function fvTools($inittype = '')
	{
		list($level, $gold, $cash, $this->sizeX, $this->sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $this->flashRevision) = explode(';', fBGetDataStore('playerinfo'));
		$this->userId = $_SESSION['userId'];
		$this->flashRevision = $_SESSION['flashRevision'];
		$this->haveWorld = false;

		if(!is_numeric($this->userId))
		{
			AddLog2("Farmville Bot Not Initialized/User Unknown");
			echo "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fvToolsDBM = new SQLiteDatabase(fvTools_Path . PluginF(fvTools_Main));
		$this->_fvToolsDBW = new SQLiteDatabase(fvTools_Path . PluginF(fvTools_World));
		if(!$this->_fvToolsDBM || !$this->_fvToolsDBW)
		{
			$this->error = 'fvTools - Database Error';
			return;
		}
		$this->_fvTools_checkDB();
		$this->_fvToolsDBM->queryExec('PRAGMA cache_size=20000');
		$this->_fvToolsDBM->queryExec('PRAGMA synchronous=OFF');
		$this->_fvToolsDBM->queryExec('PRAGMA count_changes=OFF');
		$this->_fvToolsDBM->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvToolsDBM->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fvToolsDBW->queryExec('PRAGMA cache_size=20000');
		$this->_fvToolsDBW->queryExec('PRAGMA synchronous=OFF');
		$this->_fvToolsDBW->queryExec('PRAGMA count_changes=OFF');
		$this->_fvToolsDBW->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvToolsDBW->queryExec('PRAGMA temp_store=MEMORY');
		//Get Settings
		$this->_fvUpdateWorldDB();
		if ($inittype != 'formload')
		{
			$this->_refreshWorld();
			$this->_fvDoWork();
		}
	}

	function fvGetSettings($setting = '')
	{
		$fvSQL = "SELECT settings_value FROM settings WHERE settings_name='$setting'";
		$q = $this->_fvToolsDBM->query($fvSQL);
		if($q !== false)
		{
			$results = $q->fetchSingle();
			return $results;
		}
		return false;
	}

	private function _fvUpdateWorldDB()
	{
		$fvSQL = "DELETE FROM myworld";
		$this->_fvToolsDBW->query($fvSQL);
		$this->_fvToolsDBW->query('BEGIN;');
		foreach(GetObjects() as $world)
		{

			$contents = empty($world['contents']) ? '' : serialize(@$world['contents']);
			$item = Units_GetUnitByName($world['itemName']);
			$item['realname'] = str_replace("'", "''", @$item['realname']);
			$item['sizeX'] = (@$item['sizeX'] < 1) ? 1 : @$item['sizeX'];
			$item['sizeY'] = (@$item['sizeY'] < 1) ? 1 : @$item['sizeY'];
			$world['direction'] = !isset($world['direction']) ? 1 : $world['direction'];
			$fvSQL = 'INSERT OR REPLACE INTO myworld(';
			$fvSQL .= 'myworld_id, myworld_className,';
			$fvSQL .= 'myworld_type, myworld_itemName,';
			$fvSQL .= 'myworld_itemRealName, myworld_itemCode,';
			$fvSQL .= 'myworld_posx, myworld_posy, myworld_contents,';
			$fvSQL .= 'myworld_sizex, myworld_sizey, myworld_subtype,';
			$fvSQL .= 'myworld_direction, myworld_state, myworld_fullybuilt) VALUES(';
			$fvSQL .= "'" . $world['id'] . "', '" . @$item['className'] . "',";
			$fvSQL .= "'" . $item['type'] . "', '" . $item['name'] . "',";
			$fvSQL .= "'" . trim($item['realname']) . "', '" . $item['code'] . "',";
			$fvSQL .= "'" . $world['position']['x'] . "', '" . $world['position']['y'] . "',";
			$fvSQL .= "'" . $contents . "', '" . @$item['sizeX'] . "',";
			$fvSQL .= "'" . @$item['sizeY'] . "', '" . @$item['subtype'] . "',";
			$fvSQL .= "'" . @$world['direction'] . "', '" . @$world['state']. "',";
			$fvSQL .= "'" . @$world['isFullyBuilt'] . "')";
			$this->_fvToolsDBW->queryExec($fvSQL);
			unset($item);
		}
		$this->_fvToolsDBW->query('COMMIT;');
	}

	function fvDoSettings($post = '')
	{
		if (isset($post['fgbuildings'])) {
			unset($post['fgbuildings']);
			foreach ($post as $key => $value)
			{
				if (is_numeric($key)) {
					$post[$key]['enable'] = (isset($value['enable'])) ? 1 : 0;
					$post[$key]['reverse'] = (isset($value['reverse'])) ? 1 : 0;
					$post[$key]['harvest'] = (isset($value['harvest'])) ? 1 : 0;
					$post[$key]['lastitem'] = (isset($value['lastitem'])) ? 1 : 0;
					$post[$key]['leaveitem'] = (isset($value['leaveitem'])) ? 1 : 0;
					$post[$key]['onlyone'] = (isset($value['onlyone'])) ? 1 : 0;
					$post[$key]['cycles'] = (isset($value['cycles']) && $value['cycles'] > 0) ? $value['cycles'] : 0;
				} else {
					unset($post['key']);
				}

			}
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fgbuildings','" . serialize($post) . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
		if (isset($post['ftbuilder'])) {
			foreach ($post['builder'] as $build)
			{
				$newbuild[$build] = 1;
			}
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ftbuilder','" . serialize($newbuild) . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
		if (isset($post['ftFarmLimits'])) {
			unset($post['userId'], $post['ftFarmLimits']);
			foreach ($post as $code=>$value)
			{
				if (!is_numeric($value)) unset($post[$code]);
			}
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ftFarmLimits','" . serialize($post) . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}

		if (isset($post['fthybrids'])) {
			unset($post['userId'], $post['fthybrids']);
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fthybrids','" . serialize($post) . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
	}
	function fvDoExtraSettings($post = '')
	{
		if (isset($post['fgwater'])) {
			$watertrees = (isset($post['watertrees'])) ? 1 : 0;
			$waterless = (isset($post['waterless'])) ? 1 : 0;
			$fertcrops = (isset($post['fertcrops'])) ? 1 : 0;
			$deltrees = (isset($post['deltrees'])) ? 1 : 0;
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fgwater','" . $watertrees . "');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fgwaterl','" . $waterless . "');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fgfert','" . $fertcrops . "');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('deltrees','" . $deltrees . "');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('trees','" . serialize($post['tree']) . "');";
			$this->_fvToolsDBM->queryExec($fvSQL);
		}
	}

	function fvGetBuildings()
	{
		$fvSQL = "SELECT * FROM myworld WHERE (myworld_itemName = 'duckpond_finished' OR myworld_subtype='animal_pens' OR myworld_className='OrchardBuilding' OR myworld_className='TurkeyRoostBuilding')";
		$fvSQL .= " AND myworld_fullybuilt='1'";
		//AND myworld_className<>'NurseryBuilding'
		$q = $this->_fvToolsDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	private function _fvDoWork()
	{
		$fgsettings = unserialize($this->fvGetSettings('fgbuildings'));
		$this->fbsettings = unserialize($this->fvGetSettings('ftbuilder'));
		$this->fbFarmLims = unserialize($this->fvGetSettings('ftFarmLimits'));
		$fgwater = $this->fvGetSettings('fgwater');
		$fgfert = $this->fvGetSettings('fgfert');
		$fgdelt = $this->fvGetSettings('deltrees');
		$this->_fvDoGreenhouse();
		if (!empty($this->fbFarmLims)) $this->_fvFarmLimits();
		if ($fgwater == 1) $this->_fvWaterTrees();
		if ($fgfert == 1) $this->_fvFertilizeCrops();
		if ($fgdelt == 1) $this->_fvDeleteTrees();
		if (!empty($this->fbsettings)) $this->_fvBuildBuildings();
		$this->newids = unserialize($this->fvGetSettings('newids'));
		$this->reload = false;
		//Restore Farm Before Starting New Work
		If(!empty($this->newids))
		{
			foreach ($this->newids as $id) {
				$nid[0] = $id;
				@$this->_fvMoveItemsIn($id['building'], $nid);
			}
			DoInit();
			$this->_fvUpdateWorldDB();
			$this->newids = array();
		}
		if (empty($fgsettings)) return;
		foreach ($fgsettings as $key => $building)
		{
			if ($building['enable'] == 1 && $building['cycles'] > 0 && is_array($building))
			{
				for ($x = 0; $x < $building['cycles']; $x++)
				{
					$this->_fvGetFarmSnapShot();
					AddLog2('---------Beginning Cycle ' . $x . '---------');
					$out = $this->_fvMoveItemsOut($key);
					if ($out !== false  && !empty($this->newids))
					{
						if ($building['reverse'] == 1) {
							$firstitem[0] = $this->newids[0];
							$this->_fvMoveItemsIn($key,$firstitem);
						}
						sort($this->newids);
						if ($building['lastitem'] != 1 && $building['reverse'] != 1) $finalitem[0] = array_pop($this->newids);
						Do_Farm_Work($this->newids, 'harvest');
						//DoInit();
						//$this->_fvUpdateWorldDB();
						$this->_fvMoveItemsIn($key, $this->newids);
						if ($building['lastitem'] != 1 && $building['reverse'] != 1) $this->_fvMoveItemsIn($key, $finalitem);
						DoInit();
						$this->_fvUpdateWorldDB();
						if ($building['harvest'] == 1)
						{
							$ready[0] = $this->_fvCheckBuildState($key);
							if($ready[0] !== false && isset($ready[0]['id'])) Do_Farm_Work($ready, 'harvest');
						}
						$this->_fvCheckFarmSnapShot($key);
					}
					AddLog2('---------Finished Cycle ' . $x . '---------');
				}
			}
			//unset($this->newids);
		}
	}
	private function _fvMoveItemsOut($buildingid)
	{
		$this->_findEmpty();
		if (is_numeric($buildingid)) {
			$this->newids = array();
			$fvSQL = "SELECT * FROM myworld WHERE myworld_id='$buildingid'";
			$q = $this->_fvToolsDBW->query($fvSQL);
			$results = $q->fetchAll(SQLITE_ASSOC);
			$binfo = $results[0];
			$contents = unserialize($binfo['myworld_contents']);
			$moveables = array();
			if (empty($contents)) { AddLog2('fvTools: Building is Missing Contents'); return false;}
			foreach ($contents as $items)
			{
				$info = Units_GetUnitByCode($items['itemCode']);
				if (empty($info)) AddLog2('fvTools: Item is Missing Unit Info');
				for ($x = 0; $x < $items['numItem']; $x++)
				{
					$info['buildingId'] = $buildingid;
					$info['className'] = !isset($info['className']) ? ucfirst($info['type']) : $info['className'];
					$moveables[] = $info;
				}
			}
			$fgsettings = unserialize($this->fvGetSettings('fgbuildings'));
			if ($fgsettings[$buildingid]['leaveitem'] == 1) array_pop($moveables);
			if ($fgsettings[$buildingid]['onlyone'] == 1) {
				$newmove = array_pop($moveables);
				$moveables = array();
				$moveables[0] = $newmove;
			}
			$amf = '';
			$amfcount = 0;
			$id = 63000;
			if (empty($moveables)) AddLog2('fvTools: No Items to Move');
			for ($x = 0; $x < count($moveables); $x++)
			{
				$place = $this->_GetLocation($moveables[$x]);
				if ($place !== false) {
					$amf = CreateMultAMFRequest($amf, $amfcount, 'place', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['itemName'] = $moveables[$x]['name'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['className'] = $moveables[$x]['className'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['id'] = $id;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['deleted'] = false;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['direction'] = 1;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['tempId'] = -1;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['plantTime'] = time() - 82800 - 1;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['x'] = $place['x'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['y'] = $place['y'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['z'] = 0;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isStorageWithdrawal'] = $buildingid;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isGift'] = false;
					$tmpArray[$amfcount]['realname'] = trim($moveables[$x]['realname']);
					$tmpArray[$amfcount]['class'] = trim($moveables[$x]['className']);
					$tmpArray[$amfcount]['building'] = $buildingid;
					$tmpArray[$amfcount]['posx'] = $place['x'];
					$tmpArray[$amfcount]['posy'] = $place['y'];
					$tmpArray[$amfcount]['name'] = $moveables[$x]['name'];
					$id++;
					if ($amfcount < $this->botspeed - 1)
					{
						$amfcount++;
						continue;
					}
					$amf2 = $this->_fvAMFSend($amf);
					$amf = '';
					$amfcount = 0;
					$cnt = 0;
					$this->_fvToolsDBW->queryExec('BEGIN;');
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							$cnt++;
							$fvSQL = "INSERT OR REPLACE INTO myworld(myworld_id, myworld_itemName, myworld_posx, myworld_posy, myworld_className) ";
							$fvSQL .= "VALUES('" . $returned['data']['id'] . "', '" . $tmpArray[$key]['name'] . "', '";
							$fvSQL .= $tmpArray[$key]['posx'] . "', '" . $tmpArray[$key]['posy'] . "', '" . $tmpArray[$key]['class'] . "');";
							$this->_fvToolsDBW->queryExec($fvSQL);
							$this->newids[] = array(
								'id' => $returned['data']['id'],
								'itemName' => $tmpArray[$key]['name'],
								'className' => $tmpArray[$key]['class'],
								'building' => $tmpArray[$key]['building'],
								'position' => array('x' => $tmpArray[$key]['posx'], 'y' => $tmpArray[$key]['posy']));
						}
						else
						{
							AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $this->zErrCGen[$resp]);
							continue;
						}
					}
					$this->_fvToolsDBW->queryExec('COMMIT;');
					$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('newids','" . serialize($this->newids) . "');";
					$this->_fvToolsDBM->queryExec($fvSQL);
					AddLog2('fvTools: Moved ' . $cnt . " Items to Farm");
					if ($this->reload === true) { $this->reload = false; DoInit(); }
					$this->_findEmpty();
				} else {
					AddLog2('fvTools: No Locations Left to Place Items');
					if (!empty($amf)) AddLog2('fvTools: Finishing Remaining Items in Queue');
					break;
				}
			}
			//AMFS May BE Left
			if (!empty($amf)) {
				//AddLog2(print_r($amf,true));
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 !== false)
				{
					$cnt = 0;
					$this->_fvToolsDBW->queryExec('BEGIN;');
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							$cnt++;
							$fvSQL = "INSERT OR REPLACE INTO myworld(myworld_id, myworld_itemName, myworld_posx, myworld_posy, myworld_className) ";
							$fvSQL .= "VALUES('" . $returned['data']['id'] . "', '" . $tmpArray[$key]['name'] . "', '";
							$fvSQL .= $tmpArray[$key]['posx'] . "', '" . $tmpArray[$key]['posy'] . "', '" . $tmpArray[$key]['class'] . "');";
							$this->_fvToolsDBW->queryExec($fvSQL);
							$this->newids[] = array(
								'id' => $returned['data']['id'],
								'itemName' => $tmpArray[$key]['name'],
								'className' => $tmpArray[$key]['class'],
								'building' => $tmpArray[$key]['building'],
								'position' => array('x' => $tmpArray[$key]['posx'], 'y' => $tmpArray[$key]['posy']));
						}
						else
						{
							AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $this->zErrCGen[$resp]);
							continue;
						}

					}
					$this->_fvToolsDBW->queryExec('COMMIT;');
					$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('newids','" . serialize($this->newids) . "');";
					$this->_fvToolsDBM->queryExec($fvSQL);
					AddLog2('fvTools: Moved ' . $cnt . " Items to Farm");
				}
			}
			if ($this->reload === true) { $this->reload = false; DoInit(); }
		}
		//AddLog2(print_r($this->newids,true));
	}

	private function _fvMoveItemsIn($buildingid = '', $ids)
	{
		if (is_numeric($buildingid)) {
			$fvSQL = "SELECT * FROM myworld WHERE myworld_id='$buildingid'";
			$q = $this->_fvToolsDBW->query($fvSQL);
			$results = $q->fetchAll(SQLITE_ASSOC);
			$binfo = $results[0];
			$amf = '';
			$amfcount = 0;
			$id = 63000;
			for ($x = 0; $x < count($ids); $x++)
			{
				$item = Units_GetUnitByName($ids[$x]['itemName']);
				if (!$this->_fvCheckId($ids[$x])) { unset($this->newids[$x]); continue; }
				$amf = CreateMultAMFRequest($amf, $amfcount, 'store', 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['itemName'] = $binfo['myworld_itemName'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['className'] = $binfo['myworld_className'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['id'] = $buildingid;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['state'] = 'bare';
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['deleted'] = false;
				//$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['direction'] = $binfo['direction'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['tempId'] = 'NaN';
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['plantTime'] = time() - 82800 - 1;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['x'] = $binfo['myworld_posx'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['y'] = $binfo['myworld_posy'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position']['z'] = 0;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isGift'] = true;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['resource'] = $ids[$x]['id'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['cameFromLocation'] = 0;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedItemCode'] = trim($item['code']);
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedClassName'] = $ids[$x]['className'];
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedItemName'] = $ids[$x]['itemName'];
				$tmpArray[$amfcount]['realname'] = trim($item['realname']);
				$tmpArray[$amfcount]['key'] = $x;
				$id++;
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				//AddLog2(print_r($amf,true));
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) { continue; }
				$cnt = 0;
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						$cnt++;
						unset($this->newids[$tmpArray[$key]['key']]);
					}
					else
					{
						AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $this->zErrCGen[$resp]);
						continue;
					}
				}
				$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('newids','" . serialize($this->newids) . "');";
				$this->_fvToolsDBM->queryExec($fvSQL);
				AddLog2('fvTools: Moved ' . $cnt . " Items to Building");
				if ($this->reload === true) { $this->reload = false; DoInit(); }
			}
			//AMFS May BE Left
			if (!empty($amf)) {
				//AddLog2(print_r($amf,true));
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 !== false)
				{
					$cnt = 0;
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							$cnt++;
							unset($this->newids[$tmpArray[$key]['key']]);
						}
						else
						{
							AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $this->zErrCGen[$resp]);
							continue;
						}

					}
					$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('newids','" . serialize($this->newids) . "');";
					$this->_fvToolsDBM->queryExec($fvSQL);
					AddLog2('fvTools: Moved ' . $cnt . " Items to Building");
				}
				if ($this->reload === true) { $this->reload = false; DoInit(); }
			}
		}
	}

	private function _fvCheckBuildState($buildingid)
	{
		$fvSQL = "SELECT * FROM myworld";
		$q = $this->_fvToolsDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach(GetObjects() as $world)
		{
			if ($world['id'] == $buildingid){
				if ($world['state'] == 'ripe') {
					return $world;
				} else return false;
			}
		}
		return false;

	}

	private function _fvUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "');";
		$this->_fvToolsDBM->queryExec($fvSQL);
	}

	private function _fvAMFSend($amf)
	{
		$serializer = new AMFSerializer();
		$result3 = $serializer->serialize($amf);// serialize the data
		$answer = Request('', $result3);
		$amf2 = new AMFObject($answer);
		$deserializer2 = new AMFDeserializer($amf2->rawData);// deserialize the data
		$deserializer2->deserialize($amf2);// run the deserializer
		if (!isset($amf2->_bodys[0]->_value['data'][0])) { DoInit(); return false; }
		Check4Rewards($amf2);
		foreach (@$amf2->_bodys[0]->_value['data'] as $key => $returned)
		{
			$resp = $returned['errorType'];
			$err = $returned['errorData'];
			if ($resp == 28 || $resp == 29)
			{
				$this->reload = true;

			}
		}

		return $amf2;
	}



	private function _fvGetBotSettings()
	{
		//Get Settings From Bot
		if (file_exists($_SESSION['base_path'] . F('settings.txt')))
		{
			$settings_list = @explode(';', trim(file_get_contents($_SESSION['base_path'] . F('settings.txt'))));

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

	private function _findEmpty()
	{
		$maxX = $this->sizeX;
		$maxY = $this->sizeY;
		for ($x = 0; $x < $maxX; $x++)
		for ($y = 0; $y < $maxY; $y++)
		$object[$x][$y] = 'empty';
		$fvSQL = "SELECT * FROM myworld ORDER BY myworld_posx, myworld_posy";
		$q = $this->_fvToolsDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach($results as $item)
		{
			$item2 = Units_GetUnitByName($item['myworld_itemName']);
			$item2['sizeX'] = !isset($item2['sizeX']) ? 1 : $item2['sizeX'];
			$item2['sizeY'] = !isset($item2['sizeY']) ? 1 : $item2['sizeY'];
			if ($item['myworld_state'] == 'vertical' || $item['myworld_direction'] != '1')
			{
				for ($x = $item['myworld_posx']; $x < ($item['myworld_posx'] + $item2['sizeY']); $x++)
				for ($y = $item['myworld_posy']; $y < ($item['myworld_posy'] + $item2['sizeX']); $y++)
				unset($object[$x][$y]);
			} else {
				for ($x = $item['myworld_posx']; $x < ($item['myworld_posx'] + $item2['sizeX']); $x++)
				for ($y = $item['myworld_posy']; $y < ($item['myworld_posy'] + $item2['sizeY']); $y++)
				unset($object[$x][$y]);
			}
		}
		$this->objects = $object;
	}

	private function _GetLocation($itemloc)
	{
		$maxX = $this->sizeX;
		$maxY = $this->sizeY;
		if (@$itemloc['sizeX'] > 1 || @$itemloc['sizeY'] > 1)
		{
			for ($x = 0; $x < $maxX; $x++)
			{
				for ($y = 0; $y < $maxY; $y++)
				{
					if (isset($this->objects[$x][$y]) &&
					isset($this->objects[$x+1][$y]) &&
					isset($this->objects[$x][$y+1]) &&
					isset($this->objects[$x+1][$y+1]))
					{
						unset($this->objects[$x][$y],
						$this->objects[$x+1][$y],
						$this->objects[$x][$y+1],
						$this->objects[$x+1][$y+1]);
						return array('x' => $x, 'y' => $y);
					}

				}
			}
		} else {
			for ($x = 0; $x < $maxX; $x++)
			{
				for ($y = 0; $y < $maxY; $y++)
				{
					if (isset($this->objects[$x][$y])) {
						unset($this->objects[$x][$y]);
						return array('x' => $x, 'y' => $y);
					}
				}
			}
		}
		return false;
	}

	private function _fvCheckId($info = '')
	{
		if (is_array($info)) {
			$fvSQL = "SELECT myworld_itemName FROM myworld WHERE myworld_id='" . $info['id'] . "'";
			$q = $this->_fvToolsDBW->query($fvSQL);
			$result = $q->fetchSingle();
			if ($result == $info['itemName']) {
				return true;
			} else return false;
		}
		return false;
	}

	private function _fvWaterTrees()
	{
		$ingiftbox = unserialize(fBGetDataStore('ingiftbox'));
		$fgtrees = unserialize($this->fvGetSettings('trees'));
		$cancnt = @$ingiftbox['wO'];
		$uInfo = Units_GetUnitByName('mysteryseedling', true);
		$fgwaterl = $this->fvGetSettings('fgwaterl');
		if ($fgwaterl == 1) $uInfo['matsNeeded'] = $uInfo['matsNeeded'] - 1;
		if ($cancnt == 0) return;
		foreach(GetObjects() as $world)
		{
			if ($world['itemName'] == 'mysteryseedling' && isset($fgtrees[$world['seedType']])) {
				$cneeded = $uInfo['matsNeeded'] - $world['contents'][0]['numItem'];
				for ($x = 0; $x < $cneeded; $x++)
				{
					if ($cancnt == 0) return;
					$amf = CreateRequestAMF('store', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $world['itemName'];
					$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $world['className'];
					$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $world['id'];
					$amf->_bodys[0]->_value[1][0]['params'][1]['state'] = 'construction';
					$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
					$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = 'NaN';
					$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $world['position']['x'];
					$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $world['position']['y'];
					$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['resource'] = 0;
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['cameFromLocation'] = 0;
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['storedItemCode'] = 'wO';
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['storedClassName'] = 'BuildingPart';
					$amf->_bodys[0]->_value[1][0]['params'][2][0]['storedItemName'] = 'wateringcan';
					$res = RequestAMF($amf);

					if ($res == 'OK') {
						AddLog2('fvTools: Watered ' .  trim(Units_GetRealnameByCode($world['seedType'])) . ' - Mystery Seedling');
						$cancnt--;
					} else {
						AddLog2('fvTools: Error Watering ' .  trim(Units_GetRealnameByCode($world['seedType'])) . ' - Mystery Seedling - Error: ' . $res);
						DoInit();
					}
				}
			}
		}
	}

	private function _fvFertilizeCrops()
	{
		$inconbox = unserialize(fBGetDataStore('inconbox'));
		$fertcnt = @$inconbox['eA'];
		if ($fertcnt == 0) return;
		$pcnt = 0;
		$fcnt = 0;
		foreach(GetObjects() as $world)
		{
			if ($world['className'] == 'Plot') {
				$pcnt++;
				if ($world['isJumbo'] != 1) $fcnt++;
			}
		}
		AddLog2('fvTools: ' . round($fcnt/$pcnt * 100) . '% of Farm is Unfertilized');
		if (($fcnt/$pcnt * 100) < 25) return;
		$amf = CreateRequestAMF('use', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_fertilize_all';
		$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CFertilizeAll';
		$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = 63000;
		$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
		$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['storageId'] = -1;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['targetUser'] = $_SESSION['userId'];
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isFree'] = false;

		$res = RequestAMF($amf);

		if ($res == 'OK') {
			AddLog2('fvTools: Fertilized Crops');
		} else {
			AddLog2('fvTools: Error Fertilizing Crops - Error: ' . $res);
			DoInit();
		}
	}

	private function _fvGetFarmSnapShot()
	{
		$this->snapshot = array();
		$fvSQL = "SELECT * FROM myworld";
		$q = $this->_fvToolsDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $result)
		{
			$this->snapshot[$result['myworld_id']]['itemName'] = $result['myworld_itemName'];
			$this->snapshot[$result['myworld_id']]['className'] = $result['myworld_className'];
		}
	}

	private function _fvCheckFarmSnapShot($buildid)
	{

		$fvSQL = "SELECT * FROM myworld";
		$q = $this->_fvToolsDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $key=>$result)
		{
			if (isset($this->snapshot[$result['myworld_id']])) unset($results[$key]);
		}
		if (is_array($results) && !empty($results))
		{
			AddLog2('fvTools: Restoring Farm');
			foreach ($results as $result)
			{
				$restorefarm[0] = array('id' => $result['myworld_id'],
								'itemName' => $result['myworld_itemName'],
								'className' => $result['myworld_className'],
								'building' => $buildid,
								'position' => array('x' => $result['myworld_posx'], 'y' => $result['myworld_posy']));

				$this->_fvMoveItemsIn($buildid, $restorefarm);
			}
			DoInit();
		}
	}

	private function _fvBuildBuildings()
	{
		DoInit();
		$this->giftbox = array_merge(unserialize(fBGetDataStore('ingiftbox')), unserialize(fBGetDataStore('inconbox')));
		foreach ($this->fbsettings as $building=>$trash)
		{
			if ($building == 'mysteryseedling') continue;
			$bobjs = GetObjectsByName($building);
			if (empty($bobjs)) continue;
			foreach ($bobjs as $build)
			{
				$bRealName = Units_GetRealnameByName($build['itemName']);
				//AddLog2('fvTools: Found ' . $bRealName . ' - Level: ' . $build['expansionLevel']);
				$bUnit = Units_GetUnitByName($build['itemName'], true);
				$newparts = array();
				if (isset($bUnit['features'])) {
					$features = unserialize($bUnit['features']);
					foreach ($features as $feature)
					{
						foreach ($feature as $feature2)
						{
							if (!isset($feature2['upgrade'])) continue;
							foreach ($feature2['upgrade'] as $upgrade)
							{
								if ($upgrade['@attributes']['level'] > $build['expansionLevel']) {
									AddLog2('fvTools: ' . $bRealName . ' Can Be Upgraded To Level: ' . $upgrade['@attributes']['level']);
									foreach ($upgrade['part']  as $part)
									{
										$newparts[Units_GetCodeByName($part['@attributes']['name'])] = array('need' => $part['@attributes']['need'], 'have' => 0);
									}
									foreach ($build['expansionParts'] as $key=>$value)
									{
										$newparts[$key]['have'] = $value;
									}
									$this->_fvDoBuildWork($build, $newparts);
									break 3;
								}
							}
						}
					}
					continue;
				}
				if (isset($bUnit['upgrade'])) {
					$upgrades = unserialize($bUnit['upgrade']);
					foreach ($upgrades as $upgrade)
					{
						if ($upgrade['@attributes']['level'] > $build['expansionLevel']) {
							AddLog2('fvTools: ' . $bRealName . ' Can Be Upgraded To Level: ' . $upgrade['@attributes']['level']);
							foreach ($upgrade['part']  as $part)
							{
								$newparts[Units_GetCodeByName($part['@attributes']['name'])] = array('need' => $part['@attributes']['need'], 'have' => 0);
							}
							foreach ($build['expansionParts'] as $key=>$value)
							{
								$newparts[$key]['have'] = $value;
							}
							$this->_fvDoBuildWork($build, $newparts);
							break;
						}
					}
					continue;
				}
				if (isset($bUnit['matsNeeded']) && $build['isFullyBuilt'] != 1) {
					AddLog2('fvTools: Found ' . $bRealName . ' To Build');
					$itemClass = unserialize($bUnit['storageType']);
					$items = Storage_GetByName($itemClass['@attributes']['itemClass']);
					$parts = unserialize($items['itemName']);
					$newparts = array();
					if (isset($parts['part']) && $parts['part'] == 'true') {
						foreach ($build['contents'] as $content)
						{
							if ($content['itemCode'] == Units_GetCodeByName($parts['value'])) {
								$newparts[Units_GetCodeByName($parts['value'])] = array('need' => $parts['need'], 'have' => $content['numItem']);
							}
						}
					} else {
						foreach ($parts as $part)
						{
							If ($part['part'] == 'true') {
								$newparts[Units_GetCodeByName($part['value'])] = array('need' => $part['need'], 'have' => 0);
							}
						}
						foreach ($build['contents'] as $content)
						{
							$newparts[$content['itemCode']]['have'] = $content['numItem'];
						}
					}
					$this->_fvDoBuildWork($build, $newparts);
				}
			}
		}
	}
	private function _fvDoBuildWork($build, $parts)
	{
		$amfcount = 0;
		$amf = '';
		foreach ($parts as $code=>$values)
		{
			if (isset($this->giftbox[$code]) && $values['have'] < $values['need']) {
				AddLog2($code . ' - Giftbox has: ' . $this->giftbox[$code] . ' - Need: ' . ($values['need'] - $values['have']));
				for ($x = 0; $x < ($values['need'] - $values['have']); $x++)
				{
					if ($this->giftbox[$code] == 0) {unset($this->giftbox[$code]); break; }
					$amf = CreateMultAMFRequest($amf, $amfcount, 'store', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['tempId'] = 'NaN';
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['itemName'] = $build['itemName'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['className'] = $build['className'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['position'] = $build['position'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['contents'] = array();
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['state'] = $build['state'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['plantTime'] = number_format($build['plantTime'], 0, '', '');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['deleted'] = false;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1]['id'] = $build['id'];
					$partUnit = Units_GetUnitByCode($code);
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['cameFromLocation'] = 0;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedClassName'] = $partUnit['className'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isGift'] = true;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['resource'] = 0;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedItemName'] = $partUnit['name'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isFull'] = 0;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['storedItemCode'] = $code;
					$tmpArray[$amfcount]['realname'] = $partUnit['realname'];
					$this->giftbox[$code]--;
					if ($amfcount < $this->botspeed - 1)
					{
						$amfcount++;
						continue;
					}
					$amf2 = $this->_fvAMFSend($amf);
					$amf = '';
					$amfcount = 0;
					if ($amf2 === false) { DoInit(); continue; }
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Placed: ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
						} else {
							AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
						}
					}
					if ($this->reload === true) { DoInit(); $this->reload = false; }
				}
			}
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 === false) continue;
			foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
			{
				$resp = $returned['errorType'];
				$err = $returned['errorData'];
				if($resp == 0)
				{
					AddLog2('[' . $key . '] Placed: ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
				} else {
					AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
				}
			}
			if ($this->reload === true) { DoInit(); $this->reload = false; }
		}
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
	private function _fvDeleteTrees()
	{
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('deltrees','0');";
		$this->_fvToolsDBM->queryExec($fvSQL);
		$fgtrees = unserialize($this->fvGetSettings('trees'));
		$amfcount = 0;
		$amf = '';
		$msUnit = Units_GetUnitByName('mysteryseedling');
		foreach (GetObjects('MysterySeedling') as $tree)
		{
			if (!isset($fgtrees[$tree['seedType']])) {
				$item['itemName'] = $tree['itemName'];
				$item['position'] = $tree['position'];
				$item['id'] = $tree['id'];
				$item['className'] = $tree['className'];
				$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'sell', 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = array();
				$msUnit = Units_GetUnitByCode($tree['seedType']);
				$tmpArray[$amfcount]['realname'] = $msUnit['realname'];
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) { DoInit(); continue; }
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						AddLog2('[' . $key . '] Sold: ' . $tmpArray[$key]['realname'] . ' Mystery Seedling - Result: ' . $this->zErrCGen[$resp]);
					} else {
						AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
					}
				}
				if ($this->reload === true) { DoInit(); $this->reload = false; }
			}
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 === false) continue;
			foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
			{
				$resp = $returned['errorType'];
				$err = $returned['errorData'];
				if($resp == 0)
				{
					AddLog2('[' . $key . '] Sold: ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
				} else {
					AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
				}
			}
			if ($this->reload === true) { DoInit(); $this->reload = false; }
		}
	}

	private function _fvFarmLimits()
	{
		//Go Through All Objects on the Farm and Get Counts for Items
		foreach (GetObjects() as $object)
		{
			if ($object['className'] != 'MysterySeedling') {
				$uCode = Units_GetCodeByName($object['itemName']);
				if (isset($this->fbFarmLims[$uCode])) {
					@$items[$uCode]['count']++;
					@$items[$uCode]['object'][] = $object;
				}
			} else {
				$uCode = Units_GetNameByCode($object['seedType']);
				if (isset($this->fbFarmLims['tree_' . $object['seedType']])) {
					@$items['tree_' . $object['seedType']]['count']++;
					@$items['tree_' . $object['seedType']]['object'][] = $object;
				}
			}
		}
		if (empty($items)) return;
		AddLog2('fvTools: Applying Farm Limits');
		foreach ($items as $key=>$item)
		{
			if ($this->fbFarmLims[$key] < $item['count']) {
				array_splice($item['object'], ($item['count'] - $this->fbFarmLims[$key]));
				if (!empty($item['object'])) {
					foreach ($item['object'] as $object) {
						$newitems[] = $object;
					}
				}
			}
		}
		$amfcount = 0;
		$amf = '';
		foreach ($newitems as $object)
		{
			$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'sell', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $object;
			$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = array();
			$realname = Units_GetRealnameByName($object['itemName']);
			$tmpArray[$amfcount]['realname'] = $realname;
			if ($amfcount < $this->botspeed - 1)
			{
				$amfcount++;
				continue;
			}
			$amf2 = $this->_fvAMFSend($amf);
			$amf = '';
			$amfcount = 0;
			if ($amf2 === false) { DoInit(); continue; }
			foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
			{
				$resp = $returned['errorType'];
				$err = $returned['errorData'];
				if($resp == 0)
				{
					AddLog2('[' . $key . '] Sold: ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
				} else {
					AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
				}
			}
			if ($this->reload === true) { DoInit(); $this->reload = false; }
		}
		if (!empty($amf)) //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 === false) continue;
			foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
			{
				$resp = $returned['errorType'];
				$err = $returned['errorData'];
				if($resp == 0)
				{
					AddLog2('[' . $key . '] Sold: ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
				} else {
					AddLog2('fvTools Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
				}
			}
			if ($this->reload === true) { DoInit(); $this->reload = false; }
		}
	}

	private function _fvDoGreenhouse()
	{
		$bsInfo = unserialize(fBGetDataStore('bsinfo'));
		$greenhouses = GetObjectsByName('greenhousebuildable_finished');
		if (empty($greenhouses)) return;
		$trayscnt = $bsInfo[0]['upgradeUnlockedTrays'];
		$totaltrays = $trayscnt[$greenhouses[0]['expansionLevel']];
		$bsStats = unserialize(fBGetDataStore('bsstats'));
		$fthybrids = unserialize($this->fvGetSettings('fthybrids'));
		$genealogy = $bsInfo[0]['genealogy'];
		foreach ($genealogy as $gene)
		{
			foreach ($gene['ingredient'] as $key=>$ingred)
			{
				$gene['ingredient'][$key]['quantity'] = $ingred['quantity'] * 50;
			}
			$recipes[$gene['itemCode']] = $gene['ingredient'];
		}
		$alltrays = $bsStats['breedingFeatures']['farm']['greenhousebuildable_finished']['trays'];
		//Harvest Trays
		foreach ($alltrays as $key=>$tray)
		{
			if  (count($tray['helpingFriendIds']) >= 3 || $_SESSION['servertime'] >= ($tray['startTime'] + $bsInfo[0]['breedingDuration'])) {
				$amf = CreateRequestAMF('', 'BreedingService.finishBreedingProject');
				$amf->_bodys[0]->_value[1][0]['params'][0] = 'greenhousebuildable_finished';
				$amf->_bodys[0]->_value[1][0]['params'][1] = $key;
				$res = RequestAMF($amf);
				AddLog2('fvTools: Collecting Seeds From Tray ' . ($key + 1) . ': ' . $res);
				if ($res = 'OK') unset($alltrays[$key]);
			}
		}
		//Start New Trays
		if (empty($fthybrids)) return;
		$traycnt = 0;
		for ($x = count($alltrays); $x < $totaltrays; $x++)
		{
			$traycode = $fthybrids['tray_' . $x];
			$seedname = Units_GetRealnameByCode($traycode);
			$amf = CreateRequestAMF('', 'BreedingService.beginNewBreedingProject');
			$amf->_bodys[0]->_value[1][0]['params'][0] = 'greenhousebuildable_finished';
			$amf->_bodys[0]->_value[1][0]['params'][1] = $traycnt;
			$amf->_bodys[0]->_value[1][0]['params'][2] = $recipes[$traycode];
			$res = RequestAMF($amf);
			AddLog2('fvTools: Starting ' . $seedname . ' in Tray ' . ($traycnt + 1) . ': ' . $res);
			$traycnt++;
		}
	}
}
?>
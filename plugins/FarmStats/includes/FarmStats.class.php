<?php
class FarmStats
{
	var $userId, $flashRevision;
	var $xp, $error, $fvAll, $haveWorld, $units, $_fsManagerDB, $building;
	var $level, $imageRevision, $gold, $coin, $settings, $freespace, $recipe, $featurecred;
	var $fmCraft, $fmBushels, $relVersion, $giftBox, $conBox, $stBox, $fmGoodState;
	var $fvdebug, $tileset, $wither, $neighbors, $fuel, $uname, $achieve, $achievecnt, $mastery, $masterycnt;

	private function _fsManager_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = @$this->_fsManagerDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fsManagerDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "')";
			$this->_fsManagerDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('level','" . $this->level . "')";
			$this->_fsManagerDBM->queryExec($fvSQL);
		}

		$q = @$this->_fsManagerDBW->query('SELECT * FROM myworld LIMIT 1');
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
                		myworld_message TEXT,
                		myworld_itemCode CHAR(4),
                		myworld_plantTime CHAR(20),
                		myworld_state CHAR(10),
                		myworld_iconURL CHAR(250),
                		myworld_direction INTEGER,
                		myworld_sizex INTEGER,
               			myworld_sizey INTEGER,
                		myworld_posx INTEGER,
                		myworld_posy INTEGER,
                		myworld_expansionLevel INTEGER,
                		myworld_canWander INTEGER,
                		myworld_usesAltGraphic CHAR(250),
                		myworld_giftSenderId INTEGER,
                		myworld_explicitType CHAR(250)
			)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_className ON myworld(myworld_className)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_itemName ON myworld(myworld_itemName)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_type ON myworld(myworld_type)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_id ON myworld(myworld_id)';
			$this->_fsManagerDBW->queryExec($fvSQL);
		}
		$q = @$this->_fsManagerDBW->query('SELECT * FROM storage LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Storage Table');
			$fvSQL = 'CREATE TABLE
                		storage (
                		storage_className CHAR(250),
                		storage_type CHAR(250),
                		storage_itemName CHAR(25),
                		storage_itemRealName CHAR(250),
                		storage_itemCode CHAR(4),
                		storage_itemExtra INTEGER,
                		storage_itemCount INTEGER,
                		storage_iconURL CHAR(250),
                		storage_id INTEGER
			)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_className ON storage(storage_className)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_itemName ON storage(storage_itemName)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_type ON storage(storage_type)';
			$this->_fsManagerDBW->queryExec($fvSQL);
		}
		$q = @$this->_fsManagerDBW->query('SELECT * FROM fmarket LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Farmers Market Goods Table');
			$fvSQL = 'CREATE TABLE
						fmarket (
                		fmarket_id INTEGER PRIMARY KEY,
                		fmarket_className CHAR(250),
                		fmarket_type CHAR(250),
                		fmarket_itemName CHAR(25),
                		fmarket_itemRealName CHAR(250),
                		fmarket_itemCode CHAR(4),
                		fmarket_itemCount INTEGER,
                		fmarket_iconURL CHAR(250)
			)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_className ON fmarket(fmarket_className)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_itemName ON fmarket(fmarket_itemName)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_type ON fmarket(fmarket_type)';
			$this->_fsManagerDBW->queryExec($fvSQL);
		}
		$q = @$this->_fsManagerDBW->query('SELECT * FROM fmbushels LIMIT 1');
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
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_className ON fmbushels(fmbushels_className)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_itemName ON fmbushels(fmbushels_itemName)';
			$this->_fsManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_type ON fmbushels(fmbushels_type)';
			$this->_fsManagerDBW->queryExec($fvSQL);
		}
	}

	//Function FarmStats class initializer
	function FarmStats($inittype = '')
	{
		list($this->level, $this->gold, $this->coin, $this->wsizeX, $this->wsizeY, $this->uname, $locale, $this->tileset, $this->wither, $this->xp, $this->fuel, $this->flashRevision) = explode(';', fBGetDataStore('playerinfo'));
		$this->userId = $_SESSION['userId'];
		$this->error = '';
		$this->haveWorld = true;

		if(!is_numeric($this->userId))
		{
			$this->error = "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fsManagerDBM = new SQLiteDatabase(FarmStats_Path . PluginF(FarmStats_Main));
		$this->_fsManagerDBW = new SQLiteDatabase(FarmStats_Path . PluginF(FarmStats_World));
		$this->_fsManagerDBM->queryExec('PRAGMA cache_size=20000');
		$this->_fsManagerDBM->queryExec('PRAGMA synchronous=OFF');
		$this->_fsManagerDBM->queryExec('PRAGMA count_changes=OFF');
		$this->_fsManagerDBM->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fsManagerDBM->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fsManagerDBW->queryExec('PRAGMA cache_size=20000');
		$this->_fsManagerDBW->queryExec('PRAGMA synchronous=OFF');
		$this->_fsManagerDBW->queryExec('PRAGMA count_changes=OFF');
		$this->_fsManagerDBW->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fsManagerDBW->queryExec('PRAGMA temp_store=MEMORY');

		if(!$this->_fsManagerDBM || !$this->_fsManagerDBW)
		{
			$this->error = 'FarmStats - Database Error';
			return;
		}

		//Get Settings
		$this->settings = $this->fsGetSettings();
		if($inittype == 'formload')
		{
			if(empty($this->settings))
			{
				$this->error = 'Please allow FarmStats to run a cycle';
			}
			return;
		}
		//Load the world from Z*
		if($this->haveWorld === true)
		{
			if($this->settings === false)
			{
				$this->_fsManager_checkDB();//Database doesn't exist, create
				$this->_fsUpdateSettings();//Insert initial settings
				$this->_fsUpdateWorldDB();//Update the World
				$this->_fsGetEmptySpots();//Get Empty Spots
				$this->error = 'Please allow FarmStats to run a cycle to update all settings';
				return;
			}
			if($this->settings['flashRevision'] != $this->flashRevision)
			{
				$this->settings = $this->fsGetSettings();
			}
			$this->_fsUpdateWorldDB();//Update the World
			//Export Files
			$seedinfo = $this->fsGetWorldSeeds();
			save_botarray($seedinfo, F('fs_seedinfo.txt'));
			$this->_fsUpdateSettings();//Update the settings
		}
	}

	function fsGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		$q = @$this->_fsManagerDBM->query($fvSQL);
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

	function fsMakeTime($ctime)
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
			@$timestr .= $hours . ' hrs ';
		}
		$minutes = $ctime / 60;
		if ($minutes > 1)
		{
			$minutes = intval($minutes);
			@$timestr .= $minutes . ' min';
		}
		return $timestr;
	}

	function fsGetSuperPlots()
	{
		$fvSQL = "SELECT * FROM myworld WHERE myworld_className='Plot'";
		$q = $this->_fsManagerDBW->query($fvSQL);

		$results = $q->fetchAll(SQLITE_ASSOC);
		//Get Super Plots Count
		foreach ($results as $key=>$result)
		{
			@$tmp[$result['myworld_posx'] . 'x' . $result['myworld_posy']]++;
		}
		foreach ($tmp as $key => $value)
		{
			if ($value <= 1) { unset($tmp[$key]); }
		}

		$super['count'] = count($tmp);
		$super['plcount'] = array_sum($tmp);
		return $super;
	}

	function fsGetStoreBuildings()
	{
		$fvSQL = "SELECT *, COUNT(*) as mycount FROM myworld WHERE myworld_className = 'StorageBuilding' OR myworld_className = 'InventoryCellar'";
		$fvSQL .= "GROUP BY myworld_itemCode ORDER BY myworld_itemRealName";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsGetStoreBuildCnt()
	{
		$fvSQL = "SELECT COUNT(*) as mycount FROM myworld WHERE myworld_className = 'StorageBuilding' OR myworld_className = 'InventoryCellar'";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}

	function fsGetWorldSeeds()
	{
		$fvSQL = "SELECT *, COUNT(*) as scnt FROM myworld WHERE myworld_type = 'seed' ";
		$fvSQL .= "GROUP BY myworld_plantTime, myworld_itemName ORDER BY myworld_plantTime, myworld_itemRealName";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsClassCounts()
	{
		$fvSQL = 'SELECT *, count(*) AS mycount FROM myworld GROUP BY myworld_type ORDER BY myworld_type';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsGetStoreOtherCnt()
	{
		$fsSQL = 'SELECT DISTINCT(storage_id) FROM storage WHERE storage_id > 0';
		$q = $this->_fsManagerDBW->query($fsSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsGetStoreOther()
	{
		$fsSQL = 'SELECT storage_id, SUM(storage_itemCount) AS stcnt FROM storage WHERE storage_id > 0 GROUP BY storage_id';
		$q = $this->_fsManagerDBW->query($fsSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $result)
		{
			$storage[$result['storage_id']]['cnt'] = $result['stcnt'];
			$fsSQL2 = "SELECT * FROM storage WHERE storage_id = '" . $result['storage_id'] . "'";
			$r = $this->_fsManagerDBW->query($fsSQL2);
			$results1 = $r->fetchAll(SQLITE_ASSOC);
			foreach ($results1 as $res)
			{
				$storage[$result['storage_id']][] = $res;
			}
		}
		return $storage;
	}

	private function _fsUpdateWorldDB()
	{
		AddLog2('FarmStats is updating the Farmville World');
		$this->_fsManagerDBW->queryExec('DELETE FROM myworld');
		$this->_fsManagerDBW->queryExec('BEGIN;');
		foreach(GetObjects() as $world)
		{
			$result = Units_GetUnitByName($world['itemName']);
			//Get Recipes that are available;
			if(isset($world['recipeQueue']))
			{
				$this->recipe[$world['id']] = $world['recipeQueue'];
				$this->recipe[$world['id']]['craftLevel'] = $world['craftLevel'];
			}
			if(isset($world['contents']))
			{
				$this->building[$world['id']] = array('itemName' => $world['itemName'], 'contents' => $world['contents']);
			}
			$world['message'] = str_replace("'", "''", @$world['message']);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$result['sizeX'] = (@$result['sizeX'] < 1) ? 1 : @$result['sizeX'];
			$result['sizeY'] = (@$result['sizeY'] < 1) ? 1 : @$result['sizeY'];
			$mfvSQL = "INSERT INTO myworld(myworld_id, myworld_className, myworld_itemName, myworld_itemRealName, myworld_iconURL,  myworld_itemCode, myworld_direction, myworld_sizex, myworld_sizey, myworld_posx, myworld_posy, myworld_type, myworld_plantTime, myworld_state, myworld_canWander, myworld_usesAltGraphic, myworld_giftSenderId, myworld_explicitType, myworld_expansionLevel, myworld_message)
							values(" . $world['id'] . ",'" . @$world['className'] . "', '" . $world['itemName'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . @$result['code'] . "', '" . @$world['direction'] . "', '" . @$result['sizeX'] . "', '" . @$result['sizeY'] . "', '" . @$world['position']['x'] . "','" . @$world['position']['y'] . "','" . @$result['type'] . "','" . @$world['plantTime'] . "','" . @$world['state'] . "','" . @$world['canWander'] . "','" . @$world['usesAltGraphic'] . "','" . @$world['giftSenderId'] . "','" . @$world['_explicitType'] . "',' " . @$world['expansionLevel'] . "','" . @$world['message'] . "');";
			$this->_fsManagerDBW->queryExec($mfvSQL, $error);
			if (!empty($error)) { AddLog2($error); }
		}
		$this->_fsManagerDBW->queryExec('COMMIT;');
		$this->_fsManagerDBW->queryExec('DELETE FROM storage');
		$this->_fsManagerDBW->queryExec('BEGIN;');
		//Get Storage Box Items
		$this->stBox = unserialize(fBGetDataStore('instorage'));
		foreach($this->stBox  as $key => $sbox)
		{
			if (strpos($key, ':')) $key=substr($key,0,strpos($key, ':'));
			$result = Units_GetUnitByCode($key);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL,  storage_itemCode, storage_type, storage_itemCount, storage_id)
						values('" . @$result['className'] . "', '" . $result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $sbox. "','-2');";
			$this->_fsManagerDBW->queryExec($gfvSQL);
		}
		//Get Giftbox Items
		$this->giftBox = unserialize(fBGetDataStore('ingiftbox'));
		foreach($this->giftBox AS $key => $gbox)
		{
			if (strpos($key, ':')) $key=substr($key,0,strpos($key, ':'));
			$result = Units_GetUnitByCode($key);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id)
							values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $gbox . "','-1');";
			$this->_fsManagerDBW->queryExec($gfvSQL);

		}
		//Get Consumable Box Items
		$this->conBox = unserialize(fBGetDataStore('inconbox'));
		foreach($this->conBox AS $key => $cbox)
		{
			$result = Units_GetUnitByCode($key);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id)
					values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" .@$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $cbox . "','-6');";
			$this->_fsManagerDBW->queryExec($gfvSQL);

		}
		//Get Other Stored Items
		foreach($this->building AS $key => $items)
		{
			foreach($items['contents'] as $cont)
			{
				$uinfo = $this->fsGetUnits($cont['itemCode']);
				$uinfo['realname'] = str_replace("'", "''", $uinfo['realname']);
				$bfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id, storage_itemExtra)
					values('" . @$uinfo['className'] . "', '" . @$uinfo['name'] . "', '" . @$uinfo['realname'] . "', '" .@$uinfo['iconurl'] . "', '" . $cont['itemCode'] . "', '" . @$uinfo['type'] . "', '" . @$cont['numItem'] . "', $key, '" . @$cont['numParts'] . "');";
				$this->_fsManagerDBW->queryExec($bfvSQL);
			}
		}
		$this->_fsManagerDBW->queryExec('COMMIT;');
		//Database Cleanup Routine
		if(!isset($this->settings['lastclean']) || (time() - (int)$this->settings['lastclean'] >= 21600))
		{

			AddLog2('FarmStats is doing DB Cleanup');
			$this->_fsManagerDBM->query('vacuum');
			$this->_fsManagerDBW->query('vacuum');
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('lastclean','" . time() . "')";
			$this->_fsManagerDBM->queryExec($fvSQL);
		}
		$this->_fsGetEmptySpots();
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('freespace','" . $this->freespace . "')";
		$this->_fsManagerDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('goodState','" . serialize($this->fmGoodState) . "')";
		$this->_fsManagerDBM->queryExec($fvSQL);

		AddLog2('FarmStats has finished updating the Farmville World');
	}

	function fsItemCounts($itype = '')
	{
		$fvSQL = 'SELECT *, count(*) AS mycount FROM myworld ';
		if(!empty($itype))
		{
			$fvSQL = $fvSQL . "WHERE myworld_type = '" . $itype . "'";
		}
		if ($itype != 'seed')
		{
			$fvSQL = $fvSQL . 'GROUP BY myworld_itemCode ORDER BY myworld_itemRealName';
		}

		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsMessages()
	{
		$fvSQL = "SELECT * FROM myworld WHERE myworld_type = 'messageSign'";

		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	function fsGetWorldbyType($itype = '')
	{
		$fvSQL = 'SELECT *, COUNT(*) as mycount FROM myworld ';
		$fvSQL .= "WHERE myworld_type = '" . $itype . "'";
		$fvSQL .= ' GROUP BY myworld_itemCode ORDER BY myworld_itemRealName';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsGetWorldCntbyType($itype = '')
	{
		$fvSQL = 'SELECT COUNT(*) as wcnt FROM myworld ';
		$fvSQL .= "WHERE myworld_type = '" . $itype . "'";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}

	function fsGetWorldCntbyClass($iclass = '')
	{
		$fvSQL = 'SELECT COUNT(*) as wcnt FROM myworld ';
		$fvSQL .= "WHERE myworld_className = '" . $iclass . "'";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}

	function fsGetWorldbyClass($iclass = '')
	{
		$fvSQL = 'SELECT *, COUNT(*) as mycount FROM myworld ';
		$fvSQL .= "WHERE myworld_className = '" . $iclass . "'";
		$fvSQL .= ' GROUP BY myworld_itemCode ORDER BY myworld_itemRealName';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsStorageStats()
	{
		$fvSQL = 'SELECT SUM(storage_itemCount) AS cnt, storage_id FROM storage ';
		$fvSQL .= 'GROUP BY storage_id';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results1 = $q->fetchAll(SQLITE_ASSOC);
		foreach($results1 as $result)
		{
			$data[$result['storage_id']]['cnt'] = $result['cnt'];
			$ids[] = $result['storage_id'];
		}
		$data[-1]['buildingname'] = 'Giftbox';
		$data[-6]['buildingname'] = 'Consumable Box';
		$data[-1]['cnt'] = ($data[-1]['cnt'] < 1) ? 0 : $data[-1]['cnt'];
		$data[-6]['cnt'] = ($data[-6]['cnt'] < 1) ? 0 : $data[-6]['cnt'];
		$ids = implode(',', $ids);
		$fwSQL = 'SELECT * FROM myworld ';
		$fwSQL .= "WHERE myworld_id IN($ids) OR myworld_className='StorageBuilding' OR myworld_className='InventoryCellar'";
		$r = $this->_fsManagerDBW->query($fwSQL);
		$results = $r->fetchAll(SQLITE_ASSOC);
		foreach($results AS $result)
		{
			$item = $this->fsGetUnits($result['myworld_itemCode']);
			$result['myworld_id'] = ($result['myworld_className'] == 'StorageBuilding' || $result['myworld_className'] == 'InventoryCellar') ? -2 : $result['myworld_id'];
			$result['myworld_itemRealName'] = ($result['myworld_className'] == 'StorageBuilding' || $result['myworld_className'] == 'InventoryCellar') ? 'Storage Building' : $result['myworld_itemRealName'];
			$data[$result['myworld_id']]['buildingname'] = $result['myworld_itemRealName'];
			if(isset($result['myworld_expansionLevel']) && $result['myworld_expansionLevel'] > 1)
			{
				$uinfo = unserialize($item['units_upgrade']);
				$level = intval($result['myworld_expansionLevel']);
				$item['units_capacity'] = $uinfo[$level];

			}
			$data[$result['myworld_id']]['storagecap'] = $item['units_capacity'];
		}
		$data[-1]['buildingname'] = 'Giftbox';
		$data[-1]['storagecap'] = 200;
		$data[-6]['buildingname'] = 'Consumable Box';
		$data[-6]['storagecap'] = 200;
		return $data;
	}

	function fsStorageCounts()
	{
		$fvSQL = 'SELECT * FROM storage ';
		$fvSQL .= 'ORDER BY storage_id, storage_itemRealName';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);

		return $results;
	}

	function fsGetStorageCntByID($id)
	{
		$fvSQL = 'SELECT SUM(storage_itemCount) AS sCnt FROM storage ';
		$fvSQL .= "WHERE storage_id='$id'";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);

		return $results[0];
	}

	function fsGetStorageByID($id)
	{
		$fvSQL = 'SELECT * FROM storage ';
		$fvSQL .= 'WHERE storage_id=' . $id;
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fsFMCounts($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmarket ';
		if(!empty($itype))
		{
			$fvSQL .= "WHERE fmarket_itemCode = '$itype' ";
		}
		$fvSQL .= 'ORDER BY fmarket_itemRealName';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	function fsFMBCounts($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmbushels ';
		if(!empty($itype))
		{
			$fvSQL .= "WHERE fmbushels_itemCode = '$itype' ";
		}
		$fvSQL .= 'ORDER BY fmbushels_itemRealName';
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	function fsGetUnits($code)
	{
		$results = Units_GetUnitByCode($code);
		return $results;
	}
	function fsGetWorldbyID($id)
	{
		$fvSQL = "SELECT * FROM myworld WHERE myworld_id='" . $id . "'";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}
	function fsGetWorldCnt()
	{
		$fvSQL = "SELECT COUNT(*) AS wCnt FROM myworld";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}

	function fsDoSettings()
	{
		$fsneighbors = (isset($_GET['neighbortrack'])) ? 1 : 0;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ntrack','$fsneighbors')";
		$this->_fsManagerDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ntracktime','')";
		$this->_fsManagerDBM->queryExec($fvSQL);
	}

	private function _fsGetEmptySpots()
	{
		$maxX = $this->settings['wsizeX'];
		$maxY = $this->settings['wsizeY'];
		$obcount = 0;
		$objects = array();
		$fvSQL = "SELECT * FROM myworld ORDER BY myworld_posx, myworld_posy";
		$q = $this->_fsManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach($results as $item)
		{
			//$item2 = Units_GetUnitByName($item['myworld_itemName']);
			$item['myworld_sizex'] = !isset($item['myworld_sizex']) ? 1 : $item['myworld_sizex'];
			$item['myworld_sizey'] = !isset($item['myworld_sizey']) ? 1 : $item['myworld_sizey'];
			if (!isset($objects[$item['myworld_posx']][$item['myworld_posy']])) {
				$obcount = $obcount + ($item['myworld_sizex'] * $item['myworld_sizey']);
				$objects[$item['myworld_posx']][$item['myworld_posy']] = '';
			}

		}
		unset($objects);
		$newcount = ($maxX * $maxY) - $obcount;
		$this->freespace = $newcount;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name, settings_value) values('freespace', '" . $this->freespace . "') ";
		$this->_fsManagerDBM->queryExec($fvSQL);
	}

	private function _fsUpdateSettings()
	{
		$uname = str_replace("'", "''", $this->uname);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('uname','" . $uname . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('level','" . $this->level . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('xp','" . $this->xp . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('gold','" . $this->gold . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('coin','" . $this->coin . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeX','" . $this->wsizeX . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeY','" . $this->wsizeY . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('version','" . FarmStats_version . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wither','" . $this->wither . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('neighbors','" . serialize($this->neighbors) . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('achieve','" . serialize($this->achieve) . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('mastery','" . serialize($this->mastery) . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('achievecnt','" . serialize($this->achievecnt) . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('masterycnt','" . serialize($this->masterycnt) . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('tileset','" . $this->tileset . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('fuel','" . $this->fuel . "');";
		$this->_fsManagerDBM->queryExec($fvSQL);

	}

	function fsGetGoods()
	{
		$fwSQL = "SELECT myworld_itemCode FROM myworld WHERE myworld_className = 'CraftingCottageBuilding'";
		$q = $this->_fsManagerDBW->query($fwSQL);
		$bresults = $q->fetchAll(SQLITE_ASSOC);
		foreach($bresults AS $bresult)
		{
			$fuSQL = "SELECT units_craftskill FROM units WHERE units_code = '" . $bresult['myworld_itemCode'] . "'";
			$r = $this->_fsManagerDBU->query($fuSQL);
			$csresults = $r->fetchAll(SQLITE_ASSOC);
			$craftskill[] = $csresults[0]['units_craftskill'];
		}
		$cs = implode("','", $craftskill);
		AddLog2('Craftskills: ' . $cs);
		$fuSQL = "SELECT * FROM units WHERE units_subtype IN('$cs')";
		$q = $this->_fsManagerDBU->query($fuSQL);
		$iresults = $q->fetchAll(SQLITE_ASSOC);
		return $iresults;
	}

	function fsGetFeature()
	{
		//AddLog2(print_r($this->featurecred,true));
		$fcred = unserialize(fBGetDataStore('featurecred'));
		$featcred = array();
		if (empty($fcred)) return;
		foreach ($fcred as $key=>$feature)
		{
			$uinfo = Units_GetUnitByName(strtolower($key));
			if ($uinfo['type'] == 'building') $featcred['building'][$uinfo['realname']] = $feature;
			if ($uinfo['type'] != 'building' && !empty($uinfo)) $featcred['decoration'][$uinfo['realname']] = $feature;
			if (empty($uinfo)) $featcred['building'][$key] = $feature;
		}
		//AddLog2(print_r($featcred,true));
		return $featcred;
	}
}

?>
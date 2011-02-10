<?php
class fvManager
{
	var $userId, $flashRevision, $_token, $_sequence, $_flashSessionKey;
	var $xp, $energy, $error, $fvAll, $haveWorld, $units, $_fvManagerDB, $building;
	var $level, $imageRevision, $gold, $coin, $settings, $freespace, $recipe;
	var $fmCraft, $fmBushels, $relVersion, $giftBox, $conBox, $availGoods, $fmGoodState;
	var $availBushels, $bMaxCap, $bDailyBags, $bDailyPurch, $cDailyBags, $stBox;
	var $cDailyPurch, $bConsumed, $cConsumed, $bBagsConsumed, $cBagsConsumed, $myBushels;
	var $botspeed, $zErrCGen, $zErrCBushels, $actBushel, $zErrCGoods;
	var $keepbushel;
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
		$this->zErrCGoods = array(
		0 => 'OK', 1 => 'User not Found', 2 => 'User not Friend', 3 => 'Had no Room',
		4 => 'User Purchase Limit Hit', 5 => 'Global Purchase Limit Hit', 6 => 'Good not Found',
		7 => 'Insufficient Quantity', 8 => 'Good Bad Price', 9 => 'Offer Bad Price',
		10 => 'Insufficents Funds', 11 => 'Other Error', 12 => 'Out of Sync');

		//$test = print_r($amf2->_bodys[0]->_value['data'][0]['data'], true);
		//file_put_contents('plugins/fvManager/player.txt', $test);

			$this->haveWorld = true;
			$this->giftBox = unserialize(fBGetDataStore('giftbox'));
			$this->conBox = unserialize(fBGetDataStore('consumebox'));
			$this->fmCraft = unserialize(fBGetDataStore('craftbox'));
			$this->stBox = unserialize(fBGetDataStore('storagebox'));
			$craftstate = unserialize(fBGetDataStore('craftstate'));
			$this->fmBushels = $craftstate['craftingItems'];
			$this->fmGoodState = $craftstate['craftingSkillState']['recipeStates'];
			$this->availBushels = unserialize(fBGetDataStore('availbushels'));
			$this->availGoods = unserialize(fBGetDataStore('availgoods'));
			$this->bMaxCap = $craftstate['maxCapacity'];
			$this->bDailyBags = $craftstate['shoppingState']['maxDailyBags'];
			$this->bDailyPurch = $craftstate['shoppingState']['maxDailyPurchases'];
			$this->cDailyBags = $craftstate['shoppingState']['maxDailyCraftingBags'];
			$this->cDailyPurch = $craftstate['shoppingState']['maxDailyCraftingPurchases'];
			$this->bConsumed = $craftstate['shoppingState']['consumedBags'];
			$this->cConsumed = $craftstate['shoppingState']['consumedCraftingBags'];
			foreach(@$this->bConsumed AS $bc)
			{
				$bc['uid'] = number_format($bc['uid'],0,'','');
				$bconsume[$bc['uid']] = $bc['bags'];
			}
			$this->bConsumed = @$bconsume;
			foreach(@$this->availBushels AS $aBush)
			{
				$aBush['uid'] = number_format($aBush['uid'],0,'','');
				$newaBush[] = $aBush;
			}
			$this->availBushels = @$newaBush;
			foreach(@$this->availGoods AS $aGoods)
			{
				$aGoods['uid'] = number_format($aGoods['uid'],0,'','');
				$newaGoods[] = $aGoods;
			}
			$this->availGoods = @$newaGoods;
			$this->bBagsConsumed = (empty($this->bConsumed)) ? 0 : array_sum($this->bConsumed);
			foreach(@$this->cConsumed AS $cc)
			{
				$cc['uid'] = number_format($cc['uid'],0,'','');
				$cconsume[$cc['uid']] = $cc['bags'];
			}
			$this->cConsumed = @$cconsume;
			$this->cBagsConsumed = (empty($this->cConsumed)) ? 0 : array_sum($this->cConsumed);
			foreach(@$this->fmBushels AS $av)
			{
				$bushels[$av['itemCode']] = $av['quantity'];
			}
			$this->myBushels = array_sum(@$bushels);

	}

	private function _fvManager_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = $this->_fvManagerDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "');";
			$fvSQL .= "INSERT INTO settings(settings_name,settings_value) values('level','" . $this->level . "');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asgoods','0');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asbushels','0');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ascbushels','0');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asgoods_keep','0');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asbushels_keep','0');";
			$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('debug','0');";
			$this->_fvManagerDBM->queryExec($fvSQL);



		}
		$q = $this->_fvManagerDBU->query('SELECT * FROM units LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Units Table');
			$fvSQL = 'CREATE TABLE
              		units (
					units_name CHAR(50) PRIMARY KEY,
					units_type CHAR(50),
                    units_code CHAR(4),
					units_buyable INTEGER DEFAULT 0,
					units_class CHAR(250),
					units_iconurl CHAR(250),
					units_subtype CHAR(50),
					units_requiredLevel INTEGER,
					units_sizeX INTEGER,
					units_sizeY INTEGER,
                    units_canrotate INTEGER DEFAULT 0,
                    units_canplace INTEGER DEFAULT 0,
                    units_canstore INTEGER DEFAULT 0,
                    units_capacity INTEGER,
					units_realname CHAR(100),
                    units_iphoneonly INTEGER DEFAULT 0,
                    units_limit INTEGER,
                    units_market CHAR(5),
					units_cash INTEGER,
                    units_craftskill CHAR(50),
                    units_upgrade TEXT,
                    units_ingredients TEXT,
                    units_limitedstart TIMESTAMP,
                    units_limitedend TIMESTAMP,
                    units_finishedName CHAR(100),
                    units_cost INTEGER
			)';
			$this->_fvManagerDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_class ON units(units_class)';
			$this->_fvManagerDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_name ON units(units_name)';
			$this->_fvManagerDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_code ON units(units_code)';
			$this->_fvManagerDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_type ON units(units_type)';
			$this->_fvManagerDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_buyable ON units(units_buyable)';
			$this->_fvManagerDBU->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBW->query('SELECT * FROM myworld LIMIT 1');
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
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_className ON myworld(myworld_className)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_itemName ON myworld(myworld_itemName)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_type ON myworld(myworld_type)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX myworld_id ON myworld(myworld_id)';
			$this->_fvManagerDBW->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBW->query('SELECT * FROM storage LIMIT 1');
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
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_className ON storage(storage_className)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_itemName ON storage(storage_itemName)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX storage_type ON storage(storage_type)';
			$this->_fvManagerDBW->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBW->query('SELECT * FROM fmarket LIMIT 1');
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
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_className ON fmarket(fmarket_className)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_itemName ON fmarket(fmarket_itemName)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmarket_type ON fmarket(fmarket_type)';
			$this->_fvManagerDBW->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBW->query('SELECT * FROM fmbushels LIMIT 1');
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
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_className ON fmbushels(fmbushels_className)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_itemName ON fmbushels(fmbushels_itemName)';
			$this->_fvManagerDBW->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX fmbushels_type ON fmbushels(fmbushels_type)';
			$this->_fvManagerDBW->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBM->query('SELECT * FROM work LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Work Table');
			$fvSQL = 'CREATE TABLE
              			work (
						work_id INTEGER PRIMARY KEY,
               			work_itemCode CHAR(4),
               			work_action CHAR(10),
               			work_action2 CHAR(10),
               			work_result INTEGER,
               			work_quantity INTEGER,
               			work_posx INTEGER,
               			work_posy INTEGER
			)';
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX work_itemCode ON work(work_itemCode)';
			$this->_fvManagerDBM->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBM->query('SELECT * FROM locations LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Empty Locations Table');
			$fvSQL = 'CREATE TABLE
              			locations (
                		loc_id INTEGER PRIMARY KEY,
                		loc_x INTEGER,
                		loc_y INTEGER
			)';
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX loc_x ON locations(loc_x)';
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX loc_y ON locations(loc_y)';
			$this->_fvManagerDBM->queryExec($fvSQL);
		}
		$q = $this->_fvManagerDBM->query('SELECT * FROM locrect LIMIT 1');
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
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX locrect_sizex ON locrect(locrect_sizex)';
			$this->_fvManagerDBM->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX locrect_sizey ON locrect(locrect_sizey)';
			$this->_fvManagerDBM->queryExec($fvSQL);
		}
	}

	//Function fvManager class initializer
	function fvManager($inittype = '')
	{
		list($this->level, $this->gold, $this->coin, $this->wsizeX, $this->wsizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $this->flashRevision) = explode(';', fBGetDataStore('playerinfo'));
		$this->userId = $_SESSION['userId'];
		$this->flashRevision = $_SESSION['flashRevision'];
		$this->xp = $xp;
		$this->energy = $energy;
		$this->error = '';
		$this->haveWorld = false;

		if(!is_numeric($this->userId))
		{
			$this->error = "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fvManagerDBM = new SQLiteDatabase(fvManager_Path . PluginF(fvManager_Main));
		$this->_fvManagerDBW = new SQLiteDatabase(fvManager_Path . PluginF(fvManager_World));
		$this->_fvManagerDBU = new SQLiteDatabase(fvManager_Path . PluginF(fvManager_Units));
		if(!$this->_fvManagerDBM || !$this->_fvManagerDBW || !$this->_fvManagerDBU)
		{
			$this->error = 'fvManager - Database Error';
			return;
		}
		$this->_fvManagerDBM->queryExec('PRAGMA cache_size=20000');
		$this->_fvManagerDBM->queryExec('PRAGMA synchronous=OFF');
		$this->_fvManagerDBM->queryExec('PRAGMA count_changes=OFF');
		$this->_fvManagerDBM->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvManagerDBM->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fvManagerDBW->queryExec('PRAGMA cache_size=20000');
		$this->_fvManagerDBW->queryExec('PRAGMA synchronous=OFF');
		$this->_fvManagerDBW->queryExec('PRAGMA count_changes=OFF');
		$this->_fvManagerDBW->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvManagerDBW->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fvManagerDBU->queryExec('PRAGMA cache_size=20000');
		$this->_fvManagerDBU->queryExec('PRAGMA synchronous=OFF');
		$this->_fvManagerDBU->queryExec('PRAGMA count_changes=OFF');
		$this->_fvManagerDBU->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvManagerDBU->queryExec('PRAGMA temp_store=MEMORY');
		//Get Settings
		$this->settings = $this->fvGetSettings();
		if($inittype == 'formload')
		{
			if(empty($this->settings))
			{
				$this->error = 'Please allow fvManager to run a cycle';
			}
			return;
		}
		//Version Upgrade
		if($this->settings !== false && (!isset($this->settings['version']) || $this->settings['version'] != fvManager_version))
		{
			AddLog2('fvManager preparing to upgrade');
			$fwSQL = "DROP TABLE myworld;";
			$q = $this->_fvManagerDBW->query($fwSQL);
			$fgSQL = "DROP TABLE giftbox;";
			$q = $this->_fvManagerDBW->query($fgSQL);
			$fuSQL = "DROP TABLE units;";
			$q = $this->_fvManagerDBU->query($fuSQL);
			$fgSQL = "DROP TABLE fmarket;";
			$q = $this->_fvManagerDBW->query($fgSQL);
			$fbSQL = "DROP TABLE fmbushels;";
			$q = $this->_fvManagerDBW->query($fbSQL);
			$fbSQL = "DROP TABLE work;";
			$q = $this->_fvManagerDBM->query($fbSQL);
			$fbSQL = "DROP TABLE storage;";
			$q = $this->_fvManagerDBW->query($fbSQL);
			$this->_fvManager_checkDB();//Database doesn't exist, create
			$this->_fvUpdateSettings();//Insert initial settings
			$this->_fvUpdateUnits();//Update the Units file
			AddLog2('fvManager upgrade finished');
		}
		//Load the world from Z*
		$this->_refreshWorld();
		if($this->haveWorld === true)
		{
			if($this->settings === false)
			{
				$this->_fvManager_checkDB();//Database doesn't exist, create
				$this->_fvUpdateSettings();//Insert initial settings
				$this->_fvImageRev();//Update the Image Revision
				$this->_fvUpdateUnits();//Update the Units file
				$this->_fvUpdateWorldDB();//Update the World
				$this->_findEmpty();
				//$this->_fvCreateMap();//Create World Map
				//$this->_fvGetEmptySpots();//Get Empty Spots
				$this->error = 'Please allow fvManager to run a cycle to update all settings';
				return;

			}
			if($this->settings['flashRevision'] != $this->flashRevision)
			{
				$this->_fvImageRev();//Update the Image Revision
				$this->_fvUpdateUnits();//Update the Units file
				$this->settings = $this->fvGetSettings();
			}
			if($this->settings['unitversion'] != $this->flashRevision)
			{
				$this->_fvUpdateUnits();//Update the Units file
			}
			$this->_fvUpdateWorldDB();//Update the World
			$this->_findEmpty();
			$this->_fvUpdateSettings();//Update the settings
			$this->_fvUpdateImages();//Update Thumbnails
				
		}
	}

	function fvGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		$q = $this->_fvManagerDBM->query($fvSQL);
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

	function fvGetStoreBuildings()
	{
		$fvSQL = "SELECT * FROM myworld WHERE myworld_className = 'StorageBuilding' OR myworld_className = 'InventoryCellar'";
		$fvSQL .= "ORDER BY myworld_itemRealName";
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fvClassCounts()
	{
		$fvSQL = 'SELECT *, count(*) AS mycount FROM myworld GROUP BY myworld_type ORDER BY myworld_type';
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	//Needs fixed for new units format
	function fvBuyableUnits($itype = '')
	{
		$fvSQL = "SELECT * FROM units WHERE units_buyable=1 AND ";
		if(!empty($itype))
		{
			$fvSQL .= "units_type = '" . $itype . "' AND ";
		}
		$fvSQL .= "units_requiredLevel <= " . $this->settings['level'] . " AND ";
		$fvSQL .= "(units_limitedend >= '" . time() . "' OR units_limitedend = '') AND ";
		$fvSQL .= "(units_limitedstart <= '" . time() . "' OR units_limitedstart = '') AND ";
		$fvSQL .= "(units_cash > 0 OR units_cost > 0) AND units_iphoneonly <> 1 AND ";
		$fvSQL .= "units_class <> 'BuildingPart' AND units_type <> 'fuel' AND units_code <> 'XX' AND ";
		$fvSQL .= "units_class <> 'BeehiveItem' AND units_canplace = 1";
		if(!empty($itype))
		{
			$fvSQL .= ' ORDER BY units_realname';
		}
		else
		{
			$fvSQL .= ' GROUP BY units_type ORDER BY units_type';
		}

		$q = $this->_fvManagerDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach($results as $key => $bunits)
		{
			if(empty($bunits['units_market']) || $bunits['units_market'] != 'cash')
			{
				$results[$key]['units_market'] = 'coin';
			}
			else
			{
				$results[$key]['units_cost'] = $results[$key]['units_cash'];
			}
			$results[$key]['units_sizeX'] = ($bunits['units_sizeX'] < 1) ? 1 : $bunits['units_sizeX'];
			$results[$key]['units_sizeY'] = ($bunits['units_sizeY'] < 1) ? 1 : $bunits['units_sizeY'];
		}
		if($itype <> '')
		{
			foreach($results as $key => $result)
			{
				if(intval($result['units_limit']) > 0)
				{
					$wSQL = "SELECT count(*) as mcount from myworld WHERE myworld_itemCode = '" . $result['units_code'] . "' OR myworld_itemName LIKE '%" . $result['units_name'] . "%'";
					$r = $this->_fvManagerDBW->query($wSQL);
					$mcount = $r->fetchAll(SQLITE_ASSOC);
					if($mcount[0]['mcount'] >= $result['units_limit'])
					{
						unset($results[$key]);
						/*foreach($results AS $nkey => $nresult)
						 {
							if($nresult['units_class'] == $result['units_class'] && !empty($result['units_class']))
							{
							unset($results[$nkey]);
							}
							}*/
					}
				}
			}
		}
		return $results;
	}

	private function _fvUpdateWorldDB()
	{
		AddLog2('fvManager is updating the Farmville World');
		if ($this->haveWorld === false) { return; }
		$this->_fvManagerDBW->queryExec('DELETE FROM myworld');
		$this->_fvManagerDBW->queryExec('BEGIN TRANSACTION;');
		foreach(GetObjects() as $world)
		{
			$result = Units_GetUnitByName($world['itemName']);
			if (@$result['type'] == 'seed')
			{
				$kresult = Units_GetUnitByName($world['itemName'] . 'bushel');
				if (isset($kresult['code']))
				{
					$this->keepbushel[$kresult['code']] = 1;
				}
			}
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
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$result['sizeX'] = (@$result['sizeX'] < 1) ? 1 : @$result['sizeX'];
			$result['sizeY'] = (@$result['sizeY'] < 1) ? 1 : @$result['sizeY'];
			$mfvSQL = "INSERT INTO myworld(myworld_id, myworld_className, myworld_itemName, myworld_itemRealName, myworld_iconURL,  myworld_itemCode, myworld_direction, myworld_sizex, myworld_sizey, myworld_posx, myworld_posy, myworld_type, myworld_plantTime, myworld_state, myworld_canWander, myworld_usesAltGraphic, myworld_giftSenderId, myworld_explicitType, myworld_expansionLevel)
							values(" . $world['id'] . ",'" . @$world['className'] . "', '" . @$world['itemName'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . @$result['code'] . "', '" . @$world['direction'] . "', '" . @$result['sizeX'] . "', '" . @$result['sizeY'] . "', '" . $world['position']['x'] . "','" . $world['position']['y'] . "','" . @$result['type'] . "','" . @$world['plantTime'] . "','" . @$world['state'] . "','" . @$world['canWander'] . "','" . @$world['usesAltGraphic'] . "','" . @$world['giftSenderId'] . "','" . @$world['_explicitType'] . "',' " . @$world['expansionLevel'] . "');";
			$this->_fvManagerDBW->queryExec($mfvSQL, $error);
			if (!empty($error)) { AddLog2($error); }
		}
		$this->_fvManagerDBW->queryExec('COMMIT TRANSACTION;');
		save_array($this->recipe, 'recipe.txt');
		$this->_fvManagerDBW->queryExec('DELETE FROM storage');
		$this->_fvManagerDBW->queryExec('BEGIN;');
		//Get Storage Box Items
		if ($this->settings['debug'] == 1) AddLog2('Updating Storage Box');
		foreach($this->stBox  as $key => $sbox)
		{
			$cl = explode(':', $key);
			if (!empty($cl[0])) {
				$result = Units_GetUnitByCode($cl[0]);
			} else {
				$result = Units_GetUnitByCode($key);
			}
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL,  storage_itemCode, storage_type, storage_itemCount, storage_id)
						values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $sbox[0] . "','-2');";
			$this->_fvManagerDBW->queryExec($gfvSQL);
		}
		//Get Giftbox Items
		if ($this->settings['debug'] == 1) AddLog2('Updating Giftbox');
		foreach($this->giftBox AS $key => $gbox)
		{
			$result = Units_GetUnitByCode($key);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id)
							values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $gbox[0] . "','-1');";
			$this->_fvManagerDBW->queryExec($gfvSQL);

		}
		//Get Consumable Box Items
		if ($this->settings['debug'] == 1) AddLog2('Updating Consumable Box');
		foreach($this->conBox AS $key => $cbox)
		{
			$result = Units_GetUnitByCode($key);
			$result['realname'] = str_replace("'", "''", @$result['realname']);
			$gfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id)
					values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $cbox[0] . "','-6');";
			$this->_fvManagerDBW->queryExec($gfvSQL);

		}
		//Get Other Stored Items
		if ($this->settings['debug'] == 1) AddLog2('Updating Other Stored Items');
		foreach($this->building AS $key => $items)
		{
			foreach($items['contents'] as $cont)
			{
				$uinfo = Units_GetUnitByCode($cont['itemCode']);
				$uinfo['realname'] = str_replace("'", "''", @$uinfo['realname']);
				$bfvSQL = "INSERT INTO storage(storage_className, storage_itemName, storage_itemRealName, storage_iconURL, storage_itemCode, storage_type, storage_itemCount, storage_id, storage_itemExtra)
					values('" . @$uinfo['className'] . "', '" . @$uinfo['name'] . "', '" . @$uinfo['realname'] . "', '" . @$uinfo['iconurl'] . "', '" . $cont['itemCode'] . "', '" . @$uinfo['type'] . "', '" . @$cont['numItem'] . "', $key, '" . @$cont['numParts'] . "');";
				$this->_fvManagerDBW->queryExec($bfvSQL);
			}
		}
		$this->_fvManagerDBW->queryExec('COMMIT;');
		$this->_fvManagerDBW->queryExec('DELETE FROM fmarket');
		$this->_fvManagerDBW->queryExec('BEGIN;');
		if ($this->settings['debug'] == 1) AddLog2('Updating Crafts');
		foreach($this->fmCraft AS $key => $FMarket)
		{
			$cl = explode(':', $key);
			$result = Units_GetUnitByCode($cl[0]);
			$result['realname'] = $result['realname'] . ' (Level ' . $cl[1] . ')';
			$result['realname'] = str_replace("'", "''", $result['realname']);
			$gfvSQL = "INSERT INTO fmarket(fmarket_className, fmarket_itemName, fmarket_itemRealName, fmarket_iconURL,  fmarket_itemCode, fmarket_type, fmarket_itemCount)
							values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . @$result['iconurl'] . "', '" . $key . "','" . @$result['type'] . "','" . $FMarket[0] . "');";
			$this->_fvManagerDBW->queryExec($gfvSQL);

		}
		$this->_fvManagerDBW->queryExec('COMMIT;');
		$this->_fvManagerDBW->queryExec('DELETE FROM fmbushels');
		$this->_fvManagerDBW->queryExec('BEGIN;');
		if ($this->settings['debug'] == 1) AddLog2('Updating Bushels');
		foreach($this->fmBushels  as $key => $FMBushels)
		{
			$result = Units_GetUnitByCode($FMBushels['itemCode']);
			$result['realname'] = str_replace("'", "''", $result['realname']);
			$gfvSQL = "INSERT INTO fmbushels(fmbushels_className, fmbushels_itemName, fmbushels_itemRealName, fmbushels_iconURL,  fmbushels_itemCode, fmbushels_type, fmbushels_itemCount)
							values('" . @$result['className'] . "', '" . @$result['name'] . "', '" . @$result['realname'] . "', '" . $result['iconurl'] . "', '" . $FMBushels['itemCode'] . "','" . @$result['type'] . "','" . $FMBushels['quantity'] . "');";
			$this->_fvManagerDBW->queryExec($gfvSQL);
		}
		$this->_fvManagerDBW->queryExec('COMMIT;');
		//Database Cleanup Routine
		if(!isset($this->settings['lastclean']) || (time() - (int)$this->settings['lastclean'] >= 14400))
		{
			$iguser = array();
			save_array($iguser,'ignores.txt');
			AddLog2('fvManager is doing DB Cleanup');
			$this->_fvManagerDBM->query('vacuum');
			$this->_fvManagerDBU->query('vacuum');
			$this->_fvManagerDBW->query('vacuum');
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('lastclean','" . time() . "')";
			$this->_fvManagerDBM->queryExec($fvSQL);
		}
		$this->_fvCreateMap();
		$this->_fvGetEmptySpots();
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('freespace','" . $this->freespace . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('goodState','" . serialize($this->fmGoodState) . "');";
		$this->_fvManagerDBM->queryExec($fvSQL);
		AddLog2('fvManager has finished updating the Farmville World');
	}

	function fvItemCounts($itype = '')
	{
		$fvSQL = 'SELECT *, count(*) AS mycount FROM myworld ';
		if(!empty($itype))
		{
			$fvSQL = $fvSQL . "WHERE myworld_type = '" . $itype . "'";
		}
		$fvSQL = $fvSQL . 'GROUP BY myworld_itemCode ORDER BY myworld_itemRealName';
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fvStorageStats()
	{
		$fvSQL = 'SELECT SUM(storage_itemCount) AS cnt, storage_id FROM storage ';
		$fvSQL .= 'GROUP BY storage_id';
		$q = $this->_fvManagerDBW->query($fvSQL);
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
		$r = $this->_fvManagerDBW->query($fwSQL);
		$results = $r->fetchAll(SQLITE_ASSOC);
		foreach($results AS $result)
		{
			$item = $this->fvGetUnits($result['myworld_itemCode']);
			$result['myworld_id'] = ($result['myworld_className'] == 'StorageBuilding' || $result['myworld_className'] == 'InventoryCellar') ? -2 : $result['myworld_id'];
			$result['myworld_itemRealName'] = ($result['myworld_className'] == 'StorageBuilding' || $result['myworld_className'] == 'InventoryCellar') ? 'Storage Building' : $result['myworld_itemRealName'];
			$data[$result['myworld_id']]['buildingname'] = $result['myworld_itemRealName'];
			/*Temporary Removed for New Units Fix*/
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

	function fvStorageCounts()
	{
		$fvSQL = 'SELECT * FROM storage ';
		$fvSQL .= 'ORDER BY storage_id, storage_itemRealName';
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);

		return $results;
	}
	function fvFMCounts($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmarket ';
		if(!empty($itype))
		{
			$fvSQL .= "WHERE fmarket_itemCode = '$itype' ";
		}
		$fvSQL .= 'ORDER BY fmarket_itemRealName';
		$q = $this->_fvManagerDBW->query($fvSQL);
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
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}
	function fvFMBCounts2($itype = '')
	{
		$fvSQL = 'SELECT * FROM fmbushels ';
		$fvSQL .= "WHERE fmbushels_itemCode = '$itype' ";
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return @$results[0]['fmbushels_itemCount'];
	}
	function fvGetUnits($code)
	{
		$fvSQL = "SELECT * FROM units WHERE units_code='" . $code . "'";
		$q = $this->_fvManagerDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return @$results[0];
	}
	function fvGetWorldbyID($id)
	{
		$fvSQL = "SELECT * FROM myworld WHERE myworld_id='" . $id . "'";
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results[0];
	}
	private function _fvImageRev()
	{
		$this->imageRevision = $this->flashRevision;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name, settings_value) values('imageRevision', '" . $this->imageRevision . "') ";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}// end function

	function fvAddWork()
	{
		$action = $_POST['action'];
		unset($_POST['action']);
		foreach($_POST AS $key => $myitems)
		{
			if(isset($action))
			{
				if(is_numeric($myitems) && $myitems != '0')
				{
					$status = ($this->settings['aswork'] == 1) ? 2 : 1;
					$action2 = @$_POST[$key . '_radio'];
					$newkey = $key . '_lb';
					@list($posx, $posy) = explode(':', @$_POST[$newkey]);
					$fvSQL = "INSERT INTO work(work_itemCode, work_action, work_action2, work_quantity, work_result, work_posx, work_posy) ";
					$fvSQL .= "values('" . $key . "','" . $action . "','" . $action2 . "','" . $myitems . "','" . $status . "','" . $posx . "','" . $posy . "') ";
					$this->_fvManagerDBM->queryExec($fvSQL);
				}
			}

		}
	}

	function fvAddStorageWork()
	{
		$action = $_POST['action'];
		unset($_POST['action']);
		foreach($_POST AS $key=>$myitems)
		{
			$newkey = substr($key, 0, strpos($key, '_'));
			@$action2 = $_POST[$key . '_action2'];
			if(isset($action))
			{
				if(is_numeric($myitems) && $myitems != '0')
				{
					$status = ($this->settings['aswork'] == 1) ? 2 : 1;
					$fvSQL = "INSERT INTO work(work_itemCode, work_action, work_action2, work_quantity, work_result) ";
					$fvSQL .= "values('$newkey', '$action' , '$action2', $myitems, '$status') ";
					$this->_fvManagerDBM->queryExec($fvSQL);
				}
			}

		}
	}


	function fvGetWork($wkresult = "")
	{
		$fvSQL = "SELECT * FROM work WHERE work_id <> 0 ";
		if(is_numeric($wkresult))
		{
			$fvSQL = $fvSQL . " AND work_result=$wkresult OR work_result=3";
		}
		$fvSQL = $fvSQL . "ORDER by work_action";
		$q = $this->_fvManagerDBM->query($fvSQL);
		$work = $q->fetchAll(SQLITE_ASSOC);
		foreach($work as $key => $res)
		{
			if($res['work_action'] == 'fmcraft' || $res['work_action'] == 'stsell')
			{
				$info = explode(':', $res['work_itemCode']);
				$res['work_itemCode'] = $info[0];
			}
			$fvSQL = "SELECT * FROM units WHERE units_code = '" . $res['work_itemCode'] . "'";
			$q = $this->_fvManagerDBU->query($fvSQL);
			$result = $q->fetchAll(SQLITE_ASSOC);
			foreach($result as $result1)
			{
				if($res['work_action'] == 'fmcraft')
				{
					$work[$key]['work_itemRealName'] = $result1['units_realname'] . ' (Level ' . $info[1] . ')';

				}
				else
				{
					$work[$key]['work_itemRealName'] = $result1['units_realname'];
				}
				$work[$key]['work_iconURL'] = $result1['units_iconurl'];
				SWITCH($res['work_result'])
				{
					CASE 0:
						$work[$key]['work_result'] = 'Successful';
						break;
					CASE 1:
						$work[$key]['work_result'] = 'Pending';
						break;
					CASE 2:
						$work[$key]['work_result'] = 'Ready to Go';
						break;
					CASE 3:
						$work[$key]['work_result'] = 'No Empty Spaces';
						break;
				}//Switch
			}
		}
		return $work;
	}

	function fvCancelWork()
	{
		$id = $_POST['id'];
		$action = $_POST['action'];
		$fvSQL = "DELETE FROM work WHERE work_id = $id";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvCancelAllWork()
	{
		$fvSQL = "DELETE FROM work WHERE work_result <> 0";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvChangeWork()
	{
		$fvSQL = "UPDATE work SET work_result = 2 WHERE work_result = 1";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvDoSettings($post)
	{
		$fmwork = (isset($post['fmwork'])) ? 1 : 0;
		$fmdebug = (isset($post['fmdebug'])) ? 1 : 0;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('aswork','$fmwork');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('debug','$fmdebug');";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvCraftSettings($post)
	{
		$cbBushel = serialize($post['cbBushel']);
		$fmgoods = (isset($post['fmgoods'])) ? 1 : 0;
		$pkbushels = (isset($post['pkbushels'])) ? 1 : 0;
		$fmbushels = (isset($post['fmbushels'])) ? 1 : 0;
		$fmcbushels = (isset($post['fmcbushels'])) ? 1 : 0;
		$fmbbushels = (isset($post['fmbbushels'])) ? 1 : 0;
		$fmbgoods = (isset($post['fmbgoods'])) ? 1 : 0;
		$fmgoods_keep = (!empty($post['fmgoods_keep'])) ? $post['fmgoods_keep'] : 0;
		$fmbushels_keep = (!empty($post['fmbushels_keep'])) ? $post['fmbushels_keep'] : 0;
		$fmcbushels_keep = (!empty($post['fmcbushels_keep'])) ? $post['fmcbushels_keep'] : 0;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asgoods','$fmgoods');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asbushels','$fmbushels');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asgoods_keep','$fmgoods_keep');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('asbushels_keep','$fmbushels_keep');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ascbushels','$fmcbushels');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('pkbushels','$pkbushels');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('abbushels','$fmbbushels');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('abgoods','$fmbgoods');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('cbBushels','$cbBushel');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('ascbushels_keep','$fmcbushels_keep');";
		$this->_fvManagerDBM->queryExec($fvSQL, $error);
	}

	private function _fvCreateMap()
	{
		if($this->haveWorld)
		{
			$maxX = $this->wsizeX;
			//$maxX = $maxX + 3;
			$maxY = $this->wsizeY;
			//$maxY = $maxY + 3;
			$im = imagecreate($maxX, $maxY);
			$background_color = imagecolorallocate($im, 255, 255, 255);// white
			$red = imagecolorallocate($im, 255, 0, 0);// red
			$green = imagecolorallocate($im, 0, 255, 0);
			$blue = imagecolorallocate($im, 0, 0, 255);// blue
			$white = imagecolorallocate($im, 255, 255, 255);
			$black = imagecolorallocate($im, 0, 0, 0);
			$purple = ImageColorAllocate($im, 153, 51, 255);//purple
			$pink = ImageColorAllocate($im, 255, 0, 128);//pink
			$brown = ImageColorAllocate($im, 222, 184, 135);//light brown

			$fvSQL = "SELECT * FROM myworld";
			$q = $this->_fvManagerDBW->query($fvSQL);

			$result = $q->fetchAll(SQLITE_ASSOC);
			foreach($result as $item)
			{
				$found = false;
				$FV_fill = $red;
				$item['myworld_className'] = ($item['myworld_className'] = '') ? ucfirst($item['myworld_itemName']) : trim($item['myworld_className']);
				SWITCH($item['myworld_className'])
				{
					CASE 'Decoration':
						$FV_fill = $black;
						$found = true;
						break;
					CASE 'Animal':
						$FV_fill = $purple;
						$found = true;
						break;
					CASE 'Plot':
						$FV_fill = $brown;
						$found = true;
						break;
					CASE 'Tree':
						$FV_fill = $green;
						$found = true;
						break;
				}//switch
				if($item['myworld_className'] == 'Building' || $item['myworld_type'] == 'building')
				{
					$FV_fill = $pink;
					$found = true;
				}
				if($item['myworld_type'] == 'vehicle')
				{
					$FV_fill = $red;
					$found = true;
				}
				if(!$found)
				{
					$FV_fill = $blue;
				}
				$Map_PXI = $item['myworld_posx'];
				$Map_PYI = $item['myworld_posy'];
				if ($item['myworld_state'] == 'vertical' || $item['myworld_direction'] != '1')
				{
					imagefilledrectangle($im, $Map_PXI, $Map_PYI, $Map_PXI + ($item['myworld_sizey'] - 1), $Map_PYI + ($item['myworld_sizex'] - 1), $FV_fill);
				} else {
					imagefilledrectangle($im, $Map_PXI, $Map_PYI, $Map_PXI + ($item['myworld_sizex'] - 1), $Map_PYI + ($item['myworld_sizey'] - 1), $FV_fill);
				}
				//$Map_PYI = $maxY - $Map_PYI;
			}
			$fv_map_image = "plugins/fvManager/" . $this->userId . "_FarmMap.png";
			imagepng($im, $fv_map_image);
			imagedestroy($im);
		}
	}

	private function _fvGetEmptySpots()
	{
		//$this->fvCreateMap();
		$fv_map_image = "plugins/fvManager/" . $this->userId . "_FarmMap.png";
		$img = imagecreatefrompng($fv_map_image);
		$width = imagesx($img);
		$height = imagesy($img);
		$fvSQL = "DELETE FROM locations";
		$this->_fvManagerDBM->queryExec($fvSQL);
		$z = 0;
		$mfvSQL = '';
		for($x = 0; $x < $width; $x++)
		{
			for($y = 0; $y < $height; $y++)
			{
				$color_index = imagecolorat($img, $x, $y);
				$readable_colors = imagecolorsforindex($img, $color_index);
				if($readable_colors['red'] == '255' && $readable_colors['green'] == '255' && $readable_colors['blue'] == '255')
				{
					$z++;
					$mfvSQL .= "INSERT INTO locations(loc_x,loc_y) values('" . $x . "','" . $y . "'); ";
				}
			}
		}
		$this->_fvManagerDBM->queryExec('BEGIN;');
		$this->_fvManagerDBM->queryExec($mfvSQL);
		$this->_fvManagerDBM->queryExec('COMMIT;');
		$this->freespace = $z;
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name, settings_value) values('freespace', '" . $this->freespace . "') ";
		$this->_fvManagerDBM->queryExec($fvSQL);
		$this->_fvUpdateRectangles();
	}

	function fvSell($iArray)
	{
		if($iArray['work_action'] == 'sell' || $iArray['work_action'] == 'sell_plot')
		{

			$fvSQL = "SELECT * FROM myworld WHERE myworld_itemCode = '" . $iArray['work_itemCode'] . "' LIMIT " . $iArray['work_quantity'];
			$q = $this->_fvManagerDBW->query($fvSQL);
			$result = $q->fetchAll(SQLITE_ASSOC);
			$action = (!empty($iArray['work_action2'])) ? $iArray['work_action2'] : $iArray['work_action'];
			$amfcount = 0;
			$amf = '';
			$tmpArray = array();

			foreach($result as $result1)
			{
				$item['itemName'] = $result1['myworld_itemName'];
				$item['position'] = array('x' => $result1['myworld_posx'], 'y' => $result1['myworld_posy'], 'z' => 0);
				$item['id'] = $result1['myworld_id'];
				$item['className'] = $result1['myworld_className'];
				$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, $action, 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = array();
				if ($action == 'store')
				{
					$item2['target'] = -2;
					$item2['code'] = $result1['myworld_itemCode'];
					$item2['isGift'] = false;
					$item2['origin'] = 0;
					$item2['resource'] = $result1['myworld_id'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0] = $item2;
				}
				$tmpArray[$amfcount]['realname'] = $result1['myworld_itemRealName'];
				$tmpArray[$amfcount]['work_id'] = $iArray['work_id'];
				$tmpArray[$amfcount]['id'] = $result1['myworld_id'];
				$tmpArray[$amfcount]['code'] = $result1['myworld_itemCode'];
				$tmpArray[$amfcount]['action'] = $action;
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				echo print_r($amf,true);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) continue;
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						$fvSQL = "DELETE FROM myworld WHERE myworld_id = '" . $tmpArray[$key]['id'] . "'";
						$this->_fvManagerDBW->query($fvSQL);
						AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
						$fvSQL = "UPDATE work
          		      			SET work_quantity = work_quantity - 1
                    			WHERE work_id = '" . $tmpArray[$key]['work_id'] . "'";
						$this->_fvManagerDBM->queryExec($fvSQL);
					} else {
						AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
					}
				}
			}
			if ($amf != '') //Still have requests left
			{
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 !== false) {
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							$fvSQL = "DELETE FROM myworld WHERE myworld_id = '" . $tmpArray[$key]['id'] . "'";
							$this->_fvManagerDBW->query($fvSQL);
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
							$fvSQL = "UPDATE work
          		      			SET work_quantity = work_quantity - 1
                    			WHERE work_id = '" . $tmpArray[$key]['work_id'] . "'";

							$this->_fvManagerDBM->queryExec($fvSQL);
						} else {
							AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
						}
					}
				}
			}
		}
		unset($item);
		if($iArray['work_action'] == 'stsell')
		{
			$info = explode(':', $iArray['work_itemCode']);
			$iArray['work_itemCode'] = $info[0];
			$fvSQL = "SELECT * FROM storage WHERE storage_itemCode = '" . $iArray['work_itemCode'] . "'";
			$q = $this->_fvManagerDBW->query($fvSQL);
			$result = $q->fetchAll(SQLITE_ASSOC);
			$result = $result[0];
			$item['itemName'] = trim($result['storage_itemName']);
			$item['code'] = $result['storage_itemCode'];
			$uinfo = $this->fvGetUnits($item['code']);
			$item['className'] = (!empty($uinfo['units_class'])) ? trim($uinfo['units_class']) : trim(ucfirst($uinfo['units_type']));
			$id = 63000;
			$amfcount = 0;
			$amf = '';
			$tmpArray = array();
			for($x = 0; $x < $iArray['work_quantity']; $x++)
			{
				if($iArray['work_action2'] == 'place')
				{
					$winfo = $this->fvGetWorldbyID($result['storage_id']);
					$item['id'] = $id;
					$item['tempId'] = -1;
					$item['deleted'] = false;
					if ($result['storage_type'] == 'vehicle' )
					{
						$item['m_equipmentPartsCount'] = $info[1];
						$item['state'] = 'static';
						$item['direction'] = 0;
					} else {
						$item['state'] = trim($winfo['myworld_state']);
						$item['plantTime'] = time() - 82800 - 1;
						$item['direction'] = 1;
					}

					$loc = $this->_GetLocation($uinfo);
					if($loc !== false)
					{
						$item['position'] = array('x' => $loc['x'], 'y' => $loc['y'], 'z' => 0);
						$mfvSQL = "INSERT INTO myworld(myworld_id, myworld_itemRealName, myworld_className, myworld_direction, myworld_sizex, myworld_sizey, myworld_posx, myworld_posy, myworld_type)
	                        		values(" . $id . ",'Temporary Spot Holder', '" . $uinfo['units_class'] . "', '0', '" . $uinfo['units_sizeX'] . "', '" . $uinfo['units_sizeY'] . "', '" . $loc['x'] . "','" . $loc['y'] . "','" . $uinfo['units_type'] . "')";
						$this->_fvManagerDBW->queryExec($mfvSQL);
						$this->_findEmpty();
						//$this->_fvGetEmptySpots();
					}
					else
					{
						AddLog2('fvManager Error: No space available for ' . $result['storage_itemRealName']);
						continue;
					}
					unset($item['code']);

					$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'place', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isStorageWithdrawal'] = $result['storage_id'];
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['isGift'] = ($result['storage_id'] == -1) ? true : false;
				} else {
					$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, '', 'UserService.sellStoredItem');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][0] = $item;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = false;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = $result['storage_id'];
				}
				$tmpArray[$amfcount]['realname'] = $result['storage_itemRealName'];
				$tmpArray[$amfcount]['work_id'] = $iArray['work_id'];
				$tmpArray[$amfcount]['action'] = $iArray['work_action2'];
				$id++;
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) continue;
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
						$fvSQL = "UPDATE work
          		      				SET work_quantity = work_quantity - 1
                    				WHERE work_id = '" . $tmpArray[$key]['work_id'] . "'";
						$this->_fvManagerDBM->queryExec($fvSQL);
					} else {
						AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
					}
				}
			}
			if (is_array($amf) && !empty($amf)) //Still have requests left
			{
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 !== false) {
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp = $returned['errorType'];
						$err = $returned['errorData'];
						if($resp == 0)
						{
							AddLog2('[' . $key . '] Action: ' . $tmpArray[$key]['action'] . ' - ' . $tmpArray[$key]['realname'] . ' - Result: ' . $this->zErrCGen[$resp]);
							$fvSQL = "UPDATE work
          		      				SET work_quantity = work_quantity - 1
                    				WHERE work_id = '" . $tmpArray[$key]['work_id'] . "'";
							$this->_fvManagerDBM->queryExec($fvSQL);
						} else {
							AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $err);
						}
					}
				}
			}
		}
		if($iArray['work_action'] == 'fmcraft')
		{
			$fvSQL = "SELECT * FROM fmarket WHERE fmarket_itemCode = '" . $iArray['work_itemCode'] . "'";
			$q = $this->_fvManagerDBW->query($fvSQL);
			$result = $q->fetchAll(SQLITE_ASSOC);
			$result = $result[0];
			for($x = 0; $x < $iArray['work_quantity']; $x++)
			{
				$amf = CreateRequestAMF('', 'CraftingService.onUseCraftedGood');
				$amf->_bodys[0]->_value[1][0]['params'][0] = $result['fmarket_itemCode'];
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 === false) continue;
				$resp = $amf2->_bodys[0]->_value['data'][0]['errorType'];
				$err = $amf2->_bodys[0]->_value['data'][0]['errorData'];
				if($resp == 0)
				{
					AddLog2('[' . $x . '] fvManager traded ' . $result['fmarket_itemRealName'] . " - Result: " . $this->zErrCGen[$resp]);
					$fvSQL = "UPDATE work
          		        		SET work_quantity = work_quantity - 1
                      			WHERE work_itemCode = '" . $iArray['work_itemCode'] . "' AND work_action='fmcraft' AND work_quantity > 0";
					$this->_fvManagerDBM->queryExec($fvSQL);
				} else {
					AddLog2('fvManager Error: ' . $result1['fmarket_itemRealName'] . " Code: " . $resp . ' - ' . $err);
				}
			}
		}
		if($iArray['work_action'] == 'fmbushels')
		{
			$fvSQL = "SELECT * FROM fmbushels WHERE fmbushels_itemCode = '" . $iArray['work_itemCode'] . "' LIMIT " . $iArray['work_quantity'];
			$q = $this->_fvManagerDBW->query($fvSQL);
			$result = $q->fetchAll(SQLITE_ASSOC);
			$result = $result[0];
			for($x = 0; $x < $iArray['work_quantity']; $x++)
			{
				$item['direction'] = '0';
				$item['itemName'] = $result['fmbushels_itemName'];
				$item['position'] = array('x' => '0', 'y' => '0', 'z' => '0');
				$item['id'] = '63000';
				$item['deleted'] = 'false';
				$item['tempId'] = '-1';
				$item['className'] = $result['fmbushels_className'];
				$amf = CreateRequestAMF('use', 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][0]['params'][1] = $item;
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 === false) continue;
				$resp = $amf2->_bodys[0]->_value['data'][0]['errorType'];
				$err = $amf2->_bodys[0]->_value['data'][0]['errorData'];

				if($resp == 0)
				{
					AddLog2('[' . $x . '] fvManager sold ' . $result['fmbushels_itemRealName'] . " - Result: " . $this->zErrCGen[$resp]);
					$fvSQL = "UPDATE work
          		      			SET work_quantity = work_quantity - 1
                    			WHERE work_itemCode = '" . $iArray['work_itemCode'] . "' AND (work_action='fmbushels') AND work_quantity > 0";
					$this->_fvManagerDBM->queryExec($fvSQL);
				} else {
					AddLog2('fvManager Error: ' . $result1['fmbushels_itemRealName'] . " Code: " . $resp . ' - ' . $err);
				}

			}
		}
		$fvSQL = "UPDATE work
                SET work_result = 0
                WHERE work_quantity = 0";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvSellAll()
	{
		$actRecipes = unserialize($this->settings['cbBushels']);
		if (!empty($actRecipes))
		{
			foreach($actRecipes as $reccode=>$iRecipe)
			{
				$tmp = $this->fvGetUnits($reccode);
				$ingred = unserialize($tmp['units_ingredients']);
				foreach(@$ingred AS $key => $i)
				{
					$this->actBushel[$key] = $key;
				}
			}
		}

		//Trade Goods
		if($this->settings['asgoods'] == 1)
		{
			AddLog2('Auto Trading Market Goods');
			$this->_fvTradeGoods();
		}

		//Start New Recipes
		AddLog2('Auto Starting New Recipes');
		foreach (@$this->recipe as $item)
		{
			$tables = $item['craftLevel'];
			unset($item['craftLevel']);
			if (count($item) - 1  >= $tables) { break; }
			$ingred = $this->fvGetGoods();
			$actBushel = unserialize($this->settings['cbBushels']);
			$goods = unserialize($this->settings['goodState']);
			for ($x = 0; $x < $tables; $x++)
			{
				foreach ($ingred as $ing)
				{
					if (isset($actBushel[$ing['units_code']]))
					{
						$bushels = unserialize($ing['units_ingredients']);
						$buildgood = false;
						$bused = array();
						foreach ($bushels as $key=>$bushel)
						{
							$bushcnt = $this->fvFMBCounts($key);
							$bushcount = (@$bushcnt[0]['fmbushels_itemCount'] == '') ? 0 : @$bushcnt[0]['fmbushels_itemCount'];
							//echo $bushcnt[0]['fmbushels_itemCount'] . ' ' . $bushel . "\n";
							$buildgood = intval($bushcount >= intval($bushel)) ? true : false;
							If ($buildgood === false) { break; }
							$bused[$bushcnt[0]['fmbushels_id']] = $bushel;
						}
						if ($buildgood === true)
						{
							if ($x >= $tables) { break; }
							$x++;
							$amf = CreateRequestAMF('', 'CraftingService.onBeginRecipe');
							$amf->_bodys[0]->_value[1][0]['params'][0] = $ing['units_code'];
							$amf2 = $this->_fvAMFSend($amf);
							if ($amf2 === false) continue;
							$resp = $amf2->_bodys[0]->_value['data'][0]['errorType'];
							$err = $amf2->_bodys[0]->_value['data'][0]['errorData'];
							if($resp == 0)
							{
								$uitem = $this->fvGetUnits($rec['id']);
								AddLog2("Began recipe: " . $ing['units_realname'] . " result: OK");
								sleep(2);
							}
							else
							{
								AddLog2($resp . ' : ' . $err);
								continue;
							}
							//echo 'Will build ' . $ing['units_realname'] . "\n";
							foreach ($bused as $key=>$bu)
							{
								$fvSQL = "UPDATE fmbushels SET fmbushels_itemCount = fmbushels_itemCount-$bu ";
								$fvSQL .= "WHERE fmbushels_id = $key";
								$this->_fvManagerDBW->queryExec($fvSQL);
							}
						}
					}
				}
			}
		}
		//Use Market Bushels
		if(@$this->settings['asbushels'] == 1)
		{
			AddLog2('Auto Using Market Bushels');
			$this->_fvTradeBushels();
		}
		//Buy Market Bushels
		if(@$this->settings['abbushels'] == 1)
		{
			AddLog2('Auto Buying Market Bushels');
			AddLog2('Bushels Stored: ' . $this->myBushels);
			$this->_fvBuyBushels();
		}
		//Buy Goods
		AddLog2('Auto Buying Market Goods');
		if(@$this->settings['abgoods'] == 1)
		{
			AddLog2('Purchased Goods: ' . $this->cBagsConsumed);
			$this->_fvBuyGoods();
		}
		//Complete Crafts
		AddLog2('Auto Clearing Finished Crafts');
		foreach(@$this->recipe as $key=>$item)
		{
			//echo nl2br(print_r($item,true));
			foreach($item as $rec)
			{
				if(time() >= $rec['finish_ts'] && isset($rec['finish_ts']))
				{
					$amf = CreateRequestAMF('', 'CraftingService.onClaimFinishedRecipes');
					$amf->_bodys[0]->_value[1][0]['params'][0] = $rec['id'];
					$amf2 = $this->_fvAMFSend($amf);
					if ($amf2 === false) continue;
					$resp = $amf2->_bodys[0]->_value['data'][0]['errorType'];
					$err = $amf2->_bodys[0]->_value['data'][0]['errorData'];
					if($resp == 0)
					{
						$uitem = $this->fvGetUnits($rec['id']);
						AddLog2("Finished " . $uitem['units_realname'] . " result: OK");
						unset ($this->recipe[$key]);
					}
					else
					{
						AddLog2($resp . ' : ' . $err);
						continue;
					}
				}
			}
		}

		//Auto Rush Recipes
		AddLog2('Auto Rushing Recipes');
		foreach(@$this->recipe as $key=>$item)
		{
			foreach($item as $rec)
			{
				if ($rec['finish_ts'] == '') continue;
				$uitem = $this->fvGetUnits($rec['id']);
				$rtime = intval(($rec['finish_ts'] - time()) / 60);
				if ($rtime < 0) { continue; }
				$amf = CreateRequestAMF('', 'CraftingService.onRushRecipe');
				$amf->_bodys[0]->_value[1][0]['params'][0] = trim($rec['id']);
				$amf->_bodys[0]->_value[1][0]['params'][1] = $rec['start_ts'];
				$amf->_bodys[0]->_value[1][0]['params'][2] = true;
				$amf2 = $this->_fvAMFSend($amf);
				if ($amf2 === false) { continue; }
				$resp = $amf2->_bodys[0]->_value['data'][0]['errorType'];
				$err = $amf2->_bodys[0]->_value['data'][0]['errorData'];
				if($resp == 0)
				{
					AddLog2("Finished recipe " . $uitem['units_realname'] . " result: OK");
				}
				else
				{
					AddLog2($resp . ' : ' . $err);
					continue;
				}
			}
		}
		return;
	}

	function fvBuyItem($iArray)
	{
		$fvSQL = "SELECT * FROM units WHERE units_code = '" . $iArray['work_itemCode'] . "'";
		$q = $this->_fvManagerDBU->query($fvSQL);
		$result = $q->fetchAll(SQLITE_ASSOC);
		$id = 63000;
		$amfcount = 0;
		$amf = '';
		$tmpArray = array();
		foreach($result as $result1)
		{
			for($x = 0; $x < $iArray['work_quantity']; $x++)
			{
				$item = array();
				$item['itemName'] = $result1['units_name'];
				$item['className'] = (!empty($result1['units_class'])) ? $result1['units_class'] : ucfirst($result1['units_type']);
				$item['id'] = $id;
				$item['tempId'] = -1;
				//$item['state'] = 'bare';
				//$item['plantTime'] = time() - 82800 - 1;
				//$item['direction'] = 1;
				//$item['deleted'] = false;
				if(empty($iArray['work_posx']) && empty($iArray['work_posy']))
				{
					$loc = $this->_fvGetPlaceLoc($result1);
					if($loc !== false)
					{
						$item['position'] = array('z' => 0, 'x' => $loc['x'], 'y' => $loc['y']);
					}
					else
					{
						AddLog2('fvManager Error: No space available for ' . $result1['units_realname']);
						continue;
					}
					$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'place', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['isGift'] = false;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['isStorageWithdrawal'] = 0;
				}
				else
				{
					//$item['position'] = array('z' => 0, 'x' => $iArray['work_posx'], 'y' => $iArray['work_posy']);
					$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'store', 'WorldService.performAction');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['target'] = -2;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['isGift'] = false;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['resource'] = 0;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][2]['code'] = $iArray['work_itemCode'];
					$amf->_bodys[0]->_value[1][$amfcount][1]['params'][2]['origin'] = 0;
				}
				$tmpArray[$amfcount]['realname'] = $result1['units_realname'];
				$mfvSQL = "INSERT INTO myworld(myworld_id, myworld_className, myworld_direction, myworld_sizex, myworld_sizey, myworld_posx, myworld_posy, myworld_type)
	                        values(" . $id . ",'" . $result1['units_class'] . "', '1', '" . $result1['units_sizeX'] . "', '" . $result1['units_sizeY'] . "', '" . $loc['x'] . "','" . $loc['y'] . "','" . $result1['units_type'] . "')";
				$this->_fvManagerDBW->queryExec($mfvSQL, $error);
				$this->_fvCreateMap();
				$this->_fvGetEmptySpots();
				$id++;
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) { continue; }
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$resp2 = $returned['data']['responseCode'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						AddLog2('[' . $key . '] fvManager bought ' . $tmpArray[$key]['realname'] . " result: " . $this->zErrCGen[$resp]);
						$fvSQL = "UPDATE work
          		          		SET work_quantity = work_quantity - 1
                        		WHERE work_id = '" . $iArray['work_id'] . "'";
						$this->_fvManagerDBM->queryExec($fvSQL);
					}
					else
					{
						AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp . ' - ' . $this->zErrCGen[$resp]);
						continue;
					}
				}

			}
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 !== false) {
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp = $returned['errorType'];
					$resp2 = $returned['data']['responseCode'];
					$err = $returned['errorData'];
					if($resp == 0)
					{
						AddLog2('[' . $key . '] fvManager bought ' . $tmpArray[$key]['realname'] . " result: " . $this->zErrCGen[$resp]);
						$fvSQL = "UPDATE work
          		          		SET work_quantity = work_quantity - 1
                        		WHERE work_id = '" . $iArray['work_id'] . "'";
						$this->_fvManagerDBM->queryExec($fvSQL);
					}
					else
					{
						AddLog2('fvManager Error: ' . $tmpArray[$key]['realname'] . " Code: " . $resp2 . ' - ' . $this->zErrCGen[$resp]);
						continue;
					}
				}
			}
		}
		$fvSQL = "UPDATE work
                   SET work_result = 0
                   WHERE work_quantity = 0";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	private function _fvUpdateRectangles()
	{
		$fvSQL = "DELETE FROM locrect";
		$this->_fvManagerDBM->queryExec($fvSQL);
		$fvSQL = "SELECT loc_x, loc_y FROM locations ORDER BY loc_x ASC";
		$q = $this->_fvManagerDBM->query($fvSQL);
		$yloc = $q->fetchAll(SQLITE_ASSOC);
		$mfvSQL = '';
		foreach($yloc as $ytmp)
		{
			$locs[$ytmp['loc_x']][] = $ytmp['loc_y'];
		}
		for($x = 0; $x < $this->wsizeX; $x++)
		{
			if(isset($locs[$x]) && (isset($locs[$x + 1]) || isset($locs[$x - 1])))
			{
				$multi[$x] = $locs[$x];
			}
			elseif(isset($locs[$x]) && !isset($locs[$x + 1]) && !isset($locs[$x - 1]))
			{
				$single[$x] = $locs[$x];
			}
		}
		for($x = 0; $x < $this->wsizeX; $x++)
		{
			for($y = $x + 1; $y < $this->wsizeX; $y++)
			{
				if(isset($multi[$x]) && isset($multi[$y]))
				{
					$temp = array_intersect($multi[$y], $multi[$x]);
					if(count($multi[$x]) == count($temp))
					{
						if(!isset($newmulti[$x][$x]))
						{
							$newmulti[$x][$x] = $multi[$x];
						}
						$newmulti[$x][$y] = array_intersect($multi[$y], $multi[$x]);
					}
					elseif(count($multi[$x]) > count($temp))
					{
						if(!isset($newmulti[$x][$x]))
						{
							$newmulti[$x][$x] = $multi[$x];
						}
						break;
					}
				}
				else
				{
					break;
				}
			}
		}
		foreach(@$newmulti as $key => $xval)
		{
			$x1 = $key;
			foreach($xval as $key1 => $yval)
			{
				$x2 = (!isset($x2) || $key1 > $x2) ? $key1 : $x2;
				foreach($xval[$x1] as $yval1)
				{
					$y1 = (!isset($y1) || $yval1 < $y1) ? $yval1 : $y1;
					$y2 = (!isset($y2) || $yval1 > $y2) ? $yval1 : $y2;
				}
			}
			$sizex = $x2 - $x1 + 1;
			$sizey = $y2 - $y1 + 1;
			$fvSQL = "SELECT loc_x, loc_y FROM locations WHERE loc_x=$x1 AND loc_y=$y1";
			$q = $this->_fvManagerDBM->query($fvSQL);
			$chk = $q->fetchAll(SQLITE_ASSOC);
			$fvSQL = "SELECT loc_x, loc_y FROM locations WHERE loc_x=$x2 AND loc_y=$y2";
			$q = $this->_fvManagerDBM->query($fvSQL);
			$chk1 = $q->fetchAll(SQLITE_ASSOC);
			if(count($chk) == 1 && count($chk1) == 1)
			{
				$mfvSQL .= " INSERT INTO locrect(locrect_x,locrect_y,locrect_sizex,locrect_sizey) values($x1,$y1,$sizex,$sizey);";
			}
			unset($x2, $y1, $y2);
		}
		if ($mfvSQL != '') {
			$this->_fvManagerDBM->queryExec('BEGIN;');
			$this->_fvManagerDBM->queryExec($mfvSQL);
			$this->_fvManagerDBM->queryExec('COMMIT;');
		}
		if (isset($single))
		{
			foreach(@$single as $key => $sing)
			{
				if(count($sing) > 1)
				{
					sort($sing);
					$x1 = $key;
					$y1 = $sing[0];
					$x2 = $key;
					$y2 = $sing[count($sing) - 1];
					$sizex = $x2 - $x1 + 1;
					$sizey = $y2 - $y1 + 1;
					$fvSQL = "SELECT loc_x, loc_y FROM locations WHERE loc_x=$x1 AND loc_y=$y1";
					$q = $this->_fvManagerDBM->query($fvSQL);
					$chk = $q->fetchAll(SQLITE_ASSOC);
					$fvSQL = "SELECT loc_x, loc_y FROM locations WHERE loc_x=$x2 AND loc_y=$y2";
					$q = $this->_fvManagerDBM->query($fvSQL);
					$chk1 = $q->fetchAll(SQLITE_ASSOC);
					if(count($chk) == 1 && count($chk1) == 1)
					{
						$fvSQL = "INSERT INTO locrect(locrect_x,locrect_y,locrect_sizex,locrect_sizey) values($x1,$y1,$sizex,$sizey)";
						$this->_fvManagerDBM->queryExec($fvSQL);
					}
				}
			}
		}
	}
	function fvClearCompWork()
	{
		$fvSQL = "DELETE FROM work WHERE work_result = 0";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	private function _fvUpdateUnits()
	{
		$doc = file_get_contents('./farmville-xml/' . $this->flashRevision . '_items.xml');
		if (!$doc) AddLog2('Unable to get items.xml');
		$doc3 = file_get_contents('./farmville-xml/' . $this->flashRevision . '_gameSettings.xml');
		if (!$doc3) AddLog2('Unable to get gameSettings.xml');
		$doc4 = file_get_contents('./farmville-xml/' . $this->flashRevision . '_StorageConfig.xml');
		if (!$doc4) AddLog2('Unable to get StorageConfig.xml');
		$doc2 = file_get_contents('./farmville-xml/' . $this->flashRevision . '_crafting.xml');
		if (!$doc2) AddLog2('Unable to get crafting.xml');
		if(!$doc || !$doc2 || !$doc3 || !$doc4)
		{
			return;
		}
		AddLog2('fvManager is updating units');
		//$newimgs = load_array('newimages.txt');
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc3);
		$farming = $xmlDoc->getElementsByTagName('farming');
		$higherLevelXp = $farming->item(0)->getAttribute('higherLevelXp');
		$higherLevelBegin = $farming->item(0)->getAttribute('higherLevelBegin');
		$higherLevelStep = $farming->item(0)->getAttribute('higherLevelStep');

		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelXp','" . $higherLevelXp . "')";
		$this->_fvManagerDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelBegin','" . $higherLevelBegin . "')";
		$this->_fvManagerDBM->queryExec($fvSQL);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelStep','" . $higherLevelStep . "')";
		$this->_fvManagerDBM->queryExec($fvSQL);
		unset($xmlDoc);
		$fvSQL = "DELETE FROM units";
		$this->_fvManagerDBU->queryExec($fvSQL);
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc);
		$items = $xmlDoc->getElementsByTagName("item");
		$iCount  = $items->length;
		AddLog2("Adding $iCount items to units database");
		$mfvSQL = '';
		$this->_fvManagerDBU->queryExec('BEGIN;');
		foreach($items as $item)
		{
			$data = array();
			$classname = @$item->getAttribute('className');
			$ls = @$item->getElementsByTagName('limitedStart');//limitedStart
			$lstart = @$ls->item(0)->nodeValue;
			$le = @$item->getElementsByTagName('limitedEnd');//limitedEnd
			$lend = @$le->item(0)->nodeValue;
			$lmt = @$item->getElementsByTagName('limit');//limit
			$fn = @$item->getElementsByTagName('finishedName');//finishedName
			$ca = @$item->getElementsByTagName('capacity');//capacity
			$rl = @$item->getElementsByTagName('requiredLevel');//requiredLevel
			$cash = @$item->getElementsByTagName('cash');//cash
			$cost = @$item->getElementsByTagName('cost');//cost
			$sizeX = @$item->getElementsByTagName('sizeX');//sizeX
			$sizeY = @$item->getElementsByTagName('sizeY');//sizeY
			$data['code'] = @$item->getAttribute('code');
			$data['canplace'] = @$item->getAttribute('placeable');
			$data['iphoneonly'] = @$item->getAttribute('iphoneonly');
			$data['name'] = @$item->getAttribute('name');
			$data['type'] = @$item->getAttribute('type');
			$data['buyable'] = @$item->getAttribute('buyable');
			$data['subtype'] = @$item->getAttribute('subtype');
			$data['market'] = @$item->getAttribute('market');
			$data['classname'] = (empty($classname)) ? ucfirst($data['type']) : $classname;
			$data['limitedstart'] = (!empty($lstart)) ? strtotime($lstart) : '';
			$data['limitedend'] = (!empty($lend)) ? strtotime($lend) : '';
			$data['limit'] = @$lmt->item(0)->nodeValue;
			$data['finishedName'] = @$fn->item(0)->nodeValue;
			$data['capacity'] = @$ca->item(0)->nodeValue;
			$data['requiredLevel'] = @$rl->item(0)->nodeValue;
			$data['cash'] = @$cash->item(0)->nodeValue;
			$data['cost'] = @$cost->item(0)->nodeValue;
			$data['sizeX'] = @$sizeX->item(0)->nodeValue;
			$data['sizeY'] = @$sizeY->item(0)->nodeValue;
			$upgrades = @$item->getElementsByTagName('upgrade');//upgrade
			$up = array();
			foreach($upgrades as $upgrade)
			{
				$level = @$upgrade->getAttribute('level');
				$capacity = @$upgrade->getAttribute('capacity');
				$up[$level] = @$capacity;
			}
			$data['upgrade'] = serialize(@$up);
			$imgs = @$item->getElementsByTagName('image');
			foreach($imgs as $img)
			{
				$itype = $img->getAttribute('name');
				if($itype == 'built_rotatable' || $data['classname'] == 'RotateableDecoration')
				{
					$data['canrotate'] = 'true';
				}
				if($itype == 'icon')
				{
					$data['iconurl'] = @$img->getAttribute('url');
				}
			}
			$data['canrotate'] = (@$data['canrotate'] == 'true') ? 1 : 0;
			$data['canplace'] = (@$data['canplace'] == 'true') ? 1 : 0;
			$data['canplace'] = ((@$data['type'] == 'building' || @$data['type'] == 'decoration') && !is_numeric(@$data['canplace'])) ? 1 : @$data['canplace'];
			$data['iphoneonly'] = (@$data['iphoneonly'] == 'true') ? 1 : 0;
			$data['buyable'] = (@$data['buyable'] == 'true') ? 1 : 0;

			if(!empty($data['code']))
			{
				$realname = Units_GetRealnameByName($data['name']);
				$realname = str_replace("'", "''", $realname);
				$mfvSQL = " INSERT OR REPLACE INTO units(";
				$mfvSQL .= "units_name, units_type, units_code, units_buyable, ";
				$mfvSQL .= "units_class, units_iconurl, units_subtype, ";
				$mfvSQL .= "units_requiredLevel, units_sizeX, units_sizeY, ";
				$mfvSQL .= "units_market, units_cash, units_limitedstart, units_limitedend, ";
				$mfvSQL .= "units_cost, units_canplace, units_canrotate, units_capacity, units_finishedName, ";
				$mfvSQL .= "units_limit, units_iphoneonly, units_realname, units_upgrade) ";
				$mfvSQL .= "values('";
				$mfvSQL .= $data['name'] . "','" . $data['type'] . "','" . $data['code'] . "','";
				$mfvSQL .= $data['buyable'] . "','" . $data['classname'] . "','" . @$data['iconurl'] . "','";
				$mfvSQL .= $data['subtype'] . "','" . $data['requiredLevel'] . "','";
				$mfvSQL .= $data['sizeX'] . "','" . $data['sizeY'] . "','" . $data['market'] . "','";
				$mfvSQL .= $data['cash'] . "','" . $data['limitedstart'] . "','" . $data['limitedend'] . "','";
				$mfvSQL .= $data['cost'] . "','" . $data['canplace'] . "','" . $data['canrotate'] . "','";
				$mfvSQL .= $data['capacity'] . "','" . $data['finishedName'] . "','" . $data['limit'] . "','";
				$mfvSQL .= $data['iphoneonly'] . "','" . $realname . "','" . $data['upgrade'] . "');";
				$this->_fvManagerDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error . " " . $mfvSQL); }
			}
		}
		$this->_fvManagerDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		unset($xmlDoc);
		//Storage Information
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc4);
		$invent = $xmlDoc->getElementsByTagName('StorageEntity');
		$this->_fvManagerDBU->queryExec('BEGIN;');
		foreach($invent AS $inv)
		{
			$name = $inv->getAttribute('name');
			if($name != 'Inventory')
			{
				continue;
			}
			$aClass = $inv->getElementsByTagName('allowedClass');
			foreach($aClass AS $aC)
			{
				$storeClass = $aC->getAttribute('type');
				$mfvSQL = " UPDATE OR IGNORE units ";
				$mfvSQL .= "SET units_canstore = '1'";
				$mfvSQL .= " WHERE units_class LIKE '%" . trim($storeClass) . "%' AND units_subtype <>'animal_pens'";
				$mfvSQL .= " AND units_subtype <> 'crafting' AND units_subtype <> 'storage'";
				$this->_fvManagerDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error); echo $mfvSQL . "\n"; }
			}
			$dClass = $inv->getElementsByTagName('nonStorableClass');
			foreach($dClass AS $dC)
			{
				$nonstoreClass = $dC->nodeValue;
				$mfvSQL = " UPDATE OR IGNORE units ";
				$mfvSQL .= "SET units_canstore = '0'";
				$mfvSQL .= " WHERE units_class LIKE '%" . trim($nonstoreClass) . "%';";
				$this->_fvManagerDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error); echo $mfvSQL . "\n"; }
			}
		}
		$this->_fvManagerDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		unset($xmlDoc);
		$xmlDoc = new DOMDocument();
		//Crafting Recipe's
		$xmlDoc->loadXML($doc2);
		$items = $xmlDoc->getElementsByTagName("CraftingRecipe");
		$this->_fvManagerDBU->queryExec('BEGIN;');
		foreach($items as $item)
		{
			$id = $item->getAttribute('id');
			$ingredients = @$item->getElementsByTagName('Ingredient');
			$crafting = @$item->getElementsByTagName('craft');
			$craft = @$crafting->item(0)->nodeValue;
			$ing = array();
			foreach($ingredients as $ingredient)
			{
				$in = $ingredient->getAttribute('itemCode');
				$qty = $ingredient->getAttribute('quantityRequired');
				$ing[$in] = $qty;
			}
			$ings = serialize($ing);
			$fU_SQL = "UPDATE OR IGNORE units SET units_ingredients = '" . $ings . "' WHERE units_code = '" . $id . "';";
			$fU_SQL .= "UPDATE OR IGNORE units SET units_subtype = '" . $craft . "' WHERE units_code = '" . $id . "';";
			$this->_fvManagerDBU->queryExec($fU_SQL, $error);
			if (!empty($error)) { AddLog2($error); echo $fU_SQL . "\n"; }
			unset($ing);
		}
		$this->_fvManagerDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		$items = @$xmlDoc->getElementsByTagName("craftSkill");
		$this->_fvManagerDBU->queryExec('BEGIN;');
		foreach($items as $item)
		{
			$id = $item->getAttribute('id');
			$buildings = @$item->getElementsByTagName('cottageBuilding');
			$building = @$buildings->item(0)->getAttribute('name');
			$fU_SQL = "UPDATE OR IGNORE units SET units_craftSkill = '" . $id . "' WHERE units_name = '" . $building . "'";
			$this->_fvManagerDBU->queryExec($fU_SQL, $error);
			if (!empty($error)) { AddLog2($error); echo $fU_SQL . "\n"; }
			unset($ing);
		}
		$this->_fvManagerDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		//Check Units Count
		$fvSQL = 'SELECT COUNT(*) AS ucount FROM units';
		$q = $this->_fvManagerDBU->query($fvSQL);
		$ucount = $q->fetchAll(SQLITE_ASSOC);
		if ($ucount[0]['ucount'] >= ($iCount - 200)) {
			AddLog2("Added $iCount items to units database");
		} else {
			AddLog2("Failed Adding Units - Report to RadicalLinux");
			return;
		}
		AddLog2('fvManager has finished updating units');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('unitversion','". $this->flashRevision . "')";
		$this->_fvManagerDBM->queryExec($fvSQL);
		unset($xmlDoc);

	}

	private function _fvUpdateImages()
	{
		$fvSQL = "SELECT units_iconurl from units";
		$q = $this->_fvManagerDBU->query($fvSQL);
		$images = $q->fetchAll(SQLITE_ASSOC);
		foreach($images as $img)
		{
			$this->_fvMakeImage($img);
		}
	}

	private function _fvUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('level','" . $this->level . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('xp','" . $this->xp . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('gold','" . $this->gold . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('coin','" . $this->coin . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeX','" . $this->wsizeX . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('wsizeY','" . $this->wsizeY . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('version','" . fvManager_version . "');";
		$fvSQL .= "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('mybushels','" . $this->myBushels . "');";
		$this->_fvManagerDBM->queryExec($fvSQL);
	}

	function fvGetGoods()
	{
		$fwSQL = "SELECT myworld_itemCode FROM myworld WHERE myworld_className = 'CraftingCottageBuilding'";
		$q = $this->_fvManagerDBW->query($fwSQL);
		$bresults = $q->fetchAll(SQLITE_ASSOC);
		foreach($bresults AS $bresult)
		{
			$fuSQL = "SELECT units_craftskill FROM units WHERE units_code = '" . $bresult['myworld_itemCode'] . "'";
			$r = $this->_fvManagerDBU->query($fuSQL);
			$csresults = $r->fetchAll(SQLITE_ASSOC);
			$craftskill[] = @$csresults[0]['units_craftskill'];
		}
		$cs = implode("','", $craftskill);
		$fuSQL = "SELECT * FROM units WHERE units_subtype IN('$cs')";
		$q = $this->_fvManagerDBU->query($fuSQL);
		$iresults = $q->fetchAll(SQLITE_ASSOC);
		$iresults = $this->subval_sort($iresults, 'units_realname');
		return $iresults;
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
				if (strpos($err,'MC::lock()') !== false)
				{
					//Ignore Quietly Now, even if Debug is on
					$iguser = load_array('ignores.txt');
					preg_match('/rts_USER_(.*)_lock/', $err, $matches);
					$iguser[floatval($matches[1])] = floatval($matches[1]);
					save_array($iguser,'ignores.txt');
				} else {
					if ($this->settings['debug'] == 1 && $err !='Remaining function') AddLog2('fvManager Error: ' . $resp . ' - ' . $err);
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

	private function _fvMakeImage($img)
	{
		$Bot_path = getcwd();
		$fvManager_ImagePath = str_replace("/", "\\", $Bot_path);
		$iconurl = $fvManager_ImagePath . '\\' . $img['units_iconurl'] . ".40x40.jpeg";
		$name = $fvManager_ImagePath . '\\' . str_replace("/", "\\", $img['units_iconurl']);
		if (!file_exists($name)) return;
		if(filesize($iconurl) == 0) unlink($iconurl);
		if(!file_exists($iconurl))
		{
			if(file_exists($iconurl))
			{
				return;
			}
			$arr = split("\.", $name);
			$ext = $arr[count($arr) - 1];
			$newext = "jpeg";
			if($ext == "jpeg" || $ext == "jpg")
			{
				$image = imagecreatefromjpeg($name);
			}
			elseif($ext == "png")
			{
				$image = imagecreatefrompng($name);
			}
			elseif($ext == "gif")
			{
				$image = imagecreatefromgif($name);
			}
			if(!$img)
			return false;
			$old_x = imageSX($image);
			$old_y = imageSY($image);
			if($old_x < 40 && $old_y < 40)
			{
				$thumb_w = $old_x;
				$thumb_h = $old_y;
			}
			elseif($old_x > $old_y)
			{
				$thumb_w = 40;
				$thumb_h = floor(($old_y * (40 / $old_x)));
			}
			elseif($old_x < $old_y)
			{
				$thumb_w = floor($old_x * (40 / $old_y));
				$thumb_h = 40;
			}
			elseif($old_x == $old_y)
			{
				$thumb_w = 40;
				$thumb_h = 40;
			}
			$thumb_w = ($thumb_w < 1) ? 1 : $thumb_w;
			$thumb_h = ($thumb_h < 1) ? 1 : $thumb_h;
			$new_img = ImageCreateTrueColor($thumb_w, $thumb_h);

			if($transparency)
			{
				if($ext == "png")
				{
					imagealphablending($new_img, false);
					$colorTransparent = imagecolorallocatealpha($new_img, 0, 0, 0, 127);
					imagefill($new_img, 0, 0, $colorTransparent);
					imagesavealpha($new_img, true);
				}
				elseif($ext == "gif")
				{
					$trnprt_indx = imagecolortransparent($img);
					if($trnprt_indx >= 0)
					{
						$trnprt_color = imagecolorsforindex($image, $trnprt_indx);
						$trnprt_indx = imagecolorallocate($new_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
						imagefill($new_img, 0, 0, $trnprt_indx);
						imagecolortransparent($new_img, $trnprt_indx);
					}
				}
			}
			else
			{
				Imagefill($new_img, 0, 0, imagecolorallocate($new_img, 255, 255, 255));
			}

			imagecopyresampled($new_img, $image, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
			if($border)
			{
				$black = imagecolorallocate($new_img, 0, 0, 0);
				imagerectangle($new_img, 0, 0, $thumb_w, $thumb_h, $black);
			}
			imagejpeg($new_img, $iconurl, 50);
			imagedestroy($new_img);
			imagedestroy($image);
		}
		return;
	}

	private function _fvGetPlaceLoc($itemloc)
	{
		if($itemloc['units_sizeX'] > 1 || $itemloc['units_sizeY'] > 1)
		{
			$fvSQL = "SELECT * FROM locrect WHERE locrect_sizex >= '" . $itemloc['units_sizeX'] . "' AND locrect_sizey >= '" . $itemloc['units_sizeY'] . "' LIMIT 1";
		}
		else
		{
			$fvSQL = "SELECT * FROM locations LIMIT 1";
		}
		$q = $this->_fvManagerDBM->query($fvSQL);
		$loc = $q->fetchAll(SQLITE_ASSOC);
		if(count($loc) > 0)
		{
			if(isset($loc[0]['loc_x']))
			{
				$posx = $loc[0]['loc_x'];
				$posy = $loc[0]['loc_y'];
				$this->_fvManagerDBM->queryExec($fvSQL);
			}
			if(isset($loc[0]['locrect_x']))
			{
				$posx = $loc[0]['locrect_x'];
				$posy = $loc[0]['locrect_y'];
			}

			return array('x' => $posx, 'y' => $posy);
		}
		return false;
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

	private function _fvTradeGoods()
	{

		$fvSQL = "SELECT * FROM fmarket WHERE fmarket_itemCount > " . $this->settings['asgoods_keep'];
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		$amfcount = 0;
		$amf = '';
		$tmpArray = array();
		foreach($results AS $result)
		{
			$keep = $result['fmarket_itemCount'] - $this->settings['asgoods_keep'];
			for($x = 0; $x < $keep; $x++)
			{
				$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, '', 'CraftingService.onUseCraftedGood');
				$amf->_bodys[0]->_value[1][$amfcount]['params'][0] = trim($result['fmarket_itemCode']);
				$tmpArray[$amfcount] = $result['fmarket_itemRealName'];
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) continue;
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					AddLog2('[' . $key . '] fvManager sold/cleared ' . $tmpArray[$key] . " result: " . $this->zErrCGen[$returned['errorType']]);
				}
			}
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 !== false) {
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					AddLog2('[' . $key . '] fvManager sold/cleared ' . $tmpArray[$key] . " result: " . $this->zErrCGen[$returned['errorType']]);
				}
			}
		}
	}

	private function _fvTradeBushels()
	{
		$fvSQL = "SELECT * FROM fmbushels WHERE fmbushels_itemCount > " . $this->settings['asbushels_keep'];
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		$amfcount = 0;
		$amf = '';
		$tmpArray = array();
		foreach($results AS $result)
		{
			$keep = $this->settings['asbushels_keep'];
			if($this->settings['ascbushels'] == 1 && isset($this->actBushel[$result['fmbushels_itemCode']]) && $result['fmbushels_itemCount'] < $this->settings['ascbushels_keep'])
			{
				continue;
			} else if(isset($this->actBushel[$result['fmbushels_itemCode']])){
				$keep = $this->settings['ascbushels_keep'];
			}
			if($this->settings['pkbushels'] == 1)
			{
				if(isset($this->keepbushel[$result['fmbushels_itemCode']]) && !isset($this->actBushel[$result['fmbushels_itemCode']]))
				{
					$keep = 1;
				}
			}
			for($x = 0; $x < $result['fmbushels_itemCount'] - $keep; $x++)
			{
				$item = array();
				$item['itemName'] = trim($result['fmbushels_itemName']);
				$item['id'] = '63000';
				$item['tempId'] = '-1';
				$item['className'] = trim(ucfirst($result['fmbushels_type']));
				$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, 'use', 'WorldService.performAction');
				$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $item;
				$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = array();
				$tmpArray[$amfcount] = $result['fmbushels_itemRealName'];
				if ($amfcount < $this->botspeed - 1)
				{
					$amfcount++;
					continue;
				}
				$amf2 = $this->_fvAMFSend($amf);
				$amf = '';
				$amfcount = 0;
				if ($amf2 === false) continue;
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					AddLog2('[' . $key . '] fvManager sold/cleared ' . $tmpArray[$key] . " result: " . $this->zErrCGen[$returned['errorType']]);
				}
			}
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 !== false) {
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					AddLog2('[' . $key . '] fvManager sold/cleared ' . $tmpArray[$key] . " result: " . $this->zErrCGen[$returned['errorType']]);
				}
			}
		}
	}
	private function _fvBuyBushels()
	{
		$amfcount = 0;
		$amf = '';
		$tmpArray = array();
		while($this->bBagsConsumed < $this->bDailyPurch)
		{
			if($this->myBushels >= $this->bMaxCap)
			{
				AddLog2('Max Bushels Reached - Not Buying');
				break;
			}
			$cnt = count($this->availBushels);

			if ($this->settings['debug'] == 1) {
				AddLog2('Users With Bushels Available: ' . $cnt);
			}
			$bcnt = 1;
			foreach($this->availBushels  AS $aBushels)
			{
				//number_format($number, 2, '.', '');

				$uid = number_format($aBushels['uid'],0,'','');
				if (fBGetNeighborRealName($uid) === false) continue;
				if ($this->settings['debug'] == 1) {
					AddLog2('[' . $bcnt . '] *******User: ' . $uid . ' - Bushel Types: ' . count($aBushels['in']) . '*******');
				}
				$bcnt++;
				$iguser = load_array('ignores.txt');
				if (isset($iguser[$uid])) {
					if ($this->settings['debug'] == 1) {
						AddLog2("User on Ignore List");
					}
					continue;
				}
				if($this->myBushels >= $this->bMaxCap)
				{
					AddLog2('Max Bushels Reached - Not Buying');
					break 2;
				}
				$keep = $this->settings['ascbushels_keep'];
				foreach($aBushels['in'] AS $aInvent)
				{
					$code = $aInvent['ic'];
					if(!isset($this->actBushel[$code]) && $this->settings['debug'] == 1)
					{
						AddLog2("Bushel: " . $code . " - Not Selected for Recipes");
						continue;
					}
					if($this->fvFMBCounts2($code) >= $keep && $this->settings['debug'] == 1)
					{
						AddLog2("Bushel: " . $code . " - Exceeds Keep Amount");
						continue;
					}
					if(isset($this->actBushel[$code]) && $this->fvFMBCounts2($code) < $keep) {
						if ($this->settings['debug'] == 1) {
							AddLog2("Bushel: " . $code . " - Submitting for Purchase");
						}
						$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, '', 'CraftingService.onClaimMarketStallItem');
						$amf->_bodys[0]->_value[1][$amfcount]['params'][0] = $uid;
						$amf->_bodys[0]->_value[1][$amfcount]['params'][1] = trim($code);
						$amf->_bodys[0]->_value[1][$amfcount]['params'][2] = 0;
						$tmpArray[$amfcount]['uid'] = $uid;
						$tmpArray[$amfcount]['code'] = $code;
						/*if ($amfcount < 4)
						 {
							$amfcount++;
							continue;
							}*/
						$amf2 = $this->_fvAMFSend($amf);
						$amf = '';
						$amfcount = 0;

						if ($amf2 === false) continue;

						foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
						{
							$resp2 = $returned['data']['responseCode'];
							if($resp2 == 5)
							{
								AddLog2('Farmer\'s Market Full');
								break 3;

							}
							if($resp2 == 0)
							{
								$bush = Units_GetUnitByCode($tmpArray[$key]['code']);
								AddLog2("Buy " . $bush['realname'] . " from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
								$bush['realname'] = str_replace("'", "''", $bush['realname']);
								$gfvSQL = "INSERT INTO fmbushels(fmbushels_className, fmbushels_itemName, fmbushels_itemRealName, fmbushels_iconURL,  fmbushels_itemCode, fmbushels_type, fmbushels_itemCount)
												values('" . @$bush['className'] . "', '" . @$bush['name'] . "', '" . @$bush['realname'] . "', '" . $bush['iconurl'] . "', '" . $bush . "','" . @$bush['type'] . "',1);";
								$this->_fvManagerDBW->queryExec($gfvSQL);
								$this->bBagsConsumed++;
								$this->myBushels++;
							}
							else
							{
								if ($this->settings['debug'] == 1) {
									AddLog2("Buy from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
								}
							}
						}
					}
				}
			}
			break;
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 !== false) {
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp2 = $returned['data']['responseCode'];
					if($resp2 == 5)
					{
						AddLog2('Farmer\'s Market Full');
						break;

					}
					if($resp2 == 0)
					{
						$bush = Units_GetUnitByCode($tmpArray[$key]['code']);
						AddLog2("Buy " . $bush['realname'] . " from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
						$bush['realname'] = str_replace("'", "''", $bush['realname']);
						$gfvSQL = "INSERT INTO fmbushels(fmbushels_className, fmbushels_itemName, fmbushels_itemRealName, fmbushels_iconURL,  fmbushels_itemCode, fmbushels_type, fmbushels_itemCount)
												values('" . @$bush['className'] . "', '" . @$bush['name'] . "', '" . @$bush['realname'] . "', '" . $bush['iconurl'] . "', '" . $bush . "','" . @$bush['type'] . "',1);";
						$this->_fvManagerDBW->queryExec($gfvSQL);
					}
					else
					{
						if ($this->settings['debug'] == 1) {
							AddLog2("Buy from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCBushels[$resp2]);
						}
					}
				}
			}
		}
	}
	private function _fvBuyGoods()
	{
		$amfcount = 0;
		$amf = '';
		$tmpArray = array();
		$iguser = load_array('ignores.txt');
		$usedUserIds = array();
		while($this->cBagsConsumed < $this->cDailyPurch)
		{
			foreach($this->availGoods  AS $aGoods)
			{
				$uid = number_format($aGoods['uid'],0,'','');
				if (fBGetNeighborRealName($uid) === false) continue;
				if (isset($iguser[$uid])) { continue; }
				if(@$this->cConsumed[$uid] >= $this->cDailyBags)
				{
					if ($this->settings['debug'] == 1) AddLog2("Buy from " . $uid . " result: Maximum Goods Consumed");
					continue;
				}
				foreach($aGoods['in'] AS $aInvent)
				{
					if (isset($usedUserIds[$uid]))
					{
						break;
					}
					$code = $aInvent['ic'];
					$cost = $aInvent['pr'];
					$amf = $this->_fvCreateMultAMFRequest($amf, $amfcount, '', 'CraftingService.onBuyCraftedGoods');
					$amf->_bodys[0]->_value[1][$amfcount]['params'][0][0]['itemCode'] = trim($code);
					$amf->_bodys[0]->_value[1][$amfcount]['params'][0][0]['priceOffered'] = $cost;
					$amf->_bodys[0]->_value[1][$amfcount]['params'][0][0]['sellingUserId'] = trim($uid);;
					$tmpArray[$amfcount]['uid'] = $uid;
					$tmpArray[$amfcount]['code'] = $code;
					$amf2 = $this->_fvAMFSend($amf);
					$amf = '';
					$amfcount = 0;

					if ($amf2 === false) continue;
					foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
					{
						$resp2 = $returned['data']['buyResponse']['buyResults'][0]['code'];
						if($resp2 == 1 || $resp2 == 2 || $resp2 == 12) {
							$iguser = load_array('ignores.txt');
							$iguser[$tmpArray[$key]['uid']] = $tmpArray[$key]['uid'];
							save_array($iguser,'ignores.txt');

						}
						if($resp2 == 0)
						{
							$item = $this->fvGetUnits($tmpArray[$key]['code']);
							AddLog2("Buy " . $item['units_realname'] . " from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCGoods[$resp2]);
							$usedUserIds[$uid] = 1;
							$this->cBagsConsumed++;
							break;
						}
						else
						{
							if ($this->settings['debug'] == 1) {
								AddLog2("Buy from $uid result: " . $this->zErrCGoods[$resp2]);
							}
						}
					}
				}
			}
			break;
		}
		if ($amf != '') //Still have requests left
		{
			$amf2 = $this->_fvAMFSend($amf);
			if ($amf2 !== false) {
				foreach ($amf2->_bodys[0]->_value['data'] as $key=>$returned)
				{
					$resp2 = $returned['data']['buyResponse']['code'];
					if($resp2 == 1 || $resp2 == 2 || $resp2 == 12) {
						$iguser = load_array('ignores.txt');
						$iguser[$tmpArray[$key]['uid']] = $tmpArray[$key]['uid'];
						save_array($iguser,'ignores.txt');
					}
					if($resp2 == 0)
					{
						$item = $this->fvGetUnits($tmpArray[$key]['code']);
						AddLog2("Buy " . $item['units_realname'] . " from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCGoods[$resp2]);
					}
					else
					{
						if ($this->settings['debug'] == 1) {
							AddLog2("Buy from " . $tmpArray[$key]['uid'] . " result: " . $this->zErrCGoods[$resp2]);
						}
					}
				}
			}
		}
	}
	function fvCanStore()
	{
		$fvSQL = "SELECT units_code FROM units WHERE units_canstore=1";
		$q = $this->_fvManagerDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $result)
		{
			$storable[$result['units_code']] = $result['units_code'];
		}
		return $storable;
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
	private function _findEmpty()
	{
		$maxX = $this->wsizeX;
		$maxY = $this->wsizeY;
		for ($x = 0; $x < $maxX; $x++)
		for ($y = 0; $y < $maxY; $y++)
		$object[$x][$y] = 'empty';
		$fvSQL = "SELECT * FROM myworld ORDER BY myworld_posx, myworld_posy";
		$q = $this->_fvManagerDBW->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach($results as $item)
		{
			$item2 = Units_GetUnitByName($item['myworld_itemName']);
			$item2['sizeX'] = !isset($item2['sizeX']) || empty($items2['sizeX']) ? 1 : $item2['sizeX'];
			$item2['sizeY'] = !isset($item2['sizeY']) || empty($items2['sizeY']) ? 1 : $item2['sizeY'];
			if ($item['myworld_state'] == 'vertical' || $item['myworld_direction'] != '1')
			{
				for ($x = $item['myworld_posx']; $x <= ($item['myworld_posx'] + $item2['sizeY']); $x++)
				for ($y = $item['myworld_posy']; $y <= ($item['myworld_posy'] + $item2['sizeX']); $y++)
				unset($object[$x][$y]);
			} else {
				for ($x = $item['myworld_posx']; $x <= ($item['myworld_posx'] + $item2['sizeX']); $x++)
				for ($y = $item['myworld_posy']; $y <= ($item['myworld_posy'] + $item2['sizeY']); $y++)
				unset($object[$x][$y]);
			}
		}
		$this->objects = $object;
	}

	private function _GetLocation($itemloc)
	{
		$maxX = $this->wsizeX;
		$maxY = $this->wsizeY;
		if (@$itemloc['units_sizeX'] > 1 || @$itemloc['units_sizeY'] > 1)
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
}
?>
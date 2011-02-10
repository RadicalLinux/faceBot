<?php
class fvXML
{
	var $userId;
	var $error, $fvAll, $haveWorld, $units, $_fvXMLDB;

	private function _refreshWorld()
	{
		$this->haveWorld = true;
	}

	private function _fvXML_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = $this->_fvXMLDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fvXMLDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "');";
			$this->_fvXMLDBM->queryExec($fvSQL);
		}
		$q = $this->_fvXMLDBU->query('SELECT * FROM units LIMIT 1');
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
                    units_giftable INTEGER DEFAULT 0,
                    units_limit INTEGER,
                    units_market CHAR(5),
					units_cash INTEGER,
					units_license CHAR(4),
					units_requirements TEXT,
					units_growTime REAL,
					units_plantXp INTEGER,
					units_coinYield INTEGER,
					units_bushelItemCode CHAR(4),
					units_mastery INTEGER DEFAULT 0,
                    units_craftskill CHAR(50),
                    units_upgrade TEXT,
                    units_ingredients TEXT,
                    units_limitedstart TIMESTAMP,
                    units_limitedend TIMESTAMP,
                    units_finishedName CHAR(100),
                    units_cost INTEGER
			)';
			$this->_fvXMLDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_class ON units(units_class)';
			$this->_fvXMLDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_name ON units(units_name)';
			$this->_fvXMLDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_code ON units(units_code)';
			$this->_fvXMLDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_type ON units(units_type)';
			$this->_fvXMLDBU->queryExec($fvSQL);
			$fvSQL = 'CREATE INDEX units_buyable ON units(units_buyable)';
			$this->_fvXMLDBU->queryExec($fvSQL);
		}
	}

	//Function fvXML class initializer
	function fvXML($inittype = '')
	{
		$this->userId = $_SESSION['userId'];
		list( , , , , , , , , , , , $this->flashRevision) = explode(';', fBGetDataStore('playerinfo'));
		//Open Databases
		$this->_fvXMLDBM = new SQLiteDatabase(fvXML_Path . fvXML_Main);
		$this->_fvXMLDBU = new SQLiteDatabase(fvXML_Path . fvXML_Units);
		if(!$this->_fvXMLDBM || !$this->_fvXMLDBU)
		{
			$this->error = 'fvXML - Database Error';
			return;
		}
		$this->_fvXMLDBM->queryExec('PRAGMA cache_size=20000');
		$this->_fvXMLDBM->queryExec('PRAGMA synchronous=OFF');
		$this->_fvXMLDBM->queryExec('PRAGMA count_changes=OFF');
		$this->_fvXMLDBM->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvXMLDBM->queryExec('PRAGMA temp_store=MEMORY');
		$this->_fvXMLDBU->queryExec('PRAGMA cache_size=20000');
		$this->_fvXMLDBU->queryExec('PRAGMA synchronous=OFF');
		$this->_fvXMLDBU->queryExec('PRAGMA count_changes=OFF');
		$this->_fvXMLDBU->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_fvXMLDBU->queryExec('PRAGMA temp_store=MEMORY');
		//Get Settings
		$this->settings = $this->fvGetSettings();
		if($inittype == 'formload')
		{
			if(empty($this->settings))
			{
				$this->error = 'Please allow fvXML to run a cycle';
			}
			return;
		}
		//Load the world from Z*
		$this->_refreshWorld();
		if($this->haveWorld === true)
		{
			if($this->settings === false)
			{
				$this->_fvXML_checkDB();//Database doesn't exist, create
				$this->_fvUpdateSettings();//Insert initial settings
				$this->_fvUpdateUnits();//Update the Units file
				$this->error = 'Please allow fvXML to run a cycle to update all settings';
				return;
			}
			if($this->settings['flashRevision'] != $this->flashRevision)
			{
				$this->_fvUpdateUnits();//Update the Units file
				$this->settings = $this->fvGetSettings();
			}
			if($this->settings['unitversion'] != $this->flashRevision)
			{
				$this->_fvUpdateUnits();//Update the Units file
			}
			$this->_fvUpdateSettings();//Update the settings
			$this->_fvUpdateImages();//Update Thumbnails
		}
	}

	function fvGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		$q = $this->_fvXMLDBM->query($fvSQL);
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

	//Needs fixed for new units format
	function fvBuyableUnits($itype = '')
	{
		$fvSQL = "SELECT * FROM units ";
		$fvSQL .= "WHERE units_type <> 'mission' AND units_type <> 'itembuff' AND units_type <> 'messageSign' ";
		$fvSQL .= "AND units_type <> 'buff' AND units_type <> 'plot'";
		//$fvSQL .= "AND units_type <> 'crafted' AND units_type <> 'bushel' AND units_type <> 'seed'";
		if(!empty($itype))
		{
			$fvSQL .= " AND units_type = '" . $itype . "'";
		}
		if(!empty($itype))
		{
			$fvSQL .= ' ORDER BY units_realname';
		}
		else
		{
			$fvSQL .= ' GROUP BY units_type ORDER BY units_type';
		}

		$q = $this->_fvXMLDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach($results as $key => $bunits)
		{
			if(empty($bunits['units_market']) && $bunits['units_cost'] > 0 && $bunits['units_cash'] < 1)
			{
				$results[$key]['units_market'] = 'coins';
			}
			if ($bunits['units_market'] == 'cash')
			{
				$results[$key]['units_cost'] = $results[$key]['units_cash'];
			} 
			if (empty($bunits['units_market']) && $bunits['units_cost'] >= 1)
			{
				$results[$key]['units_market'] = 'coins';
			} 
			$results[$key]['units_market'] = (empty($results[$key]['units_market'])) ? '&nbsp;' : $results[$key]['units_market'];
			$results[$key]['units_cost'] = (empty($results[$key]['units_cost'])) ? '0' : $results[$key]['units_cost'];
			$results[$key]['units_sizeX'] = ($bunits['units_sizeX'] < 1) ? 1 : $bunits['units_sizeX'];
			$results[$key]['units_sizeY'] = ($bunits['units_sizeY'] < 1) ? 1 : $bunits['units_sizeY'];
			$results[$key]['units_giftable'] = ($bunits['units_giftable'] == 1) ? 'X' : '&nbsp;';
			$results[$key]['units_iphoneonly'] = ($bunits['units_iphoneonly'] == 1) ? 'X' : '&nbsp';
			$results[$key]['units_canplace'] = ($bunits['units_canplace'] == 1) ? 'X' : '&nbsp';
		}
		return $results;
	}

	function fvGetUnits($code)
	{
		$fvSQL = "SELECT * FROM units WHERE units_code='" . $code . "'";
		$q = $this->_fvXMLDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return @$results[0];
	}

	private function _fvUpdateUnits()
	{
		error_reporting(E_ERROR | E_WARNING | E_NOTICE);
		ini_set('display_errors', true);
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
		AddLog2('fvXML is updating units');
		//$newimgs = load_array('newimages.txt');
		$fvSQL = "DELETE FROM units";
		$this->_fvXMLDBU->queryExec($fvSQL);
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc);
		$items = $xmlDoc->getElementsByTagName("item");
		$iCount  = $items->length;
		AddLog2("Adding $iCount items to units database");
		$mfvSQL = '';
		$this->_fvXMLDBU->queryExec('BEGIN;');
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
			$growTime = @$item->getElementsByTagName('growTime');
			$plantXp = @$item->getElementsByTagName('plantXp');
			$coinYield = @$item->getElementsByTagName('coinYield');
			$data['code'] = @$item->getAttribute('code');
			$data['canplace'] = @$item->getAttribute('placeable');
			$data['iphoneonly'] = @$item->getAttribute('iphoneonly');
			$data['mastery'] = @$item->getAttribute('mastery');
			$data['license'] = @$item->getAttribute('license');
			$data['name'] = @$item->getAttribute('name');
			$data['type'] = @$item->getAttribute('type');
			$data['buyable'] = @$item->getAttribute('buyable');
			$data['subtype'] = @$item->getAttribute('subtype');
			$data['giftable'] = @$item->getAttribute('giftable');
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
			$data['growTime'] = @$growTime->item(0)->nodeValue;
			$data['plantXp'] = @$plantXp->item(0)->nodeValue;
			$data['coinYield'] = @$coinYield->item(0)->nodeValue;
			$upgrades = @$item->getElementsByTagName('upgrade');//upgrade
			$up = array();
			foreach($upgrades as $upgrade)
			{
				$level = @$upgrade->getAttribute('level');
				$capacity = @$upgrade->getAttribute('capacity');
				$up[$level] = @$capacity;
			}
			$data['upgrade'] = serialize(@$up);
			$requirements = @$item->getElementsByTagName('requirements');//upgrade
			$require = array();
			foreach($requirements as $rq)
			{
				$rqs = @$rq->getElementsByTagName('requirement');
				$rname = $rqs->item(0)->getAttribute('name');
				$rlevel = $rqs->item(0)->getAttribute('level');
				$require[$rname] = @$rlevel;
			}
			$data['requirements'] = serialize(@$require);			
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
			$data['mastery'] = (@$data['mastery'] == 'true') ? 1 : 0;
			$data['giftable'] = (@$data['giftable'] == 'true') ? 1 : 0;
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
				$mfvSQL .= "units_limit, units_iphoneonly, units_realname, units_upgrade, units_license, ";
				$mfvSQL .= "units_requirements, units_coinYield, units_growTime, units_plantXp, units_mastery, units_giftable) ";
				$mfvSQL .= "values('";
				$mfvSQL .= $data['name'] . "','" . $data['type'] . "','" . $data['code'] . "','";
				$mfvSQL .= $data['buyable'] . "','" . $data['classname'] . "','" . @$data['iconurl'] . "','";
				$mfvSQL .= $data['subtype'] . "','" . $data['requiredLevel'] . "','";
				$mfvSQL .= $data['sizeX'] . "','" . $data['sizeY'] . "','" . $data['market'] . "','";
				$mfvSQL .= $data['cash'] . "','" . $data['limitedstart'] . "','" . $data['limitedend'] . "','";
				$mfvSQL .= $data['cost'] . "','" . $data['canplace'] . "','" . $data['canrotate'] . "','";
				$mfvSQL .= $data['capacity'] . "','" . $data['finishedName'] . "','" . $data['limit'] . "','";
				$mfvSQL .= $data['iphoneonly'] . "','" . $realname . "','" . $data['upgrade'] . "','";
				$mfvSQL .= $data['license'] . "','" . $data['requirements'] . "','" . $data['coinYield'] . "','";
				$mfvSQL .= $data['growTime'] . "','"  . $data['plantXp'] . "','"  . $data['mastery'] . "','" . $data['giftable'] . "');";
				$this->_fvXMLDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error); echo $mfvSQL . "\n"; }
			}
		}
		$this->_fvXMLDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		unset($xmlDoc);
		//Storage Information
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($doc4);
		$invent = $xmlDoc->getElementsByTagName('StorageEntity');
		$this->_fvXMLDBU->queryExec('BEGIN;');
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
				$this->_fvXMLDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error); echo $mfvSQL . "\n"; }
			}
			$dClass = $inv->getElementsByTagName('nonStorableClass');
			foreach($dClass AS $dC)
			{
				$nonstoreClass = $dC->nodeValue;
				$mfvSQL = " UPDATE OR IGNORE units ";
				$mfvSQL .= "SET units_canstore = '0'";
				$mfvSQL .= " WHERE units_class LIKE '%" . trim($nonstoreClass) . "%';";
				$this->_fvXMLDBU->queryExec($mfvSQL, $error);
				if (!empty($error)) { AddLog2($error); echo $mfvSQL . "\n"; }
			}
		}
		$this->_fvXMLDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		unset($xmlDoc);
	
		$xmlDoc = new DOMDocument();
		//Crafting Recipe's
		$xmlDoc->loadXML($doc2);
		$items = $xmlDoc->getElementsByTagName("CraftingRecipe");
		$this->_fvXMLDBU->queryExec('BEGIN;');
		foreach($items as $item)
		{
			$id = $item->getAttribute('id');
			$ingredients = $item->getElementsByTagName('Ingredient');
			$ing = array();
			foreach($ingredients as $ingredient)
			{
				$in = $ingredient->getAttribute('itemCode');
				$qty = $ingredient->getAttribute('quantityRequired');
				$ing[$in] = $qty;
			}
			$ings = serialize($ing);
			$fU_SQL = "UPDATE OR IGNORE units SET units_ingredients = '" . $ings . "' WHERE units_code = '" . $id . "'";
			$this->_fvXMLDBU->queryExec($fU_SQL, $error);
			if (!empty($error)) { AddLog2($error); echo $fU_SQL . "\n"; }
			unset($ing);
		}
		$this->_fvXMLDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		$items = @$xmlDoc->getElementsByTagName("craftSkill");
		$this->_fvXMLDBU->queryExec('BEGIN;');
		foreach($items as $item)
		{
			$id = $item->getAttribute('id');
			$buildings = @$item->getElementsByTagName('cottageBuilding');
			$building = @$buildings->item(0)->getAttribute('name');
			$fU_SQL = "UPDATE OR IGNORE units SET units_craftSkill = '" . $id . "' WHERE units_name = '" . $building . "'";
			$this->_fvXMLDBU->queryExec($fU_SQL, $error);
			if (!empty($error)) { AddLog2($error); echo $fU_SQL . "\n"; }
			unset($ing);
		}
		$this->_fvXMLDBU->queryExec('COMMIT;', $error);
		if (!empty($error)) { AddLog2($error); }
		//Check Units Count
		$fvSQL = 'SELECT COUNT(*) AS ucount FROM units';
		$q = $this->_fvXMLDBU->query($fvSQL);
		$ucount = $q->fetchAll(SQLITE_ASSOC);
		if ($ucount[0]['ucount'] >= ($iCount - 200)) {
			AddLog2("Added $iCount items to units database");
		} else {
			AddLog2("Failed Adding Units - Report to RadicalLinux");
			return;
		}
		AddLog2('fvXML has finished updating units');
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('unitversion','". $this->flashRevision . "')";
		$this->_fvXMLDBM->queryExec($fvSQL);
		unset($xmlDoc);

	}

	private function _fvUpdateImages()
	{
		$fvSQL = "SELECT units_iconurl from units";
		$q = $this->_fvXMLDBU->query($fvSQL);
		$images = $q->fetchAll(SQLITE_ASSOC);
		foreach($images as $img)
		{
			$this->_fvMakeImage($img);
		}
	}

	private function _fvUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $this->flashRevision . "');";
		$this->_fvXMLDBM->queryExec($fvSQL);
	}

	private function _fvMakeImage($img)
	{
		$Bot_path = getcwd();
		$fvXML_ImagePath = str_replace("/", "\\", $Bot_path);
		$iconurl = $fvXML_ImagePath . '\\' . $img['units_iconurl'] . ".40x40.jpeg";
		$name = $fvXML_ImagePath . '\\' . str_replace("/", "\\", $img['units_iconurl']);
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
	function fvCanStore()
	{
		$fvSQL = "SELECT units_code FROM units WHERE units_canstore=1";
		$q = $this->_fvXMLDBU->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		foreach ($results as $result)
		{
			$storable[$result['units_code']] = $result['units_code'];
		}
		return $storable;
	}
}
?>
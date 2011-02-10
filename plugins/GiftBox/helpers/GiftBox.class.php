<?php
class GBsql
{
	var $_GBUser, $_GBMain, $GB_Setting;

	function GBsql($inittype = '')
	{
		$this->_GBUser = new SQLiteDatabase($_SESSION['base_path'] . "plugins/GiftBox/".$_SESSION['userId']."_".GBox_DB_user);
		$this->_GBMain = new SQLiteDatabase($_SESSION['base_path'] . "plugins/GiftBox/".GBox_DB_main);
		if(!$this->_GBUser || !$this->_GBMain)
		{
			$this->error = 'GiftBox - Database Error';
			return;
		}
		$this->_GBMain->queryExec('PRAGMA cache_size=20000');
		$this->_GBMain->queryExec('PRAGMA synchronous=OFF');
		$this->_GBMain->queryExec('PRAGMA count_changes=OFF');
		$this->_GBMain->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_GBMain->queryExec('PRAGMA temp_store=MEMORY');
		$this->_GBUser->queryExec('PRAGMA cache_size=20000');
		$this->_GBUser->queryExec('PRAGMA synchronous=OFF');
		$this->_GBUser->queryExec('PRAGMA count_changes=OFF');
		$this->_GBUser->queryExec('PRAGMA journal_mode=MEMORY');
		$this->_GBUser->queryExec('PRAGMA temp_store=MEMORY');
		$this->GBDBuser_create();
		$this->GBDBmain_create();
	}
	//*********************************
	//  Open the user database
	//*********************************
	function GBDBuser_init($origin)
	{
		$now = time();
		$last = time()-600;
		$lastUpdate = $this->GB_Get_User_Setting('LastUpdate');
		$diff = $now - $lastUpdate ;
		if($lastUpdate < $last  || $lastUpdate == 'Fail' || $lastUpdate == "Not Found")
		{
			AddLog2('Giftbox: Running Database Cleanup (every 10 min).');
			$this->_GBUser->query("vacuum");
			$this->_GBMain->query("vacuum");

			// Check if flash version is up2date
			$result1 = $this->_GBMain->query("SELECT _val FROM gamesettings WHERE _set = 'flashversion' limit 1");
			if ($result1->numRows() > 0) { $flashversion = $result1->fetchSingle(); }else {$flashversion = "'NULL'";}
			$flashNeedUpdate = "N";
			if ($flashversion == $_SESSION['flashRevision'] )
			{
				AddLog2('GiftBox: Flash Version Up to Date (' .$flashversion . ")"); }
				else
				{
					AddLog2('GiftBox: Flash Version Out of Date - Have: ' . $flashversion . " Need: " . $_SESSION['flashRevision']);
					$flashNeedUpdate = "Y";
				}
				if ($flashNeedUpdate == "Y" )
				{
					AddLog2('GiftBox: Updating Units');
					$this->GB_gameSettings_SQL($_SESSION['flashRevision']);
					$GBSQL ="INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES(".$this->Qs('flashversion').",".$this->Qs($_SESSION['flashRevision']).")";
					$this->_GBUser->query($GBSQL);
					//AddLog2('GiftBox: Updating Locale');
					//$this->GB_flashLocaleXml_SQL($_SESSION['flashRevision']);
					$this->GB_Update_User_Setting('flashRevision' , $_SESSION['flashRevision']);

				}
				AddLog2('GiftBox: Detecting Special Items');
				$this->GB_DetectSpecials2();
				AddLog2('GiftBox: Detecting Building Parts');
				$this->GB_DetectBuildingParts4();
				AddLog2('GiftBox: Detecting Collection Items.');
				$this->GB_DetectCollections();
				$this->GB_Update_User_Setting('LastUpdate' , $now) ;
		} // 10 min. check
	}
	//*********************************
	//  Create the user database
	//*********************************
	function GBDBuser_create()
	{
		$q = $this->_GBUser->query('SELECT * FROM giftbox LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating giftbox table" );
			$this->_GBUser->query(
				'CREATE TABLE giftbox (
                	id INT PRIMARY KEY,
                	_itemcode CHAR(5) unique,
                	_amount CHAR(5),  
                	_orig CHAR(5),      
                	_gifters CHAR(250) )');
		}
		$q = $this->_GBUser->query('SELECT * FROM totstorage LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating totstorage table" );
			$this->_GBUser->query(
				'CREATE TABLE totstorage (
                	id INT PRIMARY KEY,   
                	_storagecode CHAR(5),  
                	_itemcode CHAR(5),
                	_amount CHAR(5),      
                	_gifters CHAR(250) )');
		}
		$q = $this->_GBUser->query('SELECT * FROM locations LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating locations table" );
			$this->_GBUser->query(
				'CREATE TABLE locations (
                	id INT PRIMARY KEY,   
                	_X INT,  
                	_Y INT,
                	_what CHAR(25) )');
			$this->_GBUser->query('CREATE INDEX "locYX" ON "locations" ("_X", "_Y")');
		}
		$q = $this->_GBUser->query('SELECT * FROM gamesettings LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating gamesettings table" );
			$this->_GBUser->query(
				'CREATE TABLE gamesettings (
                	_id INT PRIMARY KEY,    
                	_set CHAR(10) unique, 
                	_val CHAR(10)); ');
			// setting the defaults
			$GBSQL ="INSERT INTO gamesettings(_set,_val) VALUES('RunPlugin','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoFuel','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoSpecials','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoSelling','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoPlace','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoFeetPet','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoColl','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoCollSell','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoCollTrade','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoCollKeep','5');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoConstr','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoPlaceBuild','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('ShowImage','1');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('ShowImageAll','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoDebug','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DoResetXML','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('AnimalX1','35');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('AnimalY1','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('AnimalX2','45');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('AnimalY2','65');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('TreeX1','55');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('TreeY1','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('TreeX2','65');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('TreeY2','65');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DecorationX1','45');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DecorationY1','0');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DecorationX2','55');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('DecorationY2','65');";
			$GBSQL .="INSERT INTO gamesettings(_set,_val) VALUES('flashversion','".$_SESSION['flashRevision']."');";
			$this->_GBUser->query($GBSQL);

			// now load the settings from general_settings.txt
			$GB_Sets = file($_SESSION['base_path'] . 'plugins/GiftBox/general_settings.txt');
			if($GB_Sets)
			{
				foreach($GB_Sets as $GB_Set)
				{
					$GB_TSet = explode(':', $GB_Set);
					if (strpos($GB_TSet['0'], '#') !== false)
					{ $comment = $GB_TSet['0']; }
					else {
						$GB_settVar = $GB_TSet['0'] ;
						$GB_settVal = $GB_TSet['1'] ;
						$GBSQL ="INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES('".$GB_settVar."','".$GB_settVal."');";
					}
				}
				$this->_GBUser->query($GBSQL);
			}

			//Default loaded, now add the rest.
			$GBSQL ="INSERT INTO gamesettings(_set,_val) VALUES('userid','".$_SESSION['userId']."');";
			$this->_GBUser->query($GBSQL);
		}
		$q = $this->_GBUser->query('SELECT * FROM objects LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating objects table" );
			$this->_GBUser->query(
				'CREATE TABLE objects (
                	_id INT PRIMARY KEY,    
                	_obj CHAR(5),  
                	_set CHAR(25),  
                	_val CHAR(100)); ');
			$this->_GBUser->query('CREATE INDEX "obj_obj" ON "objects" ("_obj")');
			$this->_GBUser->query('CREATE INDEX "obj_SV" ON "objects" ("_set", "_val")');
		}
		$q = $this->_GBUser->query('SELECT * FROM stats LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating stats table" );
			$this->_GBUser->query(
				'CREATE TABLE stats (
                	_id INT PRIMARY KEY,    
                	_code CHAR(5),    
                	_number INT,  
                	_action CHAR(25),  
                	_name CHAR(25),  
                	_date datetime); ');
		}
		$q = $this->_GBUser->query('SELECT * FROM action LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating action table" );
			$this->_GBUser->query(
				'CREATE TABLE action (
                	_code CHAR(10) PRIMARY KEY,                _place_on_farm CHAR(10) DEFAULT 0 ,
                	_place_in_build CHAR(10) DEFAULT 0 ,       _place_in_amount CHAR(10) DEFAULT 0 ,
                	_place_in_max CHAR(10) DEFAULT 0 ,         _place_in_special CHAR(10) DEFAULT 0 ,
                	_target CHAR(10) DEFAULT 0 ,               _selling CHAR(10) DEFAULT 0 ,
                	_keep CHAR(10) DEFAULT 0 ,                 _collection CHAR(10) DEFAULT 0 ,
                	_consume CHAR(10) DEFAULT 0 ,              _construction CHAR(10) DEFAULT 0 ,
                	_pet CHAR(10) DEFAULT 0 ,                  _a CHAR(10)); ');
			$this->_GBUser->query('CREATE INDEX "act_code" ON "action" ("_code")');
		}
		$q = $this->_GBUser->query('SELECT * FROM BuildingParts LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating BuildingParts table" );
			$this->_GBUser->query(
				'CREATE TABLE BuildingParts (
                	_id INT PRIMARY KEY,
                	_name CHAR(200) DEFAULT 0 ,
                	_itemName CHAR(50) DEFAULT 0 ,
                	_itemCode CHAR(5) DEFAULT 0 ,
                	_need CHAR(5) DEFAULT 0 ,
                	_part CHAR(5)  DEFAULT 0,
                	_UnitBuildCode CHAR(5)  DEFAULT 0,
                	_UnitBuildName CHAR(50)  DEFAULT 0,
                	_ObjHave CHAR(5)  DEFAULT 0,
                	_ObjId CHAR(5)  DEFAULT 0,
                	_ObjState CHAR(5)  DEFAULT 0,
                	_action CHAR(5)  DEFAULT 0); ');
			$this->_GBUser->query('CREATE INDEX "SC_itemcode" ON "BuildingParts" ("_itemcode")');
			$this->_GBUser->query('CREATE INDEX "SC_part" ON "BuildingParts" ("_part")');
		}
		return ;
	}

	//*********************************
	//  CREATE the Main database
	//*********************************
	function GBDBmain_create()
	{
		$q = $this->_GBMain->query('SELECT * FROM unitbuilding LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating unitbuilding table" );
			$this->_GBMain->query(
				'CREATE TABLE unitbuilding (
                	id INT PRIMARY KEY,                _buildingcode CHAR(5),
                	_itemcode CHAR(5),                 _part CHAR(5),
                	_item CHAR(50),                    _name CHAR(50),
                	_itemName CHAR(50),                _need CHAR(50),
                	_limit CHAR(5),                    _level CHAR(50),
                	_capacity CHAR(50),                _component_for CHAR(50),
	                _storageType_itemName CHAR(50),
    	            _matsNeeded CHAR(50))');
		}
		$q = $this->_GBMain->query('SELECT * FROM locale LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating locale table" );
			$this->_GBMain->query(
				'CREATE TABLE locale ( 
					_id INT PRIMARY KEY,    
					_raw CHAR(20), 
					_nice CHAR(200)); ');
			$this->_GBMain->query('CREATE INDEX "localeIND" ON "locale" ("_raw", "_nice")');
		}
		$q = $this->_GBMain->query('SELECT * FROM StorageConfig LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating StorageConfig table" );
			$this->_GBMain->query(
				'CREATE TABLE StorageConfig (
                	_id INT PRIMARY KEY,                _name CHAR(200) DEFAULT 0 ,
                	_allowKeyword CHAR(50) DEFAULT 0 ,  _itemName CHAR(50) DEFAULT 0 ,
                	_itemCode CHAR(5) DEFAULT 0 ,       _need CHAR(5) DEFAULT 0 ,
                	_limit CHAR(5) DEFAULT 0 ,          _part CHAR(5)  DEFAULT 0 ); ');
			$this->_GBMain->query('CREATE INDEX "SC_itemcode" ON "StorageConfig" ("_itemcode")');
			$this->_GBMain->query('CREATE INDEX "SC_part" ON "StorageConfig" ("_part")');
		}
		$q = $this->_GBMain->query('SELECT * FROM gamesettings LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating main.gamesettings table" );
			$this->_GBMain->query(
				'CREATE TABLE gamesettings (
                	_id INT PRIMARY KEY,    _set CHAR(10) unique,
                	_val CHAR(10)); ');
		}
		$q = $this->_GBMain->query('SELECT * FROM units LIMIT 1');
		if($q === false)
		{
			AddLog2("GiftBox: Creating units table" );
			$this->_GBMain->query(
				'CREATE TABLE units (
                	id INT PRIMARY KEY,       _code CHAR(5),          _name CHAR(50),           _giftable CHAR(10),
                	_type CHAR(50),           _subtype CHAR(50),      _buyable CHAR(10),        _placeable CHAR(10),
                	_limit CHAR(5),           _className CHAR(50),    _requiredLevel CHAR(50),  _cost CHAR(50)  DEFAULT 0,
                	_sizeX CHAR(3) DEFAULT 0, _sizeY CHAR(3) DEFAULT 0, _image_icon CHAR(150),    _cash CHAR(5),
                	_limitedStart CHAR(50),   _limitedEnd CHAR(50),   _XP CHAR(5),              _coinYield CHAR(50),
                	_finishedName  CHAR(50),  _action CHAR(50),       _actionText CHAR(50),     _insanityProbability CHAR(50),
                	_baby CHAR(50),           _expansion CHAR(50),    _capacity CHAR(5),        _storageSize CHAR(5),
                	_storageType_itemClass CHAR(50),  iconurl CHAR(100), _display CHAR(10),
                	_keyword CHAR(50),                _matsNeeded CHAR(5))');
			$this->_GBMain->query('CREATE INDEX "units_code" ON "units" ("_code")');
			$this->_GBMain->query('CREATE INDEX "units_name" ON "units" ("_name")');
			$this->_GBMain->query('CREATE INDEX "units_StorItemCl" ON "units" ("_storageType_itemClass")');
			$this->_GBMain->query('CREATE INDEX "Uncommon" ON "units" ("_type", "_limitedEnd")');
			$this->_GBMain->query('CREATE INDEX "Display" ON "units" ("_type", "_name", "_display")');
			if( $_SESSION['userId'] == "") {  AddLog2("GB fail (userId unknown"); return "fail"; }
			AddLog2('Giftbox: Updating Units');
			$this->GB_gameSettings_SQL($_SESSION['flashRevision']);
			//AddLog2('Giftbox: Updating Locale Settings');
			//$this->GB_flashLocaleXml_SQL($_SESSION['flashRevision']);
		}
		return ;
	}

	//------------------------------------------------------------------------------
	// Qs for quoting the string = SQL quoting
	//------------------------------------------------------------------------------
	function Qs($temp) {  return "'" . $temp . "'";}
	//------------------------------------------------------------------------------
	// Statistics | add stat to file
	//------------------------------------------------------------------------------
	function GB_Stat3($code, $name, $amount, $action )
	{ // _code  _action  _name  _date _number
		$today = @date("Y-M-d");
		$GBSQL = "SELECT * FROM stats WHERE _code = '$code' AND _date = '$today' AND _action = '$action'" ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GB_result = $result->fetchAll(SQLITE_ASSOC);
			$NewNumber = $GB_result['0']['_number'] + $amount;
			$GBSQL ="UPDATE stats set _number=".$this->Qs($NewNumber)." WHERE _code = '$code' AND _date = '$today' AND _action = '$action'" ;
		}
		else
		{
			$GBSQL ="INSERT INTO stats(_code,_action,_name,_date,_number) VALUES(".$this->Qs($code).",".$this->Qs($action).",".$this->Qs($name).",".$this->Qs($today).",".$this->Qs($amount).")";
		}
		$this->_GBUser->query($GBSQL);
		return "OK";
	}

	//------------------------------------------------------------------------------
	// Add to SQL if exists in XML
	//------------------------------------------------------------------------------
	function GB_xml_1($type, $type_name , $xml_name, $code, $Item )
	{
		if($type == $type_name)
		{
			$temp = $Item->getElementsByTagName( $xml_name );
			if ($temp->length!=0)
			{
				$capacity = $this->Qs($temp->item(0)->nodeValue);
				$GBSQL ="UPDATE units SET _$xml_name = $capacity WHERE _code == $code ";
				$this->_GBMain->query($GBSQL);
			}
		}
	}
	//------------------------------------------------------------------------------
	// GB_get_World_storge_xml SQL version
	//------------------------------------------------------------------------------
	function GB_gameSettings_SQL()
	{

		$filelocal=$_SESSION['base_path'] . 'farmville-xml/'.$_SESSION['flashRevision'].'_items.xml';
		if (!file_exists($filelocal))
		{
			AddLog2("GiftBox: New XML File Not Found: " . $filelocal);
		}
		else
		{
			AddLog2("GiftBox: New items.xml Found Updating Database " . $_SESSION['flashRevision']);
		}
		// empty the all storages
		$GBSQL ="DELETE FROM units";
		$this->_GBMain->query($GBSQL);
		// empty the all storages
		$GBSQL ="DELETE FROM unitbuilding";
		$this->_GBMain->query($GBSQL);
		$this->_GBMain->query('BEGIN;');

		$xmlDoc = new DOMDocument();
		//$xmlDoc->load($file);
		$xmlDoc->load($filelocal);
		$Items = $xmlDoc->getElementsByTagName( "item" );
		$i = 0;
		foreach($Items as $Item)
		{
			$i++;
			if($Item->hasAttribute('code'))    { $code     = $this->Qs($Item->getAttribute('code')); }else{$code = "'NULL'";}
			if($Item->hasAttribute('name'))    { $itemname     = $this->Qs($Item->getAttribute('name')); }else{$itemname = "NULL";}
			if($Item->hasAttribute('giftable')){ $giftable = $this->Qs($Item->getAttribute('giftable')); }else{$giftable = "NULL";}
			if($Item->hasAttribute('type'))    { $type     = $this->Qs($Item->getAttribute('type')); }else{$type = "NULL";}
			if($Item->hasAttribute('subtype')) { $subtype  = $this->Qs($Item->getAttribute('subtype')); }else{$subtype = "NULL";}
			if($Item->hasAttribute('buyable')) { $buyable  = $this->Qs($Item->getAttribute('buyable')); }else{$buyable = "NULL";}
			if($Item->hasAttribute('placeable')) { $placeable  = $this->Qs($Item->getAttribute('placeable')); }else{$placeable = "NULL";}
			if($Item->hasAttribute('className')) { $className  = $this->Qs($Item->getAttribute('className')); }else{$className = "NULL";}
			$GBSQL ="INSERT INTO units(_code,_name,_giftable,_type,_subtype, _buyable, _placeable, _className )";
			$GBSQL.=" values($code,$itemname,$giftable,$type,$subtype, $buyable, $placeable, $className)";
			$this->_GBMain->query($GBSQL);

			$this->GB_xml_1($type, $type, "cash", $code, $Item );
			$this->GB_xml_1($type, $type, "cost", $code, $Item );
			$this->GB_xml_1($type, $type, "sizeX", $code, $Item );
			$this->GB_xml_1($type, $type, "sizeY", $code, $Item );
			$this->GB_xml_1($type, $type, "limitedStart", $code, $Item );
			$this->GB_xml_1($type, $type, "limitedEnd", $code, $Item );
			$this->GB_xml_1($type, $type, "requiredLevel", $code, $Item );
			$this->GB_xml_1($type, $type, "actionText", $code, $Item );
			$this->GB_xml_1($type, $type, "storageSize", $code, $Item );
			$this->GB_xml_1($type, $type, "limit", $code, $Item );
			$this->GB_xml_1($type, $type, "keyword", $code, $Item );     // added 2010-06-29
			$this->GB_xml_1($type, "'building'", "capacity", $code, $Item );
			$this->GB_xml_1($type, "'building'", "storageSize", $code, $Item );
			$this->GB_xml_1($type, "'building'", "expansion", $code, $Item );
			$this->GB_xml_1($type, "'building'", "matsNeeded", $code, $Item );
			$this->GB_xml_1($type, "'building'", "finishedName", $code, $Item );

			$images = $Item->getElementsByTagName( "image" );
			foreach( $images as $image )
			{
				if($image->hasAttribute('name')) { $name = $image->getAttribute('name'); }else{$name = "No";}
				if( $name == "icon")
				{
					if($image->hasAttribute('url'))  { $url  = $this->Qs($image->getAttribute('url')); }else{$url = "''";}
					$GBSQL ="UPDATE units SET iconurl = $url WHERE _code == $code ";
					$this->_GBMain->query($GBSQL);
				}
			}  // end foreach  $image


			if($className == "'BuildingPart'")
			{
				$components = $Item->getElementsByTagName( "component" );
				foreach( $components as $component )
				{
					$buildingTypes = $component->getElementsByTagName( "buildingType" );
					if ($buildingTypes->length!=0)
					{
						foreach($buildingTypes as $buildingType)
						{
							$buildingTypename = $this->Qs($buildingType->textContent);

							$GBSQL ="INSERT INTO unitbuilding( _component_for, _name, _itemcode)";
							$GBSQL.=" values($buildingTypename, $itemname, $code)";
							$this->_GBMain->query($GBSQL);
						}
					}
				}
			}

			if($type == "'building'")
			{
				$storageTypes = $Item->getElementsByTagName( "storageType" );
				foreach( $storageTypes as $storageType )
				{
					$itemNames = $storageType->getElementsByTagName( "itemName" );
					if ($itemNames->length!=0)
					{
						foreach($itemNames as $itemName)
						{ if($itemName->hasAttribute('need'))    { $need     = $this->Qs($itemName->getAttribute('need')); }else{$need = "'NULL'";}
						if($itemName->hasAttribute('limit'))    { $limit   = $this->Qs($itemName->getAttribute('limit')); }else{$limit = "'NULL'";}
						if($itemName->hasAttribute('part'))    { $part   = $this->Qs($itemName->getAttribute('part')); }else{$part = "'NULL'";}
						$Itemcodename = $this->Qs($itemName->textContent);
						$GBSQL ="INSERT INTO unitbuilding( _buildingcode, _itemcode, _name, _need, _limit, _part)";
						$GBSQL.=" values($code, 'NULL', $Itemcodename, $need, $limit, $part)";
						$this->_GBMain->query($GBSQL);
						}
					}  // itemcount < 1, so skip this building
				}  // end foreach  $storageTypes

				// added 2010-06-29 to detect itemClass
				if($type == "'building'")
				{
					$storageTypes = $Item->getElementsByTagName( "storageType" );
					foreach( $storageTypes as $storageType )
					{
						if ($storageType->hasAttribute('itemClass'))
						{
							$itemClass = $this->Qs($storageType->getAttribute('itemClass'));
							$GBSQL ="UPDATE units SET _storageType_itemClass = $itemClass WHERE _code == $code ";
							$this->_GBMain->query($GBSQL);
						}
					}
				}  // end foreach  $storageTypes

				$upgrades = $Item->getElementsByTagName( "upgrade" );
				foreach( $upgrades as $upgrade )
				{
					$upglevel = $this->Qs($upgrade->getAttribute('level'));
					$upgcapacity = $this->Qs($upgrade->getAttribute('capacity'));
					//Fix for Duck Pond
					if($code == '6z') $upgcapacity = 20;
					// let's store the levels and capacity for this building
					$GBSQL ="INSERT INTO unitbuilding( _buildingcode, _level, _capacity)";
					$GBSQL.=" values($code, $upglevel, $upgcapacity)";
					$this->_GBMain->query($GBSQL);

					$upgparts = $storageType->getElementsByTagName( "part" );
					if ($upgparts->length!=0)
					{
						foreach($upgparts as $upgpart)
						{
							if($itemName->hasAttribute('name')) { $upgpartname = $this->Qs($itemName->getAttribute('name')); }else{$upgpartname = "'NULL'";}
							if($itemName->hasAttribute('need')) { $upgpartneed = $this->Qs($itemName->getAttribute('need')); }else{$upgpartneed = "'NULL'";}
							$GBSQL ="INSERT INTO unitbuilding( _buildingcode, _itemcode, _name, _need, _limit, _part, _level, _capacity)";
							$GBSQL.=" values($code, 'NULL', $upgpartname, $upgpartneed, '', 'true', $upglevel, $upgcapacity)";
							$this->_GBMain->query($GBSQL);
						}
					}
				}  // end foreach  upgrade
			} // end building
		} // for each item
		// Update itmes with end date
		$GBSQL ="UPDATE units SET _display = 'Uncommon' WHERE _type = 'decoration' AND _limitedEnd IS NOT NULL";
		$this->_GBMain->query($GBSQL);
		// Update the Deco_Small
		$Items = array('flag', 'gnome', 'haybale', 'barrel', 'scarecrow', 'flower', 'bird', 'crate', 'grass' )  ;
		$TabName = 'Deco_Small';
		foreach ($Items as $Item)
		{
			$GBSQL ="UPDATE units SET _display = '".$TabName."' WHERE _name LIKE '%".$Item."%' AND _type = 'decoration' ";
			$this->_GBMain->query($GBSQL);
		}
		// Update the Deco_2
		$Items = array('mystery', 'bench', 'bike', 'fence', 'topiary', 'grab', 'hay', 'hedge', 'water', 'sign', 'bush', 'post', 'smal', 'teddy', '_deco' );
		$TabName = 'Deco_2';
		foreach ($Items as $Item)
		{
			$GBSQL ="UPDATE units SET _display = '".$TabName."' WHERE _name LIKE '%".$Item."%' AND _type = 'decoration' ";
			$this->_GBMain->query($GBSQL);
		}
		// Update the Uncommon  items
		$Items = array('master', 'cropcircl', 'ring', 'snow', 'nutcracker', 'ornament', 'firework', 'light', 'cotton', 'eifel', 'football', 'ice', 'spooky', 'soldier', 'nachos', 'mask' );
		$TabName = 'Uncommon';
		foreach ($Items as $Item)
		{
			$GBSQL ="UPDATE units SET _display = '".$TabName."' WHERE _name LIKE '%".$Item."%' AND _type = 'decoration' ";
			$this->_GBMain->query($GBSQL);
		}
		// Update the Special items
		$Items = array('tw_', 'easter_item', 'valentine_', 'potofgold_', 'present_' );
		$TabName = 'Specials';
		foreach ($Items as $Item)
		{
			$GBSQL ="UPDATE units SET _display = '".$TabName."' WHERE _name LIKE '%".$Item."%' AND _type = 'decoration' ";
			$this->_GBMain->query($GBSQL);
		}
		// Update the collection items
		$GBSQL ="UPDATE units SET _display = 'Collections' WHERE iconurl LIKE '%collect%' AND _type = 'decoration';";
		$GBSQL .="UPDATE units SET _display = 'Deco_rest' WHERE _type = 'decoration' AND _display IS NULL;";
		$this->_GBMain->query($GBSQL);
		// Done
		$this->_GBMain->query('COMMIT;');

		// update flash revision in database.
		$GBSQL ="INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES(".$this->Qs('flashversion').",".$this->Qs($_SESSION['flashRevision']).")";
		$this->_GBMain->query($GBSQL);
		AddLog2("GiftBox: XML Update - Saved: ".$i );

		// added 2010-09-09 new                                                       *****
		$filelocal= $_SESSION['base_path'] . 'farmville-xml/'.$_SESSION['flashRevision'].'_StorageConfig.xml';
		if (!file_exists($filelocal))
		{
			AddLog2("GiftBox: New XML File Not Found: " . $filelocal);
			return "Failed";
		}
		else
		{
			AddLog2("GiftBox: New StorageConfig.xml Found Updating Database " . $_SESSION['flashRevision']);
		}

		// empty the all StorageConfig
		$GBSQL ="DELETE FROM StorageConfig";
		$this->_GBMain->query($GBSQL);
		// lock the database for speed
		$this->_GBMain->query('BEGIN;');

		$xmlDoc = new DOMDocument();
		$xmlDoc->load($filelocal);  // update 2010-9-9
		$StorageBuildings = $xmlDoc->getElementsByTagName( "StorageBuilding" );
		$i = 0;
		foreach($StorageBuildings as $StorageBuilding)
		{
			$i++;
			if($StorageBuilding->hasAttribute('name'))    { $name = $this->Qs($StorageBuilding->getAttribute('name')); }else{$name = "'NULL'";}
			// check if there are allowKeyword
			$allowKeywords = $StorageBuilding->getElementsByTagName( "allowKeyword" );
			if ($allowKeywords->length!=0)
			{
				$allowKeyword  = $this->Qs($allowKeywords->item(0)->nodeValue);
			} else {$allowKeyword = "'-'";}
			// check if there are itemNames
			$itemNames = $StorageBuilding->getElementsByTagName( "itemName" );
			if ($itemNames->length!=0)
			{
				foreach($itemNames as $itemName)
				{
					if($itemName->hasAttribute('need'))    { $need     = $this->Qs($itemName->getAttribute('need')); }else{$need = "'0'";}
					if($itemName->hasAttribute('limit'))    { $limit   = $this->Qs($itemName->getAttribute('limit')); }else{$limit = "'0'";}
					if($itemName->hasAttribute('part'))    { $part   = $this->Qs($itemName->getAttribute('part')); }else{$part = "'0'";}

					$Itemcodename = $this->Qs($itemName->textContent);

					$GBSQL ="INSERT INTO StorageConfig( _name, _allowKeyword, _itemName, _need, _limit, _part)";
					$GBSQL.=" values($name, $allowKeyword, $Itemcodename, $need, $limit, $part)";
					$this->_GBMain->query($GBSQL);
				}
			}
			else
			{  // no itemnames but still need to write the building
				$GBSQL ="INSERT INTO StorageConfig( _name, _allowKeyword)";
				$GBSQL.=" values($name, $allowKeyword)";
				$this->_GBMain->query($GBSQL);
			}
		}// end foreach StorageBuilding
		$FeatureCreditStorages = $xmlDoc->getElementsByTagName( "FeatureCreditStorage" );
		foreach($FeatureCreditStorages as $FeatureCreditStorage)
		{
			$i++;
			if($FeatureCreditStorage->hasAttribute('name'))    { $name = $this->Qs($FeatureCreditStorage->getAttribute('name')); }else{$name = "'NULL'";}
			// check if there are itemNames
			$itemNames = $FeatureCreditStorage->getElementsByTagName( "itemName" );
			if ($itemNames->length!=0)
			{
				foreach($itemNames as $itemName)
				{
					$Itemcodename = $this->Qs($itemName->textContent);

					$GBSQL ="INSERT INTO StorageConfig( _name, _itemName)";
					$GBSQL.=" values($name, $Itemcodename)";
					$this->_GBMain->query($GBSQL);
				}
			} // end itemname
		} //end foreach FeatureCreditStorages

		// update all codes in the unitbuilding db
		$query = $this->_GBMain->query("SELECT _itemName FROM StorageConfig WHERE _itemcode == '0'");
		$result = $query->fetchAll(SQLITE_ASSOC);
		foreach ($result as $entry)
		{
			$GBSQL = "SELECT _code from units where _name == ".$this->Qs($entry['_itemName'])." limit 1";
			$result2 = $this->_GBMain->query($GBSQL);
			if ($result2->numRows() > 0) { $itemcodetemp = $this->Qs($result2->fetchSingle()); } else {$itemcodetemp = "'-'";}
			$GBSQL ="UPDATE StorageConfig SET _itemCode=$itemcodetemp WHERE _itemName = ".$this->Qs($entry['_itemName']);
			$this->_GBMain->query($GBSQL);
		}
		// write all changes into database
		$this->_GBMain->query('COMMIT;');
		AddLog2("GiftBox: StorageBuilding Update - Saved: ".$i );
	}

	//------------------------------------------------------------------------------
	//  Update user settings
	//------------------------------------------------------------------------------
	function GB_Update_User_Setting($setting , $valeu)
	{

		$GBSQL = "SELECT _val FROM gamesettings WHERE _set = '$setting' " ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{ $GBSQL ="UPDATE gamesettings set _val=".$this->Qs($valeu)." WHERE _set=".$this->Qs($setting)." "; }
		else
		{ $GBSQL ="INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES(".$this->Qs($setting).",".$this->Qs($valeu).")"; }
		$this->_GBUser->query($GBSQL);
		return "OK";
	}
	//------------------------------------------------------------------------------
	//  Get user settings
	//------------------------------------------------------------------------------
	function GB_Get_User_Setting($setting)
	{
		$GBSQL = "SELECT _val FROM gamesettings WHERE _set = '$setting' " ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GB_result = $result->fetchAll(SQLITE_ASSOC);
			return $GB_result['0']['_val'];
		}
		else
		{ return "Not Found"; }
		return "Fail";
	}
	//------------------------------------------------------------------------------
	//
	//------------------------------------------------------------------------------
	function GBuserQ($GBSQL)
	{
		$result1 = $this->_GBUser->query($GBSQL);
		if ($result1->numRows() > 0) { $GB_result = $result1->fetchAll(SQLITE_ASSOC); }else {$GB_result = array();}
		return $GB_result;
	}
	//------------------------------------------------------------------------------
	// GBSQLGetObjByID  = Get Object details by object ID
	//------------------------------------------------------------------------------
	function GBSQLGetObjByID($ObjID)
	{
		$GB_result = array();
		$GBSQL ="SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'id' AND _val = '". $ObjID ."')";
		$query = $this->_GBUser->query($GBSQL);
		while ($entry = $query->fetch(SQLITE_ASSOC))
		{
			$GB_result[$entry['_set']] = $entry['_val']  ;
			if($entry['_set'] == 'contents')       {$GB_result[$entry['_set']] = unserialize($entry['_val'])  ;}
			if($entry['_set'] == 'expansionParts') {$GB_result[$entry['_set']] = unserialize($entry['_val'])  ;}
			if($entry['_set'] == 'position')       {$GB_result[$entry['_set']] = unserialize($entry['_val'])  ;}
		}
		return $GB_result;
	}
	//------------------------------------------------------------------------------
	// GBSQLGetUnitByName  = Get Object details by object ID
	//------------------------------------------------------------------------------
	function GBSQLgetAction($code)
	{
		$GBSQL ="SELECT * FROM action WHERE _code = '". $code ."' LIMIT 1";
		$query = $this->_GBUser->query($GBSQL);
		$entry = $query->fetchAll(SQLITE_ASSOC)  ;
		if(array_key_exists("0", $entry))
		{ return $entry['0'];}
		else
		{ return array();}
	}
	//------------------------------------------------------------------------------
	// GBSQLGetUnitByName  = Get Object details by object ID
	//------------------------------------------------------------------------------
	function GBSQLGetUnitByName($name)
	{
		$GB_result = array();
		$GBSQL ="SELECT * FROM units WHERE _name = '". $name ."' LIMIT 1";
		$query = $this->_GBMain->query($GBSQL);
		$entry = $query->fetch(SQLITE_ASSOC)  ;
		return $entry;
	}
	//------------------------------------------------------------------------------
	// GBSQLGetUnitByCode  = Get Object details by object ID
	//------------------------------------------------------------------------------
	function GBSQLGetUnitByCode($code)
	{
		$GB_result = array();
		$GBSQL ="SELECT * FROM units WHERE _code = '". $code ."' LIMIT 1";
		$query = $this->_GBMain->query($GBSQL) ;
		$entry = $query->fetch(SQLITE_ASSOC)  ;
		return $entry;
	}

	function GB_SQL_updAction($field, $code, $val)
	{
		$GBSQL = "SELECT _code FROM action WHERE _code = '$code' " ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GBSQL ="UPDATE action set $field=".$this->Qs($val)." WHERE _code=".$this->Qs($code)." ";
		}
		else
		{
			$GBSQL ="INSERT INTO action(_code,".$field.") VALUES(".$this->Qs($code).",".$this->Qs($val).")";
		}
		$this->_GBUser->query($GBSQL);

		return "OK";
	}

	function GB_SQL_updActionCode($code, $val)
	{
		//                _code    _place_on_farm    _place_in_build
		//                _place_in_amount           _place_in_max
		//                _place_in_special         _target
		//                _selling    _keep         _construction     _a

		//          insert or replace INTO action (_code, _target) VALUES ('aa', "sss")
		$GBSQL = "INSERT OR REPLACE INTO action(_) VALUES ()";
		$this->_GBUser->query($GBSQL);

		// resource availible?
		if ($code == '') { AddLog2('GiftBox: Error - Code Empty'); return "'NULL'";}
		// all is good to go. lets insert this data
		// is setting already in DB? than we need to update the valeu.
		$GBSQL = "SELECT _val FROM action WHERE _code = '$code' " ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GBSQL ="UPDATE action set _val=".$this->Qs($val)." WHERE _code=".$this->Qs($code)." ";
		}
		else
		{
			$GBSQL ="INSERT INTO action(_code,_val) VALUES(".$this->Qs($code).",".$this->Qs($val).")";
		}
		//   AddLog2("xml SQL ".$GBSQL );
		$this->_GBUser->query($GBSQL);

		return "OK";
	}

	function GB_get_friendlyName($raw)
	{
		return Units_GetRealnameByName($raw);
	}

	function GB_DetectBuildingParts()
	{
		// remove all building parts from settings
		$GBSQL = "UPDATE action set _target='0',_construction='0' WHERE _construction != '0';";
		$this->_GBUser->query($GBSQL);
		// first look for all building that could contain building part
		$GBSQL = "SELECT DISTINCT _buildingcode FROM unitbuilding  WHERE _part = 'true'";
		$query = $this->_GBMain->query($GBSQL);
		$buildingcodes = $query->fetchAll()  ;
		$output = "";
		foreach($buildingcodes as $buildingcode)
		{
			$unit = $this->GBSQLGetUnitByCode($buildingcode['0']) ;
			// get frendly name
			$GBSQL = "SELECT _nice FROM 'locale' WHERE _raw = '". $unit['_name']. "_friendlyName' " ;
			$result = $this->_GBMain->query($GBSQL);
			$buildingname = $result->fetchSingle();
			// get all the objects.
			$GBSQL = "SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = '". $unit['_name']. "'" ;
			$result = $this->_GBUser->query($GBSQL);
			$Objectbuildings = $result->fetchAll();
			$output .=  '<br><b>' . $buildingname . "</b> <br>";
			foreach($Objectbuildings as $Objectbuilding)
			{
				$GBSQL = "SELECT _val FROM objects WHERE _set = 'expansionLevel' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$expansionLevel = $result->fetchSingle();
				if($expansionLevel == 5)
				{ // building is fully build
					$output .=  "<br>This building is fully build. <br>";
					continue;
				}

				$GBSQL = "SELECT _val FROM objects WHERE _set = 'id' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$ObjectID = $result->fetchAll();  //$ObjectID['0']['_val']

				$output .=  '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name</td>
           					<td class="helpHed">State</td><td class="helpHed">Code</td>
           					<td class="helpHed">Expansion Parts</td><td class="helpHed">Expansion Level</td></tr>';
				$GBSQL = "SELECT _val FROM objects WHERE _set = 'state' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$state = $result->fetchSingle();

				$GBSQL = "SELECT _val FROM objects WHERE _set = 'expansionParts' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$expansionParts = $result->fetchSingle();
				$expansionPart = "";
				if($expansionParts == 'a:0:{}' || $expansionParts == '')
				{ $expansionPart = ""; }
				else
				{
					$temps = unserialize($expansionParts) ;
					$expansionPartHave = array() ;
					foreach($temps as $key => $temp)
					{
						$expansionPartHave[$Objectbuilding['_obj']."".$key] = $temps[$key] ;   // !!!!!!!!!!!!!!!

					}
				}


				$GBSQL = "SELECT _val FROM objects WHERE _set = 'contents' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$contents = $result->fetchSingle();
				$content = "";
				if($contents == 'a:0:{}' || $contents == '')
				{ $content = ""; }
				else
				{
					$temps = unserialize($contents) ;
					//print_r($temps);
					foreach($temps as $temp)
					{
						$contentName = $this->GBSQLGetUnitByCode($temp['itemCode']) ;
						$content .= $temp['numItem'] . " " . $contentName['_name'] . " [" . $temp['itemCode'] ."]";
						$result = $this->_GBMain->query("SELECT _buildingcode FROM unitbuilding WHERE _itemcode = '".$temp['itemCode']."' AND _part = 'true'  ");
						if ($result->numRows() != 0)
						{
							$expansionPartHave[$Objectbuilding['_obj']."".$temp['itemCode']] = $temp['numItem'] ;  // !!!!!!!!!!!!!!!
						}
					}
				}


				$GBSQL = "SELECT _itemcode FROM unitbuilding WHERE _buildingcode = '".$buildingcode['0']."' AND _part = 'true'" ;
				$result = $this->_GBMain->query($GBSQL);
				$expansionPartsAll = $result->fetchAll();
				$expansionPart2 = "";
				foreach($expansionPartsAll as $expansionPartAll)
				{
					$expansionPartAllItemCode = $expansionPartAll['_itemcode'];

					if(array_key_exists($Objectbuilding['_obj']."".$expansionPartAllItemCode, $expansionPartHave))
					{
						$amount2 = $expansionPartHave[$Objectbuilding['_obj']."".$expansionPartAllItemCode];
					}
					else
					{
						$amount2 = 0;
					}
					$contentName = $this->GBSQLGetUnitByCode($expansionPartAllItemCode) ;
					$expansionPart2 .= $amount2 . " " .  $contentName['_name'] . " [" . $expansionPartAllItemCode ."]<br>";

					//////// NOW update the action table for this construction part.
					if($amount2 < 10)
					{
						$GBSQL = "INSERT OR REPLACE INTO action(_code, _target, _construction) VALUES (".$this->Qs($expansionPartAllItemCode).",".$this->Qs($ObjectID['0']['_val']).",".$this->Qs(10-$amount2)." );";
						$this->_GBUser->query($GBSQL);
					}

				}
				$output .=  '<tr><td>'.$buildingname.'</td><td>'.$state.'</td><td>'.$buildingcode['0'].'</td>';
				$output .=  '<td>'.$expansionPart2.'</td><td>'.$expansionLevel.'</td></tr>';
			}
			$output .=  '</table><br>';
		}

		return $output;

	}

	//------------------------------------------------------------------------------
	// new building parts database
	//
	//------------------------------------------------------------------------------

	function GB_BuildingParts4()
	{
		if (array_key_exists('ExclConstr' , $this->GB_Setting) )
		{
			$ExclConstr =  unserialize($this->GB_Setting['ExclConstr'])  ;
		} else{$ExclConstr = array();}

		//empty the database table.
		$GBSQL ="DELETE FROM BuildingParts";
		$this->_GBUser->query($GBSQL);

		// first check if all the building parts are in the table
		$GBSQL = "SELECT * FROM StorageConfig WHERE _part = 'true'";
		$query = $this->_GBMain->query($GBSQL);
		$buildingParts = $query->fetchAll();
		foreach($buildingParts as $buildingPart)
		{
			$GBSQL  = "INSERT OR REPLACE INTO BuildingParts(_name, _itemName, _itemCode, _need, _part) ";
			$GBSQL .= " VALUES (".$this->Qs($buildingPart['_name']).",".$this->Qs($buildingPart['_itemName']).",".$this->Qs($buildingPart['_itemCode']).",".$this->Qs($buildingPart['_need']).",".$this->Qs($buildingPart['_part']).");";
			$result = $this->_GBUser->query($GBSQL);
		}
		//  fill the Unit part.             _UnitBuildCode, _UnitBuildName,
		// first look for all building that could contain building part
		$GBSQL = "SELECT DISTINCT _name FROM BuildingParts";
		$query = $this->_GBUser->query($GBSQL);
		$UnitNames = $query->fetchAll();
		foreach($UnitNames as $UnitName)
		{
			$GBSQL = "SELECT DISTINCT  _code,_name,_storageType_itemClass FROM units WHERE _storageType_itemClass = '".$UnitName['_name']."'";
			$query = $this->_GBMain->query($GBSQL);
			$UnitInfo = $query->fetchAll();
			//got the info, now write into datebase
			$GBSQL ="UPDATE BuildingParts SET _UnitBuildCode=".$this->Qs(@$UnitInfo['0']['_code']).",_UnitBuildName=".$this->Qs(@$UnitInfo['0']['_name'])." WHERE _name = ".$this->Qs(@$UnitInfo['0']['_storageType_itemClass']);
			$query = $this->_GBUser->query($GBSQL);
		}
		// now check the objects on the farm. For now only 1 of each can be in construction.
		$GBSQL = "SELECT DISTINCT _UnitBuildName FROM BuildingParts";
		$query = $this->_GBUser->query($GBSQL);
		$UnitBuildNames = $query->fetchAll();
		foreach($UnitBuildNames as $UnitBuildName)
		{
			$ObjDatas = array();
			$GBSQL ="SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = '". $UnitBuildName['_UnitBuildName'] ."')";
			$query = $this->_GBUser->query($GBSQL);
			// check if there was a building
			if ($query->numRows() > 0){ $contineu = "ok";} else { continue;}  //skip if there was no object.
			// now put this into array
			while ($entry = $query->fetch(SQLITE_ASSOC))
			{
				$ObjDatas[$entry['_set']] = $entry['_val']  ;
				if($entry['_set'] == 'contents')       {$ObjDatas[$entry['_set']] = unserialize($entry['_val'])  ;}
				if($entry['_set'] == 'expansionParts') {$ObjDatas[$entry['_set']] = unserialize($entry['_val'])  ;}
				//if($entry['_set'] == 'position')       {$GB_result[$entry['_set']] = unserialize($entry['_val'])  ;}
			}
			//check the content of the object.
			if(is_array($ObjDatas['contents']))
			{ //  the contents
				foreach($ObjDatas['contents'] as $Content)
				{            //_ObjHave, _ObjId, _ObjState
					$GBSQL ="UPDATE BuildingParts SET _ObjHave=".$this->Qs($Content['numItem'])." WHERE _UnitBuildName = ".$this->Qs($ObjDatas['itemName'])." AND _itemCode = ".$this->Qs($Content['itemCode']);
					$query = $this->_GBUser->query($GBSQL);
				}// end contents
				$GBSQL ="UPDATE BuildingParts SET _ObjId=".$this->Qs($ObjDatas['id']).",_ObjState=".$this->Qs($ObjDatas['state'])." WHERE _UnitBuildName = ".$this->Qs($ObjDatas['itemName']);
				$query = $this->_GBUser->query($GBSQL);

			}
			// check if horsestablewhite is level 5, than do not add more parts.
			$DoConstruct = 'Y';
			if (array_key_exists('expansionLevel' , $ObjDatas) )
			{
				if( $ObjDatas['expansionLevel'] >= 5){ $DoConstruct = 'N';}
			}

			// now check if there is any building part in the database,
			// if so, that means it is a construction building :-)
			$GBSQL = "select sum(_ObjHave) AS total FROM BuildingParts WHERE _UnitBuildName = ".$this->Qs($ObjDatas['itemName']);
			$query = $this->_GBUser->query($GBSQL);
			$TotalBuildingParts = $query->fetchAll()  ;
			if($TotalBuildingParts['0']['total'] > 0 && $DoConstruct == 'Y')
			{
				$GBSQL ="UPDATE BuildingParts SET _action='construction' WHERE _UnitBuildName = ".$this->Qs($ObjDatas['itemName']);
				$query = $this->_GBUser->query($GBSQL);

			}
			// now check if the building is on the Eclude list
			if (array_key_exists($ObjDatas['itemName'] , $ExclConstr) )
			{
				if($ExclConstr[$ObjDatas['itemName']] == 'Exclude')
				{
					$GBSQL ="UPDATE BuildingParts SET _action='Exclude' WHERE _UnitBuildName = ".$this->Qs($ObjDatas['itemName']);
					$query = $this->_GBUser->query($GBSQL);
				}
			}
		}

	}

	function GB_DetectBuildingParts4()
	{
		// Check that all building part are known in the action list.
		$GBSQL = "SELECT _itemCode FROM StorageConfig WHERE _part = 'true'";
		$query = $this->_GBMain->query($GBSQL);
		$buildingParts = $query->fetchAll();
		foreach($buildingParts as $buildingPart)
		{
			$GBSQL = "SELECT * FROM action WHERE _code = ".$this->Qs($buildingPart['_itemCode']) ;
			$result = $this->_GBUser->query($GBSQL);
			if ($result->numRows() > 0)
			{
				$GBSQL ="UPDATE action set _construction='Y', _target = '0' WHERE _code = ".$this->Qs($buildingPart['_itemCode']) ;
			}
			else
			{
				$GBSQL  = "INSERT INTO action(_code, _target, _construction) ";
				$GBSQL .= " VALUES (".$this->Qs($buildingPart['_itemCode']).",'0','Y' );";
			}
			$this->_GBUser->query($GBSQL);
		} // all building part now in database.
	}


	function GB_DetectBuildingParts3()
	{
		// Check that all building part are known in the action list.
		$GBSQL = "SELECT _itemCode FROM StorageConfig WHERE _part = 'true'";
		$query = $this->_GBMain->query($GBSQL);
		$buildingParts = $query->fetchAll();
		foreach($buildingParts as $buildingPart)
		{
			$GBSQL  = "INSERT OR REPLACE INTO action(_code, _target, _construction) ";
			$GBSQL .= " VALUES (".$this->Qs($buildingPart['_itemCode']).",'0','Y' );";
			$result = $this->_GBUser->query($GBSQL);
		} // all building part now in database.

		// first look for all building that could contain building part
		$GBSQL = "SELECT DISTINCT  _name FROM StorageConfig WHERE _part = 'true'";
		$query = $this->_GBMain->query($GBSQL);
		$buildingNames = $query->fetchAll();
		$output = "";
		foreach($buildingNames as $buildingName)
		{
			$GBSQL = "SELECT DISTINCT  _code,_name FROM units WHERE _storageType_itemClass = '".$buildingName['_name']."'";
			$query = $this->_GBMain->query($GBSQL);
			$buildingcode = $query->fetchAll();
			$unit = $this->GBSQLGetUnitByCode($buildingcode['0']['_code']) ;
			// get frendly name
			$GBSQL = "SELECT _nice FROM 'locale' WHERE _raw = '". $unit['_name']. "_friendlyName' " ;
			$result = $this->_GBMain->query($GBSQL);
			$buildingname = $result->fetchSingle();
			// get all the objects.
			$GBSQL = "SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = '". $unit['_name']. "'" ;
			$result = $this->_GBUser->query($GBSQL);
			$Objectbuildings = $result->fetchAll();
			//get the building ID

			$output .=  '<br><b>' . $buildingname . "</b> <br>";
			foreach($Objectbuildings as $Objectbuilding)
			{
				$GBSQL = "SELECT _val FROM objects WHERE _set = 'expansionLevel' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$expansionLevel = $result->fetchSingle();
				if($expansionLevel == 5)
				{ // building is fully build
					$output .=  "<br>This building is fully build. <br>";
					continue;
				}

				$GBSQL = "SELECT _val FROM objects WHERE _set = 'id' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$ObjectID = $result->fetchAll();  //$ObjectID['0']['_val']

				$output .=  '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name</td>
           					<td class="helpHed">State</td><td class="helpHed">Code</td>
           					<td class="helpHed">Expansion Parts</td><td class="helpHed">Expansion Level</td></tr>';
				$GBSQL = "SELECT _val FROM objects WHERE _set = 'state' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$state = $result->fetchSingle();
				//echo "ee:". print_r($state);

				$GBSQL = "SELECT _val FROM objects WHERE _set = 'expansionParts' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$expansionParts = $result->fetchSingle();
				$expansionPart = "";
				if($expansionParts == 'a:0:{}' || $expansionParts == '')
				{ $expansionPart = ""; }
				else
				{
					$temps = unserialize($expansionParts) ;
					$expansionPartHave = array() ;
					foreach($temps as $key => $temp)
					{
						$expansionPartHave[$Objectbuilding['_obj']."".$key] = $temps[$key] ;   // !!!!!!!!!!!!!!!
					}
				}

				// look into the contents
				$GBSQL = "SELECT _val FROM objects WHERE _set = 'contents' AND _obj = '". $Objectbuilding['_obj']. "'" ;
				$result = $this->_GBUser->query($GBSQL);
				$contents = $result->fetchSingle();
				$content = "";
				if($contents == 'a:0:{}' || $contents == '')
				{ $content = ""; }
				else
				{
					$temps = unserialize($contents) ;
					foreach($temps as $temp)
					{
						$contentName = $this->GBSQLGetUnitByCode($temp['itemCode']) ;
						$content .= $temp['numItem'] . " " . $contentName['_name'] . " [" . $temp['itemCode'] ."]";
						// check if this is a building part or not.
						$result = $this->_GBMain->query("SELECT _name FROM StorageConfig WHERE _itemCode = '".$temp['itemCode']."' AND _part = 'true'  ");
						if ($result->numRows() != 0)
						{
							$expansionPartHave[$Objectbuilding['_obj']."".$temp['itemCode']] = $temp['numItem'] ;  // !!!!!!!!!!!!!!!
						}
					}
				}

				// now look into all posible parts .. make sure that item with 0 in content.
				$GBSQL = "SELECT _itemCode FROM StorageConfig WHERE _name = '".$buildingName['_name']."' AND _part = 'true'" ;
				$result = $this->_GBMain->query($GBSQL);
				$expansionPartsAll = $result->fetchAll()  ;
				$expansionPart2 = "";
				foreach($expansionPartsAll as $expansionPartAll)
				{
					$expansionPartAllItemCode = $expansionPartAll['_itemCode'];
					if(array_key_exists($Objectbuilding['_obj']."".$expansionPartAllItemCode, $expansionPartHave))
					{ $amount2 = $expansionPartHave[$Objectbuilding['_obj']."".$expansionPartAllItemCode]; }
					else
					{ $amount2 = 0;}
					$contentName = $this->GBSQLGetUnitByCode($expansionPartAllItemCode) ;
					$expansionPart2 .= $amount2 . " " .  $contentName['_name'] . " [" . $expansionPartAllItemCode ."]<br>";
					//////// NOW update the action table for this construction part.
					if($amount2 < 10)
					{

						$GBSQL ="UPDATE action set _code=".$this->Qs($expansionPartAllItemCode).",_target=".$this->Qs($ObjectID['0']['_val']).",_construction=".$this->Qs(10-$amount2)." WHERE _code=".$this->Qs($expansionPartAllItemCode)." ";
						$this->_GBUser->query($GBSQL);
					}

				}
				$output .=  '<tr><td>'.$buildingname.'</td><td>'.$state.'</td><td>'.$buildingcode['0'].'</td>';
				$output .=  '<td>'.$expansionPart2.'</td><td>'.$expansionLevel.'</td></tr>';
			}
			$output .=  '</table><br>';
		}

		return $output;

	}



	function GB_DetectSpecials()
	{

		$GB_specials = explode(';', file_get_contents('plugins/GiftBox/specials.txt'));
		$output =  '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name Special</td>
           <td class="helpHed">Code Special</td>
           <td class="helpHed">Object ID</td>
           <td class="helpHed">This can go into this Special</td></tr>';

		foreach($GB_specials as $GB_special)
		{
			//echo '<br>Begin check: ' . $GB_special . "<br>";
			$GBSQL = "SELECT _obj from objects WHERE _set = 'itemName' AND _val = '".$GB_special."'";
			$result =$this->_GBUser->query($GBSQL);
			$Objectbuildings = $result->fetchSingle();
			if($Objectbuildings == '') {$ObjectbuildHave= 'Not found on farm'; }else {$ObjectbuildHave= ''; }

			$GBSQL = "SELECT _val from objects WHERE _obj = '".$Objectbuildings."' AND _set = 'id'";
			$result = $this->_GBMain->query($GBSQL);
			$ObjId = $result->fetchSingle();
			if($ObjId == '') {$ObjId = 0;}
			$GBSQL = "SELECT _code from units WHERE _name = '".$GB_special."'";
			$result = $this->_GBMain->query($GBSQL);
			$buildingcode = $result->fetchSingle();
			$GBSQL = "SELECT _itemcode from unitbuilding WHERE _buildingcode = '".$buildingcode."'";
			$result = $this->_GBMain->query($GBSQL);
			$itemcodes = $result->fetchAll();
			$content ='';
			foreach($itemcodes as $itemcode)
			{
				$contentName = $this->GBSQLGetUnitByCode($itemcode['_itemcode']) ;
				$content .= $this->GB_get_friendlyName($contentName['_name']) . " [" . $itemcode['_itemcode'] ."]<br>";

				$GBSQL = "INSERT OR REPLACE INTO action(_code, _place_in_special) VALUES (".$this->Qs($itemcode['_itemcode']).",".$this->Qs($ObjId)." );";
				$this->_GBUser->query($GBSQL);
			}
			$output .=  '<tr><td>'.$GB_special.'</td><td>'.$buildingcode.'</td>';
			$output .=  '<td>'.$ObjId.'<br>'.$ObjectbuildHave.'</td><td>'.$content.'</td></tr>';

		}
		$output .=  '</table><br>';

		return $output;

	}



	function GB_DetectSpecials2()
	{
		$SQL ='';
		$output =  '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name Special</td>
                     <td class="helpHed">On farm?</td>
                     <td class="helpHed">Object ID</td>
                     <td class="helpHed">This can go into this Special</td></tr>';
		$GBSQL = "SELECT * FROM units WHERE _capacity = '100' AND _name != 'holidaytree' AND _name != 'chickencoop5' ";
		$result = $this->_GBMain->query($GBSQL);
		$GB_specials = $result->fetchAll();
		foreach($GB_specials as $GB_special)
		{
			// special = name of the special building
			$GBSQL = "SELECT _obj from objects WHERE _set = 'itemName' AND _val = '".$GB_special['_name']."'";
			$result = $this->_GBUser->query($GBSQL);
			$Objectbuildings = $result->fetchAll();
			if(empty($Objectbuildings))
			{ // building does not exist.
				$output .=  '<tr><td>'.$GB_special['_name'].'</td><td>Is not on the farm</td>';
				$output .=  '<td> - </td><td> - </td></tr>';
			}
			else
			{ // we have the special building on the farm

				$GBSQL = "SELECT _val from objects WHERE _obj = '".$Objectbuildings['0']['_obj']."' AND _set = 'id'";
				$result = $this->_GBUser->query($GBSQL);
				$ObjectID = $result->fetchAll();


				$content ='';
				$GBSQL ="SELECT _itemCode FROM StorageConfig WHERE _name = '".$GB_special['_storageType_itemClass']."'";
				$result1 = $this->_GBMain->query($GBSQL);
				if ($result1->numRows() > 0) { $Items = $result1->fetchAll(); }else {$Items = array();}
				foreach($Items as $Item)
				{
					$content .=  " [" . $Item['_itemCode'] ."]<br>";
					$this->GB_SQL_updAction("_place_in_special", $Item['_itemCode'], "999"); //$field, $code, $val
					$this->GB_SQL_updAction("_target",           $Item['_itemCode'], $ObjectID['0']['_val']); //$field, $code, $val
					$SQL .=$GBSQL . "<br>";
				}
				$output .=  '<tr><td>'.$GB_special['_name'].'</td><td>Is on the farm</td>';
				$output .=  '<td>'.$Objectbuildings['0']['_obj'].'<br>'.$ObjectID['0']['_val'].'</td><td>'.$content.'</td></tr>';
			}
		} // for each special
		$output .=  '</table><br>';
		//$output .= $SQL;
		return $output;
	}


	//------------------------------------------------------------------------------
	//  Detect Collections and update the action list
	//------------------------------------------------------------------------------
	function GB_DetectCollections()
	{
		$GB_CollectionList = GB_GetCollectionList();
		if (!$GB_CollectionList) { AddLog2("GiftBox: Error - Collection List Missing" );  return; }
		foreach($GB_CollectionList as $value)
		{
			$GB_amount_Coll = count($value['collectable']);
			$i=0;
			while($i < $GB_amount_Coll)
			{
				$code = $value['collectable'][$i];
				$this->GB_SQL_updAction("_collection", $code, 'Y');
				$i++;
			}
		}
	}

	//------------------------------------------------------------------------------
	//  Detect Collections and update the action list
	//------------------------------------------------------------------------------
	function GB_DetectBuildWI($entry, $GB_url)
	{
		$UnitCapacity = '';
		$output = '<br>';
		$GBObj = $this->GBSQLGetObjByID($entry['_storagecode']);
		$GBunit = $this->GBSQLGetUnitByName($GBObj['itemName']);
		$Storage = $entry['_storagecode'];
		$Target = $GBObj; //($Storage, $objects);
		if(!array_key_exists('isFullyBuilt', $Target)){$Target['isFullyBuilt'] = "N";}
		if( $Target['isFullyBuilt'] == "1"   )
		{
			$GBunit = $this->GBSQLGetUnitByName($GBObj['itemName']);
			$UnitCapacity = $GBunit['_capacity'];
			if(array_key_exists('expansionLevel', $Target))
			{
				$level = $Target['expansionLevel'];
				if($level > 1)
				{
					$GBSQL = "SELECT _capacity FROM unitbuilding WHERE _level = '". $level. "' AND _buildingcode = '". $GBunit['_code']. "' " ;
					$result = $this->_GBMain->query($GBSQL);
					$UnitCapacity = $result->fetchSingle();
				}
			} else {$level = 0;}
			$output .= '<br><b>' . GBHead($this->GB_get_friendlyName($GBObj['itemName'])) . "</b> State: " . $Target['state'] . " Code: ". $GBunit['_code'] . "  Capacity: ". $UnitCapacity . "  level: " . $level . "  id: " . $Target['id']. "<br>";
			$output .= '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Image</td><td class="helpHed">Amount</td><td class="helpHed">Name</td><td class="helpHed">Maximum</td><td class="helpHed">Amount to put in the '.$Target['itemName'].'</td></tr>';
			$output .= '<form action="'. GiftBox_URL .'" >';   //?url=settings_buildwi
			$output .= '<input name="url" type="hidden" value="'. $GB_url . '" >';
			$GBSQL = "SELECT _itemcode FROM unitbuilding WHERE _buildingcode = '". $GBunit['_code']. "' " ;
			$query = $this->_GBMain->query($GBSQL);
			$GB_AllItemPosible = $query->fetchAll(SQLITE_ASSOC)  ;
			$ItemPNumMax = 0;
			foreach ($GB_AllItemPosible as $ItemP2)
			{
				// Set defaults
				$ItemInputVal = 0 ;
				$ItemPNum = 0;
				$ItemP = $ItemP2['_itemcode'];
				$GBaction = $this->GBSQLgetAction($ItemP);

				// check if there is data for this building
				if(array_key_exists("_target", $GBaction))
				{ // data exist Check that it is this building
					if($GBaction['_target'] == $Target['id'] )
					{ // Yes, it is this building
						$ItemInputVal = $GBaction['_place_in_build'];
					}
				}
				$ItemInput =  '<input name="PTval" type="text" size="3" maxlength="3"  value="'. $ItemInputVal . '" >';
				$ItemInput .=  '<input name="PTcode" type="hidden" value="'. $ItemP . '" >';
				$ItemInput .=  '<input name="PTobj" type="hidden" value="'. $Target['id'] . '" >';
				$ItemInput .=  '<input name="PTmax" type="hidden" value="'. $UnitCapacity . '" >';
				$ObjD = $this->GBSQLGetUnitByCode($ItemP);
				foreach($GBObj['contents'] as $contents) { if($contents['itemCode'] == $ItemP) {$ItemPNum = $contents['numItem'];} }
				$GB_displ_name = $this->GB_get_friendlyName($ObjD['_name']) . '<br>[ ' . $ObjD['_name'] . ' ' . $ObjD['_code'] . ' ]';

				$ItemPNumMax = $ItemPNumMax + $ItemPNum;
				$output .= '<tr><td>'.GB_ShowIMG($ObjD).'</td><td>'. $ItemPNum .'</td><td>'.$GB_displ_name.'</td><td>max '.$UnitCapacity.'</td><td>'.$ItemInput.'</td></tr>';
			}
			$output .= '<tr><td></td><td></td><td>Currently in this building:</td><td>'.$ItemPNumMax.'</td><td>';
			$output .= '<input type="submit" name="submitPlaceThis"  value="Save" />';
			$output .= '<input type="submit" name="submitPlaceThis"  value="Set_All_999" />';
			$output .= '</form>';
			$output .= '</td></tr>';
			$output .= '</table>';
		}// if construction
		$output2['output'] = $output;
		$output2['Capacity'] = $UnitCapacity;
		$output2['NumbItems'] = $ItemPNumMax;
		return $output2;

	}

	function GB_getSQLsetting()
	{
		$GBSQL = "SELECT _val,_set FROM gamesettings" ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GB_results = $result->fetchAll();
			foreach($GB_results as $GB_result)
			{
				$this->GB_Setting[$GB_result['_set']] = $GB_result['_val'];
			}
		}
		return $this->GB_Setting;
	}

	function GB_import_action($Task, $file)
	{
		//   $Task =  'ADD'   ==> Add items to Action DB
		//   $Task =  'SHOW' ==> show actions and return them.
		//   $Task =  'ERROR' ==> Check for Error's and return them.
		$file = basename($file);
		$status = "";
		$show = "";
		//predefine inputs
		$VARY0 = array("Y", "0");
		$GB_imports = file($_SESSION['this_plugin']['folder'].'/actions/'.$file); //'plugins/GiftBox/actions.txt'
		if($GB_imports)
		{
			$status .= '<br>';
			$status .= 'Import actions file found.<br>';
			foreach($GB_imports as $GB_import)
			{
				$skip = 'N';
				$error = '&nbsp;';
				$GB_import = explode(':', $GB_import, 15);
				if (strpos($GB_import['0'], '#') !== false)
				{
					$comment = $GB_import['0'] ;
					//echo 'Comment: ' .$comment . "<br>";
				} else {
					// let's fill the empty input
					$i = 0;
					while($i < 14) { if (!isset($GB_import[$i])) {$GB_import[$i] = ""; } $i++;}
					// detect the codes
					$code    = $GB_import['0'] ; if(strlen($code) != 2) { $skip = 'Y'; $error = " Code is not 2 digits";}
					$show .= '<tr><td>'.$code.'</td>' ;
					if($Task == 'SHOW'){ $Unit= $this->GBSQLGetUnitByCode($code); $show .= '<td>'.$Unit['_name'].'</td>' ;}

					$place   = $GB_import['1'] ; if(!in_array($place, $VARY0)){ $skip = 'Y'; $error = " Place is not Y or 0"; }
					$show .= '<td>'.$place.'</td>' ;
					$sell    = $GB_import['2'] ;  if(!in_array($sell, $VARY0))    { $skip = 'Y'; $error = " Sell is not Y or 0";}
					$show .= '<td>'.$sell.'</td>' ;
					$keep    = $GB_import['3'] ;  if(!is_numeric($keep) ){ $skip = 'Y'; $error = " Keep is not a number";}
					$show .= '<td>'.$keep.'</td>' ;
					$consume = $GB_import['4'] ;  if(!in_array($consume, $VARY0)) { $skip = 'Y'; $error = " Consume is not Y or 0";}
					$show .= '<td>'.$consume.'</td>' ;
					$a5      = $GB_import['5'] ;
					$a6      = $GB_import['6'] ;
					$a7      = $GB_import['7'] ;
					$a8      = $GB_import['8'] ;
					$a9      = $GB_import['9'] ;
					$a10     = $GB_import['10'] ;
					$a11     = $GB_import['11'] ;
					$a12     = $GB_import['12'] ;
					$note    = $GB_import['13'] ;
					$show .= '<td>'.$error.'</td>' ;
					$show .= '<td>'.$note.'</td>' ;

					if($skip == 'Y')
					{ $status .= 'Error detected in input '.$code. ' Error:' .$error. '<br>';
					$show .= '<td> This line will not be imported into the actions</td>' ;
					}else
					{ $show .= '<td> will be imported</td>' ;}

					if($Task == 'ADD')
					{  // we need to add the codes into the actions.
						if($skip == 'N')
						{ // Code is good
							$GBSQL  ="INSERT OR REPLACE INTO action(_code,_place_on_farm,_selling,_keep,_consume) VALUES";
							$GBSQL .="('".$code."','".$place."','".$sell."','".$keep."','".$consume."')";
							$this->_GBUser->query($GBSQL);
						} // end skip
					} // end ADD
				}// not a comment
			} //foreach
		}
		else
		{
			$status .= "File Not found";
		}
		if($Task == 'SHOW'){return $show;}
		return $status;
	}

	function GB_export_action($Task, $filename)
	{
		//   $Task =  'EXPORT' ==>
		//   $Task =  'SHOW'   ==>
		$file  = '##################################################################
#  export file
##################################################################
#  Format of the lines:
#Code
#| Place on farm? Y/0
#| | Sell? Y/0
#| | | Keep
#| | | | Consume? Y/0
#| | | | | reserved
#| | | | | | reserved
#| | | | | | | reserved
#| | | | | | | | reserved
#| | | | | | | | | reserved
#| | | | | | | | | | reserved
#| | | | | | | | | | | reserved
#0 1 2 3 4 5 6 7 8 9 0 | reserved
#| | | | | | | | | | | | |                                
';
		$screen  = '';
		$screen .= '<table width="90%" class="sofT" cellspacing="0">';
		$screen .= '<tr><td class="helpHed">Code</td>
              <td class="helpHed">Item Name:</td>
              <td class="helpHed">Place?</td>
              <td class="helpHed">Sell?</td>
              <td class="helpHed">Keep?</td>
              <td class="helpHed">Consume?</td>
              <td class="helpHed">Notes:</td></tr>';
		$GBSQL = "SELECT * FROM action WHERE _target = '0' AND _place_in_special = '0' AND _construction = '0' AND _collection = '0'";
		$query = $this->_GBUser->query($GBSQL);
		$actions = $query->fetchAll();
		foreach($actions as $action)
		{
			$output  = '<tr>';
			$output .= '<td>'.$action['_code'].'</td>'; //  code
			$Unit= Units_GetUnitByCode($action['_code']);
			$output .= '<td>'.$Unit['name'].'</td>' ;  //  name
			$output .= '<td>'.$action['_place_on_farm'].'</td>'; // place
			$output .= '<td>'.$action['_selling'].'</td>'; // sell
			$output .= '<td>'.$action['_keep'].'</td>'; // keep
			$output .= '<td>'.$action['_consume'].'</td>'; // consume
			if($action['_place_on_farm'] == '0' && $action['_selling'] == '0' &&  $action['_keep'] == '0' && $action['_consume']  == '0' )
			{ $output .= '<td>Will not be exported. NO action defined</td>'; }
			else
			{
				$output .= '<td>Will be exported</td>';
				$file .= $action['_code'].':'.$action['_place_on_farm'].':'.$action['_selling'].':'.$action['_keep'].':'.$action['_consume'].':0:0:0:0:0:0:0:0:item name '.$Unit['name'].":\r\n";
			}
			$output .= '</tr>'; // end
			$screen .= $output ;
		}
		$screen .= '</table>';
		if($Task == 'EXPORT')
		{
			$f = fopen($filename, "w+");
			fputs($f, $file, strlen($file));
			fclose($f);
			return 'done';
		}
		return $screen;
	}

	function GB_FindPetsSQL()
	{
		$i = 0;
		$GB_Pets = array();
		$GBSQL ="SELECT _set,_val,_obj FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'className' AND _val = 'Pet')";
		$query = $this->_GBUser->query($GBSQL);
		while ($entry = $query->fetch(SQLITE_ASSOC))
		{
			$GB_Pets[$entry['_obj']][$entry['_set']] = $entry['_val']  ;
			if($entry['_set'] == 'position')       {$GB_Pets[$entry['_obj']][$entry['_set']] = unserialize($entry['_val'])  ;}
			// What does it need to eat? Treat or Kibble?
			if ($entry['_set'] == 'petLevel')
			{
				$GB_Pets[$entry['_obj']]['FeedWhat'] = "treat";
				if($entry['_val']  == 0) { $GB_Pets[$entry['_obj']]['FeedWhat'] = "kibble"; }
			}
			// Feet time
			if($entry['_set'] == 'lastFedTime')
			{
				$FeedTime = $entry['_val'] / 1000;
				$FeedTime = $FeedTime + 86401 ;
				$GB_Pets[$entry['_obj']]['feedtime'] = $FeedTime;
			}
			$i++;
		}
		return $GB_Pets;
	}

	//*****************************************************************************
	// garage
	// $what = html
	// $what = hook
	function GB_garage($what)
	{

		$return = array();
		$n = 0;
		$return['0']['vehicle'] = $n;
		$return['0']['id'] = 0 ;
		$html = '';

		$GBSQL = "SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = 'garage_finished'" ;
		$result = $this->_GBUser->query($GBSQL);
		$Objectbuildings = $result->fetchAll();
		if ($result->numRows() == 0)
		{
			$html .= 'No garage found on the farm<br>';
		}
		else
		{
			$html .=   '<br><b>Garage found on farm</b> <br>';
			// Get the data from this object.
			$GBSQL = "SELECT _set,_val FROM objects WHERE _obj = '". $Objectbuildings['0']['_obj']. "'" ;
			$query = $this->_GBUser->query($GBSQL);
			while ($entry = $query->fetch(SQLITE_ASSOC))
			{
				$TargetObject[$entry['_set']] = $entry['_val']  ;
				if($entry['_set'] == 'contents')       {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
				if($entry['_set'] == 'expansionParts') {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
				if($entry['_set'] == 'position')       {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
			}
			if(!array_key_exists('isFullyBuilt', $TargetObject)){$TargetObject['isFullyBuilt'] = "N";}
			if( $TargetObject['isFullyBuilt'] == "1"   )
			{
				$return['0']['id'] = $TargetObject['id'] ;
				foreach($TargetObject['contents'] as $vehicle )
				{
					$n++;
					$return['0']['vehicle'] = $n;
					$Vneedmax = 32;
					if($vehicle['numParts'] >= $Vneedmax)
					{
						$html .=  'Garage contains ' . $vehicle['itemCode'] . ' fully upgraded ('. $vehicle['numParts'] .' parts)<br>';
					}
					else
					{
						$html .=  'Garage contains ' . $vehicle['itemCode'] . ' With ' . $vehicle['numParts'] . ' vehicle parts. Need ' . ($Vneedmax-$vehicle['numParts']) . ' more parts<br>';
						$return[$n] = array('itemCode'=> $vehicle['itemCode'], 'numParts' => $vehicle['numParts'] , 'need' => ($Vneedmax-$vehicle['numParts']));
					}
				}
			}
			else
			{
				$html .=  'No garage is not fully build<br>';
				$return['vehicle'] = 0;
			}

		}
		if ($what == 'html' )
		{
			return $html;
		}
		else
		{
			return $return;
		}

	}

	function GB_renew_giftbox_SQL()
	{

		$GB_ingiftbox_temp = array();
		AddLog2("GiftBox: Updating");
		$res = 0;
		//load settings
		DoInit();

		// now let's get tthe giftbox
		$GB_ingiftbox_temp = unserialize(fBGetDataStore('giftbox'));
		if(is_array($GB_ingiftbox_temp))
		{
			// empty the giftbox
			$GBSQL ="DELETE FROM giftbox";
			$this->_GBUser->query($GBSQL);
			foreach ($GB_ingiftbox_temp as $key => $giftboxItem )
			{
				$amount = $this->Qs($giftboxItem[0]);
				$itemcode = $this->Qs($key);
				$GBSQL ="INSERT INTO giftbox(_itemcode, _amount, _gifters, _orig )";
				$GBSQL.=" values($itemcode, $amount, '', 'GB')";
				$this->_GBUser->query($GBSQL);
			}
			AddLog2("GiftBox: GiftBox Contents Saved");
		} else { AddLog2("GB SQL ERROR update =can not find giftbox="); }
		// now look for ConsumableBox
		$GB_ingiftbox_temp = unserialize(fBGetDataStore('consumebox'));
		if(is_array($GB_ingiftbox_temp))
		{
			foreach ($GB_ingiftbox_temp as $key => $giftboxItem )
			{
				$amount = $this->Qs($giftboxItem[0]);
				$itemcode = $this->Qs($key);
				$GBSQL ="INSERT INTO giftbox(_itemcode, _amount, _gifters, _orig )";
				$GBSQL.=" values($itemcode, $amount, '', 'CB')";
				$this->_GBUser->query($GBSQL);
			}
			AddLog2("GiftBox: ConsumableBox Contents Saved");
		} else { AddLog2("GB SQL ERROR update =can not find consumable box="); }

		// now let's get all storages
		$GB_AllStorages_temp = array();
		$GB_AllStorages_temp = unserialize(fBGetDataStore('storagedata'));
		if(is_array($GB_AllStorages_temp))
		{ // empty the all storages
			$GBSQL ="DELETE FROM totstorage";
			$this->_GBUser->query($GBSQL);
			while (list($GB_storageCode, $stor_array) = each($GB_AllStorages_temp))
			{ $storagecode = $this->Qs($GB_storageCode);
			foreach($stor_array as $key => $value)
			{
				$amount = @$this->Qs($value[0]);
				$itemcode = $this->Qs($key);
				$GBSQL ="INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
				$GBSQL.=" values($storagecode, $itemcode, $amount, '')";
				$this->_GBUser->query($GBSQL);
			}
			}
			AddLog2("GiftBox: AllStorage Contents Saved");
		} else { AddLog2("AllStorage ERROR - Not saved "); }

		// now add the storage data (cellar)
		$GB_ingiftbox_temp = unserialize(fBGetDataStore('storagebox'));
		if(is_array($GB_ingiftbox_temp))
		{
			foreach ($GB_ingiftbox_temp as $key => $giftboxItem )
			{
				$amount = $this->Qs($giftboxItem[0]);
				$itemcode = $this->Qs($key);
				$GBSQL ="INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
				$GBSQL.=" values('storage', $itemcode, $amount, '')";
				$this->_GBUser->query($GBSQL);
			}
			AddLog2("GiftBox: Storage Contents Saved");
		} else { AddLog2("GB SQL ERROR update =can not find storage ="); }

		// get the featureCredits (like valetine & pot of gold)
		$GB_featureCredits_temp = unserialize(fBGetDataStore('featurecred'));
		if(is_array($GB_featureCredits_temp))
		{
			$this->_GBUser->query('BEGIN;');
			foreach($GB_featureCredits_temp as $key => $value)
			{
				$storagecode = $this->Qs($key);
				$current = $this->Qs($value['current']);
				$received = $this->Qs($value['received']);
				$GBSQL ="INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
				$GBSQL.=" values($storagecode, 'current', $current, '')";
				$this->_GBUser->query($GBSQL);
				$GBSQL ="INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
				$GBSQL.=" values($storagecode, 'received', $received, '')";
				$this->_GBUser->query($GBSQL);
			}
			$this->_GBUser->query('COMMIT;');
			AddLog2("GiftBox: Feature Credits Saved");
		} else { AddLog2("featureCredits ERROR - Not saved "); }

		// Store all objects
		$objects =  unserialize((fBGetDataStore('objects')));
		if(is_array($objects))
		{
			$GBSQL ="DELETE FROM objects";
			$this->_GBUser->query($GBSQL);
			$this->_GBUser->query('BEGIN;');

			foreach($objects as $id => $object)
			{
				$obj = $this->Qs($id);
				$GBloop = array_keys($object);
				foreach($GBloop as $set1 )
				{
					$set = $this->Qs($set1)  ;
					if(is_array($object[$set1]))
					{ // handle the array like position & content
						$val = $this->Qs(serialize($object[$set1]))  ;
					}
					else
					{
						$val = $this->Qs(addslashes($object[$set1]))  ;
					}
					if($set == "'message'") {$val = "'message text'";}
					$GBSQL ="INSERT INTO objects(_obj, _set, _val) VALUES($obj, $set, $val)";
					$this->_GBUser->query($GBSQL);

				}
			}
			$this->_GBUser->query('COMMIT;');
			AddLog2("GiftBox: Objects Updated");
		} else { AddLog2("objects ERROR - Not saved ");}
		return $res;
	}

	//------------------------------------------------------------------------------
	// Giftbox main function in hook
	//------------------------------------------------------------------------------
	function Giftbox()
	{
		AddLog2('GiftBox: Start');
		$T = time(true);
		// begin SQL setup
		$this->GBDBuser_init("Hook");
		$this->GB_renew_giftbox_SQL();
		// end SQL setup

		// Get the settings
		$GBSQL = "SELECT _val,_set FROM gamesettings" ;
		$result = $this->_GBUser->query($GBSQL);
		if ($result->numRows() > 0)
		{
			$GB_results = $result->fetchAll();
			foreach($GB_results as $GB_result) { $this->GB_Setting[$GB_result['_set']] = $GB_result['_val']; }
		}
		// import default actions?
		$this->GB_AutoActionFile();

		// if place is on, than let's update the locations to find empty locations
		if($this->GB_Setting['DoPlace']) { $this->GBCreateMap(); }
		AddLog2('GiftBox: Detecting Building Parts');
		$this->GB_DetectBuildingParts4();
		$this->GB_BuildingParts4();

		if($this->GB_Setting['RunPlugin']){
			//check the cellar for storage.
			if ($this->GB_Setting['DoStorage']){ $this->GB_checkCellar();}
			// check this amount of items in giftbox.
			$result1 = $this->_GBUser->query("SELECT SUM(_amount) FROM giftbox");
			if ($result1->numRows() > 0) { $GB_total_in_giftbox = $result1->fetchSingle(); }else {$GB_total_in_giftbox = 0;}
			AddLog2( "GiftBox: Items Counted - " . $GB_total_in_giftbox );
			global $GB_tempid;
			if($GB_tempid == "")$GB_tempid = 63000;
			//get the collection info.
			$GBccount = array();
			$GBccount = GB_LoadCcount();

			$GB_changed = false ; //true when we did action.
			$MAP_ObjectArray = array();
			$Map_all_items = array();
			$MapXY = array();
			$EmptyXY = array();

			if ($this->GB_Setting['DoFeetPet'])
			{
				AddLog2( "GiftBox: Detecting Pet(s)");
				$GB_Pets = $this->GB_FindPetsSQL();
				$found = count(array_keys($GB_Pets));
				AddLog2( "GiftBox: Found $found Pet(s)" );
				if($found > 0)
				{
					foreach ($GB_Pets as $GB_Petfeed)
					{
						if($GB_Petfeed['isRunAway'] ==1)
						{
							AddLog2('GiftBox: ' . $GB_Petfeed['petName'] . ' Has Ran Away' );
						}
						else
						{
							$FeedWhat = $GB_Petfeed['FeedWhat'];
							$FeedName = $GB_Petfeed['petName'];
							AddLog2('GiftBox: ' . $FeedName . ' Needs: ' . $FeedWhat . ' '. nicetime($GB_Petfeed['feedtime']) );
						}
					}
				}
			} // DoFeetPet

			// load the totstorage.
			$GBSQL = "SELECT * FROM totstorage ";
			$query = $this->_GBUser->query($GBSQL);
			$totstorages = $query->fetchAll();
			if(!is_array($totstorages)){$totstorages = array();}

			// load the giftbox...
			$GBSQL = "SELECT * FROM giftbox ";
			$query = $this->_GBUser->query($GBSQL);
			$giftboxs = $query->fetchAll();


			foreach ($giftboxs as $giftbox)
			{
				$GB_ItemCode = $giftbox["_itemcode"];
				$GB_ItemAmount = $giftbox["_amount"];
				// if items = 0 then skip this item.
				if($GB_ItemAmount < 1){continue;}
				$place_on_farm = '';        $place_in_build = '';     $place_in_amount = '';      $place_in_max = '';
				$place_in_special = '';     $target = '';             $selling = '';              $keep = 0;
				$construction = '';         $collection = '';         $consume = '';

				//  Let's check if there is action for this item.
				$GBSQL = "SELECT * FROM action WHERE _code = '".$GB_ItemCode."'";
				$query = $this->_GBUser->query($GBSQL);
				$action = $query->fetchAll();
				if (!empty($action))
				{ // so there is action.
					$place_on_farm = $action['0']['_place_on_farm'];
					$place_in_build = $action['0']['_place_in_build'];
					$place_in_amount = $action['0']['_place_in_amount'];
					$place_in_max = $action['0']['_place_in_max'];
					$place_in_special = $action['0']['_place_in_special'];
					$target = $action['0']['_target'];
					$selling = $action['0']['_selling'];
					$construction = $action['0']['_construction'];
					$keep = $action['0']['_keep'];
					$collection = $action['0']['_collection'];
					$consume = $action['0']['_consume'];
				}
				// prepare Unit settings
				$GBSQL = "SELECT * FROM units WHERE _code = '".$GB_ItemCode."' ";
				$query = $this->_GBMain->query($GBSQL);
				$Unit = $query->fetchAll();
				// Get the realname
				$Unit['0']['realname'] = $this->GB_get_friendlyName($Unit['0']['_name']);

				// check if we need to feed pet
				$FeedNowCheck = false;
				$PetFeedFound = "";
				if($GB_ItemCode == "0O" ){$FeedNowCheck = true; $PetFeedFound = "Puppy Kibble ";}
				if($GB_ItemCode == "0z" ){$FeedNowCheck = true; $PetFeedFound = "Dog Treat ";}
				if($FeedNowCheck == true && $this->GB_Setting['DoFeetPet'])
				{
					AddLog('GiftBox: ' . $GB_ItemAmount . " " . $PetFeedFound . " Found - Checking If Needed" );
					if( $GB_ItemAmount > 0)
					{ // loop all pets
						foreach ($GB_Pets as $GB_Petfeed)
						{
							if($GB_Petfeed['isRunAway'] ==1)
							{
								AddLog2('GiftBox: ' . $GB_Petfeed['petName'] . ' Has Ran Away - Cannot Feed' ); }
								else
								{$FeedWhat = $GB_Petfeed['FeedWhat'];
								$FeedName = $GB_Petfeed['petName'];
								$FeedNow = true;
								if($FeedWhat == "kibble" && $GB_ItemCode == "0z"){AddLog2("GiftBox: This Puppy Does Not Need Dog Treats.."); $FeedNow = false;}
								if($FeedWhat == "treat"  && $GB_ItemCode == "0O"){AddLog2("GiftBox: This Dog Does Not Need Kibble.."); $FeedNow = false;}
								if($FeedNow == true)
								{
									$UnixNow = time();
									if($UnixNow > $GB_Petfeed['feedtime'] )
									{ // need feed now
										$FeedWhat = $GB_Petfeed['FeedWhat'];
											
										$result = GB_consumePet($GB_Petfeed['id'], "consume_" . $FeedWhat)  ;
										AddLog2( "GiftBox: Feeding " . $GB_Petfeed['petName'] . " " . $FeedWhat . '- ' . $result );
										if ($result == "OK")
										{
											AddLog2("GiftBox: Kibble Has Been Fed");
											$GB_changed = true ; // giftbox changed
											$this->GB_Stat3($GB_ItemCode, "Pet feed", $GB_ItemAmount, "Pet Feed" );
											//GB_Stat($GB_ItemCode, "Kibble feed", 0, 0, $GB_ItemAmount,0 );
										}
										else { $giftboxs = array(); break; }
									}
									else
									{AddLog2( $GB_Petfeed['petName'] . ' Needs to be Fed '. nicetime($GB_Petfeed['feedtime']) ); }
								}
								}
						}
					}
				} // End pet feet.
				// check target settings global.
				if ( $target != 0 )
				{
					$Target = $this->GBSQLGetObjByID($target);
					$TotItems = 0;
					$TargetItemHave = 0;
					// map the content of the target and find the total items have
					if(is_array($Target['contents']))
					{ // count the contents
						foreach($Target['contents'] as $content)
						{
							$TotItems = $TotItems + $content['numItem'] ;
							$TargetCont[$content['itemCode']] = $content['numItem'];
							if($GB_ItemCode == $content['itemCode']){$TargetItemHave = $content['numItem'];}
						}
					} // end contents
					// now check if the item is in totstorage.
					$featureCreditsName = 'N';
					if($Target['itemName'] == 'valentinesbox'){$featureCreditsName = 'valentine';}
					if($Target['itemName'] == 'potofgold'){$featureCreditsName = 'potOfGold';}
					if($Target['itemName'] == 'easterbasket'){$featureCreditsName = 'easterBasket';}
					if($Target['itemName'] == 'wedding'){$featureCreditsName = 'tuscanWedding';}
					if($Target['itemName'] == 'beehive_finished'){$featureCreditsName = 'beehive';}
					if($Target['itemName'] == 'hatchstorage')    {$featureCreditsName = 'InventoryCellar';}  //added
					if($featureCreditsName != 'N')
					{
						$GBSQL = "SELECT * FROM totstorage WHERE _storagecode = '".$featureCreditsName."' AND _itemcode = 'current'";
						$query = $this->_GBUser->query($GBSQL);
						if ($query->numRows() > 0)
						{
							$totstorage = $query->fetchAll();
							$TotItems = $totstorages['0']['_amount'];
						}
					}
					// Get target unit details.
					$GBSQL = "SELECT * FROM units WHERE _name = '". $Target['itemName']. "' " ;
					$result = $this->_GBMain->query($GBSQL);
					$TargetUnit = $result->fetchAll();
					// check capacity of target
					$TargetCapacity = 0;
					if(!array_key_exists('isFullyBuilt', $Target)){$Target['isFullyBuilt'] = "N";}
					if( $Target['isFullyBuilt'] == "1"   )
					{
						$TargetCapacity = $TargetUnit['0']['_capacity'];
						if(array_key_exists('expansionLevel', $Target))
						{
							$level = $Target['expansionLevel'];
							if($level > 1)
							{
								$GBSQL = "SELECT _capacity FROM unitbuilding WHERE _level = '". $level. "' AND _buildingcode = '". $TargetUnit['0']['_code']. "' " ;
								$result = $this->_GBMain->query($GBSQL);
								$TargetCapacity = $result->fetchSingle();
							}
						} else {$level = 0;}
					}// end fully build
					// check if building is in construction
					$TargetIsConstruction = 'N';
					if($Target['state'] == 'construction')
					{
						$TargetIsConstruction = 'Y';
					}
					else
					{ // check if it is a horsestable
						if($Target['itemName'] == 'horsestablewhite')
						{ // check if horse stable has expansionParts
							if( count(array_keys($Target['expansionParts'])) > 0 )
							{ // yes, we have  expansionParts
								$TargetIsConstruction = 'Y';
							}
							if($TargetItemHave > 0 )
							{ // yes, we have  expansionParts
								$TargetIsConstruction = 'Y';
							}
						}
					}
				} // End check target


				//check if we can store this item.
				if ($this->GB_Setting['DoStorage'] )
				{
					$Able2Store = GB_CanWeStore($Unit['0']);
					if($this->GB_Setting['StorageLocation'] == 'N')
					{
						$Able2Store = 'N';
					}
					else
					{
						if($this->GB_Setting['StorageUsed'] >= $this->GB_Setting['StorageCapacity'])
						{ // storage is full.
							$Able2Store = 'N';
							AddLog2("GiftBox: Storage is Full?" );
						}
					}
					if ($this->GB_Setting['DoStorage'] && $Able2Store == 'Y' && $GB_ItemAmount > 0 )
					{ // check content of storage
						AddLog2("GiftBox: Entering Storage Routine For: " . $Unit['0']['realname'] );
						$cellars = unserialize($this->GB_Setting['StorageContent2']);
						$AmountInCellar = 0;
						if(array_key_exists($GB_ItemCode, $cellars)) {
							$AmountInCellar = $cellars[$GB_ItemCode]; }
							AddLog2("GiftBox: Store ". $GB_ItemAmount ." ". $Unit['0']['realname'] . '?');
							if($this->GB_Setting['DoStorage1'] && $AmountInCellar < 1)
							{
								$Amount2Store = 1;
								AddLog2("GiftBox: Store ". $Amount2Store ." ". $Unit['0']['realname']);
							}
							else
							{
								AddLog2("GiftBox: Store ". $Unit['0']['realname']." Already in Storage" ); }

								if($Amount2Store > 0)
								{
									$result = GB_StoreCel($Unit['0'], $Amount2Store) ;
									if ($result == "OK") {
										AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - Added");
										$GB_changed = true ; // giftbox changed
										$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Fuel added" );
										$GB_ItemAmount = $GB_ItemAmount - $Amount2Store;
									} else { $giftboxs = array(); break; }
								}
								else { AddLog2("GiftBox: No Need to Store This Item.");}

					} // end stor
				}


				// check if we can use the item to store into a building
				if($place_in_build != 0 && $target != 0 && $this->GB_Setting['DoPlaceBuild'] && $GB_ItemAmount > 0)
				{                                                                        //$place_in_max
					AddLog2('GiftBox ' . $Target['itemName'] . ' - Capacity: ' . $TargetCapacity . ' - Have: ' .$TotItems );
					$finished = false;
					if($TotItems >= $TargetCapacity)
					{ // The building is full lets skip this item.
						AddLog2("GiftBox: This Building is Full - Skipping" );
						$finished = true;
					}
					while ( ! $finished )
					{
						$result = GB_storeItem($Unit['0'] , $Target);
						if($result == "OK")
						{  $GB_changed = true ;
						//update the amount.
						AddLog2('GiftBox: Placed in Building ' . $Unit['0']['_name'] . ' - Total in Building: ' .$TotItems );
						$this->GB_SQL_updAction("_place_in_build", $GB_ItemCode, $place_in_build - $GB_ItemAmount); //$field, $code, $val
						//update stats.
						$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Into building" );
						$TotItems++;
						$GB_ItemAmount--;
						} else { $finished = true; $giftboxs = array(); break; }
						if($TotItems >= $TargetCapacity){$finished = true;}
						if($GB_ItemAmount < 1)          {$finished = true;}

					} // not full
				}//end place in build

				// check if we have to place construction in a building
				// construction = # or Y    0 = not construction
				if($construction == 'Y'  && $this->GB_Setting['DoConstr'] && $GB_ItemAmount > 0)
				{
					$go = $GB_ItemAmount;
					$used = 0;
					while($go > 0)
					{
						AddLog2('GiftBox: Construction Part '. $Unit['0']['_name'].' Found  ' . $go);
						$GBSQL ="SELECT * FROM BuildingParts WHERE _itemCode = ".$this->Qs($GB_ItemCode)." AND _action ='construction' AND _ObjHave < 10 LIMIT ".$go;
						$query = $this->_GBUser->query($GBSQL);
						if ($query->numRows() > 0)
						{
							$BuildingParts = $query->fetchAll();
							foreach($BuildingParts as $BuildingPart)
							{// 1 or more targets found
								$Target = $this->GBSQLGetObjByID($BuildingPart['_ObjId']);
								AddLog2('GiftBox: Part: '. $BuildingPart['_itemName'].' For ' . $BuildingPart['_UnitBuildName']. " - Contains: " . $BuildingPart['_ObjHave'] . " - Adding 1");
								$result = GB_storeItem($Unit['0'] , $Target);
								if($result == "OK")
								{
									$GB_changed = true ;
									$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Construction" );
									//update the amount have
									$have = $BuildingPart['_ObjHave'] +1;
									$GBSQL ="UPDATE BuildingParts SET _ObjHave=".$this->Qs($have)." WHERE _UnitBuildName = ".$this->Qs($BuildingPart['_UnitBuildName'])." AND _itemCode = ".$this->Qs($GB_ItemCode);
									$query = $this->_GBUser->query($GBSQL);

									$go--; $used++;
								} else { $go = 0; $giftboxs = array(); break; }
							}
						}
						else
						{ //skip there are no target buildings
							AddLog2('GiftBox: Construction Part Not Needed. ('. $GB_ItemCode . ')');
							$go = 0;
						}
					}// while $GB_ItemAmount > 1
					$GB_ItemAmount = $GB_ItemAmount - $used;
				}// constructions

				// check if we have Special to handle
				if ($place_in_special != 0 && $target != 0 && $this->GB_Setting['DoSpecials'] && $GB_ItemAmount > 0 )
				{
					AddLog2('GiftBox: Special ' . $TargetUnit['0']['_name']. " For " . $Target['itemName']);

					// for specials there is no max.
					$result = GB_storeItem2($Unit['0'] , $Target, $GB_ItemAmount);
					if($result == "OK")
					{
						//GB_SpecialThisUpdate($GB_ItemCode, $GB_ItemAmount);
						$GB_changed = true ;
						$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Special" );
						$GB_ItemAmount = 0;
					} else { $giftboxs = array(); break; }
				}
				// check if we have collection
				if ($collection == 'Y' && $this->GB_Setting['DoColl'] && $GB_ItemAmount > 0)
				{
					$Amount_in_Collection = GB_GetColInfo($GB_ItemCode, $GBccount);
					// Check if we have less than 10
					if($Amount_in_Collection < 10 && $GB_ItemAmount > 0 )
					{
						if($Amount_in_Collection + $GB_ItemAmount <= 10)
						{ $Amount_to_add = $GB_ItemAmount; } else { $Amount_to_add = 10 - $Amount_in_Collection; }
						AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['_name'] . " - Have " . $Amount_in_Collection . " Will Add " . $Amount_to_add . " To Collection");
						$result = GB_DoColAdd($Unit['0']['_name'] , $GB_ItemCode, $Amount_to_add);
						if ($result == "OK")
						{
							AddLog2($Amount_to_add ." ". $Unit['0']['_name'] . " Added To Collection");
							$GB_changed = true ;
							$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Added collection" );
							$Amount_in_Collection = $Amount_in_Collection + $Amount_to_add;
							$GB_ItemAmount = $GB_ItemAmount - $Amount_to_add ;
						} else { $giftboxs = array(); break; }
					} // end < 10
					if($Amount_in_Collection >= 10 && $GB_ItemAmount > 0) {  //we have already 10
						if($this->GB_Setting['DoCollSell']){
							AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['_name'] . " - Have " . $Amount_in_Collection . " Will Sell Now");
							$result = GB_DoSellCol($Unit['0'] , $GB_ItemAmount)  ;
							if ($result == "OK") {
								AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " Sold");
								$GB_changed = true ;
								$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Sold collection" );
							} else { $giftboxs = array(); break; }
						}else{
							AddLog2("GiftBox: " . $GB_ItemAmount ." ". $ObjD['realname'] . " - Have " . $Amount_in_Collection . " Selling Disabled");
						} // end do_sell
					}     // end more 10.
				} // end collection


				// Place on farm
				if ($place_on_farm == 'Y' && $this->GB_Setting['DoPlace'] && $GB_ItemAmount > 0 )
				{
					AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - Place on Farm");
					$GB_Where = "Decoration";
					if($Unit['0']['_type'] == "animal"){$GB_Where = "Animal";}
					if($Unit['0']['_type'] == "tree")  {$GB_Where = "Tree";}
					//check if there is place on the farm.
					$GB_Free_place = $this->TEmptyXY3($GB_Where, "ALL");
					if($GB_Free_place < $GB_ItemAmount )
					{
						AddLog2("****** Error *****");
						AddLog2("GiftBox: There is no room on you farm left.");
						AddLog2("GiftBox: To place: " . $GB_ItemAmount ." ". $Unit['0']['realname'] );
						AddLog2("****** Error *****");
					}
					else
					{
						$result = GB_PlaceM3($Unit['0'], $GB_ItemAmount, $GB_Where);
						if ($result == "OK") {
							AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " Placed");
							$GB_changed = true ; // giftbox changed
							$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Placed" );
							$GB_ItemAmount = 0;
						} else { $giftboxs = array(); break; }
					}
				}   // end place

				if ($Unit['0']['_type'] == 'fuel' && $this->GB_Setting['DoFuel'] && $GB_ItemAmount > 0 )
				{ // selling fuel enabled   >> GB_BuyFuel($ObjD , $GB_amount)

					$result = GB_BuyFuel($Unit['0'] , $GB_ItemAmount)  ;
					AddLog2("GiftBox ". $GB_ItemAmount ." ". $Unit['0']['realname']." Fuel found - " . $result );
					if ($result == "OK") {
						AddLog2("GiftBox " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " Added");
						$GB_changed = true ; // giftbox changed
						$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Fuel added" );
						$GB_ItemAmount = 0;
					} else { $giftboxs = array(); break; }
				} // end fuel


				// consumable
				if ( $consume == 'Y' && $GB_ItemAmount > 0 )
				{
					$GBAction_Amount = $GB_ItemAmount - $keep;
					if($GBAction_Amount >= 1 )
					{

						$result = GB_consume($Unit['0'] , $GBAction_Amount)  ;
						AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " Consume - Leave in GiftBox: " . $keep . ' - ' . $result);
						$need_reload = true;
						if ($result == "OK") {
							AddLog2("GiftBox: " . $GBAction_Amount ." ". $Unit['0']['realname'] . " Consumed");
							$GB_changed = true ; // giftbox changed
							$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Consume" );
							$GB_ItemAmount = 0;
						} else { DoInit(); $giftboxs = array(); break; }
					}else { AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - Keep in Giftbox: " . $keep);}
				}   // end consumable

				// DoVehicle
				if ( $this->GB_Setting['DoVehicle'] && $GB_ItemCode == "dS" && $GB_ItemAmount > 0 )
				{
					$GBAction_Amount = $GB_ItemAmount - $keep;
					$vehicle = $this->GB_garage('hook');
					$AmountOfRuns = $vehicle['0']['vehicle'];
					AddLog2("GiftBox: Have ".$GB_ItemAmount." Vehicle Parts - " . $vehicle['0']['vehicle'] . " Vehicles Need Parts" );
					while($AmountOfRuns >= 1 && $GBAction_Amount > 1)
					{
						$needpart = $vehicle[$AmountOfRuns]['need'];
						AddLog2("Giftbox: Vehicle " . $vehicle[$AmountOfRuns]['itemCode'] . " Needs " . $needpart . " Parts" );
						while($needpart >=1 && $GBAction_Amount > 1)
						{
							$result = GB_DoGarage($vehicle[$AmountOfRuns] , $vehicle['0']['id'])  ;
							if ($result == "OK") {
								$vehicle[$AmountOfRuns]['numParts'] = $vehicle[$AmountOfRuns]['numParts']+1;
								$needpart--;
								AddLog2("GiftBox: 1 " . $Unit['0']['realname'] . " Added, Need ". $needpart . ' More');
								$GB_changed = true ; // giftbox changed
								$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Added" );
								$GBAction_Amount--;
								$GB_ItemAmount--;
							} else { $giftboxs = array(); break; }
						}
						$AmountOfRuns--;
					}
				}   // end DoVehicle


				// check if we can open this.
				$GB_OpenArray = unserialize($this->GB_Setting['OpenItems']);
				if(in_array($GB_ItemCode, $GB_OpenArray)) {$GB_OpenThis = 'Y';} else {$GB_OpenThis = 'N';}
				if ($this->GB_Setting['DoMystery'] && $GB_ItemAmount > 0 && $GB_OpenThis == 'Y')
				{
					$GBAction_Amount = $GB_ItemAmount - $keep;
					if($GBAction_Amount >= 1 )
					{
						$result = GB_OpenGift($Unit['0'] , $GBAction_Amount);
						AddLog2("GiftBox: Open " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - " . $result);
						$need_reload = true;
						if ($result == "OK") {
							AddLog2("GiftBox: Opened " . $GBAction_Amount ." ". $Unit['0']['realname'] . " - Done");
							$GB_changed = true ; // giftbox changed
							$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Opened" );
							$GB_ItemAmount = 0;
						} else { $giftboxs = array(); break; }
					}else { AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - Keep in GiftBox: " . $keep);}
				}   // end open



				if ($selling == 'Y' && $this->GB_Setting['DoSelling'] && $GB_ItemAmount > 0 )
				{
					$GBAction_Amount = $GB_ItemAmount - $keep;
					if($GBAction_Amount >= 1 )
					{

						$result = GB_DoSellCol($Unit['0'] , $GBAction_Amount)  ;
						AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " Will be Sold - Leave in GiftBox: " . $keep . ' - ' . $result);
						$need_reload = true;
						if ($result == "OK") {
							AddLog2("GiftBox: " . $GBAction_Amount ." ". $Unit['0']['realname'] . " Sold");
							$GB_changed = true ; // giftbox changed
							$this->GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Sold" );
							$GB_ItemAmount = 0;
						} else { $giftboxs = array(); break; }
					}else {AddLog2("GiftBox: " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " - Keep in GiftBox: " . $keep);}
				} // end Do_SellList



			} //end foreach giftbox
			// after giftbox, see the collection trade in.
			if($this->GB_Setting['DoCollTrade'])
			{    // now look to complete collections.
				AddLog2("Collection trade in set to: ". $this->GB_Setting['DoCollKeep']);
				$GB_CollCompete_res = GB_CollCompete();  // get the information
				while (list($GB_CollCode, $amount) = each($GB_CollCompete_res) )
				{
					AddLog2("Collection Info: [". $GB_CollCode ."] has" . $amount . " completed" );
					$GB_Tradein_amount = $amount - $this->GB_Setting['DoCollKeep'];
					if($GB_Tradein_amount > 0)
					{
						$GB_TradeIn_res = GB_TradeIn($GB_CollCode, $GB_Tradein_amount);
						AddLog2("Collection Trade In: [". $GB_CollCode ."] " . $GB_Tradein_amount . " Time(s)" );
						$this->GB_Stat3($GB_CollCode, "Collection", $GB_Tradein_amount, "Trade in" );
						$GB_changed = true ;
					}
				}
			}// end complete collection



		} else { AddLog2( "Skipping GiftBox..." ); }
		if($GB_changed)
		{
			$res = $this->GB_renew_giftbox_SQL();     // update giftbox. so it shows correctly in the screen.
		} else {
			$T2 = time();
			$T2 -= $T;
			AddLog2( "Giftbox: Finished in " . $T2 . " Seconds" );
		}
	}  // end function

	function GB_checkCellar()
	{
		$TotCapacity = 0;
		$this->GB_Setting['StorageUsed'] = 0;
		$this->GB_Setting['StorageCapacity'] = 0;
		$this->GB_Setting['StorageContent'] = array();
		$this->GB_Setting['StorageLocation'] = 'N';
		//check if there is a cellar & what is the content.
		$GBSQL = "SELECT * FROM totstorage WHERE _storagecode = 'InventoryCellar' AND _itemcode = 'current'";
		$query = $this->_GBUser->query($GBSQL);
		if ($query->numRows() > 0)
		{
			$totstorage = $query->fetchAll();
			$TotItems = $totstorage['0']['_amount'];
			if($TotItems > 500){$TotCapacity = 500;}else{$TotCapacity = $TotItems;}
		}
		AddLog2("GiftBox: Storage Capacity: " . $TotCapacity);
		$this->GB_Setting['StorageCapacity'] = $TotCapacity;
		if($TotCapacity > 0)
		{
			$GBSQL = "SELECT SUM(_amount) as total FROM totstorage WHERE _storagecode = 'storage'";
			$query = $this->_GBUser->query($GBSQL);
			if ($query->numRows() > 0)
			{
				$Result = $query->fetchAll()  ;
				AddLog2("GiftBox: Total Items in Storage: " .$Result['0']['total']);
				$this->GB_Setting['StorageUsed'] = $Result['0']['total'];

			}

			$GBSQL = "SELECT _itemcode,_amount FROM totstorage WHERE _storagecode = 'storage'";
			$query = $this->_GBUser->query($GBSQL);
			if ($query->numRows() > 0)
			{
				$Results = $query->fetchAll();
				$StorageContentTemp = array();
				foreach($Results as $Result)
				{
					$StorageContentTemp[$Result['_itemcode']] = $Result['_amount'];}
					$this->GB_Setting['StorageContent2'] = serialize($StorageContentTemp);
					$this->GB_Setting['StorageContent']  = serialize($Results);
			}

			$GBSQL ="SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = 'hatchstorage')";
			$query = $this->_GBUser->query($GBSQL);
			while ($entry = $query->fetch(SQLITE_ASSOC))
			{
				if($entry['_set'] == 'position')
				{
					$this->GB_Setting['StorageLocation'] = $entry['_val'];
				}
			}
		}

	}
	function GB_AutoActionFile()
	{
		$filename = 'default_actions.txt';
		$filename = $_SESSION['this_plugin']['folder'].'/actions/'.$filename ;
		if (file_exists($filename))
		{ $fileMtime = filemtime($filename);}
		else
		{AddLog2("No default action file found.");
		return 'No';}

		if(array_key_exists('ActionFileTime' , $this->GB_Setting))
		{$fileLast = $this->GB_Setting['ActionFileTime'];}
		else
		{$fileLast = '0';}

		if($fileMtime > $fileLast)
		{
			AddLog2("GB ===== Default action file found.");
			AddLog2("GB ===== Default action file is newer than last time.");
			AddLog2("GB ===== will import Default action file now.");
			$status = $this->GB_import_action('ADD', $filename);
			AddLog2("GB ===== Import status." . $status);
			$this->GB_Update_User_Setting("ActionFileTime" , $fileMtime);
		}
		return;
	}
	function GBCreateMap()
	{
		$logtext ="";
		$ObjTemps = array();
		// Get all objects
		$GBSQL = "SELECT _obj,_set,_val from objects";
		$result = $this->_GBUser->query($GBSQL);
		$Objects = $result->fetchAll();
		foreach($Objects as $Object)
		{
			$ObjTemps[$Object['_obj']][$Object['_set']] = $Object['_val']  ;
			if($Object['_set'] == 'position')       {$ObjTemps[$Object['_obj']][$Object['_set']] = unserialize($Object['_val'])  ;}

		}
		$logtext .=" Amount of Object lines found: ".  "\r\n";
		$logtext .= count($Objects).  "\r\n";
		// get all units
		$Units = array();
		$GBSQL = "SELECT _name,_code,_sizeX,_sizeY,_className from units WHERE _code != 'NULL'";
		$result = $this->_GBMain->query($GBSQL);
		$UnitsSQL = $result->fetchAll();
		foreach($UnitsSQL as $Unit)
		{
			$index = $Unit['_name'] ;// . "-" . ucwords($Unit['_className']);
			$Units[$index]['itemName'] = $Unit['_name']  ;
			$Units[$index]['code'] = $Unit['_code']  ;
			$Units[$index]['sizeX'] = $Unit['_sizeX']  ;
			$Units[$index]['sizeY'] = $Unit['_sizeY']  ;
			$logtext .="Unit found: ". $index ." ". $Unit['_name'] . " - " .$Unit['_code'] . " - " .$Unit['_sizeX'] . " - " .$Unit['_sizeY'] .  "\r\n";
		}
		if(count($Units) < 1000)
		{ // some thing when wrong, units not good
			AddLog2( "GiftBox: Failed Reading Units" );
			return 'Fail';
		}
		$logtext .=" Amount of Units found: ".  "\r\n";
		$logtext .= count($UnitsSQL).  "\r\n";

		$i=0;
		foreach($ObjTemps as $Object)
		{
			$index = $Object['itemName'] ;//. "-" . $Object['className']  ;
			$logtext .="$i className: " .  $Object['className']  .  "\r\n";
			$logtext .="$i Object itemName: " .  $Object['itemName']  .  "\r\n";

			if ($Object['className']=="Plot")
			{
				$sizeX = "4";
				$sizeY = "4";
				$code = "Plot";
			}
			else
			{
				if(isset($Units[$index]['sizeX'])){$sizeX = $Units[$index]['sizeX'];}else{$sizeX = "1";}
				if($sizeX == '0'){$sizeX = "1";}
				if(isset($Units[$index]['sizeY'])){$sizeY = $Units[$index]['sizeY'];}else{$sizeY = "1";}
				if($sizeY == '0'){$sizeY = "1";}
				if(isset($Units[$index]['code'])){$code = $Units[$index]['code'];}else{$code = "NOT";}
			}
			if(isset($Object['direction'])){$direction = $Object['direction'];}else{$direction = "";}
			$itemName  = $Object['itemName']."_".$Object['className'];
			$className = $Object['className'];
			$state     = $Object['state'];
			$posX      = $Object['position']['x'];
			$posY      = $Object['position']['y'];
			$Map_all_items[$i]=array('itemName'=>$itemName,'classname'=>$className,'state'=>$state,'posX'=>$posX,'posY'=>$posY,'direction'=>$direction,'sizeX'=>$sizeX, 'sizeY'=>$sizeY, 'code'=>$code, 'end'=> " "  );
			$logtext .="$i  $itemName  $className x:$posX  y:$posY dir:$direction size x:$sizeX y:$sizeY  code:$code state: $state \r\n";
			$i++;
		}
		$logtext .=" Amount of objects prepared: " . $i.  "\r\n";

		// now map the objects to positions
		$this->_GBUser->query('BEGIN;');
		// empty the all locations
		$GBSQL ="DELETE FROM locations";
		$this->_GBUser->query($GBSQL);
		// get the farm size
		@list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', fBGetDataStore('playerinfo'));
		if(($FarmSizeX == '') || ($FarmSizeY == '')){ $GB_place_items = "No"; return;}else{ $GB_place_items = "OK";}
		// fill the location with empty
		$X = 0;
		while($X < $FarmSizeX)
		{
			$Y = 0;
			while($Y < $FarmSizeY)
			{
				$GBSQL ="INSERT INTO locations(_X,_Y,_what) VALUES('".$X."','".$Y."','E')";
				$this->_GBUser->query($GBSQL);
				$Y++;
			}
			$X++;
		}
		$GBSQL = "";
		foreach ($Map_all_items as $Map_pos)
		{
			if($Map_pos['sizeX'] == "1" && $Map_pos['sizeY'] == "1" )
			{   // size 1x1
				$GBSQL ="UPDATE locations SET _what = '".$Map_pos['classname']."' WHERE _X = '".$Map_pos['posX']."' AND _Y = '".$Map_pos['posY']."' ;";
				$this->_GBUser->query($GBSQL);
			}
			else
			{  // size not 1x1
				if (!array_key_exists('state' , $Map_pos) ){$Map_pos['state'] = "none";}
				if($Map_pos['state'] == "vertical" || $Map_pos['state'] == "built_rotatable")
				{ // object = turned.
					$TotY = $Map_pos['sizeX'];
					$TotX = $Map_pos['sizeY'];
				}
				else
				{ // object is normal.
					$TotX = $Map_pos['sizeX'];
					$TotY = $Map_pos['sizeY'];
				}
				// now TotX & Y is corrected.
				while ($TotX > 0)
				{
					//echo "*";
					$TotYtmp = $TotY;
					while($TotYtmp > 0)
					{
						$MapX = $Map_pos['posX']+$TotX-1;
						$MapY = $Map_pos['posY']+$TotYtmp-1;
						$logtext .="   pos:" .$MapX."-".$MapY .  "\r\n";
						$GBSQL ="UPDATE locations SET _what = '".$Map_pos['classname']."' WHERE _X = '".$MapX."' AND _Y = '".$MapY."' ;";
						$this->_GBUser->query($GBSQL);
						$TotYtmp--;
					} //end while Y
					$TotX--;
				} // end while X
			} // end else
		}
		AddLog2("GiftBox: Empty Locations Saved");
		$this->_GBUser->query('COMMIT;');

		echo "Done <br>";
		//print_r($Map_all_items);
		echo " <br>";
		// dump the logfile.
		$f = fopen($_SESSION['base_path'] . 'plugins/GiftBox/GB_XY_mapLOG.txt', "w+");
		fputs($f, $logtext, strlen($logtext));
		fclose($f);


	}



	//============================================================================
	//   function TEmptyXY
	//  returns the total empty slots in a block
	// $loc = the location Animal, Tree or Decoration.
	// $amount = ALL or ONE
	//============================================================================
	function TEmptyXY3($loc, $amount)
	{
		$cont = true;
		$counter =0;
		if (!in_array($loc, array("Animal", "Tree", "Decoration"))) { $cont = false;}
		if (!in_array($amount, array("ALL", "ONE"))) { $cont = false;}

		$minX = $this->GB_Setting[$loc.'X1'];
		$minY = $this->GB_Setting[$loc.'Y1'];
		$maxX = $this->GB_Setting[$loc.'X2'];
		$maxY = $this->GB_Setting[$loc.'Y2'];

		$locations = "ERROR"  ;
		if($cont)
		{

			@list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', fBGetDataStore('playerinfo'));
			if(($FarmSizeX == '') || ($FarmSizeY == '')){ $GB_place_items = "No"; return;}else{ $GB_place_items = "OK";}

			if ($amount == "ONE")
			{
				$GBSQL = "SELECT * FROM locations WHERE _X >= '".$minX ."' AND _X <= '".$maxX ."' AND _Y >= '".$minY ."' AND _Y <= '".$maxY ."' AND _what = 'E' LIMIT 1";
				$result = $this->_GBUser->query($GBSQL);
				if ($result->numRows() > 0)
				{
					$temp = $result->fetchAll();
					$locations = array();
					$locations['x'] = $temp['0']['_X'];
					$locations['y'] = $temp['0']['_Y'];
					// reserv the spot in the DB
					$GBSQL ="UPDATE locations SET _what = 'TEMP Giftbox' WHERE _X = '".$locations['x']."' AND _Y = '".$locations['y']."' ";
					$this->_GBUser->query($GBSQL);
				} else { $locations = "fail"; }
			}
			if ($amount == "ALL")
			{
				$GBSQL = "SELECT count(*) FROM locations WHERE _X >= '".$minX ."' AND _X <= '".$maxX ."' AND _Y >= '".$minY ."' AND _Y <= '".$maxY ."' AND _what = 'E' ";
				$result = $this->_GBUser->query($GBSQL);
				if ($result->numRows() > 0)
				{
					$location = $result->fetchAll();
					$locations = $location['0']['count(*)'];
				} else { $locations = "fail"; }
			}
		} //else {return $locations;} // paramter wrong
		return $locations;
	} // end function



	//============================================================================
	//   function TEmptyXY
	//  returns the total empty slots in a block
	// $loc = the location Animal, Tree or Decoration.
	// $amount = ALL or ONE
	//============================================================================
	function TEmptyXYSQL($loc, $amount)
	{
		$cont = true;
		$counter =0;
		if (!in_array($loc, array("Animal", "Tree", "Decoration"))) { $cont = false;}
		if (!in_array($amount, array("ALL", "ONE"))) { $cont = false;}

		//$GB_Setting['userid']
		$minX = $this->GB_Setting[$loc.'X1'];
		$minY = $this->GB_Setting[$loc.'Y1'];
		$maxX = $this->GB_Setting[$loc.'X2'];
		$maxY = $this->GB_Setting[$loc.'Y2'];

		if($cont)
		{

			@list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', fBGetDataStore('playerinfo'));
			if(($FarmSizeX == '') || ($FarmSizeY == '')){ $GB_place_items = "No"; return;}else{ $GB_place_items = "OK";}

			if(file_exists( $_SESSION['base_path'] . "plugins/GiftBox/".$this->GB_Setting['userid']."_".GBox_XY_map ))
			{
				$MapXY = load_array ( GBox_XY_map );
			}
			else
			{
				AddLog2("GB_XY_map.txt not found");
				return "Not indexed yet.";
			}


			$Map_pos_x = $minX;
			while($Map_pos_x < $maxX)
			{
				$Map_pos_y = $minY;
				while($Map_pos_y < $maxY)
				{
					if(!array_key_exists($Map_pos_x."-".$Map_pos_y , $MapXY ) )
					{
						// empty position found
						$EmptyXY['x'] = $Map_pos_x;
						$EmptyXY['y'] = $Map_pos_y;
						if($amount == "ONE")
						{
							$MapXY[$Map_pos_x."-".$Map_pos_y] = "temp_Giftbox";
							save_array ( $MapXY, GBox_XY_map );
							return $EmptyXY;
						}
						else
						{
							$counter++;
						}
					}
					$Map_pos_y++;
				}
				$Map_pos_x++;
			}

			if($amount == "ONE") {return "fail";}
			return $counter;
		} else {return "fail";} // paramter wrong

	} // end function

}

?>
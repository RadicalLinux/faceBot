<?php


//------------------------------------------------------------------------------
// Giftbox get from server SQL
//------------------------------------------------------------------------------


//------------------------------------------------------------------------------
// Giftbox get from server
//------------------------------------------------------------------------------
function GB_renew_giftbox()
{
	$res = 0;
	//load settings
	DoInit();
	$GB_ingiftbox = unserialize(fBGetDataStore('giftbox'));
	$GB_AllStorages = load_array ( GBox_storage );
	if (! $GB_AllStorages ) {$GB_AllStorages = array();}
	$GB_AllStorages['have'] = unserialize(fBGetDataStore('storagedata'));
	if(is_array($GB_AllStorages['have']))
	{
		save_array ( $GB_AllStorages, GBox_storage );
		AddLog2("GiftBox AllStorage update - saved ");
	} else { AddLog2("AllStorage ERROR - Not saved "); }


	if($GBox_Settings['Place'])
	{

		// see if we can store the latest info on farm XY
		$objects =      unserialize(fBGetDataStore('objects'));
		save_array ( $objects, GBox_XY_objects );
		AddLog2("Giftbox Objects - saved ");
		// build the map to find empty spots
		GB_buildEmptyXY();
	}
	// now update the building parts
	GB_BuiltPartBD();
	return $res;
}

//------------------------------------------------------------------------------
// Consume consume_kibble
//------------------------------------------------------------------------------
function GB_consumePet($targetObjetId , $ItemName)
{
	$res = 0;
	global $GB_tempid;
	//    $GB_tempid = 63000;
	$px_time = time();
	$amf = CreateRequestAMF('use', 'WorldService.performAction');
	$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $ItemName;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CPetsKibble';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['targetObjectId'] = $targetObjetId;   //3215
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['targetUser'] = '0';
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isFree'] = false;

	$GB_tempid++;
	$res = RequestAMF2($amf);
	AddLog2("Use $ItemName result: $res ");
	return $res;
}

//------------------------------------------------------------------------------
// Open Mystery gift & eggs
//------------------------------------------------------------------------------
function GB_OpenGift($ObjD, $GB_amount)
{
	global $GB_tempid;

	if($GB_tempid<63000) $GB_tempid = 63000;
	$px_Setopts = LoadSavedSettings();
	if (!@$px_Setopts['bot_speed'])      { $vSpeed = 1;  }
	if ( @$px_Setopts['bot_speed'] < 1)  {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	if ( @$px_Setopts['bot_speed'] > 8) { $vSpeed = 8;  }

	$vRunMainLoop=ceil($GB_amount/$vSpeed);
	for($vI=0;$vI<$vRunMainLoop;$vI++) {


		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$GB_amount));$vJ++) {
			@$amf = CreateMultAMFRequest($amf, $vNumAction, 'open','WorldService.performAction');
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName'] = $ObjD['_name'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted'] = false;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = $ObjD['_className'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id'] = $GB_tempid;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['state'] = 'static';
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId'] = -1;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
			$vNumAction++;
			$GB_tempid++;
		}

		$res = RequestAMF($amf);

		if ($res == 'OK') {
			AddLog2("[ OPEN ]   ".$ObjD['realname']."   (".($GB_amount-$vJ)." remains)  Status: $res");
			$need_reload = true;
		} else {
			AddLog2("ERROR! " . $res );
			return($res);
		}

	}
	return $res;
}

//------------------------------------------------------------------------------
// Check Cellar status.
//------------------------------------------------------------------------------


//------------------------------------------------------------------------------
// Store item from Giftbox to the storage cellar.
//------------------------------------------------------------------------------
function GB_StoreCel($ObjD, $GB_amount)
{
	global $GB_tempid;
	global $GB_Setting;
	$Cellar = unserialize( $GB_Setting['StorageLocation']);

	if($GB_tempid<63000) $GB_tempid = 63000;
	$px_Setopts = LoadSavedSettings();
	if (!@$px_Setopts['bot_speed'])      { $vSpeed = 1;  }
	if ( @$px_Setopts['bot_speed'] < 1)  {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	if ( @$px_Setopts['bot_speed'] > 8) { $vSpeed = 8;  }

	$state = 'static';
	if($ObjD['_className'] == 'Building') { $state = 'preview'; }

	$vRunMainLoop=ceil($GB_amount/$vSpeed);
	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$GB_amount));$vJ++) {
			$amf = CreateMultAMFRequest($amf, $vNumAction, 'store','WorldService.performAction');
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName']  = $ObjD['_name'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id']        = $GB_tempid;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = $ObjD['_className'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted']   = false;
			if($ObjD['_className'] == 'Building')
			{
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['buildTime'] = 'NaN';
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['plantTime'] = "$px_time" ."321"; //1283025533719
			}
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['x'] = $Cellar['x'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['y'] = $Cellar['y'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['z'] = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['state']     = $state;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId']    = -1;

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['resource'] = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['code'] =   $ObjD['_code'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['origin'] = "-1";
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['target'] =  -2 ;
			$vNumAction++;
			$GB_tempid++;
		}

		$res = RequestAMF($amf);

		if ($res == 'OK') {
			AddLog2("GB store ".$ObjD['_name']." result: $res [" . ($GB_amount-$vJ) . " to go]");
			$need_reload = true;
		} else {
			AddLog2("Oeps " . $res );
			return($res);
		}

	}
	return $res;
}


//------------------------------------------------------------------------------
// Use consumable
//------------------------------------------------------------------------------
function GB_consume($ObjD , $GB_amount)
{
	global $GB_tempid;
	if($GB_tempid<63000) $GB_tempid = 63000;
	$px_time = time();


	$px_Setopts = LoadSavedSettings();
	if (!@$px_Setopts['bot_speed'])      { $vSpeed = 1;  }
	if ( @$px_Setopts['bot_speed'] < 1)  {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	if ( @$px_Setopts['bot_speed'] > 20) { $vSpeed = 8;  }

	$vRunMainLoop=ceil($GB_amount/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {
		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$GB_amount));$vJ++) {
			@$amf = CreateMultAMFRequest($amf, $vNumAction, 'use','WorldService.performAction');
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id'] = $GB_tempid;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName'] = $ObjD['_name'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted'] = false;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = 'Consumable';
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId'] = -1;

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['targetUser'] = $_SESSION['userId'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isFree'] = false;

			$vNumAction++;
			$GB_tempid++;
		}

		$res = RequestAMF($amf);

		if ($res == 'OK') {
			AddLog2("GiftBox: Consume ".$ObjD['_name']." - Result: $res [" . ($GB_amount-$vJ) . " to go]");
			$need_reload = true;
		} else {
			AddLog2("Oops " . $res );
			return($res);
		}

	}

	return $res;
}

//------------------------------------------------------------------------------
//   addToInventory
//------------------------------------------------------------------------------
function GB_addToInventory($ObjD , $GB_amount)
{
	$res = 0;
	global $GB_tempid;
	//    $GB_tempid = 63000;
	$px_time = time();
	$i = 0;
	while ($GB_amount > 0)
	{
		$amf = CreateRequestAMF('addToInventory', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][$i]['params'][1]['id'] = $GB_tempid;   //
		$amf->_bodys[0]->_value[1][$i]['params'][1]['direction'] = 0;     //
		$amf->_bodys[0]->_value[1][$i]['params'][1]['state'] = $ObjD['state'];     //

		$amf->_bodys[0]->_value[1][$i]['params'][1]['itemName'] = $ObjD['name']; //
		$amf->_bodys[0]->_value[1][$i]['params'][1]['position'] = array('x'=>47, 'y'=>63, 'z'=>0);//
		$amf->_bodys[0]->_value[1][$i]['params'][1]['deleted'] = false;  //
		$amf->_bodys[0]->_value[1][$i]['params'][1]['className'] = $ObjD['className']; //
		$amf->_bodys[0]->_value[1][$i]['params'][1]['tempId'] = -1;   //

		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['code'] = $ObjD['itemCode'];//
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift'] = true;        //
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['origin'] = "-1";        //
		$amf->_bodys[0]->_value[1][$i]['params'][2][0]['resource'] = 0;

		$GB_amount--;
		$GB_tempid++;
		$res = RequestAMF2($amf);
		if ($res == "OK")
		{
			AddLog2("Into storage ".$ObjD['name']." result: $res [" . ($GB_amount-$vJ) . " to go]");
		} else {
			AddLog2("Oeps " . $res );
			return;
		}
	}
	return $res;
}


//------------------------------------------------------------------------------
// GB_HorseStable               WORK IN PROGRESS
//------------------------------------------------------------------------------
// class to find = HorseStableBuilding
function GB_HorseStable($ItemName , $ItemCode)
{
	$res = 0;
	AddLog2("Building the horse stable with: " . $ItemName);
	$amf = CreateRequestAMF('', 'WorldService.performAction');
	$amf->_bodys[0]->_value[1][0]['params'][0] = $ItemCode;
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][0] = "Storage";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][1] = "accessing_goods";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][2] = "general_HUD_icon";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][3] = "";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][4] = "";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][5] = "";
	$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][6] = "1";
	$amf->_bodys[0]->_value[1][2]['params'][0] = $ItemName;
	$res = RequestAMF2($amf);
	if ($res == "OK")
	{
		AddLog2("result giftbox: $res");
	} else {
		AddLog2("Oeps " . $res );
		return;
	}
	return $res;
}

//------------------------------------------------------------------------------
// TradeIn Collection
//------------------------------------------------------------------------------
function GB_TradeIn($CollectionID, $GB_amount)
{
	if( $_SESSION['userId'] == "") {  AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;
	$px_time = time();
	$i = 0;
	while ($GB_amount > 0)
	{
		$amf = CreateRequestAMF('','CollectionsService.onTradeIn');
		$amf->_bodys[0]->_value[1][$i]['params'][0] = $CollectionID; // like "C001"
		$GB_amount--;
		$res = RequestAMF2($amf);
		if ($res == "OK")
		{
			AddLog2("Trade in ".$CollectionID." result: $res [" . $GB_amount . " to go]");
		} else {
			AddLog2("GiftBox: Error -" . $res );
			return;
		}
	}
	return $res;
}







//------------------------------------------------------------------------------
// Sell collectable out of gift box
//------------------------------------------------------------------------------
function GB_DoSellCol($ObjD , $GB_amount)
{
	if (array_key_exists('_className', $ObjD)){$Class = $ObjD['_className'];}else{$Class = "";}
	if( $_SESSION['userId'] == "") {  AddLog2("AMF fail (userId unknown"); return "fail"; }
	//   AddLog2("Selling from giftbox  item " . $ObjD['realname']);
	// $ObjD['realname']
	unset ($_SESSION['amfphp']['encoding']);

	$px_Setopts = LoadSavedSettings();
	if (!@$px_Setopts['bot_speed'])      { $vSpeed = 1;  }
	if ( @$px_Setopts['bot_speed'] < 1)  {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	if ( @$px_Setopts['bot_speed'] > 20) { $vSpeed = 8;  }

	$vRunMainLoop=ceil($GB_amount/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$GB_amount));$vJ++) {
			@$amf = CreateMultAMFRequest($amf, $vNumAction, '', 'UserService.sellStoredItem');
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0]['code'] = $ObjD['_code']; //added 2010-06-29
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = false;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2] = -1;

			$vNumAction++;
		}
		$res = RequestAMF($amf);
		if ($res == 'OK') {
			$need_reload = true;
		} else {
			AddLog2("GiftBox: Error - " . $res );
			return($res);
		}

	}
	return $res;
}

//------------------------------------------------------------------------------
// Use fuel out of gift box
//------------------------------------------------------------------------------
function GB_BuyFuel($ObjD , $GB_amount)
{
	global $GB_tempid;
	if($GB_tempid<63000) $GB_tempid = 63000;


	$px_Setopts = LoadSavedSettings();
	if (!@$px_Setopts['bot_speed'])      { $vSpeed = 1;  }
	if ( @$px_Setopts['bot_speed'] < 1)  {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	if ( @$px_Setopts['bot_speed'] > 20) { $vSpeed = 8;  }

	$vRunMainLoop=ceil($GB_amount/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();

		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$GB_amount));$vJ++) {
			@$amf = CreateMultAMFRequest($amf, $vNumAction, '', 'FarmService.buyFuel');
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = $ObjD['_name']; //'fuel5';
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = true;
			$vNumAction++;
			$GB_tempid++;
		}

		$res = RequestAMF2($amf);

		if ($res == 'OK') {
			AddLog2('GiftBox: Use ' . $ObjD['_name'] . ' - Result: ' . $res  . '[' . $GB_amount-$vJ . ' to Go]');
			$need_reload = true;
		} else {
			AddLog2("GiftBox: Error - " . $res );
			return($res);
		}

	}

	return $res;
}

//------------------------------------------------------------------------------
// Remove collectable out of gift box
//------------------------------------------------------------------------------
function GB_DoColAdd($ItemName , $ItemCode, $Amount_to_add)
{
	while ($Amount_to_add > 0)
	{
		$res = 0;
		//AddLog2("Accessing the giftbox for item " . $ItemName);
		$amf = CreateRequestAMF('', 'CollectionsService.addGiftItemToCollection');
		$amf->_bodys[0]->_value[1][0]['params'][0] = $ItemCode;
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][0] = "Storage";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][1] = "accessing_goods";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][2] = "general_HUD_icon";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][3] = "";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][4] = "";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][5] = "";
		$amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][6] = "1";
		$amf->_bodys[0]->_value[1][2]['params'][0] = $ItemName;
		$res = RequestAMF($amf);

		$Amount_to_add--;
		if ($res == "OK")
		{
			AddLog2($ItemName . " Added to Collection " . $Amount_to_add . " to Go" );
		} else {
			AddLog2("GiftBox: Error Adding ". $ItemName . " to Collection - " . $res );
			return;
		}
	}
	return $res;
}


//------------------------------------------------------------------------------
// GB_storeItem from giftbox
//------------------------------------------------------------------------------
function GB_storeItem($Item , $Target)
{
	// Target stuff
	// What do we need Potofgold chateau
	$itemName = $Target['itemName']; // OK OK
	$direction = 0;                  // $Target['direction'] -- --
	$id = $Target['id'];             // OK OK
	// $Target['contents'] =N need work
	if($Target['contents'] == "N") {$contents = "";}
	if(is_array($Target['contents']))
	{
		$contents = array();
		foreach($Target['contents'] as $value)
		{
			$contents[]['item'] = array('num'=>$value['numItem']) ;
		}
	}
	$buildTime = 0;                        // $Target['buildTime'] -- --
	$positionx = $Target['position']['x']; // OK OK
	$positiony = $Target['position']['y']; // OK OK
	$state = $Target['state'];             // OK OK
	$plantTime = 0;                      // $Target['plantTime'] =N =N
	$className = $Target['className'] ;  // OK OK
	$paintColor = $Target['paintColor']; // ? OK
	// Item stuff
	$storedItemCode = $Item['_code'];    // $Item['storedItemCode'] "code"

	$storedClassName = $Item['_className'];  // $Item['storedClassName'] "class"
	$storedItemName = $Item['_name'];    // $Item['storedItemName'] "name"
	//load settings
	if( $_SESSION['userId'] == "") { AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;
	//AddLog2("GiftBox Gold Gift: " . $ItemName);
	$GB_test_time = time();
	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0]['token'] = $_SESSION['token'];
	$amf->_bodys[0]->_value[0]['masterId'] = $_SESSION['userId'];
	$amf->_bodys[0]->_value[0]['wid'] = 0;
	$amf->_bodys[0]->_value[0]['snId'] = 1;
	$amf->_bodys[0]->_value[0]['flashRevision'] = $_SESSION['flashRevision'];
	$amf->_bodys[0]->_value[0]['sigTime'] = "$GB_test_time.0000";
	$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
	$amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
	$amf->_bodys[0]->_value[1][1]['params'][0] = "store";   //"storeItem";
	$amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = $itemName;
	$amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = $direction;
	$amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $id;
	$amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
	$amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = $contents;
	$amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = $buildTime;
	$amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $positionx;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0 ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $positiony ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['state'] = $state;
	$amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = $plantTime;
	$amf->_bodys[0]->_value[1][1]['params'][1]['className'] = $className;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $storedItemCode;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = $storedClassName;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $storedItemName;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
	$amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
	$amf->_bodys[0]->_value[2] = 0;
	$res = RequestAMF2($amf);
	if ($res == "OK")
	{
		AddLog2("GiftBox: Stored $storedItemName in $itemName - Result: $res");
	} else {
		AddLog2("GiftBox: Error Storing ". $ItemName . " - " . $res );
		return;
	}
	return $res;
}

//------------------------------------------------------------------------------
// GB_storeItem2 from giftbox
//------------------------------------------------------------------------------
function GB_storeItem2($Item , $Target, $GB_amount)
{
	// Target stuff
	// What do we need Potofgold chateau
	$itemName = $Target['itemName']; // OK OK
	$direction = 0;                  // $Target['direction'] -- --
	$id = $Target['id'];             // OK OK
	// $Target['contents'] =N need work
	$contents = array();
	if($Target['contents'] == "N") {$contents = "";}
	if(is_array($Target['contents']))
	{
		$contents = array();
		foreach($Target['contents'] as $value)
		{
			$contents[]['item'] = array('num'=>$value['numItem']) ;
		}
	}
	$buildTime = 0;                        // $Target['buildTime'] -- --
	$positionx = $Target['position']['x']; // OK OK
	$positiony = $Target['position']['y']; // OK OK
	$state = $Target['state'];             // OK OK
	$plantTime = 0;                      // $Target['plantTime'] =N =N
	$className = $Target['className'] ;  // OK OK
	$paintColor = $Target['paintColor']; // ? OK
	// Item stuff
	//if($Item['code'] == 1) $Item['code'] = "Lb"; // fix for olives
	$storedItemCode = $Item['_code'];    // $Item['storedItemCode'] "code"

	$storedClassName = $Item['_className'];  // $Item['storedClassName'] "class"
	$storedItemName = $Item['_name'];    // $Item['storedItemName'] "name"
	//  $storedItemCode = $Item['code'];    // $Item['storedItemCode'] "code"
	//  $storedClassName = $Item['class'];  // $Item['storedClassName'] "class"
	//  $storedItemName = $Item['name'];    // $Item['storedItemName'] "name"
	//load settings
	if( $_SESSION['userId'] == "") { AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;
	//AddLog2("GiftBox Gold Gift: " . $ItemName);
	$GB_test_time = time();
	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0]['token'] = $_SESSION['token'];
	$amf->_bodys[0]->_value[0]['masterId'] = $_SESSION['userId'];
	$amf->_bodys[0]->_value[0]['wid'] = 0;
	$amf->_bodys[0]->_value[0]['snId'] = 1;
	$amf->_bodys[0]->_value[0]['flashRevision'] = $_SESSION['flashRevision'];
	$amf->_bodys[0]->_value[0]['sigTime'] = "$GB_test_time.0000";

	while ($GB_amount > 0)
	{


		$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
		$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
		$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
		$amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
		$amf->_bodys[0]->_value[1][1]['params'][0] = "store";   //2010-07-01 "storeItem";
		$amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = $itemName;
		$amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = $direction;
		$amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $id;
		$amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
		$amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = $contents;
		$amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = $buildTime;
		$amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
		$amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $positionx;
		$amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0 ;
		$amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $positiony ;
		$amf->_bodys[0]->_value[1][1]['params'][1]['state'] = $state;
		$amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = $plantTime;
		$amf->_bodys[0]->_value[1][1]['params'][1]['className'] = $className;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $storedItemCode;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = $storedClassName;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $storedItemName;
		$amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
		$amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
		$amf->_bodys[0]->_value[2] = 0;
		$GB_amount--;

		$res = RequestAMF2($amf);
		if ($res == "OK")
		{
			AddLog2("GiftBox: Stored $itemName - Result: $res");
		} else {
			AddLog2("GiftBox: Error Storing ". $ItemName . " - " . $res );
			return;
		}

		if($res != "OK")
		{
			GB_AMF_Error($res);
			return "Failed";
		}


	} // while

	return $res;
}



//------------------------------------------------------------------------------
// Remove Valentines Gift out of gift box
//------------------------------------------------------------------------------
function GB_DoValentin($ItemCode, $ItemName , $ValBoxObj)
{
	//load settings
	if( $_SESSION['userId'] == "") {  AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;

	AddLog2("Accessing the giftbox for Valentines Gift: " . $ItemName);

	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();

	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';

	$GB_test_time = time();
	$amf->_bodys[0]->_value[0]['sigTime'] = "$GB_test_time.0000";
	$amf->_bodys[0]->_value[0]['token'] = $_SESSION['token'];
	$amf->_bodys[0]->_value[0]['flashRevision'] = $_SESSION['flashRevision'];
	$amf->_bodys[0]->_value[0]['masterId'] = $_SESSION['userId'];
	$amf->_bodys[0]->_value[0]['wid'] = 0;
	$amf->_bodys[0]->_value[0]['snId'] = 1;

	$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();

	$amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
	$amf->_bodys[0]->_value[1][1]['params'][0] = "storeItem";
	$amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['className'] = 'ValentinesBox';
	$amf->_bodys[0]->_value[1][1]['params'][1]['state']  = 'built';
	$amf->_bodys[0]->_value[1][1]['params'][1]['deleted']  = 'false';
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $ValBoxObj['0']['position']['x'] ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0 ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $ValBoxObj['0']['position']['y'] ;
	//   $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] =
	//   $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] =
	$amf->_bodys[0]->_value[1][1]['params'][1]['tempId']  =  "NaN";
	$amf->_bodys[0]->_value[1][1]['params'][1]['plantTime']  =  0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['itemName']  =  "valentinesbox";
	$amf->_bodys[0]->_value[1][1]['params'][1]['direction']  =  0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['id'] =  $ValBoxObj['0']['id'] ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = "";

	$amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] =  0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] =  $ItemCode;    //8D
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = "ValentinesGift";
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] =   $ItemName; //  valentine_03
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] =  true;
	$amf->_bodys[0]->_value[1][1]['sequence'] =  GetSequense();

	$amf->_bodys[0]->_value[2] = 0;
	$res = RequestAMF2($amf);
	if ($res == "OK")
	{
		AddLog2("result Valentines Gift: $res");
	} else {
		AddLog2("Oeps handeling ". $ItemName . " - " . $res );
		return;
	}
	return $res;
}

//------------------------------------------------------------------------------
// Remove Gold out of gift box
//------------------------------------------------------------------------------
function GB_DoGold($ItemCode, $ItemName , $potofgold)
{
	//load settings
	if( $_SESSION['userId'] == "") {  AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;

	AddLog2("GiftBox Gold Gift: " . $ItemName);
	$GB_test_time = time();

	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0]['token'] = $_SESSION['token'];
	$amf->_bodys[0]->_value[0]['masterId'] = $_SESSION['userId'];
	$amf->_bodys[0]->_value[0]['wid'] = 0;
	$amf->_bodys[0]->_value[0]['snId'] = 1;
	$amf->_bodys[0]->_value[0]['flashRevision'] = $_SESSION['flashRevision'];
	$amf->_bodys[0]->_value[0]['sigTime'] = "$GB_test_time.0000";

	$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();

	$amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
	$amf->_bodys[0]->_value[1][1]['params'][0] = "storeItem";
	$amf->_bodys[0]->_value[1][1]['params'][1]['itemName']  =  "potofgold";
	$amf->_bodys[0]->_value[1][1]['params'][1]['direction']  =  0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['id'] =  $potofgold['0']['id'] ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['tempId']  =  "NaN";
	$amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = "";
	$amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['deleted']  = 'false';
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $potofgold['0']['position']['x'] ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0 ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $potofgold['0']['position']['y'] ;
	$amf->_bodys[0]->_value[1][1]['params'][1]['state']  = 'built';
	$amf->_bodys[0]->_value[1][1]['params'][1]['plantTime']  =  0;
	$amf->_bodys[0]->_value[1][1]['params'][1]['className'] = 'PotOfGold';

	$amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] =  0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] =  $ItemCode;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = "PotOfGoldItem";
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] =   $ItemName;
	$amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] =  true;
	$amf->_bodys[0]->_value[1][1]['sequence'] =  GetSequense();

	$amf->_bodys[0]->_value[2] = 0;
	$res = RequestAMF2($amf);
	if ($res == "OK")
	{
		AddLog2("Result Gold: $res");
	} else {
		AddLog2("Oeps handeling ". $ItemName . " - " . $res );
		return;
	}

	return $res;
}

//########################################################################
// Place item on the farm.
//########################################################################

function GB_PlaceM($ObjD, $GB_amount)
{
	global $GB_tempid;
	global $GBox_Settings  ;
	AddLog2('GiftBox Place item(s) start.. ');
	// Get all settings correct
	$res = 0;
	$px_time = time();
	if(array_key_exists('type', $ObjD))
	{
		$state = "static";
		if($ObjD['type']=="Decoration"){$state = "static";}
		if($ObjD['type']=="RotateableDecoration"){$state = "horizontal";}
		if($ObjD['type']=="animal"){$state = "bare";}
	}else{ $state = "static"; }

	$type = "bla";
	if($ObjD['type'] == "animal"){$type = "Animal";}

	// Start building the AMF request

	while ($GB_amount > 0)
	{


		$EmptyXY = EmptyXY();
		$amf = CreateAMFRequest('place', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']       = false;
		$amf->_bodys[0]->_value[1][0]['params'][1]['direction']     = 1;
		if(array_key_exists('growTime' , $ObjD))
		{ $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime']     = $px_time."123";   }
		$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']        = -1;
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']     = $type ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']      = $ObjD['name'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['state']         = $state ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']     = -1;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                  = true;

		$GB_tempid++;
		$GB_amount--;
		$res = RequestAMF2($amf);
		AddLog2("Giftbox placed: ".$ObjD['name']." Result: $res [" . $GB_amount . " to go]. " );
		if($GB_Setting['DoDebug']) AddLog2("Giftbox placed On: " . $EmptyXY['x'] . "-". $EmptyXY['y'] . ' tempid:' . $GB_tempid);
	} // end while

	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer

	if (!isset($amf2->_bodys[0]->_value['data'][0])) { AddLog2("UP GB Error: BAD AMF - To many items on the farm?"); $res ="To many on the farm?"; }
	if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) { $res = 'OK'; }

	if($res == 'OK' ) {
		if($GB_Setting['DoDebug']) AddLog2( "GiftBox place item(s) done" );
	} else{
		AddLog2( "GiftBox: Error Placing Item(s)" );
	}
	return $res;
}

//########################################################################
// Place item on the farm.
//########################################################################

function GB_PlaceM2($ObjD, $GB_amount, $loc)
{
	global $GB_tempid;
	global $GBox_Settings  ;
	AddLog2('GiftBox Place item(s) start.. ');
	// Get all settings correct
	$res = 0;
	$px_time = time();
	if(array_key_exists('_type', $ObjD))
	{
		$state = "static";
		if($ObjD['_type']=="Decoration"){$state = "static";}
		if($ObjD['_type']=="RotateableDecoration"){$state = "horizontal";}
		if($ObjD['_type']=="animal"){$state = "bare";}
	}else{ $state = "static"; }

	$type = "bla";
	if($ObjD['_type'] == "animal"){$type = "Animal";}

	while ($GB_amount > 0)
	{


		$EmptyXY = $GLOBALS['GBC']->TEmptyXY($loc, "ONE");
		if( $EmptyXY == "fail")
		{
			AddLog2("Error: No more locations left for " . $loc);
			return "fail";
		}
		$amf = CreateAMFRequest('place', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']       = false;
		$amf->_bodys[0]->_value[1][0]['params'][1]['direction']     = 1;
		if(array_key_exists('growTime' , $ObjD))
		{ $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime']     = $px_time."123";   }
		$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']        = -1;
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']     = $type ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']      = $ObjD['name'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['state']         = $state ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']     = -1;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                  = true;

		$GB_tempid++;
		$GB_amount--;
		$res = RequestAMF2($amf);
		AddLog2("Giftbox placed: ".$ObjD['realname']." Result: $res [" . $GB_amount . " to go]. " );
		if($GB_Setting['DoDebug']) AddLog2("Giftbox placed On: " . $EmptyXY['x'] . "-". $EmptyXY['y'] . ' tempid:' . $GB_tempid);
	} // end while

	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer

	if (!isset($amf2->_bodys[0]->_value['data'][0])) { AddLog2("UP GB Error: BAD AMF - To many itmes on the farm?"); $res ="To many items on the farm?"; }
	if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) { $res = 'OK'; }

	if($res == 'OK' ) {
		if($GB_Setting['DoDebug']) AddLog2( "GiftBox place item(s) done" );
	} else{
		AddLog2( "GiftBox: Error Placing Item(s)" );
	}
	return $res;
}
//########################################################################
// Place item on the farm.  Version 3
//########################################################################

function GB_PlaceM3($ObjD, $GB_amount, $loc)
{
	global $GB_tempid;
	global $GB_Setting;

	AddLog2('GiftBox Place item(s) start.. ');
	// Get all settings correct
	$res = 0;
	$px_time = time();
	if(array_key_exists('_type', $ObjD))
	{
		$state = "static";
		if($ObjD['_type']=="Decoration"){$state = "static";}
		if($ObjD['_type']=="RotateableDecoration"){$state = "horizontal";}
		if($ObjD['_type']=="animal"){$state = "bare";}
	}else{ $state = "static"; }

	$type = $ObjD['_type'];
	if($ObjD['_type'] == "animal"){$type = "Animal";}


	while ($GB_amount > 0)
	{
		$EmptyXY = $GLOBALS['GBC']->TEmptyXY3($loc, "ONE");
		if( $EmptyXY == "fail")
		{  AddLog2("Error: No more locations left for " . $loc);
		return "fail";
		}
		$amf = CreateRequestAMF('place', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']       = false;
		$amf->_bodys[0]->_value[1][0]['params'][1]['direction']     = 1;
		if(array_key_exists('growTime' , $ObjD))
		{ $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime']     = $px_time."123";   }
		$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']        = -1;
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']     = $type ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']      = $ObjD['_name'] ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['state']         = $state ;
		$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']     = -1;
		$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                  = true;

		$GB_tempid++;
		$GB_amount--;
		$res = RequestAMF2($amf);
		if($res == 'OK' ) {
			AddLog2("Giftbox placed: ".$ObjD['realname']." Result: $res [" . $GB_amount . " to go]. " );
			if($GB_Setting['DoDebug']) AddLog2( "GiftBox place item(s) X: " . $EmptyXY['x'] . " Y:" . $EmptyXY['y'] );
		} else{
			AddLog2( "GiftBox place ERROR X: " . $EmptyXY['x'] . " Y:" . $EmptyXY['y'] );
		}
	} // end while

	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer

	if (!isset($amf2->_bodys[0]->_value['data'][0])) { AddLog2("UP GB Error: BAD AMF - To many itmes on the farm?"); $res ="To many items on the farm?"; }
	if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) { $res = 'OK'; }

	if($res == 'OK' ) {
		if($GB_Setting['DoDebug']) AddLog2( "GiftBox place item(s) done" );
	} else{
		AddLog2( "GiftBox: Error Placing Item(s)" );
	}
	return $res;
}


// ------------------------------------------------------------------------------
// RequestAMF sends AMF request to the farmville server
//  @param object $request AMF request
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function RequestAMF2($amf) {

	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer
	Check4Rewards($amf2);
	// Check if there was a error
	// Check if there was data[0] and or data[1]
	$errorfound = 'N';
	$errorType = '';
	$errorData = '';
	if (@$amf2->_bodys[0]->_value['errorType'] != 0)
	{$errorfound = 'Y';
	$errorType = $amf2->_bodys[0]->_value['errorType'];
	$errorData = $amf2->_bodys[0]->_value['errorData'];
	}
	if (isset($amf2->_bodys[0]->_value['data'][0]))
	{
		if (@$amf2->_bodys[0]->_value['data'][0]['errorType'] != 0)
		{$errorfound = 'Y';
		$errorType = $amf2->_bodys[0]->_value['data'][0]['errorType'];
		$errorData = $amf2->_bodys[0]->_value['data'][0]['errorData'];
		}

	}
	if (isset($amf2->_bodys[0]->_value['data'][1]))
	{
		if (@$amf2->_bodys[0]->_value['data'][1]['errorType'] != 0)
		{$errorfound = 'Y';
		$errorType = $amf2->_bodys[0]->_value['data'][1]['errorType'];
		$errorData = $amf2->_bodys[0]->_value['data'][1]['errorData'];
		}

	}


	if ($errorfound == 'Y') {
		if ($errorData == "There is a new version of the farm game released") {
			AddLog2('FV UPDATE: New Version of FV Released');
			AddLog2('Work Will Restart in 15 Seconds');
			unlink('unit_check.txt');
			RestartBot();
		} else if ($errorData == "token value failed") {
			AddLog2('FV FUBAR: Game Opened in Another Browser');
			AddLog2('Work Will Restart in 15 Seconds');
			RestartBot();
		} else if ($errorData == "token too old") {
			AddLog2('FV FUBAR: Your Session Has Expired');
			AddLog2('Work Will Restart in 15 Seconds');
			RestartBot();
		} else if ($errorType == 29) {
			AddLog2('FV FUBAR: Server Sequence Was Reset');
			DoInit();
		} else {
			$res = "Error: " . $errorType . " " . $errorData;
			DoInit();
		}
	} else if (!isset($amf2->_bodys[0]->_value['data'][0])) {
		echo "\n*****\nError:\n BAD AMF REPLY - Possible Server problem or farm badly out of sync\n*****\n";
		$res = "BAD AMF REPLY (OOS?)";
	} else if ($errorType == '' && $errorData == '') {
		$res = 'OK';
	} else { $res = 'Unknown'; }
	//if (isset($amf2->_bodys[0]->_value['data'][0])) {
	//$res = $errorType . " " . $errorData;
	//}
	//}

	return $res;
}

//------------------------------------------------------------------------------
// add vehiclePart to vehicle in storage
//------------------------------------------------------------------------------
function GB_DoGarage($ItemCode , $garage)
{
	//load settings
	if( $_SESSION['userId'] == "") {  AddLog2("AMF fail (userId unknown"); return "fail"; }
	$res = 0;

	$garageId = $garage;
	$ItemString = $ItemCode['itemCode'] . ":" . $ItemCode['numParts'] ;

	//AddLog2("GB Garage " );
	$GB_test_time = time();

	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0]['token'] = $_SESSION['token'];                    //ok
	$amf->_bodys[0]->_value[0]['uid'] = $_SESSION['userId'];                //new
	$amf->_bodys[0]->_value[0]['masterId'] = $_SESSION['userId'];                //ok
	$amf->_bodys[0]->_value[0]['wid'] = 0;                           //ok
	$amf->_bodys[0]->_value[0]['snId'] = 1;                          //ok
	$amf->_bodys[0]->_value[0]['flashRevision'] = $_SESSION['flashRevision'];    //ok
	$amf->_bodys[0]->_value[0]['sigTime'] = "$GB_test_time.0000";    //ok

	$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
	$amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();

	$amf->_bodys[0]->_value[1][1]['functionName'] = "EquipmentWorldService.onAddPartToEquipmentInGarage";//ok
	$amf->_bodys[0]->_value[1][1]['params'][0] = $garageId;  // the id
	$amf->_bodys[0]->_value[1][1]['params'][1] = $ItemString ;  // like V1:1
	$amf->_bodys[0]->_value[1][1]['params'][2] = true;
	$amf->_bodys[0]->_value[1][1]['sequence'] =  GetSequense();   //ok

	$amf->_bodys[0]->_value[2] = 0;
	$res = RequestAMF2($amf);
	if ($res == "OK")
	{
		//AddLog2("Result garage: $res");
	} else {
		AddLog2("Oeps handeling ". $ItemName . " - " . $res );
		return;
	}

	return $res;
}

function GB_AMF_Error($res)
{
AddLog2("GiftBox Oops.. that was wrong.. : $res");
return;
}

?>
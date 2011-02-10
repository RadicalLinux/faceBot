<?php

$tmp = pack("d", 1); // determine the multi-byte ordering of this machine temporarily pack 1
//define("AMFPHP_BIG_ENDIAN", $tmp == "\0\0\0\0\0\0\360\77");
$_SESSION['amfphp']['encoding'] = 'amf3';

define('AMFPHP_BASE', $_SESSION['base_path'] . 'amfphp/core/');
require_once(AMFPHP_BASE . "shared/util/CharsetHandler.php");
require_once(AMFPHP_BASE . "amf/util/AMFObject.php");
require_once(AMFPHP_BASE . "shared/util/CompatPhp5.php");
require_once(AMFPHP_BASE . "shared/util/MessageBody.php");
require_once(AMFPHP_BASE . "shared/app/Constants.php");
require_once(AMFPHP_BASE . "shared/app/Globals.php");
require_once(AMFPHP_BASE . "amf/io/AMFDeserializer.php");
require_once(AMFPHP_BASE . "amf/io/AMFSerializer.php");

// ------------------------------------------------------------------------------
// CreateRequestAMF creates AMF object
//  @param string $request
//  @param string $function
//  @return object AMF object
// ------------------------------------------------------------------------------
function CreateRequestAMF($request = '', $function = '')
{
	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0] = GetAMFHeaders();
	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
	$amf->_bodys[0]->_value[1][0]['params'] = array();
	if ($request)
	$amf->_bodys[0]->_value[1][0]['params'][0] = $request;

	if ($function)
	$amf->_bodys[0]->_value[1][0]['functionName'] = $function;

	$amf->_bodys[0]->_value[2] = 0;

	return $amf;
}
// ------------------------------------------------------------------------------
// GetAMFHeaders
//  @return array auth parameters
// ------------------------------------------------------------------------------
function GetAMFHeaders()
{
	$amf_headers = array();
	$amf_headers['sigTime'] = time() . ".0000";
	$amf_headers['token'] = $_SESSION['token'];
	$amf_headers['flashRevision'] = $_SESSION['flashRevision'];
	$amf_headers['masterId'] = $_SESSION['userId'];
	$amf_headers['wid'] = 0;
	$amf_headers['snId'] = 1;

	return $amf_headers;
}
// ------------------------------------------------------------------------------
// GetSequense
// ------------------------------------------------------------------------------
function GetSequense()
{
	$_SESSION['sequence'] = $_SESSION['sequence'] + 1;
	return $_SESSION['sequence'];
}
// ------------------------------------------------------------------------------
// Request sends AMF request to the farmville server
//  @param resourse $s socket connection
//  @param string $result AMF-serialized request
//  @return string http answer
// ------------------------------------------------------------------------------
function Request($s = '', $result)
{
	//AddLog2(print_r($result,true));
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, farmer_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $result);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-amf'));
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
		AddLog2("Parser: Error 404/Page Not Found");
		return Request('', $result);
	}
	if($httpCode == 500) {
		AddLog2("Parser: Error 500/Internal Server Error");
		return;
	}
	if($httpCode == 502) {
		AddLog2("Parser: Error 502/Bad Gateway");
		return;
	}
	if(empty($response)) {
		AddLog2("Parser: Empty Response Returned");
		return;
	}
	curl_close ($ch);
	return $response;


}
// ------------------------------------------------------------------------------
// DoInit Loading farms
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function DoInit($inittype = '')
{
	$T = time(true);
	$res = 0;

	Hook('before_load_farm');

	$_SESSION['sequence'] = 0;
	// Create Init request
	$amf = CreateRequestAMF('', 'UserService.initUser');
	$amf->_bodys[0]->_value[1][0]['params'][0] = "";
	$amf->_bodys[0]->_value[1][0]['params'][1] = - 1;
	$amf->_bodys[0]->_value[1][0]['params'][2] = true;
	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);
	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer
	$res = CheckAMF2Response($amf2);
	if ($res == 'OK')
	{
		// get flashSessionKey
		//$_SESSION['sequence'] = 1;
		//file_put_contents('all.txt', print_r($amf2,true));
		if (isset($amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['session_key']))
		{
			$_SESSION['flashSessionKey'] = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['session_key'];
		}
		$_SESSION['servertime'] = $amf2->_bodys[0]->_value['data'][0]['serverTime'];
		// save to file $flashSessionKey, $xp, $energy
		$xp = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['xp'];
		$energy = $amf2->_bodys[0]->_value['data'][0]['data']['energy'];
		$hlXP = Units_GetFarming('higherLevelXp');
		$hlBegin = Units_GetFarming('higherLevelBegin');
		$hlStep = Units_GetFarming('higherLevelStep');
		// get extra info
		$level = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['level'];
		if (isset($hlXP) && $xp >= $hlXP) {
			$level = @$hlBegin + floor(($xp - @$hlXP) / @$hlStep);
		}
		$gold = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['gold'];
		$cash = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['cash'];
		$sizeX = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeX'];
		$sizeY = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeY'];
		$firstname = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['attr']['name'];
		$locale = $amf2->_bodys[0]->_value['data'][0]['data']['locale'];
		$tileset = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['tileSet'];
		$wither = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['witherOn'];
			
		// save to file $level, $coins, $cash, $sizex, $sizey
		$uSQL = 'INSERT OR REPLACE INTO datastore(userid, storetype, content) values("' . $_SESSION['userId'] . '",
									"playerinfo", "' . implode(';', array($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $_SESSION['flashRevision'])) . '");';

		// save world to file
		save_botarray ($amf2->_bodys[0]->_value, F('world.txt'));
		//file_put_contents('world.txt', print_r($amf2->_bodys[0]->_value,true));

		// get objects on farm
		$objects = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['objectsArray'];
		//file_put_contents('world.txt', print_r($objects, true));
		// FarmFIX/object split
		$newobjects = serialize($objects);
		$cleanedobjects = str_replace("'", "''", $newobjects);
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
					'objects', '" . $cleanedobjects . "');";
		// save collection counters to a file
		$nAQ = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighborActionQueue']['m_actionQueue'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'nactionqueue', '" . serialize($nAQ) . "');";		
		$nAL = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighborActionLimits']['m_neighborActionLimits'][date('ymd', $_SESSION['servertime'])];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'nactionlimit', '" . serialize($nAL) . "');";			
		$c_count = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['collectionCounters'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'ccount', '" . serialize($c_count) . "');";
		$c_bushel = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['buffs']['BBushel']['crop'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'cbushel', '" . serialize($c_bushel) . "');";
		$c_busheltime = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['buffs']['BBushel']['time'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'cbusheltime', '" . serialize($c_busheltime) . "');";		
		$craftstate = @$amf2->_bodys[0]->_value['data'][0]['data']['craftingState'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'craftstate', '" . serialize($craftstate) . "');";						
		// save lonelyanimals to a file
		$animallinks = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['lonelyAnimals'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'ralinks', '" . serialize($animallinks) . "');";
		// save license information
		$licenses = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['licenseManager']['licenses'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'licenses', '" . serialize($licenses) . "');";
		$storagedata = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'storagedata', '" . serialize($storagedata) . "');";		
		$incraftbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-7'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'craftbox', '" . serialize($incraftbox) . "');";
		// save giftbox info for plugins
		$ingiftbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-1'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'giftbox', '" . serialize($ingiftbox) . "');";
		foreach ($ingiftbox as $key => $item)
		$ingiftbox[$key] = isset($item[0])?$item[0]:0;
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'ingiftbox', '" . serialize($ingiftbox) . "');";
		// save consumable info for plugins
		$inconbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-6'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'consumebox', '" . serialize($inconbox) . "');";
		foreach ($inconbox as $key => $item) {
			$uInfo = Units_GetUnitByCode($key);
			if (@$uInfo['className'] == 'CSeedPackage') {
				$inseedbox[$key] = isset($item[0])?$item[0]:0;
				unset($inconbox[$key]);
				continue;
			}
			$inconbox[$key] = isset($item[0])?$item[0]:0;
		}
			
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'inconbox', '" . serialize($inconbox) . "');";
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'inseedbox', '" . serialize($inseedbox) . "');";		
		// save storage info for plugins
		$instorage = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-2'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'storagebox', '" . serialize($instorage) . "');";		
		foreach ($instorage as $key => $item)
		$instorage[$key] = $item[0];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'instorage', '" . serialize($instorage) . "');";
		// save neighbors list
		$neighbors = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighbors'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'neighbors', '" . serialize($neighbors) . "');";
		$bsStats = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['breedingState'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'bsstats', '" . serialize($bsStats) . "');";		
		$pneighbors = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['pendingNeighbors'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'pneighbors', '" . serialize($pneighbors) . "');";		
		// save crop mastery list
		$cropmastery = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['masteryCounters'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'cropmastery', '" . serialize($cropmastery) . "');";
		$cropmasterycnt = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['mastery'];
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'cropmasterycnt', '" . serialize($cropmasterycnt) . "');";
		if (isset($amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['featureCredits']['farm'])) {
			$featurecred = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['featureCredits']['farm'];
		} else {
			$featurecred = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['featureCredits'];
		}
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'featurecred', '" . serialize($featurecred) . "');";
		// save ribbon data
		$achievements = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achCounters'];
		$earned_ribbons = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achievements'];

		$ribbon_merge = array();
		foreach($achievements as $name => $data)
		{
			$ribbon_merge[$name]['count'] = $data;
		}

		if (@count($earned_ribbons) > 0)
		{
			foreach($earned_ribbons as $name => $data)
			{
				$ribbon_merge[$name]['earned'] = $data;
			}
		}
		$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'ach_count', '" . serialize($ribbon_merge) . "');";
		$_SESSION['vDataStoreDB']->exec($uSQL);
		unset($uSQL, $amf2);
		// save_botarray ($array, $filename);
	}
	if ($inittype == 'full') {
		$amf = CreateRequestAMF('', 'UserService.postInit');
		$serializer = new AMFSerializer();
		$result = $serializer->serialize($amf); // serialize the data
		$answer = Request('', $result);
		$amf2 = new AMFObject($answer);
		$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
		$deserializer2->deserialize($amf2); // run the deserializer
		$res2 = CheckAMF2Response($amf2);
		if ($res2 == 'OK')
		{
			file_put_contents('world2.txt', print_r($amf2->_bodys[0]->_value,true));
			$availBushels = @$amf2->_bodys[0]->_value['data'][0]['data']['marketView']['marketStalls'];
			$availGoods = @$amf2->_bodys[0]->_value['data'][0]['data']['marketView']['craftedGoods'];
			$bsInfo = @$amf2->_bodys[0]->_value['data'][0]['data']['breedingState'];
			$uSQL = "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'availbushels', '" . serialize($availBushels) . "');";
			$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'availgoods', '" . serialize($availGoods) . "');";
			$uSQL .= "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'bsinfo', '" . serialize($bsInfo) . "');";			
			$_SESSION['vDataStoreDB']->exec($uSQL);
			unset($uSQL, $amf2);
		}
		Hook('after_load_farm');

		$res = ($res == 'OK' && $res2 == 'OK') ? 'OK' : 'Init: ' . $res . ' - PostInit: ' . $res2;
	}
	$T2 = time();
	$T2 -= $T;
	if ($res == 'OK') {
		AddLog2("Initialization Took: " . $T2 . " Seconds");
	} else {
		AddLog2("Initialization Failed: " . $res);
	}
	return $res;
}
// ------------------------------------------------------------------------------
// CheckAMF2Response check
//  @param object $request AMF2 response
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function CheckAMF2Response($amf2)
{
	if (@$amf2->_bodys[0]->_value['errorType'] != 0)
	{
		if ($amf2->_bodys[0]->_value['errorData'] == "There is a new version of the farm game released")
		{
			AddLog2('FV UPDATE: New Version of FV Released');
			AddLog2('Work Will Restart in 15 Seconds');
			@unlink('sqlite_check.txt');
			die();
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token value failed")
		{
			AddLog2('FV FUBAR: Game Opened in Another Browser');
			AddLog2('Work Will Restart in 15 Seconds');
			die();
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token too old")
		{
			AddLog2('FV FUBAR: Your Session Has Expired');
			AddLog2('Work Will Restart in 15 Seconds');
			die();
		}
		else if ($amf2->_bodys[0]->_value['errorType'] == 29)
		{
			AddLog2('FV FUBAR: Server Sequence Was Reset');
			$_SESSION['sequence'] = 0;
		}
		else
		{
			$res = "Error: " . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'];
			DoInit();
		}
	}
	else if (!isset($amf2->_bodys[0]->_value['data'][0]))
	{
		$res = "BAD AMF REPLY (OOS?)";
	}
	else if (isset($amf2->_bodys[0]->_value['data'][0]['data']) && ($amf2->_bodys[0]->_value['data'][0]['data'] == 'success'))
	{
		$res = 'OK';
	}
	else if (isset($amf2->_bodys[0]->_value['data'][0]['data']) && ($amf2->_bodys[0]->_value['data'][0]['data'] == '6uccess'))
	{
		$res = 'OK';
	}
	else if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] != 0))
	{
		$res = $amf2->_bodys[0]->_value['data'][0]['errorType'] . " " . $amf2->_bodys[0]->_value['data'][0]['errorData'] . ' ' . $amf2->_bodys[0]->_value['data'][0]['data']['error'];
	}
	else if (isset($amf2->_bodys[0]->_value['data'][0]['data']['error']) && (strlen($amf2->_bodys[0]->_value['data'][0]['data']['error']) > 0))
	{
		$res = $amf2->_bodys[0]->_value['data'][0]['data']['error'];
	}
	else
	{
		$res = 'OK';
	}
	return $res;
}

function RequestAMF($amf, $includeamf = false)
{
	$amf2 = RequestAMFIntern($amf);
	Check4Rewards($amf2);
	$res = CheckAMF2Response($amf2);
	if ($includeamf) return array('res' => $res, 'amf2' => $amf2);
	return $res;
}
// ------------------------------------------------------------------------------
// RequestAMFIntern sends AMF request to the farmville server
//  @param object $request AMF request
//  @return object $amf2
// ------------------------------------------------------------------------------
function RequestAMFIntern($amf)
{

	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer

	return $amf2;
}

function CreateMultAMFRequest($amf, $cnt, $req = '',$func)
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

function Check4Rewards($amf2)
{
	$rewards = unserialize(fBGetRewardStore('rewards'));
	$reward = array();
	if(!isset($amf2->_bodys[0]->_value['data'])) return;
	foreach($amf2->_bodys[0]->_value['data'] as $key=>$returned)
	{
		$tmp = print_r($returned,true);
		if(stripos($tmp,'reward.php') !== false)
		{
			if(stripos($tmp, '[k] => reward.php?frHost') !== false) continue;
			//file_put_contents('returns.txt', print_r($returned,true), FILE_APPEND);
		} else continue;
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['rewardUrl'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['rewardItem']),
					'timestamp' => time());
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['rewardLink'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['animalName']),
					'timestamp' => time());

		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['rewardLink'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['rewardItem']),
					'timestamp' => time());		
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['rewardLink'],
					'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['itemCode']),
					'timestamp' => time());				

		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['itemFoundRewardUrl'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['itemShareName']),
					'timestamp' => time());	

		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['fertilizeRewardLink'],
					'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['goodieBagRewardItemCode']),
					'timestamp' => time());		
		if (isset($amf2->_bodys[0]->_value['data'][$key]['data']['data']['fuelRewardLink']))
		{
			@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['fuelRewardLink'],
					'rewardCode' => '2A',
					'timestamp' => time());	
		}
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['data']['rewardLink'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['data']['rewardItem']),
					'timestamp' => time());
		if (isset($amf2->_bodys[0]->_value['data'][$key]['data']['data']['fuelDiscoveryRewardLink']))
		{
			@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['data']['fuelDiscoveryRewardLink'],
					'rewardCode' => '2A',
					'timestamp' => time());
		}
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['foalRewardLink'],
					'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['foalCode']),
					'timestamp' => time());				
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data'][0]['rewardLink'],
					'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['data'][0]['recipeId']),
					'timestamp' => time());						
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['buyResponse']['buyResults'][0]['rewardLink'],
					'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['buyResponse']['buyResults'][0]['recipe']),
					'timestamp' => time());	
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['data']['rewardLink'],
					'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['data']['harvestItem']),
					'timestamp' => time());					
		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['goals'][0]['link'],
						'rewardCode' => trim($amf2->_bodys[0]->_value['data'][$key]['goals'][0]['code']),
						'timestamp' => time());			

		@$reward[] = array('rewardLink' => $amf2->_bodys[0]->_value['data'][$key]['collectionCounters'][0]['link'],
						'rewardItem' => trim($amf2->_bodys[0]->_value['data'][$key]['collectionCounters'][0]['collectable']),
						'timestamp' => time());	
		foreach ($reward as $key=>$tmp)
		{
			if (empty($tmp['rewardLink'])) unset($reward[$key]);
			if (strlen($tmp['rewardLink']) <= 1 && strlen(@$tmp['rewardItem']) <= 1 && strlen(@$tmp['rewardCode']) <= 1) unset($reward[$key]);
			if (stripos($tmp['rewardLink'],'gifts.php?giftRecipient') !== false) unset($reward[$key]);
		}
		if (!empty($reward))
		{
			$rewards[] = $reward;
			//AddLog2(print_r($rewards,true));
		}
	}
	if (isset($rewards) && !empty($rewards)) {
		$uSQL = "INSERT OR REPLACE INTO rewardstore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'rewards', '" . serialize($rewards) . "')";
		$_SESSION['vRewardStoreDB']->exec($uSQL);
	}
}

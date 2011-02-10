<?php
class fBGifts
{
	var $userId, $_fBGiftsDBM, $error, $settings, $cookie;

	private function _fBGifts_checkDB()
	{
		if(!empty($this->error))
		{
			AddLog2($this->error);
			return;
		}
		$q = @$this->_fBGiftsDBM->query('SELECT * FROM settings LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Settings Table');
			$fvSQL = 'CREATE TABLE
						settings (
						settings_name CHAR(250) PRIMARY KEY,
						settings_value TEXT
			)';
			$this->_fBGiftsDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "')";
			$this->_fBGiftsDBM->queryExec($fvSQL);

		}
		$q = @$this->_fBGiftsDBM->query('SELECT * FROM knownapps LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating knownapps Table');
			$fvSQL = 'CREATE TABLE
						knownapps (
						knownapps_appid TEXT PRIMARY KEY,
						knownapps_name TEXT,
						knownapps_canaccept INTEGER DEFAULT 1,
						knownapps_canreturn INTEGER DEFAULT 0
			)';
			$this->_fBGiftsDBM->queryExec($fvSQL);

		}
		$q = @$this->_fBGiftsDBM->query('SELECT * FROM giftlog LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating giftlog Table');
			$fvSQL = 'CREATE TABLE
						giftlog (
						giftlog_id INTEGER PRIMARY KEY,
						giftlog_timestamp NUMERIC DEFAULT 0,
						giftlog_appname TEXT,
						giftlog_text TEXT,
						giftlog_link TEXT,
						giftlog_accept INTEGER DEFAULT 0,
						giftlog_return INTEGER DEFAULT 0
			)';
			$this->_fBGiftsDBM->queryExec($fvSQL);
		}

	}

	//Function fBGifts class initializer
	function fBGifts($inittype = '')
	{
		$this->userId = $_SESSION['userId'];

		if(!is_numeric($this->userId))
		{
			$this->error = "faceBot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fBGiftsDBM = new SQLiteDatabase(fBGifts_Path . PluginF(fBGifts_Main));
		if(!$this->_fBGiftsDBM)
		{
			$this->error = 'fBGifts - Database Error';
			return;
		}

		//Get Settings
		$this->settings = $this->fBGetSettings();

		//Load the world from Z*
		if($this->settings === false)
		{
			$this->_fBGifts_checkDB();//Database doesn't exist, create
		}
		$this->_fBUpdateSettings();//Insert initial settings
		if ($inittype != 'formload')
		{
			$this->_fBGetCookie();
			AddLog2('fBGifts: facebook Cookie Location: ' . $this->cookie);
			$this->_acceptGifts();
		}
	}

	function fBGetSettings()
	{
		$fvSQL = 'SELECT * FROM settings';
		@$q = $this->_fBGiftsDBM->query($fvSQL);
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

	function fBGetKnownApps()
	{
		$fvSQL = 'SELECT * FROM knownapps ORDER BY knownapps_name';
		$q = $this->_fBGiftsDBM->query($fvSQL);
		if($q !== false)
		{
			$results = $q->fetchAll(SQLITE_ASSOC);
			return $results;
		}
		return false;
	}

	function fBGetLogs()
	{
		$fvSQL = 'SELECT * FROM giftlog ORDER BY giftlog_timestamp DESC';
		$q = $this->_fBGiftsDBM->query($fvSQL);
		if($q !== false)
		{
			$results = $q->fetchAll(SQLITE_ASSOC);
			return $results;
		}
		return false;
	}

	function fBDeleteLog()
	{
		$fvSQL = 'DELETE FROM giftlog';
		$q = $this->_fBGiftsDBM->query($fvSQL);
		return;
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

	function fBDoSettings($postvars)
	{
		unset($postvars['submit']);
		$giftopts = serialize($postvars);
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('giftopts','$giftopts');";
		$this->_fBGiftsDBM->queryExec($fvSQL);
	}

	private function _fBUpdateSettings()
	{
		$fvSQL = "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('102452128776','FarmVille', '1', '1'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('101539264719','Café World', '1', '0'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('201278444497','FrontierVille', '1', '0'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('234860566661','Treasure Isle', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('10979261223','Mafia Wars Game', '1', '0'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('46755028429','Castle Age', '1', '0'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('271022655694','Haven', '1', '0'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('26947445683','Country Life', '1', '1'); ";																			
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('167746316127','Zoo World', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('56748925791','Farm Town', '1', '0'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('2389801228','Texas HoldEm Poker', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('151044809337','FishVille', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('25287267406','Vampire Wars', '1', '0'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('342684208824','Backyard Monsters', '1', '0'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('163965423072','Social City', '1', '0'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('8630423715','Sorority Life', '1', '0'); ";		
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('43016202276','Restaurant City', '1', '1'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('165747315075','Country Story', '1', '1'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('121763384533823','Country Life (lite)', '1', '1'); ";		
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('163576248142','PetVille', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('2405948328','Likeness', '1', '1'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('14852940614','Birthday cards', '1', '1'); ";	
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('2339854854','Horoscopes', '1', '1'); ";
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('2345673396','Hug Me', '1', '1'); ";		
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('2601240224','RockYou Live', '1', '1'); ";			
		$fvSQL .= "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept, knownapps_canreturn) " .
						"values('156383461049284','Farmville-Chinese', '1', '1'); ";	
		
		$this->_fBGiftsDBM->queryExec($fvSQL);

		//$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('higherLevelStep','" . $farm3 . "')";
		//$this->_fBGiftsDBM->queryExec($fvSQL);

	}

	private function _fBGetCookie()
	{

		$newcookiestr = trim(file_get_contents($_SESSION['base_path'] .  $_SESSION['userId'] . '_cookie.txt'));
		return $newcookiestr;
	}

	private function _getGifts()
	{
		$response = $this->_fBGiftsGet("http://apps.facebook.com/reqs.php");
		//AddLog2($response);
		if (empty($response)) { return; }
		preg_match_all('/action="\/ajax\/reqs\.php" method="post".*?<\/form>/ims', $response, $result);
		$giftRequests = array();
		foreach($result[0] as $key => $form) {
			preg_match_all('/name="([^"]*)" value="([^"]*)"/ims', $form, $nameVals);
			preg_match_all('/<input[^>]*value="([^\"]*)"[^>]*name="actions\[([^>]*)][^>]*>?/ims', $form, $actions);
			preg_match_all('/<a href="http:\/\/apps\.facebook\.com\/.*?>(.*?)<\/a>/ims', $form, $appNameVals);
			preg_match_all('/name="fb_dtsg" value="([^"]*)"/ims', $form, $DTSGVals);
			preg_match_all('/<span fb_protected="true" class="fb_protected_wrapper">(.*?)<\/span>/ims', $form, $giftText);
			@$giftInfo = trim(strip_tags($giftText[1][0]));
			$appId = '';
			$postVars = array();
			for($i = 0; $i < count($nameVals[1]); $i++) {
				if($nameVals[1][$i] == 'params[app_id]') $appId = $nameVals[2][$i];
				$postVars[$nameVals[1][$i]] = urlencode(html_entity_decode($nameVals[2][$i], ENT_QUOTES, 'UTF-8'));
			}
			//if($vAppId<>'102452128776') continue;
			$actionName = '';
			$actionUrl = '';
			for($i = 0; $i < count($actions[1]); $i++) {
				if($actions[2][$i]<>'reject') {
					$actionName = $actions[1][$i];
					$actionUrl = html_entity_decode($actions[2][$i]);
					$postVars['actions['.urlencode(html_entity_decode($actions[2][$i], ENT_QUOTES, 'UTF-8')).']'] = str_replace('+', '%20', urlencode($actions[1][$i]));
					break;
				}
			}
			$appName = '';
			if(count($appNameVals) > 0) {
				@$appName = $appNameVals[1][0];
			}
			if(count($DTSGVals) > 0) {
				$DTSG = $DTSGVals[1][0];
			}
			if (!empty($appName)) {
				$postVars['post_form_id_source'] = 'AsyncRequest';
				$giftRequests[$key] = array();
				$giftRequests[$key]['name'] = utf8_decode($appName);
				$giftRequests[$key]['app_id'] = $appId;
				$giftRequests[$key]['app_name'] = utf8_decode($appName);
				$giftRequests[$key]['action_name'] = $actionName;
				$giftRequests[$key]['gift_text'] = $giftInfo;
				$giftRequests[$key]['action_url'] = $actionUrl;
				$giftRequests[$key]['post_data'] = $postVars;
				$giftRequests[$key]['fb_dtsg'] = $DTSG;
				$appName = utf8_decode($appName);
				if (!empty($giftRequests[$key]['app_id']) && !empty($giftRequests[$key]['app_name']))
				{
					$fvSQL = "INSERT OR IGNORE INTO knownapps(knownapps_appid, knownapps_name, knownapps_canaccept) values('$appId','$appName',1)";
					$this->_fBGiftsDBM->queryExec($fvSQL);
				}
			}
		}
		return $giftRequests;
	}

	private function _fBGiftsPost($url, $postData='')
	{
		$fields_string = '';
		if (is_array($postData)) {
			foreach($postData as $key=>$value) {
				if ($value != null) {
					@$fields_string .= $key.'='.$value.'&';
				} else {
					@$fields_string .= $key.'&';
				}
			}
			//AddLog2($url);
			rtrim($fields_string,'&');
			//AddLog2($fields_string);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, count($postData));
			curl_setopt($ch, CURLOPT_TIMEOUT, 45);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
			curl_setopt($ch, CURLOPT_COOKIE, $this->_fBGetCookie());
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
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
				AddLog2("fBGifts: Error 404/Page Not Found");
				return;
			}
			if($httpCode == 500) {
				AddLog2("fBGifts: Error 500/Internal Server Error");
				return;
			}
			if(empty($response)) {
				AddLog2("fBGifts: Empty Response Returned");
				return;
			}
			curl_close ($ch);
			return $response;
		}
		return;
	}

	private function _fBGiftsGet($url = '')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
		curl_setopt($ch, CURLOPT_COOKIE, $this->_fBGetCookie());
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
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
			AddLog2("fBGifts: Error 404/Page Not Found");
			return;
		}
		if($httpCode == 500) {
			AddLog2("fBGifts: Error 500/Internal Server Error");
			return;
		}
		if(empty($response)) {
			AddLog2("fBGifts: Empty Response Returned");
			return;
		}
		curl_close ($ch);
		return $response;
	}


	private function _acceptGifts()
	{

		$giftReqs = $this->_getGifts();
		$gBCount = count(unserialize(fBGetDataStore('ingiftbox')));
		$cBCount = count(unserialize(fBGetDataStore('inconbox')));
		AddLog2('fBGifts: '.count($giftReqs).' Gifts On Gift Page');
		$giftSettings = unserialize($this->settings['giftopts']);
		if(is_array($giftReqs)) {
			if(count($giftReqs)>0) {
				$count=0;
				foreach($giftReqs as $key => $data) {
					if (!isset($giftSettings[$data['app_id'] . '_accept'])) continue;
					if ((($gBCount + $cBCount) >= 500) && $data['app_id'] == '102452128776')
					{
						AddLog2('fBGifts: Giftbox or Consumable Box Full, Skipping Farmville Gift');
						continue;
					} elseif ($data['app_id'] == '102452128776') { $gBCount++; }
					$count++;
					$accepted = 0;
					$returned = 0;
					//Remove Link From Gifts Page
					
					$discard = $this->_fBGiftsPost("http://www.facebook.com/ajax/reqs.php?__a=1", $data['post_data']);
					//file_put_contents('debug/data.txt', print_r($data,true), FILE_APPEND);
					//Submit the link to goto the page
					$giftAction = $this->_fBGiftsGet($data['action_url']);
					//file_put_contents('debug/data.txt', $giftAction, FILE_APPEND);
					if (!empty($giftAction)) {
						$accepted = 1;
						AddLog2("[$count] fBGifts: Accept Gift From " . $data['app_name'] . ' - Success');
					} else {
						$accepted = 9;
						AddLog2("[$count] fBGifts: Accept Gift From " . $data['app_name'] . ' - Failed');
					}
					if (isset($giftSettings[$data['app_id'] . '_return']) && $accepted == 1) {
						preg_match_all('/class="fb_protected_wrapper"><form(.*?)<\/div>/ims', $giftAction, $forms);
						foreach($forms[0] as $key => $form) {
							if(stripos($form, "thank you") !== false || stripos($form, 'send to') !== false || stripos($form, 'é€') !== false) {
								$postdata = array();
								$arr1 = '';
								$arr2 = '';
								preg_match_all('/.*action="([^"]*)".*/ims', $form, $acts);
								preg_match_all('/.*giftRecipient=([^&]*).*type="([^"]*)".*content="([^"]*)".*id="([^"]*)".*post_form_id=([^&]*).*/ims', $form, $fields);
								preg_match('/content="([^"]*)"/sim', $form, $content);
								//AddLog2(print_r($content,true));
								$form = html_entity_decode($form);
								preg_match_all('/PlatformInvite.*\{(.*)\}/sim', $form, $newfields);
								$newdata = str_replace('"', '', $newfields[1][0]);
								$arr1 = explode(',', $newdata);
								foreach ($arr1 as $tmpdata)
								{
									$tmp = explode(':', $tmpdata);
									$arr2[$tmp[0]] = $tmp[1];
								}
								$postdata['app_id'] = $data['app_id'];
								$postdata['to_ids[0]'] = $arr2['prefill'];
								$postdata['request_type'] = urlencode($arr2['request_type']);
								$postdata['invite'] = $arr2['invite'];
								$postdata['content'] = urlencode($content[1]);
								$postdata['preview'] = 'true';
								$postdata['is_multi'] = $arr2['is_multi'];
								$postdata['is_in_canvas'] = $arr2['is_in_canvas'];
								$postdata['form_id'] = $arr2['request_form'];
								$postdata['prefill'] = 'true';
								$postdata['message'] = '';
								$postdata['donot_send'] = 'false';
								$postdata['include_ci'] = $arr2['include_ci'];
								$postdata['__d'] = 1;
								$postdata['post_form_id'] = $fields[5][0];
								$postdata['fb_dtsg'] = $data['fb_dtsg'];
								$postdata['lsd'] = null;
								$postdata['post_form_id_source'] = 'AsyncRequest';
								$discard = $this->_fBGiftsPost("http://apps.facebook.com/fbml/ajax/prompt_send.php?__a=1", $postdata);
								//file_put_contents('debug/discard.txt', $discard, FILE_APPEND);
								//unset($postdata['request_type']);
								//$postdata['&request_type'] = urlencode($arr2['request_type']);
								$postdata['preview'] = 'false';
								$retGift = $this->_fBGiftsPost("http://apps.facebook.com/fbml/ajax/prompt_send.php?__a=1", $postdata);
								//AddLog2($acts[1][0]);
								$retGift2 = $this->_fBGiftsPost(html_entity_decode($acts[1][0]), array());
								if (stripos(strip_tags($retGift),'"error":0')) {
									$returned = 1;
									AddLog2("[$count] fBGifts: Returned Gift From " . $data['app_name'] . ' - Success');
								} else {
									$returned = 9;
									AddLog2("[$count] fBGifts: Returned Gift From " . $data['app_name'] . ' - Failed');
								}
								//file_put_contents('debug/postdata.txt', print_r($postdata,true), FILE_APPEND);
								//file_put_contents('debug/formdata.txt', print_r($form,true), FILE_APPEND);
								//file_put_contents('debug/giftaction.txt', $giftAction, FILE_APPEND);
								//file_put_contents('debug/returngift.txt', $retGift, FILE_APPEND);
								//file_put_contents('debug/returngift2.txt', $retGift2, FILE_APPEND);
								//sleep(500);
								break;
							}
						}
					}
					$data['gift_text'] = str_replace("'", "''", @$data['gift_text']);
					$fvSQL = "INSERT INTO giftlog(giftlog_timestamp, giftlog_appname, giftlog_text, giftlog_link, giftlog_accept, giftlog_return) " .
											"values('" . time() . "','" . $data['app_name'] . "','" . $data['gift_text'] . "','" . $data['action_url'] . "','$accepted', '$returned')";
					$this->_fBGiftsDBM->queryExec($fvSQL, $error);
					if (!empty($error)) {
						AddLog2 ($error . " " . $fvSQL);
					}
				}
			}
		}

	}
}

?>
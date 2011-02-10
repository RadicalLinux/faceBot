<?php
class fvLinks
{
	var $userId, $settings, $_fsManagerDBM;

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
		}
		$q = @$this->_fsManagerDBM->query('SELECT * FROM rewardlinks LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Reward Links Table');
			$fvSQL = 'CREATE TABLE
						rewardlinks (
						rewardlinks_link CHAR(250) PRIMARY KEY,
						rewardlinks_timestamp NUMERIC DEFAULT 0
			)';
			$this->_fsManagerDBM->queryExec($fvSQL);
			$fvSQL = "INSERT INTO settings(settings_name,settings_value) values('userid','" . $this->userId . "')";
			$this->_fsManagerDBM->queryExec($fvSQL);
		}
		$q = @$this->_fsManagerDBM->query('SELECT * FROM knownlinks LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating Known Links Table');
			$fvSQL = 'CREATE TABLE
						knownlinks (
						knownlinks_name CHAR(250) PRIMARY KEY,
						knownlinks_post TEXT
			)';
			$this->_fsManagerDBM->queryExec($fvSQL);
		}
		$q = @$this->_fsManagerDBM->query('SELECT * FROM locales LIMIT 1');
		if($q === false)
		{
			AddLog2('Creating facebook Post Table');
			$fvSQL = 'CREATE TABLE
						locales (
						locales_key CHAR(250) PRIMARY KEY,
						locales_value TEXT
			)';
			$this->_fsManagerDBM->queryExec($fvSQL);
		}
	}

	//Function fvLinks class initializer
	function fvLinks($inittype = '')
	{
		$this->userId = $_SESSION['userId'];
		$this->error = '';
		$this->haveWorld = true;
		$this->flashRevision = $_SESSION['flashRevision'];
		if(!is_numeric($this->userId))
		{
			$this->error = "Farmville Bot Not Initialized/User Unknown";
			return;
		}

		//Open Databases
		$this->_fsManagerDBM = new SQLiteDatabase(fvLinks_Path . PluginF(fvLinks_Main));
		$this->_fsManager_checkDB();
		if(!$this->_fsManagerDBM)
		{
			$this->error = 'fvLinks - Database Error';
			return;
		}

		//Get Settings
		$this->settings = $this->fsGetSettings();
		if($inittype == 'formload')
		{
			if(empty($this->settings))
			{
				$this->error = 'Please allow fvLinks to run a cycle';
			}
			return;
		}
		$this->_fsUpdateSettings();
		$this->_fsExpireLinks();
		//$this->_fsGetLocales();
		$this->GetALinks();
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

	function GetLinkTypes($fbid = '')
	{

		if (is_numeric($fbid)) {
			$vSQL = "SELECT content FROM rewardstore WHERE userid='" . $fbid . "' AND storetype='rewards'";
			$rlinks = unserialize(@$_SESSION['vRewardStoreDB']->querySingle($vSQL));
		} else {
			$rlinks = unserialize(fBGetRewardStore('rewards'));
		}
		//echo nl2br(print_r($rlinks,true));
		foreach ($rlinks as $url)
		foreach ($url as $key=>$link)
		{
			if (strlen($link['rewardLink']) == 1) continue;
			if (isset($newurl[$link['rewardLink']]) && empty($newurl[$link['rewardLink']]['rewardItem']) && empty($newurl[$link['rewardLink']]['rewardCode'])) {
				@$newurl[$link['rewardLink']] = array('rewardItem' => $link['rewardItem'], 'rewardCode' => $link['rewardCode'], 'timestamp' => $link['timestamp']);
			} elseif (!isset($newurl[$link['rewardLink']])) {
				@$newurl[$link['rewardLink']] = array('rewardItem' => $link['rewardItem'], 'rewardCode' => $link['rewardCode'], 'timestamp' => $link['timestamp']);
			} else { unset($url[$key]); }
		}
		foreach ($newurl as $key=>$furl)
		{
			preg_match_all('/frType=(.*)&key=/sim', $key, $cat);
			$rtypes[$cat[1][0]][] = array('rewardLink' => $key, 'rewardItem' => $furl['rewardItem'], 'rewardCode' => $furl['rewardCode'], 'timestamp' => $furl['timestamp']);
		}
		ksort($rtypes);
		foreach (array_keys($rtypes) as $cat)
		{
			if (!empty($cat)) {
				$fvSQL = "INSERT OR REPLACE INTO knownlinks(knownlinks_name,knownlinks_post) values('" . $cat . "','0')";
				$this->_fsManagerDBM->queryExec($fvSQL);
			}
		}
		return $rtypes;
	}

	function GetALinkTypes()
	{
		$rlinks = unserialize(fBGetDataStore('ralinks'));
		return $rlinks;
	}

	function GetALinks()
	{
		$rlinks = unserialize(fBGetDataStore('ralinks'));
		$i = 0;
		$links = array();
		foreach ($rlinks as $lkey=>$linfo)
		{
			$Md5string = MD5($linfo['animalType'] . "farmville1is4the3best4game4ever" . $linfo['id'] . "8415");
			$links[$i]['url'] = 'http://apps.facebook.com/onthefarm/lonelycow.php?owner_id=' . $_SESSION['userId'] . '&lonely_cow_id=' . $linfo['id'] . '&animalType=' . $linfo['animalType'] . '&lonely_cow_sig=' . $Md5string . '&_fb_noscript=1';
			$links[$i]['helpers'] = count(array_keys($linfo['helpers']));
			$links[$i]['animal'] = $linfo['animalType'];
			$links[$i]['time'] = $linfo['timeRequested'];
			$i++;
		}
		return $links;
	}


	function fsDoSettings($post)
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('sharelinks','" . serialize($post) . "')";
		$this->_fsManagerDBM->queryExec($fvSQL);

	}

	private function _fsUpdateSettings()
	{
		$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('flashRevision','" . $_SESSION['flashRevision'] . "')";
		$this->_fsManagerDBM->queryExec($fvSQL);

	}

	private function _fsGetLocales()
	{
		if ($this->settings['flashRevision'] != $this->settings['unitversion'])
		{
			$doc1 = file_get_contents('./farmville-xml/' . $this->flashRevision . '_flashLocaleXML.xml');
			if (!$doc1) AddLog2('Unable to get flashLocaleXml.xml');
			if(!$doc1)
			{
				return;
			}
			AddLog2('fvLinks is updating facebook post text');
			$xmlDoc = new DOMDocument();
			$xmlDoc->loadXML($doc1);
			$items = $xmlDoc->getElementsByTagName("bundleLine");
			$this->_fsManagerDBM->queryExec('BEGIN;');
			foreach($items as $item)
			{
				$k = $item->getAttribute('key');
				$vT = $item->getElementsByTagName('value');
				$v = $vT->item(0)->nodeValue;
				$v = str_replace("'", "''", $v);
				$fvSQL = "INSERT OR REPLACE INTO locales (locales_key, locales_value) values('" . trim($k) . "', '" . trim($v) . "');";
				$this->_fsManagerDBM->queryExec($fvSQL,$error);
				if (!empty($error)) { AddLog2($error); }
			}
			$this->_fsManagerDBM->queryExec('COMMIT;');
			AddLog2('fvLinks has finished updating facebook post text');
			$fvSQL = "INSERT OR REPLACE INTO settings(settings_name,settings_value) values('unitversion','". $this->flashRevision . "')";
			$this->_fsManagerDBM->queryExec($fvSQL);
			unset($xmlDoc);
		}

	}

	function GetKnownTypes()
	{
		$fvSQL = "SELECT * FROM knownlinks ORDER BY UPPER(knownlinks_name)";
		$q = $this->_fsManagerDBM->query($fvSQL);
		$results = $q->fetchAll(SQLITE_ASSOC);
		return $results;
	}

	function fvClearLinks()
	{
		$uSQL = "DELETE FROM rewardstore WHERE userid='" . $_SESSION['userId'] . "' AND storetype='rewards'";
		$_SESSION['vRewardStoreDB']->exec($uSQL);
	}

	function DoWork()
	{

		$grabs = unserialize($this->settings['sharelinks']);
		if (empty($grabs)) return;
		$gbcount = array_sum(unserialize(fBGetDataStore('ingiftbox')));
		$cbcount = array_sum(unserialize(fBGetDataStore('inconbox')));
		$count = 0;
		foreach ($grabs as $fbid=>$info)
		{
			if (@$info['share'] != 1)  continue;
			$links = $this->GetLinkTypes($fbid);
			foreach ($links as $cat=>$link)
			{
				if (@$info[$cat] == 1) continue;
				foreach ($link as $nlink)
				{
					if (($gbcount + $cbcount) + $count >= 500) { AddLog2('fvLinks: GiftBox/Consumable Box Full'); return; }
					$key = str_replace('&key={*key*}', '', $nlink['rewardLink']);
					if ($this->fvCheckLink($key) === true) continue;
					$fvSQL = "INSERT OR REPLACE INTO rewardlinks(rewardlinks_link, rewardlinks_timestamp) values('$key','". time() . "')";
					$this->_fsManagerDBM->queryExec($fvSQL);
					$answer = $this->_fvLinksGet('http://apps.facebook.com/onthefarm/' . $key);
					$count++;
					preg_match_all('/<h3>(.*)<\/h3>/sim', $answer, $text);
					if (!empty($answer) && empty($text[1][0])) { AddLog2('fvLinks: Unknown Gift Response'); continue; }
					if ($text[1][0] == "Hey there, farmer! You've claimed all the rewards you can from your friends today.  Try again tomorrow!") break;
					if (!empty($answer)) AddLog2($text[1][0]);
					sleep(3);
				}

			}
		}
	}

	function fvCheckLink($link = '')
	{
		$fvSQL = "SELECT * FROM rewardlinks WHERE rewardlinks_link='" . $link . "'";
		$q = $this->_fsManagerDBM->query($fvSQL);
		$results = $q->fetchSingle();
		return (@$results === false ? false : true);
	}

	private function _fvLinksGet($url = '')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
		curl_setopt($ch, CURLOPT_COOKIE, $this->_fvLinksGetCookie());
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
			AddLog2("fvLinks: Error 404/Page Not Found");
			return;
		}
		if($httpCode == 500) {
			AddLog2("fvLinks: Error 500/Internal Server Error");
			return;
		}
		if(empty($response)) {
			AddLog2("fvLinks: Empty Response Returned");
			return;
		}
		curl_close ($ch);
		return $response;
	}

	private function _fvLinksGetCookie()
	{
		$newcookiestr = trim(file_get_contents($_SESSION['base_path'] .  $_SESSION['userId'] . '_cookie.txt'));
		return $newcookiestr;
	}

	private function _fsExpireLinks()
	{
		$rlinks = unserialize(fBGetRewardStore('rewards'));
		//echo nl2br(print_r($rlinks,true));
		foreach ($rlinks as $url)
		foreach ($url as $key=>$link)
		{
			if (strlen($link['rewardLink']) == 1) continue;
			if (isset($newurl[$link['rewardLink']]) && empty($newurl[$link['rewardLink']]['rewardItem']) && empty($newurl[$link['rewardLink']]['rewardCode'])) {
				@$newurl[$link['rewardLink']] = array('rewardLink' => $link['rewardLink'], 'rewardItem' => $link['rewardItem'], 'rewardCode' => $link['rewardCode'], 'timestamp' => $link['timestamp']);
			} elseif (!isset($newurl[$link['rewardLink']])) {
				@$newurl[$link['rewardLink']] = array('rewardLink' => $link['rewardLink'], 'rewardItem' => $link['rewardItem'], 'rewardCode' => $link['rewardCode'], 'timestamp' => $link['timestamp']);
			} else { unset($link[$key]); }
		}
		$mytime = time() - (3600 * 24);
		if (isset($newurl)) {
			foreach ($newurl as $tmp)
			{
				if ($tmp['timestamp'] >= $mytime) $final[0][] = $tmp;
			}
			$uSQL = "INSERT OR REPLACE INTO rewardstore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'rewards', '" . serialize($final) . "')";
			$_SESSION['vRewardStoreDB']->exec($uSQL);
		}
		$fvSQL = "DELETE FROM rewardlinks WHERE rewardlinks_timestamp <= '" . $mytime . "'";
		$this->_fsManagerDBM->queryExec($fvSQL);
	}

	function fsExport()
	{
		$linkTypes = $this->GetLinkTypes();
		$text = '';
		foreach ($linkTypes as $cat=>$link)
		{
			$text .= '----------' . $cat . "----------\r\n";
			foreach ($link as $nlink)
			{
				$key = str_replace('&key={*key*}', '', $nlink['rewardLink']);
				$time = date("m/d g:i a", $nlink['timestamp']);
				$url = 'http://apps.facebook.com/onthefarm/' . $key;
				if (isset($nlink['rewardCode'])) {
					$name = Units_GetRealnameByCode($nlink['rewardCode']);
					$name = empty($name) ? Units_GetRealnameByName($nlink['rewardCode']) : $name;
					$item = $name;
				}
				if (isset($nlink['rewardItem'])) {
					if (empty($nlink['rewardItem'])) {
						$item = '--Unknown--';
					} else 	{
						$name = Units_GetRealnameByName($nlink['rewardItem']);
						$name = empty($name) ? Units_GetRealnameByCode($nlink['rewardItem']) : $name;
						$item = $name;
					}
				}
				$text .= $time . "\t" . $item . "\t" . $url . "\r\n";
			}
			$text .= "\r\n";
		}
		$display_file =  $_SESSION['base_path'] . "plugins\\fvLinks\\" . $_SESSION['userId'] . '_links.txt';
		if (is_file($display_file)) unlink($display_file);
		$f = fopen($display_file, "w+");
		fwrite($f, $text);
		fclose($f);
		$runcmd = "notepad.exe $display_file";
		$WshShell = new COM("WScript.Shell");
		$WshShell->Run($runcmd, 5, false);
	}
}
?>
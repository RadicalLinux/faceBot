<?php
define('FB_PARSER_VER', file_get_contents('parser.ver'));
define('PARSER_MAX_SPEED', '8');
define('PARSER_SQLITE', 'data.sqlite');
define('DATASTORE_SQLITE', 'datastore.sqlite');
define('REWARDSTORE_SQLITE', 'rewardstore.sqlite');
$_SESSION['base_path'] = getcwd() . '\\';
global $is_debug;
$is_debug = false;

$timezonefile = './plugins/fBSettings/timezone.txt';
if (file_exists($timezonefile))
{
	$timezone = trim(file_get_contents($timezonefile));
	if (strlen($timezone) > 2)
	{
		date_default_timezone_set($timezone);
	}
}
else
{
	date_default_timezone_set('America/Chicago');
}
require_once('fB_DB.php');
require_once('fB_AMF.php');
require_once('fB_Utils.php');
require_once('fB_Farm.php');
set_time_limit(0);

$_SESSION['use_proxy'] = false;
// load proxy settings
if (file_exists('proxy.txt'))
{
	$_SESSION['proxy_settings'] = file('proxy.txt');
	if (count($_SESSION['proxy_settings']))
	$_SESSION['use_proxy'] = true;
}

// ------------------------------------------------------------------------------
// ------------------------------------------------------------------------------
// RaiseError raise error
//  @param string $errnum
// ------------------------------------------------------------------------------
function RaiseError($errnum)
{
	EchoData(sprintf('_Error : %08X', $errnum));
}
// ------------------------------------------------------------------------------
// RestartBot sends command to restart
// ------------------------------------------------------------------------------
function RestartBot()
{
	echo "\n Restarting Bot in 15 seconds\n";
	sleep(15);
	die();
}
// ------------------------------------------------------------------------------
// proxy_GET can use a proxy to get the gameSettings/description xml files
// ------------------------------------------------------------------------------
function proxy_GET($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; SearchToolbar 1.1; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 1.1.4322)');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
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
		AddLog2("Parser: Error 404/Page Not Found");
		return;
	}
	if($httpCode == 500) {
		AddLog2("Parser: Error 500/Internal Server Error");
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
// Perform work on the farm
// ------------------------------------------------------------------------------
function Do_Work()
{
	global $settings;
	global $need_reload;

	global $px_Setops;

	global $res_str;
	pluginload();

	$vDir = './farmville-logs';
	if (!file_exists($vDir)) mkdir($vDir);
	$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir))
	{
		while (false !== ($file = readdir($df)))
		{
			if ($file != "." && $file != "..")
			{
				$file1 = $vDir . '/' . $file;
				$last_modified = filemtime($file1);
				if (time() - $last_modified > $time_limit)
				{
					unlink($file1);
				}
			}
		}
		closedir($df);
	}

	while (file_exists('notrun_parser.txt') || file_exists('notrun_parser_' . $_SESSION['userId'] . '.txt'))
	{
		AddLog2("Bot Paused. Next check in 30 seconds.");
		sleep(30);
	}
	parse_neighbors();
	parse_user();
	AddLog2("Beginning Bot Cycle");

	Hook('before_work');
	// Init
	$res = DoInit('full');
	if ($res != 'OK')
	{
		RaiseError(2);
	}
	else
	{
		$res_str = ''; //for main logs
	}

	Hook('before_load_settings');
	// load settings
	if (!function_exists('LoadSavedSettings'))
	{
		die("\n\nSettings plugin installed incorrectly no LoadSavedSettings found!\n\n");
	}

	$px_Setopts = LoadSavedSettings();
	$enable_lonlyanimals = $px_Setopts['lonlyanimals'];

	Hook('after_load_settings');
	if ($enable_lonlyanimals)
	{
		AddLog2("check lonlyanimal");
		Do_Check_Lonlyanimals();
	}
	Hook('flybiplane');
	Hook('before_harvest');
	Hook('harvest');
	Hook('after_harvest');
	Hook('before_harvest_buildings');
	Hook('harvest_buildings');
	Hook('after_harvest_buildings'); //after building harvest
	Hook('before_harvest_animals'); //get product from livestock
	Hook('harvest_animals');
	Hook('after_harvest_animals');
	Hook('before_transform_animals');
	Hook('transform_animals');
	Hook('after_transform_animals');
	Hook('before_harvest_trees');
	Hook('harvest_trees');
	Hook('after_harvest_trees');
	Hook('before_hoe');
	Hook('hoe');
	Hook('after_hoe');
	Hook('before_before_planting');
	Hook('before_planting');
	Hook('planting');
	Hook('after_planting');
	Hook('after_work');

	Parser_Check_Images();

	AddLog2("Peak Memory Usaged: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB");
	AddLog2("Finished Bot Cycle");
}
// ------------------------------------------------------------------------------
// Parser_Check_Images
// ------------------------------------------------------------------------------
function Parser_Check_Images()
{
	AddLog2("Parser: Checking Images");

	if (!file_exists('swfdump.exe'))
	{
		AddLog2("parser: swfdump.exe missing");
		return '';
	}

	$vHaveFlash = false;

	$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');
	$vSQL = 'select * from units where field="iconurl" and name not in (select name from units where field="imageready")';
	$vResult = $_SESSION['vDataDB']->query($vSQL);

	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vImage = $vRow['content'];
		$vName = $vRow['name'];

		if (!file_exists($vImage))
		{
			if (!$vHaveFlash)
			{
				$vDir = './farmville-flash';
				if (!file_exists($vDir)) mkdir($vDir);
				$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
				if ($df = opendir($vDir))
				{
					while (false !== ($file = readdir($df)))
					{
						if ($file != "." && $file != "..")
						{
							$file1 = $vDir . '/' . $file;
							$last_modified = filemtime($file1);
							if (time() - $last_modified > $time_limit)
							{
								unlink($file1);
							}
						}
					}
					closedir($df);
				}
				$flashVars = parse_flashvars();
				$vFlashURL = $flashVars['swfLocation'];
				$vFlashFile = substr($vFlashURL, stripos($vFlashURL,'FarmGame'));
				if (!file_exists('farmville-flash/' . $vFlashFile))
				{
					$vFlashContent = file_get_contents($vFlashURL);
					if ($vFlashContent === false)
					{
						AddLog2('Unable to get Flash File');
						return;
					}
					else
					{
						file_put_contents('farmville-flash/' . $vFlashFile, $vFlashContent);
					}
				}
				if (!file_exists('farmville-flash/' . $vFlashFile . '.txt.'))
				{
					$vFlashDump = shell_exec('swfdump.exe -a farmville-flash/' . $vFlashFile . ' 2>&1 ');
					$vFlashDump = substr($vFlashDump, strpos($vFlashDump, "slot 0: class <q>[public]Classes.Yimf::YimfMap=YimfMap"));
					$vLastPos = strpos($vFlashDump, 'Display::AssetHashMap');
					if ($vLastPos > 1000)
					{
						$vFlashDump = substr($vFlashDump, 0, $vLastPos);
					}
					file_put_contents('farmville-flash/' . $vFlashFile . '.txt.', $vFlashDump);
				}
				else
				{
					$vFlashDump = file_get_contents('farmville-flash/' . $vFlashFile . '.txt.');
				}
				$vHaveFlash = true;
			}

			$vHashImage = '';
			$vUrlPosition = strpos($vFlashDump, "pushstring \"" . $vImage);
			if ($vUrlPosition !== false)
			{
				$vUrlPosition += 12;
				$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
				$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
				if ($vUrlPosition2 !== false)
				{
					$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $vUrlLength);
				}
			}

			if (strlen($vHashImage) > 0)
			{
				$vFolder = substr($vImage, 0, strrpos($vImage, "/"));

				mkdir($vFolder, 0777, true);

				$vRemoteUrl = $flashVars ['asset_url'] . $vHashImage;

				$vImageData = file_get_contents($vRemoteUrl);
				if ($vImageData)
				{
					file_put_contents($vImage, $vImageData);
					$_SESSION['vDataDB']->query('insert into units("name","field","content") values("' . $vName . '","imageready","download")');
					AddLog2("Parser: Get Image - " . $vImage);
				}
			}
		}
		else
		{
			$_SESSION['vDataDB']->exec('insert into units("name","field","content") values("' . $vName . '","imageready","found")');
		}
	}
	$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
	AddLog2("Parser: Finished Checking Images");
}


function Parser_Get_Locale()
{
	$vHaveFlash = false;
	$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');
	$vSQL = "select * from units where field='name' and name not in (select name from units where field='realname')";
	$vResult = $_SESSION['vDataDB']->query($vSQL);

	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		if (!$vHaveFlash)
		{
			$vDir = './farmville-flash';
			if (!file_exists($vDir)) mkdir($vDir);
			$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
			if ($df = opendir($vDir))
			{
				while (false !== ($file = readdir($df)))
				{
					if ($file != "." && $file != "..")
					{
						$file1 = $vDir . '/' . $file;
						$last_modified = filemtime($file1);
						if (time() - $last_modified > $time_limit)
						{
							unlink($file1);
						}
					}
				}
				closedir($df);
			}
			$flashVars = parse_flashvars();
			$vFlashURL = $flashVars['localization_url'];
			$vFlashFile = 'Localization.' . $_SESSION['flashRevision'] . '.swf';
			if (!file_exists('farmville-flash/' . $vFlashFile))
			{
				$vFlashContent = file_get_contents($vFlashURL);
				if ($vFlashContent === false)
				{
					AddLog2('Unable to get Flash File');
					return;
				}
				else
				{
					file_put_contents('farmville-flash/' . $vFlashFile, $vFlashContent);
				}
			}
			if (!file_exists('farmville-flash/' . $vFlashFile . '.txt.'))
			{
				$vFlashDump = shell_exec('swfdump.exe -a farmville-flash/' . $vFlashFile . ' 2>&1 ');
				$vFlashDump = substr($vFlashDump, strpos($vFlashDump, "sealed protectedNS([protected]Locale) class <q>[public]::Locale extends <q>[public]flash.display::Sprite{"));
				$vLastPos = strpos($vFlashDump, 'slot 0: var <q>[public]::text:<q>[public]::Object');
				if ($vLastPos > 1000)
				{
					$vFlashDump = substr($vFlashDump, 0, $vLastPos);
				}
				file_put_contents('farmville-flash/' . $vFlashFile . '.txt.', $vFlashDump);
			}
			else
			{
				$vFlashDump = file_get_contents('farmville-flash/' . $vFlashFile . '.txt.');
			}
			$vHaveFlash = true;
		}

		$vHashImage = '';
		$vUrlPosition = strpos($vFlashDump, "pushstring \"" . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false)
		{
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false)
			{
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}

		if (strlen($vHashImage) > 0 && strpos($vHashImage,'\\') === false)
		{
			$_SESSION['vDataDB']->query('insert into units("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
			AddLog2("Parser: Get Description - " . $vName . ' - ' . $vHashImage);
		}
		else
		{
			$_SESSION['vDataDB']->query('insert into units("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');

		}

	}
	$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
	$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');
	$vSQL = "select * from collectables where field='name' and name not in (select name from collectables where field='realname')";
	$vResult = $_SESSION['vDataDB']->query($vSQL);

	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vUrlPosition = strpos($vFlashDump, "pushstring \"" . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false)
		{
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false)
			{
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}

		if (strlen($vHashImage) > 0 && strpos($vHashImage,'\\') === false)
		{
			$_SESSION['vDataDB']->query('insert into collectables("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
			AddLog2("Parser: Get Description - " . $vName . ' - ' . $vHashImage);
		}
		else
		{
			$_SESSION['vDataDB']->query('insert into collectables("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');

		}

	}
	$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
	$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');
	$vSQL = "select * from achievements where field='name' and name not in (select name from achievements where field='realname')";
	$vResult = $_SESSION['vDataDB']->query($vSQL);

	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vUrlPosition = strpos($vFlashDump, "pushstring \"" . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false)
		{
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false)
			{
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}

		if (strlen($vHashImage) > 0 && strpos($vHashImage,'\\') === false)
		{
			$_SESSION['vDataDB']->query('insert into achievements("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
			AddLog2("Parser: Get Description - " . $vName . ' - ' . $vHashImage);
		}
		else
		{
			$_SESSION['vDataDB']->query('insert into achievements("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');

		}

	}
	$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
	$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');
	$vSQL = "select * from quests where field='title' and name not in (select name from quests where field='realname')";
	$vResult = $_SESSION['vDataDB']->query($vSQL);

	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vFName = str_replace('_Title', '', $vImage);
		$vUrlPosition = strpos($vFlashDump, "pushstring \"" . $vImage);
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, "pushstring \"" . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false)
		{
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false)
			{
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}

		if (strlen($vHashImage) > 0 && strpos($vHashImage,'\\') === false)
		{
			$_SESSION['vDataDB']->query('insert into quests("name","field","content") values("' . $vImage . '", "realname" , "' . $vHashImage . '")');
			AddLog2("Parser: Get Description - " . $vName . ' - ' . $vHashImage);
		}
		else
		{
			$_SESSION['vDataDB']->query('insert into quests("name","field","content") values("' . $vImage . '","realname","' . $vImage . '")');

		}

	}
	$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
}
// ------------------------------------------------------------------------------
// GetUnitList
// ------------------------------------------------------------------------------
function GetUnitList()
{
	$vDir = 'farmville-xml';
	if (!file_exists($vDir)) mkdir($vDir);
	$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir))
	{
		while (false !== ($file = readdir($df)))
		{
			if ($file != "." && $file != "..")
			{
				$file1 = $vDir . '/' . $file;
				$last_modified = filemtime($file1);
				if (time() - $last_modified > $time_limit)
				{
					unlink($file1);
				}
			}
		}
		closedir($df);
	}
	$vDir = 'farmville-sqlite';
	if (!file_exists($vDir)) mkdir($vDir);
	$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir))
	{
		while (false !== ($file = readdir($df)))
		{
			if ($file != "." && $file != "..")
			{
				$file1 = $vDir . '/' . $file;
				$last_modified = filemtime($file1);
				if (time() - $last_modified > $time_limit)
				{
					unlink($file1);
				}
			}
		}
		closedir($df);
	}

	$sqlite_update = 0; //if 1 we are going to download new xml from server

	// check settings table
	if (@$_SESSION['vDataStoreDB']->querySingle('SELECT * FROM settings limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              settings (
                settings_name CHAR(25) PRIMARY KEY,
                settings_value CHAR(25)
              )';
		$_SESSION['vDataStoreDB']->exec($vSQL);
	}
	// check datastore table
	if (@$_SESSION['vDataStoreDB']->querySingle('SELECT * FROM datastore limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              datastore (
                userid CHAR(25),
                storetype CHAR(25),
                content BLOB,
                PRIMARY KEY (userid, storetype)
              )';
		$_SESSION['vDataStoreDB']->exec($vSQL);
	}
	// check datastore table
	if (@$_SESSION['vRewardStoreDB']->querySingle('SELECT * FROM rewardstore limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              rewardstore (
                userid CHAR(25),
                storetype CHAR(25),
                content BLOB,
                PRIMARY KEY (userid, storetype)
              )';
		$_SESSION['vRewardStoreDB']->exec($vSQL);
	}

	// check userids table
	if (@$_SESSION['vDataStoreDB']->querySingle('SELECT * FROM userids limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              userids (
                userid CHAR(25) PRIMARY KEY,
                username CHAR(25)
              )';
		$_SESSION['vDataStoreDB']->exec($vSQL);
	}

	if (@$_SESSION['vDataStoreDB']->querySingle('SELECT * FROM neighbors limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              neighbors (
                neighborid CHAR(25) PRIMARY KEY,
                fullname CHAR(50),
                profilepic TEXT
              )';
		$_SESSION['vDataStoreDB']->exec($vSQL);
	}
	// check units table
	if (@$_SESSION['vDataDB']->querySingle('SELECT * FROM units limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              units (
                name CHAR(25),
                field CHAR(25),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->exec($vSQL);
		$_SESSION['vDataDB']->exec('CREATE INDEX units_idx_1 ON units(name,field)');
		$_SESSION['vDataDB']->exec('CREATE INDEX units_idx_2 ON units(field,content)');
		$sqlite_update = 1;
	}
	// check achievements table
	if (@$_SESSION['vDataDB']->querySingle('SELECT * FROM achievements limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              achievements (
                name CHAR(25),
                field CHAR(25),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->exec($vSQL);
		$_SESSION['vDataDB']->exec('CREATE INDEX achievements_idx_1 ON achievements(name,field)');
		$_SESSION['vDataDB']->exec('CREATE INDEX achievements_idx_2 ON achievements(field,content)');
		$sqlite_update = 1;
	}
	// check collectables table
	if (@$_SESSION['vDataDB']->querySingle('SELECT * FROM collectables limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              collectables (

                name CHAR(25),
                field CHAR(25),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->exec($vSQL);
		$_SESSION['vDataDB']->exec('CREATE INDEX collectables_idx_1 ON collectables(name,field)');
		$_SESSION['vDataDB']->exec('CREATE INDEX collectables_idx_2 ON collectables(field,content)');
		$sqlite_update = 1;
	}
	// check storage table
	if (@$_SESSION['vDataDB']->query('SELECT * FROM storage limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              storage (
                name CHAR(25),
                field CHAR(25),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->exec($vSQL);
		$_SESSION['vDataDB']->exec('CREATE INDEX storage_idx_1 ON storage(name,field)');
		$_SESSION['vDataDB']->exec('CREATE INDEX storage_idx_2 ON storage(field,content)');
		$sqlite_update = 1;
	}
	// check crafting table
	if (@$_SESSION['vDataDB']->query('SELECT * FROM crafting limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              crafting (
                name CHAR(25),
                field CHAR(25),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->query($vSQL);
		$_SESSION['vDataDB']->query('CREATE INDEX crafting_idx_1 ON crafting(name,field)');
		$_SESSION['vDataDB']->query('CREATE INDEX crafting_idx_2 ON crafting(field,content)');
		$sqlite_update = 1;
	}
	// check quests table
	if (@$_SESSION['vDataDB']->query('SELECT * FROM quests limit 1') === false)
	{
		$vSQL = 'CREATE TABLE
              quests (
                name CHAR(25),
                field CHAR(50),
                content CHAR(250)
              )';
		$_SESSION['vDataDB']->query($vSQL);
		$_SESSION['vDataDB']->query('CREATE INDEX quests_idx_1 ON quests(name,field)');
		$_SESSION['vDataDB']->query('CREATE INDEX quests_idx_2 ON quests(field,content)');
		$sqlite_update = 1;
	}
	// Force download when key files are missing
	if (!file_exists('units.txt')) $sqlite_update = 1;
	if (!file_exists('collectable_info.txt')) $sqlite_update = 1;
	if (!file_exists('achievement_info.txt')) $sqlite_update = 1;

	$flashVars = parse_flashvars();

	$vGameSetting = 'farmville-xml/' . $_SESSION['flashRevision'] . '_gameSettings.xml';
	if (!file_exists($vGameSetting))
	{
		$xml_units = '';
		if (!$xml_units)
		{
			AddLog2($flashVars['game_config_url']);
			AddLog2('DL: v' . $_SESSION['flashRevision'] . ' settings file.');
			//$geturl = 'http://static-facebook.farmville.com/v' . $_SESSION['flashRevision'] . '/gameSettings.xml.gz';
			$geturl = $flashVars['game_config_url'];
			$xml_units = gzuncompress(proxy_GET($geturl));
		}
		if (!$xml_units) // Owned i guess.....
		{
			AddLog2('Couldn\'t find a settings xml...');
		}
		else
		{
			AddLog2('Download completed.');
			file_put_contents($vGameSetting, $xml_units);
			$sqlite_update = 1;
		}
		unset($xml_units);
	}

	$vItemsSetting = 'farmville-xml/' . $_SESSION['flashRevision'] . '_items.xml';
	if (!file_exists($vItemsSetting))
	{
		$xml_items = '';
		if (!$xml_items)
		{
			AddLog2($flashVars['items_url']);
			AddLog2('DL: v' . $_SESSION['flashRevision'] . ' items xml.');
			//$geturl = 'http://static-facebook.farmville.com/v' . $_SESSION['flashRevision'] . '/items.xml.gz';
			$geturl = $flashVars['items_url'];
			$xml_items = gzuncompress(proxy_GET($geturl));
		}
		if (!$xml_items)
		{
			AddLog2('Couldn\'t find a items xml...');
		}
		else
		{
			AddLog2('Download completed.');
			file_put_contents($vItemsSetting, $xml_items);
			$sqlite_update = 1;
		}
		unset($xml_items);
	}

	$vStorageConfig = 'farmville-xml/' . $_SESSION['flashRevision'] . '_StorageConfig.xml';
	if (!file_exists($vStorageConfig))
	{
		$xml_storage = '';
		AddLog2('DL: v' . $_SESSION['flashRevision'] . ' storageconfig xml');
		if (isset($flashVars['xml_url']) && !empty($flashVars['xml_url'])) {
			$geturl = $flashVars['xml_url'] . 'StorageConfig.xml.gz';
		} else {
			$geturl = $flashVars['app_url'] . 'v' . $_SESSION['flashRevision'] . '/StorageConfig.xml.gz';
		}
		$xml_storage = gzuncompress(proxy_GET($geturl));
		if (!$xml_storage)
		{
			AddLog2('Couldn\'t find a storageconfig xml...');
		}
		else
		{
			AddLog2('Download completed.');
			file_put_contents($vStorageConfig, $xml_storage);
			$sqlite_update = 1;
		}
		unset($xml_storage);
	}

	$vQuestsConfig = 'farmville-xml/' . $_SESSION['flashRevision'] . '_Quests.xml';
	if (!file_exists($vQuestsConfig))
	{
		$xml_quests = '';
		$geturl = $flashVars['social_quest_url'];
		AddLog2('DL: v' . $_SESSION['flashRevision'] . ' quests xml');
		//$geturl = 'http://static-facebook.farmville.com/v' . $_SESSION['flashRevision'] . '/quests.xml';
		$xml_quests = gzuncompress(proxy_GET($geturl));
		if (!$xml_quests)
		{
			AddLog2('Couldn\'t find a quests xml...');
		}
		else
		{
			AddLog2('Download completed.');
			file_put_contents($vQuestsConfig, $xml_quests);
			$sqlite_update = 1;
		}
		unset($xml_quests);
	}

	$vCraftingConfig = 'farmville-xml/' . $_SESSION['flashRevision'] . '_Crafting.xml';
	if (!file_exists($vCraftingConfig))
	{
		$xml_crafting = '';
		AddLog2('DL: v' . $_SESSION['flashRevision'] . ' crafting xml');
		if (isset($flashVars['xml_url']) && !empty($flashVars['xml_url'])) {
			$geturl = $flashVars['xml_url'] . 'crafting.xml.gz';
		} else {
			$geturl = $flashVars['app_url'] . 'v' . $_SESSION['flashRevision'] . '/crafting.xml.gz';
		}
		$xml_crafting = gzuncompress(proxy_GET($geturl));
		if (!$xml_crafting)
		{
			AddLog2('Couldn\'t find a crafting xml...');
		}
		else
		{
			AddLog2('Download completed.');
			file_put_contents($vCraftingConfig, $xml_crafting);
			$sqlite_update = 1;
		}
		unset($xml_crafting);
	}

	if ($sqlite_update == 1)
	{
		$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');

		$_SESSION['vDataDB']->exec('delete from units');
		$_SESSION['vDataDB']->exec('delete from achievements');
		$_SESSION['vDataDB']->exec('delete from collectables');
		$_SESSION['vDataDB']->exec('delete from storage');
		$_SESSION['vDataDB']->exec('delete from crafting');
		$_SESSION['vDataDB']->exec('delete from quests');
		$_SESSION['vDataDB']->exec('COMMIT TRANSACTION');
		$_SESSION['vDataDB']->exec('vacuum');

		$_SESSION['vDataDB']->exec('BEGIN TRANSACTION');

		$xmlDoc = simplexml_load_file($vItemsSetting);
		$itemsarray = objectsIntoArray($xmlDoc);
		//file_put_contents('testarray.txt', print_r($array,true));
		foreach ($itemsarray['items']['item'] as $item)
		{
			$itemName = $item['@attributes']['name'];
			foreach ($item as $key=>$field)
			{
				if ($key == '@attributes'){
					foreach ($field as $key1=>$attr)
					{
						$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","' . $key1 . '","' . $attr . '");');
					}
					continue;
				}
				if ($key == 'image') {
					foreach ($field as $key1=>$attr)
					{
						if ($attr['name'] == 'icon') {
							$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","iconurl","' . $attr['url'] . '");');
							break;
						}
						if ($attr['@attributes']['name'] == 'icon') {
							$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","iconurl","' . $attr['@attributes']['url'] . '");');
							break;
						}
					}
					continue;
				}
				if ($key == 'masteryLevel') {
					foreach ($field as $key1=>$attr)
					{
						if (isset($attr['@attributes']['gift'])) {
							$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","masterymax","' . $attr['@attributes']['count'] . '");');
							break;
						}
						if (isset($attr['gift'])) {
							$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","masterymax","' . $attr['count'] . '");');
							break;
						}
					}
					continue;
				}
				if (is_array($field)) {
					$_SESSION['vDataDB']->exec("insert into units(name,field,content) values('" . $itemName . "','" . $key . "','" . serialize($field) . "');");
				} else {
					$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $itemName . '","' . $key . '","' . $field . '");');
				}

			}
		}
		$xmlDoc = simplexml_load_file($vGameSetting);
		foreach($xmlDoc->farming as $vItem)
		{
			foreach($vItem->attributes() as $vField => $vContent)
			{
				$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("_farming","' . $vField . '","' . $vContent . '");');
			}
		}
		foreach($xmlDoc->collections->collection as $vItem)
		{
			$vItemName = (string)$vItem['name'];

			if (strlen($vItemName) > 0)
			{
				foreach($vItem->attributes() as $vField => $vContent)
				{
					$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				}
				foreach($vItem->children() as $vSubName => $vSubElement)
				{
					if ($vSubName == 'collectable')
					{
						$_SESSION['vDataDB']->query('insert into collectables(name,field,content) values("' . $vItemName . '","collectable","' . $vSubElement['code'] . '");');
						if (isset($vSubElement['chance']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","chance","' . $vSubElement['chance'] . '");');
						}
						if (isset($vSubElement['rarity']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","rarity","' . $vSubElement['rarity'] . '");');
						}
						if (isset($vSubElement['source']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","source","' . $vSubElement['source'] . '");');
						}
						if (isset($vSubElement['numneeded']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","numneeded","' . $vSubElement['numneeded'] . '");');
						}
					}
					if ($vSubName == 'tradeInReward')
					{
						if (isset($vSubElement['xp']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_xp","' . $vSubElement['xp'] . '");');
						}
						if (isset($vSubElement['coins']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_coins","' . $vSubElement['coins'] . '");');
						}
						if (isset($vSubElement['gift']))
						{
							$_SESSION['vDataDB']->exec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_gift","' . $vSubElement['gift'] . '");');
						}
					}
				}
			}
		}
		foreach($xmlDoc->achievements->achievement as $vItem)
		{
			$vItemName = (string)$vItem['name'];
			$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $vItemName . '","type","achieve");');

			if (strlen($vItemName) > 0)
			{
				foreach($vItem->attributes() as $vField => $vContent)
				{
					$_SESSION['vDataDB']->exec('insert into achievements(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
					$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				}
				foreach($vItem->children() as $vSubName => $vSubElement)
				{
					if ($vSubName == 'image')
					{
						if ($vSubElement['name'] == 'icon_48')
						{
							$_SESSION['vDataDB']->exec('insert into achievements(name,field,content) values("' . $vItemName . '","iconurl","' . $vSubElement['url'] . '");');
							$_SESSION['vDataDB']->exec('insert into units(name,field,content) values("' . $vItemName . '","iconurl","' . $vSubElement['url'] . '");');
						}
					}elseif ($vSubName = 'level')
					{
						$_SESSION['vDataDB']->exec('insert into achievements(name,field,content) values("' . $vItemName . '","count","' . $vSubElement['count'] . '");');

						$_SESSION['vDataDB']->exec('insert into achievements(name,field,content) values("' . $vItemName . '","xp","' . $vSubElement['xp'] . '");');
						$_SESSION['vDataDB']->exec('insert into achievements(name,field,content) values("' . $vItemName . '","coins","' . $vSubElement['coins'] . '");');
					}
				}
			}
		}
		unset($xmlDoc);

		//$xmlDoc = simplexml_load_file($vStorageConfig);
		$XML_string = file_get_contents($vStorageConfig);
		$obj = new xml2array($XML_string);
		$itemsarray = $obj->getResult();
		foreach($itemsarray['storage']['StorageBuilding'] as $item)
		{
			$itemName = $item['name'];
			unset($item['metadata'], $item['#comment']);
			foreach ($item as $key=>$value)
			{
				if (is_array($value) && count($value) == 1) {
					$_SESSION['vDataDB']->exec('insert into storage(name,field,content) values("' . $itemName . '","' . $key . '","' . implode('', $value) . '");');
				} elseif (is_string($value)) {
					$_SESSION['vDataDB']->exec('insert into storage(name,field,content) values("' . $itemName . '","' . $key . '","' . $value . '");');
				} else {
					$_SESSION['vDataDB']->exec("insert into storage(name,field,content) values('" . $itemName . "','" . $key . "','" . serialize($value) . "');");
				}
			}
		}
		foreach($itemsarray['storage']['FeatureCreditStorage'] as $item)
		{
			$itemName = $item['name'];
			unset($item['metadata'], $item['#comment']);
			foreach ($item as $key=>$value)
			{
				if (is_array($value) && count($value) == 1) {
					$_SESSION['vDataDB']->exec('insert into storage(name,field,content) values("' . $itemName . '","' . $key . '","' . implode('', $value) . '");');
				} elseif (is_string($value)) {
					$_SESSION['vDataDB']->exec('insert into storage(name,field,content) values("' . $itemName . '","' . $key . '","' . $value . '");');
				} else {
					$_SESSION['vDataDB']->exec("insert into storage(name,field,content) values('" . $itemName . "','" . $key . "','" . serialize($value) . "');");
				}
			}
		}

		$xmlDoc = simplexml_load_file($vCraftingConfig);
		foreach($xmlDoc->recipes->CraftingRecipe as $vRecipe)
		{
			$vRecipeID = (string)$vRecipe['id'];
			if (strlen($vRecipeID) > 0)
			{
				$vRecipeName = '';
				foreach($vRecipe->attributes() as $vField => $vContent)
				{
					$_SESSION['vDataDB']->query('insert into crafting(name,field,content) values("' . $vRecipeID . '","' . $vField . '","' . $vContent . '");');
				}
				foreach($vRecipe->children() as $vSubName => $vSubElement)
				{
					if ($vSubName == 'name') $vRecipeName = (string)$vSubElement;
					if ($vSubName == 'image')
					{
						if ($vSubElement['name'] == 'icon')
						{
							$_SESSION['vDataDB']->exec('insert into crafting(name,field,content) values("' . $vRecipeID . '","iconurl","' . $vSubElement['url'] . '");');
						}
					}elseif ($vSubName == 'Reward')
					{
						foreach($vSubElement->children() as $vSubSubName => $vSubSubElement)
						{
							foreach($vSubSubElement->attributes() as $vField => $vContent)
							{
								$_SESSION['vDataDB']->exec('insert into crafting(name,field,content) values("' . $vRecipeID . '","reward_' . $vSubSubName . '_' . $vField . '","' . (string)$vContent . '");');
							}
						}
					}elseif ($vSubName == 'Ingredients')
					{
						foreach($vSubElement->children() as $vSubSubName => $vSubSubElement)
						{
							$_SESSION['vDataDB']->exec('insert into crafting(name,field,content) values("' . $vRecipeID . '","Ingredient_itemCode","' . $vSubSubElement['itemCode'] . '");');
							$_SESSION['vDataDB']->exec('insert into crafting(name,field,content) values("' . $vRecipeID . '","Ingredient_quantityRequired_' . $vSubSubElement['itemCode'] . '","' . $vSubSubElement['quantityRequired'] . '");');
						}
					}
					else
					{
						$_SESSION['vDataDB']->exec('insert into crafting(name,field,content) values("' . $vRecipeID . '","' . $vSubName . '","' . (string)$vSubElement . '");');
					}
				}
				if (strlen($vRecipeName) > 0)
				{
					$_SESSION['vDataDB']->exec('update crafting set name="' . $vRecipeName . '" where name="' . $vRecipeID . '"');
				}
			}
		}
		unset($xmlDoc);

		$xmlDoc = simplexml_load_file($vQuestsConfig);
		foreach($xmlDoc->quest as $vQuest)
		{
			$vQuestID = (string)$vQuest['id'];
			if (strlen($vQuestID) > 0)
			{
				foreach($vQuest->attributes() as $vField => $vContent)
				{
					$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vField . '","' . $vContent . '");');
				}
				foreach($vQuest->children() as $vSubName => $vSubElement)
				{
					if ($vSubName == 'text')
					{
						foreach($vSubElement->attributes() as $vField => $vContent)
						{
							$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vField . '","' . (string)$vContent . '");');
						}
					}elseif ($vSubName == 'icon')
					{
						$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","iconurl","' . $vSubElement['url'] . '");');
					}elseif ($vSubName == 'questGiverImage')
					{
						$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","questGiverImage","' . $vSubElement['url'] . '");');
					}elseif ($vSubName == 'completionRequirements')
					{
						$vCompleteName = $vSubElement['name'];
						$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '","' . $vCompleteName . '");');
						$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_timeLimit","' . $vSubElement['timeLimit'] . '");');

						foreach($vSubElement->children() as $vSubSubName => $vSubSubElement)
						{
							if ($vSubSubName == 'requirement')
							{
								$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_' . (string)$vSubSubElement['action'] . '_' . (string)$vSubSubElement['type'] . '","' . (string)$vSubSubElement['many'] . '");');
							}
							if ($vSubSubName == 'reward')
							{
								$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_coins","' . (string)$vSubSubElement['coins'] . '");');
								$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_experience","' . (string)$vSubSubElement['experience'] . '");');
							}
						}
					}
					else
					{
						$_SESSION['vDataDB']->exec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vSubName . '","' . (string)$vSubElement . '");');
					}
				}
			}
		}
		unset($xmlDoc);

		$_SESSION['vDataDB']->query('COMMIT TRANSACTION');

		$vCollectable = array();
		// create collectable_info.txt
		$vSQL = 'select * from collectables where field="code"';
		$vResult = $_SESSION['vDataDB']->query($vSQL);
		while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
		{
			$vCollectable[$vRow['content']]['name'] = $vRow['name'];
			$vCollectable[$vRow['content']]['code'] = $vRow['content'];
			$vSQL2 = 'select content from collectables where name="' . $vRow['name'] . '" and field="tradeInReward_xp"';
			$vCollectable[$vRow['content']]['tradeInReward'] = $_SESSION['vDataDB']->querySingle($vSQL2);
			$vSQL2 = 'select content from collectables where name="' . $vRow['name'] . '" and field="realname"';
			$vCollectable[$vRow['content']]['realname'] = $_SESSION['vDataDB']->querySingle($vSQL2);
			$vSQL2 = 'select content from collectables where name="' . $vRow['name'] . '" and field="collectable"';
			$vResult2 = $_SESSION['vDataDB']->query($vSQL2);
			while ($vRow2 = $vResult2->fetchArray(SQLITE3_ASSOC))
			{
				$vCollectable[$vRow['content']]['collectable'][] = $vRow2['content'];
			}
		}
		file_put_contents('collectable_info.txt', serialize($vCollectable));
		unset($vCollectable);

		$vAchievements = array();
		$vSQL = 'select * from achievements where field="code"';
		$vResult = $_SESSION['vDataDB']->query($vSQL);
		while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
		{
			$vAchievements[$vRow['content']]['name'] = $vRow['name'];
			$vAchievements[$vRow['content']]['code'] = $vRow['content'];
			$vSQL2 = 'select content from achievements where name="' . $vRow['name'] . '" and field="iconurl"';
			$vAchievements[$vRow['content']]['iconurl'] = $_SESSION['vDataDB']->querySingle($vSQL2);
			$vSQL2 = 'select content from achievements where name="' . $vRow['name'] . '" and field="realname"';
			$vAchievements[$vRow['content']]['realname'] = $_SESSION['vDataDB']->querySingle($vSQL2);
			$vSQL2 = 'select content from achievements where name="' . $vRow['name'] . '" and field="desc"';
			$vAchievements[$vRow['content']]['desc'] = $_SESSION['vDataDB']->querySingle($vSQL2);

			$vSQL2 = 'select content from achievements where name="' . $vRow['name'] . '" and field="count" order by field';
			$vResult2 = $_SESSION['vDataDB']->query($vSQL2);
			while ($vRow2 = $vResult2->fetchArray(SQLITE3_ASSOC))
			{
				$vAchievements[$vRow['content']]['level'][] = $vRow2['content'];
			}
		}
		file_put_contents('achievement_info.txt', serialize($vAchievements));
		unset($vAchievements);

		file_put_contents('units.txt', serialize(Units_GetAll()));

		file_put_contents('sqlite_check.txt', $_SESSION['flashRevision']);
	}

	EchoData('OK');
}
// ------------------------------------------------------------------------------
// Hook
// ------------------------------------------------------------------------------
function Hook($hook)
{
	global $plugins;
	foreach($plugins as $plugin)
	{
		if (isset($plugin['hooks'][$hook]))
		{
			if (function_exists($plugin['hooks'][$hook]))
			{
				$_SESSION['this_plugin'] = $plugin;
				call_user_func($plugin['hooks'][$hook]);
			}
		}
	}
}

function pluginload()
{
	// get list of plugins
	global $plugins;
	$plugins = array();

	$dir = 'plugins';
	$dh = opendir($dir);

	if ($dh)
	{
		while (($file = readdir($dh)) !== false)
		{
			if (is_dir($dir . '/' . $file))
			{
				if ($file != '.' && $file != '..')
				{
					$plugin = array();
					$plugin['name'] = $file;
					$plugin['folder'] = $dir . '/' . $file;
					$plugin['main'] = file_exists($dir . '/' . $file . '/main.php') ? $dir . '/' . $file . '/main.php' : false;
					$plugin['hooks'] = array();

					$plugins[] = $plugin;
				}
			}
		}
		closedir($dh);
	}
	// initialize plugins
	foreach($plugins as $key => $plugin)
	{
		if ($plugin['main'])
		{
			// load plugin
			if (!(file_exists('notrun_plugin_' . $plugin['name'] . '.txt') || file_exists('notrun_plugin_' . $plugin['name'] . '_' . $_SESSION['userId'] . '.txt')))
			{
				include($plugin['main']);
				// find init function
				$init_function = $plugin['name'] . '_init';
				if (function_exists($init_function))
				{
					$_SESSION['hooks'] = array();
					$_SESSION['this_plugin'] = $plugin;
					// call init function
					call_user_func($init_function);
					$plugins[$key]['hooks'] = $_SESSION['hooks'];

				}
			}
		}
	}

	if (FB_PARSER_VER != FB_SETTINGS_VER)
	echo "\r\n******\r\nERROR: faceBot's updated parser version (" . FB_PARSER_VER . ") doesn't match settings version (" . FB_SETTINGS_VER . ")\r\n******\r\n";
}
// ------------------------------------------------------------------------------
// Beginning of the script
// ------------------------------------------------------------------------------
echo "----- begin parser.php v" . FB_PARSER_VER . " -----\r\n";

include_once(AMFPHP_BASE . "amf/io/AMFDeserializer.php");
include_once(AMFPHP_BASE . "amf/io/AMFSerializer.php");

$argv = $GLOBALS['argv'];
$cmd = $argv[1];
$_SESSION['userId'] = $argv[2];
$_SESSION['flashRevision'] = $argv[3];
$_SESSION['token'] = $argv[4];
if (!empty($cmd) && !empty($_SESSION['userId']) && !empty($_SESSION['flashRevision']) && !empty($_SESSION['token']))
{
}
else
{
	AddLog2("Error: Missing Initial Configuration Values (Bot fuBar'd)");
}
define('farmer', GetFarmserver());
define('farmer_url', GetFarmUrl());
// execute command
switch ($cmd)
{
	case 'runparser':
		$work_timer_start = time();
		GetUnitList();
		Parser_Get_Locale();
		Do_Work();
		$work_timer_end = time() - $work_timer_start;
		AddLog2("##### Work completed in: $work_timer_end sec #####\r\n");
		Sleep(15);
		break;
	default:
		AddLog2("Error: Invalid Command Sent to Parser (Bot fuBar'd)");
}
$_SESSION['vDataDB'] = null;
?>
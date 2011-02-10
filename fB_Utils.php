<?php

// ------------------------------------------------------------------------------
// load_array
// ------------------------------------------------------------------------------
function load_array($filename) {

	return @unserialize(file_get_contents($_SESSION['this_plugin']['folder'] . '/' . PluginF($filename)));
}
// ------------------------------------------------------------------------------
// save_array
// ------------------------------------------------------------------------------
function save_array($array, $filename) {
	file_put_contents($_SESSION['this_plugin']['folder'] . '/' . PluginF($filename),serialize($array));
}
// ------------------------------------------------------------------------------
// AddLog add string to main log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog($str) {
	global $res_str;
	$res_str .= $str . "\r\n";
}
// ------------------------------------------------------------------------------
// AddLog2 add string to advanced log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog2($str) {
	@file_put_contents($_SESSION['base_path'] . LogF("log2.txt"),@date("H:i:s")." $str\r\n",FILE_APPEND);
}
// ------------------------------------------------------------------------------
// F creates a full file name
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function F($filename) {
	$folder = "FBID_" . $_SESSION['userId'];
	if (!is_dir($_SESSION['base_path'] . $folder)) {
		mkdir($_SESSION['base_path'] . $folder);
	}

	return $folder . '/' . $filename;
}
// ------------------------------------------------------------------------------
// PluginF creates a full file name (original F())
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function PluginF($filename) {
	return $_SESSION['userId'] . '_' . $filename;
}

function LogF($filename) {
	return $_SESSION['userId'] . '_' . $filename;
}

// *********************************************************************
// we save lots of things now so created this to cleaning up the code *
// *********************************************************************
function save_botarray ($array, $filename) {
	file_put_contents($filename,serialize($array));
	if ($filename="world.txt") return;
	$filename = substr($filename, strpos($filename,'/') + 1);
	$filename = str_replace('.txt', '', $filename);
	$newarray = serialize($array);
	$cleanedarray = str_replace("'", "''", $newarray);
	$uSQL = "INSERT OR REPLACE INTO datastore(userid, storetype, content) values('" . $_SESSION['userId'] . "',
				'$filename', '" . $cleanedarray . "')";
	$_SESSION['vDataStoreDB']->exec($uSQL);
}

// ------------------------------------------------------------------------------
// EchoData returns data in the main application
//  @param string $data data
// ------------------------------------------------------------------------------
function EchoData($data) {

}

// ------------------------------------------------------------------------------
// GetFarmserver returns farmville server name
//  @return string Server name
// ------------------------------------------------------------------------------
function GetFarmserver() {

	$flashVars = parse_flashvars();
	$res = str_replace('/', '', $flashVars['app_url']);
	$res = str_replace('http:', '', $res);
	unset($flashVars);
	return $res;
}
// ------------------------------------------------------------------------------
// GetFarmUrl returns farmville URL
//  @return string URL
// ------------------------------------------------------------------------------
function GetFarmUrl() {
	$flashVars = parse_flashvars();
	$res = $flashVars['app_url'] . 'flashservices/gateway.php';
	unset($flashVars);
	return $res;
}

// ------------------------------------------------------------------------------
// GetObjects gets a list of objects on the farm
//  @param string $className Class name ('Plot', 'Animal', 'Tree' etc.)
//  @return array List of objects
// ------------------------------------------------------------------------------
function GetObjects($className = '') {
	$objectsstr = fBGetDataStore('objects');
	$objects = unserialize($objectsstr);
	if ($className) {
		$resobjects = array();
		foreach ($objects as $object)
		if ($object['className'] == $className)
		$resobjects[] = $object;
		return $resobjects;
	} else {
		return $objects; //return all objects
	}
}


// ------------------------------------------------------------------------------
// GetObjects gets a list of objects on the farm
//  @param string $name name
//  @return array List of objects
// ------------------------------------------------------------------------------
function GetObjectsByName($name = '') {
	$objectsstr = fBGetDataStore('objects');
	$objects = unserialize($objectsstr);
	if ($name) {
		$resobjects = array();
		foreach ($objects as $object)
		if ($object['itemName'] == $name)
		$resobjects[] = $object;
		return $resobjects;
	} else {
		return $objects; //return all objects
	}
}
// ------------------------------------------------------------------------------
// GetPlotName compiles plot name
//  @param array $plot
//  @return string Plot name
// ------------------------------------------------------------------------------
function DebugLog($info = '')
{
	return;
}
function GetPlotName($plot) {
	return $plot['position']['x'] . '-' . $plot['position']['y'];
}

// ------------------------------------------------------------------------------
// GetNeighbors gets a list of neighbors
//  @return array List of neighbors
// ------------------------------------------------------------------------------
function GetNeighbors() {
	$neighbors = unserialize(fBGetDataStore('neighbors'));
	return $neighbors;
}

//--------------------------
// CreateDefaultSettings()
//
// Create an default settings array and pass to SaveSettings()
//--------------------------------------------------------------


function CreateDefaultSettings() {
	$dset = array();

	$dset['version'] = FB_SETTINGS_VER;
	$dset['e_gzip'] = 1;
	$dset['farm_server'] = 0;
	$dset['bot_speed'] = 8;
	$dset['not_plugin'] = '';
	$dset['lonlyanimals'] = 1;
	$dset['acceptneighborhelp'] = 1;
	SaveSettings($dset);

	return $dset;
}


//--------------------------------------------------------------
// SaveSettings(array)
//
// Save the supplied settings array into FBID_settings.txt
//--------------------------------------------------------------

function SaveSettings($settings) {
	$set2 = array();
	foreach ($settings as $key => $sopt)
	{
		if (count($settings[$key]) > 1)
		{
			$multi = '';
			foreach ($settings[$key] as $name => $check)
			$multi .= "$name:$check:";

			$multi = substr($multi, 0, -1); //rip the last : off
			$set2[] = "$key:LIST:$multi";
		}
		else
		{
			$set2[] = "$key:$sopt";
		}
	}
	file_put_contents($_SESSION['base_path'] . F('settings.txt'),implode(';', $set2));
}

//--------------------------------------------------------------
// LoadSavedSettings()
//
// Read FBID_settings.txt if exists, call CreateDefaultSettings if not or if version is incorrect
//--------------------------------------------------------------
function LoadSavedSettings() {
	$px_Setopts = array();

	if (file_exists($_SESSION['base_path'] . F('settings.txt'))) {
		$set_read_list = @explode(';', trim(file_get_contents($_SESSION['base_path'] . F('settings.txt'))));

		foreach ($set_read_list as $setting_option) {
			$set_name = @explode(':', $setting_option);

			if (count($set_name) > 2) { //we have a settings 'list'
				$liststart = explode(':', $setting_option,3);
				$listopt = explode(':', $liststart[2]);
				$tired = count($listopt);
				for ($i=0; $i < $tired; $i=$i+2) {
					$tired2 = $i+1;
					$px_Setopts[$liststart[0]][$listopt[$i]] = $listopt[$tired2];
				}
			} else {
				$px_Setopts[$set_name[0]] = $set_name[1];
			}
		}
		if($px_Setopts['version']<>FB_SETTINGS_VER) {
			$px_Setopts['version'] = FB_SETTINGS_VER;
			@unlink('sqlite_check.txt');
		}
	} else {
		$px_Setopts = CreateDefaultSettings();
	}
	return $px_Setopts;
}

function parse_neighbors() {
	$temp = file_get_contents($_SESSION['userId'] . '_flashInfo.txt');
	preg_match('/var g_friendData = \[([^]]*)\]/sim', $temp, $friend);
	unset($temp);
	if (!isset($friend[1])) return;
	preg_match_all('/\{([^}]*)\}/sim', $friend[1], $friend2);
	unset($friend);
	foreach($friend2[1] as $f)
	{
		preg_match_all('/"([^"]*)":"([^"]*)"/im', $f, $fr);
		$newarray[] = array_combine($fr[1], $fr[2]);
	}
	unset($friend2, $fr);
	$uSQL = '';
	foreach ($newarray as $friends)
	{
		if ($friends['is_app_user'] != 1) continue;
		$friends['pic_square'] = str_replace('\\/', '\\', $friends['pic_square']);
		$friends['name'] = str_replace("'", "''", $friends['name']);
		$friends['name'] = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $friends['name']);
		$uSQL .= "INSERT OR REPLACE INTO neighbors(neighborid, fullname, profilepic) values('" . $friends['uid'] . "',
				'" . $friends['name'] . "', '" . $friends['pic_square'] . "');";
	}
	$_SESSION['vDataStoreDB']->exec($uSQL);
	unset($uSQL, $newarray);
	return;
}

function parse_user()
{
	$temp = file_get_contents($_SESSION['userId'] . '_flashInfo.txt');
	preg_match('/var g_userInfo = \{([^}]*)\}/sim', $temp, $user);
	unset($temp);
	preg_match_all('/"([^"]*)":"([^"]*)"/im', $user[1], $fr);
	$newarray = array_combine($fr[1], $fr[2]);
	$newarray['name'] = str_replace("'", "''", $newarray['name']);
	$newarray['name'] = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $newarray['name']);
	unset($user, $fr);
	$uSQL = 'INSERT OR REPLACE INTO userids(userid, username) values("' . $_SESSION['userId'] . '", "' . $newarray['name'] . '");';
	$_SESSION['vDataStoreDB']->exec($uSQL);
	unset($uSQL);
}

function parse_flashvars()
{
	$temp = file_get_contents($_SESSION['userId'] . '_flashInfo.txt');
	preg_match('/var flashVars = \{([^}]*)\}/sim', $temp, $flash);
	unset($temp);
	preg_match_all('/"([^"]*)":"([^"]*)"/im', $flash[1], $fr);
	$newarray = array_combine($fr[1], $fr[2]);
	$newarray['game_config_url'] = str_replace('\\/', '/', $newarray['game_config_url']);
	$newarray['items_url'] = str_replace('\\/', '/', $newarray['items_url']);
	$newarray['swfLocation'] = str_replace('\\/', '/', $newarray['swfLocation']);
	$newarray['localization_url'] = str_replace('\\/', '/', $newarray['localization_url']);
	$newarray['app_url'] = str_replace('\\/', '/', $newarray['app_url']);
	$newarray['social_quest_url'] = str_replace('\\/', '/', $newarray['social_quest_url']);
	$newarray['asset_url'] = str_replace('\\/', '/', $newarray['asset_url']);
	$newarray['xml_url'] = str_replace('\\/', '/', $newarray['xml_url']);
	return $newarray;
}


function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
	$arrData = array();

	// if input is object, convert into array
	if (is_object($arrObjData)) {
		$arrObjData = get_object_vars($arrObjData);
	}

	if (is_array($arrObjData)) {
		foreach ($arrObjData as $index => $value) {
			if (is_object($value) || is_array($value)) {
				$value = objectsIntoArray($value, $arrSkipIndices); // recursive call
			}
			if (in_array($index, $arrSkipIndices)) {
				continue;
			}
			$arrData[$index] = $value;
		}
	}
	return $arrData;
}
class xml2array {

	function xml2array($xml) {
		if (is_string($xml)) {
			$this->dom = new DOMDocument;
			$this->dom->loadXml($xml);
		}

		return FALSE;
	}

	function _process($node) {
		$occurance = array();
		if (is_array($node->childNodes)|| is_object($node->childNodes))
		foreach($node->childNodes as $child) {
			$occurance[$child->nodeName]++;
		}
		if($node->nodeType == XML_TEXT_NODE) {
			$result = html_entity_decode(htmlentities($node->nodeValue, ENT_COMPAT, 'UTF-8'),
			ENT_COMPAT,'ISO-8859-15');
		}
		else {
			if($node->hasChildNodes()){
				$children = $node->childNodes;

				for($i=0; $i<$children->length; $i++) {
					$child = $children->item($i);

					if($child->nodeName != '#text') {
						if($occurance[$child->nodeName] > 1) {
							$result[$child->nodeName][] = $this->_process($child);
						}
						else {
							$result[$child->nodeName] = $this->_process($child);
						}
					}
					else if ($child->nodeName == '#text') {
						$text = $this->_process($child);

						if (trim($text) != '') {
							$result['value'] = $this->_process($child);
						}
					}
				}
			}

			if($node->hasAttributes()) {
				$attributes = $node->attributes;

				if(!is_null($attributes)) {
					foreach ($attributes as $key => $attr) {
						$result[$attr->name] = $attr->value;
					}
				}
			}
		}

		return $result;
	}

	function getResult() {
		return $this->_process($this->dom);
	}
}

<?php
require_once '../../fB_pluginAPI.php';
include 'main.php';
global $GBC;
$GBC = new GBsql();
global $GB_starttime;
global $GBGetTemp;
// global $vDataDB;
$GBGetTemp = array();
$GB_starttime = microtime();
$GB_startarray = explode(" ", $GB_starttime);
$GB_starttime = $GB_startarray[1] + $GB_startarray[0];
$GB_showtime = "N";
$DebugTimer = "";
$GB_Info = '';
$GB_file_error = 'Oeps we did not find a file that we where looking for. <br>
      This file is automicicly created by the bot when it makes a full cycle. <br>
      Hit Refresh when bot has fully loaded or<br>
      if you see this after the bot make a full cycle, try closing the bot and start it again.<br>';
//--------------------------------------------  The image stuff   --------------
global $images;
$images = 1;
if (isset($_POST['image2']))
{    $crimg = @$_POST['image2'];
$fileurl = str_replace("/","\\",$crimg);
return;
}// end image GET
// image stuff done
// let's load the databases & settings
// if database is not created yet. let's stop and wait the hook to run
if (!file_exists($_SESSION['base_path'] . "plugins/GiftBox/".GBox_DB_main))
{ AddLog2("GB screen not loading. No main database yet");
echo $GB_file_error . "<br><br>(main database list file missing)";
return false;
}
// if database is not created yet. let's stop and wait the hook to run
if( $_SESSION['userId'] == "") {  AddLog2("GB fail (userId unknown"); }
if (!file_exists($_SESSION['base_path'] . "plugins/GiftBox/".$_SESSION['userId']."_".GBox_DB_user))
{ AddLog2("GB screen not loading. No user database yet");
echo $GB_file_error . "<br><br>(user database list file missing)";
return false;
}
$GBC->GB_getSQLsetting();

// See what screen to load.
if (isset($_GET['url']))
{ 
$GB_url = @$_GET['url'];   
}
else
{ $GB_url = "giftbox";  }
//$GB_Info .= 'Current URL: '. $GB_url . '<br>';


if (isset($_GET['popupcode']))
{
	$code = $_GET['popupcode'];
	$unit = Units_GetUnitByCode($code);
	if (!empty($unit))
	{
		$nameraw = $unit['name'];
		$name = $unit['realname'];
		$iconurl = $unit['iconurl'];
		$type = $unit['type'];
		$subtype = $unit['subtype'];
	}
	$GBSQL = "SELECT * FROM action  WHERE _code = '".$code."'";
	$query = $GBC->_GBUser->query($GBSQL);
	$action = $query->fetchAll();

	if($images == 1 && $GBC->GB_Setting['ShowImage']){ $GB_image = GB_ShowIMGbig($unit);}else{$GB_image = "&nbsp;";}
	$TABtext = " ";

	echo '<TABLE CLASS="PopUp"><CAPTION CLASS="PopUp">GiftBox Manager Advanced settings</CAPTION>';
	echo '<THEAD ><TR CLASS="PopUp"><TH CLASS="PopUp">Image</TH><TH CLASS="PopUp">Data</TH><TH CLASS="PopUp">Settings</TH></TR></THEAD>';
	echo '<TBODY><TR CLASS="PopUp">';
	echo '<TD CLASS="PopUp">'.$GB_image.'</TD>';
	echo "<TD CLASS='PopUp'>$name<br>$nameraw<br>Class: $type<br>Code: $code</TD>";
	echo '<TD CLASS="PopUp">'.GB_popupcontent1($unit, $action, "check").'</TD></TR>';
	echo '<TR CLASS="PopUp"><TD CLASS="PopUp"></TD><TD CLASS="PopUp"></TD><TD CLASS="PopUp"><a href="javascript:TINY.box.hide()">close it</a></TD></TR></TBODY></TABLE>';
	return;
}


//--------------------------------------------  The settings  ------------------
$MAP_ObjectArray = array();
$Map_all_items = array();
$MapXY = array();
$EmptyXY = array();
global $GB_tempid;
if($GB_tempid == "")$GB_tempid = 63000;

// import the action.txt file into action DB.
$file = '';
if (!empty($_POST))
{
	if (isset($_POST['ImportNow']))
	{
		if($_POST['ImportNow'] == "stage1")
		{if (isset($_FILES['ImportFile'])){$file = $_FILES['ImportFile']['name']; $GB_Info .= 'Action file sellected. ';} }
		if($_POST['ImportNow'] == "stage2")
		{if (isset($_POST['ImportFile2']))
		{  $file = $_POST['ImportFile2'];   $GBC->GB_import_action('ADD', $file); $GB_Info .= 'Action file imported. ';}
		}
	} // end import
	// GB_export_action('SHOW', '' );
	if(isset($_POST['ExportNow']))   {   $GBC->GB_export_action('EXPORT', $_POST['FileName'] ) ;  $GB_Info .= 'Action file saved. ';  }
	// buildings                     //submitPlaceThis"  value="Set All to max"
	if(isset($_POST['submitPlaceThis']))
	{  $PlaceThisKey = array_keys($_POST['PTval']);
	$PTval = $_POST['PTval'];
	$PTcode = $_POST['PTcode'];
	$PTobj = $_POST['PTobj'];
	$PTmax = $_POST['PTmax'];
	foreach($PlaceThisKey as $value)
	{
		if($_POST['submitPlaceThis'] == "Set_All_999"){$PTval[$value] = "999";} //old
		if($_POST['submitPlaceThis'] == "Set All to max"){$PTval[$value] = $PTmax[$value];}
		if ($PTmax[$value] > "100")
		{ // this is old stuff
			$PTmax[$value] = "999";   $place_in_special = $PTval[$value];   $place_in_build = "0";
		}
		else
		{
			$place_in_special = "0";  $place_in_build = $PTval[$value];
		}
		if ($PTmax[$value] == "999")
		{ // this is a special building
			//$PTmax[$value] = "999";
			$place_in_special = $PTval[$value];
			$place_in_build = "0";
		}
		else
		{
			$place_in_special = "0";
			$place_in_build = $PTval[$value];
		}

		$GBC->GB_SQL_updAction("_place_in_special", $PTcode[$value], $place_in_special); //$field, $code, $val
		$GBC->GB_SQL_updAction("_place_in_build",   $PTcode[$value], $place_in_build);   //$field, $code, $val
		$GBC->GB_SQL_updAction("_place_in_max",     $PTcode[$value], $PTmax[$value]);    //$field, $code, $val
		$GBC->GB_SQL_updAction("_target",           $PTcode[$value], $PTobj[$value]);    //$field, $code, $val
	}
	$GB_Info .= 'Action saved. ';
	}

	if(isset($_POST['submitSpecialThis']))
	{
		$PlaceThisKey = array_keys($_POST['STval']);
		$PTval = $_POST['STval'];
		$PTcode = $_POST['STcode'];
		$PTobj = $_POST['STobj'];
		$PTmax = $_POST['STmax'];
		$GBC->_GBUser->query('BEGIN;');
		foreach($PlaceThisKey as $value)
		{
			if($_POST['submitSpecialThis'] == "Set_All_999"){$PTval[$value] = "999";}
			$GBC->GB_SQL_updAction("_place_in_special", $PTcode[$value], $PTval[$value]); //$field, $code, $val
			$GBC->GB_SQL_updAction("_target",           $PTcode[$value], $PTobj[$value]); //$field, $code, $val
		}
		$GBC->_GBUser->query('COMMIT;');
		$GB_Info .= 'Action for special saved. ';

	}



	//================= popup form data.
	if(isset($_POST['popup']))
	{
		$code = $_POST['popup'];
		$GBC->GB_SQL_updAction("_keep", $code, $_POST['KEEP']);
		if(isset($_POST['C'])){ $GBC->GB_SQL_updAction("_consume", $code, 'Y');}else{ $GBC->GB_SQL_updAction("_consume", $code, '0');}
		if(isset($_POST['P'])){ $GBC->GB_SQL_updAction("_place_on_farm", $code, 'Y');}else{ $GBC->GB_SQL_updAction("_place_on_farm", $code, '0');}
		if(isset($_POST['S'])){ $GBC->GB_SQL_updAction("_selling", $code, 'Y');}else{ $GBC->GB_SQL_updAction("_selling", $code, '0');}
		if(isset($_POST['RemPlaceInBuild']))
		{
			$GBC->GB_SQL_updAction("_place_in_build", $code, '0');
			$GBC->GB_SQL_updAction("_place_in_max", $code, '0');
			$GBC->GB_SQL_updAction("_target", $code, '0');
		}
		if(isset($_POST['RemPlaceInSpecial']))
		{
			$GBC->GB_SQL_updAction("_place_in_special", $code, '0');
			$GBC->GB_SQL_updAction("_place_in_max", $code, '0');
			$GBC->GB_SQL_updAction("_target", $code, '0');
		}
		$GB_Info .= 'Actions saved from popup. ';
	} // end popup handling

	// Change Open items settings.
	if(isset($_POST['OpenItems']))
	{

		$GB_OpenItems = array();

		foreach($_POST as $Key => $value)
		{
			if($Key == "url"){continue;}
			if($Key == "OpenItems"){continue;}
			if(strlen($Key) == '2' )
			{$code = $Key;   // code is 2 long
			$action =  $value;
			} // len 2
			if(strlen($Key) == '3' )
			{ // code is 3 long
				$code = substr($Key, 0, 2);
				$action =  $value;
			} // len 3
			if($action == "open") { $GB_OpenItems[] =$code;}
		}
		$GBC->GB_Setting['OpenItems'] = serialize($GB_OpenItems);
		$GBC->GB_Update_User_Setting("OpenItems" , $GBC->GB_Setting['OpenItems'] );
	}

	// Update the SQL from tabs.
	if(isset($_POST['update2']))
	{
		foreach($_POST as $Key => $value)
		{
			if($Key == "url"){continue;}
			if($Key == "update2"){continue;}
			$Update = 'N';          $keep = 0;
			if(strlen($Key) == '2' )
			{$code = $Key;   // code is 2 long
			$Update = 'Y';
			$action =  $value;
			if(strlen($value) > 4 ){ $keep = substr($value, 4, strlen($value)-4); $action = 'K';}
			} // len 2
			if(strlen($Key) == '3' )
			{ // code is 3 long
				$code = substr($Key, 0, 2);
				$Update = 'Y';
				$action =  $value;
				if(strlen($value) > 4 ){ $keep = substr($value, 4, strlen($value)-4); $action = 'K';}
			} // len 3
			if(strlen($Key) == '6' )
			{ // code is 6 long
				$code = substr($Key, 0, 2);
				if( substr($Key, -4, 4) == 'KEEP')
				{ $Update = 'Y';
				$action = 'K';
				}
			} // len 6
			if(strlen($Key) == '7' )
			{ // code is 7 long
				$code = substr($Key, 0, 2);
				if( substr($Key, -5, 4) == 'KEEP')
				{ $Update = 'Y';
				$action = 'K';
				}
			} // len 7
			if($Update  == 'Y' )
			{
				if($action == 'C' )
				{ // Place
					$GBC->GB_SQL_updAction("_consume", $code, 'Y');
					$GBC->GB_SQL_updAction("_place_on_farm", $code, '0');
					$GBC->GB_SQL_updAction("_selling", $code, '0');
				}
				if($action == 'P' )
				{ // Place
					$GBC->GB_SQL_updAction("_consume", $code, '0');
					$GBC->GB_SQL_updAction("_place_on_farm", $code, 'Y');
					$GBC->GB_SQL_updAction("_selling", $code, '0');
				}
				if($action == 'S' )
				{ // Sell
					$GBC->GB_SQL_updAction("_consume", $code, '0');
					$GBC->GB_SQL_updAction("_place_on_farm", $code, '0');
					$GBC->GB_SQL_updAction("_selling", $code, 'Y');
				}
				if($action == '0' )
				{ // Reset = do nothing
					$GBC->GB_SQL_updAction("_consume", $code, '0');
					$GBC->GB_SQL_updAction("_place_on_farm", $code, '0');
					$GBC->GB_SQL_updAction("_selling", $code, '0');
				}
				if($action == 'K' )
				{ // Keep
					$GBC->GB_SQL_updAction("_keep", $code, $value);
				}
			} // end update
		}
		$GB_Info .= 'Actions saved. ';
	}  // end update2

	if(isset($_POST['bushel']))
	{
		$bakeryneeds=array('an', 'as', 'b9', 'bb', 'bi', 'bl', 'ca', 'cc', 'cf', 'gc', 'gu', 'oa', 'on', 'po', 'pp', 'pu', 'pz', 'rb', 'rc', 'rw', 'sb', 'sm', 'su', 'sy  to', 'wh');
		$perfumeryneeds=array('ae', 'b9', 'bb', 'bl', 'cf', 'ci', 'd0', 'gc', 'gt', 'gu', 'ir', 'l3', 'l4', 'll', 'lm', 'mg', 'pp', 'pu', 'r0', 'r2', 'sb', 'sf');
		$wineryneeds=array('b9', 'bb', 'bl', 'ca', 'cc', 'ci', 'ci', 'dl', 'g2', 'gt', 'gu', 'l3', 'll', 'mg', 'pp', 'pu', 'r2', 'rb', 'rc', 'sb', 'sq', 'sr', 'su', 'to', 'wt', 'yw');
		$allneeds = array_merge($bakeryneeds, $perfumeryneeds, $wineryneeds);
		if($_POST['bushel'] == 'bakery') { $bushelsells = array_diff($allneeds, $bakeryneeds); }
		if($_POST['bushel'] == 'spa') { $bushelsells = array_diff($allneeds, $perfumeryneeds); }
		if($_POST['bushel'] == 'winery') { $bushelsells = array_diff($allneeds, $wineryneeds); }
		foreach($bushelsells as $bushelsell)
		{
			$GBC->GB_SQL_updAction("_selling", $bushelsell, "Y"); //$field, $code, $val
		}
		$GB_Info .= 'Action not sell bushel for: '.$_POST['bushel'];
	}


	if(isset($_POST['add_sell_2']))
	{ $GBC->GB_SQL_updAction("_selling", $_POST['add_sell_2'], "Y"); //$field, $code, $val
	$GB_Info .= 'Action sell for item code: '.$_POST['add_sell_2'];
	}
	if(isset($_POST['rem_sell_2']))
	{ $GBC->GB_SQL_updAction("_selling", $_POST['rem_sell_2'], "0"); //$field, $code, $val
	$GB_Info .= 'Action NOT sell for item code: '.$_POST['rem_sell_2'];
	}
	if(isset($_POST['add_place_2']))
	{ $GBC->GB_SQL_updAction("_place_on_farm", $_POST['add_place_2'], "Y"); //$field, $code, $val
	$GB_Info .= 'Action place for item code: '.$_POST['add_place_2'];
	}
	if(isset($_POST['rem_place_2']))
	{ $GBC->GB_SQL_updAction("_place_on_farm", $_POST['rem_place_2'], "0"); //$field, $code, $val
	$GB_Info .= 'Action NOT place for item code: '.$_POST['rem_place_2'];
	}
	//consume
	if(isset($_POST['add_consume_2']))
	{ $GBC->GB_SQL_updAction("_consume", $_POST['add_consume_2'], "Y"); //$field, $code, $val
	$GB_Info .= 'Action consume for item code: '.$_POST['add_consume_2'];
	}
	if(isset($_POST['rem_consume_2']))
	{ $GBC->GB_SQL_updAction("_consume", $_POST['rem_consume_2'], "0"); //$field, $code, $val
	$GB_Info .= 'Action NOT cone for item code: '.$_POST['rem_consume_2'];
	}
	if(isset($_POST['KEEPconsume']))
	{ $GBC->GB_SQL_updAction("_keep", $_POST['KEEPcode'], $_POST['KEEPconsume']);}
	header("Location: index.php?userId=" . $_SESSION['userId']);	
}
// now load the settings for the deco tabs
$GB_DecTabs = file($_SESSION['base_path'] . 'plugins/GiftBox/tab.txt');
$GB_DecTab1 = explode(';', $GB_DecTabs['0']);
$GB_DecTab2 = explode(';', $GB_DecTabs['1']);
$GB_DecTab3 = explode(';', $GB_DecTabs['2']);
$GB_DecTab4 = explode(';', $GB_DecTabs['3']);
$GB_DecTab5 = explode(';', $GB_DecTabs['4']);
global $px_ver_parser;
global $px_ver_settings;
$px_ver_needed = 216;
if ((!FB_PARSER_VER) || (FB_PARSER_VER < $px_ver_needed)) { echo "<br>**** ERROR: GiftBox manager Requires px_parser version v$px_ver_needed or higher ****<br>"; return; }

if(isset($_POST['add_ExclConstr']))
{
	if (array_key_exists('ExclConstr' , $GBC->GB_Setting) )
	{ $ExclConstr =  unserialize($GBC->GB_Setting['ExclConstr']);} else{$ExclConstr = array();}
	$ExclConstr[$_POST['add_ExclConstr']] = 'Exclude';
	// save
	$GBC->GB_Update_User_Setting('ExclConstr' , serialize($ExclConstr));
	// get the new settings
	$GBC->GB_Setting = $GBC->GB_getSQLsetting();
	header("Location: index.php?userId=" . $_SESSION['userId']);	
}
if(isset($_POST['rem_ExclConstr']))
{
	if (array_key_exists('ExclConstr' , $GBC->GB_Setting) )
	{ $ExclConstr =  unserialize($GBC->GB_Setting['ExclConstr']);} else{$ExclConstr = array();}
	$ExclConstr[$_POST['rem_ExclConstr']] = 'Build';
	// save
	$GBC->GB_Update_User_Setting('ExclConstr' , serialize($ExclConstr));
	// get the new settings
	$GBC->GB_Setting = $GBC->GB_getSQLsetting();
	header("Location: index.php?userId=" . $_SESSION['userId']);	
}


//-------------------------------------------- See if there are new settings to be saved version 2
$change = false;
if(isset($_POST['save_setting']))
{
	$action = 'RunPlugin';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoFuel';         if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoSpecials';     if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoSelling';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoPlace';        if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoFeetPet';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoMystery';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoVehicle';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoStorage';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoStorage1';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoColl';         if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoCollSell';     if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoCollTrade';    if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoCollKeep';     if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoConstr';       if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'DoPlaceBuild';   if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]);}
	$action = 'ShowImage';      if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]); $ImageTemp1 = $_POST[$action];}
	$action = 'ShowImageAll';   if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]); $ImageTemp2 = $_POST[$action];}
	$action = 'DoDebug';        if(isset($_POST[$action])) {$GBC->GB_Update_User_Setting($action , $_POST[$action]); }
	$action = 'DoResetXML';     if(isset($_POST[$action]))
	{ if($_POST['DoResetXML'] == '1')
	{  $GBSQL ="UPDATE gamesettings set _val='11111' WHERE _set='flashversion'";
	$GBC->_GBMain->query($GBSQL);
	$GBSQL ="UPDATE gamesettings set _val='1262304000' WHERE _set='LastUpdate'";
	$GBC->_GBMain->query($GBSQL);
	$GB_Info .= 'XML will be loaded (again) next cycle.<br>';
	}
	}
	// check to show image or not.
	$ImageTemp3 = $ImageTemp1 + $ImageTemp2;
	if($ImageTemp3 > 0) { $GBC->GB_Setting['ImageShow'] = '1';}else{$GBC->GB_Setting['ImageShow'] = '0';}
	$f = fopen($_SESSION['base_path'] . 'plugins/GiftBox/Image.txt', "w+");
	fwrite($f, serialize($GBC->GB_Setting));
	fclose($f);
	// get the new settings
	$GBC->GB_Setting = $GBC->GB_getSQLsetting();
	$GB_Info .= 'Settings saved<br>';
	header("Location: index.php?userId=" . $_SESSION['userId']);	
}

if(isset($_POST['UpdateMap'])) { create_image2();  header("Location: index.php?userId=" . $_SESSION['userId']);	} // end update Map
// GB_section to be updated.
if(isset($_POST['GB_section_def']))
{      
$GBC->GB_Update_User_Setting('AnimalX1' , 35);     $GBC->GB_Update_User_Setting('AnimalY1' , 0);
$GBC->GB_Update_User_Setting('AnimalX2' , 45);     $GBC->GB_Update_User_Setting('AnimalY2' , 65);
$GBC->GB_Update_User_Setting('TreeX1' , 55);       $GBC->GB_Update_User_Setting('TreeY1' , 0);
$GBC->GB_Update_User_Setting('TreeX2' , 65);       $GBC->GB_Update_User_Setting('TreeY2' , 65);
$GBC->GB_Update_User_Setting('DecorationX1' , 45); $GBC->GB_Update_User_Setting('DecorationY1' , 0);
$GBC->GB_Update_User_Setting('DecorationX2' , 55); $GBC->GB_Update_User_Setting('DecorationY2' , 65);
// get the new settings
$GBC->GB_Setting = $GBC->GB_getSQLsetting();
header("Location: index.php?userId=" . $_SESSION['userId']);	
}

if(isset($_POST['GB_section']))
{
	$GBC->GB_Update_User_Setting('AnimalX1' , (int) $_POST['AnimalX1']);
	$GBC->GB_Update_User_Setting('AnimalY1' , (int) $_POST['AnimalY1']);
	if($_POST['AnimalX1'] < $_POST['AnimalX2']) {$GBC->GB_Update_User_Setting('AnimalX2' , $_POST['AnimalX2']) ;}else{GB_Update_User_Setting('AnimalX2' , 50) ;}
	if($_POST['AnimalY1'] < $_POST['AnimalY2']) {$GBC->GB_Update_User_Setting('AnimalY2' , $_POST['AnimalY2']) ;}else{GB_Update_User_Setting('AnimalY2' , 50) ;}
	$GBC->GB_Update_User_Setting('TreeX1' , (int) $_POST['TreeX1']);
	$GBC->GB_Update_User_Setting('TreeY1' , (int) $_POST['TreeY1']);
	if($_POST['TreeX1'] < $_POST['TreeX2']) {$GBC->GB_Update_User_Setting('TreeX2' , $_POST['TreeX2']) ;}else{GB_Update_User_Setting('TreeX2' , 55) ;}
	if($_POST['TreeY1'] < $_POST['TreeY2']) {$GBC->GB_Update_User_Setting('TreeY2' , $_POST['TreeY2']) ;}else{GB_Update_User_Setting('TreeY2' , 55) ;}
	$GBC->GB_Update_User_Setting('DecorationX1' , (int) $_POST['DecorationX1']);
	$GBC->GB_Update_User_Setting('DecorationY1' , (int) $_POST['DecorationY1']);
	if($_POST['DecorationX1'] < $_POST['DecorationX2']) {$GBC->GB_Update_User_Setting('DecorationX2' , $_POST['DecorationX2']) ;}else{GB_Update_User_Setting('DecorationX2' , 60) ;}
	if($_POST['DecorationY1'] < $_POST['DecorationY2']) {$GBC->GB_Update_User_Setting('DecorationY2' , $_POST['DecorationY2']) ;}else{GB_Update_User_Setting('DecorationY2' , 60) ;}
	// get the new settings
	$GBC->GB_Setting = $GBC->GB_getSQLsetting();
	header("Location: index.php?userId=" . $_SESSION['userId']);	
} // end GB_section

// Collection stuff
$GB_CollCompl = GB_CollAmount();
$Bot_path = getcwd();
global $GB_ImagePath;
$GB_ImagePath = '';
/******************GiftBox manager by Christiaan****************************/
//<style type="text/css"> <?php include "GiftBox.css";  </style>
?>
<html>
<head>
<title>GiftBox Manager</title>
<link rel="stylesheet" type="text/css"
	href="helpers/GiftBox.css" />

<script language="javascript"> function Submit()      {      main_form.submit();      } </script>
<script language="JavaScript">
 function point_it(event){
  pos_x = event.offsetX?(event.offsetX):event.pageX-document.getElementById("pointer_div").offsetLeft;
  pos_y = event.offsetY?(event.offsetY):event.pageY-document.getElementById("pointer_div").offsetTop;
  farm_size = document.pointform.FarmSizeX.value;
  document.pointform.form_x.value = parseInt((pos_x/4));
  document.pointform.form_y.value = parseInt(farm_size-(pos_y/4));
 } </script>
<SCRIPT LANGUAGE="JavaScript">
   function CheckAll(chk)
     { for (i = 0; i < chk.length; i++)
       chk[i].checked = true ; }
   function UnCheckAll(chk)
     { for (i = 0; i < chk.length; i++)
       chk[i].checked = false ;  }
  function setCheckedValue(radioObj, newValue)
    { if(!radioObj)
      return;
  var radioLength = radioObj.length;
  if(radioLength == undefined) {
    radioObj.checked = (radioObj.value == newValue.toString());
    return; }
  for(var i = 0; i < radioLength; i++) {
    radioObj[i].checked = false;
    if(radioObj[i].value == newValue.toString()) {
      radioObj[i].checked = true;
    }
   }
  }
 function mapsave()
  {   document.mapsave.AnimalX1.value = '35';
      document.mapsave.AnimalY1.value = '0';
      document.mapsave.AnimalX2.value = '45';
      document.mapsave.AnimalY2.value = '65';
      document.mapsave.TreeX1.value = '55';
      document.mapsave.TreeY1.value = '0';
      document.mapsave.TreeX2.value = '65';
      document.mapsave.TreeY2.value = '65';
      document.mapsave.DecorationX1.value = '45';
      document.mapsave.DecorationY1.value = '0';
      document.mapsave.DecorationX2.value = '55';
      document.mapsave.DecorationY2.value = '65';
      }
</script>
<script language="javascript" defer="false">
//browser detection
    var agt=navigator.userAgent.toLowerCase();
    var is_major = parseInt(navigator.appVersion);
    var is_minor = parseFloat(navigator.appVersion);
    var is_nav  = ((agt.indexOf('mozilla')!=-1) && (agt.indexOf('spoofer')==-1)
                && (agt.indexOf('compatible') == -1) && (agt.indexOf('opera')==-1)
                && (agt.indexOf('webtv')==-1) && (agt.indexOf('hotjava')==-1));
    var is_nav4 = (is_nav && (is_major == 4));
    var is_nav6 = (is_nav && (is_major == 5));
    var is_nav6up = (is_nav && (is_major >= 5));
    var is_ie     = ((agt.indexOf("msie") != -1) && (agt.indexOf("opera") == -1));
</script>
<script type="text/javascript"
	src="helpers/2leveltab.js"></script>
<script type="text/javascript" src="helpers/tinybox.js"></script>
<script type="text/javascript"
	src="helpers/tablefilter.js"></script>
</head>
<BODY>
<?php
fBAcctHeader();
// filer tabs java script.
//  echo '<script type="text/javascript" >';
//  include "tablefilter.js";
//  echo '</script>';
// get info for GiftBox header.
// Get Giftbox
$result1 = $GBC->_GBUser->query("SELECT SUM(_amount) FROM giftbox WHERE _orig = 'GB'");
if ($result1->numRows() > 0) { $GB_total_in_giftbox = $result1->fetchSingle(); }else {$GB_total_in_giftbox = 0;}
// Get Concumable Box
$result1 = $GBC->_GBUser->query("SELECT SUM(_amount) FROM giftbox WHERE _orig = 'CB'");
if ($result1->numRows() > 0) { $GB_total_in_conbox = $result1->fetchSingle(); }else {$GB_total_in_conbox = 0;}
// now show the header
?>
<div id="TopBar">
<h1 id="TopBar">GiftBox Manager</h1>
</div>
<div id="TopHead">
<DIV id=GBInfo><?php echo $GB_Info  ?></DIV>
<div id="GBVersion"><?php echo GiftBox_version; ?></div>
<div id="GBCase">
<div class="GB GBHeadItem"><a title="GiftBox"
	href="index.php?url=giftbox&userId=<?php echo $_SESSION['userId']; ?>"> <span class="GBCount"><span><?php echo $GB_total_in_giftbox  ?></span>GiftBox</span>
</a></div>
<div class="GB GBHeadItem"><a title="Consumable Box"
	href="index.php?url=giftbox&userId=<?php echo $_SESSION['userId']; ?>"> <span class="GBCount"><span><?php echo $GB_total_in_conbox  ?></span>ConBox</span>
</a></div>
<div class="GB GBHeadItem"><a title="Collections"
	href="index.php?url=collection&userId=<?php echo $_SESSION['userId']; ?>"> <span class="GBCount"><span><?php echo $GB_CollCompl  ?></span>Collect</span>
</a></div>
</div>
</div>
<ul id="maintab" class="basictab">
	<li class="selected"><a href="index.php?url=giftbox&userId=<?php echo $_SESSION['userId']; ?>">GiftBox</a></li>
	<li rel="settings"><a
		href="index.php?url=settings_general&userId=<?php echo $_SESSION['userId']; ?>">Settings
	&#9660;</a></li>
	<li rel="cellar"><a href="index.php?url=cellar_cellar&userId=<?php echo $_SESSION['userId']; ?>">Cellar
	&#9660;</a></li>
	<li rel="place"><a href="index.php?url=place&userId=<?php echo $_SESSION['userId']; ?>">Place &
	Sell &#9660;</a></li>
	<li><a href="index.php?url=pets&userId=<?php echo $_SESSION['userId']; ?>">Pets</a></li>
	<li><a href="index.php?url=collection&userId=<?php echo $_SESSION['userId']; ?>">Collections</a></li>
	<li><a href="index.php?url=statistic&userId=<?php echo $_SESSION['userId']; ?>">Statistics</a></li>
	<li><a href="index.php?url=image&userId=<?php echo $_SESSION['userId']; ?>">Images</a></li>
	<li><a href="index.php?url=debug&userId=<?php echo $_SESSION['userId']; ?>">Debug</a></li>
	<li><a href="index.php?url=help&userId=<?php echo $_SESSION['userId']; ?>">FAQ & Help</a></li>
</ul>

<div id="settings" class="submenustyle"><a
	href="index.php?url=settings_general&userId=<?php echo $_SESSION['userId']; ?>">General settings</a>
<a href="index.php?url=settings_ImportNow&userId=<?php echo $_SESSION['userId']; ?>">Import
actions</a> <a href="index.php?url=settings_ExportNow&userId=<?php echo $_SESSION['userId']; ?>">Export
actions</a> <a href="index.php?url=settings_placewhere&userId=<?php echo $_SESSION['userId']; ?>">Place
where</a> <a
	href="index.php?url=settings_constructions&userId=<?php echo $_SESSION['userId']; ?>">Constructions</a>
<a href="index.php?url=settings_buildwi&userId=<?php echo $_SESSION['userId']; ?>">Buildings with
items</a> <a href="index.php?url=settings_specials&userId=<?php echo $_SESSION['userId']; ?>">Specials</a>
<a href="index.php?url=settings_garage&userId=<?php echo $_SESSION['userId']; ?>">Garage</a> <a
	href="index.php?url=settings_open&userId=<?php echo $_SESSION['userId']; ?>">Open gifts</a></div>

<div id="cellar" class="submenustyle"><a
	href="index.php?url=cellar_cellar&userId=<?php echo $_SESSION['userId']; ?>">What is in the
cellar</a></div>

<div id="place" class="submenustyle"><a
	href="index.php?url=place_all&userId=<?php echo $_SESSION['userId']; ?>">All</a> <a
	href="index.php?url=place_animal&userId=<?php echo $_SESSION['userId']; ?>">Animal</a> <a
	href="index.php?url=place_<?php echo $GB_DecTab1['0'] ?>&userId=<?php echo $_SESSION['userId']; ?>"><?php echo $GB_DecTab1['0'] ?></a>
<a
	href="index.php?url=place_<?php echo $GB_DecTab2['0'] ?>&userId=<?php echo $_SESSION['userId']; ?>"><?php echo $GB_DecTab2['0'] ?></a>
<a
	href="index.php?url=place_<?php echo $GB_DecTab3['0'] ?>&userId=<?php echo $_SESSION['userId']; ?>"><?php echo $GB_DecTab3['0'] ?></a>
<a
	href="index.php?url=place_<?php echo $GB_DecTab4['0'] ?>&userId=<?php echo $_SESSION['userId']; ?>"><?php echo $GB_DecTab4['0'] ?></a>
<a
	href="index.php?url=place_<?php echo $GB_DecTab5['0'] ?>&userId=<?php echo $_SESSION['userId']; ?>"><?php echo $GB_DecTab5['0'] ?></a>
<a href="index.php?url=place_deco_rest&userId=<?php echo $_SESSION['userId']; ?>">Decoration rest</a>
<a href="index.php?url=place_bushel&userId=<?php echo $_SESSION['userId']; ?>">Bushel</a> <a
	href="index.php?url=place_build&userId=<?php echo $_SESSION['userId']; ?>">Buildings</a> <a
	href="index.php?url=place_tree&userId=<?php echo $_SESSION['userId']; ?>">Trees</a> <a
	href="index.php?url=place_consume&userId=<?php echo $_SESSION['userId']; ?>">Consumables</a></div>
<script type="text/javascript">
//initialize tab menu, by passing in ID of UL
initalizetab("maintab");
</script>
</div>
<?php
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_general";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>General settings</h2>';
	echo 'Sellect what the GiftBox plugin should do for you. <br>';
	//print_r(GB_Get_User_Setting('RunPlugin'));
	//if(GB_Get_User_Setting('RunPlugin') == "Y"){echo "Run plugin Yes<br>";}else{echo "Run plugin No<br>";}

	echo '<form name="main_form" method="post">
          <input type="hidden" name="save_setting" value="1" />
          <input type="submit" value="Save changes" />
          <input name="url" type="hidden" value="settings_general" >';
	echo '<table width="90%" class="sofT" cellspacing="0">
    <tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Main</td><td class="helpHed">Info</td></tr>';
	$GBMenuA = 'RunPlugin';                        //MenuItem name for DB
	$GBMenuB = 'GiftBox plugin ON/OFF.';           //MenuItem Text
	$GBMenuC = 'Handle giftbox automatically.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoFuel';                        //MenuItem name for DB
	$GBMenuB = 'Enable Fuel. ON/OFF.';           //MenuItem Text
	$GBMenuC = 'Use fuel from giftbox and add them to total fuel.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoSpecials';                        //MenuItem name for DB
	$GBMenuB = 'Enable Specials. Like Pot of Gold etc.';           //MenuItem Text
	$GBMenuC = 'Edit '.GB_UrlText("settings_buildwi", "Buildings with items tab").' what to handle.';    //MenuItem Text explaination
	$GBMenuD = 'Enable of disable.';                    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoSelling';                        //MenuItem name for DB
	$GBMenuB = 'Enable Selling.';                     //MenuItem Text
	$GBMenuC = 'Use '.GB_UrlText("place_all", "tabs").' to sellect what to sell.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoPlace';                        //MenuItem name for DB
	$GBMenuB = 'Enable place on farm.';                     //MenuItem Text
	$GBMenuC = 'Use '.GB_UrlText("place_all", "tabs").' to sellect what to place on you farm.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoFeetPet';                        //MenuItem name for DB
	$GBMenuB = 'Feed Pets(s) in you farm.';                     //MenuItem Text
	$GBMenuC = 'Make sure you have Kibble or Tread in your giftbox.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoVehicle';                        //MenuItem name for DB
	$GBMenuB = 'Add vehicle parts.';                     //MenuItem Text
	$GBMenuC = 'Add vehicle parts to vehicle in garage.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoMystery';                        //MenuItem name for DB
	$GBMenuB = 'Open Mystery Gift & Eggs.';                     //MenuItem Text
	$GBMenuC = 'Use '.GB_UrlText("settings_open", "Open Gifts").' to sellect what to open from the giftbox.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo '</table><br>';
	//  ================== Storage Menu =================
	echo '<table width="90%" class="sofT" cellspacing="0">';
	echo '<tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Storage</td><td class="helpHed">Info</td></tr>';
	$GBMenuA = 'DoStorage';                        //MenuItem name for DB
	$GBMenuB = 'Allow GiftBox to store items.';                     //MenuItem Text
	$GBMenuC = 'Store items in the <b>Cellar</b>.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoStorage1';                        //MenuItem name for DB
	$GBMenuB = 'Store 1 of each.';                     //MenuItem Text
	$GBMenuC = 'Store 1 of each decoration and building into the cellar.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo '</table><br>';


	//  ================== collection Menu =================
	echo '<table width="90%" class="sofT" cellspacing="0">';
	echo '<tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Collections</td><td class="helpHed">Info</td></tr>';

	$GBMenuA = 'DoColl';                        //MenuItem name for DB
	$GBMenuB = 'Handle collectables.';                     //MenuItem Text
	$GBMenuC = 'Get collectables from giftbox and add them in collection.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoCollSell';                        //MenuItem name for DB
	$GBMenuB = 'Sell collectable (when 10 in collection).';                     //MenuItem Text
	$GBMenuC = 'When there are 10 in collection, sell the rest.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoCollTrade';                        //MenuItem name for DB
	$GBMenuB = 'Enable trade in of collections.';                     //MenuItem Text
	$GBMenuC = 'Trade in completed collections.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoCollKeep';                        //MenuItem name for DB
	$GBMenuB = 'Leave this amount of collections.';                     //MenuItem Text
	$GBMenuC = 'The amount of collections to leave. [between 1 - 10].';    //MenuItem Text explaination
	$GBMenuAOn = $GBC->GB_Get_User_Setting($GBMenuA);
	if($GBMenuAOn == "Not Found" || $GBMenuAOn == "Fail"){$GBMenuAOn = "5"; }
	echo '<tr><td><input name="' . $GBMenuA . '" type="text" size="2" maxlength="2"  value="'. $GBMenuAOn . '"  ></td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo '</table><br>';
	//  ================== construction & building Menu =================
	echo '<table width="90%" class="sofT" cellspacing="0">';
	echo '<tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Buildings</td><td class="helpHed">Info</td>';

	$GBMenuA = 'DoConstr';                        //MenuItem name for DB
	$GBMenuB = 'Enable construction of buildings.';                     //MenuItem Text
	$GBMenuC = 'Building parts will be added to the building(s)<br> This need aditional settings in the "Construction tab" .';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoPlaceBuild';                        //MenuItem name for DB
	$GBMenuB = 'Enable place of items in buildings.';                     //MenuItem Text
	$GBMenuC = 'Place items in buildings. Like cow in dairy farm.<br> This need aditional settings in the "Buildings with items" tab.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo '</table><br>';
	//  ================== Images Menu =================
	echo '<table width="90%" class="sofT" cellspacing="0">';
	echo '<tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Images</td><td class="helpHed">Info</td>';

	$GBMenuA = 'ShowImage';                        //MenuItem name for DB
	$GBMenuB = 'Enable the images on the tab giftbox.';                     //MenuItem Text
	$GBMenuC = 'Enable the images.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'ShowImageAll';                        //MenuItem name for DB
	$GBMenuB = 'Enable the images on the other tabs.';                     //MenuItem Text
	$GBMenuC = 'Enable the images.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo '</table><br>';
	//  ================== Advanced Menu =================
	echo '<table width="90%" class="sofT" cellspacing="0">';
	echo '<tr><td width="90px" class="helpHed">Settings</td><td width="250px" class="helpHed">Advangced</td><td class="helpHed">Info</td>';

	$GBMenuA = 'DoDebug';                        //MenuItem name for DB
	$GBMenuB = 'Enable Debuging.';                     //MenuItem Text
	$GBMenuC = 'will create more detailed output.';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	$GBMenuA = 'DoResetXML';                        //MenuItem name for DB
	$GBMenuB = 'Reset XML.';                     //MenuItem Text
	$GBMenuC = 'Enable this to load all the XML from the server again. <br>This will happen durring the database routing (which run every 10 min.) ';    //MenuItem Text explaination
	if($GBC->GB_Get_User_Setting($GBMenuA) == "1"){$GBMenuAOn = "CHECKED"; $GBMenuAOff = "";}else{$GBMenuAOn = ""; $GBMenuAOff = "CHECKED";}
	echo '<tr><td>On<input type="radio" name="' . $GBMenuA . '" value="1" '. $GBMenuAOn.'/>';
	echo '<input type="radio" name="' . $GBMenuA . '" value="0" '. $GBMenuAOff.'/> Off</td><td>';
	echo $GBMenuB .'</td><td>'.$GBMenuC.''.GB_HelpText($GBMenuA , "?").'</td></tr>';

	echo ' </table></form>';
}


//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_placewhere";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Place where</h2>';
	echo '<p><b>Make your settings for the place: </b></p>';
	@list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', fBGetDataStore('playerinfo'));
	echo '<table width="40%" border="0" cellspacing="0">';
	echo '<tr><td>';
	echo '<form method="post">';
	echo '<input type="submit" value="Reset to defaults" />';
	echo '<input type="hidden" name="GB_section_def" value="def" /> ';
	echo '<input name="url" type="hidden" value="'. $GB_url . '" >';
	echo '</form>';
	echo '</td><td>';
	echo '<form name="mapsave" method="post">';
	echo '<input type="submit" value="Save changes" />';
	echo '<input type="hidden" name="GB_section" value="section" /> ';
	echo '<input name="url" type="hidden" value="'. $GB_url . '" >';
	echo '</td></tr>';
	echo '</table>';

	echo '<table class="sofT2" cellspacing="0">';
	echo '<tr><td>';
	echo '<b> Settings: </b> (note that maximum for your farm is:' . $FarmSizeX;
	// iner table
	echo '<table class="sofT2" cellspacing="0">';
	echo '<tr><td>';
	echo 'Place animal in this section:<br>';
	echo '<input name="AnimalX1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['AnimalX1'] . '" >';
	echo 'X begin.<br>';
	echo '<input name="AnimalY1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['AnimalY1'] . '" >';
	echo 'Y begin.<br>';
	echo '<input name="AnimalX2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['AnimalX2'] . '" >';
	echo 'X end.<br>';
	echo '<input name="AnimalY2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['AnimalY2'] . '" >';
	echo 'Y end.<br>';
	echo '</td><td>';
	echo 'Total empty places (1x1):<br>';
	echo $GBC->TEmptyXY3("Animal", "ALL")."<br> (indicated by bleu in the image)<br>";
	echo '</td></tr><tr><td>';
	echo 'Place tree in this section:<br>';
	echo '<input name="TreeX1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['TreeX1'] . '" >';
	echo 'X begin.<br>';
	echo '<input name="TreeY1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['TreeY1'] . '" >';
	echo 'Y begin.<br>';
	echo '<input name="TreeX2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['TreeX2'] . '" >';
	echo 'X end.<br>';
	echo '<input name="TreeY2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['TreeY2'] . '" >';
	echo 'Y end.<br>';
	echo '</td><td>';
	echo 'Total empty places (1x1):<br>';
	echo $GBC->TEmptyXY3("Tree", "ALL")."<br> (indicated by yellow in the image)<br>";
	echo '</td></tr><tr><td>';
	echo 'Place decoration in this section:<br>';
	echo '<input name="DecorationX1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['DecorationX1'] . '" >';
	echo 'X begin.<br>';
	echo '<input name="DecorationY1" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['DecorationY1'] . '" >';
	echo 'Y begin.<br>';
	echo '<input name="DecorationX2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['DecorationX2'] . '" >';
	echo 'X end.<br>';
	echo '<input name="DecorationY2" type="text" size="3" maxlength="3"  value="'. $GBC->GB_Setting['DecorationY2'] . '" >';
	echo 'Y end.<br>';
	echo '</td><td>';
	echo 'Total empty places (1x1):<br>';
	echo $GBC->TEmptyXY3("Decoration", "ALL")."<br> (indicated by black in the image)<br>";
	echo '</td></tr>';
	echo '</form>';
	echo '</table>';
	// end inner table
	echo 'X is going up, Y is going right.<br>';
	echo 'X begin should be smaller the X end.<br>';

	echo '</td>';
	echo '<td >';
	if (extension_loaded('gd') && function_exists('gd_info'))
	{
		echo 'Click update map after you have changed the settings<br>';
		echo '<form method="post">';
		echo '<input type="submit" value="Update map" />';
		echo '<input type="hidden" name="UpdateMap" value="Yes" /> ';
		echo '<input name="url" type="hidden" value="'. $GB_url . '" >';
		echo '</form>';
		$GB_map_image = $_SESSION['userId']."_FarmMap3.png";
		if (file_exists($GB_map_image))
		{
			$ImageSize = $FarmSizeX*4;
			$ImageSize = $ImageSize+3;
			echo '<form name="pointform" method="post">';
			echo 'Click on the map to find position. X<input type="text" name="form_x" size="3" /> Y<input type="text" name="form_y" size="3" /><br>';
			echo '<img id="pointer_div" onclick="point_it(event)"  src="' . $GB_map_image . '" WIDTH="'.$ImageSize.'"></div><br>';
			echo '<input type="hidden" name="FarmSizeX" value="'.$FarmSizeX.'" /> ';
			echo '</form> ';

		}
		echo "<br>When place is enabled & bot has run some time, the map will be more detailed.";
	}
	else
	{
		echo "It looks like php_gd2.dll is NOT installed<br> Follow the instructions in the README file if you like to see a picture here";
	}

	echo '</td></tr>';
	echo '</table>';
	echo '<br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_constructions";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Constructions</h2>';
	echo '<b>This page show the buildings that are under construction.  </b>';
	echo '<br>';
	echo '<i>When new storage buildings are introduced to the game these will be detected automaticly.</i><br>';
	echo '<b>Constructions are switched';
	if($GBC->GB_Get_User_Setting('DoConstr') == '1') { echo ' ON </b><br>';}else{echo '<font color="red" size="5"> OFF </font></b><br>';}
	echo '<br>';
	echo 'All buildings that can contain building parts <br>';
	echo '<i>You can disable construction from this page.</i><br>';
	if (array_key_exists('ExclConstr' , $GBC->GB_Setting) )
	{
		$ExclConstr =  unserialize($GBC->GB_Setting['ExclConstr'])  ;
	} else{$ExclConstr = array();}
	echo '<table class="sofT" cellspacing="0">
    <tr><td class="helpHed">Building name:</td><td class="helpHed">Current action</td><td class="helpHed">change</td></tr>';
	$GBSQL = "SELECT DISTINCT _UnitBuildName FROM BuildingParts";
	$query = $GBC->_GBUser->query($GBSQL);
	$UnitBuildNames = $query->fetchAll();
	foreach($UnitBuildNames as $UnitBuildName)
	{
		echo '<tr><td> ' . $UnitBuildName['_UnitBuildName'] . "</td>";
		if (array_key_exists($UnitBuildName['_UnitBuildName'] , $ExclConstr) )
		{
			if($ExclConstr[$UnitBuildName['_UnitBuildName']] == 'Exclude')
			{
				echo '<td> Parts will NOT be added to construction </td><td>';
				echo '<form method="post">';
				echo '<input type="submit" value="Do construct" class="button"/>';
				echo '<input type="hidden" name="rem_ExclConstr" value="' . $UnitBuildName['_UnitBuildName'] . '"/>';
				echo '<input type="hidden" name="url" value="'.$GB_url.'" /> ';
				echo '</form></td></tr>';
			}
			else
			{
				echo '<td> Parts will be added to construction </td><td>';
				echo '<form method="post">';
				echo '<input type="submit" value="Do NOT construct" class="button"/>';
				echo '<input type="hidden" name="add_ExclConstr" value="' . $UnitBuildName['_UnitBuildName'] . '"/>';
				echo '<input type="hidden" name="url" value="'.$GB_url.'" /> ';
				echo '</form></td></tr>';
			}
		}
		else
		{
			echo '<td> Parts will be added to construction </td><td>';
			echo '<form method="post">';
			echo '<input type="submit" value="Do NOT construct" class="button"/>';
			echo '<input type="hidden" name="add_ExclConstr" value="' . $UnitBuildName['_UnitBuildName'] . '"/>';
			echo '<input type="hidden" name="url" value="'.$GB_url.'" /> ';
			echo '</form></td></tr>';
		}
	}

	echo '</table>';
	echo '<br>';
	echo '<hr>';
	echo 'Here you find the overview of the buildings that need parts. <br>';
	echo '<table class="sofT" cellspacing="0">
    <tr><td class="helpHed">Building name:</td><td class="helpHed">Building part</td><td class="helpHed">Have</td><td class="helpHed">Action</td></tr>';

	$GBSQL ="SELECT * FROM BuildingParts WHERE _ObjId != 0 ";
	$query = $GBC->_GBUser->query($GBSQL);
	if ($query->numRows() > 0)
	{ $BuildingParts = $query->fetchAll();
	foreach($BuildingParts as $BuildingPart)
	{
		echo '<tr><td>' . $BuildingPart['_UnitBuildName'] . '</td>';
		echo     '<td>' . $BuildingPart['_itemName'] . '  [' . $BuildingPart['_itemCode'] . '] </td>';
		echo     '<td>' . $BuildingPart['_ObjHave'] .  '</td>';
		echo     '<td>' . $BuildingPart['_action'] .  '</td></tr>';
	}
	}
	echo '</table>';
	echo '<br>';

	// echo GB_DetectBuildingParts4();
	echo '<br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_buildwi";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Buildings with items</h2>';
	echo '<b>Make your settings for the buildings: </b>';
	echo '<i>Note that you can change setting for 1 building at a time </i>';
	echo '<i>edit that building and press SAVE </i> ' .GB_HelpText("Each item can only be added to 1 building at a time <br>(for example, if you have multiple dairyfarms, you can only add brow cow to one of them.)<br> when adding the same item to multiple buildingd, the last one will be saved." , "More");

	// Let's see which buildings can contain items.
	$GBSQL ="SELECT DISTINCT _name FROM StorageConfig WHERE _part = '0'";
	$result1 = $GBC->_GBMain->query($GBSQL);
	if ($result1->numRows() > 0) { $Buildings = $result1->fetchAll(); }else {$Buildings = array();}
	foreach ($Buildings as $Building)
	{  // Get the unit details for this building
		$GBSQL = "SELECT DISTINCT  * FROM units WHERE _storageType_itemClass = '".$Building['_name']."'";
		if($Building['_name'] == 'hatchstorage'){$GBSQL = "SELECT DISTINCT  * FROM units WHERE _name = '".$Building['_name']."' ";}
		$query = $GBC->_GBMain->query($GBSQL);
		$Units = $query->fetchAll();
		foreach($Units as $Unit)
		{
			// get frendly name
			$buildingname = Units_GetRealnameByName($Unit['_name']);
			// get the obj number from that building on the farm
			$GBSQL = "SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = '". $Unit['_name']. "'" ;
			$result = $GBC->_GBUser->query($GBSQL);
			$Objectbuildings = $result->fetchAll();

			echo  '<br><b>' . $buildingname . "</b> <br>";
			foreach($Objectbuildings as $Objectbuilding)
			{
				// Get the data from this object.
				$GBSQL = "SELECT _set,_val FROM objects WHERE _obj = '". $Objectbuilding['_obj']. "'" ;
				$query = $GBC->_GBUser->query($GBSQL);
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
					$UnitCapacity = $Unit['_capacity'];
					if(array_key_exists('expansionLevel', $TargetObject))
					{
						$level = $TargetObject['expansionLevel'];
						if($level > 1)
						{
							$GBSQL = "SELECT _capacity FROM unitbuilding WHERE _level = '". $level. "' AND _buildingcode = '". $Unit['_code']. "' " ;
							$result = $GBC->_GBMain->query($GBSQL);
							$UnitCapacity = $result->fetchSingle();
						}
					} else {$level = 0;}
					// count the total amount of items.
					$TotItems = '';
					if(is_array($TargetObject['contents']))
					{ // count the contents
						foreach($TargetObject['contents'] as $content)
						{ $TotItems = $TotItems + $content['numItem'] ;
						$TargetCont[$content['itemCode']] = $content['numItem'];
						}
					} // end contents

					// now check if the item is in totstorage.
					$featureCreditsName = 'N';
					if($TargetObject['itemName'] == 'valentinesbox')   {$featureCreditsName = 'valentine';}
					if($TargetObject['itemName'] == 'potofgold')       {$featureCreditsName = 'potOfGold';}
					if($TargetObject['itemName'] == 'easterbasket')    {$featureCreditsName = 'easterBasket';}
					if($TargetObject['itemName'] == 'wedding')         {$featureCreditsName = 'tuscanWedding';}
					if($TargetObject['itemName'] == 'beehive_finished'){$featureCreditsName = 'beehive';}
					if($TargetObject['itemName'] == 'hatchstorage')    {$featureCreditsName = 'InventoryCellar';}  //added
					//echo "Tatget obj name". $TargetObject['itemName'] . 'fe nmae' . $featureCreditsName.'<BR>';
					//echo "Tatget obj count". $TotItems .'<BR>';
					if($featureCreditsName != 'N')
					{ $GBSQL = "SELECT * FROM totstorage WHERE _storagecode = '".$featureCreditsName."' AND _itemcode = 'current'";
					$query = $GBC->_GBUser->query($GBSQL);
					if ($query->numRows() > 0)
					{ $totstorage = $query->fetchAll()  ;
					$TotItems = $totstorage['0']['_amount'];
					}
					}
					//echo "Tatget obj count". $TotItems.'<BR>' ;

					if($UnitCapacity > $TotItems){$TableClass = ' class="sofT" ';}else{$TableClass = ' class="sofTfull" ';}

					// write the header of the table.
					echo '<br><b>' . GBHead($GBC->GB_get_friendlyName($TargetObject['itemName'])) . "</b> Name: ".$TargetObject['itemName']." State: " . $TargetObject['state'] . " Code: ". $Unit['_code'] . "  Capacity: ". $UnitCapacity . "  level: " . $level . "  id: " . $TargetObject['id']. "<br>";
					echo '<table '.$TableClass.' cellspacing="0"><tr><td class="helpHed">Image</td><td class="helpHed">Amount</td><td class="helpHed">Name</td><td class="helpHed">Maximum</td><td class="helpHed">Amount to put in the '.$TargetObject['itemName'].'</td></tr>';
					echo '<form method="post">';   //?url=settings_buildwi
					echo '<input name="url" type="hidden" value="'. $GB_url . '" >';
					//which items fit into this building?
					$GBSQL ="SELECT _itemCode ,_allowKeyword, _limit FROM StorageConfig WHERE _part = '0' AND _name ='". $Unit['_storageType_itemClass'] ."'";
					if($Building['_name'] == 'hatchstorage'){$GBSQL ="SELECT _itemCode ,_allowKeyword, _limit FROM StorageConfig WHERE _part = '0' AND _name ='". $Unit['_name'] ."'";}  // added
					$query = $GBC->_GBMain->query($GBSQL);
					$GB_AllItemPosible1 = $query->fetchAll(SQLITE_ASSOC);
					$j=0; $GB_AllItemPosible = array();
					$GBSQL ="SELECT _code AS _itemCode FROM units WHERE _keyword = '". $GB_AllItemPosible1['0']['_allowKeyword'] ."'";
					$query = $GBC->_GBMain->query($GBSQL);
					$GB_AllItemPosible2 = $query->fetchAll(SQLITE_ASSOC);
					foreach($GB_AllItemPosible2 as $temp){$GB_AllItemPosible[$temp['_itemCode']] = 0; $j++; }
					foreach($GB_AllItemPosible1 as $temp)
					{ $GB_AllItemPosible[$temp['_itemCode']] = $temp['_limit'];  $j++; } // _limit = 0 or 1 for bull
					$ItemPNumMax = 0;
					foreach ($GB_AllItemPosible as $ItemP => $ItemP2)
					{
						// Set defaults
						$ItemInputVal = 0 ;
						$ItemPNum = 0;
						$GBaction = $GBC->GBSQLgetAction($ItemP);
						// set the maximum for this building
						$PutMax =  $UnitCapacity;
						if($ItemP2 > 0){$PutMax = $ItemP2;}

						// check if there is data for this building
						if(array_key_exists("_target", $GBaction))
						{ // data exist Check that it is this building
							if($GBaction['_target'] == $TargetObject['id'] )
							{  // Yes, it is this building
								if($UnitCapacity > "100")
								{ // this is special
									$ItemInputVal = $GBaction['_place_in_special'];
									$PutMax = 999;
								}
								else
								{ // this is normal
									$ItemInputVal = $GBaction['_place_in_build'];
								}

							}
						}
						$ItemInput  =  '<input name="PTval[]" type="text" size="3" maxlength="3"  value="'. $ItemInputVal . '" >';
						$ItemInput .=  '<input name="PTcode[]" type="hidden" value="'. $ItemP . '" >';
						$ItemInput .=  '<input name="PTobj[]" type="hidden" value="'. $TargetObject['id'] . '" >';
						$ItemInput .=  '<input name="PTmax[]" type="hidden" value="'. $PutMax . '" >';
						$ObjD = $GBC->GBSQLGetUnitByCode($ItemP);
						foreach($TargetObject['contents'] as $contents) { if($contents['itemCode'] == $ItemP) {$ItemPNum = $contents['numItem'];} }
						$GB_displ_name = $GBC->GB_get_friendlyName($ObjD['_name']) . '<br>[ ' . $ObjD['_name'] . ' ' . $ObjD['_code'] . ' ]';

						$ItemPNumMax = $ItemPNumMax + $ItemPNum;
						if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($ObjD);}else{$GB_image = "&nbsp;";}
						echo '<tr><td>'.$GB_image.'</td><td>'. $ItemPNum .'</td><td>'.$GB_displ_name.'</td><td>max '.$PutMax. '</td><td>'.$ItemInput.'</td></tr>';
					}
					echo '<tr><td></td><td></td><td>Currently in this building:</td><td>'.$TotItems .'</td><td>';
					echo '<input type="submit" name="submitPlaceThis"  value="Save" />';
					echo '<input type="submit" name="submitPlaceThis"  value="Set All to max" />';
					echo '</form>';
					echo '</td></tr>';
					echo '</table>';
				}// if construction

			}
		}  // for each $Units

	}

	echo "<br>";
	echo '<br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_specials";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Specials</h2>';
	//echo '<b>This screen is for information only. Items are automaticly detected and buildings are found automaticly.</b><br>';
	echo '<b><font color="red" size="4">This screen is not used anymore. The specials are now added to the tab buildings with items.</font></b><br>';
	echo '<i>You have to enable special in general settings to make this work</i><br><br>';
	echo '<b>Specials are switched';
	if($GBC->GB_Get_User_Setting('DoSpecials') == '1') { echo ' ON </b><br>';}else{echo '<font color="red" size="5"> OFF </font></b><br>';}
	echo '<br>';
	$output = $GBC->GB_DetectSpecials2();
	echo $output;
	echo '<br><br>';
	//  if(GB_Get_User_Setting('DoDebug') == '1')  print_r($GB_specials);
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_garage";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Garage</h2>';
	echo '<p><b>In this tab you can see the content of the garage. </b></p>';
	echo '<i>Under construction. </i><br>';

	echo $GBC->GB_garage('html');
	echo "<br>";
	echo "<br>";
	echo "<br>";

	$hook =  $GBC->GB_garage('hook');
	print_r($hook);

	echo "<br>";
	echo "<br>";
}//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_open";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Open mystery gifts & Eggs</h2>';
	echo '<p><b>Which mystery gifts or eggs to open. </b>';
	echo '<i>If there is a item missing in this list, please add the code into the opengift.txt in the GiftBox folder. </i><br>';
	// now load the settings for the .txt file
	$GB_OpenItems = unserialize($GBC->GB_Setting['OpenItems']);
	$GBGetTemp = array();
	$GB_opengifts = file('opengift.txt');
	$GB_opengift = explode(',', $GB_opengifts['0']);
	echo '<form method="post">';
	echo '<input type="hidden" name="url" value="' . $GB_url . '"/>';
	echo '<input type="hidden" name="OpenItems" value="OpenItems"/>';
	echo'<input type="submit" value="Save" class="button"/>';
	echo '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Image</td><td class="helpHed">Name</td><td class="helpHed">Code</td><td class="helpHed">&nbsp;</td><td class="helpHed">&nbsp;</td></tr>';
	foreach($GB_opengift as $GB_openitem)
	{

		$code = $GB_openitem;
		$GBGetTempCODE = strtoupper($code);
		if(array_key_exists($GBGetTempCODE,$GBGetTemp))
		{
			$GBGetTemp[$GBGetTempCODE] = $GBGetTemp[$GBGetTempCODE] + 1;
			$GBCode = $GBGetTemp[$GBGetTempCODE];
		}
		else
		{
			$GBGetTemp[$GBGetTempCODE] = 0;
			$GBCode = '';
		}

		$unit = Units_GetUnitByCode($code) ;
		if (!empty($unit))
		{
			$nameraw = $unit['name'];
			@$name = $unit['realname'] ;
			$iconurl = $unit['iconurl'];
			$type = $unit['type'];
			@$subtype = $unit['subtype'];
		}
		$ActionN = "CHECKED";  $ActionOpen = '';
		if(in_array($code, $GB_OpenItems)) {$ActionN = '';  $ActionOpen = "CHECKED"; }
		$GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBCode . '" value="0" '.$ActionN.' />';
		$GB_Radio .= ' |   Open<input type="radio" name="' . $code . '' . $GBCode . '" value="open" '.$ActionOpen.' /> ';
		// let´s print the screen
		if($images == 1 && $GBC->GB_Setting['ShowImage']){ $GB_image = GB_ShowIMG($unit);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>$GB_image </td><td>$name<br>$nameraw ($type)</td><td>$code</td><td>";
		echo " $GB_Radio</td><td>&nbsp;</td></tr>";
	}
	echo '</table>';
	echo '</form>';


	echo "<br>";
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "giftbox";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Giftbox</h2><p>';
	echo '<b>What is in the GiftBox: </b><br>';
	echo '<br>';
	echo '<i>Note that Keep only aplies for consume & selling.</i><br>';
	echo '<br>';
	echo '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Image</td><td class="helpHed">Amount</td><td class="helpHed">Name</td><td class="helpHed">Code</td><td class="helpHed">Can be stored?</td><td class="helpHed">Comments</td><td class="helpHed">In which tab?</td></tr>';
	$GBSQL = "SELECT * FROM giftbox ";
	$query = $GBC->_GBUser->query($GBSQL);
	$giftboxs = $query->fetchAll();
	$GB_popupJava = '';
	foreach ($giftboxs as $giftbox)
	{
		$code = $giftbox["_itemcode"];
		$amount = $giftbox["_amount"];
		if($amount >0){
			$unit = Units_GetUnitByCode($code);
			if (!empty($unit))
			{
				$nameraw = $unit['name'];
				$name = $unit['realname'] ;
				$iconurl = $unit['iconurl'];
				$type = $unit['type'];
				$subtype = @$unit['subtype'];
			}

			$GBSQL = "SELECT * FROM action  WHERE _code = '".$code."'";
			$query = $GBC->_GBUser->query($GBSQL);
			$action = $query->fetchAll();
			if (!empty($action))
			{
				$place_on_farm = $action['0']['_place_on_farm'];
				$place_in_build = $action['0']['_place_in_build'];
				$place_in_amount = $action['0']['_place_in_amount'];
				$place_in_special = $action['0']['_place_in_special'];
				$place_in_special = $action['0']['_place_in_special'];
				$selling = $action['0']['_selling'];
				$keep = $action['0']['_keep'];
				$construction = $action['0']['_construction'];
			}

			// let´s print the screen
			if($images == 1 && $GBC->GB_Setting['ShowImage']){ $GB_image = GB_ShowIMG($unit);}else{$GB_image = "&nbsp;";}
			// popup code
			$now = time();
			$GB_popupText = '<div class="button" id="testclick'.$code.'"><strong>Advanced settings</strong></div>';
			//T$('testclick1').onclick = function(){TINY.box.show('index.php?popupcode=KG',1,0,0,1)}
			$GB_popupJava .= 'T$(\'testclick'.$code.'\').onclick = function(){TINY.box.show(\'index.php?userId=' . $_SESSION['userId'] . '&popupcode='.$code.'&time='.$now.'\',1,0,0,1)}
      ';
			$TABtext = " ";
			echo "<tr><td>$GB_image </td><td ALIGN=CENTER> $amount </td><td>$name<br>$nameraw ($type)</td><td>$code</td>";
			echo "<td>" . GB_CanWeStore($unit) . "</td><td>";
			echo GB_input2($unit, $action, "button")." </td><td>".$GB_popupText."</td></tr>";
		} //if amount 0
	}
	echo '</table>';
	echo '<br><br><br><br>';
	echo '<script type="text/javascript">';
	echo $GB_popupJava ;
	echo '</script>';
	echo '<br><br><br><br>';
	echo '<br><br><br><br>';
	echo '<br><br><br><br>';

}


//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "cellar_cellar";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Cellar</h2><p>';
	echo '<b>What is in the Cellar</b><br>';
	echo '<br>';
	echo '<i>Note that store from the GiftBox only works if you have a cellar.</i><br>';
	echo '<br>';
	$GBC->GB_checkCellar();
	echo 'Total storage place in cellar: '.$GBC->GB_Setting['StorageCapacity'].'<br>';
	echo 'Total items in cellar: '.$GBC->GB_Setting['StorageUsed'].'<br>';
	echo '<br>';
	@$cellars = unserialize($GBC->GB_Setting['StorageContent']);
	echo '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Image</td><td class="helpHed">Amount</td><td class="helpHed">Name</td><td class="helpHed">Code</td><td class="helpHed">&nbsp;</td><td class="helpHed">&nbsp;</td></tr>';
	if (is_array($cellars))
	{
		foreach ($cellars as $cellar)
		{
			$code = $cellar["_itemcode"];
			$amount = $cellar["_amount"];
			if($amount >0){

				$unit = Units_GetUnitByCode($code) ;
				if (!empty($unit))
				{
					$nameraw = $unit['name'];
					$name = $unit['realname'] ;
					$iconurl = $unit['iconurl'];
					$type = $unit['type'];
					@$subtype = $unit['subtype'];
				}
				// let´s print the screen
				if($images == 1 && $GBC->GB_Setting['ShowImage']){ $GB_image = GB_ShowIMG($unit);}else{$GB_image = "&nbsp;";}
				$TABtext = " ";
				echo "<tr><td>$GB_image </td><td ALIGN=CENTER> $amount </td><td>$name<br>$nameraw ($type)</td><td>$code</td><td>";
				echo " &nbsp;</td><td>&nbsp;</td></tr>";
			} //if amount 0
		}
	}
	echo '</table>';
	echo '<br><br><br><br>';

}


//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "pets";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Pets</h2><p>';
	echo '<b>Information on Pets.</b><br>';
	echo 'You have to enable "Pet Feed" to see information here.<br>';
	echo "<font color='red'>Remember that you have to feed the Puppy or Dog the first time your selfs.</font><br>";
	echo '<br>';
	$GBSQL ="SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'className' AND _val = 'Pet')";
	$query = $GBC->_GBUser->query($GBSQL) ;
	while ($PetObj = $query->fetch(SQLITE_ASSOC))
	{
		if($PetObj['_set'] == 'petName')
		{
			echo '<table class="sofT" cellspacing="0">';
			echo '<tr><td class="helpHed">Name</td><td class="helpHed">'.$PetObj['_val'].'</td></tr>';
		}
		if($PetObj['_set'] == 'petLevel'){ echo '<tr><td>Level</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'lastFedTime'){ echo '<tr><td>lastFedTime</td><td>'.nicetime($PetObj['_val']/1000).'</td></tr>'; }
		if($PetObj['_set'] == 'lastRunaway'){ echo '<tr><td>lastRunaway</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'kibbleFedCount'){ echo '<tr><td>kibbleFedCount</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'isRunAway'){ echo '<tr><td>isRunAway</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'levelStartTime'){ echo '<tr><td>levelStartTime</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'wasLevelResetOnLoad'){ echo '<tr><td>wasLevelResetOnLoad</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'plantTime'){ echo '<tr><td>plantTime</td><td>'.nicetime($PetObj['_val']/1000).'</td></tr>'; }
		if($PetObj['_set'] == 'state'){ echo '<tr><td>state</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'itemName'){ echo '<tr><td>itemName</td><td>'.$PetObj['_val'].'</td></tr>'; }
		if($PetObj['_set'] == 'id'){ echo '<tr><td>id</td><td>'.$PetObj['_val'].'</td></tr>';  echo '</table>';}

	}


	$GB_Pets = array();   // from here remove
	foreach ($GB_Pets as $ObjID)
	{

		$FeedTime = $GB_Pet['lastFedTime'] / 1000;
		echo '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name</td><td class="helpHed">'.$GB_Pet['petName'].'</td></tr>';
		echo '<tr><td>Pet last Fed Time</td><td>'.nicetime($GB_Pet['lastFedTime']/1000).'</td></tr>';
		if($GB_Pet['isRunAway'] ==1) {$isRunAwayText = "<font color='red'>Pet is run away. Goto farmville to get it back!</font>";}else{$isRunAwayText = " ";}
		echo '<tr><td>Is Runaway?</td><td>'.$isRunAwayText.'</td></tr>';
		if($GB_Pet['lastRunaway'] > 0){$LastRunaway = @date("Y-M-d H:i:s", ($GB_Pet['lastRunaway']/1000));}else {$LastRunaway = "";}
		echo '<tr><td>Last Run away</td><td>'.$LastRunaway.'</td></tr>';
		echo '<tr><td>Kibble Fed Count</td><td>'.$GB_Pet['kibbleFedCount'].'</td></tr>';
		echo '<tr><td>Born</td><td>'.nicetime($GB_Pet['plantTime']/1000).'</td></tr>';
		echo '<tr><td>Pet Level</td><td>'.$GB_Pet['petLevel'].'</td></tr>';
		echo '<tr><td>Name</td><td>'.$GB_Pet['itemName'].'</td></tr>';
		echo '<tr><td>Real Name</td><td>'.$GB_units[$GB_Pet['itemName']]['realname'].'</td></tr>';
		echo '<tr><td>ID</td><td>'.$GB_Pet['id'].'</td></tr>';
		echo '<tr><td>Next feed</td><td>'.@date("Y-M-d H:i:s", ($GB_Pet['lastFedTime']/1000)+86401 ).'</td></tr>';
		echo '</table><br>';
	}
	echo '<br>';
}

//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_all";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>All</h2>';
	echo 'Here you find all items, use the filter options. (Enter in some filter text) <br>';
	echo '<table id="tabAll" class="mytable" cellspacing="0" cellpadding="0">';
	echo '<tr><th>Image</th><th>Action</th><th>Name</th><th>Code</th><th>Type</th><th>Tab</th></tr>';


	$GBSQL = "SELECT * FROM units ";
	$query = $GBC->_GBMain->query($GBSQL);
	$unit = $query->fetchAll();                          // retrieve them from DB
	
	foreach($unit as $value)
	{
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		$GB_image = '-';
		echo '<tr><td>'.$GB_image.'</td><td>'.GB_input2($value, $action, "button").'</td>';
		echo '<td>'. $GBC->GB_get_friendlyName($value['_name'])  . ' <br><i>[' . $value['_name'] .'] </i></td>';
		echo '<td>'. $value['_code'] .'</td><td>'.$value['_type'].'</td>';
		echo '<td>'. $value['_display'].'</tr>';
	}
	echo '</table>';
	echo '<br>';
	echo '<br><br><br><br>';

	echo '<script language="javascript" type="text/javascript">
    var props = {   
        col_0: "none",
        col_1: "none",
        col_4: "select",
        col_5: "select",
        highlight_keywords: true,
        on_keyup: true,
        on_keyup_delay: 1200
    }
    setFilterGrid("tabAll",props);
          </script> ';


}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_animal";
$MenuName = "Animal";        $FromName = "animal";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _type = '".$FromName."' AND _code !='XX'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_bushel";
$MenuName = "Bushel";        $FromName = "bushel";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo ' <br>';
	echo ' <br>';
	echo 'The GiftBox manager does nothing with Bushels.   <br>';
	echo ' but if the bushel is on selling, Steal will not collect them anymore. (when correct configured).<br>';
	echo ' This way you will steal only those bushel that you need.<br>';
	echo ' note that you need a recent version of steal for this.<br>';
	echo ' <br>';
	echo ' When you like to change your crafting, please us "uncheck all", press save. then sellect your new crafting.<br>';
	echo ' <br>';
	echo '<form method="post">';
	echo '<input type="hidden" name="url" value="place_bushel" /> ';
	echo '<input type="submit" value="I have a bakery" />';
	echo '<input type="hidden" name="bushel" value="bakery" /> ';
	echo '</form>';

	echo '<form method="post">';
	echo '<input type="hidden" name="url" value="place_bushel" /> ';
	echo '<input type="submit" value="I have a spa" />';
	echo '<input type="hidden" name="bushel" value="spa" /> ';
	echo '</form>';

	echo '<form method="post">';
	echo '<input type="hidden" name="url" value="place_bushel" /> ';
	echo '<input type="submit" value="I have a winery" />';
	echo '<input type="hidden" name="bushel" value="winery" /> ';
	echo '</form>';
	echo ' <br>';
	echo ' <br>';
	echo ' <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _type = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_build";
$MenuName = "Building";        $FromName = "building";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _type = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_Deco_Small";
$MenuName = "Small decoration";        $FromName = "Deco_Small";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_Deco_2";
$MenuName = "Decoration list 2";        $FromName = "Deco_2";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll() ;                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_Specials";
$MenuName = "Special decoration for Special buildings";        $FromName = "Specials";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_Collections";
$MenuName = "Collections";        $FromName = "Collections";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_Uncommon";
$MenuName = "Uncommon decorations";        $FromName = "Uncommon";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_deco_rest";
$MenuName = "Decorations all the rest";        $FromName = "Deco_rest";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _display = '".$FromName."'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_tree";
$MenuName = "All Trees";        $FromName = "tree";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'What action is needed for these items?  Do not forget to press "Save changes". <br>';
	echo GB_TabTable1($FromName, $GB_url);
	$GBSQL = "SELECT * FROM units WHERE _type = '".$FromName."' AND _placeable = 'true'";     // Get all units for this screen
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "place_consume";
$MenuName = "Consumable";        $FromName = "consumable";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>'.$MenuName.'</h2>';
	echo 'Which consumable you want to consume or sell from the giftbox? Do not forget to press "Save changes".<br />';
	echo "<font color='red'><i>Some consumebles can not be used, like the Arborists and Farmhands. These can only be sold.</i></font><br />";
	echo GB_TabTable1($FromName, $GB_url);

	$GBSQL = "SELECT * FROM units WHERE _type = 'consumable'";
	$query = $GBC->_GBMain->query($GBSQL);
	$unit_keys = $query->fetchAll();                          // retrieve them from DB
	$GBGetTemp = array();                                             // prevent dubble codes
	foreach($unit_keys as $value)
	{                                                                // Get unit details
		$GBSQL = "SELECT * FROM action  WHERE _code = '".$value['_code']."'";
		$query = $GBC->_GBUser->query($GBSQL);
		$action = $query->fetchAll();                    // Get the action for this item
		if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($value);}else{$GB_image = "&nbsp;";}
		echo "<tr><td>". $GB_image . "&nbsp;</td>";                    // Print the screens / lines
		echo  "<td>". $GBC->GB_get_friendlyName($value['_name']) . " <br><i>[" . $value['_name'] ."] </i></td>";
		echo  "<td>". GB_input2($value, $action, "radio")." </td><td>". $value['_code']. "</td></tr>";
	}                                                                // end foreach unit
	echo GB_TabTable2($FromName);
	echo "<br>";
	echo "<br>";
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "collection";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Collection</h2><p>';
	echo '<b>Collection information:</b><br> Note that this information only updates at the begin of a cycle. So it is alway 1 cycle behind.';
	echo '<br>';
	echo '<table class="sofT" cellspacing="0">';
	echo "<tr>";
	$GBccount = GB_LoadCcount();
	$GB_CollectionList = GB_GetCollectionList();
	if (!$GB_CollectionList) { echo $GB_file_error . "<br><br>(Collection list file missing)"; return; }

	foreach($GB_CollectionList as $value)
	{
		echo '<td class="helpLeft">'. $value['name']. "</td>";
		$GB_amount_Coll = count($value['collectable']);
		$i=0;
		while($i < $GB_amount_Coll)
		{
			$unit = Units_GetUnitByCode($value['collectable'][$i]);
			$Amount_in_Collection = GB_GetColInfo($value['collectable'][$i], $GBccount);
			$BG_color = " class=\"lime\"";
			if($Amount_in_Collection == "10") $BG_color = " class=\"lgreen\"";
			if($Amount_in_Collection == "0") $BG_color = " class=\"ired\"";
			if($images == 1 && $GBC->GB_Setting['ShowImageAll']){ $GB_image = GB_ShowIMG($unit);}else{$GB_image = "&nbsp;";}
			echo "<td $BG_color > $GB_image <br> $Amount_in_Collection ". $unit['realname'] . " </td>";
			$i++;
		}
		echo "</tr>";
	}
	echo '</table>';
	echo '<br><br><br><br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "statistic";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Statistic</h2><p>';
	echo '<b>Statistic information:</b><br>';
	echo 'Today<br>';

	$today = @date("Y-M-d");
	$GBSQL = "SELECT * FROM stats WHERE _date = '$today' " ;
	$result = $GBC->_GBUser->query($GBSQL);
	if ($result->numRows() > 0)
	{
		$GB_result = $result->fetchAll();
		echo '<table class="sofT" cellspacing="0">';
		echo "<tr>";
		echo '<td class="helpHed">Name:</td>';
		echo '<td class="helpHed">Code</td>';
		echo '<td class="helpHed">Total</td>';
		echo '<td class="helpHed">Action</td>';
		echo '<td class="helpHed">Date</td>';
		echo "</tr>";
		foreach($GB_result as $stat)
		{
			echo "<tr>";
			echo "<td>". $stat['_name']. "</td>";
			echo "<td>". $stat['_code']. "</td>";
			echo "<td>". $stat['_number']. "</td>";
			echo "<td>". $stat['_action']. "</td>";
			echo "<td>". $stat['_date']. "</td>";
			echo "</tr>";
		}
	}
	else
	{
		echo 'There is no Statistic to be shown yet<br>';
	}
	echo '</table>';
	echo '<br>';

	echo 'Yesterday<br>';
	$yesterday = @date("Y-M-d", mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));
	//$today = @date("Y-M-d");
	$GBSQL = "SELECT * FROM stats WHERE _date = '$yesterday' " ;
	$result = $GBC->_GBUser->query($GBSQL);
	if ($result->numRows() > 0)
	{
		$GB_result = $result->fetchAll();
		echo '<table class="sofT" cellspacing="0">';
		echo "<tr>";
		echo '<td class="helpHed">Name:</td>';
		echo '<td class="helpHed">Code</td>';
		echo '<td class="helpHed">Total</td>';
		echo '<td class="helpHed">Action</td>';
		echo '<td class="helpHed">Date</td>';
		echo "</tr>";
		foreach($GB_result as $stat)
		{
			echo "<tr>";
			echo "<td>". $stat['_name']. "</td>";
			echo "<td>". $stat['_code']. "</td>";
			echo "<td>". $stat['_number']. "</td>";
			echo "<td>". $stat['_action']. "</td>";
			echo "<td>". $stat['_date']. "</td>";
			echo "</tr>";
		}
	}
	else
	{
		echo 'There is no Statistic to be shown yet<br>';
	}
	echo '</table>';
	echo '<br>';


	echo '<br><br><br><br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "debug";
//   ***
//   ***************************************************************************

if($GB_url == $GB_current_url)
{
	echo '<h2>Debug</h2>';
	echo '<br>';
	echo "<br><b>Link to gameSettings.xml:</b><br>";
	echo 'flashRevision: ' . $GBC->GB_Setting['flashversion'] . '<br>';
	echo 'Download here: <A HREF="http://static-facebook.farmville.com/v' . $GBC->GB_Setting['flashversion'] . '/gameSettings.xml.gz" target="_blank">>XML</A>  <br>';
	echo '<br>';
	echo '<hr><br>';
	echo '<b>GD for images information:</b><br>';
	if (extension_loaded('gd') && function_exists('gd_info'))
	{
		echo "It looks like GD is installed<br>";
	} else {echo "It looks like GD is NOT installed<br> Follow the instructions in the README file";}
	echo '<hr><br>';
	echo "<b>Create missing database tables:</b><br>";
	echo "<i>Pause the BOT, than click the button below. <br>Check in the log 2 screen till you see the message \"Create new database done \" <br> this can take several minutes.</i><br>";
	echo "<i>Only use this when you have serious problems, there will be lot's of errors for each table that already exists.</i><br>";
	echo "<br>";
	echo '<form method="post">';
	echo '<input type="submit" value="Fix Tables" />';
	echo '<input type="hidden" name="CreateTable" value="CreateTable" /> ';
	echo '<input type="hidden" name="url" value="debug" /> ';
	echo '</form></br>';
	echo '<br>';
	if (isset($_POST['CreateTable']))
	{
		AddLog2('GB =======================================.');
		AddLog2('GB = Create new database on user request =.');
		AddLog2('GB =======================================.');
		echo '<br>';
		echo 'Create new main database.<br>';
		$GBC->GBDBmain_create();
		if( $_SESSION['userId'] == "") {  echo 'User ID unknown<br>'; }
		echo 'Create new user settings<br>';
		$GBC->GBDBuser_create($_SESSION['userId']);
		AddLog2('GB =======================================.');
		AddLog2('GB = Create new database done            =.');
		AddLog2('GB =======================================.');
	}

	echo '<hr><br>';
	echo 'Check if database tables exist.<br>';
	echo '<table class="sofT" cellspacing="0">';
	echo "<tr>";
	echo '<td class="helpHed">Type:</td>';
	echo '<td class="helpHed">Table name</td>';
	echo '<td class="helpHed">exist?</td>';
	echo "</tr>";
	$GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' "; //AND name = 'units'" ;
	$result = $GBC->_GBMain->query($GBSQL);
	if ($result->numRows() == 0)
	{ echo '<font color="red" size="5">Main database NOT found. Something serious happend to your plugin.</font><br>';}
	else
	{
		$GB_SQLTables = $result->fetchAll();
		print_r(@$GB_results);
		$GB_TablesMains = array('gamesettings', 'locale', 'StorageConfig', 'unitbuilding', 'units');
		foreach($GB_TablesMains as $GB_TablesMain)
		{
			$found = 'N';
			foreach($GB_SQLTables as $GB_SQLTable){ if($GB_SQLTable['name'] == $GB_TablesMain){$found = 'Y';}}
			if($found == 'Y'){echo '<tr><td>Main DB</td><td>'.$GB_TablesMain.'</td><td> found</td><tr>';}
			if($found != 'Y'){echo '<tr><td>Main DB</td><td>'.$GB_TablesMain.'</td><td> <font color="red" size="5">Not found</font><br>you have a serious problem in your database.<br>Make backup of all files in the GiftBox folder, <br>Pause the BOT, than try to fix this by clicking the "Fix Table" button above.</td><tr>';}
			//if($found != 'Y'){echo 'Main Table: \t'.$GB_TablesMain.'<font color="red" size="5">Not found</font>, you have a serious problem in your database.<br>';}
		}
	}
	$GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' "; //AND name = 'units'" ;
	$result = $GBC->_GBUser->query($GBSQL);
	if ($result->numRows() == 0)
	{ echo '<font color="red" size="5">User database NOT found. Something serious happend to your plugin.</font><br>';}
	else
	{
		$GB_SQLTables = $result->fetchAll();
		print_r(@$GB_results);
		$GB_TablesUsers = array('action','BuildingParts','gamesettings','giftbox','locations','objects','stats','totstorage');
		foreach($GB_TablesUsers as $GB_TablesUser)
		{
			$found = 'N';
			foreach($GB_SQLTables as $GB_SQLTable){ if($GB_SQLTable['name'] == $GB_TablesUser){$found = 'Y';}}
			if($found == 'Y'){echo '<tr><td>User DB</td><td>'.$GB_TablesUser.'</td><td> found</td><tr>';}
			if($found != 'Y'){echo '<tr><td>User DB</td><td>'.$GB_TablesUser.'</td><td> <font color="red" size="5">Not found</font><br>you have a serious problem in your database.<br>Make backup of all files in the GiftBox folder, <br>Pause the BOT, than try to fix this by clicking the "Fix Table" button above.</td><tr>';}
			//if($found != 'Y'){echo 'Main Table: \t'.$GB_TablesMain.'<font color="red" size="5">Not found</font>, you have a serious problem in your database.<br>';}
		}
	}
	echo '</table>';
	echo '<hr><br>';
	echo 'Default Settings<br>';
	$GB_Sets = file($_SESSION['base_path'] . 'plugins/GiftBox/general_settings.txt');

	if($GB_Sets)
	{
		echo '<br>';
		echo 'Default settings file found.<br>';
		foreach($GB_Sets as $GB_Set)
		{
			$GB_TSet = explode(':', $GB_Set);
			if (strpos($GB_TSet['0'], '#') !== false)
			{
				$comment = $GB_TSet['0'] ;
				//echo 'Comment: ' .$comment . "<br>";
			} else {
				$GB_settVar = $GB_TSet['0'] ;
				$GB_settVal = $GB_TSet['1'] ;
				$GBSQL ="INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES('".$GB_settVar."','".$GB_settVal."')";
				echo 'Setting : ' . $GB_settVar." = ".$GB_settVal. '<br>' ;
			}
		}
	}
	echo '<br>  ';
	echo '<hr><br>';
	echo '<br>';
	$DebugTimer .= GB_loadtime2($GB_starttime, "timer");
	echo $DebugTimer . '<br><br><br>';
	echo '<br><br><br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "image";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Image</h2><p>';
	echo '<b>Images:</b><br>
The parser will download the images at the end of the cycle.<br>
On this page you can display all the images.<br>
Give it some time. <br>
<br>';
	echo '<form method="post">';
	echo '<input type="submit" value="Show all images Now" />';
	echo '<input type="hidden" name="ShowNow" value="ShowNow" /> ';
	echo '<input type="hidden" name="url" value="image" /> ';
	echo '</form></br><div class="icon">';
	echo '<br>';
	echo '<br>';


	if (isset($_POST['ShowNow']))
	{
		echo 'showing now:<br><br><br><br>';

		$GBSQL = "SELECT iconurl,_name FROM units WHERE iconurl IS NOT NULL ";
		$query = $GBC->_GBMain->query($GBSQL);
		$x_units = $query->fetchAll();                          // retrieve them from DB

		//$x_units = array();
		//$x_units = GB_GetUnitList();
		foreach($x_units as $value)
		{ //echo " = " .$value['iconurl'];
			echo GB_ShowIMG($value);
		}
	} //end show now.
	echo '<br><br><br><br>';
}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_ImportNow";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Import actions</h2><p>';
	echo 'Here you can load the actions from file<br>';
	echo '<b> Make sure you have paused the bot !! </b><br>';
	echo '<hr>';
	//echo '<br>';
	if($file == '')
	{  echo 'Sellect a file to be imported in actions.<br>You will see 2nd screen before import realy happens.';
	echo '<form enctype="multipart/form-data" method="post">';
	//echo '<input type="hidden" name="MAX_FILE_SIZE" value="100000" />';
	echo 'Choose a file to import: <input name="ImportFile" type="file" /><br />';
	echo '<input type="hidden" name="url" value="settings_ImportNow" /> ';
	echo '<input type="hidden" name="ImportNow" value="stage1" /> ';
	echo '<input type="submit" value="Show action in this file" />';
	echo '</form>';
	}
	else
	{
		echo '<br>';
		echo 'Below the actions & errors in the file (if any)<br> File name: ' . $file;
		echo '<table width="90%" class="sofT" cellspacing="0">';
		echo '<tr><td class="helpHed">Code</td><td class="helpHed">Item Name:</td><td class="helpHed">Place?</td><td class="helpHed">Sell?</td><td class="helpHed">Keep?</td><td class="helpHed">Consume?</td><td class="helpHed">Error:</td><td class="helpHed">Notes:</td><td class="helpHed">Action Status</td></tr>';
		echo $GBC->GB_import_action('SHOW', $file);
		echo '</table>';
		echo '<br>';
		echo '<hr>';
		echo '<form method="post">';
		echo '<input type="submit" value="Import these actions now" />';
		echo '<input type="hidden" name="url" value="settings_ImportNow" /> ';
		echo '<input type="hidden" name="ImportFile2" value="'.$file.'" /> ';
		echo '<input type="hidden" name="ImportNow" value="stage2" /> ';
		echo '</form>';
		echo '<br>';
		echo '<br>';
		echo 'Or Sellect an other file.<br>';
		echo '<form enctype="multipart/form-data" method="post">';
		//echo '<input type="hidden" name="MAX_FILE_SIZE" value="100000" />';
		echo 'Choose a file to import: <input name="ImportFile" type="file" /><br />';
		echo '<input type="hidden" name="url" value="settings_ImportNow" /> ';
		echo '<input type="hidden" name="ImportNow" value="stage2" /> ';
		echo '<input type="submit" value="Show action in this file" />';
		echo '</form>';
	}
	echo '<br>';

}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "settings_ExportNow";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>Export actions</h2><p>';
	echo 'Here you can export the action you have currently sellected to a file.<br>';
	echo 'Default location for these files are: '.$_SESSION['this_plugin']['folder'].'/actions/<br>';
	echo '<hr>';
	//echo '<br>';
	echo  $GBC->GB_export_action('SHOW', '' );
	echo '<br>';
	echo '<hr>';
	$today = @date("Y-M-d");
	echo 'Give the filename where to save the actions.<br>';
	echo '<form method="post">';
	echo '<input type="text" name="FileName"  size="50" maxlength="50"  value="'.$_SESSION['this_plugin']['folder'].'/actions/actions_export_'.$today.'.txt" ><br>';
	echo '<input type="submit" value="Export actions to file now" />';
	echo '<input type="hidden" name="ExportNow" value="ExportNow" /> ';
	echo '</form>';
	echo '<br>';
	echo '<br>';
	echo '<br>';

}
//   ***************************************************************************
//   ***   Menu Screen
$GB_current_url = "help";
//   ***
//   ***************************************************************************
if($GB_url == $GB_current_url)
{
	echo '<h2>FAQ & Help</h2><p>';
	echo 'Here you can find the help on the GiftBox Manager.<br>';
	echo '<a href="'. GiftBox_HelpURL .'help">Jump to HELP</a>';
	echo '<a href="'. GiftBox_HelpURL .'ChangeLog">Jump to the Change log</a>';
	echo '<hr>';
	echo '<br>';
	$bb_replace      = array ('[b]','[/b]','[i]','[/i]', '[size=18]', '[/size]' );
	$bb_replacements = array ('<b>','</b>','<i>','</i>', '<span style="font-size: 18px; line-height: normal">', '</span>' );

	$GB_faqs = file($_SESSION['base_path'] . 'plugins/GiftBox/faq.txt');

	foreach ($GB_faqs as $GB_faq)
	{
		$string = str_replace($bb_replace,$bb_replacements,$GB_faq);
		echo $string . "<br>";
	}

	echo '<hr>';
	echo '<br>';
	echo '<br>';

}

// ************************************** footer *****************************
echo '<br>';
echo '</body>';
echo '</html>';
unset($GBC);
// format the input butons.
// $what = checkbox of button
// function GB_input2($code, $type, $unit, $action, $what)
function GB_input2($unit, $action, $what)
{
 global $GBGetTemp;

  $sell = "NO";
  $place = "NO";
  $sizeX = "0";
  $sizeY = "0";
  if(array_key_exists('_code',$unit))
    {
      $code = $unit['_code'];
      @$sizeX = $unit['_sizeX'];
      @$sizeY = $unit['_sizeY'];
      $type = $unit['_type'];
    }
    else
    {
      $code = $unit['code'];
      @$sizeX = $unit['sizeX'];
      @$sizeY = $unit['sizeY'];
      $type = $unit['type'];
    }
  // check for double code.
  $GBGetTempCODE = strtoupper($code);
  if(array_key_exists($GBGetTempCODE,$GBGetTemp))
    {
     $GBGetTemp[$GBGetTempCODE] = $GBGetTemp[$GBGetTempCODE] + 1;
     $GBC = $GBGetTemp[$GBGetTempCODE];
    }
    else
    {
     $GBGetTemp[$GBGetTempCODE] = 0;
     $GBC = '';
    }

  $ActionN = "CHECKED";
  $ActionS = "";
  $ActionP = "";
  $ActionC = "";
  $keep = 0;
  $consume = 'N';
  //set variable good.
  if($sizeX == ""){$sizeX = "0";}
  if($sizeY == ""){$sizeY = "0";}
  // can we place this item?
  $extraText = "";
  if($type == 'tree' ){$sizeX = "1";  $sizeY = "1";}
  if($sizeX == "1" && $sizeY == "1" ){$placeable = "Y";}else{$placeable = "N";}
  if (!empty($action))
   {
     if($action['0']['_keep'] != "0")             {$keep = $action['0']['_keep']; }  else {$keep = 0;}
     if($action['0']['_place_on_farm'] != "0" )   {$place = "already"; $ActionP = "CHECKED"; $ActionN = "";} else {$place = "show"; }
     if($action['0']['_construction'] != "0")     {$extraText .= " Used for construction"; $placeable = "N";}
     if($action['0']['_consume'] != "0")          {$consume = 'Y'; $placeable = "N"; $ActionC = "CHECKED"; $ActionN = "";}
     if($action['0']['_place_in_build'] != "0")   {$extraText .= " Will be placed in building"; $placeable = "N";}
     if($action['0']['_place_in_special'] != "0") {$extraText .= " Will be used for special"; $placeable = "N";}
     if($action['0']['_selling'] != "0")          {$sell = "already"; $ActionS = "CHECKED"; $ActionN = "";}  else {$sell = "show";}
     if($action['0']['_collection'] != "0")       {$collection = "Y"; $placeable = "N";} else {$collection = "N";}
   }
   else
   {
     // no action yet.
     if($placeable == "Y") {$place = "show"; } else {$place = "no";}
     $sell = "show";
     $collection = "N";
   }
//format the input string.
$input ="&nbsp;";
     $GB_SellButon = 'No action availible from this screen.<br>';
     $GB_SellCheck = 'No action availible from this screen.<br>';
     $GB_Radio = 'Not from here<br>';
     if($sell == "show")
       { // need to show sell buton
        $GB_SellButon  = '<form method="post">';
        $GB_SellButon .= '<input type="submit" value="Add to selling" class="button"/>';
        $GB_SellButon .= '<input type="hidden" name="add_sell_2" value="' . $code . '"/>';
        $GB_SellButon .= '</form>';
        $GB_SellCheck  = 'Sell:<input type="checkbox" name="S" value="' . $code . '" />';
        $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" CHECKED/>';
        $GB_Radio .= ' Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" /> ';
       }
     if($sell == "already")
       { // already on selling list
         $GB_SellButon  = '<form method="post">';
         $GB_SellButon .= '<input type="submit" value="Remove from selling" class="button"/>';
         $GB_SellButon .= '<input type="hidden" name="rem_sell_2" value="' . $code . '"/>';
         $GB_SellButon .= '</form>';
         $GB_SellCheck = 'Sell:<input type="checkbox" name="S" value="' . $code . '" CHECKED/>';
         $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" />';
         $GB_Radio .= ' Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" CHECKED/> ';
       }

     $GB_PlaceButon = '&nbsp;';
     $GB_ConsumButon = '&nbsp;';
     $GB_PlaceCheck = '&nbsp;';
     $GB_PlaceRadio = '&nbsp;';
     if($place == "already")
         {
           $GB_PlaceButon  = '<form method="post">';
           $GB_PlaceButon .= '<input type="submit" value="Remove from Place" class="button"/>';
           $GB_PlaceButon .= '<input type="hidden" name="rem_place_2" value="' . $code . '"/>';
           $GB_PlaceButon .= '</form>';
           $GB_PlaceCheck = 'Place:<input type="checkbox" name="P" value="' . $code . '" CHECKED/>';
           $GB_Radio .= ' Place<input type="radio" name="' . $code . '' . $GBC . '" value="P" CHECKED/>';
         }
     if($placeable == "N" ) { $GB_PlaceButon = ' '; $GB_PlaceCheck = ' '; }
     if($placeable == "Y" )
       {
          if($place == "show")
              { // Placeable && need to show
                $GB_PlaceButon  = '<form method="post">';
                $GB_PlaceButon .= '<input type="submit" value="Add to Place" class="button"/>';
                $GB_PlaceButon .= '<input type="hidden" name="add_place_2" value="' . $code . '"/>';
                $GB_PlaceButon .= '</form>';
                $GB_PlaceCheck  = 'Place:<input type="checkbox" name="P" value="' . $code . '"/>';
                $GB_Radio .= ' Place<input type="radio" name="' . $code . '' . $GBC . '" value="P"/>';
              }
       }
     if($type == "consumable")
       { //$ActionC = "CHECKED";
         if($consume == 'N')
           {
             $GB_ConsumButon  = '<form method="post">';
             $GB_ConsumButon .= '<input type="submit" value="Add to consume" class="button"/>';
             $GB_ConsumButon .= '<input type="hidden" name="add_consume_2" value="' . $code . '"/>';
             $GB_ConsumButon .= '</form>';
             $extraText .= " Can be consumed.";
           }
           else
           {
             $GB_ConsumButon  = '<form method="post">';
             $GB_ConsumButon .= '<input type="submit" value="Do NOT consume" class="button"/>';
             $GB_ConsumButon .= '<input type="hidden" name="add_consume_2" value="' . $code . '"/>';
             $GB_ConsumButon .= '</form>';
             $extraText .= " Will be consumed.";
           }
             $GB_ConsumButon .= '<form method="post">';
             $GB_ConsumButon .= 'Keep:<input type="text" size="2" maxlength="2"  name="KEEPconsume" value="'.$keep.'" /> ';
             $GB_ConsumButon .= '<input type="hidden" name="KEEPcode" value="' . $code . '"/>';
             $GB_ConsumButon .= '<input type="submit" value="Change keep" class="button"/>';
             $GB_ConsumButon .= '</form>';

         $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" '.$ActionN.' />';
         $GB_Radio .= ' | Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" '.$ActionS.' /> ';
         $GB_Radio .= ' | Consume<input type="radio" name="' . $code . '' . $GBC . '" value="C" '.$ActionC.' /> ';
         $GB_Radio .= ' | Keep:<input type="text" size="2" maxlength="2"  name="' . $code . 'KEEP' . $GBC . '" value="'.$keep.'" /> ';
       }
     if($collection == "Y" )
       {
         $extraText .= " Collectable " ;
       }
    // now for all.
    $extraText .= " Keep " . $keep;

  if($what == "button")   { $input =  $GB_SellButon ." ".$GB_PlaceButon. " ".$extraText; }
  if($what == "checkbox") { $input =  $GB_SellCheck . " ".$GB_PlaceCheck ." ".$extraText; }
  if($what == "radio")    { $input =  $GB_Radio ; }

return $input;
}


//================================================================================
// popup action function
//================================================================================
function GB_popupcontent1($unit, $action, $what)
{
 global $GBGetTemp;

  $sell = "NO";          $place = "NO";         $sizeX = "0";          $sizeY = "0";
  if(array_key_exists('code',$unit))
    {
      $code = $unit['code'];
      $sizeX = $unit['sizeX'];
      $sizeY = $unit['sizeY'];
      $type = $unit['type'];
    }
    else
    {
      $code = $unit['code'];
      $sizeX = $unit['sizeX'];
      $sizeY = $unit['sizeY'];
      $type = $unit['type'];
    }

  $ActionN = "CHECKED";
  $ActionS = "";
  $ActionP = "";
  $ActionC = "";
  $keep = 0;
  $consume = 'N';
  //set variable good.
  if($sizeX == ""){$sizeX = "0";}
  if($sizeY == ""){$sizeY = "0";}
  // can we place this item?
  $extraText = "";
  if($type == 'tree' ){$sizeX = "1";  $sizeY = "1";}
  if($sizeX == "1" && $sizeY == "1" ){$placeable = "Y";}else{$placeable = "N";}
  $place_in_build = 'N';
  $place_in_special = 'N';
  if (!empty($action))
   {
     if($action['0']['_keep'] != "0")             {$keep = $action['0']['_keep']; }  else {$keep = 0;}
     if($action['0']['_place_on_farm'] == "Y" )   {$place = "already"; $ActionP = "CHECKED"; $ActionN = "";} else {$place = "show"; }
     if($action['0']['_construction'] != "0")     {$extraText .= " Used for construction"; $placeable = "N";}
     if($action['0']['_consume'] == "Y")          {$consume = 'Y'; $placeable = "N"; $ActionC = "CHECKED"; $ActionN = "";}
     if($action['0']['_place_in_build'] != "0")   {$extraText .= " Will be placed in building"; $placeable = "N"; $place_in_build = 'Y';}
     if($action['0']['_place_in_special'] != "0") {$extraText .= " Will be used for special"; $placeable = "N"; $place_in_special = 'Y';}
     if($action['0']['_selling'] == "Y")          {$sell = "already"; $ActionS = "CHECKED"; $ActionN = "";}  else {$sell = "show";}
     if($action['0']['_collection'] != "0")       {$collection = "Y"; $placeable = "N";} else {$collection = "N";}
   }
   else
   {
     // no action yet.
     if($placeable == "Y") {$place = "show"; } else {$place = "no";}
     $sell = "show";
     $collection = "N";
   }
//format the input string.
$input ="&nbsp;";
     $GB_SellButon = 'No action availible from this screen.<br>';
     $GB_SellCheck  = 'Sell:<input type="checkbox" name="S" value="' . $code . '" '.$ActionS.' />'  ;

     $GB_PlaceCheck = '&nbsp;';
     if($place == "already")
         {
           $GB_PlaceCheck = 'Place:<input type="checkbox" name="P" value="' . $code . '" CHECKED/>';
         }
     if($placeable == "N" ) { $GB_PlaceCheck = 'Place: Not posible '; }
     if($placeable == "Y" )
       {
          if($place == "show")
              { // Placeable && need to show
                $GB_PlaceCheck  = 'Place:<input type="checkbox" name="P" value="' . $code . '"/>';
              }
       }

     if($type == "consumable")
       { //$ActionC = "CHECKED";
             $GB_ConsumCheck  = 'Consume:<input type="checkbox" name="C" value="' . $code . ' ' . $ActionC. ' "/>';
       } else {$GB_ConsumCheck  = 'Consume: Not posible';}

     if($collection == "Y" )
       { $GB_Collectable = 'This is a collectable item'; $extraText .= " Collectable " ;
       }else{$GB_Collectable = '';}

     $fuel = '';
     if($type == 'fuel' ){if($GBC->GB_Setting['DoFuel']){ $fuel = 'This is fuel. Fuel is enabled';}else{ $fuel = 'This is fuel. Fuel is disabled';}}

$input .=  '<form method="post">';
$input .=  '<input type="hidden" name="popup" value="' . $code . '" /> ';     //
$input .=  $GB_SellCheck   . "<br>" ;
$input .=  $GB_PlaceCheck  . "<br>" ;
$input .=  $GB_ConsumCheck . '<br>';
$input .=  $GB_Collectable . '<br>';
if($place_in_build == 'Y'){$input .='Remove from place in building:<input type="checkbox" name="RemPlaceInBuild" value="' . $code . '"/>';}
if($place_in_special == 'Y'){$input .='Remove from place in Special:<input type="checkbox" name="RemPlaceInSpecial" value="' . $code . '"/>';}
$input .=  $fuel . '<br>';
$input .= 'Keep in GiftBox:<input type="text" size="2" maxlength="2"  name="KEEP" value="'.$keep.'" /><br> ';
$input .= '<input type="submit" value="Change settings" class="button"/>';
$input .=  '';
$input .=  '</form>';
return $input;
}

// building the tab tables.
function GB_SmartFilter($i)
{
  global $GBox_Settings;
  $echo  = '<form name="Filter'.$i.'" method="post">
            Enable smart filtering <input name="Filter" type="checkbox" '.($GBox_Settings['Filter']?'checked':'').' value="1" onClick="SubmitFilter'.$i.'()">
            </form>';
return $echo;
}

function GB_TabTable1($FromName, $GB_url = 'giftbox')
{
     $echo  = '<table id="'.$FromName.'" class="mytable" cellspacing="0" cellpadding="0">';
     $echo .= '<form name="'.$FromName.'" method="post">';
     $echo .= '<input type="submit" value="Save changes" />';
     $echo .= '<input type="hidden" name="update2" value="'.$FromName.'" /> ';
     $echo .= '<input type="button" name="Check_All" value="Check All Place" onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'P\')">';
     $echo .= '<input type="button" name="Check_All" value="Check All Sell"  onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'S\')">';
     $echo .= '<input type="button" name="Check_All" value="Uncheck All"     onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'0\')">';
     $echo .= '<input type="hidden" name="url" value="'.$GB_url.'" /> ';
     $echo .= "<tr><th>Image</th><th>Name</th><th>Setting</th><th>Code</th></tr>";
return $echo;
}

function GB_TabTable2($FromName)
{
  $echo  = '</form></table><br>';
  $echo .= '<script language="javascript" type="text/javascript">
    var props = {   
        col_0: "none",
        on_keyup: true,   
        on_keyup_delay: 1200
    }
    setFilterGrid("'.$FromName.'",props);
          </script> ';
return $echo;
}

function GB_TabScript1($FromName)
{
  $echo = '<script language="javascript" type="text/javascript">
    var props'.$FromName.' = {   
        on_keyup: true,
        on_keyup_delay: 1200
    }
    setFilterGrid("'.$FromName.'",props'.$FromName.');
          </script> ';
return $echo;
}

//========================================== FONTS ETC
function GBHead($text)
{
$textformated = '<span style="color:#240B3B; background-color:#A9D0F5; font-weight:bold; font-size:20px ">'. $text ."</span>";
return $textformated;
}

function GB_UrlText($url, $Text)
{
  $GB_OnMouse = '<b><a href="'. GiftBox_TextURL .''.$url.'&userId=' . $_SESSION['userId'] . '" title="Jump to tab"><font color="CC0000">'.$Text.'</font></a></b>';
  return $GB_OnMouse;
}


function GB_HelpText($url, $Help)
{
  $GB_OnMouse = '<i><b><a href="'. GiftBox_HelpURL .''.$url. '" title="Jump to help"><font color="CC0000">'.$Help.'</font></a></b></i>';
  return $GB_OnMouse;
}

function GB_HelpTextbak($Text, $Help)
{
      $GB_OnMouse = '<i><b onmouseover="Tip(\'' . $Text . '\')" onmouseout="UnTip()" ><span style="color:#08088A; background-color:#BDBDBD; font-size:9px "> '.$Help.' </span></b></i>';
  return $GB_OnMouse;
}



function GB_loadtime($GB_starttime, $text)
{
    global $GB_starttime;
    $GB_endtime = microtime();
$GB_endarray = explode(" ", $GB_endtime);
$GB_endtime = $GB_endarray[1] + $GB_endarray[0];
$GB_totaltime = $GB_endtime - $GB_starttime; 
$GB_totaltime = round($GB_totaltime,5);
echo "$text - Load time $GB_totaltime seconds.";
}

function GB_loadtime2($GB_starttime, $text)
{
    global $GB_starttime;
    $GB_endtime = microtime();
$GB_endarray = explode(" ", $GB_endtime);
$GB_endtime = $GB_endarray[1] + $GB_endarray[0];
$GB_totaltime = $GB_endtime - $GB_starttime; 
$GB_totaltime = round($GB_totaltime,5);
return "$text - Load time $GB_totaltime seconds.<br>";
}

function GB_ShowIMGbig($value)
{
	global $images;
	global $GB_ImagePath;
	if (isset($value['iconurl']) && $images == 1)
	{  $x_iconurl = $value['iconurl'];
	$x_iconurl = '/' . $value['iconurl'];
	$GB_image = '<img src="'. $x_iconurl .'" alt="" />';
	return $GB_image;
	} else {$GB_image = "-&nbsp;"; return $GB_image;}
	// file does not exist, show alternative.
	$x_iconurl = 'image/progress.gif';
	$GB_image = '<img  src="'. $x_iconurl .'" alt="" />' ;
	return $GB_image;
}

// =============================================================================
// create the Farm Image
// =============================================================================
function  create_image2()
{
	$GBC = new GBsql();
	$GBC->GB_getSQLsetting();
	$GBSQL = "SELECT * from locations ";
	$result = $GBC->_GBUser->query($GBSQL);
	$locs = $result->fetchAll();
	@list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', fBGetDataStore('playerinfo'));
	if(($FarmSizeX == '') || ($FarmSizeY == '')){ $GB_place_items = "No"; return;}else{ $GB_place_items = "OK";}
	$maxX = $FarmSizeX * 4;
	$maxX = $maxX+3;
	$maxY = $FarmSizeY * 4;
	$maxY = $maxY+3;                        
	$im = @imagecreate($maxX, $maxY) or AddLog2("Cannot Initialize new GD image stream");
	$background_color = imagecolorallocate($im, 255, 255, 255);   // yellow
	$red    = imagecolorallocate($im, 255, 0, 0);                  // red
	$green  = imagecolorallocate($im, 0, 255, 0);
	$blue   = imagecolorallocate($im, 0, 0, 255);                 // blue
	$white  = imagecolorallocate($im, 255, 255, 255);
	$yellow = imagecolorallocate($im, 255, 255, 0);
	$black  = imagecolorallocate($im, 0, 0, 0);
	$purple = ImageColorAllocate($im, 153, 51, 255 );   //purple
	$pink   = ImageColorAllocate($im, 255, 0, 128 );      //pink
	$grey   = ImageColorAllocate($im, 192, 192, 192 );   //grey
	$brown  = ImageColorAllocate($im, 51, 0, 0);
	$loc = "Animal";
	$style = array($white, $white, $white, $blue, $blue, $blue);
	ImageSetStyle($im, $style);
	$X1 = $GBC->GB_Setting[$loc.'X1']*4;
	$Y1 = $maxY - $GBC->GB_Setting[$loc.'Y1']*4;
	$X2 = $GBC->GB_Setting[$loc.'X2']*4;
	$Y2 = $maxY - $GBC->GB_Setting[$loc.'Y2']*4;
	imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, IMG_COLOR_STYLED);
	$loc = "Tree";
	$style = array($white, $white, $white, $yellow, $yellow, $yellow);
	ImageSetStyle($im, $style);
	$X1 = $GBC->GB_Setting[$loc.'X1']*4;
	$Y1 = $maxY - $GBC->GB_Setting[$loc.'Y1']*4;
	$X2 = $GBC->GB_Setting[$loc.'X2']*4;
	$Y2 = $maxY - $GBC->GB_Setting[$loc.'Y2']*4;
	imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, IMG_COLOR_STYLED);
	$loc = "Decoration";
	$style = array($white, $white, $white, $black, $black, $black);
	ImageSetStyle($im, $style);
	$X1 = $GBC->GB_Setting[$loc.'X1']*4;
	$Y1 = $maxY - $GBC->GB_Setting[$loc.'Y1']*4;
	$X2 = $GBC->GB_Setting[$loc.'X2']*4;
	$Y2 = $maxY - $GBC->GB_Setting[$loc.'Y2']*4;
	imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, IMG_COLOR_STYLED);

	foreach($locs as $loc)
	{
		$GB_fill = $red;
		if (strpos($loc['_what'], 'E') !== false) { $GB_fill = $green;}
		if (strpos($loc['_what'], 'Decoration') !== false) { $GB_fill = $black;}
		if (strpos($loc['_what'], 'Animal') !== false) { $GB_fill = $purple;}
		if (strpos($loc['_what'], 'Building') !== false) { $GB_fill = $pink;}
		if (strpos($loc['_what'], 'Plot') !== false) { $GB_fill = $brown;}
		$Map_PXI = $loc['_X']*4 ;
		$Map_PYI = $loc['_Y']*4 ;
		$Map_PYI = $maxY - $Map_PYI ;
		imagefilledrectangle ($im,   $Map_PXI,  $Map_PYI , $Map_PXI+1, $Map_PYI+1, $GB_fill);
	}

	$GB_map_image = $_SESSION['userId']."_FarmMap3.png";
	imagepng($im, $GB_map_image);
	imagedestroy($im);

}
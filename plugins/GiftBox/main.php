<?php
define ( 'GiftBox_version', file_get_contents($_SESSION['base_path'] . 'plugins/GiftBox/GiftBox.ver') );
define ( 'GiftBox_date', '2 Nov 2010' );
define ( 'GiftBox_URL', '/plugins/GiftBox/index.php');
define ( 'GiftBox_HelpURL', '/plugins/GiftBox/index.php?userId=' . $_SESSION['userId'] . '&url=help#');
define ( 'GiftBox_TextURL', '/plugins/GiftBox/index.php?url=');
define ( 'GiftBox_Path', '/plugins/GiftBox/');
// file definitions
define ( 'GBox_SettingList', 'GB_settings.txt' );
define ( 'GBox_SellingList_building', 'GB_selling_building.txt' );
define ( 'GBox_SellingList_animal', 'GB_selling_animal.txt' );
define ( 'GBox_SellingList_decoration', 'GB_selling_decoration.txt' );
define ( 'GBox_SellingList_tree', 'GB_selling_tree.txt' );
define ( 'GBox_SellingList_consume', 'GB_selling_consume.txt' );
define ( 'GBox_SellingList', 'GB_selling.txt' );
define ( 'GBox_Statistics', 'GB_Statistics.txt' );
define ( 'GBox_PlaceList_animal', 'GB_Place_animal.txt' );
define ( 'GBox_PlaceList', 'GB_Place.txt' );
define ( 'GBox_XY_objects', 'GB_XY_objects.txt' );
define ( 'GBox_XY_map', 'GB_XY_map.txt' );
define ( 'GBox_storage', 'GB_StorageInfo.txt' );
/******************GiftBox manager by Christiaan****************************/
define ( 'GBox_DB_main', 'GB_DB_main.sqlite');
define ( 'GBox_DB_user', 'GB_DB.sqlite');
/******************GiftBox manager by Christiaan****************************/
include "helpers/GiftBox_AMF.php";
include "helpers/GiftBox.class.php";
include "helpers/GiftBox_Misc.php";
/******************GiftBox manager by Christiaan****************************/
function GiftBox_init()
{
	$_SESSION['hooks']['after_planting'] = 'giftbox_start';
}
/******************GiftBox manager by Christiaan****************************/
//------------------------------------------------------------------------------
// write to logfile for debugging.
//------------------------------------------------------------------------------
function giftbox_start()
{
	$GLOBALS['GBC'] = new GBsql();
	$GLOBALS['GBC']->Giftbox();
	unset($GLOBALS['GBC']);
}

function giftbox_renew()
{
	$GLOBALS['GBC'] = new GBsql();
	$GLOBALS['GBC']->GB_renew_giftbox_SQL();
	unset($GLOBALS['GBC']);
}
?>


<?php

define('FarmStats_version', file_get_contents($_SESSION['base_path'] . 'plugins/FarmStats/FarmStats.ver'));
define('FarmStats_date', '24 October 2010');
define('FarmStats_URL', '/plugins/FarmStats/main.php');
define('FarmStats_Path', 'plugins/FarmStats/');
// file definitions
define('FarmStats_Main', 'FarmStats_main.sqlite');
define('FarmStats_World', 'FarmStats_world.sqlite');
/******************FarmStats by RadicalLinux****************************/
include 'includes/FarmStats.class.php';

/******************FarmStats by RadicalLinux****************************/

function FarmStats_init()
{
	$_SESSION['hooks']['after_load_settings'] = 'FarmStats_doWork';
	$_SESSION['hooks']['after_work'] = 'FarmStats_Refresh';
	echo "Loading FarmStats V" . FarmStats_version . " by RadicalLinux\r\n";
}
/******************FarmStats by RadicalLinux****************************/

function FarmStats_doWork()
{
	$fvM = new FarmStats();
	if($fvM->error != '')
	{
		unset($fvM);
		AddLog2($fvM->error);
		return;
	}
	unset($fvM);


}

function FarmStats_Refresh()
{
	DoInit();
	$fvM = new FarmStats();
	unset($fvM);
}

?>
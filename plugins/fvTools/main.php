<?php

define('fvTools_version', file_get_contents($_SESSION['base_path'] . 'plugins/fvTools/fvTools.ver'));
define('fvTools_URL', '/plugins/fvTools/index.php');
define('fvTools_Path', 'plugins/fvTools/');
// file definitions
define('fvTools_Main', 'fvTools_main.sqlite');
define('fvTools_World', 'fvTools_world.sqlite');
/******************fvTools by RadicalLinux****************************/
include 'includes/fvTools.class.php';
/******************fvTools by RadicalLinux****************************/

function fvTools_init()
{
	$_SESSION['hooks']['after_planting'] = 'fvTools_doWork';
	//$_SESSION['hooks']['after_work'] = 'fvTools_Refresh';
}
/******************fvTools by RadicalLinux****************************/

function fvTools_doWork()
{
	AddLog2('fvTools: Beginning Work');
	$fvM = new fvTools();
	unset($fvM);
	AddLog2('fvTools: Finished Work');
}

?>
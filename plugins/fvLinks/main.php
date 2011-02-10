<?php

define('fvLinks_Path', 'plugins/fvLinks/');
define('fvLinks_Main', 'fvLinks_main.sqlite');
include 'includes/fvLinks.class.php';
// file definitions
/******************fvLinks by RadicalLinux****************************/

function fvLinks_init()
{
	$_SESSION['hooks']['after_planting'] = 'fvLinks_doWork';

}
/******************fvLinks by RadicalLinux****************************/

function fvLinks_doWork()
{
	$fvL = new fvLinks();
	$fvL->DoWork();
	unset($fvL);	
}
?>
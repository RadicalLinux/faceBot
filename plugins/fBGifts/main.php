<?php

define('fBGifts_version', file_get_contents($_SESSION['base_path'] . 'plugins/fBGifts/fBGifts.ver'));
define('fBGifts_date', '2 November 2010');
define('fBGifts_Path', 'plugins/fbGifts/');
define('fBGifts_Main', 'fbGifts_main.sqlite');
// file definitions
include 'includes/fBGifts.class.php';
/******************fbGifts by RadicalLinux****************************/

function fbGifts_init()
{
	$_SESSION['hooks']['after_planting'] = 'fbGifts_doWork';
	echo "Loading fBGifts V" . fBGifts_version . " by RadicalLinux\r\n";
}
/******************fbGifts by RadicalLinux****************************/

function fBGifts_doWork()
{
	AddLog2('----------fBGifts Start----------');
	$fBG = new fBGifts();
	if($fBG->error != '')
	{
		unset($fBG);
		AddLog2($fBG->error);
		return;
	}
	AddLog2('----------fBGifts Finished----------');
	unset($fBG);

}

?>
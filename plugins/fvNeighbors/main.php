<?php

define('fvNeighbors_version', file_get_contents($_SESSION['base_path']. 'plugins/fvNeighbors/fvNeighbors.ver'));
define('fvNeighbors_date', '24 October 2010');
define('fvNeighbors_Path', 'plugins/fvNeighbors/');
// file definitions
define('fvNeighbors_Main', 'fvNeighbors_main.sqlite');
include 'includes/fvNeighbors.class.php';
/******************fvNeighbors by RadicalLinux****************************/

if (!function_exists('AddRewardLog')) {
	function AddRewardLog($rewardName, $url) {
		$f = fopen(F('Rewards-Day' . @date('z') . '.txt'), "a");
		if ($f) {
			fputs($f, @date("H:i:s") . " \t$rewardName \thttp://apps.facebook.com/onthefarm/$url \r\n\r\n");
			fclose($f);
		}
	}
}

function fvNeighbors_init()
{
	$_SESSION['hooks']['after_planting'] = 'fvNeighbors_doWork';
	echo "Loading fvNeighbors V" . fvNeighbors_version . " by RadicalLinux\r\n";
}
/******************fvNeighbors by RadicalLinux****************************/

function fvNeighbors_doWork()
{
	AddLog2('fvNeighbors initializing');
	$fvM = new fvNeighbors();
	if($fvM->error != '')
	{
		AddLog2($fvM->error);
		unset($fvM);
		return;
	}
	AddLog2('fvNeighbors initialized');
	unset($fvM);

}

function fvNeighbors_Refresh()
{
	$fvM = new fvNeighbors();
	unset($fvM);
}

?>
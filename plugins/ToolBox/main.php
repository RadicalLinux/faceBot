<?php
	define ('ToolBox_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));
	define ('AnimalManager_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));
	define ('FlowerManager_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));
	define ('MysteryGift_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));
	define ('StorageManager_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));
	define ('FarmGold_version', file_get_contents($_SESSION['base_path'] . '/plugins/ToolBox/ToolBox.ver'));

define ('ToolBox_URL', '/plugins/ToolBox/index.php');
define ('ToolBox_path', $_SESSION['base_path'] . 'plugins/ToolBox/');
define ('ToolBox_settings', 'TB_settings.txt');
define ('ToolBox_temp_stats', 'AM_stats_temp.txt');
define ('ToolBox_sections', 'TB_sections.txt');
define ('AnimalMover_settings', 'AnimalMover_info.txt');
define ('AnimalMover_farmGold_IDs', 'AnimalMover_farmGold_IDs.txt');

define ('Section_path', $_SESSION['base_path'] . 'plugins/Sections/');
define ('GiftBox_path', $_SESSION['base_path'] . 'plugins/GiftBox/');


include 'functions.php';
include 'functions_AM.php';

$TB_settings = array();
$TB_settings_place = array();
$AM_settings = array();

function ToolBox_init() {
	global $TB_settings;
	global $TB_settings_place;
	global $AM_settings;
	$_SESSION['hooks']['after_planting'] = 'TB_run';
	$_SESSION['hooks']['before_work'] = 'TB_afterLoading';
}


function TB_run() {
	global $TB_settings;
	global $AM_settings;
	global $TB_settings_place;
	TB_loadAnimalMoverSettings();
	TB_loadSettings();
	TB_loadSections();
	$TB_settings['TB_needReload'] = false;

	$farm = null;
	$farm = TB_run_animalmanager_farmGold($farm);
	$farm = TB_run_animalmanager($farm);
	$farm = TB_run_mysterygift($farm);
	$farm = TB_run_flowermanager($farm);
	$farm = TB_run_storagemanager($farm);

	// reloding farm
	if($TB_settings['TB_needReload']) {
		DoInit();
		$TB_settings['TB_needReload'] = false;
	}
	save_array($TB_settings, ToolBox_settings);
}

function TB_run_mysterygift($getFarm = null) {
	global $TB_settings;
	global $AM_settings;
	//########################################################################
	// mysterygift functions
	//########################################################################
	echo "MysteryGift opener\r\n";
	$farm = $getFarm;
	//gifts
	if($TB_settings['MG_openGiftsFarm'] > 0 || $TB_settings['MG_openGiftsFarmAll']) {
		if(!isset($farm)) {
			AddLog2('ToolBox: Reloding Farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		AddLog2('open mystery gifts from farm');
		$re = MG_openGifts('farm',$farm);
		$TB_settings['MG_openGiftsFarm'] = 0;
	}
	if($TB_settings['MG_openGiftsGiftbox'] > 0 || $TB_settings['MG_openGiftsGiftboxAll']) {
		if(!isset($farm)) {
			AddLog2('TB - reloding farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		AddLog2('open mystery gifts from gift box');
		$re = MG_openGifts('giftbox',$farm);
		$TB_settings['MG_openGiftsGiftbox'] = 0;
	}
	if(is_array($re)) $TB_settings['TB_needReload'] = true;
	if(is_array($re)) $farm = $re;

	//eggs
	if($TB_settings['MG_openEggsFarm'] > 0 || $TB_settings['MG_openEggsFarmAll']) {
		if(!isset($farm)) {
			AddLog2('TB - reloding farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		AddLog2('open mystery eggs from farm');
		$re = MG_openEggs('farm',$farm);
		$TB_settings['MG_openEggsFarm'] = 0;
	}
	if($TB_settings['MG_openEggsGiftbox'] > 0 || $TB_settings['MG_openEggsGiftboxAll']) {
		if(!isset($farm)) {
			AddLog2('TB - reloding farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		AddLog2('open mystery eggs from gift box');
		$re = MG_openEggs('giftbox',$farm);
		$TB_settings['MG_openEggsGiftbox'] = 0;
	}
	if(is_array($re)) $TB_settings['TB_needReload'] = true;
	if(is_array($re)) $farm = $re;

	return $farm;
}

function TB_run_animalmanager($getFarm = null) {
	global $TB_settings;
	global $AM_settings;
	//########################################################################
	// animalmanager functions
	//########################################################################
	echo "AnimalManager\r\n";
	$farm = $getFarm;
	// rotate / move +++++++++++++++++++++++++
	$animals = count(AM_getAnimals($TB_settings['AM_ItemName']));
	$movingAnimals = count(AM_getMovingAnimals($TB_settings['AM_ItemName']));
	$standingAnimals = $animals - $movingAnimals;

	// rotate ++++++++++++++++++++++++++++++++
	if($TB_settings['AM_direction'] <> '-1') {
		if($animals - count(AM_getDirectionAnimals(3, $TB_settings['AM_ItemName'])) > 0) {
			AM_doRotate($TB_settings['AM_ItemName']);
			$TB_settings['TB_needReload'] = true;
		}
	}
	if(!$TB_settings['AM_saveSettings']) $TB_settings['AM_direction'] = '-1';

	// move ++++++++++++++++++++++++++++++++++
	if($TB_settings['AM_move'] <> 'nothing') {
		if($TB_settings['AM_move'] == true) {
			AM_doWalk($TB_settings['AM_ItemName']);
			$TB_settings['TB_needReload'] = true;
		} else {
			AM_doWalk($TB_settings['AM_ItemName']);
			$TB_settings['TB_needReload'] = true;
		}
	}
	if(!$TB_settings['AM_saveSettings']) $TB_settings['AM_move'] = 'nothing';
	if(!$TB_settings['AM_saveSettings']) $TB_settings['AM_ItemName'] = '';

	// animal mover ++++++++++++++++++++++++++
	foreach($AM_settings as $className => $animals) {
		$buildings = AM_loadBuildingObject($className, false);
		foreach($buildings as $building) {
			$buildingID = $building['id'];
			if($TB_settings[$buildingID]['AM_moveTo'] <> 'nothing') {
				if(!isset($farm)) {
					AddLog2('TB - reloding farm');
					DoInit();
					$farm = TB_buildFarmArray();
				}
				$re = AM_animalMover($className, $building, $farm);
				$TB_settings['TB_needReload'] = true;

				foreach($animals as $animal) {
					$TB_settings[$buildingID]['AM_move_' . $animal] = 0;
				}
				$TB_settings[$buildingID]['AM_moveTo'] = 'nothing';
				if(is_array($re)) $farm = $re;
			}
		}
	}
	return $farm;
}

function TB_run_animalmanager_farmGold($getFarm = null) {
	global $TB_settings;
	global $AM_settings;
	echo "FarmGold\r\n";
	//########################################################################
	// animalmanager_farmGold functions
	//########################################################################
	$farm = $getFarm;
	$tmp_skip_fg = false;
	if(AM_farmGold_getIDs() <> 0 && $TB_settings['AM_farmGoldRestore']) {
		if(!isset($farm)) {
			AddLog2('ToolBox: Reloading Farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		$info = AM_farmGold_getIDs();
		$re = AM_farmGold_restore($info, $farm);
		if(is_array($re)) $farm = $re;
		AM_farmGold_deletIDs();

		//reloding farm
		AddLog2('ToolBox: Animals Restored');
		AddLog2('ToolBox: Reloding Farm');
		DoInit();
		$farm = TB_buildFarmArray();
		$TB_settings['TB_needReload'] = false;
	}

	foreach($AM_settings as $className => $animals) {
		if($TB_settings['AM_farmGold_'.$className] > 0 && !$tmp_skip_fg) {

			//=================================================================
			// n0m mod: to skip stables harvesting (if 1 cycle set in FarmGold)
			$harvestBuilding = ($TB_settings['AM_farmGold_'.$className] > 1)?true:false;
			//=================================================================

			$buildings = AM_loadBuildingObject($className, false);
			$tmp = 0;
			while($tmp < $TB_settings['AM_farmGold_'.$className]  && !$tmp_skip_fg) {
				if(!isset($farm)) {
					AddLog2('ToolBox: Reloading Farm');
					DoInit();
					$farm = TB_buildFarmArray();
				}
				foreach($buildings as $building) {
					// n0m: $harvestBuilding param added
					$re = AM_farmGold($className, $building, $farm, $harvestBuilding);

					if(is_array($re)) $farm = $re;
					elseif($re == false) {
						Addlog2('ToolBox: ERROR');
						AddLog2('ToolBox: Reloading Farm');
						DoInit();
						$farm = TB_buildFarmArray();
						$info = AM_farmGold_getIDs();
						$re = AM_farmGold_restore($info, $farm);
						if(is_array($re)) {
							AM_farmGold_deletIDs();
							$farm = $re;
							AddLog2('ToolBox: Animals Restored');
						} elseif($re == false) {
							DoInit();
							AddLog2('error while restoring!');
							AddLog2('skipping other buildings');
							AddLog2('set cycle to 0 for '.$className);
							AddLog2('you may check you farm');
							$tmp_skip_fg = true;
							$TB_settings['AM_farmGold_'.$className] = 0;
						}
					}
				}
				$tmp++;
				//=================================================================
				// n0m mod: output cycle # in FarmGold
				AddLog2("~~~~~ [ $tmp / " . $TB_settings['AM_farmGold_'.$className] . " ] done ~~~~~");
				//=================================================================
			}
			$TB_settings['TB_needReload'] = true;
		}
	}
	return $farm;
}

function TB_run_flowermanager($getFarm = null) {
	global $TB_settings;
	global $AM_settings;
	//########################################################################
	// flowermanager functions
	//########################################################################
	AddLog2("FlowerManager");
	$farm = $getFarm;
	$cdf = count(FM_getDroop());

	if($TB_settings['FM_action'] != 'nothing' && $cdf > 0) {
		AddLog2('FlowerManager: ' . $cdf . ' droopy flowers');
		switch($TB_settings['FM_action']) {
			case 'delet':
				FM_delet();
				break;
			case 'replace':
				FM_replace();
				break;
		}
		$TB_settings['TB_needReload'] = true;
	}

	if(isset($TB_settings['FM_actionP']['place'])) {
		if(count($TB_settings['FM_actionP']['place']) > 0) {
			if(!isset($farm)) {
				AddLog2('TB - reloding farm');
				DoInit();
				$farm = TB_buildFarmArray();
			}
			$re = FM_place($farm);
			if(is_array($re)) $farm = $re;
		}
		$TB_settings['TB_needReload'] = true;
	}
	return $farm;
}

function TB_run_storagemanager($getFarm = null) {
	global $TB_settings;
	global $AM_settings;
	//########################################################################
	// storagemanager functions
	//########################################################################
	echo "StorageManager\r\n";
	$farm = $getFarm;
	if(isset($TB_settings['SM_action']['sell']) || isset($TB_settings['SM_action']['place'])) {
		if(count($TB_settings['SM_action']['sell']) > 0) {
			SM_sell();
		}
		if(count($TB_settings['SM_action']['place']) > 0) {
			if(!isset($farm)) {
				AddLog2('TB - reloding farm');
				DoInit();
				$farm = TB_buildFarmArray();
			}
			$re = SM_place($farm);
			if(is_array($re)) $farm = $re;
		}
		$TB_settings['TB_needReload'] = true;
	}
	return $farm;
}

function TB_afterLoading() {
	TB_renewShed();
	AM_createAnimalMoverData();
}

function TB_createInfoTxt() {
	$save_str = "ToolBox\r\n";
	$save_str = $save_str . ToolBox_version . "\r\n";
	$save_str = $save_str . "Hypothalamus\r\n";
	$save_str = $save_str . "http://farmvillebot.net/forum/viewtopic.php?t=3441\r\n";
	$save_str = $save_str . "This plugin offers tools which help you in your everyday farm-life :) \r\n";
	$save_str = $save_str . "Perfect to open mystery gifts, move animals, manage flowers and more!";
	$f = fopen(ToolBox_path . 'info.txt', "w+");
	fputs($f, $save_str, strlen($save_str));
	fclose($f);
}
?>
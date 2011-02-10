<?php
require_once '../../fB_PluginAPI.php';
	define ('ToolBox_version', file_get_contents('ToolBox.ver'));
	define ('AnimalManager_version', file_get_contents('ToolBox.ver'));
	define ('FlowerManager_version', file_get_contents('ToolBox.ver'));
	define ('MysteryGift_version', file_get_contents('ToolBox.ver'));
	define ('StorageManager_version', file_get_contents('ToolBox.ver'));
	define ('FarmGold_version', file_get_contents('ToolBox.ver'));
$_SESSION['userId'] = isset($_POST['userId']) ? $_POST['userId'] : $_SESSION['userId']; 
define ('ToolBox_URL', 'index.php?userId=' . $_SESSION['userId']);
define ('ToolBox_path', 'plugins/ToolBox/');
define ('ToolBox_settings', 'TB_settings.txt');
define ('ToolBox_temp_stats', 'AM_stats_temp.txt');
define ('ToolBox_sections', 'TB_sections.txt');
define ('AnimalMover_settings', 'AnimalMover_info.txt');
define ('AnimalMover_farmGold_IDs', 'AnimalMover_farmGold_IDs.txt');

define ('Section_path', $_SESSION['base_path'] . 'plugins/Sections/');
define ('GiftBox_path', $_SESSION['base_path'] . 'plugins/GiftBox/');

include 'functions.php';
include 'functions_AM.php';
global $TB_settings;
global $AM_settings;

global $TB_settings_place;
global $TB_update_tempStats;

fBAcctHeader();
// preparing data
TB_loadAnimalMoverSettings();
if(count($AM_settings) < 2) {
	echo '<h1>ToolBox by Hypothalamus</h1>';
	echo 'animal mover data is not set<br>';
	echo 'let the bot run one cycle!';
	#return;
}
TB_loadSettings();
TB_loadSections();
$flowers = array();
$flowers = FM_prepareFormData();
$tempStats = array();
$tempStats = TB_loadTempStats();
$update_tempStats = false;
$show = '';
//changed setting?
if(isset($_POST['send']))
{
	//toolbox settings
	if($_POST['send'] == 'TB' ) {
		$show = 'TB';
		$TB_settings['TB_speedAction'] = @$_POST['NumberRequests'];
		$TB_settings['TB_giftboxLimit'] = @$_POST['giftboxLimit'];
		$TB_settings['TB_getEmptyPositionUseRandom'] = @$_POST['getEmptyPositionUseRandom'];
		$TB_settings['TB_sectionsUseSections'] = @$_POST['sectionsUseSections'];
		$TB_settings['TB_sectionsUseGiftBox'] = @$_POST['sectionsUseGiftBox'];
		$TB_settings['TB_sectionsUseGiftBoxDecoration'] = @$_POST['sectionsUseGiftBoxDecoration'];
		//flowermanager settings
	} elseif( $_POST['send'] == 'FM' ) {
		$show = 'FM';
		$TB_settings['FM_action'] = @$_POST['action'];
		$TB_settings['FM_replaceSource'] = @$_POST['replaceSource'];
		$TB_settings['FM_replaceSame'] = @$_POST['replaceSame'];
		$shed = array();
		$shed = FM_loadFlowershed();

		unset($TB_settings['FM_actionP']['place']);
		if (!empty($shed['contents']))
		{
			foreach($shed['contents'] as $arr) {
				$itemName = GetNameByCode($arr['itemCode']);
				if(isset($_POST['place_' . $itemName])) {
					if($_POST['place_' . $itemName] > 0) {
						$TB_settings['FM_actionP']['place'][$itemName] = $_POST['place_' . $itemName];
						if($TB_settings['FM_actionP']['place'][$itemName] > $arr['numItem']) $TB_settings['FM_actionP']['place'][$itemName] = $arr['numItem'];
					}
				}
			}
		}
		//animalmanager settings
	} elseif($_POST['send'] == 'AM') {
		$show = 'AM';
		$TB_settings['AM_move'] = @$_POST['move'];
		$TB_settings['AM_direction'] = @$_POST['direction'];
		$TB_settings['AM_saveSettings'] = @$_POST['saveSettings'];
		$TB_settings['AM_ItemName'] = @$_POST['ItemName'];
		//animal mover
		foreach($AM_settings as $className => $animals) {
			$buildings = AM_loadBuildingObject($className, false);
			foreach($buildings as $building) {
				$buildingID = $building['id'];
				foreach($animals as $animal) {
					if(isset($_POST['move_' . $buildingID . '_' . $animal])) $TB_settings[$buildingID]['AM_move_' . $animal] = $_POST['move_' . $buildingID . '_' . $animal];
				}
				$TB_settings[$buildingID]['AM_moveTo'] = @$_POST[$buildingID . '_moveTo'];
			}
		}
		//farm gold
	} elseif($_POST['send'] == 'FG') {
		$show = 'FG';
		foreach($AM_settings as $className => $animals) {
			$TB_settings['AM_farmGold_'.$className] = @$_POST['farmGold_'.$className];
		}
		$TB_settings['AM_farmGoldRestore'] = @$_POST['farmGoldRestore'];
		//myterygifts settings
	} elseif($_POST['send'] == 'MG') {
		$show = 'MG';
		//gifts
		$TB_settings['MG_openGiftsFarm'] = @$_POST['openGiftsFarm'];
		$TB_settings['MG_openGiftsFarmAll'] = @$_POST['openGiftsFarmAll'];
		$TB_settings['MG_openGiftsGiftbox'] = @$_POST['openGiftsGiftbox'];
		$TB_settings['MG_openGiftsGiftboxAll'] = @$_POST['openGiftsGiftboxAll'];
		$TB_settings['MG_openGiftsGiftboxJustPlace'] = @$_POST['openGiftsGiftboxJustPlace'];
		//eggs
		$TB_settings['MG_openEggsFarm'] = @$_POST['openEggsFarm'];
		$TB_settings['MG_openEggsFarmAll'] = @$_POST['openEggsFarmAll'];
		$TB_settings['MG_openEggsGiftbox'] = @$_POST['openEggsGiftbox'];
		$TB_settings['MG_openEggsGiftboxAll'] = @$_POST['openEggsGiftboxAll'];
		$TB_settings['MG_openEggsGiftboxJustPlace'] = @$_POST['openEggsGiftboxJustPlace'];
		//storagemanager settings
	} elseif($_POST['send'] == 'SM') {
		$show = 'SM';
		$storage = array();
		$storage = TB_loadStorage();

		unset($TB_settings['SM_action']['sell']);
		unset($TB_settings['SM_action']['place']);
		foreach($storage as $i => $num) {
			//sell
			if(isset($_POST['sell_' . $i])) {
				if($_POST['sell_' . $i] > 0) {
					$TB_settings['SM_action']['sell'][$i] = $_POST['sell_' . $i];
					if($TB_settings['SM_action']['sell'][$i] > $num) $TB_settings['SM_action']['sell'][$i] = $num;
				}
			}
			//place
			if(isset($_POST['place_' . $i])) {
				if($_POST['place_' . $i] > 0) {
					$TB_settings['SM_action']['place'][$i] = $_POST['place_' . $i];
					if($TB_settings['SM_action']['place'][$i] > $num) $TB_settings['SM_action']['place'][$i] = $num;
				}
			}
		}
	}
	save_array($TB_settings, ToolBox_settings);
}
if(isset($_GET['show'])) $show = @$_GET['show'];
if(isset($_GET['importSections'])) {
	TB_importSections();
	TB_loadSections();
	save_array($TB_settings, ToolBox_settings);
}
if(isset($_GET['updateTempStats'])) $update_tempStats = true;

//small checks to avoid problmes
if($tempStats['itemName'] <> $TB_settings['AM_ItemName']) $update_tempStats = true;
if($tempStats['countGiftsInGiftBox'] <> TB_numberGiftBox()) {
	$update_tempStats = true;
	save_array($TB_settings, ToolBox_settings);
}

//update temp stats?
if($update_tempStats) {
	TB_prepareTempStats($TB_settings['AM_ItemName']);
	$tempStats = TB_loadTempStats();
}
?>

<html>
<head>
<style type="text/css">
<?php
include 'toolbox.css'
?>
</style>
</head>
<body>

<h4>ToolBox by Hypothalamus - Changed By Nena & Davdomlan (2010/11/15) - v<?php echo ToolBox_version ?> </h4>
<small>it may happen that if you change settings while ToolBox runs the
settings won't be saved properly - Animal Mover includes Ochard and Turkey Roost</small>
<br>
&nbsp;
<table width='100%' class='tableWhite'>
	<tr>
		<td width='16%' align='center'><a
			href='<?php echo ToolBox_URL; ?>&show=MG' class='headerLinks'>MysteryGift</a></td>
		<td width='16%' align='center'><a
			href='<?php echo ToolBox_URL; ?>&show=AM' class='headerLinks'>Animal & Trees Manager</a></td>
		
		<td width='16%' align='center'><a
			href='<?php echo ToolBox_URL; ?>&show=FM' class='headerLinks'>FlowerManager</a></td>
		<td width='16%' align='center'><a
			href='<?php echo ToolBox_URL; ?>&show=SM' class='headerLinks'>StorageManager</a></td>
		<td width='16%' align='center'><a
			href='<?php echo ToolBox_URL; ?>&show=TB' class='headerLinks'>Settings</a></td>
	</tr>
</table>
<?php if($show == 'MG' || $show == '') { ?>
<h3>MysteryGift opener v<?php echo MysteryGift_version ?></h3>
<form name="MG" method="post" action="index.php">
<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
<b><?php echo $tempStats['countGiftsInGiftBox'] ?></b>
gifts in your gift box! <small>limit :<?php echo $TB_settings['TB_giftboxLimit'] ?>
- <a href='<?php echo ToolBox_URL; ?>&show=MG&updateTempStats=true'
	class='headerLinks'>update values</a></small> <br>
<br>
<table style='border: none' width='100%'>
	<tr>
		<td><b>Mystery Gifts</b></td>
		<td><b>Mystery Eggs</b></td>
	</tr>
	<tr>
		<td width='50%' valign='top'>You have <b><?php echo $tempStats['countGiftsFarm'] ?></b>
		mystery gift(s) on your farm<br>
		open <input type="text" onchange="this.form.submit();" name="openGiftsFarm"
			size="2" value="<?php echo $TB_settings['MG_openGiftsFarm']?>" />
		gift(s) from your farm - max: <?php echo $tempStats['maxGiftsToAdd'] ?><br>
		<input type='checkbox' name='openGiftsFarmAll' onclick='this.form.submit();'
			value=true
			<?php if($TB_settings['MG_openGiftsFarmAll']) echo 'checked';?>> open
		all<br>
		</td>

		<td width='50%' valign='top'>You have <b><?php echo $tempStats['countEggsFarm'] ?></b>
		mystery egg(s) on your farm<br>
		open <input type="text" onchange="this.form.submit();" name="openEggsFarm"
			size="2" value="<?php echo $TB_settings['MG_openEggsFarm']?>" />
		egg(s) from your farm - max: <?php echo $tempStats['maxGiftsToAdd'] ?><br>
		<input type='checkbox' name='openEggsFarmAll' onclick='this.form.submit();'
			value=true
			<?php if($TB_settings['MG_openEggsFarmAll']) echo 'checked';?>> open
		all<br>
		</td>
	</tr>
</table>
<input type='hidden' name='send' value='MG'></form>
			<?php
}
if($show == 'AM') { ?>
<h3>Animal & Trees Manager v<?php echo AnimalManager_version ?></h3>
<form name="AM" method="post" action="<?php echo ToolBox_URL; ?>">
<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
<table width='100%'>
	<tr>
		<td colspan='2' valign='top'>select animal or leave empty to select
		all <?php AM_buildDropDown(); ?> <small> - <a
			href='<?php echo ToolBox_URL; ?>&show=AM&updateTempStats=true'
			class='headerLinks'>update values</a> </small><br>
		<input type='checkbox' name='saveSettings' onclick='this.form.submit();'
			value=true
			<?php if($TB_settings['AM_saveSettings']) echo 'checked' ?>> keep
		settings? (dont change to 'do nothing')<br>
		<br>
		</td>
	</tr>
	<tr>
		<td width='50%' valign='top'><input type='radio' name='move'
			onclick='this.form.submit();' value='nothing'
			<?php if($TB_settings['AM_move'] == 'nothing') echo 'checked';?>>do
		nothing<br>
		<input type='radio' name='move' onclick='this.form.submit();' value=true
		<?php if($TB_settings['AM_move'] == 'true') echo 'checked';?>>allow
		animals to move (<?php echo $tempStats['movingAnimals'] ?>)<br>
		<input type='radio' name='move' onclick='this.form.submit();' value=false
		<?php if($TB_settings['AM_move'] == 'false') echo 'checked';?>>forbid
		animals to move (<?php echo $tempStats['standingAnimals'] ?>)<br>
		</td>
		<td valign='top'><input type='radio' name='direction'
			onclick='this.form.submit();' value='-1'
			<?php if($TB_settings['AM_direction'] == '-1') echo 'checked';?>> do
		nothing <br>
		change animals direction to:
		<table>
			<tr>
				<td align='right'>(<?php echo $tempStats['directionAnimals3'] ?>)
				top-left<input type='radio' name='direction' onclick='this.form.submit();'
					value='3'
					<?php if($TB_settings['AM_direction'] == '3') echo 'checked';?>></td>
				<td><input type='radio' name='direction' onclick='this.form.submit();'
					value='2'
					<?php if($TB_settings['AM_direction'] == '2') echo 'checked';?>>
				top-right (<?php echo $tempStats['directionAnimals2'] ?>)</td>
			</tr>
			<tr>
				<td align='right'>(<?php echo $tempStats['directionAnimals0'] ?>)
				buttom-left<input type='radio' name='direction' onclick='this.form.submit();'
					value='0'
					<?php if($TB_settings['AM_direction'] == '0') echo 'checked';?>></td>
				<td><input type='radio' name='direction' onclick='this.form.submit();'
					value='1'
					<?php if($TB_settings['AM_direction'] == '1') echo 'checked';?>>
				buttom-right (<?php echo $tempStats['directionAnimals1'] ?>)</td>
			</tr>
		</table>
		</td>
	</tr>
</table>
					<?php
					foreach($AM_settings as $className => $animals) {
						AM_buildAnimalMoverTable($className);
					}
					?> <input type='hidden' name="send" value="AM"></form>
<?php
}
if($show == 'FG') { ?>
<h3>FarmGold v<?php echo FarmGold_version ?> — n0m mod [June 30, 2010]</h3>
<p><b>*NB on n0m's mod:</b> if you put 1 (one) as the number of cycles
you want to perform with any particular kind of building<br>
buildings of this kind won't get harvested - so that you could harvest
them <b>manually</b> and post your reward(s) to your wall.</p>
<small>It might be useful for those of you who are just breeding animals
for their neighbors ;)</small>
<form name="FG" method="post" action="<?php echo ToolBox_URL; ?>">Performing <? echo $TB_settings['TB_speedAction'] ?>
actions per request <i><a href='<?php echo ToolBox_URL; ?>&show=TB'
	class='headerLinks'>&raquo;change</a></i> &nbsp; &nbsp; &nbsp;
	<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
	<input type="button" value="Save changes >>" onclick='this.form.submit();'> <br>
<hr>
How many cycles do you want to perform with: <? AM_farmGold_showOptions() ?>
<hr>
What does this do?<br>
<ol>
	<li>move all animals out of a building. By doing this the animals will
	get riped (glitch).</li>
	<li>all animals except one are harvested (-> coins)</li>
	<li>move the one riped animal back into the building. By doing this the
	building will get riped, too</li>
	<li>move the other animals back in the building</li>
	<li>harvest the building (-> coins + other things like calf)</li>
</ol>
You should have a full (or at least not empty) building.<br>
This won't affect the rest of the farm!<br>
<br>
I recommend to perform 20 actions per request and to use a maximum bot
speed of 8 (recent changes in Zynga protocol). Otherwise it could take
some time. (if you get errors use a lower nummber)
<hr>
Restore:<br>
<input type='checkbox' name='farmGoldRestore' onclick='this.form.submit();'
	value=true <? if($TB_settings['AM_farmGoldRestore']) echo 'checked' ?>>
use restore function? (should be checked) <input type='hidden'
	name='send' value='FG'></form>
<?php
}

if($show == 'FM') { ?>
<h3>FlowerManager v<?php echo FlowerManager_version ?></h3>
<form name="FM" method="post" action="index.php">
<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
<table style='border: none' width='100%'>
	<tr>
		<td valign='top' width='25%'><input type='radio' name='action'
			onclick='this.form.submit();' value='nothing'
			<?php if($TB_settings['FM_action'] == 'nothing') echo 'checked';?>>do
		nothing <br>
		<input type='radio' name='action' onclick='this.form.submit();' value='delet'
		<?php if($TB_settings['FM_action'] == 'delet') echo 'checked';?>>delete
		droopy flowers <br>
		<input type='radio' name='action' onclick='this.form.submit();' value='replace'
		<?php if($TB_settings['FM_action'] == 'replace') echo 'checked';?>>replace
		droopy flowers <br>
		</td>
		<td><input type='checkbox' name='replaceSame' onclick='this.form.submit();'
			value=true <?php if($TB_settings['FM_replaceSame']) echo 'checked';?>>replace
		with same ( e.g. sunflowers will be replaced with sunflowers ) <br>
		take flowers from: <br>
		<input type='radio' name='replaceSource' onclick='this.form.submit();'
			value='shed'
			<?php if($TB_settings['FM_replaceSource'] == 'shed') echo 'checked';?>>garden
		shed -- <input type='radio' name='replaceSource' onclick='this.form.submit();'
			value='giftbox'
			<?php if($TB_settings['FM_replaceSource'] == 'giftbox') echo 'checked';?>>gift
		box -- <input type='radio' name='replaceSource' onclick='this.form.submit();'
			value='both'
			<?php if($TB_settings['FM_replaceSource'] == 'both') echo 'checked';?>>both
		</td>
	</tr>
</table>
<hr>
<table style='border: none' width='100%'>
	<tr>
		<!-- flowers on farm -->
		<td width='25%' valign='top'>
		<table width='100%'>
			<tr>
				<td width='70%'><b>flowers on farm</b></td>
				<td><b><?php echo $flowers['live_c'] ?></b></td>
			</tr>
			<?php TB_buildTable($flowers['live']) ?>
		</table>
		</td>
		<!-- droopy flowers on farm -->
		<td width='25%' valign='top'>
		<table width='100%'>
			<tr>
				<td width='70%'><b>droopy flowers</b></td>
				<td><b><?php echo $flowers['droop_c'] ?></b></td>
			</tr>
			<?php TB_buildTable($flowers['droop']) ?>
		</table>
		</td>
		<!-- flowers in shed -->
		<td width='25%' valign='top'>
		<table width='100%'>
			<tr>
				<td width='70%'><b>flowers in shed</b></td>
				<td><b><?php echo $flowers['shed_c'] ?></b></td>
			</tr>
			<?php TB_buildTable($flowers['shed']) ?>
		</table>
		</td>
		<!-- flowers in giftbox -->
		<td valign='top'>
		<table width='100%'>
			<tr>
				<td width='70%'><b>flowers in gift box</b></td>
				<td><b><?php echo $flowers['giftbox_c'] ?></b></td>
			</tr>
			<?php TB_buildTable($flowers['giftbox']) ?>
		</table>
		</td>
	</tr>
</table>
<hr>
<input type="button" value="Save changes >>" onclick='this.form.submit();'>
<table style='border: none' width='100%'>
	<tr>
		<td width="35%">in shed</td>
		<td width="10%" align="center">quantity</td>
		<td>place</td>
	</tr>
	<? FM_buildTable(); ?>
</table>
<input type='hidden' name='send' value='FM'></form>
	<?php
}
if($show == 'SM') { ?>
<h3>StorageManager v<?php echo StorageManager_version ?></h3>
<form name="SM" method="post" action="<?php echo ToolBox_URL; ?>"><?php SM_showFarmStats(); ?>
<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
<input type="button" value="Save changes >>" onclick='this.form.submit();'>
<hr>
<table style='border: none' width='100%'>
	<tr>
		<td width="45%">in storage</td>
		<td width="10%" align="center">size</td>
		<td width="10%" align="center">quantity</td>
		<td>sell</td>
		<td>place</td>
	</tr>

	<?php SM_buildTable(); ?>
</table>
<input type='hidden' name='send' value='SM'></form>
	<?php
}
if($show == 'TB') { ?>
<h3>Settings</h3>
<form name="TB" method="post" action="<?php echo ToolBox_URL; ?>">
<input type="hidden" name="userId" value="<?php echo $_SESSION['userId']; ?>">
<table style='border: none' width='100%'>
	<tr>
		<td>gift box limit:</td>
		<td><input type="text" onchange="this.form.submit();" name="giftboxLimit"
			size="2" title="Number of simultanious requests"
			value="<?php echo $TB_settings['TB_giftboxLimit'];?>" /></td>
		<td>you dont have to change this normally</td>
	</tr>
	<tr>
		<td width='45%' valign='top'>number of requests:</td>
		<td width='10%' valign='top'><input type="text" onchange="this.form.submit();"
			name="NumberRequests" size="2"
			title="Number of simultanious requests"
			value="<?php echo $TB_settings['TB_speedAction'];?>" /></td>
		<td>what is this?<br>
		&nbsp;&nbsp;this is similar to the bot speed.<br>
		&nbsp;&nbsp;if you have problems try lower numbers</td>
	</tr>
	<tr>
		<td>use random function when placing things?</td>
		<td><input type='checkbox' name='getEmptyPositionUseRandom'
			onclick='this.form.submit();' value=true
			<?php if($TB_settings['TB_getEmptyPositionUseRandom']) echo 'checked';?>></td>
	</tr>
</table>
<h4>Sections</h4>
			<? TB_showSections() ?> <input type='hidden' name='send' value='TB'>
</form>
<hr>
Debug:
			<?php

			echo '<small><pre>';
			//print_r(TB_loadObjects());
			//print_r(TB_loadUnits());

			//print_r($TB_settings);
			//print_r($AM_settings);
			//print_r($TB_settings_place);
			//print_r(@unserialize(file_get_contents(Section_path . $_SESSION['userId'] . '_sections.txt')));
			//print_r(TB_loadGiftBox());

			//$farm = TB_buildFarmArray();
			//for ($x = 97; $x >= 0; $x--) {
			//	for ($y = 97; $y >= 0; $y--) {
			//		if($farm[$x][$y]) echo '_';
			//		else echo '#';
			//	}
			//	echo '<br>';
			//}
			echo '</pre></small>';


}
?>
</body>
</html>


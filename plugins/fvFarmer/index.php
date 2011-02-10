<?php
require_once '../../fB_PluginAPI.php';
define('fvFarmer_version', file_get_contents('fvFarmer.ver'));
define('fvFarmer_Path', getcwd() . '\\');
// file definitions
define('fvFarmer_Main', 'fvFarmer_main.sqlite');
include 'includes/fvFarmer.class.php';
$fvM = new fvFarmer('formload');
if (isset($_POST['defsubmit'])) {
	//Save General Settings
	$fvM->fvSaveSettings('general', $_POST);
}
if (isset($_POST['orclear'])) {
	//Clear Override Seedlist
	$overridelist = array();
	file_put_contents($_SESSION['base_path'] . F('overrideseed.txt'),serialize($overridelist));
}
if (isset($_POST['defclear'])) {
	//Clear Default Seedlist
	$defaultlist = array();
	file_put_contents($_SESSION['base_path'] . F('defaultseed.txt'),serialize($defaultlist));
}
if (isset($_POST['craftset'])) {
	//Save Craft Seed Setting
	@$fvM->fvSaveSettings('craft', $_POST['craftgood']);
}
if (isset($_POST['questset'])) {
	//Save Co-Op Seed Setting
	@$fvM->fvSaveSettings('coop', $_POST['quests']);
}
if (isset($_POST['animaltset'])) {
	//Save Animal Transforms
	@$fvM->fvSaveSettings('transform', $_POST['transform']);
}
if (isset($_POST['animalset'])) {
	//Save Animals
	@$fvM->fvSaveSettings('animal', $_POST['animal']);
}
if (isset($_POST['treeset'])) {
	//Save Trees
	@$fvM->fvSaveSettings('tree', $_POST['tree']);
}
if (isset($_POST['buildset'])) {
	//Save Buildings
	@$fvM->fvSaveSettings('build', $_POST['harvest']);
}
if (file_exists($_SESSION['base_path'] . F('overrideseed.txt'))) { // fix infinite loop when no file exists
	$overridelist = unserialize((file_get_contents($_SESSION['base_path'] . F('overrideseed.txt'))));
}
if (file_exists($_SESSION['base_path'] . F('defaultseed.txt'))) { // fix infinite loop when no file exists
	$defaultlist = unserialize((file_get_contents($_SESSION['base_path'] . F('defaultseed.txt'))));
}
if (isset($_POST['overridesave'])) {
	$harvest = isset($_POST['DNH_' . $_POST['seedname']]) ? 1 : 0;
	$plant = $_POST['NTP_' . $_POST['seedname']] > 0 ? $_POST['NTP_' . $_POST['seedname']] : 0;
	$overridelist[] = array($_POST['seedname'], $harvest, $plant);
	file_put_contents($_SESSION['base_path'] . F('overrideseed.txt'),serialize($overridelist));
}
if (isset($_POST['defaultsave'])) {
	$plant = $_POST['NTP_' . $_POST['seedname']] > 0 ? $_POST['NTP_' . $_POST['seedname']] : 0;
	if ($plant > 0) {
		$defaultlist[] = array($_POST['seedname'], $plant);
		file_put_contents($_SESSION['base_path'] . F('defaultseed.txt'),serialize($defaultlist));
	} else {
		echo '<font color="red"><b>Error: Number to Plant Cannot Be 0 For Default Seeds</b></font><br />';
	}
}
if (isset($_POST['orsub'])) {
	//Remove Seed From Override Seedlist
	if (isset($_POST['overridelist'])) {
		foreach ($_POST['overridelist'] as $item)
		{
			unset($overridelist[$item]);
			file_put_contents($_SESSION['base_path'] . F('overrideseed.txt'),serialize($overridelist));
		}
	}
}
if (isset($_POST['defsub'])) {
	//Remove Seed From Default Seedlist
	if (isset($_POST['defaultlist'])) {
		foreach ($_POST['defaultlist'] as $item)
		{
			unset($defaultlist[$item]);
			file_put_contents($_SESSION['base_path'] . F('defaultseed.txt'),serialize($defaultlist));
		}
	}
}
$mastcnt = unserialize(fBGetDataStore('cropmastery'));
$maststar = unserialize(fBGetDataStore('cropmasterycnt'));
$fvM->settings = $fvM->fvGetSettings();
@$plowcheck = $fvM->settings['plow'] == 1 ? 'CHECKED' : '';
@$plantcheck = $fvM->settings['plant'] == 1 ? 'CHECKED' : '';
@$harvestcheck = $fvM->settings['harvest'] == 1 ? 'CHECKED' : '';
@$planecheck = $fvM->settings['flyplane'] == 1 ? 'CHECKED' : '';
@$autocheck = $fvM->settings['automast'] == 1 ? 'CHECKED' : '';
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/index.css" />
<link rel="stylesheet" type="text/css" href="css/fvFarmer.css" />
<script type="text/javascript" src="js/fvFarmerCheckBoxes.js"></script>
<script type="text/javascript" src="js/fvFarmer.js"></script>
<script type="text/javascript" src="js/tabber.js"></script>

<script type="text/javascript">
function showhide(id){
if (document.getElementById){
obj = document.getElementById(id);
if (obj.style.display == "none"){
obj.style.display = "block";
} else {
obj.style.display = "none";
}
}
}
</script>
</head>
<body>
<?php
fBAcctHeader();
?>
<h1>fvFarmer V<?php echo fvFarmer_version; ?></h1>
<div class="tabber" id="t"><a name="top"></a> <!--Seeding Tab-->
<div class="tabbertab">
<h2>Crops</h2>
<table>
	<tr>
		<td colspan=3>
		<table style="text-align: left;" width="100%" class="crops">
			<tbody>
				<tr>
					<td class="crops" width="100%" colspan="6" rowspan="1"
						style="vertical-align: top;"><?php 
						//$seedCnt = $fvM->fsItemCounts('seed');
						$plotCnt = count(GetObjects('Plot'));
						$seedCnt = 0;
						foreach (GetObjects('Plot') as $plot)
						{
							if ($plot['state'] == 'planted') {
								$newplot[$plot['itemName']][number_format($plot['plantTime'],0,'','')]++;
								$seedCnt++;
							}
						}
						@$plotCnt = $plotCnt - $seedCnt;
						?> <big><b>Crops: </b><?php echo $seedCnt; ?> - <b>Empty Plots: </b><?php echo $plotCnt; ?></big></td>
				</tr>
				<tr style="font-weight: bold;">
					<td class="crops" width="12%" align="center"
						style="vertical-align: top;"><small>Qty</small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small>Crop</small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small>Planted</small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small>Ripe
					At</small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small>Time
					Remaining</small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small>%
					Complete</small></td>
				</tr>
				<?php
				//$seeds = $fvM->fsGetWorldSeeds();
				foreach ($newplot as $key=>$seed)
				{
					$uinfo = Units_GetUnitByName($key, true);
					foreach ($seed as $ptime=>$seed2)
					{
						$plantTime = $ptime/1000;

						$growTime = round(23 * $uinfo['growTime']);
						$ripeTime = $plantTime + ($growTime * 3600);
						$remainTime = $ripeTime - time();
						$leftTime = $fvM->fvMakeTime($remainTime);
						$perTime = 100 - round(($remainTime / ($growTime * 3600)) * 100);
						?>
				<tr>
					<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $seed2; ?></small></td>
					<td class="crops" style="vertical-align: top;"><small><?php echo $uinfo['realname']; ?></small></td>
					<td class="crops" style="vertical-align: top;"><small><?php echo date("m/d/y, g:i a", $plantTime); ?></small></td>
					<td class="crops" style="vertical-align: top;"><small><?php echo date("m/d/y, g:i a", $ripeTime); ?></small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $leftTime; ?></small></td>
					<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $perTime . '%'; ?></small></td>
				</tr>
				<?php
					}
				}
				?>

			</tbody>
		</table>
		</td>
	</tr>
	<tr>
		<td>
		<table>
			<tr>
				<td>
				<form name="defsettings" method="post"><input type="hidden"
					name="userId" value="<?= $_SESSION['userId']; ?>" /> <input
					type="checkbox" name="plow" value="1" <?= $plowcheck; ?> />&nbsp;Plow&nbsp;
				<input type="checkbox" name="plant" value="1" <?= $plantcheck; ?> />&nbsp;Plant&nbsp;
				<input type="checkbox" name="harvest" value="1"
				<?= $harvestcheck; ?> />&nbsp;Harvest<br />
				<input type="checkbox" name="flyplane" value="1" <?= $planecheck; ?> />&nbsp;Fly
				BiPlane<small>&nbsp;(Requires FV$)</small><br />
				
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="automast" value="1"
				<?= $autocheck; ?> />&nbsp;Auto Plant Mastery<br />
				</td>
			</tr>
			<tr>
				<td align="center"><br />
				Seed By:&nbsp; <select name="masteryorder">
					<option
					<?= $fvM->settings['masteryorder'] == 'masttime' ? 'SELECTED' : ''; ?>
						value="masttime">Mastery Time</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'growtime' ? 'SELECTED' : ''; ?>
						value="growtime">Grow Time</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'profithr' ? 'SELECTED' : ''; ?>
						value="profithr">Profit/Hour</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'xphr' ? 'SELECTED' : ''; ?>
						value="xphr">XP/Hour</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'nomast' ? 'SELECTED' : ''; ?>
						value="nomast">No Mastery</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'crafting' ? 'SELECTED' : ''; ?>
						value="crafting">Crafting</option>
					<option
					<?= $fvM->settings['masteryorder'] == 'coop' ? 'SELECTED' : ''; ?>
						value="coop">Co-Op</option>
				</select><br />
				Asc&nbsp;<input type="radio" name="direction" value="asc"
				<?= $fvM->settings['direction'] == 'asc' || !isset($fvM->settings['direction']) ? 'CHECKED' : ''; ?>>&nbsp;/&nbsp;
				Desc&nbsp;<input type="radio" name="direction" value="desc"
				<?= $fvM->settings['direction'] == 'desc' ? 'CHECKED' : ''; ?>> <br />
				<br />
				<input type="submit" name="defsubmit" value="Save" />
				</form>
				</td>
			</tr>
		</table>
		</td>

		<td>
		<form method="post" style="display: inline;"><input type="hidden"
			name="userId" value="<?= $_SESSION['userId']; ?>" /> <b>Override
		Seeds:</b>&nbsp; <input type="submit" name="orsub" value="-"
			style="width: 25px;" />&nbsp; <input type="submit" name="orclear"
			value="Clear" /><br />
		<select class="seed_list" name="overridelist[]" multiple
			style="width: 250px; height: 150px;">
			<?php
			foreach ($overridelist as $key=>$seed_item)
			{
				$harvest = ($seed_item[1] == 1) ? '- Harvest Off' : '';
				echo '   <option value="'.$key.'">'.htmlentities(Units_GetRealnameByName($seed_item[0])).' ('.$seed_item[2].') ' . $harvest . '</option>';

			}
			?>
		</select></form>
		</td>

		<td>
		<form method="post" style="display: inline;"><input type="hidden"
			name="userId" value="<?= $_SESSION['userId']; ?>" /> <b>Default
		Seeds:</b>&nbsp; <input type="submit" name="defsub" value="-"
			style="width: 25px;" />&nbsp; <input type="submit" name="defclear"
			value="Clear" /><br />
		<select class="seed_list" name="defaultlist[]" multiple
			style="width: 250px; height: 150px;">
			<?php
			$i = 0;
			foreach ($defaultlist as $key=>$seed_item)
			{
				echo '   <option value="'.$key.'">'.htmlentities(Units_GetRealnameByName($seed_item[0])).' ('.$seed_item[1].')</option>';
					
			}
			?>
		</select></form>
		</td>

</table>
<div class="tabber" id="t2"><!--Unmastered Tab-->
<div class="tabbertab">
<h2>Unmastered</h2>
<table cellpadding=5 cellspacing=0 border=0 class="seedsTable">

<?php
$seeds = $fvM->fvSeedUnits('unmastered');
foreach ($seeds as $seed)
{
	if (isset($seed['limitedStart']) && (strtotime($seed['limitedStart']) > time() || strtotime($seed['limitedEnd']) < time())) {
		$expstyle = 'style="background-color: #B7DFFD;"';
		$expired = 1;
	} else {
		$expstyle='style="background-color: transparent;"';
		$expired = 0;
	}
	$profit = $seed['coinYield'] - $seed['cost'];
	$gtime = round($seed['growTime'] * 23);
	@$seed['mastcnt'] = isset($seed['mastcnt']) && $seed['mastcnt'] > 0 ? $seed['mastcnt'] : 0;
	echo '<tr ' . $expstyle . '><td width=40 valign="top"><img src="/' . $seed['iconurl'] . '.40x40.jpeg"></td>';
	echo '<td valign="top"><small><b>' . $seed['realname'] . '</b><br />';
	if (isset($seed['isHybrid'])) {
		echo '<font color="purple"><b>Hybrid (Greenhouse)</b></font><br />';
	}
	if (isset($seed['license'])) {
		echo '<font color="red"><b>Requires License</b></font><br />';
		if (isset($seed['licenseCost'])) echo '<font color="red"><b>Cost: ' . $seed['licenseCost'] . ' FV$</b></font><br />';
	}
	if (isset($seed['limitedStart'])) {
		echo '<font color="blue">Start Date: <b>' . @$seed['limitedStart'] . '</b><br />';
		echo 'End Date: <b>' . @$seed['limitedEnd'] . '</b></font><br />';
	}
	echo 'Required Level: <b>' . @$seed['requiredLevel'] . '</b><br />';
	echo 'Cost: <b>' . @$seed['cost'] . '</b><br />';
	echo 'XP: <b>' . @$seed['plantXp'] . '</b></small></td>';

	echo '<td valign="top"><small>Grow Time: <b>' . $gtime . 'hr</b><br />';
	echo 'Coin Yield: <b>' . @$seed['coinYield'] . '</b><br />';
	echo 'Profit: <b>' . $profit . '</b><br />';
	echo 'Profit/Hour: <b>' . number_format($profit / $gtime, 2) . '</b><br />';
	echo 'XP/Hour: <b>' . number_format($seed['plantXp'] / $gtime, 2) . '</b></small></td>';
	if (@$seed['mastery'] == 'true') {
		echo '<td valign="top"><small>Harvested: <b>' . @$seed['mastcnt'] . '</b><br />';
		echo 'Mastery: <b>' . @$seed['masterymax'] . '</b><br />';
		if ($seed['mastcnt'] >= $seed['masterymax']) {
			echo '<font color="green"><b>Mastered</b></font>';
		}
		echo '</small></td>';
	} else {
		echo '<td>&nbsp;</td>';
	}
	echo '<td valign="top"><small>';
	if (isset($seed['bushelItemCode'])) echo '<font color="blue"><b>Produces Bushels</b></font></br />';
	@$reqs = unserialize($seed['requirements']);
	//echo nl2br(print_r($reqs,true));
	if (is_array($reqs))
	{
		echo '<b>Requirements:</b><br />';
		foreach ($reqs as $req)
		{
			if (!isset($req['@attributes']) && !isset($req[0]) && !isset($req['requirement'])) $req['@attributes'] = $req;
			If (isset($req['@attributes']) && isset($req['@attributes']['name']) && $req['@attributes']['className'] != 'SeedPackage') {
				$seedc = Units_GetCodeByName($req['@attributes']['name']);
				@$mastered = $maststar[$seedc] == $req['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
				echo Units_GetRealnameByName($req['@attributes']['name']) . $mastered . '<br />';
			}
			if (isset($req[0]['@attributes'])) {
				foreach ($req as $req2) {
					//echo nl2br(print_r($req2,true));
					if (!isset($req2['@attributes']['name']) || $req2['@attributes']['className'] == 'SeedPackage') continue;
					$seedc = Units_GetCodeByName($req2['@attributes']['name']);
					if ($req2['@attributes']['name'] == $seed['name']) echo '(Hybrid) ';
					@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
					echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
				}
			}
			if (isset($req['requirement'])) {
				foreach ($req as $req2) {
					if ($req2['@attributes']['className'] == 'SeedPackage') continue;
					$seedc = Units_GetCodeByName($req2['@attributes']['name']);
					@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
					echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
				}
			}
		}
	} else { echo '&nbsp;'; }
	echo '</small></td>';
	// ******************** START Override and Default cell*************************
	echo '<td width="70" align="center"><div style="position:relative;z-index:1; text-align:left;">';
	// ******************** Override DIV content *************************
	echo '<div id="' . $seed['realname'] . 'OverNoMaster" class="doOverride" style="display:none;">';
	echo '<div><b>Override seed:<br />' . $seed['realname'] . '</b></div>';
	echo '<form method="post">';
	echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
	echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
	echo '<div class="divPopup"><input type="checkbox" name="DNH_' . $seed['name'] . '" />&nbsp;&nbsp;<span>Do Not Harvest</span></div>';
	echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
	echo '<div class="divPopup"><input type="submit" name="overridesave" value="Save" />&nbsp;<input type="button" value="Cancel" onclick="showhide(\'' . $seed['realname'] . 'OverNoMaster\')" /></div>';
	echo '</form>';
	echo '</div>';
	// ******************** Default DIV content *************************
	echo '<div id="' . $seed['realname'] . 'DefaultNoMaster" class="doDefault" style="display:none;">';
	echo '<form method="post">';
	echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
	echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
	echo '<div><b>Default seed:<br />' . $seed['realname'] . '</b></div>';
	echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
	echo '<div class="divPopup"><input type="submit" name="defaultsave" value="Save" />&nbsp;<input type="button" value="Cancel" onclick="showhide(\'' . $seed['realname'] . 'DefaultNoMaster\')" /></div>';
	echo '</form>';
	echo '</div>';
	// ******************** buttons *************************
	echo '<input type="button" value="Override" name="addover" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'OverNoMaster\')" title="click to show/hide options"><br />';
	echo '<input type="button" value="Default" name="adddefault" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'DefaultNoMaster\')" title="click to show/hide options">';
	echo '<div style="text-align:right; margin-top:5px;"><a href="#top" title="scroll to top"><img src="img/top.gif" title="back to top" border="0"/></a></td>';
	// ******************** END Override and Default cell*************************
	echo '</tr>';
}

?>
</table>
</div>
<!--Mastered Tab-->
<div class="tabbertab">
<h2>Mastered</h2>
<table cellpadding=5 cellspacing=0 border=0 class="seedsTable">
<?php
$seeds = $fvM->fvSeedUnits('mastered');
foreach ($seeds as $seed)
{
	if (isset($seed['limitedStart']) && (strtotime($seed['limitedStart']) > time() || strtotime($seed['limitedEnd']) < time())) {
		$expstyle = 'style="background-color: #B7DFFD;"';
		$expired = 1;
	} else {
		$expstyle='style="background-color: transparent;"';
		$expired = 0;
	}
	$profit = $seed['coinYield'] - $seed['cost'];
	$gtime = round($seed['growTime'] * 23);
	@$seed['mastcnt'] = isset($seed['mastcnt']) && $seed['mastcnt'] > 0 ? $seed['mastcnt'] : 0;
	echo '<tr ' . $expstyle . '><td width=40 valign="top"><img src="/' . $seed['iconurl'] . '.40x40.jpeg"></td>';
	echo '<td valign="top"><small><b>' . $seed['realname'] . '</b><br />';
	if (isset($seed['isHybrid'])) {
		echo '<font color="purple"><b>Hybrid (Greenhouse)</b></font><br />';
	}	
	if (isset($seed['license'])) {
		echo '<font color="red"><b>Requires License</b></font><br />';
		if (isset($seed['licenseCost'])) echo '<font color="red"><b>Cost: ' . $seed['licenseCost'] . ' FV$</b></font><br />';
	}
	if (isset($seed['limitedStart'])) {
		echo '<font color="blue">Start Date: <b>' . @$seed['limitedStart'] . '</b><br />';
		echo 'End Date: <b>' . @$seed['limitedEnd'] . '</b></font><br />';
	}
	echo 'Required Level: <b>' . @$seed['requiredLevel'] . '</b><br />';
	echo 'Cost: <b>' . @$seed['cost'] . '</b><br />';
	echo 'XP: <b>' . @$seed['plantXp'] . '</b></small></td>';

	echo '<td valign="top"><small>Grow Time: <b>' . $gtime . 'hr</b><br />';
	echo 'Coin Yield: <b>' . @$seed['coinYield'] . '</b><br />';
	echo 'Profit: <b>' . $profit . '</b><br />';
	echo 'Profit/Hour: <b>' . number_format($profit / $gtime, 2) . '</b><br />';
	echo 'XP/Hour: <b>' . number_format($seed['plantXp'] / $gtime, 2) . '</b></small></td>';
	if (@$seed['mastery'] == 'true') {
		echo '<td valign="top"><small>Harvested: <b>' . @$seed['mastcnt'] . '</b><br />';
		echo 'Mastery: <b>' . @$seed['masterymax'] . '</b><br />';
		if ($seed['mastcnt'] >= $seed['masterymax']) {
			echo '<font color="green"><b>Mastered</b></font>';
		}
		echo '</small></td>';
	} else {
		echo '<td>&nbsp;</td>';
	}
	echo '<td valign="top"><small>';
	if (isset($seed['bushelItemCode'])) echo '<font color="blue"><b>Produces Bushels</b></font></br />';
	@$reqs = unserialize($seed['requirements']);
	//echo nl2br(print_r($reqs,true));
	if (is_array($reqs))
	{
		echo '<b>Requirements:</b><br />';
		foreach ($reqs as $req)
		{
			if (!isset($req['@attributes']) && !isset($req[0]) && !isset($req['requirement'])) $req['@attributes'] = $req;
			If (isset($req['@attributes']) && isset($req['@attributes']['name']) && $req['@attributes']['className'] != 'SeedPackage') {
				$seedc = Units_GetCodeByName($req['@attributes']['name']);
				@$mastered = $maststar[$seedc] == $req['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
				echo Units_GetRealnameByName($req['@attributes']['name']) . $mastered . '<br />';
			}
			if (isset($req[0]['@attributes'])) {
				foreach ($req as $req2) {
					if (!isset($req2['@attributes']['name']) || $req2['@attributes']['className'] == 'SeedPackage') continue;
					$seedc = Units_GetCodeByName($req2['@attributes']['name']);
					if ($req2['@attributes']['name'] == $seed['name']) echo '(Hybrid) ';
					@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
					echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
				}
			}
			if (isset($req['requirement'])) {
				foreach ($req as $req2) {
					if ($req2['@attributes']['className'] == 'SeedPackage') continue;
					$seedc = Units_GetCodeByName($req2['@attributes']['name']);
					@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
					echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
				}
			}
		}
	} else { echo '&nbsp;'; }
	echo '</small></td>';
	echo '<td>';
	// ******************** START Override and Default cell*************************
	echo '<td width="70" align="center"><div style="position:relative;z-index:1; text-align:left;">';
	// ******************** Override DIV content *************************
	echo '<div id="' . $seed['realname'] . 'OverMaster" class="doOverride" style="display:none;">';
	echo '<div><b>Override seed:<br />' . $seed['realname'] . '</b></div>';
	echo '<form method="post">';
	echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
	echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
	echo '<div class="divPopup"><input type="checkbox" name="DNH_' . $seed['name'] . '" />&nbsp;&nbsp;<span>Do Not Harvest</span></div>';
	echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
	echo '<div class="divPopup"><input type="submit" name="overridesave" value="Save" />&nbsp;<input type="button" value="Cancel"onclick="showhide(\'' . $seed['realname'] . 'OverMaster\')" /></div>';
	echo '</form>';
	echo '</div>';
	// ******************** Default DIV content *************************
	echo '<div id="' . $seed['realname'] . 'DefaultMaster" class="doDefault" style="display:none;">';
	echo '<form method="post">';
	echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
	echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
	echo '<div><b>Default seed:<br />' . $seed['realname'] . '</b></div>';
	echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
	echo '<div class="divPopup"><input type="submit" name="defaultsave" value="Save" />&nbsp;<input type="button" value="Cancel" onclick="showhide(\'' . $seed['realname'] . 'DefaultMaster\')" /></div>';
	echo '</form>';
	echo '</div>';
	// ******************** buttons *************************
	echo '<input type="button" value="Override" name="addover" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'OverMaster\')" title="click to show/hide options"><br />';
	echo '<input type="button" value="Default" name="adddefault" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'DefaultMaster\')" title="click to show/hide options">';
	echo '<div style="text-align:right; margin-top:5px;"><a href="#top" title="scroll to top"><img src="img/top.gif" title="back to top" border="0"/></a></td>';
	// ******************** END Override and Default cell*************************
	echo '</tr>';
}

?>
</table>
</div>
<!--No Mastery Tab-->
<div class="tabbertab">
<h2>No Mastery</h2>
<table cellpadding=5 cellspacing=0 border=0 class="seedsTable"
	width="500">
	<?php
	$seeds = $fvM->fvSeedUnits('nomastery');
	foreach ($seeds as $seed)
	{
		if (isset($seed['limitedStart']) && (strtotime($seed['limitedStart']) > time() || strtotime($seed['limitedEnd']) < time())) {
			$expstyle = 'style="background-color: #B7DFFD;"';
			$expired = 1;
		} else {
			$expstyle='style="background-color: transparent;"';
			$expired = 0;
		}
		$profit = $seed['coinYield'] - $seed['cost'];
		$gtime = round($seed['growTime'] * 23);
		@$seed['mastcnt'] = isset($seed['mastcnt']) && $seed['mastcnt'] > 0 ? $seed['mastcnt'] : 0;
		echo '<tr ' . $expstyle . '><td width=40 valign="top"><img src="/' . $seed['iconurl'] . '.40x40.jpeg"></td>';
		echo '<td valign="top"><small><b>' . $seed['realname'] . '</b><br />';
		if (isset($seed['isHybrid'])) {
		echo '<font color="purple"><b>Hybrid (Greenhouse)</b></font><br />';
	}		
		if (isset($seed['license'])) {
			echo '<font color="red"><b>Requires License</b></font><br />';
			if (isset($seed['licenseCost'])) echo '<font color="red"><b>Cost: ' . $seed['licenseCost'] . ' FV$</b></font><br />';
		}
		if (isset($seed['limitedStart'])) {
			echo '<font color="blue">Start Date: <b>' . @$seed['limitedStart'] . '</b><br />';
			echo 'End Date: <b>' . @$seed['limitedEnd'] . '</b></font><br />';
		}
		echo 'Required Level: <b>' . @$seed['requiredLevel'] . '</b><br />';
		echo 'Cost: <b>' . @$seed['cost'] . '</b><br />';
		echo 'XP: <b>' . @$seed['plantXp'] . '</b></small></td>';

		echo '<td valign="top"><small>Grow Time: <b>' . $gtime . 'hr</b><br />';
		echo 'Coin Yield: <b>' . @$seed['coinYield'] . '</b><br />';
		echo 'Profit: <b>' . $profit . '</b><br />';
		echo 'Profit/Hour: <b>' . number_format($profit / $gtime, 2) . '</b><br />';
		echo 'XP/Hour: <b>' . number_format($seed['plantXp'] / $gtime, 2) . '</b></small></td>';
		echo '<td valign="top"><small>';
		if (isset($seed['bushelItemCode'])) echo '<font color="blue"><b>Produces Bushels</b></font></br />';
		@$reqs = unserialize($seed['requirements']);
		//echo nl2br(print_r($reqs,true));
		if (is_array($reqs))
		{
			echo '<b>Requirements:</b><br />';
			foreach ($reqs as $req)
			{
				if (!isset($req['@attributes']) && !isset($req[0]) && !isset($req['requirement'])) $req['@attributes'] = $req;
				If (isset($req['@attributes']) && isset($req['@attributes']['name']) && $req['@attributes']['className'] != 'SeedPackage') {
					$seedc = Units_GetCodeByName($req['@attributes']['name']);
					@$mastered = $maststar[$seedc] == $req['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
					echo Units_GetRealnameByName($req['@attributes']['name']) . $mastered . '<br />';
				}
				if (isset($req[0]['@attributes'])) {
					foreach ($req as $req2) {
						if (!isset($req2['@attributes']['name']) || $req2['@attributes']['className'] == 'SeedPackage') continue;
						$seedc = Units_GetCodeByName($req2['@attributes']['name']);
						if ($req2['@attributes']['name'] == $seed['name']) echo '(Hybrid) ';
						@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
						echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
					}
				}
				if (isset($req['requirement'])) {
					foreach ($req as $req2) {
						if ($req2['@attributes']['className'] == 'SeedPackage') continue;
						$seedc = Units_GetCodeByName($req2['@attributes']['name']);
						@$mastered = $maststar[$seedc] == $req2['@attributes']['level'] ? ' - <font color="green"><b>Mastered</b></font>' : '';
						echo Units_GetRealnameByName($req2['@attributes']['name']) . $mastered . '<br />';
					}
				}
			}
		} else { echo '&nbsp;'; }
		echo '</small></td>';
		// ******************** START Override and Default cell*************************
		echo '<td width="70" align="center"><div style="position:relative;z-index:1; text-align:left;">';
		// ******************** Override DIV content *************************
		echo '<div id="' . $seed['realname'] . 'OverNoMastery" class="doOverride" style="display:none;">';
		echo '<div><b>Override seed:<br />' . $seed['realname'] . '</b></div>';
		echo '<form method="post">';
		echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
		echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
		echo '<div class="divPopup"><input type="checkbox" name="DNH_' . $seed['name'] . '" />&nbsp;&nbsp;<span>Do Not Harvest</span></div>';
		echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
		echo '<div class="divPopup"><input type="submit" name="overridesave" value="Save" />&nbsp;<input type="button" value="Cancel" onclick="showhide(\'' . $seed['realname'] . 'OverNoMastery\')" /></div>';
		echo '</form>';
		echo '</div>';
		// ******************** Default DIV content *************************
		echo '<div id="' . $seed['realname'] . 'DefaultNoMastery" class="doDefault" style="display:none;">';
		echo '<div><b>Default seed:<br />' . $seed['realname'] . '</b></div>';
		echo '<form method="post">';
		echo '<input type="hidden" name="userId" value="' .  $_SESSION['userId'] . '" />';
		echo '<input type="hidden" name="seedname" value="' . $seed['name'] . '">';
		echo '<div class="divPopup"><input type="text" name="NTP_' . $seed['name'] . '" size="5" />&nbsp;&nbsp;<span># to Plant</span></div>';
		echo '<div class="divPopup"><input type="submit" name="defaultsave" value="Save" />&nbsp;<input type="button" value="Cancel" onclick="showhide(\'' . $seed['realname'] . 'DefaultNoMastery\')" /></div>';
		echo '</form>';
		echo '</div>';
		// ******************** buttons *************************
		echo '<input type="button" value="Override" name="addover" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'OverNoMastery\')" title="click to show/hide options"><br />';
		echo '<input type="button" value="Default" name="adddefault" style="width: 65px;" onclick="showhide(\'' . $seed['realname'] . 'DefaultNoMastery\')" title="click to show/hide options">';
		echo '<div style="text-align:right; margin-top:5px;"><a href="#top" title="scroll to top"><img src="img/top.gif" title="back to top" border="0"/></a></td>';
		// ******************** END Override and Default cell*************************
		echo '</tr>';
	}

	?>
</table>
</div>
<!--Crafting Tab-->
<div class="tabbertab">
<h2>Crafting</h2>
<div class="mainBlueTitle"><b>Grow For Crafts:</b></div>
<form method="post" name="craftingTable"><input type="hidden"
	name="userId" value="<?= $_SESSION['userId']; ?>" /> <!-- need this to adjust javascript style of crafting table, did not find any other way yet -->
<div style="display: none;"><input name="craftgood" type="radio"
	value="0" /></div>
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
<?php
$crafts = $fvM->fvCraftingUnits();
foreach ($crafts as $key=>$craft)
{
	$build = Units_GetByField('craftType', $craft[0]['subtype']);
	$bkey = array_keys($build);
	$x = 0;
	echo '<tr class="rowTitle"><td colspan="2"><b>' . $key . '</b><br /></td></tr>';
	echo '<tr><td><img src="/' . $build[$bkey[0]]['iconurl'] . '.40x40.jpeg" border="0" /></td>';
	echo '<td><table width="100%"><tr> ';
	foreach ($craft as $icraft)
	{
		if ($x/6 == intval($x/6)) echo '</tr><tr>';
		$anichk = ($fvM->settings['craft'] == $icraft['code']) ? 'CHECKED' : '';
		$craftStylechk = '';
		$craftStylechk = $anichk == 'CHECKED' ? 'style="background-color:#FF7D1A; color: #FFFFFF;"' : 'style="background-color:#E1E1E1;color: #000000;"';
		echo '<td nowrap="nowrap" class="craftName" ' . @$craftStylechk . '><input type="radio" name="craftgood" value="' . $icraft['code'] . '" ' . @$anichk . '  onclick="javascript:bkgColorRadio(\'craftingTable\');" /><small>'. $icraft['realname'] . '</small></td>';
		$x++;
	}
	echo '</tr></table></td>';
	echo '</tr><tr><td colspan="2">&nbsp;</td></tr>';
}
?>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="craftset" value="Save Crafting"></td>
	</tr>
</table>
</form>
</div>
<!--Co-Op Tab-->
<div class="tabbertab">
<h2>Co-Ops</h2>
<div class="mainBlueTitle"><b>Grow For Co-Ops:</b></div>
<form method="post" name="coopTable"><input type="hidden" name="userId"
	value="<?= $_SESSION['userId']; ?>" /> <!-- need this to adjust javascript style of coop table, did not find any other way yet -->
<div style="display: none;"><input name="quests" type="radio" value="0" /></div>
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
	<tr>
	<?php
	$quests = $fvM->fvQuestUnits();
	$x = 0;
	foreach ($quests as $key=>$quest)
	{
		if ($x/4 == intval($x/4)) echo '</tr><tr>';
		$anichk = ($fvM->settings['coop'] == $quest['id']) ? 'CHECKED' : '';
		$coopStylechk = '';
		$coopStylechk = $anichk == 'CHECKED' ? 'style="background-color:#FF7D1A; color: #FFFFFF;"' : 'style="background-color:#E1E1E1;color: #000000;"';
		echo '<td nowrap="nowrap" class="questName" ' . @$coopStylechk . '><input type="radio" name="quests" value="' . $quest['id'] . '" ' . @$anichk . ' onclick="javascript:bkgColorRadio(\'coopTable\');" /><small>'. $key . '</small></td>';
		$x++;
	}
	?>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="questset" value="Save Co-Ops"></td>
	</tr>
</table>
</form>
</div>
</div>
</div>
<!--Harvesting Tab-->
<div class="tabbertab">
<h2>Animals</h2>
<!-- ********************************* transform animals ******************************************* -->
<div class="mainBlueTitle"><b>Transform These Animals:</b></div>
<div>&nbsp;</div>
<form method="post"><input type="hidden" name="userId"
	value="<?= $_SESSION['userId']; ?>" />
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
	<tr>
	<?php
	$animals = $fvM->fvAnimalUnits('transform');
	$transset = $fvM->fvGetSettings2('transform');
	$x = 0;
	foreach ($animals as $animal)
	{
		if ($x/6 == intval($x/6)) echo '</tr><tr>';
		//@$dgrabchk = ($grabs[$fbid][$cat['knownlinks_name']] == 1) ? 'checked' : '';
		$anichk = isset($transset[$animal['name']]) ? 'CHECKED' : '';
		$transformStylechk = '';
		$transformStylechk = $anichk == 'CHECKED' ? 'style="background-color:#B7DFFD"' : 'style="background-color:#E1E1E1"';
		echo '<td nowrap="nowrap" class="animalName" ' . @$transformStylechk . '><input type="checkbox" name="transform[' . $animal['name'] . ']" value="1" ' . @$anichk . ' onclick="javascript:bkgColor(this);"  /><small>'. $animal['realname'] . '</small></td>';
		$x++;
	}
	?>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="animaltset" value="Save Animal Transforms"></td>
	</tr>
</table>
</form>
<!-- ********************************* harvest animals ******************************************* -->
<div class="mainBlueTitle"><b>Harvest These Animals:</b></div>
<div>&nbsp;</div>
<form method="post" name="harvestAnimals"><input type="hidden"
	name="userId" value="<?= $_SESSION['userId']; ?>" />
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Select animals by groups:</b></td>
	</tr>
	<tr>
		<td colspan=6 align="left"><input type="button" name="allAnimal"
			value="check all animals" onclick="javascript:allAnimals('animal');">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="button"
			name="allAnimal" value="uncheck all animals"
			onclick="javascript:noAnimals('animal');"></td>
	</tr>
	<!-- ******************* horses selection ********************* -->
	<tr>
		<td colspan=6 align="left" id="horseCol"
		<?php if(isset($_POST['myHorse'])){ echo "style='background-color:#FB8383'";}else{echo "style='background-color:#E1E1E1'";}  ?>>
		<span>select animals which have the word "Horse","Stallion" or "Pony"
		in their Name and not "Foal"</span> <input type="checkbox"
			name="myHorse"
			onclick="javascript:allAnimalsHorse('animal','Horse', 'Stallion','Pony');"
			<?php if(isset($_POST['myHorse'])) echo "checked"; ?> /></td>
	</tr>
	<!-- ******************* foals selection ********************* -->
	<tr>
		<td colspan=6 align="left" id="foalCol"
		<?php if(isset($_POST['myFoal'])) {echo "style='background-color:#FDCDB7'";}else{echo "style='background-color:#E1E1E1'";}  ?>>
		<span>select animals which have the word "Foal" in their Name</span> <input
			type="checkbox" name="myFoal"
			onclick="javascript:allAnimalsFoal('animal','Foal');"
			<?php if(isset($_POST['myFoal'])) echo "checked"; ?> /></td>
	</tr>
	<!-- ******************* cows selection ********************* -->
	<tr>
		<td colspan=6 align="left" id="cowCol"
		<?php if(isset($_POST['myCow'])) {echo "style='background-color:#B7FDC9'";}else{echo "style='background-color:#E1E1E1'";}  ?>>
		<span>select animals which have the word "Cow" in their Name</span> <input
			type="checkbox" name="myCow"
			onclick="javascript:allAnimalsCow('animal','Cow');"
			<?php if(isset($_POST['myCow'])) echo "checked"; ?> /></td>
	</tr>
	<!-- ******************* calf selection ********************* -->
	<tr>
		<td colspan=6 align="left" id="calfCol"
		<?php if(isset($_POST['myCalf'])) {echo "style='background-color:#F6B7FD'";}else{echo "style='background-color:#E1E1E1'";} ?>>
		<span>select animals which have the word "Calf" in their Name</span> <input
			type="checkbox" name="myCalf"
			onclick="javascript:allAnimalsCalf('animal','Calf');"
			<?php if(isset($_POST['myCalf'])) echo "checked"; ?> /></td>
	</tr>
	<!-- ******************* chicken selection ********************* -->
	<tr>
		<td colspan=6 align="left" id="chickenCol"
		<?php if(isset($_POST['myChicken'])) {echo "style='background-color:#FDFBB7'";}else{echo "style='background-color:#E1E1E1'";}  ?>>
		<span>select animals which have the word "Chicken" in their Name</span>
		<input type="checkbox" name="myChicken"
			onclick="javascript:allAnimalsChicken('animal','Chicken');"
			<?php if(isset($_POST['myChicken'])) echo "checked"; ?> /></td>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="animalset" value="Save Animals"></td>
	</tr>
	<tr>
		<td colspan=6 align="left">&nbsp;</td>
	</tr>
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Single animal selection:</b></td>
	</tr>
	<tr>
	<?php
	$animals = $fvM->fvAnimalUnits('harvest');
	$animset = $fvM->fvGetSettings2('animal');
	$x = 0;
	foreach ($animals as $animal)
	{
		if ($x/6 == intval($x/6)) echo '</tr><tr>';
		$anichk = isset($animset[$animal['name']]) ? 'CHECKED' : '';
		$myanimstyle = '';
		$myanimstyle = $anichk == 'CHECKED' ? 'style="background-color:#B7DFFD"' : 'style="background-color:#E1E1E1"';
		$myanimstyle = $anichk == 'CHECKED' && stripos($animal['realname'],'Foal') === false && (stripos($animal['realname'],'Horse') !== false || stripos($animal['realname'],'Stallion') !== false || stripos($animal['realname'],'Pony') !== false) ? "style='background-color: #FB8383;'" : $myanimstyle;
		$myanimstyle = $anichk == 'CHECKED' && stripos($animal['realname'],'Foal') !== false ? "style='background-color: #FDCDB7;'" : $myanimstyle;
		$myanimstyle = $anichk == 'CHECKED' && stripos($animal['realname'],'Cow') !== false ? "style='background-color: #B7FDC9;'" : $myanimstyle;
		$myanimstyle = $anichk == 'CHECKED' && stripos($animal['realname'],'Calf') !== false ? "style='background-color: #F6B7FD;'" : $myanimstyle;
		$myanimstyle = $anichk == 'CHECKED' && stripos($animal['realname'],'Chicken') !== false ? "style='background-color: #FDFBB7;'" : $myanimstyle;
		echo '<td nowrap="nowrap" class="animalName" id="'. $animal['realname'] . '" ' . @$myanimstyle . '><input type="checkbox" name="animal[' . $animal['name'] . ']" value="1" ' . @$anichk . ' onclick="javascript:bkgColor(this);"  /><small>'. $animal['realname'] . '</small></td>';
		$x++;
	}
	?>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="animalset" value="Save Animals"></td>
	</tr>
</table>
</form>

</div>
<!--Trees Tab-->
<div class="tabbertab">
<h2>Trees</h2>
<div class="mainBlueTitle"><b>Harvest These Trees:</b></div>
<div>&nbsp;</div>
<form method="post" name="harvestTrees"><input type="hidden"
	name="userId" value="<?= $_SESSION['userId']; ?>" />
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Select/unselect all trees:</b></td>
	</tr>
	<tr>
		<td colspan=6 align="left"><input type="button" name="allMyTrees"
			value="check all trees" onclick="javascript:allTrees('tree');">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="button"
			name="allMyTrees" value="uncheck all trees"
			onclick="javascript:noTrees('tree');"></td>
	</tr>
	<tr>
		<td colspan=6 align="left">&nbsp;</td>
	</tr>
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Single tree selection:</b></td>
	</tr>
	<tr>
	<?php
	$trees = $fvM->fvTreeUnits();
	$treeset = $fvM->fvGetSettings2('tree');
	$x = 0;
	foreach ($trees as $tree)
	{
		if ($x/6 == intval($x/6)) echo '</tr><tr>';
		$anichk = isset($treeset[$tree['name']]) ? 'CHECKED' : '';
		$treesStyleChk = '';
		$treesStyleChk = $anichk == 'CHECKED' ? 'style="background-color:#B7DFFD"' : 'style="background-color:#E1E1E1"';
		echo '<td nowrap="nowrap" class="treeName" ' . @$treesStyleChk . '><input type="checkbox" name="tree[' . $tree['name'] . ']" value="1" ' . @$anichk . ' onclick="javascript:bkgColor(this);"  /><small>'. $tree['realname'] . '</small></td>';
		$x++;
	}
	?>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="treeset" value="Save Trees"></td>
	</tr>
</table>
</form>
</div>
<!--Buildings Tab-->
<div class="tabbertab">
<h2>Buildings</h2>
<div class="mainBlueTitle"><b>Harvest These Buildings:</b></div>
<div>&nbsp;</div>
<form method="post" name="harvestBuildings"><input type="hidden"
	name="userId" value="<?= $_SESSION['userId']; ?>" />
<table border=0 cellpadding="2" cellspacing="3" class="selectTable">
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Select/unselect all buildings:</b></td>
	</tr>
	<tr>
		<td colspan=6 align="left"><input type="button" name="allMyBuildings"
			value="check all buildings"
			onclick="javascript:allBuildings('harvest');">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="button"
			name="allMyBuildings" value="uncheck all buildings"
			onclick="javascript:noBuildings('harvest');"></td>
	</tr>
	<tr>
		<td colspan=6 align="left">&nbsp;</td>
	</tr>
	<tr class="rowTitle">
		<td colspan=6 align="left"><b>Single building selection:</b></td>
	</tr>
	<tr>
	<?php
	$buildings = $fvM->fvBuildingUnits();
	$buildset = $fvM->fvGetSettings2('build');
	$x = 0;
	foreach ($buildings as $building)
	{
		if ($x/4 == intval($x/4)) echo '</tr><tr>';
		//@$dgrabchk = ($grabs[$fbid][$cat['knownlinks_name']] == 1) ? 'checked' : '';
		$anichk = isset($buildset[$building['name']]) ? 'CHECKED' : '';
		$buildingsStyleChk = '';
		$buildingsStyleChk = $anichk == 'CHECKED' ? 'style="background-color:#B7DFFD"' : 'style="background-color:#E1E1E1"';
		echo '<td nowrap="nowrap" class="buildingName" ' . @$buildingsStyleChk . '><input type="checkbox" name="harvest[' . $building['name'] . ']" value="1" ' . @$anichk . ' onclick="javascript:bkgColor(this);" /><small>'. $building['realname'] . ' (' . $building['name'] . ')</small></td>';
		$x++;
	}
	?>
	</tr>
	<tr>
		<td colspan=6 align="center" class="mainBlueTitle"><input
			type="submit" name="buildset" value="Save Buildings"></td>
	</tr>
</table>
</form>
</div>

</div>
</body>
</html>

	<?php
	unset($fvM);
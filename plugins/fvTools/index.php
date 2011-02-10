<?php require_once '../../fB_PluginAPI.php'; 
define('fvTools_version', file_get_contents('fvTools.ver'));
define('fvTools_URL', '/plugins/fvTools/index.php');
define('fvTools_Path', getcwd() . '\\');
// file definitions
define('fvTools_Main', 'fvTools_main.sqlite');
define('fvTools_World', 'fvTools_world.sqlite');
define('fvTools_Units', 'fvTools_units.sqlite');
/******************fvTools by RadicalLinux****************************/
include 'includes/fvTools.class.php';
$fvM = new fvTools('formload');
if (isset($_POST['fgbuildings']) || isset($_POST['ftbuilder'])) {
	$fvM->fvDoSettings($_POST);
}
if (isset($_POST['fgwater'])) {
	$fvM->fvDoExtraSettings($_POST);
}
if (isset($_POST['ftFarmLimits'])) {
	$fvM->fvDoSettings($_POST);
}
if (isset($_POST['fthybrids'])) {
	$fvM->fvDoSettings($_POST);
}
fBAcctHeader();
$fgsettings = unserialize($fvM->fvGetSettings('fgbuildings'));
$fbsettings = unserialize($fvM->fvGetSettings('ftbuilder'));
$fgwater = $fvM->fvGetSettings('fgwater');
$fgwaterl = $fvM->fvGetSettings('fgwaterl');
$fgfert = $fvM->fvGetSettings('fgfert');
$fgdelt = $fvM->fvGetSettings('deltrees');
$fthybrids = unserialize($fvM->fvGetSettings('fthybrids'));
$fgtrees = unserialize($fvM->fvGetSettings('trees'));
$fbFarmLimits = unserialize($fvM->fvGetSettings('ftFarmLimits'));
$greenhouses = GetObjectsByName('greenhousebuildable_finished');
$gRealname = Units_GetRealnameByName('greenhousebuildable_finished');
$bsStats = unserialize(fBGetDataStore('bsstats'));
$allhybrids = $bsStats['breedingFeatures']['farm']['greenhousebuildable_finished']['unlockStates'];
?>

<head>
<link rel="stylesheet" type="text/css" href="css/index.css" />
<link rel="stylesheet" type="text/css" href="css/fvTools.css" />
<script type="text/javascript" src="js/fvTools.js"></script>
<script type="text/javascript" src="js/tabber.js"></script>
<script type="text/javascript">
function showhide(id){
	if (document.getElementById){
		obj = document.getElementById(id);
			if (obj.style.display == "none"){
			obj.style.display = "";
		} else {
			obj.style.display = "none";
		}
	}
}
</script>
</head>
<body>
<?php

?>
<h1>fvTools V<?php echo fvTools_version; ?></h1>
<br />
<div class="tabber" id="t"><!--Seeding Tab-->
<div class="tabbertab">
<h2>Breeding/FarmGold</h2>
<br />
<i>Check <u>Harvest Last Item</u> if the Building Does Not Support Ripening<br />
(This will not make the building ripe, but will get you the extra coins)</i><br />
<br />
<i>Checking <u>Reverse Order</u> will place the ripe animal in the building<br />
first instead of last</i><br />
<br />
<i>Checking <u>Leave Item in Building</u> will keep one item in the building<br />
to maintain the ripe time</i><br />
<font color="red"><b>Not All Building Listed Have Items or Have Items to Move</b></font>
<form method="post" name="fvTool">
<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>" />
<table border="1" class="mainTable">
	<tr id="header">
		<td align="center"><b>Building</b></td>
		<td align="center"><b>ID</b></td>
		<td align="center"><b>Number in<br />
		Building</b></td>
		<td align="center"><b>Contents</b></td>
		<td align="center"><b>Enable</b> <input type="checkbox" name="enable" onClick="javascript:checkAll('[enable]','enable');" title="check/unckeck All"
			<?php if(isset($_POST['enable'])) echo "checked"; ?> /></td>
		<td align="center"><b>Reverse<br />
		Order</b> <input type="checkbox" name="reverse" onClick="javascript:checkAll('[reverse]','reverse');" title="check/unckeck All"
			<?php if(isset($_POST['reverse'])) echo "checked"; ?> /></td>
		<td align="center"><b>Harvest<br />
		Building</b> <input type="checkbox" name="harvest" onClick="javascript:checkAll('[harvest]','harvest');" title="check/unckeck All"
			<?php if(isset($_POST['harvest'])) echo "checked"; ?> /></td>
		<td align="center"><b>Harvest<br />
		Last Item</b> <input type="checkbox" name="lastitem" onClick="javascript:checkAll('[lastitem]','lastitem');" title="check/unckeck All"
			<?php if(isset($_POST['lastitem'])) echo "checked"; ?> /></td>
		<td align="center"><b>Leave Item<br />
		in Building</b> <input type="checkbox" name="leaveitem" onClick="javascript:checkAll('[leaveitem]','leaveitem');" title="check/unckeck All"
			<?php if(isset($_POST['leaveitem'])) echo "checked"; ?> /></td>	
		<td align="center"><b>Only Move<br />
		One Item</b> <input type="checkbox" name="onlyone" onClick="javascript:checkAll('[onlyone]','onlyone');" title="check/unckeck All"
			<?php if(isset($_POST['onlyone'])) echo "checked"; ?> /></td>						
		<td align="center"><b>Number of<br />
		Times</b></td>
	</tr>
	<?php
	$buildings = $fvM->fvGetBuildings();
	foreach ($buildings as $building)
	{
		echo '<tr>';
		$test = '';
		$cnt = 0;
		foreach (unserialize($building['myworld_contents']) as $contents)
		{
			if ($contents['numItem'] == 0) continue;
			$item2 = Units_GetUnitByCode($contents['itemCode']);
			$test .= $contents['numItem'] . ' - ' . $item2['realname'] . '<br>';
			$cnt = $cnt + $contents['numItem'];
		}
		echo '<td>' . $building['myworld_itemRealName'] . '</td>';
		echo '<td align="center">' . $building['myworld_id'] . '</td>';
		echo '<td align="center">' . $cnt . '</td>';

		@$echecked = $fgsettings[$building['myworld_id']]['enable'] == 1 ? 'CHECKED' : '';
		@$ochecked = $fgsettings[$building['myworld_id']]['reverse'] == 1 ? 'CHECKED' : '';
		@$hchecked = $fgsettings[$building['myworld_id']]['harvest'] == 1 ? 'CHECKED' : '';
		@$mchecked = $fgsettings[$building['myworld_id']]['lastitem'] == 1 ? 'CHECKED' : '';
		@$lchecked = $fgsettings[$building['myworld_id']]['leaveitem'] == 1 ? 'CHECKED' : '';
		@$oochecked = $fgsettings[$building['myworld_id']]['onlyone'] == 1 ? 'CHECKED' : '';
		$cycles = !isset($fgsettings[$building['myworld_id']]['cycles']) ? 0 : $fgsettings[$building['myworld_id']]['cycles'];
		?>
	<td>
	<div style="cursor: pointer; color: blue;"
		onclick="showhide('<?= $building['myworld_id']; ?>');">Show/Hide</div>
	<div id="<?= $building['myworld_id']; ?>" style="display: none;"><?= $test; ?></div>
	</td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[enable]" <?= $echecked; ?> /></td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[reverse]" <?= $ochecked; ?> /></td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[harvest]" <?= $hchecked; ?> /></td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[lastitem]" <?= $mchecked; ?> /></td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[leaveitem]" <?= $lchecked; ?> /></td>
	<td align="center"><input type="checkbox" name="<?= $building['myworld_id']; ?>[onlyone]" <?= $oochecked; ?> /></td>
	<td align="center"><input type="text" name="<?= $building['myworld_id']; ?>[cycles]" size=4 value="<?= $cycles; ?>" /></td>
	</tr>
	<?php
	}
	?>
	<tr id="footer">
		<td colspan="11" align="center"><input type="submit" name="fgbuildings" value="Save Settings" /></td>
	</tr>
</table>
</form>
</div>
<!-- Builder Tab -->
<div class="tabbertab">
<h2>Builder</h2>
<form method="post" name="fvBuilder">
<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>" />
	<?php
	$units = Units_GetByType('building',true);
	echo '<table border="1" class="mainTable">';
	echo '<tr id="footer">';
	echo '<td colspan="4" align="center"><input type="submit" name="ftbuilder" value="Save Settings" /></td>';
	echo '</tr>';	
	echo '<tr id="header">';
	echo '<td align="center">Image</td>';
	echo '<td align="center"><b>Building</b></td>';
	echo '<td align="center"><b>Requires</b></td>';?>
	<td align="center">
		<b>Build/Upgrade</b>
		<input type="checkbox" name="builder" onClick="javascript:checkAllBuilder('builder','builder');" title="check/unckeck All" <?php if(isset($_POST['builder'])) echo "checked"; ?> />
	</td></tr>	
	<?php 
	foreach ($units as $unit)
	{
		if ($unit['name'] == 'mysteryseedling') continue;
		$bcheck = isset($fbsettings[$unit['name']]) ? 'CHECKED' : '';
		if (!isset($unit['upgrade']) && !isset($unit['matsNeeded'])) continue;
		echo '<tr>';
		echo '<td><img src="/' . $unit['iconurl'] . '.40x40.jpeg' . '"></td>';
		echo '<td><b>' . $unit['realname'] . '</b> (' . $unit['name'] . ')</td>'; ?>
		<td><div style="cursor: pointer; color: blue;" onclick="showhide('<?= $unit['name'] . '_builder'; ?>');">Show/Hide</div>
		<div id="<?= $unit['name'] . '_builder'; ?>" style="display: none;">
	<?php 
		if (isset($unit['features'])) {
			$features = unserialize($unit['features']);
			foreach ($features as $feature)
			{
				foreach ($feature as $feature2)
				{
					foreach ($feature2['upgrade'] as $upgrade)
					{
						echo '--------Level: ' . $upgrade['@attributes']['level'] . '--------<br />';
						@$capacity = $upgrade['@attributes']['capacity'];
						if ($capacity > 0) echo 'Capacity: ' . $upgrade['@attributes']['capacity'] . '<br />';
						foreach ($upgrade['part']  as $part)
						{
							echo Units_GetRealnameByName($part['@attributes']['name']) . ' - ' . $part['@attributes']['need'] . '<br />';
						}
					}
				}
			}
			echo '</div></td>';
			echo '<td align="center"><input type="checkbox" name="builder[]" value="' . $unit['name'] . '" ' . $bcheck . ' /></td>';
			echo '</tr>';
			continue;
		}
		if (isset($unit['upgrade'])) {
			$upgrades = unserialize($unit['upgrade']);
			foreach ($upgrades as $upgrade)
			{
				echo '--------Level: ' . $upgrade['@attributes']['level'] . '--------<br />';
				@$capacity = $upgrade['@attributes']['capacity'];
				if ($capacity > 0) echo 'Capacity: ' . $upgrade['@attributes']['capacity'] . '<br />';
				foreach ($upgrade['part']  as $part)
				{
					echo Units_GetRealnameByName($part['@attributes']['name']) . ' - ' . $part['@attributes']['need'] . '<br />';
				}
			}
			echo '</div></td>';
			echo '<td align="center"><input type="checkbox" name="builder[]" value="' . $unit['name'] . '"' . $bcheck . ' /></td>';
			echo '</tr>';			
			continue;
		}
		if (isset($unit['matsNeeded'])) {
			$itemClass = unserialize($unit['storageType']);
			$items = Storage_GetByName($itemClass['@attributes']['itemClass']);
			$parts = unserialize($items['itemName']);
			if (isset($parts['part']) && $parts['part'] == 'true') {
				echo Units_GetRealnameByName($parts['value']) . ' - ' . $parts['need'] . '<br />';
			} else {
				foreach ($parts as $part)
				{
					If ($part['part'] == 'true') {
						echo Units_GetRealnameByName($part['value']) . ' - ' . $part['need'] . '<br />';
					}

				}
			}
		}
		echo '</div></td>';
		echo '<td align="center"><input type="checkbox" name="builder[]" value="' . $unit['name'] . '"' . $bcheck . ' /></td>';
		echo '</tr>';		
	}
	?>
	<tr id="footer">
		<td colspan="4" align="center"><input type="submit" name="ftbuilder" value="Save Settings" /></td>
	</tr>	
	</table>
	</form>
	</div>
<!-- Extras Tab -->
<div class="tabbertab">
<h2>Extras</h2>
<form method="post"name="fvWaterTrees">
<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>" />
	<?php 
@$waterchk = $fgwater == 1 ? 'CHECKED' : '';
@$waterlchk = $fgwaterl == 1 ? 'CHECKED' : '';
@$fertchk = $fgfert == 1 ? 'CHECKED' : '';
@$deltreechk = $fgdelt == 1 ? 'CHECKED' : '';
?> 
<div class="rowTitle">
	<input type="checkbox" name="watertrees" <?= $waterchk; ?> />&nbsp;<b>Water Trees (Check Trees To Water) - Colored Are Mastered</b><br />
</div>
<div class="rowTitle">
	<input type="checkbox" name="waterless" <?= $waterlchk; ?> />&nbsp;<b>Don't Completely Water (Waters 1 Less for Manual Completion)</b><br />
</div>
<div class="rowTitle">
	<input type="checkbox" name="deltrees" <?= $deltreechk; ?> />&nbsp;<b>Delete Unchecked Tree Seedlings (Runs Once Than Turns Off)</b><br />
</div>
<div class="rowTitle">
	<input type="checkbox" name="trees" onClick="javascript:checkAllTreesToWater('tree','trees');" title="check/unckeck All" <?php if(isset($_POST['trees'])) echo "checked"; ?> />&nbsp;<b>Check/Uncheck All Trees</b><br />
</div>
<div class="rowTitle">
	<input type="checkbox" name="treesnomaster" onClick="javascript:checkAllTreesNoMaster('tree','treesnomaster','unmastered');" title="check/unckeck All" <?php if(isset($_POST['treesnomaster'])) echo "checked"; ?> />&nbsp;<b>Check/Uncheck Unmastered Trees</b><br />
</div>
<br/>
<table>
<tr>
<?php 
$trees = Units_GetByType('tree');
$trees = $fvM->subval_sort($trees, 'realname');
$cnt = 0;
$maststar = unserialize(fBGetDataStore('cropmasterycnt'));
foreach ($trees as $tree)
{
	if ($tree['code'] == 'XX') continue;
	if ($cnt / 5 == intval($cnt/5)) echo '</tr><tr>';
	@$treechk = isset($fgtrees[$tree['code']]) ? 'CHECKED' : '';
	$tcolor = 'id="unmastered"';
	if (isset($maststar[$tree['code']]) && $maststar[$tree['code']] == 2) {
		$tcolor = 'style="background-color:lightblue;" ';
	}
	echo '<td ' . $tcolor . '><input type="checkbox" name="tree[' . $tree['code'] . ']"' . $treechk . ' />&nbsp;' . $tree['realname'] . '</td>';
	$cnt++;
}
?>
</tr>
</table>
<br /><br />
<input type="checkbox" name="fertcrops" <?= $fertchk; ?> />&nbsp;<b>Fertilize Crops if More Than 25% Unfertilized</b><br />
<br />
<input type="submit" name="fgwater" value="Save Settings" /></form>
</div>

<!-- Seedlings Tab -->
<div class="tabbertab">
<h2>Seedlings</h2>
<table border="1" class="mainTable"> 
<tr id="footer"><th>Image</th><th>Name</th><th>X</th><th>Y</th></tr>
<?php 
$seedlings = GetObjectsByName('mysteryseedling');
$seedlings = $fvM->subval_sort($seedlings, 'seedType');
foreach ($seedlings as $seedl)
{
	$uInfo = Units_GetUnitByCode($seedl['seedType']);
	$iconurl = $uInfo['iconurl'] . '.40x40.jpeg';
	echo '<tr>';
	echo '<td><img src="/' . $iconurl . '"></td>';
	echo '<td>' . $uInfo['realname'] . '</td>';
	echo '<td>' . $seedl['position']['x'] . '</td>';
	echo '<td>' . $seedl['position']['y'] . '</td>';
	echo '</tr>';
}

?>
</table>
</div>
<!-- Seedlings Tab -->
<div class="tabbertab" id="farmlimits">
<h2>Farm Limits</h2>
<font color="red"><b><big>WARNING!!!  IF YOU PUT IN 0 ALL OF THAT ITEM WILL BE SOLD</big></b></font><br />
<font color="purple"><b>---Leave Keep Field Blank If You Want To Keep All The Items---</b></font><br />
<font color="blue"><b>---This Only Applies to Items on Your Farm---</b></font>
<form method="post" name="farmlimits">
<input type="hidden" name="userId" value="<?= $_SESSION['userId'] ?>">
<table border="1" class="mainTable"> 
<tr id="footer"><th>Image</th><th>Name</th><th>Keep</th></tr>
<?php 
$objects = GetObjects();
$tmpset = $fbFarmLimits;
foreach ($objects as $object) 
{
	$uInfo = Units_GetUnitByName($object['itemName']);
	if (isset($tmpkeys[$uInfo['code']]) && $object['className'] != 'MysterySeedling') continue;
	if (isset($tmpkeys['tree_' . $object['seedType']]) && $object['className'] == 'MysterySeedling') continue;
	if ($object['className'] == 'Plot') continue;
	if ($object['className'] != 'MysterySeedling') {
		if (isset($tmpset[$uInfo['code']])) unset($tmpset[$uInfo['code']]);
		$tmpkeys[$uInfo['code']] = 0;
	} else {
		if (isset($tmpset['tree_' . $object['seedType']])) unset($tmpset['tree_' . $object['seedType']]);
		$tmpkeys['tree_' . $object['seedType']] = 0;
	}
	$newobjects[] = array_merge($object,$uInfo);
}
foreach ($tmpset as $code=>$tmp)
{
	if (substr($code,0, 5) == 'tree_') {
		$uInfo = Units_GetUnitByName('mysteryseedling');
		$uInfo['seedType'] = str_replace('tree_', '', $code);
		$newobjects[] = $uInfo;
	} else {
		$newobjects[] = Units_GetUnitByCode($code);
	}
}
echo '<tr><td colspan=3 align="center"><input type="submit" value="Save" name="ftFarmLimits"></td></tr>';
$newobjects = $fvM->subval_sort($newobjects, 'className');
foreach ($newobjects as $object)
{
	$iconurl = $object['iconurl'] . '.40x40.jpeg';
	echo '<tr>';
	echo '<td><img src="/' . $iconurl . '"></td>';
	if ($object['className'] != 'MysterySeedling') {
		echo '<td>' . $object['className'] . ' - <b>' . $object['realname'] . '</b> (' . $object['name'] . ')' . '</td>';
		echo '<td><input type="text" size=5 name="' . $object['code'] . '" value="' . (isset($fbFarmLimits[$object['code']]) ? $fbFarmLimits[$object['code']] : '') . '"></td>';
	} else {
		echo '<td>' . $object['className'] . ' - <b>' . $object['realname'] . '</b> (' . Units_GetRealnameByCode($object['seedType']) . ')' . '</td>';
		echo '<td><input type="text" size=5 name="tree_' . $object['seedType'] . '" value="' . (isset($fbFarmLimits['tree_' . $object['seedType']]) ? $fbFarmLimits['tree_' . $object['seedType']] : '') . '"></td>';
	}
	echo '</tr>';

}
	echo '<tr><td colspan=3 align="center"><input type="submit" value="Save" name="ftFarmLimits"></td></tr>';
?>
</form>
</table>
</div>
<div class="tabbertab" id="greenhouse">
<h2>Greenhouse Seeds</h2>
<font color="purple"><b>Select The Seeds You Want Your Greenhouse to Produce</b></font>
<table border="1" class="mainTable"> 
<?php 
foreach ($greenhouses as $greenhouse)
{
	echo '<form method="post" name="greenhouse">';
	echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
	echo '<input type="hidden" name="fthybrids" value="' . $greenhouse['id'] . '">';
	echo '<tr id="footer"><th colspan=2>' . $gRealname . ' - Level ' . $greenhouse['expansionLevel'] . ' - (ID: ' . $greenhouse['id'] . ')</td></tr>';
	
	for ($x = 0; $x < 8; $x++)
	{
		echo '<tr>';
		echo '<td><b>Option ' . ($x + 1) . '</b></td>'; 
		echo '<td><select name="tray_' . $x . '">';

	foreach ($allhybrids as $hybrid)
	{
		if ($hybrid['unlockState'] == 2) {
			echo '<option value="' . $hybrid['code'] . '" ' . ($fthybrids['tray_' . $x] == $hybrid['code'] ? 'SELECTED' : '') . '>' . Units_GetRealnameByCode($hybrid['code']) . '</option>';
		}
	} 
		echo '</select></td>'; 
		echo '</tr>';
	}
	echo '<tr id="footer"><th colspan=2><input type="submit" value="save"></td></tr>';
	echo '</form>';
}
?>
</table>
</div>
</div>
</body>
</html>

<?php 
unset($fvM); 
?>

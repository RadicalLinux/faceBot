<?php
require_once '../../fB_PluginAPI.php';
define('FarmStats_version', file_get_contents('FarmStats.ver'));
define('FarmStats_date', '24 October 2010');
define('FarmStats_URL', '/plugins/FarmStats/main.php');
define('FarmStats_Path', getcwd() . '\\');
// file definitions
define('FarmStats_Main', 'FarmStats_main.sqlite');
define('FarmStats_World', 'FarmStats_world.sqlite');

include 'includes/FarmStats.class.php';
fBAcctHeader();
$fsM = new FarmStats('formload');
	if (empty($fsM->settings)) {
		echo 'Database is not initialized yet, please allow bot to run a cycle';
		return;
	}
	if(!empty($fsM->error) && $fvM->haveWorld !== false)
	{
		echo $fsM->error;
		return;
	}
	if(isset($_GET) && (count($_GET) > 1))
	{
		if ($_GET['action'] == 'settings') {
			$fsM->fsDoSettings($_GET);
		}
	}
	$fsM->settings = $fsM->fsGetSettings();
	$Bot_path = getcwd();
	global $FarmStats_ImagePath;
	$FarmStats_ImagePath = str_replace("/", "\\", $Bot_path);
	$fsM->settings['wither'] = ($fsM->settings['wither'] == 1) ? 'On' : 'Off';
	$nbrs = unserialize(fBGetDataStore('neighbors'));
	//Counts
	$gb = $fsM->fsGetStorageCntByID(-1);
	$cb = $fsM->fsGetStorageCntByID(-6);
	$st = $fsM->fsGetStorageCntByID(-2);
	$wld = $fsM->fsGetWorldCnt();
	$wld['wCnt'] = ($wld['wCnt'] < 1) ? 0 : $wld['wCnt'] ;
	$aniCnt = $fsM->fsGetWorldCntbyType('animal');
	$aniCnt['wcnt'] = ($aniCnt['wcnt'] < 1) ? 0 : $aniCnt['wcnt'];
	$treeCnt = $fsM->fsGetWorldCntbyType('tree');
	$treeCnt['wcnt'] = ($treeCnt['wcnt'] < 1) ? 0 : $treeCnt['wcnt'];
	$vehCnt = $fsM->fsGetWorldCntbyType('vehicle');
	$vehCnt['wcnt'] = ($vehCnt['wcnt'] < 1) ? 0 : $vehCnt['wcnt'];
	$decoCnt = $fsM->fsGetWorldCntbyType('decoration');
	$decoCnt['wcnt'] = ($decoCnt['wcnt'] < 1) ? 0 : $decoCnt['wcnt'];
	$messCnt = $fsM->fsGetWorldCntbyType('messageSign');
	$messCnt['wcnt'] = ($messCnt['wcnt'] < 1) ? 0 : $messCnt['wcnt'];
	$buildCnt = $fsM->fsGetWorldCntbyType('building');
	$buildCnt['wcnt'] = ($buildCnt['wcnt'] < 1) ? 0 : $buildCnt['wcnt'];
	$plotCnt = $fsM->fsGetWorldCntbyClass('Plot');
	$plotCnt['wcnt'] = ($plotCnt['wcnt'] < 1) ? 0 : $plotCnt['wcnt'];	
	$sbuildCnt = $fsM->fsGetStoreBuildCnt();
	$sbuildCnt['mycount'] = ($sbuildCnt['mycount'] < 1) ? 0 : $sbuildCnt['mycount'];
	$sOtherCnt = $fsM->fsGetStoreOtherCnt();
	$sOtherCnt['stcnt'] = (count($sOtherCnt) < 1) ? 0 : count($sOtherCnt);
	//Do achievements
	$ac = unserialize(fBGetDataStore('ach_count'));
	$achBlue = $achRed = $achWhite = $achYellow = $totribbons = 0;
	$totach = count($ac);
	$specials = $fsM->fsGetFeature();
	$specialbcnt = count(@$specials['building']);
	$specialdcnt = count(@$specials['decoration']);
	foreach($ac as $key=>$stars)
	{
		if ($stars['earned'] == 3) { $achBlue++; }
		if ($stars['earned'] == 2) { $achRed++; }
		if ($stars['earned'] == 1) { $achWhite++; }
		if ($stars['earned'] == 0) { $achYellow++; }
		$totribbons += $stars['earned'];
	}
	//Do mastery
	$mast = unserialize(fBGetDataStore('cropmasterycnt'));
	$mastcnt = unserialize(fBGetDataStore('cropmastery'));
	$mastcompl = $totstars = 0;
	$totmast = count($mast);
	foreach($mast as $key=>$stars)
	{
		if ($stars == 2) { $mastcompl++; }
		$totstars += $stars;
	}
	$superplots = $fsM->fsGetSuperPlots();
	?>
<html>
<head>
<style type="text/css">
body {
	font-family: arial, helvetica, sans-serif;
}

table.crops {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 10px;
}

table.crops td.crops {
	border-width: 1px;
	padding: 2px;
	border-style: outset;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}

table.builds {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 12px;
}

table.builds td.builds {
	border-width: 1px;
	padding: 2px;
	border-style: dashed;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}

table.neighs {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 12px;
}

table.neighs td.neighs {
	border-width: 1px;
	padding: 4px;
	border-style: dashed;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}
</style>
<link rel="stylesheet" type="text/css" href="/plugins/FarmStats/css/index.css" />
<script src="/plugins/FarmStats/js/FarmStats.js"></script>
<script src="/plugins/FarmStats/js/tabber.js"></script>
</head>
<body>
<h1>FarmStats V<?php echo FarmStats_version; ?></h1>
<div class="tabber"
	id="FarmStats"><!--Statistics Tab-->
<div class="tabbertab" id="fsstats">
<h2>Statistics</h2>
<table
	style="text-align: left; width: 100%; height: 100%; background-color: ghostwhite; font-size: 14px;"
	border="0" cellpadding="2" cellspacing="2">
	<tbody>
		<tr>
			<td style="vertical-align: top;" width="30%">Hello <font color="red"><b><?php echo $fsM->settings['uname']; ?></b></font><br>
			<small> Facebook ID is: <?php echo $fsM->userId; ?><br>
			Current Game Version: v<?php echo $fsM->flashRevision; ?><br>
			<br />
			Withering is <font color="green"><b><?php echo $fsM->settings['wither']; ?></b></font><br>
			<br>
			<b>Game Stats</b><br />
			Level: <?php echo $fsM->settings['level']; ?><br />
			Experience: <?php echo number_format($fsM->settings['xp']); ?><br />
			Coins: <?php echo number_format($fsM->settings['gold']); ?><br />
			FV Cash: <?php echo number_format($fsM->settings['coin']); ?><br />
			Neighbors: <?php echo count($nbrs); ?><br>
			<br />
			<b>Farm Information</b><br />
			Unit Size: <?php echo $fsM->settings['wsizeX'] . 'x' . $fsM->settings['wsizeY']; ?>
			(1x1)<br>
			Plot Size: <?php echo floor($fsM->settings['wsizeX']/4) . 'x' . floor($fsM->settings['wsizeY']/4); ?>
			(4x4)<br>
			Tileset: <?php echo ucfirst($fsM->settings['tileset']); ?><br />
			Objects on Farm: <?php echo $wld['wCnt']; ?><br />
			Free Space: <?php echo $fsM->settings['freespace']; ?><br />
			Plots: <?php echo $plotCnt['wcnt']; ?><br />
			Superplots: <?php echo $superplots['plcount']; ?> in <?php echo $superplots['count']; ?>
			plots<br />
			<br />
			<b>Fuel</b><br />
			Fuel Energy: <?php echo number_format($fsM->settings['fuel']); ?><br />
			Fuel Tanks: <?php echo number_format(floor($fsM->settings['fuel']/150)); ?><br />
			<br />
			<b>Achievements</b><br />
			Blue Ribbons: <font color="blue"><b><?php echo $achBlue; ?></b></font><br>
			Red Ribbons: <font color="red"><b><?php echo $achRed; ?></b></font><br>
			White Ribbons: <font color="grey"><b><?php echo $achWhite; ?></b></font><br>
			Yellow Ribbons: <font color="GoldenRod"><b><?php echo $achYellow; ?></b></font><br>
			Total Ribbons: <b><?php echo $totach; ?></b><br>
			<br />
			<b>Masteries</b><br />
			<?php echo $mastcompl; ?> full crop masteries<br>
			<?php echo $totstars; ?> total mastery stars<br>
			<br />
			<b>Storage Info</b><br />
			<?php echo $gb['sCnt']; ?> gifts in your Giftbox.<br>
			<?php echo $cb['sCnt']; ?> consumables in Giftbox.<br>
			<?php echo $st['sCnt']; ?> items in storage.<br>
			<br>
			<b>Miscellaneous</b><br />
			Message Signs: <?php echo $messCnt['wcnt']; ?> </small></td>
			<td colspan="1" rowspan="3" style="vertical-align: top;">
			<table style="text-align: left;" width="100%" class="crops">
				<tbody>
					<tr>
						<td class="crops" width="100%" colspan="6" rowspan="1" style="vertical-align: top;"><?php 
							$seedCnt = $fsM->fsItemCounts('seed');
							$plotCnt = $fsM->fsItemCounts('plot');
							@$seedCnt[0]['mycount'] = ($seedCnt[0]['mycount'] < 1) ? 0 : $seedCnt[0]['mycount'];
							@$plotCnt[0]['mycount'] = ($plotCnt[0]['mycount'] < 1) ? 0 : $plotCnt[0]['mycount'];
							?> <big><b>Crops: </b><?php echo $seedCnt[0]['mycount']; ?> - <b>Empty
						Plots: </b><?php echo $plotCnt[0]['mycount']; ?></big></td>
					</tr>
					<tr style="font-weight: bold;">
						<td class="crops" width="12%" align="center" style="vertical-align: top;"><small>Qty</small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small>Crop</small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small>Planted</small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small>Ripe At</small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small>Time
						Remaining</small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small>% Complete</small></td>
					</tr>
					<?php
					$seeds = $fsM->fsGetWorldSeeds();
					foreach ($seeds as $seed)
					{
						$plantTime = $seed['myworld_plantTime']/1000;
						$uinfo = $fsM->fsGetUnits($seed['myworld_itemCode']);
						$growTime = round(23 * $uinfo['growTime']);
						$ripeTime = $plantTime + ($growTime * 3600);
						$remainTime = $ripeTime - time();
						$leftTime = $fsM->fsMakeTime($remainTime);
						$perTime = 100 - round(($remainTime / ($growTime * 3600)) * 100);
						?>
					<tr>
						<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $seed['scnt']; ?></small></td>
						<td class="crops" style="vertical-align: top;"><small><?php echo $seed['myworld_itemRealName']; ?></small></td>
						<td class="crops" style="vertical-align: top;"><small><?php echo date("m/d/y, g:i a", $plantTime); ?></small></td>
						<td class="crops" style="vertical-align: top;"><small><?php echo date("m/d/y, g:i a", $ripeTime); ?></small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $leftTime; ?></small></td>
						<td class="crops" align="center" style="vertical-align: top;"><small><?php echo $perTime . '%'; ?></small></td>
					</tr>
					<?php
					}
					?>

				</tbody>
			</table>
			<br />
			<table style="text-align: left; width: 100%;" class="builds">
				<tbody>
					<tr>
						<td class="builds" style="vertical-align: top; width: 33%;"><font size="2"><b>Animals:
						</b><?php echo $aniCnt['wcnt']; ?></font></td>
						<td class="builds" style="vertical-align: top; width: 33%;"><font size="2"><b>Trees:
						</b><?php echo $treeCnt['wcnt']; ?></font></td>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Gift/Cons. Box:
						</b><?php echo $gb['sCnt'] + $cb['sCnt']; ?></font></td>
					</tr>
					<tr>
						<!-- Animals -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$animals = $fsM->fsItemCounts('animal');
						foreach ($animals as $animal)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $animal['mycount'] . ' - ' . $animal['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Trees -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$trees = $fsM->fsItemCounts('tree');
						foreach ($trees as $tree)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $tree['mycount'] . ' - ' . $tree['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Gift/Cons Box -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$gb = $fsM->fsGetStorageByID(-1);
						$cb = $fsM->fsGetStorageByID(-6);
						$gcbox = array_merge($gb,$cb);
						foreach ($gcbox as $giftb)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $giftb['storage_itemCount'] . ' - ' . $giftb['storage_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
					</tr>
					<tr>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Buildings: </b><?php echo $buildCnt['wcnt']; ?></font></td>
						<td style="vertical-align: top;"><font size="2"><b>Buildings
						w/Items: </b><?php echo $sOtherCnt['stcnt']; ?></font></td>
						<td style="vertical-align: top;"><font size="2"><b>Storage
						Buildings: </b><?php echo $sbuildCnt['mycount']; ?></font></td>
					</tr>
					<tr>
						<!-- Buildings -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$builds = $fsM->fsGetWorldbyType('building');
						foreach ($builds as $build)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $build['mycount'] . ' - ' . $build['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Buildings w/Items -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$stothers = $fsM->fsGetStoreOther();
						foreach ($stothers as $key=>$stother)
						{
							$winfo = $fsM->fsGetWorldbyID($key);
							echo '<li style="text-indent:-40px; list-style-type: none;"><b><u>' . $winfo['myworld_itemRealName'] . '</u>:</b> ' . $stother['cnt'] . '</li>';
							unset($stother['cnt']);
							foreach ($stother as $stot)
							{
								if ($stot['storage_itemCount'] == 0) continue;
								echo '<li style="text-indent:-30px; list-style-type: none;">' . $stot['storage_itemCount'] . ' - ' . $stot['storage_itemRealName'] . '</li>';
							}
							echo '<br />';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Storage Buildings -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$sbuilds = $fsM->fsGetStoreBuildings();
						foreach ($sbuilds as $sbuild)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $sbuild['mycount'] . ' - ' . $sbuild['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
					</tr>
					<tr>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Items in
						Storage: </b><?php echo $st['sCnt']; ?></font></td>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Vehicles: </b><?php echo $vehCnt['wcnt']; ?></font></td>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Decorations: </b><?php echo $decoCnt['wcnt']; ?></font></td>
					</tr>
					<tr>
						<!-- Items in Storage -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$sto = $fsM->fsGetStorageByID(-2);
						foreach ($sto as $st)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $st['storage_itemCount'] . ' - ' . $st['storage_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Vehicles -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$vehicles = $fsM->fsItemCounts('vehicle');
						foreach ($vehicles as $vehicle)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $vehicle['mycount'] . ' - ' . $vehicle['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Decorations -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						$decos = $fsM->fsItemCounts('decoration');
						foreach ($decos as $deco)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $deco['mycount'] . ' - ' . $deco['myworld_itemRealName'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
					</tr>
					<tr>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Specials:</b> <?php echo $specialbcnt; ?></font></td>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Special Items:</b> <?php echo $specialdcnt; ?></font></td>
						<td class="builds" style="vertical-align: top;"><font size="2"><b>Message Signs:</b> <?php echo $messCnt['wcnt']; ?></font></td>
					</tr>
					<tr>
						<!-- Specials Buildings-->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
						<?php
						foreach ($specials['building'] as $key=>$sb)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;"><b><u>' . $key . ':</b></u></li>';
							echo '<li style="text-indent:-30px; list-style-type: none;">Current: ' . $sb['current'] . '</li>';
							echo '<li style="text-indent:-30px; list-style-type: none;">Total: ' . $sb['received'] . '</li><br />';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- Special Building Items -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
						<?php
						foreach ($specials['decoration'] as $key=>$sb)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;"><b><u>' . $key . ':</b></u></li>';
							echo '<li style="text-indent:-30px; list-style-type: none;">Current: ' . $sb['current'] . '</li>';
							echo '<li style="text-indent:-30px; list-style-type: none;">Total: ' . $sb['received'] . '</li><br />';
						}
						?>
						</ul>
						</div>						
						</td>
						<!-- Messages -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
						<?php
						$messages = $fsM->fsMessages();
						foreach ($messages as $message)
						{
							echo '<li style="text-indent:-40px; list-style-type: none;">&bull; ' . $message['myworld_message'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
					</tr>
					<tr>
						<?php $seedpkgs = unserialize(fBGetDataStore('inseedbox')); ?>
						<td class="builds" style="vertical-align: top; width: 33%;"><font size="2"><b>Seeds:
						</b><?php echo array_sum($seedpkgs); ?></font></td>
						<td class="builds" style="vertical-align: top; width: 33%;"></td>
						<td class="builds" style="vertical-align: top;"></td>
					</tr>
					<tr>
						<!-- Seeds -->
						<td class="builds" style="vertical-align: top;">
						<div style="overflow-y: scroll; height: 245px;">
						<ul>
							<?php
						foreach ($seedpkgs as $key=>$seedpack)
						{
							$uInfo = Units_GetUnitByCode($key);
							echo '<li style="text-indent:-40px; list-style-type: none;">' . $seedpack . ' - ' . $uInfo['realname'] . '</li>';
						}
						?>
						</ul>
						</div>
						</td>
						<!-- EMPTY -->
						<td class="builds" style="vertical-align: top;">
						</td>
						<!-- EMPTY -->
						<td class="builds" style="vertical-align: top;">
						</td>
					</tr>					
				</tbody>
			</table>
			</td>
		</tr>
	</tbody>
</table>
</div>
<!--Mastery Tab-->
<div class="tabbertab" id="fsmaster">
<h2>Masteries</h2>
<div style="background-color: ghostwhite;">
<b>Crop Masteries:</b> <?php echo $totmast; ?>
<table style="text-align: left; width: 100%;" class="builds">
<tr>
	<td class="builds" colspan="2"></td>
	<td class="builds" align="center"><b>Stars</b></td>
	<td class="builds" align="center"><b>Mastery</b></td>
	<td class="builds" align="center"><b>Count</b></td>
	<td class="builds" align="center"><b>Remaining</b></td>
</tr>
<?php
foreach($mast as $key=>$stars)
{
	$uinfo = $fsM->fsGetUnits($key);
	$iconurl = '/' . $uinfo['iconurl'];	
	$starurl = '/plugins/FarmStats/images/star.png';
	if (empty($uinfo['realname'])) { $uinfo['realname'] = 'Missing'; }
	echo '<tr><td class="builds" width="50"><img src="' . $iconurl . '" height="40" alt="" /></td>';
	if ($stars == 2)
	{
		echo '<td class="builds" ><font color="blue">';
	}
	if ($stars == 1)
	{
		echo '<td class="builds" ><font color="red">';
	}
	if ($stars == 0)
	{
		echo '<td class="builds" ><font color="goldenrod">';
	}
	$remain = intval($uinfo['masterymax']) - intval($mastcnt[$key]);
	$remain = ($remain <= 0) ? '<font color="green">Done</font>' : number_format($remain); 
	echo '<b>' . $uinfo['realname'] . '</b></font></td>';
	echo '<td class="builds" align="center">';
	for ($x=0; $x < $stars + 1; $x++)
	{ 
		echo '<img src="' . $starurl . '" height="30" alt="" />'; 
	}
	echo '</td>';
	echo '<td class="builds" align="center">' . number_format(intval($uinfo['masterymax'])) . '</td>';
	echo '<td class="builds" align="center">' . number_format(intval($mastcnt[$key])) . '</td>';
	echo '<td class="builds" align="center">' . $remain . '</td></tr>';
}
?>
</table>
</div>
</div>
<!--Achievements Tab-->
<div class="tabbertab" id="fsachieve">
<h2>Achievements</h2>
<div style="background-color: ghostwhite; ">
<b>Achievements: </b><?php echo $totach; ?>
<table style="text-align: left; width: 100%;" class="builds">
<tr>
	<td class="builds" colspan="2"></td>
	<td class="builds" align="center"><b>Ribbons</b></td>
	<td class="builds" align="center"><b>Count</b></td>
</tr>
<?php
foreach($ac as $key=>$stars)
{
	$uinfo = $fsM->fsGetUnits($key);
	$iconurl = '/' . $uinfo['iconurl'];
	$starurl = '/plugins/FarmStats/images/'; 
	if (empty($uinfo['realname'])) { $uinfo['realname'] = 'Z* Disabled'; }
	echo '<tr><td class="builds" width="50">';
	if (!empty($uinfo['iconurl']))
	{
	echo '<img src="' . $iconurl . '" height="40" width="40" alt="" />';
	}
	echo '</td>';
	if ($stars['earned'] == 3)
	{
		echo '<td class="builds"><font color="blue">';
	}
	if ($stars['earned'] == 2)
	{
		echo '<td class="builds"><font color="red">';
	}
	if ($stars['earned'] == 1)
	{
		echo '<td class="builds"><font color="grey">';
	}
	if ($stars['earned'] == 0)
	{
		echo '<td class="builds"><font color="goldenrod">';
	}
	echo '<b>' . $uinfo['realname'] . '</b></font></td>';
	echo '<td class="builds" align="center">'; 
	if ($stars['earned'] >= 0) echo '<img src="' . $starurl . 'yellowribbon.png" height="40" alt="" />';
	if ($stars['earned'] >= 1) echo '<img src="' . $starurl . 'whiteribbon.png" height="40" alt="" />';
	if ($stars['earned'] >= 2) echo '<img src="' . $starurl . 'redribbon.png" height="40" alt="" />'; 
	if ($stars['earned'] >= 3) echo '<img src="' . $starurl . 'blueribbon.png" height="40" alt="" />';
	echo '</td>';
	echo '<td class="builds" align="center">' . number_format($stars['count']) . '</td></tr>';
	
}
?>
</table>
</div>
</div>
</div>
</body>
</html>
<?php
unset($fsM);

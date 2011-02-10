<?php require_once '../../fB_PluginAPI.php'; 
define('fvManager_version', file_get_contents('fvManager.ver'));
define('fvManager_date', '29 October 2010');
define('fvManager_URL', '/plugins/fvManager/index.php');
define('fvManager_Path', getcwd() . '\\');
// file definitions
define('fvManager_Main', 'fvManager_main.sqlite');
define('fvManager_World', 'fvManager_world.sqlite');
define('fvManager_Units', 'fvManager_units.sqlite');
/******************fvManager by RadicalLinux****************************/
include 'includes/fvManager.class.php';
$fvM = new fvManager('formload');
if (empty($fvM->settings)) {
	echo 'Database is not initialized yet, please allow bot to run a cycle';
	return;
}
if(!empty($fvM->error) && $fvM->haveWorld !== false)
{
	echo $fvM->error;
	return;
}
if (isset($_POST['action'])) {
	switch ($_POST['action'])
	{
		case 'settings':
			$fvM->fvDoSettings($_POST);
			break;
		case 'craftsettings':
			$fvM->fvCraftSettings($_POST);
			break;
		case 'stsell':
			$fvM->fvAddStorageWork($_POST);
			break;
		case 'sell':
		case 'sell_plot':
		case 'buy':
		case 'fmcraft':
		case 'fmbushels':
			$fvM->fvAddWork($_POST);
			break;
		case 'cancel':
			$fvM->fvCancelWork($_POST);
			break;
		case 'dowork':
			$fvM->fvChangeWork();
			break;
		case 'clearcomplete':
			$fvM->fvClearCompWork();
			break;
		case 'cancelall':
			$fvM->fvCancelAllWork();
			break;
	}
	header("Location: index.php?userId=" . $_SESSION['userId']);
}
fBAcctHeader();
$fvM->settings = $fvM->fvGetSettings();
$storage = $fvM->fvGetStoreBuildings();
$Bot_path = getcwd();
global $fvManager_ImagePath;
$fvManager_ImagePath = str_replace("/", "\\", $Bot_path);
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/index.css" />
<link rel="stylesheet" type="text/css" href="css/fvManager.css" />
<script type="text/javascript" src="js/fvManager.js"></script>
<script type="text/javascript" src="js/tabber.js"></script>
</head>
<body>
<h1>Farmville Manager V<?php echo fvManager_version; ?></h1>
<div id="Coins" style="display: inline;">
	Coins Available: <b><?php echo number_format(round($fvM->settings['gold'])); ?></b>
	<input type="hidden" id="fvcoin" value="<?php echo $fvM->settings['gold']; ?>" />
</div>
&nbsp;&nbsp;
<div id="Cash" style="display: inline;">Farmville Cash: <b><?php echo number_format(round($fvM->settings['coin'])); ?></b>
	<input type="hidden" id="fvcash" value="<?php echo $fvM->settings['coin']; ?>" />
</div>

<div class="tabber" id="t">
	<!--Statistics Tab-->
	<div class="tabbertab">
		<h2>Statistics</h2>
		<?php
		$ClassCounts = $fvM->fvClassCounts();
		?>
		<table border="0" cellspacing="4">
			<tr>
				<td>
					<p>Your UserID is&nbsp;<?php echo $fvM->settings['userid']; ?></p>
					<p>Your Current Level is&nbsp;<?php echo $fvM->settings['level']; ?><br />
					Your Current Experience is&nbsp;<?php echo $fvM->settings['xp']; ?></p>
					<p>Current Flash Version is&nbsp;<?php echo $fvM->settings['flashRevision']; ?><br />
					Current Image Version is&nbsp;<?php echo $fvM->settings['imageRevision']; ?></p>
					<p>Current Farm Size is&nbsp;<?php echo $fvM->settings['wsizeX'] . 'x' . $fvM->settings['wsizeY']; ?><br />
					You have&nbsp;<?php echo $fvM->settings['freespace']; ?>&nbsp;spots available to place objects</p>
				</td>
				<td>&nbsp;</td>
				<td valign="top">
					<b>Your farm has:</b><br />
					<?php
					foreach($ClassCounts as $key => $counts)
					{
						if($counts['myworld_className'] == 'Plot')
						{
							echo $counts['mycount'] . ' - ' . $counts['myworld_className'] . 's<br />';
						}
						else
						{
							echo $counts['mycount'] . ' - ' . ucfirst($counts['myworld_type']) . 's<br />';
						}
					}
					?>
				</td>
			</tr>
		</table>
	</div>
	<!--Settings Tab-->
	<div class="tabbertab">
		<h2>Settings</h2>
		<form id="settings" method="post">
		<input type="hidden" name="action" value="settings" />
		
		
		<table border="0" cellspacing="4">
			<tr>
				<td><b>Description</b></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>Automatically Start Jobs on To Do Tab</td>
				<?php if ($fvM->settings['aswork'] == 1) { ?>
				<td><input type="checkbox" name="fmwork" checked /></td>
				<?php } else { ?>
				<td><input type="checkbox" name="fmwork" /></td>
				<?php } ?>
			</tr>
			<tr>
				<td>Enable Additional Debugging Messages<br />
				<i>(Enabling this will produce a lot of extra output.</i><br />
				<i>Leave off if things are working)</i></td>
				<?php if ($fvM->settings['debug'] == 1) { ?>
				<td><input type="checkbox" name="fmdebug" checked /></td>
				<?php } else { ?>
				<td><input type="checkbox" name="fmdebug" /></td>
				<?php } ?>
			</tr>			
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="dosettings" value="Update Settings" />
				</td>
			</tr>
		</table>
		</form>
	</div>
	<!--Selling Tab-->
	<div class="tabbertab">
		<h2>Sell</h2>
		<div class="tabber" id="t1">
			<?php 
				foreach($ClassCounts as $key => $item)
				{
					if($item['mycount'] >= 1)
					{
			?>
			<!-- Sell Subtabs -->
			<div class="tabbertab">
				<h3>
				<?php
				if($item['myworld_className'] == 'Plot')
				{
					echo ucfirst($item['myworld_className']);
				}
				else
				{
					echo ucfirst($item['myworld_type']);
				}
				?>
				</h3>
				<form method="post" id="sell_<?php echo $item['myworld_type']; ?>">
				
				<?php
				if($item['myworld_className'] == 'Plot')
				{
					$action = 'sell_plot';
				}
				else
				{
					$action = 'sell';
				}
				?>
				<input type="hidden" name="action" value="<?php echo $action; ?>" />
				<table border="1" class="mainTable" >
					<tr id="footer">
						<td colspan="7" align="center">
							<input type="submit" name="sell_<?php echo $item['myworld_type']; ?>" value="Sell/Store&nbsp;<?php echo ucfirst($item['myworld_type']) . 's'; ?>" />
						</td>
					</tr>				
					<tr id="header">
						<td align="center"><b>Images</b></td>
						<td align="center"><b>Item</b></td>
						<td><b>Code</b></td>
						<td><b>Current Amount</b></td>
						<td align="center"><b>Sell/Store</b></td>
						<td align="center"><b>Store Where</b></td>
						<td align="center"><b>Amount</b></td>
					</tr>
					<?php
					$items = $fvM->fvItemCounts($item['myworld_type']);
					$storable = $fvM->fvCanStore();
					foreach($items as $itms)
					{
						$iconurl = '/' . $itms['myworld_iconURL'] . ".40x40.jpeg";
						if (isset($storable[$itms['myworld_itemCode']])) 
						{ 
							$test = '<input type="radio" name="' . $itms['myworld_itemCode'] . '_radio" value="sell" checked> Sell</input><br />';
							$test .= '<input type="radio" name="' . $itms['myworld_itemCode'] . '_radio" value="store"> Store</input>';
						} elseif ($action != 'sell_plot') {
							$test = '<input type="radio" name="' . $itms['myworld_itemCode'] . '_radio" value="sell" checked> Sell</input>';
						} else {
							$test = '<input type="radio" name="' . $itms['myworld_itemCode'] . '_radio" value="clear" checked> Clear</input>';
						}
					?>
					<tr>
						<td><img src="<?php echo $iconurl; ?>" height="40" width="40" alt="" /></td>
						<td><?php echo html_entity_decode($itms['myworld_itemRealName']); ?></td>
						<td align="center"><?php echo $itms['myworld_itemCode']; ?></td>
						<td align="center"><?php echo $itms['mycount']; ?></td>
						<td align="left"><?php echo $test; ?></td>
						<td align="center">
							<?php if (isset($storable[$itms['myworld_itemCode']])) { ?> 
							<select name="<?php echo $itms['myworld_itemCode'] . '_lb'; ?>" id="<?php echo 'select_' . $itms['myworld_itemCode']; ?>">
							<?php foreach ($storage AS $store) { ?>
							<option value="<?php echo $store['myworld_posx'] . ':' . $store['myworld_posy']; ?>"><?php echo $store['myworld_itemRealName']; ?></option>
							<?php } ?>
							</select>
							<?php } else { ?>
							Can't Store
							<?php } ?>
						</td>					
						<td align="center">
							<input type="hidden" id="sell_<?php echo $itms['myworld_itemCode']; ?>_qty" value="<?php echo $itms['mycount']; ?>" />
							<input type="text" name="<?php echo $itms['myworld_itemCode']; ?>" size="4"  />
						</td>
						<?php
						}
						?>
					</tr>
					<tr id="footer">
						<td colspan="7" align="center">
							<input type="submit" name="sell_<?php echo $item['myworld_type']; ?>" value="Sell/Store&nbsp;<?php echo ucfirst($item['myworld_type']) . 's'; ?>" />
						</td>
					</tr>
				</table>
				</form>
			</div>
			<?php } } ?>
			<!-- Sell Storage Subtab -->
			<div class="tabbertab">
				<h3>Storage</h3>
				<form method="post" id="st_sell">
				
				<?php
				$ststats = $fvM->fvStorageStats();
				foreach ($ststats as $key=>$stats)
				{
						echo '<div class="storage">'.$stats['buildingname'] . ' - Stored Items: ' . @$stats['cnt'] . '&nbsp;&nbsp;Capacity: ' . $stats['storagecap'] . '</div>';
				}
				?>
				<input type="hidden" name="action" value="stsell" />
				<table border="1" class="mainTable" >
					<tr id="footer">
						<td colspan="7" align="center">
							<input type="submit" name="sell_st" value="Submit Items" />
						</td>
					</tr>
					<tr id="header">
						<td align="center"><b>Images</b></td>
						<td align="center"><b>Location</b></td>
						<td align="left"><b>Item</b></td>
						<td align="center"><b>Code</b></td>
						<td align="center"><b>Amount</b></td>
						<td align="center"><b>Action</b></td>						
						<td align="center"><b>Sell/Place</b></td>
					</tr>
					<?php
					$items = $fvM->fvStorageCounts();
					foreach($items as $itms)
					{
						if ($itms['storage_itemCount'] == 0) continue;
						$location = ($itms['storage_id'] == -1) ? 'Giftbox' : '';
						$location = ($itms['storage_id'] == -2) ? 'Storage Building' : $location;
						$location = ($itms['storage_id'] == -6) ? 'Consumable Box' : $location;
						if (empty($location))
						{
							$worldinfo = $fvM->fvGetWorldbyID($itms['storage_id']);
							$location = $worldinfo['myworld_itemRealName'];
						}
						$itms['storage_itemCode'] = ($itms['storage_itemExtra'] != '') ? $itms['storage_itemCode'] . ':' . $itms['storage_itemExtra'] : $itms['storage_itemCode']; 
						if ($itms['storage_id'] == -1 || $itms['storage_id'] == -2) 
						{ 
							$radioopts = '<input type="radio" name="' . $itms['storage_itemCode'] . '_' . $itms['storage_id'] . '_action2" value="sell" checked> Sell</input><br />';
							$radioopts .= '<input type="radio" name="' . $itms['storage_itemCode'] . '_' . $itms['storage_id'] . '_action2" value="place"> Place</input>';
						} elseif ($itms['storage_id'] > 0) {
							$radioopts = '<input type="radio" name="' . $itms['storage_itemCode'] . '_' . $itms['storage_id'] . '_action2" value="place" checked> Place</input>';
						} else {
							$radioopts = '<input type="radio" name="' . $itms['storage_itemCode'] . '_' . $itms['storage_id'] . '_action2" value="sell" checked> Sell</input>';
						}						
						$iconurl = '/' . $itms['storage_iconURL'] . ".40x40.jpeg";

					?>
					<tr>
						<td><img src="<?php echo $iconurl; ?>" height="40" width="40" alt="" /></td>
						<td align="center"><?php echo $location; ?></td>
						<td align="left"><?php echo html_entity_decode($itms['storage_itemRealName']); ?></td>
						<td align="center"><?php echo $itms['storage_itemCode']; ?></td>
						<td align="center"><?php echo $itms['storage_itemCount']; ?></td>
						<td align="left"><?php echo $radioopts; ?></td>
						<td align="center">
							<input type="hidden" id="sellst_<?php echo $itms['storage_itemCode'] . '_' . $itms['storage_id']; ?>_qty" value="<?php echo $itms['storage_itemCount']; ?>" />
							<input type="text" name="<?php echo $itms['storage_itemCode'] . '_' . $itms['storage_id'] ; ?>" size="4"  />
						</td>
						<?php
						}
						?>
					</tr>
					<tr id="footer">
						<td colspan="7" align="center">
							<input type="submit" name="sell_st" value="Submit Items" />
						</td>
					</tr>
				</table>
				</form>
			</div>
		</div>
	</div>
	<!--Purchase Tab-->
	<div class="tabbertab">
		<h2>Purchase</h2>
		<?php $buyable = $fvM->fvBuyableUnits(); ?>
		<div class="tabber" id="t3">
			<?php foreach($buyable as $key => $item) { ?>
			<!-- Purchase Subtabs -->
			<div class="tabbertab">
				<h3><?php echo ucfirst($item['units_type']); ?></h3>
				<form name="buy_<?php echo $item['units_type']; ?>" method="post">
				
				<input type="hidden" name="action" value="buy" />
				<i>Note: Some Items May Require Cash to Purchase if You Already Have One (ie. Craft Cottages)</i><br />
				<i>Cash Price for Additional Purchases is NOT Listed.</i>
				<table border="1" class="mainTable" >
					<tr id="footer">
						<td colspan="8" align="center">
							<input type="submit" name="buy_<?php echo $item['units_type']; ?>" value="Buy&nbsp;<?php echo ucfirst($item['units_type']) . 's'; ?>" />
						</td>
					</tr>				
					<tr id="header">
						<td align="center"><b>Images</b></td>
						<td align="center"><b>Item</b></td>
						<td align="center"><b>Code</b></td>
						<td align="center"><b>Size</b></td>
						<td><b>Currency</b></td>
						<td><b>Cost</b></td>
						<td align="center"><b>Number to Buy</b></td>
						<td align="center"><b>Place Where</b></td>
					</tr>
					<?php 
					$items = $fvM->fvBuyableUnits($item['units_type']);
					foreach($items as $itms)
					{
						$iconurl = '/' . $itms['units_iconurl'] . ".40x40.jpeg";
						if ($itms['units_limit'] > 0) {
							$itms['units_realname'] = $itms['units_realname'] . ' (Limit ' . $itms['units_limit'] . ')';
						}
					?>
					<tr>
						<td><img src="<?php echo $iconurl; ?>" height="40" width="40" /></td>
						<td><?php echo $itms['units_realname']; ?></td>
						<td align="center"><?php echo $itms['units_code']; ?></td>
						<td align="center"><?php echo $itms['units_sizeX'] . 'x' . $itms['units_sizeY']; ?></td>
						<td align="center"><?php echo $itms['units_market']; ?></td>
						<td align="center"><?php echo $itms['units_cost']; ?></td>
						<td align="center">
							<input type="hidden" id="<?php echo $itms['units_market'] . '_' . $itms['units_code']; ?>_price" value="<?php echo $itms['units_cost']; ?>" />
							<input type="text" name="<?php echo $itms['units_code']; ?>" size="4" onChange="checkBuy(this, '<?php echo $itms['units_market'] . '_' . $itms['units_code']; ?>_price')" />
						</td>
						<td>
							<?php if ($itms['units_canstore'] == 1) { ?> 
							<select name="<?php echo $itms['units_code'] . '_lb'; ?>" id="<?php echo 'select_' . $itms['units_code']; ?>">
							<?php if ($itms['units_canplace'] == 1) {?>
							<option value="0">Place on Farm</option>
							<?php } ?>
							<?php foreach ($storage AS $store) { ?>
							<option value="<?php echo $store['myworld_posx'] . ':' . $store['myworld_posy']; ?>"><?php echo $store['myworld_itemRealName']; ?></option>
							<?php } ?>
							</select>
							<?php } else { ?>
							Place on Farm
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
					<tr id="footer">
						<td colspan="8" align="center">
							<input type="submit" name="buy_<?php echo $item['units_type']; ?>" value="Buy&nbsp;<?php echo ucfirst($item['units_type']) . 's'; ?>" />
						</td>
					</tr>
				</table>
				</form>
			</div>
		<?php } ?>
		</div>
	</div>
	<!-- Crafting Tab -->
	<div class="tabbertab">
		<h2>Crafting</h2>
		<div class="tabber" id="t1">
			<!-- Settings Subtab -->
			<div class="tabbertab">
				<h3>Settings</h3>
				<form id="craftsettings" method="post">
				<input type="hidden" name="action" value="craftsettings" />
				
				<table border="0" cellspacing="4">
					<tr id="footer">
						<td colspan="3" align="center">
							<input type="submit" name="dosettings" value="Update Settings" />
						</td>
					</tr>				
					<tr>
						<td><b>Description</b></td>
						<td>&nbsp;</td>
						<td><b>Keep</b></td>
					</tr>
					<tr>
						<td>Automatically Trade Market Goods</td>
						<?php if ($fvM->settings['asgoods'] == 1) { ?>
						<td><input type="checkbox" name="fmgoods" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="fmgoods" /></td>
						<?php } ?>
						<td><input type="text" size="4" name="fmgoods_keep" value="<?php echo $fvM->settings['asgoods_keep']; ?>" /></td>
					</tr>
					<tr>
						<td>Automatically Use Market Bushels</td>
						<?php if ($fvM->settings['asbushels'] == 1) { ?>
						<td><input type="checkbox" name="fmbushels" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="fmbushels" /></td>
						<?php } ?>
						<td><input type="text" size="4" name="fmbushels_keep" value="<?php echo $fvM->settings['asbushels_keep']; ?>" /></td>
					</tr>
					<tr>
						<td>Automatically Keep 1 Bushel for Planted Crops</td>
						<?php if ($fvM->settings['pkbushels'] == 1) { ?>
						<td><input type="checkbox" name="pkbushels" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="pkbushels" /></td>
						<?php } ?>
					</tr>						
					<tr>
						<td>Automatically Buy Available Crafted Goods</td>
						<?php if ($fvM->settings['abgoods'] == 1) { ?>
						<td><input type="checkbox" name="fmbgoods" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="fmbgoods" /></td>
						<?php } ?>
						<td><small><i>Use This to Get More Fuel and Best Buyer Ribbons</i></small></td>
					</tr>						
					<tr>
						<td>Save Bushels Used for Selected Craft Recipes</td>
						<?php if ($fvM->settings['ascbushels'] == 1) { ?>
						<td><input type="checkbox" name="fmcbushels" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="fmcbushels" /></td>
						<?php } ?>
						<td><input type="text" size="4" name="fmcbushels_keep" value="<?php echo $fvM->settings['ascbushels_keep']; ?>" /></td>
					</tr>
					<tr>
						<td>Automatically Buy Bushels Used for Selected Craft Recipes</td>
						<?php if ($fvM->settings['abbushels'] == 1) { ?>
						<td><input type="checkbox" name="fmbbushels" checked /></td>
						<?php } else { ?>
						<td><input type="checkbox" name="fmbbushels" /></td>
						<?php } ?>
					</tr>
					<tr >
						<td colspan="3">
							<?php $ingred=$fvM->fvGetGoods(); ?>
							<table border="1" class="mainTable" >
								<tr id="header">
									<td><b>Crafted Good <small>(Level)</small></b></td>
									<td><b>Bushels <small>(Req/Have)</small></b></td>
									<td align="center"><b>Ready</b></td>
									<td><b>Purchase</b></td>
								</tr>
								<?php
								$goods = unserialize($fvM->settings['goodState']);
								foreach ($ingred AS $ing) {
									if ($goods[$ing['units_code']]['m_isUnlocked'] <> 1) { continue; }
									$bushels = unserialize($ing['units_ingredients']);
									$newbushels = '';
									$ready = 0;
									foreach ($bushels as $key=>$bushel)
									{
										$res = $fvM->fvGetUnits($key);
										$resCount = $fvM->fvFMBCounts2($key);
										$resCount = ($resCount < 1) ? 0 : $resCount;
										$ready = ($resCount >= $bushel) ? $ready=$ready + 1 : $ready;
										$newbushels .= '<tr><td><small>' . $res['units_realname'] . "</small></td><td><small>$bushel</small></td><td><small>/</small></td><td><small>$resCount</small></td></tr>";
									}
									$cntbushels = count($bushels);
									$redstatus = ($cntbushels == $ready) ? 'Ready' : 'Not Ready';
									$actBushel = unserialize($fvM->settings['cbBushels']);
									//echo nl2br(print_r($actBushel,true));
									$chk = '';
									if (isset($actBushel[$ing['units_code']])) { $chk = 'checked'; }
								?>
								<tr>
									<td><?php echo $ing['units_realname'] . ' (' . $goods[$ing['units_code']]['m_level'] . ')'; ?></td>
									<td>
										<table border="0" width="100%" class="noBorder">
											<?php echo $newbushels; ?>
										</table>
									</td>
									<td align="center"><?php echo $redstatus; ?></td>
									<td align="center">
										<input type="checkbox" name="cbBushel[<?= $ing['units_code']; ?>]" value="1" <?php echo $chk; ?> />
									</td>
								</tr>
								<?php } ?>
							</table>
						</td>
					</tr>
					<tr id="footer">
						<td colspan="3" align="center">
							<input type="submit" name="dosettings" value="Update Settings" />
						</td>
					</tr>
				</table>
				</form>
			</div>
			<!-- Tables Subtab -->
			<div class="tabbertab">
				<h3>Crafted Goods</h3>
				<table border="0" cellspacing="4">
					<tr>
						<td colspan="2">
							<?php 
								$recipes = load_array('recipe.txt');
								if (!empty($recipes))
								{
								foreach($recipes as $key=>$rec)
								{
									$winfo = $fvM->fvGetWorldbyID($key);
									$craftlevel = intval($rec['craftLevel']) + 1;
									unset($rec['craftLevel']);
									echo "<b>" . $winfo['myworld_itemRealName'] . "</b> - Total Tables: ";
									echo $craftlevel . " Used Tables: " . count($rec) . " Tables Available: " . ($craftlevel - count($rec)) . "<br/>";
									
									?>
									<table border="1" class="mainTable" >
										<tr id="header">
											<td align="center"><b>Crafted Good</b></td>
											<td align="center"><b>Time Left</b></td>
										</tr>
										<?php foreach($rec as $rq) 
											{
												$uinfo = $fvM->fvGetUnits($rq['id']);
												$rtime = intval(($rq['finish_ts'] - time()) / 60);
												$rtime = ($rtime < 0) ? 'Finished' : $rtime . ' min.';

										?>
											<tr>
												<td><?php echo $uinfo['units_realname']; ?></td>
												<td align="center"><?php echo $rtime; ?></td>
											</tr>
										<?php } ?>
									</table>
							<?php
								}
								} 									
							?>

						</td>
					</tr>
				</table>
			</div>			
			<!-- Market Goods Subtab -->
			<div class="tabbertab">
				<h3>Market(Goods)</h3>
				<form method="post" id="fm_craft">
				<b>Note: This will trade the crafted items for fuel (Nothing else supported at this time)</b><br />
				<?php
					$action = 'fmcraft';
					$header = 'Number to Trade';
				?>
				<input type="hidden" name="action" value="<?php echo $action; ?>" />
				
				<table border="1" class="mainTable" >
					<?php if ($fvM->settings['asgoods'] != 1) { ?>
					<tr id="footer">
						<td colspan="4" align="center">
							<input type="submit" name="sell_fm" value="Trade Farmers Market Goods" />
						</td>
					</tr>
					<?php } ?>				
					<tr id="header">
						<td align="center"><b>Item</b></td>
						<td><b>Code</b></td>
						<td><b>Current Amount</b></td>
						<td align="center"><b><?php echo $header; ?></b></td>
					</tr>
					<?php
					$items = $fvM->fvFMCounts();
					foreach($items as $itms)
					{
					?>
					<tr>
						<td><?php echo html_entity_decode($itms['fmarket_itemRealName']); ?></td>
						<td align="center"><?php echo $itms['fmarket_itemCode']; ?></td>
						<td align="center"><?php echo $itms['fmarket_itemCount']; ?></td>
						<td align="center">
							<input type="hidden" id="sell_<?php echo $itms['fmarket_itemCode']; ?>_qty" value="<?php echo $itms['fmarket_itemCount']; ?>" />
							<?php if ($fvM->settings['asgoods'] == 1) { ?>
							Auto Enabled
							<?php } else { ?>
							<input type="text" name="<?php echo $itms['fmarket_itemCode']; ?>" size="4" />
						</td>
						<?php } } ?>
					</tr>
					<?php if ($fvM->settings['asgoods'] != 1) { ?>
					<tr id="footer">
						<td colspan="4" align="center">
							<input type="submit" name="sell_fm" value="Trade Farmers Market Goods" />
						</td>
					</tr>
					<?php } ?>
				</table>
				</form>
			</div>
			<!-- Market Bushels Subtab -->
			<div class="tabbertab">
				<h3>Market(Bushels)</h3>
				<form method="post" id="fm_bushels">
				<?php
				$action = 'fmbushels';
				$header = 'Number to Use';
				?>
				<input type="hidden" name="action" value="<?php echo $action; ?>" />
				
				<b>Note: This will use the bushels making the last bushel active</b><br />
				<b>Bushels Currently In Market Stall: <?php echo $fvM->settings['mybushels']; ?></b><br />
				<table border="1" class="mainTable" >
					<?php if ($fvM->settings['asbushels'] != 1) { ?>
					<tr id="footer">
						<td colspan="4" align="center">
							<input type="submit" name="sell_fmb" value="Use Farmers Market Bushels" />
						</td>
					</tr>
					<?php } ?>				
					<tr id="header">
						<td align="center"><b>Item</b></td>
						<td><b>Code</b></td>
						<td><b>Current Amount</b></td>
						<td align="center"><b><?php echo $header; ?></b></td>
					</tr>
					<?php
					$items = $fvM->fvFMBCounts();
					foreach($items as $itms)
					{
					?>
					<tr>
						<td><?php echo html_entity_decode($itms['fmbushels_itemRealName']); ?></td>
						<td align="center"><?php echo $itms['fmbushels_itemCode']; ?></td>
						<td align="center"><?php echo $itms['fmbushels_itemCount']; ?></td>
						<td align="center">
							<input type="hidden" id="sell_<?php echo $itms['fmbushels_itemCode']; ?>_qty" value="<?php echo $itms['fmbushels_itemCount']; ?>" /> 
							<?php if ($fvM->settings['asbushels'] == 1) { ?>
							Auto Enabled 
							<?php } else { ?> 
							<input type="text" name="<?php echo $itms['fmbushels_itemCode']; ?>" size="4" />
						</td>
						<?php } ?>
						<?php } ?>
					</tr>
					<?php if ($fvM->settings['asbushels'] != 1) { ?>
					<tr id="footer">
						<td colspan="4" align="center">
							<input type="submit" name="sell_fmb" value="Use Farmers Market Bushels" />
						</td>
					</tr>
					<?php } ?>
				</table>
				</form>
			</div>
		</div>
	</div>
	<!-- To Do Tab -->
	<div class="tabbertab">
		<h2>To Do</h2>
		
     <table border="1" class="mainTable" >
		<tr id="header">
	     	<td align="center"><b>Item Name</b></td>
	     	<td><b>Item Code</b></td>
	     	<td><b>Quantity</b></td>
	     	<td><b>Action</b></td>
	     	<td align="center"><b>Result</b></td>
	     	<td align="center"><b>Cancel</b></td>
	     </tr>
<?php
   $myitems = $fvM->fvGetWork();
   foreach($myitems as $itms)
   {
      $itms['work_action'] = (!empty($itms['work_action2'])) ? $itms['work_action2'] : $itms['work_action'];
?>
		<tr>
        	<td><?php echo html_entity_decode($itms['work_itemRealName']);  ?></td>
        	<td align="center"><?php echo $itms['work_itemCode'];  ?></td>
         	<td align="center"><?php echo $itms['work_quantity'];  ?></td>
          	<td align="center"><?php echo $itms['work_action'];  ?></td>
          	<td align="center"><?php echo $itms['work_result'];  ?></td>
          	<td align="center">
          	<form method="post" name="cancel_<?php echo $itms['work_id'];  ?>">
          	
          	<input type="hidden" name="action" value="cancel" />
          	<input type="hidden" name="<?php echo $itms['work_itemCode'];  ?>" value="<?php echo $itms['work_quantity'];  ?>" />
          	<input type="hidden" name="id" value="<?php echo $itms['work_id'];  ?>" />
          	<input type="submit" name="Cancel" value="Cancel" />
          	</form>
          	</td>
         </tr>
<?php
   }
   if(count($myitems) > 0)
   {
?>
		<tr id="footer">
          	<td colspan=6 align="center">
          		<form method="post" name="do_work" style="display:inline;">
				
          		<input type="hidden" name="action" value="dowork" />
          		<input type="submit" name="do_work" value="Do Work" />
          		</form>
          		<form method="post" name="clear_complete" style="display:inline;">
          		
          		<input type="hidden" name="action" value="clearcomplete" />
          		<input type="submit" name="clear_completed" value="Clear Completed" />
				</form>
          		<form method="post" name="cancel_all" style="display:inline;">
          		
          		<input type="hidden" name="action" value="cancelall" />
          		<input type="submit" name="cancel_all_work" value="Cancel All" />
				</form>				
          	</td>
		</tr>
<?php
   }
?>
     </table>
	</div>
</div>
</body>
</html>

<?php
unset($fvM);

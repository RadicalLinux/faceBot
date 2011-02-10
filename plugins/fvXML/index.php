<?php require_once '../../fB_PluginAPI.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
define('fvXML_version', file_get_contents('fvXML.ver'));
define('fvXML_date', '24 October 2010');
define('fvXML_Path', getcwd() . '\\');
// file definitions
define('fvXML_Main', 'fvXML_main.sqlite');
define('fvXML_World', 'fvXML_world.sqlite');
define('fvXML_Units', 'fvXML_units.sqlite');
require_once 'includes/fvXML.class.php';
$fvX = new fvXML('formload');
if (empty($fvX->settings)) {
		echo 'Database is not initialized yet, please allow bot to run a cycle';
		return;
	}
	if(!empty($fvX->error) && $fvX->haveWorld !== false)
	{
		echo $fvX->error;
		return;
	}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="/plugins/fvXML/css/index.css" />
<link rel="stylesheet" type="text/css" href="/plugins/fvXML/css/fvXml.css" />
<script type="text/javascript" src="/plugins/fvXML/js/fvXML.js"></script>
<script type="text/javascript" src="/plugins/fvXML/js/tabber.js"></script>

</head>
<body>
<?php 
	fBAcctHeader();
?>
<h1>fvXML V<?php echo fvXML_version; ?></h1>
<small><font color="red">(Please Be Paitent while Page Loads - There is Lots of Information)</font></small>
	<!--XML Items Tab-->
	<div class="tabbertab">
		<?php $buyable = $fvX->fvBuyableUnits(); ?>
		<div class="tabber" id="t3">
			<?php foreach($buyable as $key => $item) { ?>
			<!-- XML Items Subtabs -->
			<div class="tabbertab">
				<h3><?php echo ucfirst($item['units_type']); ?></h3>
				<table border="1" class="mainXMLTable" >
					<tr id="header">
						<td align="center"><b>Image</b></td>
						<td align="center"><b>Real Name</b></td>
						<td align="center"><b>Code</b></td>
						<td align="center"><b>Name</b></td>
						<td align="center"><b>Classname</b></td>
						<td align="center"><b>Start Date</b></td>
						<td align="center"><b>End Date</b></td>
						<td align="center"><b>Size</b></td>
						<td align="center"><b>Giftable</b></td>
						<td align="center"><b>Placeable</b></td>
						<td align="center"><b>Iphone</b></td>
						<td align="center"><b>Currency</b></td>
						<td align="center"><b>Cost</b></td>
					</tr>
					<?php 
					$items = $fvX->fvBuyableUnits($item['units_type']);
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
						<td align="center"><?php echo $itms['units_name']; ?></td>
						<td align="center"><?php echo $itms['units_class']; ?></td>
						<td align="center"><?php echo @date("m/d/Y", $itms['units_limitedstart']);?>&nbsp;</td>
						<td align="center"><?php echo @date("m/d/Y", $itms['units_limitedend']); ?>&nbsp;</td>
						<td align="center"><?php echo $itms['units_sizeX'] . 'x' . $itms['units_sizeY']; ?></td>
						<td align="center"><?php echo $itms['units_giftable']; ?></td>
						<td align="center"><?php echo $itms['units_canplace']; ?></td>
						<td align="center"><?php echo $itms['units_iphoneonly']; ?></td>
						<td align="center"><?php echo ucfirst($itms['units_market']); ?></td>
						<td align="center"><?php echo number_format($itms['units_cost']); ?></td>
					</tr>
					<?php } ?>
				</table>
			</div>
		<?php } ?>
		</div>
	</div>
</div>
</body>
</html>

<?php
unset($fvX);

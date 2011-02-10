<?php

function fvTools_form()
{
	global $this_plugin;
	AddLog2(print_r($_POST,true));
	$fvM = new fvTools('formload');
	if (empty($fvM->settings)) {
		echo 'Database is not initialized yet, please allow bot to run a cycle';
		return;
	}
	if(!empty($fvM->error) && $fvM->haveWorld !== false)
	{
		echo $fvM->error;
		return;
	}
	if (@$_GET['submit'] == 'Save Settings')
	{
		$fvM->fvDoSettings();
	}

	$fvM->settings = $fvM->fvGetSettings();
	$Bot_path = getcwd();
	global $fvTools_ImagePath;
	$fvTools_ImagePath = str_replace("/", "\\", $Bot_path);
	$mast = unserialize($fvM->settings['mastery']);
	$mastcnt = unserialize($fvM->settings['masterycnt']);
	if (file_exists(F('seed.txt'))) { // fix infinite loop when no file exists
        $seed_list = explode(';', trim(file_get_contents(F('seed.txt'))));
	}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="/plugins/fvTools/css/index.css" />
<script type="text/javascript" src="/plugins/fvTools/js/fvTools.js"></script>
<script type="text/javascript" src="/plugins/fvTools/js/tabber.js"></script>
<script type="text/javascript" src="/plugins/fvTools/js/lib/prototype.js"></script>
<script type="text/javascript" src="/plugins/fvTools/js/src/scriptaculous.js"></script>

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
	define ( 'REQ_VER_PARSER', '218');
	if ((!PX_VER_PARSER) || (PX_VER_PARSER < REQ_VER_PARSER))
	{
		echo "<br><br><span style=\"text-align:center;color:red\">**** ERROR: fvTools v".fvTools_version." Requires parser version v".REQ_VER_PARSER." or higher ****</span><br>";
		return;
	}

?>
<h1>fvTools V<?php echo fvTools_version; ?></h1>
<p><a href="#" onclick="showhide('settings');">Show/Hide Settings</a></p>
<div id="settings" style="display: none;">
<form id="settings" method="get">
<?php 
$showxmlchk = ($fvM->settings['showxml'] == 1) ? 'checked' : ''; 
?>
<small>
<input type="checkbox" name="showxml" value="showxml" <?php echo $showxmlchk; ?> />Show XML Tab<br />
<input type="submit" name="submit" value="Save Settings" />
</small>
</form>
</div>
<div class="tabber" id="t">
	<!--Seeding Tab-->
	<div class="tabbertab">
		<h2>Seed List</h2>
		<?php $seeds = $fvM->fvSeedUnits(); ?>
		<b>Drag and Drop List Items to Sort.</b>
		<table border=0>
		<tr>
		<td>
        <ul id="seeds_list" style="list-style-type : none; margin : 0;">
            <?php foreach ($seed_list as $key => $seedname) { ?>
                <li id="seed_<?= $key ?>" style="border : 1px solid #000; cursor : move; margin : 2px 0 2px 0; padding : 3px; background : #f7f7f7; border : #ccc; width : 400px;"><?= $seedname ?></li>
            <?php } ?>
        </ul>
        </td>
        <td>
		<div id="deleteArea">
			<big>Drag Here to Delete.</big>
		</div>
		</td>
		</tr>
		</table>
		<input type="submit" value="Save" onClick="new Ajax.Request('main.php', { method: 'post', parameters: Sortable.serialize('seeds_list'), 
								onSuccess: function(transport){
		      					var response = transport.responseText || 'no response text';
	      						alert('Success! \n\n' + response);
	    						},
								onFailure: function(){ alert('Something went wrong...');}});">      
        <script type="text/javascript">
            Sortable.create('seeds_list', {
            	tag:'li',
				constraint: false,
				dropOnEmpty: true,
				containment: ['seeds_list','deleteArea']
				});
				Droppables.add('deleteArea', {
				containment: ['seeds_list','deleteArea'],
				onDrop: deleteItem
				});
				function deleteItem(draggable,deleteArea) {
					draggable.parentNode.removeChild(draggable);
					deleteArea.appendChild(draggable);
					deleteArea.removeChild(draggable);
			}            
        </script>
    </div>
	<?php if ($fvM->settings['showxml'] == 1 ) { ?>
	<!--XML Items Tab-->
	<div class="tabbertab">
		<h2>XML Items</h2>
		<?php $buyable = $fvM->fvBuyableUnits(); ?>
		<div class="tabber" id="t3">
			<?php foreach($buyable as $key => $item) { ?>
			<!-- XML Items Subtabs -->
			<div class="tabbertab">
				<h3><?php echo ucfirst($item['units_type']); ?></h3>
				<table border="1">
					<tr>
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
					$items = $fvM->fvBuyableUnits($item['units_type']);
					foreach($items as $itms)
					{
						$iconurl = 'file:///' . $fvTools_ImagePath . '/' . $itms['units_iconurl'] . ".40x40.jpeg";
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
						<td align="center"><?php echo date("m/d/y", $itms['units_limitedstart']);?>&nbsp;</td>
						<td align="center"><?php echo date("m/d/y", $itms['units_limitedend']); ?>&nbsp;</td>
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
	<?php } ?>
</div>
</body>
</html>

<?php
unset($fvM);
}
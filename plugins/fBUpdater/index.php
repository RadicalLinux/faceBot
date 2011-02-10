<?php require_once '../../fB_PluginAPI.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
require_once 'functions.php';
if (count($_POST) > 0)
{
	foreach ($_POST as $key=>$value)
	{
		if ($key == 'Parser') {
			ParserIRU($_POST['botversion']);	
		} else {
			switch ($value)
			{
				case 'Repair':
					echo "<h1>Repairing " . $key . '</h1>';
					DoIRU($value, $key, $_POST['botversion']);
					break;
				case 'Install':
					echo "<h1>Installing " . $key . '</h1>';
					DoIRU($value, $key, $_POST['botversion']);
					break;
				case 'Update':
					echo "<h1>Updating " . $key . '</h1>';
					DoIRU($value, $key, $_POST['botversion']);
			}
		}
	}
}
$botver = file_get_contents('fBUpdater.bot');


?>
<html>
<head>
<script src="js/fBUpdater.js"></script>
<script src="js/tabber.js"></script>
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
<link rel="stylesheet" type="text/css" href="css/index.css" />
<link rel="stylesheet" type="text/css" href="css/updater.css" />
</head>
<body >
<?php fBAcctHeader(); ?>
<h1>fBUpdater <?= file_get_contents('fBUpdater.ver'); ?></h1>
<a href="http://www.facebot.org">Tutorials and FAQs</a> - <a href="http://www.facebotforum.com">Support Forums</a>
<div class="tabber" id="fBUpdater">
<!--News Tab-->
<div class="tabbertab" id="news">
<h2>News And Stuff</h2>
<div id="whatsnew" style="border: 1px solid; padding:5px; background:lightskyblue;margin-right:50px;width:750px;">
<?= nl2br(file_get_contents('http://207.224.102.177/faceBot/whatsnew.txt')); ?>
</div>
</div>
<?php if ($botver == 'regular') {
$plugs = GetPlugins();
$parser = GetParser();
?>
<div class="tabbertab" id="Farmville">
<h2>Farmville</h2>
<a href="index.php">Refresh Plugin List</a>
<form method="post">
<input type="hidden" name="botversion" value="regular" />
<table border="1" class="mainTable" width="750">
	<tr id="header">
		<td colspan=5 align="center"><b>Parser/fBSettings</b></td>
	</tr>
	<tr id="header">
		<td align="center"><b>Installed Version</b></td>
		<td align="center"><b>Available Version</b></td>
		<td align="center"><b>What's New</b></td>
		<td align="center"><b>Update</b></td>
		<td align="center"><b>Repair</b></td>
	</tr>
	<tr>
		<td align="center"><?= $parser['installver']; ?></td>
		<td align="center"><?= $parser['serverver']; ?></td>
		<td align="center"><div style="cursor:pointer;display:inline;color:blue;"  onclick="showhide('parsernew');">Show/Hide</div></td>
		<?php
		if ($parser['installver'] == 'Not Installed') { ?>
		<td align="center"><input type="submit" name="<?= $parser['name']; ?>" value="Install"></td>
		<td>&nbsp;</td>
		<?php } elseif ($parser['installver'] == 'Unknown' || trim($parser['installver']) != trim($parser['serverver'])) { ?>
		<td align="center"><input type="submit" name="<?= $parser['name']; ?>" value="Update"></td>
		<td>&nbsp;</td>
		<?php } else { ?>
		<td align="center"><font color="green">Latest Version</font></td>
		<td align="center"><input type="submit" name="<?= $parser['name']; ?>" value="Repair"></td>
		
		<?php } ?>
	</tr>
	<tr  id="parsernew" style="display: none;">
	<td colspan="5" style="background:lightskyblue;"><?= nl2br(file_get_contents('http://207.224.102.177/faceBot/parser.txt')); ?></td>
	</tr>
</table>
</form>
<form method="post">
<input type="hidden" name="botversion" value="regular" />
<table border="1" class="mainTable" width="750">
	<tr id="header">
		<td align="center"><b>Plugin</b></td>
		<td align="center"><b>Installed Version</b></td>
		<td align="center"><b>Available Version</b></td>
		<td align="center"><b>What's New</b></td>
		<td align="center"><b>Install/Update</b></td>
		<td align="center"><b>Repair</b></td>
	</tr>
	<?php
	foreach ($plugs as $plug)
	{
		?>
	<tr>
		<td><?= $plug['name']; ?></td>
		<td align="center"><?= $plug['installver']; ?></td>
		<td align="center"><?= $plug['serverver']; ?></td>
		<td align="center"><div style="cursor:pointer;display:inline;color:blue;" onclick="showhide('<?= $plug['name']. '_info'; ?>');">Show/Hide</div></td>
		<?php
		if ($plug['installver'] == 'Not Installed') { ?>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Install"></td>
		<td>&nbsp;</td>
		<?php } elseif ($plug['installver'] == 'Unknown' || trim($plug['installver']) != trim($plug['serverver'])) { ?>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Update"></td>
		<td>&nbsp;</td>
		<?php } else { ?>
		<td align="center"><font color="green">Latest Version</font></td>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Repair"></td>
		<?php } ?>
	</tr>
	<tr id="<?= $plug['name'] . '_info'; ?>" style="display: none;">
	<td id="changeLog" colspan="6" style="background:lightskyblue;"><?= nl2br(file_get_contents('http://207.224.102.177/faceBot/' . $plug['name'] . '.txt')); ?></td>
	</tr>
	<?php 
}
?>
</table>
</form>
</div>
<?php } 
if ($botver == 'chinese') {
?>
<?php $cparser = GetParser('chinese'); ?>
<div class="tabbertab" id="FarmvilleCH">
<h2>Farmville - Chinese</h2>
<a href="index.php">Refresh Plugin List</a>
<form method="post">
<input type="hidden" name="botversion" value="chinese" />
<table border="1" class="mainTable" width="750">
	<tr id="header">
		<td colspan=5 align="center"><b>Parser/fBSettings</b></td>
	</tr>
	<tr id="header">
		<td align="center"><b>Installed Version</b></td>
		<td align="center"><b>Available Version</b></td>
		<td align="center"><b>What's New</b></td>
		<td align="center"><b>Update</b></td>
		<td align="center"><b>Repair</b></td>
	</tr>
	<tr>
		<td align="center"><?= $cparser['installver']; ?></td>
		<td align="center"><?= $cparser['serverver']; ?></td>
		<td align="center"><div style="cursor:pointer;display:inline;color:blue;"  onclick="showhide('parsernew');">Show/Hide</div></td>
		<?php
		if ($parser['installver'] == 'Not Installed') { ?>
		<td align="center"><input type="submit" name="<?= $cparser['name']; ?>" value="Install"></td>
		<td>&nbsp;</td>
		<?php } elseif ($parser['installver'] == 'Unknown' || trim($parser['installver']) != trim($parser['serverver'])) { ?>
		<td align="center"><input type="submit" name="<?= $cparser['name']; ?>" value="Update"></td>
		<td>&nbsp;</td>
		<?php } else { ?>
		<td align="center"><font color="green">Latest Version</font></td>
		<td align="center"><input type="submit" name="<?= $cparser['name']; ?>" value="Repair"></td>
		
		<?php } ?>
	</tr>
	<tr  id="parsernew" style="display: none;">
	<td colspan="5" style="background:lightskyblue;"><?= nl2br(file_get_contents('http://207.224.102.177/faceBot/parser.txt')); ?></td>
	</tr>
</table>
</form>
<form method="post">
<input type="hidden" name="botversion" value="chinese" />
<table border="1" class="mainTable" width="750">
	<tr id="header">
		<td align="center"><b>Plugin</b></td>
		<td align="center"><b>Installed Version</b></td>
		<td align="center"><b>Available Version</b></td>
		<td align="center"><b>What's New</b></td>
		<td align="center"><b>Install/Update</b></td>
		<td align="center"><b>Repair</b></td>
	</tr>
	<?php
	$cplugs = GetPlugins('chinese');
	
	foreach ($cplugs as $plug)
	{
		?>
	<tr>
		<td><?= $plug['name']; ?></td>
		<td align="center"><?= $plug['installver']; ?></td>
		<td align="center"><?= $plug['serverver']; ?></td>
		<td align="center"><div style="cursor:pointer;display:inline;color:blue;" onclick="showhide('<?= $plug['name']. '_info'; ?>');">Show/Hide</div></td>
		<?php
		if ($plug['installver'] == 'Not Installed') { ?>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Install"></td>
		<td>&nbsp;</td>
		<?php } elseif ($plug['installver'] == 'Unknown' || trim($plug['installver']) != trim($plug['serverver'])) { ?>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Update"></td>
		<td>&nbsp;</td>
		<?php } else { ?>
		<td align="center"><font color="green">Latest Version</font></td>
		<td align="center"><input type="submit" name="<?= $plug['name']; ?>" value="Repair"></td>
		<?php } ?>
	</tr>
	<tr id="<?= $plug['name'] . '_info'; ?>" style="display: none;">
	<td id="changeLog" colspan="6" style="background:lightskyblue;"><?= nl2br(file_get_contents('http://207.224.102.177/faceBot/' . $plug['name'] . '.txt')); ?></td>
	</tr>
	<?php 
}
?>
</table>
</form>
</div>
<?php } ?>
</div>
</body>
</html>
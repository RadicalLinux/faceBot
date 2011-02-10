<?php
require_once '../../fB_PluginAPI.php';
define('fvLinks_version', file_get_contents('fvLinks.ver'));
define('fvLinks_date', '20 October 2010');
define('fvLinks_Main', 'fvLinks_main.sqlite');
define('fvLinks_Path', $_SESSION['base_path'] . 'plugins/fvLinks/');
include 'includes/fvLinks.class.php';
fBAcctHeader();
$fsM = new fvLinks('formload');
if (empty($fsM->settings)) {
	echo 'Database is not initialized yet, please allow bot to run a cycle';
	return;
}
if(!empty($fsM->error) && $fvM->haveWorld !== false)
{
	echo $fsM->error;
	return;
}
if(isset($_POST['clearlinks']))
{
	$fsM->fvClearLinks();
}
if(isset($_POST['export']))
{
	$fsM->fsExport();
}
if(isset($_POST['submit']))
{
	$fsM->fsDoSettings($_POST);
}
$linkTypes = $fsM->GetLinkTypes();
//echo nl2br(print_r($linkTypes,true));
$AlinkTypes = $fsM->GetALinks();
$settings = $fsM->fsGetSettings();
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
	font-size: 6px;
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
<link rel="stylesheet" type="text/css" href="/plugins/fvLinks/css/index.css" />
<link rel="stylesheet" type="text/css" href="/plugins/fvLinks/css/links.css" />
<script src="/plugins/fvLinks/js/fvLinks.js"></script>
<script src="/plugins/fvLinks/js/tabber.js"></script>
<script src="/plugins/fvLinks/js/grablinks.js"></script>
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
<h1>fvLinks V<?php echo fvLinks_version; ?></h1>
<div class="tabber" id="fvLinks">
<div class="tabbertab" id="links">
<h2>Regular Links</h2>
<form method="post" style="display:inline">
	<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>"> 
	<input type="submit" name="clearlinks" value="Clear Links">
	<input type="submit" name="export" value="Export Links"></form><br /><br />
<?php
$urls = $helpers = $animals = $time = '';
foreach ($linkTypes as $cat=>$link)
{
	?><b><?= $cat; ?></b>&nbsp;&nbsp;
	<div style="cursor:pointer;display:inline;color:blue;" onclick="showhide('<?= $cat; ?>');">Show/Hide</div>
	<br />
<br />
<div id="<?= $cat ?>" style="display: none;">
<table border="1" class="mainTable" >
		<tr id="header">
		<td align="center"><b>Time</b></td>
		<td align="center"><b>Reward</b></td>
		<td align="center"><b>Link</b></td>
	</tr>
	<tr>
	<?php
	$urls = $time = $items = '';
	foreach ($link as $nlink)
	{
		$key = str_replace('&key={*key*}', '', $nlink['rewardLink']);
		//echo nl2br(print_r($link,true));
		@$time .= date("m/d/Y, g:i a", $nlink['timestamp']) . '<br>';
		@$urls .= 'http://apps.facebook.com/onthefarm/' . $key . '<br>';
		if (isset($nlink['rewardCode'])) {
			$name = Units_GetRealnameByCode($nlink['rewardCode']);
			$name = empty($name) ? Units_GetRealnameByName($nlink['rewardCode']) : $name;
			@$items .= $name . '<br>';
		}
		if (isset($nlink['rewardItem'])) {
			if (empty($nlink['rewardItem'])) {
				@$items .= '--Unknown--<br>';
			} else 	{
				$name = Units_GetRealnameByName($nlink['rewardItem']);
				$name = empty($name) ? Units_GetRealnameByCode($nlink['rewardItem']) : $name;
				@$items .= $name . '<br>';
			}
		}
	}	?>
		<td nowrap><small><?= $time ?></small></td>
		<td nowrap><small><?= $items ?></small></td>
		<td nowrap><small><?= $urls ?></small></td>
	</tr>

</table>
<br />
</div>
	<?php
}

?></div>
<div class="tabbertab" id="alinks">
<h2>Lonely Animal Links</h2>
<table border="1" class="mainTable" >
		<tr id="header">
		<td align="center"><b>Time</b></td>
		<td align="center"><b>Clicks</b></td>
		<td align="center"><b>Animal</b></td>
		<td align="center"><b>Link</b></td>
	</tr>
	<tr>
	<?php
	$urls = $helpers = $animals = $time = '';
	foreach ($AlinkTypes as $url)
	{
		?>
		<?php
		$time .= $url['time'] . '<br>';
		$urls .= $url['url'] . '<br>';
		$helpers .= $url['helpers'] . '<br>';
		$animals .= Units_GetRealnameByName($url['animal']) . '<br>';
	}
	?>
		<td nowrap><small><?php echo $time; ?></small></td>
		<td nowrap align="center"><small><?php echo $helpers; ?></small></td>
		<td nowrap><small><?php echo $animals; ?></small></td>
		<td nowrap><small><?php echo $urls; ?></small></td>
	</tr>
</table>
</div>
<div class="tabbertab" id="grablinks" onclick="javascript:checkBoxes();">
<h2>Grab Links</h2>
<form name="grablinks" method="post">
<?php
$known = $fsM->GetKnownTypes();
$uinfo = fBGetUserInfo();
$grabs = unserialize($settings['sharelinks']);
unset($uinfo[$_SESSION['userId']]);
if (empty($uinfo)) { echo '<b>No Accounts to Grab From</b>'; } else {
	echo '<div style="font-weight:bold;background-color:#FF7D1A; color: #1E3F82;padding:3px;">select which rewards to grab for ALL accounts</div>';
	@$sharechk = ($grabs['share'] == 1) ? 'checked' : ''; 
	
	echo '<div><span style="color:green;font-weight:bold;">Grab Links from ALL accounts</span><input type="checkbox" name="share" value="1" ' . $sharechk . ' onClick="javascript:checkThemAll(\'[share]\',\'share\');" /></div>';
	
	echo '<div style="margin-top:10px;"><span style="color:red;font-weight:bold;">Don\'t Grab these links for ALL accounts:</span></div>';
	
	echo '<table id="rewards"><tr>';
	$k = 0;
	foreach ($known as $catrew)
		{
			if ($k/4 == intval($k/4)) echo '</tr><tr>';
			@$drewardchk = ($grabs[$catrew['knownlinks_name']] == 1) ? 'checked' : ''; 
			echo '<td nowrap="nowrap" class="rewardName"><input type="checkbox" name="' . $catrew['knownlinks_name'] . '" value="1" '.@$drewardchk.'  onclick="javascript:checkThemAll(\'['. $catrew['knownlinks_name'] .']\',\''. $catrew['knownlinks_name'] .'\');" />'. $catrew['knownlinks_name'] . '</td>';
			$k++;
		}
	echo '</tr></table>';
	
	echo '<input type="submit" name="submit" value="Save">';
	echo '<input type="button" name="color" value="Color Rewards!" onClick="checkBoxes();">';
	
	
	echo '<div style="font-weight:bold;background-color:#FF7D1A; color: #1E3F82;margin-top:20px;padding:3px;margin-bottom:3px;">select which rewards to grab for SINGLE accounts</div>';
	foreach ($uinfo as $fbid=>$uname)
	{
		@$grabchk = ($grabs[$fbid]['share'] == 1) ? 'checked' : ''; 
		echo '<div style="padding:3px;background-color:#B7DFFD;margin-top:3px;"><b>' . $fbid . ' - ' . $uname . '</b> - <span style="color:green;font-weight:bold;">Grab Links </span><input type="checkbox" name="' . $fbid . '[share]" value="1" ' . $grabchk . ' /></div><p>';
		echo "<i style='color:red;font-weight:bold;'>Don't Grab:</i><br />";
		$x = 0;
		echo '<table id="' . $fbid . '"><tr>';
		foreach ($known as $cat)
		{
			if ($x/4 == intval($x/4)) echo '</tr><tr>';
			@$dgrabchk = ($grabs[$fbid][$cat['knownlinks_name']] == 1) ? 'checked' : ''; 
			echo '<td nowrap="nowrap" class="rewardName"><input type="checkbox" name="' . $fbid . '[' . $cat['knownlinks_name'] . ']" value="1" ' . $dgrabchk . ' onclick="javascript:checkBox(this);" />'. $cat['knownlinks_name'] . '</td>';
			$x++;
		}
echo '</tr></table>';
		echo '</p>';
		echo '<input type="button" name="checkbox" value="check all rewards" onClick="checkAll(' . $fbid . ');">';
		echo '<input type="button" name="checkbox" value="uncheck all rewards" onClick="unCheckAll(' . $fbid . ');"><br/>';
		echo '<input type="submit" name="submit" value="Save">';	
	}
	
}
?>
</form>
</div>
</div>
</body>
</html>
	<?php
	unset($fsM);

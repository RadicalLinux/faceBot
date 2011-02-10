<?php
require_once '../../fB_PluginAPI.php';
define('FB_PARSER_VER', file_get_contents($_SESSION['base_path'] . 'parser.ver'));
define('FB_SETTINGS_VER', file_get_contents('fBSettings.ver'));
if (isset($_POST['save_action']))
{

	$changed_settings = false;

	$px_Setopts = LoadSavedSettings();


	$px_Setopts['e_gzip'] = @$_POST['gzip'];
	$px_Setopts['bot_speed'] = @$_POST['bot_speed'];
	$px_Setopts['lonlyanimals'] = @$_POST['lonlyanimals'];
	$px_Setopts['acceptneighborhelp'] = @$_POST['acceptneighborhelp'];

	if(@$_POST['nr_parser']==1) {
		if (!file_exists($_SESSION['base_path'] . 'notrun_parser.txt')) {
			file_put_contents($_SESSION['base_path'] . 'notrun_parser.txt','.');
		}
	} else {
		if (file_exists($_SESSION['base_path'] . 'notrun_parser.txt')) {
			unlink($_SESSION['base_path'] . 'notrun_parser.txt');
		}
	}
	if(@$_POST['nr_parser_a']==1) {
		if (!file_exists($_SESSION['base_path'] . 'notrun_parser_'.$_SESSION['userId'].'.txt')) {
			file_put_contents($_SESSION['base_path'] . 'notrun_parser_'.$_SESSION['userId'].'.txt','.');
		}
	} else {
		if ($_SESSION['base_path'] . file_exists('notrun_parser_'.$_SESSION['userId'].'.txt')) {
			unlink($_SESSION['base_path'] . 'notrun_parser_'.$_SESSION['userId'].'.txt');
		}
	}
	if(@strlen($_POST['timezone'])>1) {
		file_put_contents('timezone.txt',@$_POST['timezone']);
	}
	$dir = $_SESSION['base_path'] . 'plugins';
	$dh = opendir($dir);

	if ($dh) {
		while (($file = readdir($dh)) !== false) {
			if (is_dir($dir . '/' . $file)) {
				if ($file != '.' && $file != '..' && $file != 'Settings') {
					if(@$_POST['nr_p_'.$file]==1) {
						if (!file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'.txt')) {
							file_put_contents($_SESSION['base_path'] . 'notrun_plugin_'.$file.'.txt','.');
						}
					} else {
						if (file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'.txt')) {
							unlink($_SESSION['base_path'] . 'notrun_plugin_'.$file.'.txt');
						}
					}
					if(@$_POST['nr_p_'.$file.'_a']==1) {
						if (!file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'_'.$_SESSION['userId'].'.txt')) {
							file_put_contents($_SESSION['base_path'] . 'notrun_plugin_'.$file.'_'.$_SESSION['userId'].'.txt','.');
						}
					} else {
						if (file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'_'.$_SESSION['userId'].'.txt')) {
							unlink($_SESSION['base_path'] . 'notrun_plugin_'.$file.'_'.$_SESSION['userId'].'.txt');
						}
					}
				}
			}
		}
	}


	$changed_settings = true;

	if (isset($_POST['del_account']) && is_array($_POST['acct_list']))
	{
		$del_accts = @$_POST['acct_list'];
		foreach ($del_accts as $acct)
		{
			$uSQL = 'DELETE FROM userids WHERE userid = ' . $acct . ';';
			$_SESSION['vDataStoreDB']->exec($uSQL);				
		}
	}

	if ($changed_settings) {
		SaveSettings($px_Setopts);
	}
	//header("Location: index.php?userId=" . $_SESSION['userId']);
}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/fBSettings.css" />
<style type="text/css">
body {

	background-color: #FFFFFF;

}
</style>
</head>
<body>

<?php
$px_Setopts = LoadSavedSettings();
fBAcctHeader();

echo '<form method="POST" id="main_form" action="index.php?userId=' . $_SESSION['userId'] . '">';
echo '<input type="hidden" name="save_action" value="1" />';
echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '" />';
echo '<br/>';
echo '<table width="100%">';
echo '<tr><td colspan="3" align="center" class="mainBlueTitle"><input type="submit" name="save" value="Save Changes"></td></tr>';
echo '<tr><td valign="top">';
echo '<b>Your current configuration:</b><span style="font:bold 16px serif; color:red; background-color: ;"><br><br>fBSettings v'.FB_SETTINGS_VER.'<br>fBParser v'.FB_PARSER_VER.' </span><br><br>';
echo '</nobr><div>Timezone: <select name="timezone" title="your time zone">';
$timezonefile = './timezone.txt';
if (file_exists($timezonefile)) {
	$timezone = trim(file_get_contents($timezonefile));
} else {
	$timezone = 'America/Los_Angeles';
}
echo '<option selected value=',$timezone,'>',$timezone,'</option>';
$timezonelist =  timezone_identifiers_list();
foreach ($timezonelist as $data) {
	echo '<option value=',$data,'>',$data,'</option>';
}
echo '</select></div><br>';

echo '<div>';
echo '<small>Bot/Account</small><br>';
echo '<input type="checkbox" name="nr_parser" value="1" '.(file_exists($_SESSION['base_path'] . 'notrun_parser.txt')?'checked':'').'>/';
echo '<input type="checkbox" name="nr_parser_a" value="1" '.(file_exists($_SESSION['base_path'] . 'notrun_parser_'.$_SESSION['userId'].'.txt')?'checked':'').'>';
echo '&nbsp;Pause Bot (at start of next cycle)<br>';
echo '</div><br>';
echo 'Switch off following Plugins (on next Cycle)<br>';
echo '&nbsp;&nbsp;<small>Bot/Account</small><br>';
$dir = $_SESSION['base_path'] . 'plugins';
$dh = opendir($dir);

if ($dh) {
	while (($file = readdir($dh)) !== false) {
		if (is_dir($dir . '/' . $file)) {
			if ($file != '.' && $file != '..' && $file != 'fBSettings' && file_exists($dir . '/' . $file . '/main.php')) {
				echo '(';
				echo '<input type="checkbox" name="nr_p_',$file,'" value="1" '.(file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'.txt')?'checked':'').'>/';
				echo '<input type="checkbox" name="nr_p_',$file,'_a" value="1" '.(file_exists($_SESSION['base_path'] . 'notrun_plugin_'.$file.'_'.$_SESSION['userId'].'.txt')?'checked':'').'>';
				echo '&nbsp;' . $file . '<br>';
			}
		}
	}
	closedir($dh);
}
echo '</div>';

echo '<br/>';

echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td valign="top">';
echo '<div>Account list: ';
echo '<input type="submit" name="del_account" value="-" style="width:32px; height:22px; margin-left:25px;" title="Delete Selected Accounts"/><br>';
echo '<select class="acct_list" name="acct_list[]" multiple style="width:250px; height: 150px;" />';

foreach (fBGetUserInfo() as $key=>$acct_item)
{
	echo '   <option value="'.$key.'">' . $acct_item . ' (' . $key . ')</option>';
}
echo '</select>';
echo '</div>';
echo '<div><br />';
echo '<nobr>[<input type="checkbox" name="acceptneighborhelp" value="1" '.($px_Setopts['acceptneighborhelp']?'checked':'').' title="if the checkbox is selected, the bot will accept neighbors help." /> Accept Neighbors Help]</nobr>';
echo '<br/>';
echo '<nobr>[<input type="checkbox" name="lonlyanimals" value="1" '.($px_Setopts['lonlyanimals']?'checked':'').' title="if the checkbox is selected, the bot will check for lonlyanimals. use px_links to get them." /> Check for LonlyAnimals]</nobr>';
echo '<br/><br/><br/>';
echo 'Bot Speed: ';
echo '<select name="bot_speed"><option value="'.$px_Setopts['bot_speed'].'" selected="selected">'.$px_Setopts['bot_speed'].'X</option><option value="1">1X</option><option value="2">2X</option><option value="3">3X</option><option value="4">4X</option><option value="5">5X</option><option value="6">6X</option><option value="7">7X</option><option value="8">8X</option></select><br><br>';
echo '</div>';
?>
</td>
</tr>
<tr><td colspan="3" align="center" class="mainBlueTitle"><input type="submit" name="save" value="Save Changes"></td></tr>
</table>


</form>
</table>
</body>
</html>

<?php


// ------------------------------------------------
// FV_Server - set farmville server
//-------------------------------------------------
function FV_Server($set)
{
	if ($set == "fbdotcom")
	{
		unlink('farmclient.txt');
		unlink('farmserver.txt');
		echo "Restart Bot to change server<br>";
	}
	else if ($set == "fvdotcom")
	{
		echo "Restart Bot to change server<br> If you have problems delete farmserver.txt and farmclient.txt to go back to default<br>";
		$fv_client = 'www.farmville.com/index.php';
		$fv_server = 'www.farmville.com;http://www.farmville.com/flashservices/gateway.php';

		file_put_contents('farmclient.txt',$fv_client);
		file_put_contents('farmserver.txt',$fv_server);
	}
}
?>
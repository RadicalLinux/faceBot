<?php
require_once '../../fB_PluginAPI.php';
$userinfo = fBGetUserInfo();
define('fbBrowserNavigator_version', '1.2');
define('fbBrowserNavigator_date', '23 November 2010');
?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="/plugins/fbBrowserNavigator/css/navigator.css" />
<script type="text/javascript">
	function selectPlugin(plugin){
		document.getElementById('myPlugin').innerHTML = '<object id="tab" type="text/html" data="'+plugin.href+'" style="width:100%; height: 90%;border:0;"><\/object>';
	}
</script>
</head>
<body>
<div class="mainBar">
	<table cellpadding="2" cellspacing="0" width="100%" class="mainBarTable">
		<tr>
			<td align="left" valign="middle">
				&nbsp;&nbsp;<img src="/plugins/fbBrowserNavigator/img/fb_logo.png">
			</td>
			<td align="right">	
				fbBrowserNavigator V<?php echo fbBrowserNavigator_version; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo fbBrowserNavigator_date; ?>&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
	</table>
</div>
<!-- ***************** accounts bar ************************ -->
<table cellpadding="2" cellspacing="1" class="accTable">
<tr>
	<td>
		<form class="accMenu" name="fBAcctForm" method="GET" onsubmit="return false;">Select Account:&nbsp;
			<select class="fBacctBar" name="userId" id="userId" onchange="this.form.submit();">
				<?php
				foreach ($userinfo as $key=>$info)
				{
					$SELECTED = "";
					if ($key == $_SESSION['userId']) $SELECTED = 'selected="selected"';
					echo '<option value="' . $key . '"' . $SELECTED . '>' . $key . ' - ' . $info . '</option>';
				}
				?>
			</select>
		</form>
	</td>
	<td>
		<div align="center" class="accMenu">
			<input class="fBacctBar" type="button" 
				onClick="location.href='index.php?userId=<?php echo $_SESSION['userId']; ?>'" value='Refresh Page'>
		</div>
	</td>
	<td align="center">
		<div>
			<a href="/plugins/fbBrowserNavigator/" target="_blank" class="plugTab">Open in Browser</a>
		</div>
	</td>
</tr>
</table>
<!-- ***************** end accounts bar ************************ -->

<!-- ***************** plugins bar ************************ -->
<div style="margin-top:1px;"></div>
<div style="overflow:auto;overflow-y: hidden;padding:4px 2px; width:99%;height:40px;">
<?php
$dir = $_SESSION['base_path'] . 'plugins';
$dh = opendir($dir);
if ($dh) {
	while (($file = readdir($dh)) !== false) {
		if (is_dir($dir . '/' . $file)) {
			if ($file != '.' && $file != '..' && $file != 'fbBrowserNavigator') {
				echo '<a class="plugTab" href="/plugins/'. $file .'/index.php?userId='.$_SESSION['userId'].'" 
				onclick="selectPlugin(this); return false;">'. $file .'</a>';
			}
		}
	}
	closedir($dh);
}
?>	
</div>
<!-- ***************** end plugins bar ************************ -->

<!-- ***************** selected plugin frame ************************ -->
<div id="myPlugin" style="width:100%; height: 90%;margin-top:10px;">
	<div style="text-align:center; font-weight:bold; color:red; width:500px;">please select a plugin</div>	
</div>
<!-- ***************** end selected plugin frame ************************ -->
</body>
</html>
                                                                                                                                                                                                          
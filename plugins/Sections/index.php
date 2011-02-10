<?
if (!empty($_POST))
{
	$_GET = $_POST;
} else {
	$_POST = $_GET;
}
require_once '../../fB_PluginAPI.php';
include_once('functions.php');
define('sections_Path', '/plugins/Sections/');
define('sections_URL', 'index.php');
define('sections_Version', file_get_contents('Sections.ver'));
define('sections_Version_Date', '');
$objects = @unserialize(fBGetDataStore('objects'));
list($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $flashRevision) = explode(';', fBGetDataStore('playerinfo'));

$sections = array();
$sections = load_array('sections.txt');
if ( !(is_array($sections) && count($sections)) ) { $sections = array(); }
$sections = array_merge($sections); // normalize ids

$vSettings = array();
$vSettings = load_array('settings.txt');
if($vSettings['ratio']<1 || $vSettings['ratio']>5) $vSettings['ratio']=3;

if (isset($_POST['vDoExpension'])) {
	foreach ($sections as $vSNum=>$section) {
		$sections[$vSNum]['top_x']=$sections[$vSNum]['top_x']+8;
		$sections[$vSNum]['top_y']=$sections[$vSNum]['top_y']+8;
		$sections[$vSNum]['bot_x']=$sections[$vSNum]['bot_x']+8;
		$sections[$vSNum]['bot_y']=$sections[$vSNum]['bot_y']+8;
	}
	save_array($sections, 'sections.txt');
}

$is_add = (isset($_POST['add_sec_form']) && strlen($_POST['add_sec_form'])>0);
$is_copy =  (isset($_POST['copy_sec_form']) && strlen($_POST['copy_sec_form'])>0);
$is_edit = (isset($_POST['edit_sec_form']) && strlen($_POST['edit_sec_form'])>0);

if(isset($_POST['vSave'])) {
	if(isset($_POST['ratio'])) $vSettings['ratio']=$_POST['ratio'];
	if($vSettings['ratio']<1 || $vSettings['ratio']>5) $vSettings['ratio']=3;
	$vSettings['map_main']=isset($_POST['map_main'])?$_POST['map_main']:'0';
	$vSettings['map_edit']=isset($_POST['map_edit'])?$_POST['map_edit']:'0';
	$vSettings['map_new']=isset($_POST['map_new'])?$_POST['map_new']:'0';
	if(isset($_POST['tooltip'])) $vSettings['tooltip']=$_POST['tooltip'];
	save_array($vSettings, 'settings.txt');
}


if (isset($_POST['image']) && $_POST['image'] == '1') {

	include('image.php');

} elseif (isset($_POST['js']) && $_POST['js'] == '1') {

	include('wz_tooltip.js');

} elseif (isset($_POST['js']) && $_POST['js'] == '2') {

	include('functions.js');

} elseif (isset($_POST['css']) && $_POST['css'] == '1') {

	include('main.css');

} else {

	if (isset($_POST['add_sec']) || isset($_POST['edit_sec'])) {
		foreach($_POST as $vName => $vValue) {
			if(strpos($vName,'s_a_')!==false && $vValue=='1') $vAnimArray[]=substr($vName,4);
			if(strpos($vName,'s_t_')!==false && $vValue=='1') $vTreeArray[]=substr($vName,4);
			if(strpos($vName,'s_d_')!==false && $vValue=='1') $vDecoArray[]=substr($vName,4);
			if(strpos($vName,'s_b_')!==false && $vValue=='1') $vBuildingArray[]=substr($vName,4);
		}
		if(@$_POST['top_x']<@$_POST['bot_x']) {
			$vTmpX=@$_POST['top_x'];
			@$_POST['top_x']=@$_POST['bot_x'];
			@$_POST['bot_x']=$vTmpX;
		}
		if(@$_POST['top_y']<@$_POST['bot_y']) {
			$vTmpY=@$_POST['top_y'];
			@$_POST['top_y']=@$_POST['bot_y'];
			@$_POST['bot_y']=$vTmpY;
		}
	}

	if (isset($_POST['add_sec'])) {
		$sections[] = array(
        'top_x' => @$_POST['top_x'],
        'top_y' => @$_POST['top_y'],
        'bot_x' => @$_POST['bot_x'],
        'bot_y' => @$_POST['bot_y'],
        'pat'  => @$_POST['pat'],
        'seed'  => @$_POST['seed'],
        'seed2' => @$_POST['seed2'],
        'type'  => @$_POST['type'],
        'anim'  => @implode('|',$vAnimArray),
        'tree'  => @implode('|',$vTreeArray),
        'deco'  => @implode('|',$vDecoArray),
        'building'  => @implode('|',$vBuildingArray),
        'buyanim'  => @$_POST['buyanim'],
        'buytree'  => @$_POST['buytree'],
        'buydeco'  => @$_POST['buydeco'],
        'active' => @$_POST['active'],
        'place' => @$_POST['place'],
        'rotate' => @$_POST['rotate'],
        'walk' => @$_POST['walk']
		);
		save_array($sections, 'sections.txt');
	}

	if (isset($_POST['edit_sec'])) {
		$sections[$_POST['num']] = array(
        'top_x' => @$_POST['top_x'],
        'top_y' => @$_POST['top_y'],
        'bot_x' => @$_POST['bot_x'],
        'bot_y' => @$_POST['bot_y'],
        'pat'  => @$_POST['pat'],
        'seed'  => @$_POST['seed'],
        'seed2' => @$_POST['seed2'],
        'type'  => @$_POST['type'],
        'anim'  => @implode('|',$vAnimArray),
        'tree'  => @implode('|',$vTreeArray),
        'deco'  => @implode('|',$vDecoArray),
        'building'  => @implode('|',$vBuildingArray),
        'buyanim'  => @$_POST['buyanim'],
        'buytree'  => @$_POST['buytree'],
        'buydeco'  => @$_POST['buydeco'],
        'active' => @$_POST['active'],
        'place' => @$_POST['place'],
        'rotate' => @$_POST['rotate'],
        'walk' => @$_POST['walk']
		);
		save_array($sections, 'sections.txt');
	}

	if (isset($_POST['vEditActive'])) {
		$sections[$_POST['vEditActive']]['active'] =  @$_POST['vActiv'];
		save_array($sections, 'sections.txt');
	}

	if (isset($_POST['vEditPlace'])) {
		$sections[$_POST['vEditPlace']]['place'] =  @$_POST['vPlace'];
		save_array($sections, 'sections.txt');
	}

	if (isset($_POST['del_sec'])) {
		$num = @$_POST['num'];
		unset($sections[$num]);
		$sections = array_merge($sections);
		save_array($sections, 'sections.txt');
	}

	?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/index.css">
<script type="text/javascript" src="functions.js"></script>
</head>
<body>
<?php fBAcctHeader(); ?>
<script type="text/javascript" src="wz_tooltip.js"></script>
<MAP name='farm'>
<?php
$item_loc = array();
for ($x = 0; $x < $sizeX; $x++) {
	for ($y = 0; $y < $sizeY; $y++) {
		$item_loc[$x][$y] = (object) array('name' => '', 'icon' => '');
	}
}

foreach($objects as $oID=>$o) {
	#      $u = $units[$o['itemName']];
	$u = Units_GetUnitByName($o['itemName']);

	if (!isset($u['sizeX'])) {
		$u['sizeX'] = 1;
		$u['sizeY'] = 1;
	}

	if ($o['state'] == 'vertical') {
		$t = $u['sizeX'];
		$u['sizeX'] = $u['sizeY'];
		$u['sizeY'] = $t;
	}

	for($x=0;$x < $u['sizeX']; $x++) {
		for($y=0;$y < $u['sizeY']; $y++) {
			$item_loc[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ]->name = Sections_GetRealName(Sections_GetValue($u, 'name'),Sections_GetValue($u, 'realname'));
			$item_loc[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ]->icon = Sections_GetValue($u, 'iconurl');

		}
	}
	$vCntItems[$o['itemName']]++;
	foreach ($sections as $vSNum=>$section) {
		if(
		($o['position']['x'] >= $section['bot_x']) &&
		($o['position']['x'] <= $section['top_x']) &&
		($o['position']['y'] >= $section['bot_y']) &&
		($o['position']['y'] <= $section['top_y'])
		) {
			#          $vSecLoc[$vSNum][(isset($units[$o['itemName']]['realname']) ? $units[$o['itemName']]['realname'] : $units[$o['itemName']]['name']).' ('.$o['itemName'].')']++;
			$vSecLoc[$vSNum][Units_GetRealnameByName($o['itemName']).' ('.$o['itemName'].')']++;
			$vSecItems[$o['itemName']][$vSNum]++;
			for($x=0;$x < $u['sizeX']; $x++) {
				for($y=0;$y < $u['sizeY']; $y++) {
					$item_loc[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ]->snum = $vSNum;
					$item_loc[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ]->stype = $section['type'];
				}
			}
			continue;
		}
	}
}

$local_path = '/';
$remote_path = 'http://static.farmville.com/v' . $flashRevision.'/';


if (
($vSettings['map_main']=='1' && (isset($_POST['add_sec_form']) || count($sections) == 0)) ||
($vSettings['map_edit']=='1' && (isset($_POST['edit_sec_form']) || isset($_POST['copy_sec_form']))) ||
($vSettings['map_new']=='1' && ( !( isset($_POST['add_sec_form']) || count($sections) == 0 || isset($_POST['edit_sec_form']) || isset($_POST['copy_sec_form']))  ))
) {

	for ($x = 0; $x < $sizeX; $x++) {
		for ($y = 0; $y < $sizeY; $y++) {
			$vRest='';
			if(isset($item_loc[$x][$y]->snum)) {
				$vAppend='<br>in Section '.$item_loc[$x][$y]->snum.' ('.$item_loc[$x][$y]->stype.')<br>';
				$vAppend2='click to edit Section';
				$vAppend3=' onclick=window.location.href="index.php?userId=' . $_SESSION['userId'] . '&edit_sec_form=Edit&num='.$item_loc[$x][$y]->snum.'"';
			} else {
				$vAppend='';
				$vAppend2='';
				$vAppend3='';
			}
			if($vSettings['tooltip']=='small') {
				if (isset($_POST['add_sec_form']) ||
				isset($_POST['edit_sec_form']) ||
				isset($_POST['copy_sec_form']) ||
				count($sections) == 0)
				{
					$vRest=" onmouseover=\"Tip('<i>click to set<br><b>'+vTopBot+'</b><br>coordinates</i>', TITLE, '".$x." - ".$y."', DELAY, 1)\" onmouseout=\"UnTip()\" onclick=\"fClick(".$x.",".$y.");this.onmouseover();\"";
				} else {
					$vRest=" onmouseover=\"Tip('".$vAppend2."', TITLE, '".$x."-".$y."', DELAY, 1)\" onmouseout=\"UnTip()\" ".$vAppend3;
				}
			}
			if($vSettings['tooltip']=='full') {
				$info = $item_loc[$x][$y]->name ? str_replace("'" , "\\'" , $item_loc[$x][$y]->name) . "<br/><img src=\\'/".$item_loc[$x][$y]->icon . "\\' width=100 height=100/>" : "";
				if(strlen($info)==0) $info='empty place';
				if (isset($_POST['add_sec_form'])  ||
				isset($_POST['edit_sec_form']) ||
				isset($_POST['copy_sec_form']) ||
				count($sections) == 0)
				{
					$vRest=" onmouseover=\"Tip('".$info.$vAppend."<br><i>click to set<br><b>'+vTopBot+'</b><br>coordinates</i>', TITLE, '".$x." - ".$y."', DELAY, 1)\" onmouseout=\"UnTip()\" onclick=\"fClick(".$x.",".$y.");this.onmouseover();\"";
				} else {
					$vRest=" onmouseover=\"Tip('".$info.$vAppend.$vAppend2."', TITLE, '".$x."-".$y."', DELAY, 1)\" onmouseout=\"UnTip()\" ".$vAppend3;
				}
			}
			Sections_Draw_MapThing($x, $y, 1, 1, $vSettings['ratio'], $vRest);
		}
	}
}

?>
</MAP>
	<h1>Sections <?php echo sections_Version; ?><font size=+1> by
	ralphm2004 <?php echo sections_Version_Date; ?></font></h1>
	<span style="font:bold 16px; color:red;"  onclick="fShowHide('tab_setting')"><small>Show/Hide Settings</small></span>
	<br>
	<table border="0" cellspacing=0 cellpadding=0 width="100%"
		style="display: none" id="tab_setting">
		<tr>
			<td>
			<form action="index.php" method="post">
			<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>">
			
			<table border="0" cellspacing="0" cellpadding="4">
				<tr valign=top>
					<td><?php
					echo   'Zoom: <select name="ratio">';
					echo    '<option value="1" '.(($vSettings['ratio'] == '1') ? 'selected' : '') .'>50%</option>';
					echo    '<option value="2" '.(($vSettings['ratio'] == '2') ? 'selected' : '') .'>75%</option>';
					echo    '<option value="3" '.(($vSettings['ratio'] == '3') ? 'selected' : '') .'>100%</option>';
					echo    '<option value="4" '.(($vSettings['ratio'] == '4') ? 'selected' : '') .'>150%</option>';
					echo    '<option value="5" '.(($vSettings['ratio'] == '5') ? 'selected' : '') .'>200%</option>';
					echo   '</select>';
					?></td>
					<td>
					<table border="0" cellspacing="0" cellpadding="0">
						<tr valign=top>
							<td>Show Map:</td>
							<td><?php
							echo   '<input type="checkbox" name="map_main" value="1" ' . ($vSettings['map_main']<>0 ? 'checked ' : ''). '>&nbsp;<small>Main Screen</small><br>';
							echo   '<input type="checkbox" name="map_edit" value="1" ' . ($vSettings['map_edit']<>0 ? 'checked ' : ''). '>&nbsp;<small>Edit Screen</small><br>';
							echo   '<input type="checkbox" name="map_new" value="1" ' . ($vSettings['map_new']<>0 ? 'checked ' : ''). '>&nbsp;<small>New Screen</small><br>';
							?></td>
						</tr>
					</table>
					</td>
					<td>
					<table border="0" cellspacing="0" cellpadding="0">
						<tr valign=top>
							<td>Show Tooltip:</td>
							<td><?php
							echo   '<input type="radio" name="tooltip" value="full" ' . ($vSettings['tooltip']=='full' ? 'checked ' : ''). '>&nbsp;<small>Full</small><br>';
							echo   '<input type="radio" name="tooltip" value="small" ' . ($vSettings['tooltip']=='small' ? 'checked ' : ''). '>&nbsp;<small>Coordinates</small><br>';
							echo   '<input type="radio" name="tooltip" value="off" ' . ($vSettings['tooltip']=='off' ? 'checked ' : ''). '>&nbsp;<small>Off</small><br>';
							?></td>
						</tr>
					</table>
					</td>
					<td>
					<button type="submit" name="vSave" value="Save"
						onmouseover="this.className='hover';"
						onmouseout="this.className='';">Save</button>
					</td>
				</tr>
			</table>
			</form>
			</td>
		</tr>
	</table>

	<?php
	if (!function_exists("gd_info")) {
		echo "<font size='+4' color='red'>GD is required to render the farm map image. Please enable it. (See the readme file on how to do this).</font>";
	} else {
		if (isset($_POST['add_sec_form']) || count($sections) == 0) {
			if($vSettings['map_new']=='1') {
				echo "<img src='index.php?userId=" . $_SESSION['userId'] . "&image=1"
				."&edit_sec_form=".(isset($_POST['edit_sec_form'])?$_POST['edit_sec_form']:'')
				."&copy_sec_form=".(isset($_POST['copy_sec_form'])?$_POST['copy_sec_form']:'')
				."&num=".(isset($_POST['num'])?$_POST['num']:'')
				."' USEMAP='#farm' border=0 style='cursor:crosshair;'/><br/>"
				."<small>Hover over the image to see coordinates, click to set top/bottom coordinates</small><br/>\n";
			}
		} elseif (isset($_POST['edit_sec_form']) || isset($_POST['copy_sec_form'])) {
			if($vSettings['map_edit']=='1') {
				echo "<img src='index.php?userId=" . $_SESSION['userId'] . "&image=1"
				."&edit_sec_form=".(isset($_POST['edit_sec_form'])?$_POST['edit_sec_form']:'')
				."&copy_sec_form=".(isset($_POST['copy_sec_form'])?$_POST['copy_sec_form']:'')
				."&num=".(isset($_POST['num'])?$_POST['num']:'')
				."' USEMAP='#farm' border=0 style='cursor:crosshair;'/><br/>"
				."<small>Hover over the image to see coordinates, click to set top/bottom coordinates</small><br/>\n";
			}
		} else {
			if($vSettings['map_main']=='1') {
				echo "<img src='index.php?userId=" . $_SESSION['userId'] . "&image=1"
				."' USEMAP='#farm' border=0 style='cursor:crosshair;'/><br/>"
				."<small>Hover over the image to see coordinates</small><br/>\n";
			}
		}
	}

	#    $vSectionUnitsArray=Sections_GetUnits($units,$flashRevision);
	$vSectionUnitsArray=Sections_GetUnits($flashRevision);
	$vSeeds=$vSectionUnitsArray['vSeeds'];
	$vAnimals=$vSectionUnitsArray['vAnimals'];
	$vTrees=$vSectionUnitsArray['vTrees'];
	$vDecorations=$vSectionUnitsArray['vDecorations'];
	$vBuildings=$vSectionUnitsArray['vBuildings'];
	$vBuyAnimals=$vSectionUnitsArray['vBuyAnimals'];
	$vBuyTrees=$vSectionUnitsArray['vBuyTrees'];
	$vBuyDecorations=$vSectionUnitsArray['vBuyDecorations'];
	$vAnimalUnits=$vSectionUnitsArray['vAnimalUnits'];
	$vAll=$vSectionUnitsArray['vAll'];

	if (isset($_POST['add_sec_form'])   ||
	isset($_POST['copy_sec_form']) ||
	isset($_POST['edit_sec_form']) ||
	count($sections) == 0)
	{

		if ( isset($_POST['edit_sec_form']) )
		{
			echo '<h4>Edit section '.$_POST['num'].'</h4>';
			$action = 'edit_sec';
			$thissec = $sections[$_POST['num']];
		}
		elseif ( isset($_POST['copy_sec_form']) )
		{
			echo '<h4>Copy section '.$_POST['num'].'</h4>';
			$action = 'add_sec';
			$thissec = $sections[$_POST['num']];
			$_POST['num']='';
		}
		else
		{
			echo '<h4>Add Section Below:</h4>';
			$action = 'add_sec';
			$thissec = array(
         'top_x'  => 8,
         'top_y'  => 8,
         'bot_x'  => 0,
         'bot_y'  => 0,
         'pat'  => 'none',
         'seed'  => '---',
         'seed2'  => '---',
         'type'  => 'seed',
         'anim'  => '',
         'tree'  => '',
         'deco'  => '',
         'buyanim' => '',
         'buytree' => '',
         'buydeco' => '',
         'active' => 1,
         'place' => ''
         );
		}

		$vGiftboxContent=Sections_GetGiftboxContent();

		echo '<form action="index.php" method="post" name=myform>';
		echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
		echo '<button type="submit" name="'. $action .'" value="Save" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Save</button>&nbsp;';
		echo '<button type="submit" name="undo" value="Back" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Back</button>';
		echo '<input type="hidden" name="num" value="'. @$_POST['num'] .'">';
		echo '<br/><br/>';
		echo '<fieldset class="cream">';
		echo   '<small>A Section consists of a square located on the map above. Hover over the map, and find the coordinates of the <b>top</b> and <b>bottom</b> corners of the square you want to define your section in.</small><br/>';
		echo   '<span style="width: 50px">Active</span>';
		echo   '<input type="checkbox" name="active" value="1" ' . ($thissec['active']<>0 ? 'checked ' : ''). '>&nbsp;&nbsp; <small>(Switch this Section on/off)</small><br>';
		echo   '<span style="width: 50px">Place/Plow</span>';
		echo   '<input type="checkbox" name="place" value="1" ' . ($thissec['place']==1 ? 'checked ' : ''). '>&nbsp;&nbsp; <small>(Place Items from GiftBox / Plow plot</small><br>';
		echo   '<span style="width: 150px">Top corner:</span>';
		echo   '<input type="text" name="top_x" id="top_x" value="'. $thissec['top_x'] .'" size=2 />';
		echo   '&nbsp;&nbsp;-&nbsp;&nbsp;';
		echo   '<input type="text" name="top_y" id="top_y" value="'. $thissec['top_y'] .'" size=2 />';
		echo   '<br/>';
		echo   '<span style="width: 150px">Bottom corner:</span>';
		echo   '<input type="text" name="bot_x" id="bot_x" value="'. $thissec['bot_x'] .'" size=2 />';
		echo   '&nbsp;&nbsp;-&nbsp;&nbsp;';
		echo  '<input type="text" name="bot_y" id="bot_y" value="'. $thissec['bot_y'] .'" size=2 />';
		echo  '<br/>';
		echo '</fieldset>';
		echo '<br/>';

		echo '<fieldset class="cream">';
		echo  '<legend>';
		echo   '<b>Section Type:</b>&nbsp;';

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'seed') {
			echo   '<input onclick="fShowTab(\'tab_seed\')" type=radio value="seed" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'seed' ? 'checked' : '') . '>Seed &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'anim') {
			echo   '<input onclick="fShowTab(\'tab_anim\')" type=radio value="anim" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'anim' ? 'checked' : '') . '>Animal &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'tree') {
			echo  '<input onclick="fShowTab(\'tab_tree\')" type=radio value="tree" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'tree' ? 'checked' : '') . '>Tree &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'deco') {
			echo  '<input onclick="fShowTab(\'tab_deco\')" type=radio value="deco" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'deco' ? 'checked' : '') . '>Decoration &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'building') {
			echo  '<input onclick="fShowTab(\'tab_building\')" type=radio value="building" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'building' ? 'checked' : '') . '>Buildings &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buyanim') {
			echo  '<input onclick="fShowTab(\'tab_buyanim\')" type=radio value="buyanim" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'buyanim' ? 'checked' : '') . '>Buy Animal &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buytree') {
			echo  '<input onclick="fShowTab(\'tab_buytree\')" type=radio value="buytree" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'buytree' ? 'checked' : '') . '>Buy Tree &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buydeco') {
			echo  '<input onclick="fShowTab(\'tab_buydeco\')" type=radio value="buydeco" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'buydeco' ? 'checked' : '') . '>Buy Decoration &nbsp;&nbsp;&nbsp;';
		}
		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'dontmove') {
			echo  '<input onclick="fShowTab(\'tab_dontmove\')" type=radio value="dontmove" name="type" '
			.(@isset($thissec['type']) && $thissec['type'] == 'dontmove' ? 'checked' : '') . '>Don\'t Move &nbsp;&nbsp;&nbsp;';
		}

		echo   '</legend>';

		echo '<br>';

		######################################## seed

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'seed') {
			echo   '<table id="tab_seed" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: '
			. (@isset($thissec['type']) && $thissec['type'] == 'seed' ? 'block' : 'none') . '">';
			echo    '<tr><td>';
			echo    '<span style="width: 150px">Pattern:</span>';
			echo     '<select name="pat" id="pat">';
			echo       '<option value="none" '      .(($thissec['pat'] == 'none')       ? 'selected' : '') .'>None (just use first seed)</option>';
			echo      '<option value="checkerboard" ' .(($thissec['pat'] == 'checkerboard')  ? 'selected' : '') .'>Checkerboard</option>';
			echo      '<option value="striped-row" '  .(($thissec['pat'] == 'striped-row')  ? 'selected' : '') .'>Striped (north to south)</option>';
			echo      '<option value="striped-col" '  .(($thissec['pat'] == 'striped-col')  ? 'selected' : '') .'>Striped (east to west)</option>';
			echo      '<option value="squared1" '    .(($thissec['pat'] == 'squared1')    ? 'selected' : '') .'>Squared variant 1</option>';
			echo      '<option value="squared2" '    .(($thissec['pat'] == 'squared2')    ? 'selected' : '') .'>Squared variant 2</option>';
			echo      '<option value="corner-n" '    .(($thissec['pat'] == 'corner-n')    ? 'selected' : '') .'>Corner (north) </option>';
			echo      '<option value="corner-e" '    .(($thissec['pat'] == 'corner-e')    ? 'selected' : '') .'>Corner (east)</option>';
			echo      '<option value="corner-s" '    .(($thissec['pat'] == 'corner-s')    ? 'selected' : '') .'>Corner (south)</option>';
			echo      '<option value="corner-w" '    .(($thissec['pat'] == 'corner-w')    ? 'selected' : '') .'>Corner (west)</option>';
			echo    '</select><br/>';
			echo    '<span style="width: 150px">First Seed:</span>';
			echo    '<select name="seed" id="seed">';
			echo      '<option '. ($thissec['seed'] == '---'||$thissec['seed']=='' ? 'selected ' : '') .'value="---">please select</option>';
			echo      '<option '. ($thissec['seed'] == 'just_plow' ? 'selected ' : '') .'value="just_plow">place/plow plot, dont seed it</option>';
			foreach($vSeeds as $vName=>$vRealName) {
				echo     '<option '. ($thissec['seed'] == htmlentities($vName) ? 'selected ' : '') .'value="'.htmlentities($vName).'">'
				.htmlentities($vRealName.' ('.$vName.')')
				.'</option>';
			}
			echo '<option>---- Seeder-Glitch ----</option>';
			foreach($vAll as $vName=>$vRealName) {
				echo     '<option '. ($thissec['seed'] == htmlentities($vName) ? 'selected ' : '') .'value="'.htmlentities($vName).'">'
				.htmlentities($vRealName.' ('.$vName.')')
				.'</option>';
			}
			echo     '</select><br/>';
			echo     '<span style="width: 150px">Second Seed:</span>';
			echo     '<select name="seed2" id="seed2">';
			echo       '<option '. ($thissec2['seed'] == '---'||$thissec2['seed']=='' ? 'selected ' : '') .'value="---">please select</option>';
			foreach($vSeeds as $vName=>$vRealName) {
				echo     '<option '. ($thissec['seed2'] == htmlentities($vName) ? 'selected ' : '') .'value="'.htmlentities($vName).'">'
				.htmlentities($vRealName.' ('.$vName.')')
				.'</option>';
			}
			echo     '</select> ';
			echo    '(Used When A Pattern Is Selected)<br/>';
			echo   '</td></tr>';
			echo  '</table>';
		}

		######################################## anim

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'anim') {
			echo '<script>';
			echo 'function fCheckUncheckAnimUnit(vUnit,vCheck){',"\n";
			foreach($vAnimalUnits as $vUnitName=>$vArray) {
				echo 'if(vUnit=="',$vUnitName,'" || vUnit=="all") {',"\n";
				foreach($vArray as $vName=>$Tmp) {
					echo 'document.forms["myform"].elements["s_a_'.htmlentities($vName).'"].checked=vCheck;',"\n";
				}
				echo '}',"\n";
			}
			echo 'document.forms["myform"].elements["vCheckAllAnimal"].value="---"',"\n";
			echo 'document.forms["myform"].elements["vUnCheckAllAnimal"].value="---"',"\n";
			echo '}',"\n";
			echo '</script>',"\n";
			echo '<table id="tab_anim" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: ';
			echo   (@isset($thissec['type']) && $thissec['type'] == 'anim' ? 'block' : 'none') . '">';
			echo   '<tr><td>';
			echo '<table border="0" cellspacing="1" cellpadding="0" class="cream2"><tr valign=top><td>';
			echo 'Walk:&nbsp;';
			echo '</td><td>';
			echo '<input type="radio" name="walk" value="nothing" ',(@isset($thissec['walk'])&&$thissec['walk']=='nothing'||$thissec['walk']==''?'checked':''),'>&nbsp;dont change<br>';
			echo '<input type="radio" name="walk" value="walk" ',(@isset($thissec['walk'])&&$thissec['walk']=='walk'?'checked':''),'>&nbsp;allow animals to walk<br>';
			echo '<input type="radio" name="walk" value="stay" ',(@isset($thissec['walk'])&&$thissec['walk']=='stay'?'checked':''),'>&nbsp;forbid animals to walk<br>';
			echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td>';
			echo 'Rotate:&nbsp;';
			echo '</td><td>';
			echo '<input type="radio" name="rotate" value="nothing" ',(@isset($thissec['rotate'])&&$thissec['rotate']=='nothing'||$thissec['rotate']==''?'checked':''),'>&nbsp;dont change<br>';
			echo '<table border="0" cellspacing="0" cellpadding="0" class="cream2">
      <tr>
        <td align="right">top-left&nbsp;<input type="radio" name="rotate" value="3" ',(@isset($thissec['rotate'])&&$thissec['rotate']=='3'?'checked':''),'></td>
        <td><input type="radio" name="rotate" value="2" ',(@isset($thissec['rotate'])&&$thissec['rotate']=='2'?'checked':''),'>&nbsp;top-right</td>
      </tr>
      <tr>
        <td align="right">bottom-left&nbsp;<input type="radio" name="rotate" value="0" ',(@isset($thissec['rotate'])&&$thissec['rotate']=='0'?'checked':''),'></td>
        <td><input type="radio" name="rotate" value="1" ',(@isset($thissec['rotate'])&&$thissec['rotate']=='1'?'checked':''),'>&nbsp;bottom-right</td>
      </tr>
    </table>';

			echo '</td></tr></table><br>';
			echo '&nbsp;Select: <select name="vCheckAllAnimal" onchange="fCheckUncheckAnimUnit(this.value,true)"><option value="---">please select</option><option value="all">all animals</option>',"\n";
			foreach($vAnimalUnits as $vUnitName=>$vTmp) {
				echo '<option value="',$vUnitName,'">',$vUnitName,'</option>',"\n";
			}
			echo '</select>',"\n";
			echo '&nbsp;UnSelect: <select name="vUnCheckAllAnimal" onchange="fCheckUncheckAnimUnit(this.value,false)"><option value="---">please select</option><option value="all">all animals</option>',"\n";
			foreach($vAnimalUnits as $vUnitName=>$vTmp) {
				echo '<option value="',$vUnitName,'">',$vUnitName,'</option>',"\n";
			}
			echo '</select>',"\n";
			echo '<br>';
			echo '<table class="cream" border="0" cellspacing="0" cellpadding="0">';
			echo  '<tr bgcolor=white>';
			echo    '<th></th><th>RealName</th><th>Name</th><th>In Giftbox</th><th>In Sections</th><th>On the Farm</th>';
			echo  '</tr>';
			$vArray=explode('|',$thissec['anim']);
			foreach($vAnimals as $vName=>$vRealName) {
				echo '<tr bgcolor=white valign=top>';
				echo '<td>';
				echo '<input type="checkbox" name="s_a_',htmlentities($vName),'" value="1" ';
				echo (in_array(htmlentities($vName),$vArray) ? 'checked ':''),'>';
				echo '</td><td>',$vRealName,'</td><td>',$vName,'</td>';


				echo '<td>';
				$vCntGB=0;
				foreach($vGiftboxContent['animal'] as $vItem) {
					if($vItem['name']==$vName) $vCntGB++;
				}
				if($vCntGB>0) echo '<nobr>',$vCntGB,'x in GiftBox&nbsp;&nbsp;</nobr> ';
				echo '</td>';
				echo '<td>';
				$vCntInSections=0;
				foreach($vSecItems[$vName] as $vSecNum => $count) {
					echo '<nobr>',$count,'x in S.',$vSecNum,'&nbsp;&nbsp;</nobr> ';
					$vCntInSections+=$count;
				}

				echo '</td>';
				echo '<td>';
				$vCntWorld=$vCntItems[$vName];
				if($vCntWorld>0) {
					echo '<nobr>',$vCntWorld,'x on Farm';
					if($vCntInSections<>$vCntWorld && $vCntInSections>0) echo ' / ',($vCntWorld-$vCntInSections),'x not in Sections';
					echo '</nobr>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
			echo   '</td></tr>';
			echo  '</table>';
		}

		######################################## tree

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'tree') {
			echo '<script>';
			echo 'function fCheckUncheckTree(vCheck){',"\n";
			foreach($vTrees as $vName=>$vRealName) {
				echo 'document.forms["myform"].elements["s_t_'.htmlentities($vName).'"].checked=vCheck;',"\n";
			}
			echo '}',"\n";
			echo '</script>';
			echo '<table id="tab_tree" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: ';
			echo   (@isset($thissec['type']) && $thissec['type'] == 'tree' ? 'block' : 'none') . '">';
			echo   '<tr><td>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:fCheckUncheckTree(true)">Select All</a>';
			echo '&nbsp; <a href="javascript:fCheckUncheckTree(false)">UnSelect All</a><br>';
			echo '<table class="cream" border="0" cellspacing="0" cellpadding="0">';
			echo  '<tr bgcolor=white>';
			echo    '<th></th><th>RealName</th><th>Name</th><th>In Giftbox</th><th>In Sections</th><th>On the Farm</th>';
			echo  '</tr>';
			$vArray=explode('|',$thissec['tree']);
			foreach($vTrees as $vName=>$vRealName) {
				echo '<tr bgcolor=white valign=top>';
				echo '<td>';
				echo '<input type="checkbox" name="s_t_',htmlentities($vName),'" value="1" ';
				echo (in_array(htmlentities($vName),$vArray) ? 'checked ':''),'>';
				echo '</td><td>',$vRealName,'</td><td>',$vName,'</td>';

				echo '<td>';
				$vCntGB=0;
				foreach($vGiftboxContent['tree'] as $vItem) {
					if($vItem['name']==$vName) $vCntGB++;
				}
				if($vCntGB>0) echo '<nobr>',$vCntGB,'x in GiftBox&nbsp;&nbsp;</nobr> ';
				echo '</td>';
				echo '<td>';
				$vCntInSections=0;
				foreach($vSecItems[$vName] as $vSecNum => $count) {
					echo '<nobr>',$count,'x in S.',$vSecNum,'&nbsp;&nbsp;</nobr> ';
					$vCntInSections+=$count;
				}

				echo '</td>';
				echo '<td>';
				$vCntWorld=$vCntItems[$vName];
				if($vCntWorld>0) {
					echo '<nobr>',$vCntWorld,'x on Farm';
					if($vCntInSections<>$vCntWorld && $vCntInSections>0) echo ' / ',($vCntWorld-$vCntInSections),'x not in Sections';
					echo '</nobr>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
			echo   '</td></tr>';
			echo  '</table>';
		}

		######################################## deco

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'deco') {
			$vEggNewNameArray[]='egg_white';     $vEggOldNameArray[]='mysteryeggwhite';
			$vEggNewNameArray[]='egg_brown';     $vEggOldNameArray[]='mysteryeggbrown';
			$vEggNewNameArray[]='egg_black';     $vEggOldNameArray[]='mysteryeggblack';
			$vEggNewNameArray[]='egg_gold';      $vEggOldNameArray[]='mysteryegggold';
			$vEggNewNameArray[]='egg_cornish';   $vEggOldNameArray[]='mysteryeggcornish';
			$vEggNewNameArray[]='egg_rhodered';  $vEggOldNameArray[]='mysteryeggrhodered';
			$vEggNewNameArray[]='egg_scotsgrey'; $vEggOldNameArray[]='mysteryeggscotsgrey';

			echo '<script>';
			echo 'function fCheckUncheckDeco(vCheck){',"\n";
			foreach($vDecorations as $vName=>$vRealName) {
				echo 'document.forms["myform"].elements["s_d_'.htmlentities($vName).'"].checked=vCheck;',"\n";
			}
			echo '}',"\n";
			echo '</script>';
			echo '<table id="tab_deco" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: ';
			echo   (@isset($thissec['type']) && $thissec['type'] == 'deco' ? 'block' : 'none') . '">';
			echo   '<tr><td>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:fCheckUncheckDeco(true)">Select All</a>';
			echo '&nbsp; <a href="javascript:fCheckUncheckDeco(false)">UnSelect All</a><br>';
			echo '<table class="cream" border="0" cellspacing="0" cellpadding="0">';
			echo  '<tr bgcolor=white>';
			echo    '<th></th><th>RealName</th><th>Name</th><th>In Giftbox</th><th>In Sections</th><th>On the Farm</th>';
			echo  '</tr>';
			$vArray=explode('|',$thissec['deco']);
			foreach($vDecorations as $vName=>$vRealName) {
				echo '<tr bgcolor=white valign=top>';
				echo '<td>';
				echo '<input type="checkbox" name="s_d_',htmlentities($vName),'" value="1" ';
				echo (in_array(htmlentities($vName),$vArray) ? 'checked ':''),'>';
				echo '</td><td>',$vRealName,'</td><td>',$vName,'</td>';

				echo '<td>';
				$vCntGB=0;
				foreach($vGiftboxContent['decoration'] as $vItem) {
					if(str_replace($vEggOldNameArray,$vEggNewNameArray,$vItem['name'])==$vName) $vCntGB++;
				}
				if($vCntGB>0) echo '<nobr>',$vCntGB,'x in GiftBox&nbsp;&nbsp;</nobr> ';
				echo '</td>';
				echo '<td>';
				$vCntInSections=0;

				if(strpos($vName,'egg_')!==false) {
					foreach($vSecItems[str_replace($vEggNewNameArray,$vEggOldNameArray,$vName)] as $vSecNum => $count) {
						echo '<nobr>',$count,'x in S.',$vSecNum,'&nbsp;&nbsp;</nobr> ';
						$vCntInSections+=$count;
					}
					$vCntWorld=$vCntItems[str_replace($vEggNewNameArray,$vEggOldNameArray,$vName)];
				} else {
					foreach($vSecItems[$vName] as $vSecNum => $count) {
						echo '<nobr>',$count,'x in S.',$vSecNum,'&nbsp;&nbsp;</nobr> ';
						$vCntInSections+=$count;
					}
					$vCntWorld=$vCntItems[$vName];
				}

				echo '</td>';
				echo '<td>';

				if($vCntWorld>0) {
					echo '<nobr>',$vCntWorld,'x on Farm';
					if($vCntInSections<>$vCntWorld && $vCntInSections>0) echo ' / ',($vCntWorld-$vCntInSections),'x not in Sections';
					echo '</nobr>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
			echo   '</td></tr>';
			echo  '</table>';
		}

		######################################## building

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'building') {

			echo '<script>';
			echo 'function fCheckUncheckBuilding(vCheck){',"\n";
			foreach($vBuildings as $vName=>$vRealName) {
				echo 'document.forms["myform"].elements["s_d_'.htmlentities($vName).'"].checked=vCheck;',"\n";
			}
			echo '}',"\n";
			echo '</script>';
			echo '<table id="tab_building" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: ';
			echo   (@isset($thissec['type']) && $thissec['type'] == 'building' ? 'block' : 'none') . '">';
			echo   '<tr><td>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:fCheckUncheckBuilding(true)">Select All</a>';
			echo '&nbsp; <a href="javascript:fCheckUncheckBuilding(false)">UnSelect All</a><br>';
			echo '<table class="cream" border="0" cellspacing="0" cellpadding="0">';
			echo  '<tr bgcolor=white>';
			echo    '<th></th><th>RealName</th><th>Name</th><th>In Giftbox</th><th>In Sections</th><th>On the Farm</th>';
			echo  '</tr>';
			$vArray=explode('|',$thissec['building']);
			foreach($vBuildings as $vName=>$vRealName) {
				echo '<tr bgcolor=white valign=top>';
				echo '<td>';
				echo '<input type="checkbox" name="s_b_',htmlentities($vName),'" value="1" ';
				echo (in_array(htmlentities($vName),$vArray) ? 'checked ':''),'>';
				echo '</td><td>',$vRealName,'</td><td>',$vName,'</td>';

				echo '<td>';
				$vCntGB=0;
				foreach($vGiftboxContent['building'] as $vItem) {
					if($vItem['name']==$vName) $vCntGB++;
				}
				if($vCntGB>0) echo '<nobr>',$vCntGB,'x in GiftBox&nbsp;&nbsp;</nobr> ';
				echo '</td>';
				echo '<td>';
				$vCntInSections=0;

				foreach($vSecItems[$vName] as $vSecNum => $count) {
					echo '<nobr>',$count,'x in S.',$vSecNum,'&nbsp;&nbsp;</nobr> ';
					$vCntInSections+=$count;
				}
				$vCntWorld=$vCntItems[$vName];

				echo '</td>';
				echo '<td>';

				if($vCntWorld>0) {
					echo '<nobr>',$vCntWorld,'x on Farm';
					if($vCntInSections<>$vCntWorld && $vCntInSections>0) echo ' / ',($vCntWorld-$vCntInSections),'x not in Sections';
					echo '</nobr>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
			echo   '</td></tr>';
			echo  '</table>';
		}

		######################################## buyanim

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buyanim') {
			echo   '<table id="tab_buyanim" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: '
			. (@isset($thissec['type']) && $thissec['type'] == 'buyanim' ? 'block' : 'none') . '">';
			echo     '<tr><td>';
			echo '<select name="buyanim" id="buyanim">';
			echo '<option ',$thissec['buyanim']=='---'||$thissec['buyanim']==''?'selected ':'','value="---">please select</option>';
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Coins ===</option>';
			foreach($vBuyAnimals['coins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buyanim'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Cash ===</option>';
			foreach($vBuyAnimals['cash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buyanim'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Coins ===</option>';
			foreach($vBuyAnimals['nocoins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buyanim'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Cash ===</option>';
			foreach($vBuyAnimals['nocash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buyanim'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '</select>';
			echo    '</td></tr>';
			echo  '</table>';
		}

		######################################## buytree

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buytree') {
			echo   '<table id="tab_buytree" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: '
			. (@isset($thissec['type']) && $thissec['type'] == 'buytree' ? 'block' : 'none') . '">';
			echo     '<tr><td>';
			echo '<select name="buytree" id="buytree">';
			echo '<option ',$thissec['buytree']=='---'||$thissec['buytree']==''?'selected ':'','value="---">please select</option>';
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Coins ===</option>';
			foreach($vBuyTrees['coins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buytree'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Cash ===</option>';
			foreach($vBuyTrees['cash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buytree'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Coins ===</option>';
			foreach($vBuyTrees['nocoins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buytree'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Cash ===</option>';
			foreach($vBuyTrees['nocash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buytree'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '</select>';
			echo    '</td></tr>';
			echo  '</table>';
		}

		######################################## buydeco

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'buydeco') {
			echo   '<table id="tab_buydeco" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: '
			. (@isset($thissec['type']) && $thissec['type'] == 'buydeco' ? 'block' : 'none') . '">';
			echo     '<tr><td>';
			echo '<select name="buydeco" id="buydeco">';
			echo '<option ',$thissec['buydeco']=='---'||$thissec['buydeco']==''?'selected ':'','value="---">please select</option>';
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Coins ===</option>';
			foreach($vBuyDecorations['coins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buydeco'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Buyable, Cash ===</option>';
			foreach($vBuyDecorations['cash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buydeco'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Coins ===</option>';
			foreach($vBuyDecorations['nocoins'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buydeco'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '<option style="background-color:#dddddd" value="">==== Not Buyable, Cash ===</option>';
			foreach($vBuyDecorations['nocash'] as $vName=>$vRealName) {
				echo '<option ',$thissec['buydeco'] == htmlentities($vName)?'selected ':'','value="'.htmlentities($vName).'">'.htmlentities($vRealName.' ('.$vName.')').'</option>';
			}
			echo '</select>';
			echo    '</td></tr>';
			echo  '</table>';
		}

		######################################## dontmove

		if(!isset($_POST['edit_sec_form']) || $thissec['type'] == 'dontmove') {
			echo   '<table id="tab_dontmove" border="0" cellspacing=0 cellpadding=0 width="100%" style="display: '
			. (@isset($thissec['type']) && $thissec['type'] == 'dontmove' ? 'block' : 'none') . '">';
			echo     '<tr><td>';
			echo    '</td></tr>';
			echo  '</table>';
		}

		echo '</fieldset><br/>';

		echo '<button type="submit" name="'.$action.'" value = "Save" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Save</button>&nbsp;';
		echo '<button type="submit" name="undo" value="Back" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Back</button>';
		echo '</form>';

	} else {

		echo '<table border="0" cellspacing="7" cellpadding="0"><tr valign=center>';
		echo '<td><big><b>Sections</b></big></td>';
		echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
		echo '<form action="index.php" method="post">';
		echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
		echo '<td>';
		echo   '<button type="submit" name="add_sec_form" value="Add section" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Add Section</button>';
		echo '</td>';
		echo '</form>';
		echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
		echo '<form action="index.php" method="post">';
		echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
		echo '<input type="hidden" name="vDoExpension" value="on">';
		echo '<td>';
		echo   '<button onclick="javascript:if(confirm(\'*REALLY* UPGRADED FARM? All Sections get move 8x8 Spots\')){this.form.submit()}" type="button" name="farm_expension" value="Farm Expension" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Farm Expansion</button>';
		echo '</td>';
		echo '</form>';
		echo '</tr></table>';


		echo '<table class="cream" border="0" cellspacing="0" cellpadding="0">';
		echo  '<tr bgcolor=white>';
		echo    '<th colspan=4>Number</th><th>&nbsp;Active&nbsp;</th><th>&nbsp;Place&nbsp;<br>&nbsp;Plow&nbsp;</th><th colspan=3>Top</th><th>&nbsp;</th><th colspan=3>Bottom</th><th>&nbsp;</th><th colspan=2>Details</th><th colspan=2>Content</th>';
		echo  '</tr>';
		foreach ($sections as $num => $section) {
			echo '<tr bgcolor=white valign=top class="sec'.$num.'">';
			echo '<td align=left>'.$num.'</td>';
			echo '<td>';
			echo   '<form action="index.php" methd="post">';
			echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
			echo    '<input type="hidden" name="num" value="'.$num.'">';
			echo    '<button type="submit" name="edit_sec_form" value="Edit" title="Edit" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Edit</button>';
			echo  '</td>';
			echo  '</form>';

			echo '<form action="index.php" method="post"><td>';
			echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
			echo '<input type="hidden" name="num" value="'.$num.'">';
			echo '<button type="submit" name="copy_sec_form" value="Copy" title="Copy" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Copy</button>';
			echo '</td></form>';

			echo '<form action="index.php" method="post"><td>';
			echo '<input type="hidden" name="userId" value="' . $_SESSION['userId'] . '">';
			echo '<input type="hidden" name="num" value="'.$num.'">';
			echo '<button type="submit" name="del_sec" value="Del" title="Delete" onmouseover="this.className=\'hover\';" onmouseout="this.className=\'\';">Delete</button>';
			echo '</td></form>';

			echo '<td>';
			echo '&nbsp;&nbsp;&nbsp;';
			echo '<input onclick="self.location.href=\'index.php?userId=' . $_SESSION['userId'] . '&vEditActive='.$num.'&vActiv='.($section['active']<>0?'0':'1').'\'" type="checkbox" name="active" value="1" ' . ($section['active']<>0 ? 'checked ' : '') . '>';
			echo '</td>';
			echo '<td>';
			echo '&nbsp;&nbsp;';
			if($section['type']=='anim' || $section['type']=='tree' || $section['type']=='deco' || $section['type']=='building' || $section['type']=='seed') echo '<input onclick="self.location.href=\'index.php?userId=' . $_SESSION['userId'] . '&vEditPlace='.$num.'&vPlace='.($section['place']<>0?'0':'1').'\'"  type="checkbox" name="place" value="1" ' . ($section['place']==1 ? 'checked ' : ''). '>';
			echo '</td>';

			echo '<td align="right">'.$section['top_x'].'</td>';
			echo '<td>-</td>';
			echo '<td align="right">'.$section['top_y'].'</td>';
			echo '<td>&nbsp;&nbsp;</td>';
			echo '<td align="right">'.$section['bot_x'].'</td>';
			echo '<td>-</td>';
			echo '<td align="right">'.$section['bot_y'].'</td>';
			echo '<td>&nbsp;&nbsp;</td>';
			echo '<td>'.$section['type'].'</td>';
			echo '<td>';

			if ($section['type'] == 'seed') {
				echo ' pattern ' . $section['pat'] . ": ";
				echo Sections_GetValue($vSeeds,$section['seed']).' ('.$section['seed'].')';
				echo ( ($section['pat'] != 'none') ? '/'.Sections_GetValue($vSeeds,$section['seed2']).' ('.$section['seed2'].')' : '' );
			} elseif ($section['type'] == 'anim') {
				foreach(explode('|',$section['anim']) as $vAnimal) {
					echo '<nobr>'.$vAnimals[$vAnimal].' ('.$vAnimal.')&nbsp;&nbsp;&nbsp;</nobr> ';
				}
				if($section['walk']=='walk') echo 'walk&nbsp;&nbsp;&nbsp; ';
				if($section['walk']=='stay') echo 'stay&nbsp;&nbsp;&nbsp; ';
				if($section['rotate']=='0') echo '<nobr>bottom-left&nbsp;&nbsp;&nbsp;</nobr> ';
				if($section['rotate']=='1') echo '<nobr>bottom-right&nbsp;&nbsp;&nbsp;</nobr> ';
				if($section['rotate']=='2') echo '<nobr>top-right&nbsp;&nbsp;&nbsp;</nobr> ';
				if($section['rotate']=='3') echo '<nobr>top-left&nbsp;&nbsp;&nbsp;</nobr> ';
			} elseif ($section['type'] == 'tree') {
				foreach(explode('|',$section['tree']) as $vTree) {
					echo '<nobr>'.(isset($vTrees[$vTree]) ? $vTrees[$vTree].' ('.$vTree.')' : $vTree).'&nbsp;&nbsp;&nbsp;</nobr> ';
				}
			} elseif ($section['type'] == 'deco') {
				foreach(explode('|',$section['deco']) as $vDeco) {
					echo '<nobr>'.$vDecorations[$vDeco].' ('.$vDeco.')&nbsp;&nbsp;&nbsp;</nobr> ';
				}
			} elseif ($section['type'] == 'building') {
				foreach(explode('|',$section['building']) as $Building) {
					echo '<nobr>'.$vBuildings[$Building].' ('.$Building.')&nbsp;&nbsp;&nbsp;</nobr> ';
				}
			} elseif ($section['type'] == 'buyanim') {
				echo $vAnimals[$section['buyanim']].' ('.$section['buyanim'].')';
			} elseif ($section['type'] == 'buytree') {
				echo $vTrees[$section['buytree']].' ('.$section['buytree'].')';
			} elseif ($section['type'] == 'buydeco') {
				echo $vDecorations[$section['buydeco']].' ('.$section['buydeco'].')';
			}
			echo '</td>';
			echo '<td>';
			foreach ($vSecLoc[$num] as $itemName => $count) {
				echo '<nobr>',$count,'x '.$itemName.'</nobr> ';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table><br/>';
	}
	echo "</body></html>";
}
?>
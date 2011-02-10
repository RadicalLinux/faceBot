<?php

function GetPlugins($type = '')
{
	if ($type == 'chinese') {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBotCH/plugins.manifest'));
	} else {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBot/plugins.manifest'));
	}
	$dir = $_SESSION['base_path'] . 'plugins';
	foreach ($allplugs as $aplugs)
	{
		$plugin = array();
		if ($aplugs['name'] == 'fBSettings') continue;
		if (file_exists($dir . '/' . $aplugs['name']))
		{
			$plugin['name'] = $aplugs['name'];
			$plugin['folder'] = $dir . '\\' . $aplugs['name'];
			@$installver = my_get_contents($dir . '/' . $aplugs['name'] . '/' . $aplugs['name'] . '.ver');
			$plugin['installver'] = empty($installver) ? 'Unknown' : $installver;
			$plugin['serverver'] = $aplugs['version'];
		} else {
			$plugin['name'] = $aplugs['name'];
			$plugin['installver'] = 'Not Installed';
			$plugin['serverver'] = $aplugs['version'];
		}
		$plugins[] = $plugin;
	}
	return $plugins;
}

function GetParser($type = '')
{
	@$installver = my_get_contents($_SESSION['base_path'] . '/parser.ver');
	if ($type == 'chinese') {
		@$serverver = my_get_contents('http://207.224.102.177/faceBotCH/parser.ver');
	} else {
		@$serverver = my_get_contents('http://207.224.102.177/faceBot/parser.ver');
	}
	$plugin['name'] = 'Parser';
	$plugin['installver'] = empty($installver) ? 'Unknown' : $installver;
	$plugin['serverver'] = $serverver;
	return $plugin;
}


function ParserIRU($type = '')
{
	$dir = $_SESSION['base_path'] . 'plugins';
	if ($type == 'chinese') {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBotCH/parser.manifest'));
	} else {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBot/parser.manifest'));
	}
	echo '<hr><font color="blue"><u><b>Checking/Creating Folders For Parser</b></u></font><br>';
	if (file_exists($dir . '\\fBSettings'))
	{
		echo $dir . '\\fBSettings - <font color="green">Folder Exists</font><br>';
	} else {
		echo '<font color="red">[CREATING] ' . $dir . '\\fBSettings Folder</font><br>';
		mkdir($dir . '\\fBSettings');
	}
	if (file_exists($_SESSION['base_path'] . 'ext'))
	{
		echo $_SESSION['base_path'] . 'ext - <font color="green">Folder Exists</font><br>';
	} else {
		echo '<font color="red">[CREATING] ' . $_SESSION['base_path'] . 'ext Folder</font><br>';
		mkdir($_SESSION['base_path'] . 'ext');
	}
	//Stage 1 - Check/Create Folders
	foreach ($allplugs as $plug)
	{

		if (strpos($plug['name'], 'fBSettings') === false) {
			$dir = substr($_SESSION['base_path'],0,-1);
			$dir2 = '';
		} else {
			$dir = $_SESSION['base_path'] . 'plugins';
			$dir2 = 'plugins/';
		}
		if (isset($plug['type']))
		{
			if (file_exists($dir . '\\' . $plug['name']) && is_dir($dir . '\\' . $plug['name']))
			{
				echo $dir . '\\' . $plug['name'] . ' - <font color="green">Folder Exists</font><br>';
			} else {
				@unlink($dir . '\\' . $plug['name']);
				echo '<font color="red">[CREATING] ' . $dir . '\\' . $plug['name'] . ' Folder</font><br>';
				mkdir($dir . '\\' . $plug['name']);
			}
		}
	}
	echo '<hr><font color="blue"><u><b>Checking/Installing Files For ' . $plugin . '</b></u></font><br>';
	//Stage 2 - Check/Create Files
	foreach ($allplugs as $plug)
	{
		if (strpos($plug['name'], 'fBSettings') === false) {
			$dir = substr($_SESSION['base_path'],0,-1);
			$dir2 = '';
		} else {
			$dir = $_SESSION['base_path'] . 'plugins';
			$dir2 = 'plugins/';
		}
		if (file_exists($dir . '\\' . $plug['name']) && !is_dir($dir . '\\' . $plug['name']))
		{
			If (sha1_file($dir . '\\' . $plug['name']) == $plug['crc']) {
				echo $dir . '\\' . $plug['name'] . ' -<font color="green"> Exists - Proper Version</font><br>';
			} else {
				echo '<font color="red">[REPLACING] ' . $dir . '\\' . $plug['name'] . '- Exists - Incorrect Version</font><br>';
				if ($type == 'chinese') {
					$newfile = my_get_contents('http://207.224.102.177/faceBotCH/' . $dir2 . $plug['name']);
				} else {
					$newfile = my_get_contents('http://207.224.102.177/faceBot/' . $dir2 . $plug['name']);
				}
				file_put_contents($dir . '\\' . $plug['name'], $newfile);
			}
		} elseif (!is_dir($dir . '\\' . $plug['name'])) {
			echo '<font color="red">[INSTALLING] ' . $dir . '\\' . $plug['name'] . '</font><br>';
			if ($type == 'chinese') {
				$newfile = my_get_contents('http://207.224.102.177/faceBotCH/' . $dir2 . $plug['name']);
			} else {
				$newfile = my_get_contents('http://207.224.102.177/faceBot/' . $dir2 . $plug['name']);
			}
			$err = file_put_contents($dir . '\\' . $plug['name'], $newfile);
		}
	}

	echo '<hr><a href="index.php">Return to Plugin Page</a>';
	die();
}

function DoIRU($type = '', $plugin = '', $botversion = '')
{
	if ($botversion == 'chinese') {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBotCH/' . $plugin . '.manifest'));
	} else {
		$allplugs = unserialize(my_get_contents('http://207.224.102.177/faceBot/' . $plugin . '.manifest'));
	}
	$dir = $_SESSION['base_path'] . 'plugins';
	//Make Sure Plugin Folder Exists
	echo '<hr><font color="blue"><u><b>Checking/Creating Folders For ' . $plugin . '</b></u></font><br>';
	if (file_exists($dir . '\\' . $plugin))
	{
		echo $dir . '\\' . $plugin . ' - <font color="green">Folder Exists</font><br>';
	} else {
		echo '<font color="red">[CREATING] ' . $dir . '\\' . $plugin . ' Folder</font><br>';
		mkdir($dir . '\\' . $plugin);
	}

	//Stage 1 - Check/Create Folders
	foreach ($allplugs as $plug)
	{
		if (isset($plug['type']))
		{
			if (file_exists($dir . '\\' . $plug['name']))
			{
				echo $dir . '\\' . $plug['name'] . ' - <font color="green">Folder Exists</font><br>';
			} else {
				echo '<font color="red">[CREATING] ' . $dir . '\\' . $plug['name'] . ' Folder</font><br>';
				mkdir($dir . '\\' . $plug['name']);
			}
		}
	}
	echo '<hr><font color="blue"><u><b>Checking/Installing Files For ' . $plugin . '</b></u></font><br>';
	//Stage 2 - Check/Create Files
	foreach ($allplugs as $plug)
	{
		if (!isset($plug['type']))
		{
			if (file_exists($dir . '\\' . $plug['name']))
			{
				If (sha1_file($dir . '\\' . $plug['name']) == $plug['crc']) {
					echo $dir . '\\' . $plug['name'] . ' -<font color="green"> Exists - Proper Version</font><br>';
				} else {
					echo '<font color="red">[REPLACING] ' . $dir . '\\' . $plug['name'] . '- Exists - Incorrect Version</font><br>';
					if ($botversion == 'chinese') {
						$newfile = my_get_contents('http://207.224.102.177/faceBotCH/plugins/' . $plug['name']);
					} else {
						$newfile = my_get_contents('http://207.224.102.177/faceBot/plugins/' . $plug['name']);
					}
					file_put_contents($dir . '\\' . $plug['name'], $newfile);
				}
			} else {
				echo '<font color="red">[INSTALLING] ' . $dir . '\\' . $plug['name'] . '</font><br>';
				if ($botversion == 'chinese') {
					$newfile = my_get_contents('http://207.224.102.177/faceBotCH/plugins/' . $plug['name']);
				} else {
					$newfile = my_get_contents('http://207.224.102.177/faceBot/plugins/' . $plug['name']);
				}
				$err = file_put_contents($dir . '\\' . $plug['name'], $newfile);
			}
		}
	}

	echo '<hr><a href="index.php">Return to Plugin Page</a>';
	die();

}

function my_get_contents($url = '')
{
	$handle = fopen($url, "rb");
	$contents = stream_get_contents($handle);
	fclose($handle);
	return $contents;
}

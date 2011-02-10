<?php
$base_path = getcwd();
$pos1 = strpos($base_path,'\\');
$pos2 = strpos($base_path,'\\', $pos1 + 1);
$_SESSION['base_path'] = getcwd() . '\\';
echo $_SESSION['base_path'];
GetPlugins();
GetParser();
CreatePluginManifest();

function GetPlugins()
{
	$allplugs = unserialize(file_get_contents('plugins.manifest'));
	$dir = $_SESSION['base_path'] . 'plugins';
	foreach ($allplugs as $aplugs)
	{
		if ($aplugs['name'] == 'fBSettings') continue;
		$x = getFileList($dir . '/' . $aplugs['name'],true);
		if (!empty($x)) {
			file_put_contents($aplugs['name'] . '.manifest', serialize($x));
		}
	}
}

function GetParser()
{
	$dir = $_SESSION['base_path'] . 'plugins';
        $x = getFileList($dir . '/fBSettings',true);
        $x2 = getFileList('xulrunner', true);
        $x4 = getFileList('amfphp', true);
        $x3 = array_merge($x,$x4,$x2);
       

	if (!empty($x3)) {
		$x3[] = array( "name" => "parser.php", 'crc' => sha1_file("parser.php") );
		$x3[] = array( "name" => "fB_AMF.php", 'crc' => sha1_file("fB_AMF.php") );
		$x3[] = array( "name" => "fB_Utils.php", 'crc' => sha1_file("fB_Utils.php") );
		$x3[] = array( "name" => "fB_PluginAPI.php", 'crc' => sha1_file("fB_PluginAPI.php") );
		$x3[] = array( "name" => "fB_Farm.php", 'crc' => sha1_file("fB_Farm.php") );
		$x3[] = array( "name" => "fB_DB.php", 'crc' => sha1_file("fB_DB.php") );
		$x3[] = array( "name" => "parser.ver", 'crc' => sha1_file("parser.ver") );
                $x3[] = array( "name" => "php.ini", 'crc' => sha1_file("php.ini") );
		file_put_contents('parser.manifest', serialize($x3));
	}
}



function CreatePluginManifest()
{
	$dir = $_SESSION['base_path'] . 'plugins';
	@$dh = opendir($dir);
	$plugins = array();
	if ($dh)
	{
		while (($file = readdir($dh)) !== false)
		{
			if (is_dir($dir . '/' . $file))
			{
				if ($file != '.' && $file != '..')
				{
					if ($file == 'fBSettings') continue;
					$plugin = array();
					$plugin['name'] = $file;
					$plugin['version'] = file_get_contents($dir . '\\' . $file . '\\' . $file . '.ver');
					$plugins[] = $plugin;
				}
			}
		}
		closedir($dh);
		file_put_contents('plugins.manifest', serialize($plugins));
	}
}
function getFileList($dir, $recurse=false)
{
	# array to hold return value
	$retval = array();
	# add trailing slash if missing
	if(substr($dir, -1) != "/") $dir .= "/";
	# open pointer to directory and read list of files
	$d = @dir($dir);
	if (!$d) return;
	$newdir = str_replace($_SESSION['base_path'] . 'plugins/', '', $dir);
	while(false !== ($entry = $d->read())) {
		# skip hidden files
		if($entry[0] == ".") continue;
		if(is_dir("$dir$entry")) {
			$retval[] = array( "name" => "$newdir$entry", "type" => filetype("$dir$entry"));
			if($recurse && is_readable("$dir$entry/")) {
				$retval = array_merge($retval, getFileList("$dir$entry/", true));
			}
		} elseif(is_readable("$dir$entry")) {
			if (!stripos("$dir$entry", 'Thumbs.db'))
			$retval[] = array( "name" => "$newdir$entry", 'crc' => sha1_file("$dir$entry") );
		}
	}
	$d->close();
	return $retval;
}







?>

<?php
define('FB_SETTINGS_VER', trim(file_get_contents($_SESSION['base_path'] . 'plugins/fBSettings/fBSettings.ver')));
define('settings_URL', '/plugins/Settings/index.php');

//------------------------------------------------------------------------------
// settings_init
//------------------------------------------------------------------------------
function fBSettings_init()
{
	if (FB_PARSER_VER != FB_SETTINGS_VER)
	AddLog2("******ERROR: faceBot's updated settings version (". FB_SETTINGS_VER .") doesn't match parser version (".FB_PARSER_VER.")******");
}

?>
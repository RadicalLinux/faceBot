<?php

define('fvXML_version', file_get_contents($_SESSION['base_path'] . 'plugins/fvXML/fvXML.ver'));
define('fvXML_date', '24 October 2010');
define('fvXML_Path', $_SESSION['base_path'] . 'plugins/fvXML/');
// file definitions
define('fvXML_Main', 'fvXML_main.sqlite');
define('fvXML_World', 'fvXML_world.sqlite');
define('fvXML_Units', 'fvXML_units.sqlite');
/******************fvXML by RadicalLinux****************************/
include 'includes/fvXML.class.php';
/******************fvXML by RadicalLinux****************************/

function fvXML_init()
{
   $_SESSION['hooks']['after_planting'] = 'fvXML_doWork';
}
/******************fvXML by RadicalLinux****************************/

function fvXML_doWork()
{
   $fvM = new fvXML();
   if($fvM->error != '')
   {
   	  unset($fvM);
      return;
   }
}


?>
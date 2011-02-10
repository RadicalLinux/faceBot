<?php

define('fvManager_version', file_get_contents($_SESSION['base_path'] . 'plugins/fvManager/fvManager.ver'));
define('fvManager_date', '29 October 2010');
define('fvManager_URL', '/plugins/fvManager/main.php');
define('fvManager_Path', 'plugins/fvManager/');
// file definitions
define('fvManager_Main', 'fvManager_main.sqlite');
define('fvManager_World', 'fvManager_world.sqlite');
define('fvManager_Units', 'fvManager_units.sqlite');
/******************fvManager by RadicalLinux****************************/
include 'includes/fvManager.class.php';
/******************fvManager by RadicalLinux****************************/

function fvManager_init()
{
   $_SESSION['hooks']['after_planting'] = 'fvManager_doWork';
   $_SESSION['hooks']['after_work'] = 'fvManager_Refresh';
   echo "Loading Farmville Manager V" . fvManager_version . " by RadicalLinux\r\n";
}
/******************fvManager by RadicalLinux****************************/

function fvManager_doWork()
{
   AddLog2('fvManager initializing');
   $fvM = new fvManager();
   if($fvM->error != '')
   {
   	  unset($fvM);
      return;
   }
   AddLog2('fvManager initialized');
   $fvM->fvSellAll();
   $results = $fvM->fvGetWork('2');
   if(empty($results) || count($results) <= 0)
   {
      unset($fvM);
      AddLog2('fvManager had no work to do');
      return;
   }
   else
   {
      AddLog2('fvManager preparing to buy/sell items');
      foreach($results as $item)
      {
         SWITCH($item['work_action'])
         {
         	CASE 'craftbuy':
            CASE 'buy':
               $result = $fvM->fvBuyItem($item);
               break;
            DEFAULT:
               $result = $fvM->fvSell($item);

         }
      }
      unset($fvM);
      AddLog2('fvManager has finished buying/selling items');
   }
}

function fvManager_Refresh()
{
   $fvM = new fvManager();
   unset($fvM);
}

?>
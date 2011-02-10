<?php
/*******************************************************************************
Sections Plugin v2.3.0
2010, ralphm2004
see info.txt
*******************************************************************************/
#error_reporting(E_ERROR);
error_reporting(E_ALL);

define('sections_Path', '/plugins/Sections/');
define('sections_URL', sections_Path.'index.php');
define('sections_Version', '2.4.0');
define('sections_Version_Date', '2010-12-20');
define('REQ_VER_PARSER', '21901');

include_once(sections_Path.'functions.php');

/*******************************************************************************
  Initialization
*******************************************************************************/

function Sections_init() {
  //set hook
  $_SESSION['hooks']['before_planting'] = 'Sections_plant_sections';
  $_SESSION['hooks']['before_before_planting'] = 'Sections_relocate_animals';
}
?>

<?php

define('fvFarmer_version', '1.0');
define('fvFarmer_URL', '/plugins/fvFarmer/index.php');
define('fvFarmer_Path', 'plugins/fvFarmer/');
// file definitions
define('fvFarmer_Main', 'fvFarmer_main.sqlite');
define('fvFarmer_World', 'fvFarmer_world.sqlite');
define('fvFarmer_Units', 'fvFarmer_units.sqlite');
/******************fvFarmer by RadicalLinux****************************/
include 'includes/fvFarmer.class.php';
/******************fvFarmer by RadicalLinux****************************/

function fvFarmer_init()
{
	$_SESSION['hooks']['flybiplane'] = 'fvFarmer_doPlane';
	$_SESSION['hooks']['harvest'] = 'fvFarmer_doHarvest';
	$_SESSION['hooks']['harvest_buildings'] = 'fvFarmer_doHarvestBuildings';
	$_SESSION['hooks']['harvest_animals'] = 'fvFarmer_doHarvestAnimals';
	$_SESSION['hooks']['transform_animals'] = 'fvFarmer_doTransformAnimals';
	$_SESSION['hooks']['harvest_trees'] = 'fvFarmer_doHarvestTrees';
	$_SESSION['hooks']['hoe'] = 'fvFarmer_doPlowPlots';
	$_SESSION['hooks']['planting'] = 'fvFarmer_doPlant';
}
/******************fvFarmer by RadicalLinux****************************/

function fvFarmer_doPlane()
{
	
	$_SESSION['fvM'] = new fvFarmer();
	if (@$_SESSION['fvM']->settings['flyplane'] == 1)
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvFlyPlane();
}

function fvFarmer_doHarvest()
{
	if (@$_SESSION['fvM']->settings['harvest'] == 1)
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvHarvestCrops();
}

function fvFarmer_doHarvestBuildings()
{
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvHarvestBuildings();
}

function fvFarmer_doHarvestAnimals()
{
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvHarvestAnimals();
}

function fvFarmer_doTransformAnimals()
{
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvTransformAnimals();
}

function fvFarmer_doHarvestTrees()
{
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvHarvestTrees();
}

function fvFarmer_doPlowPlots()
{
	if (@$_SESSION['fvM']->settings['plow'] == 1)
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvPlowPlots();
}

function fvFarmer_doPlant()
{
	if (@$_SESSION['fvM']->settings['plant'] == 1)	
	if($_SESSION['fvM']->haveWorld === true) $_SESSION['fvM']->fvPlantPlots();
	unset($_SESSION['fvM']);
}

function fvFarmer_Refresh()
{
	$fvM = new fvFarmer();
	unset($fvM);
}

?>
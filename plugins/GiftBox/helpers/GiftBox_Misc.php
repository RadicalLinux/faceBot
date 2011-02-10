<?php

function GB_ShowIMG($value)
{
	global $images;
	global $GB_ImagePath;
	global $vDataDB,$flashRevision;

	if (isset($value['name'])){ $name = $value['name'];}else{$name = '';}
	if (isset($value['iconurl']) && $images == 1)
	{
		$x_iconurl   = '/' . $value['iconurl'];
		{
			$GB_image = '<img class="icon" src="'. $x_iconurl .'" alt="'.$name.'" />';
			return $GB_image;
		}
	}
	// file does not exist, show alternative.
	$x_iconurl = '/plugins/GiftBox/image/progress.gif';
	$GB_image = '<img class="icon" src="'. $x_iconurl .'" alt="'.$name.'" />' ;
	return $GB_image;
}

//------------------------------------------------------------------------------
// GetCollectionList gets a list of objects in the Storage
//------------------------------------------------------------------------------
function GB_GetCollectionList()
{
	if (!file_exists($_SESSION['base_path'] . 'collectable_info.txt')) { return false; }
	return unserialize(file_get_contents($_SESSION['base_path'] . 'collectable_info.txt')); //return all collections info
}
//------------------------------------------------------------------------------
// find which collection is complete
//------------------------------------------------------------------------------
function GB_CollCompete()
{
	$GB_CollectionList = GB_GetCollectionList()  ;
	if (!$GB_CollectionList) { AddLog2("collectable_info.txt not found.. "); return false; }
	$GB_CollCompl = 0;
	$GBccount = array();
	$GBccount = GB_LoadCcount();
	if (!$GBccount) { AddLog2("ccount.txt not found.. "); return false; }
	$res = array();

	foreach($GB_CollectionList as $value)
	{
		// walk all collections
		$GB_amount_Coll = count($value['collectable']);
		$i=0;
		$GB_ThisCollCompl = 0;
		$GB_ThisCollVal = array();
		while($i < $GB_amount_Coll)
		{
			// each collection
			//$ObjD = GB_GetUnitInfo($value['collectable'][$i], "code", $GB_units);
			$Amount_in_Collection = GB_GetColInfo($value['collectable'][$i], $GBccount);
			$GB_ThisCollVal[] =$Amount_in_Collection;
			$i++;
		}
		$GB_ThisCollCompl = min($GB_ThisCollVal);
		$res[$value['code']] = $GB_ThisCollCompl;
	}
	return $res; //return total completed array can be empty.
}
//------------------------------------------------------------------------------
// GB_LoadCcount  load the ccount file to get the amount of collectable
//------------------------------------------------------------------------------
function GB_LoadCcount()
{
	return unserialize(fBGetDataStore('ccount'));
}
//------------------------------------------------------------------------------
// GB_GetColInfo    Get count of collectables in collection.
//------------------------------------------------------------------------------
function GB_GetColInfo($needle, $haystack)
{
	$found = "0";
	if (array_key_exists($needle, $haystack)) {
		$found = $haystack[$needle]; // return the amount.
	}
	return $found;
}

// ========================= nice time. Facebook style.
function nicetime($date)
{
	if(empty($date)) {
		return "No date provided";
	}

	$periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths         = array("60","60","24","7","4.35","12","10");

	$now             = time();
	$unix_date       = $date;

	// check validity of date
	if(empty($unix_date)) {
		return "Bad date";
	}

	// is it future date or past date
	if($now > $unix_date) {
		$difference     = $now - $unix_date;
		$tense         = "ago";
			
	} else {
		$difference     = $unix_date - $now;
		$tense         = "from now";
	}

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);

	if($difference != 1) {
		$periods[$j].= "s";
	}

	return "$difference $periods[$j] {$tense}";
}



// Check if the default?action.txt exist or was modified.


function GB_CanWeStore($Unit)
{
	$Able2Store = 'N';
	$nonStorableClass = array('CCrafted','Collection', 'Equipment', 'FlowerDecoration', 'LootableDecoration', 'StorageBuilding', 'InventoryCellar', 'HolidayTreeStorage', 'ValentinesPresent', 'Pet', 'BuildingPart', 'ShovelItem', 'MysteryGift', 'SocialPlumbingMysteryGift');
	$nonStorableSubtype = array('animal_pens', 'crafting', 'storage');
	// check if decoration
	if ($Unit['type'] == 'decoration' )
	{ $Able2Store = 'Y';
	}
	if ($Unit['type'] == 'building' )
	{ $Able2Store = 'Y';
	}
	if(in_array(@$Unit['className'], $nonStorableClass))  {$Able2Store = 'N';}
	if(in_array(@$Unit['subtype'], $nonStorableSubtype))  {$Able2Store = 'N';}

	return $Able2Store;
}

//------------------------------------------------------------------------------
// find if collection is complete
//------------------------------------------------------------------------------
function GB_CollAmount()
{
	$GB_CollectionList = GB_GetCollectionList()  ;
	if (!$GB_CollectionList) { return false; }
	$GB_CollCompl = 0;
	$GBccount = array();
	$GBccount = GB_LoadCcount();
	if (!$GBccount) { return false; }

	foreach($GB_CollectionList as $value)
	{
		// walk all collections
		$GB_amount_Coll = count($value['collectable']);
		$i=0;
		$GB_ThisCollCompl = 0;
		$GB_ThisCollVal = array();

		while($i < $GB_amount_Coll)
		{
			// each collection
			$Amount_in_Collection = GB_GetColInfo($value['collectable'][$i], $GBccount);
			$GB_ThisCollVal[] =$Amount_in_Collection;
			$i++;
		}
		$GB_ThisCollCompl = min($GB_ThisCollVal);
		$GB_CollCompl = $GB_CollCompl + $GB_ThisCollCompl;
	}
	return $GB_CollCompl; //return total completed
}
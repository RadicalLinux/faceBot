<?php

function Sections_GetUnits($flashRevision) {

	$vReturn = load_array('sections_unit.txt');

	if($vReturn['flashRevision']<>$flashRevision) {

		$units=Units_GetByType('seed');
		foreach($units as $unit) {
			if(strlen($unit['realname'])==0) $unit['realname']=$unit['name'];
			$vSeeds[$unit['name']]= $unit['realname'];
		}

		$units=Units_GetByType('animal');
		foreach($units as $unit) {
			if(strlen($unit['realname'])==0) $unit['realname']=$unit['name'];
			$vAnimals[$unit['name']]= $unit['realname'];
			if(isset($unit['buyable']) && $unit['buyable']=='true') {
				if(isset($unit['market'])) {
					$vBuyAnimals['cash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyAnimals['coins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			} else {
				if(isset($unit['market'])) {
					$vBuyAnimals['nocash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyAnimals['nocoins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			}
			$vUnit=strpos($unit['name'],'_')===false?$unit['name']:substr($unit['name'],0,strpos($unit['name'],'_'));
			$vAnimalUnits[$vUnit][$unit['name']]= $unit['realname'];
		}

		$units=Units_GetByType('tree');
		foreach($units as $unit) {
			$vTrees[$unit['name']]  = $unit['realname'];
			if(isset($unit['buyable']) && $unit['buyable']=='true') {
				if(isset($unit['market'])) {
					$vBuyTrees['cash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyTrees['coins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			} else {
				if(isset($unit['market'])) {
					$vBuyTrees['nocash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyTrees['nocoins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			}
		}

		$units=Units_GetByType('decoration');
		foreach($units as $unit) {
			$vDecorations[$unit['name']]  = $unit['realname'];
			if(isset($unit['buyable']) && $unit['buyable']=='true') {
				if(isset($unit['market'])) {
					$vBuyDecorations['cash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyDecorations['coins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			} else {
				if(isset($unit['market'])) {
					$vBuyDecorations['nocash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyDecorations['nocoins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			}
		}
		$units=Units_GetByType('building');
		foreach($units as $unit) {
			$vBuildings[$unit['name']]  = $unit['realname'];
			if(isset($unit['buyable']) && $unit['buyable']=='true') {
				if(isset($unit['market'])) {
					$vBuyBuildings['cash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyBuildings['coins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			} else {
				if(isset($unit['market'])) {
					$vBuyBuildings['nocash'][$unit['name']] = $unit['realname'].' ('.$unit['cash'].' cash)';
				} else {
					$vBuyBuildings['nocoins'][$unit['name']] = $unit['realname'].' ('.$unit['cost'].' coins)';
				}
			}
		}

		$units=Units_GetAll();
		foreach($units as $unit) {
			$vAll[$unit['name']]  = $unit['realname'];
		}

		ksort($vSeeds);
		ksort($vAnimals);
		ksort($vTrees);
		ksort($vDecorations);
		ksort($vBuildings);
		ksort($vBuyAnimals['cash']);
		ksort($vBuyAnimals['coins']);
		ksort($vBuyAnimals['nocash']);
		ksort($vBuyAnimals['nocoins']);
		ksort($vBuyTrees['cash']);
		ksort($vBuyTrees['coins']);
		ksort($vBuyTrees['nocash']);
		ksort($vBuyTrees['nocoins']);
		ksort($vBuyDecorations['cash']);
		ksort($vBuyDecorations['coins']);
		ksort($vBuyDecorations['nocash']);
		ksort($vBuyDecorations['nocoins']);
		ksort($vBuyBuildings['cash']);
		ksort($vBuyBuildings['coins']);
		ksort($vBuyBuildings['nocash']);
		ksort($vBuyBuildings['nocoins']);
		ksort($vAnimalUnits);
		ksort($vAll);

		$vReturn['flashRevision']=$flashRevision;
		$vReturn['vSeeds']=$vSeeds;
		$vReturn['vAnimals']=$vAnimals;
		$vReturn['vTrees']=$vTrees;
		$vReturn['vDecorations']=$vDecorations;
		$vReturn['vBuildings']=$vBuildings;
		$vReturn['vBuyAnimals']=$vBuyAnimals;
		$vReturn['vBuyTrees']=$vBuyTrees;
		$vReturn['vBuyDecorations']=$vBuyDecorations;
		$vReturn['vBuyBuildings']=$vBuyBuildings;
		$vReturn['vAnimalUnits']=$vAnimalUnits;
		$vReturn['vAll']=$vAll;

		save_array($vReturn, 'sections_unit.txt');
	}
	return($vReturn);
}

function Sections_GetValue($key, $name) {
	return isset($key[$name]) ? $key[$name] : '';
}

function Sections_GetRealName($name,$realname='') {
	return strlen($realname)>0?$realname:$name;
}

#function Sections_GetGiftboxContent($units) {
function Sections_GetGiftboxContent() {

	$ingiftbox = @unserialize(fBGetDataStore('ingiftbox'));
	if(is_array($ingiftbox)) {
		foreach ($ingiftbox as $gift => $count) {
			list($qcount) = explode(',', $count);
			#      $vItem = Section_GetUnit($units,$gift);
			$vItem = Units_GetUnitByCode($gift);
			if($vItem['type']=='animal') {
				$vItemTyp='animal';
			} elseif($vItem['type']=='tree') {
				$vItemTyp='tree';
			} elseif($vItem['type']=='decoration') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='rotateabledecoration') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='lootabledecoration') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='egg') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='mysterygift') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='flowerdecoration') {
				$vItemTyp='decoration';
			} elseif($vItem['type']=='building') {
				$vItemTyp='building';
			} else {
				$vItemTyp='unused';
			}
			for($vI=0;$vI<$qcount;$vI++) $Return[$vItemTyp][]=$vItem;
		}
	}
	return $Return;
}
#function Section_GetUnit($Units_Array,$Code) {
#  foreach($Units_Array as $Item_Name => $Item_Array) {
#    if($Item_Array['code']==$Code) return $Item_Array;
#  }
#  return false;
#}
function Sections_relocate_animals() {
	global $need_reload;
	global $vAnnounceMoveArray;
	global $vAnnouncePlaceArray;
	global $vAnnounceBuyArray;
	global $vAnnounceWalkRotateArray;

	if($need_reload == true) {
		$res = DoInit();
		$need_reload=false;
	}

	#return;  // uncomment to disable for debug purposes

	#  $units = @unserialize(file_get_contents(F('units.txt')));
	$objects = @unserialize(fBGetDataStore('objects'));
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $flashRevision) = explode(';', fBGetDataStore('playerinfo'));
	$sections = array();
	$sections = load_array('sections.txt');
	if( !((is_array($sections) && count($sections))) ) {
		$sections = array();
	}

	// Build matrix so we can know which locations are empty

	$location_empty = array();
	for ($x = 0; $x < $sizeX; $x++) {
		for ($y = 0; $y < $sizeY; $y++) {
			$location_empty[$x][$y] = true;
		}
	}

	foreach($objects as $o) {
		#    $u = $units[$o['itemName']];
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
				$location_empty[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ] = false;
			}
		}
	}

	$animals = GetObjects('Animal');

	foreach($animals as $o) {
		$vMoveCnt=0;
		$vInPositionCnt=0;

		#    $u = $units[ $o['itemName'] ];
		$u = Units_GetUnitByName($o['itemName']);
		if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
		if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
		if ($o['state'] == 'vertical') {
			$t = $u['sizeX'];
			$u['sizeX'] = $u['sizeY'];
			$u['sizeY'] = $t;
		}

		foreach ($sections as $section) {
			if ($section['active']=='1' && (($section['type']=='anim' && in_array($o['itemName'], explode('|',$section['anim'])))||$section['type']=='dontmove')) {
				$npx = $o['position']['x'];
				$npy = $o['position']['y'];
				for($x=0;$x < $u['sizeX']; $x++) {
					for($y=0;$y < $u['sizeY']; $y++) {

						if( !(
						($npx+$x >= $section['bot_x'] ) &&
						($npx+$x <= $section['top_x'])  &&
						($npy+$y >= $section['bot_y'])  &&
						($npy+$y <= $section['top_y'])) ) {
							$vMoveCnt++;
						} else {
							$vInPositionCnt++;
						}

					}
				}
			}
		}

		if($vMoveCnt>0 && ($vInPositionCnt==0 || $vInPositionCnt % ($u['sizeX']*$u['sizeY']))>0) {

			#      AddLog2("Sections: Found " . $o['itemName'] . " on " . $npx . "-" . $npy . " - wrong position.");

			$newpos = Sections_Find_Free_Location($sections, $u, $o, $location_empty, 'animal');

			if ($newpos != false) {
				#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);
				$oldpos = $o['position'];

				Sections_Announce_Move($o,$newpos);

				for($x=0;$x < $u['sizeX']; $x++) {
					for($y=0;$y < $u['sizeY']; $y++) {
						$location_empty[ $oldpos['x'] + $x ][ $oldpos['y'] + $y ] = true;
						$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
					}
				}
			} else {
				#        AddLog2("Sections: No new location found");
			}
		}
	}

	$trees = GetObjects('Tree');

	foreach($trees as $o) {
		$vMoveCnt=0;
		$vInPositionCnt=0;

		#    $u = $units[ $o['itemName'] ];
		$u = Units_GetUnitByName($o['itemName']);
		if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
		if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
		if ($o['state'] == 'vertical') {
			$t = $u['sizeX'];
			$u['sizeX'] = $u['sizeY'];
			$u['sizeY'] = $t;
		}

		foreach ($sections as $section) {
			if ($section['active']=='1' && (($section['type']=='tree' && in_array($o['itemName'], explode('|',$section['tree'])))||$section['type']=='dontmove')) {
				$npx = $o['position']['x'];
				$npy = $o['position']['y'];
				for($x=0;$x < $u['sizeX']; $x++) {
					for($y=0;$y < $u['sizeY']; $y++) {

						if( !(
						($npx+$x >= $section['bot_x'] ) &&
						($npx+$x <= $section['top_x'])  &&
						($npy+$y >= $section['bot_y'])  &&
						($npy+$y <= $section['top_y'])) ) {
							$vMoveCnt++;
						} else {
							$vInPositionCnt++;
						}

					}
				}
			}
		}

		if($vMoveCnt>0 && ($vInPositionCnt==0 || $vInPositionCnt % ($u['sizeX']*$u['sizeY']))>0) {

			#      AddLog2("Sections: Found " . $o['itemName'] . " on " . $npx . "-" . $npy . " - wrong position.");

			$newpos = Sections_Find_Free_Location($sections, $u, $o, $location_empty, 'tree');

			if ($newpos != false) {
				#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);
				$oldpos = $o['position'];

				Sections_Announce_Move($o,$newpos);

				for($x=0;$x < $u['sizeX']; $x++) {
					for($y=0;$y < $u['sizeY']; $y++) {
						$location_empty[ $oldpos['x'] + $x ][ $oldpos['y'] + $y ] = true;
						$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
					}
				}
			} else {
				#        AddLog2("Sections: No new location found");
			}
		}
	}

	$decorations = array_merge( GetObjects('Decoration'), GetObjects('RotateableDecoration'), GetObjects('LootableDecoration'), GetObjects('MysteryGift'), GetObjects('FlowerDecoration'), GetObjects('Building'));

	$vSearchEggArray[]='egg_white';     $vReplaceEggArray[]='mysteryeggwhite';
	$vSearchEggArray[]='egg_brown';     $vReplaceEggArray[]='mysteryeggbrown';
	$vSearchEggArray[]='egg_black';     $vReplaceEggArray[]='mysteryeggblack';
	$vSearchEggArray[]='egg_gold';      $vReplaceEggArray[]='mysteryegggold';
	$vSearchEggArray[]='egg_cornish';   $vReplaceEggArray[]='mysteryeggcornish';
	$vSearchEggArray[]='egg_rhodered';  $vReplaceEggArray[]='mysteryeggrhodered';
	$vSearchEggArray[]='egg_scotsgrey'; $vReplaceEggArray[]='mysteryeggscotsgrey';
	if (!empty($decorations)) {
		foreach($decorations as $o) {
			$vMoveCnt=0;
			$vInPositionCnt=0;

			#    $u = $units[ $o['itemName'] ];
			$u = Units_GetUnitByName($o['itemName']);
			if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
			if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
			if ($o['state'] == 'vertical') {
				$t = $u['sizeX'];
				$u['sizeX'] = $u['sizeY'];
				$u['sizeY'] = $t;
			}

			foreach ($sections as $section) {
				if ($section['active']=='1' && (($section['type']=='deco' && in_array($o['itemName'], explode('|',str_replace($vSearchEggArray,$vReplaceEggArray,$section['deco']))))||$section['type']=='dontmove')) {

					$npx = $o['position']['x'];
					$npy = $o['position']['y'];
					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {

							if( !(
							($npx+$x >= $section['bot_x'] ) &&
							($npx+$x <= $section['top_x'])  &&
							($npy+$y >= $section['bot_y'])  &&
							($npy+$y <= $section['top_y'])) ) {
								$vMoveCnt++;
							} else {
								$vInPositionCnt++;
							}

						}
					}
				}
			}

			if($vMoveCnt>0 && ($vInPositionCnt==0 || $vInPositionCnt % ($u['sizeX']*$u['sizeY']))>0) {

				#      AddLog2("Sections: Found " . $o['itemName'] . " on " . $npx . "-" . $npy . " - wrong position.");

				$newpos = Sections_Find_Free_Location($sections, $u, $o, $location_empty, 'decoration');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);
					$oldpos = $o['position'];

					Sections_Announce_Move($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $oldpos['x'] + $x ][ $oldpos['y'] + $y ] = true;
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#        AddLog2("Sections: No new location found");
				}
			}
		}
	}

	@$buildings = array_merge( GetObjects('Building'), GetObjects('MysterySeedling'));
	if (!empty($buildings)) {
		foreach($buildings as $o) {
			$vMoveCnt=0;
			$vInPositionCnt=0;

			#    $u = $units[ $o['itemName'] ];
			$u = Units_GetUnitByName($o['itemName']);
			if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
			if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
			if ($o['state'] == 'vertical') {
				$t = $u['sizeX'];
				$u['sizeX'] = $u['sizeY'];
				$u['sizeY'] = $t;
			}

			foreach ($sections as $section) {
				if ($section['active']=='1' && (($section['type']=='building' && in_array($o['itemName'], explode('|',$section['building'])))||$section['type']=='dontmove')) {
					$npx = $o['position']['x'];
					$npy = $o['position']['y'];
					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {

							if( !(
							($npx+$x >= $section['bot_x'] ) &&
							($npx+$x <= $section['top_x'])  &&
							($npy+$y >= $section['bot_y'])  &&
							($npy+$y <= $section['top_y'])) ) {
								$vMoveCnt++;
							} else {
								$vInPositionCnt++;
							}

						}
					}
				}
			}

			if($vMoveCnt>0 && ($vInPositionCnt==0 || $vInPositionCnt % ($u['sizeX']*$u['sizeY']))>0) {

				#      AddLog2("Sections: Found " . $o['itemName'] . " on " . $npx . "-" . $npy . " - wrong position.");

				$newpos = Sections_Find_Free_Location($sections, $u, $o, $location_empty, 'building');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);
					$oldpos = $o['position'];

					Sections_Announce_Move($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $oldpos['x'] + $x ][ $oldpos['y'] + $y ] = true;
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#        AddLog2("Sections: No new location found");
				}
			}
		}
	}
	$vPlaceArray=array();
	foreach ($sections as $section) {
		if ($section['active']=='1' && $section['place']=='1' && $section['type']=='anim') {
			$vPlaceArray=array_merge($vPlaceArray,explode('|',$section['anim']));
		}
		if ($section['active']=='1' && $section['place']=='1' && $section['type']=='tree') {
			$vPlaceArray=array_merge($vPlaceArray,explode('|',$section['tree']));
		}
		if ($section['active']=='1' && $section['place']=='1' && $section['type']=='deco') {
			$vPlaceArray=array_merge($vPlaceArray,explode('|',str_replace($vSearchEggArray,$vReplaceEggArray,$section['deco'])));
		}
		if ($section['active']=='1' && $section['place']=='1' && $section['type']=='building') {
			$vPlaceArray=array_merge($vPlaceArray,explode('|',$section['building']));
		}
	}

	#  $vGiftboxContent=Sections_GetGiftboxContent($units);
	$vGiftboxContent=Sections_GetGiftboxContent();

	@$animals = $vGiftboxContent['animal'];
	if (!empty($animals)) {
		foreach($animals as $o) {
			if(in_array($o['name'],$vPlaceArray)) {
				#    $u = $units[ $o['name'] ];
				$u = Units_GetUnitByName($o['name']);
				if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
				if( !isset($u['sizeY']) ) $u['sizeY'] = 1;

				#    AddLog2("Sections: Found " . $o['name'] . " (anim) in GiftBox.");
				$newpos = Sections_Find_Free_Location_Place($sections, $u, $o, $location_empty, 'animal');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);

					Sections_Announce_Place($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#      AddLog2("Sections: No new location / no section found");
				}
			}
		}
	}

	@$tree = $vGiftboxContent['tree'];
	if (!empty($tree)) {
		foreach($tree as $o) {
			if(in_array($o['name'],$vPlaceArray)) {
				#    $u = $units[ $o['name'] ];
				$u = Units_GetUnitByName($o['name']);
				if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
				if( !isset($u['sizeY']) ) $u['sizeY'] = 1;

				#    AddLog2("Sections: Found " . $o['name'] . " (tree) in GiftBox.");
				$newpos = Sections_Find_Free_Location_Place($sections, $u, $o, $location_empty, 'tree');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);

					Sections_Announce_Place($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#      AddLog2("Sections: No new location / no section found");
				}
			}
		}
	}

	@$decoration = $vGiftboxContent['decoration'];
	if (!empty($decoration)) {
		foreach($decoration as $o) {
			if(in_array($o['name'],$vPlaceArray)) {
				#    $u = $units[ $o['name'] ];
				$u = Units_GetUnitByName($o['name']);
				if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
				if( !isset($u['sizeY']) ) $u['sizeY'] = 1;

				#    AddLog2("Sections: Found " . $o['name'] . " (deco) in GiftBox.");
				$newpos = Sections_Find_Free_Location_Place($sections, $u, $o, $location_empty, 'decoration');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);

					Sections_Announce_Place($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#      AddLog2("Sections: No new location / no section found");
				}
			}
		}
	}

	@$buildings = $vGiftboxContent['building'];
	if (!empty($buildings)) {
		foreach($buildings as $o) {
			if(in_array($o['name'],$vPlaceArray)) {
				#    $u = $units[ $o['name'] ];
				$u = Units_GetUnitByName($o['name']);
				if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
				if( !isset($u['sizeY']) ) $u['sizeY'] = 1;

				#    AddLog2("Sections: Found " . $o['name'] . " (deco) in GiftBox.");
				$newpos = Sections_Find_Free_Location_Place($sections, $u, $o, $location_empty, 'building');

				if ($newpos != false) {
					#AddLog2("Sections: Found free location on: " . $newpos['x'] . '-' . $newpos['y']);

					Sections_Announce_Place($o,$newpos);

					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				} else {
					#      AddLog2("Sections: No new location / no section found");
				}
			}
		}
	}

	foreach ($sections as $num=>$section) {
		if ($section['active']=='1' && ($section['type']=='buyanim' || $section['type']=='buytree' || $section['type']=='buydeco')) {
			#      $u = $units[$section[$section['type']]];
			$u = Units_GetUnitByName($section[$section['type']]);
			if(strlen($u['name'])>0) {
				if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
				if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
				while($newpos = Sections_Find_Free_Location_Buy($section, $u, $location_empty)) {
					Sections_Announce_Buy($u,$newpos);
					for($x=0;$x < $u['sizeX']; $x++) {
						for($y=0;$y < $u['sizeY']; $y++) {
							$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
						}
					}
				}
			}
			####################################################################################################
			#      $sections[$num]['active']='0';
			#      save_array($sections, 'sections.txt');
		}
	}


	if(count($vAnnounceMoveArray)>0) {
		$result=Sections_Move_Speed();
		$need_reload = $need_reload || $result;
	}

	if(count($vAnnouncePlaceArray)>0) {
		$result=Sections_Buy_Place_Speed('place');
		$need_reload = $need_reload || $result;
	}

	if(count($vAnnounceBuyArray)>0) {
		$result=Sections_Buy_Place_Speed('buy');
		$need_reload = $need_reload || $result;
	}

	if($need_reload == true) {
		$res = DoInit();
		$need_reload = false;
	}

	$animals = GetObjects('Animal');

	foreach($animals as $o) {

		#    $u = $units[ $o['itemName'] ];
		$u = Units_GetUnitByName($o['itemName']);
		if( !isset($u['sizeX']) ) $u['sizeX'] = 1;
		if( !isset($u['sizeY']) ) $u['sizeY'] = 1;
		if ($o['state'] == 'vertical') {
			$t = $u['sizeX'];
			$u['sizeX'] = $u['sizeY'];
			$u['sizeY'] = $t;
		}

		foreach ($sections as $vSNum=>$section) {
			if ($section['active']=='1' && $section['type']=='anim' && (($section['rotate']<>'' && $section['rotate']<>'nothing') || ($section['walk']<>'' && $section['walk']<>'nothing')) && in_array($o['itemName'], explode('|',$section['anim']))) {
				if(
				($o['position']['x'] >= $section['bot_x']) &&
				($o['position']['x'] <= $section['top_x']) &&
				($o['position']['y'] >= $section['bot_y']) &&
				($o['position']['y'] <= $section['top_y'])
				) {
					if(($section['rotate']<>'' && $section['rotate']<>'nothing') && ($section['walk']<>'' && $section['walk']<>'nothing')) {
						if($o['direction']<>$section['rotate'] || ($o['canWander']=='' && $section['walk']=='walk') || ($o['canWander']==1 && $section['walk']=='stay')) {
							Sections_Announce_WalkRotate($o,$section['walk']=='walk'?1:0,$section['rotate']);
						}
					} elseif(($section['rotate']=='' || $section['rotate']=='nothing') && ($section['walk']<>'' && $section['walk']<>'nothing')) {
						if(($o['canWander']=='' && $section['walk']=='walk') || ($o['canWander']==1 && $section['walk']=='stay')) {
							Sections_Announce_WalkRotate($o,$section['walk']=='walk'?1:0,$o['direction']);
						}
					} elseif(($section['rotate']<>'' && $section['rotate']<>'nothing') && ($section['walk']=='' || $section['walk']=='nothing')) {
						if($o['direction']<>$section['rotate']) {
							Sections_Announce_WalkRotate($o,$o['canWander'],$section['rotate']);
						}
					}
				}
			}
		}

	}

	if(count($vAnnounceWalkRotateArray)>0) {
		$result=Sections_WalkRotate_Speed();
		$need_reload = $need_reload || $result;
	}

	if($need_reload == true) {
		$res = DoInit();
		$need_reload=false;
	}

}
function Sections_Announce_Move($target,$newpos) {
	global $vAnnounceMoveArray;
	AddLog2("Sections: Announce Moving " . $target['itemName'] . " to " . $newpos['x'] . '-' . $newpos['y']);
	$vAnnounceMoveArray[]=array('target'=>$target,'newpos'=>$newpos);
}
function Sections_Announce_Place($unit,$newpos) {
	global $vAnnouncePlaceArray;
	AddLog2("Sections: Announce Place " . $unit['name'] . " on " . $newpos['x'] . '-' . $newpos['y']);
	$vAnnouncePlaceArray[]=array('unit'=>$unit['name'],'type'=>$unit['type'],'newpos'=>$newpos,'item'=>$unit);
}

function Sections_Announce_Buy($unit,$newpos) {
	global $vAnnounceBuyArray;
	AddLog2("Sections: Announce Buy " . $unit['name'] . " on " . $newpos['x'] . '-' . $newpos['y']);
	$vAnnounceBuyArray[]=array('unit'=>$unit['name'],'type'=>$unit['type'],'newpos'=>$newpos,'item'=>$unit);
}

function Sections_Announce_WalkRotate($o,$vWalk,$vRotate) {
	global $vAnnounceWalkRotateArray;
	AddLog2("Sections: Announce WalkRotate " . $o['position']['x'] . '-' . $o['position']['y']);
	$vAnnounceWalkRotateArray[]=array('o'=>$o,'vWalk'=>$vWalk,'vRotate'=>$vRotate,);
}

function Sections_Move_Speed() {
	global $vAnnounceMoveArray;
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] > 50) || (@$px_Setopts['bot_speed'] < 1)) {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}

	$vCntMoves=count($vAnnounceMoveArray);
	$vRunMainLoop=ceil($vCntMoves/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$need_reload = false;
		$res = 0;

		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();

		unset($vMSG);
		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$vCntMoves));$vJ++) {

			$target=$vAnnounceMoveArray[$vJ]['target'];
			$newpos=$vAnnounceMoveArray[$vJ]['newpos'];

			$amf->_bodys[0]->_value[1][$vNumAction]['params'] = array();
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = 'move' ;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = $target;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2] = array();

			if(isset($target['canWander'])) $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['canWander'] = $target['canWander'];
			if(isset($target['state'])) $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['state'] = $target['state'];
			if(isset($target['direction'])) $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['direction'] = $target['direction'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['x'] = (int)$newpos['x'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['y'] = (int)$newpos['y'];

			$amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = 'WorldService.performAction';

			$vNumAction++;
			$vMSG[]=$newpos['x'].'/'.$newpos['y'];
		}

		$amf->_bodys[0]->_value[2] = 0;
		AddLog2("Sections: Speed_Moving to ".implode(' ',$vMSG));
		$res = RequestAMF($amf);
		AddLog2("Sections: result $res");
		if ($res === 'OK') {
			$need_reload = true;
		}

	}

	return ($need_reload);
}

function Sections_Buy_Place_Speed($vDo='buy') {
	if($vDo=='buy') {
		global $vAnnounceBuyArray;
		$vItemArray=$vAnnounceBuyArray;
	} else {
		global $vAnnouncePlaceArray;
		$vItemArray=$vAnnouncePlaceArray;
	}
	global $vCnt63000;
	if($vCnt63000<63000) $vCnt63000=63000;

	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] > 50) || (@$px_Setopts['bot_speed'] < 1)) {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}

	$vCntMoves=count($vItemArray);
	$vRunMainLoop=ceil($vCntMoves/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$need_reload = false;
		$res = 0;

		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();

		unset($vMSG);
		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$vCntMoves));$vJ++) {

			$unit=$vItemArray[$vJ]['unit'];
			$type=$vItemArray[$vJ]['type'];
			$newpos=$vItemArray[$vJ]['newpos'];

			if(array_key_exists('type', $vItemArray[$vJ]['item'])) {
				$state = 'static';
				if($vItemArray[$vJ]['type']['item']=='decoration') $state = 'static';
				if($vItemArray[$vJ]['type']['item']=='rotateableDecoration') $state = 'horizontal';
				if($vItemArray[$vJ]['type']['item']=='animal') $state = 'bare';
			} else {
				$state = 'static';
			}

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0]                           = 'place';
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName']               = $unit;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction']              = 1;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['state']                  = $state;
			if(array_key_exists('growTime', $vItemArray[$vJ]['item'])) {
				list($vUSec,$vSec) = explode(" ", microtime());
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['plantTime']              = (string)$vSec.substr((string)$vUSec, 2, 3);
			}
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted']                = false;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id']                     = $vCnt63000 ++;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className']              = $type;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId']                 = -1;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['z']          = 0;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['x']          = (int)$newpos['x'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['y']          = (int)$newpos['y'];
			if($vDo=='buy') {
				####################################################################################################
				#        $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isStorageWithdrawal'] = 0;
				#        $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift']              = false;
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isStorageWithdrawal'] = -1;
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift']              = false;
			} else {
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isStorageWithdrawal'] = -1;
				$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift']              = true;
			}
			$amf->_bodys[0]->_value[1][$vNumAction]['sequence']                            = GetSequense();
			$amf->_bodys[0]->_value[1][$vNumAction]['functionName']                        = 'WorldService.performAction';

			$vNumAction++;
			$vMSG[]=$newpos['x'].'/'.$newpos['y'];
		}

		$amf->_bodys[0]->_value[2] = 0;
		if($vDo=='buy') {
			AddLog2("Sections: Speed_Buy on ".implode(' ',$vMSG));
		} else {
			AddLog2("Sections: Speed_Place on ".implode(' ',$vMSG));
		}

		$res = RequestAMF($amf);
		AddLog2("Sections: result $res");
		if ($res === 'OK') {
			$need_reload = true;
		}

	}

	return ($need_reload);
}

function Sections_WalkRotate_Speed() {
	global $vAnnounceWalkRotateArray;
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] > 50) || (@$px_Setopts['bot_speed'] < 1)) {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}

	$vCntMoves=count($vAnnounceWalkRotateArray);
	$vRunMainLoop=ceil($vCntMoves/$vSpeed);

	for($vI=0;$vI<$vRunMainLoop;$vI++) {

		$need_reload = false;
		$res = 0;

		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();

		unset($vMSG);
		$vNumAction=0;
		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$vCntMoves));$vJ++) {

			$o=$vAnnounceWalkRotateArray[$vJ]['o'];
			$vWalk=$vAnnounceWalkRotateArray[$vJ]['vWalk'];
			$vRotate=$vAnnounceWalkRotateArray[$vJ]['vRotate'];

			$amf->_bodys[0]->_value[1][$vNumAction]['params'] = array();
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = 'move' ;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = $o;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2] = array();

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['canWander'] = $vWalk;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['state'] = $o['state'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['direction'] = $vRotate;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['x'] = $o['position']['x'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['y'] = $o['position']['y'];
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['z'] = $o['position']['z'];

			$amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = 'WorldService.performAction';

			$vNumAction++;
			$vMSG[]=$o['position']['x'].'/'.$o['position']['y'];
		}

		$amf->_bodys[0]->_value[2] = 0;
		AddLog2("Sections: Speed_WalkRotate on ".implode(' ',$vMSG));
		$res = RequestAMF($amf);
		AddLog2("Sections: result $res");
		if ($res === 'OK') {
			$need_reload = true;
		}

	}

	return ($need_reload);
}


function Sections_Find_Free_Location($sections, $unit, $o, $locs, $vWhat) {
	$vSearchEggArray[]='egg_white';     $vReplaceEggArray[]='mysteryeggwhite';
	$vSearchEggArray[]='egg_brown';     $vReplaceEggArray[]='mysteryeggbrown';
	$vSearchEggArray[]='egg_black';     $vReplaceEggArray[]='mysteryeggblack';
	$vSearchEggArray[]='egg_gold';      $vReplaceEggArray[]='mysteryegggold';
	$vSearchEggArray[]='egg_cornish';   $vReplaceEggArray[]='mysteryeggcornish';
	$vSearchEggArray[]='egg_rhodered';  $vReplaceEggArray[]='mysteryeggrhodered';
	$vSearchEggArray[]='egg_scotsgrey'; $vReplaceEggArray[]='mysteryeggscotsgrey';
	for($x=0;$x < $unit['sizeX']; $x++) {
		for($y=0;$y < $unit['sizeY']; $y++) {
			$locs[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ] = true;
		}
	}
	foreach ($sections as $section) {
		if (
		$section['active']=='1' && (
		($section['type']=='anim' && $vWhat=='animal'     && in_array($o['itemName'],explode('|',$section['anim'])))
		||
		($section['type']=='tree' && $vWhat=='tree'       && in_array($o['itemName'],explode('|',$section['tree'])))
		||
		($section['type']=='deco' && $vWhat=='decoration' && in_array($o['itemName'],explode('|',$section['deco'])))
		||
		($section['type']=='deco' && $vWhat=='decoration' && in_array($o['itemName'],explode('|',str_replace($vSearchEggArray,$vReplaceEggArray,$section['deco']))))
		||
		($section['type']=='building' && $vWhat=='building' && in_array($o['itemName'],explode('|',$section['building'])))
		)
		) {

			for($x = $section['bot_x']; $x <= ($section['top_x']-$unit['sizeX']+1);$x++) {
				for($y = $section['bot_y']; $y <= ($section['top_y']-$unit['sizeY']+1);$y++) {

					$is_available = true;

					for ($x2 = 0; $x2 < $unit['sizeX']; $x2++) {
						for ($y2 = 0; $y2 < $unit['sizeY']; $y2++) {
							if ($locs[$x+$x2][$y+$y2] == false) {
								$is_available = false;
							}
						}
					}

					if ($is_available) {
						return array("x"=>$x,"y"=>$y);
					}
				}
			}
		}
	}
	return false;
}

function Sections_Find_Free_Location_Place($sections, $unit, $o, $locs, $vWhat) {
	$vSearchEggArray[]='egg_white';     $vReplaceEggArray[]='mysteryeggwhite';
	$vSearchEggArray[]='egg_brown';     $vReplaceEggArray[]='mysteryeggbrown';
	$vSearchEggArray[]='egg_black';     $vReplaceEggArray[]='mysteryeggblack';
	$vSearchEggArray[]='egg_gold';      $vReplaceEggArray[]='mysteryegggold';
	$vSearchEggArray[]='egg_cornish';   $vReplaceEggArray[]='mysteryeggcornish';
	$vSearchEggArray[]='egg_rhodered';  $vReplaceEggArray[]='mysteryeggrhodered';
	$vSearchEggArray[]='egg_scotsgrey'; $vReplaceEggArray[]='mysteryeggscotsgrey';

	foreach ($sections as $section) {
		if (
		$section['active']=='1' && $section['place']=='1' && (
		($section['type']=='anim' && $vWhat=='animal'     && in_array($o['name'],explode('|',$section['anim'])))
		||
		($section['type']=='tree' && $vWhat=='tree'       && in_array($o['name'],explode('|',$section['tree'])))
		||
		($section['type']=='deco' && $vWhat=='decoration' && in_array($o['name'],explode('|',$section['deco'])))
		||
		($section['type']=='deco' && $vWhat=='decoration' && in_array($o['name'],explode('|',str_replace($vSearchEggArray,$vReplaceEggArray,$section['deco']))))
		||
		($section['type']=='building' && $vWhat=='building' && in_array($o['name'],explode('|',$section['building'])))
		)
		) {
			for($x = $section['bot_x']; $x <= ($section['top_x']-$unit['sizeX']+1);$x++) {
				for($y = $section['bot_y']; $y <= ($section['top_y']-$unit['sizeY']+1);$y++) {

					$is_available = true;

					for ($x2 = 0; $x2 < $unit['sizeX']; $x2++) {
						for ($y2 = 0; $y2 < $unit['sizeY']; $y2++) {
							if ($locs[$x+$x2][$y+$y2] == false) {
								$is_available = false;
							}
						}
					}

					if ($is_available) {
						return array("x"=>$x,"y"=>$y);
					}
				}
			}
		}
	}
	return false;
}

function Sections_Find_Free_Location_Buy($section, $unit, $locs) {

	for($x = $section['bot_x']; $x <= ($section['top_x']-$unit['sizeX']+1);$x++) {
		for($y = $section['bot_y']; $y <= ($section['top_y']-$unit['sizeY']+1);$y++) {

			$is_available = true;

			for ($x2 = 0; $x2 < $unit['sizeX']; $x2++) {
				for ($y2 = 0; $y2 < $unit['sizeY']; $y2++) {
					if ($locs[$x+$x2][$y+$y2] == false) {
						$is_available = false;
					}
				}
			}

			if ($is_available) {
				return array("x"=>$x,"y"=>$y);
			}
		}
	}

	return false;
}

function Sections_plant_sections() {
	global $need_reload;
	$enable_seed = true;	
	echo "Sections v",sections_Version," >> plant_sections\r\n";

	$sections = load_array('sections.txt');
	if (!((is_array($sections) && count($sections)))) { return; }

	#return;  // uncomment to disable for debug purposes

	if ($need_reload) {
		$res = DoInit(); //reload farm
		$need_reload=false;
	}

	#  $units = @unserialize(file_get_contents(F('units.txt')));
	$objects = @unserialize(fBGetDataStore('objects'));
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $flashRevision) = explode(';', fBGetDataStore('playerinfo'));

	// Build matrix so we can know which locations are empty

	$location_empty = array();
	for ($x = 0; $x < $sizeX; $x++) {
		for ($y = 0; $y < $sizeY; $y++) {
			$location_empty[$x][$y] = true;
		}
	}

	foreach($objects as $o) {
		#    $u = $units[$o['itemName']];
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
				$location_empty[ $o['position']['x'] + $x ][ $o['position']['y'] + $y ] = false;
			}
		}
	}

	foreach ($sections as $section) {
		if ($section['active']=='1' && $section['place']=='1' && $section['type']=='seed') {

			$u['sizeX'] = 4;
			$u['sizeY'] = 4;
			while($newpos = Sections_Find_Free_Location_Buy($section, $u, $location_empty)) {
				unset($vPlot);
				$vPlot['itemName']      = null;
				$vPlot['isProduceItem'] = false;
				$vPlot['isBigPlot']     = false;
				$vPlot['direction']     = 0;
				$vPlot['plantTime']     = 'NaN';
				$vPlot['deleted']       = false;
				$vPlot['isJumbo']       = false;
				$vPlot['state']         = 'plowed';
				$vPlot['tempId']        = -1;
				$vPlot['id']            = 0;
				$vPlot['className']     = 'Plot';
				$vPlot['position']['z'] = 0;
				$vPlot['position']['x'] = (int)$newpos['x'];
				$vPlot['position']['y'] = (int)$newpos['y'];

				$tractor_plots[]=$vPlot;

				for($x=0;$x < $u['sizeX']; $x++) {
					for($y=0;$y < $u['sizeY']; $y++) {
						$location_empty[ $newpos['x'] + $x ][ $newpos['y'] + $y ] = false;
					}
				}
			}
		}
	}

	if(isset($tractor_plots) && count($tractor_plots)>0) {
		$result = Do_Farm_Work_Plots($tractor_plots, 'tractor');
		$need_reload = $need_reload || $result;
	}

	// Below mostly copied from parser.php with adaptions
	if ($need_reload) {
		$res = DoInit(); //reload farm
		$need_reload=false;
	}

	if ($enable_seed) {
		$plots = GetObjects('Plot');

		//Find empty plots
		$plowed_plots = array();
		foreach ($plots as $plowed_key => $plot) {
			if ($plot['state'] == 'plowed') {
				$plowed_plots[] = $plot;
			}
		}

		$seed_plots = array();
		foreach ($plowed_plots as $plot) {
			$itemName = "";
			$px = floor($plot['position']['x']/4);
			$py = floor($plot['position']['y']/4);

			$npx = $plot['position']['x'];
			$npy = $plot['position']['y'];

			foreach ($sections as $section) {
				if(
				$section['active']=='1' &&
				($section['type'] == 'seed') &&
				($section['seed'] <> 'just_plow') &&
				($section['seed'] <> '---') &&
				($npx >= $section['bot_x'] ) &&
				($npx <= $section['top_x']) &&
				($npy >= $section['bot_y']) &&
				($npy <= $section['top_y'])) {
					// found the section
					switch($section['pat']) {
						case 'checkerboard':
							$itemName = ((($px+$py)%2) == 1) ? $section['seed'] : $section['seed2'];
							break;
						case 'striped-row':
							$itemName = ((($py)%2) == 1) ? $section['seed'] : $section['seed2'];
							break;
						case 'striped-col':
							$itemName = ((($px)%2) == 1) ? $section['seed'] : $section['seed2'];
							break;
						case 'squared1':
							$itemName = (((($px)%2) == 1) || ((($py)%2) == 1)) ? $section['seed'] : $section['seed2'];
							break;
						case 'squared2':
							$itemName = (((($px)%2) == 1) || ((($py)%2) == 1)) ? $section['seed'] : $section['seed2'];
							break;
						case 'corner-w':
							$itemName = ((((($px)%2) == 1) && ($px >= $py)) || (((($py)%2) == 1) && ($py >= $px))) ? $section['seed'] : $section['seed2'];
							break;
						case 'corner-e':
							$itemName = ((((($px)%2) == 1) && ($px <= $py)) || (((($py)%2) == 1) && ($py <= $px))) ? $section['seed'] : $section['seed2'];
							break;
						case 'corner-n':
							$sz_x = floor(($section['top_x'] - $section['bot_x'])/4);
							$itemName = ((((($px)%2) == 1) && ($sz_x - $px <= $py)) || (((($py)%2) == 1) && ($py <= $sz_x - $px))) ? $section['seed'] : $section['seed2'];
							break;
						case 'corner-s':
							$sz_x = floor(($section['top_x'] - $section['bot_x'])/4);
							$itemName = ((((($px)%2) == 1) && ($sz_x - $px >= $py)) || (((($py)%2) == 1) && ($py <= $sz_x - $px))) ? $section['seed'] : $section['seed2'];
							break;
						default:
							$itemName = $section['seed'];
					}

				}
			}

			if (strlen($itemName) > 0) {
				$plot['itemName'] = $itemName;
				$seed_plots[] = $plot;
				#      } else {
				#        echo GetPlotName($plot) . " not in a section.";
			}
		}
		if (count($seed_plots) > 0) {
			Do_Farm_Work_Plots($seed_plots, 'place');  //plant crops
		}
		unset($seed_plots, $plowed_plots);
	}
}

function Sections_Draw_Thing($image, $x, $y, $sz_x, $sz_y, $ratio, $fc, $is_section=false) {
	$s_x_horiz = $sz_x * $ratio * 2;
	$s_x_vert  = -1 * $sz_x * $ratio;

	$s_y_horiz = $sz_y * $ratio * 2;
	$s_y_vert  = -1 * $sz_y * $ratio;

	#  $base_x = (107 * $ratio * 2) + (($x - $y) * $ratio * 2);
	#  $base_y = (216 * $ratio) - (($x + $y) * $ratio);
	$base_x = (117 * $ratio * 2) + (($x - $y) * $ratio * 2);
	$base_y = (232 * $ratio) - (($x + $y) * $ratio);

	$col_poly = $is_section ? imagecolorallocate($image, 50, 50, 50) : imagecolorallocate($image, 0, 200, 0);
	$col_fill = count($fc) == 3 ? imagecolorallocate($image, $fc[0], $fc[1], $fc[2]) : imagecolorallocatealpha($image, $fc[0], $fc[1], $fc[2], $fc[3]);

	imagefilledpolygon($image, array(
	$base_x                            , $base_y,
	$base_x + $s_x_horiz               , $base_y + $s_x_vert,
	$base_x + $s_x_horiz - $s_y_horiz  , $base_y + $s_x_vert + $s_y_vert,
	$base_x - $s_y_horiz               , $base_y + $s_y_vert
	), 4, $col_fill);

	imagepolygon($image, array(
	$base_x                            , $base_y,
	$base_x + $s_x_horiz               , $base_y + $s_x_vert,
	$base_x + $s_x_horiz - $s_y_horiz  , $base_y + $s_x_vert + $s_y_vert,
	$base_x - $s_y_horiz               , $base_y + $s_y_vert
	), 4, $col_poly);

}

function Sections_Write_Caption($image, $x, $y, $sz_x, $sz_y, $ratio, $text="") {
	$s_x_horiz = $sz_x * $ratio * 2;
	$s_x_vert  = -1 * $sz_x * $ratio;

	$s_y_horiz = $sz_y * $ratio * 2;
	$s_y_vert  = -1 * $sz_y * $ratio;

	$base_x = (117 * $ratio * 2) + (($x - $y) * $ratio * 2);
	$base_y = (232 * $ratio) - (($x + $y) * $ratio);

	$color = imagecolorallocate($image, 0, 0, 0);

	$font_size=1;

	$text_width = imagefontwidth($font_size) * strlen($text);
	$text_height = imagefontheight($font_size);

	$xpos = $base_x - $s_y_horiz + (.5 * ($s_x_horiz + $s_y_horiz)) - (ceil($text_width/2));
	$ypos = $base_y + (.5 * ($s_x_vert + $s_y_vert))                - (ceil($text_height/2));

	ImageString($image, $font_size, $xpos, $ypos, $text, $color);

}


function Sections_Draw_MapThing($x, $y, $sz_x, $sz_y, $ratio, $rest="") {
	$s_x_horiz = $sz_x * $ratio * 2;
	$s_x_vert  = -1 * $sz_x * $ratio;

	$s_y_horiz = $sz_y * $ratio * 2;
	$s_y_vert  = -1 * $sz_y * $ratio;

	$base_x = (117 * $ratio * 2) + (($x - $y) * $ratio * 2);
	$base_y = (232 * $ratio) - (($x + $y) * $ratio);

	echo "<AREA SHAPE='poly' COORDS='".implode(',',array(
	$base_x                  , $base_y,
	$base_x + $s_x_horiz          , $base_y + $s_x_vert,
	$base_x + $s_x_horiz - $s_y_horiz    , $base_y + $s_x_vert + $s_y_vert,
	$base_x - $s_y_horiz          , $base_y + $s_y_vert)
	)."' " . $rest . ">\n";
}

?>

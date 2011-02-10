<?php
//########################################################################
// mysterygift functions
//########################################################################

// mystery gifts ++++++++++++++++++++++++++++++++++++++
function MG_openGifts($from, $getFarm) {
	global $TB_settings;
	$farm = $getFarm;
	if( $from == 'farm') {
		$gifts = MG_getGifts('farm');
		if(count($gifts) == 0) {
			AddLog2('-> no MGs on farm');
			return false;
		}
		if( $TB_settings['MG_openGiftsFarmAll'] ) {
			$i = count($gifts);
		} else {
			$i = $TB_settings['MG_openGiftsFarm'];
		}
		$gib = TB_numberGiftBox();
		foreach($gifts as $gift) {
			if($i > 0 && $gib < $TB_settings['TB_giftboxLimit']) {
				AddLog2('opening - ' . ($i - 1) . ' left');
				if( MG_openEggOrGift($gift, 'gift') ) {
					$gib++;
					$i--;
					$farm[$gift['position']['x']][$gift['position']['y']] = true;
				} else {
					break;
				}
			} elseif($gib >= $TB_settings['TB_giftboxLimit']) {
				AddLog2('gift box is full');
				break;
			}
		}
	} elseif( $from == 'giftbox') {
		if(MG_getGifts('giftbox') == 0) {
			AddLog2('-> no MGs in gift box');
			return false;
		}
		if( $TB_settings['MG_openGiftsGiftboxAll'] ) {
			$i = MG_getGifts('giftbox');
		} else {
			$i = $TB_settings['MG_openGiftsGiftbox'];
		}
		$gib = TB_numberGiftBox();
		$data = array();
		$position = array();
		if($TB_settings['MG_openGiftsGiftboxJustPlace']) {
			while($i > 0) {
				AddLog2('placing - ' . ($i - 1) . ' left');
				$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm);
				$data = MG_placeEggOrGift('gift', $position['x'], $position['y']);

				if($data['ans'] == 'OK') {
					$farm[$position['x']][$position['y']] = false;
				} else {
					AddLog2( "ERROR - " . $data['ans'] );
					return false;
				}
				$i--;
			}
		} else {
			if($gib > $TB_settings['TB_giftboxLimit']) {
				AddLog2('gift box is full');
				break;
			}
			while($i > 0 && $gib <= $TB_settings['TB_giftboxLimit']) {
				AddLog2('opening - ' . ($i - 1) . ' left');
				$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm);
				$data = MG_placeEggOrGift('gift', $position['x'], $position['y']);

				if($data['ans'] == 'OK' && isset($data['ID']) ) {
					$gib--;
					$ans = MG_openEggOrGift($data['ID'], 'gift', $data);
				} elseif( $data['ans'] == 'OK')  {
					AddLog2( "ERROR - no ID" );
					return false;
				} else {
					AddLog2( "ERROR - " . $data['ans'] );
					return false;
				}
				$gib++;
				$i--;
				if(!$ans) return false;
			}
		}
	}
	return $farm;
}

function MG_getGifts($from = 'farm') {
	$objects = array();
	$i = 0;
	if( $from == 'farm') {
		$gifts = array();
		$objects = TB_loadObjects();
		foreach($objects as $obj)
		{
			if($obj['className'] == 'MysteryGift')	{
				$gifts[$i] = $obj;
				$i++;
			}
		}
	} elseif( $from == 'giftbox') {
		$objects = TB_loadGiftBox();
		$gifts = 0;
		foreach($objects as $gift => $num)
		{
			if(GetNameByCode($gift) == 'mysterygift')	{
				$gifts = $num;
			}
		}
	}
	return $gifts;
}

//mytsrey eggs ++++++++++++++++++++++++++++++++++++++++
function MG_openEggs($from, $getFarm) {
	global $TB_settings;
	$farm = $getFarm;
	//farm
	if( $from == 'farm') {
		$eggs = MG_getEggs('farm');
		if(count($eggs) == 0) {
			AddLog2('-> no MEs on farm');
			return false;
		}
		if( $TB_settings['MG_openEggsFarmAll'] ) {
			$i = count($eggs);
		} else {
			$i = $TB_settings['MG_openEggsFarm'];
		}
		$gib = TB_numberGiftBox();
		foreach($eggs as $egg) {
			if($i > 0 && $gib < $TB_settings['TB_giftboxLimit']) {
				AddLog2('opening - ' . ($i - 1) . ' left ('.$egg['itemName'].')');
				if( MG_openEggOrGift($egg, 'egg') ) {
					$gib++;
					$i--;
					$farm[$egg['position']['x']][$egg['position']['y']] = true;
				} else {
					break;
				}
			} elseif($gib >= $TB_settings['TB_giftboxLimit']) {
				AddLog2('gift box is full');
				break;
			}
		}
		//giftbox
	} elseif( $from == 'giftbox') {
		$giftArr = MG_getEggs('giftbox');
		if($giftArr['count'] == 0) {
			AddLog2('-> no MEs in gift box');
			return false;
		}
		if( $TB_settings['MG_openEggsGiftboxAll'] ) {
			$i = $giftArr['count'];
		} else {
			$i = $TB_settings['MG_openEggsGiftbox'];
		}
		if($i > $giftArr['count']) $i = $giftArr['count'];
		$max = $i;
		$gib = TB_numberGiftBox();
		$data = array();
		$position = array();
		if($TB_settings['MG_openEggsGiftboxJustPlace']) {
			while($i > 0) {
				AddLog2('placing - ' . ($i - 1) . ' left (' . $giftArr['itemName'][$max - $i] . ')');
				$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm);
				//AddLog2('position: ' . $position['x'] . '-' . $position['y']);
				$data = MG_placeEggOrGift($giftArr['itemName'][$max - $i], $position['x'], $position['y']);

				if($data['ans'] == 'OK') {
					$farm[$position['x']][$position['y']] = false;
				} else {
					AddLog2( "ERROR - " . $data['ans'] );
					return false;
				}
				$i--;
			}
		} else {
			while($i > 0 && $gib <= $TB_settings['TB_giftboxLimit']) {
				AddLog2('opening - ' . ($i - 1) . ' left (' . $giftArr['itemName'][$max - $i] . ')');
				$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm);
				$data = MG_placeEggOrGift($giftArr['itemName'][$max - $i], $position['x'], $position['y']);

				if($data['ans'] == 'OK' && isset($data['ID']) ) {
					$ans = MG_openEggOrGift($data['ID'], $giftArr['itemName'][$max - $i], $data);
				} elseif( $data['ans'] == 'OK')  {
					AddLog2( "ERROR - no ID" );
					return false;
				} else {
					AddLog2( "ERROR - " . $data['ans'] );
					return false;
				}
				$i--;
				if(!$ans) return false;
			}
		}
	}
	return $farm;
}

function MG_getEggs($from = 'farm') {
	$objects = array();
	$i = 0;
	$itemNames[] = 'mysteryboxeaster';
	$itemNames[] = 'mysteryeggwhite';
	$itemNames[] = 'mysteryeggbrown';
	$itemNames[] = 'mysteryeggblack';
	$itemNames[] = 'mysteryegggold';
	$itemNames[] = 'mysteryeggcornish';
	$itemNames[] = 'mysteryeggscotsgrey';
	$itemNames[] = 'mysteryeggrhodered';

	if( $from == 'farm') {
		$gifts = array();
		$objects = TB_loadObjects();
		foreach($objects as $obj)
		{
			if(in_array($obj['itemName'], $itemNames))	{
				$gifts[$i] = $obj;
				$i++;
			}
		}
	} elseif( $from == 'giftbox') {
		$objects = TB_loadGiftBox();
		$gifts = array();
		$gifts['count'] = 0;
		foreach($objects as $gift => $num)
		{
			if(in_array(GetNameByCode($gift), $itemNames))	{
				$gifts['count'] = $gifts['count'] + $num;
				for($tmp = 0; $tmp < $num; $tmp++) {
					$gifts['itemName'][$i] = GetNameByCode($gift);
					$i++;
				}
			}
		}
	}
	return $gifts;
}

//functions
function MG_openEggOrGift($thing, $name, $data = '') {
	if( is_array($thing) ) {
		$amf = CreateRequestAMF('open', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1] = $thing;
		$amf->_bodys[0]->_value[1][0]['params'][2] = array();
	} elseif($data <> '') {
		switch($name) {
			case 'gift':
				$className = 'MysteryGift';
				$itemName = 'mysterygift';
				break;
			default:
				$className = 'LootableDecoration';
				$itemName = $name;
				break;
		}
		$amf                                                        = CreateRequestAMF( 'open', 'WorldService.performAction' );
		$amf->_bodys[0]->_value[1][0]['params'][1]['className']     = $className;
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']      = $itemName;
		$amf->_bodys[0]->_value[1][0]['params'][1]['direction']     = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $data['x'];
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $data['y'];
		$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
		$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']       = false;
		$amf->_bodys[0]->_value[1][0]['params'][1]['state']        	= 'static';
		$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] 		= 'NaN';
		$amf->_bodys[0]->_value[1][0]['params'][1]['id']  			= $thing;
		$amf->_bodys[0]->_value[1][0]['params'][2] = array();
	} else {
		AddLog2('MG_openEggOrGift <- wrong parameter');
		return false;
	}

	$res = RequestAMF($amf);
	if ( $res == 'OK' ) {
		AddLog2( '-> ' . $res );
		return true;
	} else {
		AddLog2( "ERROR - $res" );
		return false;
	}
}

function MG_placeEggOrGift($name, $x, $y) {
	switch($name) {
		case 'gift':
			$className = 'MysteryGift';
			$itemName = 'mysterygift';
			break;
		default:
			$className = 'LootableDecoration';
			$itemName = $name;
			break;
	}
	$data = array();
	$amf                                                                    = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']               	= $className;
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                	= $itemName;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            	= $x;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            	= $y;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                   = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['state']                     = 'static';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                    = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   	= -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                	= true;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] 	= false;

	$tmp = TB_sendRequest($amf);
	$amf2 = $tmp['amf2'];
	$res = $tmp['res'];

	$ID = $amf2->_bodys[0]->_value['data'][0]['data']['id'];

	$data['ans'] = $res;
	$data['ID'] = $ID;
	$data['x'] = $x;
	$data['y'] = $y;
	return $data;
}

//########################################################################
// flowermanager functions
//########################################################################

function FM_delet($flower = '', $replace = false) {
	if($flower <> '' && is_array($flower)) {
		//deleting one flower
		if(!$replace) AddLog2("Deleting: " . GetNameByItem($flower['itemName']));

		$amf = CreateRequestAMF('clear', 'WorldService.performAction');
		$amf->_bodys[0]->_value[1][0]['params'][1] = $flower;
		$amf->_bodys[0]->_value[1][0]['params'][2] = array();

		$res = RequestAMF($amf);
		if ( $res == 'OK' ) {
			if(!$replace) AddLog2( '-> ' . $res );
			return true;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else {
		//deleting more flowers
		AddLog2("Deleting ...");

		$flowers = array();
		$flowers = FM_getDroop();
		$i = 0;
		$left = count($flowers);
		$amf = '';
		foreach($flowers as $flower) {
			$amf = CreateMultAMFRequest($amf, $i, 'clear', 'WorldService.performAction');
			$amf->_bodys[0]->_value[1][$i]['params'][1] = $flower;
			$amf->_bodys[0]->_value[1][$i]['params'][2] = array();
			$i++;
			$left--;

			if($i >= $TB_settings['TB_speedAction']) {
				$res = RequestAMF( $amf );
				if ( $res == 'OK' ) {
					if(!$replace) AddLog2( '-> ' . $res . ' ' . $left . ' left');
					$amf = '';
					$i = 0;
				} else {
					AddLog2( "ERROR - $res" );
					return false;
				}
			}
		}

		if($i > 0) {
			$res = RequestAMF( $amf );
			if ( $res == 'OK' ) {
				AddLog2( '-> ' . $res );
				return true;
			} else {
				AddLog2( "ERROR - $res" );
				return false;
			}
		} else return true;
	}
}

function FM_replace() {
	global $TB_settings;

	$flowers = array();
	$flowers = FM_getDroop();

	switch($TB_settings['FM_replaceSource']) {
		case 'shed':
			$shed = FM_loadFlowershed();
			if($shed == 0 ) {
				AddLog2('no shed');
				return false;
			} elseif(count($shed['contents']) == 0) {
				AddLog2('no flowers in shed');
			}
			foreach($flowers as $dFlower) {
				$found = false;
				AddLog2("try to replace " . GetNameByItem($dFlower['itemName']));
				foreach($shed['contents'] as $key => $sFlower)
				{
					if( GetNameByCode($sFlower['itemCode']) == $dFlower['itemName'] || !$TB_settings['FM_replaceSame'])
					{
						if($sFlower['numItem'] > 0) {
							AddLog2("Replacing " . GetNameByItem($dFlower['itemName']) . " with ". GetNameByItem($sFlower['itemCode']));
							if( FM_doShed($dFlower, $shed, GetNameByCode($sFlower['itemCode']))) {
								$sFlower['numItem']--;
								$shed['contents'][$key]['numItem']--;
								$found = true;
							} else {
								return false;
							}
						}
					}
					if($found) break;
				}
				if(!$found) {
					AddLog2('no flower in shed or gift box');
				} else {
					AddLog2('done!');
				}
			}
			break;
		case 'giftbox':
			$giftbox = FM_getGiftbox();
			if(count($giftbox) == 0) {
				AddLog2('giftbox is empty');
				return false;
			}
			foreach($flowers as $dFlower) {
				$found = false;
				AddLog2("try to replace " . GetNameByItem($dFlower['itemName']));
				foreach($giftbox as $gFlower => $num)
				{
					if( $gFlower == $dFlower['itemName'] || !$TB_settings['FM_replaceSame'])
					{
						if($num > 0) {
							AddLog2("Replacing " . GetNameByItem($dFlower['itemName']) . " with ". GetNameByItem($gFlower));
							if( FM_doGiftBox($dFlower, $gFlower) ) {
								$giftbox[$gFlower]--;
								$found = true;
							}  else {
								return false;
							}
						}
					}
					if($found) break;
				}
				if(!$found) {
					AddLog2('no flower in shed or gift box');
				} else {
					AddLog2('done!');
				}
			}
			break;
		case 'both':
			$shed = FM_loadFlowershed();
			$giftbox = FM_getGiftbox();
			if($shed == 0 ) {
				AddLog2('no shed');
			} elseif(count($shed['contents']) == 0) {
				AddLog2('no flowers in shed');
			}
			if(count($giftbox) == 0) {
				AddLog2('giftbox is empty');
			}
			foreach($flowers as $dFlower) {
				$found = false;
				AddLog2("try to replace " . GetNameByItem($dFlower['itemName']));
				if(count($shed['contents']) > 0) {
					foreach($shed['contents'] as $key => $sFlower)
					{
						if( GetNameByCode($sFlower['itemCode']) == $dFlower['itemName'] || !$TB_settings['FM_replaceSame'])
						{
							if($sFlower['numItem'] > 0) {
								AddLog2("Replacing " . GetNameByItem($dFlower['itemName']) . " with ". GetNameByItem($sFlower['itemCode']));
								if( FM_doShed($dFlower, $shed, GetNameByCode($sFlower['itemCode']))) {
									$sFlower['numItem']--;
									$shed['contents'][$key]['numItem']--;
									$found = true;
								} else {
									return false;
								}
							}
						}
						if($found) break;
					}
				}
				if(!$found && count($giftbox) > 0) {
					foreach($giftbox as $gFlower => $num)
					{
						if( $gFlower == $dFlower['itemName'] || !$TB_settings['FM_replaceSame'])
						{
							if($num > 0) {
								AddLog2("Replacing " . GetNameByItem($dFlower['itemName']) . " with ". GetNameByItem($gFlower));
								if( FM_doGiftBox($dFlower, $gFlower) ) {
									$giftbox[$gFlower]--;
									$found = true;
								} else {
									return false;
								}
							}
						}
						if($found) break;
					}
				}
				if(!$found) {
					AddLog2('no flower in shed or gift box');
				} else {
					AddLog2('done!');
				}
			}
			break;
	}
	return true;
}

function FM_doShed($delFlower, $shed, $flower) {
	//delet
	if( FM_delet($delFlower,true) == false ) {
		AddLog2('Error: deleting');
		return false;
	}
	//replace
	$amf                                                                    = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']               	= 'FlowerDecoration';
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                	= $flower;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            	= $delFlower['position']['x'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            	= $delFlower['position']['y'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                   = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['state']                     = 'live';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                    = -1;
	$amf->_bodys[0]->_value[1][0]['params'][1]['plantTime']                 = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   	= $shed['id'];
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                	= false;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] 	= false;
	$res                                                                    = RequestAMF( $amf );

	if ( $res == 'OK' ) {
		AddLog2( 'replacing -> ' . $res );
		return true;
	} else {
		AddLog2( "ERROR - $res" );
		return false;
	}
}

function FM_doGiftBox($delFlower, $flower) {
	//delet
	if( FM_delet($delFlower,true) == false ) {
		AddLog2('Error: deleting');
		return false;
	}
	//replace
	$amf                                                                    = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']               	= 'FlowerDecoration';
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                	= $flower;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            	= $delFlower['position']['x'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            	= $delFlower['position']['y'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                   = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['state']                     = 'live';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                    = -1;
	$amf->_bodys[0]->_value[1][0]['params'][1]['plantTime']                 = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   	= -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                	= true;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] 	= false;
	$res                                                                    = RequestAMF( $amf );

	if ( $res == 'OK' ) {
		AddLog2( 'replacing -> ' . $res );
		return true;
	} else {
		AddLog2( "ERROR - $res" );
		return false;
	}
}

function FM_getFlowers() {
	$objects = array();
	$objects = TB_loadObjects();

	$arr = array();
	$i = 0;

	foreach($objects as $obj)
	{
		if($obj['className'] == 'FlowerDecoration')	{
			$arr[$i] = $obj;
			$i++;
		}
	}
	return $arr;
}

function FM_getDroop() {
	$flowers = array();
	$flowers = FM_getFlowers();

	$arr = array();
	$i = 0;

	foreach($flowers as $flower)
	{
		if($flower['state'] == 'droop')	{
			$arr[$i] = $flower;
			$i++;
		}
	}
	return $arr;
}

function FM_loadFlowershed() {
	$objs = array();
	$arr = 0;
	$objs = TB_loadObjects();

	foreach($objs as $obj)
	{
		if($obj['className'] == 'HarvestStorageBuilding' && $obj['itemName'] == 'flowershed') {
			if($obj['contents'] > 0) {
				$arr = $obj;
			}
		}
	}
	return $arr;
}

function FM_getGiftbox() {
	$flowers = array();
	$giftbox = array();
	$giftbox = TB_loadGiftBox();
	$units = array();
	$units = TB_loadUnits();
	foreach($giftbox as $gift => $num) {
		if(isset($units[GetNameByCode($gift)]['className'])) {
			if( $units[GetNameByCode($gift)]['className'] == 'FlowerDecoration' ) {
				$flowers[GetNameByCode($gift)] = $num;
			}
		}
	}
	return $flowers;
}

function FM_place($getFarm) {
	global $TB_settings;
	$shed = array();
	$shed = FM_loadFlowershed();
	$units = array();
	$units = TB_loadUnits();
	$farm = array();
	$farm = $getFarm;
	$itemName = '';
	$toPlace = 0;
	$position = array();

	if(is_array($TB_settings['FM_actionP']['place'])) {
		foreach($TB_settings['FM_actionP']['place'] as $itemName => $num) {
			$toPlace = $num;
			while($toPlace > 0) {
				$position = TB_findEmptySpotSection('', $farm, 1, 1, true);
				if($position == false) {
					AddLog2('can\'t find position in section -> will use whole farm');
					$position = TB_findEmptySpot($TB_settings['TB_getEmptyPositionUseRandom'], $farm,1,1);
				}
				if($position == false) {
					AddLog2('unable to place ' . $units[$itemName]['realname'] . ' - no free space');
					break;
				}
				if(FM_doPlace($itemName, $units[$itemName], $position['x'], $position['y'], $shed)) {
					$farm[$position['x']][$position['y']] = false;
				}
				$toPlace--;
			}
		}
	}
	unset($TB_settings['FM_actionP']['place']);
	return $farm;
}

function FM_doPlace($itemName, $unit, $x, $y, $shed) {
	global $TB_settings;
	AddLog2("Placing " . $unit['realname']);

	$amf                                                                    = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']               	= $unit['className'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                	= $itemName;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            	= $x;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            	= $y;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                   = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['state']                     = 'static';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                    = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   	= $shed['id'];
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                	= false;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] 	= false;


	$res = RequestAMF( $amf );
	if ( $res == 'OK' ) {
		AddLog2( '-> ' . $res );
		return true;
	} else {
		AddLog2( "ERROR - $res" );
		return false;
	}
}

//########################################################################
// storagemanager functions
//########################################################################

function SM_sell() {
	global $TB_settings;
	$units = array();
	$units = TB_loadUnits();
	$itemName = '';

	if(is_array($TB_settings['SM_action']['sell'])) {
		foreach($TB_settings['SM_action']['sell'] as $itemName => $num) {
			SM_doSell($itemName, $units[$itemName], $num);
		}
	}
	unset($TB_settings['SM_action']['sell']);
	save_array($TB_settings, ToolBox_settings);
}

function SM_doSell($itemName, $unit, $num) {
	global $TB_settings;
	AddLog2("Selling " . $unit['realname']);

	$i = 0;
	$left = $num;

	$amf = '';
	while($left > 0) {
		$amf = CreateMultAMFRequest($amf, $i, '', 'UserService.sellStoredItem');
		$amf->_bodys[0]->_value[1][$i]['params'][0]['name']			= $itemName;
		$amf->_bodys[0]->_value[1][$i]['params'][0]['rank']			= 0;
		$amf->_bodys[0]->_value[1][$i]['params'][0]['type']			= $unit['type'];
		$amf->_bodys[0]->_value[1][$i]['params'][0]['code']			= $unit['code'];
		$amf->_bodys[0]->_value[1][$i]['params'][0]['sellPrice']	= $unit['cost'] * (1 / 20);
		$amf->_bodys[0]->_value[1][$i]['params'][0]['cost']			= $unit['cost'];
		$amf->_bodys[0]->_value[1][$i]['params'][0]['localizedName']= $unit['realname'];

		$amf->_bodys[0]->_value[1][$i]['params'][1] = false;
		$amf->_bodys[0]->_value[1][$i]['params'][2] = '-2';
		$i++;
		$left--;

		if($i >= $TB_settings['TB_speedAction']) {
			$res = RequestAMF( $amf );
			if ( $res == 'OK' ) {
				AddLog2( '-> ' . $res . ' ' . $left . ' left');
				$amf = '';
				$i = 0;
			} else {
				AddLog2( "ERROR - $res" );
				return false;
			}
		}
	}

	if($i > 0) {
		$res = RequestAMF( $amf );
		if ( $res == 'OK' ) {
			AddLog2( '-> ' . $res );
			return true;
		} else {
			AddLog2( "ERROR - $res" );
			return false;
		}
	} else return true;
}

function SM_place($getFarm) {
	global $TB_settings;
	$units = array();
	$units = TB_loadUnits();
	$farm = array();
	$farm = $getFarm;
	$itemName = '';
	$sizeX = 0;
	$sizeY = 0;
	$toPlace = 0;
	$position = array();

	if(is_array($TB_settings['SM_action']['place'])) {
		foreach($TB_settings['SM_action']['place'] as $itemName => $num) {
			$toPlace = $num;
			while($toPlace > 0) {
				$sizeX = $units[$itemName]['sizeX'];
				$sizeY = $units[$itemName]['sizeY'];
				$position = TB_findEmptySpot(false, $farm, $sizeX, $sizeY);
				if($position == false) {
					AddLog2('unable to place ' . $units[$itemName]['realname'] . ' - no free space');
					break;
				}
				if(SM_doPlace($itemName, $units[$itemName], $position['x'], $position['y'])) {
					for ($x = $position['x']; $x <=  $position['x'] + $sizeX; $x++) {
						for ($y = $position['y']; $y <= $position['y'] + $sizeY; $y++) {
							$farm[$x][$y] = false;
						}
					}
				}
				$toPlace--;
			}
		}
	}
	unset($TB_settings['SM_action']['place']);
	return $farm;
}

function SM_doPlace($itemName, $unit, $x, $y) {
	global $TB_settings;
	AddLog2("Placing " . $unit['realname']);

	$amf                                                                    = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']               	= $unit['className'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                	= $itemName;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            	= $x;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            	= $y;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            	= 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                   = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['state']                     = 'static';
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                    = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   	= -1;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                	= false;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] 	= true;


	$res = RequestAMF( $amf );
	if ( $res == 'OK' ) {
		AddLog2( '-> ' . $res );
		return true;
	} else {
		AddLog2( "ERROR - $res" );
		return false;
	}
}

//########################################################################
// toolbox functions
//########################################################################

function TB_loadGiftBox() {
	$giftbox = array();
	$giftbox = @unserialize(fBGetDataStore('ingiftbox'));
	foreach($giftbox as $giftCode => $num) {
		$giftbox[$giftCode] = (int) $num;
	}
	return $giftbox;
}

function TB_loadStorage() {
	$storage = array();
	$storage2 = array();
	$storage = @unserialize(fBGetDataStore('instorage'));
	foreach($storage as $code => $num) {
		if($num > 0) {
			$storage2[GetNameByCode($code)] = $num;
		}
	}
	return $storage2;
}

function TB_loadShed() {
	return @unserialize(file_get_contents($_SESSION['base_path'] . F('inshed.txt')));
}

function TB_renewShed() {
	$objs = array();
	$shed = array();
	$shed2 = array();
	$objs = TB_loadObjects();
	foreach($objs as $obj)
	{
		if($obj['className'] == 'HarvestStorageBuilding' && $obj['itemName'] == 'flowershed') {
			if($obj['contents'] > 0) {
				$shed = $obj;
			}
		}
	}

	if(isset($shed['contents'])) {
		foreach($shed['contents'] as $content)
		{
			$shed2[$content['itemCode']] = $content['numItem'];
		}
	}
	save_botarray ($shed2, $_SESSION['base_path'] . F('inshed.txt'));
}

function TB_numberGiftBox() {
	$re = 0;
	$giftbox = array();
	$giftbox = TB_loadGiftBox();
	foreach($giftbox as $gift => $num)
	{
		if($num > 0) {
			$re += $num;
		}
	}
	return $re;
}

function TB_loadObjects() {
	return @unserialize(fBGetDataStore('objects'));
}

function TB_getObject($getID, $getObjects = '') {
	$objects = array();
	if($getObjects <> '') $objects = $getObjects;
	else $objects = $getObjects;
	foreach($objects as $object) {
		if($object['id'] == $getID) return $object;
	}
	return 0;
}

function TB_loadUnits() {
	return Units_GetAll(true);
}

function TB_loadSettings() {
	global $TB_settings;
	global $AM_settings;
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $flashRevision) = explode(';', fBGetDataStore('playerinfo'));
	$need_save = false;
	$TB_settings = load_array(ToolBox_settings);
	if( $TB_settings['TB_version'] != ToolBox_version ) {
		$TB_settings['TB_version'] = ToolBox_version;
		$TB_settings['TB_speedAction'] = 8;
		$TB_settings['TB_giftboxLimit'] = 200;
		$TB_settings['TB_getEmptyPositionUseRandom'] = false;
		$TB_settings['TB_sectionsUseSections'] = false;
		$TB_settings['TB_sectionsUseGiftBox'] = false;
		$TB_settings['TB_sectionsUseGiftBoxDecoration'] = false;
		$need_save = true;
	}
	if( $TB_settings['FM_version'] != FlowerManager_version) {
		$TB_settings['FM_version'] = FlowerManager_version;
		$TB_settings['FM_action'] = 'nothing';
		$TB_settings['FM_replaceSource'] = 'both';
		$TB_settings['FM_replaceSame'] = true;
		$TB_settings['FM_actionP'] = array(); //place
		$need_save = true;
	}
	if( $TB_settings['AM_version'] != AnimalManager_version) {
		$TB_settings['AM_version'] = AnimalManager_version;
		$TB_settings['AM_move'] = 'nothing';
		$TB_settings['AM_direction'] = -1;
		$TB_settings['AM_saveSettings'] = false;
		$TB_settings['AM_ItemName'] = '';
		foreach($AM_settings as $className => $animals) {
			$TB_settings['AM_farmGold_'.$className] = 0;
		}
		$TB_settings['AM_farmGoldRestore'] = true;
		$need_save = true;
	}
	if( $TB_settings['AM_flashRevision'] <> $flashRevision) {
		$TB_settings['AM_flashRevision'] = $flashRevision;
		foreach($AM_settings as $className => $animals) {
			$buildings = AM_loadBuildingObject($className, false);
			foreach($buildings as $building) {
				$buildingID = $building['id'];
				foreach($animals as $animal) {
					if(!isset($TB_settings[$buildingID]['AM_move_' . $animal])) $TB_settings[$buildingID]['AM_move_' . $animal] = 0;
				}
				if(!isset($TB_settings[$buildingID]['AM_moveTo'])) $TB_settings[$buildingID]['AM_moveTo'] = 'nothing';
			}
		}
		$need_save = true;
	}
	if( $TB_settings['MG_version'] <> MysteryGift_version) {
		$TB_settings['MG_version'] = MysteryGift_version;
		//gifts
		$TB_settings['MG_openGiftsFarm'] = 0;
		$TB_settings['MG_openGiftsFarmAll'] = false;
		$TB_settings['MG_openGiftsGiftbox'] = 0;
		$TB_settings['MG_openGiftsGiftboxAll'] = false;
		$TB_settings['MG_openGiftsGiftboxJustPlace'] = false;
		//eggs
		$TB_settings['MG_openEggsFarm'] = 0;
		$TB_settings['MG_openEggsFarmAll'] = false;
		$TB_settings['MG_openEggsGiftbox'] = 0;
		$TB_settings['MG_openEggsGiftboxAll'] = false;
		$TB_settings['MG_openEggsGiftboxJustPlace'] = false;
		$need_save = true;
	}
	if( $TB_settings['SM_version'] <> StorageManager_version) {
		$TB_settings['SM_version'] = StorageManager_version;
		$TB_settings['SM_action'] = array();
		$need_save = true;
	}
	if($need_save) save_array( $TB_settings, ToolBox_settings );
}

//AM
function TB_loadAnimalMoverSettings() {
	global $AM_settings;
	$AM_settings = @unserialize(file_get_contents($_SESSION['base_path'] . AnimalMover_settings));
	if(!is_array($AM_settings)) $AM_settings = array();
}

function AM_createAnimalMoverData() {
	global $AM_settings;
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale, $tileset, $wither, $xp, $energy, $flashRevision) = explode(';', fBGetDataStore('playerinfo'));
	$file = $_SESSION['base_path'] . 'farmville-xml\\' . $flashRevision . '_items.xml';
	$useXML = true;
	// the credits of this part go to 95% to Christiaan for doing all the work
	// and 5% to me for modifying it
	if(!$useXML) {
		$xml = new DOMDocument();
		$xml->load($file);

		$items = $xml->getElementsByTagName('item');
		foreach($items as $item) {
			$type = $item->getAttribute('type');
			$className = $item->getAttribute('className');
			$subtype = $item->getAttribute('subtype');
			if($type == 'building' && $subtype == 'animal_pens') {
				$storageTypes = $item->getElementsByTagName('storageType');
				foreach($storageTypes as $storageType) {
					$itemNames = $storageType->getElementsByTagName('itemName');
					foreach($itemNames as $itemName) {
						$need = $itemName->getAttribute('need');
						$limit = $itemName->getAttribute('limit');
						if($need == '')	{
							$settings[$className][$itemName->nodeValue] = $itemName->nodeValue;
						}
					}
				}
			}
		}
		// end
		//this function should still work (20.04.2010) but it needs to be edited each time Z**** adds a building or new animals with differne names
	} else {
		$units = array();
		$units = TB_loadUnits();
		//animal to ignore
		$ausnahmen = array();
		$ausnahmen[] = 'chicken_cheerblue';
		$ausnahmen[] = 'chicken_cheerorange';
		$ausnahmen[] = 'calf_fanpage';

		$settings['version'] = $flashRevision;
		foreach($units as $itemName => $unit) {
			$tmp = explode('_', $itemName);
			$vorne = $tmp[0];
			if(!in_array($itemName, $ausnahmen)) {
				//chicken
				if($vorne == 'chicken' && $unit['type'] == 'animal') {
					$settings['ChickenCoopBuilding'][$itemName] = $itemName;
				}
				//horse
				if((($vorne == 'horse' || $vorne == 'pony') || $vorne == 'stallion') && $unit['type'] == 'animal') {
					$settings['HorseStableBuilding'][$itemName] = $itemName;
				}
				//cow
				if(($vorne == 'cow' || $vorne == 'bull') && $unit['type'] == 'animal') {
					$settings['DairyFarmBuilding'][$itemName] = $itemName;
				}
				//pig
				if($vorne == 'pig' && $unit['type'] == 'animal') {
					$settings['PigpenBuilding'][$itemName] = $itemName;
				}
				//calf & foal
				if(($vorne == 'calf' || $vorne == 'foal') && $unit['type'] == 'animal') {
					$settings['NurseryBuilding'][$itemName] = $itemName;
				}
			//turkey
        if(@$unit['keyword'] == 'turkey' && @$unit['type'] == 'animal') {$settings['TurkeyRoostBuilding'][$itemName] = $itemName;}

				//tree by Davdomlan
				if($unit['type'] == 'tree' && $unit['keyword'] != 'noOrchard') {
					$settings['OrchardBuilding'][$itemName] = $itemName;
				}
			}
		}
	//adding other animals
        // stable
		$settings['HorseStableBuilding']['clydesdale'] = 'clydesdale';
		$settings['HorseStableBuilding']['dancinghorse'] = 'dancinghorse';
        $settings['HorseStableBuilding']['buckskin'] = 'buckskin';
        $settings['HorseStableBuilding']['morgan_female'] = 'morgan_female';
        $settings['HorseStableBuilding']['clydesdale_cream'] = 'clydesdale_cream';
        $settings['HorseStableBuilding']['breton'] = 'breton';
        $settings['HorseStableBuilding']['appaloosa'] = 'appaloosa';
        $settings['HorseStableBuilding']['appaloosa_white'] = 'appaloosa_white';
        $settings['HorseStableBuilding']['mustang'] = 'mustang';
        
        // pigpen
		$settings['PigpenBuilding']['kaluapig'] = 'kaluapig';
        $settings['PigpenBuilding']['hulapig'] = 'hulapig';
		
	}
	
	foreach($settings as $className => $animals) {
		ksort($settings[$className]);
	}

	$save_str = serialize($settings);
	$f = fopen($_SESSION['base_path'] . AnimalMover_settings, "w+");
	fputs($f, $save_str, strlen($save_str));
	fclose($f);
}

//tmp stats
function TB_prepareTempStats($getItemName = '') {
	global $TB_settings;
	if(isset($TB_settings['AM_ItemName']))	$itemName = $TB_settings['AM_ItemName'];
	elseif($getItemName <> '') 				$itemName = $getItemName;
	else 									$itemName = '';
	$settings = array();

	//MysteryGifts
	$settings['countGiftsGiftbox'] = MG_getGifts('giftbox');
	$tmp = MG_getEggs('giftbox');
	$settings['countEggsGiftbox'] = $tmp['count'];

	$settings['countGiftsFarm'] = count(MG_getGifts());
	$settings['countEggsFarm'] = count(MG_getEggs());

	$settings['countGiftsInGiftBox'] = TB_numberGiftBox();
	if($TB_settings['TB_giftboxLimit'] <= 0) $TB_settings['TB_giftboxLimit'] = 200;
	$settings['maxGiftsToAdd'] = $TB_settings['TB_giftboxLimit'] - $settings['countGiftsInGiftBox'];

	//AnimalsMover
	$settings['itemName'] = $itemName;

	$settings['allAnimals'] = count(AM_getAnimals($itemName));
	$settings['movingAnimals'] = count(AM_getMovingAnimals($itemName));
	$settings['standingAnimals'] = $settings['allAnimals'] - $settings['movingAnimals'];

	$settings['directionAnimals0'] = count(AM_getDirectionAnimals(0,$itemName));
	$settings['directionAnimals1'] = count(AM_getDirectionAnimals(1,$itemName));
	$settings['directionAnimals2'] = count(AM_getDirectionAnimals(2,$itemName));
	$settings['directionAnimals3'] = count(AM_getDirectionAnimals(3,$itemName));

	save_array( $settings, ToolBox_temp_stats );
}

function TB_loadTempStats() {
	return load_array(ToolBox_temp_stats);
}

//farm
function TB_findEmptySpot($random = false, $getFarm = '', $getSizeX = 0, $getSizeY = 0) {
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname) = explode(';', fBGetDataStore('playerinfo'));

	$xm = $sizeX - 1;
	$ym = $sizeY - 1;
	$x = 0;
	$y = 0;

	$re = array();
	$farm = array();
	if(($getFarm == '' || !is_array($getFarm)) || @count($getFarm) < 10) $farm = TB_buildFarmArray();
	else $farm = $getFarm;
	if($getSizeX > 0 && $getSizeY > 0 ) {
		$random = false;
		$getSizeX--;
		$getSizeY--;
	}

	if($random) {
		$i = 0;
		$max = 100;
		while($i <= $max) {
			$x = rand(0, $xm);
			$y = rand(0, $ym);
			if($farm[$x][$y]) {
				$re['x'] = $x;
				$re['y'] = $y;
				return $re;
			}
		}
	} else {
		$re = TB_findEmptySpotFunction($farm, $x, $y, $xm, $ym, $xm, $ym, $getSizeX, $getSizeY);
		if(is_array($re)) return $re;
		else false;
	}

	return false;
}

function TB_findEmptySpotSection($itemName, $getFarm = '', $getSizeX = 0, $getSizeY = 0, $isDeco = false) {
	global $TB_settings;
	global $TB_settings_place;
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname) = explode(';', fBGetDataStore('playerinfo'));

	$xm = $sizeX - 1;
	$ym = $sizeY - 1;

	$re = array();
	$farm = array();
	if(($getFarm == '' || !is_array($getFarm)) || @count($getFarm) < 10) $farm = TB_buildFarmArray();
	else $farm = $getFarm;
	if($getSizeX > 0 && $getSizeY > 0 ) {
		$getSizeX--;
		$getSizeY--;
	}

	//get section
	$x1 = 0;
	$y1 = 0;
	$x2 = 0;
	$y2 = 0;
	$foundSection = false;

	//starts with sections
	if($TB_settings['TB_sectionsUseSections']) {
		if(isset($TB_settings_place[$itemName])) {
			$x1 = $TB_settings_place[$itemName]['x1'];
			$y1 = $TB_settings_place[$itemName]['y1'];
			$x2 = $TB_settings_place[$itemName]['x2'];
			$y2 = $TB_settings_place[$itemName]['y2'];
			$foundSection = true;
		}
	}

	if($foundSection) {
		$re = TB_findEmptySpotFunction($farm, $x1, $y1, $x2, $y2, $xm, $ym, $getSizeX, $getSizeY);
		if(is_array($re)) return $re;
	}

	//GiftBox
	if($isDeco) {
		if($TB_settings['TB_sectionsUseGiftBoxDecoration']) {
			$x1 = $TB_settings_place['decoration']['x1'];
			$y1 = $TB_settings_place['decoration']['y1'];
			$x2 = $TB_settings_place['decoration']['x2'];
			$y2 = $TB_settings_place['decoration']['y2'];
			$foundSection = true;
		}
	} else {
		if($TB_settings['TB_sectionsUseGiftBox'] && $foundSection == false) {
			$x1 = $TB_settings_place['GiftBox']['x1'];
			$y1 = $TB_settings_place['GiftBox']['y1'];
			$x2 = $TB_settings_place['GiftBox']['x2'];
			$y2 = $TB_settings_place['GiftBox']['y2'];
			$foundSection = true;
		}
	}

	if($foundSection) {
		$re = TB_findEmptySpotFunction($farm, $x1, $y1, $x2, $y2, $xm, $ym, $getSizeX, $getSizeY);
		if(is_array($re)) return $re;
	}
	//still no section?
	if($foundSection == false) {
		//AddLog2('can\'t find position in section -> will use whole farm');
		$x1 = 0;
		$y1 = 0;
		$x2 = $xm;
		$y2 = $ym;
	}

	$re = TB_findEmptySpotFunction($farm, $x1, $y1, $x2, $y2, $xm, $ym, $getSizeX, $getSizeY);
	if(is_array($re)) return $re;

	return false;
}

function TB_findEmptySpotFunction($farm, $x1, $y1, $x2, $y2, $xm, $ym, $getSizeX = 0, $getSizeY = 0) {
	if($x2 > $xm) $x2 = $xm;
	if($y2 > $ym) $y2 = $ym;
	if($x1 > $x2) $x1 = $x2;
	if($y1 > $y2) $y1 = $y2;

	for ($xf = $x1; $xf <= $x2; $xf++) {
		for ($yf = $y1; $yf <= $y2; $yf++) {
			$isFree = true;
			for ($xf2 = $xf; $xf2 <=  $xf + $getSizeX; $xf2++) {
				for ($yf2 = $yf; $yf2 <= $yf + $getSizeY; $yf2++) {
					if($xf2 <= $x2 && $yf2 <= $y2) {
						if(!$farm[$xf2][$yf2]) {
							$isFree = false;
						}
					} else {
						$isFree = false;
					}
				}
			}
			if($isFree) {
				$re['x'] = $xf;
				$re['y'] = $yf;
				return $re;
			}
		}
	}
	return false;
}

function TB_buildFarmArray() {
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname) = explode(';', fBGetDataStore('playerinfo'));

	$xm = $sizeX - 1;
	$ym = $sizeY - 1;
	$x = 0;
	$y = 0;

	$farm = array();
	$objects = array();
	$objects = TB_loadObjects();
	$units = array();
	$units = TB_loadUnits();

	// ########## ini farm array
	for ($x = 0; $x <= $xm; $x++) {
		for ($y = 0; $y <= $ym; $y++) {
			$farm[$x][$y] = true;
		}
	}
	// ########## fill plots
	foreach($objects as $object) {
		//objects position
		$objX = $object['position']['x'];
		$objY = $object['position']['y'];
		if($object['className'] == 'Plot') {
			$objSizeX = 3; // = 4 - 1;
			$objSizeY = 3;
		} else {
			$unit = $units[$object['itemName']];
			//objects size
			if( isset($unit['sizeX'])) {
				$objSizeX = $unit['sizeX'] - 1;
				$objSizeY = $unit['sizeY'] - 1;
			} else {
				$objSizeX = 0; // = 1 - 1;
				$objSizeY = 0;
			}
		}
		//check rotation for fences
		if(isset($object['state'])) {
			if( $object['state'] == 'vertical') {
				$tmp = $objSizeX;
				$objSizeX = $objSizeY;
				$objSizeY = $tmp;
			}
		}
		for ($x = $objX; $x <= $objX + $objSizeX; $x++) {
			for ($y = $objY; $y <= $objY + $objSizeY; $y++) {
				$farm[$x][$y] = false;
			}
		}
	}
	return $farm;
}

function TB_checkSpot($x, $y) {
	$units = array();
	$units = TB_loadUnits();
	$objects = array();
	$objects = TB_loadObjects();

	foreach($objects as $object) {
		if( $object['position']['x'] == $x && $object['position']['y'] == $y ) return false;
		//if( isset($units[$object['itemName']])) {
		$unit = $units[$object['itemName']];
		$xMin = $object['position']['x'];
		$yMin = $object['position']['y'];
		if( isset($unit['sizeX'])) {
			$xMax = $xMin + $unit['sizeX'] - 1;
			$yMax = $yMin + $unit['sizeY'] - 1;
		} else {
			$xMax = $xMin;
			$yMax = $yMin;
		}
		if(isset($object['state'])) {
			if( $object['state'] == 'vertical') {
				$tmp = $objSizeX;
				$objSizeX = $objSizeY;
				$objSizeY = $tmp;
			}
		}
		if( $xMin <= $x && $x <= $xMax && $yMin <= $y && $y <= $yMax ) return false;
		//} else {
		//	echo '<small><pre>';
		//	echo print_r($object);
		//	echo '</pre></small>';
		//}
	}
	return true;
}

//sections
function TB_importSections() {
	global $TB_settings_place;
	global $TB_settings;
	unset($TB_settings_place);
	//sections
	if(file_exists(Section_path . $_SESSION['userId'] . '_sections.txt')) {
		$sectionsSetting = @unserialize(file_get_contents(Section_path . $_SESSION['userId'] . '_sections.txt'));
		$TB_settings_place['useSections'] = true;
		foreach($sectionsSetting as $section) {
			if($section['type'] == 'anim' || $section['type'] == 'deco') {
				$itemNames = explode('|', $section[$section['type']]);
				foreach($itemNames as $itemName) {
					$TB_settings_place[$itemName]['x1'] = $section['bot_x'];
					$TB_settings_place[$itemName]['y1'] = $section['bot_y'];
					$TB_settings_place[$itemName]['x2'] = $section['top_x'];
					$TB_settings_place[$itemName]['y2'] = $section['top_y'];
				}
			}
		}
	} else {
		$TB_settings_place['useSections'] = false;
	}

	//giftbox
	if(file_exists(GiftBox_path . $_SESSION['userId'] . '_GB_settings.txt')) {
		$giftboxSettings = @unserialize(file_get_contents(GiftBox_path . $_SESSION['userId'] . '_GB_settings.txt'));
		$TB_settings_place['useGiftBox'] = true;

		$TB_settings_place['GiftBox']['x1'] = $giftboxSettings['AnimalX1'];
		$TB_settings_place['GiftBox']['y1'] = $giftboxSettings['AnimalY1'];
		$TB_settings_place['GiftBox']['x2'] = $giftboxSettings['AnimalX2'] - 1;
		$TB_settings_place['GiftBox']['y2'] = $giftboxSettings['AnimalY2'] - 1;

		$TB_settings_place['decoration']['x1'] = $giftboxSettings['DecorationX1'];
		$TB_settings_place['decoration']['y1'] = $giftboxSettings['DecorationY1'];
		$TB_settings_place['decoration']['x2'] = $giftboxSettings['DecorationX2'] - 1;
		$TB_settings_place['decoration']['y2'] = $giftboxSettings['DecorationY2'] - 1;
	} else {
		$TB_settings_place['useGiftBox'] = false;
	}
	save_array( $TB_settings_place, ToolBox_sections );

	//reset to avoid errors
	$TB_settings['TB_sectionsUseSections'] = false;
	$TB_settings['TB_sectionsUseGiftBox'] = false;
	$TB_settings['TB_sectionsUseGiftBoxDecoration'] = false;
}

function TB_loadSections() {
	global $TB_settings_place;
	$TB_settings_place = load_array(ToolBox_sections);
}

function TB_GetCodeByName($name) {
	$units = array();
	$units = TB_loadUnits();
	foreach($units as $unit) {
		if(isset($unit['name'])) {
			if($unit['name'] == $name) return $unit['code'];
		}
	}
	return false;
}

function TB_sendRequest($amf) {
	//start parser
	$serializer = new AMFSerializer();
	$result = $serializer->serialize($amf); // serialize the data
	$answer = Request('', $result);

	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer

	if (@$amf2->_bodys[0]->_value['errorType'] != 0) {
		if ($amf2->_bodys[0]->_value['errorData'] == "There is a new version of the farm game released") {
			AddLog2("New version of the game released");
			echo "\n*****\nGame version out of date\n*****\n";
			echo "\n Restarting Bot in 15 seconds. \n";
			sleep(15);
			touch('need_restart.txt'); //creating this file will cause the game to restart
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token value failed") {
			AddLog2("Error: token value failed");
			AddLog2("You opened the game in another browser");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token value failed\nThis error is caused by opening the game in another browser\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			sleep(15);
			touch('need_restart.txt');
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token too old") {
			AddLog2("Error: token too old");
			AddLog2("The session expired");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token too old\nThe session has expired\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			sleep(15);
			touch('need_restart.txt'); //creating this file will cause the game to restart
		}
		else {
			echo "\n*****\nError: \n" .$amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData']."\n";
			$res = "Error: " . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'];
		}
	}

	else if (!isset($amf2->_bodys[0]->_value['data'][0])) { echo "\n*****\nError:\n BAD AMF REPLY - Possible Server problem or farm badly out of sync\n*****\n"; $res ="BAD AMF REPLY (OOS?)"; }

	else if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) {
		$res = 'OK';
	}
	else {
		if (isset($amf2->_bodys[0]->_value['data'][0])) {
			$res = $amf2->_bodys[0]->_value['data'][0]['errorType'] . " " . $amf2->_bodys[0]->_value['data'][0]['errorData'];
		}
	}
	//end parser
	$return['amf2'] = $amf2;
	$return['res'] = $res;
	return $return;
}

//########################################################################
// form functions
//########################################################################

function FM_prepareFormData() {
	$flowers = array();
	$flowers = FM_getFlowers();
	$shed = TB_loadShed();
	$giftbox = FM_getGiftbox();

	$arr = array();
	$arr['live'] = array();
	$arr['live_c'] = 0;
	$arr['droop'] = array();
	$arr['droop_c'] = 0;
	$arr['shed'] = array();
	$arr['shed_c'] = 0;
	$arr['giftbox'] = array();
	$arr['giftbox_c'] = 0;

	foreach($flowers as $flower)
	{
		if($flower['state'] == 'live')	{
			if(array_key_exists( $flower['itemName'], $arr['live'] )) {
				$arr['live'][$flower['itemName']]++;
			} else {
				$arr['live'][$flower['itemName']] = 1;
			}
			$arr['live_c']++;
		} else  {
			if(array_key_exists( $flower['itemName'], $arr['droop'] )) {
				$arr['droop'][$flower['itemName']]++;
			} else {
				$arr['droop'][$flower['itemName']] = 1;
			}
			$arr['droop_c']++;
		}
	}

	if(count($shed) > 0) {
		foreach($shed as $flowerCode => $num)
		{
			if($num > 0) {
				$arr['shed'][GetNameByCode($flowerCode)] = $num;
				$arr['shed_c'] += $num;
			}
		}
	}

	if(count($giftbox) > 0) {
		foreach($giftbox as $flower => $num)
		{
			if($num > 0) {
				$arr['giftbox'][$flower] = $num;
				$arr['giftbox_c'] += $num;
			}
		}
	}
	return $arr;
}

function FM_buildTable() {
	global $TB_settings;
	$shed = array();
	$shed = FM_loadFlowershed();
	$c = 1;
	@ksort($shed);
	if (!empty($shed['contents'])){
		foreach($shed['contents'] as $arr) {
			if($arr['numItem'] == 0) continue;
			$itemName = GetNameByCode($arr['itemCode']);
			echo '<tr>';
			//name
			echo '<td>';
			echo '&raquo; ' . GetNameByItem($itemName);
			echo '</td>';

			// anzahl
			echo '<td align="center">';
			echo $arr['numItem'];
			echo '</td>';

			// FM place
			echo '<td>';
			if(isset($TB_settings['FM_actionP']['place'][$itemName])) {
				$count = $TB_settings['FM_actionP']['place'][$itemName];
			} else $count = 0;
			echo "<input type='text' name='place_" . $itemName . "' value='" . $count . "' size='2'>";
			echo '</td>';
			echo '</tr>';
			if($c % 5 == 0) {
				echo '<tr>';
				echo '<td>&nbsp;</td>';
				echo '</tr>';
			}
			$c++;
		}
	}
}

function TB_buildTable($objs) {
	foreach($objs as $i => $num) {
		echo '<tr>';
		echo '<td>';
		echo '&raquo; ' . GetNameByItem($i);
		echo '</td>';
		echo '<td>';
		echo $num;
		echo '</td>';
		echo '</tr>';
	}
}

function AM_buildAnimalMoverTable($name) {
	global $TB_settings;
	global $AM_settings;
	if ($name == 'version') return false;
	if(!is_array($AM_settings[$name])) {
		echo 'please let the bot run one cycle';
		return false;
	}
	$output = array();
	$buildings = array();
	$building = array();
	$animalsInBuilding = array();
	$animals = array();
	$cBuilding = array();
	$cFarm = 0;
	$cShow = 0;

	//load infos
	$buildings = AM_loadBuildingObject($name, false);
	if(count($buildings) == 0) {
		echo 'You don\'t have that building ('.$name.')!<br />';
		return false;
	}
	$animals = AM_loadAnimalOnFarmCount($name);
	$formName = $name;

	//animals on farm
	foreach($animals as $animal => $num) {
		$output[$animal]['farm'] = $num;
		$cFarm	+= $num;
	}

	foreach($AM_settings[$name] as $animal) {
		if(!isset($output[$animal]['farm'])) $output[$animal]['farm'] = 0;
	}
	//animals in building(s)
	foreach($buildings as $buildingID => $building) {
		$animalsInBuilding[$buildingID] = AM_animalInBuilding($building);
		$maxAnimalsInBuilding[$buildingID] = AM_getMaxAnimalsInBuilding($building);
		$cBuilding[$buildingID] = 0;

		foreach($AM_settings[$name] as $animal) {
			$output[$animal][$formName][$buildingID] = 0;
		}

		foreach($animalsInBuilding[$buildingID]  as $animal => $num) {
			$output[$animal][$formName][$buildingID] = $num;
			$cBuilding[$buildingID] += $num;
		}
	}
	//show what?
	$tmp = array();
	foreach($AM_settings[$name] as $animal) {
		$output[$animal]['show'] = false;
		if($output[$animal]['farm'] > 0) {
			$output[$animal]['show'] = true;
			if(!in_array($animal,$tmp)) {
				$cShow++;
				$tmp[] = $animal;
			}
		} else {
			foreach($output[$animal][$formName] as $buildingID => $num) {
				if($num > 0) {
					$output[$animal]['show'] = true;
					if(!in_array($animal,$tmp)) {
						$cShow++;
						$tmp[] = $animal;
					}
				}
			}
		}
	}
	//show
	echo 'You have ' . count($buildings) . ' ' . $name . '(s) &nbsp; &nbsp;';
	echo '<input type="button" value="Save changes >>" onclick="this.form.submit();" >';
	echo '<br>';
	echo '<table width="100%" class="tableWhite">';

	//on farm
	//names
	echo '<tr>';
	echo '<td rowspan="2">';
	echo 'animals on<br> farm: ' . $cFarm;
	echo '</td>';
	foreach($AM_settings[$name] as $animal) {
		if($output[$animal]['show']) {
			echo '<td>';
			if($cShow > 10) echo '<small>';
			echo '<small>' . GetNameByItem($animal) .  '</small> ';
			if($cShow > 10) echo '</small>';
			echo '</td>';
		}
	}
	//on farm
	//action
	echo '<td rowspan="2" valign="bottom">';
	echo 'move to:';
	echo '</td>';
	echo '</tr>';
	//on farm
	//numbers
	echo '<tr>';
	foreach($AM_settings[$name] as $animal) {
		if($output[$animal]['show']) {
			$class = ($output[$animal]['farm'] == 0) ? 'zero' : 'not_zero';
			echo '<td class=' . $class . '>';
			echo $output[$animal]['farm'];
			echo '</td>';
		}
	}
	foreach($buildings as $buildingID => $building) {
		echo '<tr>';
		//in building
		//numbers
		if($cBuilding[$buildingID] == 0) $class = 'zero';
		elseif($cBuilding[$buildingID] == $maxAnimalsInBuilding[$buildingID]) $class = 'full';
		else $class = 'not_zero';
		echo '<td valign="middle" rowspan="2" class=' . $class . ' width="10%">';
		//echo 'in ' . $name . ': <br>';
		echo 'ID:' . $buildingID .'<br>';
		echo '( '. $cBuilding[$buildingID] . ' / ' . $maxAnimalsInBuilding[$buildingID] . ') <br>';
		echo '</td>';
			
		foreach($AM_settings[$name] as $animal) {
			if($output[$animal]['show']) {
				$class = ($output[$animal][$formName][$buildingID] == 0) ? 'zero' : 'not_zero';
				echo '<td class=' . $class . '>';
				echo $output[$animal][$formName][$buildingID];
				echo '</td>';
			}
		}
		//in building
		//action
		echo '<td rowspan="2" width="11%" class="actionBox">';
		$str = "<input type='radio' name='" . $buildingID . "_moveTo' value='nothing'";
		if($TB_settings[$buildingID]['AM_moveTo'] == 'nothing') $str = $str . ' checked';
		echo $str . '> nothing<br>';
		$str = "<input type='radio' name='" . $buildingID . "_moveTo' value='" . $name ."'";
		if($TB_settings[$buildingID]['AM_moveTo'] == $name) $str = $str . ' checked';
		echo $str . '> building<br>';
		$str = "<input type='radio' name='" . $buildingID . "_moveTo' value='farm'";
		if($TB_settings[$buildingID]['AM_moveTo'] == 'farm') $str = $str . ' checked';
		echo $str . "> farm";
		echo '</td>';
			
		echo '</tr>';
		echo '<tr>';
		//in building
		//move
		foreach($AM_settings[$name] as $animal) {
			if($output[$animal]['show']) {
				$name2 = 'move_' . $buildingID . '_' . $animal;
				$state = $TB_settings[$buildingID]['AM_move_' . $animal];
				$class = ($output[$animal][$formName][$buildingID] == 0 && $output[$animal]['farm'] == 0) ? 'zero' : 'not_zero';
				echo '<td class=' . $class . '>';
				if($cShow > 10) echo '<small>';
				echo 'move<br><input type="text" name=' . $name2 . ' size="1" value=' . $state . '>';
				if($cShow > 10) echo '</small>';
				echo '</td>';
			}
		}
		echo '</tr>';
	}
	echo '</tr>';
	echo '</table>';
}

function AM_buildDropDown() {
	global $TB_settings;
	$animals = array();
	$animals = AM_getAnimals();
	$tmp = array();
	$tmp[''] = '';

	foreach($animals as $animal) {
		$tmp[$animal['itemName']] = $animal['itemName'];
	}

	ksort($tmp);
	echo '<select name="ItemName" size="1" onchange="this.form.submit();">';
	foreach($tmp as $animal) {
		if($TB_settings['AM_ItemName'] == $animal) echo '<option selected>' . $animal . '</option>';
		else echo '<option>' . $animal . '</option>';
	}
	echo '</select>';
}

function SM_buildTable() {
	global $TB_settings;
	$storage = array();
	$storage = TB_loadStorage();
	$units = array();
	$units = TB_loadUnits();
	$c = 1;
	ksort($storage);

	foreach($storage as $i => $num) {
		echo '<tr>';
		//name
		echo '<td>';
		echo '&raquo; ' . GetNameByItem($i);
		echo '</td>';
		// size
		echo '<td>';
		echo 'X: ' . $units[$i]['sizeX'] . ' - Y: ' . $units[$i]['sizeY'];
		echo '</td>';

		// anzahl
		echo '<td align="center">';
		echo $num;
		echo '</td>';
			
		// SM sell
		echo '<td>';
		if(isset($TB_settings['SM_action']['sell'][$i])) {
			$count = $TB_settings['SM_action']['sell'][$i];
		} else $count = 0;
		echo "<input type='text' name='sell_" . $i . "' value='" . $count . "' size='2'>";
		echo '</td>';
			
		// SM place
		echo '<td>';
		if(isset($TB_settings['SM_action']['place'][$i])) {
			$count = $TB_settings['SM_action']['place'][$i];
		} else $count = 0;
		echo "<input type='text' name='place_" . $i . "' value='" . $count . "' size='2'>";
		echo '</td>';
		echo '</tr>';
		if($c % 5 == 0) {
			echo '<tr>';
			echo '<td>&nbsp;</td>';
			echo '</tr>';
		}
		$c++;
	}
}

function SM_showFarmStats() {
	list($level, $gold, $cash, $sizeX, $sizeY, $firstname) = explode(';', fBGetDataStore('playerinfo'));
	$xm = $sizeX - 1;
	$ym = $sizeY - 1;

	$farm = array();
	$farm = TB_buildFarmArray();
	$cPlots = 0;
	$cFree = 0;

	for ($x = 0; $x <= $xm; $x++) {
		for ($y = 0; $y <= $ym; $y++) {
			if($farm[$x][$y]) $cFree++;
			$cPlots++;
		}
	}
	echo '<table style="border:none" width="100%">';
	echo '<tr>';
	echo '<td colspan="4">';
	echo 'Your farm: ' . $sizeX . ' x ' . $sizeY . '  -  ' . round($cFree / $cPlots * 100) . '% is free';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

function TB_showSections() {
	global $TB_settings;
	global $TB_settings_place;
	if(isset($TB_settings_place['useSections']) && isset($TB_settings_place['useGiftBox'])) {
		$ignore[] = 'useGiftBox';
		$ignore[] = 'useSections';
		$ignore[] = 'GiftBox';
		$ignore[] = 'decoration';
		echo 'Click here to update settings. ';
		echo '<i><a href="'.ToolBox_URL.'?show=TB&importSections=true" class="headerLinks">update</a></i>';
		echo '<table width="100%">';
		echo '<tr>';
		//giftbox
		if($TB_settings_place['useGiftBox'] == true) {
			echo '<td width="25%">';
			echo 'found GiftBox\'s settings';
			echo '</td>';
			echo '<td>';
			$checked = $TB_settings['TB_sectionsUseGiftBox'] ? 'checked' : '';
			echo "<input type='checkbox' name='sectionsUseGiftBox' onclick='this.form.submit();' value=true ".$checked.">";
			echo 'use this section to place <i>animals</i>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>';
			echo '&nbsp;';
			echo '</td>';
			echo '<td>';
			$checked = $TB_settings['TB_sectionsUseGiftBoxDecoration'] ? 'checked' : '';
			echo "<input type='checkbox' name='sectionsUseGiftBoxDecoration' onclick='this.form.submit();' value=true ".$checked.">";
			echo 'use this section to place <i>flowers</i>';
			echo '</td>';
		} else {
			$TB_settings['TB_sectionsUseGiftBox'] = false;
			$TB_settings['TB_sectionsUseGiftBoxDecoration'] = false;
		}
		echo '</tr>';
		//sections
		echo '<tr>';
		if($TB_settings_place['useSections'] == true) {
			echo '<td>';
			echo 'found Sections\' settings for:';
			echo '</td>';
			echo '<td>';
			$checked = $TB_settings['TB_sectionsUseSections'] ? 'checked' : '';
			echo "<input type='checkbox' name='sectionsUseSections' onclick='this.form.submit();' value=true ".$checked.">";
			echo 'use this section(s) to place <i>animals & flowers</i>';
			echo '</td>';
			echo '</tr>';
			foreach($TB_settings_place as $itemName => $section) {
				if(!in_array($itemName, $ignore)) {
					echo '<tr>';
					echo '<td>';
					echo '<small>&raquo; ' . GetNameByItem($itemName) . '</small>';
					echo '</td>';
					echo '</tr>';
				}
			}
		} else {
			$TB_settings['TB_sectionsUseSections'] = false;
		}
		echo '</tr>';
		echo '</table>';
	} else {
		echo 'Can\'t find settings. Click here to import settings now!<br>';
		echo '<i><a href="'.ToolBox_URL.'?show=TB&importSections=true" class="headerLinks">import</a></i>';
	}
}

//************************************************************************************
// Get the real name of itemname (cornwhite returns White Corn)
//************************************************************************************
if (!function_exists('GetNameByItem'))
{
	function GetNameByItem($itemname) {
		$namelist = Units_GetRealnameByName($itemname);
		if ($namelist != '')
		{
			return $namelist;
		}
		if (file_exists($_SESSION['base_path'] . 'achievement_info.txt'))
		{
			$namelist = @unserialize(file_get_contents($_SESSION['base_path'] . 'achievement_info.txt'));
			if (@$namelist[$itemname]['realname'])
			return $namelist[$itemname]['realname'];
		}
		return 0;
	}
}

//***********************************************************************************
// Get itemname using its 2/4 letter "code"                                         *
//***********************************************************************************

if (!function_exists('GetNameByCode'))
{
	function GetNameByCode($code) {
		if (strlen($code) == 4)
		{
			if (file_exists($_SESSION['base_path'] . 'achievement_info.txt'))
			{
				$codelist = @unserialize(file_get_contents($_SESSION['base_path'] . 'achievement_info.txt'));
				return $item['code']['name'];
			}
		}
		if (strlen($code) == 2)
		{
			$item = Units_GetNameByCode($code);
			if ($item != '')
			return $item;
		}
		return 0;
	}
}
?>
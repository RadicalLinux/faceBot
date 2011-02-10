<?php

function loadUnits() {
	return Units_GetAll();
}

function loadObjects() {
	return GetObjects();
}

function Grifter_loadSettings() {
	global $Grifter_settings;

	list($level, $gold, $cash, $sizeX, $sizeY, $firstname) = explode(';', fBGetDataStore('playerinfo'));
	$need_save = false;

	$Grifter_settings = load_array(Grifter_settings);

	if ($Grifter_settings['Grifter_version'] <> Grifter_version ){

		$Grifter_settings['Grifter_version'] = Grifter_version;
		$Grifter_settings['level'] = 0;
		$Grifter_settings['gold'] = 0;
		$Grifter_settings['sizeX'] = 0;
		$Grifter_settings['sizeY'] = 0;
		$Grifter_settings['giftbox'] = 0;

		$Grifter_Settings['auto'] = false;
		$Grifter_settings['max'] = 0;
		$Grifter_settings['reset'] = false;
		$Grifter_settings['keep'] = false;
		$Grifter_Settings['smaller'] = false;
		$Grifter_Settings['turbo'] = false;

		$Grifter_settings['mansion'] = 0;
		$Grifter_settings['villa'] = 0;
		$Grifter_settings['logcabin'] = 0;
		$Grifter_settings['postoffice'] = 0;
		$Grifter_settings['fruitstand'] = 0;
		$Grifter_settings['schoolhouse'] = 0;
		$Grifter_settings['resttent'] = 0;
		$Grifter_settings['woodpile'] = 0;
		$Grifter_settings['haybale'] = 0;

		$Grifter_settings['engineer'] = 0;
		$Grifter_settings['pheasant'] = 0;
		$Grifter_settings['beltedcow'] = 0;
		$Grifter_settings['goat'] = 0;
		$Grifter_settings['saddleback'] = 0;
		$Grifter_settings['windmill'] = 0;
		$Grifter_settings['cowsilo'] = 0;

		$need_save = true;

	}

	$Grifter_settings['giftbox'] = 0;
	$Grifter_settings['giftbox'] =array_sum(@unserialize(fBGetDataStore('ingiftbox')));

	$Grifter_settings['level'] = $level;
	$Grifter_settings['gold'] = $gold;
	$Grifter_settings['sizeX'] = $sizeX;
	$Grifter_settings['sizeY'] = $sizeY;

	if($need_save) save_array( $Grifter_settings, Grifter_settings );
}


function FarmStats() {
	global $Grifter_settings;

	$xm = $Grifter_settings['sizeX'] - 1;
	$ym = $Grifter_settings['sizeY'] - 1;

	$farm = array();
	$farm = buildFarmArray();
	$cPlots = 0;
	$cFree = 0;

	for ($x = 0; $x <= $xm; $x++) {
		for ($y = 0; $y <= $ym; $y++) {
			if($farm[$x][$y]) $cFree++;
			$cPlots++;
		}
	}
	echo '<table style="border:none" width="35%">';
	echo '<tr>';
	echo '<td colspan="4">';
	echo 'Farm space: ' . $Grifter_settings['sizeX']. ' x ' . $Grifter_settings['sizeY']. '  -  ' . round($cFree / $cPlots * 100) . ' % is free ';  echo '<br>';
	echo 'Level: ' . $Grifter_settings['level'] . ' '; echo '<br>';
	echo 'Coins: ' . $Grifter_settings['gold'].  ' '; echo '<br>';
	echo 'Items in Giftbox: ' . $Grifter_settings['giftbox'].  ' '; echo '<br>';
	echo '</tr>';
	echo '</table>';

}

function Grifter_autofill() {

	global $Grifter_settings;

	if ($Grifter_settings['auto'] ){

		if ($Grifter_settings['max'] > 0 ) {
			$cash = $Grifter_settings['gold'] - $Grifter_settings['max'];
		}
		else {
			$cash = $Grifter_settings['gold'];
		}

		$n = 0;
		while ($cash > 4999999 && $Grifter_settings['level'] > 69 && (!$Grifter_settings['smaller']) ){
			$cash = $cash - 4750000;
			$n++;
		}

		$Grifter_settings['mansion'] = $n;

		while ($cash > 1999999 && $Grifter_settings['level'] > 84 && ($Grifter_settings['smaller']) ){
			$cash = $cash - 1999908;
			$n++;
		}

		$Grifter_settings['pheasant'] = $n;

			
		$n = 0;
		while ($cash > 999999 && $Grifter_settings['level'] > 33 && (!$Grifter_settings['smaller'])){
			$cash = $cash - 950000;
			$n++;
		}

		$Grifter_settings['villa'] = $n;

		while ($cash > 999999 && $Grifter_settings['level'] > 74 && ($Grifter_settings['smaller'])){
			$cash = $cash - 997000;
			$n++;
		}

		$Grifter_settings['beltedcow'] = $n;
			
		$n = 0;
		while ($cash > 499999 && $Grifter_settings['level'] > 54 ){
			$cash = $cash - 498800;
			$n++;
		}

		$Grifter_settings['goat'] = $n;


		$n = 0;
		while ($cash > 249999 && $Grifter_settings['level'] > 23 && (!$Grifter_settings['smaller'])){
			$cash = $cash - 237500;
			$n++;
		}

		$Grifter_settings['logcabin'] = $n;


		while ($cash > 299999 && $Grifter_settings['level'] > 34 && ($Grifter_settings['smaller'])){
			$cash = $cash - 299000;
			$n++;
		}

		$Grifter_settings['saddleback'] = $n;


		$n = 0;
		while ($cash > 99999 && $Grifter_settings['level'] > 16 && (!$Grifter_settings['smaller'])){
			$cash = $cash -95000;
			$n++;
		}

		$Grifter_settings['postoffice'] = $n;

		while ($cash > 99999 && $Grifter_settings['level'] > 21 && ($Grifter_settings['smaller'])){
			$cash = $cash -95000;
			$n++;
		}

		$Grifter_settings['windmill'] = $n;


		$n = 0;
		while ($cash > 49999 && $Grifter_settings['level'] > 17 && (!$Grifter_settings['smaller'])){
			$cash = $cash -47500;
			$n++;
		}

		$Grifter_settings['schoolhouse'] = $n;

		while ($cash > 24999 && $Grifter_settings['level'] > 0 && ($Grifter_settings['smaller'])){
			$cash = $cash -23750;
			$n++;
		}

		$Grifter_settings['cowsilo'] = $n;

		$n = 0;
		while ($cash > 9999 && $Grifter_settings['level'] > 9 )	{
			$cash = $cash -9500;
			$n++;
		}

		$Grifter_settings['fruitstand'] = $n;

		$n = 0;
		while ($cash > 999 && $Grifter_settings['level'] > 3 )	{
			$cash = $cash -950;
			$n++;
		}

		$Grifter_settings['resttent'] = $n;

		$n = 0;
		while ($cash > 499 && $Grifter_settings['level'] > 7 )	{
			$cash = $cash -475;
			$n++;
		}

		$Grifter_settings['woodpile'] = $n;
		$n = 0;
		while ($cash > 99 )  {

			$cash = $cash -95;
			$n++;
		}

		$Grifter_settings['haybale'] = $n;

	}

	if ($Grifter_settings['auto'] ){ save_array($Grifter_settings, Grifter_settings);
	}

}


function SetBuySell() {

	global $Grifter_settings;
	echo '<br>';
	echo '<table  width="100%" class="tablewhite">';
	echo '<tr>';

	echo '<td width= "20%" align="left">'; echo '<b>Item </b>';echo '</td>';
	echo '<td width= "20%" aling="left">'; echo '<b> Cost  </b>';echo '</td>';
	echo '<td width= "20%" aling="left">'; echo '<b> # Bought & Sold</b>';echo '</td>';
	echo '<td width= "20%" aling="left">'; echo '<b> Exp </b>';echo '</td>';

	echo '</tr>';
	echo '</table>';

	echo '<table style="border:none" width="100%">';


	if ($Grifter_settings['level'] > '69'  && (!$Grifter_settings['smaller']) )  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Mansion';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 5,000,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='mansion' value=".$Grifter_settings['mansion'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 50,000 '; echo '</td>';
	echo '</tr>';
	}
	if ($Grifter_settings['level'] > '84' && ($Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Pheasant
';echo '</td>'; 
	echo '<td width= "20%" aling="left">';	echo' 2,000,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='pheasant' value=".$Grifter_settings['pheasant'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 20,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '33' && (!$Grifter_settings['smaller']) )  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Villa';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 1,000,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='villa' value=".$Grifter_settings['villa'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 10,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '74' && ($Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Belted Cow
';echo '</td>'; 
	echo '<td width= "20%" aling="left">';	echo' 1,000,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='beltedcow' value=".$Grifter_settings['beltedcow'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 10,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '55')  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Arapawa Goat';
	echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 500,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='goat' value=".$Grifter_settings['goat'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 5,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '34' && ($Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Saddleback Pig

';echo '</td>'; 
	echo '<td width= "20%" aling="left">';	echo' 300,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='saddleback' value=".$Grifter_settings['saddleback'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 3,000 '; echo '</td>';
	echo '</tr>';
	}


	if ($Grifter_settings['level'] > '23' && (!$Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Log Cabin';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 250,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='logcabin' value=".$Grifter_settings['logcabin'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 2,500 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '16' && (!$Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Post office';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 100,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='postoffice' value=".$Grifter_settings['postoffice'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 1,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '21' && ($Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Windmill

';echo '</td>'; 
	echo '<td width= "20%" aling="left">';	echo' 100,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='windmill' value=".$Grifter_settings['windmill'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 1,000 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '17' && (!$Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'School House';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 50,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='schoolhouse' value=".$Grifter_settings['schoolhouse'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 500 '; echo '</td>';
	echo '</tr>';
	}
	if ($Grifter_settings['level'] > '0' && ($Grifter_settings['smaller']))  {echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Cowprint Silo

';echo '</td>'; 
	echo '<td width= "20%" aling="left">';	echo' 25,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='cowsilo' value=".$Grifter_settings['cowsilo'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 250 '; echo '</td>';
	echo '</tr>';
	}

	if ($Grifter_settings['level'] > '9') 	echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Fruit Stand';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 10,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='fruitstand' value=".$Grifter_settings['fruitstand'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 100 '; echo '</td>';
	echo '</tr>';

	if ($Grifter_settings['level'] > '3') 	echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Rest Tent';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 1,000 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='resttent' value=".$Grifter_settings['resttent'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 10 '; echo '</td>';
	echo '</tr>';

	if ($Grifter_settings['level'] > '7') 	echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Wood Pile';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 500 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='woodpile' value=".$Grifter_settings['woodpile'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 5 '; echo '</td>';
	echo '</tr>';

	echo '<tr>'; echo '<td width= "20%" align="left"> ';echo 'Hay Bale';echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 100 '; echo '</td>';
	echo '<td width= "20%" aling="left">';
	echo "<input type='text' size='10' onchange='SubmitGRIFT()' name='haybale' value=".$Grifter_settings['haybale'].">";echo '</td>';
	echo '<td width= "20%" aling="left">';	echo' 5'; echo '</td>';
	echo '</tr>';

	echo '</table>';


}


function findEmptySpot($random = false, $getFarm = '', $getSizeX = 0, $getSizeY = 0) {
	global $Grifter_settings;

	$xm = $Grifter_settings['sizeX'] - 1;
	$ym = $Grifter_settings['sizeY'] - 1;
	$x = 0;
	$y = 0;

	$re = array();
	$farm = array();
	if(($getFarm == '' || !is_array($getFarm)) || @count($getFarm) < 10) $farm = buildFarmArray();
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
		$re = findEmptySpotFunction($farm, $x, $y, $xm, $ym, $xm, $ym, $getSizeX, $getSizeY);

		if(is_array($re)) return $re;
		else false;
	}

	return false;
}

function findEmptySpotFunction($farm, $x1, $y1, $x2, $y2, $xm, $ym, $getSizeX = 0, $getSizeY = 0) {
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

function buildFarmArray() {
	global $Grifter_settings;

	$xm = $Grifter_settings['sizeX']- 1;
	$ym = $Grifter_settings['sizeY'] - 1;
	$x = 0;
	$y = 0;

	$farm = array();
	$objects = array();
	$objects = loadObjects();
	$units = array();
	$units = loadUnits();

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

//***************************************
//* 					*
//*  Grifters buying % selling function *
//*					*
//***************************************


function FindSpace($getfarm, $name) {
	global $Grifter_settings;
	$units = array();
	$units = loadUnits();
	$farm = array();
	$farm = $getFarm;
	$sizeX = 0;
	$sizeY = 0;
	$position = array();

	$sizeX = $units[$name]['sizeX'];
	$sizeY = $units[$name]['sizeY'];
	$position = findEmptySpot(false, $farm, $sizeX, $sizeY);

	if($position == false) {
		AddLog2('unable to place ' . $units[$name]['realname'] . ' - no free space');
		break;
	}

	$id = Grifter_doPlace($name, $units[$name], $position['x'], $position['y']);

	if ($id['ans'] == 'OK'){
		Grifter_doSell($name, $units[$name], $position['x'], $position['y'], $id['ID']);
	}
	else {
		AddLog2('famstate failed to update, check farm');
	}
	return $farm;
}



function Grifter_doPlace($name, $units, $x, $y) {

	global $count;

	AddLog2( "Place " . $name . " on " . $x . "-" . $y );

	$amf                                                                   = CreateRequestAMF( 'place', 'WorldService.performAction' );
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']                  = $units['class'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']                  = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']                    = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']                   = $name;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']                     = -1;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x']            = $x;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y']            = $y;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z']            = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']                = false;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']   = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal'] = false;

	$tmp = RequestAMF($amf, true);
	$amf2 = $tmp['amf2'];
	$res = $tmp['res'];

	$ID = $amf2->_bodys[0]->_value['data'][0]['data']['id'];

	$data['ans'] = $res;
	$data['ID'] = $ID;
	$data['x'] = $x;
	$data['y'] = $y;


	return $data;

}


function Grifter_doSell($name, $unit, $x, $y, $id ) {
	global $count;

	AddLog2( "Selling " . $name . " on " . $x . "-" . $y );

	$amf									 = CreateRequestAMF('sell', 'WorldService.performAction');
	$amf->_bodys[0]->_value[1][0]['params'][1]['className']    		 = $unit['class'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName']     		 = $name;
	$amf->_bodys[0]->_value[1][0]['params'][1]['id']   	  		 = $id;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] 		 = $x;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] 		 = $y;
	$amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] 		 = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted']      		 = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId']       		 = -1;
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction']    		 = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift']               	 = false;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal']     = 0;
	$amf->_bodys[0]->_value[1][0]['params'][2][0]['isInventoryWithdrawal']	 = false;
	$res                                                                     = RequestAMF( $amf );

	if ( $res == 'OK' )
	{

		$count++;
		return true;
	}
	else
	{
		AddLog2( "ERROR - $res" );
		return false;
	}
}

function Grifter_Turbo($name, $x){
	Addlog2('buying '.$name.' : '.$x.' times');

	$amf							 = CreateRequestAMF('', 'UserService.buyItemputGiftbox');
	$amf->_bodys[0]->_value[1][0]['params'][0]   		 = $name;
	$amf->_bodys[0]->_value[1][0]['params'][1]    		 = $x;
	$amf->_bodys[0]->_value[1][0]['params'][2] 		 = array();
	$amf->_bodys[0]->_value[1][0]['params'][3]  		 = true;

	$res                                                     = RequestAMF( $amf );

	if ( $res == 'OK' )
	{
		Addlog2(" result - $res ");
		return true;
	}
	else
	{
		Addlog2( "ERROR - $res" );
		return false;
	}
}

function Grifter_TurboSell($name, $x) {
	Addlog2('selling '.$name.' : '.$x.' times');
	$vSpeed = 8;
	$Code = Units_GetCodeByName($name);

	unset($GLOBALS['amfphp']['encoding']);

	$vRunMainLoop = ceil($x / $vSpeed);
	for($vI=0;$vI<$vRunMainLoop;$vI++) {


		$vNumAction=0;

		for($vJ=($vI*$vSpeed);(($vJ<(($vI*$vSpeed)+$vSpeed))&&($vJ<$x));$vJ++) {

			@$amf = CreateMultAMFRequest($amf, $vNumAction, '', 'UserService.sellStoredItem');

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][0]['code'] = $Code;
			$amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = false;

			$amf->_bodys[0]->_value[1][$vNumAction]['params'][2] = -1;


			$vNumAction++;
		}

		$res = RequestAMF($amf);

		if ($res == 'OK') {

			Addlog2(" result $res");

		} else {

			AddLog2("GiftBox: Error - " . $res );

			return($res);

		}


	}

	return $res;
}


?>
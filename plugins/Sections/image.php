<?php

  if (isset($_GET['image']) && $_GET['image'] == '1') {
    if (!function_exists("gd_info")) {
      return; // GD not enabled, prevent beeps
    }

#    $image = imagecreatetruecolor(204*$ratio*2, 204*$ratio);
#    $image = imagecreatetruecolor(214*$ratio*2, 219*$ratio);
    $image = imagecreatetruecolor(234*$vSettings['ratio']*2, 239*$vSettings['ratio']);

#    $col_back                       = array(136, 119,  51);
    $col_back                       = array(255, 255,  255);
    $col_fill                       = array( 60, 135,  50);
    $col_unknown                    = array(255, 125,  0);
    $col_edit                       = array(255, 102,   0, 20);

    $col['section_seed_1']          = array(255, 255, 100, 50);
    $col['section_anim_1']          = array(255, 100, 100, 50);
    $col['section_tree_1']          = array(100, 255, 100, 50);
    $col['section_deco_1']          = array(100, 100, 255, 50);
    $col['section_building_1']      = array(100, 100, 255, 50);
    $col['section_buyanim_1']       = array(255, 100, 100, 50);
    $col['section_buytree_1']       = array(100, 255, 100, 50);
    $col['section_buydeco_1']       = array(100, 100, 255, 50);
    $col['section_dontmove_1']      = array(255, 255, 255, 40);

    $col['section_seed_0']          = array(255, 255, 255, 70);
    $col['section_anim_0']          = array(255, 255, 255, 70);
    $col['section_tree_0']          = array(255, 255, 255, 70);
    $col['section_deco_0']          = array(255, 255, 255, 70);
    $col['section_building_0']      = array(255, 255, 255, 70);
    $col['section_buyanim_0']       = array(255, 255, 255, 70);
    $col['section_buytree_0']       = array(255, 255, 255, 70);
    $col['section_buydeco_0']       = array(255, 255, 255, 70);
    $col['section_dontmove_0']      = array(255, 255, 255, 70);

    $col['Plot']                    = array(140,  55,  33);

    $col['Animal']                  = array(200,   0,  50);
    $col['Tree']                    = array(  0, 255,   0);

    $col['Harvester']               = array( 25,  25, 255);
    $col['Seeder']                  = array(215, 180,  50);
    $col['Tractor']                 = array(255,  25,  25);

    $col['Building']                = array(100, 100, 100);
    $col['StorageBuilding']         = array(100, 100, 240);

    $col['ChickenCoopBuilding']     = array(245, 195,  65);
    $col['DairyFarmBuilding']       = array(170, 120,  30);
    $col['HorseStableBuilding']     = array( 60,  40,  30);

    $col['Decoration']              = array( 50, 100, 100);
    $col['FlowerDecoration']        = array(190, 255, 190);
    $col['GateDecoration']          = array(255, 255, 255);
    $col['InteractiveDecoration']   = array(255, 255, 150);
    $col['MaskedDecoration']        = array(200, 200, 255);
    $col['RotateableDecoration']    = array(200, 200, 200);
    $col['LootableDecoration']      = array(200, 200, 200);
    $col['Egg']                     = array(200, 200, 200);

    $col['BotanicalGardenBuilding'] = array(139,  69,  19);
    $col['HarvestStorageBuilding']  = array(139,  69,  19);
    $col['HolidayTreeStorage']      = array(139,  69,  19);

    $col['ValentinesBox']           = array(255, 195, 203);
    $col['EasterBasket']            = array(255, 195, 203);
    $col['PotOfGold']               = array(255, 195, 203);

    $col['MysteryGift']             = array(255,  90, 150);


    for ($x = 0; $x < $sizeX; $x++) {
      for ($y = 0; $y < $sizeY; $y++) {
        Sections_Draw_Thing($image, $x, $y, 1, 1, $vSettings['ratio'], $col_fill);
      }
    }


    foreach($objects as $o) {
#      $u = $units[$o['itemName']];
      $u = Units_GetUnitByName($o['itemName']);

      if ( !isset($u['sizeX']) ) {
        $u['sizeX'] = 1;
        $u['sizeY'] = 1;
      }

      if ($o['state'] == 'vertical') {
        $t = $u['sizeX'];
        $u['sizeX'] = $u['sizeY'];
        $u['sizeY'] = $t;
      }

      $color = (isset($col[$o['className']])) ? $col[$o['className']] : $col_unknown;

      Sections_Draw_Thing($image, $o['position']['x'], $o['position']['y'],$u['sizeX'], $u['sizeY'], $vSettings['ratio'], $color);
    }


    if (!($is_copy || $is_edit)) {
      foreach($sections as $num => $section) {
        # draw the caption
        $x = $section['bot_x'];
        $y = $section['bot_y'];

        $sx = ($section['top_x']-$section['bot_x']+1);
        $sy = ($section['top_y']-$section['bot_y']+1);

        Sections_Draw_Thing($image, $x, $y, $sx, $sy, $vSettings['ratio'], $col['section_'.$section['type'].'_'.($section['active']<>'1'?'0':'1')],true);
      }
      foreach($sections as $num => $section) {
        # draw the caption
        $x = $section['bot_x'];
        $y = $section['bot_y'];

        $sx = ($section['top_x']-$section['bot_x']+1);
        $sy = ($section['top_y']-$section['bot_y']+1);

        Sections_Write_Caption($image, $x, $y, $sx, $sy, $vSettings['ratio'], 'S-'.$num);
      }
    } elseif ( strlen($_GET['num'])>0 ) {
      foreach($sections as $num => $section) {
        # draw the caption
        $x = $section['bot_x'];
        $y = $section['bot_y'];

        $sx = ($section['top_x']-$section['bot_x']+1);
        $sy = ($section['top_y']-$section['bot_y']+1);
        if($_GET['num']<>$num) {
          Sections_Draw_Thing($image, $x, $y, $sx, $sy, $vSettings['ratio'], $col['section_'.$section['type'].'_'.($section['active']<>'1'?'0':'1')],true);
        }
      }
      $section=$sections[$_GET['num']];
      $x = $section['bot_x'];
      $y = $section['bot_y'];

      $sx = ($section['top_x']-$section['bot_x']+1);
      $sy = ($section['top_y']-$section['bot_y']+1);
      Sections_Draw_Thing($image, $x, $y, $sx, $sy, $vSettings['ratio'], $col_edit, true);
      Sections_Write_Caption($image, $x, $y, $sx, $sy, $vSettings['ratio'], 'Edit S-'.$_GET['num']);
    } else {
      foreach($sections as $num => $section) {
        # draw the caption
        $x = $section['bot_x'];
        $y = $section['bot_y'];

        $sx = ($section['top_x']-$section['bot_x']+1);
        $sy = ($section['top_y']-$section['bot_y']+1);
        Sections_Draw_Thing($image, $x, $y, $sx, $sy, $vSettings['ratio'], $col['section_'.$section['type'].'_'.($section['active']<>'1'?'0':'1')],true);
      }
    }

    imagefill($image, 0, 0, imagecolorallocate($image, $col_back[0], $col_back[1], $col_back[2]));

    @header('Content-type: image/png');

    imagepng($image);
    imagedestroy($image);

  }

?>

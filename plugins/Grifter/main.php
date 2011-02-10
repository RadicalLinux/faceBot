<?php

//****************************************************
//*                                                  *
//*               Grifter by StrmCkr                 *
//*                                                  *
//* this plug in buy's and sells an object n times   *
//* to gain x amunt of xp per cycle                  *
//*                                                  *
//****************************************************
define ('Grifter_URL', '/plugins/Grifter/index.php');
define ('Grifter_path', 'plugins/Grifter/');
define ('Grifter_settings', 'Grifter_settings.txt');
define ('Grifter_version', '1.04');

$Grifter_settings = array();

include 'functions.php';

function Grifter_init() {

	global $Grifter_settings;
	$_SESSION['hooks']['before_harvest'] = 'Grifter_run';

	echo 'Loaded Grifter v' . Grifter_version . " by StrmCkr\r\n";
}


function Grifter_run() {
	global $Grifter_settings;

	Grifter_loadSettings();
	Grifter_autofill();

	AddLog2('Grifter v' . Grifter_version);

	$farm = null;
	$farm = Grifter_Grift($farm);

	save_array($Grifter_settings, Grifter_settings);

}


function Grifter_Grift($getFarm = null) {
      
       global $Grifter_settings;
       $farm = $getFarm; 
       
       $retain = array();
       $retain = $Grifter_settings;

       $cash = $Grifter_settings['gold'] - $Grifter_settings['max'] ;

       $use = 250 - $Grifter_settings['giftbox']; 
   
        if (!$Grifter_settings['turbo'] || $use < 1 ) {

            while ($Grifter_settings['engineer'] > 0 ) {
			
			FindSpace($getFarm, city_xpromo_round2 ); 			
			$Grifter_settings['engineer']--;
		}
	
	    while ($cash > 4999999 && $Grifter_settings['level'] > 69 && $Grifter_settings['mansion'] > 0 ) {
			
                       	FindSpace($getFarm, highrollerhome); 
			$cash = $cash - 4750000;
			$Grifter_settings['mansion']--;
		} 

	   while ($cash > 1999999 && $Grifter_settings['level'] > 84 && $Grifter_settings['pheasant'] > 0 ) {
			
			FindSpace($getFarm, pheasant_ladyamherst); 
			$cash = $cash - 1999908;
			$Grifter_settings['pheasant']--;
		} 

           while ($cash > 999999 && $Grifter_settings['level'] > 33  && $Grifter_settings['villa'] > 0 ) {
			
			FindSpace($getFarm, villa ); 
			$cash = $cash - 950000;
			$Grifter_settings['villa']--;
		} 

	   while ($cash > 999999 && $Grifter_settings['level'] > 74 && $Grifter_settings['beltedcow'] > 0 ) {
			
			FindSpace($getFarm, cow_belted); 
			$cash = $cash - 997000;
			$Grifter_settings['beltedcow']--;
		} 

	   while ($cash > 499999 && $Grifter_settings['level'] > 54 && $Grifter_settings['goat'] > 0 ) {
			
			FindSpace($getFarm, goat_arapawa); 
			$cash = $cash - 498800;
			$Grifter_settings['goat']--;
		} 

	   while ($cash > 299999 && $Grifter_settings['level'] > 34 && $Grifter_settings['saddleback'] > 0 ) {
			
			FindSpace($getFarm,pig_belted ); 
			$cash = $cash - 299000;
			$Grifter_settings['saddleback']--;
		} 
	  
            while ($cash > 249999 && $Grifter_settings['level'] >23  && $Grifter_settings['logcabin'] > 0 ) {
			
			FindSpace($getFarm, logcabin ); 
			$cash = $cash - 237500;
			$Grifter_settings['logcabin']--;
		} 


	    while ($cash > 99999 && $Grifter_settings['level'] > 16  && $Grifter_settings['postoffice'] > 0 ) {
			
			FindSpace($getFarm, postoffice ); 
			$cash = $cash - 95000;
			$Grifter_settings['postoffice']--;
		} 

	    while ($cash > 99999 && $Grifter_settings['level'] > 21  && $Grifter_settings['windmill'] > 0 ) {
			
			FindSpace($getFarm, windmill ); 
			$cash = $cash - 95000;
			$Grifter_settings['windmill']--;
		} 

	     while ($cash > 49999 && $Grifter_settings['level'] > 17 &&  $Grifter_settings['schoolhouse'] > 0 ) {
			
			FindSpace($getFarm, schoolhouse ); 
			$cash = $cash - 47500;
			$Grifter_settings['schoolhouse']--;
		} 

	     while ($cash > 24999 && $Grifter_settings['level'] > 0 &&  $Grifter_settings['cowsilo'] > 0 ) {
			
			FindSpace($getFarm, grainsilocowprint ); 
			$cash = $cash - 23750;
			$Grifter_settings['cowsilo']--;
		} 


	     while ($cash > 9999 && $Grifter_settings['level'] > 9 &&  $Grifter_settings['fruitstand'] > 0 ) {
			
			FindSpace($getFarm, fruitstand ); 
			$cash = $cash - 9500;
			$Grifter_settings['fruitstand']--;
		} 

	     while ($cash > 999 && $Grifter_settings['level'] > 3 && $Grifter_settings['resttent'] > 0 ) {
			
			FindSpace($getFarm, resttent ); 
			$cash = $cash - 950;
			$Grifter_settings['resttent']--;
		} 

	     while ($cash > 499 && $Grifter_settings['level'] > 7 && $Grifter_settings['woodpile'] > 0 ) {
			
			FindSpace($getFarm, woodpile ); 
			$cash = $cash - 475;
			$Grifter_settings['woodpile']--;
		}
 
	     while ($cash > 99 && $Grifter_settings['level'] > 0 && $Grifter_settings['haybale'] > 0 ) {
			
			FindSpace($getFarm, haybale ); 
			$cash = $cash - 95;
			$Grifter_settings['haybale']--;
		} 
  }
 else  {
         
 while ($Grifter_settings['engineer'] > 0 && $use > 0 ) {
		   if ( $use < $Grifter_settings['engineer'] ) {
                          Grifter_Turbo(city_xpromo_round2, $use);
                          Grifter_TurboSell(city_xpromo_round2, $use);
                          $Grifter_settings['engineer'] = $Grifter_settings['engineer'] - $use;
                    }
                      else {
                          Grifter_Turbo(city_xpromo_round2,$Grifter_settings['engineer'] );
                          Grifter_TurboSell(city_xpromo_round2, $Grifter_settings['engineer']);
                          $Grifter_settings['engineer'] = $Grifter_settings['engineer'] - $Grifter_settings['engineer'];
                           
                    } 
                   }

while ($cash > (4999999 * $Grifter_settings['mansion']) && $Grifter_settings['level'] > 69 && $Grifter_settings['mansion'] > 0 ) {
		   if ( $use < $Grifter_settings['mansion'] ) {
                          Grifter_Turbo(highrollerhome, $use);
                          Grifter_TurboSell(highrollerhome, $use);
                          $Grifter_settings['mansion'] = $Grifter_settings['mansion'] - $use;
                          $cash = $cash - ( 4750000 * $use);
                    }
                      else {
                          Grifter_Turbo(highrollerhome,$Grifter_settings['mansion'] );
                          Grifter_TurboSell(highrollerhome, $Grifter_settings['mansion']);
                          $cash = $cash - (4750000 * $Grifter_settings['mansion']);
                          $Grifter_settings['mansion'] = $Grifter_settings['mansion'] - $Grifter_settings['mansion'];
                           
                    } 
		} 

while ($cash > ( 1999999 * $Grifter_settings['pheasant'] ) && $Grifter_settings['level'] > 84 && $Grifter_settings['pheasant'] > 0 ) {
		  if ( $use < $Grifter_settings['pheasant'] ) {
                          Grifter_Turbo(pheasant_ladyamherst, $use);
                          Grifter_TurboSell(pheasant_ladyamherst, $use);
                          $Grifter_settings['mansion'] = $Grifter_settings['pheasant'] - $use;
                          $cash = $cash - ( 1999908 * $use);
                    }
                      else {
                          Grifter_Turbo(pheasant_ladyamherst,$Grifter_settings['pheasant'] );
                          Grifter_TurboSell(pheasant_ladyamherst, $Grifter_settings['pheasant']);
                          $cash = $cash - (1999908 * $Grifter_settings['pheasant']);
                          $Grifter_settings['pheasant'] = $Grifter_settings['pheasant'] - $Grifter_settings['pheasant'];
                           
                    } 
		} 

while ($cash > (999999 * $Grifter_settings['villa'] )&& $Grifter_settings['level'] > 33  && $Grifter_settings['villa'] > 0 ) {
		  if ( $use < $Grifter_settings['villa'] ) {
                          Grifter_Turbo(villa, $use);
                          Grifter_TurboSell(villa, $use);
                          $Grifter_settings['villa'] = $Grifter_settings['villa'] - $use;
                          $cash = $cash - ( 950000 * $use);
                    }
                      else {
                          Grifter_Turbo(villa,$Grifter_settings['villa'] );
                          Grifter_TurboSell(villa, $Grifter_settings['villa']);
                          $cash = $cash - (950000 * $Grifter_settings['villa']);
                          $Grifter_settings['villa'] = $Grifter_settings['villa'] - $Grifter_settings['villa'];
                           
                    } 
		} 

while ($cash > (999999 * $Grifter_settings['beltedcow']) && $Grifter_settings['level'] > 74 && $Grifter_settings['beltedcow'] > 0 ) {
 		  if ( $use < $Grifter_settings['beltedcow'] ) {
                          Grifter_Turbo(cow_belted, $use);
                          Grifter_TurboSell(cow_belted, $use);
                          $Grifter_settings['cow_belted'] = $Grifter_settings['beltedcow'] - $use;
                          $cash = $cash - ( 997000 * $use);
                    }
                      else {
                          Grifter_Turbo(cow_belted,$Grifter_settings['beltedcow'] );
                          Grifter_TurboSell(cow_belted, $Grifter_settings['beltedcow']);
                          $cash = $cash - (997000 * $Grifter_settings['beltedcow']);
                          $Grifter_settings['beltedcow'] = $Grifter_settings['beltedcow'] - $Grifter_settings['beltedcow'];
                           
                    } 
		}
			
while ($cash > (499999 * $Grifter_settings['goat']) && $Grifter_settings['level'] > 54 && $Grifter_settings['goat'] > 0 ) {
 		  if ( $use < $Grifter_settings['goat'] ) {
                          Grifter_Turbo(goat_arapawa, $use);
                          Grifter_TurboSell(goat_arapawa, $use);
                          $Grifter_settings['goat'] = $Grifter_settings['goat'] - $use;
                          $cash = $cash - ( 498800 * $use);
                    }
                      else {
                          Grifter_Turbo(goat_arapawa, $Grifter_settings['goat'] );
                          Grifter_TurboSell(goat_arapawa, $Grifter_settings['goat']); 
                          $cash = $cash - (498800 * $Grifter_settings['goat']);
                          $Grifter_settings['goat'] = $Grifter_settings['goat'] - $Grifter_settings['goat']; 
                           
                    } 
		}			

while ($cash > ( 299999  * $Grifter_settings['saddleback'] ) && $Grifter_settings['level'] > 34 && $Grifter_settings['saddleback'] > 0 ) {
 		  if ( $use < $Grifter_settings['saddleback'] ) {
                          Grifter_Turbo(pig_belted, $use);
                          Grifter_TurboSell(pig_belted, $use);
                          $Grifter_settings['saddleback'] = $Grifter_settings['saddleback'] - $use;
                          $cash = $cash - ( 299000 * $use);
                    }
                      else {
                          Grifter_Turbo(pig_belted, $Grifter_settings['saddleback'] );
                          Grifter_TurboSell(pig_belted, $Grifter_settings['saddleback']); 
                          $cash = $cash - (299000 * $Grifter_settings['saddleback']);
                          $Grifter_settings['saddleback'] = $Grifter_settings['saddleback'] - $Grifter_settings['saddleback']; 
                           
                    } 
		}			
	   
while ($cash > (249999 * $Grifter_settings['logcabin']) && $Grifter_settings['level'] >23  && $Grifter_settings['logcabin'] > 0 ) {
 		  if ( $use < $Grifter_settings['logcabin'] ) {
                          Grifter_Turbo(logcabin, $use);
                          Grifter_TurboSell(logcabin, $use);
                          $Grifter_settings['logcabin'] = $Grifter_settings['logcabin'] - $use;
                          $cash = $cash - ( 237500 * $use);
                    }
                      else {
                          Grifter_Turbo(logcabin, $Grifter_settings['logcabin'] );
                          Grifter_TurboSell(logcabin, $Grifter_settings['logcabin']); 
                          $cash = $cash - (237500 * $Grifter_settings['logcabin']);
                          $Grifter_settings['logcabin'] = $Grifter_settings['logcabin'] - $Grifter_settings['logcabin']; 
                           
                    } 
		}			
		
while ($cash > (99999 * $Grifter_settings['postoffice']) && $Grifter_settings['level'] > 16  && $Grifter_settings['postoffice'] > 0 ) {
 		  if ( $use < $Grifter_settings['postoffice'] ) {
                          Grifter_Turbo(postoffice, $use);
                          Grifter_TurboSell(postoffice, $use);
                          $Grifter_settings['postoffice'] = $Grifter_settings['postoffice'] - $use;
                          $cash = $cash - ( 95000 * $use);
                    }
                      else {
                          Grifter_Turbo(postoffice, $Grifter_settings['postoffice'] );
                          Grifter_TurboSell(postoffice, $Grifter_settings['postoffice']); 
                          $cash = $cash - (95000 * $Grifter_settings['postoffice']);
                          $Grifter_settings['postoffice'] = $Grifter_settings['postoffice'] - $Grifter_settings['postoffice']; 
                           
                    } 
		}			
		

while ($cash > (99999 * $Grifter_settings['windmill']) && $Grifter_settings['level'] > 21  && $Grifter_settings['windmill'] > 0 ) {
 		  if ( $use < $Grifter_settings['windmill'] ) {
                          Grifter_Turbo(windmill, $use);
                          Grifter_TurboSell(windmill, $use);
                          $Grifter_settings['windmill'] = $Grifter_settings['windmill'] - $use;
                          $cash = $cash - ( 95000 * $use);
                    }
                      else {
                          Grifter_Turbo(windmill, $Grifter_settings['windmill'] );
                          Grifter_TurboSell(windmill, $Grifter_settings['windmill']); 
                          $cash = $cash - (95000 * $Grifter_settings['windmill']);
                          $Grifter_settings['windmill'] = $Grifter_settings['windmill'] - $Grifter_settings['windmill']; 
                           
                    } 
		}			

while ($cash > (49999 * $Grifter_settings['schoolhouse']) && $Grifter_settings['level'] > 17 &&  $Grifter_settings['schoolhouse'] > 0 ) {
 		  if ( $use < $Grifter_settings['schoolhouse'] ) {
                          Grifter_Turbo(schoolhouse, $use);
                          Grifter_TurboSell(schoolhouse, $use);
                          $Grifter_settings['schoolhouse'] = $Grifter_settings['schoolhouse'] - $use;
                          $cash = $cash - ( 47500 * $use);
                    }
                      else {
                          Grifter_Turbo(schoolhouse, $Grifter_settings['schoolhouse'] );
                          Grifter_TurboSell(schoolhouse, $Grifter_settings['schoolhouse']); 
                          $cash = $cash - ( 47500 * $Grifter_settings['schoolhouse']);
                          $Grifter_settings['schoolhouse'] = $Grifter_settings['schoolhouse'] - $Grifter_settings['schoolhouse']; 
                           
                    } 
		}			
			
while ($cash > ( 24999 * $Grifter_settings['cowsilo'] ) && $Grifter_settings['level'] > 0 &&  $Grifter_settings['cowsilo'] > 0 ) {
 		  if ( $use < $Grifter_settings['cowsilo'] ) {
                          Grifter_Turbo(grainsilocowprint, $use);
                          Grifter_TurboSell(grainsilocowprint, $use);
                          $Grifter_settings['cowsilo'] = $Grifter_settings['cowsilo'] - $use;
                          $cash = $cash - ( 23750 * $use);
                    }
                      else {
                          Grifter_Turbo(grainsilocowprint, $Grifter_settings['cowsilo'] );
                          Grifter_TurboSell(grainsilocowprint, $Grifter_settings['cowsilo']); 
                          $cash = $cash - ( 23750 * $Grifter_settings['cowsilo']);
                          $Grifter_settings['cowsilo'] = $Grifter_settings['cowsilo'] - $Grifter_settings['cowsilo']; 
                           
                    } 
		}			
		
while ($cash > ( 9999 * $Grifter_settings['fruitstand'] ) && $Grifter_settings['level'] > 9 &&  $Grifter_settings['fruitstand'] > 0 ) {
 		  if ( $use < $Grifter_settings['fruitstand'] ) {
                          Grifter_Turbo(fruitstand, $use);
                          Grifter_TurboSell(fruitstand, $use);
                          $Grifter_settings['fruitstand'] = $Grifter_settings['fruitstand'] - $use;
                          $cash = $cash - ( 9500 * $use);
                    }
                      else {
                          Grifter_Turbo(fruitstand, $Grifter_settings['fruitstand'] );
                          Grifter_TurboSell(fruitstand, $Grifter_settings['fruitstand']); 
                          $cash = $cash - ( 9500 * $Grifter_settings['fruitstand']);
                          $Grifter_settings['fruitstand'] = $Grifter_settings['fruitstand'] - $Grifter_settings['fruitstand']; 
                           
                    } 
		}			
			

while ($cash > ( 999 * $Grifter_settings['resttent'] ) && $Grifter_settings['level'] > 3 && $Grifter_settings['resttent'] > 0 ) {
 		  if ( $use < $Grifter_settings['resttent'] ) {
                          Grifter_Turbo(resttent, $use);
                          Grifter_TurboSell(resttent, $use);
                          $Grifter_settings['resttent'] = $Grifter_settings['resttent'] - $use;
                          $cash = $cash - ( 950 * $use);
                    }
                      else {
                          Grifter_Turbo(resttent, $Grifter_settings['resttent'] );
                          Grifter_TurboSell(resttent, $Grifter_settings['resttent']); 
                          $cash = $cash - ( 950 * $Grifter_settings['resttent']);
                          $Grifter_settings['resttent'] = $Grifter_settings['resttent'] - $Grifter_settings['resttent']; 
                           
                    } 
		}			
			
while ($cash > ( 499 * $Grifter_settings['woodpile'] ) && $Grifter_settings['level'] > 7 && $Grifter_settings['woodpile'] > 0 ) {
 		  if ( $use < $Grifter_settings['woodpile'] ) {
                          Grifter_Turbo(woodpile, $use);
                          Grifter_TurboSell(woodpile, $use);
                          $Grifter_settings['woodpile'] = $Grifter_settings['woodpile'] - $use;
                          $cash = $cash - ( 475 * $use);
                    }
                      else {
                          Grifter_Turbo(woodpile, $Grifter_settings['woodpile'] );
                          Grifter_TurboSell(woodpile, $Grifter_settings['woodpile']); 
                          $cash = $cash - ( 475 * $Grifter_settings['woodpile']);
                          $Grifter_settings['woodpile'] = $Grifter_settings['woodpile'] - $Grifter_settings['woodpile']; 
                           
                    } 
		}			
						
while ($cash > ( 99 * $Grifter_settings['haybale'] ) && $Grifter_settings['level'] > 0 && $Grifter_settings['haybale'] > 0 ) {
 		  if ( $use < $Grifter_settings['haybale'] ) {
                          Grifter_Turbo(haybale, $use);
                          Grifter_TurboSell(haybale, $use);
                          $Grifter_settings['haybale'] = $Grifter_settings['haybale'] - $use;
                          $cash = $cash - ( 95 * $use);
                    }
                      else {
                          Grifter_Turbo(haybale, $Grifter_settings['haybale'] );
                          Grifter_TurboSell(haybale, $Grifter_settings['haybale']); 
                          $cash = $cash - ( 95 * $Grifter_settings['haybale']);
                          $Grifter_settings['haybale'] = $Grifter_settings['haybale'] - $Grifter_settings['haybale']; 
                           
                    } 
		}			
					

} // else 



if ($Grifter_settings['keep']) {
        $Grifter_settings = $retain;
        }

      save_array($Grifter_settings, Grifter_settings);

return $farm;





}


?>





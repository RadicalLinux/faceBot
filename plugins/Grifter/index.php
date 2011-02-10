<?php
require_once '../../fB_PluginAPI.php';
include_once 'main.php';
global $Grifter_settings;
 
Grifter_loadSettings();

$show = '';

if(isset($_GET['send'])) {
	if($_GET['send'] == 'GRIFT' ) {
		$show = 'GRIFT';
			
		if ($_GET['reset'] ){

			$Grifter_settings['auto'] = false;
			$Grifter_settings['reset'] = false;
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
		} else {

			$Grifter_settings['auto'] = $_GET['auto'];
			$Grifter_settings['max'] = $_GET['max'];
			$Grifter_settings['reset'] = $_GET['reset'];
			$Grifter_settings['keep'] = $_GET['keep'];
			$Grifter_settings['smaller'] = $_GET['smaller'];
			$Grifter_settings['turbo'] = $_GET['turbo'];

			$Grifter_settings['mansion'] = $_GET['mansion'];
			$Grifter_settings['villa'] = $_GET['villa'];
			$Grifter_settings['logcabin'] = $_GET['logcabin'];
			$Grifter_settings['postoffice'] = $_GET['postoffice'];
			$Grifter_settings['fruitstand'] = $_GET['fruitstand'];
			$Grifter_settings['resttent'] = $_GET['resttent'];
			$Grifter_settings['woodpile'] = $_GET['woodpile'];
			$Grifter_settings['haybale'] = $_GET['haybale'];
			$Grifter_settings['schoolhouse'] = $_GET['schoolhouse'];
			 
			$Grifter_settings['engineer'] = $_GET['engineer'];
			$Grifter_settings['pheasant'] =  $_GET['pheasant'];
			$Grifter_settings['beltedcow'] = $_GET['beltedcow'];
			$Grifter_settings['goat'] =  $_GET['goat'];
			$Grifter_settings['saddleback'] =  $_GET['saddleback'];
			$Grifter_settings['windmill'] =  $_GET['windmill'];
			$Grifter_settings['cowsilo'] =  $_GET['cowsilo'];

		}
	}
}
save_array($Grifter_settings, Grifter_settings);
?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="Grifter.css" />
</head>
<body>
<script type='text/javascript'>	
			function SubmitGRIFT() { 
				GRIFT.submit(); 
			
			}
			
                      

		</script>
<?php fBAcctHeader(); ?>


<h4>Grifter by StrmCkr - v<?= Grifter_version; ?> — [Jan 4th, 2010]</h4>
<small>it may happen that if you change settings while Grifter runs the settings will not be saved properly</small>
<br>
&nbsp;

<table width='100%' class='tableWhite'>

	<tr>
		<td width='15%' align='center'><a href='<?= Grifter_URL; ?>?userId=<?= $_SESSION['userId']; ?>&show=GRIFT' class='headerLinks'>
		<h3>Grifter</h3></td>


	</tr>
</table>

<?php if($show == 'GRIFT' || $show == '') { ?>
<h3>Grifter v<?= Grifter_version ?></h3>
<form name="GRIFT"><?php FarmStats(); ?>
<input type="hidden" name="userId" value="<?= $_SESSION['userId']; ?>">
<br>
Amount of coins Grifter cannot use: <input type='text' name='max' onchange='SubmitGRIFT()' size='10' value='<?php echo $Grifter_settings['max']?>' /> <br>
Grifter to automatically set which amounts per item to buy/sell? <input type='checkbox' name='auto' onclick='SubmitGRIFT()' value=true <?php if($Grifter_settings['auto'])echo 'checked'; ?>> <br>
Grifter_Turbo: <small> {uses giftbox instead of farm}</small> <input type='checkbox' name='turbo' onclick='SubmitGRIFT()'  value=true <? if($Grifter_settings['turbo'])echo 'checked' ?> > <br>
Grifter to use smaller sized objects? <input type='checkbox' name='smaller' onclick='SubmitGRIFT()' value=true <?php if($Grifter_settings['smaller'])echo 'checked'; ?>> <br>
Grifter to keep manual vaules for each cycle: <input type='checkbox' name='keep' onclick='SubmitGRIFT()' value=true <?php if ($Grifter_settings['keep']) echo 'checked'; ?>> <br>
Reset (#Bought & Sold) values to zero: <input type='checkbox' name='reset' onclick='SubmitGRIFT()' value=true <?php if($Grifter_settings['reset']) echo 'checked'; ?>> <br>
<br>
<input type="button" value="Save changes >>" onclick='SubmitGRIFT();' >
<br><br>
<table style="border: none" width="100%">
	<tr>
		<td width="20%" align="left">Farmgineers</td>
		<td width="20%" align="left">0</td>
		<td width="20%" align="left">
			<input type='text' size='10' onchange='SubmitGRIFT()' name='engineer' value="<?php echo $Grifter_settings['engineer']; ?>" /></td>
		<td width="20%" align="left">5</td>
	</tr>
</table>

<?php if ( $Grifter_settings['auto'] ) Grifter_autofill(); ?> <?php SetBuySell(); ?>


<input type='hidden' name='send' value='GRIFT'></form>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick"> 
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----"> 
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="If you'd like to sponsor my work use: { USD }                      The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

</body>
</html>

<?php } ?>

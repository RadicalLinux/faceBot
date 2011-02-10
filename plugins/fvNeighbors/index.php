<?php
require_once '../../fB_PluginAPI.php';
define('fvNeighbors_version', file_get_contents('fvNeighbors.ver'));
define('fvNeighbors_date', '24 October 2010');
define('fvNeighbors_URL', '/plugins/fvNeighbors/index.php');
define('fvNeighbors_Path', getcwd() . '\\');
// file definitions
define('fvNeighbors_Main', 'fvNeighbors_main.sqlite');
/******************fvNeighbors by RadicalLinux****************************/
require_once('includes/GridServerHandler.php');
require_once('includes/JSON.php');
require_once('includes/ExcelExport.php');
include 'includes/fvNeighbors.class.php';

	$_SESSION['userId'] = isset($_POST['userId']) ? $_POST['userId'] : $_SESSION['userId']; 
	$fsM = new fvNeighbors('formload');
	$gridHandler = new GridServerHandler();
	$gtype = getParameter('exportType');
	if (isset($_POST['save'])) 
	{
		$json = new Services_JSON();
		$newgtjson = str_replace("\\", "", $_POST['_gt_json']);
		$value = json_decode($newgtjson, true);
		foreach ($value['deletedRecords'] as $dr)
		{
			$fsM->fnDeleteNeigh($dr['fbid']);	
		}
  		echo "{success : true,exception:''}";		
		return;
	}
	If ($gtype != '') {
			@$tmpNeigh =  $fsM->fnGetNeighbors();
			foreach ($tmpNeigh as $nbor)
			{
				$lastseen = date("m/d/y, g:i a", $nbor['neighbors_lastseen']);
				$lastseen = (empty($lastseen)) ? 'Not Seen' : $lastseen;
				$farmsize = $nbor['neighbors_sizeX'] . 'x' . $nbor['neighbors_sizeY'];
				$nbor['neighbors_name'] = fBGetNeighborRealName($nbor['neighbors_fbid']);
				$nbor['neighbors_name'] = empty($nbor['neighbors_name']) ? 'UnKnown' : $nbor['neighbors_name'];
				$nbor['neighbors_plots'] = ($nbor['neighbors_plots'] < 1) ? 0 : $nbor['neighbors_plots'];
				$data1[] =  array('fbid' => $nbor['neighbors_fbid'], 
				'name' => '"' . $nbor['neighbors_name'] . '"', 'lastseen' => '"' . $lastseen . '"',
				'farmsize' => '"' . $farmsize . '"', 'objects' => $nbor['neighbors_objects'] );
			}
			if ( $gtype == 'xml' ){
				$gridHandler->exportXML($data1);
			}else if ( $gtype == 'xls' ){
				//exporting to xls
				$gridHandler->exportXLS($data1);
			}else if ( $gtype == 'csv' ){
				//exporting to csv
				$gridHandler->exportCSV($data1);
			}
		return;
	}
	if (isset($_POST['submit']) && $_POST['submit'] == 'Save Settings')
	{
		$fsM->fnDoSettings($_POST);
		header("Location: index.php?userId=" . $_SESSION['userId']);	
	}
	if (empty($fsM->settings)) {
		echo 'Database is not initialized yet, please allow bot to run a cycle';
		return;
	}
	if(!empty($fsM->error) && $fsM->haveWorld !== false)
	{
		echo $fsM->error;
		return;
	}

	$fsM->settings = $fsM->fnGetSettings();
	$NNcount = $fsM->fnNNCount();

	//Counts



	?>
<html>
<head>
<style type="text/css">
body {
	font-family: arial, helvetica, sans-serif;
}

table.crops {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 6px;
}

table.crops td {
	border-width: 1px;
	padding: 2px;
	border-style: outset;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}

table.builds {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 12px;
}

table.builds td {
	border-width: 1px;
	padding: 2px;
	border-style: dashed;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}

table.neighs {
	border-width: thin;
	border-spacing: 0px;
	border-style: dotted;
	border-color: gray;
	border-collapse: collapse;
	font-size: 12px;
}

table.neighs td {
	border-width: 1px;
	padding: 4px;
	border-style: dashed;
	border-color: gray;
	border-collapse: collapse;
	-moz-border-radius: 0px 0px 0px 0px;
}
</style>
<link rel="stylesheet" type="text/css" href="/plugins/fvNeighbors/js/grid/gt_grid.css" />
<link rel="stylesheet" type="text/css" href="css/index.css" />
<script src="/plugins/fvNeighbors/js/grid/gt_msg_en.js"></script>
<script src="/plugins/fvNeighbors/js/grid/gt_const.js"></script>
<script src="/plugins/fvNeighbors/js/grid/gt_grid_all.js"></script>
<script type="text/javascript">
var data1= [
<?php 
		$nbors = $fsM->fnGetNeighbors();
		foreach ($nbors as $nbor) 
		{
			$lastseen = date("m/d/y, g:i a", $nbor['neighbors_lastseen']);
			$lastseen = (empty($lastseen)) ? 'Not Seen' : $lastseen;
			$lastupdate = date("m/d/y, g:i a", $nbor['neighbors_timestamp']);
			$lastupdate = (empty($lastupdate)) ? 'Not Updated' : $lastupdate;
			$farmsize = $nbor['neighbors_sizeX'] . 'x' . $nbor['neighbors_sizeY'];
			$nbor['neighbors_name'] = fBGetNeighborRealName($nbor['neighbors_fbid']);
			$nbor['neighbors_name'] = empty($nbor['neighbors_name']) ? 'Unknown' : $nbor['neighbors_name'];
			$nbor['neighbors_plots'] = ($nbor['neighbors_plots'] < 1) ? 0 : $nbor['neighbors_plots'];
			$narray[] =  '{fbid:"' . $nbor['neighbors_fbid'] . '",' . 'name:"' . $nbor['neighbors_name'] . '",lastseen:"' . $lastseen . 
				'", farmsize:"' . $farmsize . '",' . 
				'objects:' . $nbor['neighbors_objects'] . ',lastupdate:"' . $lastupdate .'"}';
		}
		@$narray2 = implode(",\n", $narray);
		echo $narray2;	
?>
];

var dsOption= {
    fields :[
        {name : "fbid" },
        {name : "name" },
        {name : "lastseen", type: 'date' },
        {name : 'farmsize' },
        {name : 'objects' ,type: 'float' },
        {name : 'lastupdate' ,type: 'date'  }    
    ],
    recordType : 'object',
             data: data1
};

var colsOption= [
	 {id: 'chk' ,isCheckColumn : true, filterable: false, exportable:false },                 
     {id: 'fbid' , header: "Facebook ID" , width :100 },
     {id: 'name' , header: "Name" , width :120 },
     {id: 'lastseen' , header: "Last Seen" , width :120 },
     {id: 'farmsize' , header: "Farm Size" , width :65 },
     {id: 'objects' , header: "Objects on Farm" , width :110 },
     {id: 'lastupdate' , header: "Updated" , width :120 }
];

var gridOption={
    id : "grid1",
    container : 'grid1_container',
    dataset : dsOption ,
    columns : colsOption,
    replaceContainer : false,
    pageSizeList : [5,10,15,20,50,100,300],
    selectRowByCheck : true,
    exportFileName : 'neighbor-list',
    exportURL : 'index.php?userId=<?php echo $_SESSION['userId']; ?>&export=1',
    saveURL : 'index.php?userId=<?php echo $_SESSION['userId']; ?>&save=1',
    remotePaging : false,
    defaultRecord : ["","","2008-01-01",0,0,0,"",0,0,0,0],
	pageSize : 50,
	resizable : true,
	toolbarContent : 'nav goto | pagesize | reload | del save | print csv xls filter | state'

};


var mygrid = new Sigma.Grid(gridOption);
Sigma.Util.onLoad( Sigma.Grid.render(mygrid));
</script>
<script type="text/javascript">
function showhide(id){
if (document.getElementById){
obj = document.getElementById(id);
if (obj.style.display == "none"){
obj.style.display = "";
} else {
obj.style.display = "none";
}
}
}
</script> 
</head>
<body>
<?php fBAcctHeader(); ?>
<h1>fvNeighbors V<?php echo fvNeighbors_version; ?></h1>
<p><a href="#" onclick="showhide('settings');">Show/Hide Settings</a></p>
<div id="settings" style="display: none;">
<form id="settings" method="post">
<?php 
@$ahelpchk = ($fsM->settings['accepthelp'] == 1) ? 'checked' : ''; 
@$delpendchk = ($fsM->settings['delpending'] == 1) ? 'checked' : ''; 
@$htreeschk = ($fsM->settings['htrees'] == 1) ? 'checked' : ''; 
@$hanimalschk = ($fsM->settings['hanimals'] == 1) ? 'checked' : ''; 
@$pplotschk = ($fsM->settings['pplots'] == 1) ? 'checked' : '';
@$fplotschk = ($fsM->settings['fplots'] == 1) ? 'checked' : '';
@$ucropschk = ($fsM->settings['ucrops'] == 1) ? 'checked' : '';
@$fchickenschk = ($fsM->settings['fchickens'] == 1) ? 'checked' : ''; 
@$hcandychk = ($fsM->settings['hcandy'] == 1) ? 'checked' : ''; 
@$ftroughchk = ($fsM->settings['ftrough'] == 1) ? 'checked' : ''; 
@$fpigpenchk = ($fsM->settings['fpigpen'] == 1) ? 'checked' : ''; 
@$hval2011chk = ($fsM->settings['hval2011'] == 1) ? 'checked' : ''; 
@$hgreenhousechk = ($fsM->settings['hgreenhouse'] == 1) ? 'checked' : ''; 
@$vneighborsnchk = ($fsM->settings['vneighborsn'] == 1) ? 'checked' : '';
@$domissionschk = ($fsM->settings['domissions'] == 1) ? 'checked' : ''; 

?>
<small>
<input type="checkbox" name="accepthelp" value="accepthelp" <?php echo $ahelpchk; ?> />Accept Neighbors Help<br />
<input type="checkbox" name="delpending" value="delpending" <?php echo $delpendchk; ?> />Cancel Pending Neighbor Requests <i>(Warning: Do not invite new neighbors while this is checked)</i><br />
Number of Neighbors to Help/Update Per Cycle: <input type="text" name="helpcycle" size="4" value="<?php echo $fsM->settings['helpcycle']; ?>" />&nbsp;
Help/Update Neighbors every <input type="text" name="helptime" size="4" value="<?php echo $fsM->settings['helptime']; ?>" /> hours.<br />
<b><i>Help Neighbors do the Following:</i></b><br />
<input type="checkbox" name="htrees" value="htrees" <?php echo $htreeschk; ?> />Harvest Trees&nbsp;
<input type="checkbox" name="hanimals" value="hanimals" <?php echo $hanimalschk; ?> />Harvest Animals&nbsp;
<input type="checkbox" name="pplots" value="pplots" <?php echo $pplotschk; ?> />Plow Plots&nbsp;
<input type="checkbox" name="fplots" value="fplots" <?php echo $fplotschk; ?> />Fertilize Plots&nbsp;
<input type="checkbox" name="ucrops" value="ucrops" <?php echo $ucropschk; ?> />Unwither Crops&nbsp;
<input type="checkbox" name="fchickens" value="fchickens" <?php echo $fchickenschk; ?> />Feed Chickens<br />
<input type="checkbox" name="hcandy" value="hcandy" <?php echo $hcandychk; ?> />Harvest Candy&nbsp;
<input type="checkbox" name="hval2011" value="hval2011" <?php echo $hval2011chk; ?> />Harvest Cupids Castle&nbsp;
<input type="checkbox" name="hgreenhouse" value="hgreenhouse" <?php echo $hgreenhousechk; ?> />Harvest Greenhouse&nbsp;
<input type="checkbox" name="ftrough" value="ftrough" <?php echo $ftroughchk; ?> />Feed Animal Trough&nbsp;
<input type="checkbox" name="fpigpen" value="fpigpen" <?php echo $fpigpenchk; ?> />Feed Pig Pen<br />
<input type="checkbox" name="domissions" value="domissions" <?php echo $domissionschk; ?> />Do Missions&nbsp;
<!--  <input type="checkbox" name="vneighborsn" value="vneighborsn" <?php echo $vneighborsnchk; ?> />Visit Neighbors Neighbors<br /-->
<input type="submit" name="submit" value="Save Settings" />
</small>
</form>
</div>
<!-- <b>Neighbor Neighbors:</b> <?php echo $NNcount; ?><br /> -->
<div id="grid1_container" style="width: 100%; height: 500px"></div>
</body>
</html>
<?php
unset($fsM);

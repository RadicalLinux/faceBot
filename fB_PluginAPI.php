<?php
//session_start();
error_reporting(0);
ini_set('display_errors', false);
$timezonefile = '../fBSettings/timezone.txt';
if (file_exists($timezonefile)) {
	$timezone = trim(file_get_contents($timezonefile));
	if (strlen($timezone) > 2) {
		date_default_timezone_set($timezone);
	}
} else {
	@date_default_timezone_set('America/Chicago');
}

$base_path = getcwd();
$pos1 = strpos($base_path,'\\');
$pos2 = strpos($base_path,'\\', $pos1 + 1);
$_SESSION['base_path'] = substr($base_path, 0, $pos2 + 1);
$_SESSION['this_plugin']['folder'] = getcwd();
define('FB_PARSER_VER', file_get_contents($_SESSION['base_path'] . 'parser.ver'));
define('PARSER_MAX_SPEED', '8');
define('PARSER_SQLITE', $_SESSION['base_path'] . 'data.sqlite');
define('DATASTORE_SQLITE', $_SESSION['base_path'] . 'datastore.sqlite');
define('REWARDSTORE_SQLITE', $_SESSION['base_path'] . 'rewardstore.sqlite');
require_once('fB_DB.php');
require_once('fB_AMF.php');
require_once('fB_Utils.php');
function fBAcctHeader()
{
	$userinfo = fBGetUserInfo();
	?>

<style>
BODY {
	margin: 60px 0px 0px 0px;
	padding: 0;
}

div.fBacctBar {
	background-color:#1E3F82 ; color: #FFFFFF;
	border: 2px solid;
	border-color: #f0f0f0 #909090 #909090 #f0f0f0;
	padding: 0px 0px 0px 0px;
	margins: 0px 0px 0px 0px;
	text-align: left;
	position: absolute;
	z-index:99;
}

table.fBacctBar,td.fBacctBar,tr.fBacctBar,form.fBacctBar,input.fBacctBar
	{
	font-family: Verdana, Times New Roman, Times, serif;
	font-size: 10pt;
	font-style: normal;
	color: #FFFFFF;
	font-weight:bold;
}

select.fBacctBar
{
	font-family: Verdana, Times New Roman, Times, serif;
	font-size: 10pt;
	font-style: normal;
	color: black;
	background-color: white;
}

.buttonBlue{color: #FFFFFF; padding-left:10px;padding-right:10px;}

<?php
if(isSet($_SESSION["browser"])&&$_SESSION["browser"]) echo "#staticcontent{display:none}";
if(isSet($_SESSION["browser"])&&$_SESSION["browser"]) echo "body{margin:0}";
?>
</style>

<div id="staticcontent" class="fBacctBar">
<table border=0 style="margin: 0; padding: 0;" class="fBacctBar">
	<tr class="fBacctBar">
		<td valign="middle" class="fBacctBar"><img src="/images/faceBot48.png" align="left"></td>
		<td valign="middle" class="fBacctBar">
		<form class="fBacctBar" name="fBAcctForm" method="GET"
			style="margin-left: 10px; margin-bottom: 0px;">Select Account:&nbsp;
		<select class="fBacctBar" name="userId" id="userId" onchange="this.form.submit()">
		<?php
		foreach ($userinfo as $key=>$info)
		{
			$SELECTED = "";
			if ($key == $_SESSION['userId']) $SELECTED = 'selected="selected"';
			echo '<option value="' . $key . '"' . $SELECTED . '>' . $key . ' - ' . $info . '</option>';
		}
		?>
		</select></form>
		</td>
		<td class="buttonBlue">
			<input type="button" onClick="location.href='index.php?userId=<?php echo $_SESSION['userId']; ?>'"value='Refresh Page'>
		</td>
	</tr>
</table>
</div>
<script type="text/javascript">
//define universal reference to "staticcontent"
var crossobj=document.all? document.all.staticcontent : document.getElementById("staticcontent");

//define reference to the body object in IE
var iebody=(document.compatMode && document.compatMode != "BackCompat")? document.documentElement : document.body;

function positionit(){
//define universal dsoc left point
var dsocleft=document.all? iebody.scrollLeft : pageXOffset;
//define universal dsoc top point
var dsoctop=document.all? iebody.scrollTop : pageYOffset;

//if the user is using IE 4+ or Firefox/ NS6+
if (document.all||document.getElementById){
crossobj.style.right=parseInt(dsocleft)+0+"px";
crossobj.style.top=dsoctop+0+"px";
}
}
<?php if(!(isSet($_SESSION["browser"])&&$_SESSION["browser"])) echo 'setInterval("positionit()",100);'; ?>
</script>
		<?php
}
if (isset($_GET['userId']) && is_numeric($_GET['userId'])) {
	$_SESSION['userId'] = $_GET['userId'];
} else {
	$dUserInfo = fBGetDefUser();
	if (!isset($dUserInfo)) {
		Echo '<b>No User Accounts have Ran Yet - Please Allow the Bot to Run</b>';
		exit;
	} else {
		$_SESSION['userId'] = $dUserInfo;
	}
}
list(, , , , , , , , , , , $_SESSION['flashRevision']) = explode(';', fBGetDataStore('playerinfo'));

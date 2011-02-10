<?php
require_once '../../fB_PluginAPI.php';
define('fBGifts_version', file_get_contents('fBGifts.ver'));
define('fBGifts_date', '2 November 2010');
define('fBGifts_Path', getcwd() . '\\');
// file definitions
define('fBGifts_Main', 'fBGifts_main.sqlite');
/******************fBGifts by RadicalLinux****************************/
include 'includes/fBGifts.class.php';
	$fBG = new fBGifts('formload');

	if (isset($_POST['submit']) && $_POST['submit'] == 'Save Settings')
	{
		$fBG->fBDoSettings($_POST);
		header("Location: index.php?userId=" . $_SESSION['userId']);	
	}
	if (isset($_POST['submit']) && $_POST['submit'] == 'Clear Log')
	{
		$fBG->fBDeleteLog();
		header("Location: index.php?userId=" . $_SESSION['userId']);	
	}
	
	$fBG->settings = $fBG->fBGetSettings();
	$kApps = $fBG->fBGetKnownApps();
	@$appSettings = unserialize($fBG->settings['giftopts']);
	$giftLogs = $fBG->fBGetLogs();
	?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="/plugins/fBGifts/css/index.css" />
<script src="/plugins/fBGifts/js/fBGifts.js"></script>
<script src="/plugins/fBGifts/js/tabber.js"></script>
<script type="text/javascript">
<!--
/* http://www.alistapart.com/articles/zebratables/ */
function removeClassName (elem, className) {
	elem.className = elem.className.replace(className, "").trim();
}

function addCSSClass (elem, className) {
	removeClassName (elem, className);
	elem.className = (elem.className + " " + className).trim();
}

String.prototype.trim = function() {
	return this.replace( /^\s+|\s+$/, "" );
}

function stripedTable() {
	if (document.getElementById && document.getElementsByTagName) {  
		var allTables = document.getElementsByTagName('table');
		if (!allTables) { return; }

		for (var i = 0; i < allTables.length; i++) {
			if (allTables[i].className.match(/[\w\s ]*scrollTable[\w\s ]*/)) {
				var trs = allTables[i].getElementsByTagName("tr");
				for (var j = 0; j < trs.length; j++) {
					removeClassName(trs[j], 'alternateRow');
					addCSSClass(trs[j], 'normalRow');
				}
				for (var k = 0; k < trs.length; k += 2) {
					removeClassName(trs[k], 'normalRow');
					addCSSClass(trs[k], 'alternateRow');
				}
			}
		}
	}
}

/* onload state is fired, append onclick action to the table's DIV */
/* container. This allows the HTML document to validate correctly. */
/* addIEonScroll added on 2005-01-28                               */
/* Terence Ordona, portal[AT]imaputz[DOT]com                       */
function addIEonScroll() {
	var thisContainer = document.getElementById('tableContainer');
	if (!thisContainer) { return; }

	var onClickAction = 'toggleSelectBoxes();';
	thisContainer.onscroll = new Function(onClickAction);
}

/* Only WinIE will fire this function. All other browsers scroll the TBODY element and not the DIV */
/* This is to hide the SELECT elements from scrolling over the fixed Header. WinIE only.           */
/* toggleSelectBoxes added on 2005-01-28 */
/* Terence Ordona, portal[AT]imaputz[DOT]com         */
function toggleSelectBoxes() {
	var thisContainer = document.getElementById('tableContainer');
	var thisHeader = document.getElementById('fixedHeader');
	if (!thisContainer || !thisHeader) { return; }

	var selectBoxes = thisContainer.getElementsByTagName('select');
	if (!selectBoxes) { return; }

	for (var i = 0; i < selectBoxes.length; i++) {
		if (thisContainer.scrollTop >= eval(selectBoxes[i].parentNode.offsetTop - thisHeader.offsetHeight)) {
			selectBoxes[i].style.visibility = 'hidden';
		} else {
			selectBoxes[i].style.visibility = 'visible';
		}
	}
} 


-->
</script>
<style type="text/css">
<!--
/* define height and width of scrollable area. Add 16px to width for scrollbar          */
/* allow WinIE to scale 100% width of browser by not defining a width                   */
/* WARNING: applying a background here may cause problems with scrolling in WinIE 5.x   */
div.tableContainer {
	clear: both;
	border: 1px solid #1E3F82;
	height: 640px;
	overflow: auto;
	/*width: 756px;*/
}

/* WinIE 6.x needs to re-account for it's scrollbar. Give it some padding */
\html div.tableContainer/* */ {
	padding: 0 16px 0 0;
	width: 100%;
}

/* clean up for allowing display Opera 5.x/6.x and MacIE 5.x */
html>body div.tableContainer {
	height: auto;
	padding: 0;
}

/* Reset overflow value to hidden for all non-IE browsers. */
/* Filter out Opera 5.x/6.x and MacIE 5.x                  */
head:first-child+body div[class].tableContainer {
	height: 285px;
	overflow: hidden;
	width: 100%;
}

/* define width of table. IE browsers only                 */
/* if width is set to 100%, you can remove the width       */
/* property from div.tableContainer and have the div scale */
div.tableContainer table {
	float: left;
	width: 100%
}

/* WinIE 6.x needs to re-account for padding. Give it a negative margin */
\html div.tableContainer table/* */ {
	margin: 0 -16px 0 0
}

/* define width of table. Opera 5.x/6.x and MacIE 5.x */
html>body div.tableContainer table {
	float: none;
	margin: 0;
	width: 100%;
}

/* define width of table. Add 16px to width for scrollbar.           */
/* All other non-IE browsers. Filter out Opera 5.x/6.x and MacIE 5.x */
head:first-child+body div[class].tableContainer table {
	width: 100%;
}

/* set table header to a fixed position. WinIE 6.x only                                       */
/* In WinIE 6.x, any element with a position property set to relative and is a child of       */
/* an element that has an overflow property set, the relative value translates into fixed.    */
/* Ex: parent element DIV with a class of tableContainer has an overflow property set to auto */
thead.fixedHeader tr {
	position: relative;
	/* expression is for WinIE 5.x only. Remove to validate and for pure CSS solution      */
	top: expression(document.getElementById("tableContainer").scrollTop);
}

/* set THEAD element to have block level attributes. All other non-IE browsers            */
/* this enables overflow to work on TBODY element. All other non-IE, non-Mozilla browsers */
/* Filter out Opera 5.x/6.x and MacIE 5.x                                                 */
head:first-child+body thead[class].fixedHeader tr {
	display: block;
}

/* make the TH elements pretty */
thead.fixedHeader th {
	background: #1E3F82;
	border-left: 1px solid #EB8;
	border-right: 1px solid #B74;
	border-top: 1px solid #EB8;
	font-weight: normal;
	padding: 4px 3px;
	text-align: left
}

/* make the A elements pretty. makes for nice clickable headers                */
thead.fixedHeader a, thead.fixedHeader a:link, thead.fixedHeader a:visited {
	color: #FFF;
	display: block;
	text-decoration: none;
	width: 100%
}

/* make the A elements pretty. makes for nice clickable headers                */
/* WARNING: swapping the background on hover may cause problems in WinIE 6.x   */
thead.fixedHeader a:hover {
	color: #FFF;
	display: block;
	text-decoration: underline;
	width: 100%
}

/* define the table content to be scrollable                                              */
/* set TBODY element to have block level attributes. All other non-IE browsers            */
/* this enables overflow to work on TBODY element. All other non-IE, non-Mozilla browsers */
/* induced side effect is that child TDs no longer accept width: auto                     */
/* Filter out Opera 5.x/6.x and MacIE 5.x                                                 */
head:first-child+body tbody[class].scrollContent {
	display: block;
	height: 262px;
	overflow: auto;
	width: 100%
}

/* make TD elements pretty. Provide alternating classes for striping the table */
/* http://www.alistapart.com/articles/zebratables/                             */
tbody.scrollContent td, tbody.scrollContent tr.normalRow td {
	background: #FFF;
	border-bottom: none;
	border-left: none;
	border-right: 1px solid #CCC;
	border-top: 1px solid #DDD;
	padding: 2px 3px 3px 4px
}

tbody.scrollContent tr.alternateRow td {
	background: #EEE;
	border-bottom: none;
	border-left: none;
	border-right: 1px solid #CCC;
	border-top: 1px solid #DDD;
	padding: 2px 3px 3px 4px
}

/* define width of TH elements: 1st, 2nd, and 3rd respectively.      */
/* All other non-IE browsers. Filter out Opera 5.x/6.x and MacIE 5.x */
/* Add 16px to last TH for scrollbar padding                         */
/* http://www.w3.org/TR/REC-CSS2/selector.html#adjacent-selectors    */
head:first-child+body thead[class].fixedHeader th {
	width: 200px
}

head:first-child+body thead[class].fixedHeader th + th {
	width: 240px
}

head:first-child+body thead[class].fixedHeader th + th + th {
	border-right: none;
	padding: 4px 4px 4px 3px;
	width: 300px
}

head:first-child+body thead[class].fixedHeader th + th + th + th {
	border-right: none;
	padding: 4px 4px 4px 3px;
	width: 316px
}

/* define width of TH elements: 1st, 2nd, and 3rd respectively.      */
/* All other non-IE browsers. Filter out Opera 5.x/6.x and MacIE 5.x */
/* Add 16px to last TH for scrollbar padding                         */
/* http://www.w3.org/TR/REC-CSS2/selector.html#adjacent-selectors    */
head:first-child+body tbody[class].scrollContent td {
	width: 200px
}

head:first-child+body tbody[class].scrollContent td + td {
	width: 240px
}

head:first-child+body tbody[class].scrollContent td + td + td {
	border-right: none;
	padding: 2px 4px 2px 3px;
	width: 300px
}

head:first-child+body tbody[class].scrollContent td + td + td + td {
	border-right: none;
	padding: 2px 4px 2px 3px;
	width: 300px
}
-->
</style>
</head>
<body>
<?php fBAcctHeader(); ?>
<h1>fBGifts V<?php echo fBGifts_version; ?></h1>
<div class="tabber"
	id="FarmStats"><!--Statistics Tab-->
<div class="tabbertab" id="fBGifs">
<h2>Available Gifts</h2>
<form id="settings" method="post">
<font color="red"><b>(Note: Games are added automatically based on what is on your gift page.  New games <u>do not</u> have the ability to return gifts.<br /> 
If you would like the ability to return the gifts, please post the following in the fBGifts thread on the forum: <br />
Application ID, Game Name, and a picture of the return thank you gift page.)</b></font>
<table border="1" class="mainTable" >
	<tr id="footer">
		<td align="center" colspan="3">
			<input type="submit" name="submit" value="Save Settings" />
		</td>
	</tr>
	<tr id="header">
	<td><b>Application</b></td>
	<td><b>Accept Gifts</b></td>
	<td><b>Return Gifts</b></td>
	</tr>
<?php foreach ($kApps as $apps)
{ ?>
	<tr>
	<td><?php echo $apps['knownapps_name'];?></td>
	<td align="center">
	<?php
		if ($apps['knownapps_canaccept'] == 1) {
			$checked = isset($appSettings[$apps['knownapps_appid'] . '_accept']) ? 'CHECKED' : '';
			 ?><input type="checkbox" name="<?php echo $apps['knownapps_appid']; ?>_accept" <?php echo $checked; ?>>
		<?php 
		}
	?>
	</td>
	<td align="center">
	<?php 
		if ($apps['knownapps_canreturn'] == 1) {
			$checked = isset($appSettings[$apps['knownapps_appid'] . '_return']) ? 'CHECKED' : '';
			 ?><input type="checkbox" name="<?php echo $apps['knownapps_appid']; ?>_return" <?php echo $checked; ?>>
		<?php 
		} else {
			echo '---';
		}
	?>
	</td>
	</tr>
<?php } ?>
<tr>
	<td id="footer" align="center" colspan="3">
		<input type="submit" name="submit" value="Save Settings" />
	</td>
</tr>
</table>
</form>
</div>
<div class="tabbertab" id="fBLogs">
<h2>Accept/Return Logs</h2>
<form method="post" id="clearlog">
	<input type="submit" name="submit" value="Clear Log" />
</form>
<div id="tableContainer" class="tableContainer">
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="scrollTable">
<thead class="fixedHeader" id="fixedHeader">
	<tr>
		<th><a href="#">Date</a></th>
		<th><a href="#">Application</a></th>
		<th><a href="#">Gift Text</a></th>
		<th><a href="#">Link</a></th>
		<th><a href="#">Accepted Gift</a></th>
		<th><a href="#">Returned Gift</a></th>
	</tr>
</thead>
<tbody class="scrollContent">
	<?php foreach ($giftLogs as $log) { 
		$accept = $log['giftlog_accept'] == 1 ? 'Yes' : 'No';
		$accept = $log['giftlog_accept'] == 9 ? 'Error' : $accept;
		$return = $log['giftlog_return'] == 1 ? 'Yes' : 'No';
		$return = $log['giftlog_return'] == 9 ? 'Error' : $return;		
	?>
	<tr>
		<td><small><?php echo date("m/d/y, g:i a", $log['giftlog_timestamp']); ?></small></td>
		<td><small><?php echo $log['giftlog_appname']; ?></small></td>
		<td><small><?php echo $log['giftlog_text']; ?></small></td>
		<td align="center"><small><a href="#" title="<?php echo $log['giftlog_link']; ?>">Link</a></small></td>
		<td align="center"><small><?php echo $accept; ?></small></td>
		<td align="center"><small><?php echo $return; ?></small></td>
	</tr>
	<?php } ?>
</tbody>
</table>
</div>

</div>
</div>
</body>
</html>
<?php
unset($fBG);

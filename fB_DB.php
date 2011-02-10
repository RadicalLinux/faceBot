<?php
$_SESSION['vDataDB'] = Parser_SQlite_Connect(PARSER_SQLITE);
$_SESSION['vDataStoreDB'] = Parser_SQlite_Connect(DATASTORE_SQLITE);
$_SESSION['vRewardStoreDB'] = Parser_SQlite_Connect(REWARDSTORE_SQLITE);
// ------------------------------------------------------------------------------
// Units_GetUnitByName get unit by Name
// ------------------------------------------------------------------------------
function Units_GetUnitByName($vName, $vAllInfo = false)
{
	if ($vAllInfo)
	{
		$vSQL = 'select * from units where name="' . $vName . '"';
	}
	else
	{
		$vSQL = "select * from units where field in ('name','type','code','buyable','className','iconurl','market','cash','cost','subtype','growTime','coinYield','action','limitedEnd','requiredLevel','crop','sizeX','sizeY','plantXp','masterymax','license','realname','desc') and name='$vName'";
	}
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = $vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['field']] = $vRow['content'];
	}
	return(@$vReturn);
}
// ------------------------------------------------------------------------------
// Units_GetUnitByCode get unit by Name
// ------------------------------------------------------------------------------
function Units_GetUnitByCode($vCode, $vAllInfo = false)
{
	return(Units_GetUnitByName(Units_GetNameByCode($vCode), $vAllInfo));
}
// ------------------------------------------------------------------------------
// Units_GetRealnameByName
// ------------------------------------------------------------------------------
function Units_GetRealnameByName($vName)
{
	$vSQL = 'select content from units where name="' . $vName . '" and field="realname"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vName:$vResult);
}

// ------------------------------------------------------------------------------
// Units_GetNameByRealname
// ------------------------------------------------------------------------------
function Units_GetNameByRealname($vRealName)
{
	$vSQL = 'select name from units where content="' . $vName . '" and field="realname"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vName:$vResult);
}
// ------------------------------------------------------------------------------
// Units_GetCodeByName
// ------------------------------------------------------------------------------
function Units_GetCodeByName($vName)
{
	$vSQL = 'select content from units where name="' . $vName . '" and field="code"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vName:$vResult);
}
// ------------------------------------------------------------------------------
// Units_GetNameByCode
// ------------------------------------------------------------------------------
function Units_GetNameByCode($vCode)
{
	$vSQL = 'select name from units where content="' . $vCode . '" and field="code"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vCode:$vResult);
}
// ------------------------------------------------------------------------------
// Units_GetRealnameByCode
// ------------------------------------------------------------------------------
function Units_GetRealnameByCode($vCode)
{
	$vSQL = 'select content from units where field="realname" and name in (select name from units where content="' . $vCode . '" and field="code")';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vCode:$vResult);
}
// ------------------------------------------------------------------------------
// Units_GetAll get all units
// ------------------------------------------------------------------------------
function Units_GetAll($vAllInfo = false)
{
	if ($vAllInfo)
	{
		$vSQL = 'select * from units';
	}
	else
	{
		$vSQL = "select * from units where field in ('name','type','code','buyable','className','iconurl','market','cash','cost','subtype','growTime','coinYield','action','limitedEnd','requiredLevel','crop','sizeX','sizeY','plantXp','masterymax','license','realname','desc')";
	}
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return($vReturn);
}
// ------------------------------------------------------------------------------
// Units_GetByType get all units of type $vType
// ------------------------------------------------------------------------------
function Units_GetByType($vType, $vAllInfo = false)
{
	if ($vAllInfo)
	{
		$vSQL = 'select * from units where name in (select name from units where field="type" and content="' . $vType . '")';
	}
	else
	{
		$vSQL = "select * from units where field in ('name','type','code','buyable','class','iconurl','market','cash','cost','subtype','growTime','coinYield','action','limitedEnd','requiredLevel','crop','sizeX','sizeY','plantXp','masterymax','license','realname','desc') and name in (select name from units where field='type' and content='" . $vType . "')";
	}
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetByType get all units of type $vType
// ------------------------------------------------------------------------------
function Units_GetByField($vField, $vType, $vAllInfo = false)
{
	if ($vAllInfo)
	{
		$vSQL = 'select * from units where name in (select name from units where field="' . $vField . '" and content="' . $vType . '")';
	}
	else
	{
		$vSQL = "select * from units where field in ('name','type','code','buyable','class','iconurl','market','cash','cost','subtype','growTime','coinYield','action','limitedEnd','requiredLevel','crop','sizeX','sizeY','plantXp','masterymax','license','realname','desc') and name in (select name from units where field='" . $vField . "' and content='" . $vType . "')";
	}
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetRealnameByField
// ------------------------------------------------------------------------------
function Units_GetRealnameByField($vData, $vField)
{
	$vSQL = 'select content from units where field="realname" and name in (select name from units where content="' . $vData . '" and field="' . $vField . '")';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vCode:$vResult);
}

// ------------------------------------------------------------------------------
// Units_GetByClass get all units of type $vType
// ------------------------------------------------------------------------------
function Units_GetByClass($vClass, $vAllInfo = false)
{
	if ($vAllInfo)
	{
		$vSQL = 'select * from units where name in (select name from units where field="className" and content="' . $vClass . '")';
	}
	else
	{
		$vSQL = "select * from units where field in ('name','type','code','buyable','class','iconurl','market','cash','cost','subtype','growTime','coinYield','action','limitedEnd','requiredLevel','crop','sizeX','sizeY','plantXp','masterymax','license','realname','desc') and name in (select name from units where field='className' and content='" . $vClass . "')";
	}
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return($vReturn);
}


// ------------------------------------------------------------------------------
// Crafts_GetByCode get all crafts of code $vCode
// ------------------------------------------------------------------------------
function Crafts_GetByCode($vCode)
{
	$vSQL = "select * from crafting where name in (select name from crafting where field='id' and content='" . $vCode . "')";
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		if (isset($vReturn[$vRow['field']])) {
			$vReturn[$vRow['field']] = $vReturn[$vRow['field']] . ':' . $vRow['content'];
		} else {
			$vReturn[$vRow['field']] = $vRow['content'];
		}
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Storage_GetByName get all storage of code $vName
// ------------------------------------------------------------------------------
function Storage_GetByName($vName)
{
	$vSQL = "select * from storage where name in (select name from storage where field='name' and content='" . $vName . "')";
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		if (isset($vReturn[$vRow['field']])) {
			$vReturn[$vRow['field']] = $vReturn[$vRow['field']] . ':' . $vRow['content'];
		} else {
			$vReturn[$vRow['field']] = $vRow['content'];
		}
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Crafts_GetByCode get all crafts of code $vCode
// ------------------------------------------------------------------------------
function Quests_GetByCode($vCode)
{
	$vSQL = "select * from quests where name in (select name from quests where field='id' and content='" . $vCode . "')";
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		if (isset($vReturn[$vRow['field']])) {
			$vReturn[$vRow['field']] = $vReturn[$vRow['field']] . ':' . $vRow['content'];
		} else {
			$vReturn[$vRow['field']] = $vRow['content'];
		}
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Quests_GetAll get all quests
// ------------------------------------------------------------------------------
function Quests_GetAll()
{
	$vSQL = 'select * from quests';
	$vResult = @$_SESSION['vDataDB']->query($vSQL);
	while ($vRow = @$vResult->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return($vReturn);
}
// ------------------------------------------------------------------------------
// Quests_GetRealnameByTitle
// ------------------------------------------------------------------------------
function Quests_GetRealnameByTitle($vField)
{
	$vSQL = 'select content from quests where field="realname" and name ="' . $vField . '"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vCode:$vResult);
}
// ------------------------------------------------------------------------------n
// Units_GetCodeByName
// ------------------------------------------------------------------------------
function Units_GetFarming($vField)
{
	$vSQL = 'select content from units where name="_farming" and field="' . $vField . '"';
	$vResult = $_SESSION['vDataDB']->querySingle($vSQL);
	return($vResult === false?$vField:$vResult);
}
// ------------------------------------------------------------------------------
// Units_GetUnitByName get unit by Name
// ------------------------------------------------------------------------------
function Units_IsConsumableByName($vName)
{
	$vSQL = 'select count(*) from units where name="' . $vName . '" and content="consumable" and field in ("type","subtype")';
	$vResult = @$_SESSION['vDataDB']->querySingle($vSQL);
	return(@$vResult === false?false:true);

}
// ------------------------------------------------------------------------------
// Parser_SQlite_Connect
// ------------------------------------------------------------------------------
function Parser_SQlite_Connect($vDBFile)
{
	$vDB = new SQLite3($vDBFile);
	if (!$vDB)
	{
		AddLog2('Parser SQlite Error: cant open ' . $vDBFile);
		return(false);
	}
	$vDB->exec('PRAGMA cache_size=20000');
	$vDB->exec('PRAGMA synchronous=OFF');
	$vDB->exec('PRAGMA count_changes=OFF');
	$vDB->exec('PRAGMA journal_mode=MEMORY');
	$vDB->exec('PRAGMA temp_store=MEMORY');
	$vDB->busyTimeout(10000);
	return $vDB;
}
// ------------------------------------------------------------------------------
// Units_GetByClass get all units of type $vType
// ------------------------------------------------------------------------------
function fBGetUserInfo()
{
	$vSQL = 'SELECT * FROM userids';
	$results = @$_SESSION['vDataStoreDB']->query($vSQL);
	while ($vRow = @$results->fetchArray(SQLITE3_ASSOC))
	{
		$vReturn[$vRow['userid']] = $vRow['username'];
	}
	return($vReturn);
}

function fBGetDefUser()
{
	$vSQL = 'SELECT * FROM userids LIMIT 1';
	$fresult = @$_SESSION['vDataStoreDB']->querySingle($vSQL);
	return($fresult);
}

function fBGetNeighborRealName($uid)
{
	$vSQL = "SELECT fullname FROM neighbors WHERE neighborid='" . $uid . "' LIMIT 1";
	$fresult = @$_SESSION['vDataStoreDB']->querySingle($vSQL);
	return($fresult=== false?false:$fresult);
}

function fBGetDataStore($storetype)
{
	$vSQL = "SELECT content FROM datastore WHERE userid='" . $_SESSION['userId'] . "' AND storetype='$storetype'";
	$fresult = @$_SESSION['vDataStoreDB']->querySingle($vSQL);
	return($fresult);
}

function fBGetRewardStore($storetype)
{
	$vSQL = "SELECT content FROM rewardstore WHERE userid='" . $_SESSION['userId'] . "' AND storetype='$storetype'";
	$fresult = $_SESSION['vRewardStoreDB']->querySingle($vSQL);
	return($fresult);
}
?>
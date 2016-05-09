<?php
/*
=== DATABASE RELATED FUNCTIONS - MYSQL ===

© Copyright 2009-2016 LuxSoft - www.LuxSoft.eu

This file is part of the LuxCal Web Calendar.
*/

//Current LuxCal version
define("LCV","4.4.0M");

//Log message
function logError($logName,$logMsg,$newMsg,$script='-') {
	date_default_timezone_set(@date_default_timezone_get());
	if ($script == '-') { $script = $_SERVER['PHP_SELF']; }
	file_put_contents("./logs/{$logName}.log", ($newMsg ? "\n".date('Y.m.d H:i:s')." Script: ".htmlentities($script) : '')." - {$logMsg}", FILE_APPEND);
}

//Connect to database
function dbConnect($calID,$exitOnError=1) {
	global $dbHost, $dbUnam, $dbPwrd, $dbName;
	
	try {
		$dbH = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8",$dbUnam,$dbPwrd);
		$dbH->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$stH = $dbH->query("SHOW TABLES LIKE '{$calID}\_%'");
		if ($exitOnError and !$row = $stH->fetch(PDO::FETCH_NUM)) {
			exit("Calendar '{$calID}' not found.");
		}
	}
	catch(PDOException $e) {
		if ($exitOnError) {
			logError('sql',"Database connection error: ".$e->getMessage(),true);
			exit("Could not connect to the calendar database. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $dbH; //return db handle
}

//Query database
function dbQuery($query,$logError=1) {
global $dbH, $calID;

	$query = preg_replace("~(\s`)(events|groups|users|categories|settings)~","$1{$calID}_$2",$query); //use calendar with calID
	try {
		$stH = $dbH->query($query);
	}
	catch (PDOException $e) {
		if ($logError) {
			logError('sql',"SQL query error: ".$e->getMessage()."\nQuery string: {$query}",true);
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $stH; //result statement handle
}

//Begin / commit transaction
function dbTransaction($action,$logError=1) {
global $dbH;

	try {
		switch ($action[0]) {
			case 'b': $result = $dbH->beginTransaction(); break;
			case 'c': $result = $dbH->commit(); break;
			case 'r': $result = $dbH->rollBack();
		}
	}
	catch (PDOException $e) {
		if ($logError) {
			logError('sql',"SQL transaction error: ".$e->getMessage()."\nQuery: {$action} transaction",true);
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $result;
}

//Get last inserted row ID
function dbLastRowId() {
global $dbH;

	return $dbH->lastInsertId();
}

//Prepare SQL statement 
function stPrep($query,$logError=1) {
global $dbH, $calID;

	$query = preg_replace("~(\s`)(events|groups|users|categories|settings)~","$1{$calID}_$2",$query); //use calendar with calID
	try {
		$stH = $dbH->prepare($query);
	}
	catch (PDOException $e) {
		if ($logError) {
			logError('sql',"SQL prepare error: ".$e->getMessage()."\nQuery string: {$query}",true);
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $stH; //successful
}

//Execute prepared statement 
function stExec($stH,$values,$logError=1) {
	try {
		$result = $stH->execute(!empty($values) ? $values : array());
	}
	catch (PDOException $e) {
		if ($logError) {
			logError('sql',"SQL execute error: ".$e->getMessage()."\nValues string: ".implode(',',$values),true);
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $result; //successful
}

function getTableSql($table) { //get SQL code to create table
	$stH = dbQuery("SHOW CREATE TABLE `{$table}`");
	$sqlCode = $stH->fetch(PDO::FETCH_NUM);
	$stH = null; //release statement handle!
	return $sqlCode[1];
}

function getTables($like='%') { //get array with db tables
	global $calID;
	
	$tables = array();
	$stH = dbQuery("SHOW TABLES LIKE '{$calID}\_{$like}'"); //get table names
	while ($row = $stH->fetch(PDO::FETCH_NUM)) {
		$tables[] = $row[0]; //add table name
	}
	$stH = null; //release statement handle!
	return $tables;
}

function getCIDs() { //get array with installed calendar IDs
	$cals = array();
	$stH = dbQuery("SHOW TABLES LIKE '%\_settings'"); //get table names
	while ($row = $stH->fetch(PDO::FETCH_NUM)) {
		$cals[] = substr($row[0],0,-9); //add calendar ID (table prefix)
	}
	$stH = null; //release statement handle!
	return $cals;
}

function getCals() { //get array with installed calendar ID/title pairs
	global $dbH;

	$curCals = array();
	if (isset($dbH)) { //connected to db
		$cals = getCIDs(); //get calendar IDs
		foreach ($cals as $id) {
			if ($stH = dbQuery("SELECT `value` FROM {$id}_settings WHERE `name` = 'calendarTitle'",0)) {
				$row = $stH->fetch(PDO::FETCH_NUM);
				$stH = null; //release statement handle!
				if (!empty($row)) { //found
					$curCals[$id] = $row[0]; //add calendar title
				}
			}
		}
	}
	return $curCals;
}

//Get settings from database
function getSettings() {
	$set = array(); //init
	$stH = dbQuery("SELECT `name`,`value` FROM `settings`");
	if (!$stH) { return false; }
	while ($row = $stH->fetch(PDO::FETCH_ASSOC)) {
		$set[$row['name']] = is_numeric($row['value']) ? intval($row['value']) : $row['value'];
	}
	$stH = null; //release statement handle
	return $set; //array with settings
}
?>
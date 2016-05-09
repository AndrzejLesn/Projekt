<?php
/*
= LuxCal Detach Event Attachement =

© Copyright 2009-2016 LuxSoft - www.LuxSoft.eu

*/
chdir('..'); //change to calendar root
//load config data
require './lcconfig.php';

//get input params
$evtID = $_POST['evtID'];
$fName = $_POST['fName'];

//sanity check
if (empty($lcV) or
		!preg_match('%^\d{1,8}$%', $evtID) or
		!preg_match('%^\d{14}[^;]+\.\w{2,3}$%i', $fName)
	) { echo "0"; exit(); } //abort

//start session
session_name('PHPSESSID');
session_start();

//get calendar ID
if (empty($_SESSION['cal']) or empty($_SESSION['uid'])) { echo "0"; exit(); } //abort
$calID = $_SESSION['cal'];

//get db toolbox
require './common/toolboxd.php'; //database tools + LCV

//connect to db
$dbH = dbConnect($calID);

//get default timezone from settings
$set = getSettings();
date_default_timezone_set($set['timeZone']);

//get user name
$stH = stPrep("SELECT `name` FROM `users` WHERE `ID` = ?");
stExec($stH,array($_SESSION['uid']));
list($uname) = $stH->fetch(PDO::FETCH_NUM);

//get attachments
$stH = stPrep("
	SELECT `attach`
	FROM `events`
	WHERE `ID` = ?");
stExec($stH,array($evtID));
$row = $stH->fetch(PDO::FETCH_NUM);
$attachments = $row[0]; 
$stH = null;

//remove attachment
$attachments = str_replace(";{$fName}",'',$attachments); //remove

//update event
$stH = stPrep("UPDATE `events` SET `attach` = ?,`mDateTime` = ? WHERE `ID` = ?");
stExec($stH,array($attachments,date("Y-m-d H:i"),$evtID)); //update events table

//Delete file from folder attachments
if(file_exists("./attachments/{$fName}")) {
	unlink("./attachments/{$fName}");
}

echo '';
?>
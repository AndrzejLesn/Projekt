<?php
/*
= matrix view of events =

© Copyright 2009-2016 LuxSoft - www.LuxSoft.eu

This file is part of the LuxCal Web Calendar.

The LuxCal Web Calendar is free software: you can redistribute it and/or modify it under 
the terms of the GNU General Public License as published by the Free Software Foundation, 
either version 3 of the License, or (at your option) any later version.

The LuxCal Web Calendar is distributed in the hope that it will be useful, but WITHOUT 
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with the LuxCal 
Web Calendar. If not, see: http://www.gnu.org/licenses/.
*/

function showEvents($date,$cSeq) {
	global $evtList,$privs,$set,$xx;

	$showDetails = ($set['details4All'] == 1 or ($set['details4All'] == 2 and $_SESSION['uid'] > 1));
	foreach ($evtList[$date] as $evt) {
		if ($evt['seq'] != $cSeq) { continue; }
		$time = makeHovT($evt);
		$chBox = '';
		if ($evt['cbx']) {
			$chBox .= strpos($evt['chd'],$date) ? $evt['cmk'] : '&#x2610;';
			$chBox = "<span class='chkBox'>{$chBox}</span>";
		}
		if ($set['popBoxYear'] and $set['evtTemplPop']) {
			$popText = "<b>{$chBox} {$time} {$evt['tix']}</b><br>";
			if ($showDetails or $evt['mayE']) {
				$popText .= makeE($evt,$set['evtTemplPop'],'br','<br>');
			}
			$popText = htmlspecialchars(addslashes($popText));
			$popClass = ($evt['pri'] ? 'private' : 'normal').(($evt['mde'] or $evt['r_t']) ? ' repeat' : '');
			$popAttr = " onmouseover=\"pop(this,'{$popText}','{$popClass}')\"";
		} else {
			$popAttr = '';
		}
		if ($set['eventColor']) { //use event color
			$eStyle = $evt['cbg'] ? "background-color:{$evt['cbg']};" : '';
		} else { //use user color
			$eStyle = $evt['uco'] ? "background-color:{$evt['uco']};" : '';
		}
		if ($evt['app'] and !$evt['apd']) { $eStyle .= 'border:1px solid #ff0000;'; }
		$eStyle = $eStyle ? " style='{$eStyle}'" : '';
		echo '<div '.(($showDetails or $evt['mayE']) ? "class='square point'{$eStyle} onclick=\"editE({$evt['eid']},'{$date}');\"" : "class='square arrow'{$eStyle}")."{$popAttr}></div>\n";
	}
}

//sanity check
if (empty($lcV)) { exit('not permitted ('.substr(basename(__FILE__),0,-4).')'); } //launch via script only

//initialize
$evtList = array();
$daysToShow = $set['XvWeeksToShow'] * 7;
$cD = $_SESSION['cD'];
$uxTime = mktime(12,0,0,substr($cD,5,2),substr($cD,8,2),substr($cD,0,4)); //current time
$dayNr = date('N',$uxTime); //1:Mo - 7:Su

//set the start and end date of the calendar period to show
$sTime = $uxTime - (($dayNr - $set['weekStart']) * 86400); //calendar start time
$sDate = date('Y-m-d',$sTime); //cal start date
$eDate = date('Y-m-d',$sTime + (($daysToShow - 1) * 86400)); //cal end date

$prevDate = date("Y-m-d",$sTime - (($daysToShow - 14) * 86400));
$nextDate = date("Y-m-d",$sTime + (($daysToShow) * 86400));

//get categories

$filter = ''; //category filter
if (count($_SESSION['cC']) > 0 and $_SESSION['cC'][0] != 0) {
	$filter .= "`sequence` IN (".implode(",",$_SESSION['cC']).") AND ";
}
$catIDs = ($ucats == '0' or $pcats == '0') ? '0' : $ucats.(!empty($pcats) ? ",{$pcats}" : '');
if ($catIDs) {
	$filter .= "`ID` IN ({$catIDs}) AND ";
}
$stH = dbQuery("SELECT `ID`,`name`,`sequence`,`color`,`bgColor`
	FROM `categories` 
	WHERE {$filter}`status` >= 0
	ORDER BY `sequence`");
$cats = $stH->fetchAll(PDO::FETCH_ASSOC); //2-dim array

//retrieve events
retrieve($sDate,$eDate,'guc');

//display header
$header = '<span'.($_SESSION['mobile'] ? '' : ' class="viewHdr"').'>'.makeD($sDate,3)." - ".makeD($eDate,3).'</span>';
echo "<div class='calHeadMx'>\n<h4><a class='noPrint' href='index.php?lc&amp;cD={$prevDate}'>&nbsp;&#9664;&nbsp;</a>{$header}<a class='noPrint' href='index.php?lc&amp;cD={$nextDate}'>&nbsp;&#9654;&nbsp;</a></h4>\n</div>";

//display matrix - categories
echo '<div'.($_SESSION['mobile'] ? '' : " class='scrollBoxMx'").">\n";
echo "<div class='catBoxMx'>\n<table class='matrix'>\n";
echo "<tr class='headMx'><th class='cName'>{$xx['vws_evt_cats']}</th></tr>\n";
foreach($cats as $cat) {
	$style = ($cat['color'] ? "color:{$cat['color']};" : '').($cat['bgColor'] ? "background-color:{$cat['bgColor']};" : '');
	$style = $style ? " style='{$style}'" : '';
	echo "<tr>\n<td class='cName'{$style}>{$cat['sequence']} : {$cat['name']}</td>\n</tr>\n";
}
echo "</table>\n</div>\n";

//display matrix - calendar days
//calendar
echo "<div class='calBoxMx'>\n";
echo "<table class='matrix'>\n";
echo "<col span='{$daysToShow}'>\n";
//calendar months
echo "<tr class='headMx'>\n";
$cTime = $sTime;
for($i = 0; $i < $daysToShow; $i++) {
	$dx = date('j',$cTime); //day of month (1 - 31)
	$dxNext = date('j',$cTime+86400);
	$mx = date('n',$cTime); //month (1 - 12)
	if($dx == 1 or $dx == 15 or ($i == 0 and $dxNext != '1' and $dxNext != '15')) {
		echo "<th class='month'>".$months[$mx-1]."</th>\n";
	} else {
		echo "<th></th>\n";
	}
	$cTime += 86400; //+1 day
}
echo "</tr>\n";
//calendar week days
echo "<tr class='headMx'>\n";
for($i=$set['weekStart']; $i < ($set['weekStart']+$daysToShow); $i++) {
	echo "<th>{$wkDays_s[$i%7]}</th>"; //week days
}
echo "</tr>\n";
//calendar body
foreach($cats as $cat) {
	echo "<tr>\n";
	for($i=0; $i < $daysToShow; $i++){ //number of days to show
		$cTime = $sTime + ($i * 86400);
		$cDate = date("Y-m-d",$cTime);
		$day = ltrim(substr($cDate,8,2),"0");
		$dayBg = '';
		if (!empty($evtList[$cDate])) { //check if day background should be set
			foreach ($evtList[$cDate] as $evt) {
				if ($evt['seq'] == $cat['sequence'] and $evt['dbg']) {
					$dayBg = " style='background:{$evt['cbg']}'";
				}
			}
		}
		$dow = date('N',$cTime) > 5 ? 'we' : 'wd';
		if ($cDate == date("Y-m-d")) {
			$dow .= ' today';
		} elseif (isset($_SESSION['nD']) and $cDate == $_SESSION['nD']) {
			$dow .= ' slday';
		}
		if (!$_SESSION['hdr']) {
			$day = "<span class='dom floatR'>{$day}</span>";
		} else {
			$day = "<span class='dom floatR hyper' onclick=\"goDay('{$cDate}');\" title=\"{$xx['vws_view_day']}\">{$day}</span>";
		}
		$dHead = ($privs > 1) ? " class='hyper' onclick=\"newEx('{$cDate}',{$cat['ID']});\" title=\"{$xx['vws_add_event']}\"" : '';
		echo "<td class='{$dow}'{$dayBg}>{$day}<div{$dHead}>&nbsp;</div>\n";
		if (!empty($evtList[$cDate])) { showEvents($cDate,$cat['sequence']); }
		echo "</td>\n";
	}
	echo "</tr>\n";
}
echo "</table>\n</div>\n</div>\n";
?>

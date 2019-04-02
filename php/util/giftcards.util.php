<?php
$path = '/home/buying28/public_html/php';
include_once($path.'/helpers/cli.php');

uselib('giftcards::giftcards');
$cards = new Giftcards();
$checkRate = 24; 				//Check every X hours.

$wheresql = array();
$wheresql[] = "status='active'";
//$wheresql[] = "(bg_balance_check=1 OR last_checked IS NULL OR last_checked < (NOW() - INTERVAL 24 HOUR))";
//$wheresql[] = "bg_balance_check=1";
$wheresql[] = "timestamp>(NOW() - INTERVAL 30 DAY)";															//Check cards added within the past 30 days

do{
	sleep(1);
	#$sql = "SELECT * FROM giftcards WHERE bg_balance_check=1 OR (".implode(" AND ",$wheresql).") ORDER BY last_update_check ASC LIMIT 1";
	$sql = "SELECT * FROM giftcards WHERE bg_balance_check=1 ORDER BY last_update_check ASC LIMIT 1";
	//t($sql);
	$q = mysql_query($sql);
	$r = mysql_fetch_assoc($q);
	if(!$r)t('Done');			
	$id = $r['id'];
	$number = $r['number'];
	t("Checking: ".$number,1);
		
	mysql_query("UPDATE giftcards SET last_update_check=NOW(),bg_balance_check=0 WHERE id='{$id}'");
	
	$balance = $cards->checkBalance($id);	
	t("$number ($id): $balance",1);			
}while($r);
t('Done');


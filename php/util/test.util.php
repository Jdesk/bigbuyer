<?php
set_time_limit(0);
$path = '/home/buying28/public_html/php';
include_once($path.'/helpers/cli.php');

uselib('orders');
$checkRate = 24; 				//Check every X hours.

$wheresql = array();


$wheresql[] = "status='processed'";
$wheresql[] = "(remote_status='In Progress' OR remote_status IS NULL OR remote_status2='Shipped')";
$wheresql[] = "remote_order_id='BBY01-805611674344'";

//$sql = "SELECT * FROM orders WHERE ".implode(" AND ",$wheresql)." ORDER BY last_update_check ASC LIMIT 1";
$sql = "SELECT * FROM orders WHERE ".implode(" AND ",$wheresql)." ORDER BY id ASC LIMIT 1";
//t($sql);
$q = mysql_query($sql);
$r = mysql_fetch_assoc($q);
if(!$r)t('Done');	
		
$id = $r['id'];
			
t("\n\nChecking: {$r['remote_order_id']}",1);	
mysql_query("UPDATE orders SET last_update_check=NOW() WHERE id='{$id}'");
$checkKey = check($r);	
if(!$checkKey){ t("Skip..."); }

$order = new Orders($id);	
$res = $order->updateStatus();
//if($res){
if($checkKey)mysql_query("UPDATE orders SET check_key='$checkKey' WHERE id='{$r['id']}'");	
//}	
	

function check($r){	
	
	//list($checks,$latest) = mysql_fetch_array(mysql_query("SELECT count(id),timestamp FROM orders_checks WHERE order_id='{$r['id']}' AND user_id is NULL GROUP BY order_id ORDER BY id DESC"));
	
	//$checks=($checks)?(int)$checks:0;
	$days = abs(xDaysAgo($r['processed_timestamp'],'now',''));
	$hours = $days*24;
	$minutes = $days*24*60;
	$seconds = $days*24*60*60;
	
	t("Ordered: {$r['processed_timestamp']} $days ($hours)",1);
	
 	if($hours>=168 && $r['check_key']<168){		
		return 168;
 	}
	else if($hours>=36 && $r['check_key']<36){		
		return 36;
 	}
 	else if($hours>=12 && $r['check_key']<12){ 		
 		return 12;
 	}
 	else if($hours>=1 && $r['check_key']<1){ 		
 		return 1;
 	}	
	return false;
}

t('Done');


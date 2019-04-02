<?php
set_time_limit(0);
$path = '/home/buying28/public_html/php';
include_once($path.'/helpers/cli.php');

$wheresql = array();
$wheresql[] = "remote_status='Canceled'";
$wheresql[] = "processed_timestamp>=DATE_SUB(NOW(), INTERVAL 3 DAY)";

$sql = "SELECT id FROM orders WHERE ".implode(" AND ",$wheresql);
$q = mysql_query($sql);
while(list($id) = mysql_fetch_array($q)){
	t("Checking: {$id}",1);
	refund($id);
}
t('Done');

function refund($oId){
	$q = mysql_query("SELECT * FROM giftcards_orders WHERE order_id='{$oId}' AND refunded=0");
	while($r = mysql_fetch_assoc($q)){
		mysql_query("UPDATE giftcards SET balance=balance+{$r['amount']} WHERE id='{$r['giftcard_id']}'");
		mysql_query("UPDATE giftcards_orders SET refunded=1 WHERE id='{$r['id']}'");
		t("UPDATE giftcards_orders SET refunded=1 WHERE id='{$r['id']}'",1);
	}
}
<?php
$path = '/home/buying28/public_html/php';
include_once($path.'/helpers/cli.php');

$q = mysql_query("SELECT * FROM accounts WHERE deleted=0");
while($r = mysql_fetch_assoc($q)){
	list($count) = mysql_fetch_array(mysql_query("SELECT count(id) FROM orders WHERE remote_status='Canceled' AND account_id='{$r['id']}'"));
	
	t($count,1);

	
	if($count<=0){ $tier = ''; }
	else if($count==1){ $tier = 'Tier 1'; }
	else if($count==2){ $tier = 'Tier 2'; }
	else if($count==3){ $tier = 'Tier 3'; }
	else if($count==4){ $tier = 'Tier 4'; }
	else if($count>4){ $tier = 'Tier 4+'; }
	
	mysql_query("UPDATE accounts SET tier='{$tier}' WHERE id='{$r['id']}'");
}

$q = mysql_query("SELECT * FROM giftcards WHERE status='active' OR status='error'");
while($r = mysql_fetch_assoc($q)){	
	list($count) = mysql_fetch_array(mysql_query("SELECT count(go.id) FROM giftcards_orders AS go
													LEFT JOIN orders AS o ON o.id=go.order_id 
													WHERE o.remote_status='Canceled' AND go.giftcard_id='{$r['id']}'"));
	t($count,1);

	
	if($count<=0){ $tier = ''; }
	else if($count==1){ $tier = 'Tier 1'; }
	else if($count==2){ $tier = 'Tier 2'; }
	else if($count==3){ $tier = 'Tier 3'; }
	else if($count==4){ $tier = 'Tier 4'; }
	else if($count>4){ $tier = 'Tier 4+'; }

	mysql_query("UPDATE giftcards SET tier='{$tier}' WHERE id='{$r['id']}'");
}
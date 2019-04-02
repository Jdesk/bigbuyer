<?php
session_write_close();

uselib('orders');
uselib('giftcards::giftcards');
usehelper('ajax::dispatch');

function retryOrder(){
	$id = $_REQUEST['id'];
	
	mysql_query("DELETE FROM orders_snapshots WHERE order_id='{$id}'");
	sql("UPDATE orders SET status='pending', error_msg='', processed_timestamp=null WHERE id='{$id}'");
}
function getErrorHtml(){
	$id = $_REQUEST['id'];
	list($html) = mysql_fetch_array(mysql_query("SELECT html FROM orders_snapshots WHERE order_id='$id'"));		
	print $html;	
}
function updateStatus(){
	$id = (int)$_REQUEST['id'];
	if(!$id) err("Order not found");
	
	$orders = new Orders($id);
	$res = $orders->updateStatus();
	
	if($res) json();
	else err("Unexpected Error. Please try again.");
}
function getAllStatus($col){
	$items = array();

	$q = mysql_query("SELECT DISTINCT $col FROM orders");
	while(list($r) = mysql_fetch_array($q)){		
		$items[] = $r;
	}
	return $items;
}
function getAccountsSummary($cc=false){
	$items = array();
	
	$wheresql = array();
	$wheresql[] = "deleted=0";
	if(!$cc){
		$wheresql[] = "(cc_num IS NULL OR cc_num='')";
	}
	else{
		$wheresql[] = "(cc_num IS NOT NULL AND cc_num!='')";
	}
		
	$q = mysql_query("SELECT count(id) as num,tier FROM accounts WHERE ".implode(" AND ",$wheresql)." GROUP BY tier ORDER BY tier ASC");
	while($r = mysql_fetch_assoc($q)){		
		$items[] = (object)$r;
	}
	return $items;
}
function getGiftcardsSummary(){
	$items = array();

	$q = mysql_query("SELECT count(id) as num,tier,SUM(balance) totalBalance FROM giftcards WHERE balance>0.01 AND status='active' GROUP BY tier ORDER BY tier ASC");
	while($r = mysql_fetch_assoc($q)){		
		$items[] = (object)$r;
	}
	return $items;
}
function reviewOrder(){
	$stats = generateOrderDetails();
	json(array('stats'=>$stats));
}
function createOrder(){
	$cbUrl = $_REQUEST['cb_url'];
	$taxId = $_REQUEST['tax_id'];
	
	$stats = generateOrderDetails();
	
	$res = (object)array('ok'=>0, 'notok'=>0);
	foreach($stats->orders as $r){
		$order = array(
			'account_id'	=> $r->account['id'],			
			'status'		=> 'pending',			
			'total'			=> $r->total,
			'card_tier'		=> $r->cardTier,
			'cashback_url'	=> $cbUrl,
			'tax_id'		=> $taxId
		);
		
		$updatesql = array();
		foreach($order AS $k=>$v) $updatesql[] = "`$k` = '$v'";
		mysql_query("INSERT INTO orders SET ".implode(", ",$updatesql));
		$error = mysql_error();
		
		if($error) $res->notok++;
		else{
			$res->ok++;
			
			$oId = mysql_insert_id();
									
			foreach($r->items as $i){												
				$item = array(
					'sku'		=> $i->sku,					
					'unit'		=> $i->unit,
					'qty'		=> $i->qty,
					'order_id'	=> $oId	
				);
				
				$updatesql = array();
				foreach($item AS $k=>$v) $updatesql[] = "`$k` = '$v'";				
				mysql_query("INSERT INTO orders_items SET ".implode(", ",$updatesql));
			}
		}
	}
	
	json($res);
}


function generateOrderDetails(){
	$skus = $_REQUEST['sku'];
	$accounts = $_REQUEST['account_tier'];
	$ccs = $_REQUEST['cc_tier'];
	$cards = $_REQUEST['giftcard_tier'];
	$count = $_REQUEST['orders_count'];

	$accountType = ($ccs)?'cc':'';

	if(!$skus)err("No products found");
	if(!$accounts && !$ccs)err("No account tiers selected");
	if(!$cards && !$ccs)err("No giftcard tieds or CC accounts selected");

	if(!$cards)$cards = array();
	if(!$ccs)$ccs=array();
	if(!$accounts)$accounts=array();

	$stats = (object)array(
			'ordersCount'	=> $count,
			'total'			=> 0,
			'qty'			=> 0,
			'skus'			=> array(),
			'orders'		=> array(),
	);
		
	list($stats->ccs) = mysql_fetch_array(mysql_query("SELECT count(id) FROM accounts WHERE cc_num IS NOT NULL AND cc_num!='' AND deleted=0 AND tier IN ('".implode("','",$accounts)."')"));
	list($stats->accounts) = mysql_fetch_array(mysql_query("SELECT count(id) FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND deleted=0 AND tier IN ('".implode("','",$ccs)."')"));
	list($stats->cards,$stats->cardsBalance) = mysql_fetch_array(mysql_query("SELECT count(id),SUM(balance) FROM giftcards WHERE tier IN ('".implode("','",$cards)."') AND balance>0.01 AND status='active'"));
	
	$history = array();
	foreach(range(1,$count) as $i){
		$items = array();
		$qty = 0;
		$total = 0;
			
		foreach($skus as $k=>$sku){
			$item = (object)array('sku'=>$sku, 'qty'=>$_REQUEST['qty'][$k], 'unit'=>$_REQUEST['cost'][$k], 'uid'=>$k);
			$qty += $item->qty;
			$total += $item->qty * $item->unit; 
			$items[] = $item;
		}
		
		
		if($accountType=='cc'){
			$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE cc_num IS NOT NULL AND deleted=0 AND cc_num!='' AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
		}
		else{
			$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND deleted=0 AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
		}
		
		if(!$account){
			$history = array();
		
			if($accountType=='cc'){
				$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE cc_num IS NOT NULL AND deleted=0 AND cc_num!='' AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
			}
			else{		
				$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND deleted=0 AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
			}
		}
		mysql_query("UPDATE accounts SET last_used=NOW() WHERE id='{$account['id']}'");
		$history[] = $account['id'];
		
		
		$order = (object)array(
			'items'		=> $items,
			'itemsCount'=> count($items),
			'qty'		=> $qty,
			'account'	=> $account,			
			'total'		=> $total,
			'cardTier'	=> $cards[rand(0,count($cards)-1)]
		);
		
		$stats->total += $total;
		$stats->orders[] = $order;
		
		
	}
	
	$accountCount = ($accountType=='cc')?$stats->ccs:$stats->accounts;
	$stats->accountsPer = ($stats->ordersCount)?ceil($stats->ordersCount/$stats->ordersCount):0;

	return $stats;
}

/*
function generateOrderDetails(){	
	$skus = $_REQUEST['sku'];
	$accounts = $_REQUEST['account_tier'];
	$ccs = $_REQUEST['cc_tier'];
	$cards = $_REQUEST['giftcard_tier'];
	$perOrder = $_REQUEST['per_order'];
	
	$accountType = ($ccs)?'cc':''; 
	
	if(!$skus)err("No products found");
	if(!$accounts && !$ccs)err("No account tiers selected");
	if(!$cards && !$ccs)err("No giftcard tieds or CC accounts selected");
	
	if(!$cards)$cards = array();
	if(!$ccs)$ccs=array();
	if(!$accounts)$accounts=array();
	
	$stats = (object)array(
			'ordersCount'	=> 0,
			'total'			=> 0,
			'qty'			=> 0,
			'skus'			=> array(),
			'orders'		=> array(),
	);
							
	list($stats->accounts) = mysql_fetch_array(mysql_query("SELECT count(id) FROM accounts WHERE cc_num IS NOT NULL AND cc_num!='' AND tier IN ('".implode("','",$accounts)."')"));
	list($stats->ccs) = mysql_fetch_array(mysql_query("SELECT count(id) FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND tier IN ('".implode("','",$ccs)."')"));
	list($stats->cards,$stats->cardsBalance) = mysql_fetch_array(mysql_query("SELECT count(id),SUM(balance) FROM giftcards WHERE tier IN ('".implode("','",$cards)."') AND balance>0.01 AND status='active'"));
	
	foreach($skus as $k=>$sku){
		$item = (object)array('sku'=>$sku, 'qty'=>$_REQUEST['qty'][$k], 'unit'=>$_REQUEST['cost'][$k], 'uid'=>$k);
	
		$item->total = $item->qty*$item->cost;
	
		$stats->ordersCount += ceil($item->qty/$perOrder);
		$stats->total += ($item->unit*$item->qty);
		$stats->qty += $item->qty;
				
		$stats->skus[] = $item;
	}
	
				
	$history = array();
	foreach($stats->skus as $s){
		$count = 0;
		for($i=0;$i<=$s->qty;$i+=$perOrder){

			if($accountType=='cc'){
				$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE cc_num IS NOT NULL AND cc_num!='' AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
			}
			else{
				$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
			}
															
			if(!$account){
				$history = array();
				
				if($accountType=='cc'){
					$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE cc_num IS NOT NULL AND cc_num!='' AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
				}
				else{
										
					$account = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE (cc_num IS NULL OR cc_num='') AND tier IN ('".implode("','",$accounts)."') AND id NOT IN ('".implode("','",$history)."') ORDER BY last_used ASC"));
				}								
			}
			mysql_query("UPDATE accounts SET last_used=NOW() WHERE id='{$account['id']}'");
			$history[] = $account['id'];
	
						
			if($s->qty-$count>$perOrder) $qty = $perOrder;
			else $qty = $s->qty-$count;	
			if(!$qty)continue;
			
			$count+=$qty;
			$order = (object)array(
					'sku'		=> $s->sku,
					'qty'		=> $qty,
					'account'	=> $account,
					'unit'		=> $s->unit,
					'total'		=> $s->unit*$qty,
					'cardTier'	=> $cards[rand(0,count($cards)-1)]
			);
	
			$stats->orders[] = $order;
		}
	}
	
	$accountCount = ($accountType=='cc')?$stats->accounts:$stats->ccs;
	$stats->accountsPer = ($stats->accounts)?ceil($stats->ordersCount/$accountCount):0;
	
	return $stats;
}
function createOrder(){
	$cbUrl = $_REQUEST['cb_url'];
	$taxId = $_REQUEST['tax_id'];
	
	$stats = generateOrderDetails();
	
	$res = (object)array('ok'=>0, 'notok'=>0);
	foreach($stats->orders as $r){
		$item = array(
			'account_id'	=> $r->account['id'],
			'sku'			=> $r->sku,
			'status'		=> 'pending',
			'qty'			=> $r->qty,
			'unit'			=> $r->unit,
			'total'			=> $r->total,
			'card_tier'		=> $r->cardTier,
			'cashback_url'	=> $cbUrl,
			'tax_id'		=> $taxId
		);
		
		$updatesql = array();
		foreach($item AS $k=>$v) $updatesql[] = "`$k` = '$v'";
		mysql_query("INSERT INTO orders SET ".implode(", ",$updatesql));
		$error = mysql_error();
		
		if($error) $res->notok++;
		else $res->ok++;
	}
	
	json($res);
}
*/
function loadOrders(){	
	$sortColumns = array('orders.timestamp','orders.status','orders.remote_status','orders.remote_status2','orders.remote_status3','orders.sku','orders.remote_order_id','orders.account_id','orders.qty','orders.unit','orders.total','orders.card_tier','orders.cashback','orders.tax_id','');

	$array = array();

	$offset = (int)$_REQUEST['start'];
	$length = (int)$_REQUEST['length'];

	if($_REQUEST['order'])$orderby = array('col'=>$sortColumns[$_REQUEST['order'][0]['column']],'dir'=>$_REQUEST['order'][0]['dir']);

	$offset = $offset;
	$length = (int) $length;
	if ($length)
		$limit = "LIMIT $offset,$length";
	else
		$limit = "";

	if (!$orderby)
		$orderby = array('o.timestamp DESC');
	else
		$orderby = array($orderby['col'] . " " . $orderby['dir']);
		
	$wheresql = array("1=1");
	$filter = $_REQUEST['filter'];
	if($filter){
		foreach($filter as $k=>$v){
			if((is_array($v) && !empty($v)) || strlen(trim($v))){
				switch($k){
					case 'timestamp':
					case 'processed_timestamp':
						$range = explode(' - ',$v);
						$wheresql[] = "orders.{$k} BETWEEN '".dbDate($range[0])." 00:00:00' AND '".dbDate($range[1])." 23:59:59'";
						break;
					case 'status':
					case 'remote_status':
					case 'remote_status2':
					case 'remote_status3':
					case 'account_id':
						$wheresql[] = "orders.{$k} IN ('".implode("','",$v)."')";
						break;
					case 'q':						
						$orsql = array();
						$orsql[] = "orders.remote_order_id like '%$v%'";
						$orsql[] = "i.sku like '%$v%'";
						$wheresql[] = "(".implode(" OR ",$orsql).")";
						break;						
					default:
						$wheresql[] = "orders.$k = '$v'";
						break;
				}
			}
		}
	}

	$sql = "SELECT SQL_CALC_FOUND_ROWS orders.* FROM orders
				LEFT JOIN orders_items AS i ON i.order_id=orders.id			
				WHERE ".implode(" AND ",$wheresql)." 
				GROUP BY orders.id 
				ORDER BY  " . implode(' ', $orderby) . " 
				$limit";
	//t($sql);
	$q = mysql_query($sql);
	list($total) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS();"));
	while($r = mysql_fetch_assoc($q)){
		$array[] = formatOrder($r);
	}
	json(array(
		//'sql' => $sql,	
		'data'=> $array,
		'summary' => getSummary($wheresql),
		'total' => $total,
		'page' => $offset,
		'sort'	=> ($sortby)?$sortby['col']:$_REQUEST['order'][0]['column'],
		'sortDir' => ($sortby)?$sortby['dir']:$_REQUEST['order'][0]['dir'],
		'length' => $length,
	));
}
function getSummary($wheresql){
	$summary = (object)array(
		'total'		=> 0,
		'pending'	=> 0,
		'success'	=> 0,
		'error'		=> 0,
		//Success
		'shipped'	=> 0,
		'delivered'	=> 0,
		//Pending
		'queued'	=> 0,
		'submitted'	=> 0,
		//
		'cancelled' => 0,
		'na'		=> 0
	);
	$sql = "SELECT orders.status,orders.remote_status,orders.remote_status2,count(orders.id) AS num FROM orders LEFT JOIN orders_items AS i ON i.order_id=orders.id WHERE ".implode(" AND ",$wheresql)." GROUP BY orders.status,orders.remote_status,orders.remote_status2";	
	$q = mysql_query($sql);	
	while($r = mysql_fetch_assoc($q)){
		$num = $r['num'];
		$summary->total+=$num;
		
		if($r['status'] == 'processed'){
			if($r['remote_status'] == 'Complete'){
				$summary->success+=$num;
				
				if($r['remote_status2'] == 'Shipped')$summary->shipped+=$num;
				if($r['remote_status2'] == 'Shipped and Invoiced')$summary->delivered+=$num;
			}
			else if($r['remote_status'] == 'In Progress'){
				$summary->pending+=$num;								
				if($r['status'] == 'processed')$summary->submitted+=$num;
			}
			else{
				$summary->error+=$num;
				
				if($r['remote_status'] == 'Canceled') $summary->cancelled+=$num;
				else $summary->na+=$num;
			}
		}
		if($r['status'] == 'pending'){
			$summary->queued+=$num;
			$summary->pending+=$num;
		}
		if($r['status'] == 'error'){
			$summary->error+=$num;
			if($r['remote_status2'] == 'Cancelled')$summary->cancelled+=$num;
			else $summary->na+=$num;
		}
	}
	return $summary;
}
function loadOrder(){
	$id = (int)$_REQUEST['id'];
	
	$q = mysql_query("SELECT * FROM orders WHERE id='{$id}'");
	$order = mysql_fetch_assoc($q);
	if($order) $order = formatOrder($order);
	
	json(array('item'=>$order));
}
function getOrderDetails(){
	$id = (int)$_REQUEST['id'];
	
	$q = mysql_query("SELECT * FROM orders WHERE id='{$id}'");
	$order = mysql_fetch_assoc($q);
	if($order){
		$order = formatOrder($order);
		$order->checks = getOrderChecks($order->id);
	}		
	json(array('item'=>$order));
}
function getOrderChecks($id){
	$items = array();
	$q = mysql_query("SELECT c.*,u.username FROM orders_checks AS c
						LEFT JOIN users AS u ON u.id=c.user_id 
						WHERE order_id='$id' 
						ORDER BY id DESC");
	while($r = mysql_fetch_assoc($q)){
		$r['data'] = json_decode($r['data']);
		$items[] = (object)$r;
	}
	return $items;
}
function formatOrder($r){
	$r['status'] = ucwords($r['status']);
	$r['account'] = mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE id='{$r['account_id']}'"));
	
	if(!$r['remote_status'])$r['remote_status']='-';
	if(!$r['remote_status2'])$r['remote_status2']='-';
	if(!$r['remote_status3'])$r['remote_status3']='-';
	
	if($r['last_status_update']) $r['last_status_update'] = date('m/d/Y h:i A',strtotime($r['last_status_update']));
	if($r['processed_timestamp']) $r['processed_timestamp'] = date('m/d/Y h:i A',strtotime($r['processed_timestamp']));
			
	$r['cards'] = getOrderCards($r['id']);
	if(!$r['card_tier']) $r['card_tier'] = 'No Tier';
	
	return (object)$r;
}
function getOrderCards($id){
	$gc = new Giftcards();
	return $gc->getOrderCards($id);
}
function removeOrder(){
	$id = $_REQUEST['id'];
	
	list($status) = mysql_fetch_array(mysql_query("SELECT status FROM orders WHERE id='{$id}'"));
	if($status != 'pending') err("Unable to delete this order!");
	
	sql("DELETE FROM orders WHERE id='{$id}'");
}
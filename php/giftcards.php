<?php
session_write_close();

uselib('giftcards::giftcards');

$stores = array(
	'purchased'	=> Giftcards::getPurchasedStores(),
	'used'		=> Giftcards::getUsedStores(),
	'default'	=> Giftcards::getdefaultStores(),
);

$errorsOptions = Giftcards::getErrors();
$statusOptions = Giftcards::getStatus();
	
usehelper('ajax::dispatch');


function bulkBalanceCancel(){
	$id = (int)$_REQUEST['id'];
	
	sql("UPDATE giftcards SET bg_balance_check=0 WHERE id='$id'");
}
function bulkCheckBalance(){
	$range = explode(' - ',$_REQUEST['daterange']);
	$start = reset($range);
	$end = end($range);		
	if($start && !$end && !$_REQUEST['error'])err("Please specify a date range or error and try again");	
	
	$wheresql = array();
	$wheresql[] = "user_id='".Users::getOwnerId()."'";
	if($start)$wheresql[] = "last_used BETWEEN '".dbdate($start)." 00:00:00' AND '".dbdate($end)." 23:59:59"."'";
	if($_REQUEST['error'])$wheresql[] = "last_check_error='{$_REQUEST['error']}'";
	if($store)$wheresql[] = "store='$store'";
			
	sql("UPDATE giftcards SET bg_balance_check=1 WHERE ".implode(" AND ",$wheresql));
}
function checkBalance(){
	$number = $_REQUEST['number'];
	$pin = $_REQUEST['pin'];
	$store = $_REQUEST['store'];
		
	$cards = new Giftcards();
	$balance = $cards->checkBalance(0,$number,$pin,$store);
	
	if($balance === false)err("An unknown error occured!");
	elseif(!is_float($balance))err($balance);
	else{	
		$checked = dbTimestamp('now');	
		json(array('balance'=>number_format($balance,2,".",""),'checked'=>$checked));
	}		
}
function updateBalance(){			
	$cards = new Giftcards();
	$balance = $cards->checkBalance($_REQUEST['id']);

	if($balance === false)err("An unknown error occured!");
	elseif(!is_float($balance))err($balance);
	else{
		//t("UPDATE giftcards SET balance='$balance', last_checked=NOW(), bg_balance_check=0 WHERE id='{$_REQUEST['id']}'",1);
		sql("UPDATE giftcards SET balance='$balance', last_checked=NOW(), bg_balance_check=0 WHERE id='{$_REQUEST['id']}'",$balance);		
	}		
}

function getCardOrders(){	
	$gc = new Giftcards();
	
	$id = (int)$_REQUEST['id'];
	$used = 0;
	$orders = array();
	if($id){		
		$orders = $gc->getCardOrders($id);				
		$used = $gc->getCardUsedAmount($id);
	}
	
	json(array('orders'=>$orders, 'used'=>number_format($used,2)));
}
function cardOrderRemove(){	
	$gc = new Giftcards();
	$gc->removeOrderCard((int)$_REQUEST['id']);
}
function cardAddOrder(){	
	$gc = new Giftcards();
	
	$id = (int)$_REQUEST['id'];
	$amount = (float)$_REQUEST['amount'];
	$title = $_REQUEST['title'];
	if(!$id || !$amount || !$title)err('Unable to add charge!');
	
	$gc->addCardOrder($id,$title,$amount);
}
function saveCard(){
	$gc = new Giftcards();
	
	$id = (int)$_REQUEST['id'];	
	$gc->saveCard($id,$_POST);
}
function removeCard(){
	$id = (int)$_REQUEST['id'];	
	sql("DELETE FROM giftcards WHERE id='$id'");
}


function getCard($id){
	$gc = new Giftcards();

	$card = $gc->getCard($id);
	return $card;
}

function loadCard(){
	$id = (int)$_REQUEST['id'];

	$q = mysql_query("SELECT * FROM giftcards WHERE id='$id'");
	$item = mysql_fetch_assoc($q);
	$item = formatCard($item);

	json(array('item'=>$item));
}

function loadCards(){
	$sortColumns = array('number','pin','balance','amount','price','discount','tier','timestamp','last_used','last_checked','');

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
		$orderby = array('ORDER BY balance DESC, timestamp DESC');
	else
		$orderby = array($orderby['col'] . " " . $orderby['dir']);		

	$wheresql = array("1=1");
	$filter = $_REQUEST['filter'];
	if($filter){
		foreach($filter as $k=>$v){			
			if((is_array($v) && !empty($v)) || strlen(trim($v))){
				switch($k){					
					case 'balance':
						if($v == "1")$wheresql[] = "balance > 0.1";
						if($v == "-1")$wheresql[] = "balance <= 0";
						break;
					case 'error':
						$wheresql[] = "last_check_error IN ('".implode("','",$v)."')";
						break;
					case 'status':
						$wheresql[] = "status IN ('".implode("','",$v)."')";
						break;
					case 'number':
						$wheresql[] = "(number like '%$v%')";
						break;
					case 'stores_purchased':
						$wheresql[] = "store_purchased in ('".implode("','",$v)."')";
						break;
					case 'purchased':
						$range = split(" - ",$v);
						$wheresql[] = "timestamp BETWEEN '".date('Y-m-d',strtotime($range[0]))." 00:00:00' and '".date('Y-m-d',strtotime($range[1]))." 23:59:59'";
						break;
					case 'last_used':
						$range = split(" - ",$v);
						$wheresql[] = "last_used BETWEEN '".date('Y-m-d',strtotime($range[0]))." 00:00:00' and '".date('Y-m-d',strtotime($range[1]))." 23:59:59'";					
						break;
					default:
						$wheresql[] = "`$k` = '$v'";
						break;
				}
			}
		}
	}

	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM giftcards WHERE ".implode(" AND ",$wheresql)." ORDER BY  " . implode(' ', $orderby) . " $limit";
	//t($sql);
	$q = mysql_query($sql);
	list($total) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS();"));
	while($r = mysql_fetch_assoc($q)){
		$array[] = formatCard($r);
	}
	json(array(
	'sql' => $sql,
	'data'=> $array,
	'total' => $total,
	'page' => $offset,
	'sort'	=> ($sortby)?$sortby['col']:$_REQUEST['order'][0]['column'],
	'sortDir' => ($sortby)?$sortby['dir']:$_REQUEST['order'][0]['dir'],
	'length' => $length,
	));
}
function formatCard($r){
	if(!$r['purchased'] || $r['purchased']=='0000-00-00')$r['purchased']='';
	else $r['purchased'] = tzdate('m/d/Y',$r['purchased']);
	
	if(!$r['last_used'] || $r['last_used']=='0000-00-00')$r['last_used']= '';
	else $r['last_used'] = tzdate('m/d/Y',$r['last_used']);
	
	if(!$r['last_checked'] || $r['last_checked']=='0000-00-00 00:00:00')$r['last_checked']= '';
	else $r['last_checked'] = tzdate('m/d/Y h:i:s A',$r['last_checked']);
		
	if(!$r['discount'] && $r['price'] && $r['amount']){
		$discount = $r['amount']-$r['price'];
		$r['discount'] = (100*$discount/$r['amount']);
	}
	if(!$r['price'] && $r['discount'] && $r['amount']){
		$r['price'] = $r['amount']-($r['discount']/100)*$r['amount'];
	}		
	$r['timestamp'] = date('m/d/Y h:i:s a',strtotime($r['timestamp']));
	return (object)$r;
}

function importCards(){
	$csv_mimetypes = array(
			'text/csv',
			'text/plain',
			'application/csv',
			'text/comma-separated-values',
			'application/excel',
			'application/vnd.ms-excel',
			'application/vnd.msexcel',
			'text/anytext',
			'application/octet-stream',
			'application/txt',
	);

	$error = '';
	if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) $error = "Upload failed with error code " . $_FILES['file']['error'];
	else if ($info === FALSE) $error = "Unable to type of uploaded file";
	else if (!in_array($_FILES['file']['type'], $csv_mimetypes)) $error = "Invalid file uploaded";
	else if ($_FILES['file']['error'] != 0) $error = "Unkown Error";

	if($error){
		if(file_exists($_FILES["file"]["tmp_name"]))unlink($_FILES["file"]["tmp_name"]);
		err($error);
	}

	$items = array();
	if (($handle = fopen($_FILES["file"]["tmp_name"], "r")) !== FALSE) {
		while (($row = fgetcsv($handle, 2000, ",")) !== FALSE) {
			$items[] = array($row[0],$row[1],$row[2],$row[3],$row[4]);
		}
	}
	fclose($handle);
	unlink($_FILES["file"]["tmp_name"]);

	$stats = array('done'=>array(), 'errors'=>array());
	foreach($items as $item){
		$amount = preg_replace("/[^0-9\.]/","",$item[0]);
		$number = preg_replace("/[^0-9\.]/","",$item[1]);
		$pin = preg_replace("/[^0-9\.]/","",$item[2]);
		$store = $item[3];
		$price = preg_replace("/[^0-9\.]/","",$item[4]);	
		$type = 'bestbuy';
		
		if(!$store)$store='No Store';
		if(!$price)$price=$amount;

		if($amount && $number && $pin){
			mysql_query("INSERT INTO giftcards SET number='$number', pin='$pin', amount='$amount', balance='$amount', store='$type', store_purchased='$store',price='$price',user_id='".Users::getOwnerId()."'");
			$stats['done'][] = $number;
		}
		else{
			$stats['errors'][] = $number;
		}
	}
	json(array('stats'=>$stats));
}
function downloadTemplate(){
	$filename = 'Giftcard Import File - Template.csv';
	$path = $GLOBALS['system']['csvtemplate_path'].'/'.$filename;	

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename='.$filename);
	readfile($path);	
}
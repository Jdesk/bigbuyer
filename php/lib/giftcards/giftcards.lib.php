<?php
uselib('giftcards::bestbuy');

class Giftcards {
	public function __construct(){		
	}
	
	public function checkBalance($id,$number=0,$pin=0,$store=''){			
		if($id){
			$card = $this->getCard($id);
			$number = trim($card->number);
			$pin = trim($card->pin);
			$store = trim($card->store);
		}		
		if(!$number || !$store)return false;
				 
		switch(preg_replace("/ /","",trim(strtolower($store)))){
			case 'bestbuy':
				$bestbuyCard = new BestbuyCard();
				$balance = $bestbuyCard->getBalance($number,$pin);
				break;			
			default:
				return false;
				break;
		}	
					
		$update = false;
		if($balance === false){
			$error = 'Unable to load page';
		}	
		else if(!is_float($balance)){
			$error = $balance;
			$update = true;
		}
		else{				
			$update = true;
			$error = '';			
		}		
		
		$updatesql = array();
		if($update){
			$updatesql[] = "last_checked=NOW()";
			if($error)$updatesql[] = "status='error'";	
			else{				
				if($balance>0){
					$updatesql[] = "status='active'";
				}
				else{
					$updatesql[] = "status='archived'";
				}	
				if($card){
					$updatesql[] = "balance='{$balance}'";
				}								
			}		
		}				
		$updatesql[] = "last_check_error='".mysql_real_escape_string($error)."'";
		$sql = "UPDATE giftcards SET ".implode(",",$updatesql)." WHERE number='$number'";
		//t($sql,1);
		mysql_query($sql);
		
		return $balance;		
	}
	public function saveCard($id=0,$data,$ajax=true){
		$fields = array('number','pin','store','amount','price','discount','balance','store_purchased','purchased','store_used','last_used','last_checked','notes','user_id','status');
		
		
		$data['number'] = preg_replace("/[^0-9]/","",$data['number']);
		$data['pin'] = preg_replace("/[^0-9]/","",$data['pin']);
		
		$data['purchased'] = dbdate($data['purchased']);
		$data['last_used'] = dbdate($data['last_used']);
		$data['user_id'] = Users::getOwnerId();
		$data['discount'] = (float)$data['discount'];
						
		//if(!$data['last_used']) $data['last_used'] = 0;
		
		$columns = array();
		$values = array();
		foreach($fields as $k){
			$columns[] = "`".$k."`";
			if($data[$k])
				$values[] = "'".$data[$k]."'";
			else
				$values[] = "NULL";
		}
						
		if(!$id){	
			$sql = "INSERT INTO giftcards (".implode(",",$columns).") VALUES (".implode(",",$values).")";
		}
		else{
			$update = array();
			foreach($columns as $i=>$c) $update[] = "$c = $values[$i]";
			$sql = "UPDATE giftcards SET ".implode(",",$update)." WHERE id='$id'";
		}
		
		//print $sql; exit;
		
		if($ajax)
			sql($sql);
		else{
			mysql_query($sql);
			return (mysql_error())?false:true;
		}
	}
	public function getCard($id){
		$q = mysql_query("SELECT * FROM giftcards WHERE id='$id'");
		$card = mysql_fetch_assoc($q);
		$card['orders'] = $this->getCardOrders($id);	
			
		return (object)$card;
	}		
	public function findMatchingCards($amount,$sort=''){
		$cards = array();						
		
		if(!$sort)$sort='store, balance DESC';
		
		$reservedIds = Users::getReservedMainCards();
				
		$q = mysql_query("SELECT * FROM giftcards WHERE balance >= '$amount' AND balance >0 AND tax_refund_card=0 AND id NOT IN ('".implode("','",$reservedIds)."') AND user_id='".Users::getOwnerId()."' ORDER BY ".$sort);
		while($c = mysql_fetch_assoc($q)){			
			$cards[] = (object)$c;
		}
		//Users::reserveMainCard($id);
		
		return $cards;
	}	
	public function findTaxRefundCards($store,$count=3){
		$items = array();
		
		$reservedIds = Users::getReservedTaxCards();
		
		$ids = array();
		$q = mysql_query("SELECT * FROM giftcards WHERE balance>0.09 AND store_purchased='$store' AND id NOT IN ('".implode("','",$reservedIds)."') ORDER BY balance DESC LIMIT 3");
		while($r = mysql_fetch_assoc($q)){
			$items[] = (object)$r;
			$ids[] = $r['id'];
		}		
		Users::reserveTaxCards($ids);
		
		return $items;
	}
	function addCardOrder($cId, $title, $amount){
		sql("INSERT INTO giftcards_orders (giftcard_id,amount,title) VALUES ('$cId','$amount','$title')");
	}
	function getCardUsedAmount($cId){
		$used = 0;
		$orders = $this->getCardOrders($cId);		
		foreach($orders as $o) if(!$o->refunded)$used += $o->used_amount;
		return $used;
	}
	function getCardOrders($cId){
		$orders = array();
			
		$sql = "SELECT o.*, go.timestamp AS used_timestamp, go.amount AS used_amount, go.title, go.id as charge_id, go.refunded FROM giftcards_orders AS go
							LEFT JOIN orders AS o ON o.id=go.order_id
							WHERE go.giftcard_id='$cId' ORDER BY go.timestamp DESC";				
		$q = mysql_query($sql);
		while($r = mysql_fetch_assoc($q)){
			$r['used_timestamp'] = tzdate('m/d/y h:i:s A',$r['used_timestamp']);
			$r['used_amount_formatted'] = number_format($r['used_amount'],2);
			$orders[] = (object)$r;
		}
		return $orders;
	}
	function getOrderCards($oId){		
		$cards = array();
		
		$q = mysql_query("SELECT * FROM giftcards_orders WHERE order_id='$oId' ORDER BY amount DESC");
		while($r = mysql_fetch_array($q)){
			$card = $this->getCard($r['giftcard_id']);
			$card->order_amount = $r['amount'];
			$card->ocid = $r['id'];
			$card->order_amount_formatted = number_format($card->order_amount,2);
			$card->order_timestamp = date('m/d/Y h:i A',strtotime($r['timestamp']));
			$card->ocid = $r['id'];
			
			$cards[] = $card;
		}
		return $cards;
	}
	function removeOrderCard($id){
		sql("DELETE FROM giftcards_orders WHERE id='$id'");
	}
	function undoOrderCard($id,$gcId,$amount){
		mysql_query("DELETE FROM giftcards_orders WHERE id='$id'");
		sql("UPDATE giftcards SET balance=balance+$amount WHERE id='$gcId'");
	}
	static public function getStatus(){
		$items = array();
	
		$q = mysql_query("SELECT DISTINCT status FROM giftcards");
		while(list($r) = mysql_fetch_array($q))
			if($r)$items[] = $r;
	
		return $items;
	}
	static public function getErrors(){
		$items = array();
		
		$q = mysql_query("SELECT DISTINCT last_check_error FROM giftcards");
		while(list($r) = mysql_fetch_array($q))
			if($r)$items[] = $r;
		
		return $items;
	}
	static public function getdefaultStores(){
		$stores = array();
		
		$q = mysql_query("SELECT DISTINCT store FROM giftcards WHERE user_id='".Users::getOwnerId()."' ORDER BY store ASC");
		while(list($s) = mysql_fetch_array($q))$stores[] = $s;
		return array_filter($stores);
	}
	static public function getPurchasedStores(){
		$stores = array();
		
		$q = mysql_query("SELECT DISTINCT store_purchased FROM giftcards WHERE user_id='".Users::getOwnerId()."' ORDER BY store_purchased ASC");
		while(list($s) = mysql_fetch_array($q))$stores[] = $s;
		return array_filter($stores);
	}
	static public function getUsedStores(){
		$stores = array();
		
		$q = mysql_query("SELECT DISTINCT store_used FROM giftcards WHERE user_id='".Users::getOwnerId()."' ORDER BY store_used ASC");
		while(list($s) = mysql_fetch_array($q))$stores[] = $s;
		return array_filter($stores);
	}
	
	
	
	
	
	
	
	
	

}
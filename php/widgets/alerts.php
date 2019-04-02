<?php
session_write_close();

uselib("alerts");
$alerts = new Alerts();

usehelper('ajax::dispatch');

function getNotifications(){
	global $alerts;
	
	global $alerts;
		
	$items = $alerts->get(array('notify'=>1),3);
	$ids = array();
	foreach($items as $i){ $ids[] = $i->id; }
	mysql_query("UPDATE alerts SET notify=0 WHERE id IN (".implode(",",$ids).")");
	json(array('items'=>$items));
}
function getList(){
	global $alerts;
		
	$items = $alerts->get(array('unread'=>1),5);
	json(array('items'=>$items));
}
function getCount(){
	global $alerts;
		
	$count = $alerts->getCount(array('unread'=>1));
	json(array('count'=>$count));
}
function read(){
	global $alerts;
	
	$id = (int)$_REQUEST['id'];
	
	$alerts->read($id);
	json();	
}
function search(){
	global $alerts;
	
	$items = $alerts->get($_REQUEST['filter'],30,$_REQUEST['page']);
	json(array('items'=>$items));
}
function remove(){
	global $alerts;
	
	$id = (int)$_REQUEST['id'];
	
	$alerts->remove($id);
	json();	
}
function removeAll(){
	global $alerts;
		
	$alerts->removeAll();
	json();		
}
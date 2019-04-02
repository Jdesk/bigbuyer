<?php
usehelper('ajax::dispatch');

$agents = getAgents();

function getAgent($id){ 
	$agents = getAgents(array($id));	
	return reset($agents);
}
function getAgents($ids=array()){		
	$agents = array();
	$wheresql = array();			
	#$wheresql[] = "parent_id = '{$_SESSION['user']->id}'";	
	$wheresql[] = "type_id != 1";	
	if(!empty($ids)) $wheresql[] = "u.id in ('".implode("','",$ids)."')";
		
	$query = mysql_query("SELECT u.* FROM users AS u					  
					  WHERE ".implode(" AND ",$wheresql)."
					  ORDER BY u.username ASC");
	while($r = mysql_fetch_assoc($query)){
		if($r['last_login'] == '0000-00-00 00:00:00') $r['last_login'] ='';
		else{ $r['last_login'] = date("m/d/y h:iA",strtotime($r['last_login'])); }
		#$r['permissions'] = explode(",",$r['permissions']);
		
		$r['settings'] = Users::getUserSettings($r['id']);
		
		
		$agents[] = (object)$r;
	}
	return $agents;
}
function save(){
	$id = (int)$_REQUEST['id'];	
	$owner = (int)$_REQUEST['owner'];
	
	if(!$_REQUEST['status']) $_POST['status'] = 0;
	if(!$_REQUEST['type_id']) $_POST['type_id'] = 3;
	$_POST['username'] = $_REQUEST['crm_username'];	
	unset($_POST['crm_username']);													//To avoid conflict with the AM.
		
	$_POST['parent_id'] = 0; //$_SESSION['user']->id; //Users::getOwnerId();
	$settings = $_POST['settings'];
	unset($_POST['settings']);
			
	$data = array_diff(array_keys($_POST),array("id","action","owner"));
	//if($owner)unset($data['permissions']);
	unset($data['permissions']);

	$columns = array();
	$values = array();
	foreach($data as $k){
		$columns[] = "`".$k."`";
		$values[] = "'".$_POST[$k]."'";
	}
						
	if(!$id){
		list($id) = mysql_fetch_array(mysql_query("SELECT id FROM users WHERE username='{$_POST['username']}'"));
		if($id) err('Username already exists!<br>Pick another username and try again.');
		
		mysql_query("INSERT INTO users (".implode(",",$columns).") VALUES (".implode(",",$values).")");
		if(mysql_error())err(mysql_error());
		
		$uId = mysql_insert_id();							
		//sendLoginInfo(false);
	}
	else{
		$update = array();
		foreach($columns as $i=>$c) $update[] = "$c = $values[$i]";
		mysql_query("UPDATE users SET ".implode(",",$update)." WHERE id='$id'");
		if(mysql_error())err(mysql_error());
		$uId = $id;			
	}	
	
	if($uId){
		if($settings){
			mysql_query("DELETE FROM user_settings WHERE user_id='$uId'");
			foreach($settings as $k=>$v){				
				mysql_query("INSERT INTO user_settings (`user_id`,`name`,`value`) VALUES ('$uId','$k','$v')");
			}
		}
		json();
	}
	else{
		err("Uknown Error");
	}	
}
function removeUser(){
	$id = $_REQUEST['id'];
	
	mysql_query("DELETE fROM bookmarks WHERE user_id='$id'");
	mysql_query("DELETE FROM groups WHERE user_id='$id'");
	mysql_query("DELETE FROM inventory WHERE user_id='$id'");
	mysql_query("DELETE FROM inventory_auto WHERE user_id='$id'");
	mysql_query("DELETE FROM inventory_events WHERE user_id='$id'");
	mysql_query("DELETE FROM inventory_events_row WHERE user_id='$id'");
	mysql_query("DELETE FROM inventory_events_stats WHERE user_id='$id'");
	mysql_query("DELETE FROM pricer_log WHERE user_id='$id'");
	mysql_query("DELETE FROM user_settings WHERE user_id='$id'");
	mysql_query("DELETE FROM users WHERE id='$id'");
	
	json();
}
function sendLoginInfo($ajax=true){	
	$email = $_REQUEST['email'];
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) if($ajax)err('Invalid email address');else return;
	
	uselib('notification');
	$notify = new Notfication();
	
	$message = array('file'=>$GLOBALS['system']['email_template_path'].'/loginInfo.phtml');
	$subject = $GLOBALS['site']['title'].' Login';			
	
	$data = $_REQUEST;
	
	$res = $notify->sendNotification($email,$subject,$message,$data);
	if($ajax){
		if($res) json();
		else err('Unable to send message!');
	}	
}

<?
if($_REQUEST['process']){
	$sql = "SELECT * FROM users WHERE username='$_REQUEST[crm_username]' AND password='$_REQUEST[crm_password]' AND status>0";	
	$query = mysql_query($sql);
	$user = mysql_fetch_assoc($query);
	if($user['id']){ login((object)$user); }
	else{ $error = 'Wrong username/password.'; }	
}
else if($_REQUEST['resetPassword']){
	$sql = "SELECT * FROM users WHERE username='$_REQUEST[crm_username]' AND status>0";
	$query = mysql_query($sql);
	$user = mysql_fetch_assoc($query);
	if($user['id']){ 
		resetPassword((object)$user); 
		$error = 'Your password was sent to the email we have in the system.'; 
	}	
	else
		$error = 'Username not found.'; 
}

?>
<?php

function login($user){
	uselib('users');
	mysql_query("UPDATE users SET last_login=NOW() WHERE id='{$user->id}'");

	Users::loadCurrentUserSession($user->id);
	$homepage = "./".$GLOBALS['site']['defaultpage'];
	//if(!$_SESSION['user']->billing && !$_SESSION['user']->parent_id) $homepage = 'tutorials#vintro';
	//if(in_array($_SESSION['user']->id,$GLOBALS['superusers'])) $_SESSION['user']->superadmin = true;
	
	//if($_SESSION['user']->superadmin){ $homepage = '/admin/billing'; }


	print "<script>window.location = '".$homepage."';</script>";
	exit;
}
function resetPassword($user){
	list($email) = mysql_fetch_array(mysql_query("SELECT email FROM biz WHERE user_id='$user->id'"));
	if(filter_var($email, FILTER_VALIDATE_EMAIL))
		email($email,"{$GLOBALS['site']['title']} Password","Your {$GLOBALS['site']['title']} password is: {$user->password} {$GLOBALS['site']['signature']}");
}
function logout(){
	$_SESSION['user']= null;
	//header("Location: /index.php");
	print "<script>window.location = './login.phtml';</script>";
	exit;
}
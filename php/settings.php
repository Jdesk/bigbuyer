<?php

usehelper('ajax::dispatch');

$system_settings = Users::getUserSettings();

function systemsave(){
	unset($_POST['action']);
	mysql_query("DELETE FROM user_settings WHERE user_id='{$_SESSION['user']->id}'");
	foreach($_POST as $k=>$v){ 
		mysql_query("INSERT INTO user_settings SET `name`='{$k}', `value`='{$_REQUEST[$k]}', user_id='{$_SESSION['user']->id}'");	
	}	
	json();
}
?>
<?php
$host = $GLOBALS['db_host'] ;
$username = $GLOBALS['db_user'];
$password = $GLOBALS['db_pass'];
$link = mysql_connect($host, $username, $password) or die(mysql_error()); 
mysql_select_db($GLOBALS['db_name']);
?>

<?php
set_time_limit(0);
session_write_close();
uselib('alerts');
//if(!$_GET['debug'])t('OFF');

$users = Users::getActiveUsers();
foreach($users as $uId){	
	try{
		$a = new Alerts($uId);
		$a->generateAlerts();
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}	
}
print "done";
exit;
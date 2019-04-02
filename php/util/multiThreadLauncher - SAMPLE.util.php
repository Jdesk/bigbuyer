<?php
set_time_limit(0);

$path = '/home/buying28/public_html/app/php';
include_once($path.'/helpers/cli.php');

uselib('process');
$util = 'pricer3.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util;
$debug = true;
do{		
	$users = Users::getActiveUsers();	
	foreach($users as $uId){
		//if($uId == 1374){ $threads = 2; }
		//else{ $threads = 1; }
		$threads = 2;
		
		t("User: ".$uId,1);
		
		
		//list($pId) = mysql_fetch_array(mysql_query("SELECT pricer_pid FROM users WHERE id='{$uId}'"));
		//if($debug)t("Checking: {$pId}",1);				
		//$running = checkRunningThread($pId);
		
		$curThreads = checkRunningThreads($uId);		
		if($debug)t($uId.': '.$curThreads.' threads running',1); 
		//if($running){
		if($curThreads >= $threads)continue; 		
		if($debug)t($uId.' starting thread',1);
		
		$process = new Process($cmd." '".json_encode(array('uId'=>$uId))."'");
		$process->start();
		$pId = $process->getPid();
		
		//mysql_query("UPDATE users SET pricer_pid='{$pId}' WHERE id='{$uId}'");
	}		
	sleep(3);
}while(1);
exit;

function checkRunningThread($pId){
	if(!$pId)return false;
	
	$p = new Process();
	$p->setPid($pId);
	return $p->status();
}
function checkRunningThreads($uId){
	global $debug;	
	$count = 0;
	
	$q = mysql_query("SELECT * FROM pricer_threads WHERE user_id='{$uId}' AND status=1");
	while($r = mysql_fetch_assoc($q)){
		$pId = $r['pid'];
		
		$pr = new Process();
		$pr->setPid($pId);
		$status = $pr->status();
		
		if($debug)t("Checking: $pId -> $status",1);
		
		if($status){ $count++; }
		else{ mysql_query("UPDATE pricer_threads SET status=0 WHERE id='{$r['id']}'"); }
	}	
	return $count;
}



/*
uselib('process');
$util = 'pricer3.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util;
$threads = 3;													//If this is changed, pricerLauncher.util.php needs to be killed.
$debug = false;
do{		
	$curThreads = countThreads($cmd);
	if($debug)t("Current Threads: ".$curThreads,1);
	while($curThreads < $threads){				
		$process = new Process($cmd);
		$process->start();		
		$curThreads = countThreads($cmd);
		if($debug)t("Current Threads: ".$curThreads,1);
	}	
	sleep(1);
}while(1);
exit;


if(check($util)){ print "Running...\n"; exit; }
exec($cmd . " > /dev/null &");
t("Launch");

function countThreads($cmd){
	$count = 0;
	exec("ps ax | grep .util",$data);
	foreach($data as $line){		
		if (strpos($line,$cmd) > 0)$count++;
	}
	return $count;
}
*/
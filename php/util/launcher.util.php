<?php
$path = '/home/buying28/public_html/php';
include_once($path.'/helpers/cli.php');


$util1 = 'giftcards.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util1;
if(check($util1)){ print "Running...\n"; }
else { t("Launch Util1",1); exec($cmd . " > /dev/null &"); }


/*
$util2 = 'ordersLauncher.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util2;
if(check($util2)){ print "Running...\n"; }
else { t("Launch Util2",1); exec($cmd . " > /dev/null &"); }
*/


$util3 = 'tiers.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util3;
if(check($util3)){ print "Running...\n"; }
else { t("Launch Util1",1); exec($cmd . " > /dev/null &"); }

$util4 = 'cancellations.util.php';
$cmd = 'php '.$GLOBALS['system']['util_path'].'/'.$util4;
if(check($util4)){ print "Running...\n"; }
else { t("Launch Util4",1); exec($cmd . " > /dev/null &"); }


function check($cmd){
	exec("ps ax | grep .util",$data);
	foreach($data as $line){
		//t($line,1);
		if (strpos($line,$cmd) > 0)return true;
	}
	return false;
}
	
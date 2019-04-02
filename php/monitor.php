<?php	
session_write_close();
usehelper("ajax::dispatch");

function killProc(){
	$pid = (int)$_REQUEST['pid'];	
	system("kill -9 $pid");
}
function getProcsStats(){
	$threshold = 1;				//hours
	$stats = array();
	
	$users = array();
	$q = mysql_query("SELECT id,username FROM users WHERE parent_id=0 AND status>0");
	while($u = mysql_fetch_array($q)) $users[$u['id']] = $u['username'];
				
	$pricers = array();
	$procs = explode("\n",shell_exec('ps -eo pid,etime,args | grep pricer3.util'));		
	foreach($procs as $p){
		list($pid,$time,$null,$cmd,$args) = preg_split("/\t+|\s+/",trim($p));
		$userId = preg_replace("/[^0-9]/","",$args);
		if(!$userId)continue;
			
		//t(preg_split("/\t+|\s+/",$p),1);
		//t($pid,1);
		//t($time,1);
		//t($cmd,1);
		//t($userId,1);
		//t("------------------",1);		
		
		$temp = explode(":",$time);
		if(count($temp) > 2){
			$hours = (int)preg_replace("/[^0-9]/","",array_shift($temp));			
			if($hours>=$threshold) $note = "Running for more than {$threshold} hours";
		}					
		
		$pricers[$userId] = array(
			'user'	=> ($users[$userId])?$users[$userId]:$userId,
			'time'	=> $time,
			'pid'	=> $pid,
			'note'	=> $note
		);
	}
	
	$invs = array();
	$procs = explode("\n",shell_exec('ps -eo pid,etime,args | grep inventory2.util'));		
	foreach($procs as $p){
		list($pid,$time,$null,$cmd,$args) = preg_split("/\t+|\s+/",trim($p));
		
		$userId = preg_replace("/[^0-9]/","",$args);
		if(!$userId)continue;
		
		$temp = explode(":",$time);
		if(count($temp) > 2){
			$hours = (int)preg_replace("/[^0-9]/","",array_shift($temp));			
			if($hours>=$threshold) $note = "Running for more than {$threshold} hours";
		}
				
		$invs[$userId] = array(
			'user'	=> ($users[$userId])?$users[$userId]:$userId,
			'time'	=> $time,
			'pid'	=> $pid,
			'note'	=> $note
		);
	}
	
	foreach($users as $id=>$username){	
		if(!$pricers[$id])$pricers[$id] = array('user'=>$username,'time'=>'-','0','');
		if(!$invs[$id])$invs[$id] = array('user'=>$username,'time'=>'-','0','');
	}	

	json(array('invs'=>$invs,'pricers'=>$pricers));		
}
function getUsersStats(){	
	$stats = (object)array('total'=>0,'users'=>array());
	
	$users = Users::getAllUsers();	
	foreach($users as $u){		
		$uId = $u['id'];
		//if($uId != 1371)continue;
		if(!$u['status'])continue;						
		
		$stats->users[$uId] = (object)array('uId'=>$uId, 'title'=>$u['username'],'rules'=>array(),'total'=>0);
		
		$wheresql = array();
		$wheresql[] = "ia.status =1";
		$wheresql[] = "e.date>Date(NOW())";		
		$wheresql[] = "ia.user_id='{$uId}'";

		$sql = "SELECT ia.*, e.date AS event_date FROM inventory_auto AS ia 
							LEFT JOIN inventory AS i ON i.id=ia.inv_id
							LEFT JOIN inventory_events AS e ON e.id=i.event_id							
							WHERE ".implode(" AND ",$wheresql);
		//t($sql);				
		$q = mysql_query($sql);
		
		$history = array();
		while($r = mysql_fetch_assoc($q)){
			if($r['group_id'] && $history[$r['group_id']])continue;
			
			$history[$r['group_id']] = 1;
			$days = xDaysAgo('now',$r['event_date'],'');
			$update = abs(xDaysAgo('now',$r['last_pricer_check'],''));
			
			$flag = false;
			//Pricer rules file
			include $GLOBALS['system']['script_path'].'/pricer_rules.php';
			//Pricer rules file
			
			
			foreach($pricer_rules as $rule){
				if($days>$rule['from'] && $days<$rule['to']){					
					$minutes = $rule['minutes'];
					if($rule['from']<0)$rule['from']=0;
					$rk = $rule['from'].' - '.$rule['to'];						
					if(!$stats->users[$uId]->rules[$rk])$stats->users[$uId]->rules[$rk] = (object)array('title'=>$rk,'tickets'=>0, 'checks'=>0);
					
					$checks = 1440/$minutes;
					
					$stats->users[$uId]->rules[$rk]->tickets++;
					$stats->users[$uId]->rules[$rk]->checks += $checks;
					$stats->users[$uId]->total += $checks;
					$stats->total += $checks;
				}
			}			
		}		
	}
	json(array('stats'=>$stats));
}
function getLiveStats(){
	$lastId = (int)$_REQUEST['lastId'];
	if(!$lastId) list($lastId) = mysql_fetch_array(mysql_query("SELECT MAX(id) FROM log_stubhub_requests"));
	
	$sql= "SELECT count(id) FROM log_stubhub_requests WHERE id>$lastId";
	list($count) = mysql_fetch_array(mysql_query($sql));		
	list($newId) = mysql_fetch_array(mysql_query("SELECT MAX(id) FROM log_stubhub_requests"));
	
	json(array('count'=>$count, 'lastId'=>$newId));
	return;
	
	
	$series = array();
	for($i=0;$i<rand(1,100);$i++){
		$series[] = array($i,rand(10,100));
	}
	json(array('series'=>$series));
}
function getInventoryStats(){
	$id = (int)$_REQUEST['id'];
	
	$stats = array(
		'checks'	=> 0,
		'updates'	=> 0,
		'prices'	=> array(),
		'series'	=> array(),
		'entries'	=> array(),
	);
	$q = mysql_query("SELECT * FROM pricer_log WHERE inv_id='$id' ORDER BY id DESC LIMIT 100");
	while($r = mysql_fetch_assoc($q)){
		if($r['action'] == 'update'){
			preg_match("/Repriced to (.*),/",$r['msg'],$matches);			
			if($matches){
				$stats['prices'][$r['timestamp']] = $matches[1];
			}
		}
		$r['timestamp'] = date('m/d/Y h:i:sA',strtotime($r['timestamp']));
		$stats['entries'][] = $r;
	}
	
	if($stats['prices']){
		$counter = 1;
		foreach($stats['prices'] as $ts=>$p){
			//$label = date('m/d/Y h:i:sA',strtotime($ts));
			//$stats['series'][] = array($label,(float)$p);
			$stats['series'][] = array($counter,(float)$p);			
			$counter++;
		}
	}
	
	list($stats['checks']) = mysql_fetch_array(mysql_query("SELECT count(id) FROM pricer_log WHERE action='check' AND inv_id='$id'"));
	list($stats['updates']) = mysql_fetch_array(mysql_query("SELECT count(id) FROM pricer_log WHERE action='update' AND inv_id='$id'"));
	
	json(array('stats'=>$stats));
}
function getEvents(){
	$uId = (int)$_REQUEST['userId'];
	$items = array();
	
	$q = mysql_query("SELECT * FROM inventory_events WHERE user_id='$uId' ORDER BY name ASC");
	while($r = mysql_fetch_assoc($q)){
		$r['title'] = $r['name'].' '.date('m/d/Y h:iA',strtotime($r['date']));
		$items[] = $r;
	}
	json(array('items'=>$items));
}
function getInventory(){
	$eId = (int)$_REQUEST['eventId'];
	$items = array();
	
	$q = mysql_query("SELECT * FROM inventory WHERE event_id='$eId'");
	while($r = mysql_fetch_assoc($q)){
		unset($r['data']);
		$r['title'] = 'Section: '.$r['section'].' Row: '.$r['row'].' Seats: '.$r['seat_min'].'-'.$r['seat_max'].' ('.$r['num'].')';
		$items[] = $r;
	}
	json(array('items'=>$items));
}
function getThreadsStats(){
	$stats = array();

	$pricers = trim(shell_exec('ps aux | grep pricer3.util | wc -l'));		
	$stats['pricers'] = $pricers-2;
	
	$invs = trim(shell_exec('ps aux | grep inventory2.util | wc -l'));
	$stats['invs'] = $invs-2;

	json(array('stats'=>$stats));
}
function getChecks(){
	$chartData = (object)array(
			'labels'	=> array(),
			'data'		=> array(),
			'series'	=> array()
	);
	
	#$range = array('2016-08-01','2016-08-08');
	$range = array_filter(explode(" - ",$_REQUEST['range']));
	if(empty($range)) $range = array(date('Y-m-d',strtotime("-5 days")),date('Y-m-d',strtotime("now")));
	if(count($range)<2)err("Error loading graph!");
	
	$data = array();

	$wheresql = array();
	$wheresql[] = "timestamp BETWEEN '".date('Y-m-d',strtotime($range[0]))." 00:00:00' AND '".date('Y-m-d',strtotime($range[1]))." 23:59:59'";
	if($_REQUEST['user_id'])$wheresql[] = "user_id='{$_REQUEST['user_id']}'";
	
	$sql = "SELECT DATE(timestamp) as tsdate, action, count(id) as num FROM pricer_log WHERE ".implode(" AND ",$wheresql)." GROUP BY DATE(timestamp), action ORDER BY DATE(timestamp) ASC";	
	//t($sql);
	$q = mysql_query($sql);
	while($r = mysql_fetch_assoc($q)){ 
		$date = strtotime($r['tsdate']);
		$dates[$date] = $date;
		if(!$data[$date])$data[$date]=array();
		
		if($r['action'] == 'update')
			$data[$date]['updates']=$r['num'];				
		else
			$data[$date]['checks']=$r['num'];						
	}	
	if($dates){
		foreach($dates as $key){
			$label = date('m/d/y',$key);
			$chartData->labels[] = $label;
			if(!$chartData->data[$label])$chartData->data[$label]=array('checks'=>0,'updates'=>0);
				
			if($data[$key]['checks'])$chartData->data[$label]['checks']+=$data[$key]['checks'];		
			if($data[$key]['updates'])$chartData->data[$label]['updates']+=$data[$key]['updates'];		
		}		
		
		$series = array();
		foreach($chartData->labels as $i=>$l){		
			$checks = ($chartData->data[$l]['checks'])?$chartData->data[$l]['checks']:0;
			$updates = ($chartData->data[$l]['updates'])?$chartData->data[$l]['updates']:0;
				
			$series[] = array($l,$checks,$updates);
		}
		$chartData->series = $series;
	}
	
	json(array('chartData' => $chartData));
}
function getRequests(){
	$chartData = (object)array(
			'labels'	=> array(),
			'data'		=> array(),
			'series'	=> array()
	);
	
	#$range = array('2016-08-01','2016-08-08');
	$range = array_filter(explode(" - ",$_REQUEST['range']));
	if(empty($range)) $range = array(date('Y-m-d',strtotime("-5 days")),date('Y-m-d',strtotime("now")));
	if(count($range)<2)err("Error loading graph!");
	
	$data = array();

	$wheresql = array();
	$wheresql[] = "timestamp BETWEEN '".date('Y-m-d',strtotime($range[0]))." 00:00:00' AND '".date('Y-m-d',strtotime($range[1]))." 23:59:59'";
	if($_REQUEST['user_id'])$wheresql[] = "user_id='{$_REQUEST['user_id']}'";
	
	$sql = "SELECT DATE(timestamp) as tsdate,count(id) as num, owner FROM log_stubhub_requests WHERE ".implode(" AND ",$wheresql)." GROUP BY DATE(timestamp), owner ORDER BY DATE(timestamp) ASC";		
	$q = mysql_query($sql);		
	while($r = mysql_fetch_assoc($q)){ 
		$date = strtotime($r['tsdate']);
		$dates[$date] = $date;
		if(!$data[$date])$data[$date]=array();
		
		if($r['owner'] == 'pricer')
			$data[$date]['pricer']=$r['num'];				
		else
			$data[$date]['other']=$r['num'];						
	}	
	if($dates){
		foreach($dates as $key){
			$label = date('m/d/y',$key);
			$chartData->labels[] = $label;
			if(!$chartData->data[$label])$chartData->data[$label]=array('pricer'=>0,'other'=>0);
				
			if($data[$key]['pricer'])$chartData->data[$label]['pricer']+=$data[$key]['pricer'];		
			if($data[$key]['other'])$chartData->data[$label]['other']+=$data[$key]['other'];		
		}		
		
		$series = array();
		foreach($chartData->labels as $i=>$l){		
			$pricer = ($chartData->data[$l]['pricer'])?$chartData->data[$l]['pricer']:0;
			$other = ($chartData->data[$l]['other'])?$chartData->data[$l]['other']:0;
				
			$series[] = array($l,$pricer,$other);
		}
		$chartData->series = $series;
	}	
	
	json(array('chartData' => $chartData));
}
function getSystemStats(){
	$stats = array();
	
	$exec_loads = sys_getloadavg();
	$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
	$stats['cpu'] = round($exec_loads[1]/($exec_cores + 1)*100, 2);	
	
	$exec_free = explode("\n", trim(shell_exec('free')));		
	$get_mem = preg_split("/[\s]+/", $exec_free[2]);
	$used = $get_mem[2];
	$free = $get_mem[3];
	$total = $used + $free;
	
	$stats['mem'] = round($used/$total*100, 2);		
	$stats['mem_gb'] = number_format(round($used/1024/1024, 2), 2) . '/' . number_format(round($total/1024/1024, 2), 2);
	
	json(array('stats'=>$stats));
}
function getPendingStats(){
	$stats = array();
	
	$total=0;
	$users = Users::getAllUsers();
	foreach($users as $u){			
		if(!$u['status'])continue;
		$uId = $u['id'];	
		
	
		$updates = array();	
	
		$items = array();

		$wheresql = array();
		$wheresql[] = "ia.status =1";
		$wheresql[] = "e.date>Date(NOW())";
		$wheresql[] = "ia.user_id='$uId'";		

		$sql = "SELECT e.*, e.date AS event_date, i.last_price_update,ia.last_pricer_attempt, ia.last_pricer_check FROM inventory_auto AS ia
					LEFT JOIN inventory AS i ON i.id=ia.inv_id
					LEFT JOIN inventory_events AS e ON e.sh_id=ia.sh_id										
					WHERE i.id IS NOT NULL AND ".implode(" AND ",$wheresql)."
					GROUP BY ia.id";
		//t($sql,1);
		$q = mysql_query($sql);
		while($r = mysql_fetch_assoc($q)){		
			$flag = false;
			//Pricer rules file
			include $GLOBALS['system']['script_path'].'/pricer_rules.php';
			//Pricer rules file			
						
			if($flag)$items[] = (object)$r;
		}
		
		//t(count($items),1);
									
		if($items){			
			$events = array();			
			$stats[$uId] =(object) array('uId'=>$uId,'title'=>$u['username'],'count'=>count($items), 'count_event'=>0,'events'=>array());
			
			foreach($items as $e){
				$total++;
				$count[$e->id]++;				
				if(!$events[$e->id])$events[$e->id] = (object)array('title'=>$e->name,'date'=>date('D M j Y, h:iA',strtotime($e->date)),'count'=>0);				
				$events[$e->id]->count++;								
			}									
			
			$count = array();
			foreach($events as $key=>$v)$count[$key] = $v->count;						
			array_multisort($count, SORT_DESC, $events);
			
			$stats[$uId]->events = $events;
			$stats[$uId]->count_event = count($events);
		}						 		
	}
	
	//t($total,1);
		
	json(array('stats'=>$stats, 'total'=>$total));
}








function getInactiveGa(){
	$entries = array();
	mysql_select_db($GLOBALS['SETTINGS']['system_data_db']);
	
	$sql = "SELECT o.*, p.*
						FROM `users_profiles` AS p		
						LEFT JOIN organization_region AS r ON r.region_id=p.profile_organization_region_id
						LEFT JOIN organization AS o ON o.organization_id=r.organization_id
						WHERE
							1=1
						GROUP BY p.profile_id";
	/*
	$sql = "SELECT o.*, p.*
						FROM `users_profiles` AS p		
						LEFT JOIN organization_region AS r ON r.region_id=p.profile_organization_region_id
						LEFT JOIN organization AS o ON o.organization_id=r.organization_id
						WHERE
							1=1
						GROUP BY p.profile_id";
	*/
	//t($sql);
	$q = mysql_query($sql);
	$sorter = array();
	while($r = mysql_fetch_assoc($q)){
		$r['latest'] = getLatestVisit($r['profile_id']);
		if(strtotime($r['latest'])>=strtotime("-".$GLOBALS['SETTINGS']['inactivity_period_alert']." days"))continue;
		
		if($r['latest']){
			$r['latest_error'] = getLatestError($r['profile_id'],$r['latest'],'ga'); 
			if(!$r['latest_error'])continue;

			$r['latest_error']['timestamp'] = date('m/d/Y h:iA',strtotime($r['latest_error']['timestamp']));			
			$message = $r['latest_error']['message'];
			$message = str_replace('Caught exception: ','',$message);
			if(strpos($message,'OAuth2') !== false) $message = 'Token Problem';
			$r['latest_error']['message'] = $message; 
		}					
		
		$r['latest'] = date('m/d/Y',tztotime($r['latest']));
		$r['organization_logo_url'] = urldecode($r['organization_logo_url']);
		//$entries[strtotime($r['latest'])] = $r;
		$sorter[] = strtotime($r['latest']);
		$entries[] = $r;
	}
	mysql_select_db($GLOBALS['db_name']);

	array_multisort($sorter, SORT_DESC, $entries);
	
	
	//krsort($entries);
	$entries = array_values($entries);

	json(array('entries'=>$entries));
}
function getLatestError($pId,$timestamp,$api){
	$latest = mysql_fetch_array(mysql_query("SELECT * FROM `log_progress` WHERE timestamp>'$timestamp' AND api='$api' AND profile_id='$pId' AND message like '%Caught exception%' ORDER BY id DESC LIMIT 1"));
	return $latest;	
}
function getLatestVisit($pId){
	list($latest) = mysql_fetch_array(mysql_query("SELECT MAX( visit_timestamp ) FROM `ga_visits_daily` WHERE visit_profile_id='$pId' LIMIT 1"));
	return $latest;
}
function getInactiveSocial(){
	$entries = array();
	mysql_select_db($GLOBALS['SETTINGS']['system_data_db']);
	$sql = "SELECT o.*, MAX( a.action_timestamp ) as latest, n.network_name, p.*
						FROM `social_actions_daily` AS a
						LEFT JOIN social_networks AS n ON n.network_id=a.action_network_id
						LEFT JOIN users_profiles AS p ON p.profile_id=a.action_profile_id
						LEFT JOIN organization_region AS r ON r.region_id=p.profile_organization_region_id
						LEFT JOIN organization AS o ON o.organization_id=r.organization_id
						WHERE 							 
							p.profile_id is not NULL AND a.action_network_id !=1
						GROUP BY a.action_profile_id, a.action_network_id 
UNION
SELECT o.*, MAX( a.fb_analytics_timestamp ) as latest, n.network_name, p.*
						FROM `facebook_social_analytics` AS a
						LEFT JOIN social_networks AS n ON n.network_id=1
						LEFT JOIN users_profiles AS p ON p.profile_id=a.fb_analytics_profile_id
						LEFT JOIN organization_region AS r ON r.region_id=p.profile_organization_region_id
						LEFT JOIN organization AS o ON o.organization_id=r.organization_id
						WHERE 		 							 
							p.profile_id is not NULL
						GROUP BY a.fb_analytics_profile_id
ORDER BY latest DESC";
	//t($sql);
	$q = mysql_query($sql);		
	while($r = mysql_fetch_assoc($q)){		
		if(strtotime($r['latest'])>=strtotime("-".$GLOBALS['SETTINGS']['inactivity_period_alert']." days"))continue;		
		$r['latest'] = date('m/d/Y',tztotime($r['latest']));
		$r['organization_logo_url'] = urldecode($r['organization_logo_url']);
		$entries[] = $r;
	}
	mysql_select_db($GLOBALS['db_name']);
	
	json(array('entries'=>$entries));	
}
function getOrphanProfiles(){
	$entries = array();
	mysql_select_db($GLOBALS['SETTINGS']['system_data_db']);
	$q = mysql_query("SELECT * FROM users_profiles AS p 
						LEFT JOIN organization_region AS r ON r.region_id=p.profile_organization_region_id
						LEFT JOIN organization AS o ON o.organization_id=r.organization_id
						WHERE o.organization_id is NULL
						GROUP BY p.profile_id 
						ORDER BY p.profile_id DESC");
	while($r = mysql_fetch_assoc($q)){
		$r['profile_timestamp'] = date('m/d/Y',tztotime($r['profile_timestamp']));
		$entries[] = $r;
	}	
	mysql_select_db($GLOBALS['db_name']);
	
	json(array('entries'=>$entries)); 
}
function removeOrphanProfile(){
	$id = $_REQUEST['id'];
	
	//err("DELETE FROM users_profiles WHERE profile_id='$id'");
	sql("DELETE FROM users_profiles WHERE profile_id='$id'");
}
function getStatus(){
	global $launcher;
	
	$status = $launcher->isActive();
	
	json(array(
		'status'=>($status)?1:0		
	));
}
function getFetchers(){
	global $launcher;
	
	json(array('fetchers'=>$launcher->getFetchers(array('range'=>getRange(-30)))));
}
function getProgressLog(){
	global $launcher;
	
	$entries = $launcher->getProgressLog((int)$_REQUEST['pid'],(int)$_REQUEST['lastId']);
	json(array('entries'=>$entries));
}

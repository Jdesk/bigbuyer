<?
uselib('process');

class Orders{ 
	private $timeout,
			$script,
			$start,
			$pid,
			$sid,
			$vars; 		   
    public function __construct($sid){    	
    	$this->sid = $sid;
    	$this->vars = $this->getVars();
    	$this->pid = 0;
    	$this->timeout = 60*60*10;
    	$this->script = $GLOBALS['system']['perl_path'] . '/getOrderStatus.pl';
    }
    function updateStatus($proxy=true,$headers=array()){ 
    	$account = $this->getAccount();    	
    	if(!$this->vars->remote_order_id || !$account) return false;
    	
    	$app = "perl {$this->script}";
    	$args = (object)array(
    		'orderId'		=> $this->vars->remote_order_id,
    		'lname'			=> end(explode(' ',$account->shipping_name)),
    		'phone'			=> $account->shipping_phone,
    	);;
    	    	    	
    	if($proxy)$args->proxy=getProxy();
    	//if($headers)$args->headers=base64_encode(json_encode($headers));
    	$args = json_encode($args);
    
    	$cmd = "{$app} '{$args}'";
    	//t($cmd);
    
    	exec($cmd,$res);    	    	    	
    	$res = implode("\n",$res);    	    	
    	$res = json_decode($res); 
    	
    	//t($res);

    	$this->logCheck($res);
    	
    	if($res){    		
    		$status = $res->order->enterprise->customerStatus;
    		$status2 = $res->order->enterprise->orderStatus;
    		$status3 = $res->order->enterprise->paymentStatus;

    		mysql_query("UPDATE orders SET remote_status='$status', remote_status2='$status2', remote_status3='$status3', last_status_update=NOW() WHERE id='{$this->vars->id}'");
    		
    		if(strtolower($status) == 'canceled'){
    			$q = mysql_query("SELECT * FROM giftcards_orders WHERE order_id='{$this->vars->id}' AND refunded=0");
    			while($r = mysql_fetch_assoc($q)){
    				mysql_query("UPDATE giftcards SET balance=balance+{$r['amount']} WHERE id='{$r['giftcard_id']}'");
    				mysql_query("UPDATE giftcards_orders SET refunded=1 WHERE id='{$r['id']}'");
    			}    			
    		}
    		
    		return true;
    	}
    	else{
    		return false;
    	}        	
   
       	/*
    	$cmd = "perl '{$this->script}' '".json_encode($this->vars)."'";
    	
    	if($debug)die($cmd);
    	//passthru($cmd,$res);
    	//t(exec($cmd),1);
    	//t($res);    	

    	$process = new Process($cmd);
    	$process->start();
    	$this->start = microtime();    	
    	$this->pid = $process->getPid();
    	
    	//$cmd = base64_encode($cmd);
    	//mysql_query("UPDATE searches SET `start`=NOW(), `status`='Working', `pid`='{$this->pid}',`cmd`='$cmd' WHERE id='{$this->sid}'");    	

    	$this->wait();
    	*/      	
    }
    function logCheck($data){
		$data = json_encode($data);
		
		$updatesql = array();
		$updatesql[] = "order_id='{$this->sid}'";		
		$updatesql[] = "data='".mysql_real_escape_string($data)."'";
		
		if($_SESSION['user']->id)$updatesql[] = "user_id='{$_SESSION['user']->id}'";
		else $updatesql[] = "user_id=null";
    	
    	
    	$sql = "INSERT INTO orders_checks SET ".implode(", ",$updatesql);
    	mysql_query($sql);    	
    }
    function getAccount(){
    	return (object)mysql_fetch_assoc(mysql_query("SELECT * FROM accounts WHERE id='{$this->vars->account_id}'"));
    }
    function getVars(){    	
    	return (object)mysql_fetch_assoc(mysql_query("SELECT * FROM orders WHERE id='{$this->sid}'"));
    }
    function wait(){
    	do{
    		$time = microtime() - $this->start;
    		sleep(1);			    		
    	}while($time<$this->timeout && $this->isActive());
    }
    function isActive(){    	
    	$process = new Process();
    	$process->setPid($this->pid);
    	$status = $process->status();
    	$status = ($status==true)?1:0;    
   		return $status;
    }
}
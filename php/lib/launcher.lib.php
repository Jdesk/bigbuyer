<?
uselib('process');

class Launcher{ 
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
    	$this->script = $GLOBALS['system']['phantom_path'] . '/scraper.js';
    }
    
    function start($debug=false){       	
    	$cmd = "/usr/local/bin/phantomjs '{$this->script}' '".json_encode($this->vars)."'";
    	
    	if($debug)die($cmd);
    	//passthru($cmd,$res);
    	//t(exec($cmd),1);
    	//t($res);    	

    	$process = new Process($cmd);
    	$process->start();
    	$this->start = microtime();    	
    	$this->pid = $process->getPid();
    	
    	$cmd = base64_encode($cmd);
    	mysql_query("UPDATE searches SET `start`=NOW(), `status`='Working', `pid`='{$this->pid}',`cmd`='$cmd' WHERE id='{$this->sid}'");    	

    	//$this->wait();      	
    }
    function getVars(){    	
    	return (object)mysql_fetch_assoc(mysql_query("SELECT * FROM searches WHERE id='{$this->sid}'"));
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
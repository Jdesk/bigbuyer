<?
class Process{
    private $pid;
    private $command;
	private $options;

    public function __construct($cl=false,$options=array()){
        if ($cl != false){
            $this->command = $cl;            
			$this->options = $options;
        }
    }
    private function runCom(){
        $command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
        exec($command ,$op);
        $this->pid = (int)$op[0];
    }

    public function setPid($pid){
        $this->pid = $pid;
    }

    public function getPid(){
        return $this->pid;
    }

    public function status(){
        $command = 'ps -p '.$this->pid;
        exec($command,$op);
        if (!isset($op[1]))return false;
        else return true;
    }

    public function start(){
        if ($this->command != '')$this->runCom();
        else return true;
    }

    public function stop(){
        $command = 'kill '.$this->pid;
        exec($command);
        if ($this->status() == false)return true;
        else return false;
    }
	
	public function getOption($key){
		return $this->options[$key];
	}
	
	public function wait($pid){	
		pcntl_waitpid ($pid,$status);
		return $status;	
	}
}
<?
abstract class Types extends BasicEnum {
    const GeneralError = 1;
    const GeneralWarning = 2;
    const PriceFloor = 3;
    const NoComps = 4;
    const NotPriced = 5;    
	const PriceDrop = 6;
	const OutlierCompete = 7;
}

Class Alerts{
	private $userId, $settings;
	public function __construct($uId=0){
		$this->userId = ($uId)?$uId:$_SESSION['user']->id;	
		
		$this->settings = (object)array(
			'alerts_rate'		=> 1
		);
	}	
	public function get($filter=array(),$limit=0, $offset=0){
		$res = $this->fetch($filter,$limit,$offset);
		return $res['alerts'];
	}
	public function getCount($filter=array()){
		$res = $this->fetch($filter,1000);
		if($res['total']>=1000) $res['total'] = '999+';
		return $res['total'];
	}
	public function fetch($filter=array(),$limit=0, $offset=0){
		$this->cleanup();
		$alerts = array();
		
		$offset = (int)$offset*(int)$limit;
		$limit = ((int)$limit)?" LIMIT $offset,$limit":'';
		
		$wheresql = array();
		$wheresql[] = "a.deleted=0";
		$wheresql[] = "a.user_id='{$this->userId}'";
		foreach($filter as $k=>$v){					
			if(!is_string($v) || trim($v)){
				switch($k){				
					case 'type':					
						$tId = Types::isValidName($v); 								
						$wheresql[] = "a.type_id = '$tId'";
						break;
					case 'range':					
						$range = explode(" - ",$v);						
						$wheresql[] = "a.`timestamp` BETWEEN '".dbdate($range[0])." 00:00:00' AND '".dbdate($range[1])." 23:59:59'";
						break;
					default:
						$wheresql[] = "a.`{$k}` = '$v'";
						break;
				}			
			}			
		}
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS t.*,a.* FROM alerts AS a
							LEFT JOIN alerts_types AS t ON t.id=a.type_id							
							WHERE ".implode(" AND ",$wheresql)."
							ORDER BY a.timestamp DESC
							$limit";
		//t($sql);
		$q = mysql_query($sql);
		list($total) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()"));
		while($r = mysql_fetch_assoc($q)){
			$alerts[] = $this->format($r);
		}		
		return array('alerts'=>$alerts,'total'=>$total);
	}
	private function cleanup(){
		mysql_query("DELETE FROM alerts WHERE timestamp <'".date('Y-m-d 00:00:00',strtotime("today"))."'");
		mysql_query("DELETE FROM alerts AS a LEFT JOIN inventory_events AS e ON e.id=a.event_id WHERE e.date < DATE(NOW())");
	}
	private function format($r){				
		$r['event'] = $this->getEvent($r['event_id']);
		$r['group'] = $this->getGroup($r['group_id']);
		$r['inventory'] = $this->getInventory($r['inv_id']);		
		return (object)$r;
	}
	private function getEvent($eId){
		if(!$eId)return false;
		
		$q = mysql_query("SELECT * FROM inventory_events WHERE id='$eId'");
		$r = mysql_fetch_assoc($q);		
		return $r;
	}
	private function getGroup($pId){
		if(!$pId)return false;
		
		$q = mysql_query("SELECT * FROM groups WHERE id='$pId'");
		$r = mysql_fetch_assoc($q);		
		return $r;
	}
	private function getInventory($iId){		
		if(!$iId)return false;
				
		$q = mysql_query("SELECT * FROM inventory WHERE id='$iId'");
		$r = mysql_fetch_assoc($q);		
		return $r;
	}
	public function create($type,$invId,$eventId,$groupId,$title,$description,$data=array()){
		//$res = Types::isValidValue(1);
		
		
		$tId = Types::isValidName($type); 		
		if(!$tId){ 			
			throw new Exception('Type not found');
			return false;
		}
		
		if(in_array($tId,array('5','3'))){
			$found = $this->checkAlertRate($type,$eventId,$invId);
			if($found)return false;
		}		
		
		$updatesql = array();
		$updatesql[] = "type_id='$tId'";
		$updatesql[] = "user_id='{$this->userId}'";
		$updatesql[] = "event_id='{$eventId}'";
		$updatesql[] = "inv_id='{$invId}'";
		$updatesql[] = "group_id='{$groupId}'";	
		$updatesql[] = "title='".mysql_real_escape_string($title)."'";
		$updatesql[] = "description='".mysql_real_escape_string($description)."'";
		$updatesql[] = "data='".base64_encode(json_encode($data))."'";
		
		$sql = "INSERT INTO alerts SET ".implode(", ",$updatesql);
		//t($sql);
		mysql_query($sql);
		$error = mysql_error();
		
		if($error){
			throw new Exception('Unexpected error');
			return false;
		}
		$id = mysql_insert_id();
		return $id;
	}
	public function checkAlertRate($type,$eventId,$invId){
		$tId = Types::isValidName($type); 										
		#t("SELECT id FROM alerts WHERE type_id='$tId' AND inv_id='$invId' AND event_id='$eventId' AND `timestamp` > DATE_SUB(now(), INTERVAL ".$this->settings->alerts_rate." DAY) LIMIT 1",1);
		list($alert) = mysql_fetch_array(mysql_query("SELECT id FROM alerts WHERE type_id='$tId' AND inv_id='$invId' AND event_id='$eventId' AND `timestamp` > DATE_SUB(now(), INTERVAL ".$this->settings->alerts_rate." DAY) LIMIT 1"));						
		return ($alert)?true:false;
	}
	public function read($id){
		mysql_query("UPDATE alerts SET unread = 0 WHERE id='$id'");
	}	
	public function remove($id){
		mysql_query("UPDATE alerts SET deleted=1 WHERE id='$id'");
	}
	public function removeAll(){
		mysql_query("UPDATE alerts SET deleted=1 WHERE user_id='{$this->userId}'");
	}
	public function clearInvAlerts($invId){
		mysql_query("UPDATE alerts SET deleted=1 WHERE inv_id='$invId'");		
	}
	
	public function generatePriceAlert($invId,$inv,$price){
		$newPrice = $price['price'];
		$oldPrice = $inv->cp;
		
		$diff = $newPrice - $oldPrice;
		$p = ($oldPrice)?100-abs((100*$newPrice)/$oldPrice):0;
		
		$this->clearInvAlerts($invId);
				
		if(!$price['comp']){			
			$title = "Inventory has no comps";			
			$this->create('NoComps',$invId,$inv->event_id,$inv->group_id,$title,'');
		}
		else{
			if($price['compIsOutlier']){
				$title = "Inventory competing with ourlier ({$price['compIsOutlier']}%)";
				$this->create('OutlierCompete',$invId,$inv->event_id,$inv->group_id,$title,'');
			}	
		}
		if($newPrice && abs($p)>=20){
			$msg = "Your invetory dropped ".number_format($p,0)."% from <b>$".number_format($oldPrice,2)."</b> to <b>$".number_format($newPrice,2)."</b>";
			$this->create('PriceDrop',$invId,$inv->event_id,$inv->group_id,'Significant price drop',$msg);													
		}		
	}
	
	public function generatePriceAlert2($invId,$inv,$price){
		$newPrice = $price['price'];
		$oldPrice = $inv->cp;
		
		$diff = $newPrice - $oldPrice;
		$p = 100-abs((100*$newPrice)/$oldPrice);
				
		
		/*
		t('ID: '.$invId,1);		
		t('Event: '.$eventId,1);
		t('Old Price: '.$oldPrice,1);
		t('New Price: '.$newPrice,1);
		t('Diff: '.$diff,1);
		t('Precent: '.$p,1);		
		t($price['comp']);
		*/
		
		if(!$price['comp']){			
			$title = "Inventory has no comps";			
			$this->create('NoComps',$invId,$inv['event_id'],$inv['group_id'],$title,'');
		}
		if($newPrice && $diff<0 && $p>=20){
			$msg = "Your invetory dropped ".number_format($p,0)."% from $".number_format($oldPrice,2)." to $".number_format($newPrice,2)."";
			$this->create('PriceDrop',$invId,$inv['event_id'],$inv['group_id'],'Significant price drop',$msg);													
		}		
	}			
	public function generateAlerts(){
		$events = array();
		$page = 0;
		//do{
			$found = false;
			$wheresql = array();			
			$wheresql[] = "u.status>0";
			$wheresql[] = "e.date>Date(NOW())";
			$wheresql[] = "i.user_id='{$this->userId}'";
			$sql = "SELECT i.*, ia.rank, ia.group_id, g.title AS group_title, ia.timestamp AS auto_timestamp, ia.id AS auto_id, e.name AS event_name, e.date AS event_date, e.event_id as pos_event_id, ia.pricing, ia.query, i.id AS alert_inv_id, ia.group_id AS alert_group_id FROM inventory AS i 						
						LEFT JOIN inventory_auto AS ia ON ia.inv_id=i.id
						LEFT JOIN groups AS g ON g.id=ia.group_id
						LEFT JOIN inventory_events AS e ON i.event_id=e.id
						LEFT JOIN users AS u ON i.user_id=u.id
						WHERE ".implode(" AND ",$wheresql)." 
						GROUP BY i.id";
						//ORDER BY timestamp DESC
						//LIMIT ".($page*100).",100";
			//t($sql);
			$q = mysql_query($sql);	
			while($r = mysql_fetch_assoc($q)){
				$found = true;				
								
				$item = formatInventory($r);
				
				//Check PriceFloor
				if($item['price']>0 && $item['price'] <= $item['pricing']->minValue){																				
					$type = 'PriceFloor';
					$title = "Inventory price at floor";					
					$msg = "Inventory just hit the floor at $".number_format($item['price'],2);																			
					$this->create($type,$item['alert_inv_id'],$item['event_id'],$item['alert_group_id'],$title,$msg);													
				}
				
				//check NotPriced
				if(!$item['price'] || $item['price']==0){					
					$type = 'NotPriced';					
					$title = "Inventory not priced";
					$msg = '';
					$this->create($type,0,$item['event_id'],0,$title,$msg);													
				}								
			}	
			$page++;	
		//}while($found);					
	}	
}

abstract class BasicEnum {
    private static $constCacheArray = NULL;

    private static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName($name) {
        $constants = self::getConstants();

        $keys = array_map('strtolower', array_keys($constants));		
		$index = array_search(strtolower($name), $keys);		
		
		$values = array_values(self::getConstants());		
		return $values[$index];
    }

    public static function isValidValue($value) {
        $values = array_values(self::getConstants());				
		$index = array_search(strtolower($value), $values);
		$keys = array_keys(self::getConstants());		
		return $keys[$index];		
    }
}
<?php

class Users {
	private $id, $users;
	public function __construct($admin_id){
		$this->id = $admin_id;		
	}
	public static function loadCurrentUserSession($id=0){
		if(!$id) $id = $_SESSION['user']->id;
		$user = self::getUsersData($id);	
		
		$user->admin=($user->type_id==1)?true:false;
		$_SESSION['user'] = (object)$user;
		$_SESSION['user']->loggedin = true;
		$_SESSION['user']->password = '';				
	}
	static public function getSettingsFields(){
		$fields = array();
		
		$q = mysql_query("SELECT DISTINCT name FROM user_settings");
		while(list($f) = mysql_fetch_array($q)) $fields[] = $f;
		
		return $fields;
	}
	static public function getUserSetting($userId,$name){
		list($value) = mysql_fetch_array(mysql_query("SELECT value FROM user_settings WHERE name='{$name}' AND user_id='{$userId}'"));
		return $value;
	}
	static public function getUserSettings($userId=0){
		if(!$userId)$userId = $_SESSION['user']->id;
		
		$fields = self::getSettingsFields();
		
		$settings = array();
												
		$query = mysql_query("SELECT * FROM user_settings WHERE user_id='{$userId}' ORDER BY name ASC");
		while($data = mysql_fetch_assoc($query)){		
			$settings[$data['name']] = $data['value'];	
		}
		
		foreach($fields as $f){
			if(!isset($settings[$f])) $settings[$f]='';
		}
		return $settings;
	}
	static public function getActiveUsers(){
		$uIds = array();
		
		$q = mysql_query("SELECT id FROM users WHERE parent_id=0 AND status>0");
		while(list($id) = mysql_fetch_array($q)) $uIds[] = $id;
		
		return $uIds;
	}
	static public function getAllUsers(){
		$users = array();
		
		$q = mysql_query("SELECT * FROM users WHERE parent_id=0 ORDER BY username ASC");
		while($r = mysql_fetch_array($q)) $users[] = $r;
		
		return $users;
	}
	private function loadUsers(){
		$this->users = array();
		
		$query = mysql_query("SELECT * FROM users WHERE parent_id='{$this->id}' OR id='{$this->id}' AND status>0"); 
		while($row = mysql_fetch_assoc($query)){
			$u['billing'] =  authorizeNET::getProfileData($row['id']);
			$this->users[] = (object)$row;
		}				
	}
	public function getUsers(){
		if(!$this->users) $this->loadUsers();
		
		return $this->users;
	}
	public static function getUsersData($userIds=array()){
		if(!is_array($userIds)) $userIds = array($userIds);				
		
		$users = array();
		$query = mysql_query("SELECT * FROM users WHERE id in ('".implode("','",$userIds)."') AND status>0");
		while($u = mysql_fetch_assoc($query)){
			//$u['billing'] =  authorizeNET::getProfileData($u['id']);
			$users[] = (object)$u;			
		}
		if(count($users) == 1)$users = reset($users);
		return $users;
	}
	public static function checkPermissions($permissions=array()){
		if(!is_array($permissions)) $permissions = array($permissions);
		
		if($_SESSION['user']->type_id == 1) return 1;

		$sql = "SELECT count(a.id) 
							  FROM users_permissions_assignments AS a			
							  LEFT JOIN users_permissions AS p ON p.id = a.permission_id				  
							  WHERE a.user_type_id='{$_SESSION['user']->type_id}' AND p.key in ('".implode("','",$permissions)."')
							  GROUP BY a.user_type_id
							";
		$query = mysql_query($sql);
		list($allowed) = mysql_fetch_array($query);
		return $allowed;		
	}
	
	public static function getOwnerId(){
		return ($_SESSION['user']->parent_id)?$_SESSION['user']->parent_id:$_SESSION['user']->id;
	}
}
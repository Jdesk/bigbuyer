<?php
uselib('users');

class Notfication {	
	private $template;
	
	public function __construct(){	
		$this->template = $GLOBALS['emails']['defaultTemplate'];
	}
	
	public function notify($userIds, $message, $subject, $data=array(), $notifyAdmins=false){
		if(!is_array($userIds)) $userIds = array($userIds);
		$userIds = array_unique($userIds);
		$users = Users::getUsersData($userIds);

		if($users){
			if(!is_array($users)) $users = array($users);
			foreach($users as $user){
				$data = array_merge((array)$user,$data);
				if($user->email){
					return $this->sendNotification($user->email,$subject,$message,$data);
					if($notifyAdmins){
						return $this->notifyAdminsEmails($message, $subject,$data);
					}
				}					
			}
		}	
	}
	
	public function notifyAdminsEmails($message, $subject, $data=array()){					
		foreach($GLOBALS['emails']['admins'] as $email){			
			return $this->sendNotification($email,'Admin Notification: '.$subject,$message,$data);			
		}
	}
	
	public function sendNotification($email, $subject, $message, $data){ 
		if(is_array($message))
			$message = $this->renderTemplate($message['file'],$data);
		else{
			$message = $this->replaceVariables($message,$data);				
			if($this->template){
				$message = $this->renderTemplate($this->template,array('content'=>$message));				
			}			
		}					
					
		$subject = $this->replaceVariables($subject,$data);

		$res = $this->smtp($email,$subject,$message);
		if(!$res)errorlog('error','Notification Error','Email Not Sent to '.$user->email.', File:'.__FILE__.', Line:'.__LINE__.', Function:'.__FUNCTION__);
		
		return $res;	
	}
	private function replaceVariables($str,$data){		
		foreach($data as $k=>$v){
			if(is_array($v) || is_object($v)){
				$str = $this->replaceVariables($str,$v);
			}
			else{				
				$str = str_replace('{'.$k.'}',$v,$str);
			}
		}
		return $str;
	}
	private function renderTemplate($path,$data){
		foreach($data as $k=>$v){  if(is_string($v)){$data[$k] = nl2br($data[$k]); $data[$k] = str_replace("\\r\\n","<br>",$data[$k]);} }
		ob_start();
		include($path);
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
	}
	private function email($emails=array(),$subject='',$message=''){
		if(!$emails) return;
		if(is_array($emails)) $emails = implode(",",$emails);
		
		$from = $GLOBALS['emails']['from'];
		$from_email = $GLOBALS['emails']['from_email']; 	
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: '.$from.' <'.$from_email.'>' . "\r\n";

		return mail($emails,$subject,$message, $headers);				
	}
	private function smtp($emails=array(),$subject='',$message=''){	
		$res = smtp($emails,$subject,$message);
		if(!$res)errorlog('error','Notification Error','Mailer Error: ' . $mail->ErrorInfo.', File:'.__FILE__.', Line:'.__LINE__.', Function:'.__FUNCTION__);
		return $res;
	}
	

}
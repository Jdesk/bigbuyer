<?php
$statesMaster = array('AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','DC'=>'District of Columbia','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming');

function getAllAccounts(){
	$items = array();

	$q = mysql_query("SELECT * FROM accounts");
	while($r = mysql_fetch_assoc($q)){
		$items[] = (object)$r;
	}		
	return $items;
}

//General
function errorlog($type,$source,$msg, $userId=0){
	if(!$userId)$userId = $_SESSION['user']->id;
	mysql_query("INSERT INTO log_errors (`user_id`,`type`,`source`,`msg`) VALUES ('$userId','$type','".mysql_real_escape_string($source)."','".mysql_real_escape_string($msg)."')");	
}
function includeWidget($widget, $params=array()){	
	$_REQUEST = $params;
	global $pagedata;
	$path = explode("::",$widget);
	
	$template = 'index.phtml';
	$file = array_pop($path);
	
	if(strpos($file,"~")===0){
		$template = $file.'.phtml';		
		$file = array_pop($path);
	}	
	
	$path = implode("/",$path);
	
	$widgetdata = array();
	$widgetdata['js'] = array();

	$template = $GLOBALS['widgets']['template_path'] . "/" . $path . "/" . $file . "/" . $template;	
	if(!file_exists($template)){
		$template = $GLOBALS['widgets']['template_path'] . "/" . $path . "/$file.phtml";
	}
	$widgetdata['content'] = $template;

	$js = $GLOBALS['widgets']['js_path'] . "/"  . $path . "/$file.js";		
	if(file_exists($js)){ $widgetdata['js'][] = $GLOBALS['widgets']['js_href'] . "/" . $path . "/$file.js".'?'.filemtime($js); }
	else{
		$js = $GLOBALS['widgets']['js_path'] . "/"  . $path . "/$file/index.js";		
		if(file_exists($js)){ $widgetdata['js'][] = $GLOBALS['widgets']['js_href'] . "/" . $path . "/$file/index.js".'?'.filemtime($js); }
	}	
	foreach($widgetdata['js'] as $js){
		foreach($widgetdata['js'] as $k=>$file){ $widgetdata['js'][$k] = $file; }
	}
	
	$script = $GLOBALS['widgets']['script_path'] . "/" . $path . "/$widget.php";
	if(file_exists($script)){ $widgetdata['script'] = $script; }
	
	if( $widgetdata['script'])
		include_once $widgetdata['script'];
	include_once $widgetdata['content'];

	$pagedata['js_default'] = array_merge($widgetdata['js'],$pagedata['js_default']);		
}
function mapTemplate($template){
	$template = explode("::",$template);
	$file = array_pop($template).'.phtml';
	return $GLOBALS['system']['template_path'].'/'.implode("/",$template).'/'.$file;
}
function includeTemplate($template){
	include mapTemplate($template);
}
function uselib($lib){
	$lib = explode("::",$lib);
	$file = array_pop($lib).'.lib.php';

	include_once($GLOBALS['system']['lib_path'].'/'.implode("/",$lib).'/'.$file);
}
function usehelper($helper){
	$helper = explode("::",$helper);
	$file = array_pop($helper).'.php';	
	include_once($GLOBALS['system']['helper_path'].'/'.implode("/",$helper).'/'.$file);
}
function t($var,$live=false){
	print '<pre>';
	var_dump($var);
	print '</pre>';
	if(!$live)
		exit;
}
function sql($sql,$msg='ok',$silent=false){
	if(gettype($msg) == 'boolean'){ $silent = $msg; $msg='ok'; }
	
	mysql_query($sql);
	$error = mysql_error();
	if($error){
		err($error);
	}
	else{	
		if(!$silent)	
			print json_encode(array('success'=>$msg));
	}
}
function json($data=''){
	if(empty($data)){
		$data = array('success'=>'ok');
	}
	$error = mysql_error();
	if(empty($error)){ print json_encode($data); }
	else{ err($error); }	
}
function err($msg,$ajax=1){	
	if($ajax){
		print json_encode(array('error'=>$msg));
	}
	else{
		displayError($msg);
	}
	exit;
}
function errs($errors,$ajax=1){	
	if(is_array($errors))$errors = array_filter($errors);	
	if(!$errors)return;
	
	if($ajax){				
		print json_encode(array('errors'=>$errors));
	}
	else{
		if(is_array($errors)){
			displayError(implode('<br>',$errors));
		}
		else{
			err($errors,0);
		}
	}
	exit;
}
function displayError($msg){
	print '<div class="alert alert-danger">'.$msg.'</div>';
}
function r($id,$msg,$error=false){
	if($error){ $type = 'error'; }
	else{ $type = 'message'; }		
}
function objectToArray($d){
	if (is_object($d)){ $d = get_object_vars($d); } 
	if (is_array($d)){ return array_map(__FUNCTION__, $d); }
	else { return $d; }
}
function arrayToObject($d){
	if (is_array($d)){ return (object) array_map(__FUNCTION__, $d); }
	else{ return $d; }
}
function email($emails,$subject,$message,$attachments=array(),$from='',$from_email=''){
	if($emails && !is_array($emails)) $emails = array($emails);
	if($attachments && !is_array($attachments)) $attachments = array($attachments);
	if(!$emails) return false;
	if(!$from){ $from = $GLOBALS['site']['title']; }
	if(!$from_email){ $from_email = $GLOBALS['emails']['from_email']; }
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";	
	$headers .= 'From: '.$from.' <'.$from_email.'>' . "\r\n";	
	
	$message = str_replace("\\r","",$message);
	$message = str_replace("\\n","",$message);
	$message = stripslashes($message);	
	
	return smtp($emails,$subject,$message,$from,$from_email);		
		
	//return mail($emails,$subject,$message, $headers);	
	//return mail('gontham@hotmail.com',$subject,$message, $headers);
	
	return sendgrid(array(
		'emails'=>$emails,
		'subject'=>$subject,
		'body'=>$message,
		'attachments'=>$attachments,
		'from'=>$from_email,
		'replyTo'=>$from_email,
		'fromName'=>$from 
	));		
	/*
	if($GLOBALS['emails']['smtp_host'])
		return smtp($emails,$subject,$message,$from,$from_email);		
	else
		return mail(implode(",",$emails),$subject,$message, $headers);	
	*/
}
function sendgrid($data){
	require_once("php/lib/sendgrid-php/sendgrid-php.php");
	
	$sendgrid = new SendGrid($GLOBALS['SETTINGS']['sendgrid_key']);
	$email    = new SendGrid\Email();

	if(!$from_name)$from_name=$from;
	
	$emails = $data['emails'];
	if(!$emails)return false;
	if(!is_array($emails))$emails = array($emails);
	
	$email->setTos($emails);
	//$email->setTos(array('gontham@hotmail.com'));	
	if($data['from'])$email->setFrom($data['from']);
	if($data['fromName'])$email->setFromName($data['fromName']);
	if($data['replyTo'])$email->setReplyTo($data['replyTo']);
	if($data['subject'])$email->setSubject($data['subject']);	
	foreach($data['attachments'] as $a)$email->addAttachment($a);
		
	if($data['body'])$email->setHtml($data['body']);
	
	$sendgrid->send($email);
	return true;
}
function smtp($emails=array(),$subject='',$message='',$from='',$from_email=''){	
	require 'lib/PHPMailer/PHPMailerAutoload.php';			
	$mail = new PHPMailer;
	
	if(!is_array($emails))$emails=array($emails);
		
	$mail->isSMTP();                                     		 // Set mailer to use SMTP
	$mail->Host = $GLOBALS['emails']['smtp_host'];			 // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                              		 // Enable SMTP authentication
	$mail->Username = $GLOBALS['emails']['smtp_username'];      // SMTP username
	$mail->Password = $GLOBALS['emails']['smtp_password'];      // SMTP password
	$mail->SMTPSecure = 'tls';                            		 // Enable encryption, 'ssl' also accepted	
	//$mail->SMTPDebug  = 2;
	
	$mail->From = ($from_email)?$from_email:$GLOBALS['emails']['from_email'];
	$mail->FromName = ($from)?$from:$GLOBALS['emails']['from'];
	foreach($emails as $to)
		$mail->addAddress($to);
		
	$mail->isHTML(true);                                  // Set email format to HTML
	
	$mail->Subject = $subject;
	$mail->Body    = $message;
	$res = $mail->send();			
	if(!$res) {		
		return false;
	} else {
		return true;
	}
}
function loadTemplateFile($file=''){
	$file = $GLOBALS['system']['template_path'].$file;	
	if(file_exists($file)){
		ob_start();
		include $file;
		$output = ob_get_clean();
	}
	return $output;
}
function strshorten($str,$len,$suffix='...'){	
	if(strlen($str)>$len){
		return substr($str,0,$len).$suffix;
	}
	else{
		return $str;
	}
}
function formatDate($timestamp,$format=''){	
	if(!$timestamp || strpos("0000-00-00",$timestamp)!==false)return '';
	if(!$format)$format = $GLOBALS['SETTINGS']['date_format_compact'];	
	return date($format,strtotime($timestamp));
}
function dbtimestamp($timestamp){
	if(!$timestamp || strpos("0000-00-00",$timestamp)!==false) return false;
	return date('Y-m-d H:i:s',strtotime($timestamp));
}
function dbdate($timestamp){
	if(!$timestamp || strpos("0000-00-00",$timestamp)!==false) return false;
	return date('Y-m-d',strtotime($timestamp));
}
function tzdate($format,$str,$tz=''){
	if(!$tz) $tz = $GLOBALS['system']['timezone'];
	return date($format,strtotime($str.' '.$tz));
}
function getUser($userID){
	$query = mysql_query("SELECT * FROM `users` WHERE `id` ='".$userID."'");
	return mysql_fetch_assoc($query);
}
function xTimeAgo ($oldTime, $newTime, $suffix='ago') {
	$timeCalc = strtotime($newTime) - strtotime($oldTime);
	if ($timeCalc > (60*60*24)) {$timeCalc = round($timeCalc/60/60/24) . " days $suffix"; }
	else if ($timeCalc > (60*60)) {$timeCalc = round($timeCalc/60/60) . " hours $suffix"; }
	else if ($timeCalc > 60) {$timeCalc = round($timeCalc/60) . " minutes $suffix"; }
	else if ($timeCalc > 0) {$timeCalc .= " seconds $suffix"; }
	return $timeCalc;
}
function xHoursAgo ($oldTime, $newTime, $suffix='ago') {
	$timeCalc = strtotime($newTime) - strtotime($oldTime);	
	$timeCalc = number_format($timeCalc/60/60,1) . " $suffix";	
	return $timeCalc;
}
function xDaysAgo ($oldTime, $newTime, $suffix='ago') {
	$timeCalc = strtotime($newTime) - strtotime($oldTime);
	$timeCalc = $timeCalc/60/60/24 . " $suffix";	
	return $timeCalc;
}
function pathUrl($path){	
	return str_replace($GLOBALS['system']['upload_path'],$GLOBALS['system']['upload_href'],$path);
}
function getProxy(){	
	list($proxy) = mysql_fetch_array(mysql_query("SELECT proxy FROM proxies WHERE status=1 ORDER BY rand()"));
	return $proxy;
}
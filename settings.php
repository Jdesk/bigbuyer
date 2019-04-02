<?php
date_default_timezone_set('America/Los_Angeles');

###########################DB Settings
$GLOBALS['db_host'] = 'localhost';
$GLOBALS['db_user'] = 'buying28_main';
$GLOBALS['db_pass'] = 'Zit#vHvuYk%+';
$GLOBALS['db_name'] = 'buying28_main';


###########################Site Settings
$GLOBALS['site']['title'] = 'BB Buyer';
$GLOBALS['site']['url'] = 'http://173.231.213.119/~buying28/';
$GLOBALS['site']['redirect'] = 'http://173.231.213.119/~buying28/';
$GLOBALS['site']['defaultpage'] = 'orders';
$GLOBALS['site']['signature'] = "<br><br>Thanks,<br>{$GLOBALS['site']['title']} team";


##########################System Settings
$debug = false;
$debug = true;
if($debug){
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
	ini_set('display_errors', '1');		
}

$GLOBALS['system']['path'] 					= '/home/buying28/public_html/';
$GLOBALS['system']['js_path'] 				= 'js';
$GLOBALS['system']['template_path'] 		= 'template';
$GLOBALS['system']['email_template_path'] 	= $GLOBALS['system']['path'].$GLOBALS['system']['template_path'].'/emails';
$GLOBALS['system']['script_path'] 			= $GLOBALS['system']['path'].'php';
$GLOBALS['system']['href_base'] 			= '/~buying28/site/';
$GLOBALS['system']['lib_path'] 				= $GLOBALS['system']['path'].'php/lib';
$GLOBALS['system']['util_path'] 			= $GLOBALS['system']['path'].'php/util';
$GLOBALS['system']['helper_path'] 			= $GLOBALS['system']['path'].'php/helpers';
$GLOBALS['system']['tmp_path']				= $GLOBALS['system']['path'] .'temp';
$GLOBALS['system']['csvtemplate_path']		= $GLOBALS['system']['path'] .'csvTemplates';
$GLOBALS['system']['upload_path']			= $GLOBALS['system']['path'] .'uploads';
$GLOBALS['system']['upload_href']			= $GLOBALS['system']['href_base'] .'uploads';
$GLOBALS['system']['public_path'] 			= 'public';
$GLOBALS['system']['images_path']			= $GLOBALS['system']['path'] .'images/';
$GLOBALS['system']['images_href']			= $GLOBALS['system']['href_base'] .'images/';
$GLOBALS['system']['phantom_path']			= $GLOBALS['system']['path'] .'phantomjs/';
$GLOBALS['system']['perl_path']		  		= $GLOBALS['system']['path'] .'perl/';


$GLOBALS['system']['font_path']				= $GLOBALS['system']['path'] .'fonts';

##########################Emails Settings
$GLOBALS['emails']['from'] 					= $GLOBALS['site']['title'];
$GLOBALS['emails']['from_email'] 			= '';
$GLOBALS['emails']['smtp_host'] 			= '';
$GLOBALS['emails']['smtp_username'] 		= '';
$GLOBALS['emails']['smtp_password'] 		= '';
$GLOBALS['emails']['admins']				= array('gontham.inc@gmail.com');
$GLOBALS['emails']['defaultTemplate']		= $GLOBALS['system']['email_template_path'] . '/default.phtml'; 

##########################Widgets
$GLOBALS['widgets']['js_path'] 				= 'js/widgets';
$GLOBALS['widgets']['js_href'] 				= $GLOBALS['widgets']['js_path'];
$GLOBALS['widgets']['script_path'] 			= $GLOBALS['system']['script_path'].'/widgets';
$GLOBALS['widgets']['template_path'] 		= $GLOBALS['system']['template_path'].'/widgets';


##########################Page Settings
$GLOBALS['pages']['public']					= array('login','resetpassword');

##########################Admin Settings
$GLOBALS['superusers'] = array(1);
$GLOBALS['SETTINGS'] = array();

##########################POS Settings
$GLOBALS['POS']['list'] = array('Skybox','Ticket Utils');

<?
//Set request Type.
$REQUEST = new stdclass();
$REQUEST->type = $_REQUEST['_type'];
$REQUEST->page = $_REQUEST['_request'];
$REQUEST->request = $_REQUEST['_request'];
$REQUEST->ajaxify = true;
$REQUEST->admin = ($_REQUEST['_admin'])?'admin/':'';

include_once 'settings.php';
include_once 'includes/php/main.php';
include_once 'php/main.php';

$node = $GLOBALS['menu']->getNode($REQUEST->request);
if($node->url){ header("Location: ".$node->url); exit; }
if($node->redirect){	
	$REQUEST->request = $node->redirect;
}

if($node->admin && !$_SESSION['user']->admin){ header("Location: ".$GLOBALS['system']['href_base'].$GLOBALS['site']['defaultpage']); exit; }
if($node->biz && $_SESSION['user']->type_id>2){ header("Location: ".$GLOBALS['system']['href_base'].$GLOBALS['site']['defaultpage']); exit; }
if($node->query){
	parse_str($node->query,$q);
	if(is_array($q))foreach($q as $k=>$v){
		$_GET[$k] = $v;
		$_REQUEST[$k] = $v;
	}
}


if(in_array($_SESSION['user']->id,$GLOBALS['superusers'])) $_SESSION['user']->superadmin = true;
if($REQUEST->admin && !$_SESSION['user']->superadmin)t('Unauthorized Access');

if($REQUEST->type == 'util'){
	include_once $GLOBALS['system']['util_path'] . '/' . $REQUEST->request .'.util.php';	
	exit;		
}
else if($REQUEST->request == 'login' ||  !$_SESSION['user']->loggedin || $_SESSION['user']->loggedin<=0){	
	if($_SESSION['user']->loggedin){
		header("Location: ".$GLOBALS['system']['href_base'].$GLOBALS['site']['defaultpage']);
		exit;
	}
	else{
		$pagedata['title'] = "Agent Login";
		$pagedata['script'] = $GLOBALS['system']['script_path'] . '/login.php';
		$pagedata['content'] = $GLOBALS['system']['template_path'] . '/default/login.phtml';	
		
		$REQUEST->ajaxify = false;			
	}
}
else if($REQUEST->type == 'public'){
	if(!$REQUEST->request)$REQUEST->request = 'index';
	include_once $GLOBALS['system']['public_path'] . '/' . $REQUEST->request .'.phtml';
	exit;
}
else if($REQUEST->type == 'store'){
	if(!$REQUEST->request)$REQUEST->request = 'index';
	include_once $GLOBALS['system']['store_path'] . '/' . $REQUEST->request .'.phtml';
	exit;
}
else if($REQUEST->request == 'logout'){
	logout();
}
else if($REQUEST->type == 'widget'){	
}
else if($REQUEST->type == 'ajax'){
	$pages = explode("/",$_REQUEST['_admin'] . $REQUEST->request);
	
	foreach($pages as $page){
		$script = $_REQUEST['_admin'] . $GLOBALS['system']['script_path'] . "/$page.php";
		if(file_exists($script)){ include $script; }
	}
	
	include $REQUEST->request;
	exit;
}
else{
	//Default JS for loggedin users
	$pagedata['js'] = array();
	$pagedata['js_default'] = array();			
	//$pagedata['js_default'][] = '../assets/js/notifications.js';

	$page = $REQUEST->request;
		
	$template = $REQUEST->admin . $GLOBALS['system']['template_path'] . "/$page/index.phtml";
	if(!file_exists($template)){
		$template = $REQUEST->admin . $GLOBALS['system']['template_path'] . "/$page.phtml";
	}

	if(file_exists($template)){		
		$script = $REQUEST->admin . $GLOBALS['system']['script_path'] . "/$page.php";
		if(file_exists($script)){ $pagedata['script'] = $script; }
								
		$js = $REQUEST->admin . $GLOBALS['system']['js_path'] . "/$page.js";
		if(file_exists($js)){ $pagedata['js'][$js] = '/'.$js; }
		
		$pagedata['content'] = $template;
	}
	else{ 
		$pagedata['content'] = $GLOBALS['system']['template_path'] . "/errors/404.phtml";
	}
}
if($pagedata['js']){
	foreach($pagedata['js'] as $file=>$url){		
		$ts = (file_exists($file))?filemtime($file):'';
		$pagedata['js'][$file] = $url.'?'.$ts;
	}
}
?>
<?
if($pagedata['script']) include $pagedata['script'];
include 'template/default/head.phtml';

if($REQUEST->type == 'widget'){
	t('Not Implemented');		
}
else{
		
	include 'template/default/header.phtml';	
	if($_SESSION['user']->loggedin){
		include 'template/~frame.phtml';
	}
	else{			
		include $pagedata['content'];
	}	
	include 'template/default/foot.phtml';	
}
?>

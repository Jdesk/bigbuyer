<?php 
//Ajax
if($_REQUEST['action']){		
	$function = $_REQUEST['action']; 
			
	if(function_exists($function)){ $function(); }
	else{ err('Function not found.'); }	
	exit;
}

?>
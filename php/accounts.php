<?php
session_write_close();

usehelper("ajax::dispatch");

function removeAccount(){
	$id = (int)$_REQUEST['id'];
	sql("UPDATE accounts SET deleted=1, status=0 WHERE id='$id'");
}
function removeAccounts(){
	$ids = $_REQUEST['ids'];
	if(!$ids)err("Accounts not found!");
	
	sql("UPDATE accounts SET deleted=1, status=0 WHERE id IN ('".implode("','",$ids)."')");
}
function saveAccount(){
	$id = $_REQUEST['id'];
	
	$updatesql = array();
	
	unset($_POST['action']);
	unset($_POST['id']);
	foreach(array_keys($_POST) as $k){		
		$updatesql[] = "`$k`='{$_REQUEST[$k]}'";
	}	
	
	if($id){
		sql("UPDATE accounts SET ".implode(",",$updatesql)." WHERE id='$id'");
	}
	else{		
		list($found) = mysql_fetch_array(mysql_query("SELECT id FROM accounts WHERE email='{$_REQUEST['email']}'"));
		if($found) err("Account already exists in the system");		
		
		sql("INSERT INTO accounts SET ".implode(",",$updatesql)."");
	}
}
function getAccount(){
	$id = (int)$_REQUEST['id'];
	
	$q = mysql_query("SELECT * FROM accounts WHERE id='$id'");
	$item = mysql_fetch_assoc($q);
	$item = formatAccount($item);
	
	json(array('item'=>$item));
}
function loadAccounts(){
	$sortColumns = array('timestamp','email','shipping_address1','status','','','','');	
		
	$array = array();
	
	$offset = (int)$_REQUEST['start'];
	$length = (int)$_REQUEST['length'];
	
	if($_REQUEST['order'])$orderby = array('col'=>$sortColumns[$_REQUEST['order'][0]['column']],'dir'=>$_REQUEST['order'][0]['dir']);
	
	$offset = $offset;
	$length = (int) $length;
	if ($length)
		$limit = "LIMIT $offset,$length";
	else
		$limit = "";
	
	if (!$orderby)
		$orderby = array('timestamp DESC');
	else
		$orderby = array($orderby['col'] . " " . $orderby['dir']);
					
	$wheresql = array();
	$wheresql[] = "deleted=0";
	$filter = $_REQUEST['filter'];
	if($filter){
		foreach($filter as $k=>$v){			
			if(trim($v)){
				switch($k){
					case 'q':
						$cols = array('email','shipping_address1','shipping_city','shipping_state','shipping_zip','shipping_phone','billing_address1','billing_city','billing_state','billing_zip','billing_phone');
						$orsql = array();
						foreach($cols as $c) $orsql[] = "$c like '%$v%'";
						$wheresql[] = "(".implode(" OR ",$orsql).")";
						break;
					default:
						$wheresql[] = "`$k` = '$v'";
						break;
				}
			}
		}
	}	
	
	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM accounts WHERE ".implode(" AND ",$wheresql)." ORDER BY  " . implode(' ', $orderby) . " $limit";
	//t($sql);
	$q = mysql_query($sql);
	list($total) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS();"));
	while($r = mysql_fetch_assoc($q)){
		$array[] = formatAccount($r);
	}
	json(array(
		'sql' => $sql,
		'data'=> $array,
		'total' => $total,
		'page' => $offset,
		'sort'	=> ($sortby)?$sortby['col']:$_REQUEST['order'][0]['column'],
		'sortDir' => ($sortby)?$sortby['dir']:$_REQUEST['order'][0]['dir'],
		'length' => $length,
	));
}
function formatAccount($r){	
	$r['timestamp'] = date('m/d/Y h:i:s a',strtotime($r['timestamp']));	
	list($r['orders_count']) = mysql_fetch_array(mysql_query("SELECT count(id) FROM orders WHERE account_id='{$r['id']}'"));	
	list($r['canc_count']) = mysql_fetch_array(mysql_query("SELECT count(id) FROM orders WHERE account_id='{$r['id']}' AND status='cancelled'"));
	return (object)$r;
}
function importAccounts(){
	$csv_mimetypes = array(
			'text/csv',
			'text/plain',
			'application/csv',
			'text/comma-separated-values',
			'application/excel',
			'application/vnd.ms-excel',
			'application/vnd.msexcel',
			'text/anytext',
			'application/octet-stream',
			'application/txt',
	);

	$error = '';
	if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) $error = "Upload failed with error code " . $_FILES['file']['error'];
	else if ($info === FALSE) $error = "Unable to type of uploaded file";
	else if (!in_array($_FILES['file']['type'], $csv_mimetypes)) $error = "Invalid file uploaded";
	else if ($_FILES['file']['error'] != 0) $error = "Unkown Error";

	if($error){
		if(file_exists($_FILES["file"]["tmp_name"]))unlink($_FILES["file"]["tmp_name"]);
		err($error);
	}

	$items = array();
	if (($handle = fopen($_FILES["file"]["tmp_name"], "r")) !== FALSE) {
		while (($row = fgetcsv($handle, 5000, ",")) !== FALSE) {
			$items[] = array($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$row[14],$row[15],$row[16],$row[17],$row[18],$row[19]);
		}
	}
	fclose($handle);
	unlink($_FILES["file"]["tmp_name"]);

	$stats = array('done'=>array(), 'errors'=>array());
	foreach($items as $item){
		$email = $item[0];
		$password = $item[1];
		$shipping_name = $item[2];
		$shipping_address = $item[3];
		$shipping_address2 = $item[4];
		$shipping_city = $item[5];
		$shipping_state = $item[6];
		$shipping_zip = $item[7];
		$shipping_phone = $item[8];
		$cc_num = $item[9];
		$cc_exp_month = $item[10];
		$cc_exp_year = $item[11];
		$cc_exp_cvv = $item[12];
		$billing_name = $item[13];
		$billing_address = $item[14];
		$billing_address2 = $item[15];
		$billing_city = $item[16];
		$billing_state = $item[17];
		$billing_zip = $item[18];
		$billing_phone = $item[19];		
				

		if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !$shipping_address || !$shipping_city || !$shipping_state || !$shipping_zip || !$shipping_phone){
			$stats['errors'][] = $email;
		}
		else{
			mysql_query("INSERT INTO accounts SET 
								tier = '',
								email = '{$email}', 
								password = '{$password}',
								shipping_name = '{$shipping_name}',
								shipping_address1 = '{$shipping_address}',
								shipping_address2 = '{$shipping_address2}',
								shipping_city = '{$shipping_city}',
								shipping_state = '{$shipping_state}',
								shipping_zip = '{$shipping_zip}',
								shipping_phone = '{$shipping_phone}',
								cc_num = '{$cc_num}',
								cc_exp_month = '{$cc_exp_month}',
								cc_exp_year = '{$cc_exp_year}',
								cc_cvv = '{$cc_exp_cvv}',
								billing_name = '{$billing_name}',
								billing_address1 = '{$billing_address}',
								billing_address2 = '{$billing_address2}',
								billing_city = '{$billing_city}',
								billing_state = '{$billing_state}',
								billing_zip = '{$billing_zip}',
								billing_phone = '{$billing_phone}'
								");
			$stats['done'][] = $email;
		}

	}
	json(array('stats'=>$stats));
}
function downloadTemplate(){
	$filename = 'Accounts Import File - Template.csv';
	$path = $GLOBALS['system']['csvtemplate_path'].'/'.$filename;

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename='.$filename);
	readfile($path);	
}
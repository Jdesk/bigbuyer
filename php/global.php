<?php
uselib('users');
uselib("sessions");
uselib('menu');
uselib('users'); 

foreach($_REQUEST as $k=>$v){ if(!is_array($v)){$_REQUEST[$k] = mysql_real_escape_string($v);} }

$GLOBALS["states"] = array("AL" => "Alabama","AK" => "Alaska","AS" => "American Samoa","AZ" => "Arizona","AR" => "Arkansas","AF" => "Armed Forces Africa","AA" => "Armed Forces Americas","AC" => "Armed Forces Canada","AE" => "Armed Forces Europe","AM" => "Armed Forces Middle East","AP" => "Armed Forces Pacific","CA" => "California","CO" => "Colorado","CT" => "Connecticut","DE" => "Delaware","DC" => "District of Columbia","FM" => "Federated States Of Micronesia","FL" => "Florida","GA" => "Georgia","GU" => "Guam","HI" => "Hawaii","ID" => "Idaho","IL" => "Illinois","IN" => "Indiana","IA" => "Iowa","KS" => "Kansas","KY" => "Kentucky","LA" => "Louisiana","ME" => "Maine","MH" => "Marshall Islands","MD" => "Maryland","MA" => "Massachusetts","MI" => "Michigan","MN" => "Minnesota","MS" => "Mississippi","MO" => "Missouri","MT" => "Montana","NE" => "Nebraska","NV" => "Nevada","NH" => "New Hampshire","NJ" => "New Jersey","NM" => "New Mexico","NY" => "New York","NC" => "North Carolina","ND" => "North Dakota","MP" => "Northern Mariana Islands","OH" => "Ohio","OK" => "Oklahoma","OR" => "Oregon","PW" => "Palau","PA" => "Pennsylvania","PR" => "Puerto Rico","RI" => "Rhode Island","SC" => "South Carolina","SD" => "South Dakota","TN" => "Tennessee","TX" => "Texas","UT" => "Utah","VT" => "Vermont","VI" => "Virgin Islands","VA" => "Virginia","WA" => "Washington","WV" => "West Virginia","WI" => "Wisconsin","WY" => "Wyoming");

//Load Menu
$menu =  array(
	'orders' => array(
			'title' => 'Orders',
			'icon' => 'fa fa-list',
			'submenu' => array()
	),
	'accounts' => array(
		'title' => 'Accounts',
		'icon' => 'fa fa-users',
		'submenu' => array()	
	),
	'giftcards' => array(
		'title' => 'Giftcards',
		'icon' => 'fa fa-credit-card',
		'submenu' => array()
	),
	/* 
	'team' => array(
		'title' => 'Users',
		'icon' => 'fa fa-users',
		'admin'=>1,
		'submenu' => array()
		
	),
	*/ 
	'settings' => array(
		'title' => 'Settings',
		'icon' => 'fa fa-cogs',		
		'submenu' => array()
	)	
);
$GLOBALS['menu'] = new Menu($menu);

//Load Admin Settings

/*
$query = mysql_query("SELECT * FROM admin_settings");
while($data = mysql_fetch_assoc($query)){		
	$GLOBALS['SETTINGS'][$data['name']] = $data['value'];	
}
*/


//Load Admin Settings
$GLOBALS['SETTINGS'] = Users::getUserSettings();
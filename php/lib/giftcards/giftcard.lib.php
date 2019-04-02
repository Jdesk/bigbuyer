<?php
//uselib('decaptcher::decaptcher');
//uselib('simpletest::browser');

class Giftcard {
	public function __construct(){	
		
	}
	
	public function submitRequest($url,$data,$headers=array(),$proxy=false){	
		if($proxy){
			list($proxy) = mysql_fetch_array(mysql_query("SELECT proxy FROM proxies WHERE status=1 ORDER BY rand()"));	
			if (php_sapi_name() == "cli")t($proxy,1);
		}
				
										
		$ch = curl_init();		
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");		
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); //timeout in seconds
		
		
		if($proxy)curl_setopt($ch, CURLOPT_PROXY, $proxy);
		if($headers)curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		 
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		$response = curl_getinfo($ch);
		$result = curl_exec($ch);
						
		//$headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT );
		//t($headerSent,1);

		curl_close($ch);
		
		
		t($result);
		
		return $result;
	}

}
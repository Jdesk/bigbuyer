<?php
uselib('giftcards::giftcard');

class BestbuyCard extends Giftcard {
   var $settings;
  
    public function __construct(){
		$this->settings = (object)array(
			'store'		=> 'Bestbuy',
			//'url'		=> 'https://www-ssl.bestbuy.com/gift-card-balance/api/lookup',
			'url'		=> 'https://www.bestbuy.com/gift-card-balance/api/lookup',
			'proxy'		=> true,
			'headers' 	=> array( 					
							'Content-type: application/json',	
							'accept-encoding: gzip;q=0,deflate,sdch',
							//'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
							'User-Agent: curl/7.29.0',
							),
		);		
	}
	public function getBalance($card,$pin){
		if(!$card || !$pin){ return false; }
		
		//$token = getReCaptchaToken('https://m.sears.com/giftCard/checkBalance');
		//if(!$token) return "Unable to get recaptcha token";
		//t("HERER: ".$token,1);
		
		$balance = false;

		/*
		$data = json_encode(array(
			'cardNumber' => trim($card),
			'pin' => trim($pin)			
		));				
		$res = $this->submitRequest($this->settings->url,$data,$this->settings->headers,$this->settings->proxy);
		*/
		
		$this->script = $GLOBALS['system']['perl_path'] . '/checkBalance.pl';
		$app = "perl {$this->script}";
		$args = (object)array(
			'cardNumber' 	=> trim($card),
			'pin'			=> trim($pin),		
			'proxy'			=> getProxy(),
		);;		 		
		$args = json_encode($args);		
		$cmd = "{$app} '{$args}'";
		//t($cmd);		
		exec($cmd,$res);
		$res = implode("\n",$res);
		//t($res,1);				 
			
		try{
			//$decoded = gzdecode($res);			
			//if($decoded)$res = $decoded;
		
			if($res){
				$res = json_decode($res);
				
				if(strlen($res->balance))
					$balance = (float)$res->balance;				
				else if($res->errors){
					$err = reset($res->errors);
					$balance = $err->message." (".$err->errorCode.")";				
				}
				else if($res->responseStatus && $res->responseStatus->errorMessge)
					$balance = $res->responseStatus->errorMessge;
			}
		} catch (Exception $e) {
			return false;
		}
		
		//t($res,1);
		//t($res->giftCardBalance->balance,1);
		//t($balance,1);
		return $balance;
	}
}
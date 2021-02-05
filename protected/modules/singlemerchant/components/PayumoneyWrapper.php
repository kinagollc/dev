<?php
class PayumoneyWrapper
{
	public static $error_code;
	
	public static function paymentCode()
	{
		return 'payu';
	}
	
	public static function paymentLink($mode='')
	{
		$url='';
		$mode = strtolower($mode);
		switch ($mode) {
			case "live":		
			    $url = 'https://secure.payu.in';
				break;
		
			default:
				$url = 'https://sandboxsecure.payu.in';
				break;
		}
		return $url;
	}
	
	public static function getAdminCredentials()
	{
		$enabled = false; $mode=''; 
		$key = '';  $salt='';
		
		$enabled = getOptionA('admin_payu_enabled');
		$mode = getOptionA('admin_payu_mode');		
		$card_fee = 0;
		$key = getOptionA('admin_payu_key');
		$salt = getOptionA('admin_payu_salt');					
										
		if($enabled=="yes" && !empty($key) && !empty($salt) ){
			return array(
			  'mode'=>$mode,
			  'card_fee'=>$card_fee,
			  'key'=>$key,
			  'salt'=>$salt,			
			  'payment_link'=>self::paymentLink($mode)  
			);
		}
		return false;
	}
	
	public static function getCredentials($merchant_id='')
	{							
		if($merchant_id<0 || empty($merchant_id)){
			return false;
		}
		
		$enabled = false; $mode='';  $card_fee = 0;
		$key = '';  $salt='';
		
		if (FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){			
			$enabled = getOptionA('admin_payu_enabled');
			$mode = getOptionA('admin_payu_mode');					
			$key = getOptionA('admin_payu_key');
			$salt = getOptionA('admin_payu_salt');						
		} else {						
			$enabled = getOption($merchant_id,'merchant_payu_enabled');
			$mode = getOption($merchant_id,'merchant_payu_mode');		
			$key = getOption($merchant_id,'merchant_payu_key');
			$salt = getOption($merchant_id,'merchant_payu_salt');
		}		
						
		if($enabled=="yes" && !empty($key) && !empty($salt) ){
			return array(
			  'mode'=>$mode,
			  'card_fee'=>$card_fee,
			  'key'=>$key,
			  'salt'=>$salt,	
			  'payment_link'=>self::paymentLink($mode)
			);
		}
		return false;
	}		 
	
	public static function generateHash($params=array(), $salt='')
	{
		if(!is_array($params)){
			throw new Exception( "invalid parameters" );
		}
		
		if(empty($salt)){
			throw new Exception( "invalid salt" );
		}
		
		$hash_string=''; $hash='';
		$hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
		$hashVarsSeq = explode('|', $hashSequence);			
		$hash_string = '';	
		foreach($hashVarsSeq as $hash_var) {
	      $hash_string .= isset($params[$hash_var]) ? $params[$hash_var] : '';
	      $hash_string .= '|';
	    }	    
	    $hash_string .= $salt;	  	    
	    $hash = strtolower(hash('sha512', $hash_string));	    
	    return $hash;
	}
	
}/* end class*/
<?php
class FcmWrapper
{
	private static $_file = '';
	private static $_option_token = '';
	private static $_single_device='';
	private static $_title='';
	private static $_body='';
	private static $_channel='';
	private static $_android_sounds='';
	private static $_ios_sounds='';
	private static $_badge='1';
	private static $_notification_foreground='true';
	private static $_payload = array();
		
	public static function ServiceAccount($file='',$option_token='')
	{
		 self::$_file = $file;
		 self::$_option_token = $option_token;
		 return new static;
	}
	
	public static function setTarget($device_id='')
	{
		self::$_single_device = trim($device_id);	
		return new static;	
	}
	
	public static function setTitle($title='')
	{
		self::$_title = trim($title);
		return new static;
	}
	
	public static function setBody($body='')
	{
		self::$_body = trim($body);
		return new static;
	}
	
	public static function setChannel($channel='')
	{
		self::$_channel = trim($channel);
		return new static;
	}
	
	public static function setSound($sound='')
	{
		self::$_android_sounds = trim($sound);
		return new static;
	}
	
	public static function setAppleSound($sound='')
	{
		self::$_ios_sounds = trim($sound);
		return new static;
	}
	
	public static function setBadge($badge='')
	{
		self::$_badge = (integer) $badge;
		return new static;
	}
	
	public static function setForeground($value='')
	{
		self::$_notification_foreground = $value;
		return new static;
	}
			
	public static function prepare()
	{
		if(empty(self::$_single_device)){
			throw new Exception( "Device token is empty" );
		}
		if(empty(self::$_title)){
			throw new Exception( "Title is empty" );
		}
		if(empty(self::$_body)){
			throw new Exception( "Body is empty" );
		}
				
		self::$_payload = array(
		  'message'=>array(
		    'token'=>self::$_single_device,
		    'notification' => array(
		        'title' => self::$_title,
		        'body' => self::$_body
		    ),
		    'android'=>array(
		      'notification'=>array(
		        'channel_id'=>self::$_channel,
		        'sound'=>!empty(self::$_android_sounds)?self::$_android_sounds:'default'
		      )
		    ),
		    'apns'=>array(
		      'payload'=>array(
		        'aps'=>array(
		          'apns-priority'=>10,
		          'content-available'=>1,
		          'apns-push-type'=>"background",
		          'badge'=>self::$_badge>0?self::$_badge:0,
		          'sound'=>!empty(self::$_ios_sounds)?self::$_ios_sounds:'default'
		        )
		      )
		    ),
		    'data'=>array(	        
		        'notification_foreground'=>!empty(self::$_notification_foreground)?self::$_notification_foreground:'',	 
		        'title' => self::$_title,
		        'body' => self::$_body,
		        'notification_title' => self::$_title,
		        'notification_body' => self::$_body,
		        'notification_ios_sound'=>self::$_ios_sounds
		      )	
		  )
		);				
		return new static;
	}	
	
	public static function getPayload()
	{
		return self::$_payload;
	}
	
	public static function generateToken($client)
	{
		$client->fetchAccessTokenWithAssertion();
	    $accessToken = $client->getAccessToken();	   
	    Yii::app()->functions->updateOptionAdmin(self::$_option_token,
	       json_encode($accessToken)
	    );
	    return $accessToken;
	}
	
	public static function send()
	{		
		$access_token = array();
		
		if(!file_exists(self::$_file)){
			throw new Exception( "Service account json file does not exist" );
		}
		
		require_once 'google-client/vendor/autoload.php';
		$client = new Google_Client();	
		$client->setAuthConfig(self::$_file);
		$client->addScope(Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);
		
		$save_access_token = getOptionA(self::$_option_token);
		if(!empty($save_access_token)){
			$access_token = json_decode($save_access_token,true);		
			$client->setAccessToken($access_token);
			if ($client->isAccessTokenExpired()) {				
				$access_token = self::generateToken($client);
			    $client->setAccessToken($access_token);
			}
		} else {
			$access_token = self::generateToken($client);
			$client->setAccessToken($access_token);
		}
						
		$oauth_token = isset($access_token["access_token"])?$access_token["access_token"]:'';						
		if(empty($oauth_token)){
			throw new Exception( "Authorization token is empty" );
		}
		
		$file = file_get_contents(self::$_file);
		if(empty($file)){
			throw new Exception( "Authorization file is empty" );
		}
		if (!$parse_file = json_decode($file,true)){						
			throw new Exception( "Account service json is not valid" );					
		}
						
		$project_id = $parse_file['project_id'];		
		
				
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/$project_id/messages:send");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);	   
	    //curl_setopt($ch, CURLOPT_SSLVERSION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(self::$_payload));
		
		$headers = array();
		$headers[] = 'Authorization: Bearer '.$oauth_token;
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {		    
		    throw new Exception( Yii::t("default",'Eror: [error]',array(
		      '[error]'=>curl_error($ch)
		    )) );	
		}
		curl_close($ch);
		
		$err = '';
		
		if($resp = json_decode($result,true)){			
			if(isset($resp['error'])){				
				if(isset($resp['error']['message'])){
					$err.=$resp['error']['message'];
				}
				
				if(isset($resp['error']['details'])){
					if(is_array($resp['error']['details']) && count($resp['error']['details'])>=1){
						foreach ($resp['error']['details'] as $error_val) {						
							if(isset($error_val['errorCode'])){
							   $err.= " errorCode: ";
							   $err.= isset($error_val['errorCode'])?$error_val['errorCode']:'';
							} 
						}
					}
				}
				
				if(empty($err)){
					$err.= json_encode($resp);
				}				
				throw new Exception( $err );
			} elseif ( isset($resp['name'])){
				$resp_name = explode("/",$resp['name']);
				if(isset($resp_name[3])){
					return "message id : ".$resp_name[3];
				}
				return $resp_name;
			}
		} else throw new Exception( Yii::t("default","Invalid fcm response [response]",array(
		 '[response]'=>json_encode($resp)
		)) );							
	}	
		
}
/*end class*/
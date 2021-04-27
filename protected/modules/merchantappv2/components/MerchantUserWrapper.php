<?php
class MerchantUserWrapper
{
	
	public static function login($username='', $password='')
	{
		$stmt="
		SELECT 
		a.id,a.merchant_id,a.user_type,a.email_address,a.username,a.password,a.session_token,a.status,
		a.user_access,a.contact_number, a.pin,
		b.restaurant_name, b.stic_dark_theme		
		FROM {{view_user_master}} a
		left join {{merchant}} b
		on
		a.merchant_id = b.merchant_id
	
		WHERE a.username=".q($username)."		
		AND user_type IN ('merchant_user','merchant')
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			$res = Yii::app()->request->stripSlashes($res);			
			if($res['status']!="active"){
				throw new Exception( "Your account is no longer active" );
			} elseif ( $res['password']!=md5($password)){
				throw new Exception( "Password is incorrect" );
			} else {				
				$token = $res['session_token'];				
				if(empty($token)){
					$token = self::generateTokens($res['id']);					
					self::updateUserToken($res['id'], $res['user_type'], $token );
				}				
								
				$logo = getOption($res['merchant_id'],'merchant_photo');
				$merchant_photo_bg = getOption($res['merchant_id'],'merchant_photo_bg');
				return array(				
				  'id'=>$res['id'],
				  'merchant_id'=>$res['merchant_id'],
				  'merchant_token'=>$token, 
				  'restaurant_name'=>$res['restaurant_name'],
				  'user_type'=>translate($res['user_type']),
				  'user_access'=>$res['user_access'],
				  'username'=>$res['username'],
				  'email_address'=>$res['email_address'],
				  'contact_number'=>$res['contact_number'],
				  'pin'=>$res['pin'],
				  'merchant_photo'=>FoodItemWrapper::getImage( $logo ,'chef.svg'),
				  'stic_dark_theme'=>$res['stic_dark_theme']
				);
			}
		}
		throw new Exception( "Either username or password is invalid" );
	}
	
	public static function generateTokens($id='')
	{
		$token = self::generateToken($id);
		$stmt="SELECT session_token FROM {{view_user_master}}
		WHERE session_token = ".q($token)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return self::generateTokens($id);
		}
		return $token;
	}
	
	public static function generateToken($id='')
	{		
		$agent = md5($id.$_SERVER['HTTP_USER_AGENT']);		
		return sha1(uniqid(mt_rand(), true)).$agent;
	}
	
	public static function updateUserToken($id='', $user_type='', $token='')
	{
		if($user_type=="merchant"){
			Yii::app()->db->createCommand()->update("{{merchant}}",array(
			  'mobile_session_token'=>$token,
			  'last_login'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			),
	  	    'merchant_id=:merchant_id',
		  	    array(
		  	      ':merchant_id'=>$id
		  	    )
	  	    );
		} elseif ( $user_type=="merchant_user"){
			Yii::app()->db->createCommand()->update("{{merchant_user}}",array(
			  'mobile_session_token'=>$token,
			  'last_login'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			),
	  	    'merchant_user_id=:merchant_user_id',
		  	    array(
		  	      ':merchant_user_id'=>$id
		  	    )
	  	    );
		}		
	}
	
	public static function validateToken($token='')
	{		
		$stmt="
		SELECT 
		a.id,a.merchant_id,a.user_type,a.email_address,a.username,a.password,a.session_token,a.status,
		a.user_access,a.contact_number, a.pin,
		b.restaurant_name, b.stic_dark_theme,
		( 
		 select option_value from {{option}}
		 where merchant_id=a.merchant_id
		 and option_name='merchant_timezone'
		 limit 0,1
		) as timezone ,
		
		( 
		 select option_value from {{option}}
		 where merchant_id=a.merchant_id
		 and option_name='merchant_photo'
		 limit 0,1
		) as merchant_photo ,

		( 
		 select option_value from {{option}}
		 where merchant_id=a.merchant_id
		 and option_name='merchant_photo_bg'
		 limit 0,1
		) as merchant_photo_bg 
		
		FROM {{view_user_master}} a
		left join {{merchant}} b
		on
		a.merchant_id = b.merchant_id
	
		WHERE a.session_token=".q($token)."		
		AND a.status ='active'
		AND user_type IN ('merchant_user','merchant')
		LIMIT 0,1
		";
		if(!empty($token)){
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				$res = Yii::app()->request->stripSlashes($res);			
				if(!empty($res['timezone'])){
				   Yii::app()->timeZone = $res['timezone'];
				}
				return array(			
				  'merchant_id'=>$res['merchant_id'],
				  'id'=>$res['id'],
				  'merchant_token'=>$token, 
				  'restaurant_name'=>$res['restaurant_name'],
				  'user_type'=>$res['user_type'],
				  'user_access'=>$res['user_access'],
				  'username'=>$res['username'],
				  'email_address'=>$res['email_address'],
				  'contact_number'=>$res['contact_number'],
				  'pin'=>$res['pin'],
				  'merchant_photo'=>FoodItemWrapper::getImage($res['merchant_photo'],'chef.svg'),
				  'stic_dark_theme'=>$res['stic_dark_theme']
				);
			}
		}
        throw new Exception( "Session token not valid" );
	}
	
	public static function validatePin($token='', $pin='')
	{
		$stmt ="
		SELECT merchant_id,pin 
		FROM {{view_user_master}}
		WHERE session_token=".q($token)."
		AND pin =".q($pin)."
		LIMIT 0,1
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
			return $res;
		}
		throw new Exception( "Invalid PIN" );
	}
	
	public static function getUserByEmail($email_address='')
	{
		if(!empty($email_address)){
			$stmt ="
			SELECT a.id, a.merchant_id, a.user_type, a.email_address, a. lost_password_code, a.pin,
			a.status, b.restaurant_name
			FROM {{view_user_master}} a
			LEFT JOIN {{merchant}} b
			ON
			a.merchant_id = b.merchant_id
			WHERE email_address=".q($email_address)."		
			LIMIT 0,1
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
				return $res;
			}
		}
		throw new Exception( "Email address not found" );
	}
	
	public static function getUserByEmailCode($email_address='',$code='')
	{
		if(!empty($email_address) && !empty($code)){
			$stmt ="
			SELECT id,merchant_id,user_type,email_address,lost_password_code,password
			FROM {{view_user_master}}
			WHERE email_address=".q($email_address)."		
			AND lost_password_code = ".q(trim($code))."
			LIMIT 0,1
			";					
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
				return $res;
			}
		}
		throw new Exception( "Email address not found" );
	}
	
	public static function udapteLostPasswordCode($user_type='', $merchant_id='', $id='', $code='')
	{
		$params = array(
		  'lost_password_code'=>$code,
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		switch ($user_type) {
			case "merchant":
				$up = Yii::app()->db->createCommand()->update("{{merchant}}",$params,
		  	    'merchant_id=:merchant_id',
			  	    array(
			  	      ':merchant_id'=>$merchant_id
			  	    )
		  	    );				
		  	    if($up){
		  	    	return true;
		  	    } else throw new Exception( "External server error cannot update code" );
				break;
				
			case "merchant_user":
				$up = Yii::app()->db->createCommand()->update("{{merchant_user}}",$params,
		  	    'id=:id',
			  	    array(
			  	      ':id'=>$id
			  	    )
		  	    );				
		  	    if($up){
		  	    	return true;
		  	    } else throw new Exception( "External server error cannot update code" );
				break;
		
			default:
				throw new Exception( "Invalid user type" );
				break;
		}
	}
	
	public static function updateProfile($id='', $user_type='',$params=array())
	{		
		
		switch ($user_type) {
			case "merchant":				
				 $stmt="
				SELECT count(*) as username,
				(
				 select count(*)
				  from {{merchant_user}}
				  WHERE email_address=".q($params['contact_email'])."
				  AND id !=".q($id)."
				) as email,
				
				(
				 select count(*)
				  from {{merchant_user}}
				  WHERE contact_number=".q($params['contact_phone'])."
				  AND id !=".q($id)."
				) as phone
				
				FROM {{view_user_master}}
				WHERE  username=".q($params['username'])."
				AND
				user_type IN ('merchant_user','merchant')		
				AND 
				merchant_id !=".q($id)."		
				";					
				 			
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
					if($res['username']>0){
						throw new Exception( "Username already exist" );
					}
					if($res['phone']>0){
						throw new Exception( "Mobile number already exist" );
					}
					if($res['email']>0){
						throw new Exception( "Email address already exist" );
					}
				}
								
				$up =Yii::app()->db->createCommand()->update("{{merchant}}",$params,
		  	    'merchant_id=:merchant_id',
			  	    array(
			  	      ':merchant_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
				break;
		
			case "merchant_user":				
			    $stmt="
				SELECT count(*) as username,
				(
				 select count(*)
				  from {{merchant_user}}
				  WHERE email_address=".q($params['contact_email'])."
				  AND id !=".q($id)."
				) as email,
				
				(
				 select count(*)
				  from {{merchant_user}}
				  WHERE contact_number=".q($params['contact_number'])."
				  AND id !=".q($id)."
				) as phone
				
				FROM {{view_user_master}}
				WHERE  username=".q($params['username'])."
				AND
				user_type IN ('merchant_user','merchant')		
				AND 
				id !=".q($id)."		
				";											    	   
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){						
					if($res['username']>0){
						throw new Exception( "Username already exist" );
					}
					if($res['phone']>0){
						throw new Exception( "Mobile number already exist" );
					}
					if($res['email']>0){
						throw new Exception( "Email address already exist" );
					}
				}												
				
				$up =Yii::app()->db->createCommand()->update("{{merchant_user}}",$params,
		  	    'merchant_user_id=:merchant_user_id',
			  	    array(
			  	      ':merchant_user_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
			break;
				
			default:
				throw new Exception( "Invalid user type" );
				break;
		}
	}
	
	public static function changePassword($id='', $user_type='',$params=array(), $old_password='')
	{				
		switch ($user_type) {
			case "merchant":				
				$stmt="
				SELECT password 
				FROM {{merchant}}
				WHERE merchant_id=".q($id)."
				LIMIT 0,1
				";											
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
									
					if($res['password'] <> $old_password ){
						throw new Exception( "Old password is invalid" );
					}
					
					if($res['password']==$params['password']){
						throw new Exception( "New password cannot be the same as old password" );
					}
				}							
				$up =Yii::app()->db->createCommand()->update("{{merchant}}",$params,
		  	    'merchant_id=:merchant_id',
			  	    array(
			  	      ':merchant_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
				break;
		
			case "merchant_user":
				$stmt="
				SELECT password 
				FROM {{view_user_master}}
				WHERE id=".q($id)."
				AND user_type='merchant_user'
				LIMIT 0,1
				";															
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){						
					if($res['password'] <> $old_password ){
						throw new Exception( "Old password is invalid" );
					}
					
					if($res['password']==$params['password']){
						throw new Exception( "New password cannot be the same as old password" );
					}
				}							
				$up =Yii::app()->db->createCommand()->update("{{merchant_user}}",$params,
		  	    'merchant_user_id=:merchant_user_id',
			  	    array(
			  	      ':merchant_user_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
				die();
				break;
				
			default:
				throw new Exception( "Invalid user type" );
				break;
		}
	}
	
	public static function changePin($id='', $user_type='',$params=array())
	{				
		switch ($user_type) {
			case "merchant":				
				$stmt="
				SELECT pin 
				FROM {{merchant}}
				WHERE merchant_id=".q($id)."
				LIMIT 0,1
				";											
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
					$res['pin'] = $res['pin']==0?'':$res['pin'];					
					if( (string)$res['pin'] == (string)$params['pin']){
						throw new Exception( "New pin cannot be the same as old pin" );
					}
				}							
				$up =Yii::app()->db->createCommand()->update("{{merchant}}",$params,
		  	    'merchant_id=:merchant_id',
			  	    array(
			  	      ':merchant_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
				break;
		
			case "merchant_user":
				$stmt="
				SELECT pin 
				FROM {{merchant_user}}
				WHERE merchant_user_id=".q($id)."
				LIMIT 0,1
				";											
				if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
					$res['pin'] = $res['pin']==0?'':$res['pin'];					
					if( (string)$res['pin'] == (string)$params['pin']){
						throw new Exception( "New pin cannot be the same as old pin" );
					}
				}							
				$up =Yii::app()->db->createCommand()->update("{{merchant_user}}",$params,
		  	    'merchant_user_id=:merchant_user_id',
			  	    array(
			  	      ':merchant_user_id'=>(integer)$id
			  	    )
		  	    );
		  	    return true;
				break;
			default:
				throw new Exception( "Invalid user type" );
				break;
		}
	}
	
	public static function getPin($token='')
	{		
		$resp = Yii::app()->db->createCommand()
          ->select('pin')
          ->from('{{view_user_master}}')   
          ->where("session_token=:session_token",array(
             ':session_token'=>$token
          ))           
          ->limit(1)
          ->queryRow();		        
        if($resp){
        	return $resp;
        }
        throw new Exception( "Pin not found" );   
	}
	
	public static function RegisteredDevice($device_uiid='',$params=array())
	{		
		if(empty($device_uiid)){
			return false;
		}
				
		$stmt="
		SELECT device_uiid 
		FROM {{merchantapp_device_reg}}
		WHERE
		device_uiid=".q($device_uiid)."
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			unset($params['date_created']);
			Yii::app()->db->createCommand()->update("{{merchantapp_device_reg}}",$params,
	  	    'device_uiid=:device_uiid',
		  	    array(
		  	      ':device_uiid'=>$device_uiid
		  	    )
	  	    );
	  	    $resp = self::GetDeviceInformation($device_uiid);	  	    
	  	    return $resp;
		} else {
			unset($params['date_modified']);
			Yii::app()->db->createCommand()->insert("{{merchantapp_device_reg}}",$params);
			return array(
			  'push_enabled'=>1,
			  'subscribe_topic'=>1
			);
		}				
	}
	
	public static function UpdateDeviceStatus($device_uiid='', $status='active')
	{
		if(empty($device_uiid)){
			return false;
		}
						
		Yii::app()->db->createCommand()->update("{{merchantapp_device_reg}}",array(
		 'status'=>$status,
		 'date_modified'=>FunctionsV3::dateNow(),
		 'ip_address'=>$_SERVER['REMOTE_ADDR']
		),
  	    'device_uiid=:device_uiid',
	  	    array(
	  	      ':device_uiid'=>$device_uiid
	  	    )
  	    );		
	}
	
	public static function UpdateDevice($device_uiid='', $params=array())
	{
		if(empty($device_uiid)){
			return false;
		}
						
		$up = Yii::app()->db->createCommand()->update("{{merchantapp_device_reg}}",$params,
  	    'device_uiid=:device_uiid',
	  	    array(
	  	      ':device_uiid'=>$device_uiid
	  	    )
  	    );		
  	    if($up){
  	    	return true;
  	    }
  	    throw new Exception( "failed cannot update." ); 
	}
	
	public static function GetDeviceInformation($device_uiid='')
	{
		if(!empty($device_uiid)){
			$resp = Yii::app()->db->createCommand()
	          ->select('registration_id,merchant_id,push_enabled,subscribe_topic')
	          ->from('{{merchantapp_device_reg}}')   
	          ->where("device_uiid=:device_uiid",array(
	             ':device_uiid'=>$device_uiid
	          ))           
	          ->limit(1)
	          ->queryRow();		        
	        if($resp){
	        	return $resp;
	        }
		}
        throw new Exception( "Device information not found" );   							
	}
	
	public static function generatePin($id='', $merchant_id='')
	{		
		$pin  = yii::app()->functions->generateRandomKey(3);
		$stmt="
		SELECT pin FROM
		{{view_user_master}}
		WHERE
		id=".q($id)."
		AND merchant_id = ".q($merchant_id)."
		AND pin = ".q($pin)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return self::generatePin($id,$merchant_id);
		}
		return $pin;
	}
	
	public static function getDeviceByID($id='')
	{
		$stmt="
		SELECT * 
		FROM {{view_merchantapp_device}}
		WHERE registration_id=".q($id)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
}
/*end class*/
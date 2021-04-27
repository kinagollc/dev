<?php
class CustomerNotification
{
	public static function getNotificationTemplate($key='',$lang='',$type='email',$return_all=true)
	{				
		$data = array();
		$resp = Yii::app()->db->createCommand()
	          ->select()
	          ->from('{{option}}')   
	          ->where(array('like', 'option_name', "$key%" ))	          	          
	          ->order('option_name ASC')
	          ->queryAll();	
	    if($resp){	   	    	
	    	foreach ($resp as $val) {	    	    		
	    		if($val['option_name']==$key."_email"){	    			
	    			$data['email_enabled']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_sms"){
	    			$data['sms_enabled']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_tpl_subject_$lang"){
	    			$data['email_subject']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_tpl_content_$lang"){
	    			$data['email_content']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_sms_content_$lang"){
	    			$data['sms_content']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_push"){
	    			$data['push_enabled']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_push_content_$lang"){
	    			$data['push_content']=$val['option_value'];
	    		} elseif ( $val['option_name']==$key."_push_title_$lang"){
	    			$data['push_title']=$val['option_value'];
	    		}
	    	}	    		    	
	    		    	
	    	if(is_array($data) && count($data)>=1){	    	    		
	    		switch ($type) {
	    			case "push":	    	
		    			if(isset($data['push_title'])){
		    			  	  return $data;
		    			  } else {
		    			  	  if(method_exists('CustomerNotification',$key)){
						    	 return self::$key();	    	
						      }
		    			  }				    				
	    				break;

	    			case "email":	    				
	    			  if(isset($data['email_subject'])){
	    			  	  return $data;
	    			  } else {
	    			  	  if(method_exists('CustomerNotification',$key)){
					    	 return self::$key();	    	
					      }
	    			  }
	    			break;
	    			
	    			case "sms":	    				
	    			  if(isset($data['sms_content'])){
	    			  	  return $data;
	    			  } else {
	    			  	  if(method_exists('CustomerNotification',$key)){
					    	 return self::$key();	    	
					      }
	    			  }
	    			break;
	    				
	    			default:
	    				break;
	    		}	    		
	    		return $data;
	    	}
	    } else {
	    	if(method_exists('CustomerNotification',$key)){
	    	   return self::$key();	    	
	    	}
	    }
	    throw new Exception( Yii::t("default","The template [tpl] does not exist",array(
	      '[tpl]'=>$key
	    )));
	}
	
	public static function merchant_change_pin()
	{
		return array(
		  'email_subject'=>"Merchant Forgot Pin",
		  'email_content'=>"Your new pin code is [code]",
		  'sms_content'=>"Your new pin code is [code]",
		  'push_title'=>"Merchant Forgot Pin",
		  'push_content'=>"Your new pin code is [code]"
		);
	}
	
	public static function merchant_forgot_password()
	{
		return array(
		  'email_subject'=>"Merchant Forgot Password",
		  'email_content'=>"Your verification code is [code]",
		  'sms_content'=>"Your verification code is  [code]",
		  'push_title'=>"Merchant Forgot Password",
		  'push_content'=>"Your verification code is  [code]"
		);
	}
	
	public static function receipt_send_to_merchant()
	{
		return array(
		  'email_subject'=>"New Order #[order_id] From [customer_name]",
		  'email_content'=>"New Order #[order_id] From [customer_name]",
		  'sms_content'=>"New Order #[order_id] From [customer_name]",
		  'push_title'=>"New Order #[order_id] From [customer_name]",
		  'push_content'=>"New Order #[order_id] From [customer_name]"
		);
	}
	
	public static function order_request_cancel_to_merchant()
	{
		return array(
		  'email_subject'=>"New cancel request from [customer_name]",
		  'email_content'=>"New cancel request from [customer_name]",
		  'sms_content'=>"New cancel request from [customer_name]",
		  'push_title'=>"New cancel request from [customer_name]",
		  'push_content'=>"New cancel request from [customer_name]"
		);
	}
	
	public static function booked_notify_merchant()
	{
		return array(
		  'email_subject'=>"New booking from [customer_name]",
		  'email_content'=>"New booking from [customer_name]",
		  'sms_content'=>"New booking from [customer_name]",
		  'push_title'=>"New booking from [customer_name]",
		  'push_content'=>"New booking from [customer_name]"
		);
	}
	
	public static function booking_request_cancel()
	{
		return array(
		  'email_subject'=>"New cancel booking from [customer_name]",
		  'email_content'=>"New cancel booking from [customer_name]",
		  'sms_content'=>"New cancel booking from [customer_name]",
		  'push_title'=>"New cancel booking from [customer_name]",
		  'push_content'=>"New cancel booking from [customer_name]"
		);
	}	
	
	public static function offline_new_bank_deposit()
	{
		return array(
		  'email_subject'=>"New bank deposit from [customer_name]",
		  'email_content'=>"New bank deposit from [customer_name]",
		  'sms_content'=>"New bank deposit from [customer_name]",
		  'push_title'=>"New bank deposit from [customer_name]",
		  'push_content'=>"New bank deposit from [customer_name]"
		);
	}	
	
	public static function merchant_near_expiration()
	{
$tpl = ' hi [restaurant_name]
Your membership is about to expire in [expiration_date]
Regards
- [sitename]
';
		return array(
		  'email_subject'=>"Your membership about to expired",
		  'email_content'=>$tpl,
		  'sms_content'=>$tpl,
		  'push_title'=>"Your membership about to expired",
		  'push_content'=>$tpl
		);
	}
	
	public static function food_is_done_to_driver()
	{
$tpl = 'hi [driver_name] the task id#[task_id] with order#[order_id] is now ready for pickup notes:[notes]';
		return array(		  
		  'push_title'=>"task id#[task_id] is now ready for pickup",
		  'push_content'=>$tpl
		);
	}	
	
	public static function auto_order_update()
	{
       $tpl = 'order#[order_id] is auto updated';
		return array(		  
		  'push_title'=>"order#[order_id] is auto updated",
		  'push_content'=>$tpl
		);
	}	
	
	public static function driver_update_to_merchant()
	{
       $tpl = 'order#[order_id] has been updated by delivery agent';
		return array(		  
		  'push_title'=>"order#[order_id] has been updated by delivery agent",
		  'push_content'=>$tpl
		);
	}	
	
	
}
/*end class*/
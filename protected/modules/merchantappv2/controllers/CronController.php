<?php
class CronController extends CController
{

	public function __construct()
	{				
		$website_timezone=getOptionA('website_timezone');
	    if (!empty($website_timezone)){
	 	   Yii::app()->timeZone=$website_timezone;
	    }		
	}		
	
	public function actionIndex()
	{
		echo 'cron is working';
	}
	
	public function actiontrigger_order()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_trigger_order');		
        if(($pid = cronHelper::lock()) !== FALSE):

		$sitename = getOptionA('website_title'); $siteurl=websiteUrl();
		$pattern = array('order_id','customer_name','restaurant_name','total_amount','order_details');
		
		$stmt="
		SELECT a.trigger_id, a.trigger_type, a.order_id, a.order_status as template_type,
		a.remarks as trigger_remarks, a.language, a.status as status_trigger,
		b.merchant_id,b.customer_name, b.profile_customer_name, b.restaurant_name,
		FORMAT(b.total_amount,2) as total_amount
		
		FROM {{merchantapp_order_trigger}} a	
		LEFT JOIN {{view_order}} b
		ON
		a.order_id = b.order_id
				
		WHERE a.status='pending'
		AND trigger_type IN ('order','auto_order_update','driver_update_to_merchant')
		LIMIT 0,10
		";		
		
		//AND trigger_type='order'
		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$resp = Yii::app()->request->stripSlashes($resp);
			foreach ($res as $val) {				
				$trigger_id = $val['trigger_id'];  $status_trigger='process';
				$template_type = trim($val['template_type']);				
				try {
					
					$tpl = CustomerNotification::getNotificationTemplate($template_type,$val['language'],'push',false);	
					$push_title = $tpl['push_title']; 
					$push_content = $tpl['push_content']; 
														
					foreach ($pattern as $key_pattern=>$pattern_val) {
						if($pattern_val=="total_amount"){							
							$data[$pattern_val]=isset($val[$pattern_val])? Yii::app()->functions->normalPrettyPrice($val[$pattern_val]) :'';
						} else $data[$pattern_val]=isset($val[$pattern_val])?$val[$pattern_val]:'';						
					}
					$data['sitename']=$sitename;					
					$data['siteurl']=$siteurl;
										
					$push_title = FunctionsV3::replaceTags($push_title,$data);
					$push_content = FunctionsV3::replaceTags($push_content,$data);	
					
					$params = array(
					 'merchant_id'=>(integer)$val['merchant_id'],
					 'merchant_name'=>$val['restaurant_name'],
					 'order_id'=>$val['order_id'],
					 'push_title'=>$push_title,
					 'push_message'=>$push_content,
					 'topics'=>CHANNEL_TOPIC.$val['merchant_id'],
					 'date_created'=>FunctionsV3::dateNow(),
					 'ip_address'=>$_SERVER['REMOTE_ADDR'],
					 'trigger_type'=>$val['trigger_type']
					);									
					Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params);					
				} catch (Exception $e) {
			        $status_trigger = $e->getMessage();
		        }		
		        	
		        $params_update = array(
		          'status'=>$status_trigger,
		          'date_process'=>FunctionsV3::dateNow(),
		          'ip_address'=>$_SERVER['REMOTE_ADDR']
		        );				        
		        Yii::app()->db->createCommand()->update("{{merchantapp_order_trigger}}",$params_update,
		  	    'trigger_id=:trigger_id',
			  	    array(
			  	      ':trigger_id'=>$trigger_id
			  	    )
		  	    );
			} /*end foreach */
			
			OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));
			
		}		
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actiontrigger_order_booking()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_trigger_order_booking');		
        if(($pid = cronHelper::lock()) !== FALSE):

		$sitename = getOptionA('website_title'); $siteurl=websiteUrl();
		
		$pattern = array('booking_id','restaurant_name','number_guest','date_booking',
		'time','customer_name','email','mobile','instruction','status');
		
		$data = array();
		
		$stmt="
		SELECT a.trigger_id, a.trigger_type, a.order_id, a.order_status as template_type,
		a.remarks as trigger_remarks, a.language, a.status as status_trigger,
		b.booking_id, b.merchant_id, b.number_guest, b.date_booking , b.booking_time as time,
		b.booking_name as customer_name, b.email , b.mobile, b.booking_notes as instruction,
		b.status,
		c.restaurant_name 
		
		FROM {{merchantapp_order_trigger}} a	
		LEFT JOIN {{bookingtable}} b
		ON
		a.order_id = b.booking_id
		
		LEFT JOIN {{merchant}} c
		ON
		b.merchant_id = c.merchant_id
				
		WHERE a.status='pending'
		AND trigger_type='booking'
		LIMIT 0,10
		";
				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {				
				$trigger_id = $val['trigger_id'];  $status_trigger='process';
				$template_type = trim($val['template_type']);				
				
				try {
					
					$tpl = CustomerNotification::getNotificationTemplate($template_type,$val['language'],'push',false);
					
					$push_title = $tpl['push_title']; 
					$push_content = $tpl['push_content']; 
					
					foreach ($pattern as $pattern_val) {
						$data[$pattern_val]=isset($val[$pattern_val])?$val[$pattern_val]:'';
					}
					$data['sitename']=$sitename;					
					$data['siteurl']=$siteurl;
										
					$push_title = FunctionsV3::replaceTags($push_title,$data);
					$push_content = FunctionsV3::replaceTags($push_content,$data);	
					
					$params = array(
					 'merchant_id'=>isset($val['merchant_id'])?(integer)$val['merchant_id']:0,
					 'merchant_name'=>isset($val['restaurant_name'])?$val['restaurant_name']:'',
					 'booking_id'=>isset($val['booking_id'])?(integer)$val['booking_id']:0,
					 'push_title'=>$push_title,
					 'push_message'=>$push_content,
					 'topics'=>CHANNEL_TOPIC.$val['merchant_id'],
					 'date_created'=>FunctionsV3::dateNow(),
					 'ip_address'=>$_SERVER['REMOTE_ADDR'],
					 'trigger_type'=>'booking'
					);			
					Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params);
					
				} catch (Exception $e) {
			        $status_trigger = translate($e->getMessage());
		        }		
		        
		        $params_update = array(
		          'status'=>$status_trigger,
		          'date_process'=>FunctionsV3::dateNow(),
		          'ip_address'=>$_SERVER['REMOTE_ADDR']
		        );
		        		        
		        Yii::app()->db->createCommand()->update("{{merchantapp_order_trigger}}",$params_update,
		  	    'trigger_id=:trigger_id',
			  	    array(
			  	      ':trigger_id'=>$trigger_id
			  	    )
		  	    );
		        
			} //end foreach
			
			OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));
		}
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionprocesspush()
	{
		dump("cron start..");
		define('LOCK_SUFFIX', APP_FOLDER.'_processpush');		
		if(($pid = cronHelper::lock()) !== FALSE):
		
		$stmt="
		SELECT a.*,
		(
		 select option_value
		 from {{option}}
		 where		 		 
		 option_name = 'merchantapp_services_account_json'
		 limit 0,1
		) as services_account_json

		FROM {{merchantapp_push_logs}} a
		WHERE a.status='pending'		
		ORDER BY id ASC		
		LIMIT 0,10		
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			$file = FunctionsV3::uploadPath()."/".$res[0]['services_account_json'];
						
			foreach ($res as $val) {			
				$process_status=''; $json_response='';
				$process_date = FunctionsV3::dateNow();				
				$device_id = trim($val['device_id']);																			
				
				 try {		    		
	    			$json_response = FcmWrapper::ServiceAccount($file,APP_FOLDER.'_fcm')
					->setTarget($val['device_id'])
					->setTitle($val['push_title'])
					->setBody($val['push_message'])
					->setChannel(CHANNEL_ID)
					->setSound(CHANNEL_SOUNDNAME)
					->setAppleSound(CHANNEL_SOUNDFILE)
					->setBadge(1)
					->setForeground("true")
					->prepare()
					->send();						
					$process_status = 'process';
	    		} catch (Exception $e) {
	    			$process_status = 'failed';
					$json_response = $e->getMessage();						
				}			
								
				if(!empty($process_status)){
		   	  	   $process_status=substr( strip_tags($process_status) ,0,255);
		   	    } else $process_status='failed';	
		   	    
		   	    if(is_array($json_response) && count($json_response)>=1){
		   	    	$json_response = json_encode($json_response);
		   	    } 
		   	    
		   	    $params = array(
				  'status'=>$process_status,
				  'date_process'=>$process_date,
				  'json_response'=>$json_response
				);		
				
				Yii::app()->db->createCommand()->update("{{merchantapp_push_logs}}",$params,
		  	    'id=:id',
			  	    array(
			  	      ':id'=>$val['id']
			  	    )
		  	    );
				  
			} //end foreach
		}
		
		cronHelper::unlock();
		endif;	
		dump("cron end..");
	}
	
	public function actionprocessbroadcast()
	{
		dump("cron start..");
		define('LOCK_SUFFIX', APP_FOLDER.'_broadcast');		
		if(($pid = cronHelper::lock()) !== FALSE):
		
		$stmt="
		SELECT a.*,
		(
		 select option_value
		 from {{option}}
		 where		 		 
		 option_name = 'merchantapp_services_account_json'
		 limit 0,1
		) as services_account_json

		FROM {{merchantapp_broadcast}} a
		WHERE a.status='pending'		
		ORDER BY broadcast_id ASC		
		LIMIT 0,10
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){		
			
			$file = FunctionsV3::uploadPath()."/".$res[0]['services_account_json'];
						
			foreach ($res as $val) {				
				$process_status=''; $json_response='';
				$process_date = FunctionsV3::dateNow();
				$trigger_type = isset($val['trigger_type'])?$val['trigger_type']:'';
				$order_id = isset($val['order_id'])?(integer)$val['order_id']:'';
				
				 try {		    		
	    			$json_response = FcmWrapper::ServiceAccount($file,APP_FOLDER.'_fcm')
					->setTarget($val['topics'])
					->setTitle($val['push_title'])
					->setBody($val['push_message'])
					->setChannel(CHANNEL_ID)
					->setSound(CHANNEL_SOUNDNAME)
					->setAppleSound(CHANNEL_SOUNDFILE)
					->setBadge(1)
					->setForeground("true")
					->setKey1($trigger_type)
					->setKey2($order_id)
					->prepare()
					->send();						
					$process_status = 'process';
	    		} catch (Exception $e) {
	    			$process_status = 'failed';
					$json_response = $e->getMessage();						
				}			
								
				if(!empty($process_status)){
		   	  	   $process_status=substr( strip_tags($process_status) ,0,255);
		   	    } else $process_status='failed';	
		   	    
		   	    if(is_array($json_response) && count($json_response)>=1){
		   	    	$json_response = json_encode($json_response);
		   	    } 
		   	    
		   	    $params = array(
				  'status'=>$process_status,
				  'date_modified'=>$process_date,
				  'fcm_response'=>$json_response
				);		
				
				Yii::app()->db->createCommand()->update("{{merchantapp_broadcast}}",$params,
		  	    'broadcast_id=:broadcast_id',
			  	    array(
			  	      ':broadcast_id'=>$val['broadcast_id']
			  	    )
		  	    );
				  
			} //end foreach
		}
		
		cronHelper::unlock();
		endif;	
		dump("cron end..");
	}
	
	public function actionunattented_order()
	{		
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_unattented_order');		
        if(($pid = cronHelper::lock()) !== FALSE):


		$and=''; $lang=Yii::app()->language;
		$pattern = array('order_id','customer_name','restaurant_name','total_amount');
								
		$order_unattended_minutes = (integer)getOptionA('order_unattended_minutes');
		if($order_unattended_minutes<=0){
			$order_unattended_minutes = 5;
		}		
					
				
		$interval_date = date("Y-m-d H:i:s", strtotime("+$order_unattended_minutes minutes"));
		$todays_date = date("Y-m-d");
		
		
		$end = date("Y-m-d H:i:s");
		$start = date("Y-m-d H:i:s", strtotime("-$order_unattended_minutes minutes"));
						
		$stats = OrderWrapper::getStatusFromSettings('order_incoming_status',array('pending','paid'));
				
		$and.=" AND a.status IN ($stats)
		AND a.request_cancel='2'
		";		
		
		$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
				
		$and.=" AND ".q($interval_date)." > a.date_created  ";
				
		$and.=" AND a.order_id NOT IN (
		  select order_id from
		  {{merchantapp_broadcast}}
		  where order_id=a.order_id
		  and 
		  date_created BETWEEN ".q($start)." AND ".q($end)."
		)";
				
		$tpl = CustomerNotification::getNotificationTemplate('receipt_send_to_merchant',$lang,'push',false);
		$push_title = isset($tpl['push_title'])?$tpl['push_title']:''; 
		$push_content = isset($tpl['push_content'])?$tpl['push_content']:'';
		
		$stmt="
		SELECT a.*
		FROM {{view_order}} a
		WHERE 1
		$and
		LIMIT 0,50
		";					
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){						
			foreach ($res as $val) {				
				$data=array();
				foreach ($pattern as $pattern_val) {
					$data[$pattern_val]=isset($val[$pattern_val])?$val[$pattern_val]:'';
				}
				$push_title = FunctionsV3::replaceTags($push_title,$data);
				$push_content = FunctionsV3::replaceTags($push_content,$data);	
				
				$params = array(
				 'merchant_id'=>(integer)$val['merchant_id'],
				 'merchant_name'=>$val['restaurant_name'],
				 'order_id'=>$val['order_id'],
				 'push_title'=>$push_title,
				 'push_message'=>$push_content,
				 'topics'=>CHANNEL_TOPIC.$val['merchant_id'],
				 'date_created'=>FunctionsV3::dateNow(),
				 'ip_address'=>$_SERVER['REMOTE_ADDR'],
				);						
				Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params);					
			} //end foreach
			OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));
		} 
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionunattented_booking()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_unattented_booking');		
        if(($pid = cronHelper::lock()) !== FALSE):


		$and=''; $lang=Yii::app()->language;
		$pattern = array('booking_id','restaurant_name','number_guest','date_booking',
		'time','customer_name','email','mobile','instruction','status');
								
		$unattended_minutes = (integer)getOptionA('booking_incoming_unattended_minutes');
		if($unattended_minutes<=0){
			$unattended_minutes = 5;
		}		
											
		$interval_date = date("Y-m-d H:i:s", strtotime("+$unattended_minutes minutes"));
		$todays_date = date("Y-m-d");
								
		$end = date("Y-m-d H:i:s");
        $start = date("Y-m-d H:i:s", strtotime("-$unattended_minutes minutes"));

				
		$and.=" AND a.status IN ('pending')
		AND a.request_cancel='0'
		";		
		
		$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
				
		$and.=" AND ".q($interval_date)." > a.date_created  ";
				
		$and.=" AND a.booking_id NOT IN (
		  select booking_id from
		  {{merchantapp_broadcast}}
		  where booking_id=a.booking_id
		  and 		  
		  date_created BETWEEN ".q($start)." AND ".q($end)."
		)";
		
		$tpl = CustomerNotification::getNotificationTemplate('booked_notify_merchant',$lang,'push',false);
		$push_title = isset($tpl['push_title'])?$tpl['push_title']:''; 
		$push_content = isset($tpl['push_content'])?$tpl['push_content']:'';
		
		$stmt="
		SELECT 
		a.booking_id, a.merchant_id, a.client_id, a.number_guest, a.date_booking,
		a.date_booking as date_booking_raw, a.booking_time as booking_time_raw,
		a.booking_time, a.booking_name, a.email, a.mobile, a.booking_notes,
		a.date_created,a.date_created as date_created_raw, a.status, a.status as status_raw,
		c.restaurant_name 
		
		FROM {{bookingtable}} a
		LEFT JOIN {{merchant}} c
		ON
		a.merchant_id = c.merchant_id
		
		WHERE 1
		$and
		LIMIT 0,50
		";						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){						
			foreach ($res as $val) {				
				$data=array();
				foreach ($pattern as $pattern_val) {
					$data[$pattern_val]=isset($val[$pattern_val])?$val[$pattern_val]:'';
				}
				$push_title = FunctionsV3::replaceTags($push_title,$data);
				$push_content = FunctionsV3::replaceTags($push_content,$data);	
				
				$params = array(
				 'merchant_id'=>isset($val['merchant_id'])?(integer)$val['merchant_id']:0,
				 'merchant_name'=>isset($val['restaurant_name'])?$val['restaurant_name']:'',
				 'booking_id'=>isset($val['booking_id'])?(integer)$val['booking_id']:0,
				 'push_title'=>$push_title,
				 'push_message'=>$push_content,
				 'topics'=>CHANNEL_TOPIC.$val['merchant_id'],
				 'date_created'=>FunctionsV3::dateNow(),
				 'ip_address'=>$_SERVER['REMOTE_ADDR'],
				);								
				Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params);					
			} //end foreach
			OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));
		} 
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public static function actionclear_logs()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_clear_logs');		
        if(($pid = cronHelper::lock()) !== FALSE):
        
        $unattended_minutes=1;
        $interval_date = date("Y-m-d H:i:s", strtotime("+$unattended_minutes minutes"));
        
        $stmt="
        DELETE FROM {{merchantapp_broadcast}}
        WHERE date_created <= CURRENT_DATE() - INTERVAL 2 MONTH
        ";                
        Yii::app()->db->createCommand($stmt)->query();
        
        $stmt="
        DELETE FROM {{merchantapp_push_logs}}
        WHERE date_created <= CURRENT_DATE() - INTERVAL 2 MONTH
        ";                
        Yii::app()->db->createCommand($stmt)->query();
        
        $stmt="
        DELETE FROM {{merchantapp_device_reg}}
        WHERE last_login <= CURRENT_DATE() - INTERVAL 2 MONTH
        ";                
        Yii::app()->db->createCommand($stmt)->query();
        
        cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionNearexpiration()
	{
		
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_near_expiration');		
        if(($pid = cronHelper::lock()) !== FALSE):
        
		$lang=Yii::app()->language;
		$email_enabled=getOptionA("merchant_near_expiration_email");
		$sms_enabled=getOptionA("merchant_near_expiration_sms");
		$sender=getOptionA("global_admin_sender_email");
		
		if($email_enabled!=1 && $sms_enabled!=1){
			if(isset($_GET['debug'])){ echo "disabled"; }
			return ;
		}
		
		$days=getOptionA('merchant_near_expiration_day');
		if(empty($days)){
			$days=5;
		}
		$date=date("Y-m-d", strtotime("+$days day"));		
		$stmt="
		SELECT 
		a.merchant_id,a.restaurant_name,a.membership_expired		
		FROM
		{{merchant}} a
		WHERE
		membership_expired<".FunctionsV3::q($date)."
		AND status in ('active')
		AND is_commission ='1'
		LIMIT 0,1000
		";		
		
		$tpl  = CustomerNotification::getNotificationTemplate('merchant_near_expiration',$lang,'push',false);		
		
		$push_title = isset($tpl['push_title'])?$tpl['push_title']:'';
		$push_content = isset($tpl['push_content'])?$tpl['push_content']:'';	
		$site_title = getOptionA('website_title'); $siteurl = websiteUrl();
		
		if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){			
			foreach ($resp as $val) {				
				$data = array(			  
				  'restaurant_name'=>isset($val['restaurant_name'])?$val['restaurant_name']:'',
				  'expiration_date'=>isset($val['expiration_date'])?$val['expiration_date']:'',
				  'sitename'=>$site_title,
			      'siteurl'=>$siteurl
				);
				
				$push_title = FunctionsV3::replaceTags($push_title,$data);			
				$push_content = FunctionsV3::replaceTags($push_content,$data);

				$params = array(
				  'merchant_id'=>$val['merchant_id'],
				  'merchant_name'=>isset($val['restaurant_name'])?$val['restaurant_name']:'',
				  'push_title'=>$push_title,
				  'push_message'=>$push_content,
				  'topics'=>CHANNEL_TOPIC_ALERT.$val['merchant_id'],
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);
				if(Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params)){
					
				}
			}						
			
			OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));
		}
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionAutoUpdateStatus()
	{
		dump("cron start..");
   	    define('LOCK_SUFFIX', APP_FOLDER.'_unattented_order');		
	    if(($pid = cronHelper::lock()) !== FALSE):

		$enabled = getOptionA('merchantapp_enabled_auto_status_enabled');
		$time_interval = (integer)getOptionA('merchantapp_enabled_auto_status_time');
		$order_status = getOptionA('merchantapp_enabled_auto_status');		
		
		if($enabled!=1 || $time_interval<=0 || empty($order_status) ){
			die();
		}
		
		$order_action_accepted_status = getOptionA('order_action_accepted_status');
		$accepted_based_time = (integer)getOptionA('accepted_based_time');
        $accepted_based_time = $accepted_based_time>0?$accepted_based_time:1;
        $time = (integer)getOptionA('merchantapp_enabled_auto_status_readyin');
        if($time<=0){
        	$time=20;
        }
		
		$order_status = trim($order_status);
				
		$and='';
		
		$interval_date = date("Y-m-d H:i:s", strtotime("-$time_interval minutes"));
	    $todays_date = date("Y-m-d");
	    
	    $end = date("Y-m-d H:i:s");
	    $start = date("Y-m-d H:i:s", strtotime("-$time_interval minutes"));
		
	    $and.=" AND a.status IN ('pending','paid')
		AND a.request_cancel='2'
		";		
	    $and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
	    $and.="\n";
	    $and.=" AND ".q($interval_date)." > a.date_created  ";
	    
		$stmt="
		SELECT a.*
		FROM {{view_order}} a
		WHERE 1
		$and
		LIMIT 0,10
		";				
		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
												
				$order_id  = (integer)$val['order_id'];
				$merchant_id = (integer)$val['merchant_id'];
				$status = $order_status;
				$remarks='Auto update order status'; $estimated_words='';
				
				
				try {
					
					if($order_action_accepted_status==$order_status){
						
						$date_now = date('Y-m-d'); $datetime_now = date("Y-m-d g:i:s a");
						$delivery_date=date("Y-m-d",strtotime($val['delivery_date']));
						
						if(!empty($val['delivery_time'])){					
						   $delivery_time=date("H:i:s",strtotime($val['delivery_time']));				   
						} else {
							$delivery_time=date("H:i:s");
						}			
						
						$is_late = false;
						$date_created = "$delivery_date $delivery_time";
						$time_diff=Yii::app()->functions->dateDifference($date_created,$datetime_now);			
						if(is_array($time_diff) && count($time_diff)>=1){
							if($time_diff['days']>0){
								$is_late = true;
							}				
							if($time_diff['hours']>0){
								$is_late = true;
							}				
							if($time_diff['minutes']>0){
								$is_late = true;
							}				
						}			
						
						if($is_late){
							$delivery_date = date('Y-m-d'); $delivery_time=date("H:i:s");
						}			
										
						if($accepted_based_time==2){					
							$date_now = date('Ymd');					
							$delivery_date2=date("Ymd",strtotime($order['delivery_date']));				    
							if($delivery_date2>$date_now){
								//
							} else {
								$delivery_date = date('Y-m-d'); $delivery_time=date("H:i:s");
							}
						}										
												
						$estimated_date_time = date("Y-m-d H:i:s",strtotime($delivery_date." ".$delivery_time));
							
						$params = array(
						  'estimated_time'=>$time,
						  'estimated_date_time'=>date('Y-m-d H:i:s', strtotime("+$time minutes", strtotime($estimated_date_time))),
						);
						
						
						$estimated_words = "estimated food ready in [minute] mins";		  	   				  	    
				  	    
				  	    /*CHECK IF DATE IS FUTURE ORDER*/
				  	    $chk_delivery_date = new DateTime($delivery_date);
				  	    $current_date = new DateTime();
				  	   
				  	    if ($chk_delivery_date > $current_date) {
				  	    	$estimated_words = "Order accepted and will be ready on time.";
				  	    }
						
				  	    $remarks = translate($estimated_words,array(
				  	     '[minute]'=>$time
				  	    ));
											
						$up =Yii::app()->db->createCommand()->update("{{order_delivery_address}}",$params,
						'order_id=:order_id',
							array(
							  ':order_id'=>$order_id
							)
						);
						
					}/* end if*/
					
					$params = array(
					  'order_id'=>$order_id,
					  'status'=>$status,
					  'remarks'=>$remarks,
					  'date_created'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR'],
					  'remarks2'=>$estimated_words,
				      'remarks_args'=>json_encode(array('[minute]'=>$time))
					);		
					
					$params2 = array(
					  'status'=>$status,
					  'admin_viewed'=>1,
					  'viewed'=>2,
					  'date_modified'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR']
					);
				
					OrderWrapper::updateOrderHistory($order_id,$merchant_id,$params,$params2);
					
					/*SEND NOTIFICATION*/
					if(method_exists("FunctionsV3","notifyCustomerOrderStatusChange")){		  	   	   
					   FunctionsV3::notifyCustomerOrderStatusChange(
						  $order_id,
						  $status,
						  $remarks
					   );
					}		
					
					OrderWrapper::InsertOrderTrigger($order_id,'auto_order_update','','auto_order_update');
					
					if (FunctionsV3::hasModuleAddon("driver")){
						/*Driver app*/
						Yii::app()->setImport(array(			
						  'application.modules.driver.components.*',
						));
						Driver::addToTask($order_id);
					}		   
					
					/*UPDATE POINTS BASED ON ORDER STATUS*/
					if (FunctionsV3::hasModuleAddon("pointsprogram")){	    						    					
					if (method_exists('PointsProgram','updateOrderBasedOnStatus')){
					   PointsProgram::updateOrderBasedOnStatus($status,$order_id);
					}
					if (method_exists('PointsProgram','udapteReviews')){
					   PointsProgram::udapteReviews($order_id,$status);
					}							
					}
					
					/*INVENTORY ADDON*/				
					if (method_exists('FunctionsV3','inventoryEnabled')){
					if (FunctionsV3::inventoryEnabled($merchant_id)){
					try {							  
					   InventoryWrapper::insertInventorySale($order_id,$status);	
					} catch (Exception $e) {										    
					  // echo $e->getMessage();		    					    	  
					}		    					    	
					}
					}  
					
					
				} catch (Exception $e) {
				    // $e->getMessage()
			     }	  	    	  	    	
				
			}
		}

		cronHelper::unlock();
	    endif;	
	    dump("cron end..");		
	}
	
	
	public function actionRunAll()
	{
	   dump("cron start..");
       define('LOCK_SUFFIX', APP_FOLDER.'_runall');		
       if(($pid = cronHelper::lock()) !== FALSE):
       
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/processpush");
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/processbroadcast");
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/unattented_order");
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/unattented_booking");	   
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/autoupdatestatus");
	   
	   cronHelper::unlock();
       endif;	
       dump("cron end..");		
	}
	
}
/*end class*/
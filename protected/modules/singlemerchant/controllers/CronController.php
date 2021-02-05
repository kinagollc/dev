<?php
class CronController extends CController
{
	
	public function __construct()
	{
		Yii::app()->setImport(array(			
		  'application.components.*',
		));		
		require_once 'Functions.php';
	}
	
	public function actionIndex()
	{
		echo 'cron is working';
	}
	
	public function actionprocesspush()
	{
		define('LOCK_SUFFIX', '.singleapp_processpush');
		if(($pid = cronHelper::lock()) !== FALSE):
				
		$stmt="
		SELECT a.*,
		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_id
		 and
		 option_name = 'singleapp_fcm_provider'
		 limit 0,1
		) as fcm_provider,
		
		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_id
		 and
		 option_name = 'singleapp_services_account_json'
		 limit 0,1
		) as services_account,

		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_id
		 and
		 option_name = 'singleapp_android_push_key'
		 limit 0,1
		) as server_key			
			
		FROM {{singleapp_mobile_push_logs}} a		
		
		WHERE a.status='pending'		
		ORDER BY id ASC		
		LIMIT 0,10	
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
				
				$process_status=''; $json_response='';
				$process_date = FunctionsV3::dateNow();
				
				$device_id = trim($val['device_id']);
				$merchant_id = (integer)$val['merchant_id'];
				$fcm_provider = $val['fcm_provider']>0?$val['fcm_provider']:1;				
				$file = FunctionsV3::uploadPath()."/".$val['services_account'];
				$server_key = trim($val['server_key']);
								
				if($fcm_provider==2):
				
				   try {		    		
		    			$json_response = FcmWrapper::ServiceAccount($file,'singleapp_fcm_v1',$merchant_id)
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
				
				else :
				
				  $singleapp_push_icon = getOption($merchant_id,'singleapp_push_icon');
			      $singleapp_enabled_pushpic = getOption($merchant_id,'singleapp_enabled_pushpic');
			      $singleapp_push_picture = getOption($merchant_id,'singleapp_push_picture');
				
			      switch (strtolower($val['device_platform'])) {
			      	  case "android":
						$data = array(
						  'title'=>$val['push_title'],
						  'body'=>$val['push_message'],
						  'vibrate'	=> 1,			
			              'soundname'=> CHANNEL_SOUNDNAME,
			              'android_channel_id'=>CHANNEL_ID,
			              'content-available'=>1,
			              'count'=>1,			              
			              'badge'=>1,
			              'push_type'=>$val['push_type']
						 );
						 
						 if(!empty($singleapp_push_icon)){
						 	$data['image'] = SingleAppClass::getImage($singleapp_push_icon);
						 }
						 if($singleapp_enabled_pushpic==1){
						 	$data['style'] ="picture";
						 	$data['picture'] = SingleAppClass::getImage($singleapp_push_picture);
						 }
						 						 						 												
						 if(!empty($server_key)){
							 try {
							 	$json_response = fcmPush::pushAndroid($data,$device_id,$server_key);						 	
							 	$process_status='process';
							 } catch (Exception $e) {
							 	$process_status = 'failed';
				                $json_response = 'Caught exception:'. $e->getMessage();
			                 }
						 } else {
						 	$process_status = 'failed';
						 	$json_response = 'server key is empty';
						 }
		                 						 
						break;						
										
					 case "ios":
						
						try {
							 $data = array( 
						      'title' =>$val['push_title'],
						      'body' => $val['push_message'],
						      'sound'=>CHANNEL_SOUNDFILE,
						      'android_channel_id'=>CHANNEL_ID,
						      'badge'=>1,
						      'content-available'=>1,
						      'push_type'=>$val['push_type']
						    );						   
							$json_response = fcmPush::pushIOS($data,$device_id,$server_key);
							$process_status='process';							
						} catch (Exception $e) {
							$process_status = 'failed';
							$json_response =  $e->getMessage();
						}		
										
					    break;
					    
					default:
						$process_status = 'failed';
						$json_response='undefined device platform'; 
						break;
			      }
			      
				endif;
				
				
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
				
				Yii::app()->db->createCommand()->update("{{singleapp_mobile_push_logs}}",$params,
		  	    'id=:id',
			  	    array(
			  	      ':id'=>$val['id']
			  	    )
		  	    );
			    
				
			} /*end foreach*/
		}
		
		cronHelper::unlock();
		endif;	
	}
	
	public function actionprocessbroadcast()
	{
		define('LOCK_SUFFIX', '.singleapp_processbroadcast');
		if(($pid = cronHelper::lock()) !== FALSE):
		
		$stmt="
		SELECT a.*,
		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_list
		 and
		 option_name = 'singleapp_fcm_provider'
		 limit 0,1
		) as fcm_provider,
		
		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_list
		 and
		 option_name = 'singleapp_services_account_json'
		 limit 0,1
		) as services_account,

		(
		 select option_value
		 from {{option}}
		 where
		 merchant_id = a.merchant_list
		 and
		 option_name = 'singleapp_android_push_key'
		 limit 0,1
		) as server_key			
			
		FROM {{singleapp_broadcast}} a		
		
		WHERE a.status='pending'		
		AND 
		fcm_version='1'
		ORDER BY broadcast_id ASC		
		LIMIT 0,5	
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {				
				$process_status=''; $json_response='';
				$process_date = FunctionsV3::dateNow();
				
				$device_id = trim($val['device_platform']);
				$merchant_id = (integer)$val['merchant_list'];						
				$file = FunctionsV3::uploadPath()."/".$val['services_account'];
				$server_key = trim($val['server_key']);
				
				 try {		    		
	    			$json_response = FcmWrapper::ServiceAccount($file,'singleapp_fcm_v1',$merchant_id)
					->setTarget($device_id)
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
				  'date_modified'=>$process_date,
				  'fcm_response'=>$json_response
				);		
				
				Yii::app()->db->createCommand()->update("{{singleapp_broadcast}}",$params,
		  	    'broadcast_id=:broadcast_id',
			  	    array(
			  	      ':broadcast_id'=>$val['broadcast_id']
			  	    )
		  	    );				
			} //end foreach
		}
		
        cronHelper::unlock();
		endif;	

	}
		
	public function actionProcessBroadcastOld()
	{		
		
		define('LOCK_SUFFIX', '.singleapp_processpushold');
		if(($pid = cronHelper::lock()) !== FALSE):
		
	    $stmt="
	    SELECT * FROM
	    {{singleapp_broadcast}}
	    WHERE
	    status='pending'
	    AND
	    fcm_version=0
	    ORDER BY broadcast_id ASC
	    LIMIT 0,1	    
	    ";	   
	    if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
	    	$res = Yii::app()->request->stripSlashes($res);
	    		    	
	    	$broadcast_id=$res['broadcast_id'];	    		    
	    	
	    	$and='';
	    	switch ($res['device_platform']) {
	    		case "android":	    				    		    
	    		    $and=" AND device_platform IN ('Android','android') ";
	    			break;
	    	
	    		case "ios":		    		   
	    		   $and=" AND device_platform IN ('ios','iOS') ";
	    		   break;  
	    		   
	    		default:
	    			break;
	    	}
	    	
	    	$merchant_list = !empty($res['merchant_list'])?json_decode($res['merchant_list'],true):false;
	    	if(is_array($merchant_list) && count($merchant_list)>=1){
	    		$in_merchant ='';
	    		foreach ($merchant_list as $mtid) {
	    			$in_merchant.=FunctionsV3::q($mtid).",";
	    		}
	    		$in_merchant = substr($in_merchant,0,-1);
	    		$and.="
	    		AND single_app_merchant_id IN ($in_merchant)
	    		";
	    	}
	    	
	    	$and.=" 
	    	  AND client_id NOT IN (
	    	  select client_id from {{singleapp_mobile_push_logs}}
	    	  where client_id=a.client_id
	    	  and broadcast_id=".FunctionsV3::q($broadcast_id)."
	    	)
	    	";
	    	
	    	$stmt2="
	    	SELECT a.* FROM
	    	{{client}} a
	    	WHERE
	    	enabled_push='1'
	    	AND status in ('active')
	    	AND device_id !='' 
	    	$and   	
	    	LIMIT 0,50
	    	";	
	    	if($res2 = Yii::app()->db->createCommand($stmt2)->queryAll()){		
	    		foreach ($res2 as $val) {	    			
	    			$params=array(
	    			  'client_id'=>$val['client_id'],
	    			  'client_name'=>!empty($val['first_name'])?$val['first_name']." ".$val['last_name']:'no name',
	    			  'device_platform'=>$val['device_platform'],
	    			  'device_id'=>$val['device_id'],
	    			  'push_title'=>$res['push_title'],
	    			  'push_message'=>$res['push_message'],
	    			  'push_type'=>'campaign',
	    			  'date_created'=>FunctionsV3::dateNow(),
	    			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
	    			  'broadcast_id'=>$res['broadcast_id'],
	    			  'merchant_id'=>$val['single_app_merchant_id']
	    			);	    				    			
	    			Yii::app()->db->createCommand()->insert("{{singleapp_mobile_push_logs}}",$params);
	    		}
	    		
	    	} else {	    		
	    		$params_update=array('status'=>"process");
	    	    Yii::app()->db->createCommand()->update("{{singleapp_broadcast}}",$params,
		  	    'broadcast_id=:broadcast_id',
			  	    array(
			  	      ':broadcast_id'=>$broadcast_id
			  	    )
		  	    );

	    	}	    		   
	    	
	    } 	    	    
	    
	    $cron = websiteUrl()."/singlemerchant/cron/processpush";
	    FunctionsV3::fastRequest( $cron );
	    
	    cronHelper::unlock();
		endif;	
	}	
	
	public function actiongetfbavatar()
	{
		define('LOCK_SUFFIX', '.singleapp_getfbavatar');
		if(($pid = cronHelper::lock()) !== FALSE):
						
		$stmt="
		SELECT client_id,avatar,social_id
		FROM {{client}}
		WHERE avatar =''
		AND social_id !=''
		AND social_strategy ='fb_mobile'
		LIMIT 0,2
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {				
				$params = array();
				$client_id = $val['client_id'];
				if($avatar = FunctionsV3::saveFbAvatarPicture($val['social_id'])){
				   $params['avatar'] = $avatar;
				} else $params['avatar'] = "avatar.jpg";
				$params['date_modified']=FunctionsV3::dateNow();
				$params['ip_address']=$_SERVER['REMOTE_ADDR'];				
				
				Yii::app()->db->createCommand()->update("{{client}}",$params,
		  	    'client_id=:client_id',
			  	    array(
			  	      ':client_id'=>$client_id
			  	    )
		  	    );
			}
		} 		
		cronHelper::unlock();
		endif;	
	}	
	
	public function actiontrigger_order()
	{		
		ob_end_clean();
		header("Connection: close");
		ignore_user_abort(true);
		set_time_limit(1800);		
		ob_start();
		header("Content-Length: 0");
		ob_end_flush();
		flush();
		session_write_close();
		
		define('LOCK_SUFFIX', '.singleapp_trigger_order');
		if(($pid = cronHelper::lock()) !== FALSE):
		
		$default_title = "Updates on your order id [order_id]";
		$default_push_content = 'hi [customer_name]
								Your order id [order_id] has been updated to [order_status]
								';				
		$stmt="
		SELECT 
		a.trigger_id,
		a.trigger_type,
		a.order_id,
		a.order_status,
		a.order_status as request_status,
		a.remarks,
		a.remarks as merchant_remarks,
		a.language,
		a.status,
		
		b.merchant_id,
		b.client_id,
		
		c.restaurant_name,
				
		concat( d.first_name,' ',d.last_name ) as customer_name,
		
		e.device_id,
		e.device_platform,
		e.push_enabled,
		e.code_version
					
		FROM {{singleapp_order_trigger}} a
		left join {{order}} b
		on
		a.order_id = b.order_id
		
		left join {{merchant}} c
		on
		b.merchant_id = c.merchant_id
		
		left join {{client}} d
		on
		b.client_id = d.client_id
		
		left join {{singleapp_device_reg}} e
		on
		b.client_id = e.client_id
				
		WHERE 
		a.status = 'pending'
		AND a.trigger_type NOT IN ('booking')
		AND e.push_enabled=1
		LIMIT 0,50
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			$res = Yii::app()->request->stripSlashes($res);			
			foreach ($res as $val) {
				
				$trigger_id = $val['trigger_id'];
				$order_status = $val['order_status'];
				$lang = $val['language'];
				$process_status = 'process';
				
				switch ( trim($val['trigger_type']) ) {
					case "order":						
						$enabled = getOptionA("order_status_".$order_status."_push");						
						if($enabled==1){
							$push_title=getOptionA("order_status_".$order_status."_push_title_$lang");
							$push_message = getOptionA("order_status_".$order_status."_push_content_$lang"); 
							if(empty($push_title)){
								$push_title=$default_title;
							}
							if(empty($push_message)){
								$push_message=$default_push_content;
							}
							
							$pattern = 'order_id,order_status,restaurant_name,customer_name,remarks,sitename,siteurl';
			                $pattern = explode(",",$pattern); 
			                
			                foreach ($pattern as $key) {    							
								$push_title = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_title);
								$push_message = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_message);
							}											
							$params = array(
							  'client_id'=>(integer)$val['client_id'],
							  'client_name'=>trim($val['customer_name']),
							  'device_platform'=>trim($val['device_platform']),
							  'device_id'=>trim($val['device_id']),
							  'push_title'=>$push_title,
							  'push_message'=>$push_message,
							  'date_created'=>FunctionsV3::dateNow(),
							  'ip_address'=>$_SERVER['REMOTE_ADDR'],
							  'merchant_id'=>(integer)$val['merchant_id']
							);								
							Yii::app()->db->createCommand()->insert("{{singleapp_mobile_push_logs}}",$params);
						} else $process_status = 'error order status template is not enabled';
						break;
				
					case "order_request_cancel":
						$enabled = getOptionA("order_request_cancel_to_customer_push");								
						if($enabled==1){							
							$push_title=getOptionA("order_request_cancel_to_customer_push_title_$lang");
							$push_message = getOptionA("order_request_cancel_to_customer_push_content_$lang"); 
							if(empty($push_title)){
								$push_title=$default_title;
							}
							if(empty($push_message)){
								$push_message=$default_push_content;
							}
							
							$pattern = 'order_id,order_status,restaurant_name,customer_name,request_status,sitename,siteurl';
			                $pattern = explode(",",$pattern); 
			                
			                foreach ($pattern as $key) {    							
								$push_title = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_title);
								$push_message = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_message);
							}		
												
							$params = array(
							  'client_id'=>(integer)$val['client_id'],
							  'client_name'=>trim($val['customer_name']),
							  'device_platform'=>trim($val['device_platform']),
							  'device_id'=>trim($val['device_id']),
							  'push_title'=>$push_title,
							  'push_message'=>$push_message,
							  'date_created'=>FunctionsV3::dateNow(),
							  'ip_address'=>$_SERVER['REMOTE_ADDR'],
							  'merchant_id'=>(integer)$val['merchant_id']
							);	
							Yii::app()->db->createCommand()->insert("{{singleapp_mobile_push_logs}}",$params);
						} else $process_status = 'error order_request_cancel template is not enabled';
						break;
										    
					default:
						$process_status = 'invalid triger type';
						break;
				}
				
				Yii::app()->db->createCommand()->update("{{singleapp_order_trigger}}",array(
				  'status'=>$process_status,
				  'date_process'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				),
		  	    'trigger_id=:trigger_id',
			  	    array(
			  	      ':trigger_id'=>$trigger_id
			  	    )
		  	    );
		  	    
		  	    /*PROCESS THE PUSH*/		  	    
		  	    FunctionsV3::fastRequest(
		  	    FunctionsV3::getHostURL().Yii::app()->createUrl("singlemerchant/cron/processpush"));
				
			} //end foreach
		} 
		
		
		/*BOOKING*/
		$stmt="
		SELECT 
		a.trigger_id,
		a.trigger_type,
		a.order_id,
		a.order_status,
		a.order_status as request_status,
		a.remarks,
		a.remarks as merchant_remarks,
		a.language,
		a.status,
		
		b.merchant_id,
		b.client_id,
		b.booking_id,
		b.number_guest,
		b.date_booking,
		b.booking_time as time,		
		b.email,
		b.booking_notes as instruction,
		b.booking_name,
		
		c.restaurant_name,
		
		d.device_id,
		d.device_platform,
		d.push_enabled,
		d.code_version
		
		FROM {{singleapp_order_trigger}} a
		left join {{bookingtable}} b
		on
		a.order_id = b.booking_id
		
		left join {{merchant}} c
		on
		b.merchant_id = c.merchant_id
		
		left join {{singleapp_device_reg}} d
		on
		b.client_id = d.client_id
		
		WHERE 
		a.status = 'pending'
		AND
		a.trigger_type='booking'
		AND
		d.push_enabled = 1
		LIMIT 0,50
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
		   $res = Yii::app()->request->stripSlashes($res);				
		   foreach ($res as $val) {
		   	   $trigger_id = $val['trigger_id'];
			   $order_status = $val['order_status'];
			   $lang = $val['language'];
			   $process_status = 'process';		
			   
			   $val['status']=$val['order_status'];
			   $val['customer_name']=$val['booking_name'];
			   $enabled = getOptionA("booking_update_status_push");
			   if($enabled==1){
		         	$push_title=getOptionA("booking_update_status_push_title_$lang");
		         	$push_message=getOptionA("booking_update_status_push_content_$lang");
		         	
		         	if(empty($push_title)){
		         		$push_title='Update with your booking id [booking_id]';
		         	}
		         	if(empty($push_message)){
		         		$push_message='Update on your booking id [booking_id] with status of [status]';
		         	}
		         	
		         	$pattern = 'booking_id,restaurant_name,number_guest,date_booking,time,customer_name,email,mobile,instruction,status,merchant_remarks,sitename,siteurl';
	                $pattern = explode(",",$pattern); 
	                			                
	                foreach ($pattern as $key) {    							
						$push_title = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_title);
						$push_message = FunctionsV3::smarty($key, isset($val[$key])?$val[$key]:'' ,$push_message);
					}		
																
					$params = array(
					  'client_id'=>(integer)$val['client_id'],
					  'client_name'=>trim($val['customer_name']),
					  'device_platform'=>trim($val['device_platform']),
					  'device_id'=>trim($val['device_id']),
					  'push_title'=>$push_title,
					  'push_message'=>$push_message,
					  'date_created'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR'],
					  'merchant_id'=>(integer)$val['merchant_id']
					);							
					Yii::app()->db->createCommand()->insert("{{singleapp_mobile_push_logs}}",$params);
		         } else $process_status = 'template is not enabled';				         

		         
		        Yii::app()->db->createCommand()->update("{{singleapp_order_trigger}}",array(
				  'status'=>$process_status,
				  'date_process'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				),
		  	    'trigger_id=:trigger_id',
			  	    array(
			  	      ':trigger_id'=>$trigger_id
			  	    )
		  	    );
		  	    
		  	    /*PROCESS THE PUSH*/		  	    
		  	    FunctionsV3::fastRequest(
		  	    FunctionsV3::getHostURL().Yii::app()->createUrl("singlemerchant/cron/processpush"));   	
		          
		   } /*end foreach*/
		}
		
		cronHelper::unlock();
		endif;	
	}
	
	public function actionRunAll()
	{
	   Driver::consumeUrl(websiteUrl()."/singlemerchant/cron/processpush");
	   Driver::consumeUrl(websiteUrl()."/singlemerchant/cron/processbroadcast");
	   Driver::consumeUrl(websiteUrl()."/singlemerchant/cron/trigger_order");	   
	}
	
}
/*END CLASS*/
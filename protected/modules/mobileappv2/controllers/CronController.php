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
	
	public function actionProcessBroadcast()
	{
		define('LOCK_SUFFIX', '.mobileapp2_broadcast');
		
		dump("running broadcast...");
		
		if(($pid = cronHelper::lock()) !== FALSE) {	
			
			$service_account = getOptionA('mobileapp2_services_account_json');
			$file = FunctionsV3::uploadPath()."/$service_account";
			$fcm_response='';
			
			$resp = Yii::app()->db->createCommand()
		      ->select()
		      ->from('{{mobile2_broadcast}}')    
		      ->where("status=:status AND fcm_version=:fcm_version ",array(
		         ':status'=>"pending",                    
		         ':fcm_version'=>1
		      ))        
		      ->order('broadcast_id asc')    
		      ->limit(1)
		      ->queryAll();		
		    if($resp){		    	
		    	$res = Yii::app()->request->stripSlashes($res);
		    	foreach ($resp as $val) {
		    		$broadcast_id = $val['broadcast_id'];		    		
		    		try {		    		
		    			$fcm_response = FcmWrapper::ServiceAccount($file,'mobileapp2_fcm_v1')
						->setTarget($val['device_platform'])
						->setTitle($val['push_title'])
						->setBody($val['push_message'])
						->setChannel(CHANNEL_ID)
						->setSound(CHANNEL_SOUNDNAME)
						->setAppleSound(CHANNEL_SOUNDFILE)
						->setBadge(1)
						->setForeground("true")
						->prepare()
						->send();						
						$resp = 'process';
		    		} catch (Exception $e) {
						$fcm_response = $e->getMessage();						
						$resp = 'failed';
					}
					
					Yii::app()->db->createCommand()->update("{{mobile2_broadcast}}",array(
					 'status'=>$resp,
					 'date_modified'=>FunctionsV3::dateNow(),
					 'ip_address'=>$_SERVER['REMOTE_ADDR'],
					 'fcm_response'=>$fcm_response
					),
			  	    'broadcast_id=:broadcast_id',
				  	    array(
				  	      ':broadcast_id'=>$broadcast_id
				  	    )
			  	    );
					
		    	}
		    }
		    cronHelper::unlock();
		}
	}
	
	public function actionProcessOldBroadcast()
	{
		define('LOCK_SUFFIX', '.mobileapp2_broadcast_old');
		
		if(($pid = cronHelper::lock()) !== FALSE) :
					
		$end_records = false; $broadcast_id=0;
		
		$resp = Yii::app()->db->createCommand()
          ->select()
          ->from('{{mobile2_broadcast}}')    
          ->where("status=:status AND fcm_version=:fcm_version",array(
             ':status'=>"pending",
             ':fcm_version'=>0,
          ))        
          ->order('broadcast_id asc')    
          ->limit(1) 
          ->queryRow();		
          if($resp){
          	                     	 
			  $broadcast_id=$resp['broadcast_id'];
			  $push_title=$resp['push_title'];
			  $push_message=$resp['push_message'];
			  $date_created  = FunctionsV3::dateNow();
			  $ip_address = $_SERVER['REMOTE_ADDR'];
			  $date_now = date("Y-m-d");
			  
			   $and='';
		       switch ($resp['device_platform']) {
	    		case "1":	    				    		    
	    		    $and=" AND a.device_platform IN ('Android','android') ";
	    			break;
	    	
	    		case "2":		    		   
	    		   $and=" AND a.device_platform IN ('ios','iOS') ";
	    		   break;  	    		   
		       }
		       
		       $stmt="
		        SELECT		        
		        a.client_id,
		        concat(b.first_name,' ',b.last_name) as client_name,
		        a.device_platform,
		        a.device_id 
		        
		        FROM {{mobile2_device_reg}} a
		        
		        LEFT JOIN {{client}} b
		        ON
		        a.client_id = b.client_id
		        
		        WHERE a.push_enabled='1'
		        AND a.push_enabled='1'
		    	AND a.status in ('active')
		    	AND a.device_id !='' 
		    	$and
		        AND a.client_id NOT IN (
		          select client_id
		          from {{mobile2_push_logs}}
		          where
		          client_id=a.client_id
		          and
		          broadcast_id=".q($broadcast_id)."
		          and
		          device_id = a.device_id 		          		         
		        )
		       LIMIT 0,1
		       ";
		       //dump($stmt);
		       if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
		       	   //dump($res);
		       	   $stmt_insert="
			        INSERT INTO {{mobile2_push_logs}} 
			        (
				        broadcast_id,		        
				        client_id,
				        client_name,
				        device_platform,
				        device_id,
				        push_title,
				        push_message,
				        date_created,
				        ip_address
			        )	    	
			        SELECT
			        ".FunctionsV3::q($broadcast_id).",
			        a.client_id,
			        IFNULL(concat(b.first_name,' ',b.last_name),''),
			        IFNULL(a.device_platform,''),
			        IFNULL(a.device_id,''),
			        ".FunctionsV3::q($push_title).",
			        ".FunctionsV3::q($push_message).",
			        ".FunctionsV3::q($date_created).",
			        ".FunctionsV3::q($ip_address)."
			        FROM {{mobile2_device_reg}} a
			        LEFT JOIN {{client}} b
			        ON
			        a.client_id = b.client_id
			        
			        WHERE a.push_enabled='1'
			        AND a.push_enabled='1'
			    	AND a.status in ('active')
			    	AND a.device_id !='' 
			    	$and
		       	    AND a.client_id NOT IN (
			          select client_id
			          from {{mobile2_push_logs}}
			          where
			          client_id=a.client_id
			          and
			          broadcast_id=".q($broadcast_id)."
			          and
			          device_id = a.device_id 			         
			        )
		       	    LIMIT 0,50
			    	";    	     		       	    
		       	    Yii::app()->db->createCommand($stmt_insert)->query();
		       	
		       } else $end_records = true;
		       
		       if($end_records==TRUE){
		       	  //dump("END OF RECORDS =>$broadcast_id");
		       	  $params_update=array(
			          'status'=>"process",
			          'date_modified'=>FunctionsV3::dateNow(),	          
			      );
		       	  Yii::app()->db->createCommand()->update("{{mobile2_broadcast}}",$params_update,
	          	    'broadcast_id=:broadcast_id',
	          	    array(
	          	      ':broadcast_id'=>$broadcast_id
	          	    )
	          	  );
		       }
		       			 
          } else echo 'No records to process';
          
          cronHelper::unlock();
          endif;
	}	
	
	public function actionprocesspush()
	{		
		
		define('LOCK_SUFFIX', '.mobileapp2_processpush');
				
		if(($pid = cronHelper::lock()) !== FALSE) :
		
		dump("running processpush...");
		
		$server_key = getOptionA('mobileapp2_push_server_key');
		$push_icon = getOptionA('android_push_icon');
		
		$pushpic = '';
		$enabled_pushpic = getOptionA('android_enabled_pushpic');
		if($enabled_pushpic==1){		   
			$pushpic = getOptionA('android_push_picture');
		}
		
		$process_date = FunctionsV3::dateNow();
		$channel_id = CHANNEL_ID;
		
		$mobileapp2_fcm_provider = getOptionA('mobileapp2_fcm_provider');
		$mobileapp2_fcm_provider = $mobileapp2_fcm_provider>0?(integer)$mobileapp2_fcm_provider:1;
		
		$service_account = getOptionA('mobileapp2_services_account_json');
		$file = FunctionsV3::uploadPath()."/$service_account";
				
		$stmt="
		SELECT * FROM
		{{mobile2_push_logs}}
		WHERE status='pending'		
		ORDER BY id ASC		
		LIMIT 0,20
		";
						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$res = Yii::app()->request->stripSlashes($res);
			foreach ($res as $val) {
								
				$process_status=''; $json_response='';
			    $device_id = $val['device_id'];
			       
			    if($mobileapp2_fcm_provider==2):
			    
			    	try {		    		
		    			$json_response = FcmWrapper::ServiceAccount($file,'mobileapp2_fcm_v1')
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
			    
				    switch (strtolower($val['device_platform'])) {
				    	case "android":
				    		$data = array(
							  'title'=>$val['push_title'],
							  'body'=>$val['push_message'],
							  'vibrate'	=> 1,			
				              'soundname'=> CHANNEL_SOUNDNAME,
				              'android_channel_id'=>$channel_id,
				              'content-available'=>1,
				              'count'=>1,			              
				              'badge'=>1,
				              'push_type'=>$val['push_type']
							 );			
							 if(!empty($push_icon)){
							 	$data['image'] = mobileWrapper::getImage($push_icon);
							 }			 
							 
							 if($enabled_pushpic==1 && !empty($pushpic)){
							 	$data['style'] ="picture";
							 	$data['picture'] = mobileWrapper::getImage($pushpic);
							 }
							 
							 if(!empty($server_key)){
							 	 try {
								 	$json_response = fcmPush::pushAndroid($data,$device_id,$server_key);						 	
								 	$process_status='process';
								 } catch (Exception $e) {
								 	$process_status = "failed";
					                $json_response = 'Caught exception:'. $e->getMessage();
				                 }
							 } else {
							 	$process_status = "failed";
							 	$json_response = 'server key is empty';
							 }
				    		break;
				    		
				    	case "ios":
				    		try {
								 $data = array( 
							      'title' =>$val['push_title'],
							      'body' => $val['push_message'],
							      'sound'=>CHANNEL_SOUNDFILE,
							      'android_channel_id'=>$channel_id,
							      'badge'=>1,
							      'content-available'=>1,
							      'push_type'=>$val['push_type']
							    );						   
								$json_response = fcmPush::pushIOS($data,$device_id,$server_key);
								$process_status='process';							
							} catch (Exception $e) {
								$process_status = "failed";
								$json_response =  $e->getMessage();
							}		
				    		break;
				    		
				    	default:
				    		$process_status = "failed";
				    		$json_response = 'undefined device platform'; 
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
				
				Yii::app()->db->createCommand()->update("{{mobile2_push_logs}}",$params,
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
	
	public function actiontriggerorder()
	{
		define('LOCK_SUFFIX', '.mobileapp2_triggerorder');
		
		if(($pid = cronHelper::lock()) !== FALSE) :
		
		dump("running triggerorder...");		
		$stmt="
		SELECT
		a.trigger_id,
		a.trigger_type,
		a.order_id,
		a.order_status,
		a.remarks,	
		a.status,
		a.language,
		b.order_id as b_order_id,
		b.client_id,
		b.merchant_id,
		concat(c.first_name,' ',c.last_name) as customer_name,
		d.restaurant_name,
		e.booking_id,
		e.client_id as booking_client_id,
		(
		   select concat(first_name,' ',last_name)
		   from {{client}}
		   where client_id = e.client_id
		) as booking_customer_name
		
		
		FROM {{mobile2_order_trigger}} a
		left join {{order}} b
		ON a.order_id = b.order_id
		
		left join {{client}} c
		ON b.client_id = c.client_id
		
		left join {{merchant}} d
		ON b.merchant_id = d.merchant_id
		
		left join {{bookingtable}} e
		ON a.order_id = e.booking_id
		
		WHERE 
		a.status='pending'
		ORDER BY trigger_id ASC
		LIMIT 0,10
		";
		
		$website_title = getOptionA('website_title');
		$website_url = websiteUrl(); 
		$error='';
			
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
								
				$trigger_id = $val['trigger_id'];
				$lang = $val['language'];
				$status = $val['order_status'];
				$order_id = $val['order_id'];
								
				switch ($val['trigger_type']) {
					case "driver":						
					    require "trigger-driver.php";
						break;
						
					case "order":
						require "trigger-order.php";
						break;	
						
					case "order_request_cancel":
						require "trigger-cancel-order.php";
						break;	
						
					case "booking":						    				   
					    require "trigger-booking.php";					    
					break;	
				
					default:
						$error = "invalid trigger type";						
						break;
				}				
						    			    	
		    	$params_update = array(
		    	  'status'=>$error,
		    	  'date_process'=>FunctionsV3::dateNow(),
		    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
		    	);
		    	Yii::app()->db->createCommand()->update("{{mobile2_order_trigger}}",$params_update,
		  	    'trigger_id=:trigger_id',
			  	    array(
			  	      ':trigger_id'=>$trigger_id
			  	    )
		  	    );
		    		    	
		    	FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processpush"));
		    			    	
			} /*end foreach*/
		} else {
		    //dump("no records to process");
		}
		
		cronHelper::unlock();
        endif;		
	}
	
	public function actiongetfbavatar()
	{
		define('LOCK_SUFFIX', '.mobileapp2_fbavatar');
		
		if(($pid = cronHelper::lock()) !== FALSE) :
		
		dump("running getfbavatar...");
		
		$db = new DbExt();
		$stmt="
		SELECT client_id,avatar,social_id
		FROM {{client}}
		WHERE avatar =''
		AND social_id !=''
		AND social_strategy ='fb_mobile'
		LIMIT 0,2
		";
		if($res = $db->rst($stmt)){
			foreach ($res as $val) {				
				$params = array();
				$client_id = $val['client_id'];
				if($avatar = FunctionsV3::saveFbAvatarPicture($val['social_id'])){
				   $params['avatar'] = $avatar;
				} else $params['avatar'] = "avatar.jpg";
				$params['date_modified']=FunctionsV3::dateNow();
				$params['ip_address']=$_SERVER['REMOTE_ADDR'];				
				$db->updateData('{{client}}',$params,'client_id',$client_id);
			}
		} else {
			if(isset($_GET['debug'])){
			   echo 'no records to process';
			}	
		}
		
		cronHelper::unlock();
        endif;		
	}	
	
	public function actionRemoveInActiveDevice()
	{
		dump("running RemoveInActiveDevice...");
		
		$date_now=date('Y-m-d g:i:s a');
		$days_inactive = 30;
		
		$db = new DbExt();
		$stmt="
		SELECT * FROM
		{{mobile2_device_reg}}
		WHERE status ='active'
		ORDER BY date_created ASC
		LIMIT 0,20
		";
		if($res = $db->rst($stmt)){
			foreach ($res as $val) {				
				$time=date("Y-m-d g:i:s a",strtotime($val['date_created']));	
				$date_diff=Yii::app()->functions->dateDifference($time,$date_now);				
				if (is_array($date_diff) && count($date_diff)>=1){					
					if($date_diff['days']>=$days_inactive){						
						$db->updateData("{{mobile2_device_reg}}",array(
								  'status'=>'deactivated',
								  'date_modified'=>FunctionsV3::dateNow()
								),'id',$val['id']);
					}
				}
			}
		}
	}		
	
	public function actionRunAll()
	{
	   dump("cron start..");
       define('LOCK_SUFFIX', APP_FOLDER.'_runall');		
       if(($pid = cronHelper::lock()) !== FALSE):
       
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/processpush");
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/processbroadcast");
	   FunctionsV3::consumeUrl(websiteUrl()."/".APP_FOLDER."/cron/triggerorder");	   
	   
	   cronHelper::unlock();
       endif;	
       dump("cron end..");		
	}
	
}
/*end class*/
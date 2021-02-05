<?php
class CronController extends CController
{
	static $db;
	
	public function __construct()
	{		
		self::$db=new DbExt;
	}
	
	public function init()
	{			
		 // set website timezone
		 $website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone");	 		 
		 if (!empty($website_timezone)){		 	
		 	Yii::app()->timeZone=$website_timezone;
		 }		 				 
	}
	
	public function actionIndex()
	{		
		
	}
	
	public function actionProcessPush()
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
		 option_name = 'drv_services_json_account'
		 limit 0,1
		) as services_account_json,
		
		b.app_version
		
		FROM {{driver_pushlog}} a
		LEFT JOIN {{driver}} b
		ON
		a.driver_id = b.driver_id
		
		WHERE a.status='pending'
		ORDER BY a.date_created ASC
		LIMIT 0,20
		";		
						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
		   $file = Driver::certificatePath()."/".$res[0]['services_account_json'];		   
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
				
				Yii::app()->db->createCommand()->update("{{driver_pushlog}}",$params,
		  	    'push_id=:push_id',
			  	    array(
			  	      ':push_id'=>(integer)$val['push_id']
			  	    )
		  	    );
		   	  
		   } //end foreach
		}
		
		cronHelper::unlock();
		endif;	
		dump("cron end..");
	}
	
	public function actionAutoAssign()
	{
		
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_autoassign');        
        if(($pid = cronHelper::lock()) !== FALSE):

        
		$db=new DbExt;		
		$distance_exp=3959;  $radius=3000;			
		
		$date_now=date('Y-m-d');
		
		//$online_interval_date = date("Y-m-d H:i:s", strtotime("-5 minutes"));
		$online_interval_date = date("Y-m-d H:i:s", strtotime("-10 minutes"));
        $todays_date=date('Y-m-d');									
		
		$stmt="SELECT * FROM
		{{driver_task_view}}
		WHERE 1
		AND status IN ('unassigned')  
		AND auto_assign_type=''
		AND delivery_date like '$date_now%'
		AND delivery_address!=''
		ORDER BY task_id ASC
		LIMIT 0,10
		";
		
		if (isset($_GET['debug'])){dump($stmt);}		
		
		if ( $res=$db->rst($stmt)){			
			foreach ($res as $val) {
				
				if (isset($_GET['debug'])){
				   dump($val);
				}
				
				$user_type=$val['user_type'];
				$user_id=$val['user_id'];				
				
				$driver_enabled_auto_assign = Driver::getOption('driver_enabled_auto_assign',$user_type,$user_id);
				$driver_include_offline_driver = Driver::getOption('driver_include_offline_driver',$user_type,$user_id);
				$driver_auto_assign_type = Driver::getOption('driver_auto_assign_type',$user_type,$user_id);
				$driver_assign_request_expire = (integer) Driver::getOption('driver_assign_request_expire',$user_type,$user_id);
				if($driver_assign_request_expire<=0){
					$driver_assign_request_expire=10;
				}
				
				$assign_type = $driver_auto_assign_type;
				
				$notify_email = Driver::getOption('driver_autoassign_notify_email',$user_type,$user_id);
				
				if (isset($_GET['debug'])){
					dump("driver_enabled_auto_assign=>".$driver_enabled_auto_assign);
					dump("driver_include_offline_driver->".$driver_include_offline_driver);
				}
				
				if(empty($driver_enabled_auto_assign)){
					if (isset($_GET['debug'])){echo "auto assign is disabled";}		
					$db->updateData("{{driver_task}}",array(
					  'auto_assign_type'=>"none"
					),'task_id',$val['task_id']);
					continue;
				}
				
				$lat=''; $lng='';

				if($val['merchant_id']>0){
					if(!empty($val['dropoff_lat'])){
						$lat=$val['dropoff_lat'];
				        $lng=$val['dropoff_lng'];				
					} else {
						$lat=getOption($val['merchant_id'],'merchant_latitude');
				        $lng=getOption($val['merchant_id'],'merchant_longtitude');
					}
				} else {
					$lat=$val['task_lat'];
				    $lng=$val['task_lng'];				
				}										
				
				if(empty($lat)){					
					$lat=$val['task_lat'];
				    $lng=$val['task_lng'];
				}
				
				$task_id=$val['task_id'];	
				
				if (isset($_GET['debug'])){			
				   dump($lat); dump($lng);
				}
				
				$and='';
				//$todays_date=date('Y-m-d');			
		        //$time_now = time() - 600;
		        //$time_now=strtotime("-10 minutes");
		        
		        $assignment_status=t("waiting for driver acknowledgement");
				
				if ( $driver_include_offline_driver==""){
					/*$and.=" AND a.on_duty ='1' ";
                    $and.=" AND a.last_online >='$time_now' ";
                    $and.=" AND a.last_login like '".$todays_date."%'";*/
										
					$and.=" AND a.on_duty ='1' ";      
					$and.=" AND CAST(a.last_login as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." \n";
					$and.=" AND ".q($online_interval_date)." < a.last_login  ";              
				}
				
				$limit="LIMIT 0,100";
				if ( $driver_auto_assign_type=="one_by_one"){
					
					$and.=" AND a.user_type=".Driver::q($user_type)."";
					
				    if ($user_type=="merchant"){
					  $and.=" AND a.user_id=".Driver::q($user_id)."";
					  
					  $radius=getOption($val['user_id'],'driver_within_radius');
					  $driver_within_radius_unit=getOption($val['user_id'],'driver_within_radius_unit');					  
					  if ($driver_within_radius_unit=="km"){
						  $distance_exp=6371;
					  }	
					  
					  if($radius<=0){
					  	$radius=3000;
					  }
					  
					  /*check if can use driver admin*/
					  $driver_allowed_team_to_merchant=getOptionA('driver_allowed_team_to_merchant');
					  
					  if (isset($_GET['debug'])){
					     dump("driver_allowed_team_to_merchant=>".$driver_allowed_team_to_merchant);					  
					  }
					  if($driver_allowed_team_to_merchant>0){
					  	  if($driver_allowed_team_to_merchant==1){
					  	  	 $and.=" OR user_type='admin' ";
					  	  }  elseif ($driver_allowed_team_to_merchant==2){
					  	  	 $driver_allowed_merchant_list=getOptionA('driver_allowed_merchant_list');
					  	  	 if(!empty($driver_allowed_merchant_list)){
					  	  	 	$driver_allowed_merchant_list=json_decode($driver_allowed_merchant_list,true);
					  	  	 	if($val['user_id']>0){
					  	  	 		if(in_array($val['user_id'],(array)$driver_allowed_merchant_list)){
					  	  	 			$and.=" OR user_type='admin' ";
					  	  	 		}
					  	  	 	}
					  	  	 }
					  	  }
					  }
					  
				    } else {
				    	$radius=getOptionA('driver_within_radius');
				    	if($radius<=0){
					  	  $radius=3000;
					    }
					    
					    $driver_within_radius_unit=getOptionA('driver_within_radius_unit');
					    if ($driver_within_radius_unit=="km"){
						    $distance_exp=6371;
					    }	
				    }
					
					$and.=" AND a.driver_id NOT IN (
					  select driver_id
					  from
					  {{driver_assignment}}
					  where
					  driver_id=a.driver_id
					  and
					  task_id=".Driver::q($task_id)."
					) ";
										
					$stmt2="
					SELECT a.driver_id, a.first_name,a.last_name,a.location_lat,a.location_lng,
					a.user_type,a.user_id,
					a.on_duty, a.last_online, a.last_login
					, 
					( $distance_exp * acos( cos( radians($lat) ) * cos( radians( location_lat ) ) 
			        * cos( radians( location_lng ) - radians($lng) ) 
			        + sin( radians($lat) ) * sin( radians( location_lat ) ) ) ) 
			        AS distance
			        FROM {{driver}} a
			        WHERE a.status = 'active'
			        $and
			        HAVING distance < $radius										
					ORDER BY distance ASC					
					$limit
					";
				} else {
					
					if (isset($_GET['debug'])){
					   dump('send to all qry');
					}
					$and.=" AND user_type=".Driver::q($user_type)."";
					if ($user_type=="merchant"){
						$and.=" AND user_id=".Driver::q($user_id)."";
						
						 /*check if can use driver admin*/
						  $driver_allowed_team_to_merchant=getOptionA('driver_allowed_team_to_merchant');
						  
						  if (isset($_GET['debug'])){
						    dump("driver_allowed_team_to_merchant=>".$driver_allowed_team_to_merchant);					  
						  }
						  
						  if($driver_allowed_team_to_merchant>0){
						  	  if($driver_allowed_team_to_merchant==1){
						  	  	 $and.=" OR user_type='admin' ";
						  	  }  elseif ($driver_allowed_team_to_merchant==2){
						  	  	 $driver_allowed_merchant_list=getOptionA('driver_allowed_merchant_list');
						  	  	 if(!empty($driver_allowed_merchant_list)){
						  	  	 	$driver_allowed_merchant_list=json_decode($driver_allowed_merchant_list,true);
						  	  	 	if($val['user_id']>0){
						  	  	 		if(in_array($val['user_id'],(array)$driver_allowed_merchant_list)){
						  	  	 			$and.=" OR user_type='admin' ";
						  	  	 		}
						  	  	 	}
						  	  	 }
						  	  }
						  }
						
					}
					
					$and.=" AND a.driver_id NOT IN (
					  select driver_id
					  from
					  {{driver_assignment}}
					  where
					  driver_id=a.driver_id
					  and
					  task_id=".Driver::q($task_id)."
					) ";
					
					$stmt2="SELECT a.* FROM {{driver}} a		
					WHERE a.status='active'
					$and			
					";					
				}				
				if ( $res2=$db->rst($stmt2)){		
										
					$x=0;
								
					foreach ($res2 as $val2) {
														
						if($x==0){							
							$request_interval =  -$driver_assign_request_expire;
						} else {
							$request_interval =  $x*$driver_assign_request_expire;
						}
																	
						$params=array(
						  'auto_assign_type'=>$assign_type,
						  'task_id'=>$val['task_id'],
						  'driver_id'=>$val2['driver_id'],
						  'first_name'=>$val2['first_name'],
						  'last_name'=>$val2['last_name'],
						  'date_created'=>FunctionsV3::dateNow(),
						  'ip_address'=>$_SERVER['REMOTE_ADDR'],
						  'request_interval'=>$driver_assign_request_expire
						);
						if($driver_auto_assign_type=="one_by_one"){
							$params['date_created']=date("Y-m-d H:i:s", strtotime("+$request_interval minutes"));
						}
						if (isset($_GET['debug'])){
							echo "<h3>driver_assignment</h3>";
							dump($params);							
						}						
						if ( !Driver::validateAssigment($val['task_id'],$val2['driver_id']) ){
						   $db->insertData("{{driver_assignment}}",$params);
						}
						$x++;
					}					
				} else {
					//send email
					if(!empty($notify_email)){
						
						if (isset($_GET['debug'])){dump($notify_email);}
						$email_enabled=getOptionA('FAILED_AUTO_ASSIGN_EMAIL');
						if($email_enabled){
						   $tpl=getOptionA('FAILED_AUTO_ASSIGN_EMAIL_TPL');
						   $tpl=Driver::smarty('TaskID',$task_id,$tpl);
						   $tpl=Driver::smarty('CompanyName',getOptionA('website_title'),$tpl);
						   if (isset($_GET['debug'])){dump($tpl);}
						   sendEmail($notify_email,"","Unable to auto assign Task $task_id",$tpl);
						}
					}   	
					$assignment_status = "unable to auto assign";
				}
			} /*end foreach*/
			
			$less="-1";
			if($driver_assign_request_expire>0){
				$less="-$driver_assign_request_expire";
			}
			
			$params_task=array(
			 'auto_assign_type'=>$assign_type,			 			 
			 'assign_started'=>FunctionsV3::dateNow(),
			 'assignment_status'=> $assignment_status
			);	
			//dump($params_task);		
			if (isset($_GET['debug'])){dump($params_task);}			
			$db->updateData("{{driver_task}}",$params_task,'task_id',$task_id);
			
		} else {
			if (isset($_GET['debug'])){
				echo 'no record to process';
			}
		}		
		
		Driver::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("driver/cron/processautoassign_all"));
		Driver::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("driver/cron/processautoassign_onebyone"));
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}

	public function actionprocessautoassign_all()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_processautoassign_all');        
        if(($pid = cronHelper::lock()) !== FALSE):
        
		$stmt="
		SELECT 
		a.assignment_id,
		a.auto_assign_type,
		a.task_id,
		a.driver_id,
		concat(a.first_name,' ',a.last_name) as driver_name,
		
		b.customer_name,
		b.delivery_address,
		b.delivery_date,
		b.order_id,
		
		c.enabled_push,
		c.user_type,
		c.user_id,
		c.device_platform,
		c.device_id,
		c.phone as driver_phone,
		c.email as driver_email
		
		FROM
		{{driver_assignment}} a
		LEFT JOIN {{driver_task}} b
		ON
		a.task_id = b.task_id
		
		LEFT JOIN {{driver}} c
		ON
		a.driver_id = c.driver_id
		
		WHERE a.status='pending'
		AND a.auto_assign_type='send_to_all'	
		LIMIT 0,50		
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			foreach ($res as $val) {																	
				Driver::sendDriverNotification('ASSIGN_TASK',$val);	
				
				$params = array(
				 'status'=>"process",
				 'date_process'=>FunctionsV3::dateNow()
				);
				
				Yii::app()->db->createCommand()->update("{{driver_assignment}}",$params,
		  	    'assignment_id=:assignment_id',
			  	    array(
			  	      ':assignment_id'=>(integer)$val['assignment_id']
			  	    )
		  	    );	
							
			}
		}
		
		cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionprocessautoassign_onebyone()
	{
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_processautoassign_onebyone');        
        if(($pid = cronHelper::lock()) !== FALSE):
        
        $now = date("Y-m-d H:i:s");        
        //$online_interval_date = date("Y-m-d G:i:s", strtotime("-2 minutes"));		
        $online_interval_date = date("Y-m-d G:i:s", strtotime("-10 minutes"));		
        dump($online_interval_date);

        $website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone");
        if(!empty($website_timezone)){
        	$now = new DateTime();
			$mins = $now->getOffset() / 60;
			$sgn = ($mins < 0 ? -1 : 1);
			$mins = abs($mins);
			$hrs = floor($mins / 60);
			$mins -= $hrs * 60;
			$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);			
			Yii::app()->db->createCommand("SET time_zone='$offset';")->query();
        }
                       
        if($res = Yii::app()->db->createCommand("SELECT NOW()")->queryRow()){
        	dump($res);
        }
        
        $stmt="
        SELECT 
		a.assignment_id,
		a.auto_assign_type,
		a.task_id,
		a.driver_id,
		concat(a.first_name,' ',a.last_name) as driver_name,
		a.date_created,
		
		b.customer_name,
		b.delivery_address,
		b.delivery_date,
		b.order_id,
		
		c.enabled_push,
		b.user_type,
		b.user_id,
		c.device_platform,
		c.device_id,
		c.phone as driver_phone
				
		FROM
		{{driver_assignment}} a
		LEFT JOIN {{driver_task}} b
		ON
		a.task_id = b.task_id
		
		LEFT JOIN {{driver}} c
		ON
		a.driver_id = c.driver_id
		
		WHERE a.status='pending'
		AND a.auto_assign_type='one_by_one'					
		AND NOW() - INTERVAL a.request_interval MINUTE >= a.date_created
		LIMIT 0,50
		";
                
        if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
        	foreach ($res as $val) {
        		        		
        		Driver::sendDriverNotification('ASSIGN_TASK',$val);	
        		
        		Yii::app()->db->createCommand()->update("{{driver_assignment}}",array(
        		 'status'=>"process",
        		 'date_process'=>FunctionsV3::dateNow(),
        		 'ip_address'=>$_SERVER['REMOTE_ADDR']
        		),
		  	    'assignment_id=:assignment_id',
			  	    array(
			  	      ':assignment_id'=>$val['assignment_id']
			  	    )
		  	    );
		  	    
		  	    
		  	    $assigment_status = Yii::t("driver","waiting for [driver_name] acknowledgement",array(
		  	      '[driver_name]'=>isset($val['driver_name'])?$val['driver_name']:''
		  	    ));
		  	    
		  	    Yii::app()->db->createCommand()->update("{{driver_task}}",array(
		  	      'assignment_status'=>$assigment_status
		  	    ),
		  	    'task_id=:task_id',
			  	    array(
			  	      ':task_id'=> (integer) $val['task_id']
			  	    )
		  	    );
		  	    
		  	    
        	}
        }
        
        cronHelper::unlock();
        endif;	
        dump("cron end..");		
	}
	
	public function actionCheckAutoAssign()
	{
		
		dump("cron start..");
        define('LOCK_SUFFIX', APP_FOLDER.'_checkautoassign');        
        if(($pid = cronHelper::lock()) !== FALSE):


		$db=new DbExt;
				
		$stmt="SELECT a.* FROM
		{{driver_task}} a
		WHERE 1
		AND status IN ('unassigned') 	
		AND auto_assign_type IN ('one_by_one','send_to_all')	
		AND assignment_status NOT IN ('','unable to auto assign')
		AND task_id NOT IN (
		  select task_id from {{driver_assignment}}
		  where
		  task_id=a.task_id
		  and
		  status='pending'  
		)
		ORDER BY task_id ASC
		LIMIT 0,5
		";
		if (isset($_GET['debug'])){dump($stmt);}
		if ( $res=$db->rst($stmt)){
			foreach ($res as $val) {	
				
				
			    if (isset($_GET['debug'])){dump($val);}
			    
			    $user_type=$val['user_type'];
				$user_id=$val['user_id'];
				
				$notify_email = Driver::getOption('driver_autoassign_notify_email',$user_type,$user_id);
							    			    
			    $task_id=$val['task_id'];
			    		
				$task_id=$val['task_id'];
				$assign_type=$val['auto_assign_type'];
				$assign_started=date("Y-m-d g:i:s a",strtotime($val['assign_started']));
				
								
				$request_expire= (integer) Driver::getOption('driver_request_expire',$user_type,$user_id);				
				if(!is_numeric($request_expire)){
			        $request_expire=10;
				}			    				
				if($request_expire<=0){
			        $request_expire=10;
				}			    
				
				$date_now=date('Y-m-d g:i:s a');
				
				/*dump($task_id);
				dump("expire in :".$request_expire);
				dump($assign_type);
				dump($assign_started);
				dump($date_now);*/
				
				$time_diff=Yii::app()->functions->dateDifference($assign_started,$date_now);
				
				if (is_array($time_diff) && count($time_diff)>=1){
					
					if (isset($_GET['debug'])){dump($time_diff);}
					
				    if ( $time_diff['hours']>0 || $time_diff['minutes']>=$request_expire){				    	
				    	$params=array('assignment_status'=>"unable to auto assign");
				    	dump($params);
				    	$db->updateData("{{driver_task}}",$params,'task_id',$task_id);
				    	
				    	
				    	$stmt_assign="
				    	UPDATE {{driver_assignment}}
				    	SET task_status='unable to auto assign'
				    	WHERE
				    	task_id=".Driver::q($task_id)."
				    	";
				    	$db->qry($stmt_assign);
				    					    	
				    	Driver::sendDriverNotification('CANCEL_TASK',$val);
				    	
				    	//send email
				    	if(!empty($notify_email)){
				    		if (isset($_GET['debug'])){dump($notify_email);}
				    		$email_enabled=getOptionA('FAILED_AUTO_ASSIGN_EMAIL');
				    		if($email_enabled){
							   $tpl=getOptionA('FAILED_AUTO_ASSIGN_EMAIL_TPL');
							   $tpl=Driver::smarty('TaskID',$task_id,$tpl);
							   $tpl=Driver::smarty('CompanyName',getOptionA('website_title'),$tpl);
							   if (isset($_GET['debug'])){dump($tpl);}
				    		   sendEmail($notify_email,"","Unable to auto assign Task $task_id",$tpl);
				    		}
				    	}   	
				    	
				    	/*retry auto assign*/
				    	$driver_auto_assign_retry=Driver::getOption('driver_auto_assign_retry',$user_type,$user_id);
				    	if ( $driver_auto_assign_retry==1){
				    		Driver::retryAutoAssign($task_id);
				    	}
				    	
				    }
				}
				
			} /*end foreach*/
		}  else {
			if (isset($_GET['debug'])){
				echo "No results";
			}
		}		
		
		cronHelper::unlock();
		endif;	
		dump("cron end..");		

	}
	
	public function actionProcessBulkOld()
	{
		$stmt="SELECT * FROM
		{{driver_bulk_push}}
		WHERE
		status='pending'
		ORDER BY bulk_id ASC
		LIMIT 0,1
		";
		if ( $res=self::$db->rst($stmt)){
			foreach ($res as $val) {
				$bulk_id=$val['bulk_id'];
				dump($val);
				$stmt2="SELECT a.* FROM
				{{driver}} a
				WHERE
				device_id !=''
				AND driver_id NOT IN (
				  select driver_id
				  from {{driver_pushlog}}
				  where
				  driver_id=a.driver_id
				  and
				  bulk_id=".Driver::q($bulk_id)."
				)
				ORDER BY driver_id ASC
				LIMIT 0,1000
				";
				dump($stmt2);
				if ( $res2=self::$db->rst($stmt2)){
					foreach ($res2 as $val2) {						
						$params=array(
						  'push_title'=>$val['push_title'],
						  'push_message'=>$val['push_message'],
						  'device_platform'=>$val2['device_platform'],
						  'driver_id'=>$val2['driver_id'],
						  'device_id'=>$val2['device_id'],
						  'push_type'=>"bulk",
						  'actions'=>"bulk",
						  'bulk_id'=>$bulk_id,
						  'date_created'=>FunctionsV3::dateNow(),
						  'ip_address'=>$_SERVER['REMOTE_ADDR']
						);
						dump($params);
						self::$db->insertData("{{driver_pushlog}}",$params);
					}
				} else {
					echo "No records to process";
					self::$db->updateData("{{driver_bulk_push}}",
					   array('status'=>"process",'date_process'=>FunctionsV3::dateNow())
					   ,'bulk_id',$bulk_id);
				}
			}
		} else echo "No records to process";
	}
	
	public function actionProcessBulk()
	{
		dump("cron start..");
		define('LOCK_SUFFIX', APP_FOLDER.'_driver_broadcast');		
		if(($pid = cronHelper::lock()) !== FALSE):
		
		$stmt="
		SELECT a.*,
		(
		 select option_value
		 from {{option}}
		 where		 		 
		 option_name = 'drv_services_json_account'
		 limit 0,1
		) as services_account_json

		FROM {{driver_bulk_push}} a
		WHERE a.status='pending'		
		ORDER BY bulk_id ASC		
		LIMIT 0,10
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$file = Driver::certificatePath()."/".$res[0]['services_account_json'];			
			foreach ($res as $val) {				
				$process_status=''; $json_response='';
				$process_date = FunctionsV3::dateNow();
								
				$topic_id = $val['user_type']."_".$val['user_id'];
				$topics = CHANNEL_TOPIC_ALERT.$topic_id;
				 
				 try {		    		
	    			$json_response = FcmWrapper::ServiceAccount($file,APP_FOLDER.'_fcm')
					->setTarget($topics)
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
				  'fcm_response'=>$json_response
				);		
								
				Yii::app()->db->createCommand()->update("{{driver_bulk_push}}",$params,
		  	    'bulk_id=:bulk_id',
			  	    array(
			  	      ':bulk_id'=>$val['bulk_id']
			  	    )
		  	    );
				  
			} //end foreach
		}
		
		cronHelper::unlock();
		endif;	
		dump("cron end..");
		
		Driver::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("driver/cron/processpush"));
	}
	
	public function actionClearAgentTracking()
    {
    	$date=date("Y-m-d 23:59:00",strtotime("-5 days"));
    	$db=new DbExt;
    	$stmt="
    	DELETE FROM
    	{{driver_track_location}}
    	WHERE 
    	date_created <=".Driver::q($date)."
    	";
    	if (isset($_GET['debug'])){
    	   dump($stmt);
    	}
    	$db->qry($stmt);
    }
    
	public function actionRunAll()
	{
	   Driver::consumeUrl(websiteUrl()."/driver/cron/processpush");
	   Driver::consumeUrl(websiteUrl()."/driver/cron/autoassign");
	   Driver::consumeUrl(websiteUrl()."/driver/cron/processautoassign_all");
	   Driver::consumeUrl(websiteUrl()."/driver/cron/processautoassign_onebyone");
	   Driver::consumeUrl(websiteUrl()."/driver/cron/checkautoassign");
	   Driver::consumeUrl(websiteUrl()."/driver/cron/processbulk");
	}
		
} /*end class*/
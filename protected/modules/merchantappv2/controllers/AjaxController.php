<?php
class AjaxController extends CController
{
	public $code=2;
	public $msg;
	public $details;
	public $data;
	
	public function __construct()
	{
		$this->data=$_POST;	
		
		FunctionsV3::handleLanguage();
	    $lang=Yii::app()->language;	  
	      	   
	    $website_timezone=getOptionA('website_timezone');
	    if (!empty($website_timezone)){
	 	   Yii::app()->timeZone=$website_timezone;
	    }		
	}
	
	public function beforeAction($action)
	{		
		if(!Yii::app()->functions->isAdminLogin()){		   
		   Yii::app()->end();		
		}				
		return true;
	}
	
	private function jsonResponse()
	{
		$resp=array('code'=>$this->code,'msg'=>$this->msg,'details'=>$this->details);
		echo CJSON::encode($resp);
		Yii::app()->end();
	}
	
	private function otableNodata()
	{
		if (isset($_POST['draw'])){
			$feed_data['draw']=$_POST['draw'];
		} else $feed_data['draw']=1;	   
		     
        $feed_data['recordsTotal']=0;
        $feed_data['recordsFiltered']=0;
        $feed_data['data']=array();		
        echo json_encode($feed_data);
    	die();
	}

	private function otableOutput($feed_data='')
	{
	  echo json_encode($feed_data);
	  die();
    }    
    /*END INITIAL FUNCTIONS*/
	
    public function actionsavesettings()
    {
        	
    	Yii::app()->functions->updateOptionAdmin('merchantappv2_api_hash_key',
		isset($this->data['merchantappv2_api_hash_key'])? trim($this->data['merchantappv2_api_hash_key']) :''
		);
			
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionsavesettings_app()
    {
    	Yii::app()->functions->updateOptionAdmin('order_incoming_status',
		isset($this->data['order_incoming_status'])? json_encode($this->data['order_incoming_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_outgoing_status',
		isset($this->data['order_outgoing_status'])? json_encode($this->data['order_outgoing_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_ready_status',
		isset($this->data['order_ready_status'])? json_encode($this->data['order_ready_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_failed_status',
		isset($this->data['order_failed_status'])? json_encode($this->data['order_failed_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_successful_status',
		isset($this->data['order_successful_status'])? json_encode($this->data['order_successful_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_accepted_status',
		isset($this->data['order_action_accepted_status'])? trim($this->data['order_action_accepted_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_decline_status',
		isset($this->data['order_action_decline_status'])? trim($this->data['order_action_decline_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_cancel_status',
		isset($this->data['order_action_cancel_status'])? trim($this->data['order_action_cancel_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_food_done_status',
		isset($this->data['order_action_food_done_status'])? trim($this->data['order_action_food_done_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_delayed_status',
		isset($this->data['order_action_delayed_status'])? trim($this->data['order_action_delayed_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_completed_status',
		isset($this->data['order_action_completed_status'])? trim($this->data['order_action_completed_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_approved_cancel_order',
		isset($this->data['order_action_approved_cancel_order'])? trim($this->data['order_action_approved_cancel_order']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('order_action_decline_cancel_order',
		isset($this->data['order_action_decline_cancel_order'])? trim($this->data['order_action_decline_cancel_order']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('accepted_based_time',
		isset($this->data['accepted_based_time'])? trim($this->data['accepted_based_time']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_remove_accepting_time',
		isset($this->data['merchantapp_remove_accepting_time'])? (integer)trim($this->data['merchantapp_remove_accepting_time']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_remove_cancel_status',
		isset($this->data['merchantapp_remove_cancel_status'])? (integer)trim($this->data['merchantapp_remove_cancel_status']) :''
		);
			
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionbooking_settings()
    {
    	Yii::app()->functions->updateOptionAdmin('booking_incoming_status',
		isset($this->data['booking_incoming_status'])? json_encode($this->data['booking_incoming_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('booking_cancel_status',
		isset($this->data['booking_cancel_status'])? json_encode($this->data['booking_cancel_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('booking_done_status',
		isset($this->data['booking_done_status'])? json_encode($this->data['booking_done_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_enabled_booking',
		isset($this->data['merchantapp_enabled_booking'])? trim($this->data['merchantapp_enabled_booking']) :''
		);
		
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionapplication_settings()
    {
    	Yii::app()->functions->updateOptionAdmin('order_unattended_minutes',
		isset($this->data['order_unattended_minutes'])? trim($this->data['order_unattended_minutes']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('ready_outgoing_minutes',
		isset($this->data['ready_outgoing_minutes'])? trim($this->data['ready_outgoing_minutes']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('ready_unattended_minutes',
		isset($this->data['ready_unattended_minutes'])? trim($this->data['ready_unattended_minutes']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('booking_incoming_unattended_minutes',
		isset($this->data['booking_incoming_unattended_minutes'])? trim($this->data['booking_incoming_unattended_minutes']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('booking_cancel_unattended_minutes',
		isset($this->data['booking_cancel_unattended_minutes'])? trim($this->data['booking_cancel_unattended_minutes']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_keep_awake',
		isset($this->data['merchantapp_keep_awake'])? trim($this->data['merchantapp_keep_awake']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('refresh_order',
		isset($this->data['refresh_order'])? (integer)trim($this->data['refresh_order']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('refresh_cancel_order',
		isset($this->data['refresh_cancel_order'])? (integer)trim($this->data['refresh_cancel_order']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('refresh_booking',
		isset($this->data['refresh_booking'])? (integer)trim($this->data['refresh_booking']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('refresh_cancel_booking',
		isset($this->data['refresh_cancel_booking'])? (integer)trim($this->data['refresh_cancel_booking']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('interval_ready_order',
		isset($this->data['interval_ready_order'])? (integer)trim($this->data['interval_ready_order']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_upload_resize_width',
		isset($this->data['merchantapp_upload_resize_width'])? (integer)trim($this->data['merchantapp_upload_resize_width']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_upload_resize_height',
		isset($this->data['merchantapp_upload_resize_height'])? (integer)trim($this->data['merchantapp_upload_resize_height']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_upload_resize_enabled',
		isset($this->data['merchantapp_upload_resize_enabled'])? (integer)trim($this->data['merchantapp_upload_resize_enabled']) :''
		);
		
		$order_estimated_time='';
		if(isset($this->data['order_estimated_time'])){
			$new_json = array();
			if($json = json_decode($this->data['order_estimated_time'],true)){
				foreach ($json as $val) {
					if((integer)$val['value']>0){
						$new_json[]= $val;
					}
				}
				$order_estimated_time=json_encode($new_json);
			}
		}		
		
		Yii::app()->functions->updateOptionAdmin('order_estimated_time',$order_estimated_time);
		
		Yii::app()->functions->updateOptionAdmin('decline_reason_list',
		isset($this->data['decline_reason_list'])? trim($this->data['decline_reason_list']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('number_of_alert',
		isset($this->data['number_of_alert'])? (integer) trim($this->data['number_of_alert']) :''
		);
		
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionlanguage_settings()
    {    	
    	Yii::app()->functions->updateOptionAdmin('merchantapp_language',
		isset($this->data['merchantapp_language'])? json_encode($this->data['merchantapp_language']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('set_language',
		isset($this->data['set_language'])? trim($this->data['set_language']) :''
		);
		$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionuploadFile2()
    {
    	require_once('SimpleUploader.php');
    	if ( !Yii::app()->functions->isAdminLogin()){
			$this->msg = translate("Session has expired");
			$this->jsonResponse();
		}
		
		$path_to_upload  = Yii::getPathOfAlias('webroot')."/upload/";
		
		$valid_extensions = array('json');
		$Upload = new FileUpload('uploadfile');
		$ext = $Upload->getExtension();
		$time=time();
        $filename = $Upload->getFileNameWithoutExt();         
        $new_filename =  "$filename.$ext";
        $Upload->newFileName = $new_filename;
        $Upload->sizeLimit = FunctionsV3::imageLimitSize();
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions); 
	    if (!$result) {
	    	 $this->msg=$Upload->getErrorMsg();
	    } else {	    		    
	    	
	    	 $field_name = isset($_GET['field_name'])?$_GET['field_name']:'file_name';
	    	 $input = CHtml::hiddenField($field_name,$new_filename);
	    	 $input.= translate("File [file]",array(
	    	   '[file]'=>$new_filename
	    	 ));
	    	 
	    	 Yii::app()->functions->updateOptionAdmin(APP_FOLDER.'_fcm','');
	    		 
	    	 $this->code = 1;
	    	 $this->msg="OK";
	    	 $this->details=array(
	    	   'file_name'=>$new_filename,
	    	   'field_name'=>$field_name,
	    	   'input'=>$input
	    	 );
	    }
	    $this->jsonResponse();
    }
    
    public function actionsavesettings_fcm()
    {
    	
    	Yii::app()->functions->updateOptionAdmin('merchantapp_services_account_json',
		isset($this->data['merchantapp_services_account_json'])?$this->data['merchantapp_services_account_json']:''
		);
		
		Yii::app()->functions->updateOptionAdmin(APP_FOLDER.'_fcm','');
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_disabled_broadcast',
		isset($this->data['merchantapp_disabled_broadcast'])?$this->data['merchantapp_disabled_broadcast']:''
		);
		
    	$this->code=1;
	    $this->msg=translate("Settings saved");		
    	$this->jsonResponse();
    }
    
    public function actiondatable_localize()
    {
    	header('Content-type: application/json');
    	$data = array(
    	  'decimal'=>'',
    	  'emptyTable'=> translate('No data available in table'),
    	  'info'=> translate('Showing [start] to [end] of [total] entries',array(
    	    '[start]'=>"_START_",
    	    '[end]'=>"_END_",
    	    '[total]'=>"_TOTAL_",
    	  )),
    	  'infoEmpty'=> translate("Showing 0 to 0 of 0 entries"),
    	  'infoFiltered'=>translate("(filtered from [max] total entries)",array(
    	    '[max]'=>"_MAX_"
    	  )),
    	  'infoPostFix'=>'',
    	  'thousands'=>',',
    	  'lengthMenu'=> translate("Show [menu] entries",array(
    	    '[menu]'=>"_MENU_"
    	  )),
    	  'loadingRecords'=>translate('Loading...'),
    	  'processing'=>translate("Processing..."),
    	  'search'=>translate("Search:"),
    	  'zeroRecords'=>translate("No matching records found"),
    	  'paginate' =>array(
    	    'first'=>translate("First"),
    	    'last'=>translate("Last"),
    	    'next'=>translate("Next"),
    	    'previous'=>translate("Previous")
    	  ),
    	  'aria'=>array(
    	    'sortAscending'=>translate(": activate to sort column ascending"),
    	    'sortDescending'=>translate(": activate to sort column descending")
    	  )
    	);    	
    	echo json_encode($data);
    }    
        
	public function actionregisteredDeviceList()
	{
				
		$cols = array(
		  'registration_id','name','device_platform','device_uiid','device_id',
		  'push_enabled','subscribe_topic','date_created','last_login','registration_id'
		);
				
        $resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		
		$and=" AND a.status in ('active')";
		$and.=" AND a.device_id !='' ";
		
		$search_fields = isset($this->data['search_fields'])?trim($this->data['search_fields']):'';
		if(!empty($search_fields)){
			$and.=" AND 
			(			  
			  a.name LIKE ".q("%$search_fields%")." OR
			  a.device_platform LIKE ".q("%$search_fields%")." OR
			  a.device_uiid LIKE ".q("%$search_fields%")." OR
			  a.device_id LIKE ".q("%$search_fields%")." 
			)
			";
		}
						
		$stmt="SELECT SQL_CALC_FOUND_ROWS
		a.registration_id,
		a.name,		
		a.device_platform,
		a.device_uiid,
		a.device_id,
		a.push_enabled,
		a.subscribe_topic,
		a.date_created,
		a.last_login		
		
		FROM
		{{view_merchantapp_device}} a
		
		WHERE 1				
		$and
		$order
		$limit
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){									
			$res = Yii::app()->request->stripSlashes($res);
			$total_records=0;									
			if($resc = Yii::app()->db->createCommand("SELECT FOUND_ROWS() as total_records")->queryRow()){						
				$total_records=$resc['total_records'];
			}			
			$feed_data['draw']=$this->data['draw'];
			$feed_data['recordsTotal']=$total_records;
			$feed_data['recordsFiltered']=$total_records;
					
			
			foreach ($res as $val) {
				
			    $date_created = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
			    $last_login = FunctionsV3::prettyDate($val['last_login'])." ".FunctionsV3::prettyTime($val['last_login']);
			    
			    
			    $enabled_push =  $val['push_enabled']==1?translate("Yes"):translate("No");			    
			    $subscribe_topic =  $val['subscribe_topic']==1?translate("Yes"):translate("No");
			    
			    $actions='<a href="javascript:;" data-id="'.$val['registration_id'].'" class="send_push" >'.translate("send push").'</a>';
			    
			    $info= "<a href=\"javascript:;\" class=\"show_device_id\" data-id=\"".$val['device_id']."\" data-toggle=\"modal\" data-target=\"#deviceDetails\" >";
		  	    $info.= '<div class="concat-text">'.$val['device_id']."</div>" ;
			    $info.="</a> ";				
			    
				$feed_data['aaData'][]=array(
				  $val['registration_id'],				  
				  $val['name'],
				  translate( strtolower($val['device_platform']) ),				  
				  "<p class=\"concat-text\">".$val['device_uiid']."</p>",
				  $info,	 
				  MerchantWrapper::prettyBadge($enabled_push),
				  MerchantWrapper::prettyBadge($subscribe_topic),
				  $date_created,
				  $last_login,
				  $actions
				);
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}    
	
	public function actionsendpush()
	{
		if(empty($this->data['push_title'])){
			$this->msg =$this->t("Push title is required");
			$this->jsonResponse();
		}
		if(empty($this->data['push_message'])){
			$this->msg =$this->t("Push message is required");
			$this->jsonResponse();
		}
					
	    $id = isset($this->data['id'])?(integer)$this->data['id']:0;	    
	    if($id>0){
	    	if ($data = MerchantUserWrapper::getDeviceByID($id)){	    			    		
	    		$params = array(	    		  
	    		  'push_type'=>"campaign",
	    		  'merchant_name'=>isset($data['name'])?$data['name']:'',
	    		  'device_platform'=>$data['device_platform'],
	    		  'device_id'=>$data['device_id'],
	    		  'device_uiid'=>$data['device_uiid'],
	    		  'push_title'=>$this->data['push_title'],
	    		  'push_message'=>$this->data['push_message'],	    		  
	    		  'date_created'=>FunctionsV3::dateNow(),
	    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],	    		  
	    		);	    			    		
	    		if(Yii::app()->db->createCommand()->insert("{{merchantapp_push_logs}}",$params)){	
	    			$this->code = 1;
	    			$this->msg = translate("Request has been sent");	
	    			$this->details = array(
	    			  'next_action'=>"close_send_push_modal"
	    			);
	    			FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processpush"));
	    		} else $this->msg = translate("failed cannot insert records. please try again later");
	    	} else $this->msg = translate("device id not found");
	    } else $this->msg = translate("Invalid registration id");
	    $this->jsonResponse();
	}
    
	public function actionpushLogs()
	{
		
		$p = new CHtmlPurifier();
		
		$cols = array(
		  'id','push_type','merchant_name','device_platform',
		  'device_id',
		  'push_title','push_message','date_created','date_process'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and='';		
		$search_fields = isset($this->data['search_fields'])?trim($this->data['search_fields']):'';
		if(!empty($search_fields)){
			$and.=" AND 
			(			  
			  a.merchant_name LIKE ".q("%$search_fields%")." OR
			  a.push_type LIKE ".q("%$search_fields%")." OR
			  a.device_platform LIKE ".q("%$search_fields%")." OR
			  a.device_id LIKE ".q("%$search_fields%")." OR
			  a.device_uiid LIKE ".q("%$search_fields%")." OR
			  a.push_title LIKE ".q("%$search_fields%")." OR
			  a.push_message LIKE ".q("%$search_fields%")." 
			)
			";
		}
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{merchantapp_push_logs}} a
		WHERE 1				
		$and
		$order
		$limit
		";						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			
			$res = Yii::app()->request->stripSlashes($res);		
			$total_records=0;									
			if($resc = Yii::app()->db->createCommand("SELECT FOUND_ROWS() as total_records")->queryRow()){						
				$total_records=$resc['total_records'];
			}			
			$feed_data['draw']=$this->data['draw'];
			$feed_data['recordsTotal']=$total_records;
			$feed_data['recordsFiltered']=$total_records;
			
			foreach ($res as $val) {
							    
			    $t = MerchantWrapper::prettyBadge( $val['status'] );												
				$t.= "<a href=\"javascript:;\" class=\"show_error_details\" data-id=\"".$val['id']."\" data-toggle=\"modal\" data-target=\"#errorDetails\" >
				<i class=\"pl-2 fas fa-question-circle\"></i></a> ";											
				$t .= "<div></div>";
				
				$t.= FunctionsV3::prettyDate( $val['date_created'] )." ".FunctionsV3::prettyTime( $val['date_created'] );
			    
			    $date_process = FunctionsV3::prettyDate($val['date_process'])." ".FunctionsV3::prettyTime($val['date_process']);
			    
			    $info= "<a href=\"javascript:;\" class=\"show_device_id\" data-id=\"".$val['device_id']."\" data-toggle=\"modal\" data-target=\"#deviceDetails\" >";
			  	 $info.= '<div class="concat-text">'.$val['device_id']."</div>" ;
				 $info.="</a> ";				
			    			    
				$feed_data['aaData'][]=array(
				  $val['id'],
				  translate($val['push_type']),
				  $val['merchant_name'],
				  translate( strtolower($val['device_platform']) ),
				  $info,
				  $p->purify($val['push_title']),
				  $p->purify($val['push_message']),
				  $t,	
				  $date_process			  
				);
			}			
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();	
	}
	
    public function actionerrorDetails()
    {    	
    	$stmt = '';
    	$id = isset($this->data['details_id'])?(integer)$this->data['details_id']:'';
    	$current_page = isset($this->data['current_page'])?$this->data['current_page']:'';    	
    	if($id>0){
    		
    		switch ($current_page) {
    			case "push_logs":
    				$stmt = "
    				SELECT json_response as fcm_response
    				FROM {{merchantapp_push_logs}}
    				WHERE id=".q($id)."
    				LIMIT 0,1
    				";
    				break;
    		
    			case "push_broadcast":	    			
    			   $stmt = "
    				SELECT fcm_response
    				FROM {{merchantapp_broadcast}}
    				WHERE broadcast_id=".q($id)."
    				LIMIT 0,1
    				";
    			    break;
    			    
    			default:
    				break;
    		}     			       	
    		if(!empty($stmt)){
    			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){    				    				
    				$this->code = 1; 
    				$this->msg = !empty($res['fcm_response'])?$res['fcm_response']:translate("None");   
    				$this->details = array(
    				  'next_action'=>"set_push_status"
    				);
    			} else $this->msg = translate("Record not found");
    		} else $this->msg = translate("Invalid table");
    	} else $this->msg = translate("Invalid details id");
    	$this->jsonResponse();
    }    	
    
	public function actionpushBroadcast()
	{
		$cols = array(
		  'broadcast_id','push_title','push_message','merchant_name',
		  'topics','date_created','date_modified'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and="";
		
		$search_fields = isset($this->data['search_fields'])?trim($this->data['search_fields']):'';
		if(!empty($search_fields)){
			$and.=" AND 
			(			  
			  a.merchant_name LIKE ".q("%$search_fields%")." OR
			  a.push_title LIKE ".q("%$search_fields%")." OR
			  a.push_message LIKE ".q("%$search_fields%")." 			  
			)
			";
		}
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		
		FROM
		{{merchantapp_broadcast}} a
		
		WHERE 1				
		$and
		$order
		$limit
		";						
		//dump($stmt);
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$res = Yii::app()->request->stripSlashes($res);
			$total_records=0;									
			if($resc = Yii::app()->db->createCommand("SELECT FOUND_ROWS() as total_records")->queryRow()){
				$total_records=$resc['total_records'];
			}			
			$feed_data['draw']=$this->data['draw'];
			$feed_data['recordsTotal']=$total_records;
			$feed_data['recordsFiltered']=$total_records;
			
			foreach ($res as $val) {	
				$res = Yii::app()->request->stripSlashes($res);						
				$details_link=Yii::app()->createUrl('singlemerchant/index/broadcast_details',array(
			      'id'=>$val['broadcast_id']
			    ));	
			    
				$action = '<a href="'.$details_link.'">'.translate("View details").'</a>';
												
				$date_modified = FunctionsV3::prettyDate( $val['date_modified'] )." ".FunctionsV3::prettyTime( $val['date_modified'] );			
				
				 $t = MerchantWrapper::prettyBadge( $val['status'] );				   	  	 
				 $t.= "<a href=\"javascript:;\" class=\"show_error_details\" data-id=\"".$val['broadcast_id']."\" data-toggle=\"modal\" data-target=\"#errorDetails\" >
				 <i class=\"pl-2 fas fa-question-circle\"></i></a> ";
				
		   	  	 $t .= "<div></div>";
		   	  	 $t.= FunctionsV3::prettyDate( $val['date_created'] )." ".FunctionsV3::prettyTime( $val['date_created'] );	
				
				$feed_data['aaData'][]=array(				  
				   $val['broadcast_id'],
				   $val['push_title'],
				   $val['push_message'],		
				   $val['merchant_name'],		   
				   $val['topics'],
				   $t,
				   $date_modified
				);
			}			
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();	
	}    

    public function actionmerchant_list()
    {
    	$this->data = $_GET; $data = array(); $and='';    	
    	if(isset($this->data['search'])){    		
    		if(strlen($this->data['search'])>0){
    			$and=" AND restaurant_name LIKE ".q($this->data['search']."%")." ";
    		}
    	}
    	
    	$stmt="
    	SELECT merchant_id as id, restaurant_name as text
    	FROM {{merchant}}
    	WHERE status IN ('active')    	
    	$and
    	ORDER BY restaurant_name ASC
    	LIMIT 0,20
    	";    	
    	if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
    		$res = Yii::app()->request->stripSlashes($res);
    		$data = $res;
    	} 
    	$result = array(
    	  'results'=>$data
    	);
    	header('Content-type: application/json');
    	echo json_encode($result);
    }	
    
    public function actionsave_broadcast()
    {    	    	
    	
    	$merchant_id = isset($this->data['merchant_list'])?(integer)$this->data['merchant_list']:0;
    	if($merchant_id<0){
    		$this->msg = translate("Merchant is required");
    		$this->jsonResponse();
    	}
    	
    	try {
    		$resp = MerchantWrapper::getMerchantInformation($merchant_id);
    		
    	} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
			$this->jsonResponse();
		}				
    	
    	$params = array(
    	   'merchant_id'=>$merchant_id,
    	   'merchant_name'=>$resp['restaurant_name'],
    	   'push_title'=>isset($this->data['push_title'])?$this->data['push_title']:'',
    	   'push_message'=>isset($this->data['push_message'])?$this->data['push_message']:'',
    	   'topics'=>CHANNEL_TOPIC_ALERT.$merchant_id,
    	   'date_created'=>FunctionsV3::dateNow(),
    	   'ip_address'=>$_SERVER['REMOTE_ADDR'],    	       	   
    	);    	    	
    	
    	if(Yii::app()->db->createCommand()->insert("{{merchantapp_broadcast}}",$params)){	
    		$this->code = 1;
    		$this->msg = translate("Successful");    		    		    		
    		$this->details = array(
			  'next_action'=>"close_broadcast_modal"
			);
	    			
    		FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/processbroadcast"));    		    		
    	} else $this->msg = translate("failed cannot insert records. please try again later");
    	$this->jsonResponse();
    }
    
    public function actionorder_trigger()
    {    	    	
    	$feed_data = array();
    	
    	$cols = array(
		  'trigger_id','trigger_type',
		  'order_id','order_status','status','date_process'
		);			
				
		$resp = DatatablesWrapper::format($cols,$this->data);		
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and='';
		
		$search_fields = isset($this->data['search_fields'])?trim($this->data['search_fields']):'';
		if(!empty($search_fields)){
			$and.=" AND 
			(			  
			  a.trigger_id LIKE ".q("$search_fields%")." OR
			  a.trigger_type LIKE ".q("%$search_fields%")." OR
			  a.order_status LIKE ".q("%$search_fields%")." OR
			  a.status LIKE ".q("%$search_fields%")." OR
			  a.order_id LIKE ".q("$search_fields%")." 			  
			)
			";
		}
		
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.* FROM
		{{merchantapp_order_trigger}} a
		WHERE 1
		$where
		$and
		$order
		$limit
		";
						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$res = Yii::app()->request->stripSlashes($res);
			$total_records=0;						
			if($resc = Yii::app()->db->createCommand("SELECT FOUND_ROWS() as total_records")->queryRow()){						
	           $total_records=$resc['total_records'];
            }			
			$feed_data['draw']=$this->data['draw'];
			$feed_data['recordsTotal']=$total_records;
			$feed_data['recordsFiltered']=$total_records;
			
			$datas=array(); 
			foreach ($res as $val) {
											
				$cols_data = array();
				foreach ($cols as $key_cols=> $cols_val) {						   
				   if(array_key_exists($cols_val,(array)$val)){		
				   	  if($key_cols==5 ){
				   	  	 
						$t = MerchantWrapper::prettyBadge( $val['status'] );
						$t .= "<div></div>";
						$t.= FunctionsV3::prettyDate( $val[$cols_val] )." ".FunctionsV3::prettyTime( $val[$cols_val] );
						$cols_data[]=$t;
					  
				   	  } elseif ( $key_cols==1){
				   	  	$cols_data[]=translate($val[$cols_val]);
				   	  } elseif ( $key_cols==3){
				   	  	$cols_data[]=translate($val[$cols_val]);	
				   	  } elseif ( $key_cols==4){
				   	  	$cols_data[]=translate($val[$cols_val]);		
				   	  } else $cols_data[]=$val[$cols_val];
				   }			
				}
				$datas[]=$cols_data;
			}			
			$feed_data['data']=$datas;						
			$this->otableOutput($feed_data);	
		} else $this->otableNodata();
    }	
    
    public function actionauto_order_settings()
    {
    	
    	Yii::app()->functions->updateOptionAdmin('merchantapp_enabled_auto_status_enabled',
		isset($this->data['merchantapp_enabled_auto_status_enabled'])? trim($this->data['merchantapp_enabled_auto_status_enabled']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_enabled_auto_status_time',
		isset($this->data['merchantapp_enabled_auto_status_time'])? trim($this->data['merchantapp_enabled_auto_status_time']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_enabled_auto_status',
		isset($this->data['merchantapp_enabled_auto_status'])? trim($this->data['merchantapp_enabled_auto_status']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin('merchantapp_enabled_auto_status_readyin',
		isset($this->data['merchantapp_enabled_auto_status_readyin'])? trim($this->data['merchantapp_enabled_auto_status_readyin']) :''
		);
			
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionsavesettings_printer()
    {    	
    	$prefix = "print_";
    	
    	Yii::app()->functions->updateOptionAdmin($prefix.'merchant_name',
		isset($this->data[$prefix.'merchant_name'])? trim($this->data[$prefix.'merchant_name']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'merchant_address',
		isset($this->data[$prefix.'merchant_address'])? trim($this->data[$prefix.'merchant_address']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'merchant_contact_phone',
		isset($this->data[$prefix.'merchant_contact_phone'])? trim($this->data[$prefix.'merchant_contact_phone']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'printed_date',
		isset($this->data[$prefix.'printed_date'])? trim($this->data[$prefix.'printed_date']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'customer_name',
		isset($this->data[$prefix.'customer_name'])? trim($this->data[$prefix.'customer_name']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'trans_type',
		isset($this->data[$prefix.'trans_type'])? trim($this->data[$prefix.'trans_type']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'payment_type',
		isset($this->data[$prefix.'payment_type'])? trim($this->data[$prefix.'payment_type']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'order_id',
		isset($this->data[$prefix.'order_id'])? trim($this->data[$prefix.'order_id']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'date_created',
		isset($this->data[$prefix.'date_created'])? trim($this->data[$prefix.'date_created']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'delivery_date',
		isset($this->data[$prefix.'delivery_date'])? trim($this->data[$prefix.'delivery_date']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'delivery_time',
		isset($this->data[$prefix.'delivery_time'])? trim($this->data[$prefix.'delivery_time']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'delivery_address',
		isset($this->data[$prefix.'delivery_address'])? trim($this->data[$prefix.'delivery_address']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'delivery_instruction',
		isset($this->data[$prefix.'delivery_instruction'])? trim($this->data[$prefix.'delivery_instruction']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'location_name',
		isset($this->data[$prefix.'location_name'])? trim($this->data[$prefix.'location_name']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'contact_phone',
		isset($this->data[$prefix.'contact_phone'])? trim($this->data[$prefix.'contact_phone']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'order_change',
		isset($this->data[$prefix.'order_change'])? trim($this->data[$prefix.'order_change']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'site_url',
		isset($this->data[$prefix.'site_url'])? trim($this->data[$prefix.'site_url']) :''
		);
		Yii::app()->functions->updateOptionAdmin($prefix.'footer',
		isset($this->data[$prefix.'footer'])? trim($this->data[$prefix.'footer']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'footer',
		isset($this->data[$prefix.'footer'])? trim($this->data[$prefix.'footer']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'enabled_printer',
		isset($this->data[$prefix.'enabled_printer'])? trim($this->data[$prefix.'enabled_printer']) :''
		);
		
		Yii::app()->functions->updateOptionAdmin($prefix.'enabled_printer_fp_wifi',
		isset($this->data[$prefix.'enabled_printer_fp_wifi'])? trim($this->data[$prefix.'enabled_printer_fp_wifi']) :''
		);
		
    	
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
    
    public function actionsavesettings_policy()
    {
    	Yii::app()->functions->updateOptionAdmin('merchantapp_privacy_policy_link',
		isset($this->data['merchantapp_privacy_policy_link'])? trim($this->data['merchantapp_privacy_policy_link']) :''
		);
		
    	
    	$this->code=1;
	    $this->msg=translate("Settings saved");
		$this->jsonResponse();	
    }
	
}
/*end class*/
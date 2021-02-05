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
	}
	
	public function init()
	{
		FunctionsV3::handleLanguage();
		$lang=Yii::app()->language;				
	}
	
	public function beforeAction($action)
	{		
		if(!Yii::app()->functions->isAdminLogin()){
		   $this->msg = SingleAppClass::t("Session has expired please relogin again");
		   $this->jsonResponse();
		}	
		
		/*DEMO*/
		$action_name= $action->id;
		$demo_action = array(
		'savesettings',
		'save_appsettings',
		'savesettings_startup',
		'save_banner',
		'save_socialsettings',
		'save_sandroidsettings',
		'save_fcm',
		'save_page',
		'save_contactus',
		'saveBroadcast',
		'uploadFile',
		'upload'
		);
		/*if(in_array($action_name,$demo_action)){
			$this->msg = "This action is disabled on this demo";
			$this->jsonResponse();
		}*/
		
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
    
	public function actionIndex()
	{					
	}	
	
	public function actionvalidate_lang()
    {    	
    	header("Content-type:application/javascript");
    	echo '
		$.extend( $.validator.messages, {
			required: "'. $this->t("This field is required.") . '" ,
			remote:  "'. $this->t("Please correct this field to continue") . '" ,
			email: "'. $this->t("Please enter a valid email address") . '" ,
			url: "'. $this->t("Please enter a valid website address") . '" ,
			date: "'. $this->t("Please enter a valid date") . '" ,
			dateISO: "'. $this->t("Please enter a valid date (ISO)") . '" ,
			number: "'. $this->t("Please enter a valid number") . '" ,
			digits: "'. $this->t("Please enter numbers only") . '" ,
			creditcard: "'. $this->t("Please enter a valid credit card number") . '" ,
			equalTo: "'. $this->t("Please enter the same value") . '" ,
			extension: "'. $this->t("Please enter a file with an approved extension") . '" ,
			maxlength: $.validator.format( "'. $this->t("Maximum number of characters is {0}") . '"  ),
			minlength: $.validator.format( "'. $this->t("The minimum number of characters is {0}") . '"  ),
			rangelength: $.validator.format( "'. $this->t("The number of characters must be between {0} and {1}") . '"  ),
			range: $.validator.format( "'. $this->t("Please enter a value between {0} and {1}") . '"   ),
			max: $.validator.format( "'. $this->t("Please enter less than or equal to {0}") . '"  ),
			min: $.validator.format(  "'. $this->t("Please enter more than or equal to {0}") . '"  )
		} ); ';	
    }
	
	public function t($msg='')
	{
		return SingleAppClass::t($msg);
	}
	
	public function actionsavesettings()
	{		
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		
		$single_app_keys = isset($this->data['single_app_keys'])?$this->data['single_app_keys']:'';
		if(empty($single_app_keys)){
			$this->msg = st("Merchant keys is required");
			$this->jsonResponse();
		}		
		$params = array(
		  'single_app_keys'=>trim($this->data['single_app_keys']),
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		if($merchant_id>0){
			$db = new  DbExt();
			if ($db->updateData("{{merchant}}",$params,'merchant_id',$merchant_id)){
				$this->code = 1;
				$this->msg = $this->t("Successful");
			} else $this->msg = $this->t("Failed cannot update. please try again later");
		} else $this->msg = $this->t("Invalid merchant id");
		$this->jsonResponse();
	}
	
	public function actiongenerateKeys()
	{
		$single_app_keys  = SingleAppClass::generateMerchantKeys();
		$this->code = 1;
		$this->msg = "OK";
		$this->details= $single_app_keys;
		$this->jsonResponse();		
	}
	
    public function actionUpload()
	{
		require_once('Uploader.php');
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload";
        $valid_extensions = array('jpeg', 'png' ,'jpg'); 
        if(!file_exists($path_to_upload)) {	
           if (!@mkdir($path_to_upload,0777)){           	               	
           	    $this->msg=SingleAppClass::t("Error has occured cannot create upload directory");
                $this->jsonResponse();
           }		    
	    }
	    
        $Upload = new FileUpload('uploadfile');
        $ext = $Upload->getExtension(); 
        $Upload->newFileName = $Upload->getFileName()."_".time().".".$ext;
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions);                
        if (!$result) {                    	
            $this->msg=$Upload->getErrorMsg();            
        } else {         	
        	$this->code=1;
        	$this->msg=SingleAppClass::t("upload done");        	        			
            $this->details=array(			
			  'file_url'=>Yii::app()->getBaseUrl(true)."/upload/".$Upload->newFileName,
			  'file_name'=>$Upload->newFileName
			);
        }
        $this->jsonResponse();
	}	
	
	public function actionsave_appsettings()
	{
		
        $merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		
		if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
		
		$this->code = 1;		
	    $this->msg = $this->t("Successful");

        Yii::app()->functions->updateOption("singleapp_default_image",
	    isset($this->data['singleapp_default_image'])?$this->data['singleapp_default_image']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_location_accuracy",
	    isset($this->data['singleapp_location_accuracy'])?$this->data['singleapp_location_accuracy']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_default_lang",
	    isset($this->data['singleapp_default_lang'])?$this->data['singleapp_default_lang']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_help_url",
	    isset($this->data['singleapp_help_url'])?$this->data['singleapp_help_url']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_terms_url",
	    isset($this->data['singleapp_terms_url'])?$this->data['singleapp_terms_url']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_privacy_url",
	    isset($this->data['singleapp_privacy_url'])?$this->data['singleapp_privacy_url']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_time_format",
	    isset($this->data['singleapp_time_format'])?$this->data['singleapp_time_format']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_time_interval",
	    isset($this->data['singleapp_time_interval'])?$this->data['singleapp_time_interval']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_reg_email",
	    isset($this->data['singleapp_reg_email'])?$this->data['singleapp_reg_email']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_reg_phone",
	    isset($this->data['singleapp_reg_phone'])?$this->data['singleapp_reg_phone']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_reg_verification_enabled",
	    isset($this->data['singleapp_reg_verification_enabled'])?$this->data['singleapp_reg_verification_enabled']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_remove_phone_prefix",
	    isset($this->data['singleapp_remove_phone_prefix'])?$this->data['singleapp_remove_phone_prefix']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_menu_type",
	    isset($this->data['singleapp_menu_type'])?$this->data['singleapp_menu_type']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_disabled_default_menu",
	    isset($this->data['singleapp_disabled_default_menu'])?$this->data['singleapp_disabled_default_menu']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_tracking_theme",
	    isset($this->data['singleapp_tracking_theme'])?$this->data['singleapp_tracking_theme']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_tracking_interval",
	    isset($this->data['singleapp_tracking_interval'])?$this->data['singleapp_tracking_interval']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_distance_results",
	    isset($this->data['singleapp_distance_results'])?$this->data['singleapp_distance_results']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_order_processing",
	    isset($this->data['singleapp_order_processing'])?json_encode($this->data['singleapp_order_processing']):''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_order_completed",
	    isset($this->data['singleapp_order_completed'])?json_encode($this->data['singleapp_order_completed']):''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_order_cancelled",
	    isset($this->data['singleapp_order_cancelled'])?json_encode($this->data['singleapp_order_cancelled']):''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_prefix",
	    isset($this->data['singleapp_prefix'])?$this->data['singleapp_prefix']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_location_accuracy",
	    isset($this->data['singleapp_location_accuracy'])?$this->data['singleapp_location_accuracy']:''
	    ,$merchant_id);
	    	    	    
	    Yii::app()->functions->updateOption("singleapp_cart_theme",
	    isset($this->data['singleapp_cart_theme'])?$this->data['singleapp_cart_theme']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_cart_auto_address",
	    isset($this->data['singleapp_cart_auto_address'])?$this->data['singleapp_cart_auto_address']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_floating_category",
	    isset($this->data['singleapp_floating_category'])?$this->data['singleapp_floating_category']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_custom_pages_position",
	    isset($this->data['singleapp_custom_pages_position'])?$this->data['singleapp_custom_pages_position']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_rtl",
	    isset($this->data['singleapp_rtl'])?$this->data['singleapp_rtl']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_enabled_addon_desc",
	    isset($this->data['singleapp_enabled_addon_desc'])?$this->data['singleapp_enabled_addon_desc']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_confirm_future_order",
	    isset($this->data['singleapp_confirm_future_order'])?$this->data['singleapp_confirm_future_order']:''
	    ,$merchant_id);
	    
		$this->jsonResponse();
	}
	
	public function actionsave_socialsettings()
	{
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
	    if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
	    
	    $this->code = 1;		
	    $this->msg = $this->t("Successful");	    	   
	    
		Yii::app()->functions->updateOption("singleapp_enabled_fblogin",
	    isset($this->data['singleapp_enabled_fblogin'])?$this->data['singleapp_enabled_fblogin']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_enabled_google",
	    isset($this->data['singleapp_enabled_google'])?$this->data['singleapp_enabled_google']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_fb_save_pic",
	    isset($this->data['singleapp_fb_save_pic'])?$this->data['singleapp_fb_save_pic']:''
	    ,$merchant_id);
	    
		$this->jsonResponse();
	}
	
	public function actionsave_sandroidsettings()
	{	
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
	    if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
	    
	    $this->code = 1;		
	    $this->msg = $this->t("Successful");	
	    
	    Yii::app()->functions->updateOption("singleapp_enabled_pushpic",
	    isset($this->data['singleapp_enabled_pushpic'])?$this->data['singleapp_enabled_pushpic']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_push_icon",
	    isset($this->data['singleapp_push_icon'])?$this->data['singleapp_push_icon']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_push_picture",
	    isset($this->data['singleapp_push_picture'])?$this->data['singleapp_push_picture']:''
	    ,$merchant_id);
	    
		$this->jsonResponse();
	}
	
	public function actionsave_fcm()
	{
		
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';		
	    if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
	    
	    $this->code = 1;		
	    $this->msg = $this->t("Successful");	
	    	    
		Yii::app()->functions->updateOption("singleapp_android_push_key",
	    isset($this->data['singleapp_android_push_key'])?$this->data['singleapp_android_push_key']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_fcm_provider",
	    isset($this->data['singleapp_fcm_provider'])?$this->data['singleapp_fcm_provider']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_services_account_json",
	    isset($this->data['singleapp_services_account_json'])?$this->data['singleapp_services_account_json']:''
	    ,$merchant_id);
	    
		$this->jsonResponse();
	}
	
    public function actionUploadCertificate()
	{
		require_once('Uploader.php');
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload/certificate";
        $valid_extensions = array('pem'); 
        if(!file_exists($path_to_upload)) {	
           if (!@mkdir($path_to_upload,0777)){           	               	
           	    $this->msg=$this->t("Error has occured cannot create upload directory");
                $this->jsonResponse();
           }		    
	    }
	    
        $Upload = new FileUpload('uploadfile');
        $ext = $Upload->getExtension();         
        $Upload->newFileName = $_GET['uploadfile']."_".time().".".$ext;
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions);                
        if (!$result) {                    	
            $this->msg=$Upload->getErrorMsg();            
        } else {         	
        	$this->code=1;
        	$this->msg=$this->t("upload done");        	        
			$this->details=array(			
			  'file_url'=>Yii::app()->getBaseUrl(true)."/upload/".$Upload->newFileName,
			  'file_name'=>$Upload->newFileName
			);
        }
        $this->jsonResponse();
	}
	
	public function actionsave_settingios()
	{		
	    $this->code = 1;		
	    $this->msg = $this->t("Successful");	
	    
	    Yii::app()->functions->updateOption("singleapp_ios_push_mode",
	    isset($this->data['singleapp_ios_push_mode'])?$this->data['singleapp_ios_push_mode']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_ios_passphrase",
	    isset($this->data['singleapp_ios_passphrase'])?$this->data['singleapp_ios_passphrase']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_ios_push_dev_cer",
	    isset($this->data['singleapp_ios_push_dev_cer'])?$this->data['singleapp_ios_push_dev_cer']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_ios_push_prod_cer",
	    isset($this->data['singleapp_ios_push_prod_cer'])?$this->data['singleapp_ios_push_prod_cer']:''
	    ,$merchant_id);
	    
		$this->jsonResponse();
	}
	
	public function actionregisteredDeviceList()
	{
				
		$cols = array(
		  'client_id','first_name','device_uiid','device_platform',
		  'device_id','push_enabled','date_created','client_id'
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
			  b.first_name LIKE ".q("$search_fields%")." OR
			  b.last_name LIKE ".q("$search_fields%")." OR
			  a.device_platform LIKE ".q("$search_fields%")." OR
			  a.device_uiid LIKE ".q("$search_fields%")." OR
			  a.device_id LIKE ".q("$search_fields%")." 
			)
			";
		}
						
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
		b.first_name,
		b.last_name,
		b.last_login
		
		FROM
		{{singleapp_device_reg}} a
		left join {{client}} b
		on
		a.client_id = b.client_id
		
		WHERE 1				
		$and
		$order
		$limit
		";						
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){									
			$total_records=0;									
			if($resc = Yii::app()->db->createCommand("SELECT FOUND_ROWS() as total_records")->queryRow()){						
				$total_records=$resc['total_records'];
			}			
			$feed_data['draw']=$this->data['draw'];
			$feed_data['recordsTotal']=$total_records;
			$feed_data['recordsFiltered']=$total_records;
					
			
			foreach ($res as $val) {
				
			    $date_created = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
			    $last_login = FunctionsV3::prettyDate($val['last_login'])." ".FunctionsV3::prettyTime($val['date_created']);
			    
			    $link=Yii::app()->createUrl('singlemerchant/index/send_push',array(
			      'id'=>$val['client_id']
			    ));			    
			    
			    $enabled_push =  $val['push_enabled']==1?st("Yes"):st("No");			    
			    $subscribe_topic =  $val['subscribe_topic']==1?st("Yes"):st("No");
			    
			    $actions='<a href="javascript:;" data-id="'.$val['id'].'" class="send_push" >'.st("send push").'</a>';
			    
			    $info= "<a href=\"javascript:;\" class=\"show_device_id\" data-id=\"".$val['device_id']."\" data-toggle=\"modal\" data-target=\"#deviceDetails\" >";
		  	    $info.= '<div class="concat-text">'.$val['device_id']."</div>" ;
			    $info.="</a> ";				
			    
				$feed_data['aaData'][]=array(
				  $val['client_id'],				  
				  $val['first_name']." ".$val['last_name'],
				  SingleAppClass::t( strtolower($val['device_platform']) ),				  
				  "<p class=\"concat-text\">".$val['device_uiid']."</p>",
				  $info,	 
				  SingleAppClass::prettyBadge($enabled_push),
				  SingleAppClass::prettyBadge($subscribe_topic),
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
	
	public function actionpushLogs()
	{
		
		$p = new CHtmlPurifier();
		
		$cols = array(
		  'id','push_type','client_name','device_platform',
		  'device_id',
		  'push_title','push_message','date_created','date_process'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and=" AND registration_type ='fcm' ";		
		
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{singleapp_mobile_push_logs}} a
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
							    
			    $t = SingleAppClass::prettyBadge( $val['status'] );												
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
				  SingleAppClass::t($val['push_type']),
				  $val['client_name'],
				  SingleAppClass::t( strtolower($val['device_platform']) ),
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
	    	if ($data = SingleAppClass::getDeviceByID($id)){	    			    		
	    		$params = array(
	    		  'client_id'=>$data['client_id'],
	    		  'client_name'=>$data['first_name']." ".$data['last_name'],
	    		  'device_platform'=>$data['device_platform'],
	    		  'device_id'=>$data['device_id'],
	    		  'push_title'=>$this->data['push_title'],
	    		  'push_message'=>$this->data['push_message'],
	    		  'push_type'=>"campaign",
	    		  'date_created'=>FunctionsV3::dateNow(),
	    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
	    		  'registration_type'=>SingleAppClass::registrationType(),	
	    		  'merchant_id'=>$data['merchant_id']
	    		);	    	    		
	    		if(Yii::app()->db->createCommand()->insert("{{singleapp_mobile_push_logs}}",$params)){	
	    			$this->code = 1;
	    			$this->msg = $this->t("Request has been sent");	    	
	    			FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("singlemerchant/cron/processpush"));	    			
	    		} else $this->msg = $this->t("failed cannot insert records. please try again later");
	    	} else $this->msg = $this->t("customer id not found");
	    } else $this->msg = $this->t("Invalid customer id");
	    $this->jsonResponse();
	}
	
	public function actionmerchantList()
	{
		$cols = array(
		  'merchant_id','restaurant_name','single_app_keys','status','merchant_id'
		);
		
        $resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and=" AND status IN ('active')";		
		
		$search_fields = isset($this->data['search_fields'])?trim($this->data['search_fields']):'';
		if(!empty($search_fields)){
			$and.=" AND 
			(
			  restaurant_name LIKE ".q("$search_fields%")."	OR
			  merchant_id = ".q((integer)$search_fields)."
			)
			";
		}	
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{merchant}} a
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
				$settings=Yii::app()->createUrl('singlemerchant/index/merchant_settings',array(
			      'merchant_id'=>$val['merchant_id']
			    ));	
				$action = '<a class="btn '.APP_BTN.'" href="'.$settings.'">'.SingleAppClass::t("settings").'</a>';
				$feed_data['aaData'][]=array(				  
				   $val['merchant_id'],
				   $val['restaurant_name'],
				   $val['single_app_keys'],
				   SingleAppClass::prettyBadge($val['status']),
				   $action
				);
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();	
	}
	
	public function actionbannerList()
	{
		$this->otableNodata();	
	}
	
	public function actionsave_banner()
	{
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		if($merchant_id>0){
						
			Yii::app()->functions->updateOption("singleapp_banner",
		    isset($this->data['banner'])?json_encode($this->data['banner']):''
		    ,$merchant_id);
		    
		    Yii::app()->functions->updateOption("singleapp_enabled_banner",
	        isset($this->data['singleapp_enabled_banner'])?$this->data['singleapp_enabled_banner']:''
	        ,$merchant_id);
	        
	        Yii::app()->functions->updateOption("singleapp_homebanner_interval",
	        isset($this->data['singleapp_homebanner_interval'])?$this->data['singleapp_homebanner_interval']:''
	        ,$merchant_id);
	        
	        Yii::app()->functions->updateOption("singleapp_homebanner_auto_scroll",
	        isset($this->data['singleapp_homebanner_auto_scroll'])?$this->data['singleapp_homebanner_auto_scroll']:''
	        ,$merchant_id);
		    
		    $this->code = 1;		
	        $this->msg = $this->t("Successful");	 
	    
		} else $this->msg = $this->t("invalid merchant id");
		$this->jsonResponse();
	}
	
	public function actionpushBroadcast()
	{
		$cols = array(
		  'broadcast_id','push_title','push_message','restaurant_name',
		  'device_platform','date_created','date_modified'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and="
		AND fcm_version=1
		";	
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
		IFNULL(b.restaurant_name,'') as restaurant_name
		
		FROM
		{{singleapp_broadcast}} a
		left join {{merchant}} b
		on
		a.merchant_list = b.merchant_id
		
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
				$res = Yii::app()->request->stripSlashes($res);						
				$details_link=Yii::app()->createUrl('singlemerchant/index/broadcast_details',array(
			      'id'=>$val['broadcast_id']
			    ));	
			    
				$action = '<a href="'.$details_link.'">'.SingleAppClass::t("View details").'</a>';
												
				$date_modified = FunctionsV3::prettyDate( $val['date_modified'] )." ".FunctionsV3::prettyTime( $val['date_modified'] );			
				
				 $t = SingleAppClass::prettyBadge( $val['status'] );				   	  	 
				 $t.= "<a href=\"javascript:;\" class=\"show_error_details\" data-id=\"".$val['broadcast_id']."\" data-toggle=\"modal\" data-target=\"#errorDetails\" >
				 <i class=\"pl-2 fas fa-question-circle\"></i></a> ";
				
		   	  	 $t .= "<div></div>";
		   	  	 $t.= FunctionsV3::prettyDate( $val['date_created'] )." ".FunctionsV3::prettyTime( $val['date_created'] );	
				
				$feed_data['aaData'][]=array(				  
				   $val['broadcast_id'],
				   $val['push_title'],
				   $val['push_message'],		
				   $val['restaurant_name'],		   
				   st($val['device_platform']),
				   $t,
				   $date_modified
				);
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();	
	}
	
	public function actionsaveBroadcast()
	{
		$push_title = isset($this->data['push_title'])?$this->data['push_title']:'';
		$push_message = isset($this->data['push_message'])?$this->data['push_message']:'';
		$device_platform = isset($this->data['device_platform'])?$this->data['device_platform']:'';
		
		if(empty($push_title)){
			$this->msg = $this->t("Push title is invalid");
			$this->jsonResponse();
		}
		if(empty($push_message)){
			$this->msg = $this->t("Push message is invalid");
			$this->jsonResponse();
		}
		
		$params = array(
		 'push_title'=>trim($push_title),
		 'push_message'=>trim($push_message),
		 'device_platform'=>trim($device_platform),
		 'date_created'=>FunctionsV3::dateNow(),
		 'ip_address'=>$_SERVER['REMOTE_ADDR'],
		 'merchant_list'=>isset($this->data['merchant'])?json_encode($this->data['merchant']):''
		);								
		if(Yii::app()->db->createCommand()->insert("{{singleapp_broadcast}}",$params)){	
			$this->code = 1;
			$this->msg = $this->t("Broadcast saved");
		} else $this->msg = $this->t("failed cannot insert records. please try again later");
				
		$this->jsonResponse();
	}
	
	public function actionpushLogsDetails()
	{
		
		$broadcast_id = isset($_GET['broadcast_id'])?$_GET['broadcast_id']:'';
		
		if(empty($broadcast_id)){
			$broadcast_id='-1';
		}
		if($broadcast_id<=0){
			$broadcast_id='-1';
		}
		
		$cols = array(
		  'id','push_type','client_name','device_platform','push_title','push_message','date_created'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$and=" AND registration_type ='fcm' ";		
		
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{singleapp_mobile_push_logs}} a
		WHERE 
		broadcast_id=".FunctionsV3::q($broadcast_id)."
				
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
				$date_created.="<br/>";
				$date_created.=SingleAppClass::prettyBadge($val['status']);
			    			    
				$feed_data['aaData'][]=array(
				  $val['id'],
				  SingleAppClass::t($val['push_type']),
				  $val['client_name'],
				  SingleAppClass::t( strtolower($val['device_platform']) ),
				  $val['push_title'],
				  "<p class=\"concat-text\">".$val['push_message']."..."."</p>",				  
				  $date_created,				  
				);
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionpageList()
	{
		$cols = array(
		  'page_id','title',
		  'content','sequence',
		  'date_created','page_id'
		);
		
		$resp = DatatablesWrapper::format($cols,$this->data);			
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:0;	
		if($merchant_id<=0){
			$this->otableNodata();
		}
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{singleapp_pages}} a
		WHERE 
		merchant_id = ".FunctionsV3::q($merchant_id)."			
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
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);					
			    			    			    			   
			    $page_id = $val['page_id'];
				$action ='<a href="javascript:;" class="edit_page btn btn-info" data-page_id="'.$page_id.'" ><i class="fas fa-edit" aria-hidden="true"></i></a>';
				$action.='<a href="javascript:;" class="delete_page btn btn-danger" data-page_id="'.$page_id.'" ><i class="fas fa-trash" aria-hidden="true"></i></a>';
			    
			    $status=SingleAppClass::prettyBadge($val['status']);
			    
			    $val['content'] = stripslashes(strip_tags($val['content']));			   
			    $use_html='';
			    $content =  "<p class=\"concat-text\">".$val['content']."..."."</p>";
			    if ($val['use_html']==2){
			    	$use_html = '<i class="fa fa-check"></i>';			    	
			    }
			    
			    
				$feed_data['aaData'][]=array(
				  $val['page_id'],
				  stripslashes($val['title']),
				  $content,				  
				  $val['icon'],
				  $use_html,
				  $val['sequence'],  
				  $status.'<br/>'.$date_created,
				  $action
				);
			}			
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
		
	}

	public function actionsavePages()
	{		
		$validator=new Validator();
		$req=array( 
		  'title'=>SingleAppClass::t("title is required"),
		  'content'=>SingleAppClass::t("content is required"),
		  'merchant_id'=>SingleAppClass::t("merchant id is required"),
		);
		$validator->required($req,$this->data);
		if ( $validator->validate()){
			$params=array(
			  'title'=>$this->data['title'],
			  'content'=>$this->data['content'],
			  'icon'=>isset($this->data['icon'])?$this->data['icon']:'',
			  'sequence'=>isset($this->data['sequence'])?$this->data['sequence']:0,
			  'status'=>$this->data['status'],
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			  'use_html'=>isset($this->data['use_html'])?$this->data['use_html']:1,
			  'merchant_id'=>isset($this->data['merchant_id'])?$this->data['merchant_id']:0,
			);
			
			if ( Yii::app()->functions->multipleField()==2){				
				if ( $fields=FunctionsV3::getLanguageList(false)){
					foreach ($fields as $f_val){
						$params["lang_title_$f_val"] = isset($this->data["lang_title_$f_val"])?$this->data["lang_title_$f_val"]:'';
						$params["lang_content_$f_val"] = isset($this->data["lang_content_$f_val"])?$this->data["lang_content_$f_val"]:'';
					}
				}				
			}
			
			$DbExt=new DbExt; 
						
			if(!is_numeric($params['use_html'])){
				unset($params['use_html']);
			}					
			if(!is_numeric($params['sequence'])){
				$params['sequence']=0;
			}			
			if(!is_numeric($params['merchant_id'])){
				$params['merchant_id']=0;
			}
								
			if (isset($this->data['id'])){
				unset($params['date_created']);
				$params['date_modified']=FunctionsV3::dateNow();
				if ( $DbExt->updateData("{{singleapp_pages}}",$params,'page_id',$this->data['id'])){
					$this->code = 1;
					$this->msg = SingleAppClass::t("successfully updated");
					$this->details='';
				} else $this->msg = SingleAppClass::t("Failed cannot saved records");
			} else {				
				if ( $DbExt->insertData("{{singleapp_pages}}",$params)){
					$this->details=Yii::app()->createUrl('singlemerchant/index/pages_new',array(
					  'id'=>Yii::app()->db->getLastInsertID(),
					  'merchant_id'=>$params['merchant_id']
					));
					$this->code = 1;
					$this->msg = SingleAppClass::t("Successful");
				} else $this->msg = SingleAppClass::t("Failed cannot saved records");
			}
			
		} else $this->msg= $validator->getErrorAsHTML();
		$this->jsonResponse();
	}	
	
	public function actiondeletePages()
	{
		$this->data = $_GET;
		$page_id = isset($this->data['page_id'])?$this->data['page_id']:0;
		if($page_id>0){
			$DbExt=new DbExt; 
			$DbExt->qry("DELETE FROM
			{{singleapp_pages}}
			WHERE
			page_id=".FunctionsV3::q($this->data['page_id'])."
			");
			$this->code=1;
			$this->msg="OK";
			$this->details='';		
		} else $this->msg = $this->t("invalid page id");
		$this->jsonResponse();
	}
	
    public function actiondatable_localize()
    {
    	header('Content-type: application/json');
    	$data = array(
    	  'decimal'=>'',
    	  'emptyTable'=> $this->t('No data available in table'),
    	  'info'=> st('Showing [start] to [end] of [total] entries',array(
    	    '[start]'=>"_START_",
    	    '[end]'=>"_END_",
    	    '[total]'=>"_TOTAL_",
    	  )),
    	  'infoEmpty'=> $this->t("Showing 0 to 0 of 0 entries"),
    	  'infoFiltered'=>$this->t("(filtered from [max] total entries)",array(
    	    '[max]'=>"_MAX_"
    	  )),
    	  'infoPostFix'=>'',
    	  'thousands'=>',',
    	  'lengthMenu'=> $this->t("Show [menu] entries",array(
    	    '[menu]'=>"_MENU_"
    	  )),
    	  'loadingRecords'=>$this->t('Loading...'),
    	  'processing'=>$this->t("Processing..."),
    	  'search'=>$this->t("Search:"),
    	  'zeroRecords'=>$this->t("No matching records found"),
    	  'paginate' =>array(
    	    'first'=>$this->t("First"),
    	    'last'=>$this->t("Last"),
    	    'next'=>$this->t("Next"),
    	    'previous'=>$this->t("Previous")
    	  ),
    	  'aria'=>array(
    	    'sortAscending'=>$this->t(": activate to sort column ascending"),
    	    'sortDescending'=>$this->t(": activate to sort column descending")
    	  )
    	);    	
    	echo json_encode($data);
    }
	
    public function actionsavesettings_startup()
    {
    	 $merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		
		if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
		
		$this->code = 1;		
	    $this->msg = $this->t("Successful");

        Yii::app()->functions->updateOption("singleapp_enabled_select_language",
	    isset($this->data['singleapp_enabled_select_language'])?$this->data['singleapp_enabled_select_language']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_startup",
	    isset($this->data['singleapp_startup'])?$this->data['singleapp_startup']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_startup_banner",
	    isset($this->data['singleapp_startup_banner'])?json_encode($this->data['singleapp_startup_banner']):''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_startup_auto_scroll",
	    isset($this->data['singleapp_startup_auto_scroll'])?$this->data['singleapp_startup_auto_scroll']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_startup_interval",
	    isset($this->data['singleapp_startup_interval'])?$this->data['singleapp_startup_interval']:''
	    ,$merchant_id);
	    
    	$this->jsonResponse();
    }

    public function actionuploadFile()
    {
    	require_once('SimpleUploader.php');
    	if ( !Yii::app()->functions->isAdminLogin()){
			$this->msg = st("Session has expired");
			$this->jsonResponse();
		}
		
		$path_to_upload  = FunctionsV3::uploadPath();
		
		$valid_extensions = FunctionsV3::validImageExtension();        
		$Upload = new FileUpload('uploadfile');
		$ext = $Upload->getExtension();
		$time=time();
        $filename = $Upload->getFileNameWithoutExt();       
        $new_filename =  "$time-$filename.$ext";
        $Upload->newFileName = $new_filename;
        $Upload->sizeLimit = FunctionsV3::imageLimitSize();
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions); 
	    if (!$result) {
	    	 $this->msg=$Upload->getErrorMsg();
	    } else {
	    	
	    	 $fields = ''; $remove_class='remove_picture';
	    	 if($_GET['id']=="multi_upload"){
	    	 	$remove_class='multi_remove_picture';
	    	 	$fields = '<input type="hidden" name="'.$_GET['field_name'].'[]" value="'.$new_filename.'" > ';
	    	 }
	    	
	    	 $class_name = "preview_".$_GET['id'];
	    	 $html_preview='	    	 
	    	 <div class="card '.$class_name.'" style="width: 10rem;">
				<img class="img-thumbnail" src="'.websiteUrl()."/upload/$new_filename".'" >
				
				<div class="card-body">
				  <a href="javascript:;" data-id="'.$_GET['id'].'" 
				  data-fieldname="'.$_GET['field_name'].'" 
				  class="card-link '.$remove_class.'">'.st("Remove Image").'</a>
				</div>
				
				'.$fields.'
				
			 </div>			 
			 <div class="height10"></div>
	    	 ';
	    	 	    	 
	    	 
	    	 $this->code = 1;
	    	 $this->msg="OK";
	    	 $this->details=array(
	    	   'file_name'=>$new_filename,
	    	   'file_url'=>websiteUrl()."/upload/$new_filename",
	    	   'html_preview'=>$html_preview
	    	 );
	    }
	    $this->jsonResponse();
    }    
    
    public function actionsave_page()
    {    	
    	$merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		
		if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
	    
    	$db = new DbExt();
    	$params = array(
    	  'merchant_id'=>$merchant_id,
    	  'title'=>isset($this->data['title'])?$this->data['title']:'',
    	  'content'=>isset($this->data['content'])?$this->data['content']:'',
    	  'use_html'=>isset($this->data['use_html'])?$this->data['use_html']:0,
    	  'icon'=>isset($this->data['icon'])?$this->data['icon']:'',
    	  'sequence'=>isset($this->data['sequence'])?$this->data['sequence']:'0',
    	  'status'=>isset($this->data['status'])?$this->data['status']:'',
    	  'date_created'=>FunctionsV3::dateNow(),
    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
    	);
    	
    	if($params['sequence']<=0){    		
    		if($max_count = SingleAppClass::getMaxPage($merchant_id)){    			
    			$params['sequence']=$max_count;
    		}     		
    	}
    	    	
    	
    	if(Yii::app()->functions->multipleField()){       		
    		if ( $fields=FunctionsV3::getLanguageList(false)){
    			foreach ($fields as $lang) {    				
    				$params["title_$lang"] = isset($this->data["title_$lang"])?$this->data["title_$lang"]:'';
    				$params["content_$lang"] = isset($this->data["content_$lang"])?$this->data["content_$lang"]:'';
    			}
    		}    		
    	}
    	    	   
    	$page_id = isset($this->data['page_id'])?$this->data['page_id']:'';
    	if($page_id>0){    		
    		unset($params['date_created']);
    		$params['date_modified']=FunctionsV3::dateNow();    		
    		if($db->updateData("{{singleapp_pages}}",$params,'page_id',$page_id)){
    			$this->code = 1;
    			$this->msg = st("Page Succesfully updated");    			
    		} else $this->msg = st("Failed cannot update records");
    	} else {
    		if (!SingleAppClass::getPageByTitle($merchant_id,$params['title'])){
    			$db->insertData("{{singleapp_pages}}",$params);
    			$this->code = 1;
    			$this->msg = st("Page Succesfully added");
    		} else $this->msg = st("Page title already exist");
    	}
    	
    	$this->jsonResponse();
    }    
    
    public function actionget_page()
    {    	
    	$page_id = isset($this->data['page_id'])?$this->data['page_id']:'';    
    	if($page_id>=1){
    		if ($res=SingleAppClass::getPageByID($page_id)){   
    			
    			$lang=array();
    			if(Yii::app()->functions->multipleField()){
    				$lang = LanguageTable::getLangList();
    			}
    			 			
    			$this->code = 1;
    			$this->msg = "ok";
    			$this->details = array(
    			 'lang'=>$lang,
    			 'data'=>$res
    			);
    			    			
    		} else $this->msg = st("Records not found");
    	} else $this->msg = st("Invalid page id");
    	$this->jsonResponse();
    }    
    
    public function actionsave_contactus()
    {
    	
    	 $merchant_id = isset($this->data['merchant_id'])?$this->data['merchant_id']:'';
		
		if($merchant_id<=0){
	    	$this->msg = $this->t("Invalid merchant id");
	    	$this->jsonResponse();
	    }
		
		$this->code = 1;		
	    $this->msg = $this->t("Setting saved");

        Yii::app()->functions->updateOption("singleapp_contact_email",
	    isset($this->data['singleapp_contact_email'])?$this->data['singleapp_contact_email']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_contact_tpl",
	    isset($this->data['singleapp_contact_tpl'])?$this->data['singleapp_contact_tpl']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_contact_subject",
	    isset($this->data['singleapp_contact_subject'])?$this->data['singleapp_contact_subject']:''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_contactus_fields",
	    isset($this->data['singleapp_contactus_fields'])?json_encode($this->data['singleapp_contactus_fields']):''
	    ,$merchant_id);
	    
	    Yii::app()->functions->updateOption("singleapp_contactus_enabled",
	    isset($this->data['singleapp_contactus_enabled'])?$this->data['singleapp_contactus_enabled']:''
	    ,$merchant_id);
	        	
    	$this->jsonResponse();
    }
    
    public function actionuploadFile2()
    {
    	require_once('SimpleUploader.php');
    	if ( !Yii::app()->functions->isAdminLogin()){
			$this->msg = t("Session has expired");
			$this->jsonResponse();
		}
		
		$path_to_upload  = FunctionsV3::uploadPath();
		
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
	    	 $input.= st("File [file]",array(
	    	   '[file]'=>$new_filename
	    	 ));
	    		 
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
    
    public function actionsave_broadcast()
    {    	    	
    	
    	$merchant_id = isset($this->data['merchant_list'])?(integer)$this->data['merchant_list']:0;
    	if($merchant_id<0){
    		$this->msg = st("Merchant is required");
    		$this->jsonResponse();
    	}
    	
    	$params = array(
    	   'push_title'=>isset($this->data['push_title'])?$this->data['push_title']:'',
    	   'push_message'=>isset($this->data['push_message'])?$this->data['push_message']:'',
    	   'device_platform'=>"/topics/".CHANNEL_TOPIC_MERCHANT.$merchant_id,
    	   'date_created'=>FunctionsV3::dateNow(),
    	   'ip_address'=>$_SERVER['REMOTE_ADDR'],
    	   'fcm_version'=>isset($this->data['fcm_version'])?$this->data['fcm_version']:'',
    	   'merchant_list'=>$merchant_id
    	);    	
    	    	
    	if(Yii::app()->db->createCommand()->insert("{{singleapp_broadcast}}",$params)){	
    		$this->code = 1;
    		$this->msg = st("Successful");    		    		    		
    		FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("singlemerchant/cron/processbroadcast"));    		    		
    	} else $this->msg = st("Failed cannot insert records");
    	$this->jsonResponse();
    }
    
    public function actionerrorDetails()
    {
    	$this->data = $_GET;        	
    	$stmt = '';
    	$id = isset($this->data['details_id'])?(integer)$this->data['details_id']:'';
    	$current_page = isset($this->data['current_page'])?$this->data['current_page']:'';
    	if($id>0){
    		switch ($current_page) {
    			case "push_logs":
    				$stmt = "
    				SELECT json_response as fcm_response
    				FROM {{singleapp_mobile_push_logs}}
    				WHERE id=".q($id)."
    				LIMIT 0,1
    				";
    				break;
    		
    			case "push_broadcast":	    			
    			   $stmt = "
    				SELECT fcm_response
    				FROM {{singleapp_broadcast}}
    				WHERE broadcast_id=".q($id)."
    				LIMIT 0,1
    				";
    			    break;
    			    
    			default:
    				break;
    		}    		    		    		       		
    		if(!empty($stmt)){
    			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){    				    				
    				$this->code = 1; $this->msg = !empty($res['fcm_response'])?$res['fcm_response']:st("None");    				
    			} else $this->msg = st("Record not found");
    		} else $this->msg = st("Invalid table");
    	} else $this->msg = st("Invalid details id");
    	$this->jsonResponse();
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
    	AND single_app_keys <>''
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
    
    public function actionpush_broadcast_old()
	{
		$aColumns = array(
		  'broadcast_id','push_title','push_message','merchant_list','device_platform','date_created','broadcast_id'
		);

		$resp = DatatablesWrapper::format($aColumns,$this->data);		
    	$where = $resp['where'];
		$order = $resp['order'];
		$limit = $resp['limit'];
			
		$and=' AND fcm_version=0';		
						
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*
		FROM
		{{singleapp_broadcast}} a
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
						
				$details_link=Yii::app()->createUrl('singlemerchant/index/broadcast_details',array(
			      'id'=>$val['broadcast_id']
			    ));	
			    
				$action = '<a href="'.$details_link.'">'.SingleAppClass::t("View details").'</a>';
								
				$date_created='';
				$date_created.= SingleAppClass::prettyBadge($val['status']);
				$date_created.='<br/>';
				$date_created.= FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
				
				$merchant_names ='';
				$merchant_list = !empty($val['merchant_list'])?json_decode($val['merchant_list'],true):false;
				if(is_array($merchant_list) && count($merchant_list)>=1){
					$merchant_names= SingleAppClass::getMerchantNames($merchant_list);
				}
				
				$feed_data['aaData'][]=array(				  
				   $val['broadcast_id'],
				   $val['push_title'],
				   $val['push_message'],
				   $merchant_names,
				   st($val['device_platform']),
				   $date_created,
				   $action
				);
			}			
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();	
	}

    public function actionorder_trigger()
    {    	
    	$this->data = $_POST; 
    	$feed_data = array();
    	
    	$cols = array(
		  'trigger_id','trigger_type',
		  'order_id','order_status','remarks','date_created'
		);			
				
		$resp = DatatablesWrapper::format($cols,$this->data);		
		$where = '';
		$order = $resp['order'];
		$limit = $resp['limit'];
		
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.* FROM
		{{singleapp_order_trigger}} a
		WHERE 1
		$where
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
				   	  	 
						$t = SingleAppClass::prettyBadge( $val['status'] );
						$t .= "<div></div>";
						$t.= FunctionsV3::prettyDate( $val[$cols_val] )." ".FunctionsV3::prettyTime( $val[$cols_val] );
						$cols_data[]=$t;
					  
				   	  } elseif ( $key_cols==1){
				   	  	$cols_data[]=st($val[$cols_val]);
				   	  } elseif ( $key_cols==3){
				   	  	$cols_data[]=st($val[$cols_val]);	
				   	  } else $cols_data[]=$val[$cols_val];
				   }			
				}
				$datas[]=$cols_data;
			}			
			$feed_data['data']=$datas;						
			$this->otableOutput($feed_data);	
		} else $this->otableNodata();
    }	
    
    public function actiongetNotification()
    {
    	$error  = array();
    	try {
    		SingleappNotificationWrapper::checkRequiredFile();
    	} catch (Exception $e) {
    		$error[] = array(
    		  'message'=>$e->getMessage(),
    		  'link'=>''
    		);
    	}
    	
    	try {
    		/*2.3*/
    	    SingleappNotificationWrapper::checkFields('singleapp_cart',array(
    	      'cart_subtotal'=>"",
    	      'remove_tip'=>""
    	    ));    	        	        	    
    	    SingleappNotificationWrapper::checkFields('singleapp_broadcast',array(
    	      'fcm_response'=>"",
    	      'fcm_version'=>"",
    	      'date_modified'=>"",
    	    ));    	        	
    	    
    	    SingleappNotificationWrapper::checkTable(array(
    	      'singleapp_device_reg','singleapp_order_trigger'
    	    ));
    	       	    
    	    /*end 2.3*/
    	    
    	    /*2.4*/
    	    SingleappNotificationWrapper::checkFields('singleapp_cart',array(
    	      'merchant_id'=>"",    	      
    	    ));    	        	
    	    /*END 2.4*/
    	    
    	} catch (Exception $e) {
    		$error[] = array(
    		  'message'=>$e->getMessage(),
    		  'link'=>Yii::app()->createUrl(APP_FOLDER."/update")
    		);
    	}
    	
    	/*GET DEVICE TO MIGRATE*/
    	try {
    		SingleappNotificationWrapper::getCountDeviceMigrate();
    	} catch (Exception $e) {
    		$error[] = array(
    		  'message'=>$e->getMessage(),
    		  'link'=>Yii::app()->createUrl(APP_FOLDER."/index/migrate_device")
    		);
    	}
    	
    	if(is_array($error) && count($error)>=1){    	    		
    		$html='';
    		foreach ($error as $error_val) {    		
    			$link = $error_val['link'];
    			if(empty($link)){
    				$link = 'javascript:;';
    			}
    			$html.='<a class="dropdown-item" href="'.$link.'">';
    			$html.=$error_val['message'];
    			$html.='</a>';
    		}
    		
    		$html.='<a class="dropdown-item text-success view_all" href="'.Yii::app()->createUrl(APP_FOLDER."/index/notification").'">';
    		$html.=st("View all");
    		$html.='</a>';
    		    		
    		$this->code = 1;    	
    		$this->msg = "ERROR";
    		$this->details = array(
    		  'count'=>count($error),
    		  'error'=>$html    		  
    		);
    	} else $this->msg = st("No new notification");
    	$this->jsonResponse();
    }
		
} /*end class*/
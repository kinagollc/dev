<?php
class ApiController extends CController
{	
	public $data;
	public $code=2;
	public $msg='';
	public $details='';		
	public $device_uiid;
	public $merchant_token;
	
	public function __construct()
	{			
		$website_timezone=getOptionA('website_timezone');
	    if (!empty($website_timezone)){
	 	   Yii::app()->timeZone=$website_timezone;
	    }	
	    	    	    				
		$this->data=$_POST;
		$this->getPOSTData();
										
		$lang=Yii::app()->language;		
	}
		
	public function beforeAction($action)
	{		
		if(isset($_GET['debug'])){ 
	       dump("<h3>Request</h3>");
       	   dump($this->data);
        }                       
        
        /*CHECK API HASH KEY*/
        $api_key = isset($_REQUEST['api_key'])?trim($_REQUEST['api_key']):'';        
        $api_has_key = trim(getOptionA('merchantappv2_api_hash_key'));
                
        if($api_has_key!=$api_key){
        	$this->msg = translate("invalid api hash key");
        	$this->output();
        	return false;
        }	
                
        return true;
	}
	
	private function output()
    {
    	
       if (!isset($this->data['debug'])){    		
       	  header('Access-Control-Allow-Origin: *');       	  
          header('Content-type: application/javascript;charset=utf-8');          
       } 
       
	   $resp=array(
	     'code'=>$this->code,
	     'msg'=>$this->msg,
	     'details'=>$this->details,	     	     
	     'get'=>$_GET,
	     'post'=>$_POST
	   );		   
	   if (isset($this->data['debug'])){
	   	   dump($resp);
	   }
	   
	   if (!isset($_GET['callback'])){
  	   	   $_GET['callback']='';
	   }    
	   
	   if (isset($_GET['jsonp']) && $_GET['jsonp']==TRUE){	   		   	   
	   	   echo $_GET['callback'] . '('.CJSON::encode($resp).')';
	   } else echo CJSON::encode($resp);
	   Yii::app()->end();
    }	
    
    private function getGETData()
	{
		$this->device_uiid = isset($this->data['device_uiid'])?$this->data['device_uiid']:'';
        $this->merchant_token = isset($this->data['merchant_token'])?$this->data['merchant_token']:'';
	}
	
	private function getPOSTData()
	{
		$this->device_uiid = isset($_POST['device_uiid'])?$_POST['device_uiid']:'';
        $this->merchant_token = isset($_POST['merchant_token'])?$_POST['merchant_token']:'';
	}
	
    public function actionIndex(){
		echo "API IS WORKING";
	}	
	
	private function appSettings()
	{
		$set_language = getOptionA('set_language');		
		$data = array();
		$data['dashboard_menu'] = MerchantWrapper::dashboardMenu();
		$data['services'] = Yii::app()->functions->Services();		
		$data['two_flavor_options'] = MerchantWrapper::twoFlavorOptions();
		$data['distance_unit'] = Yii::app()->functions->distanceOption();
		$data['tip_list'] = MerchantWrapper::tipList();		
		$data['status_list']=(array)statusList();
		$data['voucher_type']=MerchantWrapper::voucherType();
		$data['time_list_ready']=MerchantWrapper::timeListReady();
		$data['reason_decline']=MerchantWrapper::reasonDeclineList();
		$data['order_status_list']=OrderWrapper::orderStatusList();		
		$data['options'] = MerchantWrapper::getOptionsSettings();		
		$data['map_provider'] = MapsWrapperTemp::getMapProvider();
		$data['default_map_location'] = array(
		  'lat'=>1,
		  'lng'=>1
		);		
		$data['timezone_list'] = Yii::app()->functions->timezoneList();	
		$data['set_language']= !empty($set_language)?$set_language:'en';
		$data['month_list']=Yii::app()->functions->ccExpirationMonth();
		$data['year_list']=Yii::app()->functions->ccExpirationYear();
		$data['driver_addon']=FunctionsV3::hasModuleAddon("driver")==true?true:false;
						
		$data['bluetooth'] = array(
		  'interface_type'=>Bluetooth::interfaceList(),
		  'pape_width'=>Bluetooth::paperWidthList(),
		  'char_set'=>Bluetooth::characterCodeList()
		);
		
		$data['location_message']=MerchantWrapper::AcessFineLocationMessage();
		
		$dict = Merchantappv2Module::$global_dict;		
		$data['dictionary'] = $dict;				
		return $data;
	}
	
	public function actiongetsettings()
	{		
		$this->code = 1;
		$this->msg = translate("OK");		
				
		$data = self::appSettings();
				
		try {
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);				
			$this->code = 1; $this->msg = "OK";
			
			if( strlen($resp['pin'])>2 ){			   
			   $data['next_action']="enter_pin";	
			} else $data['next_action']="already_login";
					
			$data['merchant_info'] = $resp;			
						
			
			$device_resp = MerchantUserWrapper::GetDeviceInformation($this->device_uiid);
			$device_resp['topic_new_order'] = str_replace("/topics/","",CHANNEL_TOPIC).$resp['merchant_id'];
			$device_resp['topic_alert'] = str_replace("/topics/","",CHANNEL_TOPIC_ALERT).$resp['merchant_id'];
			$data['device_info']=$device_resp;	
			$close_store = 	getOption($resp['merchant_id'],'merchant_close_store');
			$close_store = $close_store=="yes"?1:0;
			$data['options']['merchant_close_store']=$close_store;
						
			$this->details = $data;			
											
		} catch (Exception $e) {			
			$data['next_action']="show_login";
			$this->details = $data;	
		}			
					
		$this->output();
	}
	
	public function actionreget_getsettings()
	{	
		$data = self::appSettings();
		
		$this->code = 1; $this->msg = "OK";
		$data['next_action']="reget_getsettings";	
		$this->details = $data;				
		$this->output();
	}
	
	public function actionregisterDevice()
	{
		$data = array();
		try {
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);				
			$params = array(
			 'id'=>$resp['id'],
			 'merchant_id'=>$resp['merchant_id'],
			 'user_type'=>$resp['user_type'],
			 'device_uiid'=>$this->device_uiid,
			 'device_id'=>isset($this->data['device_id'])?trim($this->data['device_id']):'',
			 'device_platform'=>isset($this->data['device_platform'])?trim($this->data['device_platform']):'',
			 'code_version'=>isset($this->data['code_version'])?trim($this->data['code_version']):'',
			 'status'=>"active",
			 'date_created'=>FunctionsV3::dateNow(),
			 'date_modified'=>FunctionsV3::dateNow(),
			 'last_login'=>FunctionsV3::dateNow(),
			 'ip_address'=>$_SERVER['REMOTE_ADDR']
			);		
			$device_resp = MerchantUserWrapper::RegisteredDevice($this->device_uiid,$params);
		} catch (Exception $e) {			
			//
		}		
		$this->code = 1; $this->msg = "OK";	
		$this->details = array(
		  'next_action'=>"silent"
		);
		$this->output();
	}
	
	public function actionappLogin()
	{		
		$username = isset($this->data['username'])?trim($this->data['username']):'';
		$password = isset($this->data['password'])?trim($this->data['password']):'';
		
		try {
			
			$resp = MerchantUserWrapper::login($username,$password);
			$this->code = 1; $this->msg = "OK";
			$data = array();
			$data['merchant_info'] = $resp;			
			$data['next_action']="show_homepage";			
			
			$params = array(
			 'id'=>$resp['id'],
			 'merchant_id'=>$resp['merchant_id'],
			 'user_type'=>$resp['user_type'],
			 'device_uiid'=>$this->device_uiid,
			 'device_id'=>isset($this->data['device_id'])?trim($this->data['device_id']):'',
			 'device_platform'=>isset($this->data['device_platform'])?trim($this->data['device_platform']):'',
			 'code_version'=>isset($this->data['code_version'])?trim($this->data['code_version']):'',
			 'status'=>"active",
			 'date_created'=>FunctionsV3::dateNow(),
			 'date_modified'=>FunctionsV3::dateNow(),
			 'last_login'=>FunctionsV3::dateNow(),
			 'ip_address'=>$_SERVER['REMOTE_ADDR']
			);			
			$device_resp = MerchantUserWrapper::RegisteredDevice($this->device_uiid,$params);
			$device_resp['topic_new_order'] = str_replace("/topics/","",CHANNEL_TOPIC).$resp['merchant_id'];
			$device_resp['topic_alert'] = str_replace("/topics/","",CHANNEL_TOPIC_ALERT).$resp['merchant_id'];
			$data['device_info']=$device_resp;
			
			$close_store = 	getOption($resp['merchant_id'],'merchant_close_store');
            $close_store = $close_store=="yes"?1:0;
            $data['merchant_info']['merchant_close_store']=$close_store;

			$this->details = $data;
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}	
	
	public function actionforgotPassword()
	{		
		try {
			$email_address = isset($this->data['email_address'])?$this->data['email_address']:'';
			$resp = MerchantUserWrapper::getUserByEmail($email_address);			
			if($resp['status']!="active"){
				$this->msg = translate("Your account is no longer active");
				$this->output();
			}
				
			$code = yii::app()->functions->generateRandomKey(3);
			$merchant_id = $resp['merchant_id'];
			
			
			$lang=Yii::app()->language;		
			$tpl  = CustomerNotification::getNotificationTemplate('merchant_forgot_password',$lang,'email',false);
			
			MerchantUserWrapper::udapteLostPasswordCode($resp['user_type'],$resp['merchant_id'],$resp['id'],$code);
			
			$to = $resp['email_address'];
			
			$email_content = isset($tpl['email_content'])?$tpl['email_content']:'';
			$email_subject = isset($tpl['email_subject'])?$tpl['email_subject']:'';	
			
			$data = array(
			  'code'=>$code,
			  'restaurant_name'=>isset($resp['restaurant_name'])?$resp['restaurant_name']:'',
			  'sitename'=>getOptionA('website_title'),
		      'siteurl'=>websiteUrl()
			);
			
			$email_subject = FunctionsV3::replaceTags($email_subject,$data);			
			$email_content = FunctionsV3::replaceTags($email_content,$data);			
			
			sendEmail($to,'',$email_subject, $email_content );
			
			$this->code =1; $this->msg = translate("We have sent verification code in your email.");
			$this->details = array(
			   'next_action'=>"show_forgot_change_pass",
			   'email_address'=>$to
			);			
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
	
	public function actionChangeForgotPassword()
	{		
		if(trim($this->data['new_password'])!= trim($this->data['confirm_password']) ){
			$this->msg = translate("Confirm password does not match");
			$this->output();
		}
		
		$code = isset($this->data['code'])?$this->data['code']:'';
		$email_address = isset($this->data['email_address'])?$this->data['email_address']:'';
		$new_password = isset($this->data['new_password'])?trim($this->data['new_password']):'';		
		$next_action = isset($this->data['next_action'])?trim($this->data['next_action']):'back_to_login';
		try {			
			$resp = MerchantUserWrapper::getUserByEmailCode($email_address,$code);					
			if($resp['lost_password_code']==$code){
				$params = array(
				  'password'=>md5($new_password),
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);
				
				MerchantUserWrapper::changePassword($resp['id'],$resp['user_type'],$params,$resp['password']);
		    
			    $this->code = 1;
			    $this->msg = translate("Change password succesful");
			    $this->details = array(
			      'next_action'=>$next_action
			    );
		    
			} else $this->msg = translate("Invalid verification code");
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actiongetMerchantinformation()
	{
		try {
			$token = MerchantUserWrapper::validateToken($this->merchant_token);
			$merchant_id = (integer)$token['merchant_id'];
			$info = MerchantWrapper::getMerchantInformation($merchant_id);
			$this->code = 1; $this->msg = "ok";
			
			$cuisine = !empty($info['cuisine'])?json_decode($info['cuisine'],true):'';
			$info['cuisine']=$cuisine;
			
			$info['merchant_information'] = getOption($merchant_id,'merchant_information');			
			
			$this->details = array(
			  'next_action'=>"set_merchant_info",
			  'data'=>$info
			);			
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
		
	public function actiongetCuisineList()
	{				
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        
		if ($resp = FoodItemWrapper::getAllCuisine($page,$page_limit)){			
			
			$data = array();
			foreach ($resp as $val) {
				$data[] = array(
				  'id'=>$val['cuisine_id'],
				  'name'=>$val['cuisine_name']
				);
			}
								
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else $this->msg = translate("No results");
		}
		$this->output();
	}
	
	public function actionupdateMerchantInfo()
	{
		try {
			$token = MerchantUserWrapper::validateToken($this->merchant_token);
			$merchant_id = (integer)$token['merchant_id'];					
			$params = array(
			  'restaurant_slug'=>isset($this->data['restaurant_slug'])?$this->data['restaurant_slug']:'',
			  'restaurant_name'=>isset($this->data['restaurant_name'])?$this->data['restaurant_name']:'',
			  'restaurant_phone'=>isset($this->data['restaurant_phone'])?$this->data['restaurant_phone']:'',
			  'contact_name'=>isset($this->data['contact_name'])?$this->data['contact_name']:'',
			  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',
			  'contact_email'=>isset($this->data['contact_email'])?$this->data['contact_email']:'',
			  'street'=>isset($this->data['street'])?$this->data['street']:'',
			  'city'=>isset($this->data['city'])?$this->data['city']:'',
			  'post_code'=>isset($this->data['post_code'])?$this->data['post_code']:'',
			  'state'=>isset($this->data['state'])?$this->data['state']:'',
			  'service'=>isset($this->data['service'])?$this->data['service']:'',
			  'cuisine'=>isset($this->data['cuisine'])?json_encode($this->data['cuisine']):'',
			  'is_ready'=>isset($this->data['is_ready'])?2:1,
			  'latitude'=>isset($this->data['latitude'])?$this->data['latitude']:'',
			  'lontitude'=>isset($this->data['lontitude'])?$this->data['lontitude']:'',
			);
			if (!empty($this->data['restaurant_slug'])){
				$params['restaurant_slug']=FunctionsV3::verifyMerchantSlug(
				  Yii::app()->functions->seo_friendly_url($this->data['restaurant_slug']),
				  $merchant_id
				);
			} else {	
			    $params['restaurant_slug']=Yii::app()->functions->createSlug($this->data['restaurant_name']);
			}
			$params['date_modified'] = FunctionsV3::dateNow();
			$params['ip_address'] = $_SERVER['REMOTE_ADDR'];
								
			MerchantWrapper::udapteMerchantInfo($params,$merchant_id);
			$this->code = 1;
			$this->msg = translate("Successful");
			
			Yii::app()->functions->updateOption('merchant_latitude',
				isset($this->data['latitude'])?$this->data['latitude']:''
				,$merchant_id);
				
			Yii::app()->functions->updateOption('merchant_longtitude',
				isset($this->data['lontitude'])?$this->data['lontitude']:''
				,$merchant_id);	
				
			Yii::app()->functions->updateOption('merchant_information',
				isset($this->data['merchant_information'])?$this->data['merchant_information']:''
				,$merchant_id);		
			
			try {				
				$cuisine = isset($this->data['cuisine'])?$this->data['cuisine']:'';
				MerchantWrapper::insertCuisine($merchant_id, (array) $cuisine );
			} catch (Exception $e) {
				//
			}
					
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
	
	public function actiongetMerchantSettings()
	{
		$data = array();		
		
		try {
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);			
			$merchant_id = (integer)$resp['merchant_id'];			
			$settings = MerchantWrapper::getMerchantSettings($merchant_id, MerchantWrapper::merchantSettingsOption() );
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>"set_form_options",
			  'data'=>$settings,
			  'site_url'=>Yii::app()->getBaseUrl(true)
			);							
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
	
	public function actionupdateMerchantSettings()
	{		
		try {
			
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);			
			$merchant_id = (integer)$resp['merchant_id'];			
			
			$options = MerchantWrapper::merchantSettingsOption();
			foreach ($options as $val) {				
				Yii::app()->functions->updateOption($val,
				isset($this->data[$val])?$this->data[$val]:''
				,$merchant_id);
			}
						
			$merchant_photo = isset($this->data['merchant_photo'])?$this->data['merchant_photo']:'';
			$params = array(
			   'delivery_charges'=>is_numeric($this->data['merchant_delivery_charges'])?$this->data['merchant_delivery_charges']:0,
	    	  'minimum_order'=>is_numeric($this->data['merchant_minimum_order'])?$this->data['merchant_minimum_order']:0,
	    	  'delivery_minimum_order'=>is_numeric($this->data['merchant_minimum_order'])?$this->data['merchant_minimum_order']:0,
	    	  'delivery_maximum_order'=>is_numeric($this->data['merchant_maximum_order'])?$this->data['merchant_maximum_order']:0,
	    	  'pickup_minimum_order'=>is_numeric($this->data['merchant_minimum_order_pickup'])?$this->data['merchant_minimum_order_pickup']:0,
	    	  'pickup_maximum_order'=>is_numeric($this->data['merchant_maximum_order_pickup'])?$this->data['merchant_maximum_order_pickup']:0,	    	  
	    	  'delivery_estimation'=>isset($this->data['merchant_delivery_estimation'])?$this->data['merchant_delivery_estimation']:'',	    	  
	    	  'distance_unit'=>isset($this->data['merchant_distance_type'])?$this->data['merchant_distance_type']:'',
	    	  'delivery_distance_covered'=>isset($this->data['merchant_delivery_miles'])?(float)$this->data['merchant_delivery_miles']:0,
	    	  'close_store'=>isset($this->data['merchant_close_store'])?1:0
			);
						
			if(!empty($merchant_photo)){
				$params['logo']=$merchant_photo;
			}
			
			$up = Yii::app()->db->createCommand()->update("{{merchant}}",$params,
		  	    'merchant_id=:merchant_id',
			  	    array(
			  	      ':merchant_id'=>$merchant_id
			  	    )
		  	    );
			
			$this->code = 1;
			$this->msg = translate("Settings saved");
			$this->details = array(
			  'next_action'=>'set_close_store',
			  'close_store'=>$params['close_store']
			);						
			
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
	
	public function actiongetCategoryList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllCategory($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {
				$add_even='add';
				if($x % 2){
					$add_even='odd';
				}								
				$data[] = array(
				  'id'=>$val['cat_id'],
				  'name'=>$val['category_name'],
				  'description'=>$val['category_description'],
				  'thumbnail'=>FoodItemWrapper::getImage($val['photo']),
				  'status'=>t($val['status']),
				  'date_created'=>OrderWrapper::prettyDateTime($val['date_created'])
				);
				$x++;
			}
						
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	private function validateToken()
	{
		try {
            $token = MerchantUserWrapper::validateToken($this->merchant_token);			
		    $merchant_id = (integer)$token['merchant_id'];			
		    return $merchant_id;
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
			$this->output();
		}		
	}
	
	public function actionCategoryDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteCategory($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionCategoryGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("category","cat_id=:cat_id",array(
			 ':cat_id'=>$id
			));
						
			$data = array(
			  'cat_id'=>$resp['cat_id'],
			  'category_name'=>$resp['category_name'],
			  'category_description'=>$resp['category_description'],
			  'photo'=>$resp['photo'],
			  'thumbnail'=>FoodItemWrapper::getImage($resp['photo']),
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"category_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddCategory()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		$params = array(
		   'merchant_id'=>$merchant_id,
		  'category_name'=>isset($this->data['category_name'])?$this->data['category_name']:'',
		  'category_description'=>isset($this->data['category_description'])?$this->data['category_description']:'',
		  'status'=>isset($this->data['status'])?$this->data['status']:'',
		  'photo'=>isset($this->data['photo'])?$this->data['photo']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		try {
								
			FoodItemWrapper::insertCategory($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetAddonList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllAddon($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {
				$add_even='add';
				if($x % 2){
					$add_even='odd';
				}				
				$data[] = array(
				  'id'=>$val['subcat_id'],
				  'name'=>$val['subcategory_name'],
				  'description'=>$val['subcategory_description'],
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>OrderWrapper::prettyDateTime($val['date_created'])
				);				
				$x++;
			}
								
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionAddAddon()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;			
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'subcategory_name'=>isset($this->data['subcategory_name'])?$this->data['subcategory_name']:'',
		  'subcategory_description'=>isset($this->data['subcategory_description'])?$this->data['subcategory_description']:'',
		  'status'=>isset($this->data['status'])?$this->data['status']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		try {
			
			FoodItemWrapper::insertAddonCategory($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddonDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteAddonCategory($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddonGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("subcategory","subcat_id=:subcat_id",array(
			 ':subcat_id'=>$id
			));
						
			$data = array(
			  'subcat_id'=>$resp['subcat_id'],
			  'subcategory_name'=>$resp['subcategory_name'],
			  'subcategory_description'=>$resp['subcategory_description'],
			  'thumbnail'=>FoodItemWrapper::getImage(''),
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"addon_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetAddonItemList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllAddonItem($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['sub_item_id'],
				  'name'=>$val['sub_item_name'],
				  'description'=>$val['item_description'],
				  'thumbnail'=>FoodItemWrapper::getImage($val['photo']),
				  'price'=>FunctionsV3::prettyPrice($val['price'])
				);				
				$x++;
			}
										
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}	
	
	public function actionAddonCategoryList()
	{				
		$merchant_id = $this->validateToken();
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        
		if ($resp = FoodItemWrapper::getAllAddon($merchant_id,$page,$page_limit)){		
			
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['subcat_id'],
				  'name'=>$val['subcategory_name']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else $this->msg = translate("No results");
		}
		$this->output();
	}
	
	public function actionAddAddonItem()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;			
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'sub_item_name'=>isset($this->data['sub_item_name'])?$this->data['sub_item_name']:'',
		  'item_description'=>isset($this->data['item_description'])?$this->data['item_description']:'',
		  'price'=>isset($this->data['price'])?(float)$this->data['price']:0,
		  'category'=>isset($this->data['category'])? json_encode($this->data['category']) :'',
		  'status'=>isset($this->data['status'])?$this->data['status']:'',
		  'photo'=>isset($this->data['photo'])?$this->data['photo']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);			
		
		if(empty($params['category'])){
			$this->msg = translate("Category is required");
			$this->output();
		}
		
		try {
									
			FoodItemWrapper::insertAddonItem($merchant_id,$params,
			json_decode($params['category'],true)
			,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddonItemDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteSubItem($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
    public function actionAddonItemGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("subcategory_item","sub_item_id=:sub_item_id",array(
			 ':sub_item_id'=>$id
			));					
			$data = array(
			  'sub_item_id'=>$resp['sub_item_id'],
			  'sub_item_name'=>$resp['sub_item_name'],
			  'item_description'=>$resp['item_description'],
			  'price'=>normalPrettyPrice($resp['price']),
			  'thumbnail'=>FoodItemWrapper::getImage($resp['photo']),
			  'photo'=>$resp['photo'],			  
			  'category'=>!empty($resp['category'])?json_decode($resp['category'],true):'',
			  'status'=>$resp['status'],
			);					
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"addon_item_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
	
    public function actionIngredientsList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllingredients($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['ingredients_id'],
				  'name'=>$val['ingredients_name'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}	
		
	public function actionAddIngredients()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'ingredients_name'=>isset($this->data['ingredients_name'])?$this->data['ingredients_name']:'',		  
		  'status'=>isset($this->data['status'])?$this->data['status']:'',		  
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);			
		try {
						
			FoodItemWrapper::insertIngredients($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionIngredientsGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("ingredients","ingredients_id=:ingredients_id",array(
			 ':ingredients_id'=>$id
			));
						
			$data = array(
			  'ingredients_id'=>$resp['ingredients_id'],
			  'ingredients_name'=>$resp['ingredients_name'],			  
			  'thumbnail'=>FoodItemWrapper::getImage(''),
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"ingredients_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
		
	public function actionIngredientsDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteIngredients($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
    public function actionCookingList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllCooking($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['cook_id'],
				  'name'=>$val['cooking_name'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}		
	
	public function actionAddcooking()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'cooking_name'=>isset($this->data['cooking_name'])?$this->data['cooking_name']:'',		  
		  'status'=>isset($this->data['status'])?$this->data['status']:'',		  
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);			
		try {
						
			FoodItemWrapper::insertCookingRef($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionCookingDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteCookingRef($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
		
	public function actionCookingGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("cooking_ref","cook_id=:cook_id",array(
			 ':cook_id'=>$id
			));
						
			$data = array(
			  'cook_id'=>$resp['cook_id'],
			  'cooking_name'=>$resp['cooking_name'],			  
			  'thumbnail'=>FoodItemWrapper::getImage(''),
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"cooking_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
    public function actionItemList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllitem($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0; 
			foreach ($resp as $val) {				
				
				$prices=array();
				if ( $price_list = json_decode($val['price'],true) ){
					foreach ($price_list as $size_id=>$price) {
						if($size_id>0){							
							try {
								$size_name = FoodItemWrapper::getData("size",'size_id=:size_id 
								AND merchant_id=:merchant_id',array(
								 ':size_id'=>$size_id,
								 ':merchant_id'=>$merchant_id
								));
								$prices[]= translate("[size_name] [price]",array(
								  '[size_name]'=>$size_name['size_name'],
								  '[price]'=>FunctionsV3::prettyPrice($price)
								));
							} catch (Exception $e) {
							   $prices[]=FunctionsV3::prettyPrice($price);	
							}														
						} else $prices[]=FunctionsV3::prettyPrice($price);
					}
				}
								
				$item_status = 'available';
				if($val['not_available']==2){
					$item_status = 'disabled';
				}			
				
				if(isset($val['count_out_of_stock'])){
					if($val['count_out_of_stock']>=1){
						$item_status = 'out_of_stock';
					}				
				}
				
				
				$data[] = array(
				  'id'=>$val['item_id'],
				  'name'=>$val['item_name'],
				  'description'=>$val['item_description'],
				  'thumbnail'=>FoodItemWrapper::getImage($val['photo']),
				  'prices'=>$prices,
				  'item_status'=>$item_status
				);				
				$x++;
			}
					
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}	
	
	public function actionAddItem()
	{
		
		$merchant_id = $this->validateToken();
		
		if (!Yii::app()->functions->validateMerchantCanPost($merchant_id) ){
    		if (isset($this->data['id']) && is_numeric($this->data['id'])){				
    		} else {	
    		   $this->msg=Yii::t("default","Sorry but you reach the limit of adding food item. Please upgrade your membership");
    		   $this->output();
    		}
    	}	    
		
		$price=array();
    	if (isset($this->data['price']) && count($this->data['price'])>=1){
    		foreach ($this->data['price'] as $key=>$val) {
    			if (!empty($val)){
    			   $price[$this->data['size'][$key]]=$val;
    			}
    		}	    		
    	}	  

    	$item_id = isset($this->data['id'])?(integer)$this->data['id']:0;
    	
		$params = array(
		    'merchant_id'=>(integer)$merchant_id,
		    'item_name'=>isset($this->data['item_name'])?$this->data['item_name']:'',
		    'item_description'=>isset($this->data['item_description'])?$this->data['item_description']:'',
		    'not_available'=>isset($this->data['not_available'])?(integer)$this->data['not_available']:1,
		    'status'=>isset($this->data['status'])?$this->data['status']:'',
		    'category'=>isset($this->data['category'])?json_encode($this->data['category']):"",
		    'price'=>isset($price)?json_encode($price):'',
		    'cooking_ref'=>isset($this->data['cooking_ref'])?json_encode($this->data['cooking_ref']):"",
		    'discount'=>isset($this->data['discount'])?$this->data['discount']:"",
		    'photo'=>isset($this->data['photo'])?$this->data['photo']:"",
		    'ingredients'=>isset($this->data['ingredients'])?json_encode($this->data['ingredients']):"",
		    'spicydish'=>isset($this->data['spicydish'])?(integer)$this->data['spicydish']:1,
		    'two_flavors'=>isset($this->data['two_flavors'])?$this->data['two_flavors']:'0',				   
		    'dish'=>isset($this->data['dish'])?json_encode($this->data['dish']):'',
		    'non_taxable'=>isset($this->data['non_taxable'])?(integer)$this->data['non_taxable']:1,		    		    
		    'packaging_fee'=>isset($this->data['packaging_fee'])?(float)$this->data['packaging_fee']:0,
		    'packaging_incremental'=>isset($this->data['packaging_incremental'])?(integer)$this->data['packaging_incremental']:0,
		    'date_created'=>FunctionsV3::dateNow(),
		    'date_modified'=>FunctionsV3::dateNow(),
		    'ip_address'=>$_SERVER['REMOTE_ADDR'],
		    'multi_option'=>isset($this->data['multi_option'])?json_encode($this->data['multi_option']):"",
			'multi_option_value'=>isset($this->data['multi_option_value'])?json_encode($this->data['multi_option_value']):"",
			'require_addon'=>isset($this->data['require_addon'])?json_encode($this->data['require_addon']):"",
			'addon_item'=>isset($this->data['sub_item_id'])?json_encode($this->data['sub_item_id']):"",
			'two_flavors_position'=>isset($this->data['two_flavors_position'])?json_encode($this->data['two_flavors_position']):"",
		);		
		
		
	    if(empty($params['category'])){
			$this->msg = translate("Category is required");
			$this->output();
		}
		
		try {			
						
			$resp = FoodItemWrapper::insertItem($merchant_id,$params,$item_id);		
			$this->code = 1;
			$this->msg = $item_id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
	
	public function actionItemGetByID()
	{	
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(integer)$this->data['id']:0; $size_list = array();
		
		
		$size_list[] = array(
		 'name'=>"",
		 'value'=>0
		);
		if( $resp = FoodItemWrapper::getSizes($merchant_id)){
			foreach ($resp as $val) {				
				$size_list[] = array(
				  'name'=>$val['size_name'],
				  'value'=>$val['size_id'],
				);
			}
		} 
		
		if($resp = FoodItemWrapper::getItem($merchant_id,$id)){			
			$resp = Yii::app()->request->stripSlashes($resp);			
			$data = array(
			  'item_id'=>$resp['item_id'],
			  'item_name'=>$resp['item_name'],			  			  
			  'item_description'=>$resp['item_description'],	
			  'not_available'=>$resp['not_available'],
			  'status'=>$resp['status'],			  
			  'category'=>!empty($resp['category'])?json_decode($resp['category'],true):'',
			  'price'=>!empty($resp['price'])?json_decode($resp['price'],true):'',
			  'cooking_ref'=>!empty($resp['cooking_ref'])?json_decode($resp['cooking_ref'],true):'',
			  'discount'=>$resp['discount'],			  
			  'photo'=>$resp['photo'],
			  'thumbnail'=>FoodItemWrapper::getImage($resp['photo']),
			  'ingredients'=>!empty($resp['ingredients'])?json_decode($resp['ingredients'],true):'',
			  'spicydish'=>!empty($resp['spicydish'])?json_decode($resp['spicydish'],true):'',
			  'two_flavors'=>$resp['two_flavors'],
			  'two_flavors_position'=>$resp['two_flavors_position'],
			  'require_addon'=>!empty($resp['require_addon'])?json_decode($resp['require_addon'],true):'',
			  'dish'=>!empty($resp['dish'])?json_decode($resp['dish'],true):'',
			  'non_taxable'=>$resp['non_taxable'],
			  'gallery_photo'=>!empty($resp['gallery_photo'])?json_decode($resp['gallery_photo'],true):'',
			  'packaging_fee'=>$resp['packaging_fee']>0?normalPrettyPrice($resp['packaging_fee']):'',
			  'packaging_incremental'=>$resp['packaging_incremental'],
			  'addon_item'=>!empty($resp['addon_item'])?json_decode($resp['addon_item'],true):'',			  
			);							
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"item_form.html",
			  'data'=>$data,
			  'size'=>$size_list
			);						
		} else $this->msg = translate("Record not found");
		$this->output();
	}
	
	public function actionItemDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;		
		try {
						
			FoodItemWrapper::deleteItem($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
		
    public function actionCategoryList()
	{				
		$merchant_id = $this->validateToken();
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $field  = isset($this->data['field'])?trim($this->data['field']):'';        
        
		if ($resp = FoodItemWrapper::getListCategory($merchant_id,$page,$page_limit)){		
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['cat_id'],
				  'name'=>$val['category_name']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'field'=>$field
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->msg = translate("No results");
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'clear_list',				  
				);
			}		
		}
		$this->output();
	}
	
	public function actionCookingRefList()
	{				
		$merchant_id = $this->validateToken();
		$search_string='';
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $field  = isset($this->data['field'])?trim($this->data['field']):'';  
        
		if ($resp = FoodItemWrapper::getAllCooking($merchant_id,$page,$page_limit,
		$search_string,'a.cooking_name','ASC', false)){		
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['cook_id'],
				  'name'=>$val['cooking_name']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'field'=>$field
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->msg = translate("No results");
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'clear_list',				  
				);
			}		
		}
		$this->output();
	}
	
	public function actionIngredList()
	{				
		$merchant_id = $this->validateToken();
		$search_string='';
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $field  = isset($this->data['field'])?trim($this->data['field']):'';  
        
		if ($resp = FoodItemWrapper::getAllingredients($merchant_id,$page,$page_limit,$search_string,
		'a.ingredients_name','ASC',false
		)){		
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['ingredients_id'],
				  'name'=>$val['ingredients_name']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'field'=>$field
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->msg = translate("No results");
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'clear_list',				  
				);
			}		
		}
		$this->output();
	}
	
	
	public function actionDishList()
	{				
		$merchant_id = $this->validateToken();
		$search_string='';
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $field  = isset($this->data['field'])?trim($this->data['field']):'';  
        
		if ($resp = FoodItemWrapper::getAllDish($merchant_id,$page,$page_limit,$search_string)){		
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['dish_id'],
				  'name'=>$val['dish_name']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'field'=>$field
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->msg = translate("No results");
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'clear_list',				  
				);
			}		
		}
		$this->output();
	}
	
    public function actionSizeList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllSize($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['size_id'],
				  'name'=>$val['size_name'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}			

	public function actionSizeGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("size","size_id=:size_id",array(
			 ':size_id'=>$id
			));
						
			$data = array(
			  'size_id'=>$resp['size_id'],
			  'size_name'=>$resp['size_name'],			  
			  'thumbnail'=>FoodItemWrapper::getImage(''),
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"size_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddSize()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'size_name'=>isset($this->data['size_name'])?$this->data['size_name']:'',		  
		  'status'=>isset($this->data['status'])?$this->data['status']:'',		  
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);				
		try {
						
			FoodItemWrapper::insertSize($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionSizeDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteSize($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
		
	public function actiongetSizeList()
	{
		$merchant_id = $this->validateToken();
		$data[] = array(
		 'name'=>"",
		 'value'=>0
		);
		if( $resp = FoodItemWrapper::getSizes($merchant_id)){
			foreach ($resp as $val) {				
				$data[] = array(
				  'name'=>$val['size_name'],
				  'value'=>$val['size_id'],
				);
			}
		} 
						
		$this->code = 1; $this->msg = "OK";
		$this->details = array(
	       'next_action'=>'show_add_item_form',
	       'size'=>$data
	    );
		
		$this->output();
	}
	
    public function actionShippingList()
	{		
		$merchant_id = $this->validateToken();
		
		$shipping_enabled = getOption($merchant_id,'shipping_enabled');
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllShipping($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['id'],
				  'name'=>FunctionsV3::prettyPrice($val['distance_price']),
				  'description'=>translate("Distance [from] to [to] [unit]",array(
				    '[from]'=>normalPrettyPrice($val['distance_from']),
				    '[to]'=>normalPrettyPrice($val['distance_to']),
				    '[unit]'=>MapsWrapperTemp::prettyUnit($val['shipping_units'])
				  )),
				  'thumbnail'=>FoodItemWrapper::getImage('','distance.png'),				  
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'shipping_enabled'=>$shipping_enabled
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'shipping_enabled'=>$shipping_enabled,
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}		
		$this->output();
	}			

	public function actionAddShipping()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'distance_from'=>isset($this->data['distance_from'])?(float)$this->data['distance_from']:0,		  
		  'distance_to'=>isset($this->data['distance_to'])?(float)$this->data['distance_to']:0,		  
		  'distance_price'=>isset($this->data['distance_price'])?(float)$this->data['distance_price']:0,		  
		  'shipping_units'=>isset($this->data['shipping_units'])?$this->data['shipping_units']:0,		  
		);			
		try {
						
			FoodItemWrapper::insertShipping($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionShippingDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteShipping($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
	
	public function actionShippingGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("shipping_rate","id=:id",array(
			 ':id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['id'],
			  'distance_from'=>$resp['distance_from'],			  
			  'distance_to'=>$resp['distance_to'],
			  'shipping_units'=>$resp['shipping_units'],
			  'distance_price'=>normalPrettyPrice($resp['distance_price'])
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"shipping_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
	
	public function actionenabled_shipping()
	{
		$merchant_id = $this->validateToken();		
		$shipping_enabled = isset($this->data['shipping_enabled'])?(integer)$this->data['shipping_enabled']:0;		

		Yii::app()->functions->updateOption("shipping_enabled",$shipping_enabled,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
	public function actionenabled_min_table()
	{
		$merchant_id = $this->validateToken();		
		$enabled_min_table = isset($this->data['min_tables_enabled'])?(integer)$this->data['min_tables_enabled']:0;		
		
		Yii::app()->functions->updateOption("min_tables_enabled",$enabled_min_table,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
	public function actionenabled_category_sked()
	{
		$merchant_id = $this->validateToken();		
		$enabled = isset($this->data['enabled_category_sked'])?(integer)$this->data['enabled_category_sked']:0;				

		Yii::app()->functions->updateOption("enabled_category_sked",$enabled,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
    public function actionOffersList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllOffers($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0;
			foreach ($resp as $val) {		
				
				$list = '';
				if( $applicable_to = json_decode($val['applicable_to'],true)){
					foreach ($applicable_to as $applicable_val) {
						$list.= t($applicable_val).", ";
					}
					$list = substr($list,0,-1);
				}
						
				$data[] = array(
				  'id'=>$val['offers_id'],
				  'name'=>translate("[offer]%",array(
				   '[offer]'=>normalPrettyPrice($val['offer_percentage'])
				  )),
				  'description'=>$list,
				  'thumbnail'=>FoodItemWrapper::getImage(''),				  
				  'status'=>t($val['status']),
				  'date_created'=>translate("Valid from [from] to [to]",array(
				    '[from]'=>FunctionsV3::prettyDate($val['valid_from']),
				    '[to]'=>OrderWrapper::prettyDateTime($val['date_created']),
				  ))
				);				
				$x++;
			}
														
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}			

	public function actionTransactionList()
	{
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
		
		$data = array();
		$data[] = array(
		  'id'=>'delivery',
		  'name'=>t('delivery')
		);
		$data[] = array(
		  'id'=>'pickup',
		  'name'=>t('pickup')
		);
		$data[] = array(
		  'id'=>'dinein',
		  'name'=>t('dinein')
		);
				
		$this->code = 1;
		$this->msg = "OK";			
		$this->details = array(
		 'next_action'=>'display_selected',
		 'refresh'=>$refresh,
		 'data'=>$data
		);			
		$this->output();
	}
	
	public function actionDateList()
	{
		$page_limit=1;
		$data = array();
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
		
		$next_action = 'display_single_selected';
		$multiple = isset($this->data['multiple'])?(integer)$this->data['multiple']:0;		
		if($multiple>0){
			$next_action = 'display_selected';
		}	
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $start_count = 0; $end_count = 60;
        
        $field  = isset($this->data['field'])?trim($this->data['field']):'';        
        $field_prev = array('start_date','end_date');    
        if(in_array($field,$field_prev)){
        	$start_count = -60;
        	$end_count = 0;
        }               
        	
		for ($i = $start_count ; $i <= $end_count; $i++) {				
			$key=date("Y-m-d",strtotime("+$i day"));
			$pretty_date = FunctionsV3::prettyDate(date("D F d Y",strtotime("+$i day")));
			if($multiple>0){
			  $data[] = array(
				  'id'=>$key,
				  'value'=>$key,
				  'name'=>$pretty_date
			  );
			} else {
				$data[] = array(
				  'id'=>'date',
				  'value'=>$key,
				  'name'=>$pretty_date
				);
			}
		}
		
		if($page<=0){
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>$next_action,
			 'refresh'=>$refresh,
			 'data'=>$data
			);					
		} else {
			$this->code = 1;
			$this->details = array(
			  'next_action'=>'end_of_list',
			  'refresh'=>$refresh
			);
		}				
		$this->output();
	}
	
	public function actionAddOffers()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,	
		  'offer_percentage'=>isset($this->data['offer_percentage'])?(float)$this->data['offer_percentage']:0,
		  'offer_price'=>isset($this->data['offer_price'])?(float)$this->data['offer_price']:0,
		  'valid_from'=>isset($this->data['valid_from'])?$this->data['valid_from']:null,
		  'valid_to'=>isset($this->data['valid_to'])?$this->data['valid_to']:null,
		  'status'=>isset($this->data['status'])?$this->data['status']:'pending',
		  'applicable_to'=>isset($this->data['applicable_to'])?json_encode($this->data['applicable_to']):'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);			
		try {

			if($id>0){
			   unset($params['date_created']);
			}
			
			FoodItemWrapper::insertOffers($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionOffersDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteOffers($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
		
	public function actionOffersGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("offers","offers_id=:offers_id",array(
			 ':offers_id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['offers_id'],
			  'offer_percentage'=>normalPrettyPrice($resp['offer_percentage']),			  
			  'offer_price'=>normalPrettyPrice($resp['offer_price']),
			  'valid_from'=>$resp['valid_from'],
			  'valid_to'=>$resp['valid_to'],
			  'status'=>$resp['status'],
			  'applicable_to'=>(array)json_decode($resp['applicable_to'],true)
			);
			
						
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"offers_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
		
	public function actionVoucherList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllVouchers($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0;
			foreach ($resp as $val) {		
				
				$voucher_name = translate("[voucher_name] [discount]%",array(
				    '[voucher_name]'=>$val['voucher_name'],
				    '[discount]'=>normalPrettyPrice($val['amount']),
				  ));
				if($val['voucher_type']=="fixed amount"){
					$voucher_name = $val['voucher_name'];
				}			
									
				$data[] = array(
				  'id'=>$val['voucher_id'],
				  'name'=>$voucher_name,
				  'description'=>translate($val['voucher_type']),
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>translate("Expiration [expiration]",array(
				    '[expiration]'=>FunctionsV3::prettyDate($val['expiration'])
				  ))
				);				
				$x++;
			}
			
														
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}		
	
	public function actionVoucherGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("voucher_new","voucher_id=:voucher_id",array(
			 ':voucher_id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['voucher_id'],
			  'voucher_name'=>$resp['voucher_name'],
			  'voucher_type'=>$resp['voucher_type'],
			  'amount'=>normalPrettyPrice($resp['amount']),
			  'expiration'=>$resp['expiration'],
			  'used_once'=>$resp['used_once'],
			  'status'=>$resp['status'],
			);			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"voucher_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
		
	public function actionAddVoucher()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>(integer)$merchant_id,	
		  'voucher_name'=>isset($this->data['voucher_name'])?$this->data['voucher_name']:'',
		  'voucher_type'=>isset($this->data['voucher_type'])?$this->data['voucher_type']:'',
		  'amount'=>isset($this->data['amount'])?(float)$this->data['amount']:0,
		  'expiration'=>isset($this->data['expiration'])?$this->data['expiration']:'',
		  'used_once'=>isset($this->data['used_once'])?(integer)$this->data['used_once']:0,		  
		  'status'=>isset($this->data['status'])?$this->data['status']:'pending',
		  'date_created'=>FunctionsV3::dateNow(),
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		
		try {
			
			if($id>0){
			   unset($params['date_created']);
			}		
				
			FoodItemWrapper::insertVoucher($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionVoucherDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteVoucher($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
		
	public function actionMintableList()
	{		
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllMinTable($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0;
			foreach ($resp as $val) {		
									
				$data[] = array(
				  'id'=>$val['id'],	
				  'name' => FunctionsV3::prettyPrice($val['min_order']),
				  'description'=>translate("[distance_from] to [distance_to] [unit]",array(
				    '[distance_from]'=>normalPrettyPrice($val['distance_from']),
				    '[distance_to]'=>normalPrettyPrice($val['distance_to']),
				    '[unit]'=>MapsWrapperTemp::prettyUnit($val['shipping_units']),
				  )),
				  'thumbnail'=>FoodItemWrapper::getImage('','distance.png'),
				);				
				$x++;
			}
														
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'min_tables_enabled'=>getOption($merchant_id,'min_tables_enabled')
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'min_tables_enabled'=>getOption($merchant_id,'min_tables_enabled'),
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}				
	
	public function actionAddMintable()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>(integer)$merchant_id,	
		  'distance_from'=>isset($this->data['distance_from'])?(float)$this->data['distance_from']:0,
		  'distance_to'=>isset($this->data['distance_to'])?(float)$this->data['distance_to']:0,
		  'shipping_units'=>isset($this->data['shipping_units'])?$this->data['shipping_units']:'',		  
		  'min_order'=>isset($this->data['min_order'])?(float)$this->data['min_order']:'',
		);		
		
		try {
						
			FoodItemWrapper::insertMintable($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
			
	public function actionMintableDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			FoodItemWrapper::deleteMintable($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
		
	public function actionMintableGetByID()
	{		
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("minimum_table","id=:id",array(
			 ':id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['id'],
			  'distance_from'=>normalPrettyPrice($resp['distance_from']),
			  'distance_to'=>normalPrettyPrice($resp['distance_to']),
			  'shipping_units'=>$resp['shipping_units'],
			  'min_order'=>normalPrettyPrice($resp['min_order']),			  			  
			);			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"mintable_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}	
		
	public function actionSchedulerList()
	{		
		$merchant_id = $this->validateToken();
		$enabled_category_sked = getOption($merchant_id,'enabled_category_sked');
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::getAllSchedulerList($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0;
			foreach ($resp as $val) {											
				$data[] = array(
				  'id'=>$val['cat_id'],				  
				  'name'=>$val['category_name'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'schedule'=>array(
				      'monday'=>$val['monday'],
					  'tuesday'=>$val['tuesday'],
					  'wednesday'=>$val['wednesday'],
					  'thursday'=>$val['thursday'],
					  'friday'=>$val['friday'],
					  'saturday'=>$val['saturday'],
					  'sunday'=>$val['sunday'],
				  )
				);				
				$x++;
			}

			//dump($data);
								
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data,
			 'enabled_category_sked'=>$enabled_category_sked
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'enabled_category_sked'=>$enabled_category_sked,
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}				
	
	public function actionSchedulerGetByID()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("category","cat_id=:cat_id",array(
			 ':cat_id'=>$id
			));
						
			$data = array(
			  'cat_id'=>$resp['cat_id'],
			  'category_name'=>$resp['category_name'],			  
			  'monday'=>$resp['monday'],
			  'tuesday'=>$resp['tuesday'],
			  'wednesday'=>$resp['wednesday'],
			  'thursday'=>$resp['thursday'],
			  'friday'=>$resp['friday'],
			  'saturday'=>$resp['saturday'],
			  'sunday'=>$resp['sunday'],
			);
						
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"scheduler_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddScheduler()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		$params = array(
		  'monday'=>isset($this->data['monday'])?(integer)$this->data['monday']:0,
		  'tuesday'=>isset($this->data['tuesday'])?(integer)$this->data['tuesday']:0,
		  'wednesday'=>isset($this->data['wednesday'])?(integer)$this->data['wednesday']:0,
		  'thursday'=>isset($this->data['thursday'])?(integer)$this->data['thursday']:0,
		  'friday'=>isset($this->data['friday'])?(integer)$this->data['friday']:0,
		  'saturday'=>isset($this->data['saturday'])?(integer)$this->data['saturday']:0,
		  'sunday'=>isset($this->data['sunday'])?(integer)$this->data['sunday']:0,
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		try {
									
			FoodItemWrapper::insertCategoryScheduler($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetPaymentList()
	{		
		try {
            $token = MerchantUserWrapper::validateToken($this->merchant_token);			
            $user_access = json_decode($token['user_access'],true);
		    $merchant_id = (integer)$token['merchant_id'];	
		    
		    if(is_array($user_access) && count((array)$user_access)>=1){
		    	$user_access = array_map('strtolower', $user_access);
		    } else $user_access = false;
		    		    
		    
		    $list = FunctionsV3::PaymentOptionList(); $available = array();
		    $list_payment_enabled=Yii::app()->functions->getMerchantListOfPaymentGateway();		
		    		   
		    $master = MerchantWrapper::getMerchantSettings($merchant_id,array(
		     'merchant_switch_master_cod','merchant_switch_master_ocr','merchant_switch_master_pyr',
		     'merchant_switch_master_paypal_v2','merchant_switch_master_stp','merchant_switch_master_mercadopago',
		     'merchant_switch_master_ide','merchant_switch_master_payu','merchant_switch_master_pys',
		     'merchant_switch_master_bcy','merchant_switch_master_epy','merchant_switch_master_atz',
		     'merchant_switch_master_obd','merchant_switch_master_btr','merchant_switch_master_rzr',
		     'merchant_switch_master_vog'
		    ));
		    		    
		    $new_master = array();
		    if(is_array($master) && count($master)>=1){
		    	foreach ($master as $val_master) {
		    		if($val_master['option_value']!=1){
		    			$new_master[] = str_replace("merchant_switch_master_","",$val_master['option_name']);
		    		}
		    	}
		    	
		    	if(is_array($new_master) && count($new_master)>=1){
		    		$new_list_payment_enabled =  array();
			    	foreach ($new_master as $new_master_val) {
			    		if(in_array($new_master_val,(array)$list_payment_enabled)){
			    			$new_list_payment_enabled[] = $new_master_val;
			    		}
			    	}
			    	$list_payment_enabled = $new_list_payment_enabled;
		    	}
		    	
		    }
		    		    
		        
		    foreach ($list as $key=>$val) {
		    	if(in_array($key,$list_payment_enabled)){		 		    		
		    		$available[$key]=array(
		    		  'code'=>$key,
		    		  'name'=>$val,
		    		  'icon'=>FoodItemWrapper::getImage('',"payment/$key.png")
		    		);
		    		if($key=="obd"){
		    			$available['obd_receive']=array(
			    		  'code'=>"obd_receive",
			    		  'name'=>t("Receive Bank Deposit"),
			    		  'icon'=>FoodItemWrapper::getImage('',"payment/$key.png")
			    		);
		    		}
		    	}
            }		   
                                   
            if(FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){                  	            	
            	if(array_key_exists('pyr',$available)){            		
            		$available=array();
            		$available["pyr"]=array(
		    		  'code'=>"pyr",
		    		  'name'=>t("Pay On Delivery"),
		    		  'icon'=>FoodItemWrapper::getImage('',"payment/pyr.png")
		    		);
            	} else $available=array();
            } 

            $new_available = $available;
            if(is_array($user_access) && count($user_access)>=1){            	
            	$new_available = array();
            	foreach ($available as $val) {
            		if(in_array($val['code'],(array)$user_access)){            			
            			$new_available[] = $val;
            		}
            	}
            }

                               
            $this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_payment_list',
			  'data'=>$new_available
			);		    	   
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetPaymentInfo()
	{
		$merchant_id = $this->validateToken(); $settings = array();
		$name = ''; $list = FunctionsV3::PaymentOptionList();
		$payment_code = isset($this->data['code'])?$this->data['code']:'';
				
		$name = array_key_exists($payment_code,(array)$list)?$list[$payment_code]:'';
				
		switch ($payment_code) {
			case "cod":
				$settings = array(
				  'merchant_disabled_cod'=>getOption($merchant_id,'merchant_disabled_cod'),
				  'cod_change_required_merchant'=>getOption($merchant_id,'cod_change_required_merchant'),
				);				
				break;
				
			case "ocr":
				$settings = array(
				  'merchant_disabled_ccr'=>getOption($merchant_id,'merchant_disabled_ccr'),				  
				);				
				break;
				
			case "pyr":	
                $card_list = array();			
			    $list_check=Yii::app()->functions->getOption('payment_provider',$merchant_id);
			    $list = Yii::app()->functions->getPaymentProviderListActive();
			    if(is_array($list) && count($list)>=1){
			    	foreach ($list as $val) {
			    		$card_list[]= array(
			    		  'id'=>$val['id'],
			    		  'payment_name'=>$val['payment_name'],
			    		  'payment_logo'=>FoodItemWrapper::getImage($val['payment_logo']),
			    		);
			    	}
			    }
			    $settings = array(
				  'merchant_payondeliver_enabled'=>getOption($merchant_id,'merchant_payondeliver_enabled'),	
				  'card_list'=>$card_list,
				  'card_selected'=>json_decode($list_check,true)
				);				
			    break;
		
			case "stp":    
			   $settings = array(
				  'stripe_enabled'=>getOption($merchant_id,'stripe_enabled'),
				  'stripe_mode'=>getOption($merchant_id,'stripe_mode'),
				  'merchant_stripe_card_fee'=>getOption($merchant_id,'merchant_stripe_card_fee'),
				  'sanbox_stripe_secret_key'=>getOption($merchant_id,'sanbox_stripe_secret_key'),
				  'sandbox_stripe_pub_key'=>getOption($merchant_id,'sandbox_stripe_pub_key'),
				  'merchant_sandbox_stripe_webhooks'=>getOption($merchant_id,'merchant_sandbox_stripe_webhooks'),
				  
				  'live_stripe_secret_key'=>getOption($merchant_id,'live_stripe_secret_key'),
				  'live_stripe_pub_key'=>getOption($merchant_id,'live_stripe_pub_key'),
				  'merchant_live_stripe_webhooks'=>getOption($merchant_id,'merchant_live_stripe_webhooks'),
				);				
			   break;
			   
			case "payu":   
			   $settings = array(
				  'merchant_payu_enabled'=>getOption($merchant_id,'merchant_payu_enabled'),	
				  'merchant_payu_mode'=>getOption($merchant_id,'merchant_payu_mode'),
				  'merchant_payu_key'=>getOption($merchant_id,'merchant_payu_key'),
				  'merchant_payu_salt'=>getOption($merchant_id,'merchant_payu_salt'),
				);				
			    break;
			  
			case "obd":      
			   $settings = array(
				  'merchant_bankdeposit_enabled'=>getOption($merchant_id,'merchant_bankdeposit_enabled'),	
				  'merchant_deposit_subject'=>getOption($merchant_id,'merchant_deposit_subject'),
				  'merchant_deposit_instructions'=>getOption($merchant_id,'merchant_deposit_instructions'),				  
				);				
			    break;
			    
			case "paypal_v2":    
			   $settings = array(
				  'merchant_paypal_v2_enabled'=>getOption($merchant_id,'merchant_paypal_v2_enabled'),	
				  'merchant_paypal_v2_mode'=>getOption($merchant_id,'merchant_paypal_v2_mode'),
				  'merchant_paypal_v2_card_fee'=>getOption($merchant_id,'merchant_paypal_v2_card_fee'),				  
				  'merchant_paypal_v2_client_id'=>getOption($merchant_id,'merchant_paypal_v2_client_id'),
				  'merchant_paypal_v2_secret'=>getOption($merchant_id,'merchant_paypal_v2_secret'),
				);				
			   break;
			    
			case "mercadopago":
				$settings = array(
				  'merchant_mercadopago_v2_enabled'=>getOption($merchant_id,'merchant_mercadopago_v2_enabled'),	
				  'merchant_mercadopago_v2_mode'=>getOption($merchant_id,'merchant_mercadopago_v2_mode'),
				  'merchant_mercadopago_v2_card_fee'=>getOption($merchant_id,'merchant_mercadopago_v2_card_fee'),				  
				  'merchant_mercadopago_v2_client_id'=>getOption($merchant_id,'merchant_mercadopago_v2_client_id'),
				  'merchant_mercadopago_v2_client_secret'=>getOption($merchant_id,'merchant_mercadopago_v2_client_secret'),
				);				 
				break;  
				
			case "atz":	
			    $settings = array(
				  'merchant_enabled_autho'=>getOption($merchant_id,'merchant_enabled_autho'),	
				  'merchant_mode_autho'=>getOption($merchant_id,'merchant_mode_autho'),
				  'merchant_autho_api_id'=>getOption($merchant_id,'merchant_autho_api_id'),				  
				  'merchant_autho_key'=>getOption($merchant_id,'merchant_autho_key')				  
				);				 
			   break;  
			   
		    case "btr":	
			    $settings = array(
				  'merchant_btr_enabled'=>getOption($merchant_id,'merchant_btr_enabled'),	
				  'merchant_btr_mode'=>getOption($merchant_id,'merchant_btr_mode'),
				  'mt_sanbox_brain_mtid'=>getOption($merchant_id,'mt_sanbox_brain_mtid'),				  
				  'mt_sanbox_brain_publickey'=>getOption($merchant_id,'mt_sanbox_brain_publickey'),				  
				  'mt_sanbox_brain_privateckey'=>getOption($merchant_id,'mt_sanbox_brain_privateckey'),
				  'mt_live_brain_mtid'=>getOption($merchant_id,'mt_live_brain_mtid'),
				  'mt_live_brain_publickey'=>getOption($merchant_id,'mt_live_brain_publickey'),
				  'mt_live_brain_privateckey'=>getOption($merchant_id,'mt_live_brain_privateckey'),
				);				 
			   break; 
			   	   
		    case "rzr":   
		        $settings = array(
				  'merchant_rzr_enabled'=>getOption($merchant_id,'merchant_rzr_enabled'),	
				  'merchant_rzr_mode'=>getOption($merchant_id,'merchant_rzr_mode'),	
				  'merchant_razor_key_id_sanbox'=>getOption($merchant_id,'merchant_razor_key_id_sanbox'),
				  'merchant_razor_secret_key_sanbox'=>getOption($merchant_id,'merchant_razor_secret_key_sanbox'),				  
				  'merchant_razor_key_id_live'=>getOption($merchant_id,'merchant_razor_key_id_live'),
				  'merchant_razor_secret_key_live'=>getOption($merchant_id,'merchant_razor_secret_key_live'),
				);				 
		       break; 
		       
		    case "vog":
		    	$settings = array(
				  'merchant_vog_enabled'=>getOption($merchant_id,'merchant_vog_enabled'),	
				  'merchant_vog_merchant_id'=>getOption($merchant_id,'merchant_vog_merchant_id'),				  
				);				 
		    	break; 
		    	
		    case "obd_receive":
		    	$this->code = 1; $this->msg = "OK";
		    	$this->details = array(
				  'next_action'=>'obd_receive_list',				  
				);		
				$this->output();
		    	break;   
		       
			default:
				$this->msg = translate("Payment settings not available");
				$this->output();
				break;
		}
				
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		  'next_action'=>'fill_payment_info',
		  'code'=>$payment_code,
		  'name'=>$name,
		  'data'=>$settings
		);				
		$this->output();
	}
	
	public function actionSavePaymentSettings()
	{
		$merchant_id = $this->validateToken(); $settings = array();		
		$payment_code = isset($this->data['payment_code'])?$this->data['payment_code']:'';
		
		//dump($this->data);
		
		switch ($payment_code) {
			case "cod":
				Yii::app()->functions->updateOption("merchant_disabled_cod",
    	         isset($this->data['merchant_disabled_cod'])?$this->data['merchant_disabled_cod']:'',
    	         $merchant_id);				
    	         
    	         Yii::app()->functions->updateOption("cod_change_required_merchant",
    	         isset($this->data['cod_change_required_merchant'])?$this->data['cod_change_required_merchant']:'',
    	         $merchant_id);				
				break;
				
			case "ocr":
				  Yii::app()->functions->updateOption("merchant_disabled_ccr",
    	          isset($this->data['merchant_disabled_ccr'])?$this->data['merchant_disabled_ccr']:'',
    	          $merchant_id);				
				break;
				
			case "pyr":	
			      Yii::app()->functions->updateOption("merchant_payondeliver_enabled",
    	          isset($this->data['merchant_payondeliver_enabled'])?$this->data['merchant_payondeliver_enabled']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("payment_provider",
		    	  isset($this->data['payment_provider'])?json_encode($this->data['payment_provider']):''
		    	  ,$merchant_id);			
			    break;
			    
			case "stp":    
			      Yii::app()->functions->updateOption("stripe_enabled",
    	          isset($this->data['stripe_enabled'])?$this->data['stripe_enabled']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("stripe_mode",
    	          isset($this->data['stripe_mode'])?$this->data['stripe_mode']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("merchant_stripe_card_fee",
    	          isset($this->data['merchant_stripe_card_fee'])?$this->data['merchant_stripe_card_fee']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("sanbox_stripe_secret_key",
    	          isset($this->data['sanbox_stripe_secret_key'])?$this->data['sanbox_stripe_secret_key']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("sandbox_stripe_pub_key",
    	          isset($this->data['sandbox_stripe_pub_key'])?$this->data['sandbox_stripe_pub_key']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("merchant_sandbox_stripe_webhooks",
    	          isset($this->data['merchant_sandbox_stripe_webhooks'])?$this->data['merchant_sandbox_stripe_webhooks']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("live_stripe_secret_key",
    	          isset($this->data['live_stripe_secret_key'])?$this->data['live_stripe_secret_key']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("live_stripe_pub_key",
    	          isset($this->data['live_stripe_pub_key'])?$this->data['live_stripe_pub_key']:'',
    	          $merchant_id);	
    	          
    	          Yii::app()->functions->updateOption("merchant_live_stripe_webhooks",
    	          isset($this->data['merchant_live_stripe_webhooks'])?$this->data['merchant_live_stripe_webhooks']:'',
    	          $merchant_id);	
			    break;
		
			case "payu":
				 Yii::app()->functions->updateOption("merchant_payu_enabled",
    	          isset($this->data['merchant_payu_enabled'])?$this->data['merchant_payu_enabled']:'',
    	          $merchant_id);	 
    	          
    	          Yii::app()->functions->updateOption("merchant_payu_mode",
    	          isset($this->data['merchant_payu_mode'])?$this->data['merchant_payu_mode']:'',
    	          $merchant_id);	 
    	          
    	          Yii::app()->functions->updateOption("merchant_payu_key",
    	          isset($this->data['merchant_payu_key'])?$this->data['merchant_payu_key']:'',
    	          $merchant_id);	 
    	          
    	          Yii::app()->functions->updateOption("merchant_payu_salt",
    	          isset($this->data['merchant_payu_salt'])?$this->data['merchant_payu_salt']:'',
    	          $merchant_id);	 
				break;
				    
			case "obd": 
			    Yii::app()->functions->updateOption("merchant_bankdeposit_enabled",
    	          isset($this->data['merchant_bankdeposit_enabled'])?$this->data['merchant_bankdeposit_enabled']:'',
    	        $merchant_id);	 
    	        
    	        Yii::app()->functions->updateOption("merchant_deposit_subject",
    	          isset($this->data['merchant_deposit_subject'])?$this->data['merchant_deposit_subject']:'',
    	        $merchant_id);	 
    	        
    	        Yii::app()->functions->updateOption("merchant_deposit_instructions",
    	          isset($this->data['merchant_deposit_instructions'])?$this->data['merchant_deposit_instructions']:'',
    	        $merchant_id);	 
			    break;	
			    
			case "paypal_v2":				
				Yii::app()->functions->updateOption("merchant_paypal_v2_enabled",
    	          isset($this->data['merchant_paypal_v2_enabled'])?$this->data['merchant_paypal_v2_enabled']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_paypal_v2_mode",
    	          isset($this->data['merchant_paypal_v2_mode'])?$this->data['merchant_paypal_v2_mode']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_paypal_v2_card_fee",
    	          isset($this->data['merchant_paypal_v2_card_fee'])?$this->data['merchant_paypal_v2_card_fee']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_paypal_v2_client_id",
    	          isset($this->data['merchant_paypal_v2_client_id'])?$this->data['merchant_paypal_v2_client_id']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_paypal_v2_secret",
    	          isset($this->data['merchant_paypal_v2_secret'])?$this->data['merchant_paypal_v2_secret']:'',
    	        $merchant_id);	 
				break;
				    
				
			case "mercadopago":
				Yii::app()->functions->updateOption("merchant_mercadopago_v2_enabled",
    	          isset($this->data['merchant_mercadopago_v2_enabled'])?$this->data['merchant_mercadopago_v2_enabled']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_mercadopago_v2_mode",
    	          isset($this->data['merchant_mercadopago_v2_mode'])?$this->data['merchant_mercadopago_v2_mode']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_mercadopago_v2_card_fee",
    	          isset($this->data['merchant_mercadopago_v2_card_fee'])?$this->data['merchant_mercadopago_v2_card_fee']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_mercadopago_v2_client_id",
    	          isset($this->data['merchant_mercadopago_v2_client_id'])?$this->data['merchant_mercadopago_v2_client_id']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_mercadopago_v2_client_secret",
    	          isset($this->data['merchant_mercadopago_v2_client_secret'])?$this->data['merchant_mercadopago_v2_client_secret']:'',
    	        $merchant_id);	 
				break;
					
			case "atz":
				Yii::app()->functions->updateOption("merchant_enabled_autho",
    	          isset($this->data['merchant_enabled_autho'])?$this->data['merchant_enabled_autho']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_mode_autho",
    	          isset($this->data['merchant_mode_autho'])?$this->data['merchant_mode_autho']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_autho_api_id",
    	          isset($this->data['merchant_autho_api_id'])?$this->data['merchant_autho_api_id']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_autho_key",
    	          isset($this->data['merchant_autho_key'])?$this->data['merchant_autho_key']:'',
    	        $merchant_id);	 
				break;
					
			case "btr":	
			   Yii::app()->functions->updateOption("merchant_btr_enabled",
    	          isset($this->data['merchant_btr_enabled'])?$this->data['merchant_btr_enabled']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("merchant_btr_mode",
    	          isset($this->data['merchant_btr_mode'])?$this->data['merchant_btr_mode']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_sanbox_brain_mtid",
    	          isset($this->data['mt_sanbox_brain_mtid'])?$this->data['mt_sanbox_brain_mtid']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_sanbox_brain_publickey",
    	          isset($this->data['mt_sanbox_brain_publickey'])?$this->data['mt_sanbox_brain_publickey']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_sanbox_brain_privateckey",
    	          isset($this->data['mt_sanbox_brain_privateckey'])?$this->data['mt_sanbox_brain_privateckey']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_live_brain_mtid",
    	          isset($this->data['mt_live_brain_mtid'])?$this->data['mt_live_brain_mtid']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_live_brain_publickey",
    	          isset($this->data['mt_live_brain_publickey'])?$this->data['mt_live_brain_publickey']:'',
    	        $merchant_id);	 
    	        Yii::app()->functions->updateOption("mt_live_brain_privateckey",
    	          isset($this->data['mt_live_brain_privateckey'])?$this->data['mt_live_brain_privateckey']:'',
    	        $merchant_id);	 
			  break;
			  
			case "rzr": 
			   Yii::app()->functions->updateOption("merchant_rzr_enabled",
    	          isset($this->data['merchant_rzr_enabled'])?$this->data['merchant_rzr_enabled']:'',
    	       $merchant_id);	 
    	       
    	       Yii::app()->functions->updateOption("merchant_rzr_mode",
    	          isset($this->data['merchant_rzr_mode'])?$this->data['merchant_rzr_mode']:'',
    	       $merchant_id);	 
    	       
    	       Yii::app()->functions->updateOption("merchant_razor_key_id_sanbox",
    	          isset($this->data['merchant_razor_key_id_sanbox'])?$this->data['merchant_razor_key_id_sanbox']:'',
    	       $merchant_id);	 
    	       
    	       Yii::app()->functions->updateOption("merchant_razor_secret_key_sanbox",
    	          isset($this->data['merchant_razor_secret_key_sanbox'])?$this->data['merchant_razor_secret_key_sanbox']:'',
    	       $merchant_id);	 
    	       
    	       Yii::app()->functions->updateOption("merchant_razor_key_id_live",
    	          isset($this->data['merchant_razor_key_id_live'])?$this->data['merchant_razor_key_id_live']:'',
    	       $merchant_id);	 
    	       
    	       Yii::app()->functions->updateOption("merchant_razor_secret_key_live",
    	          isset($this->data['merchant_razor_secret_key_live'])?$this->data['merchant_razor_secret_key_live']:'',
    	       $merchant_id);	 
			  break;  
			  	
			case "vog":
				Yii::app()->functions->updateOption("merchant_vog_enabled",
    	          isset($this->data['merchant_vog_enabled'])?$this->data['merchant_vog_enabled']:'',
    	       $merchant_id);	 
    	       Yii::app()->functions->updateOption("merchant_vog_merchant_id",
    	          isset($this->data['merchant_vog_merchant_id'])?$this->data['merchant_vog_merchant_id']:'',
    	       $merchant_id);	 
				break;  
				
			default:
				$this->msg = translate("Payment settings not available");
				$this->output();
				break;
		}
		
		$this->code = 1;
		$this->msg = translate("Setting saved");
		$this->details = array();		
		$this->output();
	}
	
	public function actionReceiveBankList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = MerchantWrapper::getAllBankDeposit($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
						
			$data = array();$x=0;
			foreach ($resp as $val) {		
												
				$data[] = array(
				  'id'=>$val['id'],	
				  'name'=>$val['customer_name'],
				  'description'=>translate("Branch code:[branch_code]",array(
				   '[branch_code]'=>$val['branch_code']
				  )),
				  'thumbnail'=>FoodItemWrapper::getImage($val['scanphoto']),
				  'status'=>t($val['status']),
				  'date_created'=>translate("Date:[date] Time:[time] Amout:[amount]",array(
				    '[date]'=>FunctionsV3::prettyDate($val['date_of_deposit']),
				    '[time]'=>FunctionsV3::prettyTime($val['time_of_deposit']),
				    '[amount]'=>FunctionsV3::prettyPrice($val['amount']),
				  ))
				);				
				$x++;
			}
					
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				);
			}
		}
		$this->output();
	}
	
	public function actionReceiveBankID()
	{
		$this->msg = translate("Edit function is not available");
		$this->output();
	}
	
	public function actionReceiveBankDelete()
	{
		$this->msg = translate("Delete function is not available");
		$this->output();
	}
	
	public function actionSaveSocialSettings()
	{
		$merchant_id = $this->validateToken();
		
		Yii::app()->functions->updateOption("facebook_page",
    	isset($this->data['facebook_page'])?$this->data['facebook_page']:'',
    	$merchant_id);	 
    	
    	Yii::app()->functions->updateOption("twitter_page",
    	isset($this->data['twitter_page'])?$this->data['twitter_page']:'',
    	$merchant_id);	 
    	
    	Yii::app()->functions->updateOption("google_page",
    	isset($this->data['google_page'])?$this->data['google_page']:'',
    	$merchant_id);	 
    	
    	$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
	public function actiongetSocialSettings()
	{
		$merchant_id = $this->validateToken();		
		$settings[]=array(
		  'option_name'=>"facebook_page",
		  'option_value'=>getOption($merchant_id,'facebook_page')
		);
		$settings[]=array(
		  'option_name'=>"twitter_page",
		  'option_value'=>getOption($merchant_id,'twitter_page')
		);
		$settings[]=array(
		  'option_name'=>"google_page",
		  'option_value'=>getOption($merchant_id,'google_page')
		);
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		  'next_action'=>"set_form_options",
		  'data'=>$settings
		);
		$this->output();
	}
	
	public function actiongetAlertNotification()
	{
		$merchant_id = $this->validateToken();		
		$settings[]=array(
		  'option_name'=>"merchant_notify_email",
		  'option_value'=>getOption($merchant_id,'merchant_notify_email')
		);
		$settings[]=array(
		  'option_name'=>"merchant_cancel_order_email",
		  'option_value'=>getOption($merchant_id,'merchant_cancel_order_email')
		);
		$settings[]=array(
		  'option_name'=>"merchant_cancel_order_phone",
		  'option_value'=>getOption($merchant_id,'merchant_cancel_order_phone')
		);
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		  'next_action'=>"set_form_options",
		  'data'=>$settings
		);
		$this->output();
	}
	
	public function actionorder_list()
	{
		$merchant_id = $this->validateToken();			
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		$date_now = date('Ymd');
		
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $page_id  = isset($this->data['page_id'])?$this->data['page_id']:'';
        $new  = isset($this->data['new'])?$this->data['new']:false;
        $order_type  = isset($this->data['order_type'])?$this->data['order_type']:'';
        
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
                
		if ($resp = OrderWrapper::getAllOrder($new,$order_type,$merchant_id,$page,$page_limit,$search_string)){			
						
			$stmt="SELECT FOUND_ROWS() as total_row"; $total = 0;
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				$total = $res['total_row'];
			}						
			$resp = Yii::app()->request->stripSlashes($resp);
										
			$data = array();$x=0;
			foreach ($resp as $val) {												
						
				$pre_order = 0; $pre_order_msg='';
				$delivery_date=$val['delivery_date'];
				$delivery_date=date("Ymd",strtotime($delivery_date));				
				$datediff = Yii::app()->functions->dateDifference($delivery_date,$date_now);
				
				//dump("$delivery_date=>$date_now");
				
				if($delivery_date>$date_now){					
					$pre_order = 1;
					$pre_order_msg = translate("This is advance order on [date]",array(					  
					  '[date]'=>FunctionsV3::prettyDate($val['delivery_date'])
					));
				}
								
				$delivery_time = FunctionsV3::prettyTime($val['delivery_time']);
				if($val['delivery_asap']==1){					
					$delivery_time = t("Deliver ASAP");
				}			
								
				$duration='';
				if(isset($val['task_location']) && $page_id=="ready_order"){
					if(!empty($val['task_location'])){		
					   try{			  
					      $resp_duration = DriverWrapper::getTaskDistance($merchant_id,$val['task_location']);
					      $duration = isset($resp_duration['duration'])?$resp_duration['duration']:'';
					   } catch (Exception $e) {
					   	  //echo $e->getMessage();					   	  
					   }
					}
				}
				
				$driver_id=0; $driver_name=''; $driver_photo='';
				if(isset($val['driver_information'])){
					if(!empty($val['driver_information'])){
					   	if ( $driver_information  = explode("|",$val['driver_information']) ){					   		
					   		$driver_id = isset($driver_information[0])?$driver_information[0]:'';
					   		$driver_name = isset($driver_information[1])?$driver_information[1]:'';				   		
					   		$driver_photo = isset($driver_information[2])?$driver_information[2]:'';
					   		$driver_photo = DriverWrapper::driverPhotoUrl( $driver_photo );					   		
					   	} 
					}
				}
								
				$data[] = array(
				  'order_id'=>$val['order_id'],
				  'order_no'=>translate("Order No. #[order_id]",array(
				   '[order_id]'=>$val['order_id']
				  )),
				  'total_items'=>$val['total_items'],
				  'items'=>translate("Items for [customer_name]",array(
				    '[customer_name]'=>$val['customer_name']
				  )),
				  'delivery_date'=>$val['delivery_date'],
				  'date_created'=>PrettyDateTime::parse(new DateTime($val['date_created'])),
				  'total_order_amount'=>FunctionsV3::prettyPrice($val['total_order_amount']),
				  'status'=>t($val['status']),
				  'status_raw'=>$val['status_raw'],
				  'trans_type'=>t($val['trans_type']),
				  'trans_type_raw'=>$val['trans_type_raw'],
				  'estimated_time'=>$val['estimated_time'],
				  'estimated_date_time'=>date("Y-m-d H:i:s",strtotime($val['estimated_date_time'])),
				  'date_created_raw'=>date("Y-m-d H:i:s",strtotime($val['date_created'])),
				  'date_created_now'=>date("Y-m-d H:i:s"),
				  'date_modified'=>date("Y-m-d H:i:s",strtotime($val['date_modified'])),
				  'timezone'=>Yii::app()->timeZone,
				  'pre_order'=>$pre_order,
				  'pre_order_msg'=>$pre_order_msg,
				  'request_cancel'=>$val['request_cancel'],
				  'delivery_time'=>$delivery_time,
				  'assigned_driver'=>isset($val['assigned_driver'])?(integer)$val['assigned_driver']:0,
				  'duration'=>$duration,
				  'driver_id'=>$driver_id,
				  'driver_name'=>$driver_name,
				  'driver_photo'=>$driver_photo,
				  'stic_customer_name'=>$val['customer_name'],
				  'stic_delivery_address'=>$val['full_address']
				);							
				$x++;
			}		
													
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_order_list',
			 'refresh'=>$refresh,
			 'total'=>$total,
			 'data'=>$data,
			 'page_id'=>$page_id
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list_no_order',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}				
		$this->output();
	}
	
	public function actionAcceptOrder()
	{		
		$merchant_id = $this->validateToken();
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		$order_id  = isset($this->data['order_id'])?(integer)$this->data['order_id']:0;
		$time = isset($this->data['reason'])?(integer)$this->data['reason']:0;
		
		if($time<=0){
		   $this->msg = translate("Time estimation is required");
		   $this->output();	
		}	
		
		if(!$order = OrderWrapper::validateOrder($merchant_id,$order_id)){
			$this->msg = translate("Order not found");
		    $this->output();	
		}
				
		if($order_id>0){
			
			/*check if merchant can change the status*/
			if(!OrderWrapper::canChangeOrderStatus($order)){
				$this->msg=translate("Sorry but you cannot change the order status anymore. Order is lock by the website admin");
				$this->details = array(
				  'next_action'=>"close_all_dialog_order",
				  'order_id'=>$order_id
				);
				$this->output();	
			}	
						
										  	  	    	  	   
			try {
							
				$accepted_based_time = (integer)getOptionA('accepted_based_time');
				$accepted_based_time = $accepted_based_time>0?$accepted_based_time:1;
									
				$date_now = date('Y-m-d'); $datetime_now = date("Y-m-d g:i:s a");
				$delivery_date=date("Y-m-d",strtotime($order['delivery_date']));
				if(!empty($order['delivery_time'])){					
				   $delivery_time=date("H:i:s",strtotime($order['delivery_time']));				   
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
									
				$up =Yii::app()->db->createCommand()->update("{{order_delivery_address}}",$params,
		  	    'order_id=:order_id',
			  	    array(
			  	      ':order_id'=>$order_id
			  	    )
		  	    );
		  	    
		  	   $status = OrderWrapper::getActionStatus('accept');
		  	   
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
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);	
				  	    
	  	       OrderWrapper::updateOrderHistory($order_id,$merchant_id,$params,$params2);
	  	       $this->code = 1;
		  	   $this->msg = "OK";
		  	   $this->details = array(
		  	      'next_action'=>"pop_dialog_order",
		  	      'order_id'=>$order_id,
		  	      'action_taken'=>'accepted'
		  	   );
		  	   
		  	   /*SEND NOTIFICATION*/
		  	  if(method_exists("FunctionsV3","notifyCustomerOrderStatusChange")){		  	   	   
			  	   FunctionsV3::notifyCustomerOrderStatusChange(
					  $order_id,
					  $status,
					  $remarks
				   );
		  	  }		  	  
		  	  
		  	  
		  	  
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
				$this->msg = translate($e->getMessage());
			}	  	    	  	    
		} else $this->msg = translate("Invalid order id");			
		$this->output();
	}
			
	public function actionOrderOptions()
	{
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		$order_id  = isset($this->data['order_id'])?(integer)$this->data['order_id']:0;
		$cancel_action = isset($this->data['cancel_action'])?$this->data['cancel_action']:'';
		$reason = isset($this->data['reason'])?$this->data['reason']:'';
		$notes = isset($this->data['notes'])?$this->data['notes']:'';
						
		$status = OrderWrapper::getActionStatus($cancel_action);
		
		$remarks = ''; $remarks2 = ''; $remarks_args=array();		
		
		if(!$order = OrderWrapper::validateOrder($merchant_id,$order_id)){
			$this->msg = translate("Order not found");
		    $this->output();	
		}
				
		/*check if merchant can change the status*/
		if(!OrderWrapper::canChangeOrderStatus($order)){
			$this->msg=translate("Sorry but you cannot change the order status anymore. Order is lock by the website admin");
			$this->details = array(
			  'next_action'=>"close_all_dialog_order",
			  'order_id'=>$order_id
			);
			$this->output();	
		}	
				
		switch ($cancel_action) {
			case "cancel_order":
			case "decline":  				
				if(empty($reason)){
				   $this->msg = translate("Reason is required");
		 		   $this->output();
				}
				if(!empty($notes)){
					/*$reason = translate("[reason], additional comments: [comment]",array(
		   	    	  '[reason]'=>$reason,
		   	    	  '[comment]'=>$notes
		   	    	));	
		   	    	$notes='';*/
					$remarks = "[reason], additional comments: [comment]";
					$remarks_args = array(
					  '[reason]'=>$reason,
		   	    	  '[comment]'=>$notes
					);
				}						
				break;
		
			case "delay_order":
				if(empty($reason)){
				   $this->msg = translate("Additional time is required");
		 		   $this->output();
				}								
				$remarks = "[min] minutes delayed";
				$remarks_args = array(
				   '[min]'=>$reason
				);
				
				if(!empty($notes)){
		   	    	$remarks = "[min] minutes delayed, additional comments: [comment]";
		   	    	$remarks_args = array(
					   '[min]'=>$reason,
					   '[comment]'=>$notes
					);
				}						
				break;
				
			case "manual_change_status":	
			    if(empty($reason)){
				   $this->msg = translate("Order status is required");
		 		   $this->output();
				}
			   $status = $reason;			
			   if(!empty($notes)){
					$remarks = "additional comments: [comment]";
					$remarks_args = array(					  
		   	    	  '[comment]'=>$notes
					);
				}	
			   break;
			   
			case "food_is_done":
				//$reason = $notes; $notes='';				
			   break;	 
			   
			case "approved_cancel_order":   
			   //$reason = $notes; $notes='';
			break;	 
			
			case "decline_cancel_order":
				if(empty($reason)){
				   $this->msg = translate("Reason is required");
		 		   $this->output();
				}
				if(!empty($notes)){
					/*$reason = translate("[reason], additional comments: [comment]",array(
		   	    	  '[reason]'=>$reason,
		   	    	  '[comment]'=>$notes
		   	    	));	
		   	    	$notes='';*/
					$remarks = "[reason], additional comments: [comment]";
					$remarks_args = array(
					  '[reason]'=>$reason,
		   	    	  '[comment]'=>$notes
					);
				}		
			break;	
			   						   
			default:
				break;
		}
		
		try {
						
			
			/*HISTORY*/
			$remarks2 = $remarks;
			$params = array(
			  'order_id'=>$order_id,
			  'status'=>$status,
			  'remarks'=>translate($remarks,(array)$remarks_args),
			  'remarks2'=>$remarks2,
			  'remarks_args'=>json_encode($remarks_args),
			  'notes'=>$notes,			  
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']			  
			);	
			
			/*ORDER*/
			$params2 = array(
			  'status'=>$status,
			  'date_modified'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);
					
			if($cancel_action=="approved_cancel_order"){
				$params2['request_cancel']=2;
				$params2['request_cancel_status']='approved';			
			} else if ($cancel_action=="decline_cancel_order") {				
				$params2['request_cancel']=2;
				$params2['request_cancel_status']='decline';
				unset($params2['status']);				
				$params['status']='decline';
			}
						
			/*dump($params);
			dump($params2);
			die();*/

					
			OrderWrapper::updateOrderHistory($order_id,$merchant_id,$params,$params2);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>"pop_dialog_order",
			  'order_id'=>$order_id
			);
			
			
			switch ($cancel_action) {
								
				case "delay_order":							    
				    OrderWrapper::updateEstimationTime($order_id,(float)$reason);	
				    OrderWrapper::updateTaskDeliveryDate($order_id,(float)$reason);							    				   
					break;
												
				default:
					break;
			}
						
            /*SEND NOTIFICATION*/
		    if(method_exists("FunctionsV3","notifyCustomerOrderStatusChange")){		  	   	   
		  	   FunctionsV3::notifyCustomerOrderStatusChange(
				  $order_id,
				  $status,
				  $reason
			   );
	  	    }	
	  	    
	  	    /*SEND PUSH TO DRIVER*/
	  	    if($cancel_action=="food_is_done"){	  	    	
	  	    	Yii::app()->setImport(array(			
				  'application.modules.merchantappv2.components.*',
				));				
			   DriverWrapper::notifyDriver($order_id,$reason);
			}
	  	    
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
			$this->msg = translate($e->getMessage());
		}
		$this->output();
	}
	
	public function actionOrderDetails()
	{				
		try {
			
			$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;			
			$merchant_id = $this->validateToken();
			MerchantWrapper::setMerchantTimezone($merchant_id);
			
		    $order_id  = isset($this->data['order_id'])?(integer)$this->data['order_id']:0;		
		    
			$resp = OrderWrapper::prepareReceipt($order_id);
			//dump($resp);die();
			$resp = Yii::app()->request->stripSlashes($resp);
			$resp['timezone']=Yii::app()->timeZone;
			
			$history = array();
			if($res = OrderWrapper::getOrderHistory($order_id)){
			   foreach ($res as $val) {
			   	  $remarks = $val['remarks'];
			   	  if(!empty($val['remarks2']) && !empty($val['remarks_args']) ){
		           	   $remarks_args = json_decode($val['remarks_args'],true);
		           	   if(is_array($remarks_args) && count($remarks_args)>=1){
		           	   	  $new_arrgs = array();
		           	   	  foreach ($remarks_args as $remarks_args_key=>$remarks_args_val) {
		           	   	  	$new_arrgs[$remarks_args_key]= Yii::t("driver",$remarks_args_val);
		           	   	  }		           	   	  
		           	      //$remarks = Yii::t("driver",$val['remarks2'],$new_arrgs);
		           	      $remarks = Yii::t("merchantappv2",$val['remarks2'],$new_arrgs); 
		           	   }
		           }
			      $history[]=array(
			         'date_created'=>OrderWrapper::prettyDateTime($val['date_created']),
			         'status'=>t($val['status']),
			         'remarks'=>!empty($remarks)?$remarks:''
			      );		
			   }				  
			}
			
			Yii::app()->db->createCommand()->update("{{order}}",array(
			  'merchantapp_viewed'=>1,
			  'viewed'=>1
			),
	  	    'order_id=:order_id',
		  	    array(
		  	      ':order_id'=>$order_id
		  	    )
	  	    );	
	  	    	  	    									
			$this->code = 1; $this->msg = "OK";
			$this->details = array(
			 'next_action'=>"display_order_details",
			 'refresh'=>$refresh,
			 'data'=>$resp,
			 'history'=>$history,			 
			);						
						
		} catch (Exception $e) {
		    $this->msg = translate($e->getMessage());
		}		
		$this->output();
	}
	
	public function actionrefresh_order(){
		$order_ids = '';
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		if(empty($this->data['order_id'])){		   			
			//
		} else {			
			$id = isset($this->data['order_id'])?explode(",",$this->data['order_id']):'';	
			if(is_array($id) && count($id)>=1){
				foreach ($id as $val_id) {
					$order_ids.= q($val_id).",";
				}
				$order_ids = substr($order_ids,0,-1);
			} 
		}
		
		$need_refresh = false;
				
		if($resp = OrderWrapper::getNewestOrder($order_ids, $merchant_id )){			
		   $this->msg = 1;
		   $need_refresh = true;
		} else {			
			if(!empty($order_ids)){
				if ( OrderWrapper::reheckNewestOrder($order_ids, $merchant_id)){
					$need_refresh = true;
				}			
			}		
		}						
				
		if($need_refresh){
		   $this->code = 1;		   
		   $this->details = array(
		     'next_action'=>"refresh_oder",
		     'datenow'=>date('c')
		   );
		} else {
		   $this->details = array(
		     'next_action'=>"silent",
		     'datenow'=>date('c')
		   );
		}	
		$this->output();
	}
	
	public function actionrefresh_cancel_order(){
		$order_ids = '';
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		if(empty($this->data['order_id'])){		   			
			//
		} else {			
			$id = isset($this->data['order_id'])?explode(",",$this->data['order_id']):'';	
			if(is_array($id) && count($id)>=1){
				foreach ($id as $val_id) {
					$order_ids.= q($val_id).",";
				}
				$order_ids = substr($order_ids,0,-1);
			} 
		}
		
		$need_refresh = false;
						
		if($resp = OrderWrapper::getNewestCancel($order_ids,$merchant_id)){
		   $this->msg = 1;
		   $need_refresh = true;
		}		
				
		if($need_refresh){
		   $this->code = 1;		  
		   $this->msg = translate("There are new cancel order request");
		   $this->details = array(
		     'next_action'=>"new_cancel_order",
		     'datenow'=>date('c')
		   );
		} else {
		   $this->details = array(
		     'next_action'=>"clear_new_cancel_badge",
		     'datenow'=>date('c')
		   );
		}	
		$this->output();
	}
	
	public function actiongetProfile()
	{		
		try {
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);
			$resp = Yii::app()->request->stripSlashes($resp);
			$data = array(
			  'username'=>$resp['username'],
			  'email_address'=>$resp['email_address'],
			  'mobile_number'=>$resp['contact_number'],
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
		     'next_action'=>"fill_profile",
		     'data'=>$data
		   );
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionsaveProfile()
	{		
		try {
		    $resp = MerchantUserWrapper::validateToken($this->merchant_token);
		    $id = $resp['id'];
		    $user_type = $resp['user_type'];
		    if($resp['user_type']=="merchant"){
		    	$params = array(
		    	  'username'=>$this->data['username'],
				  'contact_phone'=>$this->data['mobile_number'],
				  'contact_email'=>$this->data['email_address'],
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);
		    } else {
		    	$params = array(
		    	  'username'=>$this->data['username'],
				  'contact_number'=>$this->data['mobile_number'],
				  'contact_email'=>$this->data['email_address'],
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);
		    }		
		    MerchantUserWrapper::updateProfile($id,$user_type,$params);
		    $this->code = 1;
		    $this->msg = translate("Profile saved");
		    $this->details = array();
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionchangePassword()
	{
		
		if(trim($this->data['new_password'])!= trim($this->data['repeat_password']) ){
			$this->msg = translate("Confirm password does not match");
			$this->output();
		}
		
		try {
		    $resp = MerchantUserWrapper::validateToken($this->merchant_token);
		    $id = $resp['id'];
		    $user_type = $resp['user_type'];		    
		    $params = array(
		      'password'=>md5( trim($this->data['new_password']) ),
		      'date_modified'=>FunctionsV3::dateNow(),
		      'ip_address'=>$_SERVER['REMOTE_ADDR']
		    );
		    		    
		    MerchantUserWrapper::changePassword($id,$user_type,$params, md5($this->data['old_password']) );
		    
		    $this->code = 1;
		    $this->msg = translate("Change password succesful");
		    $this->details = array(
		      'next_action'=>"pop_form2"
		    );
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionsetPin()
	{
		
		if(trim($this->data['pin'])!= trim($this->data['confirm_pin']) ){
			$this->msg = translate("Confirm pin does not match");
			$this->output();
		}
		
		if (strlen($this->data['pin'])!=4){
			$this->msg = translate("Invalid pin please enter 4 digit");
			$this->output();
		}	
				
		if ($this->data['pin']==0000){		
			$this->msg = translate("Invalid pin 0000 is not allowed");
			$this->output();
		}	
		
		try {
		    $resp = MerchantUserWrapper::validateToken($this->merchant_token);
		    $id = $resp['id'];
		    $user_type = $resp['user_type'];		
		        
		    $params = array(
		      'pin'=>trim($this->data['pin']),
		      'date_modified'=>FunctionsV3::dateNow(),
		      'ip_address'=>$_SERVER['REMOTE_ADDR']
		    );
		    
		    MerchantUserWrapper::changePin($id,$user_type,$params);
		    
		    $this->code = 1;
		    $this->msg = translate("Change pin succesful");
		    $this->details = array(
		      'next_action'=>"pop_form2"
		    );
		    
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionremovePin()
	{
		try {
		    $resp = MerchantUserWrapper::validateToken($this->merchant_token);
		    $id = $resp['id'];
		    $user_type = $resp['user_type'];		
		        
		    $params = array(
		      'pin'=>0,
		      'date_modified'=>FunctionsV3::dateNow(),
		      'ip_address'=>$_SERVER['REMOTE_ADDR']
		    );
		    		    
		    MerchantUserWrapper::changePin($id,$user_type,$params);
		    
		    $this->code = 1;
		    $this->msg = translate("PIN succesfully removed");
		    $this->details = array(
		      'next_action'=>"pop_form2"
		    );
		    
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actiongetPIN()
	{
		try {
						
			$resp = MerchantUserWrapper::getPin($this->merchant_token);			
			$with_pin = 0;
			if(strlen($resp['pin'])>2){
			   $with_pin = 1;
			}						
			$data = array(			
			  'pin'=>str_split($resp['pin']),
			  'with_pin'=>$with_pin
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
		      'next_action'=>$with_pin==1?"remove_pin":'show_pin',
		      'data'=>$data
		    );		    
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actiongetLanguageList()
	{
		$data = array();
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
		$merchantapp_language = getOptionA('merchantapp_language');
		$language = !empty($merchantapp_language)?json_decode($merchantapp_language,true):'';
		
		$icon_url = Yii::app()->getBaseUrl(true)."/protected/modules/".APP_FOLDER."/assets/vendor/flag-icon/flags/1x1";
		
		if(is_array($language) && count($language)>=1){			
			foreach ($language['label'] as $key=>$val) {
				$flag =  strtolower($language['flag'][$key]) .".svg";
				$data[] = array(
				  'value'=>$key,
				  'label'=>translate($val),				  
				  'sub_label'=>$language['sub_label'][$key],	
				  'image'=>$icon_url."/$flag"
				);
			}			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>"set_language",
			  'refresh'=>$refresh,
			  'data'=>$data
			);
		} else {
		    $this->code = 1;
			$this->msg = translate("There are no available language");
			$this->details = array(
			  'next_action'=>'clear_list_language',
			);
		}			
		$this->output();
	}
	
	public function actionGetAlertSettings()
	{
		try {
		   $merchant_id = $this->validateToken();
		   $resp = MerchantUserWrapper::GetDeviceInformation($this->device_uiid);
		   $this->code =1;
		   $this->msg = "OK";
		   $this->details = array(
		     'next_action'=>"set_alert_settings",
		     'data'=>$resp
		   );		   
		} catch (Exception $e) {			
		   $this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionsaveAlertOrder()
	{		
		try {
		   $push_enabled = isset($this->data['push_enabled'])?(integer)$this->data['push_enabled']:0;		
		   $params = array(
		     'push_enabled'=>$push_enabled,
		     'date_modified'=>FunctionsV3::dateNow(),
		     'ip_address'=>$_SERVER['REMOTE_ADDR']
		   );
		   MerchantUserWrapper::UpdateDevice($this->device_uiid,$params);
		   $this->code = 1;
		   $this->msg = translate("Setting saved");
		} catch (Exception $e) {			
		   $this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionsaveSubsribe()
	{
		try {		   
		   $subscribe_topic = isset($this->data['push_alert'])?(integer)$this->data['push_alert']:0;		
		   $params = array(
		     'subscribe_topic'=>$subscribe_topic,
		     'date_modified'=>FunctionsV3::dateNow(),
		     'ip_address'=>$_SERVER['REMOTE_ADDR']
		   );		   
		   //dump($params);
		   MerchantUserWrapper::UpdateDevice($this->device_uiid,$params);
		   $this->code = 1;
		   $this->msg = translate("Setting saved");
		} catch (Exception $e) {			
		   $this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionlogoutApp()
	{
		try {	
			MerchantUserWrapper::UpdateDeviceStatus($this->device_uiid,'inactive');					
		} catch (Exception $e) {			
			//			
		}	
		
		$this->code = 1;
		$this->msg = "ok";
		$this->details = array(
		  'next_action'=>'silent',
		);
		$this->output();
	}
	
	public function actionValidatePin()
	{				
		$pin = isset($this->data['pin'])?(string)$this->data['pin']:0;						
		try {	
			MerchantUserWrapper::validatePin($this->merchant_token,$pin);		
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);				
			$this->code = 1; $this->msg = "OK";
			
			$data['next_action']="show_homepage";
			$data['merchant_info'] = $resp;					
			$this->details = $data;			
			
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}

	public function actionforgotPin()
	{			
		try {
			$email_address = isset($this->data['email_address'])?$this->data['email_address']:'';
			$resp = MerchantUserWrapper::getUserByEmail($email_address);					
			$code = MerchantUserWrapper::generatePin($resp['id'],$resp['merchant_id']);			
			$merchant_id = $resp['merchant_id'];
						
			$lang=Yii::app()->language;
			$tpl  = CustomerNotification::getNotificationTemplate('merchant_change_pin',$lang);
			
			MerchantUserWrapper::changePin($resp['id'],$resp['user_type'],array(
			  'pin'=>$code,
		      'date_modified'=>FunctionsV3::dateNow(),
		      'ip_address'=>$_SERVER['REMOTE_ADDR']
			));			
			
			$to = $resp['email_address'];
			
			$email_content = $tpl['email_content'];
			$email_subject = $tpl['email_subject'];	
			
			$data = array(
			  'code'=>$code,
			  'sitename'=>getOptionA('website_title'),
		      'siteurl'=>websiteUrl()
			);
			
			$email_subject = FunctionsV3::replaceTags($email_subject,$data);			
			$email_content = FunctionsV3::replaceTags($email_content,$data);	
			
			sendEmail($to,'',$email_subject,$email_content);
						
			$this->code =1; $this->msg = translate("We have sent new pin code in your email.");
			$this->details = array(
			   'next_action'=>"back_to_pin",			   
			);			
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}			
		$this->output();
	}	
	
	public function actionbooking_list()
	{
		$merchant_id = $this->validateToken();			
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		$date_now = date('Y-m-d');
		
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $page_id  = isset($this->data['page_id'])?$this->data['page_id']:'';        
        $booking_type  = isset($this->data['booking_type'])?$this->data['booking_type']:'';
        
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
                
        if ($resp = BookingWrapper::getAllBooking($booking_type,$merchant_id,$page,$page_limit,$search_string)){
        	$resp = Yii::app()->request->stripSlashes($resp);
        	
        	$stmt="SELECT FOUND_ROWS() as total_row"; $total = 0;
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				$total = $res['total_row'];
			}						
			
        	$data = array();$x=0;
        	foreach ($resp as $val) {
        		
        		$stats = $val['status'];
        		if($stats=="request_cancel_booking"){
        			$stats = "request to cancel";
        		} elseif ( $stats=="cancel_booking_approved"){
        			$stats = "cancellation approved";
        		}
        		
        		$val['date_created']=PrettyDateTime::parse(new DateTime($val['date_created']));
        		$val['status']=t($stats);
        		$val['timezone']=Yii::app()->timeZone;
        		$data[]=$val;
        	}
        	$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_booking_list',
			 'refresh'=>$refresh,
			 'total'=>$total,
			 'data'=>$data,
			 'page_id'=>$page_id
			);			
        } else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list_no_booking',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}						
		$this->output();
	}
	
	public function actionBookingOptions()
	{		
		$booking_id  = isset($this->data['booking_id'])?(integer)$this->data['booking_id']:0;
		$cancel_action = isset($this->data['cancel_action'])?$this->data['cancel_action']:'';
		$notes = isset($this->data['notes'])?$this->data['notes']:'';
		$reason = isset($this->data['reason'])?$this->data['reason']:'';
		$params = array(); $remarks='';
					
		switch ($cancel_action) {
			case "accept":
			case "cancel_booking_approved":	
			
			     $stats = 'approved';
			     if($cancel_action=="cancel_booking_approved"){
			     	$stats='cancel_booking_approved';
			     }			  
				$params = array(
				  'status'=>$stats,
				  'remarks'=>$notes,
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);				
				$remarks = $notes;	
				break;
				
			case "decline":	
			case "denied":  			
			   if(empty($notes) && empty($reason)){
			   	   $this->msg = translate("Reason is required");
		 		   $this->output();
			   }				
			    $remarks = $notes;
		   	    if(!empty($notes)){
		   	    	$remarks = translate("[reason], additional comments: [comment]",array(
		   	    	  '[reason]'=>$reason,
		   	    	  '[comment]'=>$notes
		   	    	));
		   	    }			   			   	
				$params = array(
				  'status'=>'denied',
				  'remarks'=>$remarks,
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);					
			   break;			   						
		}
		
		if(is_array($params) && count($params)>=1){
			try {				
				BookingWrapper::updateBooking($booking_id,$params,$remarks);					
				$this->code = 1; $this->msg = "OK";
				$this->details = array(					  
				  'next_action'=>"pop_dialog_booking"
				);
			} catch (Exception $e) {
				$this->msg = translate($e->getMessage());
			}	
		} else $this->msg = translate("Undefined parameter");
		
		$this->output();
	}
	
	public function actionBookingDetails()
	{		
		try {

			$history = array();
			$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;			
		    $booking_id  = isset($this->data['booking_id'])?(integer)$this->data['booking_id']:0;
		
			$resp = BookingWrapper::getBookingDetails($booking_id);
			$resp = Yii::app()->request->stripSlashes($resp);
			$resp['number_guest'] = translate("[number_guest] guest",array(
			 '[number_guest]'=>$resp['number_guest']
			));
			$resp['status'] = t($resp['status']);
			$resp['date_booking'] = FunctionsV3::prettyDate($resp['date_booking']);
			$resp['booking_time'] = FunctionsV3::prettyTime($resp['booking_time']);
			$resp['date_created'] = OrderWrapper::prettyDateTime($resp['date_created']);
			
			$history=array();
			$resph = BookingWrapper::getHistory($booking_id);
			if($resph){
				$resph = Yii::app()->request->stripSlashes($resph);
				foreach ($resph as $val) {
					$val['status'] = t($val['status']);
					$val['date_created'] = FunctionsV3::prettyDate($val['date_created']);
					$history[]=$val;
				}
			}		
			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			 'next_action'=>"display_booking_details",
			 'refresh'=>$refresh,
			 'data'=>$resp,
			 'history'=>$history,			 
			);		
			
						
		} catch (Exception $e) {
			$this->code = 1;
			$this->details = array(
			 'next_action'=>"clear_booking_details",			 
			);		
			$this->msg = translate($e->getMessage());
		}	
		$this->output();
	}
	
	public function actionrefresh_booking()
	{
		$booking_ids = '';
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		if(empty($this->data['booking_id'])){		   			
			//
		} else {			
			$id = isset($this->data['booking_id'])?explode(",",$this->data['booking_id']):'';	
			if(is_array($id) && count($id)>=1){
				foreach ($id as $val_id) {
					$booking_ids.= q($val_id).",";
				}
				$booking_ids = substr($booking_ids,0,-1);
			} 
		}
		
		$need_refresh = false;
				
		if($resp = BookingWrapper::getNewestBooking($booking_ids)){
		   $this->msg = 1;
		   $need_refresh = true;
		}						
				
		if($need_refresh){
		   $this->code = 1;		   
		   $this->details = array(
		     'next_action'=>"refresh_booking",
		     'datenow'=>date('c')
		   );
		} else {
		   $this->details = array(
		     'next_action'=>"silent",
		     'datenow'=>date('c')
		   );
		}	
		$this->output();
	}
	
	public function actionrefresh_cancel_booking()
	{
		$booking_ids = '';
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
		
		if(empty($this->data['booking_id'])){		   			
			//
		} else {			
			$id = isset($this->data['booking_id'])?explode(",",$this->data['booking_id']):'';	
			if(is_array($id) && count($id)>=1){
				foreach ($id as $val_id) {
					$booking_ids.= q($val_id).",";
				}
				$booking_ids = substr($booking_ids,0,-1);
			} 
		}
		
		$need_refresh = false;
				
		if($resp = BookingWrapper::getNewestCancel($booking_ids)){
		   $this->msg = 1;
		   $need_refresh = true;
		}						
				
		if($need_refresh){
		   $this->code = 1;		   
		   $this->details = array(
		     'next_action'=>"refresh_booking",
		     'datenow'=>date('c')
		   );
		} else {
		   $this->details = array(
		     'next_action'=>"silent",
		     'datenow'=>date('c')
		   );
		}	
		$this->output();
	}
	
	public function actionnotificationList()
	{
		$merchant_id = $this->validateToken();
		
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $page_id  = isset($this->data['page_id'])?$this->data['page_id']:'';        
		$list_type = isset($this->data['list_type'])?$this->data['list_type']:'';		
		
		$search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
                              
		if ($resp = MerchantWrapper::getViewNotification($list_type,$this->device_uiid,$merchant_id,
		    $page,$page_limit,$search_string)){
		    	
			$resp = Yii::app()->request->stripSlashes($resp);			
			$data = array();
			foreach ($resp as $val) {	        		  		        	
        		$val['date_created']=OrderWrapper::prettyDateTime($val['date_created']);
        		$data[]=$val;
			}					
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_notification_list',
			 'refresh'=>$refresh,			 
			 'data'=>$data,
			 'page_id'=>$page_id
			);						
		 } else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list_no_push',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}	
		$this->output();			
	}
	
	public function actionPushMarkRead()
	{
		$merchant_id = $this->validateToken();
		
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $page_id  = isset($this->data['page_id'])?$this->data['page_id']:'';   
        $id  = isset($this->data['id'])?(integer)$this->data['id']:''; 
        $record_type  = isset($this->data['record_type'])?$this->data['record_type']:''; 
        $row_id = $id;
        $search_string='';     
        
        
        try {
        	
        	if($id<=0){
	        	MerchantWrapper::MarkReadNotification($merchant_id,$this->device_uiid);	        
				$this->code = 1;
				$this->msg = "ok";
				$this->details = array(				  
				  'next_action'=>'refresh_push_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false,
				  'row_id'=>$row_id
				);
        	} else {
        		MerchantWrapper::MarkReadNotificationByID($record_type,$this->device_uiid,$id);
        		$this->code = 1;
				$this->msg = "ok";
				$this->details = array(
				  //'next_action'=>'refresh_push_list',				  				  
				  'next_action'=>'mark_push_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false,
				  'row_id'=>$row_id
				);
        	}
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}				
		$this->output();		
	}
	
	public function actionPushRemoveAll()
	{
		$merchant_id = $this->validateToken();
		
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $page_id  = isset($this->data['page_id'])?$this->data['page_id']:'';   
        $id  = isset($this->data['id'])?(integer)$this->data['id']:'';   
        $record_type  = isset($this->data['record_type'])?$this->data['record_type']:''; 
        $row_id = $id;
        $search_string='';     
        
        
		try {
        	
			if($id<=0){
	        	MerchantWrapper::PushRemoveAll($merchant_id,$this->device_uiid);
	        	
				$this->code = 1;
				$this->msg = "ok";
				$this->details = array(
				  'next_action'=>'clear_list_no_push',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false,
				  'row_id'=>$row_id
				);
			} else {
				MerchantWrapper::PushRemoveByID($record_type,$this->device_uiid,$id);
        		$this->code = 1;
				$this->msg = "ok";
				$this->details = array(
				  'next_action'=>'remove_push_list',
				  'refresh'=>$refresh,
				  'page_id'=>$page_id,
				  'is_search'=>!empty($search_string)?true:false,
				  'row_id'=>$row_id
				);
			}
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}				
		$this->output();		
	}
	
	public function actionSaveAlertSettings()
	{		
		$merchant_id = $this->validateToken();
		Yii::app()->functions->updateOption("merchant_notify_email",
         isset($this->data['merchant_notify_email'])?$this->data['merchant_notify_email']:'',
         $merchant_id);		
         
        Yii::app()->functions->updateOption("merchant_cancel_order_email",
         isset($this->data['merchant_cancel_order_email'])?$this->data['merchant_cancel_order_email']:'',
         $merchant_id);		 
         
        Yii::app()->functions->updateOption("merchant_cancel_order_phone",
         isset($this->data['merchant_cancel_order_phone'])?$this->data['merchant_cancel_order_phone']:'',
         $merchant_id);		  
         
         Yii::app()->functions->updateOption("merchant_invoice_email",
         isset($this->data['merchant_invoice_email'])?$this->data['merchant_invoice_email']:'',
         $merchant_id);		  

        $this->code = 1;
		$this->msg = translate("Settings saved");
			 
		$this->output();		
	}
	
	public function actionGalleryList()
	{
		$merchant_id = $this->validateToken();
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
		$enabled =  getOption($merchant_id,'gallery_disabled');
		$enabled = $enabled=="yes"?0:1;
		
		$page_limit=10;
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
		
        if($page>=1){
    		$this->code = 1;
			$this->details = array(
			  'next_action'=>'end_of_list',
			  'refresh'=>$refresh,
			  'enabled_gallery'=>$enabled,
			);
			$this->output();	
        }
        
		$merchant_gallery = getOption($merchant_id,'merchant_gallery');
		if ($json = json_decode($merchant_gallery,true)){			
			$data = array(); $x=0;
			foreach ($json as $val) {
				$data[] = array(
				  'id'=>$x,
				  'name'=>translate("Image [number]",array(
				   '[number]'=>$x+1
				  )),
				  'description'=>$val,
				  'thumbnail'=>FoodItemWrapper::getImage($val),
				);			
				$x++;
			}
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'enabled_gallery'=>$enabled,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'enabled_gallery'=>$enabled,
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'enabled_gallery'=>$enabled,
				);
			}
		}
		$this->output();
	}
	
	public function actionenabled_gallery()
	{
		$merchant_id = $this->validateToken();		
		$enabled = isset($this->data['enabled_gallery'])?(integer)$this->data['enabled_gallery']:0;
		if($enabled<=0){
			$enabled="yes";
		} else $enabled='';
				
		
		Yii::app()->functions->updateOption("gallery_disabled",$enabled,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
	public function actionbanner_enabled()
	{
		$merchant_id = $this->validateToken();		
		$enabled = isset($this->data['banner_enabled'])?(integer)$this->data['banner_enabled']:0;
		
		Yii::app()->functions->updateOption("banner_enabled",$enabled,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Settings saved");
		$this->output();
	}
	
	public function actionGetGallery()
	{
		$this->code = 1;
		$this->msg = translate("Edit not available");
		$this->output();
	}
	
	public function actionDeleteGallery()
	{
		$merchant_id = $this->validateToken();				
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;
		try {

			$merchant_gallery = getOption($merchant_id,'merchant_gallery');
			if ($json = json_decode($merchant_gallery,true)){
				$data = array();
				foreach ($json as $key=>$val) {					
					if($key<>$id){
						$data[]=$val;
					} else {
						FunctionsV3::deleteUploadedFile($val);
					}
				}
				Yii::app()->functions->updateOption("merchant_gallery",
				json_encode($data)
				,$merchant_id);	
			}
			
				
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionBannerList()
	{
		$merchant_id = $this->validateToken();
		$refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
		$enabled =  getOption($merchant_id,'banner_enabled');
		$enabled = $enabled=="yes"?0:1;
		
		$page_limit=10;
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
		
        if($page>=1){
    		$this->code = 1;
			$this->details = array(
			  'next_action'=>'end_of_list',
			  'refresh'=>$refresh,
			  'banner_enabled'=>$enabled,
			);
			$this->output();	
        }
        
		$merchant_gallery = getOption($merchant_id,'merchant_banner');
		if ($json = json_decode($merchant_gallery,true)){			
			$data = array(); $x=0;
			foreach ($json as $val) {
				$data[] = array(
				  'id'=>$x,
				  'name'=>translate("Image [number]",array(
				   '[number]'=>$x+1
				  )),
				  'description'=>$val,
				  'thumbnail'=>FoodItemWrapper::getImage($val),
				);			
				$x++;
			}
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'banner_enabled'=>$enabled,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'banner_enabled'=>$enabled,
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'banner_enabled'=>$enabled,
				);
			}
		}
		$this->output();
	}

	public function actionDeleteBanner()
	{
		$merchant_id = $this->validateToken();				
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;
		try {

			$merchant_gallery = getOption($merchant_id,'merchant_banner');
			if ($json = json_decode($merchant_gallery,true)){
				$data = array();
				foreach ($json as $key=>$val) {					
					if($key<>$id){
						$data[]=$val;
					} else {
						FunctionsV3::deleteUploadedFile($val);
					}
				}
				Yii::app()->functions->updateOption("merchant_banner",
				json_encode($data)
				,$merchant_id);	
			}
			
				
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionUploadFile()
	{				                		
		$merchant_id = $this->validateToken();		
		$profile_photo = '';
		$path_to_upload= FunctionsV3::uploadPath();
		$file_url = ''; $new_image_name='';
		
		$next_action = isset($this->data['next_action'])?$this->data['next_action']:'';
		$upload_type = isset($this->data['upload_type'])?$this->data['upload_type']:'';
		$upload_option_name = isset($this->data['upload_option_name'])?$this->data['upload_option_name']:'';
				
		if(isset($_FILES['file'])){				
		   header('Access-Control-Allow-Origin: *');		

		   $path_parts = pathinfo($_FILES["file"]["name"]);
		   $extension =  isset($path_parts['extension'])? strtolower($path_parts['extension']) : "jpg";
		   
		   $valid_extensions = FunctionsV3::validImageExtension();
		   if(!in_array($extension,$valid_extensions)){
		   	  $this->msg = translate("Invalid file extension");
		   	  $this->output();
		   }		
		      	
	       $new_image_name = urldecode($_FILES["file"]["name"]).".$extension";		       
	       $new_image_name=str_replace(array('?',':'),'',$new_image_name);	        
	       $time=time();
	       $new_image_name =  "$time-$new_image_name"; 
	       
	       $upload_res = @move_uploaded_file($_FILES["file"]["tmp_name"], "$path_to_upload/".$new_image_name);
		   if($upload_res){
		   	
		   	    if($upload_type==2){
			   	    $current_image = getOption($merchant_id,$upload_option_name);
			   	    $current_image = !empty($current_image)?json_decode($current_image,true):false;		   	
			   	    $image = array($new_image_name);
			   	    
			   	    if($current_image!=false){
			   	    	$image = array_merge($image,$current_image);
			   	    }		   
			   	    
			   	    Yii::app()->functions->updateOption( $upload_option_name ,
					  json_encode($image)
					,$merchant_id);
		   	    } else {
		   	    	//Yii::app()->functions->updateOption("merchant_gallery",$new_image_name,$merchant_id);
		   	    }		 
		   	    		   	    
		
		   	    $this->code=1;
				$this->msg= translate("Upload successful");
				$file_url = FoodItemWrapper::getImage($new_image_name);
								
		   } else $this->msg = translate("Cannot upload file");		    
		} else $this->msg = translate("Image is missing");						   
		echo '{ "code":"'.$this->code.'", "next_action":"'.$next_action.'", "msg":"'.$this->msg.'", "filename":"'.$new_image_name.'", "file_url":"'.$file_url.'" }';
		Yii::app()->end();
	}

	public function actiongeTimeOpening()
	{
		$merchant_id = $this->validateToken(); $data = array();
		$merchant_holiday = getOption($merchant_id,'merchant_holiday');
		
		if ( $res = MerchantWrapper::getTimeOpening($merchant_id)){
			foreach ($res as $val) {				
				$data[] = array(
				  'id'=>$val['day'],
				  'status'=>$val['status']=="open"?1:0,
				  'start_time'=>$val['start_time'],
				  'end_time'=>$val['end_time'],
				  'start_time_pm'=>$val['start_time_pm'],
				  'end_time_pm'=>$val['end_time_pm'],
				  'custom_text'=>$val['custom_text']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			  'next_action'=>"fill_opening_hours",
			  'data'=>$data,
			  'merchant_preorder'=>getOption($merchant_id,'merchant_preorder'),
			  'merchant_close_msg'=>getOption($merchant_id,'merchant_close_msg'),
			  'merchant_close_msg_holiday'=>getOption($merchant_id,'merchant_close_msg_holiday'),
			  'merchant_holiday'=>!empty($merchant_holiday)?(array)json_decode($merchant_holiday,true):array()
			);									
		} else {
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			  'next_action'=>"fill_opening_hours",
			  'data'=>array(),
			  'merchant_preorder'=>getOption($merchant_id,'merchant_preorder'),
			  'merchant_close_msg'=>getOption($merchant_id,'merchant_close_msg'),
			  'merchant_close_msg_holiday'=>getOption($merchant_id,'merchant_close_msg_holiday'),
			  'merchant_holiday'=>!empty($merchant_holiday)?(array)json_decode($merchant_holiday,true):array()
			);			
		}	
		$this->output();
	}
	
	public function actionStoreHours()
	{
		$merchant_id = $this->validateToken();
		
		$stmt_del="DELETE FROM {{opening_hours}} WHERE merchant_id=".q($merchant_id)." ";
        Yii::app()->db->createCommand($stmt_del)->query();  
		$days=Yii::app()->functions->getDays();	
		
		if(isset($this->data['stores_open_day'])){
			foreach ($days as $day_key=>$days) {
				$params_days = array(
				  'merchant_id'=>$merchant_id,
				  'day'=>$day_key,
				  'status'=>in_array($day_key,(array)$this->data['stores_open_day'])?"open":"close",
				  'start_time'=>isset($this->data['stores_open_starts'][$day_key])?$this->data['stores_open_starts'][$day_key]:'',
				  'end_time'=>isset($this->data['stores_open_ends'][$day_key])?$this->data['stores_open_ends'][$day_key]:'',
				  'start_time_pm'=>isset($this->data['stores_open_pm_start'][$day_key])?$this->data['stores_open_pm_start'][$day_key]:'',
				  'end_time_pm'=>isset($this->data['stores_open_pm_ends'][$day_key])?$this->data['stores_open_pm_ends'][$day_key]:'',
				  'custom_text'=>isset($this->data['stores_open_custom_text'][$day_key])?$this->data['stores_open_custom_text'][$day_key]:'',
				  
				);	 			
				Yii::app()->db->createCommand()->insert("{{opening_hours}}",$params_days);	
			}	    		    
	    }
	    	    
	    Yii::app()->functions->updateOption("stores_open_day",
		isset($this->data['stores_open_day'])?json_encode($this->data['stores_open_day']):''
		,$merchant_id);
		
		Yii::app()->functions->updateOption("stores_open_starts",
		isset($this->data['stores_open_starts'])?json_encode($this->data['stores_open_starts']):''
		,$merchant_id);
		
		Yii::app()->functions->updateOption("stores_open_ends",
		isset($this->data['stores_open_ends'])?json_encode($this->data['stores_open_ends']):''
		,$merchant_id);
		
		Yii::app()->functions->updateOption("stores_open_custom_text",
		isset($this->data['stores_open_custom_text'])?json_encode($this->data['stores_open_custom_text']):''
		,$merchant_id);

		Yii::app()->functions->updateOption("stores_open_pm_start",
		isset($this->data['stores_open_pm_start'])?json_encode($this->data['stores_open_pm_start']):''
		,$merchant_id);
					
		Yii::app()->functions->updateOption("stores_open_pm_ends",
		isset($this->data['stores_open_pm_ends'])?json_encode($this->data['stores_open_pm_ends']):''
		,$merchant_id);    				
		
		Yii::app()->functions->updateOption("merchant_preorder",
		isset($this->data['merchant_preorder'])?trim($this->data['merchant_preorder']):''
		,$merchant_id);    				
		
		Yii::app()->functions->updateOption("merchant_close_msg",
		isset($this->data['merchant_close_msg'])?trim($this->data['merchant_close_msg']):''
		,$merchant_id);    				
		
		Yii::app()->functions->updateOption("merchant_close_msg_holiday",
		isset($this->data['merchant_close_msg_holiday'])?trim($this->data['merchant_close_msg_holiday']):''
		,$merchant_id);    				
	    				
		if(is_array($this->data['merchant_holiday']) && count($this->data['merchant_holiday'])>=1){
			Yii::app()->functions->updateOption("merchant_holiday",
		    isset($this->data['merchant_holiday'])?json_encode($this->data['merchant_holiday']):''
		    ,$merchant_id);    				
		} else {
			Yii::app()->functions->updateOption("merchant_holiday",'',$merchant_id);    				
		}
		
		$this->code = 1;
		$this->msg = translate("Setting saved");
		$this->details = array();		
		$this->output();
	}
	
	public function actionReviewList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = MerchantWrapper::getAllReview($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);			
			$data = array();$x=0;
			foreach ($resp as $val) {			
				$description = $val['review'];	
				if($val['total_comments']>0){
					$description.= "<br/>".translate("comments([count])",array(
					  '[count]'=>$val['total_comments']
					));
				}
				$data[] = array(
				  'id'=>$val['id'],
				  'name'=>$val['customer_name'],
				  'description'=>$description,
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>t($val['status']),
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
											
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionReviewGetByID()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		
		$merchant_can_edit_reviews = getOptionA('merchant_can_edit_reviews');
		if($merchant_can_edit_reviews=="yes"){
			$this->msg = translate("Sorry but you don't have permission to modify this review");
			$this->output();
		}
		
		try {
								
			$resp = FoodItemWrapper::getData("review","id=:id",array(
			 ':id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['id'],
			  'review'=>$resp['review'],			  
			  'status'=>$resp['status']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"review_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddReview()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(		  
		  'review'=>isset($this->data['review'])?$this->data['review']:'',		  
		  'status'=>isset($this->data['status'])?$this->data['status']:'',		  
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		try {
						
			MerchantWrapper::addReview($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionReviewDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		
		$merchant_can_edit_reviews = getOptionA('merchant_can_edit_reviews');
		if($merchant_can_edit_reviews=="yes"){
			$this->msg = translate("Sorry but you don't have permission to modify this review");
			$this->output();
		}
		
		try {
						
			MerchantWrapper::deleteReview($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionorders_status_list()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = FoodItemWrapper::merchantStatusList($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['stats_id'],
				  'name'=>$val['description'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage(''),
				  'status'=>' ',
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionOrderStatusGet()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("order_status","stats_id=:stats_id",array(
			 ':stats_id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['stats_id'],
			  'description'=>$resp['description'],			  
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"order_status_form.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionAddOrderStatus()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'description'=>isset($this->data['description'])?$this->data['description']:'',		  		  
		  'date_created'=>FunctionsV3::dateNow(),
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);			
		try {
			
			if($id>0){
			  unset($params['date_created']);
			}
						
			FoodItemWrapper::inserrOrderStatus($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionDeleteOrderStatus()
	{
	    $merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
								
			FoodItemWrapper::deleteOrderStatus($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetOrderStatusList()
	{
		$merchant_id = $this->validateToken();		
		if ($resp = OrderWrapper::orderStatusList($merchant_id)){
			array_unshift($resp , array(
			  'value'=>"",
			  'label'=>translate("Please select")
			));			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_status_list',
			  'selected'=>getOption($merchant_id,'default_order_status'),
			  'data'=>$resp
			);
		} else $this->msg = translate("No results");
		$this->output();
	}
	
	public function actionSavedOrderStatusSettings()
	{
		$merchant_id = $this->validateToken();
		
		Yii::app()->functions->updateOption('default_order_status',
		isset($this->data['default_order_status'])?$this->data['default_order_status']:''
		,$merchant_id);				
		
		$this->code = 1;
		$this->msg = translate("Setting saved");
		$this->details = array();		
		$this->output();
	}
	
	public function actionListAddon()
	{		
		$merchant_id = $this->validateToken();
		if ($resp = FoodItemWrapper::getAddonCategory($merchant_id)){						
			$data = FoodItemWrapper::dropdownFormat($resp,'subcat_id','subcategory_name',true);			
			$this->code = 1; $this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_addon_list',			  
			  'data'=>$data
			);					
		} else {
			$this->msg = translate("No results");			
			$this->details = array(
			  'next_action'=>'silent'			
			);					
		}	
		$this->output();
	}
	
	public function actionGetAddonDetails()
	{		
		$merchant_id = $this->validateToken();
		$addon_id  = isset($this->data['addon_id'])?(integer)$this->data['addon_id']:0;	
		$item_id = isset($this->data['item_id'])?(integer)$this->data['item_id']:0;	
				
		if ( !FoodItemWrapper::getAddonItemView($merchant_id,$addon_id)){
			FoodItemWrapper::migrateDataAddon($merchant_id,$addon_id);			
		}
		
		if ( $resp = FoodItemWrapper::getAddonItemView($merchant_id,$addon_id)){
			 $category_name = $resp[0]['subcategory_name'];
			 $category_id = $resp[0]['subcat_id'];
			 $data = array();
			 foreach ($resp as $val) {			 	
			 	$val['name_with_price'] = translate("[name] ([price])",array(
			 	  '[name]'=>$val['sub_item_name'],
			 	  '[price]'=>FunctionsV3::prettyPrice($val['price'])
			 	));
			 	$data[]=$val;
			 }
			$this->code = 1; $this->msg = "OK";
			
			$selected_data = array(); 
			if($item_id>0){				
				$data2 = FoodItemWrapper::getItem($merchant_id,$item_id);		

				
				$addon_item = !empty($data2['addon_item'])?json_decode($data2['addon_item'],true):'';
				$multi_option = !empty($data2['multi_option'])?json_decode($data2['multi_option'],true):'';
				$multi_option_value = !empty($data2['multi_option_value'])?json_decode($data2['multi_option_value'],true):'';
				$require_addon = !empty($data2['require_addon'])?json_decode($data2['require_addon'],true):'';
				$two_flavors_position = !empty($data2['two_flavors_position'])?json_decode($data2['two_flavors_position'],true):'';
							
				$selected_data['addon_item'] = isset($addon_item[$addon_id])?$addon_item[$addon_id]:'';	
				$selected_data['multi_option'] = isset($multi_option[$addon_id])?$multi_option[$addon_id]:'';	
				$selected_data['multi_option_value'] = isset($multi_option_value[$addon_id])?$multi_option_value[$addon_id]:'';								
				$selected_data['require_addon'] = isset($require_addon[$addon_id])?$require_addon[$addon_id]:'';	
				$selected_data['two_flavors'] = $data2['two_flavors']==2?1:0;
				$selected_data['two_flavors_position'] = isset($two_flavors_position[$addon_id])?$two_flavors_position[$addon_id]:'';	
				
			}	
						
			$this->details = array(
			  'next_action'=>'addon_details',			  
			  'category_name'=>$category_name,
			  'category_id'=>$category_id,
			  'data'=>$data,
			  'selected_data'=>$selected_data
			);			
			//dump($this->details);
		} else $this->msg = translate("No results");	
		$this->output();
	}
	
	public function actionsales_report()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        
        $start_date  = isset($this->data['start_date'])?trim($this->data['start_date']):'';
        $end_date  = isset($this->data['end_date'])?trim($this->data['end_date']):'';
        $order_status  = isset($this->data['order_status'])?$this->data['order_status']:'';
        $pull_hook  = isset($this->data['pull_hook'])?$this->data['pull_hook']:'';
        
        if(!empty($search_string)){
        	$refresh=1;
        }
                
        
        if ($resp = ReportsWrapper::salesReport($merchant_id,$page,$page_limit,$search_string,
        $start_date,$end_date,$order_status)){
        	$data = array();
        	
        	foreach ($resp as $val) {
        		$val['status']=t($val['status']);
        		
        		$val['payment_type'] = FunctionsV3::prettyPaymentType('payment_order',$val['payment_type_raw'],
        		$val['order_id'],$val['trans_type_raw']);
        		
        		$val['trans_type']=t($val['trans_type']);
        		$val['order_number']= translate("Order No. #[order_id]",array( 
        		  '[order_id]'=>$val['order_id']
        		));
        		$val['total_amount'] = FunctionsV3::prettyPrice($val['total_amount']);
        		$val['date_created'] = OrderWrapper::prettyDateTime($val['date_created']);
        		$data[]=$val;
        	}
        	        	
        	$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_report',
			 'report_type'=>'sales_report',
			 'refresh'=>$refresh,
			 'pull_hook'=>$pull_hook,
			 'data'=>$data
			);				
        } else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'pull_hook'=>$pull_hook,
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
        		
		$this->output();
	}
	
	public function actionOrderStatusList()
	{
		$merchant_id = $this->validateToken();
		
		$page_limit = 15;
						
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        
		if ($resp = OrderWrapper::orderStatusListPaginate($merchant_id,$page,$page_limit)){					
			$data = array();
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['value'],
				  'name'=>$val['label']
				);
			}
			
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'display_selected',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else $this->msg = translate("No results");
		}
		$this->output();
	}
	
	public function actionsales_summary_report()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        
        $start_date  = isset($this->data['start_date'])?trim($this->data['start_date']):'';
        $end_date  = isset($this->data['end_date'])?trim($this->data['end_date']):'';
        $order_status  = isset($this->data['order_status'])?$this->data['order_status']:'';
        $pull_hook  = isset($this->data['pull_hook'])?$this->data['pull_hook']:'';
        
        if(!empty($search_string)){
        	$refresh=1;
        }
                
        
        if ($resp = ReportsWrapper::salesSummaryReport($merchant_id,$page,$page_limit,$search_string,
        $start_date,$end_date,$order_status)){
        	        
        	$data = array();
        	
        	foreach ($resp as $val) {        		
        		$total_amount = (float)$val['total_qty']*(float)$val['price_raw'];
        		$val['price'] = FunctionsV3::prettyPrice($val['price']);
        		$val['total_amount'] = FunctionsV3::prettyPrice($total_amount);
        		$data[]=$val;
        	}
        	        	
        	$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_report',
			 'report_type'=>'sales_summary_report',
			 'refresh'=>$refresh,
			 'pull_hook'=>$pull_hook,
			 'data'=>$data
			);							
        } else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh,
				  'pull_hook'=>$pull_hook,
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
        		
		$this->output();
	}
	
	public function actionbooking_report()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        
        $start_date  = isset($this->data['start_date'])?trim($this->data['start_date']):'';
        $end_date  = isset($this->data['end_date'])?trim($this->data['end_date']):'';
        $order_status  = isset($this->data['order_status'])?$this->data['order_status']:'';
        
        if(!empty($search_string)){
        	$refresh=1;
        }
                
        
        if ($resp = ReportsWrapper::bookingSummary($merchant_id,$page,$page_limit,$search_string,
        $start_date,$end_date,$order_status)){
        	        
        	$data = array();
        	        	
        	foreach ($resp as $val) {        		
        		$val['total_approved'] = translate("Total approved : [total]",array(
        		   '[total]'=>$val['total_approved']+0
        		));
        		$val['total_denied'] = translate("Total denied : [total]",array(
        		   '[total]'=>$val['total_denied']+0
        		));
        		$val['total_pending'] = translate("Total pending : [total]",array(
        		   '[total]'=>$val['total_pending']+0
        		));
        		$data[]=$val;
        	}
        	        	
        	$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_report',
			 'report_type'=>'booking_report',
			 'refresh'=>$refresh,
			 'data'=>$data
			);								
        } else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
        		
		$this->output();
	}
	
	public function actiongetSMSAlertSettings()
	{
		$merchant_id = $this->validateToken();		
		$settings[]=array(
		  'option_name'=>"sms_notify_number",
		  'option_value'=>getOption($merchant_id,'sms_notify_number')
		);
		
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		  'next_action'=>"set_form_options",
		  'data'=>$settings
		);
		$this->output();
	}
	
	public function actionSaveSMSAlertSettings()
	{
		
		$merchant_id = $this->validateToken(); $settings = array();		

		Yii::app()->functions->updateOption('sms_notify_number',
				isset($this->data['sms_notify_number'])?$this->data['sms_notify_number']:''
				,$merchant_id);	
		
		$this->code = 1;
		$this->msg = translate("Setting saved");
		$this->details = array();		
		$this->output();
	}
	
	public function actionAddCard()
	{
		$merchant_id = $this->validateToken();
		$p = new CHtmlPurifier();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;
		
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'card_name'=>$p->purify($this->data['card_name']),
		  'credit_card_number'=>FunctionsV3::maskCardnumber($p->purify($this->data['credit_card_number'])),
		  'expiration_month'=>isset($this->data['expiration_month'])?$this->data['expiration_month']:'',
		  'expiration_yr'=>isset($this->data['expiration_yr'])?$this->data['expiration_yr']:'',
		  'cvv'=>isset($this->data['cvv'])?$this->data['cvv']:'',
		  'billing_address'=> isset($this->data['billing_address'])? $p->purify($this->data['billing_address']) :'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		
		try {
    	   $params['encrypted_card']=CreditCardWrapper::encryptCard(trim($this->data['credit_card_number']));
    	} catch (Exception $e) {
    		$this->msg = Yii::t("default","Caught exception: [error]",array(
						    '[error]'=>$e->getMessage()
						  ));
    		$this->output();
    	}
	    	
		try {
			
			MerchantWrapper::addCards($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}
					
		$this->output();
	}
	
	public function actionCardList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = MerchantWrapper::getAllCards($merchant_id,$page,$page_limit,$search_string)){			
			$resp = Yii::app()->request->stripSlashes($resp);
			
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['mt_id'],
				  'name'=>$val['card_name'],
				  'description'=>$val['credit_card_number'],
				  'thumbnail'=>FoodItemWrapper::getImage('','credit-card.png'),
				  'status'=>'',
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionCardDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			MerchantWrapper::deleteCard($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}				
		$this->output();
	}
	
	public function actionCardGetByID()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("merchant_cc","mt_id=:mt_id",array(
			 ':mt_id'=>$id
			),false);
			
			
			$decryp_card = isset($resp['credit_card_number'])?$resp['credit_card_number']:'';		
			if(isset($resp['encrypted_card'])){				
			   $decryp_card = CreditCardWrapper::decryptCard($resp['encrypted_card']);
		    }
			
			$data = array(
			  'id'=>$resp['mt_id'],
			  'card_name'=>$resp['card_name'],			  			  
			  'credit_card_number'=>$decryp_card,
			  'expiration_month'=>$resp['expiration_month'],
			  'expiration_yr'=>$resp['expiration_yr'],
			  'cvv'=>$resp['cvv'],
			  'billing_address'=>$resp['billing_address'],
			);
								
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"add_card.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionGetReviewByID()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		
		
		try {
								
			$resp = FoodItemWrapper::getData("review","id=:id",array(
			 ':id'=>$id
			));
						
			$data = array(
			  'id'=>$resp['id'],
			  'review'=>$resp['review'],			  
			  'status'=>$resp['status']
			);
						
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"review_form_reply.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}	
		$this->output();	
	}
	
	public function actionAddReviewReply()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;
		$reply= isset($this->data['reply'])?$this->data['reply']:'';
		
		if(empty($reply)){
		  $this->msg = translate("Comments is required");
		  $this->output();		
		}	
		
		$stmt = "
		SELECT a.id,a.merchant_id,
		b.restaurant_name
		FROM {{review}} a
		LEFT JOIN {{merchant}} b
		ON
		a.merchant_id = b.merchant_id
		
		WHERE 
		a.id = ".q($id)."
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			$res['restaurant_name'] = Yii::app()->request->stripSlashes($res['restaurant_name']);
			$params = array(  
			  'parent_id'=>$id,
			  'review'=>$reply,
			  'date_created'=>FunctionsV3::dateNow()
			);
			
			try {
						
				MerchantWrapper::addReview($merchant_id,$params,'');
				$this->code = 1;
				$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
				$this->details = array(
				  'next_action'=>'pop_form'			  
				);
				
			} catch (Exception $e) {			
				$this->msg = translate($e->getMessage());			
			}		
		} else $this->msg = translate("Record not found");
		
		$this->output();
	}

	public function actionAddBroadcast()
	{
		$merchant_id = $this->validateToken();
		
	
		$id = '';
		$params = array(
		  'push_title'=>isset($this->data['push_title'])?$this->data['push_title']:'',
		  'push_message'=>isset($this->data['push_message'])?$this->data['push_message']:'',
		  'device_platform'=>"/topics/broadcast",
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
		  'merchant_id'=>$merchant_id,
		  'fcm_version'=>1
		);
		
		if(empty($params['push_message'])){
			$this->msg = translate("Message is required");
			$this->output();
		}	
				
		try {

			MerchantWrapper::verifyLastBroadcast($merchant_id);
			
			MerchantWrapper::addBroadcast($merchant_id,$params,'');
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
	
		$this->output();
	}
	
	public function actionPushBroadcastList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = MerchantWrapper::PushBroadcastList($merchant_id,$page,$page_limit,$search_string)){			
						
			$data = array();$x=0;
			foreach ($resp as $val) {				
				$data[] = array(
				  'id'=>$val['broadcast_id'],
				  'name'=>Yii::app()->request->stripSlashes($val['push_title']),
				  'description'=>Yii::app()->request->stripSlashes($val['push_message']),
				  'thumbnail'=>FoodItemWrapper::getImage('','chatting.png'),
				  'status'=>t($val['status']),
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created'])
				);				
				$x++;
			}
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionPrintThermal()
	{		
		$merchant_id = $this->validateToken();
		$order_id = isset($this->data['id'])?(integer)$this->data['id']:0;				

		try {
			
			PrinterWrapper::verifyLastPrint($merchant_id,$order_id);			
			PrintWrapper::doPrint($order_id,'merchant');
			
			$this->code = 1;
			$this->msg = translate("Print request has been sent");
			$this->details='';
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}			
		$this->output();
	}
	
	public function actionDriverList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $order_id  = isset($this->data['order_id'])?(integer)$this->data['order_id']:0;
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }

        $merchant_lat = 0;
        $merchant_lng = 0;
        
        $unit = getOption($merchant_id,'merchant_distance_type');
        $pretty_unit = MapsWrapperTemp::prettyUnit($unit);
        
        try {
        	$orders = OrderWrapper::getOrderLocation($order_id);        	
        	$merchant_lat = $orders['merchant_lat'];
        	$merchant_lng = $orders['merchant_lng'];
        } catch (Exception $e) {
        	//echo $e->getMessage();
        }
        
        $path_to_upload=Yii::getPathOfAlias('webroot')."/upload/driver";
              
		if ($resp = DriverWrapper::ListRider($merchant_id,$page,$page_limit,$search_string,$merchant_lat,$merchant_lng,$unit)){			
											
			$data = array();$x=0;
			foreach ($resp as $val) {		
				
				/*$thumbnail = FoodItemWrapper::getImage('','profile@2x.png');
				if(!empty($val['profile_photo'])){
					$profile_photo_path=$path_to_upload."/".$val['profile_photo'];
					if(file_exists($profile_photo_path)){
		    			$thumbnail=websiteUrl()."/upload/driver/".$val['profile_photo'];
		    		}
				}*/		
				$thumbnail = DriverWrapper::driverPhotoUrl( $val['profile_photo'] );

				$description = "";
				if(!empty($val['transport_type_id'])){				
					$description.= ucwords(translate($val['transport_type_id']))."<br/>";
				}
				if(!empty($val['team_name'])){				
					$description.= translate("([team_name])",array(
					  '[team_name]'=>ucwords($val['team_name'])
					)) ."<br/>";
				}
				if(!empty($val['location_lat'])){
				   $description.= MapsWrapperTemp::prettyDistance($val['distance'],$pretty_unit);
				}
				
				$data[] = array(
				  'id'=>$val['driver_id'],
				  'name'=>Yii::app()->request->stripSlashes($val['driver_name']),
				  'description'=>$description,
				  'thumbnail'=>$thumbnail,
				  'status'=>'',
				  'date_created'=>'', 
				  'item_status'=> (integer)$val['online_status']>0?"available":'disabled'
				);				
				$x++;
			}						
												
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);			
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionAssignOrder()
	{
		if(!Yii::app()->db->schema->getTable("{{driver_task}}")){
			$this->msg = translate("Driver addon database table is missing");
			$this->output();
		}
		
		$merchant_id = $this->validateToken();
		$driver_id = isset($this->data['driver_id'])?(integer)$this->data['driver_id']:0;
		$order_id = isset($this->data['order_id'])?(integer)$this->data['order_id']:0;
		$team_id = 0; $task_id = 0;
		
		$driver_info = DriverWrapper::getDriverInfo($driver_id);
		$team_id = isset($driver_info['team_id'])?$driver_info['team_id']:'';
		$driver_name = isset($driver_info['driver_name'])?$driver_info['driver_name']:'';
		
		if ( $task = DriverWrapper::getTaskByOrderID($order_id)){
			$task_id = (integer)$task['task_id'];
			$old_driver = (integer)$task['driver_id'];

			if($old_driver>0){
				if($old_driver==$driver_id){
					$this->msg = translate("Order#[order_id] is already assign to [driver_name]",array(
					 '[order_id]'=>$order_id,
					 '[driver_name]'=>$driver_name
					));
					$this->output();
				}			
			}
			
			$params = array(
			  'status'=>'assigned',
			  'driver_id'=>$driver_id,
			  'team_id'=>$team_id,
			  'date_created'=>FunctionsV3::dateNow(),
			);
			$up = Yii::app()->db->createCommand()->update("{{driver_task}}",$params,
	  	    'task_id=:task_id',
		  	    array(
		  	      ':task_id'=>$task_id
		  	    )
	  	    );
	  	    
	  	    $this->code = 1;
	  	    $this->msg = translate("Order#[order_id] has been assigned to [driver_name]",array(
	  	      '[order_id]'=>$order_id,
	  	      '[driver_name]'=>$driver_name
	  	    ));
	  	    $this->details = array(
			  'next_action'=>'pop_form_assign'			  
			);
		} else {
		    // INSERTT AS NEW TASK
		    if( $order = OrderWrapper::getReceiptByID($order_id) ){
		    	
		    	$user_type='merchant'; $user_id = $order['merchant_id'];
		    	$driver_owner_task = getOptionA('driver_owner_task');
		    	if($driver_owner_task=="admin"){
		    		$user_type='admin'; $user_id = DriverWrapper::getAdminID();
		    	}		    
		    	
		    	$drop_address = '';
		    	try {
		    		$merchant = MerchantWrapper::getMerchantInformation($order['merchant_id']);		    		
		    		$drop_address = $merchant['street']." ".$merchant['city']." ".$merchant['state'];
		    		$drop_address.=" ".$merchant['post_code']." ".$merchant['country_code'];
		    	} catch (Exception $e) {
				    //
				}
		    	
		    	$params = array(
		    	  'order_id'=>(integer)$order_id,
		    	  'user_type'=>$user_type,
		    	  'user_id'=>(integer)$user_id,
		    	  'trans_type'=>isset($order['trans_type'])?$order['trans_type']:'',
		    	  'contact_number'=>isset($order['customer_phone'])?$order['customer_phone']:'',
		    	  'email_address'=>isset($order['customer_email'])?$order['customer_email']:'',
		    	  'customer_name'=>isset($order['full_name'])?$order['full_name']:'',
		    	  'delivery_date'=>$order['delivery_date']." ".$order['delivery_time'],
		    	  'delivery_address'=>isset($order['full_address'])?$order['full_address']:'',
		    	  'delivery_address'=>isset($order['full_address'])?$order['full_address']:'',
		    	  'team_id'=>(integer)$team_id,
		    	  'driver_id'=>(integer)$driver_id,
		    	  'task_lat'=>isset($order['location_lat'])?$order['location_lat']:'',
		    	  'task_lng'=>isset($order['location_lng'])?$order['location_lng']:'',
		    	  'status'=>"assigned",
		    	  
		    	  'dropoff_merchant'=>isset($merchant['merchant_id'])?(integer)$merchant['merchant_id']:0,
		    	  'dropoff_contact_name'=>isset($merchant['contact_name'])?$merchant['contact_name']:'',
		    	  'dropoff_contact_number'=>isset($merchant['contact_phone'])?$merchant['contact_phone']:'',
		    	  'drop_address'=>$drop_address,
		    	  'dropoff_lat'=>isset($merchant['latitude'])?$merchant['latitude']:'',
		    	  'dropoff_lng'=>isset($merchant['lontitude'])?$merchant['lontitude']:'',
		    	  
		    	  'date_created'=>FunctionsV3::dateNow(),
		    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
		    	);
		    	
		    	try {
		    		
			    	Yii::app()->db->createCommand()->insert("{{driver_task}}",$params);	
			    	$task_id=Yii::app()->db->getLastInsertID();
			    				    	
			    	$this->code = 1;
			  	    $this->msg = translate("Order#[order_id] has been assigned to [driver_name]",array(
			  	      '[order_id]'=>$order_id,
			  	      '[driver_name]'=>$driver_name
			  	    ));
			  	    $this->details = array(
					  'next_action'=>'pop_form_assign'			  
					);
				} catch (Exception $e) {
				    $this->msg = $e->getMessage();
				}
		    			    	
		    } else $this->msg = translate("Order information not found");
		}	
		
		/*SEND NOTIFICATION*/
		if($task_id>0){
			if($res=Driver::getTaskId( (integer) $task_id )){
				$assigned_task='assigned';
				$status_pretty = Driver::prettyStatus($res['status'],$assigned_task);
				
				$remarks_args=array(
				  '{from}'=>$res['status'],
				  '{to}'=>$assigned_task
				);
				$params_history=array(
				  'order_id'=>$res['order_id'],
				  'remarks'=>$status_pretty,
				  'status'=>$assigned_task,
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				  'task_id'=>$task_id,
				  'remarks2'=>"Status updated from {from} to {to}",
				  'remarks_args'=>json_encode($remarks_args)
				);		
				Yii::app()->db->createCommand()->insert("{{order_history}}",$params_history);
				
				/*send notification to driver*/
		         Driver::sendDriverNotification('ASSIGN_TASK',$res);
		         		         
		         Yii::app()->db->createCommand("DELETE FROM {{driver_assignment}}
		         WHERE task_id = ".q($task_id)."
		          ")->query();
			}
		}	
				
		$this->output();
	}
	
	public function actiongetDriverSettings()
	{
		$merchant_id = $this->validateToken();
		
		if (FunctionsV3::hasModuleAddon('driver')){
			
			$transport_type = Driver::transportType();		
			if($team_list = Driver::teamListNormal( 'merchant', $merchant_id  )){
				$team_list=Driver::toList($team_list,'team_id','team_name',
               translate("Please select a team from a list") );
			}		
									
			if(!is_array($team_list)){
				$team_list['']=translate("Please select a team from a list");
			}		
						
			$data = array(
			  'transport_type'=>(array)$transport_type,
			  'status'=>(array)Driver::driverStatus(),
			  'team_list'=>(array)$team_list
			);
			
			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'set_driver_settings',
			  'settings'=>$data
			);
												
		} else {
			$this->msg = "modules not installed";
			$this->details = array(
			  'next_action'=>'silent'			  
			);
		}				
		$this->output();
	}

	public function actionAddDriver()
	{
		if(!Yii::app()->db->schema->getTable("{{driver}}")){
			$this->msg = translate("Driver addon database table is missing");
			$this->output();
		}
		
		$merchant_id = $this->validateToken();
		
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;
		$last_login = date("Y-m-d H:i:s", strtotime("-10 minutes"));
		
		$params=array(		 
		  'user_type'=>"merchant",
		  'user_id'=>$merchant_id,
		  'first_name'=>isset($this->data['first_name'])?$this->data['first_name']:'',
		  'last_name'=>isset($this->data['last_name'])?$this->data['last_name']:'',
		  'email'=>isset($this->data['email'])?$this->data['email']:'',
		  'phone'=>isset($this->data['phone'])?$this->data['phone']:'',
		  'username'=>isset($this->data['username'])?$this->data['username']:'',
		  'password'=>isset($this->data['password'])?md5($this->data['password']):'',
		  'team_id'=>isset($this->data['team_id'])?$this->data['team_id']:'',
		  'transport_type_id'=>isset($this->data['transport_type_id'])?$this->data['transport_type_id']:'',
		  'transport_description'=>isset($this->data['transport_description'])?$this->data['transport_description']:'',
		  'licence_plate'=>isset($this->data['licence_plate'])?$this->data['licence_plate']:'',
		  'color'=>isset($this->data['color'])?$this->data['color']:'',
		  'status'=>isset($this->data['status'])?$this->data['status']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
		  'profile_photo'=>isset($this->data['profile_photo'])?$this->data['profile_photo']:'',
		  'last_login'=>$last_login
		);				
		
		if ( Driver::getDriverByUsername($this->data['username'],$id)){
			$this->msg=translate("Username already exist");
			$this->output();
		}	
		
		if ( Driver::getDriverByEmail($this->data['email'],$id)){
			$this->msg = translate("Email already exist");
			$this->output();
		}			

		$Validator = new Validator;
		$Validator->email(array(
		 'email'=>translate("Invalid email")
		), $this->data);
		
		if(!$Validator->validate()){			
			$this->msg = translate("Invalid email");
			$this->output();
		}	
		
		try {
			
			if($id>0){
			  unset($params['date_created']);
			  unset($params['last_login']);
			} else unset($params['date_modified']);
						
			DriverWrapper::addDriver($merchant_id,$params,$id);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {
			$this->msg = translate($e->getMessage());
		}
					
		$this->output();
	}
	
	public function actiongetDriverProfile()
	{
		$merchant_id = $this->validateToken();
		$driver_id = isset($this->data['driver_id'])?(integer)$this->data['driver_id']:0;
				
		try {
			/*$resp = FoodItemWrapper::getData("driver ","driver_id=:driver_id",array(
			 ':driver_id'=>$driver_id
			));*/
			
			$resp = DriverWrapper::getDriverInfos($driver_id);			
			
			$data = array(			  
			  'full_name'=>$resp['driver_name'],
			  'email'=>$resp['email'],
			  'phone'=>$resp['phone'],
			  'vehicle'=>$resp['transport_type_id'],
			  'transport_description'=>$resp['transport_description'],
			  'licence_plate'=>$resp['licence_plate'],
			  'color'=>$resp['color'],
			  'profile_photo'=>DriverWrapper::driverPhotoUrl($resp['profile_photo']),
			  'team_name'=>$resp['team_name']
			);
			$this->code = 1; $this->msg = "ok";
			$this->details = array(
			  'next_action'=>'fill_driver_profile',
			  'data'=>$data
			);			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionrefresh_ready_order()
	{
		$order_ids = '';
		$merchant_id = $this->validateToken();		
		MerchantWrapper::setMerchantTimezone($merchant_id);
				
		if(empty($this->data['order_id'])){		   			
			//
		} else {			
			$id = isset($this->data['order_id'])?explode(",",$this->data['order_id']):'';	
			if(is_array($id) && count($id)>=1){
				foreach ($id as $val_id) {
					$order_ids.= q($val_id).",";
				}
				$order_ids = substr($order_ids,0,-1);
			} 
		}
				
		$need_refresh = false;
		
		if($resp = OrderWrapper::getUpdatedReadyOrder($order_ids, $merchant_id )){
		  $this->msg = 1;
		  $need_refresh = true;		  
		}
					
		if($need_refresh){
		   $this->code = 1;		   
		   $this->details = array(
		     'next_action'=>"refresh_ready_tab",
		     'datenow'=>date('c')
		   );
		} else {
		   $this->details = array(
		     'next_action'=>"silent",
		     'datenow'=>date('c')
		   );
		}			
		$this->output();
	}
	
	public static function actionlogDevice()
	{
		Yii::app()->functions->createLogs("bluetooth",'');
	}
	
	public function actionprinterList()
	{
		$merchant_id = $this->validateToken();
				
		$page_limit = MerchantWrapper::paginateLimit();
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $refresh  = isset($this->data['refresh'])?(integer)$this->data['refresh']:0;
        $search_string  = isset($this->data['s'])?trim($this->data['s']):'';
        if(!empty($search_string)){
        	$refresh=1;
        }
               					        
		if ($resp = Bluetooth::getPrinterList($merchant_id,$this->device_uiid,$page,$page_limit,$search_string)){						
			$data = array();$x=0;
			foreach ($resp as $val) {				
				
				$item_status='';
				if($val['auto_print']==1){
					$item_status='available';
				}
				if($val['auto_print_after_accepted']==1){
					$item_status='out_of_stock';
				}			
				
				$data[] = array(
				  'id'=>$val['id'],
				  'name'=>$val['printer_name'],
				  'description'=>'',
				  'thumbnail'=>FoodItemWrapper::getImage('','printer.png'),
				  'status'=>'',
				  'date_created'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
				  'item_status'=>$item_status
				);				
				$x++;
			}
														
			$this->code = 1;
			$this->msg = "OK";			
			$this->details = array(
			 'next_action'=>'set_list_column',
			 'refresh'=>$refresh,
			 'data'=>$data
			);						
		} else {
			if(isset($this->data['page'])){
				$this->code = 1;
				$this->details = array(
				  'next_action'=>'end_of_list',
				  'refresh'=>$refresh
				);
			} else {
				$this->code = 1;
				$this->msg = translate("No results");
				$this->details = array(
				  'next_action'=>'clear_list',
				  'is_search'=>!empty($search_string)?true:false
				);
			}
		}
		$this->output();
	}
	
	public function actionAddPrinter()
	{
	    $merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;					
		$params = array(
		  'merchant_id'=>$merchant_id,
		  'device_uiid'=>$this->device_uiid,
		  'printer_name'=>isset($this->data['printer_name'])?$this->data['printer_name']:'',
		  'bluetooth_printer_name'=>isset($this->data['bluetooth_printer_name'])?$this->data['bluetooth_printer_name']:'',
		  'interface_type'=>isset($this->data['interface_type'])?$this->data['interface_type']:'',
		  'mac_address'=>isset($this->data['mac_address'])?$this->data['mac_address']:'',
		  'data1'=>isset($this->data['data1'])?$this->data['data1']:'',
		  'data2'=>isset($this->data['data2'])?$this->data['data2']:'',
		  'paper_width'=>isset($this->data['paper_width'])?$this->data['paper_width']:'',
		  'auto_print'=>isset($this->data['auto_print'])?$this->data['auto_print']:'',
		  'auto_print_after_accepted'=>isset($this->data['auto_print_after_accepted'])?$this->data['auto_print_after_accepted']:'',
		  'char_set'=>isset($this->data['char_set'])?$this->data['char_set']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		
		if(empty($params['printer_name'])){
			$this->msg = translate("Printe name is required");
			$this->output();
		}
		
		if(empty($params['mac_address'])){
			$this->msg = translate("Please select printer");
			$this->output();
		}
				
		try {
									
			Bluetooth::insertPrinter($merchant_id,$params,$id,$this->device_uiid);
			$this->code = 1;
			$this->msg = $id>0?translate("Succesfully updated"):translate("Successful");
			$this->details = array(
			  'next_action'=>'pop_form'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionprinterGet()
	{
		$merchant_id = $this->validateToken();
		$id = isset($this->data['id'])?(integer)$this->data['id']:0;		
		try {
						
			$resp = FoodItemWrapper::getData("printer_list_new","id=:id",array(
			 ':id'=>$id
			));
			
						
			$data = array(
			  'id'=>$resp['id'],
			  'printer_name'=>$resp['printer_name'],
			  'bluetooth_printer_name'=>$resp['bluetooth_printer_name'],
			  'interface_type'=>$resp['interface_type'],
			  'mac_address'=>$resp['mac_address'],
			  'paper_width'=>$resp['paper_width'],
			  'auto_print'=>$resp['auto_print'],
			  'auto_print_after_accepted'=>$resp['auto_print_after_accepted'],
			  'data1'=>$resp['data1'],
			  'data2'=>$resp['data2'],
			  'char_set'=>$resp['char_set']
			);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'fill_form',
			  'form_id'=>"printer_add.html",
			  'data'=>$data
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actionprinterDelete()
	{
		$merchant_id = $this->validateToken();		
		$id = isset($this->data['id'])?(array)$this->data['id']:0;
		try {
						
			Bluetooth::deletePrinter($merchant_id,$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>'list_reload'			  
			);
			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		$this->output();
	}
	
	public function actiongetPrinterOptions()
	{
		$merchant_id = $this->validateToken(); $data = array();
		$order_id = isset($this->data['order_id'])?(integer)$this->data['order_id']:'';		
		$data[0] = array(
		  'name'=> translate("FP-80WC 80mm WIFI printer"),
		  'id'=>-1,
		  'order_id'=>$order_id
		);
		if ( $res = Bluetooth::getPrinterList($merchant_id,$this->device_uiid,0,100)){
			foreach ($res as $val) {				
				$data[] = array(
				  'name'=>$val['printer_name'],
				  'id'=>$val['id'],
				  'order_id'=>$order_id
				);
            }		            
		} 
		
		$data[-1] = array(
		  'name'=> translate("Add new printer"),
		  'id'=>-2,
		  'order_id'=>$order_id
		);
		
		$this->code = 1; $this->msg = "OK";
		$this->details = array(
		  'next_action'=>'show_printer_list_options',
		  'data'=>$data
		);
		$this->output();
	}
	
	public function actionPrintOrder()
	{		
		$merchant_id = $this->validateToken(); $data = array();
		$order_id = isset($this->data['order_id'])?(integer)$this->data['order_id']:'';
		$printer_id = isset($this->data['printer_id'])?(integer)$this->data['printer_id']:'';
		
		$prefix="print_"; 
				
		try {
			
			$printer_data = Bluetooth::getPrinter($printer_id);
			$order_details = OrderWrapper::prepareReceipt($order_id,false);						
			$print_opts = Bluetooth::getPrinterOptions($merchant_id);
			$print_opts_data = Bluetooth::printerOptionsData();
			
			if($order_details){
				foreach ($order_details['order_data'] as $key=>$val) {					
					$pre = $prefix."".$key;						
					if(!array_key_exists($pre,(array)$print_opts)){
						if(in_array($pre,(array)$print_opts_data)){
						   //$order_details['order_data'][$key]='';
						   unset($order_details['order_data'][$key]);
						}
					}				
				}				
			}		
									
			$footer = array();
			if(isset($print_opts['print_footer'])){
				$print_footer = explode("\r\n",$print_opts['print_footer']);
				if(is_array($print_footer)){
					$footer = $print_footer;
				}
			}		
			
			$now  = date("c");
			$data = array(
			  'printer_custom_data'=> array(
			    'printed_date'=> array_key_exists($prefix."printed_date",$print_opts)? FunctionsV3::prettyDate($now)." ".FunctionsV3::prettyTime($now) :'',
			    'site_url'=> array_key_exists($prefix."site_url",$print_opts)?FunctionsV3::prettyUrl($_SERVER['HTTP_HOST']):'',
			    'footer'=>array_key_exists($prefix."footer",$print_opts)?$footer:''
			  ) ,
			  'printer_data'=>$printer_data,
			  'order_details'=>$order_details
			);
			
			/*INSERT LOGS*/
			$auto_print = isset($this->data['auto_print'])?$this->data['auto_print']:'';
			if($auto_print==1){
				Yii::app()->db->createCommand()->insert("{{printer_auto_print}}", array(
				  'merchant_id'=>$merchant_id,
				  'device_uiid'=>$this->device_uiid,
				  'order_id'=>$order_id,
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				));
			}
		    			
			$this->code = 1; $this->msg = "OK";			
			$this->details = array(
			  'next_action'=>'print_order',		
			  'data'=>$data
			);
		    			
		} catch (Exception $e) {			
			$this->msg = translate($e->getMessage());			
		}		
		
		$this->output();
	}
	
	public function actionAutoPrint()
	{
		$merchant_id = $this->validateToken(); 		
		$order_id = isset($this->data['order_id'])?(integer)$this->data['order_id']:'';
		$stmt="
		SELECT a.id as printer_id,
		
		(
		 select order_id
		 from {{order}} 
		 where order_id=".q($order_id)."	
		 and order_id not in (
		   select order_id from {{printer_auto_print}}
		   where order_id=".q($order_id)."
		   and
		   device_uiid=".q($this->device_uiid)."
		 ) 
		) as order_id
		
		FROM {{printer_list_new}} a
		WHERE
		a.merchant_id=".q($merchant_id)."
		AND 
		device_uiid=".q($this->device_uiid)."
		AND
		a.auto_print=1		
		order by a.date_created DESC
		LIMIT 0,1
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
			if($res['printer_id']>0 && $res['order_id']>0){
				$this->code = 1; 
				$this->msg = "ok";
				$this->details = array(
				   'next_action'=>"auto_print_receipt",
				  'data'=>$res
				);
				$this->output();
			}
		} 
		
		$this->msg = "no results";
	   $this->details = array(
		   'next_action'=>"silent"			 
		);			
		$this->output();
	}
	
	public function actionupdateCloseStore()
	{
		$merchant_id = $this->validateToken(); 
		$value = isset($this->data['value'])?$this->data['value']:'';		
		$close_store = isset($this->data['value'])?(integer)$this->data['value']:0;
		$value = $value==1?"yes":'';
		
		Yii::app()->functions->updateOption('merchant_close_store',$value,$merchant_id);
		
		$params = array(
		  'close_store'=>$close_store,
		  'date_modified'=>FunctionsV3::dateNow(),
	      'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
        Yii::app()->db->createCommand()->update("{{merchant}}",$params,
  	    'merchant_id=:merchant_id',
	  	    array(
	  	      ':merchant_id'=>$merchant_id
	  	    )
  	    );
				
		$this->code = 1;
		$this->msg = $value=="yes"?translate("Your store is now close"):translate("Your store is now open");
		$this->output();
	}
	
	public function actionupdateDarkMode()
	{
		$merchant_id = $this->validateToken(); 
		$value = isset($this->data['value'])?$this->data['value']:'';		
		$stic_dark_theme = isset($this->data['value'])?(integer)$this->data['value']:0;
		$value = $value==1?"yes":'';
		
		$params = array(
		  'stic_dark_theme'=>$stic_dark_theme
		);		
		
        Yii::app()->db->createCommand()->update("{{merchant}}",$params,
  	    'merchant_id=:merchant_id',
	  	    array(
	  	      ':merchant_id'=>$merchant_id
	  	    )
  	    );
				
		$this->code = 1;
		$this->msg = $value=="yes"?translate("Dark mode enabled"):translate("Dark mode disabled");
		$this->output();
	}
	
	public function actioniosAutoPrint()
	{
		$merchant_id = $this->validateToken(); 
		
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
                               
        $todays_date = date("Y-m-d");
        $status = OrderWrapper::getStatusFromSettings('order_incoming_status',array('pending','paid'));
        
        $next_action = "silent"; $order_id = 0;
        
        $stmt="
		SELECT order_id 
		FROM {{order}}				
		WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
		AND status IN ($status)
		AND request_cancel='2'
		AND merchant_id = ".q($merchant_id)."
		AND NOW() - INTERVAL 5 SECOND <= date_created
		LIMIT 0,1
		";			        
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
			$next_action = "ios_auto_print";
			$order_id = $res['order_id'];
			$this->msg = "OK";
		} else $this->msg = "no results";
		
		$this->code = 1;
		$this->details = array(
		   'next_action'=>$next_action,
		   'order_id'=>$order_id
		);
        $this->output();
	}
	
	public function actiongetBookingSettings()
	{
		$data = array();		
		
		try {
			$resp = MerchantUserWrapper::validateToken($this->merchant_token);			
			$merchant_id = (integer)$resp['merchant_id'];			
			$settings = MerchantWrapper::getMerchantSettings($merchant_id, array(
			  'merchant_table_booking','accept_booking_sameday','fully_booked_msg',
			  'merchant_booking_alert','merchant_booking_receiver'
			) );
			
			$max_booked = getOption($merchant_id,'max_booked');
			if(!empty($max_booked)){
				if($max_booked = json_decode($max_booked,true)){			       
			       foreach ($max_booked as $key=>$val) {
			       		$settings[]=array(
						  'option_name'=>"max_booked_$key",
						  'option_value'=>$val
						);
			       	}	
				}			
			}		
						
			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'next_action'=>"set_form_options",
			  'data'=>(array)$settings			  
			);										
		} catch (Exception $e) {
			$this->code = 3;
			$this->msg = translate($e->getMessage());
		}				
		$this->output();
	}
	
	public function actionSavedBookingSettings()
	{	
		$merchant_id = $this->validateToken();
		
		Yii::app()->functions->updateOption("max_booked",
        isset($this->data['max_booked'])?json_encode($this->data['max_booked']):'',
        $merchant_id);	 
        
		Yii::app()->functions->updateOption("merchant_table_booking",
        isset($this->data['merchant_table_booking'])?trim($this->data['merchant_table_booking']):'',
        $merchant_id);	 
        
        Yii::app()->functions->updateOption("accept_booking_sameday",
        isset($this->data['accept_booking_sameday'])?trim($this->data['accept_booking_sameday']):'',
        $merchant_id);	 
        
        Yii::app()->functions->updateOption("fully_booked_msg",
        isset($this->data['fully_booked_msg'])?trim($this->data['fully_booked_msg']):'',
        $merchant_id);	 
        
        Yii::app()->functions->updateOption("merchant_booking_alert",
        isset($this->data['merchant_booking_alert'])?trim($this->data['merchant_booking_alert']):'',
        $merchant_id);	 
        
        Yii::app()->functions->updateOption("merchant_booking_receiver",
        isset($this->data['merchant_booking_receiver'])?trim($this->data['merchant_booking_receiver']):'',
        $merchant_id);	 
        
        
		
		$this->code = 1;
		$this->msg = translate("Setting saved");
		$this->details = array();		
		$this->output();
	}
	
	public function actiongetPrinterAutoAccepted()
	{
		$merchant_id = $this->validateToken(); 		
		$order_id = isset($this->data['order_id'])?(integer)$this->data['order_id']:'';
		$stmt="
		SELECT id as printer_id 
		FROM {{printer_list_new}}
		WHERE
		merchant_id = ".q($merchant_id)."
		AND auto_print_after_accepted ='1'
		AND device_uiid = ".q($this->device_uiid)."
		order by date_created DESC
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
			$res['order_id'] = $order_id;			
			$this->code = 1; 
			$this->msg = "ok";
			$this->details = array(
			   'next_action'=>"auto_print_receipt",
			  'data'=>$res
			);
			$this->output();
		} 
		
		$this->msg = "no results";
	   $this->details = array(
		   'next_action'=>"silent"			 
		);			
		$this->output();
	}

}
/*end class*/
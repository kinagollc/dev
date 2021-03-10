<?php
class IndexController extends CController
{
	public $layout='layout';	
	
	public function init()
	{
		
		$cs = Yii::app()->getClientScript();
		
		$map_provider = FunctionsV3::getMapProvider();
		
		$cs->registerScript(
		  'map_provider',
		  "var map_provider='$map_provider[provider]';",
		  CClientScript::POS_HEAD
		);
		
		$cs->registerScript(
		  'map_token',
		  "var map_token='$map_provider[token]';",
		  CClientScript::POS_HEAD
		);
		
		if($map_provider['provider']=="google.maps"){
			$cs->registerScriptFile("//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=".$map_provider['token']
			,CClientScript::POS_END); 		
										
			$cs->registerScriptFile(Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/gmaps.js'
			,CClientScript::POS_END); 			
		} else {
			
			$cs->registerScriptFile(Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/leaflet/leaflet.js'
			,CClientScript::POS_END); 			
			
			$cs->registerCssFile(Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER."/assets/vendor/leaflet/leaflet.css");
		}		
					
		$error_ajax_message = mobileWrapper::t("an error has occured");
		$cs->registerScript(
		  'error_ajax_message',
		  "var error_ajax_message='$error_ajax_message';",
		  CClientScript::POS_HEAD
		);
		
		$image_limit_size=FunctionsV3::imageLimitSize();
		$cs->registerScript(
		  'image_limit_size',
		  "var image_limit_size='$image_limit_size';",
		  CClientScript::POS_HEAD
		);
		
		$page_length = Yii::app()->functions->getOptionAdmin('mobile2_table_length');
		if($page_length<=0){
			$page_length=10;
		}
		$cs->registerScript(
		  'page_length',
		  "var page_length='$page_length';",
		  CClientScript::POS_HEAD
		);
		
		$notify_delay = Yii::app()->functions->getOptionAdmin('mobile2_notification_delay');
		if($notify_delay<=0){
			$notify_delay=1;
		}
		$cs->registerScript(
		  'notify_delay',
		  "var notify_delay='$notify_delay';",
		  CClientScript::POS_HEAD
		);
	}
	
	public function beforeAction($action)
	{		
		if(!Yii::app()->functions->isAdminLogin()){
		   $this->redirect(Yii::app()->createUrl('/admin/noaccess'));
		   Yii::app()->end();		
		}
		
		$action_name = "mobileappv2";	
		$aa_access=Yii::app()->functions->AAccess();
	    $menu_list=Yii::app()->functions->AAmenuList();		    
	    if (in_array($action_name,(array)$menu_list)){
	    	if (!in_array($action_name,(array)$aa_access)){	   	    		
	    		$this->redirect(Yii::app()->createUrl('/admin/noaccess'));
	    	}
	    }
	    
	    $action_name = $action->id;			
		$cs = Yii::app()->getClientScript();				
		$cs->registerScript(
		  'current_page',
		  "var current_page='$action_name';",
		  CClientScript::POS_HEAD
		);
			    		
		/*CHECK DATABASE*/
	    $new=0;
	    
	    if( !FunctionsV3::checkIfTableExist('mobile2_device_reg')){
	        $this->redirect(Yii::app()->createUrl('/mobileappv2/update'));
			Yii::app()->end();
	    }	    
	    	    
	    $new_fields=array('social_id'=>"social_id");
		if ( !FunctionsV3::checkTableFields('client',$new_fields)){			
			$new++;
		}		
		
		/*1.3*/
		if( !FunctionsV3::checkIfTableExist('mobile2_homebanner')){
			$new++;
		}
		$new_fields=array('distance_unit'=>"distance_unit");
		if ( !FunctionsV3::checkTableFields('mobile2_cart',$new_fields)){			
			$new++;
		}		
		
		/*1.5.1*/		
		$new_fields=array('close_store'=>"close_store");
		if ( !FunctionsV3::checkTableFields('merchant',$new_fields)){			
			$new++;
		}		
		
		/*1.5.2*/		
		$new_fields=array('actions'=>"actions");
		if ( !FunctionsV3::checkTableFields('mobile2_homebanner',$new_fields)){			
			$new++;
		}		
		
		if(Yii::app()->db->schema->getTable("{{item_relationship_size}}")){
			if(!Yii::app()->db->schema->getTable("{{view_item2}}")){
				$new++;
			}
		}
				
		if($new>0){
			$this->redirect(Yii::app()->createUrl('/mobileappv2/update'));
			Yii::app()->end();
		}
		/*END CHECK DATABASE*/
							
		return true;
	}
	
	public function actionIndex(){
		$this->redirect(Yii::app()->createUrl(APP_FOLDER.'/index/settings'));
	}		
	
	public function actionsettings()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
				
		$this->render('general_settings',array(
		  'settings_title'=>mt("API Settings"),
		  'tpl'=>"settings_api",
		  'data'=>array()
		));
	}
	
	public function actionsettings_startup()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
				
		$startup_banner = getOptionA('mobileapp2_startup_banner');   
		
		$this->setActiveSettings();
		
		$this->render('general_settings',array(
		  'settings_title'=>mt("App Startup"),
		  'tpl'=>"settings_startup",
		  'data'=>array(
		    'startup_banner'=>!empty($startup_banner)?json_decode($startup_banner):false
		  )
		));
	}
	
	public function actionsettings_social_login()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
				
		$this->setActiveSettings();
		
		$this->render('general_settings',array(
		  'settings_title'=>mt("Social Login"),
		  'tpl'=>"settings_social_login",
		  'data'=>array()
		));
	}
	
	public function actionsettings_analytics()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Firebase Analytics"),
		  'tpl'=>"settings_analytics",
		  'data'=>array()
		));
	}	
	
	public function actionsettings_application()
	{
		$this->pageTitle = mobileWrapper::t("Settings");

		$country_list=require_once('CountryCode.php');
		$mobile_country_list=getOptionA('mobile_country_list');
		if (!empty($mobile_country_list)){
			$mobile_country_list=json_decode($mobile_country_list);
		} else $mobile_country_list=array();
		
		$search_options = mobileWrapper::getDataSearchOptions();
		
		$data = array(
		  'country_list'=>$country_list,
		  'mobile_country_list'=>$mobile_country_list,
		  'search_options'=>$search_options,
		  'order_status_list'=>Yii::app()->functions->orderStatusList2(true)
		);
		
		$this->setActiveSettings();
		
		$this->render('general_settings',array(
		  'settings_title'=>mt("Application Settings"),
		  'tpl'=>"settings_application",
		  'data'=>$data
		));
	}
	
	public function actionsettings_android()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Android Settings"),
		  'tpl'=>"settings_android",
		  'data'=>array(
		    'upload_push_icon'=>getOptionA('android_push_icon'),
            'upload_push_picture'=>getOptionA('android_push_picture'),
		  )
		));
	}
	
	public function actionsettings_fcm()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("FCM"),
		  'tpl'=>"settings_fcm",
		  'data'=>array()
		));
	}	
	
	public function actionsettings_map()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		$this->setActiveSettings();				
		
		$admin_country_set = getOptionA('admin_country_set');
		$lat = '37.09024'; $long='-95.712891';		
		
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  'default_location',
		  "var default_lat='$lat';",
		  CClientScript::POS_HEAD
		);		
		$cs->registerScript(
		  'default_long',
		  "var default_long='$long';",
		  CClientScript::POS_HEAD
		);		
		
		$this->render('general_settings',array(
		  'settings_title'=>mt("Map Settings"),
		  'tpl'=>"map_settings",
		  'data'=>array()
		));
	}		
	
	public function actiondevice_list()
	{
		$this->pageTitle = mobileWrapper::t("Device List");
		$this->render('device_list');
	}
	
	public function actionbroadcast_list()
	{
		$this->pageTitle = mobileWrapper::t("Broadcast");
		$this->render('broadcast_list');
	}
	
	public function actionpush_list()
	{
		$this->pageTitle = mobileWrapper::t("Push Logs");
		$this->render('push_list');
	}
	
	public function actionpage_list()
	{
		if(Yii::app()->functions->multipleField()){ 
			DBTableWrapper::alterTablePages();
		}
		
		$this->pageTitle = mobileWrapper::t("Page");
		$this->render('page_list');
	}
	
	public function actionothers()
	{
		$this->pageTitle = mobileWrapper::t("Others");
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processpush"),
		  'notes'=>mt("run this every minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processbroadcast"),
		  'notes'=>mt("run this every minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processoldbroadcast"),
		  'notes'=>mt("run this every minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/triggerorder"),
		  'notes'=>mt("run this every minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/getfbavatar"),
		  'notes'=>mt("run this every 5 minutes")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/removeinactivedevice"),
		  'notes'=>mt("run once in a day")
		);
		
		$update_db = FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/update");
		
		$this->render('others',array(
		  'cron'=>$cron,
		  'cron_sample'=>$cron[0]['link'],
		  'update_db'=>$update_db
		));
	}
	
	public function actiontest_api()
	{
		$api_has_key = getOptionA('mobileapp2_api_has_key');		
		$api_settings = websiteUrl()."/".APP_FOLDER."/api/getsettings";
		if(!empty($api_has_key)){
			$api_settings.="/?api_key=".urlencode($api_has_key);
		}				
		$this->redirect($api_settings);
	}
	
	public function actionbroadcast_details()
	{
		$bid = isset($_GET['bid'])?$_GET['bid']:'';
		$this->pageTitle = mobileWrapper::t("Broadcast details [id]",array(
		  'id'=>$bid
		));
		$this->render('push_list',array(
		  'bid'=>$bid
		));
	}
	
	public function actionorder_trigger()
	{
		$this->pageTitle = mt("Order Trigger Notification");
		$this->render('order_trigger');
	}
	
	public function actionhome_banner_list()
	{		
	        
		$category = 'home_banner';
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "category",
		  "var category = ".q($category)."; ",
		  CClientScript::POS_HEAD 
		);	
		
		$this->pageTitle = mt("Home Banner");
		$this->render('banner_list',array( 
		  'category'=>$category,
		  'add_link'=>Yii::app()->createUrl("/".APP_FOLDER."/index/home_banner_new")
		));
	}
	
	public function actionhome_banner_new()
	{
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "active_menu",
		  '$(".menu_nav li:nth-child(7)").addClass("active");',
		  CClientScript::POS_END 
		);	
				
		$category = 'home_banner';
		$data = $_GET; $banner_id = '';  $resp = array();
		if(isset($data['banner_id'])){
			$banner_id = $data['banner_id'];
			if(!$resp = mobileWrapper::getHomeBannerByID($banner_id)){
				$this->pageTitle = mt("Home Banner");
				$this->render('error',array(
				  'error'=>mt("sorry but we cannot find what you are looking for")
				));
				return ;
			}
		}
		
		$last_increment = Yii::app()->functions->getLastIncrement('{{mobile2_homebanner}}');	
		
		$tags = array();
		if(method_exists("FunctionsV3",'dropdownFormat')){		
			$tags =   (array)FunctionsV3::dropdownFormat(
			   FunctionsV3::getTags(),'tag_id','tag_name'
			);
		} 				
		
		$actions = mobileWrapper::homeBannerActions();
		$pages = array();
		try {
		  $pages = itemWrapper::getAllData("mobile2_pages",'page_id,title',"sequence asc");		  
		  $pages = itemWrapper::dropdownFormat($pages,'page_id','title');
		} catch (Exception $e) {
			//
		}
						
		$this->pageTitle = mt("Home Banner");
		$this->render('banner_add',array(
		 'data'=>$resp,
		 'last_increment'=>$last_increment+0,
		 'tags'=>(array)$tags,
		 'category'=>$category,		 
		 'back_url'=>Yii::app()->createUrl("/".APP_FOLDER."/index/home_banner_list"),
		 'actions'=>$actions,
		 'pages'=>$pages
		));
	}
	
	private function setActiveSettings()
	{
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "active_menu",
		  '$(".menu_nav li:first-child").addClass("active");',
		  CClientScript::POS_END 
		);	
	}
	
	public function actionold_broadcast()
	{
		$this->pageTitle = mobileWrapper::t("Broadcast");
		$this->render('broadcast_old_list');
	}
	
	public function actionnotification()
	{
		$this->pageTitle = mobileWrapper::t("Notification");
		$this->render('notification');
	}
	
	public function actionhome_category()
	{		
		$category = 'home_category';
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "category",
		  "var category = ".q($category)."; ",
		  CClientScript::POS_HEAD 
		);	
		
		$this->pageTitle = mt("Home categories");
		$this->render('banner_list',array( 
		  'category'=>$category,
		  'add_link'=>Yii::app()->createUrl("/".APP_FOLDER."/index/home_category_new")
		));
	}
	
	public function actionhome_category_new()
	{
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "active_menu",
		  '$(".menu_nav li:nth-child(6)").addClass("active");',
		  CClientScript::POS_END 
		);	
		
		$category = 'home_category';
		$data = $_GET; $banner_id = '';  $resp = array();
		if(isset($data['banner_id'])){
			$banner_id = $data['banner_id'];
			if(!$resp = mobileWrapper::getHomeBannerByID($banner_id)){
				$this->pageTitle = mt("Home Banner");
				$this->render('error',array(
				  'error'=>mt("sorry but we cannot find what you are looking for")
				));
				return ;
			}
		}
				
		$last_increment = Yii::app()->functions->getLastIncrement('{{mobile2_homebanner}}');	
		
		$tags = array();
		if(method_exists("FunctionsV3",'dropdownFormat')){		
			$tags =   (array)FunctionsV3::dropdownFormat(
			   FunctionsV3::getTags(),'tag_id','tag_name'
			);
		} 				
		
		$actions = mobileWrapper::homeBannerActions();
		$pages = array();
		try {
		  $pages = itemWrapper::getAllData("mobile2_pages",'page_id,title',"sequence asc");		  
		  $pages = itemWrapper::dropdownFormat($pages,'page_id','title');
		} catch (Exception $e) {
			//
		}
		
		$this->pageTitle = mt("Home categories");
		$this->render('banner_add',array(
		 'data'=>$resp,
		 'last_increment'=>$last_increment+0,
		 'tags'=>(array)$tags,
		 'category'=>$category,
		 'back_url'=>Yii::app()->createUrl("/".APP_FOLDER."/index/home_category"),
		 'actions'=>$actions,
		 'pages'=>$pages
		));
	}
	
	public function actionoperating_hours()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		
		$data = array();
		$days=Yii::app()->functions->getDays();
		$data['days']=$days;
		$data['time_list'] = mobileWrapper::timeList();	
		$data['opening_hours'] = mobileWrapper::getOperatingHours();
		
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Operating hours"),
		  'tpl'=>"operating_hours",
		  'data'=>$data
		));		
	}
	
	public function actionrating_app()
	{
		$this->pageTitle = mobileWrapper::t("Settings");
		$data = array();
		
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Rating App Settings"),
		  'tpl'=>"rating_app",
		  'data'=>$data
		));		
	}
	
	public function actionfirebase_otp()
	{
		$this->pageTitle = mobileWrapper::t("Firebase OTP");
		$data = array();		
		
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Firebase OTP Settings"),
		  'tpl'=>"firebase_otp",
		  'data'=>$data
		));		
	}
	
	public function actiongraphical_tracking()
	{
		$this->pageTitle = mobileWrapper::t("Graphical Tracking");
		$data = mobileWrapper::getOptionsValue(array(
		  'admin_tracking_estimation_delivery1','admin_tracking_estimation_delivery2',
		  'admin_tracking_estimation_pickup1','admin_tracking_estimation_pickup2',
		  'admin_tracking_estimation_dinein1','admin_tracking_estimation_dinein2',
		));	
		
		$data = array('data'=>$data);
				
		$this->setActiveSettings();				
		$this->render('general_settings',array(
		  'settings_title'=>mt("Graphical Tracking Settings"),
		  'tpl'=>"graphical_tracking",
		  'data'=>$data
		));		
	}
	
	
} /*end class*/
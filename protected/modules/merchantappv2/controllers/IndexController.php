<?php
class IndexController extends CController
{
	public $layout='layout';	
	
		public function init()
	{
		FunctionsV3::handleLanguage();
		$lang=Yii::app()->language;				
		$cs = Yii::app()->getClientScript();
		$cs->registerScript(
		  'lang',
		  "var lang='$lang';",
		  CClientScript::POS_HEAD
		);
		
	   $table_translation=array(
	      "tablet_1"=>translate("No data available in table"),
    	  "tablet_2"=>translate("Showing _START_ to _END_ of _TOTAL_ entries"),
    	  "tablet_3"=>translate("Showing 0 to 0 of 0 entries"),
    	  "tablet_4"=>translate("(filtered from _MAX_ total entries)"),
    	  "tablet_5"=>translate("Show _MENU_ entries"),
    	  "tablet_6"=>translate("Loading..."),
    	  "tablet_7"=>translate("Processing..."),
    	  "tablet_8"=>translate("Search:"),
    	  "tablet_9"=>translate("No matching records found"),
    	  "tablet_10"=>translate("First"),
    	  "tablet_11"=>translate("Last"),
    	  "tablet_12"=>translate("Next"),
    	  "tablet_13"=>translate("Previous"),
    	  "tablet_14"=>translate(": activate to sort column ascending"),
    	  "tablet_15"=>translate(": activate to sort column descending"),
    	  'are_you_sure'=>translate("Are you sure"),
    	  'invalid_file_extension'=>translate("Invalid File extension"),
    	  'invalid_file_size'=>translate("Invalid File size"),
    	  'failed'=>translate("Failed"),
	   );	
	   $js_translation=json_encode($table_translation);
		
	   $cs->registerScript(
		  'js_translation',
		  "var js_translation=$js_translation;",
		  CClientScript::POS_HEAD
		);	
	   			
	}	
	
	public function beforeAction($action)
	{
		if(!Yii::app()->functions->isAdminLogin()){
			$this->redirect(Yii::app()->createUrl('/admin/noaccess'));
			Yii::app()->end();		
		}
		
		$action_name = $action->id;			
		
		RegisterScriptWrapper::registerScript(array(
		 "var current_page='$action_name';",
		 "var notify_delay='2';",
		),'beforeAction');
				
		
		/*CHECK DATABASE*/
		$new=0;		
		if(!Yii::app()->db->schema->getTable("{{merchantapp_device_reg}}")){
	        $this->redirect(Yii::app()->createUrl(APP_FOLDER.'/update'));
			Yii::app()->end();
	    }	    	    		
	    
	    /*1.0.1*/
	    if(!Yii::app()->db->schema->getTable("{{subcategory_item_relationships}}")){
	        $this->redirect(Yii::app()->createUrl(APP_FOLDER.'/update'));
			Yii::app()->end();
	    }	    	    		
	    	   	       
	    /*1.0.2*/
	    if(!Yii::app()->db->schema->getTable("{{merchantapp_task_location}}")){
	        $this->redirect(Yii::app()->createUrl(APP_FOLDER.'/update'));
			Yii::app()->end();
	    }	    	    		
	    
	    /*1.0.3*/
	    if(!Yii::app()->db->schema->getTable("{{printer_list_new}}")){
	        $this->redirect(Yii::app()->createUrl(APP_FOLDER.'/update'));
			Yii::app()->end();
	    }	    	    	
	    
	    /*1.0.6*/	
	    if (!DatataseMigration::checkFields('{{printer_list_new}}',array(
	      'auto_print_after_accepted'=>"auto_print_after_accepted"
	    ))){
	    	$this->redirect(Yii::app()->createUrl(APP_FOLDER.'/update'));
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
		$this->pageTitle = translate("Settings");
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("General settings"),
		  'tpl'=>'settings_api',
		  'data'=>array()
		));
	}
	
	public function actiontest_api()
	{
		$api_has_key = getOptionA('merchantappv2_api_hash_key');		
		$api_settings = websiteUrl()."/".APP_FOLDER."/api/getsettings";
		if(!empty($api_has_key)){
			$api_settings.="/?api_key=".urlencode($api_has_key);
		}				
		$this->redirect($api_settings);
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
	
	public function actionorder_settings()
	{
		$this->pageTitle = translate("Settings");

		$country_list=require_once('CountryCode.php');
		$mobile_country_list=getOptionA('mobile_country_list');
		if (!empty($mobile_country_list)){
			$mobile_country_list=json_decode($mobile_country_list);
		} else $mobile_country_list=array();
		
		$search_options = array();
		
		$order_incoming_status = getOptionA('order_incoming_status');
		$order_outgoing_status = getOptionA('order_outgoing_status');
		$order_ready_status = getOptionA('order_ready_status');
		
		$order_failed_status = getOptionA('order_failed_status');
		$order_successful_status = getOptionA('order_successful_status');
		
		$order_status_list = MerchantWrapper::dropdownFormat((array)OrderWrapper::AllOrderStatus(),
		'description','description');		
		
		$options = MerchantWrapper::getOptions(array(
		  'order_action_accepted_status',
		  'order_action_decline_status','order_action_cancel_status','order_action_food_done_status',
		  'order_action_delayed_status','order_action_completed_status','order_action_approved_cancel_order',
		  'order_action_decline_cancel_order','accepted_based_time'
		));
						
		$data = array(		  
		  'order_status_list'=>$order_status_list,
		  'order_incoming_status'=>!empty($order_incoming_status)?json_decode($order_incoming_status,true):'',
		  'order_outgoing_status'=>!empty($order_outgoing_status)?json_decode($order_outgoing_status,true):'',
		  'order_ready_status'=>!empty($order_ready_status)?json_decode($order_ready_status,true):'',
		  'order_failed_status'=>!empty($order_failed_status)?json_decode($order_failed_status,true):'',
		  'order_successful_status'=>!empty($order_successful_status)?json_decode($order_successful_status,true):'',
		);
		
		if(is_array($options) && count($options)>=1){
			foreach ($options as $val) {
				$data[$val['option_name']]=$val['option_value'];
			}
		}
					
		$this->setActiveSettings();
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Order Settings"),
		  'tpl'=>"order_settings",
		  'data'=>$data
		));
	}
	
	public function actionbooking_settings()
	{
		$this->pageTitle = translate("Settings");
		$data = array();
		$this->setActiveSettings();
		
		$order_status_list = bookingStatus();		
		
		$booking_incoming_status = getOptionA('booking_incoming_status');
		$booking_cancel_status = getOptionA('booking_cancel_status');
		$booking_done_status = getOptionA('booking_done_status');	
		
		$data = array(		  
		  'order_status_list'=>$order_status_list,		  
		  'booking_incoming_status'=>!empty($booking_incoming_status)?json_decode($booking_incoming_status,true):'',
		  'booking_cancel_status'=>!empty($booking_cancel_status)?json_decode($booking_cancel_status,true):'',
		  'booking_done_status'=>!empty($booking_done_status)?json_decode($booking_done_status,true):'',
		);
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Booking Settings"),
		  'tpl'=>"booking_settings",
		  'data'=>$data
		));
	}
	
	public function actionapplication_settings()
	{
		$this->pageTitle = translate("Settings");
		$data = array();$this->setActiveSettings();
		
		$order_estimated_time = MerchantWrapper::parseTagTify('order_estimated_time');
		$decline_reason_list = MerchantWrapper::parseTagTify('decline_reason_list');
				
		$data = array(
		  'order_unattended_minutes'=>getOptionA('order_unattended_minutes'),
		  'ready_outgoing_minutes'=>getOptionA('ready_outgoing_minutes'),
		  'ready_unattended_minutes'=>getOptionA('ready_unattended_minutes'),
		  'booking_incoming_unattended_minutes'=>getOptionA('booking_incoming_unattended_minutes'),
		  'booking_cancel_unattended_minutes'=>getOptionA('booking_cancel_unattended_minutes'),
		  'merchantapp_keep_awake'=>getOptionA('merchantapp_keep_awake'),
		  'refresh_order'=>getOptionA('refresh_order'),
		  'refresh_cancel_order'=>getOptionA('refresh_cancel_order'),
		  'refresh_booking'=>getOptionA('refresh_booking'),
		  'refresh_cancel_booking'=>getOptionA('refresh_cancel_booking'),
		  'order_estimated_time'=>$order_estimated_time,
		  'decline_reason_list'=>$decline_reason_list,
		  'interval_ready_order'=>getOptionA('interval_ready_order'),
		  'merchantapp_upload_resize_width'=>getOptionA('merchantapp_upload_resize_width'),
		  'merchantapp_upload_resize_height'=>getOptionA('merchantapp_upload_resize_height'),
		  'merchantapp_upload_resize_enabled'=>getOptionA('merchantapp_upload_resize_enabled')
		);						
		$this->render('general_settings',array(
		  'settings_title'=>translate("Application Settings"),
		  'tpl'=>"application_settings",
		  'data'=>$data
		));
	}
	
	public function actionlanguage_settings()
	{
		$this->pageTitle = translate("Settings");
		$data = array();$this->setActiveSettings();
		
		$merchantapp_language = getOptionA('merchantapp_language');
		$flags = require_once 'CountryCode.php';
		
		$data = array(
		  'language_list'=>FunctionsV3::getEnabledLanguage(),
		  'merchantapp_language'=>!empty($merchantapp_language)?json_decode($merchantapp_language,true):'',
		  'flag'=>$flags,
		  'set_language'=>getOptionA('set_language')
		);		
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Language Settings"),
		  'tpl'=>"language_settings",
		  'data'=>$data
		));
	}

	public function actionsettings_fcm()
	{
		$this->pageTitle = translate("Settings");
		$data = array();$this->setActiveSettings();
		
		$image_limit_size=FunctionsV3::imageLimitSize();		
		RegisterScriptWrapper::registerScript(array(
		 "var image_limit_size='". CJavaScript::quote($image_limit_size) ."';",		 
		),'image_limit_size');
		
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("FCM Settings"),
		  'tpl'=>"settings_fcm",
		  'data'=>$data
		));
	}
	
	public function actiondevice_list()
	{
		$this->pageTitle = translate("Device List");
				
        RegisterScriptWrapper::registerScript(array(
		  "var action_name ='registeredDeviceList'"
		),'device_list');
		
		$this->render('device_list');  
	}
	
	public function actionpush_logs()
	{
		$this->pageTitle = translate("Push Logs");
		RegisterScriptWrapper::registerScript(array(
		  "var action_name ='pushLogs'"
		));
		$this->render('push_logs');  
	}
	
	public function actionpush_broadcast()
	{
		$this->pageTitle = translate("Broadcast");
		RegisterScriptWrapper::registerScript(array(
		  "var action_name ='pushBroadcast'"
		));
		$this->render('push_broadcast_list');  
	}
	
	public function actionorder_trigger()
	{
		$this->pageTitle = translate("Order trigger notification");
				
		RegisterScriptWrapper::registerScript(array(
		  "var action_name ='order_trigger'"
		));
		
		$this->render('order_trigger_list');  
	}
	
	public function actionothers()
	{
		$this->pageTitle = translate("Others");
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processpush"),
		  'notes'=>translate("run this every 1 minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processbroadcast"),
		  'notes'=>translate("run this every 1 minute")
		);
		
		/*$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/trigger_order"),
		  'notes'=>translate("run this every 1 minute")
		);
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/trigger_order_booking"),
		  'notes'=>translate("run this every 2 minute")
		);*/
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/unattented_order"),
		  'notes'=>translate("run this every 1 minute")
		);
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/autoupdatestatus"),
		  'notes'=>translate("run this every 1 minute")
		);
				
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/unattented_booking"),
		  'notes'=>translate("run this every 2 minute")
		);
				
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/clear_logs"),
		  'notes'=>translate("run this every end of the month. this will clear all logs with record past 2months")
		);
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/nearexpiration"),
		  'notes'=>translate("run this once a day")
		);
		
		$update_db = FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/update");
		
		$cron_min[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/runall"),
		  'notes'=>translate("run this every 1 minute")
		);
		
		$cron_min[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/clear_logs"),
		  'notes'=>translate("run this every end of the month. this will clear all logs with record past 2months")
		);
		
		$cron_min[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/nearexpiration"),
		  'notes'=>translate("run this once a day")
		);
		
		$this->render('others',array(
		  'cron'=>$cron,
		  'cron_sample'=>$cron[0]['link'],
		  'update_db'=>$update_db,
		  'cron_min'=>$cron_min
		));
	}	
	
	public function actionauto_order()
	{
		$this->pageTitle = translate("Auto update");
		
		$data = array();
		$this->setActiveSettings();
		
		$order_status_list = MerchantWrapper::dropdownFormat((array)OrderWrapper::AllOrderStatus(),
		'description','description');		
								
		$data = array(		  
		  'order_status_list'=>$order_status_list,
		  'merchantapp_enabled_auto_status_enabled'=>getOptionA('merchantapp_enabled_auto_status_enabled'),
		  'merchantapp_enabled_auto_status_time'=>getOptionA('merchantapp_enabled_auto_status_time'),
		  'merchantapp_enabled_auto_status'=>getOptionA('merchantapp_enabled_auto_status'),
		  'merchantapp_enabled_auto_status_readyin'=>getOptionA('merchantapp_enabled_auto_status_readyin')
		);
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Auto update order status"),
		  'tpl'=>"auto_order",
		  'data'=>$data
		));
	}
	
	public function actionsettings_printer()
	{
		$this->pageTitle = translate("Printer");
		
		$data = array();
		$this->setActiveSettings();
				
								
		$data = array(		  		
		);
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Printer"),
		  'tpl'=>"settings_printer",
		  'data'=>$data
		));
	}
	
	public function actionprivacy_policy()
	{
		$this->pageTitle = translate("Printer");
		
		$data = array();
		$this->setActiveSettings();
				
								
		$data = array(	
		  'merchantapp_privacy_policy_link'=>getOptionA('merchantapp_privacy_policy_link')
		);
		
		$this->render('general_settings',array(
		  'settings_title'=>translate("Privacy policy"),
		  'tpl'=>"privacy_policy",
		  'data'=>$data
		));
	}
	
}
/*END CLASS*/
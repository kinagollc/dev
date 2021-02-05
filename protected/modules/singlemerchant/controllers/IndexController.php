<?php
//if (!isset($_SESSION)) { session_start(); }


class IndexController extends CController
{
	public $layout='layout';
	public $needs_db_update=false;
		
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
	      "tablet_1"=>SingleAppClass::t("No data available in table"),
    	  "tablet_2"=>SingleAppClass::t("Showing _START_ to _END_ of _TOTAL_ entries"),
    	  "tablet_3"=>SingleAppClass::t("Showing 0 to 0 of 0 entries"),
    	  "tablet_4"=>SingleAppClass::t("(filtered from _MAX_ total entries)"),
    	  "tablet_5"=>SingleAppClass::t("Show _MENU_ entries"),
    	  "tablet_6"=>SingleAppClass::t("Loading..."),
    	  "tablet_7"=>SingleAppClass::t("Processing..."),
    	  "tablet_8"=>SingleAppClass::t("Search:"),
    	  "tablet_9"=>SingleAppClass::t("No matching records found"),
    	  "tablet_10"=>SingleAppClass::t("First"),
    	  "tablet_11"=>SingleAppClass::t("Last"),
    	  "tablet_12"=>SingleAppClass::t("Next"),
    	  "tablet_13"=>SingleAppClass::t("Previous"),
    	  "tablet_14"=>SingleAppClass::t(": activate to sort column ascending"),
    	  "tablet_15"=>SingleAppClass::t(": activate to sort column descending"),
    	  'are_you_sure'=>SingleAppClass::t("Are you sure"),
    	  'invalid_file_extension'=>SingleAppClass::t("Invalid File extension"),
    	  'invalid_file_size'=>SingleAppClass::t("Invalid File size"),
    	  'failed'=>SingleAppClass::t("Failed"),
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
		
		/*CHECK DATABASE*/
	    $new=0;
	    
	    if( !FunctionsV3::checkIfTableExist('singleapp_cart')){
	        $this->redirect(Yii::app()->createUrl('/singlemerchant/update'));
			Yii::app()->end();
	    }
	    	    
	    $new_fields=array('delivery_lat'=>"delivery_lat");
		if ( !FunctionsV3::checkTableFields('singleapp_cart',$new_fields)){			
			$new++;
		}
		
		/*2.0*/
		$new_fields=array('is_read'=>"is_read");
		if ( !FunctionsV3::checkTableFields('singleapp_mobile_push_logs',$new_fields)){			
			$new++;
		}
		
		if( !FunctionsV3::checkIfTableExist('singleapp_broadcast')){
			$new++;
		}	
		if( !FunctionsV3::checkIfTableExist('singleapp_pages')){
			$new++;
		}	
		
		
		/*2.1*/
		$new_fields=array('single_app_merchant_id'=>"single_app_merchant_id");
		if ( !FunctionsV3::checkTableFields('client',$new_fields)){			
			$new++;
		}
		if( !FunctionsV3::checkIfTableExist('singleapp_recent_location')){
			$new++;
		}			
		
		/*2.2*/
		$new_fields=array('latitude'=>"latitude");
		if ( !FunctionsV3::checkTableFields('address_book',$new_fields)){			
			$new++;
		}
		
		/*2.4*/
		$new_fields=array('merchant_id'=>"merchant_id");
		if ( !FunctionsV3::checkTableFields('singleapp_cart',$new_fields)){			
			$new++;
		}
		
		if($new>0){
			$this->redirect(Yii::app()->createUrl('/singlemerchant/update'));
			Yii::app()->end();
		}
	    
		$action_name = $action->id;			
		$cs = Yii::app()->getClientScript();				
		$cs->registerScript(
		  'current_page',
		  "var current_page='$action_name';",
		  CClientScript::POS_HEAD
		);
		
		return true;
	}	
	
	public function actionIndex(){			
		$this->redirect(Yii::app()->createUrl('/'.SingleAppClass::moduleName().'/index/settings'));
	}		
	
	public function actionsettings()
	{				
		$this->pageTitle = st("Merchant List");
		
		SingleAppClass::registerScript(array(
		  "var action_name ='merchantList'"
		));
		
		$this->render('merchant-list',array(		
		  'modulename' => SingleAppClass::moduleName(),			  		  
		));  
	}
	
	public function actiondevice()
	{
		$this->pageTitle = st("Device List");
				
        SingleAppClass::registerScript(array(
		  "var action_name ='registeredDeviceList'"
		));
		
		$this->render('device_list',array(		
		  'modulename' => SingleAppClass::moduleName(),		  
		));  
	}
	
	public function actionpush_logs()
	{
		$this->pageTitle = st("Push Logs");
		SingleAppClass::registerScript(array(
		  "var action_name ='pushLogs'"
		));
		$this->render('push_logs',array(		
		  'modulename' => SingleAppClass::moduleName(),		  
		));  
	}
	
	public function actionsend_push()
	{		
		$client_id = isset($_GET['id'])?$_GET['id']:'';		
		
		$this->pageTitle = st("Send Push [id]",array(
		 'id'=>$client_id
		));
		
		if($client_id>0){	
			if ($data = Yii::app()->functions->getClientInfo($client_id)){
				
				$this->pageTitle = st("Send Push to [first_name]",array(
		           'first_name'=>$data['first_name']
		        ));
				
				$this->render('send_push',array(		
				  'modulename' => SingleAppClass::moduleName(),			  				  
				  'data'=>$data
				));  
			} else $this->render('error',array(
			  'message'=>SingleAppClass::t("Client id not found")
		    ));
		} else $this->render('error',array(
			  'message'=>SingleAppClass::t("invalid client id")
		    ));
	}
	
	public function actioncron_jobs()
	{
		$this->render('cron_jobs',array(		
		  'modulename' => SingleAppClass::moduleName(),			  			  
		));  
	}
	
	private function setMerchant($title='', $tpl='', $data=array())
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';
		$this->pageTitle = st("Merchant [id]",array(
		 'id'=>$merchant_id
		));
		
		$cs = Yii::app()->getClientScript(); 
		$cs->registerScript(
		  "active_menu",
		  '$(".menu_nav li:first-child").addClass("active");',
		  CClientScript::POS_END 
		);	
		
		$merchant_info = FunctionsV3::getMerchantInfo($merchant_id);
		if($merchant_info){
			$this->pageTitle = st("Merchant [merchant_name]",array(
		      '[merchant_name]'=>$merchant_info['restaurant_name']
		    ));
		}
		
		if($merchant_id>0 && $merchant_info){
			$this->render('general_settings',array(
			  'settings_title'=>st($title),
			  'merchant_id'=>$merchant_id,
			  'tpl'=>$tpl,
			  'data'=>$data
			));				
		} else {
			$this->render('error',array(
			  'message'=>st("Invalid merchant id")
			));
		}
	}
	
	public function actionmerchant_settings()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';
		$merchant_info = FunctionsV3::getMerchantInfo($merchant_id);		
		$this->setMerchant('API Settings','settings_api',array(
		  'merchant_id'=>$merchant_id,
		  'single_app_keys'=>isset($merchant_info['single_app_keys'])?$merchant_info['single_app_keys']:''
		));
	}
		
	public function actionsettings_application()
	{			
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';
		
		$singleapp_default_image = getOption($merchant_id,'singleapp_default_image');
        $default_image_url = SingleAppClass::getImage( $singleapp_default_image );  
  
		$this->setMerchant('Application Settings','settings_app',array(
		  'merchant_id'=>$merchant_id,
		  'order_status_list'=>Yii::app()->functions->orderStatusList2(true),
		  'default_image_url'=>$default_image_url
		));
	}
	
	public function actionsettings_startup()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';
		$startup_banner = getOption($merchant_id,'singleapp_startup_banner');
		$this->setMerchant('App startup','settings_startup',array(
		  'merchant_id'=>$merchant_id,
		  'startup_banner'=>!empty($startup_banner)?json_decode($startup_banner,true):array()		  		  
		));
	}
	
	public function actionsettings_homebanner()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';		
		$this->setMerchant('Home banner','settings_banner_add',array(
		  'merchant_id'=>$merchant_id,	
		  'banner'=>getOption($merchant_id,'singleapp_banner')	  
		));
	}
	
    public function actionsettings_social()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';		
		$this->setMerchant('Social login','settings_social',array(
		  'merchant_id'=>$merchant_id,			  
		));
	}
	
	public function actionsettings_android()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';		
		$this->setMerchant('Android Settings','settings_android',array(
		  'merchant_id'=>$merchant_id,			  
		));
	}
	
	public function actionsettings_fcm()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';		
		$this->setMerchant('FCM','settings_fcm',array(
		  'merchant_id'=>$merchant_id,			  
		));
	}
	
	public function actionsettings_pages()
	{		
		SingleAppClass::registerScript(array(
		  "var action_name ='pageList'"
		));
		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';		
		$this->setMerchant('Pages','page_list',array(
		  'merchant_id'=>$merchant_id,			  
		));
	}
	
	public function actionsettings_contactus()
	{		
		$merchant_id = isset($_GET['merchant_id'])?(integer)$_GET['merchant_id']:'';	
		
		$cotact_fields = getOption($merchant_id,'singleapp_contactus_fields');
		if(!empty($cotact_fields)){
		    $cotact_fields = json_decode($cotact_fields,true);
		}	
		$this->setMerchant('Contact us','contact_us',array(
		  'merchant_id'=>$merchant_id,
		  'fields'=>$cotact_fields,	  
		));
	}
	
	public function actionpush_broadcast()
	{
		$this->pageTitle = st("Broadcast");
		SingleAppClass::registerScript(array(
		  "var action_name ='pushBroadcast'"
		));
		$this->render('push_broadcast_list',array(		
		  'modulename' => SingleAppClass::moduleName(),		  
		));  
	}
	
	public function actionbroadcast_new()
	{
		$this->pageTitle = st("Broadcast New");
		$this->render('push_broadcast_new',array(		
		   'merchant_list'=>SingleAppClass::getMerchantList()
		));  
	}
	
	public function actionbroadcast_details()
	{
		$broadcast_id = isset($_GET['id'])?$_GET['id']:'';	
		
		$this->pageTitle = st("Broadcast details [id]",array(
		  'id'=>$broadcast_id
		));
		
		SingleAppClass::registerScript(array(
		  "var action_name ='pushLogsDetails'"
		));	
			
		$this->render('push_broadcast_details',array(		
		  'modulename' => SingleAppClass::moduleName(),	
		  'broadcast_id'=>$broadcast_id
		));  
	}
	
	public function actionpages_new()
	{
		$data = array();
		$merchant_id = isset($_GET['merchant_id'])?$_GET['merchant_id']:'';		
		$page_id = isset($_GET['id'])?$_GET['id']:'';		
		if($page_id>0){
			$data = SingleAppClass::getPagesByID($page_id);
		}		
		if($merchant_info = FunctionsV3::getMerchantInfo($merchant_id)){					
			$this->render('pages_new',array(		
			  'modulename' => SingleAppClass::moduleName(),	
			  'merchant_id'=>$merchant_id,
			  'data'=>$data,
			  'merchant_info'=>$merchant_info
			));  
		} else {
			$this->render('error',array(					  
			  'message'=>SingleAppClass::t("invalid merchant id")
			));  
		}
	}
	
	public function actionothers()
	{
		$this->pageTitle = st("Others");
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processpush"),
		  'notes'=>st("run this every minute")
		);
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processbroadcast"),
		  'notes'=>st("run this every 2 minute")
		);
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/trigger_order"),
		  'notes'=>st("run this every 5 minute")
		);
		
		$cron[] = array(
		  'link'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/processbroadcastold"),
		  'notes'=>st("run this only if your using old broadcast function this every 1 minute")
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
		$data  = $_GET;
		$merchant_id = isset($data['merchant_id'])?$data['merchant_id']:'';
		//if($res = FunctionsV3::getMerchantById($merchant_id)){
		if($res = FunctionsV3::getMerchantInfo($merchant_id)){
			$single_app_keys = $res['single_app_keys'];	
			
			$api_settings = websiteUrl()."/".APP_FOLDER."/api/getAppSettings";
			if(!empty($single_app_keys)){
				$api_settings.="/?merchant_keys=".urlencode($single_app_keys);
			}				
			$this->redirect($api_settings);			
		} else echo st("Merchant information not found");				
	}
	
	public function actionold_broadcast()
	{
		$this->pageTitle = st("Old Broadcast");
				
		SingleAppClass::registerScript(array(
		  "var action_name ='push_broadcast_old'"
		));
		
		$this->render('push_broadcast_old',array(		
		  'modulename' => SingleAppClass::moduleName(),		  
		));  
	}
	
	public function actionorder_trigger()
	{
		$this->pageTitle = st("Order trigger notification");
				
		SingleAppClass::registerScript(array(
		  "var action_name ='order_trigger'"
		));
		
		$this->render('order_trigger_list');  
	}
	
	public function actionnotification()
	{
		$this->pageTitle = st("Notification");					
		$this->render('notification');  
	}
	
	public function actionmigrate_device()
	{
		$this->pageTitle = st("Migrate device");					
		$logger = '';
		
		$stmt="
		SELECT 
		a.client_id,
		a.device_platform,
		a.device_id,
		a.enabled_push,
		a.single_app_device_uiid,
		a.single_app_merchant_id
		
		FROM {{client}} a
		WHERE  single_app_device_uiid !=''
		AND 
		single_app_device_uiid NOT IN (
		  select device_uiid from {{singleapp_device_reg}}
		  where 
		  device_uiid=a.single_app_device_uiid
		)
		LIMIT 0,300
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			$res = Yii::app()->request->stripSlashes($res);		
			foreach ($res as $key=>$val) {
				$key++;
							
				$params = array(
				  'merchant_id'=>(integer)$val['single_app_merchant_id'],
				  'client_id'=>(integer)$val['client_id'],
				  'device_uiid'=>$val['single_app_device_uiid'],
				  'device_id'=>$val['device_id'],
				  'device_platform'=>$val['device_platform'],
				  'push_enabled'=>(integer)$val['enabled_push'],
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				);
				Yii::app()->db->createCommand()->insert("{{singleapp_device_reg}}",$params);				
				
				$logger.="<li>";
				$logger.= Yii::t("default","Adding data [count]",array(
				  '[count]'=>$key
				));
				$logger.="</li>";
			}
			$logger.="<p>".t("Done")."....</p>";
		} else $logger.="<p>".st("No records to process")."....</p>";
		
		$this->render('update_table',array(
		   'logger'=>$logger
		));  
	}
	
} /*end class*/
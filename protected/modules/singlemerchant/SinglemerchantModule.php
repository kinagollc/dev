<?php
/* ********************************************************
 *   Single merchant Module
 *
 *   Last Update : 20 June 2018 Version 1.0
 *   Last Update : 06 July 2018 Version 1.1
 *   Last Update : 11 July 2018 Version 1.2
 *   Last Update : 01 March  2018 Version 1.2.1
 *   Last Update : 30 May 2018 Version  2.0
 *   Last Update : 02 Jan 2020 Version  2.1
 *   Last Update : 09 Jan 2020 Version  2.2
 *   Last Update : 01 June 2020 Version  2.3
 *   Last Update : 09 August 2020 Version  2.4 
 *   Last Update : 28 November 2020 Version  2.4.1  
***********************************************************/

define("APP_FOLDER",'singlemerchant');
define("APP_BTN",'btn-raised btn-info');
define("APP_BTN2",'btn-raised');
define("CHANNEL_ID",'kmrs_singleapp');
define("CHANNEL_TOPIC",'/topics/singleapp_broadcast');
define("CHANNEL_TOPIC_MERCHANT",'singleapp_broadcast');
define("CHANNEL_SOUNDNAME",'beep');
define("CHANNEL_SOUNDFILE",'beep.wav');

class SinglemerchantModule extends CWebModule
{	
	static $global_dict;
	
	public function init()
	{
		
		$session = Yii::app()->session;
		
		// this method is called when the module is being created
		// you may place code here to customize the module or the application
		
		// import the module-level models and components
		$this->setImport(array(			
			'singlemerchant.components.*',
			'application.components.*',
		));
		require_once 'Functions.php';
		
		$ajaxurl=Yii::app()->baseUrl.'/'.APP_FOLDER.'/ajax';
		
		Yii::app()->clientScript->scriptMap=array(
          'jquery.js'=>false,
          'jquery.min.js'=>false
        );

		$cs = Yii::app()->getClientScript();  
		$cs->registerScript(
		  'ajaxurl',
		 "var ajaxurl='$ajaxurl'",
		  CClientScript::POS_HEAD
		);
		
		$csrfTokenName = Yii::app()->request->csrfTokenName;
        $csrfToken = Yii::app()->request->csrfToken;        
        
		$cs->registerScript(
		  "$csrfTokenName",
		 "var $csrfTokenName='$csrfToken';",
		  CClientScript::POS_HEAD
		);
		
		$dict = SingleAppClass::getAppLanguage();
		self::$global_dict = $dict;
		
		$dict=json_encode($dict);
		$cs->registerScript(
		  'dict',
		  "var dict=$dict;",
		  CClientScript::POS_HEAD
		);
		
		$notify_delay=5;	
		$cs->registerScript(
		  'notify_delay',
		  "var notify_delay=$notify_delay;",
		  CClientScript::POS_HEAD
		);
		
		$image_limit_size=FunctionsV3::imageLimitSize();
		$cs->registerScript(
		  'image_limit_size',
		  "var image_limit_size='$image_limit_size';",
		  CClientScript::POS_HEAD
		);
		
		/*JS FILE*/
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/jquery-v3.4.1.js',
		CClientScript::POS_END
		);
				
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/jquery-ui-1.12.1/jquery-ui.min.js',
		CClientScript::POS_END
		);
				
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/popper.min.js',
		CClientScript::POS_END
		);
				
		
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/bootstrap-material-design/js/bootstrap-material-design.min.js',
		CClientScript::POS_END
		);
												
					
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/jquery.webui-popover.min.js',
		CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/datatables/datatables.min.js',
		CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/jquery.translate.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/loader/jquery.loading.min.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/notify/bootstrap-notify.min.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/SimpleAjaxUploader.min.js',
			CClientScript::POS_END
		);
				
				
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/jquery.validate.min.js',
			CClientScript::POS_END
		);
		
		if(Yii::app()->functions->isAdminLogin()){
			Yii::app()->clientScript->registerScriptFile(
		        Yii::app()->baseUrl . '/'.APP_FOLDER.'/ajax/validate_lang',
				CClientScript::POS_END
			);
		}
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/chosen/chosen.jquery.min.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/select2/select2.min.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/app.js',
			CClientScript::POS_END
		);
		

		/*END JS FILE*/
				
		/*CSS FILE*/
		$baseUrl = Yii::app()->baseUrl."/protected/modules/".APP_FOLDER; 		
		$cs = Yii::app()->getClientScript();		
		$cs->registerCssFile($baseUrl."/assets/vendor/bootstrap-material-design/css/bootstrap-material-design.min.css");		
		$cs->registerCssFile($baseUrl."/assets/vendor/fontawesome/css/all.min.css");
		$cs->registerCssFile($baseUrl."/assets/vendor/jquery.webui-popover.min.css");		
		$cs->registerCssFile($baseUrl."/assets/vendor/datatables/datatables.min.css");
		
		$cs->registerCssFile($baseUrl."/assets/css/animate.min.css");
		$cs->registerCssFile($baseUrl."/assets/vendor/loader/jquery.loading.min.css");									
		$cs->registerCssFile($baseUrl."/assets/vendor/chosen/chosen.min.css");	
		$cs->registerCssFile($baseUrl."/assets/vendor/select2/select2.min.css");	
		
		$cs->registerCssFile($baseUrl."/assets/css/app.css?ver=1.0");
		$cs->registerCssFile($baseUrl."/assets/css/responsive.css?ver=1.0");		
		
	}

	public function beforeControllerAction($controller, $action)
	{		
		if(parent::beforeControllerAction($controller, $action))
		{
			// this method is called before any module controller action is performed
			// you may place customized code here									
			return true;
		}
		else
			return false;
	}
}



function st($words='', $params=array())
{	
	return Yii::t("singleapp",$words,$params);
}
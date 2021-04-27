<?php
/* ********************************************************
 *   Karenderia Merchant app Version 2
 *
 *   Last Update : 12 July 2020 Version 1.0
 *   Last Update : 24 July 2020 Version 1.0.1
 *   Last Update : 24 September 2020 Version 1.0.2
 *   Last Update : 23 November 2020 Version 1.0.3
 *   Last Update : 25 November 2020 Version 1.0.4
 *   Last Update : 01 December 2020 Version 1.0.5
 *   Last Update : 13 January 2021 Version 1.0.6
 *   Last Update : 13 April 2021 Version 1.0.7
 *
***********************************************************/

define("KARENDERIA_APP_VERSION",'1.0.7');
define("APP_FOLDER",'merchantappv2');
define("APP_BTN",'btn-raised btn-info');
define("APP_BTN2",'btn-raised');
define("CHANNEL_ID",'merchantapp_channel');
define("CHANNEL_TOPIC",'/topics/new_order_');
define("CHANNEL_TOPIC_ALERT",'/topics/merchant_alert_');
define("CHANNEL_SOUNDNAME",'neworder');
define("CHANNEL_SOUNDFILE",'neworder.mp3');

class Merchantappv2Module extends CWebModule
{
	public $defaultController='home';	
	static $global_dict;
	 
	public function init()
	{		
		
		$session = Yii::app()->session;
				
		$this->setImport(array(			
			APP_FOLDER.'.components.*',
			APP_FOLDER.'.models.*',
			APP_FOLDER.'.components.*',
		));			
		require_once 'Functions.php';
		
		$ajaxurl=Yii::app()->baseUrl.'/'.APP_FOLDER.'/ajax';
		
		Yii::app()->clientScript->scriptMap=array(
          'jquery.js'=>false,
          'jquery.min.js'=>false
        );

		$cs = Yii::app()->getClientScript();  
		
		FunctionsV3::handleLanguage();
		$lang=Yii::app()->language;				
		$cs = Yii::app()->getClientScript();
		$cs->registerScript(
		  'lang',
		  "var lang='$lang';",
		  CClientScript::POS_HEAD
		);
						
		$dict = LanguageWrapper::getTranslation();		
		self::$global_dict = $dict;
				
		$dict=json_encode($dict);
		$cs->registerScript(
		  'dict',
		  "var dict=$dict;",
		  CClientScript::POS_HEAD
		);
		
		$cs->registerScript(
		  'ajaxurl',
		 "var ajaxurl='$ajaxurl';",
		  CClientScript::POS_HEAD
		);
		
		
        $csrfTokenName = Yii::app()->request->csrfTokenName;
        $csrfToken = Yii::app()->request->csrfToken;        
        
		$cs->registerScript(
		  "$csrfTokenName",
		 "var $csrfTokenName='$csrfToken';",
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

		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/select2/select2.min.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/tagtify/tagify.min.js',
			CClientScript::POS_END
		);
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/vendor/tagtify/jQuery.tagify.min.js',
			CClientScript::POS_END
		);
					
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/app.js',
			CClientScript::POS_END
		);
		
		Yii::app()->clientScript->registerScriptFile(
	        Yii::app()->baseUrl . '/protected/modules/'.APP_FOLDER.'/assets/js/map_wrapper.js',
			CClientScript::POS_END
		);
		
		/*END JS FILE*/
				
		/*CSS FILE*/
		$baseUrl = Yii::app()->baseUrl."/protected/modules/".APP_FOLDER; 		
		$cs = Yii::app()->getClientScript();		
		$cs->registerCssFile($baseUrl."/assets/vendor/bootstrap-material-design/css/bootstrap-material-design.min.css");		
		
		$cs->registerCssFile($baseUrl."/assets/vendor/jquery.webui-popover.min.css");
		$cs->registerCssFile($baseUrl."/assets/vendor/fontawesome/css/all.min.css");
		$cs->registerCssFile($baseUrl."/assets/vendor/datatables/datatables.min.css");
		
		$cs->registerCssFile($baseUrl."/assets/css/animate.min.css");
		$cs->registerCssFile($baseUrl."/assets/vendor/loader/jquery.loading.min.css");			
		$cs->registerCssFile($baseUrl."/assets/vendor/select2/select2.min.css");			
				
		$cs->registerCssFile($baseUrl."/assets/vendor/flag-icon/css/flag-icon.min.css");			
		$cs->registerCssFile($baseUrl."/assets/vendor/tagtify/tagify.css");			
		
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

function translate($words='', $params=array())
{
	return Yii::t("merchantappv2",$words,$params);
}
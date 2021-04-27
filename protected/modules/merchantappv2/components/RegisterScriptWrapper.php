<?php
class RegisterScriptWrapper
{
	public static function registerScript($script=array(), $script_name='reg_script')
	{
		$reg_script='';
		if(is_array($script) && count($script)>=1){		
			foreach ($script as $val) {
				$reg_script.="$val\n";
			}
			$cs = Yii::app()->getClientScript(); 
			$cs->registerScript(
			  $script_name,
			  "$reg_script",
			  CClientScript::POS_HEAD
			);		
		}
	}
	
	public static function registerJS($data=array())
	{
		$cs = Yii::app()->getClientScript();
		if(is_array($data) && count($data)>=1){
			foreach ($data as $link) {
				Yii::app()->clientScript->registerScriptFile($link,CClientScript::POS_END);
			}
		}		
	}
	
	public static function registerCSS($data=array())
	{		
		$cs = Yii::app()->getClientScript();		
		if(is_array($data) && count($data)>=1){
			foreach ($data as $link) {
				$cs->registerCssFile($link);
			}
		}		
	}			
}
/*end class*/
<?php
class LanguageWrapper
{
	
	public static function getTranslation()
	{
		$translation=array();
		$enabled_lang=FunctionsV3::getEnabledLanguage();		
		if(is_array($enabled_lang) && count($enabled_lang)>=1){			
			$path=Yii::getPathOfAlias('webroot')."/protected/messages";    	
    	    $res=array_diff(scandir($path), array('..', '.'));    	    
    	    if(is_array($res) && count($res)>=1){
    	    	foreach ($res as $val) {
    	    		if(in_array($val,$enabled_lang)){
    	    			$lang_path=$path."/$val/merchantappv2.php";   
    	    			if (file_exists($lang_path)){       	    						
    	    				$temp_lang='';
		    				$temp_lang=require_once $lang_path;		  		    						
		    				if(is_array($temp_lang) && count($temp_lang)>=1){				
			    				foreach ($temp_lang as $key=>$val_lang) {
			    					$translation[$key][$val]=$val_lang;
			    				}
		    				}
    	    			}
    	    		}
    	    	}
    	    }    	     	    
		}
		return $translation;
	}	
	
}
/*end class*/
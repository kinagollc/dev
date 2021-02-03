<?php
class InventoryMobile
{
	
	public static function enabled()
	{
		if (FunctionsV3::hasModuleAddon('inventory')){
		    if(FunctionsV3::checkIfTableExist('view_item_stocks')){		     	
	     		Yii::app()->setImport(array(			
			       'application.modules.inventory.components.*',
		        ));	
		        return true;
		    }
    	}		 
    	return false;
	}
		
}
/*end class*/
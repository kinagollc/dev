<?php
class Item_utility
{	
	public static function paginate()
	{
		return 10;
	}
	
	public static function purifyData($data=array())
	{
		if(is_array($data) && count($data)>=1){
			$p = new CHtmlPurifier(); $new_data=array();
			foreach ($data as $key=>$val) {				
				$new_data[$key]=$p->purify($val);
			}			
			return $new_data;
		}
		return false;
	}
	
	public static function purify($data='')
	{
		$p = new CHtmlPurifier();
		return $p->purify($data);
	}
	
	public static function MultiCurrencyEnabled()
	{
		Yii::app()->setImport(array(			
		  'application.modules.multicurrency.components.*',
	    ));
	    	    
	    $enabled = getOptionA('multicurrency_enabled');	    	    
	    
	    $module = false;
	    if (Yii::app()->hasModule('multicurrency')) {
	    	$module = true;
	    }
	    
	    $module2 = false;
	    if (FunctionsV3::hasModuleAddon('multicurrency')){			
	    	$module2 = true;
	    }
	    	    
	    if($enabled==1 && $module && $module2){
	    	return true;
	    }
	    return false;
	}
	
	public static function InventoryEnabled($merchant_id=0)
	{
		Yii::app()->setImport(array(			
		  'application.modules.inventory.components.*',
	    ));
	    	    
	    $enabled = getOption( (integer) $merchant_id,'inventory_live');
	    
	    $module = false;
	    if (Yii::app()->hasModule('inventory')) {
	    	$module = true;
	    }
	    
	    $module2 = false;
	    if (FunctionsV3::hasModuleAddon('inventory')){			
	    	$module2 = true;
	    }
	    	    	    
	    if($enabled==1 && $module && $module2){	    	
	    	return true;
	    }
	    return false;
	}	
	
	public static function defaultExchangeRate($currency_code='')
	{
		$admin_currency_set = getOptionA('admin_currency_set');
		return array(
		  'used_currency'=>$admin_currency_set,
		  'base_currency'=>$admin_currency_set,
		  'exchange_rate'=>1		  
		);
	}
	
	public static function getRates()
	{
		$rates = Yii::app()->session['exchange_rate'];
        $exchange_rate = isset($rates['exchange_rate'])? (float) $rates['exchange_rate']:1;
        return $exchange_rate;
	}
	
	public static function InitMultiCurrency()
	{
		Yii::app()->session['currency'] = isset($_GET['mc_currency']) ? $_GET['mc_currency']: Yii::app()->session['currency'] ;				
		$currency_use = Yii::app()->session['currency'];
				
		if (Item_utility::MultiCurrencyEnabled()){		    		
									
			if(empty($currency_use)){
				if( $resp_location = Multicurrency_utility::handleAutoDetecLocation() ){				
					$currency_use = $resp_location;
					Yii::app()->session['currency'] = $currency_use;
				}			
			}
						
			$rates = Multicurrency_finance::getExchangeRate( $currency_use );			
			Multicurrency_utility::includeFrontendLibrary();			    		
		} else {				
			$rates = Item_utility::defaultExchangeRate( $currency_use );							
		} 
		
					
		if($currency_use!=$rates['used_currency']){			
			Yii::app()->session['currency'] = isset($rates['used_currency'])?$rates['used_currency']:'';
		}					
					
		Price_Formatter::init( Yii::app()->session['currency']  );
		Yii::app()->session['exchange_rate'] = $rates;
	}

	public static function queryCurrency()
	{
		$stmt = ',
		IFNULL((
		select 
		GROUP_CONCAT(
		CONCAT_WS("|",number_decimal,decimal_separator,thousand_separator,currency_position,currency_symbol)
		SEPARATOR "~"
		)
		from {{currency}}
		where
		currency_code = (
		      select used_currency
		      from {{order_delivery_address}}
		      where
		      order_id = a.order_id
		      limit 0,1
		   )				
		),(		
		select 
		GROUP_CONCAT(
		CONCAT_WS("|",number_decimal,decimal_separator,thousand_separator,currency_position,currency_symbol)
		SEPARATOR "~"
		)
		from {{currency}}
		where
		as_default = 1		
		limit 0,1
		)
		
		) as currency_format
		';
		return $stmt;
	}
	
}
/*end class*/
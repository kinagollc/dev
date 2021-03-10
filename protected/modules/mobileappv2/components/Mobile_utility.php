<?php
class Mobile_utility
{	
	public static $currency;	
	public static $exchange_rates = array();	
	public static $price_formater = false;
	
	public static function fileExist($path='')
	{		
		if(!empty($path)){
			$filepath = Yii::getPathOfAlias('webroot')."/protected/$path";	
			if(file_exists($filepath)){
				return true;
			}
		}
		return false;
	}
	
	public static function getDefaultImagePlaceholder()
	{
		return 'resto_banner.jpg';
	}
	
	public static function InitMultiCurrency($currency_use='')
	{		
		$rates = array();
		self::$currency  = $currency_use;
		
		if (Item_utility::MultiCurrencyEnabled()){

			if(empty($currency_use)){
				if( $resp_location = Multicurrency_utility::handleAutoDetecLocation() ){				
					$currency_use = $resp_location;		
					self::$currency = $currency_use;
				}			
			}
						
			$rates = Multicurrency_finance::getExchangeRate( $currency_use );					
		} else {				
			$rates = Item_utility::defaultExchangeRate( $currency_use );							
		} 
		
		if($currency_use!=$rates['used_currency']){			
			self::$currency = isset($rates['used_currency'])?$rates['used_currency']:'';
		}		
						
		
		Price_Formatter::init( self::$currency );
		self::$exchange_rates  = $rates;
	}
	
	public static function getRates()
	{
		$rates = self::$exchange_rates;
        $exchange_rate = isset($rates['exchange_rate'])? (float) $rates['exchange_rate']:1;
        return $exchange_rate;
	}
	
	public static function formatNumber($amount=0)
	{		
		if( self::$price_formater){
			return Price_Formatter::formatNumber($amount);
		} else return FunctionsV3::prettyPrice($amount);
	}
	
	public static function getOptionsArray($options_array=array(), $merchant_id=0)
	{
		$in_stmt= ''; $data = array();
		if(is_array($options_array) && count($options_array)>=1){
			foreach ($options_array as $val) {
				$in_stmt.=q($val).",";
			}
			$in_stmt = substr($in_stmt,0,-1);
		}
		$stmt = "
		SELECT option_name,option_value
		FROM {{option}}
		WHERE
		merchant_id=".q((integer)$merchant_id)." AND option_name IN ($in_stmt)
		GROUP BY option_name,option_value
		ORDER BY id DESC
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
				$data[$val['option_name']] = $val['option_value'];
			}
			return $data;
		}
		return false;
	}
	
}
/*end class*/
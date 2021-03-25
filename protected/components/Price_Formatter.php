<?php 
class Price_Formatter
{
	public static $currency_code='';
	
	public static $number_format=array(
	  'decimals'=>2, 
	  'decimal_separator'=>'.', 
	  'thousand_separator'=>'',
	  'position'=>"left",
	  'spacer'=>"",
	  'currency_symbol'=>"",
	  'show_symbol'=>true
	);
	
	public static function init($currency='')
	{
		Price_Formatter::$currency_code = $currency;
		$res  = array();
		
		if (Item_utility::MultiCurrencyEnabled()){
					
			$stmt="
			SELECT currency_code,currency_symbol,currency_position,
			IF(number_decimal IS NULL or number_decimal = '',  2 , number_decimal )  as number_decimal,
			IF(decimal_separator IS NULL or decimal_separator = '',  '.' , decimal_separator )  as decimal_separator
			,thousand_separator
			
			FROM {{currency}} a
			WHERE currency_code = ".q($currency)."
			LIMIT 0,1
			";
			
			if(!$res = Yii::app()->db->createCommand($stmt)->queryRow()){					
				$stmt="
				SELECT id,currency_code,currency_symbol,currency_position,
				IF(number_decimal IS NULL or number_decimal = '',  2 , number_decimal )  as number_decimal,
				IF(decimal_separator IS NULL or decimal_separator = '',  '.' , decimal_separator )  as decimal_separator
				,thousand_separator
				
				FROM {{currency}} a
				WHERE as_default = 1
				LIMIT 0,1
				";
				$res = Yii::app()->db->createCommand($stmt)->queryRow();
			} 			
		
		} else {		
			
			$new_data = array();
			
			$stmt = "
			SELECT option_name,option_value
			FROM {{option}}
			WHERE option_name IN (
			  'admin_decimal_separator','admin_currency_set',
			  'admin_currency_position','admin_add_space_between_price',			
			  'admin_use_separators','admin_thousand_separator','admin_decimal_place'
			)
			";			
			if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){
				
				foreach ($resp as $val) {
					$new_data[$val['option_name']] = $val['option_value'];
				}
				
				$decimal_separator = isset($new_data['admin_decimal_separator'])?$new_data['admin_decimal_separator']:'.';
				$currency_set = isset($new_data['admin_currency_set'])?$new_data['admin_currency_set']:'USD';
				$currency_position = isset($new_data['admin_currency_position'])?$new_data['admin_currency_position']:'';
				$space_between_price = isset($new_data['admin_add_space_between_price'])?$new_data['admin_add_space_between_price']:'';
				$use_separators = isset($new_data['admin_use_separators'])?$new_data['admin_use_separators']:'';
				$thousand_separator = isset($new_data['admin_thousand_separator'])?$new_data['admin_thousand_separator']:'';
				$decimal_place = isset($new_data['admin_decimal_place'])?$new_data['admin_decimal_place']:'';
				
				if($space_between_price==1){
					if($currency_position=="right"){
						$currency_position = "right_space";
					} else $currency_position = "left_space";
				}			
				
				if($use_separators=="yes"){
					$thousand_separator = !empty($thousand_separator)?$thousand_separator:",";
				}
				
				$res['currency_code'] = $currency_set;
				$res['currency_symbol'] = self::getSymbol( $currency_set );
				$res['currency_position'] = $currency_position;
				$res['number_decimal'] = $decimal_place;
				$res['decimal_separator'] = !empty($decimal_separator)?$decimal_separator:'.';
				$res['thousand_separator'] = $thousand_separator;			
					
			}			
		}	
		
		if($res){						
			
			$spacer = ""; $currency_position = $res['currency_position'];
			switch ($res['currency_position']) {
				case "left_space":				
				    $spacer = " ";
				    $currency_position = "left";
				break;
				
				case "right_space":	
				    $spacer = " ";
				    $currency_position = "right";
					break;
			
				default:
					//
					break;
			}
									
			Price_Formatter::$number_format = array(
			   'decimals'=>$res['number_decimal'], 
			   'decimal_separator'=>$res['decimal_separator'], 
			   'thousand_separator'=>$res['thousand_separator'], 
			   'position'=>$currency_position,
			   'spacer'=>$spacer,
			   'currency_symbol'=>$res['currency_symbol']
			);			
		}
	}
	
	public static function getSymbol($currency_code='')
	{
		$stmt="
		SELECT currency_symbol 
		FROM {{currency}}
		WHERE 
		currency_code = ".q($currency_code)."
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res['currency_symbol'];
		}
		return '$';
	}
	
	public static function formatNumber($value=0)
	{				
	if($value<=0){
            return '';
        }
		$formatted_number = number_format( (float) $value ,
		   !empty(Price_Formatter::$number_format['decimals'])?Price_Formatter::$number_format['decimals']:2,
		   Price_Formatter::$number_format['decimal_separator'],
		   Price_Formatter::$number_format['thousand_separator']
		);
		
		if(Price_Formatter::$number_format['position']=="left" || self::$number_format['position']=="left_space"){
			return Price_Formatter::$number_format['currency_symbol'].Price_Formatter::$number_format['spacer'].$formatted_number;
		} else {
			return $formatted_number.Price_Formatter::$number_format['spacer'].Price_Formatter::$number_format['currency_symbol'];
		}
	}
	
	public static function convertToRaw($price, $decimal=2)
	{
		if (is_numeric($price)){
		    return number_format($price,$decimal,'.','');
	    }
	    return 0;        
	}
	
	public static function getSpacer($currency_position='')
	{
		$spacer = "";
		switch ($currency_position) {
			case "left_space":				
			    $spacer = " ";			    
			break;
			
			case "right_space":	
			    $spacer = " ";			    
				break;
		
			default:
				//
				break;
		}
		return $spacer;	
	}
	
}
/*end class*/
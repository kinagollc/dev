<?php
class Bluetooth
{
	
	public static function interfaceList()
	{
		return array(
		    'bluetooth'=>translate("bluetooth"),
		    //'wifi'=>translate("wifi"),
		 );
	}
	
	public static function paperWidthList()
	{
		 return array(
		    '58'=>translate("58 mm"),
		    '80'=>translate("80 mm"),
		  );
	}
	
	public static function characterCodeList()
	{
		return array(
		  'CHARCODE_PC437'=>"USA Standard Europe",
		  'CHARCODE_JIS'=>"Japanese Katakana",
		  'CHARCODE_PC850'=>"Multilingual",
		  'CHARCODE_PC860'=>"Portuguese",
		  'CHARCODE_PC863'=>"Canadian-French",
		  'CHARCODE_PC865'=>"Nordic",
		  'CHARCODE_WEU'=>"Simplified Kanji, Hirakana",
		  'CHARCODE_GREEK'=>"Simplified Kanji",
		  'CHARCODE_HEBREW'=>"Simplified Kanji",
		  'CHARCODE_PC1252'=>"Western European Windows Code Set",
		  'CHARCODE_PC866'=>"Cirillic",
		  'CHARCODE_PC852'=>"Latin 2",
		  'CHARCODE_PC858'=>"Euro",
		  'CHARCODE_THAI42'=>"Thai character code 42",
		  'CHARCODE_THAI11'=>"Thai character code 11",
		  'CHARCODE_THAI13'=>"Thai character code 13",
		  'CHARCODE_THAI14'=>"Thai character code 14",
		  'CHARCODE_THAI16'=>"Thai character code 16",
		  'CHARCODE_THAI17'=>"Thai character code 17",
		  'CHARCODE_THAI18'=>"Thai character code 18"
		);
	}
	
	public static function getPrinterList($merchant_id=0,$device_uiid='',$start=0, $total_rows=10,$search_string='',
	$field_sort='a.id', $sort='DESC', $all=true )
	{
		
		$and='';
		if(!empty($search_string)){				
			$and=" AND printer_name LIKE ".q("%$search_string%")." ";
		}				
		
		$stmt="
		select SQL_CALC_FOUND_ROWS a.*
		FROM
		{{printer_list_new}} a		
		WHERE merchant_id = ".q( (integer)$merchant_id )."
		AND
		device_uiid=".q($device_uiid)."
		$and
		ORDER BY $field_sort $sort
		LIMIT $start,$total_rows
		";		
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){        	
        	return $resp;
        }
        return false;     
	}	
	
	
	public static function insertPrinter($merchant_id='',$params=array(), $id='',$device_uiid='')
	{					
		if($id>0){
			$resp = Yii::app()->db->createCommand()
	          ->select('mac_address')
	          ->from('{{printer_list_new}}')   
	          ->where("merchant_id=:merchant_id AND mac_address=:mac_address AND device_uiid=:device_uiid AND id<>:id",array(
	            ':merchant_id'=>$merchant_id,
	            ':mac_address'=>$params['mac_address'],
	            ':device_uiid'=>$device_uiid,
	            ':id'=>$id
	          ))	          
	          ->limit(1)
	          ->queryRow();		
	          if(!$resp){
	          	  $up =Yii::app()->db->createCommand()->update("{{printer_list_new}}",$params,
	          	    'id=:id',
	          	    array(
	          	      ':id'=>$id
	          	    )
	          	  );
	          	  if($up){
	          	  	 return true;
	          	  } else throw new Exception( "Failed cannot update records" );
	          } else throw new Exception( "Name already exist" );
		} else {			
			$resp = Yii::app()->db->createCommand()
	          ->select('mac_address')
	          ->from('{{printer_list_new}}')   
	          ->where("merchant_id=:merchant_id AND mac_address=:mac_address AND device_uiid=:device_uiid",array(
	            ':merchant_id'=>$merchant_id,
	            ':mac_address'=>$params['mac_address'],
	            ':device_uiid'=>$device_uiid
	          ))	          
	          ->limit(1)
	          ->queryRow();		
			if(!$resp){
				if(Yii::app()->db->createCommand()->insert("{{printer_list_new}}",$params)){
					return true;
				} else throw new Exception( "Failed cannot insert records" );
			} else throw new Exception( "printer Mac address already exist" );
		}		
		
		throw new Exception( "an error has occurred" );
	}		
	
	
	public static function deletePrinter($merchant_id='', $ids=array())
	{
		$criteria = new CDbCriteria();
		$criteria->compare('merchant_id', $merchant_id);
		$criteria->addInCondition('id', $ids );
		$command = Yii::app()->db->commandBuilder->createDeleteCommand('{{printer_list_new}}', $criteria);		
		$resp = $command->execute();		
		if($resp){
			return true;
		} else throw new Exception( "Failed cannot delete records" );
	}
	
	public static function getPrinter($printer_id='')
	{
		  $printer_data = array();
		  $resp = FoodItemWrapper::getData("printer_list_new","id=:id",array(
			 ':id'=>$printer_id
			));
		  $printer_data['printer_name']=$resp['printer_name'];
		  $printer_data['mac_address']=$resp['mac_address'];
		  $printer_data['data1']=$resp['data1'];
		  $printer_data['data2']=$resp['data2'];
		  $printer_data['paper_width']=$resp['paper_width'];
		  $printer_data['auto_print']=$resp['auto_print'];
		  $printer_data['char_set']=$resp['char_set'];
		  return $printer_data;
	}
	
	public static function printerOptionsData()
	{
		$prefix="print_"; 
		return array(
		  $prefix.'merchant_name', $prefix.'merchant_address', $prefix.'merchant_contact_phone', $prefix.'printed_date',
		  $prefix.'customer_name', $prefix.'trans_type', $prefix.'payment_type', $prefix.'order_id',
		  $prefix.'date_created', $prefix.'delivery_date', $prefix.'delivery_time', $prefix.'delivery_address',
		  $prefix.'delivery_instruction', $prefix.'location_name', $prefix.'contact_phone', $prefix.'order_change',
		  $prefix.'site_url', $prefix.'footer'
		);
	}
	
	public static function getPrinterOptions($merchant_id='')
	{
		$data = array();
		
		$options_name = self::printerOptionsData();
		
		$que='';
		if(is_array($options_name) && count($options_name)>=1){
			foreach ($options_name as $key=>$val) {
				$que.=q($val).",";
			}
			$que = substr($que,0,-1);
		}
		$stmt="
		SELECT option_name,option_value
		FROM {{option}}		
		WHERE option_name IN ($que)
		AND merchant_id=0
		";	
		if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($resp as $val) {
				switch ($val['option_name']) {
					case "print_footer":
						if(!empty($val['option_value'])){
						   $data[$val['option_name']] = trim($val['option_value']);
						}
						break;
				
					default:
						if($val['option_value']==1){
						   $data[$val['option_name']] = trim($val['option_value']); 
						}
						break;
				}				
			}
		}
		return $data;
	}
	
}
/*end class*/
<?php
class Item_translation
{
	
	public static function insertTranslation($id='',$primary_key='', $column1 = '', $column2='', $data=array(), $table ='')
	{
		$params = array();
		
		if(!Yii::app()->db->schema->getTable($table)){
			return false;
		}
		
		if(Yii::app()->functions->multipleField()){
			if(is_array($data) && count($data)>=1){
				
				Yii::app()->db->createCommand("DELETE FROM $table
				WHERE $primary_key =".q( (integer) $id)."
				")->query();
															
				foreach ($data[$column1] as $lang=>$val) {	
					if(!empty($column2)){
						$params = array(
						  $primary_key=>(integer)$id,
						  $column1=>$val,
						  'language'=>$lang,
						  $column2=>$data[$column2][$lang]
						);
					} else {									
						$params = array(
						  $primary_key=>(integer)$id,
						  $column1=>$val,
						  'language'=>$lang
						);
					}
					$params = Item_utility::purifyData($params);					
					Yii::app()->db->createCommand()->insert($table,$params);
				}								
				return true;
			}
		}
		return false;
	}
	
	public static function deleteTranslation($id='', $primary='', $table='')
	{
		$trans_table = "{{".$table."_translation}}";
		if(Yii::app()->db->schema->getTable($trans_table)){
			$stmt = "
			DELETE FROM $trans_table
			WHERE $primary=".q($id)."
			";	
			try {			
			    Yii::app()->db->createCommand($stmt)->query();
			} catch (Exception $e) {
			    // $e->getMessage()
			}

		}
	}
	
}
/*end class*/
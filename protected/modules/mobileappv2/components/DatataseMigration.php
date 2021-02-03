<?php
class DatataseMigration
{
	
	public static function addColumn($table_name='',$fields = array())
	{
		$stats = array();
		$table_cols = Yii::app()->db->schema->getTable($table_name);
		if(is_array($fields) && count($fields)>=1){
			foreach ($fields as $key=>$val) {
				if(!isset($table_cols->columns[$key])) {							
				   Yii::app()->db->createCommand()->addColumn($table_name,$key,$val);				   
				    $stats[]= "field $key [OK]";
				} else {
					$stats[]= "field $key already exist";
				}							
			}
		}			
		return $stats;																			
	}
	
		
	public static function checkFields($table_name='',$fields = array())
	{		
		$found = false;
		$table_cols = Yii::app()->db->schema->getTable($table_name);
		if (isset($table_cols->columns)){
			foreach ($table_cols->columns as $val) {				
				if(in_array($val->name,$fields)){
					$found = true;
				}
			}
			return $found;
		}		
		return false;		
	}
		
	public static function createTable($table_name='',$fields = array())
	{
		$stats = array();
		if(Yii::app()->db->schema->getTable($table_name)){
			$stats[]= "table $table_name already exist";
		} else {
			Yii::app()->db->createCommand()->createTable(
			 $table_name,
			  $fields,
			'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$stats[]= "table $table_name created";
		}
		return $stats;
	}
	
	public static function createIndex($table_name='', $fields = array())
	{	
		$stats = array();
		foreach ($fields as $val) {		   
		   try {
		      Yii::app()->db->createCommand()->createIndex($val,$table_name,$val);
		      $stats[]  = "index [$val] created";
		   } catch (Exception $e) {
			  $stats[]  = "index [$val] already";
		   }					
		}	
		return $stats;
	}
	
	public static function getTable($table_name=''){
		if(Yii::app()->db->schema->getTable($table_name)){
			return true;
		} else {
			 throw new Exception( Yii::t("mobile2","[table] needs to be created please run the db update",array(
			  '[table]'=>$table_name
			)) );
		}
	}	
	
	public static function alterColumn($table_name='',$field='',$type='')
	{
		if(Yii::app()->db->schema->getTable($table_name)){
			try {
		      Yii::app()->db->createCommand()->alterColumn($table_name,$field,$type);
		      return true;
		   } catch (Exception $e) {
			 throw new Exception($e->getMessage());
		   }					
		} else {
			 throw new Exception( Yii::t("mobile2","[table] needs to be created please run the db update",array(
			  '[table]'=>$table_name
			)) );
		}
	}
	
	public static function homeBannerTranslateMigrate()
	{		
		$fields = array();
		if(Yii::app()->functions->multipleField()){
			$fields=FunctionsV3::getLanguageList(false);
		}
		
		if(is_array($fields) && count($fields)>=1){			
			$stmt="
			SELECT banner_id,title,sub_title
			FROM {{mobile2_homebanner}}
			ORDER BY banner_id ASC						
			";
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				foreach ($res as $val) {					
					$banner_id = (integer) $val['banner_id'];
					$title=array(); $sub_title=array();
					
					foreach ($fields as $lang) {
						$title['default']= isset($val['title'])?$val['title']:'';
						$title[$lang]='';
						
						$sub_title['default']=isset($val['sub_title'])?$val['sub_title']:'';
						$sub_title[$lang]='';
					}
					
					$params = array(
					  'title'=>$title,
					  'sub_title'=>$sub_title,
					);
					
					itemWrapper::insertHomebannerTranslation($banner_id,array(
					  'title'=>$params['title'],
					  'sub_title'=>$params['sub_title']
					));
				}
			}
		}		
	}
	
}
/*end class*/
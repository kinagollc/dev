<?php
class SingleappNotificationWrapper
{	
	public static function isTableView($table_name='')
	{
		$stmt="SELECT TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ".FunctionsV3::q($table_name)." ";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
			if($res['TABLE_TYPE']=="VIEW"){
				return true;
			}
		} 
		return false;
	}
	
	public static function hasPrimaryKey($table_name='')
	{
		$stmt="
		SELECT *  
        FROM information_schema.table_constraints  
        WHERE constraint_type = 'PRIMARY KEY'   
        AND table_name = ".q($table_name)."
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
			return $res;
		} 
		return false;
	}
	
	public static function hasAutoIncrement($table_name='')
	{
		$stmt="
		SHOW CREATE TABLE $table_name
		";			
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){						
            if (preg_match("/AUTO_INCREMENT/i", $res['Create Table'])) {	
            	 return true;
            }	
		} 
		return false;
	}
	
	public static function checkAllTable($required_table=array(),$view_table=array())
	{
		$table_prefix=Yii::app()->db->tablePrefix;					
		$tables = Yii::app()->db->schema->getTableNames();		
		
		$table_from_db = array();
		
		if(is_array($tables) && count($tables)>=1){
			foreach ($tables as $table_name) {
				$table_name_without_prefix = str_replace($table_prefix,"",$table_name);
				
				$table_from_db[]=$table_name_without_prefix;
				
				if(in_array($table_name_without_prefix,$required_table)):
					if(in_array($table_name_without_prefix,$view_table)){				
						if( !self::isTableView($table_prefix.$table_name_without_prefix)){						
							Yii::app()->db->createCommand()->dropTable($table_prefix.$table_name_without_prefix);
							throw new Exception( Yii::t("default","[table] is not a view please run database update",array(
							  '[table]'=>$table_name
							)) );
						}
					} else {
						if(!self::hasPrimaryKey($table_name)){
							throw new Exception( Yii::t("default","[table] has no primary key",array(
							  '[table]'=>$table_name
							)) );
						}					
						if(!self::hasAutoIncrement($table_name)){
							throw new Exception( Yii::t("default","[table] has no auto increment",array(
							  '[table]'=>$table_name
							)) );
						}
					}				
				endif;
			}
		}
		
		if(is_array($table_from_db) && count($table_from_db)>=1){
			foreach ($required_table as $required_table_val) {
				if(!in_array($required_table_val,$table_from_db)){
					throw new Exception( Yii::t("default","table [table] not found. please run the db update",array(
					  '[table]'=>$required_table_val
					)) );
				}
			}
		}				
	}
		
	public static function checkFields($table_name='', $fields=array())
	{				
		$orig_table = $table_name;
		$table_name = "{{{$table_name}}}";		
		if(Yii::app()->db->schema->getTable($table_name)){
			if($table_cols = Yii::app()->db->schema->getTable($table_name)){
				foreach ($fields as $key=>$val) {
					if(!isset($table_cols->columns[$key])) {
						if( !self::isTableView($table_name)){												    
						    throw new Exception( Yii::t("default","[table] needs update please run the db update",array(
							  '[table]'=>$orig_table
							)) );
						} else {
							throw new Exception( Yii::t("default","[table] needs update please run the db update",array(
							  '[table]'=>$orig_table
							)) );
						}
					}
				}
			}
		} else throw new Exception( Yii::t("default","table [table] not found",array(
					  '[table]'=>$table_name
					)) );
	}			
	
	public static function checkRequiredFile()
	{
	     $folder = array(
	       Yii::getPathOfAlias('webroot')."/upload",	      
	       Yii::getPathOfAlias('webroot')."/cronHelper",
	       Yii::getPathOfAlias('webroot')."/protected/runtime"
	     );
	     foreach ($folder as $val) {
	     	if(!is_dir($val)){	     		
	     		 if (!mkdir($val,0777)){	     		
			     	 throw new Exception( Yii::t("default","Folder [folder_name] does not exist please. create this folder manually and set the permission to 777",array(
					  '[folder_name]'=>$val
					)) );
	     		 }
		     }
	     }	     
	     return true;
	}
	
	public static function checkTable($table_name=array())
	{
		foreach ($table_name as $tablename) {						
			if(!Yii::app()->db->schema->getTable("{{{$tablename}}}")){
				throw new Exception( Yii::t("default","[table] not found. database needs update please run the db update",array(
				  '[table]'=>$tablename
				)) );
			}
		}
	}
	
	public static function getCountDeviceMigrate()
	{
		$continue = false;
		try {
			self::checkFields("client",array(
			 'single_app_device_uiid'=>'',
			 'device_id'=>''
			));
			$continue = true;								
		} catch (Exception $e) {
			
		}				
				
		if($continue):
		$stmt="
		SELECT count(*) as total
		FROM {{client}} a
		WHERE  single_app_device_uiid !=''
		AND 
		single_app_device_uiid NOT IN (
		  select device_uiid from {{singleapp_device_reg}}
		  where 
		  device_uiid=a.single_app_device_uiid
		)
		";					
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){							
			if($res['total']>0){						
				throw new Exception( Yii::t("default","[count] device needs to migrate to new table. click here",array(
				  '[count]'=>$res['total']
				)) );					
			}
		}			
		endif;
			
		return true;
	}
			
}
/*end class*/
<?php
class DBTableWrapper
{
	public function __construct()
	{		
	}	
	
	public static function getLangList()
	{
		$lang=array();
		if ($res=FunctionsV3::getLanguageList(false)){
			foreach ($res as $val) {
				$val=str_replace(" ","_",$val);
				$lang[]=$val;
			}				
		}
		return $lang;
	}
	
	public static function alterTablePages()
	{
		if ($res=FunctionsV3::getLanguageList(false)){
			foreach ($res as $val) {						
				$file_path = Yii::getPathOfAlias('webroot')."/protected/messages/$val";	
				if(is_dir($file_path)){	
					$new_field=array(
					  "title_$val"=>"varchar(255) NOT NULL DEFAULT ''",
					  "content_$val"=>"text",
					);
					self::alterTable("mobile2_pages",$new_field);
				}
			}			
		}
	}
	
	public static function alterTable($table='',$new_field='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		$existing_field=array();
		if ( $res = Yii::app()->functions->checkTableStructure($table)){
			foreach ($res as $val) {								
				$existing_field[$val['Field']]=$val['Field'];
			}			
			foreach ($new_field as $key_new=>$val_new) {				
				if (!in_array($key_new,$existing_field)){
					//echo "Creating field $key_new <br/>";
					$stmt_alter="ALTER TABLE ".$prefix."$table ADD $key_new ".$new_field[$key_new];
					//dump($stmt_alter);
				    if ($DbExt->qry($stmt_alter)){
					   //echo "(Done)<br/>";
				   } //else echo "(Failed)<br/>";
				} //else echo "Field $key_new already exist<br/>";
			}
		}
	}	
	
	public static function defaultData()
	{
		$DbExt=new DbExt;			
		$data[] = array(		  
		  'option_name'=>'mobile2_home_cuisine',
		  'option_value'=>1
		);
		$data[] = array(		  
		  'option_name'=>'mobile2_home_all_restaurant',
		  'option_value'=>1
		);
		$data[] = array(		  
		  'option_name'=>'mobileapp2_merchant_list_type',
		  'option_value'=>1
		);
		$data[] = array(		  
		  'option_name'=>'mobileapp2_merchant_menu_type',
		  'option_value'=>3
		);
		$data[] = array(		  
		  'option_name'=>'mobileapp2_distance_results',
		  'option_value'=>1
		);
		$data[] = array(		  
		  'option_name'=>'mobile2_search_data',
		  'option_value'=>'{\"1\":\"open_tag\",\"2\":\"review\",\"3\":\"cuisine\",\"5\":\"minimum_order\",\"6\":\"distace\",\"8\":\"delivery_fee\"}'
		);		
		$data[] = array(		  
		  'option_name'=>'mobileapp2_order_processing',
		  'option_value'=>'[\"pending\",\"paid\",\"accepted\",\"acknowledged\",\"started\",\"inprogress\"]'
		);
		$data[] = array(		  
		  'option_name'=>'mobileapp2_order_completed',
		  'option_value'=>'[\"delivered\",\"successful\"]'
		);
		$data[] = array(		  
		  'option_name'=>'mobileapp2_order_cancelled',
		  'option_value'=>'[\"cancelled\",\"decline\",\"failed\"]'
		);
		
		foreach ($data as $params) {
			$DbExt->insertData("{{option}}",$params);
		}
		unset($DbExt);
	}
	
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
							throw new Exception( Yii::t("mobile2","[table] is not a view please run database update",array(
							  '[table]'=>$table_name
							)) );
						}
					} else {
						if(!self::hasPrimaryKey($table_name)){
							throw new Exception( Yii::t("mobile2","[table] has no primary key",array(
							  '[table]'=>$table_name
							)) );
						}					
						if(!self::hasAutoIncrement($table_name)){
							throw new Exception( Yii::t("mobile2","[table] has no auto increment",array(
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
					throw new Exception( Yii::t("mobile2","table [table] not found",array(
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
						    //Yii::app()->db->createCommand()->addColumn("{{{$table_name}}}",$key,$val);
						    throw new Exception( Yii::t("mobile2","[table] needs update please run the db update",array(
							  '[table]'=>$orig_table
							)) );
						} else {
							throw new Exception( Yii::t("mobile2","[table] needs update please run the db update",array(
							  '[table]'=>$orig_table
							)) );
						}
					}
				}
			}
		} else throw new Exception( Yii::t("mobile2","table [table] not found",array(
					  '[table]'=>$table_name
					)) );
	}
	
	public static function checkUpdatePrimaryKey($data=array())
	{		
		$table_prefix=Yii::app()->db->tablePrefix;		
		if(is_array($data) && count($data)>=1){
			foreach ($data as $table=>$id) {				
				$table_name = $table_prefix."$table";				
				if(Yii::app()->db->schema->getTable($table_name)){					
					if(!DBTableWrapper::hasPrimaryKey($table_name)){
						Yii::app()->db->createCommand("Alter table $table_name add primary key($id)")->query();
					}
					if(!DBTableWrapper::hasAutoIncrement($table_name)){
						Yii::app()->db->createCommand("Alter table $table_name modify $id int(14) NOT NULL AUTO_INCREMENT")->query();		  
					}
				}								
			}
		}		
	}	
	
} /*end class*/
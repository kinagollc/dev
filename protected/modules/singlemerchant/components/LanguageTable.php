<?php
class LanguageTable
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
	
	public static function alterTablePages($table_name='')
	{
		if ($res=FunctionsV3::getLanguageList(false)){
			foreach ($res as $val) {		
				$val=str_replace(" ","_",$val);
				/*$new_field=array(
				  "title_$val"=>"varchar(255) NOT NULL DEFAULT ''",
				  "content_$val"=>"varchar(255) NOT NULL DEFAULT ''",
				);*/
				$new_field=array(
				  "title_$val"=>"varchar(255) NOT NULL DEFAULT ''",
				  "content_$val"=>"text",
				);
				self::alterTable($table_name,$new_field);
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
	
} /*end class*/
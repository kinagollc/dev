<?php
class Item_migration
{
	public static function getItemToMigrate()
	{
		if(Yii::app()->db->schema->getTable("{{item_relationship_size}}")){	
			$stmt="
			SELECT count(*) as total FROM {{item}} a
			WHERE item_id NOT IN (
			  select item_id from {{item_relationship_size}}
			  where item_id = a.item_id
			)
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				if($res['total']>0){
					return $res['total'];
				}
			}
		}
		return false;
	}
	
	public static function getSubItem()
	{
		$total = 0;
		if(Yii::app()->db->schema->getTable("{{subcategory_item_relationships}}")){	
			//LENGTH(category) != 2
			$stmt="
			SELECT count(*) as total
			FROM {{subcategory_item}} a		 
			WHERE 			
			TRIM(IFNULL(category,'')) <> ''
			AND
			sub_item_id NOT IN (
			  select sub_item_id from {{subcategory_item_relationships}}
			  where sub_item_id = a.sub_item_id
			)
			";
			$subcategory_id = array();
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				if($res['total']>0){
					return $res['total'];
				}
			}						
		}
		return false;
	}
	
	public static function GetTranslation($table='', $table_translation='', $id='')
	{
		if(Yii::app()->db->schema->getTable("{{{$table_translation}}}")){	
			$stmt="
			SELECT count(*) as total FROM {{{$table}}} a
			WHERE $id NOT IN (
			  select $id from {{{$table_translation}}}
			  where $id = a.$id
			)
			";					
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				if($res['total']>0){
					return $res['total'];
				}
			}
		}
		return false;
	}	
	
	public static function getItemSubcategoryItem()
	{
		if(Yii::app()->db->schema->getTable("{{item_relationship_subcategory_item}}")){	
			$stmt="
			SELECT count(*) as total FROM {{item}} a
			WHERE 
			TRIM(IFNULL(addon_item,'')) <> ''
			AND
			item_id NOT IN (
			  select item_id from {{item_relationship_subcategory_item}}
			  where item_id = a.item_id
			)
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				if($res['total']>0){
					return $res['total'];
				}
			}
		}
		return false;
	}
}
/*end class*/
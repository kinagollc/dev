<?php
class ItemClass
{
	public static function paginate()
	{
		return 10;
	}
	
	public static function paginateMigrate()
	{
		return 10;
	}
	
	public static function getMerchantMenu($merchant_id='', $todays_day='')
	{
		$and='';
		$enabled_category_sked = getOption($merchant_id,'enabled_category_sked');   
		
		if($enabled_category_sked==1){
    		$and .= " AND $todays_day='1' ";
    	}    
    	
    	$and_category='';
    	$food_option_not_available = getOption($merchant_id,'food_option_not_available');
    	if($food_option_not_available==1){
    		$and_category= " AND not_available=1 ";
    	}
    	
    	$paginate = self::paginate();
    	
		$stmt="
		SELECT 
		cat_id,category_name,category_description,category_name_trans,category_description_trans,photo,dish,	
		
		( 
		 select count(*) from {{item}}
		 where
		 merchant_id=".q($merchant_id)."
		 AND status in ('publish','published')
		) as total_item,
			
		(
		select count(*) from {{view_rs_category}}
		where
		cat_id=a.cat_id
		$and_category
		) as total_item_in_category
				
		 FROM
		{{category}} a
		WHERE 
		merchant_id= ".FunctionsV3::q($merchant_id)."
		AND status in ('publish','published')
		$and
		ORDER BY sequence ASC
		";												
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){						
			foreach ($res as $val) {										
				$total_cat_paginate = $val['total_item_in_category']>0? ceil($val['total_item_in_category']/$paginate) : 0;
				$total_item = $val['total_item']>0? ceil($val['total_item']/$paginate) : 0;				
				$data[]=array(
				  'category_id'=>$val['cat_id'],
				  'category_name'=>clearString($val['category_name']),
				  'category_description'=>clearString($val['category_description']),
				  'category_name_trans'=>!empty($val['category_name_trans'])?json_decode($val['category_name_trans'],true):'',
				  'photo'=>$val['photo'],
				  'category_description_trans'=>!empty($val['category_description_trans'])?json_decode($val['category_description_trans'],true):'',
				  'dish'=>$val['dish'],
				  'item'=>array(),				  
				  'total_item_in_category'=>$val['total_item_in_category'],
				  'total_cat_paginate'=>$total_cat_paginate,
				  'total_item'=>$total_item+1
				);
			}			
			return $data;
		}
		return false;
	}

	
	/*	
	@parameters	
	$data = 
	Array
	(
	    [0] => 1
	)*/
	public static function insertItemRelationship($merchant_id='',$item_id=0, $data=array())
	{
		self::deleteItemRelationshipCategory($merchant_id,$item_id);
		if(is_array($data) && count($data)>=1){
			foreach ($data as $cat_id) {
				$params = array(
				  'merchant_id'=>(integer)$merchant_id,
				  'item_id'=>(integer)$item_id,
				  'cat_id'=>(integer)$cat_id
				);				
				Yii::app()->db->createCommand()->insert("{{item_relationship_category}}",$params);
			}
		}
	}
	
	public static function deleteItemRelationshipCategory($merchant_id='',$item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{item_relationship_category}}', 
		'merchant_id=:merchant_id AND item_id=:item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$item_id
		));		
	}
	
	
	/*	
	@parameters
	$data = 
	  [sub_item_id] => Array
        (
            [1] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

    )*/	
	public static function insertItemRelationshipSubcategory($merchant_id='',$item_id=0, $data=array())
	{
		self::deleteItemRelationshipSubcategory($merchant_id,$item_id);
		self::deleteItemRelationshipSubcategoryItem($merchant_id,$item_id);
		
		if(is_array($data) && count($data)>=1){
			foreach ($data as $subcat_id=>$val) {
				$params = array(
				  'merchant_id'=>(integer)$merchant_id,
				  'item_id'=>(integer)$item_id,
				  'subcat_id'=>(integer)$subcat_id
				);	
				Yii::app()->db->createCommand()->insert("{{item_relationship_subcategory}}",$params);
				
				if(is_array($val) && count($val)>=1){
					foreach ($val as $sub_item_id) {
						$params_sub = array(
						  'merchant_id'=>$merchant_id,
						  'item_id'=>$item_id,
						  'subcat_id'=>$subcat_id,
						  'sub_item_id'=>$sub_item_id,
						);						
						Yii::app()->db->createCommand()->insert("{{item_relationship_subcategory_item}}",$params_sub);
					}
				}				
			}
		}
	}
	
	public static function deleteItemRelationshipSubcategory($merchant_id='',$item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{item_relationship_subcategory}}', 
		'merchant_id=:merchant_id AND item_id=:item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$item_id
		));		
	}
	
	public static function deleteItemRelationshipSubcategoryItem($merchant_id='',$item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{item_relationship_subcategory_item}}', 
		'merchant_id=:merchant_id AND item_id=:item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$item_id
		));		
	}
	
	/*	
	@parameters
	$data = 
	  Array
            (
                [0] => 1
                [1] => 2
                [2] => 3
            )
    )*/	
	public static function insertSubcategoryItemRelationship($sub_item_id=0, $data=array())
	{
		self::deleteSubcategoryItem($sub_item_id);		
		if(is_array($data) && count($data)>=1){
			foreach ($data as $subcat_id) {
				$params = array(
				  'subcat_id'=>(integer)$subcat_id,
				  'sub_item_id'=>(integer)$sub_item_id
				);				
				Yii::app()->db->createCommand()->insert("{{subcategory_item_relationships}}",$params);				
			}
		}
	}
	
	public static function deleteSubcategoryItem($sub_item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{subcategory_item_relationships}}', 
		'sub_item_id=:sub_item_id ', 
		 array( 		  
		  ':sub_item_id'=>(integer)$sub_item_id
		));		
	}
	
	/*
	@parameters
	size] => Array
        (
            [0] => 0
        )

    [price] => Array
        (
            [0] => 1
        )
        */
	public static function insertItemRelatinship($merchant_id='',$item_id='',$data=array())
	{							
		self::deleteItemRelationship($merchant_id,$item_id);				
		if(is_array($data['size']) && count($data['size'])>=1){
			foreach ($data['size'] as $key=>$size_id) {						
				$price = isset($data['price'][$key])?$data['price'][$key]:0;
				$token=self::generateFoodSizeToken();
				$params = array(
				  'merchant_id'=>(integer)$merchant_id,
				  'item_id'=>(integer)$item_id,
				  'size_id'=>(integer)$size_id,
				  'price'=>(float)$price,
				  'created_at'=>FunctionsV3::dateNow(),
				  'item_token'=>$token
				);						
				Yii::app()->db->createCommand()->insert("{{item_relationship_size}}",$params);				
			}
		} else {
			$token=self::generateFoodSizeToken();
			$params = array(
			  'merchant_id'=>(integer)$merchant_id,
		      'item_id'=>(integer)$item_id,
			  'size_id'=>(integer)0,
			  'price'=>(float)0,
			  'created_at'=>FunctionsV3::dateNow(),
			  'item_token'=>$token
			);					
			Yii::app()->db->createCommand()->insert("{{item_relationship_size}}",$params);				
		}
	}
	
	public static function deleteItemRelationship($merchant_id='',$item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{item_relationship_size}}', 
		'merchant_id=:merchant_id  AND item_id=:item_id ', 
		 array( 		  
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$item_id
		));		
	}
	
	public static function generateFoodToken()
	{		
		$token=FunctionsV3::generateCode(20);
		$resp = Yii::app()->db->createCommand()
          ->select('item_token')
          ->from('{{item}}')   
          ->where("item_token=:item_token",array(
            ':item_token'=>$token            
          ))	          
          ->limit(1)
          ->queryRow();		
	    if($resp){
	    	$token=self::generateFoodToken();
	    }
	    return $token;
	}
	
	public static function generateFoodSizeToken()
	{		
		$token=FunctionsV3::generateCode(20);
		$resp = Yii::app()->db->createCommand()
          ->select('item_token')
          ->from('{{item_relationship_size}}')   
          ->where("item_token=:item_token",array(
            ':item_token'=>$token            
          ))	          
          ->limit(1)
          ->queryRow();		
	    if($resp){
	    	$token=self::generateFoodToken();
	    }
	    return $token;
	}
	
	public static function getItemByCategory($merchant_id='', $cat_id='',$page=0,$page_limit=10)
	{
		$and='';
        
        $food_option_not_available=getOption($merchant_id,'food_option_not_available');		
		if (!empty($food_option_not_available)){
			if ($food_option_not_available==1){
				$and.=" AND not_available!='2'";
			}
		}		
        		
		$stmt="
		SELECT 
		cat_id,category_name,item_id,item_name,price as raw_price,price,size_id,size_name,photo,not_available,discount,
		cooking_ref, ingredients, item_description, category_sequence,
		category_description,
		(
		 select count(*) from {{item_relationship_subcategory}}
		 where
		 item_id=a.item_id
		) as total_addon
		
		FROM {{view_item_cat}} a
		WHERE merchant_id=".q($merchant_id)."
		AND status IN ('publish','published')
		AND cat_id=".q($cat_id)."
		$and
		GROUP BY item_id,cat_id				
		ORDER BY category_sequence,cat_id,item_id ASC
		LIMIT $page,$page_limit
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			return self::prepareResults($res);
		}
		return false;
	}
	
	public static function prepareResults($res=array())
	{
		$data = array();
		foreach ($res as $val) {				
			$single = 2;
			
			if(strlen($val['cooking_ref'])>0){
				$single = 1;
			}
			if(strlen($val['ingredients'])>0){
				$single = 1;
			}
			if($val['total_addon']>0){
				$single = 1;
			}			
			$val['item_name'] = clearString($val['item_name']);
			
			$val['description_dummy'] = false;
			if (strlen($val['item_description'])<59){
				$val['description_dummy'] = true;
			}
			$val['item_description'] = clearString($val['item_description']);
			
			if(isset($val['category_description'])){
			   $val['category_description'] = clearString($val['category_description']);
			}
			
			$val['photo'] = FunctionsV3::getFoodDefaultImage($val['photo'],false);
			if($val['discount']>0){
				$val['discount_price'] = FunctionsV3::prettyPrice($val['price']-$val['discount']);
			} else $val['discount_price']='';
			
			$val['price'] = FunctionsV3::prettyPrice($val['price']);				
			$val['single_item'] = $single;
			$data[]=$val;
		}	
		return $data;		
	}

	public static function getCategory($category_id=0)
	{
		$stmt = "
		SELECT 
		cat_id,category_name,category_description,photo,status,category_name_trans,category_description_trans
		FROM {{category}}
		WHERE cat_id=".q($category_id)."
		AND status IN ('publish','published')
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
	public static function searchByItem($search_string='',$merchant_id, $page=0, $page_limit=10)
	{
		$and='';
        
        $food_option_not_available=getOption($merchant_id,'food_option_not_available');		
		if (!empty($food_option_not_available)){
			if ($food_option_not_available==1){
				$and.=" AND not_available!='2'";
			}
		}		
        		
		$stmt="
		SELECT 
		cat_id,category_name,item_id,item_name,price as raw_price,price,size_id,size_name,photo,not_available,discount,
		cooking_ref, ingredients, item_description, category_sequence,
		(
		 select count(*) from {{item_relationship_subcategory}}
		 where
		 item_id=a.item_id
		) as total_addon
		
		FROM {{view_item_cat}} a
		WHERE merchant_id=".q($merchant_id)."
		AND status IN ('publish','published')
		AND item_name LIKE ".q("%$search_string%")."
		$and
		GROUP BY item_id,cat_id				
		ORDER BY category_sequence,cat_id,item_id ASC
		LIMIT $page,$page_limit
		";			
		//dump($stmt);
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			return self::prepareResults($res);
		}
		return false;
	}
	
	public static function getData($table='',$where='',$where_val=array())
	{
		$resp = Yii::app()->db->createCommand()
	      ->select('')
	      ->from("{{{$table}}}")   
	      ->where($where,$where_val)	          
	      ->limit(1)
	      ->queryRow();	
	      if($resp){
	      	return $resp;
	      } else throw new Exception( "Record not found" );	      
	}
	
	public static function deleteRelationship($id='', $primary='', $table='')
	{
		$trans_table = "{{".$table."}}";
		if(Yii::app()->db->schema->getTable($trans_table)){
			$stmt = "
			DELETE FROM $trans_table
			WHERE $primary=".q($id)."
			";	
			Yii::app()->db->createCommand($stmt)->query();
		}
	}
	
	public static function deleteCategory($merchant_id=0, $cat_id=0)
	{
		if($res = Yii::app()->db->createCommand("SELECT
		cat_id FROM {{order_details}}
		WHERE cat_id = ".q($cat_id)."
		")->queryRow()){
			throw new Exception( "Cannot delete this item it has reference to order table" );	      
		}
		
		$photo = '';
		if($data = Yii::app()->db->createCommand("SELECT
		photo FROM {{category}}
		WHERE cat_id = ".q($cat_id)."
		")->queryRow()){
			$photo = $data['photo'];
		}		
					
		$resp = Yii::app()->db->createCommand()->delete('{{category}}', 
		'merchant_id=:merchant_id AND cat_id=:cat_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':cat_id'=>(integer)$cat_id
		));		
		if($resp){			
									
			self::deleteRelationship($cat_id, 'cat_id','category_translation');
			self::deleteRelationship($cat_id, 'cat_id','item_relationship_category');
			if(!empty($photo)){
				FunctionsV3::deleteUploadedFile($photo);
			}
			
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}
		
	public static function deleteSize($merchant_id=0, $size_id=0)
	{
		if($res = Yii::app()->db->createCommand("SELECT
		cat_id FROM {{order_details}}
		WHERE size_id = ".q($size_id)."
		")->queryRow()){
			throw new Exception( "Cannot delete this item it has reference to order table" );	      
		}
								
		$resp = Yii::app()->db->createCommand()->delete('{{size}}', 
		'merchant_id=:merchant_id AND size_id=:size_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':size_id'=>(integer)$size_id
		));		
		if($resp){			
									
			self::deleteRelationship($size_id, 'size_id','size_translation');
			self::deleteRelationship($size_id, 'size_id','item_relationship_size');
			
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}
	
	public static function deleteAddonCategory($merchant_id=0, $id=0)
	{							
		$resp = Yii::app()->db->createCommand()->delete('{{subcategory}}', 
		'merchant_id=:merchant_id AND subcat_id=:subcat_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':subcat_id'=>(integer)$id
		));		
		if($resp){			
									
			self::deleteRelationship($id, 'subcat_id','subcategory_translation');
			self::deleteRelationship($id, 'subcat_id','item_relationship_subcategory');
			
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}	
	
	public static function deleteAddonItem($merchant_id=0, $id=0)
	{		
		$photo = '';
		if($data = Yii::app()->db->createCommand("SELECT
		photo FROM {{subcategory_item}}
		WHERE sub_item_id = ".q($id)."
		")->queryRow()){
			$photo = $data['photo'];
		}		
					
		$resp = Yii::app()->db->createCommand()->delete('{{subcategory_item}}', 
		'merchant_id=:merchant_id AND sub_item_id=:sub_item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':sub_item_id'=>(integer)$id
		));		
		if($resp){			
									
			self::deleteRelationship($id, 'sub_item_id','subcategory_item_translation');
			self::deleteRelationship($id, 'sub_item_id','subcategory_item_relationships');
			if(!empty($photo)){
				FunctionsV3::deleteUploadedFile($photo);
			}
			
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}
	
	public static function deleteIngredients($merchant_id=0, $id=0)
	{							
		$resp = Yii::app()->db->createCommand()->delete('{{ingredients}}', 
		'merchant_id=:merchant_id AND ingredients_id=:ingredients_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':ingredients_id'=>(integer)$id
		));		
		if($resp){												
			self::deleteRelationship($id, 'ingredients_id','ingredients_translation');		
			self::deleteMetaID($merchant_id,'ingredients',$id);				
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}	
	
	public static function deleteCookingRef($merchant_id=0, $id=0)
	{							
		$resp = Yii::app()->db->createCommand()->delete('{{cooking_ref}}', 
		'merchant_id=:merchant_id AND cook_id=:cook_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':cook_id'=>(integer)$id
		));		
		if($resp){											
			self::deleteRelationship($id, 'cook_id','cooking_ref_translation');
			self::deleteMetaID($merchant_id,'cooking_ref',$id);
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}	
	
	public static function deleteFoodItem($merchant_id=0, $id=0)
	{											
		if($res = Yii::app()->db->createCommand("SELECT
		cat_id FROM {{order_details}}
		WHERE item_id = ".q($id)."
		")->queryRow()){
			throw new Exception( "Cannot delete this item it has reference to order table" );	      
		}
				
		
		$photo = ''; $gallery_photo = array();
		if($data = Yii::app()->db->createCommand("SELECT
		photo,gallery_photo FROM {{item}}
		WHERE item_id = ".q($id)."
		")->queryRow()){
			$photo = $data['photo'];
			$gallery_photo = json_decode($data['gallery_photo'],true);
		}		
		
		$resp = Yii::app()->db->createCommand()->delete('{{item}}', 
		'merchant_id=:merchant_id AND item_id=:item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$id
		));		
				
		if($resp){											
			self::deleteRelationship($id, 'item_id','item_translation');
			self::deleteRelationship($id, 'item_id','item_relationship_category');
			self::deleteRelationship($id, 'item_id','item_relationship_subcategory');
			self::deleteRelationship($id, 'item_id','item_relationship_subcategory_item');
			self::deleteRelationship($id, 'item_id','item_relationship_size');
			self::deleteAllMeta($merchant_id,$id);
			
			if(!empty($photo)){
				FunctionsV3::deleteUploadedFile($photo);
			}
			if(is_array($gallery_photo) && count($gallery_photo)>=1){
				foreach ($gallery_photo as $val) {
					FunctionsV3::deleteUploadedFile($val);
				}
			}			
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}	
	
	/*
	@parameters
	$meta_id = 
	    (
            [0] => 1
            [1] => 4
        )
	*/
	public static function insertMeta($merchant_id='',$item_id='', $meta_name='', $meta_id=array() , $default=true)
	{
		if(!Yii::app()->db->schema->getTable("{{item_meta}}")){
			return false;
		}
		
		ItemClass::deleteMeta($merchant_id,$item_id, $meta_name);
		
		$stmt_insert = '';
		if(is_array($meta_id) && count($meta_id)>=1){
			foreach ($meta_id as $id) {		
				if($default){		
				    $stmt_insert.= "(NULL,".(integer)$merchant_id.",".q( (integer) $item_id).",".q($meta_name).",".q( (integer) $id)." ),";				    
				} else {
					
					$stmt_insert.= "(NULL,".(integer)$merchant_id.",".q( (integer) $item_id).",".q($meta_name).",".q( $id)." ),";
				}
			}
			$stmt_insert = substr($stmt_insert,0,-1);
			$stmt = "
			INSERT INTO {{item_meta}} 
			VALUES $stmt_insert;
			";								
			if(Yii::app()->db->createCommand($stmt)->query()){
				return true;
			}
		}		
		return false;
	}
	
	public static function deleteMeta($merchant_id='',$item_id='', $meta_name='')
	{
		$stmt = "
		DELETE FROM {{item_meta}}
		WHERE 
		merchant_id = ".q( (integer) $merchant_id)."
		AND item_id = ".q( (integer) $item_id)."
		AND meta_name = ".q($meta_name)."
		";				
		if(Yii::app()->db->createCommand($stmt)->query()){
			return true;
		}
		return false;
	}
	
	public static function deleteAllMeta($merchant_id='',$item_id='')
	{
		$resp = Yii::app()->db->createCommand()->delete('{{item_meta}}', 
		'merchant_id=:merchant_id AND item_id=:item_id ', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':item_id'=>(integer)$item_id
		));		
	}
	
	public static function deleteMetaID($merchant_id='',$meta_name='', $meta_id='')
	{
		Yii::app()->db->createCommand()->delete('{{item_meta}}', 
		'merchant_id=:merchant_id AND meta_name=:meta_name AND meta_id=:meta_id', 
		 array( 
		  ':merchant_id'=>(integer)$merchant_id,
		  ':meta_id'=>(integer)$meta_id,
		  ':meta_name'=>$meta_name
		));	
	}
	
	public static function deleteDishes($dish_id=0)
	{							
		$resp = Yii::app()->db->createCommand()->delete('{{dishes}}', 
		'dish_id=:dish_id', 
		 array( 
		  ':dish_id'=>(integer)$dish_id		  
		));		
		if($resp){											
			//self::deleteRelationship($id, 'dish_id','dish_translation');			
			$resp = Yii::app()->db->createCommand()->delete('{{item_meta}}', 
			'meta_name=:meta_name AND meta_id=:meta_id ', 
			 array( 
			  ':meta_name'=>'dish',
			  ':meta_id'=>(integer)$dish_id
			));		
						
			self::deleteRelationship($dish_id, 'dish_id','dishes_translation');
				
			return true;
		}
		throw new Exception( "Failed cannot delete records" );	      
	}	
	
	public static function getDishes($dish_id=0)
	{
		$data = array();
		$stmt = "
		SELECT 
		a.dish_id,
		a.dish_name,
		a.photo,
		a.status,
		IFNULL(b.language,'default') as language,
		IFNULL(b.dish_name,'') as  dish_name_trans
		
		FROM {{dishes}} a		
		LEFT JOIN {{dishes_translation}} b
		ON
		a.dish_id = b.dish_id
		
		WHERE a.dish_id = ".q($dish_id)."
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
				$data[$val['language']] = array(
				 'dish_id'=>$val['dish_id'],
				 'dish_name'=> $val['language']=="default"?$val['dish_name']:$val['dish_name_trans'],
				 'photo'=>$val['photo'],
				 'status'=>$val['status'],
				);
			}
			return $data;
		}
		return false;
	}
	
	public static function getMerchantCategory($merchant_id=0)
	{
		$stmt="
		SELECT * FROM {{category}}
		WHERE merchant_id = ".q($merchant_id)."
		AND status ='publish'		
		ORDER BY category_name ASC
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			return $res;
		}
		return false;
	}
	
}
/*end class*/
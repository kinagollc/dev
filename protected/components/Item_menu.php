<?php
class Item_menu
{
	
	public static $language='';
	public static $currency_code ='';	
	
	public static $multi_currency = false;
	public static $multi_field = false;
	public static $table_item_translation = false;
	public static $table_view_item_cat = false;
	public static $table_category_translation = false;
	public static $table_size_translation = false;
	public static $table_view_item_stocks_status = false;
	public static $table_item_relationship_size = false;
	
	public static $enabled_category_sked = false;
    public static $food_option_not_available = false;
    public static $paginated = false;
    public static $inventory_enabled = false;
	
	public static function init( $merchant_id = 0 )
	{
		self::$multi_currency = Item_utility::MultiCurrencyEnabled();
		self::$multi_field = Yii::app()->functions->multipleField();
		self::$table_item_translation = Yii::app()->db->schema->getTable("{{item_translation}}");
		self::$table_view_item_cat = Yii::app()->db->schema->getTable("{{view_item_cat}}");
		self::$table_category_translation = Yii::app()->db->schema->getTable("{{category_translation}}");
		self::$table_size_translation = Yii::app()->db->schema->getTable("{{size_translation}}");
		self::$table_view_item_stocks_status = Yii::app()->db->schema->getTable("{{view_item_stocks_status}}");
		self::$table_item_relationship_size = Yii::app()->db->schema->getTable("{{item_relationship_size}}");
		
		self::$enabled_category_sked = getOption($merchant_id,'enabled_category_sked');   
		self::$food_option_not_available = getOption($merchant_id,'food_option_not_available');   		
		self::$inventory_enabled = Item_utility::InventoryEnabled();
		self::$paginated = false;
	}
	
	
	public static function getCategory($merchant_id='', $todays_day='')
	{
				
		$and='';
		$todays_day = strtolower($todays_day);
		
		if(self::$enabled_category_sked==1){
    		$and .= " AND $todays_day='1' ";
    	}    
    	    	    	
		if( self::$multi_field  && self::$table_category_translation  ){
			$stmt="
			SELECT
			a.cat_id as category_id,
			a.merchant_id,
			a.photo,
			a.dish,
			a.category_name_trans,
			a.category_description_trans,
						
			IFNULL((
			SELECT IF(category_name IS NULL or category_name = '', 
			a.category_name, category_name) as category_name
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_name ) as category_name,
			
			IFNULL((
			SELECT IF(category_description IS NULL or category_description = '', 
			a.category_description, category_description) as category_description
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_description ) as category_description
				
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."				
			AND a.status IN ('publish','published')		
			$and
			ORDER BY a.sequence ASC
			";
		} else {
			$stmt = "
			SELECT
			a.cat_id as category_id,
			a.merchant_id,
			a.photo,
			a.dish,
			a.category_name_trans,
			a.category_description_trans,
			a.category_name,
			a.category_description
															
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."		
			AND a.status IN ('publish','published')			
			$and
			ORDER BY a.sequence ASC
			";
		}		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			return $res;
		}
		return false;
	}
	
	public static function getItem($category_id='' , $merchant_id=0)
	{
		$data = array(); $select = ''; $and = '';	 $limit= '';

		if($merchant_id>0){
			$and.= " AND merchant_id = ".q( (integer) $merchant_id)."";
		}
		
		if(self::$inventory_enabled ){
			if(InventoryWrapper::hideItemOutStocks($merchant_id) && self::$table_view_item_stocks_status){
				$and.="
				AND a.item_id IN (
					  select item_id from {{view_item_stocks_status}}
					  where available ='1'
					  and track_stock='1'
					  and stock_status not in ('Out of stocks')		
					  and item_id = a.item_id				  
					)		
				";
			} else {
				if(self::$food_option_not_available==1 && self::$table_item_relationship_size ){
					$and.="
					AND a.item_id IN (
					   select item_id from {{item_relationship_size}}
					   where available ='1'					
					   and item_id = a.item_id		   
					)		
					";
				}
			}
		} else {		
			if(self::$food_option_not_available==1){
				$and.= " AND not_available=1 ";
			}
		}
		
				
		if ( self::$multi_field && self::$table_item_translation  ){
			$select = "
			IFNULL((
			SELECT IF(item_name IS NULL or item_name = '', 
			a.item_name, item_name) 
			from {{item_translation}}
			 where
			 item_id = a.item_id
			 and language = ".q(self::$language)."
			), a.item_name ) as item_name,
						
			
			IFNULL((
			SELECT IF(item_description IS NULL or item_description = '', 
			a.item_description, item_description)
			from {{item_translation}}
			 where
			 item_id = a.item_id
			 and language = ".q(self::$language)."
			), a.item_description ) as item_description,	
			";
		} else {
			$select = "
			a.item_name, a.item_description ,
			";
		}
				
		if( self::$table_view_item_cat  ){	
			$stmt="
			SELECT 
			DISTINCT a.item_id,
			
			$select		
			
			a.discount,a.photo,a.spicydish,a.dish,
			a.item_name_trans,a.item_description_trans,a.not_available,
			a.cat_id, a.item_token,
			a.cooking_ref,a.ingredients,

			(
			  select IF( count(*)>1, 1, 2 ) from {{view_item_cat}}
			  where item_id = a.item_id
			  and cat_id = a.cat_id			  
			) as single_item,
			
			(
			  select count(*) from {{item_relationship_subcategory}}
			  where item_id = a.item_id			  
			) as addon_count,
			
			(
			  select CONCAT_WS(';',price,size_id,size_name,item_size_token) from {{view_item_cat}}
			  where item_id = a.item_id
			  and cat_id = a.cat_id
			  limit 0,1
			) as single_details
			
					
			FROM {{view_item_cat}} a
			WHERE
			a.cat_id = ".q( (integer) $category_id )."		
			AND a.status IN ('publish','published')			
			$and	
			ORDER BY item_sequence,item_id ASC
			";
		} else {
			return false;
		}	
					
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){				
			foreach ($res as $val) {
				$single_details = array();
				
				if(!empty($val['cooking_ref'])){
					$val['single_item'] = 1;
				}
				if(!empty($val['ingredients'])){
					$val['single_item'] = 1;
				}
				if($val['addon_count']>0){
					$val['single_item'] = 1;
				}
				
				if($val['single_item']==2){
					$single_price = !empty($val['single_details'])?explode(";",$val['single_details']):false;
					if($single_price!=false){
						$single_details['price'] = isset($single_price[0])?(float)$single_price[0]:'';
						$single_details['size_id'] = isset($single_price[1])?(integer)$single_price[1]:'';
						$single_details['size'] = isset($single_price[2])?$single_price[2]:'';
						$single_details['item_size_token']=isset($single_price[3])?$single_price[3]:'';
					}					
				}				
				$data[] = array(
				  'item_id'=> (integer) $val['item_id'],
				  'item_token'=>$val['item_token'],
				  'item_name'=> clearString($val['item_name']),
				  'item_description'=> clearString($val['item_description']),
				  'discount'=> $val['discount'],
				  'photo'=> $val['photo'],
				  'photo_url' => FunctionsV3::getFoodDefaultImage($val['photo'],false),
				  'spicydish'=> $val['spicydish'],
				  'dish'=> $val['dish'],
				  'item_name_trans'=>  !empty($val['item_name_trans'])?json_decode($val['item_name_trans'],true):'',
				  'item_description_trans'=>  !empty($val['item_description_trans'])?json_decode($val['item_description_trans'],true):'',
				  'not_available'=> $val['not_available'],
				  'single_item'=>(integer)$val['single_item'],
				  'single_details'=>$single_details,
				  'prices'=> self::getPrice( $val['item_id'], $val['cat_id'])
				);
			}			
			return $data;
		}
		return false;
	}
	
	public static function getPrice($item_id='', $cat_id='')
	{
		$data = array(); $and=''; $and_sizename='';
		
		if(self::$multi_currency){
			$and = Multicurrency_finance::itemPriceQuery( self::$currency_code );
		} 
		
		if ( self::$multi_field && self::$table_size_translation ){
			$and_sizename ="
			IFNULL((
			SELECT IF(size_name IS NULL or size_name = '', 
			a.size_name, size_name) 
			from {{size_translation}}
			 where
			 size_id = a.size_id
			 and language = ".q(self::$language)."
			), a.size_name ) as size_name,	
			";
		} else {
			$and_sizename ="
			a.size_name,
			";
		}
		
		if( self::$table_view_item_cat  ){	
				
			$stmt = "
			SELECT 
			a.price, 
			a.item_size_token,

			$and_sizename		
									
			a.size_id,		
			IF(a.discount>0, (a.price - a.discount) , 0) as discount_price
			
			$and
					
			FROM {{view_item_cat}} a
			WHERE 
			a.item_id = ". (integer) $item_id ."
			and a.cat_id = ". (integer) $cat_id ."
			";
		} else {
			return false;
		}								
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			foreach ($res as $val) {			
				$exchange_discount_price = 0;
				if ( $val['discount_price']>0){
					$exchange_discount_price = $val['discount_price'];
				}
											
				$data[] = array(
				   'item_size_token'=>$val['item_size_token'],
				   'price'=>$val['price'],
				   'size_name'=>$val['size_name'],
				   'size_id'=>$val['size_id'],				   
				   'discount_price'=>$val['discount_price'],
				   'exchange_rate'=>isset($val['exchange_rate'])?$val['exchange_rate']: 1,
				   'exchange_price'=>isset($val['exchange_price'])?$val['exchange_price'] : $val['price'] ,
				   'exchange_discount_price'=>isset($val['exchange_discount_price'])?$val['exchange_discount_price'] : $exchange_discount_price,
				   'exchange_price1'=>isset($val['exchange_price'])? Price_Formatter::formatNumber($val['exchange_price']) : Price_Formatter::formatNumber($val['price']) ,
				   'exchange_discount_price1'=>isset($val['exchange_discount_price'])? Price_Formatter::formatNumber($val['exchange_discount_price']) : Price_Formatter::formatNumber($exchange_discount_price),
				);
			}			
			return $data;
		}
		return false;
	}
	
	public static function getMenu($merchant_id='', $todays_day ='')
	{				
		$data = array();			
	
		if ( $res_category = self::getCategory($merchant_id, $todays_day ) ){
			foreach ($res_category as $key=>$val) {
				$data[] = array(
				  'category_id'=> (integer) $val['category_id'],
				  'category_name'=>  clearString($val['category_name']),
				  'category_description'=>  clearString($val['category_description']),
				  'category_name_trans'=>  !empty($val['category_name_trans'])?json_decode($val['category_name_trans'],true):'',
				  'category_description_trans'=>  !empty($val['category_description_trans'])?json_decode($val['category_description_trans'],true):'',
				  'dish'=>$val['dish'],
				  'photo'=>$val['photo'],
				  'item'=> self::getItem( $val['category_id'], $val['merchant_id'])
				);
			}
			return $data;
		}
		return false;
	}
	
	public static function getItemPrice($item_id=0, $size_id = 0)
	{
		$stmt="
		SELECT price,discount,not_available,size_name,size_id
		FROM {{view_item_cat}}
		WHERE item_id = ". q( (integer)$item_id)."
		AND size_id = ". q( (integer)$size_id) ."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
	public static function getAddonPrice($sub_item_id=0)
	{
		$stmt="
		SELECT price
		FROM {{subcategory_item}}
		WHERE sub_item_id = ". q( (integer)$sub_item_id)."		
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res['price'];
		}
		return false;
	}
	
	public static function getCategoryLazyLoad($merchant_id='', $todays_day='')
	{
		$and='';
		$todays_day = strtolower($todays_day);
		
		if(self::$enabled_category_sked==1){
    		$and .= " AND $todays_day='1' ";
    	}    
    	
    	$and_category='';    	
    	if(self::$food_option_not_available==1){
    		$and_category= " AND not_available=1 ";
    	}
    	
    	$paginate = Item_utility::paginate();
    	
    	$and_name = '';
    	if( self::$multi_field  && self::$table_category_translation  ){
    		$and_name = "    		
    		IFNULL((
			SELECT IF(category_name IS NULL or category_name = '', 
			a.category_name, category_name) as category_name
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_name ) as category_name,
			
			IFNULL((
			SELECT IF(category_description IS NULL or category_description = '', 
			a.category_description, category_description) as category_description
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_description ) as category_description ,
			
    		";
    	} else {
    		$and_name = " a.category_name, a.category_description ,";
    	}
    	
		$stmt="
		SELECT 		
		a.cat_id as category_id,
		a.photo,
		a.dish,
		a.category_name_trans,
		a.category_description_trans,
			
		$and_name	
		
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
		AND a.status in ('publish','published')
		$and
		ORDER BY sequence ASC
		";				
		//dump($stmt);die();
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){						
			foreach ($res as $val) {										
				$total_cat_paginate = $val['total_item_in_category']>0? ceil($val['total_item_in_category']/$paginate) : 0;
				$total_item = $val['total_item']>0? ceil($val['total_item']/$paginate) : 0;				
				$data[]=array(
				  'category_id'=>$val['category_id'],
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
		
	public static function getCategoryByID($merchant_id=0, $category_id=0)
	{
				
		if( self::$multi_field  && self::$table_category_translation  ){
			$stmt="
			SELECT
			a.cat_id as category_id,
			a.photo,
			a.dish,			
						
			IFNULL((
			SELECT IF(category_name IS NULL or category_name = '', 
			a.category_name, category_name) as category_name
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_name ) as category_name,
			
			IFNULL((
			SELECT IF(category_description IS NULL or category_description = '', 
			a.category_description, category_description) as category_description
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_description ) as category_description
				
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."		
			AND a.cat_id = ".q( (integer) $category_id)."
			";
		} else {
			$stmt = "
			SELECT
			a.cat_id as category_id,
			a.photo,
			a.dish,			
			a.category_name,
			a.category_description
															
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."				
			AND a.cat_id = ".q( (integer) $category_id)."
			";
		}		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			$res['category_name'] = clearString($res['category_name']);
			$res['photo_url'] = FunctionsV3::getFoodDefaultImage($res['photo'],false);
			$res['category_description'] = clearString($res['category_description']);
			return $res;
		}
		return false;
	}	
	
	public static function itemQueryStatment()
	{
		$stmt = ''; $select = '';
		
		if ( self::$multi_field && self::$table_item_translation  ){
			$select = "
			IFNULL((
			SELECT IF(item_name IS NULL or item_name = '', 
			a.item_name, item_name) 
			from {{item_translation}}
			 where
			 item_id = a.item_id
			 and language = ".q(self::$language)."
			), a.item_name ) as item_name,
						
			
			IFNULL((
			SELECT IF(item_description IS NULL or item_description = '', 
			a.item_description, item_description)
			from {{item_translation}}
			 where
			 item_id = a.item_id
			 and language = ".q(self::$language)."
			), a.item_description ) as item_description,
			
						
			IFNULL((
			SELECT IF(category_name IS NULL or category_name = '', 
			a.category_name, category_name) as category_name
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_name ) as category_name,
			
			IFNULL((
			SELECT IF(category_description IS NULL or category_description = '', 
			a.category_description, category_description) as category_description
			from {{category_translation}}
			 where
			 cat_id = a.cat_id
			 and language = ".q(self::$language)."
			), a.category_description ) as category_description,
				
			";
		} else {
			$select = "
			a.item_name, a.item_description ,
			a.category_name, a.category_description,
			";
		}
							
		if( self::$table_view_item_cat  ){	
			$stmt="
			SELECT 
			DISTINCT a.item_id,
			
			$select		
			
			a.discount,a.photo,a.spicydish,a.dish,
			a.not_available,
			a.cat_id,a.cat_id as category_id, a.item_token,
			a.cooking_ref,a.ingredients,

			(
			  select IF( count(*)>1, 1, 2 ) from {{view_item_cat}}
			  where item_id = a.item_id
			  and cat_id = a.cat_id			  
			) as single_item,
			
			(
			  select count(*) from {{item_relationship_subcategory}}
			  where item_id = a.item_id			  
			) as addon_count,
			
			(
			  select CONCAT_WS(';',price,size_id,size_name,item_size_token) from {{view_item_cat}}
			  where item_id = a.item_id
			  and cat_id = a.cat_id
			  limit 0,1
			) as single_details
			
					
			FROM {{view_item_cat}} a					
			";
			return $stmt;
		} else return false;
	}

	public static function getItemLazyLoadAll($merchant_id=0, $page = 0, $page_limit=0)
	{		
		$stmt = ''; $and='';
				
		if(self::$inventory_enabled ){
			if(InventoryWrapper::hideItemOutStocks($merchant_id) && self::$table_view_item_stocks_status){
				$and.="
				AND a.item_id IN (
					  select item_id from {{view_item_stocks_status}}
					  where available ='1'
					  and track_stock='1'
					  and stock_status not in ('Out of stocks')		
					  and item_id = a.item_id				  
					)		
				";
			} else {
				if(self::$food_option_not_available==1 && self::$table_item_relationship_size ){
					$and.="
					AND a.item_id IN (
					   select item_id from {{item_relationship_size}}
					   where available ='1'					
					   and item_id = a.item_id		   
					)		
					";
				}
			}
		} else {		
			if(self::$food_option_not_available==1){
				$and.= " AND not_available=1 ";
			}
		}
		
		if ( $stmt = self::itemQueryStatment()){
			$stmt.="
		    WHERE			
			a.merchant_id = ".q( (integer) $merchant_id )."
			AND a.status IN ('publish','published')			
			$and	
			ORDER BY category_sequence,cat_id,item_id ASC
			LIMIT $page,$page_limit
			";
		} else return false;		
		
		return self::processLazyQuery($stmt);
	}
	
	public static function getItemLazyLoad($category_id='', $merchant_id=0, $page = 0, $page_limit=0)
	{		
		
		$stmt = ''; $and='';
		
		if(self::$inventory_enabled ){
			if(InventoryWrapper::hideItemOutStocks($merchant_id) && self::$table_view_item_stocks_status){
				$and.="
				AND a.item_id IN (
					  select item_id from {{view_item_stocks_status}}
					  where available ='1'
					  and track_stock='1'
					  and stock_status not in ('Out of stocks')		
					  and item_id = a.item_id				  
					)		
				";
			} else {
				if(self::$food_option_not_available==1 && self::$table_item_relationship_size ){
					$and.="
					AND a.item_id IN (
					   select item_id from {{item_relationship_size}}
					   where available ='1'					
					   and item_id = a.item_id		   
					)		
					";
				}
			}
		} else {		
			if(self::$food_option_not_available==1){
				$and.= " AND not_available=1 ";
			}
		}
				
		if ( $stmt = self::itemQueryStatment()){
			$stmt.="
			WHERE
			a.cat_id = ".q( (integer) $category_id )."
			AND a.merchant_id = ".q( (integer) $merchant_id )."
			AND a.status IN ('publish','published')			
			$and	
			ORDER BY item_sequence,item_id ASC
			LIMIT $page,$page_limit		    
			";
		} else return false;					
		
		return self::processLazyQuery($stmt);
	}
	
		
	public static function searchByItem($search_string='', $merchant_id=0, $page = 0, $page_limit=0)
	{		
		
		$stmt = ''; $and='';
		
		if(self::$food_option_not_available==1){
			$and = " AND not_available=1 ";
		}
				
		if ( $stmt = self::itemQueryStatment()){
			
			$where = "WHERE a.item_name LIKE ".q("%$search_string%")."";
			if ( self::$multi_field && self::$table_item_translation && self::$language!="en"  ){
				$where ="
				WHERE 
	        	a.item_id IN (
	        	  select item_id from {{item_translation}}
	        	  where item_id = a.item_id
	        	  and language = ".q(self::$language)."
	        	  and item_name LIKE ".FunctionsV3::q("%$search_string%")."	  	        	        	
	        	)
				";
			}
			
			$stmt.="
			$where
			AND a.merchant_id = ".q( (integer) $merchant_id )."
			AND a.status IN ('publish','published')		
			$and		
			LIMIT $page,$page_limit			  
			";
		} else return false;
						
		return self::processLazyQuery($stmt);
	}
	
	public static function processLazyQuery($stmt='')
	{				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){							
			foreach ($res as $val) {
				$single_details = array();
				
				if(!empty($val['cooking_ref'])){
					$val['single_item'] = 1;
				}
				if(!empty($val['ingredients'])){
					$val['single_item'] = 1;
				}
				if($val['addon_count']>0){
					$val['single_item'] = 1;
				}
				
				if($val['single_item']==2){
					$single_price = !empty($val['single_details'])?explode(";",$val['single_details']):false;
					if($single_price!=false){
						$single_details['price'] = isset($single_price[0])?(float)$single_price[0]:'';
						$single_details['size_id'] = isset($single_price[1])?(integer)$single_price[1]:'';
						$single_details['size'] = isset($single_price[2])?$single_price[2]:'';
						$single_details['item_size_token']=isset($single_price[3])?$single_price[3]:'';
					}					
				}				
				$data[] = array(
				  'item_id'=> (integer) $val['item_id'],
				  'item_token'=>$val['item_token'],
				  'item_name'=> clearString($val['item_name']),
				  'item_description'=> clearString($val['item_description']),
				  'category_id'=>$val['category_id'],
				  'category_name'=>  clearString($val['category_name']),
				  'category_description'=>  clearString($val['category_description']),
				  'discount'=> $val['discount'],
				  'photo'=> $val['photo'],
				  'photo_url' => FunctionsV3::getFoodDefaultImage($val['photo'],false),
				  'spicydish'=> $val['spicydish'],
				  'dish'=> $val['dish'],					  
				  'not_available'=> $val['not_available'],
				  'single_item'=>(integer)$val['single_item'],
				  'single_details'=>$single_details,
				  'prices'=> self::getPrice( $val['item_id'], $val['cat_id'])
				);
			}			
			return $data;
		}
		return false;
	}
	
	
	public static function searchByFoodName($item_name='')
	{
		$select=''; $where = '';
		if ( self::$multi_field && self::$table_item_translation  ){
			$select = "
			IFNULL((
			SELECT IF(item_name IS NULL or item_name = '', 
			a.item_name, item_name) 
			from {{item_translation}}
			 where
			 item_id = a.item_id
			 and language = ".q(self::$language)."
			), a.item_name ) as name				
			";
		} else {			
			$select = "item_name as name";
		};
		
		$stmt = "
		SELECT $select 
		FROM {{item}} a
		WHERE 
			a.item_id IN (
	        	  select item_id from {{item_translation}}
	        	  where item_id = a.item_id
	        	  and language = ".q(self::$language)."
	        	  and item_name LIKE ".FunctionsV3::q("%$item_name%")."	  	        	        	
	        	)	    
	         AND status ='publish'
   	    ORDER BY item_name ASC   	    	
		";		
		if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){			
			$data = array();
			foreach ($resp as $key=>$val) {				
				$data[]['name'] = clearString($val['name']);
			}
			return $data;
		} 
		return false;
	}
	
	public static function searchByCuisine($name='')
	{
		$select=''; $where = '';
		if ( self::$multi_field && self::$table_item_translation  ){
			$select = "
			IFNULL((
			SELECT IF(cuisine_name IS NULL or cuisine_name = '', 
			a.cuisine_name, cuisine_name) 
			from {{cuisine_translation}}
			 where
			 cuisine_id = a.cuisine_id
			 and language = ".q(self::$language)."
			), a.cuisine_name ) as name				
			";
		} else {			
			$select = "cuisine_name as name";
		};
		
		$stmt = "
		SELECT $select 
		FROM {{cuisine}} a
		WHERE 
			a.cuisine_id IN (
	        	  select cuisine_id from {{cuisine_translation}}
	        	  where cuisine_id = a.cuisine_id
	        	  and language = ".q(self::$language)."
	        	  and cuisine_name LIKE ".FunctionsV3::q("%$name%")."	  	        	        	
	        	)	    
	         AND status ='publish'
   	    ORDER BY cuisine_name ASC   	    	
		";				
		if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){			
			$data = array();
			foreach ($resp as $key=>$val) {				
				$data[]['name'] = clearString($val['name']);
			}
			return $data;
		} 
		return false;
	}
}
/*end class*/
<?php
class Item_menu_mobile
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
	public static $table_cooking_ref_translation = false;
	public static $table_subcategory_translation = false;
	public static $table_subcategory_item_translation = false;
	
	public static $enabled_category_sked = false;
    public static $food_option_not_available = false;
    public static $paginated = false;
    public static $inventory_enabled = false;
    
    public static $disabled_default_image = false;
    public static $merchant_menu_type = '';
    public static $default_image = '';
    public static $hide_empty_category = false;
    
    public static $pre_filter = array();
    
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
		self::$table_cooking_ref_translation = Yii::app()->db->schema->getTable("{{cooking_ref_translation}}");
		self::$table_subcategory_translation = Yii::app()->db->schema->getTable("{{subcategory_translation}}");		
		self::$table_subcategory_item_translation = Yii::app()->db->schema->getTable("{{subcategory_item_translation}}");		
		
		self::$enabled_category_sked = getOption($merchant_id,'enabled_category_sked');   
		self::$food_option_not_available = getOption($merchant_id,'food_option_not_available');   		
		self::$inventory_enabled = Item_utility::InventoryEnabled();
		self::$paginated = false;
		
		self::$disabled_default_image = getOptionA('mobile2_disabled_default_image');
		self::$merchant_menu_type = getOptionA('mobileapp2_merchant_menu_type');
		if(self::$merchant_menu_type==3){
			self::$disabled_default_image = false;
		}
		
		self::$default_image = Mobile_utility::getDefaultImagePlaceholder();
		self::$hide_empty_category = getOptionA('mobile2_hide_empty_category');
	}
	
	public static function getFoodPromo($and='',$sort_fields='',$sort_by='',$page=0,$page_limit=10)
	{
		$select = ''; $data = array(); $and_category = '';
		$default_image = Mobile_utility::getDefaultImagePlaceholder();
		
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
			
		} else $select = "a.item_id,a.item_name,a.item_name_trans,";
		
		
		if(self::$enabled_category_sked==1){
			$and_category = "
			 AND cat_id IN (  
	           select cat_id 
	           from {{category}}
	           where
	           cat_id = a.cat_id
	           and merchant_id = a.merchant_id
	           and ". strtolower(date("l"))." = 1 
	        )        
			";
		}
		
		if( self::$table_view_item_cat ){	
			$stmt="
	        SELECT 
	        SQL_CALC_FOUND_ROWS 
	        DISTINCT a.item_id, a.cat_id, a.cat_id as category_id, a.merchant_id,
	        $select        
	        a.photo	        
	                     
	        FROM {{view_item_cat}} a
	        WHERE discount>0
	        AND status IN ('publish')
	        AND not_available = 1
	        $and_category               
	        $and
	        ORDER BY $sort_fields $sort_by
	        LIMIT $page,$page_limit
	        ";    		
		} else return false;
				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){	

			$total_records=0;
			$stmtc="SELECT FOUND_ROWS() as total_records";				
			if($resp = Yii::app()->db->createCommand($stmtc)->queryRow()){		 		
				$total_records=$resp['total_records'];
			}											
					
			foreach ($res as $val) {
				$val['item_name'] = stripslashes($val['item_name']);
				$val['photo']=mobileWrapper::getImage($val['photo'],$default_image,false);
				$val['prices2'] = Item_menu_mobile::getPrice($val['item_id'],$val['cat_id']);
				$val['total_rows'] = (integer) $total_records;
				$data[] = $val;
			}
						
			return $data;
		}
		return false;
	}
	
	public static function getItem($category_id='' , $merchant_id=0, $page=0, $page_limit=10)
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

					
		if(is_array(Item_menu_mobile::$pre_filter) && count(Item_menu_mobile::$pre_filter)>=1){
			foreach (Item_menu_mobile::$pre_filter as $key=> $filter) {
				switch ($key) {
					case "dish":						
					     if($filter>0){
						    $and.=" AND dish like ".FunctionsV3::q('%"'. (integer) $filter.'"%')." ";
					     }
						break;
				
					default:
						break;
				}
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
			SQL_CALC_FOUND_ROWS
			DISTINCT a.item_id,			
			a.cat_id as category_id,
			a.merchant_id,			
			
			$select		
			
			a.discount,a.photo,a.spicydish,a.dish,a.not_available,
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
			LIMIT $page,$page_limit
			";
		} else {
			return false;
		}				
										
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){		
			
			$total_records=0;
			$stmtc="SELECT FOUND_ROWS() as total_records";				
			if($resp = Yii::app()->db->createCommand($stmtc)->queryRow()){		 		
				$total_records=$resp['total_records'];
			}								
			$paginate_total = ceil( $total_records / (integer)$page_limit );			
				
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
				  'paginate_total'=>(integer)$paginate_total,
				  'category_id'=>(integer)$val['category_id'],
				  'merchant_id'=>(integer)$val['merchant_id'],
				  'item_id'=> (integer) $val['item_id'],
				  'item_token'=>$val['item_token'],
				  'item_name'=> stripslashes($val['item_name']),
				  'item_description'=> stripslashes($val['item_description']),
				  'discount'=> $val['discount'],
				  'photo'=> $val['photo'],				  
				  'photo_url' => mobileWrapper::getImage($val['photo'], self::$default_image , self::$disabled_default_image ),
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
		} else return false;
							
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
				   'original_price'=>mt("[size_name] [price]",array(
				     '[size_name]'=>$val['size_name'],
				     '[price]'=> isset($val['exchange_price'])? Price_Formatter::formatNumber($val['exchange_price']) : Price_Formatter::formatNumber($val['price'])
				   )),
				   'discounted_price_pretty'=>mt("[size_name] [price]",array(
				     '[size_name]'=>$val['size_name'],
				     '[price]'=> isset($val['exchange_discount_price'])? Price_Formatter::formatNumber($val['exchange_discount_price']) : Price_Formatter::formatNumber($val['discount_price'])
				   )),
				);
			}			
			return $data;
		}
		return false;
	}	
	
	public static function getCategory($merchant_id='', $todays_day='' , $pagenumber=0, $pagelimit=0 )
	{
				
		$and=''; $limit = '';
		$todays_day = strtolower($todays_day);
		
		
		if(self::$enabled_category_sked==1){
    		$and .= " AND $todays_day='1' ";
    	}    
    	    	
    	if( self::$table_view_item_cat  && Item_menu_mobile::$hide_empty_category ){	
	    	$and.="
	    	  AND a.cat_id IN (
	    	    select cat_id 
	    	    from {{view_item_cat}}
	    	    where
	    	    cat_id = a.cat_id
	    	    and not_available = 1
	    	  )
	    	";
    	}
    	
    	if($pagelimit>0){
    		$limit = "LIMIT $pagenumber,$pagelimit";
    	}
    	    	    	
		if( self::$multi_field  && self::$table_category_translation  ){
			$stmt="
			SELECT
			SQL_CALC_FOUND_ROWS
			a.cat_id,
			a.cat_id as category_id,
			a.merchant_id,
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
			AND a.status IN ('publish','published')		
			$and			
			ORDER BY a.sequence ASC
			$limit
			";
		} else {
			$stmt = "			
			SELECT
			SQL_CALC_FOUND_ROWS
			a.cat_id,
			a.cat_id as category_id,
			a.merchant_id,
			a.photo,
			a.dish,			
			a.category_name,
			a.category_description						
															
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."		
			AND a.status IN ('publish','published')			
			$and
			ORDER BY a.sequence ASC
			$limit
			";
		}										
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			return $res;
		}
		return false;
	}
	
	public static function getCategoryByID($merchant_id=0, $category_id=0)
	{
				
		if( self::$multi_field  && self::$table_category_translation  ){
			$stmt="
			SELECT
			a.cat_id,
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
			a.cat_id,
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
			$res['category_name'] = stripslashes($res['category_name']);
			$res['photo_url'] = FunctionsV3::getFoodDefaultImage($res['photo'],false);
			$res['category_description'] = stripslashes($res['category_description']);
			return $res;
		}
		return false;
	}		
	
	public static function getCategoryItemCount($merchant_id=0, $todays_day = '')
	{
		$and=''; $limit = ''; $data = array();
		$todays_day = strtolower($todays_day);
		
		
		if(self::$enabled_category_sked==1 && !empty($todays_day)){
    		$and .= " AND $todays_day='1' ";
    	}    
    	
    	if( self::$table_view_item_cat  && Item_menu_mobile::$hide_empty_category ){	
	    	$and.="
	    	  AND a.cat_id IN (
	    	    select cat_id 
	    	    from {{view_item_cat}}
	    	    where
	    	    cat_id = a.cat_id
	    	    and not_available = 1
	    	  )
	    	";
    	}
    	
    	$not_available='';
    	if(self::$food_option_not_available==1){
			$not_available = " AND not_available=1 ";
		}
    	
		if( self::$multi_field  && self::$table_category_translation && self::$table_view_item_cat ){
			$stmt="
			SELECT			
			a.cat_id,
			a.cat_id as category_id,
			a.merchant_id,
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
			), a.category_description ) as category_description,

			(
			  select count(*)
			  from {{view_item_cat}}
			  where cat_id = a.cat_id
			  and merchant_id = ".q( (integer) $merchant_id)."
			  $not_available
			) as item_count
				
			FROM {{category}} a		
			WHERE		
			a.merchant_id = ".q( (integer) $merchant_id)."				
			AND a.status IN ('publish','published')		
			$and			
			ORDER BY a.sequence ASC			
			";
		} else {
			if( self::$table_view_item_cat ) {
				$stmt = "			
				SELECT
				a.cat_id,
				a.cat_id as category_id,
				a.merchant_id,
				a.photo,
				a.dish,			
				a.category_name,
				a.category_description,
				
				(
				  select count(*)
				  from {{view_item_cat}}
				  where cat_id = a.cat_id
				  and merchant_id = ".q( (integer) $merchant_id)."
				  $not_available
				) as item_count	
																
				FROM {{category}} a		
				WHERE		
				a.merchant_id = ".q( (integer) $merchant_id)."		
				AND a.status IN ('publish','published')			
				$and
				ORDER BY a.sequence ASC			
				";
			} else return false;
		}		
				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			foreach ($res as $val) {
				$val['item_count']= mt("[count] item",array(
				  '[count]'=>(integer)$val['item_count']
				));
				$data[]=$val;
			}
			return $data;
		}
		return false;
	}
	
	public static function reFormat($data=array() , $cart_data=array() , $highlight_word=false , $search_word='' )
	{		
		$new_data = array();
		if(is_array($data) && count($data)>=1){
			foreach ($data as $val) {				
				$prices2 = array(); $prices = array();
				if(is_array($val['prices']) && count($val['prices'])>=1){
					foreach ($val['prices'] as $price) {						
						$prices2[] = array(
						  'original_price'=>$price['original_price'],
						  'discount'=>(float)$val['discount'],
						  'discounted_price_pretty'=>$price['discounted_price_pretty'],
						);
						$prices[] = $price['original_price'];
					}
				}
				
				$dish_image = array();
				if(!empty($val['dish'])){
				   $dish_image  = self::getDishImage($val['dish']);
				}
				
				$added_qty = 0;
				if($val['single_item']!=1 && is_array($cart_data) && count($cart_data)>=1 ){
					foreach ($cart_data as $cart_data_val) {							
						if($val['item_id']==$cart_data_val['item_id'] &&  $val['category_id']==$cart_data_val['category_id'] ){
							$added_qty = $cart_data_val['qty'];
						}
					}
				}
				
				$item_name = $val['item_name'];
				if($highlight_word){					
					$item_name = mobileWrapper::highlight_word($item_name,$search_word);					
				}
				
				$new_data[] = array(
				  'paginate_total'=>isset($val['paginate_total'])?(integer)$val['paginate_total']:0,
				  'item_id'=>(integer)$val['item_id'],
				  'merchant_id'=>isset($val['merchant_id'])?(integer)$val['merchant_id']:0,
				  'item_name'=>$item_name,
				  'item_description'=>$val['item_description'],		
				  'photo'=>$val['photo_url'],
				  'discount'=>(float)$val['discount'],
				  'prices'=>$prices,
				  'prices2'=>$prices2,
				  'cat_id'=>isset($val['category_id'])?$val['category_id']:'',				  
				  'dish_image'=>(array)$dish_image,
				  'customizable'=>$val['single_item']==1?true:false,
				  'added_qty'=>$added_qty
				);
			}			
		}
		return $new_data;
	}
	
	public static function getDishImage($dish_id='')
	{
		if($dish = json_decode($dish_id,true)){
			$stmt="SELECT photo FROM {{dishes}}
			WHERE dish_id IN (".implode(",",$dish).")
			";
			$data = array();
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				foreach ($res as $val) {					
					$data[] = FunctionsV3::getFoodDefaultImage($val['photo']);
				}
			}
			return $data;
		}
		return array();
	}
	
	/*
	@parameters 
	$item_id = item id
	$category_id = category id
	*/
	public static function getItemDetails($item_id = 0 , $category_id=0)
	{
		$select=''; $where = ''; $data = array(); $prices = array();
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
			), a.item_description ) as item_description
					
			";
		} else {						
			$select = "item_name,item_description";
		};
		
		if(self::$multi_currency){
			$select.= Multicurrency_finance::itemPriceQuery( self::$currency_code );
		} else {
			$select.= ", 
			1 as exchange_rate,
			a.price as exchange_price,
			IF(a.discount>0, (a.price - a.discount) , 0) as exchange_discount_price			
			";
		}
		
		if ( self::$multi_field && self::$table_size_translation ){
			$select.="
			, a.size_id,
			IFNULL((
			SELECT IF(size_name IS NULL or size_name = '', 
			a.size_name, size_name) 
			from {{size_translation}}
			 where
			 size_id = a.size_id
			 and language = ".q(self::$language)."
			), a.size_name ) as size_name			
			";
		} else {
			$select.="
			, a.size_id,a.size_name
			";
		}
		
		if( Yii::app()->db->schema->getTable("{{view_item_cat2}}") ){
			$stmt = "
			SELECT 
			merchant_id,item_id,price,
			$select
			, discount,photo,cooking_ref,ingredients,spicydish,dish,addon_item,
			multi_option,multi_option_value,two_flavors,two_flavors_position,require_addon,gallery_photo,
			not_available,
			
			IFNULL((
			select restaurant_slug
			from {{merchant}}
			where
			merchant_id = a.merchant_id
			limit 0,1
			),'') as restaurant_slug
			
			FROM {{view_item_cat2}} a
			WHERE 
			item_id = ".q( (integer) $item_id)." AND cat_id =".q( (integer) $category_id)."
			";
		} else return false;		
		
								
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			foreach ($res as $val) {
				
				$val['size_name'] = stripslashes($val['size_name']);
				
				$prices[] = array(
				  'price'=>$val['price'],
				  'size'=>$val['size_name'],
				  'size_id'=>$val['size_id'],
				  'discount_price'=>$val['discount'],
				  'formatted_price'=>mt("[size_name] [price]",array(
				     '[size_name]'=>$val['size_name'],
				     '[price]'=> Price_Formatter::formatNumber($val['exchange_price'])
				   )),
				   'formatted_discount_price'=>mt("[size_name] [price]",array(
				     '[size_name]'=>$val['size_name'],
				     '[price]'=> Price_Formatter::formatNumber($val['exchange_discount_price'])
				   )),				  
				);
			}

			$cooking_ref = self::getCookingRef($val['cooking_ref']);
			
			$addon_item = self::getAddon(
				isset($res[0]['item_id'])?$res[0]['item_id'] :'',
				isset($res[0]['addon_item'])?$res[0]['addon_item']:'',
				isset($res[0]['multi_option'])?$res[0]['multi_option']:'',
				isset($res[0]['multi_option_value'])?$res[0]['multi_option_value']:'',
				isset($res[0]['require_addon'])?$res[0]['require_addon']:'',
				isset($res[0]['two_flavors_position'])?$res[0]['two_flavors_position']:''
			);			
						
			$ingredients = self::getIngredients( $res[0]['ingredients'] );
			$dish_list = self::getDishImage( $res[0]['dish'] );
			$gallery = self::getGallery( $res[0]['gallery_photo'] );

					
			$multiple_price = false;
			if ( count($res)>1){
				$multiple_price = true;
			} else {
				if ( $res[0]['size_id']>0){
					$multiple_price = true;
				}
			}
			
			$data = array(
			  'merchant_id'=>$res[0]['merchant_id'],
			  'item_id'=>$res[0]['item_id'],
			  'item_name'=>stripslashes($res[0]['item_name']),
			  'item_description'=>stripslashes($res[0]['item_description']),
			  'discount'=>$res[0]['discount'],
			  'photo'=>mobileWrapper::getImage($res[0]['photo'],'default_cuisine.png'),
			  'prices'=>$prices,
			  'cooking_ref'=>(array)$cooking_ref,
			  'addon_item'=>(array)$addon_item,
			  'ingredients'=>$ingredients,
			  'spicydish'=>$res[0]['spicydish'],
			  'dish'=>$res[0]['dish'],
			  'two_flavors'=>$res[0]['two_flavors'],
			  'gallery_photo'=>$res[0]['gallery_photo'],
			  'not_available'=>$res[0]['not_available'],
			  'dish_list'=>$dish_list,
			  'gallery'=>$gallery,
			  'multiple_price'=>$multiple_price,
			  'share_options'=>array(
			    'message'=>$res[0]['item_name'],
			    'subject'=>$res[0]['item_name'],
			    'url'=>websiteUrl()."/menu/".$res[0]['restaurant_slug']
			  )
			);
						
			return $data;
		}
		return false;
	}
	
	/*
	@parameters $cooking_ref = ["1","4"]
	*/
	public static function getCookingRef($cooking_ref='')
	{
		$data = array(); $select='';
		if ( $cooking_id = json_decode($cooking_ref,true)){			
			
			if(self::$multi_field && self::$table_cooking_ref_translation){
			$select = ",
			IFNULL((
			SELECT IF(cooking_name IS NULL or cooking_name = '', 
			a.cooking_name, cooking_name) 
			from {{cooking_ref_translation}}
			 where
			 cook_id = a.cook_id
			 and language = ".q(self::$language)."
			), a.cooking_name ) as cooking_name
			";
			} else {
				$select = ", cooking_name";
			}
			
			$stmt="SELECT 
			cook_id $select 
			FROM {{cooking_ref}} a
			WHERE cook_id IN (".implode(",",$cooking_id).")
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){								
				foreach ($res as $val) {
					$data[ $val['cook_id'] ] = stripslashes($val['cooking_name']);
				}
			}
		}
		return $data;
	}
	
	/*
	@parameters
	addon category id => addon item id
	$addon_item = {"1":["2","3","14"],"5":["1","8","12","14"],"10":["1","13","14"],"11":["14"]}	
	$multi_option =  {"1":["multiple"],"5":["custom"],"10":["one"],"11":["one"]}
	$multi_option_value = {"1":[""],"5":["2"],"10":[""],"11":[""]}
	$require_addon = {"1":["2"]}
	$two_flavors_position = {"1":["left"],"5":["right"],"10":[""],"11":[""]}
	*/
	public static function getAddon($item_id=0,  $addon_item='',$multi_option='', $multi_option_value='' , 
	$require_addon='' , $two_flavors_position='')
	{		
		$data = array(); $select='';
		if ( $addon = json_decode($addon_item,true)){
			$multi_option = json_decode($multi_option,true);
			$multi_option_value = json_decode($multi_option_value,true);
			$require_addon = json_decode($require_addon,true);
			$two_flavors_position = json_decode($two_flavors_position,true);			
						
			$subcat_id = array();
			foreach ($addon as $subcatid=>$val) {
				$subcat_id[]=$subcatid;
			}
						
			if(self::$multi_field && self::$table_subcategory_translation ){
				$select=",
				IFNULL((
				SELECT IF(subcategory_name IS NULL or subcategory_name = '', 
				(
				 select subcategory_name from {{subcategory}}
				 where subcat_id = a.subcat_id
				 limit 0,1
				), subcategory_name) 
							
				from {{subcategory_translation}}
				where
				subcat_id = a.subcat_id
				and language = ".q(self::$language)."
				), (
				  select subcategory_name from {{subcategory}}
				  where subcat_id = a.subcat_id
				  limit 0,1
				) ) as subcategory_name
				";
			} else $select = ", (
			   select subcategory_name from {{subcategory}}
			   where subcat_id = a.subcat_id
 			   limit 0,1
			) as subcategory_name";
			
			$stmt="
			SELECT
			a.subcat_id
			$select					
			
			FROM {{item_relationship_subcategory}} a
			WHERE
			item_id =".q( (integer)$item_id )."
			AND a.subcat_id IN (".implode(",",$subcat_id).")
			ORDER BY a.id ASC
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				foreach ($res as $val) {		
					
					$subitemid = isset($addon[$val['subcat_id']])?$addon[$val['subcat_id']]:'';
					$sub_item = self::getSubItem($subitemid);
					$val['subcategory_name'] = stripslashes($val['subcategory_name']);
					$val['subcat_name'] = stripslashes($val['subcategory_name']);
								
					$val['multi_option'] = isset($multi_option[$val['subcat_id']])?$multi_option[$val['subcat_id']][0]:'';
					$val['multi_option_val'] = isset($multi_option_value[$val['subcat_id']])?$multi_option_value[$val['subcat_id']][0]:'';
					$val['two_flavor_position'] = isset($two_flavors_position[$val['subcat_id']])?$two_flavors_position[$val['subcat_id']][0]:'';
					$val['require_addons'] = isset($require_addon[$val['subcat_id']])?$require_addon[$val['subcat_id']][0]:'';					
					$val['sub_item'] = $sub_item;
					$data[]=$val;
				}
			}						
		}
		return $data;
	}
	
	/*
	@parameters 
	$subitemid = 
	Array
	(
	    [0] => 2
	    [1] => 3
	    [2] => 14
	)
	*/
	public static function getSubItem($subitemid='')
	{		
		$data = array(); $select='';
		
		
		if(self::$multi_field && self::$table_subcategory_item_translation){
			$select = ",
			IFNULL((
			SELECT IF(sub_item_name IS NULL or sub_item_name = '', 
			a.sub_item_name, sub_item_name) 
			from {{subcategory_item_translation}}
			 where
			 sub_item_id = a.sub_item_id
			 and language = ".q(self::$language)."
			), a.sub_item_name ) as sub_item_name,
			
			IFNULL((
			SELECT IF(item_description IS NULL or item_description = '', 
			a.item_description, item_description) 
			from {{subcategory_item_translation}}
			 where
			 sub_item_id = a.sub_item_id
			 and language = ".q(self::$language)."
			), a.item_description ) as item_description
			
			";
		} else $select=", sub_item_name,item_description";
		
		if(self::$multi_currency){			
			$select.=",
			IFNULL((
			select ( ( IF(exchange_rate<=0, 1, exchange_rate ) + exchange_rate_fee)) from {{currency}}
			where currency_code = ".q(self::$currency_code)."
			limit 0,1
			),1) as exchange_rate,
			
			IFNULL((
			select ( ( IF(exchange_rate<=0, 1, exchange_rate ) + exchange_rate_fee) * a.price) from {{currency}}
			where currency_code = ".q(self::$currency_code)."
			limit 0,1
			),a.price ) as exchange_price			
			";
		} else {
			$select.=",
			1 as exchange_rate,
			a.price as exchange_price
			";
		}
		
		$stmt = "
		SELECT sub_item_id,price,photo
		$select			
		FROM {{subcategory_item}} a
		WHERE sub_item_id IN (".implode(",",$subitemid).")
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){					
			foreach ($res as $val) {
				$val['sub_item_name'] = stripslashes($val['sub_item_name']);
				$val['item_description'] = stripslashes($val['item_description']);
				$val['pretty_price'] = Price_Formatter::formatNumber($val['exchange_price']);
				$data[]=$val;
			}
		}
		return $data;
	}
	
	/*
	@parameters  
	$ingredients = ["9","10"]
	*/
	public static function getIngredients($ingredients='')
	{
		$data = array(); $select='';
		if ( $ingredients = json_decode($ingredients,true)){
			
			if(self::$multi_field && self::$table_cooking_ref_translation){
			$select = ",
			IFNULL((
			SELECT IF(ingredients_name IS NULL or ingredients_name = '', 
			a.ingredients_name, ingredients_name) 
			from {{ingredients_translation}}
			 where
			 ingredients_id = a.ingredients_id
			 and language = ".q(self::$language)."
			), a.ingredients_name ) as ingredients_name
			";
			} else {
				$select = ", ingredients_name";
			}
			
			$stmt="SELECT 
			ingredients_id $select 
			FROM {{ingredients}} a
			WHERE ingredients_id IN (".implode(",",$ingredients).")
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){				
				foreach ($res as $val) {
					$data[ $val['ingredients_id'] ] = $val['ingredients_name'];
				}
			}				
			
		}
		return $data;
	}
	
	/*
	@parameters
	$photo =  ["1590632075-Burgers.jpg","1590632077-burger 1.jpg","1590632080-burger.jpg"]
	*/
	public static function getGallery($photo='')
	{
		$data = array();
		if ( $photo = json_decode($photo,true)){
			foreach ($photo as $filename) {
				$data[] = FunctionsV3::getFoodDefaultImage($filename);
			}
			return $data;
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
				  'item_name'=> stripslashes($val['item_name']),
				  'item_description'=> stripslashes($val['item_description']),
				  'category_id'=>$val['category_id'],
				  'category_name'=>  stripslashes($val['category_name']),
				  'category_description'=>  stripslashes($val['category_description']),
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
	
	public static function deletePreviousCart($device_uiid='',$date='')
	{
		$stmt = "
		DELETE FROM {{mobile2_cart}}
		WHERE CAST(date_modified as DATE) <=".q($date)."
		";
		Yii::app()->db->createCommand($stmt)->query();
	}

}
/*end class*/

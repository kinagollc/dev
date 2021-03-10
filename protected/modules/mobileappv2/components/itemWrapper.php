<?php
class itemWrapper
{
	static $sizes = array();
	static $enabled_trans=false;
	
	public static function setMultiTranslation()
	{
		$enabled_trans=getOptionA('enabled_multiple_translation');
		if($enabled_trans==2){
			self::$enabled_trans = true;
		}
	}
	
	public static function getMenu($merchant_id='', $pagenumber=0, $pagelimit=10 , $cart_data = array() )
	{	
									
		$paginate_total=0;
		$menu_type = mobileWrapper::getMenuType();
		
		$default_image='';
		
		$disabled_default_image = getOptionA('mobile2_disabled_default_image');
		$merchant_menu_type = getOptionA('mobileapp2_merchant_menu_type');		
		if($merchant_menu_type==3){
			$default_image='resto_banner.jpg';
			$disabled_default_image=false;
		}
		
		if($merchant_id>0){
			self::$sizes = self::getSize($merchant_id);
			
			$todays_day = date("l");
            $todays_day = !empty($todays_day)?strtolower($todays_day):'';
            
            $and='';
            
            $enabled_category_sked = getOption($merchant_id,'enabled_category_sked'); 
            if($enabled_category_sked==1){
    		    $and .= " AND $todays_day='1' ";
    	    }    	    
    	    			
			$stmt="
			SELECT SQL_CALC_FOUND_ROWS *
			FROM
			{{category}}
			WHERE
			merchant_id = ".FunctionsV3::q($merchant_id)."
			AND status in ('publish','published')
			$and
			ORDER BY sequence,date_created ASC
			LIMIT $pagenumber,$pagelimit
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				$res = Yii::app()->request->stripSlashes($res);
				
				$total_records=0;
				$stmtc="SELECT FOUND_ROWS() as total_records";				
				if($resp = Yii::app()->db->createCommand($stmtc)->queryRow()){		 		
					$total_records=$resp['total_records'];
				}		
				
				$paginate_total = ceil( $total_records / $pagelimit );
				
				foreach ($res as $val) {					
					$new_data['cat_id']=$val['cat_id'];
					$new_data['category_name']=$val['category_name'];
					$new_data['category_description']=$val['category_description'];
					$new_data['category_pic']=mobileWrapper::getImage($val['photo'],$default_image,$disabled_default_image);
					
					if(self::$enabled_trans==TRUE){
					  $category_name['category_name_trans']=!empty($val['category_name_trans'])?json_decode($val['category_name_trans'],true):'';
					  $new_data['category_name'] = qTranslate($new_data['category_name'],'category_name',$category_name);
					  
					  $category_description['category_description_trans']=!empty($val['category_description_trans'])?json_decode($val['category_description_trans'],true):'';
					  $new_data['category_description'] = qTranslate($new_data['category_description'],'category_description',$category_description);					  
					}
					
					
					$new_data['category_description'] = $new_data['category_description'];
					
					/*public static function getItemByCategory($merchant_id='',$category_id='', $paginate=false, 
	                $pagenumber=0, $pagelimit=10, $filter_dishes = array(), $cart_data=array() )*/
									
					if($menu_type==1){					   
					   $item_data = self::getItemByCategory($merchant_id,$val['cat_id'],false,0,10,array(),$cart_data);
					   $new_data['item'] = is_array($item_data['data'])?$item_data['data']:array();
					}
					$data[]=$new_data;
				}
				return array(
				  'paginate_total'=>$paginate_total,
				  'list'=>$data
				);
			}
			unset($db);
		}
		return false;
	}
	
	public static function getMerchantCategory($merchant_id='')
	{		
		$data = array();
		if($merchant_id>0){
			
			$todays_day = date("l");
            $todays_day = !empty($todays_day)?strtolower($todays_day):'';
            
            $and='';
            
            $enabled_category_sked = getOption($merchant_id,'enabled_category_sked'); 
            if($enabled_category_sked==1){
    		    $and .= " AND $todays_day='1' ";
    	    }    	 
    	    
    	    $cart_theme = getOptionA('mobileapp2_cart_theme');
    	    
    	    			
			$stmt="
			SELECT 
			cat_id,
			category_name,
			category_description,
			photo,
			status,
			category_name_trans,
			category_description_trans
			FROM
			{{category}}
			WHERE
			merchant_id = ".FunctionsV3::q($merchant_id)."
			AND status in ('publish','published')
			$and
			ORDER BY sequence,date_created ASC		
			LIMIT 0,1000	
			";											
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				if($cart_theme==2){
					self::$enabled_trans = true;
				}
					
				if(self::$enabled_trans!=TRUE){
					return $res;
				}				
				foreach ($res as $val) {					
					$val['category_name'] = qTranslate($val['category_name'],'category_name',array(
					  'category_name_trans'=>json_decode($val['category_name_trans'],true)
					));				
					$val['category_description'] = qTranslate($val['category_description'],'category_description',array(
					  'category_description_trans'=>json_decode($val['category_description_trans'],true)
					));					
					
					if($cart_theme==2){						
						$item_count = self::getItemCountByCategory($val['cat_id']);
						$val['item_count']= mt("[count] item",array(
						  '[count]'=>$item_count
						));
					}
					
					$data[]=$val;
				}
				return $data;
			}
		}
		return false;
	}
	
	public static function getCategoryByID($cat_id='')
	{				
		if($cat_id>0){		
			$stmt="SELECT 
			cat_id,
			category_name,
			category_description,
			photo,
			status,
			category_name_trans,
			category_description_trans
			 FROM
			{{category}}
			WHERE cat_id=".FunctionsV3::q($cat_id)."
			AND status in ('publish')
			LIMIT 0,1
			";
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){		 	
				return $res;
			}			
		}
		return false;
	}
	
	public static function getItemByCategory($merchant_id='',$category_id='', $paginate=false, 
	$pagenumber=0, $pagelimit=10, $filter_dishes = array(), $cart_data=array() )
	{		
				
		$paginate_total=0; 
		$limit="LIMIT $pagenumber,$pagelimit";
		
		if($merchant_id>0 && $category_id>0){
			
		if(!$paginate){
			$limit='';
		}
		
		$and = '';
		
		$default_image='';
				
		$disabled_default_image = getOptionA('mobile2_disabled_default_image');
		$merchant_menu_type = getOptionA('mobileapp2_merchant_menu_type');
		if($merchant_menu_type==3){
			$default_image='resto_banner.jpg';
			$disabled_default_image=false;
		}
		
		$food_option_not_available = getOption($merchant_id,'food_option_not_available');
		if($food_option_not_available==1){
			$and = "AND not_available <> '2' ";
		}
		
		if(!empty($filter_dishes)){			
			$and.=" AND dish like ".FunctionsV3::q('%"'.$filter_dishes.'"%')." ";
		}
					
		$stmt="
		SELECT SQL_CALC_FOUND_ROWS 
		item_id,
		merchant_id,
		item_name,
		item_description,
		item_name_trans,
		item_description_trans,
		status,
		price,
		photo,
		discount,
		dish,
		addon_item,
		cooking_ref,
		ingredients
				
		FROM
		{{item}}
		WHERE
		category like ".FunctionsV3::q('%"'.$category_id.'"%')."
		AND
		status IN ('publish','published')
		AND merchant_id = ".FunctionsV3::q($merchant_id)."
		$and
		ORDER BY sequence ASC
		$limit
		";		
						
		/*inventory*/				
		if(FunctionsV3::inventoryEnabled($merchant_id)){
			$and_inv = '';
			if(!empty($filter_dishes)){			
				$and_inv.=" AND dish like ".FunctionsV3::q('%"'.$filter_dishes.'"%')." ";
			}
			
			if(InventoryWrapper::hideItemOutStocks($merchant_id)){
			   $stmt="
				SELECT SQL_CALC_FOUND_ROWS 
				item_id,
				merchant_id,
				item_name,
				item_description,
				item_name_trans,
				item_description_trans,
				status,
				price,
				photo,
				discount,
				dish,
				addon_item,
				cooking_ref,
				ingredients
				
				FROM
				{{item}} a
				WHERE
				category like ".FunctionsV3::q('%"'.$category_id.'"%')."
				AND
				status IN ('publish','published')
				AND merchant_id = ".FunctionsV3::q($merchant_id)."
				$and_inv
				AND item_id IN (
						  select item_id from {{view_item_stocks_status}}
						  where available ='1'
						  and track_stock='1'
						  and stock_status not in ('Out of stocks')		
						  and item_id = a.item_id				  
						)		
						
				ORDER BY sequence ASC
				$limit
				";			
			} else {				
				if($food_option_not_available==1):
				$stmt="
				SELECT SQL_CALC_FOUND_ROWS 
				item_id,
				merchant_id,
				item_name,
				item_description,
				item_name_trans,
				item_description_trans,
				status,
				price,
				photo,
				discount,
				dish,
				addon_item,
				cooking_ref,
				ingredients
				
				FROM
				{{item}} a
				WHERE
				category like ".FunctionsV3::q('%"'.$category_id.'"%')."
				AND
				status IN ('publish','published')
				AND merchant_id = ".FunctionsV3::q($merchant_id)."
				$and_inv
				AND item_id IN (
				  select item_id from {{view_item_stocks_status}}
				  where available ='1'						  
				  and item_id = a.item_id				  
				)								
				ORDER BY sequence ASC
				$limit
				";		
				endif;
			}			
		}		
		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){			
			$res = Yii::app()->request->stripSlashes($res);
			
			$total_records=0;
			$stmtc="SELECT FOUND_ROWS() as total_records";			
			if($resp = Yii::app()->db->createCommand($stmtc)->queryRow()){		 		
				$total_records=$resp['total_records'];
			}		
			
			$paginate_total = ceil( $total_records / $pagelimit );
			
			$data = array();
			foreach ($res as $val) {				
				$price=''; $prices = array(); $prices2 = array(); 
				if ( json_decode($val['price'])){
					$price = json_decode($val['price'],true);					
					foreach ($price as $size_id=>$priceval) {
												
						$original_price = $priceval;
						$discounted_price = 0;
						
						if($val['discount']>=0.001){
							$priceval = $priceval-$val['discount'];
							$discounted_price = $priceval;
						}					
							
						if(array_key_exists($size_id,(array)self::$sizes)){
							$prices[]=self::$sizes[$size_id]."&nbsp;".FunctionsV3::prettyPrice($priceval);
							$prices2[] = array(							  
							  'original_price'=>self::$sizes[$size_id]."&nbsp;".FunctionsV3::prettyPrice($original_price),
							  'discount'=>$val['discount'],
							  'discounted_price_pretty'=>self::$sizes[$size_id]."&nbsp;".FunctionsV3::prettyPrice($priceval),
							);
						} else {							
							$prices[]=FunctionsV3::prettyPrice($priceval);		
							$prices2[] = array(							  
							  'original_price'=>FunctionsV3::prettyPrice($original_price),
							  'discount'=>$val['discount'],
							  'discounted_price_pretty'=>FunctionsV3::prettyPrice($priceval),
							);
						}
					}					
				} 
				
				if(self::$enabled_trans==TRUE){
					$val['item_name'] = qTranslate($val['item_name'],'item_name',array(
					  'item_name_trans'=>json_decode($val['item_name_trans'],true)
					));
					
					$val['item_description'] = qTranslate($val['item_description'],'item_description',array(
					  'item_description_trans'=>json_decode($val['item_description_trans'],true)
					));
				}
								
				$val['photo']=mobileWrapper::getImage($val['photo'],$default_image,$disabled_default_image);						
				$val['item_description']=$val['item_description'];				
				
				$val['prices']=$prices;
				$val['prices2']=$prices2;
				$val['cat_id']=$category_id;
				
				$icon_dish= array();
				if(!empty($val['dish'])){				
					if (method_exists("FunctionsV3","getDishIcon")){	   
				       $icon_dish = FunctionsV3::getDishIcon($val['dish']);
					} else $icon_dish='';
				} else $icon_dish='';
				
				$val['dish_image'] = $icon_dish;
				
				$customizable = false;
				if(is_array($val['prices']) && count( (array) $val['prices'])>=2 ){
					$customizable = true;
				}
				if(!empty($val['addon_item'])){
					$customizable = true;
				}
				if(!empty($val['cooking_ref'])){
					$customizable = true;
				}
				if(!empty($val['ingredients'])){
					$customizable = true;
				}
				
				$val['customizable']=$customizable;
				
				$val['added_qty']=0;
				if(!$customizable){						
					$item_id = $val['item_id'];
					$category_id = $val['cat_id'];
					if(is_array($cart_data) && count($cart_data)>=1){
						foreach ($cart_data as $cart_data_val) {							
							if($item_id==$cart_data_val['item_id'] &&  $category_id==$cart_data_val['category_id'] ){
								$val['added_qty'] = $cart_data_val['qty'];
							}
						}
					}					
				}
				
				$data[] = $val;
			}
			
						
			return array(
			  'data'=>$data,
			  'paginate_total'=>$paginate_total
			);
		}
		unset($db);
		}
		return false;			
	}
	
	public static function getSize($merchant_id='')
	{		
		$db = new DbExt();
		$stmt="SELECT 
		size_id,
		size_name,
		size_name_trans
		FROM
		{{size}}
		WHERE
		merchant_id = ".FunctionsV3::q($merchant_id)."
		AND status IN ('publish')
		";
		if($res=$db->rst($stmt)){
			$data = array();			
		   	foreach ($res as $val) {		   
		   		if(self::$enabled_trans==TRUE){
		   			$val['size_name'] = qTranslate($val['size_name'],'size_name',array(
					  'size_name_trans'=>json_decode($val['size_name_trans'],true)
					));
		   		}
		   		$data[$val['size_id']]=$val['size_name'];
		   	}
		   	return $data;
		}
		return false;
	}
	
	public static function searchItemByName($merchant_id='',$item_name='')
	{	
		if($merchant_id>0 && !empty($item_name)){		
			$stmt="
			SELECT 
			item_id,
			merchant_id,
			item_name,
			category,
			item_name_trans,
			item_description,
			item_description_trans,
			photo,
			price,
			discount
			FROM {{item}}
			WHERE merchant_id = ".FunctionsV3::q($merchant_id)."
			AND item_name like ".FunctionsV3::q( "%$item_name%" )."
			AND not_available <> '2'
			AND status='publish'
			ORDER BY item_name ASC
			LIMIT 0,100
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				return $res;
			}
		}
		return false;
	}
	
	public static function searchByCategoryByName($merchant_id='',$category_name='')
	{
		$db = new DbExt();
		if($merchant_id>0 && !empty($category_name)){		
			
			$and='';
			$todays_day = date("l");
            $todays_day = !empty($todays_day)?strtolower($todays_day):'';                        
            $enabled_category_sked = getOption($merchant_id,'enabled_category_sked'); 
            if($enabled_category_sked==1){
    		    $and .= " AND $todays_day='1' ";
    	    }    	    	    
			
			$stmt="
			SELECT 
			cat_id,
			merchant_id,
			category_name,
			category_description,
			photo,
			status,
			category_name_trans,
			category_description_trans			
			FROM {{category}}
			WHERE merchant_id = ".FunctionsV3::q($merchant_id)."				
			$and		
			AND status in ('publish')
			AND ( category_name like ".FunctionsV3::q( "%$category_name%" )." OR 
			category_description LIKE ".FunctionsV3::q( "%$category_name%" )."  )			
			ORDER BY category_name ASC
			";								
			if($res = $db->rst($stmt)){
				return $res;
			}
		}
		return false;
	}
	
	
	public static function translateCookingRef($data=array())
	{
		$new_data = array();
		if(self::$enabled_trans==TRUE){	
			if(is_array($data) && count($data)>=1){
				foreach ($data as $cook_id=>$val) {
					if($res = Yii::app()->functions->getCookingRef($cook_id)){
						$val = qTranslate($res['cooking_name'],'cooking_name',array(
						  'cooking_name_trans'=>json_decode($res['cooking_name_trans'],true)
						));
					}
					$new_data[$cook_id]=$val;
				}
				return $new_data;
			}			
		}
		return $data;
	}
	
	public static function translateIngredients($data=array())
	{
		$new_data = array();
		if(self::$enabled_trans==TRUE){
			if(is_array($data) && count($data)>=1){
				foreach ($data as $ingredients_id=>$val) {
					if($res = Yii::app()->functions->getIngredients($ingredients_id)){
						$val = qTranslate($res['ingredients_name'],'ingredients_name',array(
						  'ingredients_name_trans'=>json_decode($res['ingredients_name_trans'],true)
						));
					}
					$new_data[$ingredients_id]=$val;
				}
				return $new_data;
			}
		} 
		return $data;
	}
	
	public static function dishesList()
	{
		$data = array();
		$db = new DbExt();
		$stmt="
		SELECT 
		dish_id,dish_name
		FROM {{dishes}}
		WHERE
		status IN ('publish')
		ORDER BY dish_id ASC
		";
		if($res = $db->rst($stmt)){
			foreach ($res as $val) {
				$data[]=array(
				  'dish_id'=>$val['dish_id'],
				  'dish_name'=>$val['dish_name'],
				);
			}
		}
		return $data;
	}
	
	public static function getItemCountByCategory($category_id='')
	{
		$db = new DbExt();
		$stmt="
		SELECT COUNT(*) AS total
		FROM {{item}}
		WHERE category like ".FunctionsV3::q('%"'.$category_id.'"%')."
		";
		if($res = $db->rst($stmt)){
			return $res[0]['total'];
		}
		return 0;
	}
	
	public static function limitText($p='',$text='', $limit = 100)
	{
		return $text;
		/*if(strlen($text)>=100){
			$text = $p->purify( strip_tags($text) );
			$text = substr($text,0,100)."...";
		}
		return $text;*/
	}
	
	public static function insertTagRelationship($banner_id='', $tag_ids=array())
	{
		Yii::app()->db->createCommand("DELETE FROM {{tags_relationship}}
		WHERE banner_id=".q($banner_id)."
		")->query();
		
		if(is_array($tag_ids) && count($tag_ids)>=1){
			foreach ($tag_ids as $tag_id) {
				Yii::app()->db->createCommand()->insert("{{tags_relationship}}",array(
				  'banner_id'=>$banner_id,
				  'tag_id'=>$tag_id
				));
			}
		}
	}
	
	public static function getItem($id='')
	{
		$resp = Yii::app()->db->createCommand()
          ->select()
          ->from('{{item}}')   
          ->where("item_id=:item_id",array(             
             ':item_id'=>$id,
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	return $resp;
        }
        return false;     
	}	
	
	public static function getSizeByID($size_id=0)
	{
		$resp = Yii::app()->db->createCommand()
          ->select()
          ->from('{{size}}')   
          ->where("size_id=:size_id",array(             
             ':size_id'=>(integer)$size_id,
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	return $resp;
        }
        return false;     
	}
	
	public static function verifyItemAvailable($item_id=0)
	{
		$resp = Yii::app()->db->createCommand()
          ->select('item_id,not_available')
          ->from('{{item}}')   
          ->where("item_id=:item_id",array(             
             ':item_id'=>(integer)$item_id,
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	if($resp['not_available']==2){
        		throw new Exception(  "Sorry but this item is not available" );
        	}
        	return true;
        } 
        throw new Exception(  "Item details not found" );       
	}
	
	public static function insertHomebannerTranslation($banner_id='', $data=array())
	{		
		if(Yii::app()->functions->multipleField()){
			if(is_array($data) && count($data)>=1){
				Yii::app()->db->createCommand("DELETE FROM {{mobile2_homebanner_translation}}
				WHERE banner_id=".q($banner_id)."
				 ")->query();
				foreach ($data['title'] as $lang=> $val) {
					$sub_title = isset($data['sub_title'][$lang])?$data['sub_title'][$lang]:'';
					$params = array(
					  'banner_id'=>$banner_id,
					  'language'=>$lang,
					  'title'=>$val,
					  'sub_title'=>$sub_title
					);
					Yii::app()->db->createCommand()->insert("{{mobile2_homebanner_translation}}",$params);
				}
			}
		}
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
	
	public static function getAllData($table='', $fields='', $order_by='')
	{
		$resp = Yii::app()->db->createCommand()
	      ->select($fields)
	      ->from("{{{$table}}}")   	      	         
	      ->order($order_by)	
	      ->queryAll();
	      if($resp){
	      	return $resp;
	      } else throw new Exception( "Record not found" );	      
	}
		
    public static function dropdownFormat($data=array(),$value='', $label='',$placeholder='Please select')
	{
		$list = array();
		$list['']= mt($placeholder);
		if(is_array($data) && count($data)>=1){
			foreach ($data as $val) {
				if(isset($val[$value]) && isset($val[$label])){
			 	   $list[ $val[$value] ] = $val[$label];
				}
			}
		}
		return $list;
	}		
		
}
/*end class*/
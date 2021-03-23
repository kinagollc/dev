<?php
class ApiController extends CController
{	
	public $data;
	public $code=2;
	public $msg='';
	public $details='';
	public $paginate_limit = 10;
	public $merchant_id='';
	public $device_id='';
	public $merchant_name='';
	
	public $device_uiid;
	
	public function __construct()
	{
		$this->data=$_GET;
		$this->getGETData();
		if(isset($_GET['post'])){
			$this->data=$_POST;		
			$this->getPOSTData();
		}	
				
		$website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone");		 
	    if (!empty($website_timezone)){
	 	   Yii::app()->timeZone=$website_timezone;
	    }		 
	    	    
	    
	    FunctionsV3::handleLanguage();
	    $lang=Yii::app()->language;	    
	}
		
	private function getGETData()
	{
		if(isset($_GET['code_version']) || isset($_POST['code_version'])){
			$this->device_uiid = isset($this->data['device_uiid'])?$this->data['device_uiid']:'';        
		} else {
			$this->device_uiid = isset($this->data['device_id'])?$this->data['device_id']:'';        
		}	
	}
	
	private function getPOSTData()
	{
		if(isset($_GET['code_version']) || isset($_POST['code_version'])){
			$this->device_uiid = isset($_POST['device_uiid'])?$_POST['device_uiid']:'';        
		} else {
			$this->device_uiid = isset($_POST['device_id'])?$_POST['device_id']:'';        
		}		
	}
	
	private function setMerchantTimezone(){
		$merchant_id = isset($this->merchant_id)?$this->merchant_id:'';
		if($merchant_id>0){			
			$mt_timezone=Yii::app()->functions->getOption("merchant_timezone",$this->merchant_id);			
	    	if (!empty($mt_timezone)){
	    		Yii::app()->timeZone=$mt_timezone;
	    	}    	
		}
	}
	
	public function t($message='')
	{
		return Yii::t("singleapp",$message);
	}
	
	
	public function beforeAction($action)
	{		
	    if(isset($_GET['debug'])){ 
	       dump("<h3>Request</h3>");
       	   dump($this->data);
        }
              
        $code_version = isset($_REQUEST['code_version'])?(float)$_REQUEST['code_version']:1.5;		        
		if($action->id=="addToCart" && $code_version>2.2){
			$this->data = $_POST;
		}	
		
		$merchant_keys = isset($this->data['merchant_keys'])?trim($this->data['merchant_keys']):'';		
		if(empty($merchant_keys)){
			$this->code = 10;	
			$this->msg = SingleAppClass::t("Invalid merchant keys");
			$this->output();
			return false;
		}
		if(!$resp=SingleAppClass::validateKeys($merchant_keys)){
			$this->code = 10;		
			$this->msg = SingleAppClass::t("Invalid merchant keys");
			$this->output();
			return false;
		}
				
		if($resp['status']!="active"){
			$this->code = 11;		
			$this->msg = SingleAppClass::t("Failed merchant is no longer active");
			$this->output();
			return false;
		}
			
		$this->merchant_id = $resp['merchant_id'];
		
		/*CHECK IF ADDON IS DISABLED FOR THIS MERCHANT*/
		$disabled_single_app_modules = getOption($this->merchant_id,'disabled_single_app_modules');
		if($disabled_single_app_modules==1){
			$this->code = 11;		
			$this->msg = SingleAppClass::t("Failed merchant module is disabled");
			$this->output();
			return false;
		}
		
		$this->merchant_name = isset($resp['restaurant_name'])?$resp['restaurant_name']:'';
		$this->device_id = isset($this->data['device_id'])?$this->data['device_id']:'';
		return true;
	}
	
	private function output()
    {
    	
       if (!isset($this->data['debug'])){    		
       	  header('Access-Control-Allow-Origin: *');       	  
          header('Content-type: application/javascript;charset=utf-8');          
       } 
       
	   $resp=array(
	     'code'=>$this->code,
	     'msg'=>$this->msg,
	     'details'=>$this->details,	     	     
	     'get'=>$_GET,
	     'post'=>$_POST
	   );		   
	   if (isset($this->data['debug'])){
	   	   dump($resp);
	   }
	   
	   if (!isset($_GET['callback'])){
  	   	   $_GET['callback']='';
	   }    
	   
	   if(isset($_GET['code_version']) || isset($_POST['code_version'])){
	   	  if (isset($_GET['jsonp']) && $_GET['jsonp']==TRUE){	   		   	   
	   	     echo $_GET['callback'] . '('.CJSON::encode($resp).')';
	      } else echo CJSON::encode($resp);
	   } else {
	   	  if (isset($_GET['json']) && $_GET['json']==TRUE){	   	
	   	      echo CJSON::encode($resp); 
	      } else echo $_GET['callback'] . '('.CJSON::encode($resp).')';		    	   	   	  
	   }    
	   	   
	   Yii::app()->end();
    }	
    
	public function actionIndex(){
		echo "API IS WORKING";
	}	
	
	public function actionloadCategory()
	{
		$this->setMerchantTimezone();
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $this->paginate_limit;
        } else  $page = 0;  
        
		if ($resp = SingleAppClass::getCategory($this->merchant_id, $page , $this->paginate_limit)){
			$this->code = 1; $this->msg = 'OK';  
			$this->details = array('data'=>$resp);
		} else {
			$this->code = 6;
			$this->msg = st("end of records");
			$this->details = array(
			  'title'=>st("No Category found"),
			  'sub_title'=>st("This restaurant has not published their menu yet")
			);	
		}	
		$this->output();
	}
	
	public function actionloadItemByCategory()
	{
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $this->paginate_limit;
        } else  $page = 0;  
        
        /*dump($this->merchant_id);
        dump($this->data);*/
        
        $p = new CHtmlPurifier();
        $new_data = array();
        
        $trans=getOptionA('enabled_multiple_translation'); 
        
        $category_id = isset($this->data['cat_id'])?$this->data['cat_id']:'';
        $and='';
        
        $food_option_not_available = getOption($this->merchant_id,'food_option_not_available');
        if($food_option_not_available==1){
        	$and = "AND not_available !='2' ";
        }	
        
        $stmt="SELECT 
        item_id,
        item_name,
        item_description,
        price,
        discount,
        dish,
        photo,
        item_name_trans,
        item_description_trans 
        
        FROM
		{{item}}
		WHERE
		category like ".FunctionsV3::q( '%"'. $category_id .'"%')."
		AND
		status IN ('publish','published')				
		AND merchant_id = ".FunctionsV3::q($this->merchant_id)."		
		$and		
		ORDER BY sequence ASC
		LIMIT $page,$this->paginate_limit
		";		
        
        if(SingleAppClass::inventoryEnabled($this->merchant_id)){
        	if(InventoryWrapper::hideItemOutStocks($this->merchant_id)){
        		$stmt="SELECT 
		        item_id,
		        item_name,
		        item_description,
		        price,
		        discount,
		        dish,
		        photo,
		        item_name_trans,
		        item_description_trans 
		        
		        FROM
				{{item}} a
				WHERE
				category like ".FunctionsV3::q( '%"'. $category_id .'"%')."
				AND
				status IN ('publish','published')	
				AND merchant_id = ".FunctionsV3::q($this->merchant_id)."	
				AND item_id IN (
						  select item_id from {{view_item_stocks_status}}
						  where available ='1'
						  and track_stock='1'
						  and stock_status not in ('Out of stocks')		
						  and item_id = a.item_id				  
						)													
				ORDER BY sequence ASC
				LIMIT $page,$this->paginate_limit
				";        		
        	} else {        		
        	   if($food_option_not_available==1):
        	     $stmt="SELECT 
			        item_id,
			        item_name,
			        item_description,
			        price,
			        discount,
			        dish,
			        photo,
			        item_name_trans,
			        item_description_trans 
			        
			        FROM
					{{item}} a
					WHERE
					category like ".FunctionsV3::q( '%"'. $category_id .'"%')."
					AND
					status IN ('publish','published')	
					AND merchant_id = ".FunctionsV3::q($this->merchant_id)."	
					AND item_id IN (
							  select item_id from {{view_item_stocks_status}}
							  where available ='1'							  
							  and item_id = a.item_id				  
							)													
					ORDER BY sequence ASC
					LIMIT $page,$this->paginate_limit
					";        		
        	   endif;
        	}       
        }
                
        if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
        	$res = Yii::app()->request->stripSlashes($res);
        	foreach ($res as $val) {        	
        		        		
        		if ( $trans==2){
	        		$item_name_trans = json_decode($val['item_name_trans'],true);
	        		$val['item_name_trans']=$item_name_trans;
	        		$val['item_name']=qTranslate($val['item_name'],'item_name',$val); 
	        		
	        		$item_description_trans = json_decode($val['item_description_trans'],true);
	        		$val['item_description_trans']=$item_description_trans;
	        		$val['item_description']=qTranslate($val['item_description'],'item_description',$val); 
        		}
        			
        		$val['item_name']= $p->purify($val['item_name']);
        		$val['item_description']= $p->purify($val['item_description']);
        		
        		if(!empty($val['photo'])){
        		  $val['photo_url'] = SingleAppClass::getImage($val['photo']);
        		} else $val['photo_url'] =SingleAppClass::getImage(getOption($this->merchant_id,'singleapp_default_image'),'default_cuisine.png');
        		        		        		
        		$val['prices'] = SingleAppClass::getPrices($val['price'],$val['discount']);
				unset($val['price']);
								
        		/*GET DISH*/
				$icon_dish= array();
				if(!empty($val['dish'])){				
					if (method_exists("FunctionsV3","getDishIcon")){	   
				       $icon_dish = FunctionsV3::getDishIcon($val['dish']);
					} else $icon_dish='';
				} else $icon_dish='';
				
				$val['icon_dish'] = $icon_dish;
											 	
        		$new_data[]=$val;
        	}
        	$this->code = 1; $this->msg="OK";
        	$this->details = array(
        	  'cat_id'=>$category_id,
        	  'data'=>$new_data
        	);
        } else {
        	$this->msg = $this->t("No item found in this category");
        	$this->code = 6;
        	$this->details = array(
			  'title'=>st("No item found"),
			  'sub_title'=>st("No item found in this category")
			);	
        }	
        
        $this->output();
	}
	
	public function actionloadItemDetails()
	{		
		
		/*CHECK IF ORDERING IS DISABLED*/
		$ordering_disabled=false; $ordering_msg='';
		$disabled_website_ordering = getOptionA('disabled_website_ordering');		
		if($disabled_website_ordering=="yes"){
			$ordering_msg = $this->t("Ordering is disabled by admin");
			$ordering_disabled=true;			
		}
		$merchant_disabled_ordering = getOption($this->merchant_id,'merchant_disabled_ordering');
		if($merchant_disabled_ordering=="yes"){
			$ordering_msg = $this->t("Ordering is disabled by merchant");
			$ordering_disabled=true;
		}
		$merchant_close_store = getOption($this->merchant_id,'merchant_close_store');
		if($merchant_close_store=="yes"){
			$ordering_msg = $this->t("Merchant is now close and not accepting any orders");
			$ordering_disabled=true;
		}
		
		$p = new CHtmlPurifier(); $cart_data=array();
		$item_id = isset($this->data['item_id'])?$this->data['item_id']:'';
		
		$trans=getOptionA('enabled_multiple_translation'); 
		
		if(is_numeric($item_id)){
			if ($res=Yii::app()->functions->getItemById($this->data['item_id'])){
				$res = $res[0];	
								
				/*TRANSLATE ADDON*/
				if($trans==2){
					$new_addon = array();
					if(is_array($res['addon_item']) && count($res['addon_item'])>=1){
						foreach ($res['addon_item'] as $add_val) {							
							$add_val['subcat_name']=qTranslate($add_val['subcat_name'],'subcat_name',$add_val);
																					
							if(is_array($add_val['sub_item']) && count($add_val['sub_item'])>=1){
								$new_sub_item = array();
								foreach ($add_val['sub_item'] as $sub_item_val) {
									$sub_item_val['sub_item_name'] = qTranslate($sub_item_val['sub_item_name'],'sub_item_name',$sub_item_val);
									$sub_item_val['item_description'] = qTranslate($sub_item_val['item_description'],'item_description',$sub_item_val);									
									$new_sub_item[]=$sub_item_val;
								}
								$add_val['sub_item']=$new_sub_item;
							}													
														
							$new_addon[]=$add_val;
						}
						
						$res['addon_item']=$new_addon;
					}				
				}								
				/*END TRANSLATE ADDON*/
				
				/*$food_option_not_available = getOption($this->merchant_id,'food_option_not_available');		
				if($food_option_not_available==2){*/
					if($res['not_available']==2){				
					   $ordering_msg = $this->t("Sorry but this item is not available");
					   $ordering_disabled=true;
					}
				//}	
				
				$res['item_name']=qTranslate($res['item_name'],'item_name',$res);        	
				$res['item_description']=qTranslate($res['item_description'],'item_description',$res);
				
				
				$res['item_name'] = $p->purify($res['item_name']);
				$res['item_description'] = $p->purify($res['item_description']);
				$res['item_name_trans'] = $p->purify($res['item_name_trans']);
				$res['item_description_trans'] = $p->purify($res['item_description_trans']);
				
				if(!empty($res['photo'])){
				    $res['photo'] = SingleAppClass::getImage($res['photo']);
				} else $res['photo'] = SingleAppClass::getImage(getOption($this->merchant_id,'singleapp_default_image'),'default_cuisine.png');
				
				/*GET DISH*/
				$icon_dish= array();
				if(!empty($res['dish'])){				
					if (method_exists("FunctionsV3","getDishIcon")){	   
				       $icon_dish = FunctionsV3::getDishIcon($res['dish']);
					} else $icon_dish='';
				} else $icon_dish='';
				
				$res['dish_list'] = $icon_dish;
								
				/*GALLERY*/
				$res['gallery']=array();
				if(!empty($res['gallery_photo'])){
					$new_gallery_photo=array();
					$gallery_photo = json_decode($res['gallery_photo'],true);
					if(is_array($gallery_photo) && count((array)$gallery_photo)>=1){
						foreach ($gallery_photo as $gallery_photo_val) {
							$new_gallery_photo[]= SingleAppClass::getImage($gallery_photo_val);
						}
						$res['gallery']=$new_gallery_photo;					
					}			
				}
				
				/*CHECK IF MULTIPLE PRICE*/
				$res['multiple_price'] = false;
				if(is_array($res['prices']) && count($res['prices'])>=2){	
					$new_price = array();
					foreach ($res['prices'] as $prices) {
						$prices['size']=qTranslate($prices['size'],'size',$prices); 					
						$new_price[]=$prices;
					}
					$res['prices']=$new_price;					
					$res['multiple_price'] = true;
				} else {				
					/*FIXED FOR SINGLE PRICE WITH ONLY 1 SIZE*/	
					if(isset($res['prices'][0])){
						if( $res['prices'][0]['size_id']>0 ){
							$res['multiple_price'] = true;
						}					
					}									
				}			
				
				$row = isset($this->data['row'])?$this->data['row']:'';
				if(is_numeric($row)){				
					if($resp=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
						$cart=json_decode($resp['cart'],true);
						if(array_key_exists($row,(array)$cart)){
							$cart[$row]['row']=$row;
							$cart_data = isset($cart[$row])?$cart[$row]:'';
						}
					}
				} else $cart_data='';
				
				// GET FAVORITE
				$is_favorite = false;
				if ($client_id = $this->checkToken()){
					if(SingleAppClass::isItemFavorite($client_id,$item_id)){
						$is_favorite = true;
					}
				}
				
				$inventory_enabled = SingleAppClass::inventoryEnabled($this->merchant_id);
											
				$this->code = 1;
				$this->msg = "OK";
				$this->details = array(
				  'inventory_enabled'=>$inventory_enabled==true?1:0,
				  'cat_id'=>isset($this->data['cat_id'])?$this->data['cat_id']:'',
				  'data'=>$res,
				  'cart_data'=>$cart_data,		
				  'ordering_disabled'=>$ordering_disabled,
				  'ordering_msg'=>$ordering_msg,
				  'is_favorite'=>$is_favorite
				);
				
			} else {
				$this->msg=$this->t("Item details not found");
				$this->code = 6;
				$this->details = array(
				  'title'=>st("Item details not found"),
				  'sub_title'=>st("Sorry but we cannot find what your looking for")
				);	
			}		
		} else {
			$this->msg = $this->t("Invalid item id");
			$this->code = 6;
			$this->details = array(
			  'title'=>st("Invalid item id"),
			  'sub_title'=>st("Sorry but we cannot find what your looking for")
			);	
		}	
		$this->output();		
	}
	
	public function actionaddToCart()
	{				
		
		$code_version = isset($_REQUEST['code_version'])?(float)$_REQUEST['code_version']:2.3;		
		if($code_version<2.2){
			$this->getGETData();
		    $this->data = $_GET;
		} else {
			$this->getPOSTData();
			$this->data = $_POST;
		}	
			
		$data = $_POST;			
		
		$cart_count = 0;
		$data['merchant_id'] = $this->merchant_id;
		$device_id = $this->device_uiid;
		$item_id = isset($data['item_id'])?$data['item_id']:'';		
		
		if(!is_numeric($item_id)){
			$this->msg = $this->t("Invalid item id");
			$this->output();
		}
		
		
		$qty = isset($data['qty'])?$data['qty']:'';		
		
		if($qty>0){			
			if (strpos($qty,'.') !== false) {
			   $this->msg = $this->t("invalid quantity");
			   $this->output();
			}	
		} else {
		  	$this->msg = $this->t("invalid quantity");
			$this->output();
		}
		
		if(!$item_details = Yii::app()->functions->getFoodItem($item_id)){
			$this->msg = $this->t("Item details not found");
			$this->output();
		}
		if($item_details['merchant_id']!=$this->merchant_id){
			$this->msg = $this->t("Item does not belong to merchant");
			$this->output();
		}		
		$data['discount'] = isset($item_details['discount'])?$item_details['discount']:0;
		$data['non_taxable'] = isset($item_details['non_taxable'])?$item_details['non_taxable']:0;
		/*dump($data);
		die();*/
		
		if(!isset($data['price'])){
			$this->msg = $this->t("Please select price");
			$this->output();
		}
				
		$refresh = 0;
		
		if(!empty($device_id)){			
			$DbExt=new DbExt;
			
			$debug = false;	
			
			if ( $res = SingleAppClass::getCart($device_id,$this->merchant_id)){
				$current_cart = json_decode($res['cart'],true);
								
				$row = isset($data['row'])?$data['row']:'';
				if(is_numeric($row)){								
					$current_cart[$row]= $data;	
					$refresh = 1;		
				} else {					
					if($debug){
						dump($data);
						dump("END DATA");
						dump($current_cart);
					}
					
					/*CHECK IF THE ITEM IS ALREADY IN THE CART */					
					$item_found = true; $found_key = -1;
					
					if(is_array($current_cart) && count($current_cart)>=1){
						foreach ($current_cart as $current_cart_key => $current_cart_val) {
							/*dump($current_cart_key);
							dump($current_cart_val);*/
										
							$item_found = true;
													
							if ($current_cart_val['item_id']!=$data['item_id']){
								$item_found = false;
							}
							if ($current_cart_val['price']!=$data['price']){
								$item_found = false;
							}
							
							/*COOKING REF*/
							if(array_key_exists('cooking_ref',$data) && array_key_exists('cooking_ref',$current_cart_val)){
								if ( $data['cooking_ref']!=$current_cart_val['cooking_ref']){
									$item_found = false;
								}
							} else {								
								if(!array_key_exists('cooking_ref',$data) && !array_key_exists('cooking_ref',$current_cart_val)){
								} else $item_found = false;								
							}
							
							/*INGREDIENTS*/
							if(array_key_exists('ingredients',$data) && array_key_exists('ingredients',$current_cart_val)){
								$ingredients = json_encode($data['ingredients']);
								$ingredients2 = json_encode($current_cart_val['ingredients']);								
								if($ingredients!=$ingredients2){
									$item_found = false;
								} 
							} else {
								if(!array_key_exists('ingredients',$data) && !array_key_exists('ingredients',$current_cart_val)){
								} else $item_found = false;								
							}
							
							/*ADDON*/
							if(array_key_exists('sub_item',$data) && array_key_exists('sub_item',$current_cart_val)){
								$sub_item = json_encode($data['sub_item']);
								$sub_item2 = json_encode($current_cart_val['sub_item']);
								if($sub_item!=$sub_item2){
									$item_found = false;
								} 
							} else {
								if(!array_key_exists('sub_item',$data) && !array_key_exists('sub_item',$current_cart_val)){
								} else $item_found = false;								
							}
							
							if($item_found==TRUE){								
							   $found_key = $current_cart_key;
						    } 
						    
						} /*END LOOP*/
						
						if($found_key>=0){
							if($debug){dump("found key=> $found_key");}						
							$current_cart[$found_key]['qty']  = $current_cart[$found_key]['qty']+$data['qty'];
						} else {
							array_push($current_cart,$data);
						}
						
					} else {									
						array_push($current_cart,$data);
					}					
				}
				
				if($debug){
					dump("FINAL CART");
					dump($current_cart);
					die();
				}
				
				/*inventory*/				
				$merchant_id = $this->merchant_id;
				if(SingleAppClass::inventoryEnabled($merchant_id)){
				  $current_item_id = isset($data['item_id'])?(integer)$data['item_id']:'';
				  $current_item_price = isset($data['price'])?$data['price']:'';		
				  $current_item_size = isset($data['with_size'])?(integer)$data['with_size']:0;
				  $inv_qty = 0;				  
				  foreach ($current_cart as $val) {
				  	  if($current_item_id==$val['item_id'] && trim($current_item_price) == trim($val['price']) ){
				  	  	 $inv_qty+=$val['qty'];
				  	  }
				  }				 				  				  
				  try {
				  	 StocksWrapper::verifyStocks($inv_qty,$merchant_id,$current_item_id,$current_item_size,$current_item_price);
				  } catch (Exception $e) {
		            $this->msg = $e->getMessage();
		            $this->output();
		          }					  
				}
				
				
				$cart_count = count($current_cart);
				$DbExt->updateData("{{singleapp_cart}}",array(
				  'device_id'=>$device_id,
				  'device_platform'=>isset($this->data['device_platform'])?strtolower($this->data['device_platform']):'android',
				  'cart'=>json_encode($current_cart),
				  'cart_count'=>$cart_count,
				  'date_modified'=>FunctionsV3::dateNow(),
				),'cart_id', $res['cart_id']);
			} else {			
					
				$cart_count=1;
				$DbExt->insertData("{{singleapp_cart}}",array(
			     'device_id'=>$device_id,
			     'device_platform'=>isset($this->data['device_platform'])?strtolower($this->data['device_platform']):'',
			     'cart'=>json_encode(array($data)),
			     'cart_count'=>$cart_count,
			     'date_modified'=>FunctionsV3::dateNow(),
			     'merchant_id'=>$this->merchant_id
			    ));
			}		
			
			$this->code = 1;
			$this->msg=$this->t("Added to cart");
			if($refresh==1){
				$this->msg=$this->t("Cart updated");
			}			
			$this->details=array(
			 'cart_count'=>$cart_count,
			 'refresh'=>$refresh
			);
				
		} else $this->msg = $this->t("Device id is empty. please restart the application and try again");
		$this->output();
	}
	
	public function actiongetCartCount()
	{		
		$basket_total=0;$item_total=0; 
		
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($client_info = SingleAppClass::getCustomerByToken($token)){						
			$this->data['client_id'] = $client_info['client_id'];						
			$this->data['merchant_id'] = $this->merchant_id;
            SingleAppClass::registeredDevice($this->data);
		}
		
		if($res=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
			$cart=json_decode($res['cart'],true);			
			
			$params = array(
			  'delivery_type'=>'delivery',
			  'merchant_id'=>$this->merchant_id,
			  'card_fee'=>0
			);
			
			Yii::app()->functions->displayOrderHTML( $params,$cart );
			$code = Yii::app()->functions->code;		
			if($code==1){
				$details = Yii::app()->functions->details['raw'];
				if(is_array($cart) && count($cart)>=1){
					foreach ($cart as $val) {					
						$item_total+=$val['qty'];
					}
				}			    
				$basket_total = $details['total']['subtotal'];
				$basket_total = FunctionsV3::prettyPrice($basket_total);
				$this->code=1;
				$this->msg = "OK";				
				$this->details = array(
				  'count'=>$item_total,
				  'basket_count'=>st("[item] items",array('[item]'=>$item_total)),
				  'basket_total'=>$basket_total
				);			
				$this->output();
			}								
		} 
		
		$this->msg=st("0 found");
		$this->details = array(
		  'basket_count'=>st("0 item"),
		  'basket_total'=>baseCurrency()."0.00"
		);				
		$this->output();
	}
	
	public function actionloadCart()
	{						
		$this->setMerchantTimezone();		
		
		/*CHECK IF ORDERING IS DISABLED*/
		$disabled_website_ordering = getOptionA('disabled_website_ordering');		
		if($disabled_website_ordering=="yes"){
			$this->msg = $this->t("Ordering is disabled by admin");
			$this->code = 4;
			$this->output();
		}
		$merchant_disabled_ordering = getOption($this->merchant_id,'merchant_disabled_ordering');
		if($merchant_disabled_ordering=="yes"){
			$this->msg = $this->t("Ordering is disabled by merchant");
			$this->code = 4;
			$this->output();
		}
				
		$merchant_close_store = getOption($this->merchant_id,'merchant_close_store');
		if($merchant_close_store=="yes"){
			$this->msg = $this->t("Merchant is now close and not accepting any orders");
			$this->code = 4;
			$this->output();
		}
		
		
		$search_resp = SingleAppClass::searchMode();		
		$search_mode = $search_resp['search_mode'];	
		$location_mode = $search_resp['location_mode'];	
				
				
		$transaction_type='';
		$services = Yii::app()->functions->DeliveryOptions($this->merchant_id);
		
		if(is_array($services) && count($services)>=1){
			foreach ($services as $services_key=>$services_val) {				
				$transaction_type = $services_key;
				break;				
			}
		}
		
		if(isset($this->data['transaction_type'])){
			if(!empty($this->data['transaction_type'])){
				$transaction_type=$this->data['transaction_type'];
			}
		} else {
			$this->data['transaction_type'] = $transaction_type;
		}	
		
		/*GET CART*/
		$res=SingleAppClass::getCart($this->device_uiid,$this->merchant_id);

		/*CHECK TIPS DEFAULT*/
		if($res && $transaction_type=="delivery"){
			$merchant_tip_default = getOption($this->merchant_id,'merchant_tip_default');		
			if($merchant_tip_default>0 && $res['tips']<=0 && $res['remove_tip']<=0 ){			
				$res['tips'] = $merchant_tip_default;
				
				Yii::app()->db->createCommand()->update("{{singleapp_cart}}",array(
				  'tips'=>$merchant_tip_default
				),
		  	    'cart_id=:cart_id',
			  	    array(
			  	      ':cart_id'=>$res['cart_id']
			  	    )
		  	    );
			}					
					  
		} else if ( $res ) {
			if($res['tips']>0){
				Yii::app()->db->createCommand()->update("{{singleapp_cart}}",array(
				  'tips'=>0
				),
		  	    'cart_id=:cart_id',
			  	    array(
			  	      ':cart_id'=>$res['cart_id']
			  	    )
		  	    );
			}		
			$res['tips'] = 0;
		}	

				
		if($res){
			$cart=json_decode($res['cart'],true);					
					
			$data = array(
			  'delivery_type'=>$transaction_type,
			  'merchant_id'=>$this->merchant_id,
			  'card_fee'=>0
			);
			//dump($data);
			if($res['tips']>0.0001){
				$data['cart_tip_percentage']=$res['tips'];
				$data['tip_enabled']=2;
				$data['tip_percent']=$res['tips'];
			}		
			
			$voucher_details = !empty($res['voucher_details'])?json_decode($res['voucher_details'],true):false;	
			if(is_array($voucher_details) && count($voucher_details)>=1){
				$data['voucher_name']=$voucher_details['voucher_name'];
				$data['voucher_amount']=$voucher_details['amount'];
				$data['voucher_type']=$voucher_details['voucher_type'];
			}
			
			if($res['points_apply']>0.0001){
				$data['points_apply']=$res['points_apply'];
			}
			if($res['points_amount']>0.0001){
				$data['points_amount']=$res['points_amount'];
			}
			
			unset($_SESSION['shipping_fee']);
			if($res['delivery_fee']>0.0001){
				$data['delivery_charge']=$res['delivery_fee'];
			}
			
			$cart_details = $res;
			unset($cart_details['cart']);		
			unset($cart_details['device_id']);
			unset($cart_details['cart_id']);
			unset($cart_details['cart_id']);									
			unset($_SESSION['pts_redeem_amt']);
			
			/*inventory*/
			if(SingleAppClass::inventoryEnabled($this->merchant_id)){
				$new_cart = array();				
			    if(is_array($cart) && count($cart)>=1){
			    	foreach ($cart as $cartval) {			    		
			    		try {					    		  
			    		   StocksWrapper::verifyStocks(
			    		      isset($cartval['qty'])?(integer)$cartval['qty']:0,
			    		      $this->merchant_id,
			    		      isset($cartval['item_id'])?(integer)$cartval['item_id']:0,
			    		      isset($cartval['with_size'])?(integer)$cartval['with_size']:0,
			    		      isset($cartval['price'])?$cartval['price']:0
			    		   );
			    		   $new_cart[]=$cartval;
			    		} catch (Exception $e) {
				   	  	    //echo $e->getMessage();
				   	  	 }
			    	}
			    	$cart = $new_cart;
			    }			
			}
			
			$multiple_translation = getOptionA('enabled_multiple_translation'); 	
			
			Yii::app()->functions->displayOrderHTML( $data,$cart );
			$code = Yii::app()->functions->code;
			$msg  = Yii::app()->functions->msg;
			if ($code==1){
			   $this->code = 1;
			   $details = Yii::app()->functions->details['raw'];
			   
			   
			   /*TRANSLATE*/
			   if($multiple_translation==2){
				   if(is_array($details['item']) && count($details['item'])>=1){
				   	  $new_item = array();
				   	  foreach ($details['item'] as $key=> $details_item_val) {				   	  	
				   	  	 $details_item_val['item_name'] = qTranslate($details_item_val['item_name'],'item_name',$details_item_val['item_name_trans']);
				   	  	 
				   	  	 if(isset($details_item_val['new_sub_item'])){
				   	  	 if(is_array($details_item_val['new_sub_item']) && count($details_item_val['new_sub_item'])>1){				   	  	 	
				   	  	 	$newest_new_sub_item_val=array();
				   	  	 	foreach ($details_item_val['new_sub_item'] as $new_sub_item_key=>$new_sub_item_val) {		
				   	  	 		$new_sub_item_key = qTranslate($new_sub_item_key,'subcategory_name',$new_sub_item_val[0]['subcategory_name_trans']);				   	  	 						   	  	 		
				   	  	 		$newest_new_sub_item_val[$new_sub_item_key]=$new_sub_item_val;
				   	  	 	}		
				   	  	 	$details_item_val['new_sub_item']=$newest_new_sub_item_val;
				   	  	 }				   	  
				   	  	 }
				   	  	 
				   	  	 $new_item[$key]=$details_item_val;
				   	  }		
				   	  $details['item']=$new_item;
				   }			
			   }
			   /*END TRANSLATE*/
			   
			   //dump($details);

			   
			   
			   /*EURO TAX*/
			   $is_apply_tax = 2;
			   if(EuroTax::isApplyTax($this->merchant_id)){
			   	   $new_total = EuroTax::computeWithTax($details, $this->merchant_id);
			   	   $details['total']=$new_total;			
			   	   $is_apply_tax=1;   	   
			   }
			   /*EURO TAX*/
			   
			   //dump($details);
			   
			   $has_addressbook = 0;
			   $client_id='';
			   
			   $token = isset($this->data['token'])?$this->data['token']:'';
    	       if($client_info = SingleAppClass::getCustomerByToken($token)){
    		       $client_id = $client_info['client_id'];
    		       if($search_mode=="location"){
    		       	   if(LocationWrapper::hasAddress($client_id)){
    		       	   	  $has_addressbook = 1; 
    		       	   }    		       
    		       } else {
	    		       if (SingleAppClass::getAddressBookByClient($client_id)){
					   	  $has_addressbook = 1; 
					   }
    		       }
    	       }    

    	       $defaul_delivery_date = date("Y-m-d");
    	       $date_list = SingleAppClass::deliveryDateList($this->merchant_id);
    	       foreach ($date_list as $date_list_key => $date_list_val) {    	       	  
    	       	  $defaul_delivery_date = $date_list_key;
    	       	  break;
    	       }
    	       
    	       $subtotal = $details['total']['subtotal'];
    	       $cart_error=array();

    	       /*CHECKING MAX AND MIN AMOUNT*/
    	       if($transaction_type=="delivery"){
    	       	    	       	 
    	       	  $merchant_minimum_order = getOption($this->merchant_id,'merchant_minimum_order');     	
    	       	  $min_tables_enabled = getOption($this->merchant_id,'min_tables_enabled');
    	       	      	       	      	       	      	      	       	  
    	       	  if($min_tables_enabled==1 && !empty($res['distance'])){    	       	  	  
    	       	  	  $merchant_minimum_order = CheckoutWrapperTemp::getMinimumOrderTable(
    	       	  	  $this->merchant_id,$res['distance'],$res['distance_unit'],$merchant_minimum_order
    	       	  	  );
    	       	  }    	       
    	       	     	       	     	
    	       	  if($merchant_minimum_order>0){
    	       	  	 if($merchant_minimum_order>$subtotal){
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order does not meet the minimum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($merchant_minimum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }      
    	       	  
    	       	  $merchant_maximum_order = getOption($this->merchant_id,'merchant_maximum_order');
    	       	  if($merchant_maximum_order>0.001){
    	       	  	 if($subtotal>$merchant_maximum_order) {
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order has exceeded the maximum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($merchant_maximum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }    	       	     
    	       } elseif ( $transaction_type=="pickup"){
    	       	  $minimum_order = getOption($this->merchant_id,'merchant_minimum_order_pickup'); 
    	       	  if($minimum_order>0.001){
    	       	  	 if($minimum_order>$subtotal){
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order does not meet the minimum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($minimum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }    	         	       	  
    	       	  $maximum_order = getOption($this->merchant_id,'merchant_maximum_order_pickup');
    	       	  if($maximum_order>0.001){
    	       	  	 if($subtotal>$maximum_order) {
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order has exceeded the maximum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($maximum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }	       	
    	       } elseif ( $transaction_type=="dinein"){
    	       	  $minimum_order = getOption($this->merchant_id,'merchant_minimum_order_dinein'); 
    	       	  if($minimum_order>0.001){
    	       	  	 if($minimum_order>$subtotal){
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order does not meet the minimum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($minimum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }      	       	  
    	       	  $maximum_order = getOption($this->merchant_id,'merchant_maximum_order_dinein');
    	       	  if($maximum_order>0.001){
    	       	  	 if($subtotal>$maximum_order) {
    	       	  	 	$cart_error[] = Yii::t("singleapp","Sorry, your order has exceeded the maximum [transaction_type] amount of [min_amount]",array(
    	       	  	 	 '[min_amount]'=>FunctionsV3::prettyPrice($maximum_order),
    	       	  	 	 '[transaction_type]'=>$this->t($transaction_type)
    	       	  	 	));
    	       	  	 }    	       	  
    	       	  }	       	
    	       }    	       
    	       /*CHECKING MAX AND MIN AMOUNT*/	
    	           	           	      
    	       /*CHECK IF HAS POINTS ADDON*/
    	       $available_points=0; $available_points_label = '';
    	       $points_enabled = '';   $pts_disabled_redeem=''; 	       
    	       if (FunctionsV3::hasModuleAddon("pointsprogram")){
    	       	    	       	
    	       	  $points_enabled = getOptionA('points_enabled');
    	       	  if($points_enabled=="1"){
    	       	   	  if(!PointsProgram::isMerchantSettingsDisabled()){
    	       	   	  	  $mt_disabled_pts = getOption($this->merchant_id,'mt_disabled_pts');
    	       	   	  	  if($mt_disabled_pts==2){
    	       	   	  	  	 $points_enabled='';
    	       	   	  	  }	    	       	   	  
    	       	   	  }
    	       	  }
    	       	  
    	       	  $pts_disabled_redeem = getOptionA('pts_disabled_redeem');
    	       	  if(!PointsProgram::isMerchantSettingsDisabled()){
    	       	  	  $mt_pts_disabled_redeem=getOption($this->merchant_id,'mt_pts_disabled_redeem');
    	       	  	  if($mt_pts_disabled_redeem>0){
    	       	  	  	  $pts_disabled_redeem=$mt_pts_disabled_redeem;
    	       	  	  }    	       	  
    	       	  }
    	       	   
    	       	  /*GET EARNING POINTS FOR THIS ORDER*/
    	       	  $subtotal = $details['total']['subtotal'];    	       	  
    	       	  if ($earn_pts = SingleAppClass::getCartEarningPoints($cart,$subtotal,$this->merchant_id)){      	       	  	 
    	       	  	 Yii::app()->db->createCommand()->update("{{singleapp_cart}}",array(
    	       	  	   'points_earn'=>$earn_pts['points_earn'],
    	       	  	   'date_modified'=>FunctionsV3::dateNow()
    	       	  	 ),
			  	    'device_id=:device_id',
				  	    array(
				  	      ':device_id'=>$this->device_uiid
				  	    )
			  	    );   	       	  	 
    	       	  }    	    
    	       	         	       	     	       	    	       
    	       	  if($client_id>0){    	       	  	    	       	      	       	   	  
    	       	   	  if($points_enabled=="1"){
	    	       	   	  $available_points = PointsProgram::getTotalEarnPoints( $client_id , $this->merchant_id);
	    	       	   	  $available_points_label = Yii::t("singleapp","Your available points [points]",array(
	    	       	   	    '[points]'=>$available_points
	    	       	   	  ));
    	       	   	  }
    	       	   }    	       
    	       }    	
    	       
    	       $checkout_stats = FunctionsV3::isMerchantcanCheckout($this->merchant_id);
    	       if($checkout_stats['code']==2){
    	       	  $cart_error[] = $checkout_stats['msg'];
    	       }			
    	       
    	       $subtotal = isset($details['total']['subtotal'])?$details['total']['subtotal']:0;        	       
    	       Yii::app()->db->createCommand()->update("{{singleapp_cart}}",array(
				  'cart_subtotal'=>(float)$subtotal,
       	  	      'date_modified'=>FunctionsV3::dateNow()
				),
          	     'cart_id=:cart_id',
          	     array(
          	      ':cart_id'=>$res['cart_id']
          	     )
          	   );		
    	       
			   $this->details = array(
			     'is_apply_tax'=>$is_apply_tax,
			     'checkout_stats'=>$checkout_stats,
			     'has_addressbook'=>$has_addressbook,
			     'services'=>$services,
			     'transaction_type'=>$transaction_type,
			     'default_delivery_date'=>$defaul_delivery_date,
			     //'default_delivery_date_pretty'=>date("D F d, Y"),
			     'default_delivery_date_pretty'=>FunctionsV3::prettyDate($defaul_delivery_date),
			     'required_delivery_time'=>getOption($this->merchant_id,'merchant_required_delivery_time'),	
			     'tip_list'=>SingleAppClass::tipList(),
			     'data'=>$details,
			     'cart_details'=>$cart_details,
			     'cart_error'=>$cart_error,
			     'points_enabled'=>$points_enabled,			     
			     'points_earn'=>isset($earn_pts['points_earn'])?$earn_pts['points_earn']:'',
			     'pts_label_earn'=>isset($earn_pts['pts_label_earn'])?$earn_pts['pts_label_earn']:'',
			     'available_points'=>$available_points,
			     'available_points_label'=>$available_points_label,
			     'pts_disabled_redeem'=>$pts_disabled_redeem,
			     'opt_contact_delivery'=>getOption($this->merchant_id,'merchant_opt_contact_delivery'),
			   );
			} else {
				SingleAppClass::clearCart($this->device_uiid);
				$this->msg = $msg;
			}	
		} else $this->msg = $this->t("Cart is empty");
		$this->output();
	}
	
	public function actionremoveCartItem()
	{		
		$row = isset($this->data['row'])?$this->data['row']:0;		
		if($res=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
			$cart=json_decode($res['cart'],true);			
			if(array_key_exists($row,(array)$cart)){
				unset($cart[$row]);
				$DbExt=new DbExt;				
				$DbExt->updateData("{{singleapp_cart}}",array(
				  'device_id'=>$this->device_uiid,
				  'cart'=>json_encode($cart),
				  'cart_count'=>count($cart),
				),'cart_id', $res['cart_id']);
				
				$this->code = 1;
				$this->msg="OK"; 
				$this->details='';
			} else $this->msg = $this->t("Cannot find cart row");
		} else $this->msg = $this->t("Cart is empty");
		$this->output();
	}
	
	public function actionapplyVoucher()
	{		

		if (!$client_id = $this->checkToken()){
			$this->msg = st("You must login to apply voucher");
			$this->output();
		}
				
		$data = array(
		  'delivery_type'=>isset($this->data['transaction_type'])?$this->data['transaction_type']:'',
		  'merchant_id'=>$this->merchant_id,
		  'card_fee'=>0
		);
		if ( $cart = SingleAppClass::getCartContent($this->device_uiid,$data)){			
			if ( $cart['total']['discounted_amount']>=0.0001){
				$this->msg = $this->t("Sorry you cannot apply voucher, exising discount is alread applied in your cart");
				$this->output();
			}
		}			
		
		/*CHECK IF HAS POINTS APPLIED*/		
		if (FunctionsV3::hasModuleAddon("pointsprogram")){
			$pts_enabled_add_voucher = getOptionA('pts_enabled_add_voucher');						
			$is_disabled_merchant_settings = PointsProgram::isMerchantSettingsDisabled();
			if(!$is_disabled_merchant_settings){
				$mt_pts_enabled_add_voucher = getOption($this->merchant_id,'mt_pts_enabled_add_voucher');
				if($mt_pts_enabled_add_voucher>0){
					$pts_enabled_add_voucher=$mt_pts_enabled_add_voucher;
				}		
			}						
			if($pts_enabled_add_voucher!=1){
				$pts_redeem_amt_orig = isset($cart['total']['pts_redeem_amt_orig'])?$cart['total']['pts_redeem_amt_orig']:0;
				if($pts_redeem_amt_orig>0.0001){
					$this->msg = $this->t("Sorry but you cannot apply voucher when you have already redeem a points");
					$this->output();
				}			
			}
		}
		/*END CHECK IF HAS POINTS APPLIED*/
		
		$voucher_name = isset($this->data['voucher_name'])?$this->data['voucher_name']:'';
		if(!empty($voucher_name)){						
			if ( $res=SingleAppClass::getVoucherMerchant($client_id, $voucher_name,$this->merchant_id) ){
				$voucher_type='merchant';
			} else {
				$voucher_type='admin';
				$res=SingleAppClass::getVoucherMerchant($client_id,$voucher_name);
			}
			if($res){
				
				if ( !empty($res['expiration'])){						
					$expiration=$res['expiration'];
					$now=date('Y-m-d');						
					$date_diff=date_diff(date_create($now),date_create($expiration));						
					if (is_object($date_diff)){
						if ( $date_diff->invert==1){
							if ( $date_diff->d>0){
								$this->msg= $this->t("Voucher code has expired");
								$this->output();
							}
						}
					}
				}
				
				/*check if voucher code can be used only once*/
				if ( $res['used_once']==2){
					if ( $res['number_used']>0){
						$this->msg= $this->t("Sorry this voucher code has already been used");
						$this->output();
					}
				}
				
				if($voucher_type=="admin"){
					if (!empty($res['joining_merchant'])){							
						$joining_merchant=json_decode($res['joining_merchant']);							
						if (in_array($this->merchant_id,(array)$joining_merchant)){								
						} else {
							$this->msg= $this->t("Sorry this voucher code cannot be used on this merchant");
							$this->output();
						}
					} 				
				}						
				
				
				/*CHECK SUBTOTAL WILL BECOME LESS THAN ZERO*/
				if($resp=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
					$cart=json_decode($resp['cart'],true);
					$data = array(
					  'delivery_type'=>isset($this->data['transaction_type'])?$this->data['transaction_type']:'delivery',
					  'merchant_id'=>$this->merchant_id,
					  'card_fee'=>0
					);
					Yii::app()->functions->displayOrderHTML( $data,$cart );
					if(Yii::app()->functions->code==1){
						$raw = Yii::app()->functions->details['raw']['total'];
						$subtotal = isset($raw['subtotal'])?$raw['subtotal']:0;						
												
						if ($res['voucher_type']=="percentage"){
						    $less_voucher = $subtotal*($res['amount']/100);
						    $subtotal_after_voucher = $subtotal  - $less_voucher;
						} else $subtotal_after_voucher = $subtotal- $res['amount'];
						
						if($subtotal_after_voucher<=0){
							$this->msg = $this->t("Sorry you cannot Voucher which the Sub Total will become negative when after applying the voucher");
							$this->output();
						}
					}					
				}
				
				/*CHECK IF ALREADY USE*/
				if ( $res['found']<=0){					
				} else {
					$this->msg = $this->t("Sorry but you have already use this voucher code");
					$this->output();
				}
						
				$params = array(
				  'voucher_id'=>$res['voucher_id'],
				  'voucher_owner'=>$res['voucher_owner'],
				  'voucher_name'=>$res['voucher_name'],
				  'amount'=>$res['amount'],
				  'voucher_type'=>$res['voucher_type'],
				);
								
				$DbExt=new DbExt;
				$DbExt->updateData("{{singleapp_cart}}",array(
				  'voucher_details'=>json_encode($params)
				),'device_id', $this->device_uiid);
				$this->code = 1;
				$this->msg="OK";
				$this->details='';
						
			} else $this->msg = $this->t("Invalid voucher code");
		} else $this->msg = $this->t("Voucher is required");
		$this->output();
	}
	
	public function actionremoveVoucher()
	{
		SingleAppClass::removeVoucher($this->device_uiid);
		$this->code = 1;
		$this->msg="OK";
		$this->details='';
		$this->output();
	}
	
	public function actionservicesList()
	{
		$services = Yii::app()->functions->DeliveryOptions($this->merchant_id);
		if(is_array($services) && count($services)>=1){
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'data'=>$services
			);
		} else $this->msg = $this->t("Services not available");
		$this->output();
	}
	
	public function actiondeliveryDateList()
	{		
		$this->setMerchantTimezone();			
		$dates = FunctionsV3::getDateList($this->merchant_id);
		
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		 'data'=>$dates
		);
		$this->output();
		$this->output();
	}
	
	public function actiondeliveryTimeList()
	{			
		$this->setMerchantTimezone();			
		$delivery_date = isset($this->data['delivery_date'])?$this->data['delivery_date']:'';		
		$times = FunctionsV3::getTimeList($this->merchant_id,$delivery_date);
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(
		  'data'=>$times
		);		
		$this->output();
	}
	
	public function actioncustomerRegister()
	{
		
		$Validator=new Validator;
		$reg_email = getOption($this->merchant_id,'singleapp_reg_email');
		$reg_mobile = getOption($this->merchant_id,'singleapp_reg_phone');
		
		$this->data = SingleAppClass::purifyData($this->data);
				
		if(empty($reg_email) && empty($reg_mobile)){
			$reg_email = 1;
			$reg_mobile = 1;
		}			
				
		if ($this->data['password']!=$this->data['cpassword']){			
			$Validator->msg[] = $this->t("Confirm password does not match");
		}
		
		/*check if email address is blocked*/
		if($reg_email==1){
	    	if ( FunctionsK::emailBlockedCheck($this->data['email_address'])){
	    		$Validator->msg[] = $this->t("Sorry but your email address is blocked by website admin");    		
	    	}	    
		}
    	
		if($reg_mobile==1){
	    	if ( FunctionsK::mobileBlockedCheck($this->data['contact_phone'])){
				$Validator->msg[] = $this->t("Sorry but your mobile number is blocked by website admin");			
			}
			$functionk=new FunctionsK();
			if ( $functionk->CheckCustomerMobile($this->data['contact_phone'])){
	        	$Validator->msg[] = $this->t("Sorry but your mobile number is already exist in our records");        	
	        }	
		}
		
		if($reg_email==1){
			if ( $resp = Yii::app()->functions->isClientExist($this->data['email_address']) ){			
				$Validator->msg[] = $this->t("Sorry but your email address already exist in our records");
			}
		}
				
		if($Validator->validate()){								
			$params=array(
    		  'first_name'=>$this->data['first_name'],
    		  'last_name'=>$this->data['last_name'],    		  
    		  'password'=>md5($this->data['password']),
    		  'date_created'=>FunctionsV3::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],    		      		  
    		  'single_app_merchant_id'=>$this->merchant_id,    		  
    		);    		    
    		
    		if($reg_email==1){
    			$params['email_address'] = $this->data['email_address'];
    		}	
    		if($reg_mobile==1){
    			$params['contact_phone'] = trim($this->data['contact_phone']);
    		}	
    			
    		/** update 2.3*/
	    	if (isset($this->data['custom_field1'])){
	    		$params['custom_field1']=!empty($this->data['custom_field1'])?$this->data['custom_field1']:'';
	    	}
	    	if (isset($this->data['custom_field2'])){
	    		$params['custom_field2']=!empty($this->data['custom_field2'])?$this->data['custom_field2']:'';
	    	}
	    	
	    	$primary_next_step = isset($this->data['next_step'])?$this->data['next_step']:'';
	    		    		    		    	
	    	/** send verification code */
            $enabled_verification_mobile = getOptionA('website_enabled_mobile_verification');            
	    	if ( $enabled_verification_mobile=="yes" && $reg_mobile==1){
	    		$code=Yii::app()->functions->generateRandomKey(5);		    		
	    		FunctionsV3::sendCustomerSMSVerification($params['contact_phone'],$code);
	    		$params['mobile_verification_code']=$code;
	    		$params['status']='pending';
	    		$this->data['next_step'] = 'verification_mobile';
	    	}	    	  
	    	
	    	/*send email verification added on version 3*/	  	 
	    	   	
	    	$enabled_verification_email = getOptionA('theme_enabled_email_verification');
	    	if ($enabled_verification_email==2 && $reg_email==1){
	    		$email_code=Yii::app()->functions->generateRandomKey(5);
	    		$params['email_verification_code']=$email_code;
	    		$params['status']='pending';
	    		FunctionsV3::sendEmailVerificationCode($params['email_address'],$email_code,$params);
	    		$this->data['next_step'] = 'verification_email';
	    	}
	    	
	    	if($reg_email==1){
	    		$token = SingleAppClass::generateUniqueToken(15,$params['email_address']);
	    	} else $token = SingleAppClass::generateUniqueToken(15,$params['contact_phone']);
	    	
	    	$params['token']=$token;	    		    		    	
	    	    			    		    	    		
    		if(Yii::app()->db->createCommand()->insert("{{client}}",$params)){	
    			$customer_id =Yii::app()->db->getLastInsertID();	  
    			
    			$this->data['client_id'] = $customer_id;
    			$this->data['merchant_id'] = $this->merchant_id;
    			SingleAppClass::registeredDevice($this->data);
    			  		
	    		$this->code=1;
	    		$this->msg = $this->t("Registration successful");
	    		
	    		if ( $enabled_verification_email==2 || $enabled_verification_mobile=="yes"){	
	    			if($this->data['next_step']=="verification_mobile"){
    				   $this->msg=t("We have sent verification code to your mobile number");
	    			} else $this->msg=t("We have sent verification code to your email address");    			
    			} else {    				
    				/*sent welcome email*/	
    				FunctionsV3::sendCustomerWelcomeEmail($params);
    			}	    	
    			
    			$this->details = array(
    			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
    			  'token'=>$token,
    			  'primary_next_step'=>$primary_next_step,
    			  'contact_phone'=>isset($params['contact_phone'])?$params['contact_phone']:''
    			);    			
    			
                /*POINTS PROGRAM*/	    			
	    	    if (FunctionsV3::hasModuleAddon("pointsprogram")){
	    		    PointsProgram::signupReward($customer_id);
	    	    }
    				    		
    		} else $this->msg = $this->t("Something went wrong during processing your request. Please try again later");
			
		} else $this->msg = SingleAppClass::parseValidatorError($Validator->getError());
		
		$this->output();
	}
	
	public function actionsetDeliveryAddress()
	{	    
	    $lat = isset($this->data['lat'])?$this->data['lat']:'';
	    $lng = isset($this->data['lng'])?$this->data['lng']:'';
	    
	    $merchant_id = $this->merchant_id;
	    if($merchant_id<=0){
	    	$this->msg = $this->t("invalid merchant id");
    		$this->output();
	    }	
	    
	    if(!$cart=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
	    	$this->msg = $this->t("Cart is empty");    	
    		$this->output();
	    }
	    
	    $country_name = Yii::app()->functions->countryCodeToFull(isset($this->data['country_code'])?$this->data['country_code']:'');		
		if(!empty($country_name)){
			$this->data['country']=$country_name;
		}
		
		$complete_address = $this->data['street']." ".$this->data['city']." ".$this->data['state']." ".$this->data['zipcode'];
		$complete_address.=" $country_name";
		
		try {
			
			$min_fees=0;			
			$cart_subtotal = isset($cart['cart_subtotal'])?(float)$cart['cart_subtotal']:0;						
			$resp = CheckoutWrapperTemp::verifyLocation($merchant_id,$lat,$lng,$cart_subtotal);	
			
			$this->code = 1;
			$this->msg = "OK";
			
			$this->details = array(
			  'complete_address'=>$complete_address,			  
			  'min_delivery_order'=>$min_fees,
			  'lat'=>$lat,
			  'lng'=>$lng,
			  'formatted_address'=>$complete_address,
			  'street'=>isset($this->data['street'])?$this->data['street']:'',
			  'city'=>isset($this->data['city'])?$this->data['city']:'',
			  'state'=>isset($this->data['state'])?$this->data['state']:'',
			  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',
			  'country'=>$country_name
			);
			
			$params = array(
			  'street'=>isset($this->data['street'])?$this->data['street']:'',
			  'city'=>isset($this->data['city'])?$this->data['city']:'',
			  'state'=>isset($this->data['state'])?$this->data['state']:'',
			  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',
			  'delivery_instruction'=>isset($this->data['delivery_instruction'])?$this->data['delivery_instruction']:'',
			  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',
			  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',
			  'country_code'=>isset($this->data['country_code'])?$this->data['country_code']:'',
			  'delivery_lat'=>$lat,
			  'delivery_long'=>$lng,
			  'save_address'=>isset($this->data['save_address'])?(integer)$this->data['save_address']:0,
			  'delivery_fee'=>(float)$resp['delivery_fee'],
			  'min_delivery_order'=>(float)$resp['min_order'],
			  'distance'=>$resp['distance'],
			  'distance_unit'=>$resp['unit'],
			);						
			Yii::app()->db->createCommand()->update("{{singleapp_cart}}",$params,
      	     'cart_id=:cart_id',
      	     array(
      	      ':cart_id'=>$cart['cart_id']
      	     )
      	   );		
      	         	         	         	   
      	   $token = isset($this->data['token'])?$this->data['token']:'';
      	   if($res_client = SingleAppClass::getCustomerByToken($token)){
      	   	
      	   	    $client_id = $res_client['client_id'];	
      	   	    
				if(isset($this->data['save_address'])){
				  if($this->data['save_address']==1){
				  	 if(!empty($this->data['street']) && !empty($this->data['city'])){				  	 	
				  	 	if (!SingleAppClass::getBookAddressByClientID($client_id,$this->data['street'],$this->data['city'],
				  	 	$this->data['state'])){				  	 		
				  	 		Yii::app()->db->createCommand("UPDATE {{address_book}} SET as_default='1' ")->query();
				  	 		$params_address_book = array(
							  'client_id'=>$client_id,
							  'street'=>isset($this->data['street'])?$this->data['street']:'',
							  'city'=>isset($this->data['city'])?$this->data['city']:'',
							  'state'=>isset($this->data['state'])?$this->data['state']:'',
							  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',
							  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',
							  'country_code'=>isset($this->data['country_code'])?$this->data['country_code']:'',
							  'as_default'=>2,
							  'date_created'=>FunctionsV3::dateNow(),
							  'latitude'=>isset($this->data['lat'])?$this->data['lat']:'',
							  'longitude'=>isset($this->data['lng'])?$this->data['lng']:'',
							  'ip_address'=>$_SERVER['REMOTE_ADDR']
							);													
							Yii::app()->db->createCommand()->insert("{{address_book}}",$params_address_book);
				  	 	}
				  	 }			  
				  }			
				}		
			}

			/*SAVE LOCATION*/					
			$params_recent = array(
			  'device_uiid'=>$this->device_uiid,
			  'search_address'=>isset($this->data['search_address2'])?trim($this->data['search_address2']):'',
			  'street'=>isset($this->data['street'])?trim($this->data['street']):'',
			  'city'=>isset($this->data['city'])?trim($this->data['city']):'',
			  'state'=>isset($this->data['state'])?trim($this->data['state']):'',
			  'country'=>isset($this->data['country'])?trim($this->data['country']):'',
			  'location_name'=>isset($this->data['location_name'])?trim($this->data['location_name']):'',
			  'zipcode'=>isset($this->data['zipcode'])?trim($this->data['zipcode']):'',
			  'latitude'=>$lat,
			  'longitude'=>$lng,
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
			);							
			if(!empty($params_recent['search_address'])){
				if($res = SingleAppClass::getRecentLocationByID($this->device_uiid,$lat, $lng)){					
					$id = $res['id'];					
					Yii::app()->db->createCommand()->update("{{singleapp_recent_location}}",$params_recent,
			  	    'id=:id',
				  	    array(
				  	      ':id'=>$id
				  	    )
			  	    );
				} else {									
					Yii::app()->db->createCommand()->insert("{{singleapp_recent_location}}",$params_recent);
				}		
			}
			
		} catch (Exception $e) {
		   $this->msg = $e->getMessage();					    
        }
        	    
		$this->output();
	}
	
	public function actionsetAddressBook()
	{		
	    $merchant_id = $this->merchant_id;
	    if($merchant_id<=0){
	    	$this->msg = $this->t("invalid merchant id");
    		$this->output();
	    }		
	    
	    if(!$cart=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
	    	$this->msg = $this->t("Cart is empty");    	
    		$this->output();
	    } 
	        
	    $min_fees = 0;
	    $addressbook_id  = isset($this->data['addressbook_id'])?(integer)$this->data['addressbook_id']:0;
	    if($addressbook_id>0){
	    	if ( $res = Yii::app()->functions->getAddressBookByID($addressbook_id)){	    		
	    		try {
	    			$lat = isset($res['latitude'])?$res['latitude']:0;
	    			$lng = isset($res['longitude'])?$res['longitude']:0;	    			
			        $cart_subtotal = isset($cart['cart_subtotal'])?(float)$cart['cart_subtotal']:0;				        
			        $resp = CheckoutWrapperTemp::verifyLocation($merchant_id,$lat,$lng,$cart_subtotal);	
			        
			        $country_code = isset($res['country_code'])?$res['country_code']:'';
			        $country_name = Yii::app()->functions->countryCodeToFull($country_code);		
					
			        $complete_address = $res['street']." ".$res['city']." ".$res['state']." ".$res['zipcode'];
					$complete_address.=" $country_name";
			        
			        $min_fees = (float)$resp['min_order'];
			        $params = array(
					  'street'=>isset($res['street'])?$res['street']:'',
					  'city'=>isset($res['city'])?$res['city']:'',
					  'state'=>isset($res['state'])?$res['state']:'',
					  'zipcode'=>isset($res['zipcode'])?$res['zipcode']:'',
					  'country_code'=>isset($res['country_code'])?$res['country_code']:'',
					  'delivery_instruction'=>isset($this->data['delivery_instruction'])?$this->data['delivery_instruction']:'',
					  'location_name'=>isset($res['location_name'])?$res['location_name']:'',
					  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',					  
					  'delivery_lat'=>$lat,
					  'delivery_long'=>$lng,
					  'save_address'=>isset($this->data['save_address'])?(integer)$this->data['save_address']:0,
					  'delivery_fee'=>(float)$resp['delivery_fee'],
					  'min_delivery_order'=>(float)$resp['min_order'],
					  'distance'=>$resp['distance'],
					  'distance_unit'=>$resp['unit'],
					);		
										
					Yii::app()->db->createCommand()->update("{{singleapp_cart}}",$params,
		      	     'cart_id=:cart_id',
		      	     array(
		      	      ':cart_id'=>$cart['cart_id']
		      	     )
		      	   );		
		      	   
		      	   $this->code = 1;
					$this->msg = "OK";
					$this->details = array(
					  'complete_address'=>$complete_address,
					  'save_address'=>'',
					  'min_delivery_order'=>$min_fees
					);
			
	    		} catch (Exception $e) {
		            $this->msg = $e->getMessage();					    
                }		        	    		
	    	} else $this->msg = $this->t("Address not available. please try again later");
	    } else $this->msg = $this->t("Invalid id");	
	    
		$this->output();
	}
	
	public function actionverifyCustomerToken()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!empty($token)){			 
			 if($res=SingleAppClass::getCustomerByToken($token)){
			 	$data = array(
			 	  'contact_phone'=>$res['contact_phone'],
			 	  'email_address'=>$res['email_address'],
			 	);
			 	$this->code = 1;
			 	$this->msg = "OK";
			 	$this->details = array(
			 	  'data'=>$data
			 	);
			 } else {
			 	$this->code = 3;
			 	$this->msg = $this->t("token not found");			 	
			 }
		} else $this->msg = $this->t("token empty");
		$this->output();
	}
	
	public function actionloadPaymentList()
	{
		
		/*CHECK IF ORDERING IS DISABLED*/
		$disabled_website_ordering = getOptionA('disabled_website_ordering');		
		if($disabled_website_ordering=="yes"){
			$this->msg = $this->t("Ordering is disabled by admin");
			$this->output();
		}
		$merchant_disabled_ordering = getOption($this->merchant_id,'merchant_disabled_ordering');
		if($merchant_disabled_ordering=="yes"){
			$this->msg = $this->t("Ordering is disabled by merchant");
			$this->output();
		}
			
		$merchant_opt_contact_delivery= getOption($this->merchant_id,'merchant_opt_contact_delivery');
		$opt_contact_delivery = isset($this->data['opt_contact_delivery'])?$this->data['opt_contact_delivery']:'';
		
		if ( $res = FunctionsV3::getMerchantPaymentListNew($this->merchant_id)){
			 $transaction_type = isset($this->data['transaction_type'])?$this->data['transaction_type']:'';
			 $this->code = 1;
			 $this->msg = "OK";		
			 $list = array();
			 
			 if(isset($res['mcd'])){
			    unset($res['mcd']);
			 }
			 if(isset($res['pyp'])){
			    unset($res['pyp']);
			 }
			 
			 /*REMOVE OFFLINE PAYMENT OPTION CONTACT DELIVERY*/
			 if($merchant_opt_contact_delivery==1 && $transaction_type=="delivery" && $opt_contact_delivery==1){
			 	if(isset($res['cod'])){unset($res['cod']);}
			 	if(isset($res['pyr'])){unset($res['pyr']);}
			 	if(isset($res['obd'])){unset($res['obd']);}
			 	if(isset($res['ocr'])){unset($res['ocr']);}
			 }		
			 
			 foreach ($res as $key => $val) {
			 	switch ($key) {
			 		case "cod":
			 			if ( $transaction_type=="pickup"){
			 			   $val= $this->t("Pay On Pickup");
				 		} elseif ( $transaction_type=="dinein"){
				 			$val= $this->t("Pay in person");
				 		} else $val = t($val);
			 			break;
			 	
			 		case "pyr":
			 			if ($transaction_type=="pickup"){
			 				$val = $this->t("Pay On Pickup Using Cards");
			 			} else $val = t($val);
			 			break;
			 						 	
			 		case "paypal_v2":	
			 		   if ( $resp = PaypalWrapper::getCredentials($this->merchant_id)){
			 		   	   if ($resp['card_fee']>0.0001){
			 		   	   	  $val = Yii::t("singleapp","Paypal V2 (card fee [card_fee])",array(
			 		   	   	    '[card_fee]'=>FunctionsV3::prettyPrice($resp['card_fee'])
			 		   	   	  ));
			 		   	   } else $val = t($val);
			 		   }
			 		   break;	   
			 		  
			 		case "stp":	
			 		   if ( $resp = StripeWrapper::getCredentials($this->merchant_id)){
			 		   	   if ($resp['card_fee']>0.0001){
			 		   	   	  $cardfee = FunctionsV3::prettyPrice($resp['card_fee']);
			 		   	   	  if(isset($resp['card_percentage'])){
			 		   	   	  	 $cardfee = FunctionsV3::prettyPriceNoCurrency($resp['card_percentage'])."%";
			 		   	   	  	 $cardfee.= "+".FunctionsV3::prettyPriceNoCurrency($resp['card_fee']);
			 		   	   	  }			 		   	   
			 		   	   	  $val = Yii::t("singleapp","Stripe (card fee [card_fee])",array(
			 		   	   	    '[card_fee]'=>$cardfee
			 		   	   	  ));
			 		   	   } else $val = t($val);
			 		   }
			 		   break;    
			 		   
			 		case "mercadopago":	
			 		   if ( $resp = mercadopagoWrapper::getCredentials($this->merchant_id)){
			 		   	   if ($resp['card_fee']>0.0001){
			 		   	   	  $val = Yii::t("singleapp","Mercadopago (card fee [card_fee])",array(
			 		   	   	    '[card_fee]'=>FunctionsV3::prettyPrice($resp['card_fee'])
			 		   	   	  ));
			 		   	   } else $val = t($val);
			 		   }
			 		   break;    
			 		   
			 		case "paytrail":	
			 		   if ( $resp = PaytrailWrapper::getCredentials($this->merchant_id)){
			 		   	   if ($resp['card_fee']>0.0001){
			 		   	   	  $val = Yii::t("singleapp","Paytrail (card fee [card_fee])",array(
			 		   	   	    '[card_fee]'=>FunctionsV3::prettyPrice($resp['card_fee'])
			 		   	   	  ));
			 		   	   } else $val = t($val);
			 		   }
			 		   break;       
			 		   
			 		case "mollie":	
			 		   if ( $resp = MollieWrapper::getCredentials($this->merchant_id)){
			 		   	   if ($resp['card_fee']>0.0001){
			 		   	   	  $val = Yii::t("singleapp","mollie (card fee [card_fee])",array(
			 		   	   	    '[card_fee]'=>FunctionsV3::prettyPrice($resp['card_fee'])
			 		   	   	  ));
			 		   	   } else $val = t($val);
			 		   }
			 		   break;      
			 		   			 		
			 		default:
			 			$val = t($val);
			 			break;
			 	}			 	
			 	$list[] = array(
		 		  'payment_code'=>$key,
		 		  'payment_name'=>$val
		 		);
			 }
			 $this->details = array(
			   'data'=>$list
			 );
		} else $this->msg = $this->t("No payment option available");
		$this->output();
	}
	
	public function actionpayNow()
	{
		$db=new DbExt();
		
		$this->setMerchantTimezone();
		
		if (!yii::app()->functions->validateSellLimit($this->merchant_id) ){
        	$this->msg =t("This merchant has reach the maximum sells per month");
        	$this->output();
        }
    	
    	$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$customer_first_name=''; $customer_last_name =''; $customer_email='';    	
    	
    	$client_id = $client_info['client_id'];    	
    	$email_address = trim($client_info['email_address']);
    	$customer_email = trim($client_info['email_address']);
    	$customer_first_name = isset($client_info['first_name'])?$client_info['first_name']:'';
    	$customer_last_name = isset($client_info['last_name'])?$client_info['last_name']:'';
    	
    	if ( FunctionsK::emailBlockedCheck($email_address)){
    		$this->msg = $this->t("Sorry but your email address is blocked by website admin"); 
    		$this->output();
    	}    	
    	
    	/*CHECK CUSTOMER CAN ORDER*/
    	try {	    	    		    	
	    	CheckoutWrapperTemp::verifyCanPlaceOrder($client_id);	    	    	
	    } catch (Exception $e) {
			 $this->msg = $e->getMessage();
			 $this->output();
		}
    	
    	$transaction_type = isset($this->data['transaction_type'])?$this->data['transaction_type']:'';
    	$delivery_date = isset($this->data['delivery_date'])?$this->data['delivery_date']:'';
    	$delivery_time = isset($this->data['delivery_time'])?$this->data['delivery_time']:'';
    	$payment_provider = isset($this->data['payment_provider'])?$this->data['payment_provider']:'';
    	
    	if(empty($delivery_date)){
    		$this->msg = $this->t("Delivery date is required");
    		$this->output();
    	}
    	
    	if(empty($payment_provider)){
    		$this->msg = $this->t("Payment provider is empty. please go back and try again");
    		$this->output();
    	}
	    
    	$full_delivery = "$delivery_date $delivery_time";    	
    	$delivery_day = strtolower(date("D",strtotime($full_delivery)));
    	    
    	$delivery_time_formated = '';
    	if(!empty($delivery_time)){
    		$delivery_time_formated=date('h:i A',strtotime($delivery_time));
    	} else $delivery_time_formated = date('h:i A');
    	    	    	
    	/*CHECK MERCHANT OPENING HOURS*/
    	//dump($delivery_day);dump($delivery_time_formated);    	
    	if ( !Yii::app()->functions->isMerchantOpenTimes($this->merchant_id,$delivery_day,$delivery_time_formated)){
    		$date_close=date("F,d l Y h:ia",strtotime($full_delivery));
    		$this->msg = Yii::t("singleapp","Sorry but we are closed on [date_close]. Please check merchant opening hours.",array(
    		  '[date_close]'=>$date_close
    		));
    		$this->output();
    	}    	    	
    	
    	/*CHECK IF DATE IS HOLIDAY*/
    	if ( $res_holiday =  Yii::app()->functions->getMerchantHoliday($this->merchant_id)){
    		if (in_array($delivery_date,$res_holiday)){
    		   $this->msg=Yii::t("singleapp","were close on [date]",array(
			   	  	   '[date]'=>FunctionsV3::prettyDate($delivery_date)
			   	));
			   	
			   	$close_msg=getOption($this->merchant_id,'merchant_close_msg_holiday');
			   	if(!empty($close_msg)){
	   	  	 	  $this->msg = Yii::t("default",$close_msg,array(
	   	  	 	   '[date]'=>FunctionsV3::prettyDate($delivery_date)
	   	  	 	  ));
	   	  	    }	
    			$this->output();	
    		}
    	}
    	
    	/*CHECK PRE ORDER*/
    	$date_today = date("Y-m-d");    	
    	if($date_today!=$delivery_date){
    		$merchant_preorder = getOption($this->merchant_id,'merchant_preorder');    		
    		if($merchant_preorder!=1){
    			$this->msg = Yii::t("singleapp","Merchant is not accepting pre-order");
	    		$this->output();
    		}    	
    	}	
    	/*END PRE ORDER*/
    	    	
    	$delivery_date = isset($this->data['delivery_date'])?$this->data['delivery_date']:'';
    	$delivery_time = isset($this->data['delivery_time'])?$this->data['delivery_time']:'';
    	
    	/*CHECK DELIVERY TIME PAST*/
    	if(!empty($delivery_date) && !empty($delivery_time)){    		    		    		
    		$time_1=date('Y-m-d g:i:s a');
    		$time_2="$delivery_date $delivery_time";
    		$time_2=date("Y-m-d g:i:s a",strtotime($time_2));
    		$time_diff=Yii::app()->functions->dateDifference($time_2,$time_1);       		
    		if (is_array($time_diff) && count($time_diff)>=1){
    			if ( $time_diff['hours']>0){	       	  	     	
	       	  	     $this->msg= SingleAppClass::timePastByTransaction($transaction_type);
	       	  	     $this->output(); 	  	     	
       	  	     }	       	  	
       	  	     if ( $time_diff['minutes']>0){	       	  	     	
	       	  	     $this->msg= SingleAppClass::timePastByTransaction($transaction_type);
	       	  	     $this->output();  	  	     	
       	  	     }	       	  	
    		}
    	}        	    	
    	   
    	    	
    	if($res=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
    		$cart=json_decode($res['cart'],true);			
    		
    		$card_fee = 0; $card_percentage=0;
    		
    		//dump($payment_provider);
    		
    		/*CARD FEE*/
    		switch ($payment_provider) {
    			case "pyp":
    				if (FunctionsV3::isMerchantPaymentToUseAdmin($this->merchant_id)){
    					$card_fee=getOptionA('admin_paypal_fee');
    				} else {    					
    					$card_fee = getOption($this->merchant_id,'merchant_paypal_fee');
    				}	    	
    				break;
    				
    			case "paypal_v2":	
    			    if ( $credentials = PaypalWrapper::getCredentials($this->merchant_id)){
    			    	if ($credentials['card_fee']>0.0001){
    			    		$card_fee = $credentials['card_fee'];
    			    	}
    			    }
    			   break;
    			   
    			case "stp":	
    			    if ( $credentials = StripeWrapper::getCredentials($this->merchant_id)){
    			    	if ($credentials['card_fee']>0.0001){
    			    		$card_fee = $credentials['card_fee'];
    			    		if(isset($credentials['card_percentage'])){
    			    			$card_percentage=$credentials['card_percentage']>0?$credentials['card_percentage']:0;
    			    		}    			    	
    			    	}
    			    }
    			   break;   
    			   
    			case "mercadopago":   
    			   if ( $credentials = mercadopagoWrapper::getCredentials($this->merchant_id)){
    			    	if ($credentials['card_fee']>0.0001){
    			    		$card_fee = $credentials['card_fee'];
    			    	}
    			    }
    			   break;   
    			   
    			case "paytrail":   
    			   if ( $credentials = PaytrailWrapper::getCredentials($this->merchant_id)){
    			    	if ($credentials['card_fee']>0.0001){
    			    		$card_fee = $credentials['card_fee'];
    			    	}
    			    }
    			   break;      
    			   
    			case "mollie":   
    			   if ( $credentials = MollieWrapper::getCredentials($this->merchant_id)){
    			    	if ($credentials['card_fee']>0.0001){
    			    		$card_fee = $credentials['card_fee'];
    			    	}
    			    }
    			   break;         
    			       			
    			default:
    				break;
    		}
    		    		
			$data = array(
			  'delivery_type'=>$transaction_type,
			  'merchant_id'=>$this->merchant_id,
			  'card_fee'=>$card_fee
			);
			if($card_percentage>0){
			   $data['card_percentage']=$card_percentage;
			}    
			
			$voucher_details = !empty($res['voucher_details'])?json_decode($res['voucher_details'],true):false;	
			if(is_array($voucher_details) && count($voucher_details)>=1){
				$data['voucher_name']=$voucher_details['voucher_name'];
				$data['voucher_amount']=$voucher_details['amount'];
				$data['voucher_type']=$voucher_details['voucher_type'];
			}
			
			if($res['tips']>0.0001){
				$data['cart_tip_percentage']=$res['tips'];
				$data['tip_enabled']=2;
				$data['tip_percent']=$res['tips'];
			}					

			/*POINTS*/
			if($res['points_amount']>0.0001){
				$data['points_amount']=$res['points_amount'];
			}								
			//dump($data);die();
			
			/*DELIVERY FEE*/
			unset($_SESSION['shipping_fee']);
			if($res['delivery_fee']>0.0001){
				$data['delivery_charge']=$res['delivery_fee'];
			}
			
			Yii::app()->functions->displayOrderHTML( $data,$cart );
			$code = Yii::app()->functions->code;
		    $msg  = Yii::app()->functions->msg;
			if ($code==1){
				$raw = Yii::app()->functions->details['raw'];
				
				/*EURO TAX*/
			   $is_apply_tax = 0;
			   if(EuroTax::isApplyTax($this->merchant_id)){
			   	   $new_total = EuroTax::computeWithTax($raw, $this->merchant_id);
			   	   $raw['total']=$new_total;			
			   	   $is_apply_tax=1;   	   
			   }
			   /*EURO TAX*/				
				
				$donot_apply_tax_delivery = getOption($this->merchant_id,'merchant_tax_charges');
				if(empty($donot_apply_tax_delivery)){
					$donot_apply_tax_delivery=1;
				}
				
				if($card_percentage>0){
					$card_fee = (float) $raw['total']['card_fee'];
				}
				
				$params = array(
				  'merchant_id'=>$this->merchant_id,				  
				  'client_id'=>$client_id,
				  'json_details'=>$res['cart'],
				  'trans_type'=>$transaction_type,
				  'payment_type'=>$this->data['payment_provider'],
				  'sub_total'=>$raw['total']['subtotal'],
				  'tax'=>$raw['total']['tax'],
				  'taxable_total'=>$raw['total']['taxable_total'],
				  'total_w_tax'=>isset($raw['total']['total'])?$raw['total']['total']:0,
				  'delivery_charge'=>isset($raw['total']['delivery_charges'])?$raw['total']['delivery_charges']:0,
				  'delivery_date'=>$delivery_date,
				  'delivery_time'=>$delivery_time,
				  'delivery_asap'=>isset($this->data['delivery_asap'])?$this->data['delivery_asap']:'',
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				  'delivery_instruction'=>isset($res['delivery_instruction'])?$res['delivery_instruction']:'',
				  'cc_id'=>isset($this->data['cc_id'])?$this->data['cc_id']:'',
				  'order_change'=>isset($this->data['order_change'])?$this->data['order_change']:0,
				  'payment_provider_name'=>'',
				  'card_fee'=>$card_fee,
				  'packaging'=>$raw['total']['merchant_packaging_charge'],
				  'donot_apply_tax_delivery'=>$donot_apply_tax_delivery,
				  'order_id_token'=>FunctionsV3::generateOrderToken(),
				  'request_from'=>"single_mob",
				  'apply_food_tax'=>$is_apply_tax,				  
				  //'calculation_method'=>FunctionsV3::getReceiptCalculationMethod()
				);
				
				$order_id_token = $params['order_id_token'];
				
				/*TIPS*/
				if(isset($raw['total']['tips'])){
					if($raw['total']['tips']>0.0001){
						$params['cart_tip_percentage']= $raw['total']['cart_tip_percentage'];
						$params['cart_tip_value']= $raw['total']['tips'];
					}				
				}			
								
				switch ($transaction_type) {
					case "dinein":
						$params['dinein_number_of_guest'] = isset($this->data['dinein_number_of_guest'])?$this->data['dinein_number_of_guest']:'';
						$params['dinein_special_instruction'] = isset($this->data['dinein_special_instruction'])?$this->data['dinein_special_instruction']:'';
						
						$params['dinein_table_number'] = isset($this->data['dinein_table_number'])?$this->data['dinein_table_number']:'';
						
						if(isset($this->data['contact_phone'])){
							if(!empty($this->data['contact_phone'])){
								$db->updateData("{{client}}",array(
								  'contact_phone'=>$this->data['contact_phone']
								),'client_id',$client_id);
							}
						}						
						break;
						
					case "pickup":	 
					      if(isset($this->data['contact_phone'])){
							if(!empty($this->data['contact_phone'])){
								$db->updateData("{{client}}",array(
								  'contact_phone'=>$this->data['contact_phone']
								),'client_id',$client_id);
							}
						  }						
					    break;
						
					case "delivery":
						$delivery_asap = '';
						if(isset($this->data['delivery_asap'])){
							$delivery_asap = $this->data['delivery_asap']=="true"?1:'';
							$params['delivery_asap'] = $delivery_asap;
						}
						break;
				
					default:
						break;
				}
					
				/*DEFAULT ORDER STATUS*/				
				$default_order_status=getOption($this->merchant_id,'default_order_status');										
				switch ($payment_provider) {								
					case "cod":
					case "obd":
						$params['status'] =!empty($default_order_status)?$default_order_status:'pending';
						break;
					case "ccr":
					case "ocr":
						 $params['cc_id'] = isset($this->data['cc_id'])?$this->data['cc_id']:'';	
						 $params['status']= !empty($default_order_status)?$default_order_status:'pending';
						 break;
								
					case "pyr":	 		 
					     $params['payment_provider_name'] = isset($this->data['selected_card'])?$this->data['selected_card']:'';	
						 $params['status']= !empty($default_order_status)?$default_order_status:'pending';
						 break;
						 
					default:			
					    $params['status']=initialStatus();
						break;
				}
							
				/*PROMO*/	    				
				//dump($raw);
				if (isset($raw['total']['discounted_amount'])){
    				if ($raw['total']['discounted_amount']>=0.0001){	    					
    				    $params['discounted_amount']=$raw['total']['discounted_amount'];
    				    $params['discount_percentage']=$raw['total']['merchant_discount_amount'];
    				}
				}
				
				/*VOUCHER*/
				if(!empty($res['voucher_details'])){
					$voucher_details = !empty($res['voucher_details'])?json_decode($res['voucher_details'],true):false;	
					if(is_array($voucher_details) && count($voucher_details)>=1){
						$params['voucher_amount']=$voucher_details['amount'];
			         	$params['voucher_code']=$voucher_details['voucher_name'];
			         	$params['voucher_type']=$voucher_details['voucher_type'];
					}
				}
				
				/*POINTS*/
				if($res['points_amount']>0.0001){
					$params['points_discount']=$res['points_amount'];
				}			
				
				/*SET COMMISSION*/
				if ( Yii::app()->functions->isMerchantCommission($this->merchant_id)){
					$admin_commision_ontop=Yii::app()->functions->getOptionAdmin('admin_commision_ontop');
					if ( $com=Yii::app()->functions->getMerchantCommission($this->merchant_id)){
	            		$params['percent_commision']=$com;			            		
	            		$params['total_commission']=($com/100)*$params['total_w_tax'];
	            		$params['merchant_earnings']=$params['total_w_tax']-$params['total_commission'];
	            		if ( $admin_commision_ontop==1){
	            			$params['total_commission']=($com/100)*$params['sub_total'];
	            			$params['commision_ontop']=$admin_commision_ontop;			            		
	            			$params['merchant_earnings']=$params['sub_total']-$params['total_commission'];
	            		}
	            	}	
	            	
	            	/** check if merchant commission is fixed  */
			        $merchant_com_details=Yii::app()->functions->getMerchantCommissionDetails($this->merchant_id);	
			        if ( $merchant_com_details['commision_type']=="fixed"){
	            		$params['percent_commision']=$merchant_com_details['percent_commision'];
	            		$params['total_commission']=$merchant_com_details['percent_commision'];
	            		$params['merchant_earnings']=$params['total_w_tax']-$merchant_com_details['percent_commision'];
	            		$params['commision_type']='fixed';
	            		
	            		if ( $admin_commision_ontop==1){			            		
	            		    $params['merchant_earnings']=$params['sub_total']-$merchant_com_details['percent_commision'];
	            		}
	            	} 
				}
				/*END COMMISSION*/

				if(!is_numeric($params['cc_id'])){
					unset($params['cc_id']);
				}
				if(!is_numeric($params['order_change'])){
					unset($params['order_change']);
				}
												
				/*BEGIN INSERT ORDER*/				
				if(!is_numeric($params['sub_total'])){
					$params['sub_total']=0;
				}			
				if(!is_numeric($params['tax'])){
					$params['tax']=0;
				}			
				if(!is_numeric($params['taxable_total'])){
					$params['taxable_total']=0;
				}			
				if(!is_numeric($params['total_w_tax'])){
					$params['total_w_tax']=0;
				}
				
				if(isset($params['order_change'])){
					if(!is_numeric($params['order_change'])){
						$params['order_change']=0;
					}			
				}
				if(!is_numeric($params['card_fee'])){
					$params['card_fee']=0;
				}			
				if(!is_numeric($params['packaging'])){
					$params['packaging']=0;
				}			
				if(!is_numeric($params['donot_apply_tax_delivery'])){
					unset($params['donot_apply_tax_delivery']);
				}			
				if(!is_numeric($params['apply_food_tax'])){
					unset($params['apply_food_tax']);
				}			
				
				if(isset($params['percent_commision'])){
					if(!is_numeric($params['percent_commision'])){
						$params['percent_commision']=0;
					}			
				}
				
				if(isset($params['total_commission'])){
					if(!is_numeric($params['total_commission'])){
						$params['total_commission']=0;
					}			
				}
				
				if(isset($params['merchant_earnings'])){
					if(!is_numeric($params['merchant_earnings'])){
						$params['merchant_earnings']=0;
					}			
				}				
				
				if($transaction_type=="delivery"){			
				   if($res['distance']>0){
				      $params['distance'] = st("[distance] [unit]",array(
				        '[distance]'=>isset($res['distance'])?$res['distance']:0,
				        '[unit]'=>MapsWrapperTemp::prettyUnit( isset($res['distance_unit'])?$res['distance_unit']:'' )
				      ));
				   }
				}
																		
				if( $db->insertData("{{order}}",$params)){
					$order_id=Yii::app()->db->getLastInsertID();
					
					$params_history=array(
    				  'order_id'=>$order_id,
    				  'status'=>initialStatus(),    	
    				  'remarks'=>'',
    				  'date_created'=>FunctionsV3::dateNow(),
    				  'ip_address'=>$_SERVER['REMOTE_ADDR']
    				);	    				
    				$db->insertData("{{order_history}}",$params_history);
					
					$next_step = "receipt";
					
					/*SAVE ITEM */					
					foreach ($raw['item'] as $val) {		    					
						$params_order_details=array(
						  'order_id'=>isset($order_id)?$order_id:'',
						  'client_id'=>$client_id,
						  'item_id'=>isset($val['item_id'])?$val['item_id']:'',
						  'item_name'=>isset($val['item_name'])?$val['item_name']:'',
						  'order_notes'=>isset($val['order_notes'])?$val['order_notes']:'',
						  'normal_price'=>isset($val['normal_price'])?$val['normal_price']:'',
						  'discounted_price'=>isset($val['discounted_price'])?$val['discounted_price']:'',
						  'size'=>isset($val['size_words'])?$val['size_words']:'',
						  'qty'=>isset($val['qty'])?$val['qty']:'',		    					  
						  'addon'=>isset($val['sub_item'])?json_encode($val['sub_item']):'',
						  'cooking_ref'=>isset($val['cooking_ref'])?$val['cooking_ref']:'',
						  'ingredients'=>isset($val['ingredients'])?json_encode($val['ingredients']):'',
						  'non_taxable'=>isset($val['non_taxable'])?$val['non_taxable']:1
						);
						/*inventory*/
						$new_fields=array('size_id'=>"size_id");
                        if ( FunctionsV3::checkTableFields('order_details',$new_fields)){
                        	$params_order_details['size_id'] = isset($val['size_id'])? (integer) $val['size_id']:0;
                        	$params_order_details['cat_id'] = isset($val['category_id'])? (integer) $val['category_id']:0;
                        }			
						$db->insertData("{{order_details}}",$params_order_details);
						
						/*inventory*/
    					if (FunctionsV3::checkIfTableExist('order_details_addon')){
	    					if(isset($val['sub_item'])){
		    					if(is_array($val['sub_item']) && count($val['sub_item'])>=1){
		    						foreach ($val['sub_item'] as $sub_item_data) {
		    							Yii::app()->db->createCommand()->insert("{{order_details_addon}}",array(
		    							  'order_id'=>$order_id,
		    							  'subcat_id'=>$sub_item_data['subcat_id'],
		    							  'sub_item_id'=>$sub_item_data['sub_item_id'],
		    							  'addon_price'=>$sub_item_data['addon_price'],
		    							  'addon_qty'=>$sub_item_data['addon_qty'],
		    							));
		    						}
		    					}		    				
	    					}
    					}
						
					}
					
					/*SAVE DELIVERY ADDRESS*/
					$params_address = array();
					
					/*SAVE DELIVERY ADDRESS*/
					if ($transaction_type=="delivery"){						
						$params_address=array(	    				  
	    				  'street'=>isset($res['street'])?$res['street']:'',
	    				  'city'=>isset($res['city'])?$res['city']:'',
	    				  'state'=>isset($res['state'])?$res['state']:'',
	    				  'zipcode'=>isset($res['zipcode'])?$res['zipcode']:'',
	    				  'location_name'=>isset($res['location_name'])?$res['location_name']:'',
	    				  'contact_phone'=>isset($res['contact_phone'])?$res['contact_phone']:'',
	    				  'country'=>isset($res['country_code'])?$res['country_code']:'',
	    				  'google_lat'=>isset($res['delivery_lat'])?$res['delivery_lat']:'',
	    				  'google_lng'=>isset($res['delivery_long'])?$res['delivery_long']:'',
	    				  'opt_contact_delivery'=>isset($this->data['opt_contact_delivery'])?(integer)$this->data['opt_contact_delivery']:0
	    				);		    					    				
					} elseif ( $transaction_type=="pickup"){
						$params_address = array(						  
	    				  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:''	    				  
						);
					} elseif ( $transaction_type=="dinein"){
						$params_address = array(						  
	    				  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',
	    				  'dinein_number_of_guest'=>isset($this->data['dinein_number_of_guest'])?$this->data['dinein_number_of_guest']:'',
	    				  'dinein_special_instruction'=>isset($this->data['dinein_special_instruction'])?$this->data['dinein_special_instruction']:'',
	    				  'dinein_table_number'=>isset($this->data['dinein_table_number'])?$this->data['dinein_table_number']:''
						);
					}
					
					$params_address['order_id'] = (integer)$order_id;
	    		    $params_address['client_id'] = (integer)$client_id;
					$params_address['first_name'] = $customer_first_name;
					$params_address['last_name'] = $customer_last_name;
					$params_address['contact_email'] = $customer_email;
					$params_address['date_created'] = FunctionsV3::dateNow();
					$params_address['ip_address'] = $_SERVER['REMOTE_ADDR'];
										
					Yii::app()->db->createCommand()->insert("{{order_delivery_address}}",$params_address);
					
					/*SAVE ADDRESS*/										
					if(isset($this->data['save_address'])){			
						if (!SingleAppClass::getBookAddress($res['street'],$res['city'],$res['state'])){							
							$db->qry("UPDATE {{address_book}} SET as_default='1' "); 
							$params_address_book = array(
							  'client_id'=>$client_id,
							  'street'=>$res['street'],
							  'city'=>$res['city'],
							  'state'=>$res['state'],
							  'zipcode'=>$res['zipcode'],
							  'location_name'=>$res['location_name'],
							  'country_code'=>getOptionA('admin_country_set'),
							  'as_default'=>2,
							  'date_created'=>FunctionsV3::dateNow()
							);							
							$db->insertData("{{address_book}}",$params_address_book);
						} 
					}
					
					$this->code = 1;
				    $this->msg = Yii::t("singleapp","Your order has been placed. Reference # [order_id]",array(
				      '[order_id]'=>$order_id
				    ));
					
					$provider_credentials=array();
					$redirect_url='';
					
					/*SAVE POINTS*/
					switch ($payment_provider) {
						case "cod":
						case "ccr":
					    case "ocr":				
					    case "pyr":
					    case "obd":
					    break;
					    
					    default:					    	
					    	SingleAppClass::savePoints(
					    	  $this->device_uiid,
					    	  $client_id,
					    	  $this->merchant_id,
					    	  $order_id,
					    	  'initial_order'
					    	);
					    	break;
					}
					
					
					/*PAYMENT DATA*/
					switch ($payment_provider) {
						case "cod":
						case "ccr":
					    case "ocr":				
					    case "pyr":	    					    
					          SingleAppClass::handleAll($order_id,$this->merchant_id,
					          $client_id,$this->device_uiid,$params['status']);	
					          
					          if (method_exists("SingleAppClass","updatePoints")){
					          	  SingleAppClass::updatePoints($order_id,$params['status']); 
					          }					          
					          
							  break;	
							  
					    case "obd":
					    	  FunctionsV3::sendBankInstructionPurchase(
	    					      $this->merchant_id,
	    					      $order_id,
	    					      isset($params['total_w_tax'])?$params['total_w_tax']:0,
	    					      $client_id
	    					  );
	    					  SingleAppClass::handleAll($order_id,$this->merchant_id,
	    					  $client_id,$this->device_uiid,$params['status']);	
	    					  
	    					  if (method_exists("SingleAppClass","updatePoints")){
	    					      SingleAppClass::updatePoints($order_id,$params['status']);
	    					  }
	    					  				    	  				    	 
					    	  break;
					    	  
					    case "rzr":	  
					       $next_step = "init_".$payment_provider;
					       $provider_credentials = FunctionsV3::razorPaymentCredentials($this->merchant_id);
					       if(!$provider_credentials){
					       	  $this->code = 2;
					          $this->msg = $this->t("Merchant payment credentials not properly set");
					       }
					       break;
					       
					    case "btr":
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/braintree?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					    	break;
					    	
					    case "paypal_v2":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/paypal?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;
					       
					    case "stp":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/stripe?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;   
					       
					    case "mercadopago":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/mercadopago?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;      
					       
					    case "vog":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/voguepay?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;         
					       
					    case "paytrail":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/paytrail?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;            
					       
					    case "mollie":	
					       $next_step='init_webview';
					       $redirect_url = websiteUrl()."/singlemerchant/molliepay?id=".urlencode($order_id);
					       $redirect_url.= "&device_id=".urlencode($this->device_uiid);					       
					       break;               
							  		
					    case "payu":   
					       $next_step='init_webview';					       
					       $redirect_url = websiteUrl()."/".APP_FOLDER."/payu?id=".urlencode($order_id)."&lang=".Yii::app()->language;
					       break; 
					       
					          			    
						default:						
						    $next_step = "init_".$payment_provider;
							break;
					}
					
				    $client_info = array( 
				      'first_name'=>$client_info['first_name'],
				      'last_name'=>$client_info['last_name'],
				      'email_address'=>$client_info['email_address'],
				      'contact_phone'=>$client_info['contact_phone'],				      
				    );
				    
				    $payment_description = Yii::t("singleapp","Payment to merchant [merchant_name]",array(
				      '[merchant_name]'=>clearString($this->merchant_name)
				    ));
				    
				    $total = number_format($params['total_w_tax'],2,'.','');
				    
				    $this->details=array(
				      'order_id'=>$order_id,
				      'total_amount'=>$params['total_w_tax'],
				      'total_amount_by_100'=>$total*100,
				      'total_amount_formatted'=>$total,
				      'payment_provider'=>$payment_provider,
				      'next_step'=>$next_step,
				      'currency_code'=>Yii::app()->functions->adminCurrencyCode(),
				      'payment_description'=>$payment_description,
				      'merchant_name'=>clearString($this->merchant_name),
				      'provider_credentials'=>$provider_credentials,
				      'redirect_url'=>$redirect_url,
				      'client_info'=>$client_info
				    );
				    
				} else $this->msg = $this->t("Something went wrong cannot insert records. please try again later");
				
			} else $this->msg = $msg;			
    	} else $this->msg = $this->t("Cart is empty");    	
    	
		$this->output();
	}
	
	public function actionGetAddressFromCart()
	{
		if($resp=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
			$this->code = 1;
			$this->msg = "OK";
			
			$country_list = require_once('CountryCode.php');
			
			$default_country_code = getOptionA('admin_country_set');
			
			$this->details = array(
			  'street'=>$resp['street'],
			  'city'=>$resp['city'],
			  'state'=>$resp['state'],
			  'zipcode'=>$resp['zipcode'],
			  'delivery_instruction'=>$resp['delivery_instruction'],
			  'location_name'=>$resp['location_name'],
			  'contact_phone'=>$resp['contact_phone'],
			  'country_code'=>!empty($resp['country_code'])?$resp['country_code']:$default_country_code,
			  'delivery_lat'=>$resp['delivery_lat'],
			  'delivery_long'=>$resp['delivery_long'],
			  'country_list'=>$country_list
			);
			
		} else $this->msg = "cart not available";
		$this->output();
	}
	
	public function actiongetUserProfile()
	{		
		$token = isset($this->data['token'])?$this->data['token']:'';
		$device_uiid = isset($this->data['device_uiid'])?$this->data['device_uiid']:'';
		
		if($res = SingleAppClass::getCustomerByTokenAndDevice($token,$device_uiid)){											
			
			$has_pts = '';
			$client_id = (integer) $res['client_id'];			
			$avatar =  SingleAppClass::getAvatar2($res['avatar']);		
			
			if($res['single_app_merchant_id']<=0){
				Yii::app()->db->createCommand()->update("{{client}}",array(
				  'single_app_merchant_id'=>$this->merchant_id,
				  'date_modified'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR']
				),
		  	    'client_id=:client_id',
			  	    array(
			  	      ':client_id'=>$client_id
			  	    )
		  	    );
			}		
											
			$data = array(
			   'first_name'=>$res['first_name'],
			   'last_name'=>$res['last_name'],
			   'full_name'=>ucwords($res['first_name']." ".$res['last_name']),
			   'email_address'=>$res['email_address'],
			   'contact_phone'=>$res['contact_phone'],
			   'enabled_push'=>$res['push_enabled']>0?$res['push_enabled']:0,
			   'stic_dark_theme'=>$res['stic_dark_theme']>0?$res['stic_dark_theme']:0,
			   'has_pts'=>$has_pts,
			   'avatar'=>$avatar,
			   'social_strategy'=>$res['social_strategy'],
			   'subscribe_topic'=>$res['subscribe_topic']>0?$res['subscribe_topic']:0
			);
			
			$this->code = 1;
			$this->msg = "ok";
			$this->details = array(
			 'data'=>$data
			);
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");
		}
		$this->output();
	}
	
	public function actionsaveChangePassword()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if ($this->data['password']==$this->data['cpassword']){
			if($res = SingleAppClass::getCustomerByToken($token)){		
				$client_id = $res['client_id'];
				$current_pass = md5($this->data['current_password']);				
				$new_password = md5($this->data['password']);
				if ( $current_pass == $res['password']){					
					if ( $current_pass == $new_password){
					   $this->msg = $this->t("New password cannot be the same as old password");
					} else {
					   $params = array(
						  'password'=>md5($this->data['password']),
						  'date_modified'=>FunctionsV3::dateNow(),
						  'ip_address'=>$_SERVER['REMOTE_ADDR']
						);				
						$db = new DbExt();
						if ( $db->updateData("{{client}}",$params,'client_id',$client_id)){
							 $this->code = 1;
							 $this->msg = $this->t("Password updated");
							 $this->details='';
						} else $this->msg = $this->t("Cannot update records, please try again later");	
					}										
				} else $this->msg = $this->t("Current password is not valid");
			} else {
				$this->code = 3;
				$this->msg = $this->t("token not found");
			}
		} else $this->msg = $this->t("Confirm password does not match");
		$this->output();
	}
	
	public function actionsaveProfile()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];
			$params = array(
			  'first_name'=>$this->data['first_name'],
			  'last_name'=>$this->data['last_name'],
			  'contact_phone'=>$this->data['contact_phone'],
			  'date_modified'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);
			$db = new DbExt();
			if ( $db->updateData("{{client}}",$params,'client_id',$client_id)){
				 $this->code = 1;
				 $this->msg = $this->t("Profile updated");
				 
				 $avatar = SingleAppClass::getAvatar($client_id);
				 
				 $this->details=array(
				   'full_name'=>ucwords($res['first_name']." ".$res['last_name']),
				   'avatar'=>$avatar
				 );
			} else $this->msg = $this->t("Cannot update records, please try again later");	
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");
		}
		$this->output();
	}
	
	public function actionsavePushSettings()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];		
			$params = array(
			  'push_enabled'=>isset($this->data['enabled_push'])?(integer)$this->data['enabled_push']:0,
			  'date_modified'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);		
			
			$up =Yii::app()->db->createCommand()->update("{{singleapp_device_reg}}",$params,
	  	    'device_uiid=:device_uiid AND client_id=:client_id',
		  	    array(
		  	      ':device_uiid'=>$this->device_uiid,
		  	      ':client_id'=>$client_id
		  	    )
	  	    );
	  	    if($up){
		  	    $this->code = 1;
				$this->msg = $this->t("Push settings updated");
				$this->details=array(
				  'enabled_push'=>$params['push_enabled']
				);
	  	    } else $this->msg = $this->t("Cannot update records, please try again later"); 
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");		
		}
		$this->output();
	}

	public function actionsaveDarkMode()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];		
			$params = array(
			  'stic_dark_theme'=>isset($this->data['stic_dark_theme'])?(integer)$this->data['stic_dark_theme']:0,
			  'date_modified'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);		
    		$up =Yii::app()->db->createCommand()->update("{{singleapp_device_reg}}",$params,
      	    'device_uiid=:device_uiid AND client_id=:client_id',
    	  	    array(
    	  	      ':device_uiid'=>$this->device_uiid,
    	  	      ':client_id'=>$client_id
    	  	    )
      	    );
	  	    if($up){
		  	    $this->code = 1;
				$this->msg = $this->t("Changed successfully");
				$this->details=array(
				  'stic_dark_theme'=>$params['stic_dark_theme']
				);
	  	    } else $this->msg = $this->t("Cannot update records, please try again later"); 
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");		
		}
		$this->output();
	}
	
	public function actionlogin()
	{
		$username = isset($this->data['username'])?$this->data['username']:'';
		$password = isset($this->data['password'])?$this->data['password']:'';
		if ($res=SingleAppClass::appLogin($username,$password)){						
			
			if ( FunctionsK::emailBlockedCheck($res['email_address'])){
    		   $this->msg = $this->t("sorry but your email address is blocked by website admin");
    		   $this->output();
    	    }	  			
    	    
    	    if ( FunctionsK::mobileBlockedCheck($res['contact_phone'])){
    		   $this->msg = $this->t("Sorry but your mobile number is blocked by website admin");
    		   $this->output();
    	    }	  	
			
			$client_id = $res['client_id'];
			
			$token = SingleAppClass::generateUniqueToken(15,$res['email_address']);
			$params = array(			
			  'token'=>$token,
			  'last_login'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']			  
			);
			
			if(!empty($res['token'])){
				unset($params['token']);
				$token = $res['token'];
			}
					
			$up = Yii::app()->db->createCommand()->update("{{client}}",$params,
	  	    'client_id=:client_id',
		  	    array(
		  	      ':client_id'=>(integer)$client_id
		  	    )
	  	    );  	    			
			if($up){
				$this->data['client_id'] = $client_id;
				$this->data['merchant_id'] = $this->merchant_id;
				SingleAppClass::registeredDevice($this->data);
				$this->code = 1;
				$this->msg = $this->t("Login successful");
				$this->details = array(
				  'token'=>$token,
				  'mobile_number'=>$res['contact_phone']
				);
			} else $this->msg = $this->t("Something went wrong cannot update records. please try again later");
		} else $this->msg = $this->t("Login failed either username or password is not valid");
		$this->output();
	}
	
	public function actiongetCreditCards()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];
			
			$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
	
	        $paginate_total=0; 
	        $limit="LIMIT $page,$pagelimit"; 
	        
			$db=new DbExt;
	    	$stmt="
	    	SELECT 
	    	cc_id,
	    	credit_card_number,
	    	date_created
	    	FROM {{client_cc}}
	    	WHERE
	    	client_id = ".FunctionsV3::q($client_id)."
	    	ORDER BY cc_id DESC
	    	$limit
	    	";
	    	
	    	if(isset($_GET['debug'])){
			   dump($stmt);
		    }
				    	
			if($res = $db->rst($stmt)){
				 $data =array();
				 foreach ($res as $val) {
				 	$date_added = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
	    			$date_added = st("Added [date]",array(
	    			  '[date]'=>$date_added
	    			));
				 	$data[]= array(
				 	  'id'=>$val['cc_id'],
				 	  'card'=>Yii::app()->functions->maskCardnumber($val['credit_card_number']),
				 	  'date_added'=>$date_added
				 	);
				 }			
				 $this->code = 1;
				 $this->msg = "OK";
				 $this->details = array(
				    'page_action'=>$page_action,
				   'data'=>$data
				 );
				 $this->output();
			} else {
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {
					$this->code = 6;
					$this->details = array(
					  'title'=>st("Your credit card list is empty"),
					  'sub_title'=>st("Add your first credit card")
					);	
				}
			}		
        } else {
        	$this->code = 3;
        	$this->msg = $this->t("token not found");
        }
		$this->output();
	}
	
	public function actionloadCardAttributes()
	{
		$this->code = 1;
		$this->msg ="OK";
		
		$month = Yii::app()->functions->ccExpirationMonth();
		$html='<ons-select id="expiration_month" class="expiration_month full_width">';
		foreach ($month as $key => $val) {
			$html.='<option value="'.$key.'">'.$val.'</option>';
		}
		$html.='</ons-select>';
		
		$year = Yii::app()->functions->ccExpirationYear();
		$html_y='<ons-select id="expiration_yr" class="expiration_yr full_width">';
		foreach ($year as $key => $val) {
			$html_y.='<option value="'.$key.'">'.$val.'</option>';
		}
		$html_y.='</ons-select>';
		
		$this->details = array(
		  'month'=>$html,
		  'year'=>$html_y
		);
		$this->output();
	}
	
	public function actionsaveCard()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}
		
		if(strlen($this->data['credit_card_number'])!=16){
			$this->msg = $this->t("Invalid credit card length");
			$this->output();
		}
		
		$client_id = $res['client_id'];
		$id = isset($this->data['cc_id'])?$this->data['cc_id']:'';
		
		$p = new CHtmlPurifier();			
		$params = array(
		  'client_id'=>$client_id,
		  'card_name'=>isset($this->data['card_name'])?$p->purify($this->data['card_name']):'',
		  'credit_card_number'=>isset($this->data['credit_card_number'])?$this->data['credit_card_number']:'',
		  'billing_address'=>isset($this->data['billing_address'])?$p->purify($this->data['billing_address']):'',
		  'cvv'=>isset($this->data['cvv'])?$this->data['cvv']:'',
		  'expiration_month'=>isset($this->data['expiration_month'])?$this->data['expiration_month']:'',
		  'expiration_yr'=>isset($this->data['expiration_yr'])?$this->data['expiration_yr']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		
		$params['credit_card_number']=FunctionsV3::maskCardnumber($p->purify($params['credit_card_number']));
				
    	try {        	
    	   $params['encrypted_card']=CreditCardWrapper::encryptCard($p->purify($this->data['credit_card_number']));
    	} catch (Exception $e) {
    		$this->msg =  Yii::t("default","Caught exception: [error]",array(
						    '[error]'=>$e->getMessage()
						  ));
		    $this->output();
    		return ;
    	}
				
		$db = new DbExt();
		if($id>=1){
			unset($params['date_created']);
			unset($params['ip_address']);
			$db->updateData("{{client_cc}}",$params,'cc_id',$id);
			$this->code = 1;
			$this->msg = $this->t("Successfully updated");
		} else {
			if ( !Yii::app()->functions->getCCbyCard($params['credit_card_number'],$client_id) ){
				$db->insertData("{{client_cc}}",$params);
				$this->code = 1;
				$this->msg = $this->t("Successful");
			} else $this->msg = $this->t("Credit card already exits");
		}
		
		$this->output();
	}
	
	public function actiondeleteCard()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];
		
		$id = isset($this->data['id'])?$this->data['id']:'';
		if($id>=1){
			$db = new DbExt();
			$db->qry("
			DELETE FROM {{client_cc}}
			WHERE cc_id = ".FunctionsV3::q($id)."
			");
			$this->code = 1;
			$this->msg="OK";
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiongetCards()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		$id = isset($this->data['id'])?$this->data['id']:'';
		if($id>=1){
			if ($res=Yii::app()->functions->getCreditCardInfo($id)){				
				unset($res['client_id']);
				unset($res['date_created']);unset($res['date_modified']);
				unset($res['ip_address']);
				$this->code = 1;
				$this->msg = "OK";
				
				$decryp_card = isset($res['credit_card_number'])?$res['credit_card_number']:'';
				if(isset($res['encrypted_card'])){
					try {
						$decryp_card = CreditCardWrapper::decryptCard($res['encrypted_card']);
					} catch (Exception $e) {
						$decryp_card = Yii::t("default","Caught exception: [error]",array(
						  '[error]'=>$e->getMessage()
						));
					}
				}
				
				$res['credit_card_number']=$decryp_card;
						
				unset($res['encrypted_card']);
				$this->details = $res;
				
			} else $this->msg = $this->t("Record not found. please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiongetAddressBookDropDown()
	{
		$this->actiongetAddressBookList(false , "ORDER BY as_default DESC");
	}
	
	public function actiongetAddressBookList($with_limit=true, $sort_by='ORDER BY id DESC')
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];				
		if($client_id>0){			
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
		        
	        $limit="LIMIT $page,$pagelimit"; 
	        if(!$with_limit){
	        	$limit='';
	        }
	        
	        $search_resp = SingleAppClass::searchMode();
		    $search_mode = $search_resp['search_mode'];		    
	        
	        $stmt="SELECT      	       
    	       concat(street,' ',city,' ',state,' ',zipcode) as address,
    	       id,
    	       location_name,
    	       country_code,
    	       as_default,
    	       latitude,
    	       longitude,
    	       date_created
    	       FROM
    	       {{address_book}}
    	       WHERE
    	       client_id =".FunctionsV3::q($client_id)."
    	       $sort_by 
    	       $limit  
    	    ";    	 
	        
	        if($search_mode=="location"){
				$stmt="
				SELECT SQL_CALC_FOUND_ROWS 
				a.id,
				a.as_default,			
				a.date_created,
				concat(a.street,' ',d.name,' ',c.name,' ',b.name) as address			
				FROM
				{{address_book_location}} a
	
				LEFT JOIN {{location_states}} b
				ON 
				a.state_id = b.state_id
				
				LEFT JOIN {{location_cities}} c
				ON 
				a.city_id = c.city_id
				
				LEFT JOIN {{location_area}} d
				ON 
				a.area_id = d.area_id
				    
				WHERE a.client_id=".FunctionsV3::q($client_id)."
						
				AND a.street <> ''    	      
				
				ORDER BY a.id DESC
				$limit
				";			
			}	
		
	        if(isset($_GET['debug'])){
	        	dump($stmt);
	        }		
	        	        
	        $db=new DbExt;    
			if ( $res = $db->rst($stmt)){
				
				$data = array();
				foreach ($res as $val) {
	    			$date_added = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
	    			$val['date_added'] = st("Added [date]",array(
	    			  '[date]'=>$date_added
	    			));
	    			
	    			if($search_mode=="location"){
						if($val['as_default']==1){
							$val['as_default']=2;
						}			
					}
	    			
	    			$data[]=$val;
	    		}
    		
				$this->code = 1;
				$this->msg = "OK";								
				$this->details = array(				  
				  'data'=>$data,
				  'page_action'=>$page_action
				);
				$this->output();
			} else {
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {
					$this->code = 6;
					$this->details = array(
					  'title'=>st("Your address book list is empty"),
					  'sub_title'=>st("Add your first address")
					);		
				}
			}		
		} else {
			$this->msg = $this->t("Invalid token, please relogin again");
			$this->code =3;
		}	
				
		$this->output();
	}
	
	public function actionsaveAddressBook()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		
		$Validator=new Validator;
		
		$Validator->required(array(
		  'lat'=>$this->t("latitude is required"),
		  'lng'=>$this->t("longitude id is required")
		),$this->data);
		
		
		if($Validator->validate()){	
			$client_id = $res['client_id'];
			$id = isset($this->data['book_id'])?$this->data['book_id']:'';
			
			$params = array(
			  'client_id'=>$client_id,
			  'street'=>isset($this->data['street'])?$this->data['street']:'',
			  'city'=>isset($this->data['city'])?$this->data['city']:'',
			  'state'=>isset($this->data['state'])?$this->data['state']:'',
			  'zipcode'=>isset($this->data['zipcode'])?$this->data['zipcode']:'',
			  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			  'country_code'=>isset($this->data['country_code'])?$this->data['country_code']:'',
			  'as_default'=>isset($this->data['as_default'])?$this->data['as_default']:1,
			  'latitude'=>isset($this->data['lat'])?$this->data['lat']:'',
			  'longitude'=>isset($this->data['lng'])?$this->data['lng']:'',
			);
								
									
			if($id>=1){
				unset($params['date_created']);
				$params['date_modified']=FunctionsV3::dateNow();
				if(!SingleAppClass::checkAddressBook($client_id,$params['latitude'],$params['longitude'],$id)){					
					$up = Yii::app()->db->createCommand()->update("{{address_book}}",$params,
			  	    'id=:id',
				  	    array(
				  	      ':id'=>$id
				  	    )
			  	    );
					if($up){					
						$this->code = 1;
					    $this->msg = $this->t("Successfully updated");
					} else $this->msg = $this->t("Something went wrong cannot update records. please try again later");
				} else $this->msg = $this->t("Address already exist");
			} else {
				if(!SingleAppClass::checkAddressBook($client_id,$params['latitude'],$params['longitude'])){					
					if(Yii::app()->db->createCommand()->insert("{{address_book}}",$params)){	
						$id = Yii::app()->db->getLastInsertID();
						$this->code = 1;
						$this->msg = $this->t("Successfully added");
						$this->details='';
					} else $this->msg = $this->t("Something went wrong cannot insert records. please try again later");
				} else $this->msg = $this->t("Address already exist");
			}		
			
			if($this->code==1 && $id>0){
				if ( $params['as_default']==2){
					Yii::app()->db->createCommand("UPDATE {{address_book}} SET as_default='1' 
					WHERE client_id=".q($client_id)."
					AND ID NOT IN (".q($id).")
					 ")->query();
				}
			}		
			
			
			/*SAVE LOCATION*/		
			$lat = isset($this->data['lat'])?$this->data['lat']:'';
		    $lng = isset($this->data['lng'])?$this->data['lng']:'';
		
			$country_name = Yii::app()->functions->countryCodeToFull(isset($this->data['country_code'])?$this->data['country_code']:'');		
			if(!empty($country_name)){
				$this->data['country']=$country_name;
			}
								
			$params_recent = array(
			  'device_uiid'=>$this->device_uiid,
			  'search_address'=>isset($this->data['search_address2'])?trim($this->data['search_address2']):'',
			  'street'=>isset($this->data['street'])?trim($this->data['street']):'',
			  'city'=>isset($this->data['city'])?trim($this->data['city']):'',
			  'state'=>isset($this->data['state'])?trim($this->data['state']):'',
			  'country'=>isset($this->data['country'])?trim($this->data['country']):'',
			  'location_name'=>isset($this->data['location_name'])?trim($this->data['location_name']):'',
			  'zipcode'=>isset($this->data['zipcode'])?trim($this->data['zipcode']):'',
			  'latitude'=>$lat,
			  'longitude'=>$lng,
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
			);					
			if(!empty($params_recent['search_address'])){
				if($res = SingleAppClass::getRecentLocationByID($this->device_uiid,$lat, $lng)){					
					$id = $res['id'];					
					Yii::app()->db->createCommand()->update("{{singleapp_recent_location}}",$params_recent,
			  	    'id=:id',
				  	    array(
				  	      ':id'=>$id
				  	    )
			  	    );
				} else {									
					Yii::app()->db->createCommand()->insert("{{singleapp_recent_location}}",$params_recent);
				}		
			}
			
		} else $this->msg  = SingleAppClass::parseValidatorError($Validator->getError());
		
		$this->output();
	}
	
	public function actiondeleteAddressBook()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];
		
		$id = isset($this->data['id'])?$this->data['id']:'';
		if($id>=1){
			$db = new DbExt();
			$search_resp = SingleAppClass::searchMode();
		    $search_mode = $search_resp['search_mode'];
		    if($search_mode=="location"){
		    	$db->qry("
				DELETE FROM {{address_book_location}}
				WHERE id = ".FunctionsV3::q($id)."
				AND client_id=".FunctionsV3::q($client_id)."
				");
		    } else {		
				$db->qry("
				DELETE FROM {{address_book}}
				WHERE id = ".FunctionsV3::q($id)."
				AND client_id=".FunctionsV3::q($client_id)."
				");
		    }
			$this->code = 1;
			$this->msg="OK";
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiongetAddressBook()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];
		$id = isset($this->data['id'])?$this->data['id']:'';
		if($id>=1){
			if ($res=Yii::app()->functions->getAddressBookByID($id)){
				unset($res['date_created']);
				unset($res['date_modified']);
				unset($res['ip_address']);
				
				$country_list = require_once('CountryCode.php');
				$res['country_list'] = $country_list;
				
				$this->code = 1;
				$this->msg = "ok";
				$this->details = $res;
			} else $this->msg = $this->t("Record not found. please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiongetOrders()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';	
		
		if ($client_id = $this->checkToken()){
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
	
	        $paginate_total=0; 
	        $limit="LIMIT $page,$pagelimit"; 
	        
	        $cancel_order_enabled = getOptionA('cancel_order_enabled');		
			$website_review_type = getOptionA('website_review_type');
			$review_baseon_status = getOptionA('review_baseon_status');	
			$merchant_can_edit_reviews = getOptionA('merchant_can_edit_reviews');
			if($website_review_type==1){
				$review_baseon_status = getOptionA('review_merchant_can_add_review_status');
			}	
			
			$date_now=date('Y-m-d g:i:s a');	 
			
			$and='';		
		    $tab = isset($this->data['tab'])?$this->data['tab']:'';				   
		    $and = SingleAppClass::getOrderTabsStatus($this->merchant_id,$tab);		    
        			        
			$stmt="
			SELECT SQL_CALC_FOUND_ROWS 
			a.order_id,
			a.client_id,
			a.merchant_id,
			a.trans_type,
			a.payment_type,
			a.date_created,
			a.date_created as date_created_raw,
			a.total_w_tax,
			a.status,
			a.status as status_raw,		
			a.request_cancel,
			a.order_locked,
			a.request_cancel_status,
			b.restaurant_name as merchant_name,
			b.logo,
			
			(
			select rating from {{review}}
			where order_id = a.order_id
			and status='publish'		
			limit 0,1
			) as rating
			
			FROM
			{{order}} a
			left join {{merchant}} b
	        ON
	        a.merchant_id = b.merchant_id
	                
			WHERE a.client_id=".FunctionsV3::q($client_id)."
			
			AND a.merchant_id=".FunctionsV3::q($this->merchant_id)."
			
			AND a.status NOT IN ('".initialStatus()."')
	
			$and	
			
			ORDER BY a.order_id DESC
			$limit
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
				$res = Yii::app()->request->stripSlashes($res);
			   	$data = array();
			   	foreach ($res as $val) {
			   		$val['merchant_name'] = clearString($val['merchant_name']);
					$val['status'] = st($val['status']);
					$val['transaction'] = st("[trans_type] #[order_id]",array(
					  '[trans_type]'=>t($val['trans_type']),
					  '[order_id]'=>t($val['order_id']),
					));
					$val['date_created'] = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
					$val['stic_date_created'] = SingleAppClass::sticPrettyDate($val['date_created']);
					$val['stic_time_created'] = SingleAppClass::sticPrettyTime($val['date_created']);
					$val['total_w_tax'] = FunctionsV3::prettyPrice($val['total_w_tax']);
					$val['payment_type'] = st(FunctionsV3::prettyPaymentTypeTrans($val['trans_type'],$val['payment_type']));
					$val['logo']=SingleAppClass::getImage($val['logo']);
					
					$add_review = false;		
					if(SingleAppClass::canReviewOrder($val['status_raw'],$website_review_type,$review_baseon_status)){
					   $add_review=true;
					}				
					
					if($add_review){		
						if ($val['client_id']==$client_id){		    		
			    			$date_diff=Yii::app()->functions->dateDifference(
			    			date('Y-m-d g:i:s a',strtotime($val['date_created_raw']))
			    			,$date_now);
			    			if(is_array($date_diff) && count($date_diff)>=1){
			    				if ($date_diff['days']>=5){
			    				   $add_review=false;
			    				}
			    			}	    	
						} else $add_review=false;
					}
					
					if($website_review_type==1){
						if($val['rating']>0){
							if($merchant_can_edit_reviews=="yes"){
							   	$add_review=false;
							}
						}				
					}
									
					$val['add_review'] = $add_review;
					
					$show_cancel = false; $cancel_status='';
					if(FunctionsV3::canCancelOrderNew($val['request_cancel'],$val['date_created_raw'],$val['status_raw'],$val['order_locked'],$val['request_cancel_status'],$cancel_order_enabled)){
						if($val['request_cancel']==1){
							$cancel_status = st("Pending for review");
						} else $show_cancel=true;									
					}	
					
					if ($val['request_cancel_status']!='pending'){					
						$cancel_status = Yii::t("singleapp","Request cancel : [status]",array(
						  '[status]'=>t($val['request_cancel_status'])
						));
					}		
					
					$val['add_cancel']=$show_cancel;
					$val['cancel_status']=$cancel_status;
	
					$val['add_track']=true;
					
					/*FIXED FOR OLD VERSION*/
					$val['transaction_type']=st($val['trans_type']);
					$val['placed']= st("Placed on [date]",array(
					  '[date]'=>FunctionsV3::prettyDate($val['date_created'])
					));
					$val['total'] = $val['total_w_tax'];
					
					$data[]=$val;
			   	}
			   	
			   	$this->code = 1;
				$this->msg="OK";
				$this->details = array( 
				  'page_action'=>$page_action,
				  'paginate_total'=>$paginate_total,
				  'data'=>$data
				);
			} else {
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {			
									
					$msg1='';	
					switch ($tab) {
						case "processing":		
						    $msg1 = $this->t("There is no processing order");			        
							break;
					
						case "completed":			
						    $msg1 = $this->t("There is no completed order");	
							break;
							
						case "cancelled":				
						    $msg1 = $this->t("There is no cancelled order");	
							break;
									
						default:
							$msg1 = $this->t("Your order list is empty");
							break;
					}
					
					$this->code = 6;
					$this->details = array(
					  'title'=>$msg1,
					  'sub_title'=>st("Make your first order")
					);	
				}
			}      
		} else {
			$this->code = 3;
					$this->details = array(
					  'title'=>st("Get your first order, sign up now!"),
					  'sub_title'=>st("Make your first order")
					);	
		}	
		$this->output();
	}
	
	public function actiongetOrderDetails()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		$id = isset($this->data['id'])?$this->data['id']:'';		
		$order_id = $id;
		if($id>=1){
			$_GET['backend']='';
			if ( $data = SingleAppClass::getReceiptByID($order_id)){
				 $data = Yii::app()->request->stripSlashes($data);
				
				$json_details=!empty($data['json_details'])?json_decode($data['json_details'],true):false;				
				
				if ( $json_details !=false){
					
					 Yii::app()->functions->displayOrderHTML(array(
				       'merchant_id'=>$data['merchant_id'],
				       'order_id'=>$data['order_id'],
				       'delivery_type'=>$data['trans_type'],
				       'delivery_charge'=>$data['delivery_charge'],
				       'packaging'=>$data['packaging'],
				       'cart_tip_value'=>$data['cart_tip_value'],
					   'cart_tip_percentage'=>$data['cart_tip_percentage']/100,
					   'card_fee'=>$data['card_fee'],
					   'donot_apply_tax_delivery'=>$data['donot_apply_tax_delivery'],
					   'points_discount'=>isset($data['points_discount'])?$data['points_discount']:'' /*POINTS PROGRAM*/,
					   'voucher_amount'=>$data['voucher_amount'],
					   'voucher_type'=>$data['voucher_type']
				     ),$json_details,true,$data['order_id']);
				     
				     $data2=Yii::app()->functions->details;
				      
				     $merchant_info=Yii::app()->functions->getMerchant($this->merchant_id);
			         $full_merchant_address=$merchant_info['street']." ".$merchant_info['city']. " ".$merchant_info['state'].
			         " ".$merchant_info['post_code'];
			
					 if (isset($data['contact_phone1'])){
						if (!empty($data['contact_phone1'])){
							$data['contact_phone']=$data['contact_phone1'];
						}
					 }				
					 if (isset($data['location_name1'])){
						if (!empty($data['location_name1'])){
							$data['location_name']=$data['location_name1'];
						}
					}
					
					$new_data = array();					
					$new_data[] = SingleAppClass::receiptFormater("Customer Name", clearString($data['full_name']));
					$new_data[] = SingleAppClass::receiptFormater("Merchant Name", clearString($data['merchant_name']) );					
					if (isset($data['abn']) && !empty($data['abn'])){						
						$new_data[] = SingleAppClass::receiptFormater("ABN",$data['abn']);					
					}
					$new_data[] = SingleAppClass::receiptFormater("Telephone",$data['merchant_contact_phone']);
					$new_data[] = SingleAppClass::receiptFormater("Address",$full_merchant_address);
										
					$merchant_tax_number=getOption($this->merchant_id,'merchant_tax_number');
			        if(!empty($merchant_tax_number)){
			           $new_data[] = SingleAppClass::receiptFormater("Tax number",$merchant_tax_number);
			        }
			        
			        $new_data[] = SingleAppClass::receiptFormater("TRN Type", t($data['trans_type']) );
			        $new_data[] = SingleAppClass::receiptFormater("Payment Type",
			          FunctionsV3::prettyPaymentType('payment_order',$data['payment_type'],$data['order_id'],$data['trans_type'])
			        );
			        
			        if ( $data['payment_provider_name']){			       	   
			       	   $new_data[] = SingleAppClass::receiptFormater("Card#",$data['payment_provider_name']);
			        }
			        
			        if ( $data['payment_type'] =="pyp"){
			       	  $paypal_info=Yii::app()->functions->getPaypalOrderPayment($data['order_id']);	
			          			       	  
			          $new_data[] = SingleAppClass::receiptFormater("Paypal Transaction ID",
			            isset($paypal_info['TRANSACTIONID'])?$paypal_info['TRANSACTIONID']:''
			          );
			        }
			        			        
			        $new_data[] = SingleAppClass::receiptFormater("Reference #", Yii::app()->functions->formatOrderNumber($data['order_id']));
			        
			        if ( !empty($data['payment_reference'])){			       	  
			       	   $new_data[] = SingleAppClass::receiptFormater("Payment Ref",$data['payment_reference']);
			        }
			        if ( $data['payment_type']=="ccr" || $data['payment_type']=="ocr"){			           
			           $new_data[] = SingleAppClass::receiptFormater("Card #",
			             Yii::app()->functions->maskCardnumber($data['credit_card_number'])
			           );
			        }
			        
			        $trn_date=date('M d,Y G:i:s',strtotime($data['date_created']));			        
			        $new_data[] = SingleAppClass::receiptFormater("TRN Date",
			          Yii::app()->functions->translateDate($trn_date)
			        );

			        switch ($data['trans_type']) {
        	         	case "delivery":
        	         		
        	         		if (isset($data['delivery_date'])){
				           	   $date = prettyDate($data['delivery_date']);
					           $date=Yii::app()->functions->translateDate($date);				               
				               $new_data[] = SingleAppClass::receiptFormater("Delivery Date",$date);
				            }
				            
				            if (isset($data['delivery_time'])){
				       	  	  if ( !empty($data['delivery_time'])){				       	  	  	  
				       	  	  	  $new_data[] = SingleAppClass::receiptFormater("Delivery Time",
				       	  	  	    Yii::app()->functions->timeFormat($data['delivery_time'],true)
				       	  	  	  );
				       	  	  }
				       	    }
				       	    
				       	    if (isset($data['delivery_asap'])){
				       	   	   if ( !empty($data['delivery_asap'])){				       	   	   	   
				       	   	   	   $new_data[] = SingleAppClass::receiptFormater("Deliver ASAP", $data['delivery_asap']==1?t("Yes"):'' );
				       	   	   }
				       	    } 
				       	    
				       	    if (!empty($data['client_full_address'])){
					         	$delivery_address=$data['client_full_address'];
					        } else $delivery_address=$data['full_address'];				       	    
					        
				       	    $new_data[] = SingleAppClass::receiptFormater("Deliver to",$delivery_address);
				       	    
				       	    if (!empty($data['delivery_instruction'])){					       	   
					       	    $new_data[] = SingleAppClass::receiptFormater("Delivery Instruction",$data['delivery_instruction']);
					       	}
					       	
					       	if (!empty($data['location_name1'])){
					           $data['location_name']=$data['location_name1'];
					        }					       	
					       	$new_data[] = SingleAppClass::receiptFormater("Location Name",$data['location_name']);
					       						       	 
					       	if ( !empty($data['contact_phone1'])){
					          $data['contact_phone']=$data['contact_phone1'];
					        }				       	    
				       	    $new_data[] = SingleAppClass::receiptFormater("Contact Number",$data['contact_phone']);
        	         		
				       	    if ($data['order_change']>=0.0001){	       	   	               
	       	   	               $new_data[] = SingleAppClass::receiptFormater("Change", FunctionsV3::prettyPrice($data['order_change']) );
	       	                }
	       	                
	       	                if($data['opt_contact_delivery']==1){
	       	                	$new_data[] = SingleAppClass::receiptFormater("Delivery options", st("Leave order at the door or gate") );
	       	                }
				       	    
        	         		break;
        	         
        	         	case "pickup":
        	         		        	         		
        	         		$new_data[] = SingleAppClass::receiptFormater("Contact Number", $data['contact_phone'] );
        	         		if (isset($data['delivery_date'])){	       	  	                
	       	  	                $new_data[] = SingleAppClass::receiptFormater("Pickup Date", $data['delivery_date'] );
	       	                }
	       	                
	       	                if (isset($data['delivery_time'])){
				       	  	   if ( !empty($data['delivery_time'])){				       	  	  	  
				       	  	  	  $new_data[] = SingleAppClass::receiptFormater("Pickup Time", $data['delivery_time'] );
				       	  	   }
					       	}
					       	
					       	if ($data['order_change']>=0.0001){	       	   	               
	       	   	               $new_data[] = SingleAppClass::receiptFormater("Change", FunctionsV3::prettyPrice($data['order_change']) );
	       	                }
        	         		
        	         	    break;        	         	
        	         	    
        	         	case "dinein":
        	         		
        	         		$new_data[] = SingleAppClass::receiptFormater("Contact Number", $data['contact_phone'] );
        	         		if (isset($data['delivery_date'])){	       	  	                
	       	  	                $new_data[] = SingleAppClass::receiptFormater("Dine in Date", $data['delivery_date'] );
	       	                }
	       	                
	       	                if (isset($data['delivery_time'])){
				       	  	   if ( !empty($data['delivery_time'])){				       	  	  	  
				       	  	  	  $new_data[] = SingleAppClass::receiptFormater("Dine in Time", $data['delivery_time'] );
				       	  	   }
					       	}
					       	
					       	if ($data['order_change']>=0.0001){	       	   	               
	       	   	               $new_data[] = SingleAppClass::receiptFormater("Change", FunctionsV3::prettyPrice($data['order_change']) );
	       	                }
	       	                
	       	                $new_data[] = SingleAppClass::receiptFormater("Number of guest", $data['dinein_number_of_guest'] );
	       	                $new_data[] = SingleAppClass::receiptFormater("Table number", $data['dinein_table_number'] );
	       	                $new_data[] = SingleAppClass::receiptFormater("Special instructions", $data['dinein_special_instruction'] );	       	                
        	         		
        	         	    break;     
        	         }	                 	         
					        	      
        	        
        	        
        	        $new_total_html='';
        	        
        	        if($data['apply_food_tax']==1){          	        	
        	        	$file = Yii::getPathOfAlias('webroot')."/protected/modules/singlemerchant/views/api/cart.php";        	        	
        	        	$new_total_html=$this->renderFile($file,array(
			    		   'data'=>$data
			    		),true);			    					    					    
        	        }        	      
        	                	        
					$this->code = 1;
					$this->msg = "OK";
					$this->details = array(
					  'apply_food_tax'=>$data['apply_food_tax'],
					  'data'=>$new_data,
					  'html'=>$data2['html'],
					  'new_total_html'=>$new_total_html
					);
				     
				} else $this->msg = $this->t("Order not available to view. please try again later");				
			} else $this->msg = $this->t("Order not available to view. please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actionreOrder()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		$id = isset($this->data['id'])?$this->data['id']:'';	
		$order_id = $id;
		if($id>=1){
			if ($res = SingleAppClass::ReOrderGetInfo($order_id)){	
				$res = Yii::app()->request->stripSlashes($res);
				
				if($res['merchant_status']!="active"){
					$this->msg = $this->t("Merchant is no longer active");
					$this->output();
				}			
				if($res['is_ready']!=2){
					$this->msg = $this->t("Merchant is not published");
					$this->output();
				}			
																
				/*VALIDATE IF ITEM IS AVAILABLE*/
				$cart_count=0;
				$json_details = json_decode($res['json_details'],true);
				$re_order_items = array();							
					
				if(is_array($json_details) && count($json_details)>=1){
				   foreach ($json_details as $item) {				   	   				   	   
				   	
				   	   $newest_price = 0; $newest_discount=0;
				   	   $current_discount = 0; $current_item_price = 0;
				   	
				   	   if ($item_res = Yii::app()->functions->getFoodItem($item['item_id'])){
				   	   	   if($item_res['not_available']==2){
				   	   	   	  // do nothing			   	   	   	  
				   	   	   } else {				   	   	   
				   	   	   	  //dump($item_res);				   	   	   	  
				   	   	   	  $current_discount = isset($item['discount'])?$item['discount']:0;
				   	   	   	  $item_price = explode("|",$item['price']);
				   	   	   	  				   	   	   	  
				   	   	   	  if( count($item_price) <=1){				   	   	   	  	
				   	   	   	  			  
				   	   	   	  	$newest_discount = $item_res['discount'];
				   	   	   	  	$current_item_price = $item_price[0];
				   	   	   	  	$current_item = json_decode($item_res['price'],true);
				   	   	   	  	if(is_array($current_item) && count($current_item)>=1){
				   	   	   	  		//$newest_price = $current_item[0];
				   	   	   	  		foreach ($current_item as $new_price) {
				   	   	   	  			$newest_price = $new_price;
				   	   	   	  		}
				   	   	   	  	}								   	   	   	  					   	   	   	  				   	   	   	  
				   	   	   	  	if($current_item_price!=$newest_price){
				   	   	   	  		$item['price'] = $newest_price;
				   	   	   	  	}				   	   	   	  
				   	   	   	  	if($current_discount!=$newest_discount){
				   	   	   	  		$item['discount'] = $newest_discount;
				   	   	   	  	}				   	   	   	  
				   	   	   	  } else {				   	  					   	   	   	  			   	   	   	  
				   	   	   	  	$newest_discount = $item_res['discount'];				   	   	   	  	 	   	  	
				   	   	   	  	$current_size_id = isset($item_price[2])?$item_price[2]:0;
				   	   	   	  	$current_item_price = isset($item_price[0])?$item_price[0]:0;
				   	   	   	  	$newest_price_list = json_decode($item_res['price'],true);				   	   	   	  	
				   	   	   	  	if(array_key_exists($current_size_id,(array)$newest_price_list)){
				   	   	   	  		$newest_price = $newest_price_list[$current_size_id];				   	   	   	  						   	   	   	  	
				   	   	   	  	    if($current_item_price!=$newest_price){
				   	   	   	  	    	$item['price'] = $newest_price."|".$item_price[1]."|".$item_price[2];
				   	   	   	  	    }			
				   	   	   	  	    
				   	   	   	  	    if($current_discount!=$newest_discount){
				   	   	   	  		   $item['discount'] = $newest_discount;
				   	   	   	  	    }				   	   	   	  
				   	   	   	  		   	   	   	  	
				   	   	   	  	} else {
				   	   	   	  		// price does not exist
				   	   	   	  	}	   	   	   	  				   	   	   	  					   	   	   	  	
				   	   	   	  } 	   				   	   	   	  
				   	   	   	  				   	   	   	  
				   	   	   	  $re_order_items[] = $item;
				   	   	   	  $cart_count++;
				   	   	   }
				   	   }				   
				   }
				}	
				
				
				/*dump($re_order_items);
				die();*/
							
				if($cart_count<=0){
					$this->msg = $this->t("There is no item to re-order");
					$this->output();
				}		
								
				/*inventory*/				
				$merchant_id = $this->merchant_id;
				if(SingleAppClass::inventoryEnabled($merchant_id)){
					try {						
						StocksWrapper::verifyStocksReOrder($id,$merchant_id);
					} catch (Exception $e) {
						$this->msg = $e->getMessage();
		                $this->output();
					}
				}		
				
				$params = array(				  
				  'cart'=>json_encode($re_order_items),
				  'date_modified'=>FunctionsV3::dateNow(),
				  'device_id'=>$this->device_uiid,
				  'merchant_id'=>$this->merchant_id
				);
				
				$db=new DbExt; 
				
				if(SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
					if ( $db->updateData("{{singleapp_cart}}",$params,'device_id',$this->device_uiid)){
						$this->code = 1;
						$this->msg = "OK";
					} else $this->msg = $this->t("Order not available to re-order. please try again later");
				} else {
					if ($db->insertData("{{singleapp_cart}}",$params)){
						$this->code = 1;
						$this->msg = "OK";
					} else $this->msg = $this->t("Order not available to re-order. please try again later");
				}
				
				$trans_type = $res['trans_type'];				
				$services = SingleAppClass::getMerchantServices($res['service']);
				
				if(!array_key_exists($trans_type,(array)$services)){
					if(is_array($services) && count($services)>=1){
						foreach ($services as $key=>$val) {
							$trans_type = $key;							
							break;
						}
					}			
				}
				
				$this->details = $trans_type;
				
			} else $this->msg = $this->t("Order not available to re-order. please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actionloadReviews()
	{
		$client_id='';  
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){		
			$client_id = $res['client_id'];
		}					
		
		$date_now=date('Y-m-d g:i:s a');
		$p = new CHtmlPurifier();
		$data = array();
		$reply = array();
		
		if(isset($this->data['limit'])){
			$this->paginate_limit = $this->data['limit'];
		}
		
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $this->paginate_limit;
        } else  $page = 0;  
        
		$db=new DbExt;
		$stmt="SELECT a.*,
		(
		select first_name
		from 
		{{client}}
		where
		client_id=a.client_id
		) as client_name
		FROM
		{{review}} a
		WHERE		
		merchant_id= ".FunctionsV3::q($this->merchant_id)."
		AND
		status ='publish'
		ORDER BY id DESC
		LIMIT $page,$this->paginate_limit
		";
		
		if(isset($_GET['debug'])){
			dump($stmt);
		}
		
		$website_review_type = getOptionA('website_review_type');
		$review = false;
		if($website_review_type==1){
			if($remaining_review = FunctionsK::getRemainingReview($client_id,$this->merchant_id)){
				$review=true;
			}
		}
				
		if ( $res = $db->rst($stmt) ){
			foreach ($res as $val) {
				
				//dump($val);
				$reply = array();
				
				$can_edit=true;
				
			    $date_diff=Yii::app()->functions->dateDifference(
    			date('Y-m-d g:i:s a',strtotime($val['date_created']))
    			,$date_now);
    			if(is_array($date_diff) && count($date_diff)>=1){
    				if ($date_diff['days']>=10){
    				   $can_edit=false;
    				}
    			}
    			
	    		$pretyy_date=PrettyDateTime::parse(new DateTime($val['date_created']));
	    		$pretyy_date=Yii::app()->functions->translateDate($pretyy_date);
	    		
	    		if ( $replies=FunctionsV3::reviewReplyList($val['id'],'publish')){
	    			foreach ($replies as $val_reply){
	    				
	    				$pretyy_date_reply=PrettyDateTime::parse(new DateTime($val_reply['date_created']));
	    		        $pretyy_date_reply=Yii::app()->functions->translateDate($pretyy_date);
	    		
	    				$reply[] = array(
	    				   'reply_from'=>Yii::t("singleapp","[from] reply",array('[from]'=> stripslashes($val_reply['reply_from']) )),
	    				   'review'=>$p->purify($val_reply['review']),
	    				   'date'=>$pretyy_date_reply
	    				);
	    			}
	    		}
	    		
	    		if($can_edit){
		    		if ( $val['client_id']!=$client_id ){
		    			$can_edit=false;
		    		}
	    		}
	    			    		
	    		$data[] = array(
	    		  'id'=>$val['id'],
	    		  'review'=>nl2br($p->purify($val['review'])),
	    		  'rating'=>$val['rating'],
	    		  'client_name'=>$val['client_name'],
	    		  'avatar'=>SingleAppClass::getAvatar($val['client_id']),
	    		  'can_edit'=>$can_edit,
	    		  'date'=>$pretyy_date,
	    		  'reply'=>$reply
	    		);
	    		
			}
			
			$this->code = 1;
			$this->msg="OK";
			$this->details = array(
			   'review'=>$review,
			   'data'=>$data
			);
			
		} else {
			$this->details = array(
			   'review'=>$review			  
			);
			$this->msg = $this->t("no results");
		}	
				
		
		$this->output();
	}
	
	public function actiongetReview()
	{
		$id = isset($this->data['id'])?$this->data['id']:'';
		if ($id>0){
			if ( $res=Yii::app()->functions->getReviewsById2($id,$this->merchant_id)){				
				$data = array(
				  'id'=>$res['id'],
				  'review'=>$res['review'],
				  'rating'=>$res['rating'],				  
				);
				$this->code = 1;
				$this->msg = "OK";
				$this->details = array(
				  'data'=>$data
				);
			} else $this->msg = $this->t("Review is not available to view. please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actionupdateReview()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		$id = isset($this->data['review_id'])?$this->data['review_id']:'';
		if ($id>0){
			
			$p = new CHtmlPurifier();
			 
			$params = array(
			 'review'=>$p->purify($this->data['review']),
			 'date_modified'=>FunctionsV3::dateNow(),			 			 
			);
			$db=new DbExt;
			if ($db->updateData("{{review}}",$params,'id',$id)){
				$this->code = 1;
				$this->msg  = "OK";
				$this->details = '';
			} else $this->msg = $this->t("Cannot update records, please try again later");
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiondeleteReview()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		$id = isset($this->data['id'])?$this->data['id']:'';
		if ($id>0){
			$db=new DbExt;
			$db->qry("
			DELETE FROM {{review}}
			WHERE id=".FunctionsV3::q($id)."
			AND
			client_id=".FunctionsV3::q($client_id)."
			");
			$this->code = 1;
			$this->msg="OK";
		} else $this->msg = $this->t("Invalid id");
		$this->output();
	}
	
	public function actiongetUserInfo()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!empty($token)){
			if($res = SingleAppClass::getCustomerByToken($token)){			
				$data = array(
				  'name'=>$res['first_name']." ".$res['last_name'],
				  'email_address'=>$res['email_address'],
				  'contact_phone'=>$res['contact_phone']
				);
				$this->code = 1;
				$this->msg = "OK";	
				$this->details = array(
				   'data'=>$data
				);
			} else $this->msg = "Not login";
		} else $this->msg = "Not login";
		$this->output();
	}

	public function actiongetUserData()
	{		
		$token = isset($this->data['token'])?$this->data['token']:'';
		$device_uiid = isset($this->data['device_uiid'])?$this->data['device_uiid']:'';
		
		if($res = SingleAppClass::getCustomerByTokenAndDevice($token,$device_uiid)){											
			$client_id = (integer) $res['client_id'];			
			$data = array(
			   'stic_dark_theme'=>$res['stic_dark_theme']>0?$res['stic_dark_theme']:0,
			);
			
			$this->code = 1;
			$this->msg = "ok";
			$this->details = array(
			 'data'=>$data
			);
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");
		}
		$this->output();
	}
	
	public function actionsaveBooking()
	{
		 if ( isset($this->data['booking_time'])){
       	  if(!empty($this->data['booking_time'])){
       	  	 $time_1=date('Y-m-d g:i:s a');
       	  	 $time_2=$this->data['date_booking']." ".$this->data['booking_time'];       	  	 
       	  	 $time_2=date("Y-m-d g:i:s a",strtotime($time_2));	     	       	  	 
       	  	 $time_diff=Yii::app()->functions->dateDifference($time_2,$time_1);	       	  		       	  	        	  	        	  	 
       	  	 if (is_array($time_diff) && count($time_diff)>=1){
       	  	     if ( $time_diff['hours']>0){	       	  	     	
	       	  	     $this->msg=$this->t("Sorry but you have selected time that already past");
	       	  	     $this->output(); 	  	     	
       	  	     }	       	  	
       	  	     if ( $time_diff['minutes']>0){	       	  	     	
	       	  	     $this->msg=$this->t("Sorry but you have selected time that already past");
	       	  	     $this->output();  	  	     	
       	  	     }	       	  	
       	  	 }	       	  
       	  }	       
       }		     
       
       $merchant_id = $this->merchant_id;
       
       $full_booking_time=$this->data['date_booking']." ".$this->data['booking_time'];
	   $full_booking_day=strtolower(date("D",strtotime($full_booking_time)));			
	   $booking_time=date('h:i A',strtotime($full_booking_time));	
	   	   
	   if ( !Yii::app()->functions->isMerchantOpenTimes($merchant_id,$full_booking_day,$booking_time)){			
			$this->msg = Yii::t("singleapp","Sorry but we are closed on [date]. Please check merchant opening hours",array(
			  '[date]'=>date("F,d Y h:ia",strtotime($full_booking_time))
			));
		    $this->output();  	 
		}		   
		
		$now=isset($this->data['date_booking'])?$this->data['date_booking']:'';			
		$merchant_close_msg_holiday='';
	    $is_holiday=false;
	    if ( $m_holiday=Yii::app()->functions->getMerchantHoliday($merchant_id)){
      	    if (in_array($now,(array)$m_holiday)){
      	   	    $is_holiday=true;
      	    }
	    }
	    if ( $is_holiday==true){
	    	$merchant_close_msg_holiday=!empty($merchant_close_msg_holiday)?$merchant_close_msg_holiday:$this->t("Sorry but we are on holiday on")." ".date("F d Y",strtotime($now));
	    	$this->msg=$merchant_close_msg_holiday;
	    	$this->output();  
	    }		  
	    
	    $fully_booked_msg=Yii::app()->functions->getOption("fully_booked_msg",$merchant_id);
		if (!Yii::app()->functions->bookedAvailable($merchant_id)){
		   if (!empty($fully_booked_msg)){
		    		$this->msg=t($fully_booked_msg);
		   } else $this->msg=$this->t("Sorry we are fully booked for that day");			 	
		   $this->output();  
		}  
		
		$params=array(
		  'merchant_id'=>$this->merchant_id,
		  'number_guest'=>isset($this->data['number_guest'])?$this->data['number_guest']:'',
		  'date_booking'=>isset($this->data['date_booking'])?$this->data['date_booking']:'',
		  'booking_time'=>isset($this->data['booking_time'])?$this->data['booking_time']:'',
		  'booking_name'=>isset($this->data['booking_name'])?$this->data['booking_name']:'',
		  'email'=>isset($this->data['email'])?$this->data['email']:'',
		  'mobile'=>isset($this->data['mobile'])?$this->data['mobile']:'',
		  'booking_notes'=>isset($this->data['booking_notes'])?$this->data['booking_notes']:'',
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR'],		  
		);
		
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
		   $params['client_id']= $res['client_id'];
		}			
		
		$db=new DbExt;				
		if ( $db->insertData('{{bookingtable}}',$params)){			
			$booking_id=Yii::app()->db->getLastInsertID();
			$this->code=1;			
			
			$this->msg = Yii::t("singleapp","Your booking has been placed. Reference # [booking_id]",array(
				      '[booking_id]'=>$booking_id
				    ));
			
			$this->details = $booking_id;
			
			/*SEND NOTIFICATIONS*/		
			$new_data = $params;	
			$new_data['restaurant_name']=$this->merchant_name;
		    $new_data['booking_id']=$booking_id;		    
		    FunctionsV3::notifyBooking($new_data);
		    
		    /*POINTS PROGRAM*/		    		
    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
    		   PointsProgram::rewardsBookTable($booking_id , isset($params['client_id'])?$params['client_id']:'' , $merchant_id );
    		}
			    
		} else $this->msg = $this->t("Something went wrong during processing your request. Please try again later");
	   
	    $this->output();
	}
	
	public function actiongetMerchantInfo()
	{
		if ( $res = FunctionsV3::getMerchantInfo($this->merchant_id)){	
			$ratings=Yii::app()->functions->getRatings($this->merchant_id);   	
			$rating_text = '';
			if(is_array($ratings) && count($ratings)>=1){
				$rating_text = Yii::t("singleapp","[rating] Reviews",array(
				  '[rating]'=>$ratings['votes']
				));
			}
			
			$merchant_photo_bg=getOption($this->merchant_id,'merchant_photo_bg');
			if ( !file_exists(FunctionsV3::uploadPath()."/$merchant_photo_bg")){
				$merchant_photo_bg='';
			} 			
						
			$data = array(  
			  'merchant_name'=>clearString($res['restaurant_name']),
			  'logo'=>SingleAppClass::getImage($res['logo']),
			  'contact_phone'=>$res['contact_phone'],
			  'address'=>clearString($res['complete_address']),
			  'cuisine'=>FunctionsV3::displayCuisine($res['cuisine']),
			  'free_delivery'=>FunctionsV3::getFreeDeliveryTag($this->merchant_id),
			  'ratings'=>$ratings,
			  'rating_text'=>$rating_text,
			  // 'background_image'=>$merchant_photo_bg,
			  'background_image'=>websiteUrl()."/upload/".$merchant_photo_bg,
			  'latitude'=>$res['latitude'],
			  'lontitude'=>$res['lontitude'],
			);
			
			$new_hours = array();
			if ( $hours=FunctionsV3::getMerchantOpeningHours($this->merchant_id)){
				foreach ($hours as $val){
					$new_hours[]=array(
					  'day'=>$this->t($val['day']),
					  'hours'=>$val['hours'],
					  'open_text'=>$this->t($val['open_text']),
					);
				}
			} else $new_hours ='';
			
			$data['opening_hours'] = $new_hours;

			$payment_list_new=array();
			$payment_list = FunctionsV3::getMerchantPaymentListNew($this->merchant_id);		
			if(is_array($payment_list) && count($payment_list)>=1){
			   foreach ($payment_list as $payment_list_key=>$payment_list_val) {
			   		$payment_list_new[$payment_list_key] = $this->t($payment_list_val);
			   	}	
			}		
			$data['payment_list'] = $payment_list_new;
			
			$data['information'] = getOption($this->merchant_id,'merchant_information');
			$data['information'] = clearString($data['information']);
			
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'data'=>$data
			);
		} else {
			$this->msg = $this->t("Merchant information not available. please try again later");
			$this->details = array(
			  'title'=>st("Information not found"),
			  'sub_title'=>st("Merchant information is not available")
			);	
		}	
		$this->output();
	}
	
	public function actiongetMerchantPhoto()
	{
		$list = array();
		$gallery=Yii::app()->functions->getOption("merchant_gallery",$this->merchant_id);
        $gallery=!empty($gallery)?json_decode($gallery):false;
        if(is_array($gallery) && count($gallery)>=1){
        	foreach ($gallery as $val) {
        		$list[] = SingleAppClass::getImage($val);
        	}        
        	$this->code = 1;
        	$this->msg ="OK";
        	$this->details=array('data'=>$list);
        } else $this->msg = $this->t("Photos not available");        
		$this->output();
	}
	
	public function actionloadPromo()
	{
		 $merchant_id = $this->merchant_id;
    	if($merchant_id>0){
    		$promo = array();
    		$promo['enabled']=1;
    		    		
    		if (method_exists("FunctionsV3","getOffersByMerchantNew")){	
	    		if($offer=FunctionsV3::getOffersByMerchantNew($merchant_id)){
		    	   $promo['offer']=$offer;
		    	   $promo['enabled']=2;
		    	}		    	
    		}
	    	
    		if (method_exists("FunctionsV3","merchantActiveVoucher")){			
		    	if ( $voucher=FunctionsV3::merchantActiveVoucher($merchant_id)){		    	    		
		    		$promo['enabled']=2;	    		
		    		foreach ($voucher as $val) {
		    			if ( $val['voucher_type']=="fixed amount"){
				      	  $amount=FunctionsV3::prettyPrice($val['amount']);
				        } else $amount=number_format( ($val['amount']/100)*100 )." %";
				        
				        $promo['voucher'][] = $val['voucher_name']." - ".$amount." ".SingleAppClass::t("Discount");
		    		}	    		 	    		
		    	}
    		}
	    	
	    	$free_delivery_above_price=getOption($merchant_id,'free_delivery_above_price');
	    	if ($free_delivery_above_price>0){
	    	    $promo['free_delivery']=$this->t("Free Delivery On Orders Over")." ". FunctionsV3::prettyPrice($free_delivery_above_price);
	    		$promo['enabled']=2;
	    	}
	    		 	  
	    	$this->code = 1;
	    	$this->msg = "OK";
	    	if($promo['enabled']==1){
	    		$this->msg = $this->t("No available promos for this merchant");	    		
	    	}    		    		    
	    	$this->details = array(
	    	  'data'=>$promo,
	    	  'title'=>st("No available promo"),
			   'sub_title'=>st("We don't have promo at this time")
	    	);
    		
    	} else $this->msg = $this->t("Invalid merchant id");    	
		$this->output();
	}
	
	public function actionloadBooking()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';	
		$data = array();
		
		if ($client_id = $this->checkToken()){
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
	        $limit="LIMIT $page,$pagelimit"; 

	        $and='';
	        $tab = isset($this->data['tab'])?$this->data['tab']:'';		        
	        switch ($tab) {        	
	        	case "all":
	        		break;
	        	default:
	        		$and=" AND a.status=".FunctionsV3::q($tab)." ";
	        		break;
	        }
	        
	        $booking_cancel_days = getOptionA('booking_cancel_days');
            $booking_cancel_hours = getOptionA('booking_cancel_hours');
            $booking_cancel_minutes = getOptionA('booking_cancel_minutes');
	        
	        $db = new DbExt();
			$stmt="
			SELECT
			a.booking_id,
			a.merchant_id,
			a.number_guest,
			a.status,
			a.status as status_raw,
			a.date_created,
			a.date_created as date_created_raw,
			a.request_cancel,
			b.restaurant_name as merchant_name,
			b.logo
			
			FROM
			{{bookingtable}} a
			left join {{merchant}} b
	        ON
	        a.merchant_id = b.merchant_id
	                
			WHERE a.client_id=".FunctionsV3::q($client_id)."	
			AND a.merchant_id=". FunctionsV3::q($this->merchant_id) ."	
			$and
			ORDER BY a.booking_id DESC
			$limit
			";					
			
			if($res = $db->rst($stmt)){
				$res = Yii::app()->request->stripSlashes($res);
				foreach ($res as $val) {		
					
					$val['merchant_name'] = $val['merchant_name'];
					$val['status'] = st($val['status']);
					$val['number_guest'] = st("[count]",array(
					  '[count]'=> $val['number_guest']
					));
					$val['booking_ref'] = st("[booking_id]",array(
					  '[booking_id]'=> $val['booking_id']
					));
					$val['date_created'] = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
					$val['stic_date_created'] = SingleAppClass::sticPrettyDate($val['date_created']);
					$val['stic_time_created'] = SingleAppClass::sticPrettyTime($val['date_created']);
					$val['logo']=SingleAppClass::getImage($val['logo']);
					
					$ratings = Yii::app()->functions->getRatings($val['merchant_id']);
					
					$ratings['review_count'] = st("[count] reviews",array(
		 			  '[count]'=>$ratings['votes']
		 			));
		 			$val['rating']=$ratings;
		 			
		 			$val['can_cancel'] = 0;
		 			$can_cancel = SingleAppClass::canCancel($val['date_created_raw'],$booking_cancel_days,$booking_cancel_hours,$booking_cancel_minutes);
		 			if($can_cancel){
		 			   if($val['request_cancel']<=0){
		 			   	  if($val['status_raw']=='pending'){
		 			   	  	  $val['can_cancel'] = 'cancel_booking';
		 			   	  } else {
		 			   	  	  $val['can_cancel'] = 'cancel_booking_request_sent';
		 			   	  }			   
		 			   }	
		 			} else {
		 				if($val['request_cancel']>0){
		 				   	$val['can_cancel'] = 'cancel_booking_request_sent';
		 				}
		 			}	 			 			
		 			
					$data[]=$val;
				}
				
				$this->code = 1;
				$this->msg="OK";
				$this->details = array( 
				  'page_action'=>$page_action,				  
				  'data'=>$data
				);				
			} else {
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {			
					$this->code = 6;
					$this->msg = st("Your Booking is empty");
					$this->details = array(
					  'title'=>st("Your Booking is empty"),
					  'sub_title'=>st("Make your first booking")
					);	
				}
			}			    
		}
		$this->output();
	}
	
	public function actiongetPaypal()
	{		
		$total=0;
		
		$order_id = isset($this->data['order_id'])?$this->data['order_id']:'';
		
		if($order_id>0){
			if($res=Yii::app()->functions->getOrderInfo($order_id)){				
				$total = $res['total_w_tax'];
			}
		} else {
		   $this->msg = $this->t("Invalid order id");
		   $this->output();	
		}	
		
		if($total<=0){
		  $this->msg = $this->t("Invalid amount");
		  $this->output();	
		}
				
		if ( $res = SingleAppClass::getPaypalCredentials($this->merchant_id)){		
						
			if( strtolower($res['mode'])=="live"){
				$res['mode']='production';
			}		
				
			$res['total']= number_format($total,2,'.','');
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'order_id'=>$order_id,
			  'currency'=>Yii::app()->functions->adminCurrencyCode(),
			  'total_to_pay'=>Yii::t("singleapp","Total amount to pay [total]",array(
			    '[total]'=>FunctionsV3::prettyPrice($total)
			  )),
			  'data'=>$res
			);
		} else $this->msg = $this->t("Credentials not available");
		$this->output();
	}
	
	public function actionselectCreditCards()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		if ( $res = SingleAppClass::getCreditCards($client_id)){
			 $data =array();
			 foreach ($res as $val) {
			 	$data[]= array(
			 	  'id'=>$val['cc_id'],
			 	  'card'=>Yii::app()->functions->maskCardnumber($val['credit_card_number'])
			 	);
			 }			
			 $this->code = 1;
			 $this->msg = "OK";
			 $this->details = array(
			   'data'=>$data
			 );
		} else $this->msg =  $this->t("No results");
		$this->output();
	}
	
	public function actiongetStripe()
	{
		if ( $res = SingleAppClass::getStripeCredentials($this->merchant_id)){
			$this->code = 1;
			$this->msg = 'OK';
			$this->details = array(
			  'credentials'=>$res
			);
		} else $this->msg = $this->t("Credentials not available");
		$this->output();
	}
		
	public function actiongetPayondeliverycards()
	{
			
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];	
		
		if($res=Yii::app()->functions->getPaymentProviderMerchant($this->merchant_id)){
			$data = array();
			foreach ($res as $val) {
				$val['payment_logo'] = SingleAppClass::getImage($val['payment_logo']);
				$data[] = $val;
			}
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'data'=>$data
			);
		} else $this->msg = $this->t("No results");
		
		$this->output();
	}
	
	public function actionrazorPaymentSuccessfull()
	{		
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		
		$order_id = isset($this->data['order_id'])?$this->data['order_id']:'';	
		if($order_id>0){
			
			$params=array(
			  'payment_type'=>'rzr',
			  'payment_reference'=>$this->data['payment_id'],
			  'order_id'=>$order_id,
			  'raw_response'=>$this->data['payment_id'],
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);					
			$db = new DbExt();
			if ( $db->insertData("{{payment_order}}",$params) ){
							
				$params_update=array( 
				   'status'=>'paid',
				   'date_modified'=>FunctionsV3::dateNow()
				);	        
		        $db->updateData("{{order}}",$params_update,'order_id',$order_id);	
		        
		        $this->code = 1;
				 $this->msg = Yii::t("singleapp","Your order has been placed. Reference # [order_id]",array(
				      '[order_id]'=>$order_id
				 ));
				 
				 $total = 0;
				 if ($order_details = SingleAppClass::getOrderDetails($order_id)){
				 	$total = $order_details['total_w_tax'];
				 }			
				 
			     $this->details=array(
			      'order_id'=>$order_id,
			      'total_amount'=>$total,				      
			     );
		         			     
			     SingleAppClass::handleAll($order_id,$this->merchant_id,$client_id,$this->device_uiid,'paid');
			     
			     if (method_exists("SingleAppClass","updatePoints")){
				     SingleAppClass::updatePoints($order_id,'paid');
				 }		     
			     
			} else $this->msg  = $this->t("Something went wrong cannot insert records. please try again later");
			
		} else $this->msg = $this->t("invalid order id");
		
		$this->output();
	}
	
	public function actionmapInfo()
	{
		if ($res = FunctionsV3::getMerchantInfo($this->merchant_id)){
			$latitude = $res['latitude'];
			$lontitude  = $res['lontitude'];
			$address = clearString($res['complete_address']);
			$merchant_name = clearString($res['restaurant_name']);
			$this->code = 1;
			$this->msg = "OK";
			$this->details = array(
			  'data'=>array(			    
			    'info_window'=>"<h5>$merchant_name</h5><p>$address</p>",
			    'latitude'=>$latitude,
			    'lontitude'=>$lontitude,			    
			  )
			);
		} else $this->msg = $this->t("Merchant information not available. please try again later");
		$this->output();
	}
	
	public function actionfbRegister()
	{			
	    $db = new DbExt();
		$Validator=new Validator;		
		
		$Validator->required(array(
		  'email_address'=>$this->t("email address is required"),
		  'fb_id'=>$this->t("facebook id is required")
		),$this->data);
		
		
		/*check if email address is blocked*/
    	if ( FunctionsK::emailBlockedCheck($this->data['email_address'])){
    		$Validator->msg[] = $this->t("Sorry but your email address is blocked by website admin");    		
    	}	    
    	
    	    			
		if($Validator->validate()){						
			
			$p = new CHtmlPurifier();			
			$params=array(
    		  'first_name'=>$p->purify($this->data['first_name']),
    		  'last_name'=>$p->purify($this->data['last_name']),
    		  'email_address'=>$p->purify($this->data['email_address']),
    		  'password'=>md5($this->data['fb_id']),
    		  'date_created'=>FunctionsV3::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],    		  
    		  'device_id'=>isset($this->data['device_id'])?$this->data['device_id']:'',
    		  'device_platform'=>isset($this->data['device_platform'])?strtolower($this->data['device_platform']):'',
    		  'social_strategy'=>'fb_mobile',
    		  'single_app_merchant_id'=>$this->merchant_id,
    		  'enabled_push'=>1,
    		  'single_app_device_uiid'=>$this->device_uiid,
    		);
    		
    		$save_pic = getOption($this->merchant_id,'singleapp_fb_save_pic');
    		$save_avatar_exist = false;
    		if(method_exists('FunctionsV3','saveFbAvatarPicture')){
    			$save_avatar_exist=true;
    		}		
    		
    		if( $res = Yii::app()->functions->isClientExist($params['email_address']) ){
    			
    			if($save_pic==1 && $save_avatar_exist==true){
    				if (empty($res['avatar'])){
	    				$params['social_id'] = $this->data['fb_id'];
    				}
    			}
    			
    			$token = $res['token'];
    			if(empty($token)){
    				$token = SingleAppClass::generateUniqueToken(15,$params['fb_id']);	    
    				$params['token'] = $token;
    			}    		
    			
    			unset($params['date_created']);
    			$params['last_login']=FunctionsV3::dateNow();
    			    			
    			$db->updateData("{{client}}",$params,'client_id',$res['client_id']);			
    			
    			$this->code=1;
	    		$this->msg = $this->t("Registration successful");
	    		
	    		$this->details = array(
    			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
    			  'token'=>$token
    			);
    			
    			FunctionsV3::fastRequest( websiteUrl()."/singlemerchant/cron/getfbavatar" );
    			
    		} else {
    			// insert 
    			
    			if($save_pic==1 && $save_avatar_exist==true){    				
    				$params['social_id'] = $this->data['fb_id'];
    			}
    			
    			$token = SingleAppClass::generateUniqueToken(15,$params['email_address']);
	    	    $params['token']=$token;
    			if ( $db->insertData("{{client}}",$params)){
    				$customer_id =Yii::app()->db->getLastInsertID();	    		
	    		    $this->code=1;
	    		    $this->msg = $this->t("Registration successful");
	    		    
	    		    $this->details = array(
	    			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
	    			  'token'=>$token
	    			);
	    				    			
	                /*POINTS PROGRAM*/	    			
		    	    if (FunctionsV3::hasModuleAddon("pointsprogram")){
		    		    PointsProgram::signupReward($customer_id);
		    	    }
		    	    		    	    
	                FunctionsV3::fastRequest( websiteUrl()."/singlemerchant/cron/getfbavatar" );
	    			
    			} else $this->msg = $this->t("Something went wrong during processing your request. Please try again later");
    		}
		
		} else $this->msg = SingleAppClass::parseValidatorError($Validator->getError());
		$this->output();
	}
	
	public function actionverificationMobile()
	{
		$code = isset($this->data['code'])?trim($this->data['code']):'';
		if(!empty($code)){
			$token = isset($this->data['token'])?$this->data['token']:'';
			if(empty($token)){
				$this->msg = $this->t("Token is empty");
				$this->output();
			}					
			if($res = SingleAppClass::getCustomerByToken($token,false)){				
				$client_id = $res['client_id'];
				if ( $res['mobile_verification_code']==$code){
									
					$this->code=1;
				    $this->msg=t("Successful");
				    $this->details = array(
				      'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
				      'token'=>$token
				    );
				    $params=array( 
					  'status'=>"active",
					  'mobile_verification_date'=>FunctionsV3::dateNow()
					);
					$db = new DbExt();
					$db->updateData("{{client}}",$params,'client_id',$client_id);
					
					/*sent welcome email*/	    			
		    		FunctionsV3::sendCustomerWelcomeEmail($res);
				    
				} else $this->msg = $this->t("Verification code is invalid");
			} else $this->msg = $this->t("Records not found");
		} else $this->msg = $this->t("Invalid code");
		$this->output();
	}
	
	public function actionverificationEmail()
	{
		$code = isset($this->data['code'])?trim($this->data['code']):'';
		if(!empty($code)){
			$token = isset($this->data['token'])?$this->data['token']:'';
			if(empty($token)){
				$this->msg = $this->t("Token is empty");
				$this->output();
			}		
			if($res = SingleAppClass::getCustomerByToken($token,false)){				
				$client_id = $res['client_id'];				
				if ( $res['email_verification_code']==$code){
									
					$this->code=1;
				    $this->msg=t("Successful");
				    $this->details = array(
				      'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
				      'token'=>$token
				    );
				    $params=array( 
					  'status'=>"active",
					  'last_login'=>FunctionsV3::dateNow()
					);
					$db = new DbExt();
					$db->updateData("{{client}}",$params,'client_id',$client_id);
					
					/*sent welcome email*/	    			
		    		FunctionsV3::sendCustomerWelcomeEmail($res);
				    
				} else $this->msg = $this->t("Verification code is invalid");
			} else $this->msg = $this->t("Records not found");
		} else $this->msg = $this->t("Invalid code");		
		$this->output();
	}
	
	public function actiongetAppSettings()
	{
		$this->code = 1;
		$this->msg = "OK";	

		$lang = 'en';
			
		$mobile_prefix  = getOption($this->merchant_id,'singleapp_prefix');
		if(!empty($mobile_prefix)){
			$mobile_prefix = "+$mobile_prefix";
		} else $mobile_prefix="+1";
				
		$singleapp_default_lang = getOption($this->merchant_id,'singleapp_default_lang');		
		if(!empty($singleapp_default_lang)){
			$lang=$singleapp_default_lang;
		}
		
		$has_pts = '';
		if (FunctionsV3::hasModuleAddon("pointsprogram")){
			$points_enabled = getOptionA('points_enabled');
			if($points_enabled==1){
			   $has_pts=1;
			}
			
			if($has_pts==1){					
				$points_disabled_merchant_settings  = getOptionA('points_disabled_merchant_settings');
				if(empty($points_disabled_merchant_settings)){
					$mt_disabled_pts = getOption($this->merchant_id,'mt_disabled_pts');
					if($mt_disabled_pts==2){
						$has_pts='';
					}					
				}			
			}
		}
				
		$settings = array(		  		  
		  'terms_customer'=>getOptionA('website_terms_customer'),
		  'terms_customer_url'=>FunctionsV3::prettyUrl(getOptionA('website_terms_customer_url')),
		  'currency_set'=>getOptionA('admin_currency_set'),
		  'currency_symbol'=>getCurrencyCode(),
		  'currency_position'=>getOptionA('admin_currency_position'),
		  'currency_decimal_place'=>getOptionA('admin_decimal_place'),
		  'currency_space'=>getOptionA('admin_add_space_between_price'),
		  'currency_use_separators'=>getOptionA('admin_use_separators'),
		  'currency_decimal_separator'=>getOptionA('admin_decimal_separator'),
		  'currency_thousand_separator'=>getOptionA('admin_thousand_separator'),
		  'booking_disabled'=>getOptionA('merchant_tbl_book_disabled'),
		  'cod_change_required'=>getOptionA('cod_change_required'),
		  'disabled_website_ordering'=>getOptionA('disabled_website_ordering'),
		  'website_hide_foodprice'=>getOptionA('website_hide_foodprice'),
		  'enabled_map_selection_delivery'=>getOptionA('enabled_map_selection_delivery'),
		  'map_icon_pin'=>websiteUrl()."/protected/modules/singlemerchant/assets/images/icon_28.png",
		  'mobile_prefix'=>$mobile_prefix,
		  'lang'=>$singleapp_default_lang,
		  
		  'order_verification'=>getOption($this->merchant_id,'order_verification'),
		  'gallery_disabled'=>getOption($this->merchant_id,'gallery_disabled'),
		  'merchant_enabled_voucher'=>getOption($this->merchant_id,'merchant_enabled_voucher'),
		  'merchant_required_delivery_time'=>getOption($this->merchant_id,'merchant_required_delivery_time'),
		  'merchant_enabled_tip'=>getOption($this->merchant_id,'merchant_enabled_tip'),
		  'merchant_tip_default'=>getOption($this->merchant_id,'merchant_tip_default'),
		  'singleapp_location_accuracy'=>getOption($this->merchant_id,'singleapp_location_accuracy'),
		  'singleapp_enabled_fblogin'=>getOption($this->merchant_id,'singleapp_enabled_fblogin'),
		  
		  'singleapp_help_url'=>getOption($this->merchant_id,'singleapp_help_url'),
		  'singleapp_terms_url'=>getOption($this->merchant_id,'singleapp_terms_url'),
		  'singleapp_privacy_url'=>getOption($this->merchant_id,'singleapp_privacy_url'),
		  
		  'merchant_two_flavor_option'=>getOption($this->merchant_id,'merchant_two_flavor_option'),
		  'singleapp_enabled_banner'=>getOption($this->merchant_id,'singleapp_enabled_banner'),
		  'map_provider'=>FunctionsV3::getMapProvider(),
		  'map_country'=>FunctionsV3::getCountryCode(),
		  'geocomplete_default_country'=>getOptionA('google_default_country'),
		  'mapbox_access_token'=>getOptionA('mapbox_access_token'),
		  'mapbox_default_zoom'=>getOptionA('mapbox_default_zoom'),
		  'singleapp_enabled_google'=>getOption($this->merchant_id,'singleapp_enabled_google'),
		  'has_pts'=>$has_pts,
		  'disabled_cc_management'=>getOptionA('disabled_cc_management')
		);		
		
		if($settings['booking_disabled']!=2){
		   if(getOption($this->merchant_id,'merchant_table_booking')=="yes"){
		   	  $settings['booking_disabled']=2;
		   }		
		}	
		
		$settings['default_map_location']  = array(
		  'lat'=>getOption($this->merchant_id,'singleapp_default_lat'),
		  'lng'=>getOption($this->merchant_id,'singleapp_default_lng')
		);
		
		$settings['icons']=array(
		  'marker1'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/icon_28.png",
		  'marker2'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker_green.png",
		  'marker3'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker_orange.png",
		  'bicycle'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/bicycle.png",
		  'bike'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/bike.png",
		  'car'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/car.png",
		  'scooter'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/scooter.png",
		  'truck'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/truck.png",
		  'walk'=>websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/walk.png",
		);	
		$settings['marker_icon']=array(
		   $settings['icons']['marker1'],
		   $settings['icons']['marker2'],
		   $settings['icons']['marker3'],
		   websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker1.png",
		   websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker2.png",
		   websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker3.png",
		   websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/marker4.png",
		);
		
		if($settings['singleapp_enabled_banner']==1){		
			if($banner = SingleAppClass::getBannerLink($this->merchant_id)){
				$settings['singleapp_banner']=$banner;		
				
				$homebanner_interval = getOption($this->merchant_id,'singleapp_homebanner_interval');
			    $homebanner_auto_scroll = getOption($this->merchant_id,'singleapp_homebanner_auto_scroll');
			    $settings['homebanner_interval']=$homebanner_interval>0?$homebanner_interval:3000;
			    $settings['homebanner_auto_scroll']=$homebanner_auto_scroll>0?$homebanner_auto_scroll:0;
						
			} else $settings['singleapp_enabled_banner']=0;								
		}	
		
		if(empty($settings['currency_set'])){
			$settings['currency_set']='USD';
		}
		if(empty($settings['currency_position'])){
			$settings['currency_position']='left';
		}
		if(empty($settings['currency_decimal_place'])){
			$settings['currency_position']=2;
		}
		if(empty($settings['currency_decimal_separator'])){
			$settings['currency_decimal_separator']=".";
		}
		if($settings['currency_use_separators']=="yes"){
			if($settings['currency_thousand_separator']==""){
				$settings['currency_thousand_separator']=",";
			}		
		}	
		if($settings['order_verification']==2){
			$mechant_sms_enabled = getOptionA('mechant_sms_enabled');
			if($mechant_sms_enabled=="yes"){
				$settings['order_verification']='';
			}		
			$sms_balance=Yii::app()->functions->getMerchantSMSCredit($this->merchant_id);
			 if ( $sms_balance<=0){
			 	$settings['order_verification']='';
			 }		
		}				
		
		$reg_email = getOption($this->merchant_id,'singleapp_reg_email'); 
		$reg_mobile = getOption($this->merchant_id,'singleapp_reg_phone');
		
		if(empty($reg_email) && empty($reg_mobile)){
			$reg_email = 1;
			$reg_mobile = 1;
		}
		
		$settings['registration']=array(
		  'email'=>$reg_email,
		  'mobile'=>$reg_mobile,
		  'custom_field1'=>getOptionA('client_custom_field_name1'),
		  'custom_field2'=>getOptionA('client_custom_field_name2'),
		);
		
		$valid_token = false;
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(SingleAppClass::getCustomerByToken($token)){
			$valid_token = true;
		}
				
		$settings['valid_token'] = $valid_token;			
		$settings['remove_phone_prefix'] = getOption($this->merchant_id,'singleapp_remove_phone_prefix');
		
		$location_rep = SingleAppClass::searchMode();
		$settings['search_mode']=$location_rep['search_mode'];
		$settings['location_mode'] = $location_rep['location_mode'];
		
		
		$singleapp_startup = getOption($this->merchant_id,'singleapp_startup');		
		$singleapp_startup_auto_scroll = getOption($this->merchant_id,'singleapp_startup_auto_scroll');
		$singleapp_startup_interval = getOption($this->merchant_id,'singleapp_startup_interval');
		
		$settings['home']=array(
		  'startup_language'=>getOption($this->merchant_id,'singleapp_enabled_select_language'),
		  'startup_banner'=>!empty($singleapp_startup)?$singleapp_startup:1,
		  'startup_banner_auto'=>$singleapp_startup_auto_scroll==1?true:false,
		  'startup_banner_interval'=>$singleapp_startup_interval>0?$singleapp_startup_interval:3000,	  
		);						
		$settings['startup_banner_images'] = (array)SingleAppClass::getStartUpBanner($this->merchant_id);
				
		$settings['custom_pages'] = SingleAppClass::getTitlePages($this->merchant_id);
		
		$singleapp_rtl = getOption($this->merchant_id,'singleapp_rtl');
		$settings['is_rtl'] = $singleapp_rtl>0?$singleapp_rtl:0;
		
		$custom_pages_position = getOption($this->merchant_id,'singleapp_custom_pages_position');
		$settings['custom_pages_position'] = $custom_pages_position>0?$custom_pages_position:1;
		
		$cart_theme = getOption($this->merchant_id,'singleapp_cart_theme');		
		$settings['cart_settings']=array(
		  'theme'=>$cart_theme>0?$cart_theme:1,
		  'auto_address'=>getOption($this->merchant_id,'singleapp_cart_auto_address'),
		  'floating_category'=>getOption($this->merchant_id,'singleapp_floating_category')
		);
							
		$settings['banner'] = array(
		  'banner1'=>SingleAppClass::getImage('resto_banner.jpg','resto_banner.jpg')
		);
		
		$settings['tracking_interval_timeout'] = getOption($this->merchant_id,'singleapp_tracking_interval');
		
		if ($merchant_res = FunctionsV3::getMerchantInfo($this->merchant_id)){
			$info_address = clearString($merchant_res['complete_address']);
			$info_merchant_name = clearString($merchant_res['restaurant_name']);
			$settings['merchant_details'] = array(
			  'lat'=>trim($merchant_res['latitude']),
			  'lng'=>trim($merchant_res['lontitude']),
			  'info_window'=>"<h5>$info_merchant_name</h5><p>$info_address</p>",
			);
		}
		
		$menu_type = getOption($this->merchant_id,'singleapp_menu_type');
		$settings['menu_type'] = $menu_type>0?$menu_type:1;
				
		$settings['disabled_default_image'] = getOption($this->merchant_id,'singleapp_disabled_default_menu');
					
		$dict = SinglemerchantModule::$global_dict;
		$settings['dict'] = $dict;
		
		$settings['booking_tabs'] = SingleAppClass::BookingTabs();
		$settings['order_tabs'] = SingleAppClass::OrderTabs();
		$settings['contact_us']= SingleAppClass::ContactUsData($this->merchant_id);
		$settings['contact_us_enabled']= getOption($this->merchant_id,'singleapp_contactus_enabled');
		
		$settings['enabled_addon_desc']= getOption($this->merchant_id,'singleapp_enabled_addon_desc');
		$settings['confirm_future_order']= getOption($this->merchant_id,'singleapp_confirm_future_order');
				
		$settings['customer_forgot_password_sms'] = getOptionA('customer_forgot_password_sms');
		
		if ($device_info = SingleAppClass::getDeviceByUIID( $this->device_uiid )){
			$settings['subscribe_topic']= $device_info['subscribe_topic'];
		} else $settings['subscribe_topic'] = 1;
		
		$settings['topics'] = CHANNEL_TOPIC_MERCHANT.$this->merchant_id;
		
		$this->details = $settings;
		$this->output();
	}
	
	public function actionSendOrderSMSCode()
	{
		$db=new DbExt();
		$token = isset($this->data['token'])?$this->data['token']:'';
		$customer_number = isset($this->data['customer_number'])?$this->data['customer_number']:'';
		
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		$contact_phone = trim($res['contact_phone']);
				
		if(!empty($customer_number)){
			$contact_phone = trim($customer_number);
			$db->updateData("{{client}}",array(
			  'contact_phone'=>$customer_number
			),'client_id',$client_id);
		}	
				
		if(empty($contact_phone)){
			$this->msg = $this->t("We cannot send sms code to your phone number cause its empty. please fixed by putting mobile number into your profile");
			$this->output();
		}	
		
		$sms_balance=Yii::app()->functions->getMerchantSMSCredit($this->merchant_id);	
		if ( $sms_balance>=1){
			$code=FunctionsK::generateSMSOrderCode($contact_phone);
			$sms_msg= Yii::t("singleapp","Your order sms code is [code]",array(
			  '[code]'=>$code
			));			
						
			if($last = SingleAppClass::getLastOrderSMS($contact_phone)){				
				$date_now=date('Y-m-d g:i:s a');
				$date_created = date("Y-m-d g:i:s a",strtotime($last['date_created']));
				$date_diff=Yii::app()->functions->dateDifference($date_created,$date_now);				
				
				$order_sms_code_waiting = (integer) getOption($this->merchant_id,'order_sms_code_waiting');
				if($order_sms_code_waiting<=0){
					$order_sms_code_waiting = 5;
				}			
										
				$continue = true;	$waiting_time = '';		    						
				if(is_array($date_diff) && count($date_diff)>=1){				
					if($order_sms_code_waiting>$date_diff['minutes']){								
						$waiting_time = $date_diff['minutes'];
						$continue=false;
					}	
					if($continue==false){
						if($date_diff['days']>0){
							$continue=true;
						}					
						if($date_diff['hours']>0){
							$continue=true;
						}					
					}				
				}						
				
				if(!$continue){
					$waiting_time = (integer)$order_sms_code_waiting - (integer)$waiting_time;
					$this->msg = st("Your requesting too soon please wait after [time] minutes",array(
					  '[time]'=>$waiting_time
					));
					$this->output();
				}				    						
			}
			
			if ( $resp=Yii::app()->functions->sendSMS($contact_phone,$sms_msg)){	
							 
				$resp['msg']="process";
				$resp['raw']=mktime();
				
				 if ($resp['msg']=="process"){				 	 
				 	
				 	$sms_order_session = Yii::app()->functions->generateCode(50);
				 	
				 	$this->code=1;
				    $this->msg= Yii::t("singleapp","Your order sms code has been sent to [mobile]",array(
				     '[mobile]'=>$contact_phone
				    ));
				    
				    $this->details = array(
				      'sms_order_session'=>$sms_order_session
				    );				    				    
				    
				    $contact_phone = str_replace("+","",$contact_phone);
				    $params=array(
			    	  'mobile'=>trim($contact_phone),
			    	  'code'=>$code,
			    	  'session'=>$sms_order_session,
			    	  'date_created'=>FunctionsV3::dateNow(),
			    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
			    	);			    	
			    	$db->insertData("{{order_sms}}",$params);
			    	
			    	$params=array(
		        	  'merchant_id'=>$this->merchant_id,
		        	  'broadcast_id'=>"999999999",			        	  
		        	  'contact_phone'=>$contact_phone,
		        	  'sms_message'=>$sms_msg,
		        	  'status'=>$resp['msg'],
		        	  'gateway_response'=>$resp['raw'],
		        	  'date_created'=>FunctionsV3::dateNow(),
		        	  'date_executed'=>FunctionsV3::dateNow(),
		        	  'ip_address'=>$_SERVER['REMOTE_ADDR'],
		        	  'gateway'=>$resp['sms_provider']
		        	);	  		        	  
		        	$db->insertData("{{sms_broadcast_details}}",$params);	
				    
				 } else $this->msg=t("Sorry but we cannot send sms code this time")." ".$resp['msg'];
			} else $this->msg=$this->t("Sorry but we cannot send sms code this time. please try again later");
		} else $this->msg=$this->t("Sorry but this merchant does not have enought sms credit to send sms");		
		$this->output();
	}
	
	
	public function actionverifyOrderSMSCODE()
	{
		$order_sms_session = isset($this->data['order_sms_session'])?$this->data['order_sms_session']:'';	
		$sms_code = isset($this->data['sms_code'])?$this->data['sms_code']:'';	
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token,false)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		$contact_phone = $res['contact_phone'];
		
		if(!empty($order_sms_session)){
			if ($res = FunctionsK::validateOrderSMSCode($contact_phone,$sms_code,$order_sms_session)){
				$this->code = 1;
				$this->msg = "OK";
				$this->details='';
			} else 	$this->msg = $this->t("Invalid sms code");
		} else $this->msg = $this->t("sms session is empty");
		
		$this->output();
	}
	
	public function actionapplyTips()
	{
		if (!is_numeric($this->merchant_id)){
			$this->msg = $this->t("Invalid merchant id");
			$this->output();
		}
		
		$tips = isset($this->data['tips'])?$this->data['tips']:0;
		if ($tips>0){
			
			$data = array(
			  'delivery_type'=>isset($this->data['transaction_type'])?$this->data['transaction_type']:'',
			  'merchant_id'=>$this->merchant_id,
			  'card_fee'=>0
			);
			if ( $cart = SingleAppClass::getCartContent($this->device_uiid,$data)){			
				$params = array(
				  'tips'=>$tips,
				  'date_modified'=>FunctionsV3::dateNow()
				);
				$db = new DbExt();
				$db->updateData("{{singleapp_cart}}",$params,'device_id',$this->device_uiid);
				$this->code = 1;
				$this->msg = "OK";
			} else $this->msg = $this->t("cart not available");
						
		} else $this->msg = $this->t("Invalid tip");
		$this->output();
	}
	
	public function actionremoveTip()
	{
		SingleAppClass::removeTip($this->device_uiid);
		$this->code = 1;
		$this->msg="OK";
		$this->details='';		
		$this->output();
	}
	
	public function actiongetOrderHistory()
	{
		if ($client_id = $this->checkToken()){			
	        $order_id = isset($this->data['id'])?$this->data['id']:0;
	        $page_action =  isset($this->data['page_action'])?$this->data['page_action']:'';		
			if($order_id>0){
				if ($res = SingleAppClass::orderHistory($order_id)){
					$data =array();
					$p = new CHtmlPurifier();	
					
					foreach ($res as $val) {
				  
					  $remarks = $p->purify(clearString($val['remarks']));
					  if(!empty($val['remarks2'])){
						  $args=json_decode($val['remarks_args'],true);  
						  if(is_array($args) && count( (array) $args)>=1){
							 foreach ($args as $args_key=>$args_val) {
								$args[$args_key]=t($args_val);
							 }						 
							 $new_remarks=$val['remarks2'];
							 $remarks=Yii::t("driver","".$new_remarks,$args);	
						  }
					  }
					  
					  $data[]=array(
						'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
						'status_raw'=>$val['status'],
						'status'=>st($val['status']),
						'remarks'=>$remarks
					  );
				   }
				   
				   $order_info = SingleAppClass::orderDetails($order_id);		   	   
				   $order_info['merchant_name'] = clearString($order_info['merchant_name']);
				   $order_info['logo'] = $order_info['logo']=SingleAppClass::getImage($order_info['logo']);
				   $order_info['transaction'] = st("[trans_type] #[order_id]",array(
					'[trans_type]'=>t($order_info['trans_type']),
					'[order_id]'=>t($order_info['order_id']),
				   ));		   	   
				   $order_info['payment_type'] = st(FunctionsV3::prettyPaymentTypeTrans($order_info['trans_type'],$order_info['payment_type']));
				   
				   $this->code = 1;
				   $this->msg="OK";
				   $this->details = array(
					 'order_id'=>$order_id,
					 'show_track'=>SingleAppClass::showTrackOrder($order_id),
					 'page_action'=>$page_action,
					 'order_info'=>$order_info,
					 'data'=>$data,		   	    
				   );				
				} else {
					$this->code = 6;		
					$this->msg = $this->t("No results");							
					$this->details = array(
					  'title'=>st("No results"),
					  'sub_title'=>st("Order history is empty")
					);	
				}		
			} else {				
				$this->code = 6;		
				$this->msg = $this->t("invalid order id");							
				$this->details = array(
				  'title'=>st("Invalid order id"),
				  'sub_title'=>st("Order history is empty")
				);	
			}
		}
		$this->output();
	}
	
	public function actionReGetOrderHistory()
	{
		if ($client_id = $this->checkToken()){		
			$order_id = isset($this->data['order_id'])?$this->data['order_id']:0;
			if($order_id>0){
				if ($res = SingleAppClass::orderHistory($order_id)){
					$data =array();
					$p = new CHtmlPurifier();	
					foreach ($res as $val) {				  
					  $remarks = $p->purify(clearString($val['remarks']));
					  if(!empty($val['remarks2'])){
						  $args=json_decode($val['remarks_args'],true);  
						  if(is_array($args) && count( (array) $args)>=1){
							 foreach ($args as $args_key=>$args_val) {
								$args[$args_key]=t($args_val);
							 }						 
							 $new_remarks=$val['remarks2'];
							 $remarks=Yii::t("driver","".$new_remarks,$args);	
						  }
					  }
					  
					  $data[]=array(
						'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
						'status_raw'=>$val['status'],
						'status'=>st($val['status']),
						'remarks'=>$remarks
					  );
				   }
				   $this->code=1;
				   $this->details = array(					 
					 'show_track'=>SingleAppClass::showTrackOrder($order_id),					 
					 'data_count'=>count($data),
					 'data'=>$data,		   	    
				   );		
				} else $this->msg = $this->t("No results");
			} else {
				$this->code = 6;
				$this->msg = $this->t("invalid order id");
			}		
		}
		$this->output();
	}
	
	public function actiongetlanguageList()
	{
		$data = array();
		if ($lang_list=FunctionsV3::getLanguageList(false) ){	
			$enabled_lang=FunctionsV3::getEnabledLanguage();
			foreach ($lang_list as $val) {
				if (in_array($val,(array)$enabled_lang)){
					$data[$val]=st($val);
				}			
			}			
			$this->code=1;
			$this->msg = "OK";
			$this->details = array(
			  'page_action'=>isset($this->data['page_action'])?$this->data['page_action']:'',
			  'lang'=>Yii::app()->language,
			  'data'=>$data
			);
		} else {			
			$this->code = 6;
			$this->msg = $this->t("No available language");							
			$this->details = array(
			  'title'=>st("No available language"),
			  'sub_title'=>st("language not available")
			);									
		}
		$this->output();
	}	
	
	public function actiongetMobileCodeList()
	{
		$mobile_countrycode = require_once 'MobileCountryCode.php';
		$data = array();
		
		foreach ($mobile_countrycode as $key=>$val) {						
			$val['name']=ucwords(strtolower($val['name']));
			$val['country_code']=$key;
			$data[]=$val;			
		}
				
		$this->code=1;
		$this->msg="OK";
		$this->details = array(				  
		  'data'=>$data
		);
		$this->output();
	}
	
	public function actiontest()
	{
		$this->actiongetAppSettings();
	}
	
	public function actionloadNotification()
	{
		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $this->paginate_limit;
        } else  $page = 0;  
        
        $token = isset($this->data['token'])?$this->data['token']:'';
        $client_id='';
        if(!$res = SingleAppClass::getCustomerByToken($token)){        
        } else $client_id = $res['client_id'];
        
        $where="WHERE is_read='0'";
        $and='';
        
        if($client_id>0){
        	$and=" AND client_id = ".FunctionsV3::q($client_id)."  ";
        } else {
        	$and=" AND device_id = ".FunctionsV3::q($this->device_id)."  ";
        }
        
        $stmt="
        SELECT 
        push_title,
        push_message,
        date_created
        FROM {{singleapp_mobile_push_logs}}
        $where
        $and
        ORDER BY id DESC
        LIMIT $page,$this->paginate_limit
        ";
                
        $db = new DbExt();
        if ($res = $db->rst($stmt)){
        	$data = array();
        	foreach ($res as $val) {
        		$date_created = FunctionsV3::prettyDate($val['date_created']);
        		$date_created = Yii::app()->functions->translateDate($date_created);
        		$data[] = array(
        		  'push_title'=>clearString($val['push_title']),
        		  'push_message'=>clearString($val['push_message']),
        		  'date_created'=>$date_created
        		);
        	}
        	$this->code = 1;
        	$this->msg = "OK";
        	$this->details = array(
        	  'data'=>$data
        	);
        } else $this->msg = $this->t("No results");
		$this->output();
	}
	
	public function actionrequestForgotPass()
	{		
		$user_email = isset($this->data['user_email'])?$this->data['user_email']:'';
		if(empty($user_email)){
			$user_email = isset($this->data['user_mobile'])?$this->data['user_mobile']:'';
		}	
		if(!empty($user_email)){
			if ( $res=yii::app()->functions->isClientExist($user_email) ){
				$token=md5(date('c'));
				$params=array('lost_password_token'=>$token);		
				$db = new DbExt();
				if ($db->updateData("{{client}}",$params,'client_id',$res['client_id'])){
					
					$this->code=1;						
				    $this->msg= $this->t("We sent your forgot password link, Please follow that link. Thank You.");
				    
				    $to=$res['email_address'];
				    
				    //send email											
					$enabled=getOptionA('customer_forgot_password_email');
					if($enabled){
						$lang=Yii::app()->language; 
						$subject=getOptionA("customer_forgot_password_tpl_subject_$lang");
						if(!empty($subject)){
							$subject=FunctionsV3::smarty('firstname',
							isset($res['first_name'])?$res['first_name']:'',$subject);
							
							$subject=FunctionsV3::smarty('lastname',
							isset($res['last_name'])?$res['last_name']:'',$subject);
						}
													
						$tpl=getOptionA("customer_forgot_password_tpl_content_$lang") ;
						if (!empty($tpl)){								
							$tpl=FunctionsV3::smarty('firstname',
							isset($res['first_name'])?$res['first_name']:'',$tpl);
							
							$tpl=FunctionsV3::smarty('lastname',
							isset($res['last_name'])?$res['last_name']:'',$tpl);
							
							$tpl=FunctionsV3::smarty('change_pass_link',
							FunctionsV3::getHostURL().Yii::app()->createUrl('store/forgotpassword',array(
							  'token'=>$token
							))
							,$tpl);
							
							$tpl=FunctionsV3::smarty('sitename',getOptionA('website_title'),$tpl);
							$tpl=FunctionsV3::smarty('siteurl',websiteUrl(),$tpl);
						}
						if (!empty($subject) && !empty($tpl)){
							sendEmail($to,'',$subject, $tpl );
						}						
					}		

					
				} else $this->msg = $this->t("Cannot update records, please try again later");
			} else $this->msg = $this->t("Sorry but we cannot find your information");
		} else $this->msg = $this->t("Invalid username or email address");
		$this->output();
	}
	
	public function actionmapboxgeocode()
	{
		$this->actiongeocode();
	}
	
	public function actiongeoCode()
	{
		$lat = isset($this->data['lat'])?$this->data['lat']:'';
		$lng = isset($this->data['lng'])?$this->data['lng']:'';
		
		if(!empty($lat) && !empty($lng)){
			try {			 
								
			  if(method_exists('FunctionsV3','latToAdress')){
			      $res = FunctionsV3::latToAdress($lat,$lng);	
			  } else $res = SingleAppClass::latToAdress($lat,$lng);	
			  
			  $this->code = 1;
			  $this->msg  = "OK";	
			  $this->details = $res;
		    } catch (Exception $e) {
		      $this->msg =  $this->t($e->getMessage());
		    }			
		} else $this->msg = $this->t("Lat and long is required");
		$this->output();
	}
	
	public function actionupdateDeviceID()
	{
		
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			$this->output();
		}			
		$client_id = $res['client_id'];		
		$old_device_id = isset($this->data['old_device_id'])?$this->data['old_device_id']:'';			
		$device_id = isset($this->data['device_id'])?$this->data['device_id']:'';
		
		if(!empty($device_id)){
			$this->code = 1;
			$this->msg = 'Token updated';
			$params = array( 
			  'device_id'=>trim($device_id),
			  'device_platform'=>isset($this->data['device_platform'])?strtolower($this->data['device_platform']):'android',
			  'date_modified'=>FunctionsV3::dateNow(),
			  'single_app_merchant_id'=>$this->merchant_id,
			);
			$db = new DbExt();
			$db->updateData("{{client}}",$params,'client_id',$client_id);
			
			/*CHECK IF OLD DEVICE IS THE SAME AS NEW*/
			if(!empty($old_device_id)){
				if($old_device_id!=$device_id){
					$stmt="UPDATE
					{{singleapp_cart}}
					SET device_id =".FunctionsV3::q($device_id).",
					    device_platform = ".FunctionsV3::q($params['device_platform']).",
					    date_modified = ".FunctionsV3::q(FunctionsV3::dateNow())."
					    WHERE
					    device_id= ".FunctionsV3::q($old_device_id)."
					";					
					$db->qry($stmt);
				}		
			}
			
		} else $this->msg = $this->t("device id is empty");
		$this->output();
	}
	
	public function actionapplyRedeemPoints()
	{
		
		$points = isset($this->data['points'])?$this->data['points']:0;
		
		if($points>0.0001){		
		} else {
			$this->msg = SingleAppClass::t("Invalid redeem points");
			$this->output();
		}
				
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	
		$pts_disabled_redeem = getOptionA('pts_disabled_redeem');
		if($pts_disabled_redeem==1){
			$this->msg = SingleAppClass::t("Redeeming points is disabled");
			$this->output();
		}	
		
		/*CHECK POINTS BALANCE*/
		$available_points = PointsProgram::getTotalEarnPoints( $client_id , $this->merchant_id);
		if($available_points<=0){
			$this->msg = SingleAppClass::t("Sorry but your points is not enough");
			$this->output();
		}	
		
		if($points>$available_points){
			$this->msg = SingleAppClass::t("Sorry but your points is not enough");
			$this->output();
		}
		
	    $data = array(
		  'delivery_type'=>isset($this->data['transaction_type'])?$this->data['transaction_type']:'',
		  'merchant_id'=>$this->merchant_id,
		  'card_fee'=>0
		);					
		if ( $cart = SingleAppClass::getCartContent($this->device_uiid,$data)){		
			
			$is_disabled_merchant_settings = PointsProgram::isMerchantSettingsDisabled();
			
			/*CHECK IF HAS ALREADY DISCOUNT*/			
			$pts_enabled_offers_discount = getOptionA('pts_enabled_offers_discount');
			if(!$is_disabled_merchant_settings){
				$mt_pts_enabled_offers_discount = getOption($this->merchant_id,'mt_pts_enabled_offers_discount');
				if($mt_pts_enabled_offers_discount>0){					
					$pts_enabled_offers_discount = $mt_pts_enabled_offers_discount;
				}			
			}
			
			if($pts_enabled_offers_discount!=1){
				$discounted_amount = isset($cart['total']['discounted_amount'])?$cart['total']['discounted_amount']:0;			
				if($discounted_amount>0.0001){
					$this->msg = SingleAppClass::t("Sorry you cannot apply voucher, exising discount is alread applied in your cart");
					$this->output();
				}					
			}
			/*END CHECK IF HAS ALREADY DISCOUNT*/
			
			/*CHECK IF HAS ALREADY VOUCHER*/				
			$pts_enabled_add_voucher = getOptionA('pts_enabled_add_voucher');
			if(!$is_disabled_merchant_settings){
				$mt_pts_enabled_add_voucher= getOption($this->merchant_id,'mt_pts_enabled_add_voucher');
				if($mt_pts_enabled_add_voucher>0){
					$pts_enabled_add_voucher=$mt_pts_enabled_add_voucher;
				}			
			}
			
			if($pts_enabled_add_voucher!=1){
				$less_voucher = $cart['total']['less_voucher'];			
				if($less_voucher>0.0001){
				   $this->msg = SingleAppClass::t("Sorry but you cannot redeem points if you have already voucher applied on your cart");
				   $this->output();
				}					
			}
			/*END CHECK IF HAS ALREADY VOUCHER*/	
						
			$redeeming_point = getOptionA('pts_redeeming_point');
			$redeeming_point_value = getOptionA('pts_redeeming_point_value');
			
			if(!$is_disabled_merchant_settings){
				$mt_pts_redeeming_point = getOption($this->merchant_id,'mt_pts_redeeming_point');
				$mt_pts_redeeming_point_value = getOption($this->merchant_id,'mt_pts_redeeming_point_value');
				
				if($mt_pts_redeeming_point>0){
					$redeeming_point=$mt_pts_redeeming_point;
				}
				if($mt_pts_redeeming_point_value>0){
					$redeeming_point_value=$mt_pts_redeeming_point_value;
				}
			}	
			
			/*CHECK ABOVE ORDER*/
			$subtotal = isset($cart['total']['subtotal'])?$cart['total']['subtotal']:0;
			
			$points_apply_order_amt = getOptionA('points_apply_order_amt');
			if(!$is_disabled_merchant_settings){
				$mt_points_apply_order_amt = getOption($this->merchant_id,'mt_points_apply_order_amt');
				if($mt_points_apply_order_amt>0){
					$points_apply_order_amt=$mt_points_apply_order_amt;
				}			
			}
			
			if($points_apply_order_amt>0.0001){
				if($points_apply_order_amt>$subtotal){
					$this->msg = Yii::t("singleapp","Sorry but you can only redeem points on orders over [amount]",array(
					  '[amount]'=>FunctionsV3::prettyPrice($points_apply_order_amt)
					));
					$this->output();
				}			
			}								
			/*END CHECK ABOVE ORDER*/
			
			/*CHECK MINIMUM POINTS CAN BE USED*/
			$points_minimum = getOptionA('points_minimum');
			if(!$is_disabled_merchant_settings){
				$mt_points_minimum = getOption($this->merchant_id,'mt_points_minimum');
				if($mt_points_minimum>0){
					$points_minimum=$mt_points_minimum;
				}			
			}						
			if($points_minimum>0.0001){
				if($points_minimum>$points){
					$this->msg = Yii::t("singleapp","Sorry but Minimum redeem points can be used is [points]",array(
					  '[points]'=>$points_minimum
					));
					$this->output();
				}			
			}								
			/*END CHECK MINIMUM POINTS CAN BE USED*/
			
			
			/*CHECK MAXIMUM POINTS CAN BE USED*/
			$points_max = getOptionA('points_max');
			if(!$is_disabled_merchant_settings){
				$mt_points_max = getOption($this->merchant_id,'mt_points_max');
				if($mt_points_max>0.0001){
					$points_max=$mt_points_max;
				}			
			}
			
			if($points_max>0.0001){
				if($points_max<$points){
				   	$this->msg = Yii::t("singleapp","Sorry but Maximum redeem points can be used is [points]",array(
						  '[points]'=>$points_max
						));
					$this->output();
				}		
			}
			/*END CHECK MAXIMUM POINTS CAN BE USED*/
			
						
			$temp_redeem=intval($this->data['points']/$redeeming_point);
			$points_amount=$temp_redeem*$redeeming_point_value;
			
			/*CHECK IF SUB TOTAL WILL BE IN NEGATIVE*/			
			$new_balance = $subtotal-$points_amount;
			if($new_balance<=0){
				$this->msg = SingleAppClass::t("Sorry you cannot redeem points which the Sub Total will become negative when after applying the points");
				$this->output();
			}			

			$db = new DbExt();
			$params = array(
			  'points_apply'=>$this->data['points'],
			  'points_amount'=>$points_amount
			);
			$db->updateData("{{singleapp_cart}}",$params,'device_id',$this->device_uiid);
					
			$this->code = 1;
			$this->msg = "Succesful";
			$this->details = array(
			  'points_apply'=>$this->data['points'],
			  'points_amount'=>$points_amount,
			  'pretty_points_amount'=>FunctionsV3::prettyPrice($points_amount)
			);
			
		} else $this->msg = $this->t("Cart is empty");		
		$this->output();
	}
	
	public function actionremovePoints()
	{
		$DbExt=new DbExt;
    	$params = array(
    	  'date_modified'=>FunctionsV3::dateNow(),
    	  'points_apply'=>0,
    	  'points_amount'=>0
    	);
    	$DbExt->updateData("{{singleapp_cart}}",$params,'device_id',$this->device_uiid);	
    	
    	$this->code = 1;
		$this->msg="OK";
		$this->details='';
    	
		$this->output();
	}
	
	public function actionpointsSummary()
	{		
		if ( !FunctionsV3::hasModuleAddon('pointsprogram')){
			$this->msg = $this->t("points addon not installed");
    		$this->output();			
		}
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	
    	$total_available_pts = PointsProgram::getTotalEarnPoints($client_id);		
    	$total_expiring_pts = PointsProgram::getExpiringPoints($client_id);
    	$total_expenses = SingleAppClass::pointsTotalExpenses($client_id);
    	$total_earn_by_merchant = SingleAppClass::pointsEarnByMerchant($client_id);
    	
    	$data = array();
    	
    	$data[]=array(
    	  'label'=>$this->t("Income Points"),
    	  'value'=>$total_available_pts>0?$total_available_pts:0,
    	  'point_type'=>'income_points'
    	);
    	
    	$data[]=array(
    	  'label'=>$this->t("Expenses Points"),
    	  'value'=>$total_expenses>0?$total_expenses:0,
    	  'point_type'=>'expenses_points'
    	);
    	
    	$data[]=array(
    	  'label'=>$this->t("Expired Points"),
    	  'value'=>$total_expiring_pts>0?$total_expiring_pts:0,
    	  'point_type'=>'expired_points'
    	);
    	
    	$data[]=array(
    	  'label'=>$this->t("Points By Merchant"),
    	  'value'=>$total_earn_by_merchant,
    	  'point_type'=>'points_merchant'
    	);
    	
    	$this->code = 1;
    	$this->msg="OK";
    	$this->details=array(
    	  'page_action'=>isset($this->data['page_action'])?$this->data['page_action']:'',
    	  'data'=>$data
    	);
    	
		$this->output();
	}
	
	public function actionpointsGetEarn()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	$db=new DbExt();
		$stmt="
		SELECT * FROM
		{{points_earn}}
		WHERE
		status='active'
		AND
		client_id=".FunctionsV3::q($client_id)."
		ORDER BY id DESC
		LIMIT 0,1000
		";	
		if ( $res=$db->rst($stmt)){
			$data = array();
			foreach ($res as $val) {				
				$label=PointsProgram::PointsDefinition('earn',$val['trans_type'],$val['order_id']);
				$data[]=array(
				  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
				  'label'=>$label,
				  'points'=>$val['total_points_earn']
				);
			}
			$this->code = 1;
			$this->msg="OK";
			$this->details = array(
			  'data'=> $data
			);
		} else $this->msg="No results";
		$this->output();
	}
	
	public function actionpointsExpenses()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	
    	$db=new DbExt();
		$stmt="
		SELECT * FROM
		{{points_expenses}}
		WHERE
		status='active'
		AND
		client_id=".FunctionsV3::q($client_id)."
		ORDER BY id DESC
		LIMIT 0,1000
		";
		if ( $res=$db->rst($stmt)){
			$data = array();
			foreach ($res as $val) {				
				$label=PointsProgram::PointsDefinition($val['points_type'],$val['trans_type'],
				$val['order_id'],$val['total_points']);
				$data[]=array(
				  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
				  'label'=>$label,
				  'points'=>$val['total_points']
				);
			}
			$this->code = 1;
			$this->msg="OK";
			$this->details = array(
			  'data'=> $data
			);
		} else $this->msg="No results";
		$this->output();
	}

	public function actionpointsExpired()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	
    	$db=new DbExt();
		$stmt="
		SELECT * FROM
		{{points_earn}}
		WHERE
		status='expired'
		AND
		client_id=".FunctionsV3::q($client_id)."
		ORDER BY id DESC
		LIMIT 0,1000
		";
		if ( $res=$db->rst($stmt)){
			$data = array();
			foreach ($res as $val) {				
				
				$label=PointsProgram::PointsDefinition($val['points_type'],$val['trans_type'],
				$val['order_id'],$val['total_points_earn']);
				
				$data[]=array(
				  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
				  'label'=>$label,
				  'points'=>$val['total_points_earn']
				);
			}
			$this->code = 1;
			$this->msg="OK";
			$this->details = array(
			  'data'=> $data
			);
		} else $this->msg="No results";
		$this->output();
	}
	
	public function actionpointsEarnByMerchant()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$client_info = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = $this->t("Invalid token, please relogin again");
    		$this->output();
    	}    	
    	
    	$client_id = $client_info['client_id'];
    	
    	$DbExt=new DbExt; 
		$stmt="
		SELECT 
		a.merchant_id,
		b.restaurant_name,
		b.restaurant_slug
		FROM {{points_earn}} a		
		LEFT JOIN {{merchant}} b
		ON
		a.merchant_id=b.merchant_id		
		WHERE
		a.merchant_id <> 0
		and
		client_id=".FunctionsV3::q($client_id)."
		GROUP BY a.merchant_id		
		ORDER BY b.restaurant_name ASC
		";		
		if($res=$DbExt->rst($stmt)){
			$data = array();
			foreach ($res as $val) {
				$points = SingleAppClass::getTotalEarnPoints($client_id,$val['merchant_id']);
				$data[]=array(
				  'date'=>$val['restaurant_name'],
				  'label'=>SingleAppClass::t("Merchant Name"),
				  'points'=>$points>0?$points:0
				);
			}
			$this->code = 1;
			$this->msg="OK";
			$this->details = array(
			  'data'=> $data
			);
		} else $this->msg="No results";
		$this->output();
	}
	
	public function actiongetCountryList()
	{		
		$country_list = require_once('CountryCode.php');
		$this->code = 1;
		$this->msg="OK";
		$this->details  = array(
		  'counry_code'=>getOptionA('admin_country_set'),
		  'list'=>$country_list,
		);
		$this->output();
	}
	
	public function actionclearCart()
	{				
		SingleAppClass::clearCart($this->device_uiid); 
		$this->code = 1;
		$this->msg = "OK";
		$this->output();
	}
	
	public function actionsetDeliveryLocation()
	{
		if($resp=SingleAppClass::getCart($this->device_uiid,$this->merchant_id)){
			$id = $resp['cart_id'];
			$params = array(
			  'delivery_lat'=>trim($this->data['selected_lat']),
			  'delivery_long'=>trim($this->data['selected_lng']),
			);
			$db=new DbExt();
			$db->updateData("{{singleapp_cart}}",$params,'cart_id',$id);
			$this->code = 1;
			$this->msg = "OK";
			$this->details=array();
		} else $this->msg = $this->t("Cart is empty");
		$this->output();
	}
	
	public function actionsearchByCategory()
	{				
		$db = new DbExt();
		$db->qry("SET SQL_BIG_SELECTS=1");
		
		$search_str =  isset($this->data['category_name'])?trim($this->data['category_name']):'';		
		$merchant_id = $this->merchant_id;
		
		if($merchant_id>0){
			$stmt="SELECT merchant_id,cat_id, category_name,photo,category_name_trans
			FROM {{category}}
			WHERE
			category_name LIKE ".FunctionsV3::q("%".$search_str."%")."
			AND merchant_id = ".FunctionsV3::q($merchant_id)."
			LIMIT 0,10
			";						
			if($res = $db->rst($stmt)){
				$data = array();
				foreach ($res as $val) {
					$category_id = $val['cat_id'];
					$stmt2="
					SELECT count(*) as total
					FROM {{item}}
					WHERE merchant_id=".FunctionsV3::q($merchant_id)."
					AND
					category like ".FunctionsV3::q('%"'.$category_id.'"%')."
		            AND
		            status IN ('publish','published')
					";
					$total_found = 0;
					if($resp=$db->rst($stmt2)){						
						$total_found = $resp[0]['total'];
					}				
										
					$json = json_decode($val['category_name_trans'],true);					
										
					$category_name_trans = qTranslate($val['category_name'],'category_name',array(
					 'category_name_trans'=>$json
					));
					
					$category_name_orig = $category_name_trans;
					
					$category_name = SingleAppClass::highlight_word($category_name_trans,$search_str);
					
					$val['category_name']=$category_name;
					$val['category_name_orig']=$category_name_orig;
					$val['photo_url'] = SingleAppClass::getImage($val['photo']);
					$val['item_found']= Yii::t("mobile","[found] item",array(
					  '[found]' =>$total_found
					));
					$data[]=$val;
				}
								
				$this->code=1;
				$this->msg="OK";
				$this->details = array(
				  'list'=>$data
				);
			} else $this->msg = $this->t("no results");
		} else $this->msg = $this->t("invalid merchant id");
		$this->output();
	}
	
	public function actionsearchByItem()
	{	
		$db = new DbExt();
		
		$search_str =  isset($this->data['item_name'])?trim($this->data['item_name']):'';		
		$merchant_id = $this->merchant_id;		
		$category_id = isset($this->data['category_id'])?$this->data['category_id']:'';
		
		if(!empty($search_str) && !empty($merchant_id)){
			if($merchant_id>0){
				$stmt="SELECT
				item_id,merchant_id,item_name,item_description,photo,
				item_name_trans,item_description_trans,
				price, discount
				FROM {{item}}
				WHERE
				merchant_id=".FunctionsV3::q($merchant_id)."
				AND item_name LIKE ".FunctionsV3::q("%".$search_str."%")."			
				AND category like ".FunctionsV3::q('%"'.$category_id.'"%')."	
				LIMIT 0,10
				";
				if ($res = $db->rst($stmt)){
					$data = array();
					foreach ($res as $val) {
						
						$json = json_decode($val['item_name_trans'],true);						
						$item_name_trans = qTranslate($val['item_name'],'item_name',array(
						 'item_name_trans'=>$json
						));												
					    $item_name = SingleAppClass::highlight_word($item_name_trans,$search_str);
					    
					    $json = json_decode($val['item_description_trans'],true);						
						$item_description = qTranslate($val['item_description'],'item_description',array(
						 'item_description_trans'=>$json
						));
						
						$val['prices'] = SingleAppClass::getPrices($val['price'],$val['discount']);
												
					    $val['photo_url']=SingleAppClass::getImage($val['photo']);					
					    $val['item_name']=$item_name;
					    $val['item_description']=$item_description;
					    $val['category_id']=$category_id;
						$data[]=$val;
					}
													
					$this->code=1;
					$this->msg="OK";
					$this->details = array(
					  'list'=>$data
					);
				} else $this->msg = $this->t("no results");					
			} else $this->msg = $this->t("invalid merchant id");
		} else $this->msg = $this->t("no results");			
		$this->output();
	}	
	
	public function actionCancelOrder()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';		
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];
			$order_id = isset($this->data['order_id'])?$this->data['order_id']:'';
			if($order_id>0){
				if ($res = Yii::app()->functions->getOrderInfo($order_id)){
					if($res['client_id']== $client_id){
						$params = array(
	    				  'request_cancel'=>1,
	    				  'date_modified'=>FunctionsV3::dateNow(),
	    				  'ip_address'=>$_SERVER['REMOTE_ADDR']
	    				);
	    				$db = new DbExt();
	    				if ( $db->updateData("{{order}}",$params,'order_id',$order_id)){ 
	    					FunctionsV3::notifyCancelOrder($res);
	    					$this->code = 1;
			    			$this->msg = st("Your request has been sent to merchant");
			    			$this->details;
			    			
			    			/*logs*/
			    			$params_logs=array(
			    			  'order_id'=>$order_id,
			    			  'status'=>"cancel order request",
			    			  'date_created'=>FunctionsV3::dateNow(),
			    			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			    			);
			    			$db->insertData("{{order_history}}",$params_logs);
			    			
	    				} else $this->msg = st("ERROR: cannot update records.");
					}else $this->msg = st("Sorry but this order does not belong to you");
				} else $this->msg = st("Order id not found");
			} else $this->msg = st("invalid order id");
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");
		}
		$this->output();
	}
	
	public function actionPayAuthorize()
	{
        $token = isset($this->data['token'])?$this->data['token']:'';
        if($res = SingleAppClass::getCustomerByToken($token)){
        	
        	$client_id = $res['client_id'];
        	
        	$mtid = $this->merchant_id;
        	$mode_autho=Yii::app()->functions->getOption('merchant_mode_autho',$mtid);
            $autho_api_id=Yii::app()->functions->getOption('merchant_autho_api_id',$mtid);
            $autho_key=Yii::app()->functions->getOption('merchant_autho_key',$mtid);
            
            if (FunctionsV3::isMerchantPaymentToUseAdmin($mtid)){
				$mode_autho=Yii::app()->functions->getOptionAdmin('admin_mode_autho');
		        $autho_api_id=Yii::app()->functions->getOptionAdmin('admin_autho_api_id');
		        $autho_key=Yii::app()->functions->getOptionAdmin('admin_autho_key');        
			}
			
			if(empty($mode_autho) || empty($autho_api_id) || empty($autho_key)){
            	$this->msg=$this->t("Payment settings not properly configured");
			    $this->output();
		 	    Yii::app()->end();
            }
                        
            AuthorizePayWrapper::$mode = $mode_autho;     
            AuthorizePayWrapper::$api = $autho_api_id;
            AuthorizePayWrapper::$key = $autho_key; 
            
            $order_id = isset($this->data['order_id'])?$this->data['order_id']:'';
            $_GET['id'] = $order_id;
            require_once('buy.php');
            if(empty($error)){
	            
	            $params = array(
	              'total_w_tax'=>$amount_to_pay,
	              'cc_number'=>$this->data['credit_card_number'],
	              'expiration_month'=>$this->data['expiration_month'],
	              'expiration_yr'=>$this->data['expiration_yr'],
	              'cvv'=>$this->data['cvv'],
	              'paymet_desc'=>$payment_description,
	              'x_first_name'=>$this->data['first_name'],
	              'x_last_name'=>$this->data['last_name'],
	              'x_address'=>$this->data['address'],
	              'x_city'=>$this->data['city'],
	              'x_state'=>$this->data['state'],
	              'x_zip'=>$this->data['zip_code'],
	              'x_country'=>$this->data['country_code'],
	            );
	            	            
	            if($resp = AuthorizePayWrapper::Paynow($params, $client_id)){
	            	
	            	$payment_reference = $resp['payment_reference'];
	            	
	            	FunctionsV3::updateOrderPayment($order_id,"atz",
		    		  	  $payment_reference,$resp,$reference_id);		 
		    		  	     		  	 
		    		FunctionsV3::callAddons($order_id);
		    		
		    		/*SEND EMAIL RECEIPT*/
                    SingleAppClass::sendNotifications($order_id);
                    
                    /*CLEAR CART*/
	                SingleAppClass::clearCart($this->device_uiid); 
	                
	                $this->code = 1;
				    $this->msg = Yii::t("singleapp","Your order has been placed. Reference # [order_id]",array(
				      '[order_id]'=>$order_id
				    ));
				    
				    $this->details=array(
				      'order_id'=>$order_id,
				      'total_amount'=>$amount_to_pay,				      
				    );			
		    		  	  
	            } else $this->msg = AuthorizePayWrapper::$error;
	            
            } else $this->msg = $error;            
        } else {
			$this->code = 3;
			$this->msg = $this->t("token not found");
		}
		$this->output();   	    
	}
	
	public function actionLoginGoogle()
	{
		$db = new DbExt();
		$Validator=new Validator;		
		
		$Validator->required(array(
		  'email'=>$this->t("email address is required"),
		  'userid'=>$this->t("google user id is required")
		),$this->data);
		
		/*check if email address is blocked*/
    	if ( FunctionsK::emailBlockedCheck($this->data['email'])){
    		$Validator->msg[] = $this->t("Sorry but your email address is blocked by website admin");    		
    	}	 
    	
    	foreach ($this->data as $key => $val) {
    		if($val=="null" || $val==null ){
    			$this->data[$key]='';
    		} else $this->data[$key]=$val;
    	}
    	    	
    	if($Validator->validate()){
    		$p = new CHtmlPurifier();			
    		$params=array(
    		  'first_name'=>$p->purify($this->data['fullname']),
    		  'last_name'=>$p->purify($this->data['lastname']),
    		  'email_address'=>$p->purify($this->data['email']),
    		  'password'=>md5($this->data['userid']),
    		  'date_created'=>FunctionsV3::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],    		  
    		  'device_id'=>isset($this->data['device_id'])?$this->data['device_id']:'',
    		  'device_platform'=>isset($this->data['device_platform'])?strtolower($this->data['device_platform']):'',
    		  'social_strategy'=>'google_mobile',
    		  'single_app_merchant_id'=>$this->merchant_id,
    		  'enabled_push'=>1,
    		  'single_app_device_uiid'=>$this->device_uiid
    		);
    		
    		if( $res = Yii::app()->functions->isClientExist($params['email_address']) ){
    			$token = $res['token'];
    			if(empty($token)){
    				$token = SingleAppClass::generateUniqueToken(15,$params['userid']);
    				$params['token'] = $token;
    			}    		
    			
    			unset($params['date_created']);
    			$params['last_login']=FunctionsV3::dateNow();
    			
    			$db->updateData("{{client}}",$params,'client_id',$res['client_id']);			
    			
    			$this->code=1;
	    		$this->msg = $this->t("Registration successful");
	    		
	    		$this->details = array(
    			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
    			  'token'=>$token
    			);
    		} else {
    			$token = SingleAppClass::generateUniqueToken(15,$params['email_address']);
    			$params['token']=$token;
    			if ( $db->insertData("{{client}}",$params)){
    				$customer_id =Yii::app()->db->getLastInsertID();	    		
	    		    $this->code=1;
	    		    $this->msg = $this->t("Registration successful");
    				
	    		    $this->details = array(
	    			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
	    			  'token'=>$token
	    			);
	    			
	    			/*POINTS PROGRAM*/	    			
		    	    if (FunctionsV3::hasModuleAddon("pointsprogram")){
		    		    PointsProgram::signupReward($customer_id);
		    	    }
		    	    
    			} else $this->msg = $this->t("Something went wrong during processing your request. Please try again later");
    		}    	
    	
    	} else $this->msg = SingleAppClass::parseValidatorError($Validator->getError());
    	
		$this->output();
	}
	
	public function actionUploadProfile()
    {    	
    	$profile_photo = '';
    	$path_to_upload= FunctionsV3::uploadPath();
    	
    	$token = isset($this->data['token'])?$this->data['token']:'';
    	if($res = SingleAppClass::getCustomerByToken($token)){
    		$client_id = $res['client_id'];
    		
    		if(isset($_FILES['file'])){
    			
    		   header('Access-Control-Allow-Origin: *');
	    	
		       $new_image_name = urldecode($_FILES["file"]["name"]).".jpg";	
		       $new_image_name=str_replace(array('?',':'),'',$new_image_name);
		        
		       $upload_res = @move_uploaded_file($_FILES["file"]["tmp_name"], "$path_to_upload/".$new_image_name);

			   if($upload_res){
			        $DbExt=new DbExt;	  
			      	
			        $params = array(
			          'avatar'=>$new_image_name,
			          'date_modified'=>FunctionsV3::dateNow(),
			          'ip_address'=>$_SERVER['REMOTE_ADDR']
			        );
			        
			        if($DbExt->updateData("{{client}}",$params,'client_id',$client_id)){
			        	$this->code=1;
						$this->msg=self::t("Upload successful");
						$this->details=$new_image_name;
						$profile_photo = SingleAppClass::getImage($new_image_name);
			        } else $this->msg = self::t("Cannot update records");
			    } else $this->msg = self::t("Cannot upload file");
    			
    		} else $this->msg=$this->t("Image is missing");
    		
    	} else {
    		$this->code = 3;
			$this->msg = $this->t("token not found");			
    	}   
    	
    	echo "$this->code|$this->msg|$profile_photo";
    	Yii::app()->end();    	
    }
	
    public function actiongetTrackOrderData()
    {
    	$order_id = isset($this->data['order_id'])?$this->data['order_id']:0;
    	if($order_id>0){
    		if ($res = SingleAppClass::TrackOrderData($order_id)){
    			
    			$transport_type = $res['transport_type_id'];    			    		
    			if(empty($transport_type)){
    				$transport_type='car';
    			}    		
    			$driver_photo = SingleAppClass::getDriverPhoto($res['profile_photo']);
    			    			
    			$data = array(
    			  'task_id'=>$res['task_id'],
    			  'order_id'=>$res['order_id'],
    			  'trans_type'=>$res['trans_type'],
    			  'status_raw'=>$res['status'],
    			  'status'=>Driver::t($res['status']),
    			  'rating' => $res['rating'],
    			  'customer'=>array(
    			    'contact_number'=>$res['contact_number'],
    			    'email_address'=>$res['email_address'],
    			    'customer_name'=>$res['customer_name'],
    			  ),
    			  'task_info'=>array(    			   
    			    'address'=>$res['delivery_address'],
    			    'lat'=>$res['task_lat'],
    			    'lng'=>$res['task_lng'],
    			  ),
    			  'dropoff_info'=>array(
    			    'address'=>$res['drop_address'],
    			    'lat'=>$res['dropoff_lat'],
    			    'lng'=>$res['dropoff_lng'],
    			  ),
    			  'driver_info'=>array(
    			     'driver_id'=>$res['driver_id'],
    			     'driver_name'=>ucfirst($res['driver_name']),
    			     'licence_plate'=>$res['licence_plate'],
    			     'transport_type'=>$res['transport_type_id'],
    			     'phone'=>$res['phone'],
    			     'email'=>$res['email'],
    			     'lat'=>$res['driver_location_lat'],
    			     'lng'=>$res['driver_location_lng'],
    			     'photo'=>$driver_photo
    			  ),
    			  'icons'=>array(
    			    'destination'=>websiteUrl()."/protected/modules/singlemerchant/assets/images/marker_green.png",
    			    'dropoff'=>websiteUrl()."/protected/modules/singlemerchant/assets/images/marker_orange.png",
    			    'driver'=>websiteUrl()."/protected/modules/singlemerchant/assets/images/$transport_type.png",
    			  )
    			);
    			
    			$this->code=1; $this->msg = "ok"; $this->details=$data;
    		} else $this->msg = st("no tracking details found for Order ID#[order_id]",array('[order_id]'=>$order_id));
    	} else $this->msg = $this->t("invalid order id");
    	$this->output();
    }
    
    public function actiontrackDriver()
    {    	
    	$order_id = isset($_POST['order_id'])?$_POST['order_id']:'';    	
    	if($order_id>0){
    		if (FunctionsV3::hasModuleAddon("driver")){
    		   if ( $res = SingleAppClass::TrackOrderData($order_id)){    		   	        		   	    
    		   	    $data = array(
    		   	      'task_id'=>$res['task_id'],
    		   	      'status_raw'=>$res['status'],
    		   	      'status'=>st($res['status']), 
    		   	      'order_id'=>$res['order_id'],
    		   	      'rating'=>$res['rating'],
    		   	      'driver_name'=>$res['driver_name'],
    		   	      'driver_email'=>$res['email'],
    		   	      'driver_phone'=>$res['phone'],    		   	      
    		   	      'profile_photo'=>SingleAppClass::getDriverPhoto($res['profile_photo']),
    		   	      'location_lat'=>$res['driver_location_lat'],
    		   	      'location_lng'=>$res['driver_location_lng'],    		   	      
    		   	    );    		   	    
    		   	    $this->code = 1;
    		   	    $this->msg = "OK";
    		   	    $this->details = array(
    		   	      'data'=>$data
    		   	    );    		   	    
    		   } else $this->msg = st("Task not found");
    		} else $this->msg = $this->t("addon not found") ;
    	} else $this->msg = $this->t("invalid driver id") ;
    	$this->output();
    }

    public function actiongetPages()
    {    	
    	$data=array();
    	$enabled_multiple_translation = getOptionA('enabled_multiple_translation');
    	$lang = $this->data['lang']?$this->data['lang']:'';        	
    	if ($res = SingleAppClass::getPages($this->merchant_id)){
    		foreach ($res as $val) {    			    			
    			if ($enabled_multiple_translation==2 && !empty($lang)){    	    				
    				$title = $val['title'];
    				$field_title="lang_title_$lang";    				
    				if(array_key_exists($field_title,$val)){
    					if(!empty($val[$field_title])){
    					   $title=$val[$field_title]; 
    					}
    				} 
    				$data[]= array(
    				  'page_id'=>$val['page_id'],
    				  'merchant_id'=>$val['merchant_id'],
    				  'title'=>$title,  
    				  'icon'=>$val['icon'],
    				);
    			} else {
    				$data[]= array(
    				  'page_id'=>$val['page_id'],
    				  'merchant_id'=>$val['merchant_id'],
    				  'title'=>$val['title'],    				  
    				  'icon'=>$val['icon'],
    				);
    			}    		
    		}
    		$this->code = 1;
    		$this->msg = "ok";
    		$this->details = array(
    		  'data'=>$data
    		);
    	} else $this->msg = "no results";
    	$this->output();
    }
    
    public function actiongetPagesByID()
    {
       $data=array();
       $enabled_multiple_translation = getOptionA('enabled_multiple_translation');
       $lang = $this->data['lang']?$this->data['lang']:'';        	
    	
       $page_id = isset($this->data['page_id'])?$this->data['page_id']:0;
       $merchant_id = $this->merchant_id;
       if($page_id>0){
       	  if ( $val = SingleAppClass::getPagesByID($page_id)){
       	  	  $content = '';
       	  	         	  	         	  	 
       	  	  if($val['use_html']==1){
			     $content=nl2br(strip_tags($val['content']));
			  } else $content=trim($val['content']);
       	  	  
       	  	  if ($enabled_multiple_translation==2 && !empty($lang)){
       	  	  	$title = $val['title'];
				$field_title="title_$lang";   
				$field_content="content_$lang"; 
						 				
				if(array_key_exists($field_title,$val)){
					if(!empty($val[$field_title])){
					   $title=$val[$field_title]; 
					}
				} 
				
				if(array_key_exists($field_content,$val)){
					if(!empty($val[$field_content])){
					   $content=$val[$field_content]; 
					   
					   if($val['use_html']==1){
						  $content=nl2br(strip_tags($content));
					   } else $content=trim($content);
					}
				} 
				
				$data = array(
				  'page_id'=>$val['page_id'],
				  'merchant_id'=>$val['merchant_id'],
				  'title'=>$title,  
				  'content'=>$content,  
				  'icon'=>$val['icon'],
				);
       	  	  } else {
       	  	  	$data = array(
				  'page_id'=>$val['page_id'],
				  'merchant_id'=>$val['merchant_id'],
				  'title'=>$val['title'],    				  
				  'content'=>$content,  
				  'icon'=>$val['icon'],
				);
       	  	  }       	  
       	  	  
       	  	  $this->code = 1;
	    	  $this->msg = "ok";
	    	  $this->details = array(
	    		  'data'=>$data
	    	  );
    		
       	  } else {
       	  	$this->code = 6; 
       	  	$this->msg = $this->t("Page not found");
       	  	$this->details = array(
			  'title'=>st("Page not found"),
			  'sub_title'=>st("Sorry but we cannot find what your looking for")
			);	
       	  }       
       } else {
       	   $this->code = 6; 
       	   $this->msg = $this->t("Invalid page id");
       	   $this->details = array(
			  'title'=>st("Invalid page id"),
			  'sub_title'=>st("Sorry but we cannot find what your looking for")
			);	
       }
       $this->output();
    }
    
    public function actionclearNotification()
    {
    	$token = isset($this->data['token'])?$this->data['token']:'';
    	$id = isset($this->data['id'])?$this->data['id']:'';
        $client_id='';
        if(!$res = SingleAppClass::getCustomerByToken($token)){        
        } else $client_id = $res['client_id'];
        
        
        $and='';
        if($id>0){
        	$and.=" AND id=".FunctionsV3::q($id)." ";
        }    
        
        if($client_id>0){
        	$stmt="UPDATE {{singleapp_mobile_push_logs}}
        	SET is_read='1'
        	WHERE
        	client_id=".FunctionsV3::q($client_id)."
        	$and
        	";
        } else {
        	$stmt="UPDATE {{singleapp_mobile_push_logs}}
        	SET is_read='1'
        	WHERE
        	device_id=".FunctionsV3::q($this->device_id)."
        	$and
        	";
        }    
                
        $db=new DbExt();
        $db->qry($stmt);
        $this->code = 1;
        $this->msg = "OK";
        $this->details = '';
        
    	$this->output();
    }
    
    public function actionaddReview()
    {    	
    	$db = new DbExt();
    	
    	$token = isset($this->data['token'])?$this->data['token']:'';
    	if(!$res = SingleAppClass::getCustomerByToken($token)){
    		$this->msg = t("Sorry but you need to login to write a review.");
    		$this->output();
    	}
    	
    	$client_id = $res['client_id'];    	     	
    	$order_id =  isset($this->data['review_order_id'])?$this->data['review_order_id']:'';    	
    	
    	//if($order_id>0){    		
	    	$website_review_type = getOptionA('website_review_type');
	    	if ($website_review_type==2){    	
	    	    if($order_id>0){    		
		    		if ( $order_info=Yii::app()->functions->getOrder($order_id)){
		    			$params = array(
						  'merchant_id'=>$order_info['merchant_id'],
						  'client_id'=>$client_id,
						  'review'=>$this->data['review'],
						  'rating'=>$this->data['rating'],
						  'date_created'=>FunctionsV3::dateNow(),
						  'ip_address'=>$_SERVER['REMOTE_ADDR'],
						  'order_id'=>$order_id,				  
						);
						
						if(method_exists('FunctionsV3','getReviewBasedOnStatus')){
						   $params['status']=FunctionsV3::getReviewBasedOnStatus($order_info['status']);
					    }				    
					    
					    if(!FunctionsV3::getReviewByOrder($client_id,$order_id)){
					    	if ( $db->insertData("{{review}}",$params)){
					    		$review_id=Yii::app()->db->getLastInsertID();
					    		
					    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
									if (method_exists('PointsProgram','addReviewsPerOrder')){
										PointsProgram::addReviewsPerOrder($order_id,
										$client_id,$review_id,$order_info['merchant_id'],$order_info['status']);
									}			
								}	
								
								$this->code = 1;
						        $this->msg = st("Your review has been published.");
						        $this->details = array(
								  'tab'=>2
								);
					    		
					    	} else $this->msg = t("ERROR. cannot insert data.");
					   } else $this->msg = t("You have already have add review to this order");
						
		    		} else $this->msg = $this->t("Order id not found");
	    		} else $this->msg = $this->t("Invalid order id");
	    	} else {
	    			    		
	    		$functionk=new FunctionsK();
	    		
	    		if ( Yii::app()->functions->getOptionAdmin('website_reviews_actual_purchase')=="yes"){	    			
	    			if (!$functionk->checkIfUserCanRateMerchant($client_id,$this->merchant_id)){
		    	    	$this->msg= st("Reviews are only accepted from actual purchases!");
		    	    	$this->output();
		    	    }
		    	    if (!$functionk->canReviewBasedOnOrder($client_id,$this->merchant_id)){
		    		   $this->msg= st("Sorry but you can make one review per order");
		    	       $this->output();
		    	    }	 
	    		}
	    			    		
	    		$params = array(
				  'merchant_id'=>$this->merchant_id,
				  'client_id'=>$client_id,
				  'review'=>$this->data['review'],
				  'rating'=>$this->data['rating'],
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				  'order_id'=>$order_id,				  
				);			
				
				if ( $ref_orderid=$functionk->reviewByLastOrderRef($client_id,$this->merchant_id)){
	    	    	$params['order_id']=$ref_orderid;
	    	    }			
				
				if ( $db->insertData("{{review}}",$params)){
					$review_id  = $this->details=Yii::app()->db->getLastInsertID();
					$this->code = 1;
					$this->msg = st("Your review has been published.");
					$this->details = array(
					  'tab'=>1
					);
					
					/*POINTS PROGRAM*/		    		
		    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
		    		   PointsProgram::reviewsReward($client_id , $review_id  , $this->merchant_id );
		    		}
		    		
				} else $this->msg = st("ERROR: cannot insert records.");
	        }
        //} else $this->msg = $this->t("Invalid order id");
    	$this->output();
    }
	
    public function actiongetAllCategory()
    {
    	if ( $resp = SingleAppClass::getCategory($this->merchant_id, 0 ,0, true)){
			$this->code = 1; $this->msg = 'OK';  
			$this->details = array('data'=>$resp);
		} else $this->msg = SingleAppClass::t("This restaurant has not published their menu yet");
    	$this->output();
    }
    
    private function checkToken()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if(!$res = SingleAppClass::getCustomerByToken($token)){
			$this->code = 3;
			$this->msg = $this->t("token not found");
			return false;
		}			
		$client_id = $res['client_id'];	
		return $client_id;
		
	}
	
	public function actionPointsDetails()
	{
		
        if ( !FunctionsV3::hasModuleAddon('pointsprogram')){
			$this->msg = $this->t("points addon not installed");
    		$this->output();			
		}
		
		$limit=''; $stmt=''; $page_title='';
		if ($client_id = $this->checkToken()){
			$point_type = isset($this->data['point_type'])?$this->data['point_type']:'';
			
			$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
	        
	        $limit="LIMIT $page,$pagelimit"; 
			
			switch ($point_type) {
				case "income_points":					
					$page_title = $this->t("Income Points");					
					$stmt="
					SELECT SQL_CALC_FOUND_ROWS 
					a.trans_type,
					a.order_id,
					a.date_created,
					a.total_points_earn
					FROM
					{{points_earn}} a
					WHERE
					status='active'
					AND
					client_id=".FunctionsV3::q($client_id)."
					ORDER BY id DESC
					$limit
					";
					break;
					
				case "expenses_points":					
					$page_title = $this->t("Expenses Points");					
					$stmt="
					SELECT SQL_CALC_FOUND_ROWS 
					a.points_type,
					a.trans_type,
					a.order_id,
					a.total_points,
					a.date_created
					FROM
					{{points_expenses}} a
					WHERE
					status='active'
					AND
					client_id=".FunctionsV3::q($client_id)."
					ORDER BY id DESC
					$limit
					";					
					break;
					
				case "expired_points":	
				
				    $page_title = $this->t("Expired Points");
					
					$stmt="
					SELECT SQL_CALC_FOUND_ROWS 
					a.points_type,
					a.trans_type,
					a.order_id,
					a.date_created,
					a.total_points_earn
					FROM
					{{points_earn}} a
					WHERE
					status='expired'
					AND
					client_id=".FunctionsV3::q($client_id)."
					ORDER BY id DESC
					$limit					
					";		
				
				   break;
				   
				case "points_merchant":
					
					$page_title = $this->t("Points By Merchant");
					
					$stmt="
					SELECT SQL_CALC_FOUND_ROWS 
					a.merchant_id,
					b.restaurant_name,
					b.restaurant_slug
					FROM {{points_earn}} a		
					LEFT JOIN {{merchant}} b
					ON
					a.merchant_id=b.merchant_id		
					WHERE
					a.merchant_id <> 0
					and
					client_id=".FunctionsV3::q($client_id)."
					GROUP BY a.merchant_id		
					ORDER BY b.restaurant_name ASC
					$limit					
					";							
					break;
			}
			
			$data = array();
			$db=new DbExt();
			
			if(isset($_GET['debug'])){
				dump($stmt);
			}		
			
			if($res = $db->rst($stmt)){
				foreach ($res as $val) {
					switch ($point_type) {
						case "income_points":					    
							$label=PointsProgram::PointsDefinition('earn',$val['trans_type'],$val['order_id']);
							$data[]=array(
							  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
							  'label'=>$label,
							  'points'=>$val['total_points_earn']
							);
							break;
							
						case "expenses_points":	
							$label=PointsProgram::PointsDefinition($val['points_type'],$val['trans_type'],
							$val['order_id'],$val['total_points']);
							$data[]=array(
							  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
							  'label'=>$label,
							  'points'=>$val['total_points']
							);
						  break;
						  
						case "expired_points":
							$label=PointsProgram::PointsDefinition($val['points_type'],$val['trans_type'],
							$val['order_id'],$val['total_points_earn']);
							
							$data[]=array(
							  'date'=>FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']),
							  'label'=>$label,
							  'points'=>$val['total_points_earn']
							);  
							break;  
					
						case "points_merchant":	
						    
						    $points = SingleAppClass::getTotalEarnPoints($client_id,$val['merchant_id']);
							$data[]=array(
							  'date'=>clearString($val['restaurant_name']),
							  'label'=>$this->t("Merchant Name"),
							  'points'=>$points>0?$points:0
							);
						    break;
						    
						default:
							break;
					}
				}
				
				$this->code = 1; $this->msg = "ok";
				$this->details = array(
				  'page_action'=>$page_action,
				  'page_title'=>$page_title,
				  'data'=>$data
				);
				
			} else {
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {
					$this->code = 6;
					$this->details = array(
					  'title'=>st("[points_type] is empty",array(
					   '[points_type]'=>$page_title
					  )),
					  'sub_title'=>st("Make your first order to earn points")
					);	
				}
			}		
			
		}
		$this->output();
	}
	
	public function actionFavoritesList()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		if ($client_id = $this->checkToken()){
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 
	
	        $paginate_total=0; 
	        $limit="LIMIT $page,$pagelimit"; 

	        $db = new DbExt();
			$stmt="
			SELECT SQL_CALC_FOUND_ROWS 
			a.id,
			a.merchant_id,
			a.client_id,
			a.date_created,
			b.restaurant_name as merchant_name,
			b.logo
			
			FROM
			{{favorites}} a
			left join {{merchant}} b
	        ON
	        a.merchant_id = b.merchant_id
	                
			WHERE a.client_id=".FunctionsV3::q($client_id)."
					
			ORDER BY a.id DESC
			$limit
			";		
			
			if(isset($_GET['debug'])){
				dump($stmt);
			}
		
			if($res = $db->rst($stmt)){
				
				$total_records=0;
				$stmtc="SELECT FOUND_ROWS() as total_records";
				if ($resp=$db->rst($stmtc)){			 			
					$total_records=$resp[0]['total_records'];
				}					
				$paginate_total = ceil( $total_records / $pagelimit );
				
				$data = array();
				foreach ($res as $val) {										
					$date_added = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
					$val['date_added']= st("Added [date]",array(
					  '[date]'=>$date_added
					));
					$val['logo']=SingleAppClass::getImage($val['logo']);
					
					$ratings = Yii::app()->functions->getRatings($val['merchant_id']);
					
					$ratings['review_count'] = st("[count] reviews",array(
		 			  '[count]'=>$ratings['votes']
		 			));
		 			$val['rating']=$ratings;
		 			
		 			$val['background_url'] = SingleAppClass::getMerchantBackground($val['merchant_id'],'resto_banner.jpg');
		 			
					$data[]=$val;
				}
				
				$this->code = 1;
				$this->msg="OK";
				$this->details = array( 
				  'page_action'=>isset($this->data['page_action'])?$this->data['page_action']:'',
				  'paginate_total'=>$paginate_total,
				  'data'=>$data
				);				
			} else {				
				if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {			
					$this->code = 6;
					$this->details = array(
					  'title'=>st("Your Favorites is empty"),
					  'sub_title'=>st("Add your own favorite restaurant")
					);	
				}
			}        
		}
		$this->output();
	}
	
	public function actionGetOrderInfo()
	{
		$data = array();
		$order_id = isset($this->data['order_id'])?$this->data['order_id']:0;
		if($order_id>0){
		   if ($res = SingleAppClass::orderDetails($order_id)){
		   	  
		   	  $res['review_as']='';
		   	  if($clien_info =  Yii::app()->functions->getClientInfo($res['client_id'])){
		   	  	 $res['review_as'] = st("Review as [customer_name]",array(
				   '[customer_name]'=>$clien_info['first_name']
				 ));
		   	  }		   
		   	  $this->code = 1;
		   	  $this->msg = "ok";
		   	  
		   	  $res['logo'] = $res['logo']=SingleAppClass::getImage($res['logo']);
		   	  
		   	  $res['transaction'] = st("[trans_type] #[order_id]",array(
		   	    '[trans_type]'=>t($res['trans_type']),
				'[order_id]'=>t($res['order_id']),
		   	  ));
		   	  
		   	  $res['payment_type'] = st(FunctionsV3::prettyPaymentTypeTrans($res['trans_type'],$res['payment_type']));
		   	  $res['merchant_name'] = clearString($res['merchant_name']);
		   	  
		   	  $this->details = array(
		   	    'data'=>$res
		   	  );
		   } else $this->msg = $this->t("order not found");		
		} else $this->msg = $this->t("invalid order id");		
		$this->output();
	}
	
	public function actionGetOrderInfoCancel()
	{
		$this->actionGetOrderInfo();
	}
	
	public function actionaddReviewNew()
	{		
		$db = new DbExt();
		$order_id =  isset($this->data['order_id'])?$this->data['order_id']:''; 
		$rating =  isset($this->data['rating'])?$this->data['rating']:''; 
		
		if(!is_numeric($rating)){
			$this->msg = $this->t("Please select rating");
			$this->output();
		}
		if(!is_numeric($order_id)){
			$this->msg = $this->t("invalid order id");
			$this->output();
		}
		
		if ($client_id = $this->checkToken()){
			$website_review_type = getOptionA('website_review_type');				
		    if($order_info=Yii::app()->functions->getOrderInfo($order_id)){
		    	if ($website_review_type==2){														
					$order_id = $order_info['order_id'];
					$params = array(
					  'merchant_id'=>$order_info['merchant_id'],
					  'client_id'=>$client_id,
					  'review'=>$this->data['review'],
					  'rating'=>$this->data['rating'],
					  'as_anonymous'=>isset($this->data['as_anonymous'])?$this->data['as_anonymous']:0,
					  'date_created'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR'],
					  'order_id'=>$order_id,  
					);
					if(method_exists('FunctionsV3','getReviewBasedOnStatus')){
					   $params['status']=FunctionsV3::getReviewBasedOnStatus($order_info['status']);
				    }
				    				    
				    if(!$res_review = FunctionsV3::getReviewByOrder($client_id,$order_id)){
				    	if ( $db->insertData("{{review}}",$params)){
				    		$review_id=Yii::app()->db->getLastInsertID();
				    		
				    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
								if (method_exists('PointsProgram','addReviewsPerOrder')){
									PointsProgram::addReviewsPerOrder($order_id,
									$client_id,$review_id,$order_info['merchant_id'],$order_info['status']);
								}			
							}	
							
							$this->code = 1;
					        $this->msg = st("Your review has been published.");
					        $this->details = array();
									
				    	} else $this->msg = st("ERROR. cannot insert data.");
				    } else {				    	
				    	$id = $res_review['id'];
				    	unset($params['date_created']);
				    	$params['date_modified'] = FunctionsV3::dateNow();
				    	$db->updateData("{{review}}",$params,'id', $id);
				    	$this->code = 1;
					    $this->msg = st("Your review has been published.");
					    $this->details = array();
				    }
						    			
				} else {
					// review merchant
					$order_id = $order_info['order_id'];
					$params = array(
					  'merchant_id'=>$order_info['merchant_id'],
					  'client_id'=>$client_id,
					  'review'=>$this->data['review'],
					  'rating'=>$this->data['rating'],
					  'as_anonymous'=>isset($this->data['as_anonymous'])?$this->data['as_anonymous']:0,
					  'date_created'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR'],
					  'order_id'=>$order_id,  
					);
					$actual_purchase = getOptionA('website_reviews_actual_purchase');				
					if($actual_purchase=="yes"){
						$functionk=new FunctionsK();
						if (!$functionk->checkIfUserCanRateMerchant($client_id,$order_info['merchant_id'])){
							$this->msg=st("Reviews are only accepted from actual purchases!");
						}
						if (!$functionk->canReviewBasedOnOrder($client_id,$order_info['merchant_id'])){
			    		   $this->msg=st("Sorry but you can make one review per order");
			    	       return ;
			    	    }	  		   
					}
					
					if(!$res_review = FunctionsV3::getReviewByOrder($client_id,$order_id)){
				    	if ( $db->insertData("{{review}}",$params)){
				    		$review_id=Yii::app()->db->getLastInsertID();
				    		
				    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
								if (method_exists('PointsProgram','addReviewsPerOrder')){
									PointsProgram::addReviewsPerOrder($order_id,
									$client_id,$review_id,$order_info['merchant_id'],$order_info['status']);
								}			
							}	
							
							$this->code = 1;
	
					        $this->msg = st("Your review has been published.");
					        $this->details = array();
									
				    	} else $this->msg = st("ERROR. cannot insert data.");
				    } else {
				    	$id = $res_review['id'];
				    	unset($params['date_created']);
				    	$params['date_modified'] = FunctionsV3::dateNow();
				    	$db->updateData("{{review}}",$params,'id', $id);
				    	$this->code = 1;
					    $this->msg = st("Your review has been published.");
					    $this->details = array();
				    }
								
				}
		    } else $this->msg = $this->t("order id not found");		
		}
		$this->output();
	}
    
	public function actionReviewList()
	{
		$website_title = getOptionA('website_title');
		$page_action =  isset($this->data['page_action'])?$this->data['page_action']:'';
		
		$pagelimit = SingleAppClass::paginateLimit();		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $pagelimit;
        } else  $page = 0; 
        
        $paginate_total=0; 
        $limit="LIMIT $page,$pagelimit";         
        $db = new DbExt();
        
        $stmt="
        SELECT SQL_CALC_FOUND_ROWS 
        a.id,
        a.merchant_id,
        a.client_id,
        a.review,
        a.rating,
        a.as_anonymous,
        a.date_created,
        concat(b.first_name,' ',b.last_name) as customer_name,
        b.avatar
        
        FROM {{review}} a
        left join {{client}} b
        ON
        a.client_id = b.client_id
        
        WHERE a.status='publish'
        AND a.merchant_id=".FunctionsV3::q($this->merchant_id)."        
        
        ORDER BY a.id DESC
		$limit
        ";    
        if(isset($_GET['debug'])){ 	       
       	   dump($stmt);
        }           
        if($res = $db->rst($stmt)){        
			
			$data = array();
			foreach ($res as $val) {						
				if($val['as_anonymous']==1){
					$val['customer_name'] = st("By [sitename] Customer",array(
					  '[sitename]'=>$website_title
					));
					$val['avatar'] = SingleAppClass::getImage('x.png','avatar.png');
				} else {
					$val['avatar'] = SingleAppClass::getImage($val['avatar'],'avatar.png');
					$val['customer_name'] = st("[customer_name]",array(
					  '[customer_name]'=>$val['customer_name']
					));
				}		

				$pretyy_date=PrettyDateTime::parse(new DateTime($val['date_created']));
		        $pretyy_date=Yii::app()->functions->translateDate($pretyy_date);
		        $val['date_posted']=$pretyy_date;
		        
		        $val['reply'] = SingleAppClass::getReviewReplied($val['id'],$val['merchant_id']);
		        		    					
				$data[]=$val;
			}
			
			$this->code = 1;
			$this->msg="OK";
			$this->details = array( 			   
			  'page_action'=>$page_action,			  
			  'data'=>$data
			);
			
        } else {
        	if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
	        	$this->msg = $this->t("No available review");        	
				$this->details = array(
				  'title'=>st("No available review"),
				  'sub_title'=>st("be the first one to leave review order now!")
				);	
			}        	
        }	
        
		$this->output();
	}
	
	public function actionGetTask()
	{
		$task_id = isset($this->data['task_id'])?$this->data['task_id']:'';		
		if ($client_id = $this->checkToken()){
			$this->data['client_id'] = $client_id;
			if($res = SingleAppClass::getTaskFullInformation($task_id)){
				
				$res['profile_photo'] = SingleAppClass::getImage($res['driver_photo'],'avatar.png',false,'driver');
				$res['review_as'] = st("Review as [customer_name]",array(
				  '[customer_name]'=>$res['customer_firstname']
				));
				
				$this->code = 1;
				$this->msg = "OK";
				$this->details = array(
				  'data'=>$res
				);
			} else $this->msg = st("Sorry but we cannot find what your looking for");
		}				
		$this->output();
	}
	
	public function actionRateTask()
	{
		$task_id = isset($this->data['task_id'])?$this->data['task_id']:'';
		$rating = isset($this->data['rating'])?$this->data['rating']:'';
		
		if(!is_numeric($this->data['rating'])){
			$this->msg = $this->t("Please select rating");
			$this->output();
		}		
		if($this->data['rating']<=0){
			$this->msg = $this->t("Please select rating");
			$this->output();
		}		
		
		$params = array(
		  'rating'=>$rating,
		  'rating_comment'=>isset($this->data['review'])?$this->data['review']:'',
		  'rating_anonymous'=>isset($this->data['as_anonymous'])?$this->data['as_anonymous']:0,
		  'date_modified'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		
		if(!is_numeric($params['rating'])){
			$params['rating']=0;
		}	
		if(!is_numeric($params['rating_anonymous'])){
			$params['rating_anonymous']=0;
		}	
				
		if($task_id>0){
		   $db = new DbExt();
		   $db->updateData("{{driver_task}}",$params,'task_id', $task_id);
		   $this->code = 1;
		   $this->msg = st("your review has submitted");
		} else $this->msg = st("invalid task id");
		
		$this->output();
	}
	
	public function actionGetNotification()
	{		
		$page_action =  isset($this->data['page_action'])?$this->data['page_action']:'';
		
		$pagelimit =  SingleAppClass::paginateLimit();		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $pagelimit;
        } else  $page = 0; 
        
        $paginate_total=0; 
        $limit="LIMIT $page,$pagelimit";   
        
              
        $db = new DbExt();
        
        $where="WHERE is_read='0'";
        $and='';
        
        if (!$client_id = $this->checkToken()){
        	$client_id=0;
        }
        
        if($client_id>0){
        	$and=" AND client_id = ".FunctionsV3::q($client_id)."  ";
        } else {
        	$and=" AND device_id = ".FunctionsV3::q($this->device_id)."  ";
        }
        
        $stmt="
        SELECT 
        id,
        push_title,
        push_message,
        date_created
        FROM {{singleapp_mobile_push_logs}}
        $where
        $and
        ORDER BY id DESC
        $limit
        ";
        //dump($stmt);
        if ($res = $db->rst($stmt)){
        	$data = array();
        	foreach ($res as $val) {
        		$date_created = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);        		
        		$data[] = array(
        		  'id'=>$val['id'],
        		  'push_title'=>clearString($val['push_title']),
        		  'push_message'=>clearString($val['push_message']),
        		  'date_created'=>$date_created
        		);
        	}
        	$this->code = 1;
        	$this->msg = "OK";
        	$this->details = array(
        	  'page_action'=>$page_action,
        	  'data'=>$data
        	);
        } else {        	
			if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("Notifications is empty"),
				  'sub_title'=>st("You don't have any notifications")
				);	
			}
        }	
        
		$this->output();
	}	
	
	public function actionGetGallery()
	{
		$page_action =  isset($this->data['page_action'])?$this->data['page_action']:'';
		$list = array();
		$gallery=Yii::app()->functions->getOption("merchant_gallery",$this->merchant_id);
        $gallery=!empty($gallery)?json_decode($gallery):false;
        //$gallery=false;
        if(is_array($gallery) && count($gallery)>=1){
        	foreach ($gallery as $val) {
        		$list[] = SingleAppClass::getImage($val);
        	}        
        	$this->code = 1;
        	$this->msg ="OK";
        	$this->details=array(
        	  'page_action'=>$page_action,
        	  'data'=>$list
        	);
        } else {        	
        	if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = $this->t("Photos not available"); 
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("No photos found"),
				  'sub_title'=>st("There are no photo available")
				);	
			}
        }       
		$this->output();
	}
	
	public function actionsearchOrder()
	{
		if ($client_id = $this->checkToken()){
			
			$cancel_order_enabled = getOptionA('cancel_order_enabled');		
			$website_review_type = getOptionA('website_review_type');
			$review_baseon_status = getOptionA('review_baseon_status');	
			$merchant_can_edit_reviews = getOptionA('merchant_can_edit_reviews');
			if($website_review_type==1){
				$review_baseon_status = getOptionA('review_merchant_can_add_review_status');
			}
			$date_now=date('Y-m-d g:i:s a');	 
			
			$data = array();
			$search_str = isset($this->data['search_str'])?$this->data['search_str']:'';
			if(!empty($search_str)){
				$db=new DbExt();
				$stmt="SELECT 
				a.order_id,
				a.client_id,
				a.trans_type,
				a.trans_type as trans_type_raw,
				a.payment_type,
				a.payment_type as payment_type_raw,
				a.total_w_tax,
				a.status,
			    a.status as status_raw,		
			    a.date_created,
			    a.date_created as date_created_raw,
			    a.request_cancel,
			    a.order_locked,
			    a.request_cancel_status,
				b.restaurant_name,
				b.logo
				FROM {{order}} a			
				left join {{merchant}} b
	            ON
	            a.merchant_id = b.merchant_id
	            WHERE a.client_id=".FunctionsV3::q($client_id)."
	            AND a.merchant_id = ".FunctionsV3::q($this->merchant_id)."
	            AND a.status NOT IN ('".initialStatus()."')
	            AND ( 
	                a.order_id LIKE ".FunctionsV3::q("%$search_str")."
	                OR b.restaurant_name LIKE ".FunctionsV3::q("%$search_str%")."
	                OR a.trans_type LIKE ".FunctionsV3::q("%$search_str%")."
	                OR a.payment_type LIKE ".FunctionsV3::q("%$search_str%")."
	             )
	            
				LIMIT 0,20
				";						
				if(isset($_GET['debug'])){
				   dump($stmt);
				}
				if ($res = $db->rst($stmt)){
					foreach ($res as $val) {
						
						$val['restaurant_name'] = clearString($val['restaurant_name']);
						$val['payment_type'] = st(FunctionsV3::prettyPaymentTypeTrans($val['trans_type'],$val['payment_type']));
						$val['restaurant_name']= SingleAppClass::highlight_word($val['restaurant_name'],$search_str);
						$val['transaction'] = st("[trans_type] #[order_id]",array(
						  '[trans_type]'=>t($val['trans_type']),
						  '[order_id]'=>t($val['order_id']),
						));
						
						$val['payment_type']= SingleAppClass::highlight_word($val['payment_type'],$search_str);
						$val['restaurant_name']= SingleAppClass::highlight_word($val['restaurant_name'],$search_str);
						$val['transaction']= SingleAppClass::highlight_word($val['transaction'],$search_str);
						
						$val['logo']=SingleAppClass::getImage($val['logo']);
						
						$add_review = false;		
						if(SingleAppClass::canReviewOrder($val['status_raw'],$website_review_type,$review_baseon_status)){
						   $add_review=true;
						}	
						
						if($add_review){		
							if ($val['client_id']==$client_id){		    		
				    			$date_diff=Yii::app()->functions->dateDifference(
				    			date('Y-m-d g:i:s a',strtotime($val['date_created_raw']))
				    			,$date_now);
				    			if(is_array($date_diff) && count($date_diff)>=1){
				    				if ($date_diff['days']>=5){
				    				   $add_review=false;
				    				}
				    			}	    	
							} else $add_review=false;
						}
						
						if($website_review_type==1){
							if($val['rating']>0){
								if($merchant_can_edit_reviews=="yes"){
								   	$add_review=false;
								}
							}				
						}
										
						$val['add_review'] = $add_review;
						
						$show_cancel = false; $cancel_status='';
						if(FunctionsV3::canCancelOrderNew($val['request_cancel'],$val['date_created'],$val['status_raw'],$val['order_locked'],$val['request_cancel_status'],$cancel_order_enabled)){
							if($val['request_cancel']==1){
								$cancel_status = st("Pending for review");
							} else $show_cancel=true;									
						}	
						
						if ($val['request_cancel_status']!='pending'){					
							$cancel_status = Yii::t("singleapp","Request cancel : [status]",array(
							  '[status]'=>t($val['request_cancel_status'])
							));
						}		
						
						$val['add_cancel']=$show_cancel;
						$val['cancel_status']=$cancel_status;
		
						$val['add_track']=true;
							
							$data[] = $val;
						}
					
					$this->code = 1;
					$this->msg = "OK";
					$this->details = array(
					 'data'=>$data
					);
					
				} else $this->msg = $this->t("No results");
			} else $this->msg = $this->t("invalid search string");
		}
		$this->output();
	}
	
	public function actionsearchBooking()
	{
		if ($client_id = $this->checkToken()){
			$search_str = isset($this->data['search_str'])?$this->data['search_str']:'';
			if(!empty($search_str)){
				$db=new DbExt();
				$stmt="
				SELECT 				
				a.booking_id,
				a.client_id,
				a.merchant_id,
				a.date_booking,
				a.booking_time,
				a.number_guest,
				b.restaurant_name,
			    b.logo
				FROM {{bookingtable}} a
				left join {{merchant}} b
                ON
                a.merchant_id = b.merchant_id
                WHERE a.client_id=".FunctionsV3::q($client_id)."
                AND a.merchant_id=". FunctionsV3::q($this->merchant_id) ."	
                AND (
                   a.booking_id LIKE ".FunctionsV3::q("%$search_str")."
                   OR b.restaurant_name LIKE ".FunctionsV3::q("%$search_str%")."
                )
                LIMIT 0,20
				";
				if(isset($_GET['debug'])){
			      dump($stmt);
			    }
			    if ($res = $db->rst($stmt)){
			    	foreach ($res as $val) {
			    		$val['date_booking_format'] = FunctionsV3::prettyDate( $val['date_booking'] )." ".FunctionsV3::prettyTime($val['booking_time']);
			    		$val['restaurant_name']=clearString($val['restaurant_name']);
			    		$val['logo']=SingleAppClass::getImage($val['logo']);
			    		$val['booking_ref'] = st("[booking_id]",array(
						  '[booking_id]'=> $val['booking_id']
						));
						$val['number_guest'] = st("[count]",array(
				           '[count]'=> $val['number_guest']
				        ));
				        
				        $val['restaurant_name']= SingleAppClass::highlight_word($val['restaurant_name'],$search_str);
				        $val['booking_ref']= SingleAppClass::highlight_word($val['booking_ref'],$search_str);
				        
			    		$data[] = $val;
			    	}
			    	$this->code = 1;
					$this->msg = "OK";
					$this->details = array(
					 'data'=>$data
					);			    	
			    } else $this->msg = $this->t("No results");
			} else $this->msg = $this->t("invalid search string");
		}			
		$this->output();
	}	
	
	public function actionGetBookingDetails()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		if ($client_id = $this->checkToken()){			
			$booking_id = isset($this->data['booking_id'])?$this->data['booking_id']:'';
			if($res = SingleAppClass::GetBookingDetails($booking_id,$client_id)){
				$this->code = 1;
				$this->msg = "ok";
				$data = array();
				
				$data[]=array(
				  'label'=>st("Booking ID"),
				  'value'=>$res['booking_id'],
				);
				$data[]=array(
				  'label'=>st("Number of guest"),
				  'value'=>$res['number_guest'],
				);
				$data[]=array(
				  'label'=>st("Date Of Booking"),
				  'value'=>FunctionsV3::prettyDate($res['date_booking']),
				);
				$data[]=array(
				  'label'=>st("Time"),
				  'value'=>FunctionsV3::prettyTime($res['booking_time']),
				);
				$data[]=array(
				  'label'=>st("Name"),
				  'value'=>$res['booking_name']
				);
				$data[]=array(
				  'label'=>st("Email"),
				  'value'=>$res['email']
				);
				$data[]=array(
				  'label'=>st("Mobile"),
				  'value'=>$res['mobile']
				);
				$data[]=array(
				  'label'=>st("Your Instructions"),
				  'value'=>$res['booking_notes']
				);
				
				$this->details = array(
				  'page_action'=>$page_action,
				  'data'=>$data
				);
			} else {
				$this->code = 6;
				$this->msg = $this->t("Booking not found");
				$this->details = array(
				  'title'=>st("Booking not found"),
				  'sub_title'=>st("booking details not available")
				);														
			}
		}
		$this->output();
	}	
	
	public function actionlogout()
	{
		if ($client_id = $this->checkToken()){
			$db = new DbExt();
			$db->updateData("{{client}}",array(
			  'enabled_push'=>0
			),'client_id', $client_id);
		}
		$this->code = 1;
		$this->msg = "OK";
		$this->output();
	}
	
	public function actionsaveLocationAddress()
	{
		$lat = isset($this->data['lat'])?$this->data['lat']:'';
		$lng = isset($this->data['lng'])?$this->data['lng']:'';
		
		if(!empty($lat) && !empty($lng)){
			$db = new DbExt();
			$params = array(
			  'device_uiid'=>$this->device_uiid,
			  'search_address'=>isset($this->data['search_address'])?trim($this->data['search_address']):'',
			  'street'=>isset($this->data['street'])?trim($this->data['street']):'',
			  'city'=>isset($this->data['city'])?trim($this->data['city']):'',
			  'state'=>isset($this->data['state'])?trim($this->data['state']):'',
			  'country'=>isset($this->data['country'])?trim($this->data['country']):'',
			  'latitude'=>$lat,
			  'longitude'=>$lng,
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
			);
			if($res = SingleAppClass::getRecentLocationByID($this->device_uiid,$lat, $lng)){				
				$id = $res['id'];
				$db->updateData("{{singleapp_recent_location}}",$params,'id',$id);
			} else {				
				$db->insertData("{{singleapp_recent_location}}",$params);
			}		
			
			$this->code =1; $this->msg = "OK";
			unset($db);
			
		} else $this->msg = st("Invalid location");
		
		$this->output();
	}
	
	public function actionGetRecentLocation()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		$pagelimit = SingleAppClass::paginateLimit();		
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $pagelimit;
        } else  $page = 0; 

        $paginate_total=0; 
        $limit="LIMIT $page,$pagelimit"; 
        
        $search_resp = SingleAppClass::searchMode();
		$search_mode = $search_resp['search_mode'];	
		
		$and="AND search_mode='address'";
		if($search_mode=="location"){
			$and="AND search_mode='location'";
		}
        
		$db=new DbExt;
    	$stmt="
    	SELECT *
    	FROM {{singleapp_recent_location}}
    	WHERE device_uiid=".FunctionsV3::q($this->device_uiid)."
    	$and
    	ORDER BY id DESC
    	$limit
    	";    	
    	
    	if(isset($_GET['debug'])){
		   dump($stmt);
	    }
	    $db = new DbExt();  $data=array();
	    if($res = $db->rst($stmt)){
	       foreach ($res as $val) {	       	  
	       	  unset($val['ip_address']);
	       	  $val['date_added'] = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
	       	  $data[]=$val;
	       }
	       $this->code = 1;
	       $this->msg = "OK";
	       $this->details = array(
	         'page_action'=>$page_action,
	         'data'=>$data
	       );
	    } else {	    	
	    	if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("No recent location"),
				  'sub_title'=>st("list is empty")
				);	
			}
	    }		   
		$this->output();
	}
	
	public function actionClearLocation()
	{
		if(!empty($this->device_uiid)){
			$this->code = 1;
			$this->msg = "OK";
			SingleAppClass::clearRecentLocation($this->device_uiid);
		} else $this->msg = st("Invalid device uiid");
		$this->output();
	}
	
	public function actionAddFavorite()
	{
		$item_id = isset($this->data['item_id'])?$this->data['item_id']:'';
		$category_id = isset($this->data['category_id'])?$this->data['category_id']:'';
		if ($client_id = $this->checkToken()){
			SingleAppClass::addItemFavorite($client_id, $item_id, $category_id);
			$this->code = 1;
			$this->msg =st("Item added to your favorites");
		}
		$this->output();
	}
	
	public function actionRemoveFavorite()
	{
		$item_id = isset($this->data['item_id'])?$this->data['item_id']:'';
		if ($client_id = $this->checkToken()){
			SingleAppClass::removeItemFavorite($client_id, $item_id);
			$this->code = 1;
			$this->msg ="OK";
		}
		$this->output();
	}
	
	public function actionRemoveFavoriteByID()
	{
		$id = isset($this->data['id'])?$this->data['id']:'';
		if ($client_id = $this->checkToken()){
			SingleAppClass::removeItemFavoriteByID($client_id, $id);
			$this->code = 1;
			$this->msg ="OK";
		}
		$this->output();
	}
	
	public function actionItemFavoritesList()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';		
		if ($client_id = $this->checkToken()){
			
			
			$pagelimit = SingleAppClass::paginateLimit();		
			if (isset($this->data['page'])){
	        	$page = $this->data['page'] * $pagelimit;
	        } else  $page = 0; 	        
	        $limit="LIMIT $page,$pagelimit"; 
	        
	        $stmt="
    	       SELECT
    	       a.id,
    	       a.item_id,
    	       a.date_created,
    	       b.item_name,
    	       b.photo
    	       FROM {{favorite_item}} a
    	       LEFT join {{item}} b
	           ON 
	           a.item_id = b.item_id
	           
	           WHERE a.client_id = ".FunctionsV3::q($client_id)."
	           ORDER BY a.id DESC
    	       $limit  
    	    ";    	 
	        
	        if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
	        	$res = Yii::app()->request->stripSlashes($res);
	        	$data = array();
				foreach ($res as $val) {
	    			$date_added = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
	    			$val['date_added'] = st("Added [date]",array(
	    			  '[date]'=>$date_added
	    			));
	    			$val['photo']= SingleAppClass::getImage($val['photo']);
	    			$val['item_name'] = clearString($val['item_name']);
	    			$data[]=$val;
	    		}
	    		$this->code = 1;
				$this->msg = "OK";								
				$this->details = array(				  
				  'data'=>$data,
				  'page_action'=>$page_action
				);
	        } else {
	        	if($page_action=="infinite_scroll"){
					$this->code = 2;
					$this->msg = st("end of records");
				} else {
					$this->code = 6;
					$this->details = array(
					  'title'=>st("Your favorites is empty"),
					  'sub_title'=>st("Add your first food item")
					);		
				}
	        }		
		}
		$this->output();
	}
	
	public function actionresendVerificationCode()
	{
		$db = new DbExt();
		$verification_type = isset($this->data['verification_type'])?$this->data['verification_type']:'';
		$signup_token = isset($this->data['signup_token'])?$this->data['signup_token']:'';
		$date_now=date('Y-m-d g:i:s a');	 
		
		$params=array(
		 'verify_code_requested'=>FunctionsV3::dateNow(),
		 'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
					
		if($resp = SingleAppClass::getCustomerByToken($signup_token,false)){	
			
			$verify_code_requested = $resp['verify_code_requested'];			
			
			$date_diff=Yii::app()->functions->dateDifference(
			    			date('Y-m-d g:i:s a',strtotime($verify_code_requested))
			    			,$date_now);
			    			
			    			
			$waiting_code = 5;
			
            $continue = true;	$waiting_time = '';		    						
			if(is_array($date_diff) && count($date_diff)>=1){				
				if($waiting_code>$date_diff['minutes']){	
					$waiting_time = $date_diff['minutes'];
					$continue=false;
				}		
				if($continue==false){
					if($date_diff['days']>0){
						$continue=true;
					}					
					if($date_diff['hours']>0){
						$continue=true;
					}					
				}		
			}		
			
			if(!$continue){
				$waiting_time = (integer)$waiting_code - (integer)$waiting_time;
				$this->msg = st("Your requesting too soon please wait after [time] minutes",array(
				  '[time]'=>$waiting_time
				));
				$this->output();
			}				    						
			
			switch ($verification_type) {
				case "email":
					$code = trim($resp['email_verification_code']);
					FunctionsV3::sendEmailVerificationCode($resp['email_address'],$code,$resp);
					$this->code = 1;
					$this->msg = st("We have sent verification code to your email address");					
					$db->updateData("{{client}}",$params,'client_id',$resp['client_id']);
					break;
					
				case "sms":	
				    $code = trim($resp['mobile_verification_code']);
				    FunctionsV3::sendCustomerSMSVerification($resp['contact_phone'],$code);
				    $this->code = 1;
					$this->msg = st("We have sent verification code to your mobile number");					
					$db->updateData("{{client}}",$params,'client_id',$resp['client_id']);
				    break; 
			
				default:
					$this->msg = st("Invalid verification type");
					break;
			}
		} else $this->msg = st("Token not found");
		$this->output();
	}
	
	public function actionStateList()
	{				      
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		$page_limit = SingleAppClass::paginateLimit();
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $search_string = isset($this->data['search_str'])?$this->data['search_str']:'';
		
		if($res = LocationWrapper::GetStateList($page,$page_limit, $search_string) ){			
			$this->code =1;
			$this->msg = "OK";
			$this->details = array(
			  'page_action'=>$page_action,
			  'paginate_total'=>$res['paginate_total'],
			  'data'=>$res['list'],
			);
		} else {
			if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("Search results 0 found"),
				  'sub_title'=>st("we cannot find what your looking for")
				);	
			}
		};
		$this->output();
	}
	
	public function actionCityList()
	{				      
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		$page_limit = SingleAppClass::paginateLimit();
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $search_string = isset($this->data['search_str'])?$this->data['search_str']:'';
        $state_id = isset($this->data['state_id'])?$this->data['state_id']:'';
        
        $search_resp = SingleAppClass::searchMode();		
		$location_mode = $search_resp['location_mode'];
		if($location_mode==2){
			if((integer)$state_id<=0){
				$this->code = 6;
				$this->details = array(
				  'title'=>st("No results"),
				  'sub_title'=>st("we cannot find what your looking for")
				);	
				$this->output();
			}		
		}	
		
		if($res = LocationWrapper::GetLocationCity($page,$page_limit, $search_string, $state_id) ){
			$this->code =1;
			$this->msg = "OK";
			$this->details = array(
			  'page_action'=>$page_action,
			  'paginate_total'=>$res['paginate_total'],
			  'data'=>$res['list'],
			);
		} else {
			if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("No results"),
				  'sub_title'=>st("we cannot find what your looking for")
				);	
			}
		}	
		$this->output();
	}
	
	public function actionAreaList()
	{
		$page_action = isset($this->data['page_action'])?$this->data['page_action']:'';
		
		$page_limit = SingleAppClass::paginateLimit();
		if (isset($this->data['page'])){
        	$page = $this->data['page'] * $page_limit;
        } else  $page = 0;  
        
        $search_string = isset($this->data['search_str'])?$this->data['search_str']:'';
        $city_id = isset($this->data['city_id'])?$this->data['city_id']:'';
                
        if($res = LocationWrapper::GetAreaList($city_id,$page,$page_limit, $search_string) ){
			$this->code =1;
			$this->msg = "OK";
			$this->details = array(
			  'page_action'=>$page_action,
			  'paginate_total'=>$res['paginate_total'],
			  'data'=>$res['list'],
			);
		} else {
			if($page_action=="infinite_scroll"){
				$this->code = 2;
				$this->msg = st("end of records");
			} else {
				$this->code = 6;
				$this->details = array(
				  'title'=>st("No results"),
				  'sub_title'=>st("we cannot find what your looking for")
				);	
			}
		}	
		$this->output();
	}
	
	public function actionSaveAddresBookLocation()
	{
		if ($client_id = $this->checkToken()){
			
			$params = array(
			  'client_id'=>$client_id,
			  'street'=>isset($this->data['street'])?$this->data['street']:'',
			  'latitude'=>isset($this->data['lat'])?$this->data['lat']:'',
			  'longitude'=>isset($this->data['lng'])?$this->data['lng']:'',
			  'state_id'=>isset($this->data['state_id'])?$this->data['state_id']:'',
			  'city_id'=>isset($this->data['city_id'])?$this->data['city_id']:'',
			  'area_id'=>isset($this->data['area_id'])?$this->data['area_id']:'',
			  'as_default'=>isset($this->data['as_default'])?$this->data['as_default']:'',
			  'location_name'=>isset($this->data['location_name'])?$this->data['location_name']:'',
			  'country_id'=>isset($this->data['country_id'])?$this->data['country_id']:'',
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
			);					
			
			if(empty($params['latitude'])){
				$this->msg = $this->t("please select your location on the map");
				$this->output();
			}		
			if(empty($params['longitude'])){
				$this->msg = $this->t("please select your location on the map");
				$this->output();
			}		
			
			if(!is_numeric($params['as_default'])){			    
			    $params['as_default']=0;
		    }			
		    
		    $db = new DbExt();	
		    $id = isset($this->data['book_location_id'])?$this->data['book_location_id']:'';
		    
		    if($id>0){
				 unset($params['date_created']);
				 $params['date_modified']=FunctionsV3::dateNow();
				 
				 if(LocationWrapper::isAddressBookExist($client_id,$params['state_id'],
				 $params['city_id'],$params['area_id'],$id
				 )){
				   $this->msg = st("Address already exist");
				   $this->output();
				 }
				 
				 if ($params['as_default']==1){
					LocationWrapper::UpdateAllAddressBookDefaultLocation($client_id);
				 }							 
				 $db->updateData("{{address_book_location}}", $params ,'id',$id);
				 $this->code = 1; $this->msg = $this->t("Successfully updated");
			} else {				
				
				if(LocationWrapper::isAddressBookExist($client_id,$params['state_id'],
				$params['city_id'],$params['area_id']
				)){
				   $this->msg = st("Address already exist");
				   $this->output();
				}
								
				if ($params['as_default']==1){
					LocationWrapper::UpdateAllAddressBookDefaultLocation($client_id);
				}
								
				if ( $db->insertData("{{address_book_location}}",$params)){
					$this->code = 1; $this->msg = $this->t("Successfully added");
				} else $this->msg = $this->t("failed cannot insert records. please try again later");
			}		
		}
		$this->output();
	}
	
	public function actiongetAddressBookLocationByID()
	{
		if ($client_id = $this->checkToken()){
			$id = isset($this->data['id'])?$this->data['id']:'';
			if($id>=1){
				if ($res=LocationWrapper::getAddressBookByID($id,$client_id)){
				unset($res['date_created']);
				unset($res['date_modified']);
				unset($res['ip_address']);
											
				
				$this->code = 1;
				$this->msg = "ok";
				$this->details = array(
				  'data'=>$res
				);
			} else $this->msg = $this->t("Record not found. please try again later");
			} else $this->msg = $this->t("Invalid id");
		}
		$this->output();
	}
	
	public function actionsetAddressBookLocation()
	{
			if ($client_id = $this->checkToken()){
			$addressbook_id = isset($this->data['addressbook_id'])?$this->data['addressbook_id']:'';		
			if($addressbook_id>0){
				if($res = LocationWrapper::getAddressBookByID($addressbook_id,$client_id)){					
					$new_data = array(
					  'contact_phone'=>isset($this->data['contact_phone'])?$this->data['contact_phone']:'',
					  'delivery_instruction'=>isset($this->data['delivery_instruction'])?$this->data['delivery_instruction']:'',
					  'street'=>$res['street'],
					  'state_name'=>$res['state_name'],
					  'city_name'=>$res['city_name'],
					  'area_name'=>$res['area_name'],
					  'location_name'=>$res['location_name'],
					  'lat'=>$res['lat'],
					  'lng'=>$res['lng'],
					  'state_id'=>$res['state_id'],
					  'city_id'=>$res['city_id'],
					  'area_id'=>$res['area_id'],
					  'save_address'=>0,
					);									
					$this->data = $new_data;
					$this->actionsetDeliveryLocationFee();
					$this->output();
				} else $this->msg = st("invalid address book not found");
			} else $this->msg = st("invalid address book id");
		}		
		$this->output();
	}
	
	public function actionsetDeliveryLocationFee()
	{
		$db = new DbExt();
		$params=array();
		$params['street'] = isset($this->data['street'])?$this->data['street']:'';
		$params['state'] = isset($this->data['state_name'])?$this->data['state_name']:'';
		$params['city']= isset($this->data['city_name'])?$this->data['city_name']:'';
		$params['zipcode']= isset($this->data['area_name'])?$this->data['area_name']:'';
		$params['location_name']= isset($this->data['location_name'])?$this->data['location_name']:'';
		$params['contact_phone']= isset($this->data['contact_phone'])?$this->data['contact_phone']:'';
		$params['delivery_instruction']= isset($this->data['delivery_instruction'])?$this->data['delivery_instruction']:'';
		$params['save_address']= isset($this->data['save_address'])?$this->data['save_address']:0;
		$params['delivery_lat']= isset($this->data['lat'])?$this->data['lat']:'';
		$params['delivery_long']= isset($this->data['lng'])?$this->data['lng']:'';
		$params['state_id']= isset($this->data['state_id'])?$this->data['state_id']:'';
		$params['city_id']= isset($this->data['city_id'])?$this->data['city_id']:'';
		$params['area_id']= isset($this->data['area_id'])?$this->data['area_id']:'';
		
		$delivery_fee = getOption($this->merchant_id,'merchant_delivery_charges');
		
		
		$resp_delivery = LocationWrapper::getDeliveryFee(
		 $this->merchant_id,
		 $delivery_fee,
		 $params['state_id'],
		 $params['city_id'],
		 $params['area_id']
		);		
		
		if($resp_delivery){
		  $params['delivery_fee']=$resp_delivery;
		} else {
		   $this->msg = st("Sorry this merchant does not deliver to your location");
		   $this->output();
		}	
				
		$db->updateData("{{singleapp_cart}}",$params,'device_id',$this->device_uiid);
		
		if($params['save_address']==1){
			if ($client_id = $this->checkToken()){
				if (!LocationWrapper::isAddressBookExist($client_id,$params['state_id'],$params['city_id'],$params['area_id'])){
					LocationWrapper::UpdateAllAddressBookDefaultLocation($client_id);
					$address = array(
					  'client_id'=>$client_id,
					  'street'=>$params['street'],
					  'location_name'=>$params['location_name'],
					  'country_id'=>LocationWrapper::getCountryID($params['state_id']),
					  'state_id'=>$params['state_id'],
					  'city_id'=>$params['city_id'],
					  'area_id'=>$params['area_id'],
					  'latitude'=>$params['delivery_lat'],
					  'longitude'=>$params['delivery_long'],
					  'as_default'=>1,
					  'date_created'=>FunctionsV3::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR']
					);					
					$db->insertData("{{address_book_location}}",$address);
				}		
			}
		}	
		
		$search_resp = SingleAppClass::searchMode();
		$search_mode = $search_resp['search_mode'];	

		$lat=$params['delivery_lat'];
		$lng=$params['delivery_long'];
		
	    /*SAVE LOCATION*/		
		$params_location = array(
		  'search_mode'=>$search_mode,
		  'device_uiid'=>$this->device_uiid,
		  'search_address'=>isset($this->data['search_address2'])?trim($this->data['search_address2']):'',
		  'street'=>$params['street'],
		  'city'=>$params['city'],
		  'state'=>$params['state'],
		  'country'=>isset($this->data['country_name'])?$this->data['country_name']:'',
		  'location_name'=>$params['location_name'],
		  'zipcode'=>$params['zipcode'],
		  'state_id'=>$this->data['state_id']?$this->data['state_id']:'',
		  'city_id'=>$this->data['city_id']?$this->data['city_id']:'',
		  'area_id'=>$this->data['area_id']?$this->data['area_id']:'',
		  'country_id'=>isset($this->data['country_id'])?$this->data['country_id']:'',
		  'latitude'=>$params['delivery_lat'],
		  'longitude'=>$params['delivery_long'],
		  'date_created'=>FunctionsV3::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
		);			
		if(!empty($params_location['search_address'])){			
			if($res = SingleAppClass::getRecentLocationByID($this->device_uiid,$lat,$lng,$search_mode)){
				$id = $res['id'];
				$db->updateData("{{singleapp_recent_location}}",$params_location,'id',$id);
			} else {				
				$db->insertData("{{singleapp_recent_location}}",$params_location);
			}		
		}
		
		
		$this->code = 1;
		$this->msg = "OK";
		$this->details = array(		  
		);
		
		$this->output();
	}
	
	public function actionSocialLogin()
	{
		$DbExt=new DbExt; 
		$email_address = isset($this->data['email_address'])?$this->data['email_address']:'';
		
		$Validator=new Validator;
		if ( FunctionsK::emailBlockedCheck($email_address)){
    		$Validator->msg[] = $this->t("Sorry but your email address is blocked by website admin");    		
    	}	 
    	
    	if(empty($email_address)){
    	  $Validator->msg[] = $this->t("invalid email address");    		
    	}	
    	
    	$Validator->email(array(
		  'email_address'=>$this->t("Invalid email address")
		),$this->data);
    	
    	$token='';
    	$verification=getOptionA('website_enabled_mobile_verification'); 
    	$email_verification=getOptionA('theme_enabled_email_verification');
    	
    	if($Validator->validate()){ 
    		
    		 $p = new CHtmlPurifier();
    		 $params = array(
    	   	    'first_name'=>$p->purify($this->data['first_name']),
    	   	    'last_name'=>$p->purify($this->data['last_name']),
    	   	    'email_address'=>$p->purify($email_address),
    	   	    'password'=>md5($this->data['userid']),
    	   	    'last_login'=>FunctionsV3::dateNow(),
    	   	    'ip_address'=>$_SERVER['REMOTE_ADDR'],
    	   	    'social_strategy'=>isset($this->data['social_strategy'])?$this->data['social_strategy']:'',
    	   	    'social_id'=>isset($this->data['userid'])?$this->data['userid']:'',    	   	    
			    'single_app_merchant_id'=>$this->merchant_id,			    
    	   	 );
    		
    		if($res = Yii::app()->functions->isClientExist($email_address)){
    		   /*UPDATE*/
    		   $client_id = $res['client_id'];
    	   	   $token = $res['token'];
    	   	   
    	   	   $this->data['client_id'] = $client_id;
    	   	   $this->data['merchant_id'] = $this->merchant_id;
               SingleAppClass::registeredDevice($this->data);
    	   	       	   	       	   	       	   	    	
    	   	   if(empty($token)){
    	   	  	 $token = SingleAppClass::generateUniqueToken(15,$this->data['device_uiid']);    	   	  	 
    	   	  	 $params['token']=$token;
    	   	   }    	 
    	   	   if($res['status']=="pending"){
    	   	   	  $email_code=Yii::app()->functions->generateRandomKey(5);
    	   	   	  if($verification=="yes" || $email_verification==2){
    	   	   	  	 $params['email_verification_code']=$email_code;		    		
		    		 FunctionsV3::sendEmailVerificationCode($params['email_address'],$email_code,$params);
		    		 $this->data['next_step'] = 'verification_email';
		    		 $this->msg = st("We have sent verification code to your email address");    	   	     	
    	   	   	  }    	   	   	  
    	   	   } else $this->msg = st("Registration successful");
    	   	       	   	    
    	   	   unset($params['enabled_push']);
    	   	   unset($params['password']);
    	   	   
    	   	   $this->code = 1;
    	   	   $DbExt->updateData("{{client}}",$params,'client_id',$client_id);
    	   	   
    		} else {
    		  /*INSERT*/    	   	      	   	    	   	  
    	   	  $email_code=Yii::app()->functions->generateRandomKey(5);
    	   	  if($verification=="yes" || $email_verification==2){
    	   	  	 $params['email_verification_code']=$email_code;
	    		 $params['status']='pending';	    		 
	    		 FunctionsV3::sendEmailVerificationCode($params['email_address'],$email_code,$params);
	    		 $this->data['next_step'] = 'verification_email';
    	   	  }    	   
    	   	  
    	   	  $token = SingleAppClass::generateUniqueToken(15,$this->data['device_uiid']);
	    	  $params['token']=$token;
	    	  
	    	  if ( $DbExt->insertData("{{client}}",$params)){
	    	  	   $customer_id =Yii::app()->db->getLastInsertID();
	    	  	   
	    	  	   $this->data['client_id'] = $customer_id;
	    	  	   $this->data['merchant_id'] = $this->merchant_id;
                   SingleAppClass::registeredDevice($this->data);
	    	  	   
	    	  	   $this->code=1;
	    		   $this->msg = $this->t("Registration successful");
	    		   
	    		   if($verification=="yes" || $email_verification==2){
	    		   	  $this->msg = st("We have sent verification code to your email address");    				
    				  $this->data['client_id'] = $customer_id;				          				  
	    		   } else {
	    		   	  /*sent welcome email*/	
	    		   	  FunctionsV3::sendCustomerWelcomeEmail($params);
	    		   	  $this->data['client_id'] = $customer_id;				      
	    		   }	    	  
	    		   
	    		   /*POINTS PROGRAM*/	    			
		    	   if (FunctionsV3::hasModuleAddon("pointsprogram")){
		    		  PointsProgram::signupReward($customer_id);
		    	   }	    		    	      	    	    	    	    	  
	    		   
	    	  } else $this->msg = $this->t("Something went wrong during processing your request. Please try again later");	    	    	     	
    		}
    		    		
    		if($params['social_strategy']=="fb_mobile"){
    		   FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("singlemerchant/cron/getfbavatar"));
    		}
    		
    		$this->details = array(    			  
			  'next_step'=>isset($this->data['next_step'])?$this->data['next_step']:'',
			  'token'=>$token,    			  
			  'contact_phone'=>'',
			  'email_address'=>$email_address,
			);
    		
    	} else $this->msg = SingleAppClass::parseValidatorError($Validator->getError());	
		$this->output();
	}
	
	public function actionContactUs()
	{		
		$lang=Yii::app()->language;		
		
		$to=getOption($this->merchant_id,'singleapp_contact_email');
		if(empty($to)){
		    $this->msg = st("To email is empty under merchant contact us tab");
		    $this->output();
			return ;
		}	    	
				
		$subject=getOption($this->merchant_id,'singleapp_contact_subject');
		if(empty($subject)){
		    $this->msg = st("Subject email is empty under merchant contact us tab");
		    $this->output();
			return ;
		}	    	
				
        $tpl=getOption($this->merchant_id,'singleapp_contact_tpl');
        if(empty($tpl)){
		    $this->msg = st("Template is empty under merchant contact us tab");
		    $this->output();
			return ;
		}	    	
		
        if(!empty($tpl)){
        	foreach ($this->data as $key=>$val) {
        		if($key=="contact_phone"){
        			$key='phone';
        		}        	
        		$tpl=FunctionsV3::smarty($key,$val,$tpl);
        		$subject=FunctionsV3::smarty($key,$val,$subject);
        	}          	
        }                      
                	
        if(sendEmail($to,'',$subject,$tpl)){
        	$this->code=1;    		
	        $this->msg= st("Your message was sent successfully. Thanks.");
        } else $this->msg = st("Failed sending email");
        
		$this->output();
	}
	
	public function actionpreCheckout()
	{
		$this->setMerchantTimezone();
				
		if (!yii::app()->functions->validateSellLimit($this->merchant_id) ){
        	$this->msg =t("This merchant has reach the maximum sells per month");
        	$this->output();
        }
		
		$transaction_type = isset($this->data['transaction_type'])?$this->data['transaction_type']:'';
    	$delivery_date = isset($this->data['delivery_date'])?$this->data['delivery_date']:'';
    	$delivery_time = isset($this->data['delivery_time'])?$this->data['delivery_time']:'';    	
		
		$error=''; 
		$checkout_stats = FunctionsV3::isMerchantcanCheckout($this->merchant_id);		
		if($checkout_stats['code']==1){
			
	    	if(empty($delivery_date)){
	    		$this->msg = $this->t("Delivery date is required");
	    		$this->output();
	    	}
	    		    	
	    	$full_delivery = "$delivery_date $delivery_time";
    	    $delivery_day = strtolower(date("D",strtotime($full_delivery)));
    	        	    
    	    $delivery_time_formated = '';
	    	if(!empty($delivery_time)){
	    		$delivery_time_formated=date('h:i A',strtotime($delivery_time));
	    	} else $delivery_time_formated = date('h:i A');
	    	
	    	
	    	if ( !Yii::app()->functions->isMerchantOpenTimes($this->merchant_id,$delivery_day,$delivery_time_formated)){
	    		
	    		if(empty($delivery_time)){
	    			$delivery_time = date('h:i A');
	    			$full_delivery = "$delivery_date $delivery_time";
	    		}	    	
	    		
	    		$date_close=date("F,d l Y h:ia",strtotime($full_delivery));
	    		$this->msg = Yii::t("singleapp","Sorry but we are closed on [date_close]. Please check merchant opening hours.",array(
	    		  '[date_close]'=>$date_close
	    		));
	    		$this->output();
	    	}    	    	
    	
	    	/*CHECK IF DATE IS HOLIDAY*/
	    	if ( $res_holiday =  Yii::app()->functions->getMerchantHoliday($this->merchant_id)){
	    		if (in_array($delivery_date,$res_holiday)){
	    		   $this->msg=Yii::t("singleapp","were close on [date]",array(
				   	  	   '[date]'=>FunctionsV3::prettyDate($delivery_date)
				   	));
				   	
				   	$close_msg=getOption($this->merchant_id,'merchant_close_msg_holiday');
				   	if(!empty($close_msg)){
		   	  	 	  $this->msg = Yii::t("default",$close_msg,array(
		   	  	 	   '[date]'=>FunctionsV3::prettyDate($delivery_date)
		   	  	 	  ));
		   	  	    }	
	    			$this->output();	
	    		}
	    	}
	    	
	    	$date_now = date("Y-m-d");	    			
			$merchant_preorder= Yii::app()->functions->getOption("merchant_preorder",$this->merchant_id);	    	
			if($merchant_preorder==1){    			   		
				if($date_now!=$delivery_date){
				   $checkout_stats['is_pre_order']=1;
				   if(empty($delivery_time)){    			   	   	    			   	  
					   $this->msg = $this->t("For furure order delivery time is required");
				       $this->output();
				   }
				}	    		
			} else {
				if($date_now!=$delivery_date){
					$this->msg = $this->t("Merchant is not accepting pre-order");
				    $this->output();
				}
			}	    	   
	    			
		} else $error = $checkout_stats['msg'];
		
		if(empty($error)){
			$this->code = 1;
			$this->msg = "OK";
			$details = array();
			$details['is_pre_order'] = $checkout_stats['is_pre_order'];
			if($checkout_stats['is_pre_order']==1){
				$details['message'] = st("This order is for another day. Continue?");
			} else $details['message'] = '';
			
			$this->details = $details;
		} else $this->msg = $error;	
		
		$this->output();
	}
	
	public function actiongetStocks()
	{
		Yii::app()->setImport(array(			
	       'application.modules.inventory.components.*',
        ));	        		        
     
		$this->data = $_POST;		
		   
        $value = isset($this->data['price'])?$this->data['price']:'';
		$item_id = isset($this->data['item_id'])? (integer) $this->data['item_id']:'';
		$with_size = isset($this->data['with_size'])? (integer) $this->data['with_size']:'';
		$merchant_id = $this->merchant_id;
		        									
		if($merchant_id>0 && $item_id>0 ){
			try {
				
				$allow_negative_stock = InventoryWrapper::allowNegativeStock($merchant_id);
				
				$size_id = 0;
								
				if($with_size>0){
					$value = explode("|",$value);
					if(is_array($value) && count($value)>=1){
						$size_id = isset($value[2])?(integer)$value[2]:0;
					}
				}		
				
				$resp = StocksWrapper::getAvailableStocks($merchant_id,$item_id,$size_id);
				
				$this->code = 1; $this->msg = "OK";
				$this->details = array(
				  'next_action'=>"display_stocks",
				  'available_stocks'=>$resp['available_stocks'],
				  'message'=>$resp['message'],
				  'allow_negative_stock'=>$allow_negative_stock
				);			
						
			} catch (Exception $e) {
			   $this->details = array('next_action'=>"item_not_available");
		       $this->msg = Yii::t("inventory",$e->getMessage());
		    }
		} else {
			 $this->details = array('next_action'=>"item_info_not_available");
			 $this->msg = Yii::t("inventory","invalid merchant id or size id");
		}
		$this->output();
	}	
	
	public function actionAddTip()
	{		
		$tip_amount = isset($this->data['tip_amount'])?(float)$this->data['tip_amount']:0;		
		if($resp=SingleAppClass::getCart($this->device_uiid , $this->merchant_id)){
			$cart_id = $resp['cart_id'];
			$subtotal = (float)$resp['cart_subtotal'];			
			$percentage = ($tip_amount/$subtotal)*100;
			$percentage = number_format($percentage/100,4);			
			Yii::app()->db->createCommand()->update("{{singleapp_cart}}",array(
			  'tips'=>$percentage
			),
	  	    'cart_id=:cart_id',
		  	    array(
		  	      ':cart_id'=>$cart_id
		  	    )
	  	    );	  	    
	  	    $this->code = 1;
	  	    $this->msg = "OK";
	  	    $this->details = array();			
		} else $this->msg = st("cart not available");
		$this->output();
	}
	
	public function actionretrievePasswordBySMS()
	{
		$lang = Yii::app()->language;
		
		$phone_number = isset($this->data['user_mobile'])?$this->data['user_mobile']:'';
		if(empty($phone_number)){
			$this->msg = st("Phone number is required");
			$this->output();
		}
		if(strlen($phone_number)<=4){
			$this->msg = st("Invalid phone number");
			$this->output();
		}
		
		try {
		
		   $code = Yii::app()->functions->generateRandomKey(5);
		   $res = FunctionsV3::getCustomerByPhone( str_replace("+","",$phone_number) );
		   $token=md5(date('c').$res['client_id']);  													 
		   
		   FunctionsV3::updateCustomerProfile($res['client_id'],array(
			 'mobile_verification_code'=>$code,
			 'mobile_verification_date'=>FunctionsV3::dateNow(),
			 'lost_password_token'=>$token,
			 'ip_address'=>$_SERVER['REMOTE_ADDR']
		   ));
		   
		   $resp = FunctionsV3::getNotificationTemplate('customer_forgot_password',$lang,'sms');
		   $data = array(
			  'firstname'=>$res['first_name'],
			  'lastname'=>$res['last_name'],
			  'code'=>$code,							  
			);		
			$sms_content = $resp['sms_content'];
			$sms_content = FunctionsV3::replaceTags($sms_content,$data);			
			$sms = Yii::app()->functions->sendSMS($phone_number,$sms_content);
			if($sms['msg']=="process"){
				$this->code=1; $this->msg=st("We have sent verification code in your phone number");
				$this->details = array(							   	  				  
				  'forgot_password_token'=>$token
				);
			} else $this->msg = st("Failed sending sms [error]",array(
			  '[error]'=>$sms['msg']
			));
			
		} catch (Exception $e) {
		   $this->msg = $e->getMessage();
		}	
		$this->output();
	}
	
	public function actionchangePasswordBySMS()
	{		
		$token = isset($this->data['forgot_password_token'])?trim($this->data['forgot_password_token']):'';
    	$sms_code = isset($this->data['sms_code'])?trim($this->data['sms_code']):'';
    	if($res = Yii::app()->functions->getLostPassToken($token)){    		
    		if($res['mobile_verification_code']==$sms_code){ 
    			$new_password = isset($this->data['new_password'])?$this->data['new_password']:'';
    			$confirm_new_password = isset($this->data['confirm_new_password'])?$this->data['confirm_new_password']:'';
    			if(!empty($new_password) && $new_password==$confirm_new_password){
    				
    				 Yii::app()->db->createCommand()->update("{{client}}",
			    		 array(
			    		   'password'=>md5($new_password),
			    		   'ip_address'=>$_SERVER['REMOTE_ADDR'],
			    		   'date_modified'=>FunctionsV3::dateNow()
			    		 )
			    		,
			      	     'client_id=:client_id',
			      	     array(
			      	       ':client_id'=>$res['client_id']
			      	     )
			      	   );   			
			      	   
	    			   $this->code = 1; 
	    			   $this->msg = t("You have successfully change your password");
	    			   
    				
    			} else $this->msg = t("Password is not valid");
    		} else $this->msg = t("Invalid verification code");	
    	} else $this->msg = t("Invalid token");
    	$this->output();
	}		
	
	public function actioncancelBooking()
	{		
		$booking_id = isset($this->data['booking_id'])?(integer)$this->data['booking_id']:0;
		
		if ($client_id = $this->checkToken()){
			if($booking_id>0){
				$pattern = 'booking_id,restaurant_name,number_guest,date_booking,time,booking_name,email,mobile,instruction,status,merchant_remarks,sitename,siteurl';
    	        $pattern = explode(",",$pattern);    	        
    	        $lang = Yii::app()->language;
    	        if ($res = FunctionsV3::getBookingByIDWithDetails($booking_id)){
    	        	if($res['request_cancel']>=1){
		    			$this->msg = st("You have already request to cancel this booking");
		    		    $this->output();
		    		}
		    		
		    		$res['sitename'] = getOptionA('website_title');
    		        $res['siteurl'] = websiteUrl();
    		        $res = Yii::app()->request->stripSlashes($res);
    		        
    		        $merchant_id = $res['merchant_id'];
    		        $merchant_email = getOption($merchant_id,'merchant_notify_email');
    		        $sender = getOptionA('global_admin_sender_email');
    		        $email_provider  = getOptionA('email_provider');
    		        
    		        /*SEND EMAIL TO MERCHANT*/
    		        if(!empty($merchant_email)){    		        
    		        	$email = getOptionA('booking_request_cancel_email');    		
			    		$subject = getOptionA('booking_request_cancel_tpl_subject_'.$lang);
			    		$content = getOptionA('booking_request_cancel_tpl_content_'.$lang);
			    		foreach ($pattern as $val) {    			
			    			$content = FunctionsV3::smarty($val, isset($res[$val])?$res[$val]:'' ,$content);
			    			$subject = FunctionsV3::smarty($val, isset($res[$val])?$res[$val]:'' ,$subject);
			    		}
			    		$merchant_email = explode(",",$merchant_email);			    		
    		        	
			    		if($email==1 && is_array($merchant_email) && count($merchant_email)>=1){
			    			foreach ($merchant_email as $_mail) {
			    				$params = array(
			    				  'email_address'=>$_mail,
			    				  'sender'=>$sender,
			    				  'subject'=>$subject,
			    				  'content'=>$content,
			    				  'date_created'=>FunctionsV3::dateNow(),
			    				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			    				  'email_provider'=>$email_provider,	    				  
			    				);
			    				Yii::app()->db->createCommand()->insert("{{email_logs}}",$params);
			    			}
			    		}
    		        }
    		        
    		        /*SMS*/	
    		        $balance=Yii::app()->functions->getMerchantSMSCredit($merchant_id);	
    		        $phone = getOption($merchant_id,'merchant_cancel_order_phone');	    		    		        
    		        $sms_enabled = getOptionA('booking_request_cancel_sms');
    		        
		    		if(!empty($phone) && $balance>0 && $sms_enabled==1){	    		    			
		    		    $sms_content = getOptionA('booking_request_cancel_sms_content_'.$lang);
		    		    foreach ($pattern as $val) {    			
		    			   $sms_content = FunctionsV3::smarty($val, isset($res[$val])?$res[$val]:'' ,$sms_content);	    			   
		    		    }
		    		    $params = array(
		    		      'merchant_id'=>$merchant_id,
		    		      'contact_phone'=>$phone,
		    		      'sms_message'=>$sms_content,
		    		      'date_created'=>FunctionsV3::dateNow(),
		    		      'ip_address'=>$_SERVER['REMOTE_ADDR']
		    		    );
		    		    Yii::app()->db->createCommand()->insert("{{sms_broadcast_details}}",$params);    		    
		    		}
		    		
		    		/*PUSH*/	    		
		    		if(Yii::app()->db->schema->getTable("{{mobile_device_merchant}}")){
		    			
		    			$push_enabled=getOptionA('booking_request_cancel_sms');
		    			$push_title=getOptionA('booking_request_cancel_push_title_'.$lang);
		    			$push_message=getOptionA('booking_request_cancel_push_content_'.$lang);
		    			
		    			$resp = Yii::app()->db->createCommand()
				          ->select()
				          ->from('{{mobile_device_merchant}}')   
				          ->where("merchant_id=:merchant_id AND enabled_push=:enabled_push AND status=:status",array(
				             ':merchant_id'=>$merchant_id,			             
				             ':enabled_push'=>1,
				             ':status'=>'active'
				          )) 
				          ->limit(1)
				          ->queryAll();	
				        if($resp && $push_enabled==1){
				        	
				        	foreach ($pattern as $val) {    			
    			               $push_title = FunctionsV3::smarty($val, isset($res[$val])?$res[$val]:'' ,$push_title);
    			               $push_message = FunctionsV3::smarty($val, isset($res[$val])?$res[$val]:'' ,$push_message);
    		                }
    		                    		        
				        	foreach ($merchant_resp as $merchant_device_id) {
				        		$params_merchant = array(
				        		  'merchant_id'=>(integer)$merchant_id,
				        		  'user_type'=>$merchant_device_id['user_type'],
				        		  'merchant_user_id'=>(integer)$merchant_device_id['merchant_user_id'],
				        		  'device_platform'=>$merchant_device_id['device_platform'],
				        		  'device_id'=>$merchant_device_id['device_id'],
				        		  'push_title'=>$push_title,
				        		  'push_message'=>$push_message,
				        		  'date_created'=>FunctionsV3::dateNow(),
				        		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				        		  'booking_id'=>(integer)$booking_id
				        		);
				        		Yii::app()->db->createCommand()->insert("{{mobile_merchant_pushlogs}}",$params_merchant);
				        	}
				        }
		    		}
		    		
		    		Yii::app()->db->createCommand()->update("{{bookingtable}}",
		    		 array(
		    		   'request_cancel'=>1,
		    		   'status'=>'request_cancel_booking'
		    		 )
		    		,
		      	     'booking_id=:booking_id',
		      	     array(
		      	       ':booking_id'=>$booking_id
		      	     )
		      	   );
    		        
    		        $this->code = 1;
    		        $this->msg = st("Your request has been sent to merchant");
		    		
    	        } else $this->msg = t("Booking id not found");    	        
			} else $this->msg = st("Invalid booking id");
		}		
		$this->output();
	}	
	
	public function actionsaveSubsribe()
	{
		$token = isset($this->data['token'])?$this->data['token']:'';
		if($res = SingleAppClass::getCustomerByToken($token)){
			$client_id = $res['client_id'];		
			$params = array(
			  'subscribe_topic'=>isset($this->data['subscribe_topic'])?(integer)$this->data['subscribe_topic']:0,
			  'date_modified'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);		
			
			$up =Yii::app()->db->createCommand()->update("{{singleapp_device_reg}}",$params,
	  	    'device_uiid=:device_uiid AND client_id=:client_id',
		  	    array(
		  	      ':device_uiid'=>$this->device_uiid,
		  	      ':client_id'=>$client_id
		  	    )
	  	    );
	  	    if($up){
		  	    $this->code = 1;
				$this->msg = $this->t("Push settings updated");
				$this->details=array(
				  'subscribe_topic'=>$params['subscribe_topic']
				);
	  	    } else $this->msg = $this->t("Cannot update records, please try again later"); 
		} else {
			$this->code = 3;
			$this->msg = $this->t("token not found");		
		}
		$this->output();
	}	

} /*END Class*/
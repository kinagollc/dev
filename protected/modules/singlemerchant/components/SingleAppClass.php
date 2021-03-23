<?php
class SingleAppClass
{
	public static function moduleBaseUrl()
	{
		return Yii::app()->getBaseUrl(true)."/protected/modules/singlemerchant";
	}
	
	public static function moduleName()
	{
		return Yii::app()->controller->module->id;
	}
	
	public static function getMerchantId()
	{
		return Yii::app()->functions->getMerchantID();	
	}
	
	public static function registrationType()
	{
		return 'fcm';
	}
	
	public static function t($message='')
	{
		return Yii::t("singleapp",$message);
	}
	
	public static function validateKeys($keys='')
	{	
		if(empty($keys)){
    		return false;
    	}
    	
		 $DbExt=new DbExt; 
		 $stmt="
		 SELECT 
		 merchant_id,
		 single_app_keys,
		 restaurant_name,
		 status
		 FROM
		 {{merchant}}
		 WHERE
		 single_app_keys=".FunctionsV3::q($keys)."
		 LIMIT 0,1
		 "; 
		 if ($res = $DbExt->rst($stmt)){
		 	unset($DbExt);
		 	return $res[0];
		 }
		 unset($DbExt);
		 return false;
	}
	
	public static function getCategory($merchant_id='',$page=0, $limit=0,$all=false)
	{
		if(empty($merchant_id)){
    		return false;
    	}
    	
    	$and = '';    	
    	$todays_day = date("l");
		$todays_day = !empty($todays_day)?strtolower($todays_day):'';
				
		$enabled_category_sked = getOption($merchant_id,'enabled_category_sked'); 
		if($enabled_category_sked==1){			
		    $and .= " AND $todays_day='1' ";
		}
		    	       		
		$stmt="
		SELECT 
		cat_id,
		category_name,
		category_description,
		photo,
		dish,
		category_name_trans,
		category_description_trans
		FROM
		{{category}}
		WHERE 
		merchant_id= ".FunctionsV3::q($merchant_id)."
		AND status in ('publish','published')	
		$and	
		ORDER BY sequence ASC
		LIMIT $page,$limit
		";		
		if($all){
			$stmt="
			SELECT 
			cat_id,
			category_name,
			category_description,
			photo,
			dish,
			category_name_trans,
			category_description_trans
			FROM
			{{category}}
			WHERE 
			merchant_id= ".FunctionsV3::q($merchant_id)."
			AND status in ('publish','published')	
			$and	
			ORDER BY sequence ASC		
		    ";		
		}
		
		
        $trans=getOptionA('enabled_multiple_translation'); 
        $new_data = array();
        $p = new CHtmlPurifier();
                
        if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
        	$res = Yii::app()->request->stripSlashes($res);
        	
        	if ( $trans==2 && isset($_GET['lang'])){        		
        		foreach ($res as $val) {        			
        			$category_name_trans = json_decode($val['category_name_trans'],true);
        			$val['category_name_trans']=$category_name_trans;
        			        			        			
        			$val['category_name']=stripslashes(qTranslate($val['category_name'],'category_name',$val));
        			
        			if(!empty($val['photo'])){
        			   $val['photo_url'] = self::getImage($val['photo']);
        			} else $val['photo_url'] = self::getImage(getOption($merchant_id,'singleapp_default_image'),'default_cuisine.png');
        			        			
        			$category_description = json_decode($val['category_description_trans'],true);        			
        			$val['category_description_trans']=$category_description; 
        			$val['category_description'] = qTranslate($val['category_description'],'category_description',$val);
        			$val['category_description'] = $p->purify($val['category_description']);
        			        			
        			$val['dish_list'] = self::getDishPics(isset($val['dish'])?$val['dish']:'');	        	
        			
        			if($all){
        				unset($val['photo']);
        				unset($val['dish']);
        				unset($val['category_description']);
        				unset($val['category_name_trans']);
        				unset($val['category_description_trans']);
        				unset($val['photo_url']);
        				unset($val['dish_list']);
        				$item_count = self::getItemCountByCategory($val['cat_id']);
						$val['item_count']= st("[count] item",array(
						  '[count]'=>$item_count
						));
        			}
        					
        			$new_data[]= $val;
        		}
        	} else {
        		foreach ($res as $val) {
        			$val['category_name']=stripslashes($val['category_name']);
        			
        			if(!empty($val['photo'])){
        			   $val['photo_url'] = self::getImage($val['photo']);
        			} else $val['photo_url'] = self::getImage(getOption($merchant_id,'singleapp_default_image'),'default_cuisine.png');
        			
        			$val['category_description'] = $p->purify($val['category_description']);
        			$val['dish_list'] = self::getDishPics(isset($val['dish'])?$val['dish']:'');	     
        			
        			if($all){
        				unset($val['photo']);
        				unset($val['dish']);
        				unset($val['category_description']);
        				unset($val['category_name_trans']);
        				unset($val['category_description_trans']);
        				unset($val['photo_url']);
        				unset($val['dish_list']);
        				$item_count = self::getItemCountByCategory($val['cat_id']);
						$val['item_count']= st("[count] item",array(
						  '[count]'=>$item_count
						));
        			}
        			   			
        			$new_data[]= $val;
        		}
        	}
        	return $new_data;        	        	
	    }
	    return false;
	}	
	
	public static function getImage($image='', $default="default-logo.png")
	{	
		$url='';			
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload";				
		
		if (!empty($image)){						
			if (file_exists($path_to_upload."/$image")){							
				$default=$image;				
				$url = Yii::app()->getBaseUrl(true)."/upload/$default";
			} else $url=self::moduleBaseUrl()."/assets/images/$default";
		} else $url=self::moduleBaseUrl()."/assets/images/$default";
		return $url;
	}
	
	public static function getImage2($image='')
	{	
		$url='';			
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload";				
		
		if (!empty($image)){			
			if (file_exists($path_to_upload."/$image")){														
				$url = Yii::app()->getBaseUrl(true)."/upload/$image";
			}
		} 
		return $url;
	}	
	
	public static function getAvatar($client_id='')
    {
    	
    	if($client_id>0){
	    	if ( $res= Yii::app()->functions->getClientInfo($client_id) ){
	    		$file=$res['avatar'];
	    	} else $file='avatar.jpg';
    	} else $file='avatar.jpg';
    	
    	if (empty($file)){
    		$file='avatar.jpg';
    	}
    	    	    
    	$path=Yii::getPathOfAlias('webroot')."/upload/$file";
    	
    	if ( file_exists($path) ){       		 		    	
    		return Yii::app()->getBaseUrl(true)."/upload/$file";
    	} else return websiteUrl()."/assets/images/avatar.jpg";    	
    }
    
    public static function getAvatar2($file='')
    {    	    	    	
    	if (empty($file)){
    		$file='avatar.jpg';
    	}
    	    	    
    	$path=Yii::getPathOfAlias('webroot')."/upload/$file";
    	
    	if ( file_exists($path) ){       		 		    	
    		return Yii::app()->getBaseUrl(true)."/upload/$file";
    	} else return websiteUrl()."/assets/images/avatar.jpg";    	
    }
	
    public static function getGallerPicURL($filename='')
    {
    	$path=Yii::getPathOfAlias('webroot')."/upload/$filename";  
    	if(file_exists($path)){
    		return Yii::app()->getBaseUrl(true)."/upload/$filename";
    	} 
    	return false;
    }
    
	public static function getDishPics($list='')
    {
    	$dish_list = array();
    	$dish = json_decode($list,true);
		if(is_array($dish) && count($dish)>=1){
			foreach ($dish as $dish_id) {
				if($dish_info = Yii::app()->functions->GetDish($dish_id)){								
					if ($icon_link = self::getGallerPicURL($dish_info['photo'])){
						$dish_list[]=$icon_link;
					}							
				}						
			}
			
		} else $dish_list='';
		return $dish_list;
    }
    
    public static function getPrices($price='',$discount=0)
    {    	    	
    	$new_price = array();
    	$price = !empty($price)? json_decode($price,true) : false;
    	if(is_array($price) && count($price)>=1){
    		foreach ($price as $size_id =>  $presyo) {
    			//dump($size_id."=>".$presyo);
    			$size_name = '';
    			if ($resp = Yii::app()->functions->getSize($size_id)){    		
    						    				
    				$size_name_trans = json_decode($resp['size_name_trans'],true);
    				$resp['size_name_trans']=$size_name_trans;    				
    				$resp['size_name']=qTranslate($resp['size_name'],'size_name',$resp);        	
    				
    				$size_name = $resp['size_name'];
    			}
    			
    			$formatted_price='';
    			if(!empty($size_name)){
    				//$formatted_price = "$size_name ".FunctionsV3::prettyPrice($presyo);
    				$formatted_price = FunctionsV3::prettyPrice($presyo);
    			} else $formatted_price = FunctionsV3::prettyPrice($presyo);
    			
    			$discount_price = 0;
    			if($discount>0.0001){
    				$discount_price = $presyo-$discount;
    			}
    			
    			$new_price []  = array(
    			  'price'=>$presyo,
    			  'formatted_price'=>$formatted_price,
    			  'discount'=>$discount,
    			  'discount_price'=>$discount_price,
    			  'formatted_discount_price'=>FunctionsV3::prettyPrice($discount_price),
    			  'size'=>$size_name,
    			  'size_id'=>$size_id,
    			  'size_trans'=>'',
    			);
    		}
    		return $new_price;
    	}
    	return false;
    }
    
    public static function getCart($device_id='', $merchant_id='')
    {
    	if(empty($device_id)){
    		return false;
    	}    	   
    	    	
    	$merchant_id = (integer) $merchant_id;    	
    	
    	$stmt="SELECT * FROM
    	{{singleapp_cart}}
    	WHERE
    	device_id=".q($device_id)."
    	AND merchant_id = ".q($merchant_id)."
    	LIMIT 0,1
    	";
    	if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
    		return $res;
    	}
    	return false;
    }    
    
    public static function removeVoucher($device_id='')
    {
    	if(empty($device_id)){
    		return false;
    	}
    	
    	$DbExt=new DbExt;
    	$params = array(
    	  'date_modified'=>FunctionsV3::dateNow(),
    	  'voucher_details'=>''
    	);
    	$DbExt->updateData("{{singleapp_cart}}",$params,'device_id',$device_id);
    }
    
    public static function removeTip($device_id='')
    {    	
    	if(empty($device_id)){
    		return false;
    	}    	    	
    	$params = array(
    	  'date_modified'=>FunctionsV3::dateNow(),    	  
    	  'tips'=>0,
    	  'remove_tip'=>1
    	);    	
    	$up =Yii::app()->db->createCommand()->update("{{singleapp_cart}}",$params,
  	    'device_id=:device_id',
	  	    array(
	  	      ':device_id'=>$device_id
	  	    )
  	    );
    }
    
    public static function createTimeRange($start, $end, $interval = '30 mins', $format = '12') {
	    $startTime = strtotime($start); 
	    $endTime   = strtotime($end);
	    $returnTimeFormat = ($format == '12')?'g:i:s A':'G:i:s';
	
	    $current   = time(); 
	    $addTime   = strtotime('+'.$interval, $current); 
	    $diff      = $addTime - $current;
	
	    $times = array(); 	    
	    while ($startTime < $endTime) { 
	        $times[] = date($returnTimeFormat, $startTime); 
	        $startTime += $diff; 
	    } 
	    $times[] = date($returnTimeFormat, $startTime); 
	    return $times; 
	}
	
	public static function parseValidatorError($error='')
	{
		$error_string='';
		if (is_array($error) && count($error)>=1){
			foreach ($error as $val) {
				$error_string.="$val\n";
			}
		}
		return $error_string;		
	}		
	
    public static function generateUniqueToken($length,$unique_text=''){	
		$key = '';
	    $keys = array_merge(range(0, 9), range('a', 'z'));	
	    for ($i = 0; $i < $length; $i++) {
	        $key .= $keys[array_rand($keys)];
	    }	
	    return $key.md5($unique_text);
	}	
	
    public static function getCustomerByToken($token='', $is_active=true)
    {
    	if(empty($token)){
    		return false;
    	}
    	    	
    	$and="";
    	if($is_active){
    		$and=" AND status IN ('active')";
    	}
    	    	
    	$stmt="SELECT * FROM
    	{{client}}
    	WHERE
    	token=".FunctionsV3::q($token)."  
    	$and  	
    	LIMIT 0,1
    	";
    	if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
    		$res = Yii::app()->request->stripSlashes($res);
    		return $res;
    	}
    	return false;
    }    	
    
    public static function getCustomerByTokenAndDevice($token='', $device_uiid='',$is_active=true)
    {
    	if(empty($token)){
    		return false;
    	}
    	    	
    	$and="";
    	if($is_active){
    		$and=" AND a.status IN ('active')";
    	}
    	    	
    	$stmt="SELECT 
    	a.client_id,
    	a.first_name,
    	a.last_name,
    	concat(a.first_name,' ',a.last_name) as full_name,
    	a.email_address,
    	a.contact_phone,
    	a.social_strategy,
    	a.avatar,
    	a.single_app_merchant_id,
    	
    	b.device_uiid,
    	b.device_id,
    	b.device_platform,
    	b.push_enabled,
    	b.subscribe_topic,    	
        b.stic_dark_theme       
    	
    	FROM
    	{{client}} a
    	LEFT JOIN {{singleapp_device_reg}} b
    	ON
    	a.client_id = b.client_id
    	
    	WHERE
    	a.token=".FunctionsV3::q($token)."  
    	$and  	
    	LIMIT 0,1
    	";    	
    	if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
    		$res = Yii::app()->request->stripSlashes($res);
    		return $res;
    	}
    	return false;
    }    	    
    
    public static function clearCart($device_id='')
    {    	
    	if(empty($device_id)){
    		return false;
    	}
    	
    	$db=new DbExt;
    	$db->qry("DELETE FROM
    	{{singleapp_cart}}
    	WHERE
    	device_id=".FunctionsV3::q($device_id)."
    	");
    }
    
    public static function getCartContent($device_id='',$data=array())
    {
    	if(empty($device_id)){
    		return false;
    	}
    	
    	$merchant_id = isset($data['merchant_id'])? (integer) $data['merchant_id']:0;
    	
    	if($res=SingleAppClass::getCart($device_id , $merchant_id)){
    		$cart=json_decode($res['cart'],true);
    		
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
			
			/*DELIVERY FEE*/
			unset($_SESSION['shipping_fee']);
			if($res['delivery_fee']>0.0001){
				$data['delivery_charge']=$res['delivery_fee'];
			}
								
			$cart_details = $res;
			unset($cart_details['cart']);		
			unset($cart_details['device_id']);
			unset($cart_details['cart_id']);			
			
			Yii::app()->functions->displayOrderHTML( $data,$cart );
			$code = Yii::app()->functions->code;
			$msg  = Yii::app()->functions->msg;
			if ($code==1){
				$details = Yii::app()->functions->details['raw'];
				return $details;
			}
    	}
    	return false;
    }
    
    public static function appLogin($username='', $password='')
    {
    	if(empty($username)){
    		return false;
    	}
    	if(empty($password)){
    		return false;
    	}
    	
    	$db=new DbExt;
    	$stmt="
    	SELECT 
    	client_id,
    	first_name,
    	last_name,
    	email_address,
    	token,
    	contact_phone
    	FROM {{client}}
    	WHERE
    	email_address = ".FunctionsV3::q($username)."
    	AND
    	password = ".FunctionsV3::q(md5($password))."
    	AND
    	status IN ('active')
    	LIMIT 0,1
    	";    	
    	if ($res=$db->rst($stmt)){
    		return $res[0];
    	} else {
    		$stmt="
	    	SELECT 
	    	client_id,
	    	first_name,
	    	last_name,
	    	email_address,
	    	token,
	    	contact_phone
	    	FROM {{client}}
	    	WHERE
	    	contact_phone LIKE ".FunctionsV3::q( "%".$username)."
	    	AND
	    	password = ".FunctionsV3::q(md5($password))."
	    	AND
    	    status IN ('active')
	    	LIMIT 0,1
	    	";    	    		
    		if ($res=$db->rst($stmt)){
    			return $res[0];
    		}
    	}
    	return false;
    }
    
    public static function getCreditCards($client_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	
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
    	";
    	if(isset($_GET['debug'])){
    		dump($stmt);
    	}
    	if ($res=$db->rst($stmt)){
    		return $res;
    	}
        return false;
    }
    
    public static function getAddressBookByClient($client_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	
    	$data = array();
    	$db_ext=new DbExt;    	
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
    	       ORDER BY street ASC    	       
    	";    	    	
    	if ($res=$db_ext->rst($stmt)){    		
    		foreach ($res as $val) {
    			$date_added = FunctionsV3::prettyDate($val['date_created'])." ".FunctionsV3::prettyTime($val['date_created']);
    			$val['date_added'] = st("Added [date]",array(
    			  '[date]'=>$date_added
    			));
    			$data[]=$val;
    		}
    		return $data;
    	}
    	return false;
    } 	       
    
    public static function getMerchantLogo($merchant_id='')
	{		
		if ( !$logo = getOption($merchant_id,'merchant_photo') ){			
			$logo = Yii::app()->functions->getOptionAdmin('mobile_default_image_not_available');
			if (empty($logo)){
			   $logo="default-logo.png";
			}
		}		
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload/";			
		if (file_exists($path_to_upload."/$logo")){
			return Yii::app()->getBaseUrl(true)."/upload/$logo";
		} 
		return self::moduleBaseUrl()."/assets/images/$logo";				
	}     
    
	public static function receiptFormater($label='', $val='')
	{
		return array(
		  'label'=>self::t($label),
		  'value'=>$val
		);
	}
	
    public static function getOpeningHours($merchant_id='')
	{
		if(empty($merchant_id)){
    		return false;
    	}
    	
        $stores_open_day=Yii::app()->functions->getOption("stores_open_day",$merchant_id);
		$stores_open_starts=Yii::app()->functions->getOption("stores_open_starts",$merchant_id);
		$stores_open_ends=Yii::app()->functions->getOption("stores_open_ends",$merchant_id);
		$stores_open_custom_text=Yii::app()->functions->getOption("stores_open_custom_text",$merchant_id);
		
		$stores_open_day=!empty($stores_open_day)?(array)json_decode($stores_open_day):false;
		$stores_open_starts=!empty($stores_open_starts)?(array)json_decode($stores_open_starts):false;
		$stores_open_ends=!empty($stores_open_ends)?(array)json_decode($stores_open_ends):false;
		$stores_open_custom_text=!empty($stores_open_custom_text)?(array)json_decode($stores_open_custom_text):false;
		
		
		$stores_open_pm_start=Yii::app()->functions->getOption("stores_open_pm_start",$merchant_id);
		$stores_open_pm_start=!empty($stores_open_pm_start)?(array)json_decode($stores_open_pm_start):false;
		
		$stores_open_pm_ends=Yii::app()->functions->getOption("stores_open_pm_ends",$merchant_id);
		$stores_open_pm_ends=!empty($stores_open_pm_ends)?(array)json_decode($stores_open_pm_ends):false;		
												
		$open_starts='';
		$open_ends='';
		$open_text='';
		$data=array();
				
		if (is_array($stores_open_day) && count($stores_open_day)>=1){
			foreach ($stores_open_day as $val_open) {	
				if (array_key_exists($val_open,(array)$stores_open_starts)){
					$open_starts=timeFormat($stores_open_starts[$val_open],true);
				}							
				if (array_key_exists($val_open,(array)$stores_open_ends)){
					$open_ends=timeFormat($stores_open_ends[$val_open],true);
				}							
				if (array_key_exists($val_open,(array)$stores_open_custom_text)){
					$open_text=$stores_open_custom_text[$val_open];
				}					
				
				$pm_starts=''; $pm_ends=''; $pm_opens='';
				if (array_key_exists($val_open,(array)$stores_open_pm_start)){
					$pm_starts=timeFormat($stores_open_pm_start[$val_open],true);
				}											
				if (array_key_exists($val_open,(array)$stores_open_pm_ends)){
					$pm_ends=timeFormat($stores_open_pm_ends[$val_open],true);
				}												
				
				$full_time='';
				if (!empty($open_starts) && !empty($open_ends)){					
					$full_time=$open_starts."-".$open_ends;
				}			
				if (!empty($pm_starts) && !empty($pm_ends)){
					if ( !empty($full_time)){
						$full_time.="x";
					}				
					$full_time.="$pm_starts-$pm_ends";
				}												
								
				$data[$val_open]=array(
				  'day'=>$val_open,
				  'hours'=>$full_time				  
				);
				
				$open_starts='';
		        $open_ends='';
		        $open_text='';
			}
			return $data;
		}			
		return false;		
	}	
	
	public static function getBookAddress($street='', $city='', $state='' )
	{
		$db=new DbExt;
		$stmt="SELECT * FROM
		{{address_book}}
		WHERE
		street=".FunctionsV3::q($street)."
		AND
		city = ".FunctionsV3::q($city)."
		AND
		state = ".FunctionsV3::q($state)."
		LIMIT 0,1
		";
		if ($res = $db->rst($stmt)){
			return $res;
		}
		return false;
	}
	
	public static function getPaypalCredentials($merchant_id='')
	{
		if(empty($merchant_id)){
    		return false;
    	}
    	
		if ( FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
		   $paypal_mobile_enabled=getOptionA('adm_paypal_mobile_enabled');
	   	   $paypal_fee=getOptionA("admin_paypal_fee");	   	   
	   	   $paypal_mobile_mode=getOptionA('adm_paypal_mobile_mode');
	   	   $paypal_client_id=getOptionA('adm_paypal_mobile_clientid');
		} else {
		   $paypal_mobile_enabled=getOption($merchant_id,'mt_paypal_mobile_enabled');
	   	   $paypal_fee=getOption($merchant_id,'merchant_paypal_fee');	   	   
	   	   $paypal_mobile_mode=getOption($merchant_id,'mt_paypal_mobile_mode');
	   	   $paypal_client_id=getOption($merchant_id,'mt_paypal_mobile_clientid');
		}		
		if ($paypal_mobile_enabled=="yes"){
			return  array(
			  'enabled'=>$paypal_mobile_enabled,
			  'fee'=>$paypal_fee,
			  'mode'=>strtolower($paypal_mobile_mode),
			  'client_id'=>$paypal_client_id
			);
		}
		return false;
	}
	
	public static function getStripeCredentials($merchant_id='')
	{
		if(empty($merchant_id)){
    		return false;
    	}
    	
		$enabled = false; $mode = ''; $secret_key=''; $publish_key='';
		if ( FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
			
			$enabled = getOptionA('admin_stripe_enabled');
			if($enabled=="yes"){
				$enabled=true;
			}
			$mode = strtolower(getOptionA('admin_stripe_mode'));
			if($mode=="sandbox"){
			   $secret_key = getOptionA('admin_sanbox_stripe_secret_key');
			   $publish_key = getOptionA('admin_sandbox_stripe_pub_key');
			} else if ($mode=="live") {						
			   $secret_key = getOptionA('admin_live_stripe_secret_key');
			   $publish_key = getOptionA('admin_live_stripe_pub_key');
			}		
		} else {
			$enabled = getOption($merchant_id,'stripe_enabled');
			if($enabled=="yes"){
				$enabled=true;
			}
			$mode = strtolower(getOption($merchant_id,'stripe_mode'));
			if($mode=="sandbox"){
			   $secret_key = getOption($merchant_id,'sanbox_stripe_secret_key');
			   $publish_key = getOption($merchant_id,'sandbox_stripe_pub_key');
			} else if ($mode=="live") {						
			   $secret_key = getOption($merchant_id,'live_stripe_secret_key');
			   $publish_key = getOption($merchant_id,'live_stripe_pub_key');
			}		
		}
		
		if ($enabled && !empty($secret_key) && !empty($publish_key)){
			return array(
			  'mode'=>$mode,
			  'secret_key'=>$secret_key,
			  'publish_key'=>$publish_key
			);
		}
		return false;
	}
	
	public static function getBraintreeCredentials($merchant_id='')
	{
		
		if(empty($merchant_id)){
    		return false;
    	}
    	
		$enabled = false; $mode = ''; $mtid=''; $public_key=''; $private_key='';
						
		if ( FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
			$enabled = getOptionA('admin_btr_enabled');
			$mode = getOptionA('admin_btr_mode');
			$mode = strtolower($mode);
			if($mode=="sandbox"){
				$mtid = getOptionA('sanbox_brain_mtid');
				$public_key = getOptionA('sanbox_brain_publickey');
				$private_key = getOptionA('sanbox_brain_privateckey');
			} else {
				$mtid = getOptionA('live_brain_mtid');
				$public_key = getOptionA('live_brain_publickey');
				$private_key = getOptionA('live_brain_privateckey');
			}
		} else {
			$enabled = getOption($merchant_id,'merchant_btr_enabled');
			$mode = getOption($merchant_id,'merchant_btr_mode');
			$mode = strtolower($mode);
			if($mode=="sandbox"){
				$mtid = getOption($merchant_id,'mt_sanbox_brain_mtid');
				$public_key = getOption($merchant_id,'mt_sanbox_brain_publickey');
				$private_key = getOption($merchant_id,'mt_sanbox_brain_privateckey');
			} else {
				$mtid = getOption($merchant_id,'mt_live_brain_mtid');
				$public_key = getOption($merchant_id,'mt_live_brain_publickey');
				$private_key = getOption($merchant_id,'mt_live_brain_privateckey');
			}
		}
		
		if($enabled==2){
			return array(
			  'mode'=>$mode,
			  'merchant_id'=>$mtid,
			  'public_key'=>$public_key,
			  'private_key'=>$private_key,
			);
		}			
		return false;
	}
	
	public static function getOrderDetails($order_id='')
	{
		if(empty($order_id)){
    		return false;
    	}
    	
		$_GET['backend']=''; 
	    if ($res = Yii::app()->functions->getOrder2($order_id) ){
	    	return $res;
	    }
	    return false;
	}
		
	public static function deliveryDateList($merchant_id='')
	{		
		$dates=array();
		$day=Yii::app()->functions->getOption("stores_open_day",$merchant_id);
		$day_open=!empty($day)?json_decode($day,true):false;
			
		if(is_array($day_open) && count($day_open)>=1){
			
			for ($i = 0; $i <= 30; $i++) {				
				$key=date("Y-m-d",strtotime("+$i day"));
				$key_day = strtolower(date("l",strtotime($key)));
				if(in_array($key_day,(array)$day_open)){
					$dates[$key] = FunctionsV3::prettyDate($key);
				}
			}
		} else {
			for ($i = 0; $i <= 30; $i++) {				
				$key=date("Y-m-d",strtotime("+$i day"));
				$dates[$key] = date("D F d Y",strtotime("+$i day"));
			}
		}
		return $dates;
	}
	
	public static function tipList()
	{
		return array(
	    	   '0.1'=>"10%",
	    	   '0.15'=>"15%",
	    	   '0.2'=>"20%",
	    	   '0.25'=>"25%"    	   
	    	);	
	}		
	
	public static function getAppLanguage()
	{
		$translation=array();
		$enabled_lang=FunctionsV3::getEnabledLanguage();		
		if(is_array($enabled_lang) && count($enabled_lang)>=1){			
			$path=Yii::getPathOfAlias('webroot')."/protected/messages";    	
    	    $res=scandir($path);
    	    if(is_array($res) && count($res)>=1){
    	    	foreach ($res as $val) {
    	    		if(in_array($val,$enabled_lang)){
    	    			$lang_path=$path."/$val/singleapp.php";        	    			
    	    			if (file_exists($lang_path)){    	    				
    	    				$temp_lang='';
		    				$temp_lang=require_once($lang_path);   		    				
		    				foreach ($temp_lang as $key=>$val_lang) {
		    					$translation[$key][$val]=$val_lang;
		    				}
    	    			}
    	    		}
    	    	}
    	    }    	     	    
		}
		return $translation;
	}	
	
	public static function generateMerchantKeys()
	{
		$single_app_keys=md5(Yii::app()->functions->generateCode(50));
		$db = new DbExt();
		$stmt = "
		SELECT single_app_keys
		FROM
		{{merchant}}
		WHERE single_app_keys = ".FunctionsV3::q($single_app_keys)."
		LIMIT 0,1
		";
		if ( $res = $db->rst($stmt)){
			return self::generateMerchantKeys();
		} 
		return $single_app_keys;
	}
	
    public static function handleAll($order_id='', $merchant_id='', 
    $client_id='',$device_id='', $order_status='')
	{
		self::sendNotifications($order_id);
		
		/*POINTS ADDON*/
		if (FunctionsV3::hasModuleAddon("pointsprogram")){			
			if($res=SingleAppClass::getCart($device_id , $merchant_id)){
				$points_earn = $res['points_earn'];
				PointsProgram::saveEarnPoints($points_earn,$client_id,$merchant_id,$order_id,'',$order_status);
				
				if ($res['points_apply']>=0.0001){
					PointsProgram::saveExpensesPoints(
					  $res['points_apply'],
					  $res['points_amount'],
					  $client_id,
					  $merchant_id,
					  $order_id,
					  ''
					);
				}
			}
		}
		
		/*SEND FAX*/
        Yii::app()->functions->sendFax($merchant_id,$order_id);
        
        if ( FunctionsV3::hasModuleAddon("driver")){
	     	Yii::app()->setImport(array(			
			  'application.modules.driver.components.*',
			));							
			Driver::addToTask($order_id);	     
	    }
	    
	     /*inventory*/		
		 if(SingleAppClass::inventoryEnabled($merchant_id)){
		 	try {		    					    	  
			   InventoryWrapper::insertInventorySale($order_id,$order_status);	
			} catch (Exception $e) {										    
			  // echo $e->getMessage();				    	  
			}		    					    	 
		 }
	    
	    /*CLEAR CART*/
	    SingleAppClass::clearCart($device_id); 
	}

	public static function sendNotifications($order_id='')
	{
		$_GET['backend']=true; $print=array();
		if ( $data=Yii::app()->functions->getOrder2($order_id)){
			$merchant_id=$data['merchant_id'];
	        $json_details=!empty($data['json_details'])?json_decode($data['json_details'],true):false;				
	        if ( $json_details !=false){
	        	Yii::app()->functions->displayOrderHTML(array(
	        	  'order_id'=>$order_id,
				  'merchant_id'=>$data['merchant_id'],
				  'delivery_type'=>$data['trans_type'],
				  'delivery_charge'=>$data['delivery_charge'],
				  'packaging'=>$data['packaging'],
				  'cart_tip_value'=>$data['cart_tip_value'],
				  'cart_tip_percentage'=>$data['cart_tip_percentage']/100,
				  'card_fee'=>$data['card_fee'],
				  'tax'=>$data['tax'],
				  'points_discount'=>isset($data['points_discount'])?$data['points_discount']:'' /*POINTS PROGRAM*/,
				  'voucher_amount'=>$data['voucher_amount'],
				  'voucher_type'=>$data['voucher_type']
				  ),$json_details,true);
	        }
	        
	        $print[]=array( 'label'=> t("Customer Name"), 'value'=>$data['full_name'] );
	        $print[]=array( 'label'=> t("Merchant Name"), 'value'=>$data['merchant_name']);
	        if (isset($data['abn']) && !empty($data['abn'])){
	        	$print[]=array(
		         'label'=>Yii::t("default","ABN"),
		         'value'=>$data['abn']
		        );
	        }
	        $print[]=array('label'=>Yii::t("default","Telephone"),'value'=>$data['merchant_contact_phone']);
	        
	        $merchant_info=Yii::app()->functions->getMerchant(isset($merchant_id)?$merchant_id:'');
			$full_merchant_address=$merchant_info['street']." ".$merchant_info['city']. " ".$merchant_info['state'].
			" ".$merchant_info['post_code'];

	        $print[]=array('label'=>Yii::t("default","Address"),'value'=>$full_merchant_address);
	        
	        $print[]=array('label'=>Yii::t("default","TRN Type"),'value'=>t($data['trans_type']));
	        
	        $print[]=array(
	         'label'=>Yii::t("default","Payment Type"),
	         'value'=>FunctionsV3::prettyPaymentType('payment_order',$data['payment_type'],$order_id,$data['trans_type'])
	        );	       
	       
	        if ( $data['payment_provider_name']){
	        	$print[]=array('label'=>Yii::t("default","Card#"),'value'=>strtoupper($data['payment_provider_name']));
	        }
	        
	        if ( $data['payment_type'] =="pyp"){
	        	$paypal_info=Yii::app()->functions->getPaypalOrderPayment($order_id);
	        	$print[]=array(
                   'label'=>Yii::t("default","Paypal Transaction ID"),
	               'value'=>isset($paypal_info['TRANSACTIONID'])?$paypal_info['TRANSACTIONID']:''
	            );
	        }
	        	        
	        $print[]=array(
	         'label'=>Yii::t("default","Reference #"),
	         'value'=>Yii::app()->functions->formatOrderNumber($data['order_id'])
	        );
	        
	        if ( !empty($data['payment_reference'])){
	        	$print[]=array(
		          'label'=>Yii::t("default","Payment Ref"),
		          'value'=>isset($data['payment_reference'])?$data['payment_reference']:Yii::app()->functions->formatOrderNumber($data['order_id'])
		        );
	        }
	        
	        if ( $data['payment_type']=="ccr" || $data['payment_type']=="ocr"){
	        	$print[]=array(
		          'label'=>Yii::t("default","Card #"),
		          'value'=>Yii::app()->functions->maskCardnumber($data['credit_card_number'])
		        );
	        }
	        
	        $trn_date=date('M d,Y G:i:s',strtotime($data['date_created']));
	        $print[]=array(
	          'label'=>Yii::t("default","TRN Date"),
	          'value'=>$trn_date
	        );
	        	        
	        /*dump($data);
	        dump($print);
	        die();*/
	        
	        switch ($data['trans_type']) {
	        	case "delivery":	        		
	        		$print[]=array(
			         'label'=>Yii::t("default","Delivery Date"),
			         'value'=>Yii::app()->functions->translateDate($data['delivery_date'])
			        );
			        
			        if(!empty($data['delivery_time'])){
			           $print[]=array(
				         'label'=>Yii::t("default","Delivery Time"),
				         'value'=>Yii::app()->functions->timeFormat($data['delivery_time'],true)
				       );
			        }
			        
			        if(!empty($data['delivery_asap'])){
			        	$delivery_asap=$data['delivery_asap']==1?t("Yes"):'';
			        	$print[]=array(
						 'label'=>Yii::t("default","Deliver ASAP"),
						 'value'=>$delivery_asap
						);
			        }
			        
			        if (!empty($data['client_full_address'])){		         	
		         	   $delivery_address=$data['client_full_address'];
		            } else $delivery_address=$data['full_address'];		
		            		            
			        $print[]=array(
					  'label'=>Yii::t("default","Deliver to"),
					  'value'=>$delivery_address
					);
					
					$print[]=array(
					  'label'=>Yii::t("default","Delivery Instruction"),
					  'value'=>$data['delivery_instruction']
					);         
					
					$print[]=array(
					  'label'=>Yii::t("default","Location Name"),
					  'value'=>$data['location_name']
					);
		       
					$print[]=array(
					  'label'=>Yii::t("default","Contact Number"),
					  'value'=>$data['contact_phone']
					);
					
					if ($data['order_change']>=0.1){
						$print[]=array(
						  'label'=>Yii::t("default","Change"),
						  'value'=>normalPrettyPrice($data['order_change'])
						);
					}
				
	        		break;
	        	
	        	case "pickup":		
	        	case "dinein":		
	        	
		            $label_date=t("Pickup Date");
			        $label_time=t("Pickup Time");
			        if ($data['trans_type']=="dinein"){
			      	    $label_date=t("Dine in Date");
			            $label_time=t("Dine in Time");
			        }   
			        
			        if (isset($data['contact_phone1'])){
						if (!empty($data['contact_phone1'])){
							$data['contact_phone']=$data['contact_phone1'];
						}
					}
				
			        $print[]=array(
					  'label'=>Yii::t("default","Contact Number"),
					  'value'=>$data['contact_phone']
					);
					
					$print[]=array(
			         'label'=>$label_date,
			         'value'=>Yii::app()->functions->translateDate($data['delivery_date'])
			        );
			        
			        if(!empty($data['delivery_time'])){
			           $print[]=array(
				         'label'=>$label_time,
				         'value'=>Yii::app()->functions->timeFormat($data['delivery_time'],true)
				       );
			        }
			        
			        if ($data['order_change']>=0.1){
						$print[]=array(
						  'label'=>Yii::t("default","Change"),
						  'value'=>normalPrettyPrice($data['order_change'])
						);
					}
			        
					if ($data['trans_type']=="dinein"){
						$print[]=array(
						  'label'=>t("Number of guest"),
						  'value'=>$data['dinein_number_of_guest']
						);
						$print[]=array(
						  'label'=>t("Special instructions"),
						  'value'=>$data['dinein_special_instruction']
						);
					}
	        	
	        	   break;
	        
	        	default:
	        		break;
	        }
	        
	        $to=isset($data['email_address'])?$data['email_address']:'';
	        
	        /*CHECK IF EURO TAX*/
	        if($data['apply_food_tax']==1){	        	
	        	$new_total = EuroTax::computeWithTax(Yii::app()->functions->details['raw'], $merchant_id);
	        	Yii::app()->functions->details['raw']['total']=$new_total;	      	        	  		        		        		        	
	        	$receipt=EmailTPL::salesReceiptTax($print,Yii::app()->functions->details['raw']);
	        } else $receipt=EmailTPL::salesReceipt($print,Yii::app()->functions->details['raw']);
	        
	        FunctionsV3::notifyCustomer($data,Yii::app()->functions->additional_details,$receipt, $to);
	        FunctionsV3::notifyMerchant($data,Yii::app()->functions->additional_details,$receipt);
	        FunctionsV3::notifyAdmin($data,Yii::app()->functions->additional_details,$receipt);
	        	        
	        FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("cron/processemail"));
	        FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("cron/processsms"));	  
	        	       
	        /*PRINTER ADDON*/
	        if (FunctionsV3::hasModuleAddon("printer")){
	        	Yii::app()->setImport(array('application.modules.printer.components.*'));
	        	$html=getOptionA('printer_receipt_tpl');
				if($print_receipt = ReceiptClass::formatReceipt($html,$print,Yii::app()->functions->details['raw'],$data)){							
					PrinterClass::printReceipt($data['order_id'],$print_receipt);												
				}
				
				$html = getOption($merchant_id,'mt_printer_receipt_tpl');
				if($print_receipt = ReceiptClass::formatReceipt($html,$print,Yii::app()->functions->details['raw'],$data)){
			       PrinterClass::printReceiptMerchant($merchant_id,$data['order_id'],$print_receipt);		
				}		
				FunctionsV3::fastRequest(FunctionsV3::getHostURL().Yii::app()->createUrl("printer/cron/processprint"));	
	        }
	              
		}
	}	
	
	
    public static function latToAdress($lat='' , $lng='')
	{
		$lat_lng="$lat,$lng";
		$protocol = isset($_SERVER["https"]) ? 'https' : 'http';
		if ($protocol=="http"){
			$api="http://maps.googleapis.com/maps/api/geocode/json?latlng=".urlencode($lat_lng);
		} else $api="https://maps.googleapis.com/maps/api/geocode/json?latlng=".urlencode($lat_lng);
		
		/*check if has provide api key*/
		$key=Yii::app()->functions->getOptionAdmin('google_geo_api_key');		
		if ( !empty($key)){
			$api="https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($lat_lng)."&key=".urlencode($key);
		}	
		

		$google_use_curl = getOptionA('google_use_curl');		
				
		if($google_use_curl==2){
			$json=Yii::app()->functions->Curl($api,'');
		} else $json=@file_get_contents($api);
		
		if (isset($_GET['debug'])){
			dump($api);			
		}
		
		$address_out=array();
			
		if (!empty($json)){			
			
			$results = json_decode($json,true);					
			if(array_key_exists('error_message',(array)$results)){
				 throw new Exception( $results['status']);
			}
						
			$parts = array(
		      'address'=>array('street_number','route'),		      
		      'city'=>array('locality','political','sublocality','administrative_area_level_2','administrative_area_level_1'),
		      'state'=>array('administrative_area_level_1'),
		      'zip'=>array('postal_code'),
		      'country'=>array('country'),
		    );		    
		    if (!empty($results['results'][0]['address_components'])) {
		      $ac = $results['results'][0]['address_components'];		      
		      foreach($parts as $need=>$types) {
		        foreach($ac as &$a) {		          			          
			          if (in_array($a['types'][0],$types)){
			          	  if (in_array($a['types'][0],$types)){
			          	  	  if($need=="address"){
			          	  	  	  if(isset($address_out[$need])) {
			          	  	  	     $address_out[$need] .= " ".$a['long_name'];
			          	  	  	  } else $address_out[$need]= $a['long_name'];
			          	  	  } else $address_out[$need] = $a['long_name'];			          	  	  
			          	  }
			          } elseif (empty($address_out[$need])) $address_out[$need] = '';	
		        }
		      }
		      
		      if(!empty($results['results'][0]['formatted_address'])){
		         $address_out['formatted_address']=$results['results'][0]['formatted_address'];
		      }		      
		      return $address_out;
		    } 				
		}			
		return false;
	}	
	
	public static function getCartEarningPoints($cart=array(), $sub_total=0 , $mtid='')
	{
		/*CHECK IF ADMIN ENABLED THE POINTS SYSTEM*/
		$points_enabled=getOptionA('points_enabled');
		if ($points_enabled!=1){
			return false;
		}
		
		/*CHECK IF MERCHANT HAS DISABLED POINTS SYSTEM*/
		if(isset($cart[0])){
			if(isset($cart[0]['merchant_id'])){				
				$mt_disabled_pts=getOption($mtid,'mt_disabled_pts');
				if($mt_disabled_pts==2){
					return false;
				}
			}		
		}
		
		$points=0;

		if (is_array($cart) && count($cart)>=1){
			$earning_type =  PointsProgram::getBasedEarnings($mtid);
			
			if($earning_type==1){
				foreach ($cart as $val) {
					$temp_price=explode("|",$val['price']);														
					if($val['discount']>=0.01){
						$set_price = ($temp_price[0]-$val['discount'])*$val['qty'];
					} else $set_price = (float)$temp_price[0]*$val['qty'];				
									
					$points+= PointsProgram::getPointsByItem($val['item_id'],$set_price , $mtid);
				}
			} else {								
				$points+=PointsProgram::getTotalEarningPoints($sub_total,$mtid);				
			}
			
			/*CHECK IF SUBTOTAL ORDER IS ABOVE */
			$pts_earn_above_amount=getOptionA('pts_earn_above_amount');
			
			if(!PointsProgram::isMerchantSettingsDisabled()){
				$mt_pts_earn_above_amount=getOption($mtid,'mt_pts_earn_above_amount');
				if($mt_pts_earn_above_amount>0){
					$pts_earn_above_amount = $mt_pts_earn_above_amount;
				}
			}
			
			if(is_numeric($pts_earn_above_amount)){
				if($pts_earn_above_amount>$sub_total){
					$points=0;
				}
			}
						
			if ($points>0){
				$pts_label_earn=getOptionA('pts_label_earn');
				if(empty($pts_label_earn)){
					$pts_label_earn = "This order earned {points}";
				}				
				return array(
				  'points_earn'=>$points,
				  'pts_label_earn'=>Yii::t("singleapp",$pts_label_earn,array(
				    '{points}'=>$points
				  ))
				);
			}
		}
		return false;
	}	
	
	public static function pointsTotalExpenses($client_id='')
	{
		$db = new DbExt();
		$stmt="
		SELECT SUM(total_points) as total
		FROM {{points_expenses}}
		WHERE
		status ='active'
		AND
		client_id=".FunctionsV3::q($client_id)."
		";
		if($res=$db->rst($stmt)){
			return $res[0]['total'];
		}
		return 0;
	}
	
	public static function getTotalEarnPoints($client_id='', $merchant_id='')
	{
		$and=" AND (merchant_id=".FunctionsV3::q($merchant_id)." OR trans_type='adjustment' ) ";		
		
		$db=new DbExt();
		$stmt="
		SELECT SUM(total_points_earn) as total_earn,
		(
		  select sum(total_points)
		  from {{points_expenses}}
		  WHERE
		  status IN ('active','adjustment')
		  AND
		  client_id=".FunctionsV3::q($client_id)." 
		  $and
		) as  total_points_expenses
		
		FROM
		{{points_earn}}
		WHERE
		status IN ('active','adjustment')
		AND
		client_id=".FunctionsV3::q($client_id)."
		$and
		";		
		if ($res=$db->rst($stmt)){
			$res=$res[0];
			return $res['total_earn']-$res['total_points_expenses'];
		}
		return 0;
	}
	
	public static function checkDeliveryAddress($merchant_id='',$data='')
	{
		if($merchant_info=FunctionsV3::getMerchantById($merchant_id)){
		   $distance_type=FunctionsV3::getMerchantDistanceType($merchant_id); 
		   
		   $complete_address=$data['street']." ".$data['city']." ".$data['state']." ".$data['zipcode'];
    	   if(isset($data['country'])){
    			$complete_address.=" ".$data['country'];
    	   } 
    	   
    	   $lat=0; $lng=0;
    	   
    	   if ( isset($data['address_book_id'])){
    		  if ($address_book=Yii::app()->functions->getAddressBookByID($data['address_book_id'])){
        		$complete_address=$address_book['street'];	    	
    	        $complete_address.=" ".$address_book['city'];
    	        $complete_address.=" ".$address_book['state'];
    	        $complete_address.=" ".$address_book['zipcode'];
        	  }
    	   }
    	   
    	   //dump($complete_address);
    	   
    	   if (isset($data['map_address_toogle'])){    			
    			if ($data['map_address_toogle']==2){
    				$lat=$data['map_address_lat'];
    				$lng=$data['map_address_lng'];
    			} else {
    				if ($lat_res=Yii::app()->functions->geodecodeAddress($complete_address)){
			           $lat=$lat_res['lat'];
					   $lng=$lat_res['long'];
		    	    }
    			}
    		} else {    			
    			if ($lat_res=Yii::app()->functions->geodecodeAddress($complete_address)){
		           $lat=$lat_res['lat'];
				   $lng=$lat_res['long'];
	    	    }
    		}
    		
    		$distance=FunctionsV3::getDistanceBetweenPlot(
				$lat,
				$lng,
				$merchant_info['latitude'],$merchant_info['lontitude'],$distance_type
			);  
			
			$distance_type_raw = $distance_type=="M"?"miles":"kilometers";		
			$merchant_delivery_distance=getOption($merchant_id,'merchant_delivery_miles'); 
			
			if(!empty(FunctionsV3::$distance_type_result)){
             	$distance_type_raw=FunctionsV3::$distance_type_result;
            }
                        
            //dump($distance);dump($distance_type_raw);
            
            if (is_numeric($merchant_delivery_distance)){
            	if ( $distance>$merchant_delivery_distance){
            		if($distance_type_raw=="ft" || $distance_type_raw=="meter" || $distance_type_raw=="mt"){
					   return true;
					} else {
						$error = Yii::t("singleapp",'Sorry but this merchant delivers only with in [distance] your current distance is [current_distance]',array(
			    		  '[distance]'=>$merchant_delivery_distance." ".t($distance_type_raw),
			    		  '[current_distance]'=>$distance." ".t($distance_type_raw)
			    		));
						throw new Exception( $error );
					}		            
            	} else {            		
	    			$delivery_fee=FunctionsV3::getMerchantDeliveryFee(
					              $merchant_id,
					              $merchant_info['delivery_charges'],
					              $distance,
					              $distance_type_raw);
					if($delivery_fee){
						return array(
						  'delivery_fee'=>$delivery_fee,
						  'distance'=>$distance,
						  'distance_unit'=>$distance_type_raw
						);
					}
					return true;
            	}
            } else {
            	// OK DO NOT CHECK DISTAMCE             	
            	$delivery_fee=FunctionsV3::getMerchantDeliveryFee(
				              $merchant_id,
				              $merchant_info['delivery_charges'],
				              $distance,
				              $distance_type_raw);
				if($delivery_fee){
					return array(
					  'delivery_fee'=>$delivery_fee,
					  'distance'=>$distance,
					  'distance_unit'=>$distance_type_raw
					);
				}
            	return true;
            }		   
		} else {
			 throw new Exception( self::t("Merchant not found") );
		}
	}
	
	public static function clearCartParamaters($device_id='')
	{
		if($resp=SingleAppClass::getCart($device_id)){		   
		   $cart_id = $resp['cart_id'];
		   $params=array(
		      'voucher_details'=>'',
		      'street'=>'',
		      'city'=>'',
		      'state'=>'',
		      'zipcode'=>'',
		      'delivery_instruction'=>'',
		      'location_name'=>'',
		      'contact_phone'=>'',
		      'tips'=>0,
		      'points_earn'=>0,
		      'points_apply'=>0,
		      'points_amount'=>0,
		      'country_code'=>'',
		      'delivery_fee'=>0,
		      'min_delivery_order'=>0,
		      'date_modified'=>FunctionsV3::dateNow(),
		      'distance'=>'',
		      'distance_unit'=>'',
		      'delivery_lat'=>'',
		      'delivery_long'=>'',
		      'state_id'=>0,
		      'city_id'=>0,
		      'area_id'=>0,
		   );
		   $db = new DbExt();
		   $db->updateData("{{singleapp_cart}}",$params,'cart_id',$cart_id);
		}
	}
	
	public static function getBannerLink($merchant_id='')
	{
		$banner = getOption($merchant_id,'singleapp_banner');
		if(!empty($banner)){
			$banner = json_decode($banner,true);
			if(is_array($banner) && count($banner)>=1){
				$new_banner=array();
				foreach ($banner as $val) {
					$new_banner[]=websiteUrl()."/upload/$val";
				}
				return $new_banner;
			}
		}
		return false;
	}
	
	public static function highlight_word( $content, $word ) {
	    $replace = '<span class="highlight">' . $word . '</span>'; // create replacement
	    $content = str_ireplace( $word, $replace, $content ); // replace content	
	    return $content; // return highlighted data
    }
    
    public static function savePoints($device_id='',$client_id='',$merchant_id='', $order_id='',$order_status='')
    {
    	/*POINTS ADDON*/
		if (FunctionsV3::hasModuleAddon("pointsprogram")){			
			if($res=SingleAppClass::getCart($device_id , $merchant_id )){
				$points_earn = $res['points_earn'];
				PointsProgram::saveEarnPoints($points_earn,$client_id,$merchant_id,$order_id,'',$order_status);				
				
				if ($res['points_apply']>=0.0001){
					PointsProgram::saveExpensesPoints(
					  $res['points_apply'],
					  $res['points_amount'],
					  $client_id,
					  $merchant_id,
					  $order_id,
					  ''
					);
				}
			}
		}
    }   
    
    public static function updatePoints($order_id='', $order_status='')
	{
		if (FunctionsV3::hasModuleAddon('pointsprogram')){
			if (method_exists("PointsProgram","updateOrderBasedOnStatus")){
				PointsProgram::updateOrderBasedOnStatus($order_status,$order_id);
			}
		}
	}
	
	public static function orderHistory($order_id='')
    {
    	$db_ext=new DbExt;
    	$stmt="SELECT * FROM
    	{{order_history}}
    	WHERE
    	order_id=".q($order_id)."
    	ORDER BY id DESC
    	";
    	if ( $res=$db_ext->rst($stmt)){
    		return $res;
    	}
    	return false;
    }
    
    public static function getTaskByOrderId($order_id='')
    {
    	if (FunctionsV3::hasModuleAddon("driver")){
	    	$db_ext=new DbExt;
	    	$stmt="SELECT 
	    	a.*,
	    	b.request_cancel_status
	    	FROM
	    	{{driver_task}} a
	    	
	    	left join {{order}} b
	    	ON
	    	a.order_id = b.order_id
	    	
	    	WHERE
	    	a.order_id=".q($order_id)."
	    	LIMIT 0,1
	    	";
	    	if ( $res=$db_ext->rst($stmt)){
	    		return $res[0];
	    	}    	
    	}
    	return false;
    }
    
    public static function showTrackOrder($order_id='')
    {    	
    	
    	if (FunctionsV3::hasModuleAddon("driver")){
	    	$track_status = array('started','inprogress','failed','cancelled','declined','successful');    		    
	    	if($res = self::getTaskByOrderId($order_id)){    			    		
	    		if($res['driver_id']>0){
	    			
	    			if($res['request_cancel_status']=="approved"){	    				
	    				return false;
	    			}
	    			
	    			if(in_array($res['status'],$track_status)){
	    			   return true;
	    			} 
	    		}
	    	}
    	}
    	return false;
    }
    
    public static function TrackOrderData($order_id='')
    {
    	$db_ext=new DbExt;
    	$stmt="SELECT a.*,
    	 concat(b.first_name,' ',b.last_name) as driver_name,
    	 b.email,
    	 b.phone,
    	 b.licence_plate,
    	 b.transport_description,
    	 b.transport_type_id,
    	 b.location_lat as driver_location_lat,
    	 b.location_lng as driver_location_lng,
    	 b.profile_photo
    	 FROM
    	{{driver_task}} a
    	
    	left join {{driver}} b
		ON 
		a.driver_id = b.driver_id
    	
    	WHERE
    	order_id=".q($order_id)."
    	LIMIT 0,1
    	";
    	if ( $res=$db_ext->rst($stmt)){
    		return $res[0];
    	}
    	return false;
    }
    
    public static function getDriverPhoto($image='', $default="avatar.png")
	{	
		$url='';			
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload/driver";		
		if (!empty($image)){						
			if (file_exists($path_to_upload."/$image")){							
				$default=$image;				
				$url = Yii::app()->getBaseUrl(true)."/upload/driver/$default";
			} else $url=self::moduleBaseUrl()."/assets/images/$default";
		} else $url=self::moduleBaseUrl()."/assets/images/$default";
		return $url;
	}	
	
	public static function platFormList()
    {
    	return array(
	    	'android'=>st("android"),
	        'ios'=>st('ios'),
	        'all'=>st("all platform")
    	);
    }
    
	public static function getPagesByID($page_id='',$fields='*')
	{		
	    $DbExt=new DbExt;
		$stmt="
		SELECT $fields
	    FROM
		{{singleapp_pages}}
		WHERE 
		page_id=".FunctionsV3::q($page_id)."
		AND status in ('publish')
		LIMIT 0,1
		";					
		if($res=$DbExt->rst($stmt)){
			$res=$res[0];			
			return $res;
		}
		return false;
	}	
	
	public static function getPages($merchant_id='')
	{		
	    $DbExt=new DbExt;
		$stmt="
		SELECT *
	    FROM
		{{singleapp_pages}}
		WHERE 
		merchant_id=".FunctionsV3::q($merchant_id)."
		AND status in ('publish')
		ORDER BY sequence ASC
		";					
		if($res=$DbExt->rst($stmt)){			
			return $res;
		}
		return false;
	}		
	
	public static function canReviewOrder($order_status='',$website_review_type='', $review_baseon_status='')
    {       	
    	/*if($website_review_type==2){    		    	
    		if(!empty($review_baseon_status)){
    		   $review_baseon_status = json_decode($review_baseon_status,true);
    		   if (is_array($review_baseon_status) && count($review_baseon_status)>=1){
    		   	  if (in_array($order_status,$review_baseon_status)){
    		   	  	  return true;
    		   	  }
    		   }
    		} else return true;
    	}
    	return false;*/
    	
    	if(!empty($review_baseon_status)){
		   $review_baseon_status = json_decode($review_baseon_status,true);
		   if (is_array($review_baseon_status) && count($review_baseon_status)>=1){
		   	  if (in_array($order_status,$review_baseon_status)){
		   	  	  return true;
		   	  }
		   }
		} else return true;
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
			
	
	public static function checkAddressBook($client_id='', $lat='', $lng='', $id='')
	{
		$db=new DbExt;
		
		$and='';
		if($id>0){
			$and.=" AND id <>".FunctionsV3::q($id)." ";
		}
		
		$stmt="SELECT * FROM
		{{address_book}}
		WHERE
		client_id=".FunctionsV3::q($client_id)."
		AND
		latitude = ".FunctionsV3::q($lat)."
		AND
		longitude = ".FunctionsV3::q($lng)."
		$and
		LIMIT 0,1
		";				
		if ($res = $db->rst($stmt)){
			return $res;
		}
		return false;
	}
	
	public static function getDistanceResultsType($merchant_id='')
    {
    	$distance_results_type = getOption($merchant_id,'singleapp_distance_results');
    	if(empty($distance_results_type)){
    		return 1;
    	}
    	if(!is_numeric($distance_results_type)){
    		return 1;
    	}
    	return $distance_results_type;
    }
    
    public static function checkDeliveryAddresNew( $merchant_id='', $lat='', $lng='' )
    {
    	if(!is_numeric($merchant_id)){
    		throw new Exception( self::t("invalid merchant id") );
    	}
    	if(!is_numeric($lat)){
    		throw new Exception( self::t("invalid latitude") );
    	}
    	if(!is_numeric($lng)){
    		throw new Exception( self::t("invalid longtitude") );
    	}
    	
    	$distance=0;
    	$distance_results_type = self::getDistanceResultsType($merchant_id);	
    	
    	if($merchant_info=FunctionsV3::getMerchantById($merchant_id)){
    	   $distance_type=FunctionsV3::getMerchantDistanceType($merchant_id); 
    	   $merchant_lat = $merchant_info['latitude'];
    	   $merchant_lng = $merchant_info['lontitude'];
    	       	       	       	   
    	   if($distance_results_type==1){
    	   	  $distance = self::getLocalDistance($distance_type,$lat,$lng,$merchant_lat,$merchant_lng);    	   	  
    	   } else {    	   
	    	   $distance=FunctionsV3::getDistanceBetweenPlot(
					$lat,
					$lng,
					$merchant_lat,$merchant_lng,$distance_type
			   );      
    	   }	   
    	   
    	   if(isset($_GET['debug'])){
    	      dump("distance=>$distance");
    	   }
		   		   
		   $distance_type_raw = $distance_type=="M"?"miles":"kilometers";		
		   $merchant_delivery_distance=getOption($merchant_id,'merchant_delivery_miles');   
		   
		   if(!empty(FunctionsV3::$distance_type_result)){
              $distance_type_raw=FunctionsV3::$distance_type_result;
           }
           
           /*dump("distance=>$distance");
           dump("merchant_delivery_distance=>$merchant_delivery_distance");
           dump("distance_type_raw=>$distance_type_raw");*/
           
           if (is_numeric($merchant_delivery_distance)){
           	   if ( $distance>$merchant_delivery_distance){
           	   	   if($distance_type_raw=="ft" || $distance_type_raw=="meter" || $distance_type_raw=="mt"){
					   return true;
					} else {
						$error = Yii::t("singleapp",'Sorry but this merchant delivers only with in [distance] your current distance is [current_distance]',array(
			    		  '[distance]'=>$merchant_delivery_distance." ".t($distance_type_raw),
			    		  '[current_distance]'=>$distance." ".t($distance_type_raw),
			    		));
						throw new Exception( $error );
					}		
           	   } else {           	   	           	   	   
           	   	   /*$delivery_fee=FunctionsV3::getMerchantDeliveryFee(
					              $merchant_id,
					              $merchant_info['delivery_charges'],
					              $distance,
					              $distance_type_raw);*/
           	   	    $delivery_fee = self::getShippingRate($merchant_id,
           	   	      $merchant_info['delivery_charges'],
           	   	      $distance,
           	   	      $distance_type_raw
           	   	    );
           	   	    		           
					//dump("delivery_fee=>$delivery_fee");              
					if($delivery_fee){
						return array(
						  'delivery_fee'=>$delivery_fee,
						  'distance'=>$distance,
						  'distance_unit'=>$distance_type_raw
						);
					}
					return true;
           	   }
           } else {
           	   // OK DO NOT CHECK DISTAMCE 
           	   $delivery_fee=FunctionsV3::getMerchantDeliveryFee(
				              $merchant_id,
				              $merchant_info['delivery_charges'],
				              $distance,
				              $distance_type_raw);
				if($delivery_fee){
					return array(
					  'delivery_fee'=>$delivery_fee,
					  'distance'=>$distance,
					  'distance_unit'=>$distance_type_raw
					);
				}
            	return true;
           }
    	   
    	} else throw new Exception( self::t("Merchant not found") );
    }	
	
    public static function getLocalDistance($unit='', $lat1='',$lon1='', $lat2='', $lon2='')
    {    	      	  
    	  $theta = $lon1 - $lon2;
    	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    	 
    	  $dist = acos($dist);
		  $dist = rad2deg($dist);
		  $miles = $dist * 60 * 1.1515;
		  $unit = strtoupper($unit);
		  
		  $resp = 0;
		  
		  if ($unit == "K") {
		      $resp = ($miles * 1.609344);
		  } else if ($unit == "N") {
		      $resp = ($miles * 0.8684);
		  } else {
		      $resp = $miles;
		  }		  
		  
		  if($resp>0){
		  	 $resp = number_format($resp,1,'.','');
		  }
		  
		  return $resp;
    }
    
    public static function getBookAddressByClientID($client_id='',$street='', $city='', $state='' )
	{
		if(empty($street)){
			return false;
		}
		if(empty($city)){
			return false;
		}
		if(empty($state)){
			return false;
		}
		
		$db=new DbExt;
		$stmt="SELECT * FROM
		{{address_book}}
		WHERE
		client_id=".FunctionsV3::q($client_id)."
		AND
		street=".FunctionsV3::q($street)."
		AND
		city = ".FunctionsV3::q($city)."
		AND
		state = ".FunctionsV3::q($state)."
		LIMIT 0,1
		";		
		if ($res = $db->rst($stmt)){
			return $res;
		}
		return false;
	}	    
	
    public static function getVoucherMerchant($client_id='',$voucher_code='',$merchant_id='')
    {
    	$db_ext=new DbExt;    	
    	$stmt="
    	SELECT a.*,
    	(
    	select count(*) from
    	{{order}}
    	where
    	voucher_code=".FunctionsV3::q($voucher_code)."
    	and
    	client_id=".FunctionsV3::q($client_id)."  	
    	LIMIT 0,1
    	) as found,
    	
    	(
    	select count(*) from
    	{{order}}
    	where
    	voucher_code=".FunctionsV3::q($voucher_code)."    	
    	LIMIT 0,1
    	) as number_used    
    	
    	FROM
    	{{voucher_new}} a
    	WHERE
    	voucher_name=".FunctionsV3::q($voucher_code)."
    	AND
    	merchant_id=".FunctionsV3::q($merchant_id)."
    	AND status IN ('publish','published')
    	LIMIT 0,1
    	";    	    	
    	if ($res=$db_ext->rst($stmt)){    		    		
    		return $res[0];
    	}
    	return false;
    } 
    
    public static function getVoucherAdmin($client_id='', $voucher_code='')
    {
    	$db_ext=new DbExt;    	
    	$stmt="
    	SELECT a.*,
    	(
    	select count(*) from
    	{{order}}
    	where
    	voucher_code=".FunctionsV3::q($voucher_code)."
    	and
    	client_id=".FunctionsV3::q($client_id)."  	
    	LIMIT 0,1
    	) as found,
    	
    	(
    	select count(*) from
    	{{order}}
    	where
    	voucher_code=".FunctionsV3::q($voucher_code)."    	
    	LIMIT 0,1
    	) as number_used    	
    	
    	FROM
    	{{voucher_new}} a
    	WHERE
    	voucher_name=".FunctionsV3::q($voucher_code)."
    	AND
    	voucher_owner='admin'
    	AND status IN ('publish','published')
    	LIMIT 0,1
    	";    	
    	if ($res=$db_ext->rst($stmt)){    		    		
    		return $res[0];
    	}
    	return false;
    }     		
    
	public static function pointsEarnByMerchant($client_id='')
	{
		
		$stmt="
		SELECT sum(a.total_points_earn) as total_earn,
		(
		  select sum(total_points)
		  from {{points_expenses}}
		  where client_id=".FunctionsV3::q($client_id)."
		  AND a.status IN ('active','adjustment')
		) as total_expenses
		FROM {{points_earn}} a
		WHERE client_id=".FunctionsV3::q($client_id)."
		AND a.status IN ('active','adjustment')
		AND merchant_id>0
		group by merchant_id
		";		
		$db=new DbExt();
		if($res = $db->rst($stmt)){
			$total_earn=0; $total_expenses=0;
			foreach ($res as $val) {
				$total_earn+=$val['total_earn'];
				$total_expenses+=$val['total_expenses'];
			}
			$total = $total_earn-$total_expenses;
			return $total;
		}
		return 0;
	}    
    
	public static function paginateLimit()
    {
    	return 10;
    }	
    
    public static function getMerchantBackground($merchant_id='',$set_image='')
    {    	
    	$image_url = websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/default_bg.jpg";
        $merchant_photo_bg = getOption($merchant_id,'merchant_photo_bg');        
    	if(!empty($merchant_photo_bg)){    		    		
	    	if ( file_exists(FunctionsV3::uploadPath()."/$merchant_photo_bg")){
	    		$image_url = websiteUrl()."/upload/$merchant_photo_bg";
	    	}
    	} else {
    		if(!empty($set_image)){
    			$image_url = websiteUrl()."/protected/modules/".APP_FOLDER."/assets/images/$set_image";
    		}
    	}    	
    	return FunctionsV3::prettyUrl($image_url);
    }
    
    public static function BookingTabs()
    {    
    	$data =array();
    	$data[]=array(
    	  'tab'=>"all",
    	  'label'=>st("All")
    	);
    	$data[]=array(
    	  'tab'=>"pending",
    	  'label'=>st("Pending")
    	);
    	$data[]=array(
    	  'tab'=>"approved",
    	  'label'=>st("Approved")
    	);
    	$data[]=array(
    	  'tab'=>"denied",
    	  'label'=>st("Denied")
    	);
    	return $data;
    }
    
    public static function OrderTabs()
    {    
    	$data =array();
    	$data[]=array(
    	  'tab'=>"all",
    	  'label'=>st("All")
    	);
    	$data[]=array(
    	  'tab'=>"processing",
    	  'label'=>st("Processing")
    	);
    	$data[]=array(
    	  'tab'=>"completed",
    	  'label'=>st("Completed")
    	);
    	$data[]=array(
    	  'tab'=>"cancelled",
    	  'label'=>st("Cancelled")
    	);
    	return $data;
    }
    
    public static function getOrderTabsStatus($merchant_id,$tab='')
	{
		$status = ''; $and='';
		switch ($tab) {
			case "processing":					
			    $status=getOption($merchant_id,'singleapp_order_processing');
				break;
		
			case "completed":				
			    $status=getOption($merchant_id,'singleapp_order_completed'); 
				break;
				
			case "cancelled":				
			    $status=getOption($merchant_id,'singleapp_order_cancelled'); 
				break;
						
			default:
				break;
		}	
		
		if(!empty($status)){
			$status = json_decode($status,true);			
			if(is_array($status) && count((array)$status)>=1){
				foreach ($status as $val) {
					$and.= FunctionsV3::q($val)."," ;
				}
				$and = substr($and,0,-1);
				$and = "AND a.status IN ($and)";
			}
		}
		return $and;
	}    
	
    public static function orderDetails($order_id='')
    {
    	$db = new DbExt();
    	$stmt="
    	SELECT 
    	a.order_id,
    	a.merchant_id,
    	a.client_id,
    	a.trans_type,
    	a.status,
    	a.status as status_raw,
    	a.payment_type,
    	a.payment_type as payment_type_raw,
    	b.restaurant_name as merchant_name,
		b.logo,
		c.review,
		c.rating,
		c.as_anonymous
							
		FROM
		{{order}} a
		
		left join {{merchant}} b
        ON
        a.merchant_id = b.merchant_id
        
        left join {{review}} c
        ON
        a.order_id = c.order_id
                
		WHERE a.order_id=".FunctionsV3::q($order_id)."
		LIMIT 0,1
    	";    	
    	if($res = $db->rst($stmt)){
    	   return $res[0];
    	}
    	return false;
    }	
    
	public static function getReviewReplied($review_id='', $merchant_id='')
	{	
		if($merchant_id>0){			
		} else $merchant_id=-1;
				
		$data = array();
		$db = new DbExt();
		$stmt="
	   	   SELECT 
	   	   a.merchant_id,
	   	   a.review,
	   	   a.parent_id,
	   	   a.reply_from,
	   	   a.date_created,
	   	   ( 
	   	     select logo from {{merchant}}
	   	     where merchant_id=".FunctionsV3::q($merchant_id)."
	   	     limit 0,1
	   	   ) as logo
	   	   	   	   
	   	   FROM
	   	   {{review}} a
	   	   
	   	   WHERE
	   	   a.parent_id=".FunctionsV3::q($review_id)."
	   	   AND 
	   	   a.status = 'publish'
	   	   ORDER BY a.id ASC
	   	   LIMIT 0,10
	   	 ";   	  
		 if($res = $db->rst($stmt)){
		 	foreach ($res as $val) {		 		
		 		$val['logo']=SingleAppClass::getImage($val['logo']);	 		
		 		$pretyy_date=PrettyDateTime::parse(new DateTime($val['date_created']));
		        $pretyy_date=Yii::app()->functions->translateDate($pretyy_date);
		        $val['date_posted']=$pretyy_date;
		        $val['customer_name'] = st("Replied By [merchant_name]",array(
					  '[merchant_name]'=>$val['reply_from']
					));
					
				unset($val['merchant_id']);
		 		unset($val['reply_from']);
		 		unset($val['date_created']);
		 		$data[]=$val;
		 	}
		 }		 
		 return $data;
	}    
    
    public static function getTaskFullInformation($task_id='')
	{
		if($task_id<=0){
			return false;
		}
		if(!is_numeric($task_id)){
			return false;
		}
		
		$db = new DbExt();
		$stmt="
		SELECT 
		a.task_id,
		a.order_id,
		a.driver_id,
		a.status,
		a.rating,
		a.rating_comment,		
		a.rating_anonymous,
		concat(b.first_name,' ',b.last_name) as driver_name,
		b.email as driver_email,
		b.phone as driver_phone,
		b.profile_photo as driver_photo, 
		c.client_id,
		d.first_name  as customer_firstname
				
		FROM
		{{driver_task}} a		
		left join {{driver}} b
		ON
		a.driver_id = b.driver_id		
		
		left join {{order}} c
		ON
		a.order_id = c.order_id
		
		left join {{client}} d
		ON
		c.client_id = d.client_id
		
		WHERE
		task_id =".FunctionsV3::q($task_id)."
		LIMIT 0,1
		";				
		if($res = $db->rst($stmt)){
			return $res[0];
		}
		return false;
	}		
	
	public static function GetBookingDetails($booking_id='',$client_id='')
	{				
		$stmt="
		SELECT * FROM
		{{bookingtable}}
		WHERE
		client_id=".FunctionsV3::q($client_id)."
		AND
		booking_id=".FunctionsV3::q($booking_id)."		
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
		   $res = Yii::app()->request->stripSlashes($res);
		   return $res;
		}	
		return false;
	}
	
	public static function getRecentLocationByID($device_uiid='',$lat='', $lng='', $mode='address')
	{		
		$stmt="
		SELECT * FROM
		{{singleapp_recent_location}}
		WHERE
		device_uiid=".FunctionsV3::q($device_uiid)."
		AND
		latitude=".FunctionsV3::q($lat)."
		AND
		longitude=".FunctionsV3::q($lng)."		
		AND
		search_mode = ".FunctionsV3::q($mode)."
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
		   return $res;
		}	
		return false;
	}
	
	public static function clearRecentLocation($device_uiid='')
	{
		if(!empty($device_uiid)){
			
			$search_resp = SingleAppClass::searchMode();
		    $search_mode = $search_resp['search_mode'];	
			
			$db = new DbExt();
			$db->qry("DELETE FROM {{singleapp_recent_location}}
			WHERE 
			device_uiid=".FunctionsV3::q($device_uiid)."
			AND search_mode=".FunctionsV3::q($search_mode)."
			");
			unset($db);
		}
	}
	
    public static function getCustomerByID($client_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	    	    	
    	$DbExt=new DbExt;
    	$stmt="SELECT * FROM
    	{{client}}
    	WHERE
    	client_id=".FunctionsV3::q($client_id)."      	
    	LIMIT 0,1
    	";
    	if($res=$DbExt->rst($stmt)){
    		return $res[0];
    	}
    	return false;
    }    		
    
    public static function isItemFavorite($client_id='',$item_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	    	    	
    	$DbExt=new DbExt;
    	$stmt="SELECT * FROM
    	{{favorite_item}}
    	WHERE
    	client_id=".FunctionsV3::q($client_id)."      	
    	AND item_id =".FunctionsV3::q($item_id)."
    	LIMIT 0,1
    	";
    	if($res=$DbExt->rst($stmt)){
    		return $res[0];
    	}
    	return false;
    }
    
    public static function addItemFavorite($client_id='',$item_id='', $category_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	
    	$db = new DbExt();
    	$params = array(
    	  'client_id'=>$client_id,
    	  'item_id'=>$item_id,
    	  'category_id'=>$category_id,
    	  'date_created'=>FunctionsV3::dateNow(),
    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
    	);
    	if(!self::isItemFavorite($client_id,$item_id)){
    		$db->insertData("{{favorite_item}}",$params);
    	}
    	unset($db);
    }
    
    public static function removeItemFavorite($client_id='',$item_id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	if(empty($item_id)){
    		return false;
    	}
    	$db = new DbExt();
    	$stmt="
    	DELETE FROM {{favorite_item}}
    	WHERE 
    	client_id=".FunctionsV3::q($client_id)."
    	AND item_id =".FunctionsV3::q($item_id)."
    	";
    	$db->qry($stmt);
    }
    
    public static function removeItemFavoriteByID($client_id='',$id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	if(empty($id)){
    		return false;
    	}
    	$db = new DbExt();
    	$stmt="
    	DELETE FROM {{favorite_item}}
    	WHERE 
    	client_id=".FunctionsV3::q($client_id)."
    	AND id =".FunctionsV3::q($id)."
    	";    	
    	$db->qry($stmt);
    }
    
    public static function getItemFavorite($client_id='',$id='')
    {
    	if(empty($client_id)){
    		return false;
    	}
    	    	    	
    	$DbExt=new DbExt;
    	$stmt="SELECT * FROM
    	{{favorite_item}}
    	WHERE
    	client_id=".FunctionsV3::q($client_id)."      	
    	AND id =".FunctionsV3::q($id)."
    	LIMIT 0,1
    	";
    	if($res=$DbExt->rst($stmt)){
    		return $res[0];
    	}
    	return false;
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
    
    public static function getTitlePages($merchant_id='')
	{
		$db = new DbExt();
		
		$titles = "page_id,title,icon";
		if(Yii::app()->functions->multipleField()){
			$list = self::getLangList();
			if(is_array($list) && count((array)$list)>=1){
				foreach ($list as $val) {
					$titles.=",title_$val";
				}
			}
		}
		
		$stmt="
		SELECT $titles FROM {{singleapp_pages}}
		WHERE status = 'publish'
		AND merchant_id=".FunctionsV3::q($merchant_id)."
		";			
		if($res = $db->rst($stmt)){			
			return $res;
		}
		return false;
	}    
	
	
	public static function searchMode()
	{
		$search_mode = getOptionA('home_search_mode');
		$location_mode = getOptionA('admin_zipcode_searchtype');
		if(empty($search_mode)){
		   $search_mode = 'address';	
		} elseif ($search_mode=="postcode"){
			$search_mode='location';
		}
		return array(
		  'search_mode'=>$search_mode,
		  'location_mode'=>$location_mode,
		);
	}
	
	public static function isLocation()
	{
		$mode = self::searchMode();
		if($mode=="location"){
			return true;
		}
		return false;
	}

	public static function getLastOrderSMS($mobile='')
	{
		$db = new DbExt();
		$stmt="
		SELECT * FROM {{order_sms}}
		WHERE mobile = ".FunctionsV3::q($mobile)."
		ORDER BY id DESC
		LIMIT 0,1
		";	
		if($res = $db->rst($stmt)){			
			return $res[0];
		}
		return false;
	}
	
	public static function clearCartByCustomerID($client_id='')
    {        	    	
    	$stmt="
    	DELETE FROM {{singleapp_cart}}
    	WHERE device_id IN (
    	 select device_uiid from {{singleapp_device_reg}}
    	 where client_id =".FunctionsV3::q( (integer) $client_id)."
    	)
    	";     	
        Yii::app()->db->createCommand($stmt)->query();
    }    
    
    public static function ContactUsData($merchant_id='')
    {    	    	
    	$contact_field = getOption($merchant_id,'singleapp_contactus_fields');
    	if(!empty($contact_field)){
    		$contact_field = json_decode($contact_field,true);
    	} else {
    		$contact_field = array('name','email');
    	}    
    	return array(    	 
    	 'contact_field'=>$contact_field
    	);
    }
    
    public static function prettyBadge($status='')
	{
		$$status=strtolower(trim($status));
		if($status=="pending"){
		   return '<span class="badge badge-primary">'.st($status).'</span>';
		} elseif ( $status=="process" ){
			return '<span class="badge badge-success">'.st($status).'</span>';
		} elseif ( preg_match("/properly set in/i", $status)){
			return '<span class="badge badge-danger">'.st($status).'</span>';
		} elseif ( preg_match("/caught/i", $status)){
			return '<span class="badge badge-danger">'.st($status).'</span>';	
		} elseif ( preg_match("/error/i", $status)){
			return '<span class="badge badge-danger">'.st($status).'</span>';			
		} elseif ( preg_match("/failed/i", $status)){
			return '<span class="badge badge-danger">'.st($status).'</span>';		
		} elseif ( preg_match("/no/i", $status)){
			return '<span class="badge badge-secondary">'.st($status).'</span>';			
		} else {			
		   return '<span class="badge badge-success">'.st($status).'</span>';
		}
	}
	
    public static function locationAccuracyList()
    {
    	return array(
    	  //'REQUEST_PRIORITY_NO_POWER'=>self::t("REQUEST_PRIORITY_NO_POWER"),
    	  'REQUEST_PRIORITY_LOW_POWER'=>self::t("REQUEST_PRIORITY_LOW_POWER"),
    	  'REQUEST_PRIORITY_BALANCED_POWER_ACCURACY'=>self::t("REQUEST_PRIORITY_BALANCED_POWER_ACCURACY"),
    	  'REQUEST_PRIORITY_HIGH_ACCURACY'=>self::t("REQUEST_PRIORITY_HIGH_ACCURACY"),
    	);
    }	
    
    public static function MenuType()
    {
    	return array(
    	  1=>self::t("Menu 1"),
    	  2=>self::t("Menu 2 - Classic menu"), 
    	  3=>self::t("Menu 3 - column"),    	  
    	);
    }
    
    public static function mobileCodeList()
	{
		$mobile_countrycode = require_once 'MobileCountryCode.php';
		$data = array();
		$data[] = self::t("Please select")."...";
				
		foreach ($mobile_countrycode as $key=>$val) {						
			$data[$val['code']]= st("[name] +[code]",array(
			  '[name]'=>$val['name'],
			  '[code]'=>$val['code'],
			));
		}
		
		return $data;		
	}
	
	public static function trackingTheme()
	{
		return array(
		  1 => self::t("Theme 1"),
		  2 => self::t("Theme 2"),
		);
	}
	
    public static function getMaxPage($merchant_id='')
	{
		$db = new DbExt();
		$stmt="
		SELECT max(sequence) as max	 FROM
		{{singleapp_pages}}		
		WHERE merchant_id = ".FunctionsV3::q($merchant_id)."
		";
		if($res=$db->rst($stmt)){				
			if($res[0]['max']>=1){
			   return $res[0]['max']+1;
			} else return 1;
		}
		return false;
	}	
	
    public static function getPageByTitle($merchant_id='',$title="")
	{
		if(empty($title)){
			return false;
		}
		
		$db = new DbExt();
		$stmt="
		SELECT * FROM
		{{singleapp_pages}}
		WHERE
		title=".FunctionsV3::q($title)."
		AND merchant_id = ".FunctionsV3::q($merchant_id)."
		LIMIT 0,1
		";
		if($res=$db->rst($stmt)){
			return $res[0];
		}
		return false;
	}	
	
	public static function getPageByID($page_id="")
	{
		if(empty($page_id)){
			return false;
		}
				
		$stmt="
		SELECT * FROM
		{{singleapp_pages}}
		WHERE
		page_id=".FunctionsV3::q($page_id)."
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}	
	
	public static function getMerchantList()
	{
		$db = new DbExt();
		$stmt="
		SELECT 
		merchant_id,
		restaurant_name FROM
		{{merchant}}
		WHERE
		status IN ('active')
		ORDER BY restaurant_name ASC
		";
		if($res=$db->rst($stmt)){
			$data = array();
			foreach ($res as $val) {
				$data[$val['merchant_id']] = clearString($val['restaurant_name']);
			}
			return $data;
		}
		return false;
	}
	
	public static function getMerchantNames($merchant_id=array())
	{
		$in_merchant_id = '';
		if(is_array($merchant_id) && count($merchant_id)>=1){
			foreach ($merchant_id as $id) {
				$in_merchant_id.= FunctionsV3::q($id).",";
			}
			$in_merchant_id = substr($in_merchant_id,0,-1);
		}
		$db = new DbExt();
		$stmt="
		SELECT 
		group_concat(restaurant_name SEPARATOR '<br/>') as merchant_name
		FROM {{merchant}}
		WHERE
		merchant_id IN ($in_merchant_id)
		";		
		if($res=$db->rst($stmt)){
			return clearString($res[0]['merchant_name']);
		}
		return '';
	}
	
	public static function getStartUpBanner($merchant_id='')
	{
		$data = array();
		$banner = getOption($merchant_id,'singleapp_startup_banner');	
		$banner = !empty($banner)?json_decode($banner,true):array();
		if(is_array($banner) && count($banner)>=1){
			foreach ($banner as $val) {
				$data[] = SingleAppClass::getImage($val);
			}
		}
		return $data;
	}
	
	public static function getAddressBookDefault($client_id='')
	{
		if($client_id>0){
			$db=new DbExt;    	
	    	$stmt="SELECT a.*,
	               b.contact_phone   	       
	    	       FROM
	    	       {{address_book}} a
	    	       
	    	       left join {{client}} b
                   ON
                   a.client_id=b.client_id
	    	       
	    	       WHERE
	    	       a.client_id= ".FunctionsV3::q($client_id)."
	    	       AND
	    	       a.as_default ='2'
	    	       LIMIT 0,1
	    	";    	    	 
	    	if ($res=$db->rst($stmt)){    		
	    		unset($db);
	    		return $res[0];
	    	}
	    	unset($db);
		}
		return false;	
	}
	
	public static function getRecentLocation($device_uiid='', $lat='', $lng='')
    {
    	if(empty($device_uiid)){
    	   return false;
    	}    
    	if(empty($lat)){    		
    	   return false;
    	}    
    	
    	if (FunctionsV3::hasModuleAddon("driver")){
	    	$db=new DbExt;
	    	$stmt="SELECT * FROM
	    	{{singleapp_recent_location}}
	    	WHERE
	    	device_uiid =".FunctionsV3::q($device_uiid)."
	    	AND
	    	latitude =".FunctionsV3::q($lat)."
	    	AND
	    	longitude =".FunctionsV3::q($lng)."
	    	LIMIT 0,1
	    	";	    		    		    	
	    	if ( $res=$db->rst($stmt)){	 	    		
	    		return $res[0];
	    	}    	
    	}    	
    	return false;
    }    
	
	public static function setAutoAddress($merchant_id='',$client_id='', $current_lat=0, $current_lng=0 , $device_uiid='' )
	{		
		if($merchant_id<=0){
			throw new Exception( st("invalid merchant id") );
		}
					
		$address_use = array();
		if($client_id>0){
			$address_use = SingleAppClass::getAddressBookDefault($client_id);			
		} else {						
			if ($res_recent = SingleAppClass::getRecentLocation($device_uiid,$current_lat,$current_lng)){				
				if(empty($res_recent['street'])){
				    if($resp_lat_address = FunctionsV3::latToAdress($current_lat,$current_lng)){
					    $res_recent['street']=$resp_lat_address['address'];
					    $res_recent['city']=$resp_lat_address['city'];
					    $res_recent['state']=$resp_lat_address['state'];
					    $res_recent['zipcode']=$resp_lat_address['zip'];				    
				    }
				}				
				if(!empty($res_recent['street'])){
				    $address_use = $res_recent;
				}
			}
		}		
		
		if(is_array($address_use) && count($address_use)>=1){
			
			if(empty($address_use['latitude'])){
				throw new Exception( st("invalid latitude") );		
			}
			if(empty($address_use['longitude'])){
				throw new Exception( st("invalid longitude") );		
			}
			if(empty($address_use['street'])){
				throw new Exception( st("invalid street") );		
			}			
			
			//dump($address_use);			
			$lat = $address_use['latitude']; $lng = $address_use['longitude'];			
			/*dump($merchant_id);
			dump("lat=>$lat lng=>$lng");*/
			$resp = SingleAppClass::checkDeliveryAddresNew($merchant_id,$lat, $lng);			
			
			if(is_array($resp) && count((array)$resp)>=1){
				//dump($resp);
				$params = array(
				  'street'=>isset($address_use['street'])?$address_use['street']:'',
				  'city'=>isset($address_use['city'])?$address_use['city']:'',
				  'state'=>isset($address_use['state'])?$address_use['state']:'',
				  'zipcode'=>isset($address_use['zipcode'])?$address_use['zipcode']:'',				  
				  'location_name'=>isset($address_use['location_name'])?$address_use['location_name']:'',
				  'contact_phone'=>isset($address_use['contact_phone'])?$address_use['contact_phone']:'',
				  'country_code'=>isset($address_use['country_code'])?$address_use['country_code']:'',
				  'delivery_lat'=>$lat,
				  'delivery_long'=>$lng,				  
				);
				
				$min_fees=0;
			    $params['delivery_fee']=0;
			    $params['min_delivery_order']=0;
			    
			    if(isset($resp['delivery_fee'])){
					$params['delivery_fee']=$resp['delivery_fee'];								                    
				}
				if($resp['distance']>0.001){
				   /*GET MINIMUM ORDER TABLE*/
				   $merchant_minimum_order = getOption($merchant_id,'merchant_minimum_order');
				   $min_fees=FunctionsV3::getMinOrderByTableRates(
					   $merchant_id,
					   $resp['distance'],
					   $resp['distance_unit'],
					   $merchant_minimum_order
					);					
					$params['min_delivery_order'] = $min_fees;
				}
				if(!is_numeric($params['min_delivery_order'])){
				    $params['min_delivery_order']=0;
			    }	
			    
			    $params['distance'] = $resp['distance'];
			    $params['distance_unit'] = $resp['distance_unit'];			    
			    			    							    			    			    
				return $params;
				
			} else throw new Exception( $resp );		
		} else throw new Exception( st("address invalid") );		
	}	
	
	public static function getShippingRate($merchant_id='',$default_fee='',$distance='',$unit='')
	{
		$db = new DbExt();
		
		$distance=is_numeric($distance)?number_format($distance,3):0; 
		$shipping_enabled=getOption($merchant_id,'shipping_enabled');    			
		$charge=$default_fee;
		
		if($shipping_enabled==2){
			if ($unit=="ft" || $unit=="mm" || $unit=="mt"){
				// do nothing
			} else {				
				switch (strtolower($unit)){
		    		case "miles":    
		    		case "mi":	
		    			$unit='mi';
		    			break;
		    		case "kilometers":		
		    		case "km":		
		    		    $unit='km';
		    			break;
		    		case "ft":	
		    		    $unit='mi';
		    		    $distance=1;
		    			break;
		    	}
		    	
		    	$stmt="
		    	SELECT * FROM
		    	{{shipping_rate}}
		    	WHERE
		    	merchant_id = ".FunctionsV3::q($merchant_id)."
		    	AND 
		    	shipping_units=".FunctionsV3::q($unit)."  
		    	AND
		    	distance_from<=".$distance." AND distance_to>=".$distance."
		    	LIMIT 0,1
		    	";		    	
		    	if($resp = $db->rst($stmt)){
		    		$resp = $resp[0];		    		
		    	} else {
		    		$stmt2="SELECT * FROM
		    		{{shipping_rate}}
		    		WHERE
		    	    merchant_id = ".FunctionsV3::q($merchant_id)."
		    	    AND 
		    	    shipping_units=".FunctionsV3::q($unit)."  		    	    	    	   
		    	    ORDER BY distance_to DESC
		    	    LIMIT 0,1
		    		";		 		    			
		    		if($res = $db->rst($stmt2)){	    			
		    			$res = $res[0];		    			
		    			if($distance>=$res['distance_to']){		
		    				 $resp = $res;		    				 
		    			}		    			
		    		} 
		    	}	

		    	if(is_array($resp) && count($resp)>=1){		    	   
		    	   if($resp['distance_price']>=0.0001){
		    	   	  return $resp['distance_price'];
		    	   }
		    	}
			}
		} 
		
		if ($charge>0.0001){
    		return $charge;
    	}
		return false;
	}	   
	
	public static function registerScript($script=array(), $script_name='reg_script')
	{
		$reg_script='';
		if(is_array($script) && count($script)>=1){		
			foreach ($script as $val) {
				$reg_script.="$val\n";
			}
			$cs = Yii::app()->getClientScript(); 
			$cs->registerScript(
			  $script_name,
			  "$reg_script",
			  CClientScript::POS_HEAD
			);		
		}
	}
	
	public static function registerJS($data=array())
	{
		$cs = Yii::app()->getClientScript();
		if(is_array($data) && count($data)>=1){
			foreach ($data as $link) {
				Yii::app()->clientScript->registerScriptFile($link,CClientScript::POS_END);
			}
		}		
	}
	
	public static function registerCSS($data=array())
	{		
		$cs = Yii::app()->getClientScript();		
		if(is_array($data) && count($data)>=1){
			foreach ($data as $link) {
				$cs->registerCssFile($link);
			}
		}		
	}		
	
	public static function settingsMenu()
	{
		$menu[] = array(
		  'label'=>"API Settings",
		  'link'=>APP_FOLDER."/index/merchant_settings",
		  'id'=>'merchant_settings'
		);		
		$menu[] = array(
		  'label'=>"Application Settings",
		  'link'=>APP_FOLDER."/index/settings_application",
		  'id'=>'settings_application'
		);		
		$menu[] = array(
		  'label'=>"App startup",
		  'link'=>APP_FOLDER."/index/settings_startup",
		  'id'=>'settings_startup'
		);
		$menu[] = array(
		  'label'=>"Home banner",
		  'link'=>APP_FOLDER."/index/settings_homebanner",
		  'id'=>'settings_homebanner'
		);
		$menu[] = array(
		  'label'=>"Social login",
		  'link'=>APP_FOLDER."/index/settings_social",
		  'id'=>'settings_social'
		);
		$menu[] = array(
		  'label'=>"Android Settings",
		  'link'=>APP_FOLDER."/index/settings_android",
		  'id'=>'settings_android'
		);
		$menu[] = array(
		  'label'=>"FCM",
		  'link'=>APP_FOLDER."/index/settings_fcm",
		  'id'=>'settings_fcm'
		);
		$menu[] = array(
		  'label'=>"Pages",
		  'link'=>APP_FOLDER."/index/settings_pages",
		  'id'=>'settings_pages'
		);
		$menu[] = array(
		  'label'=>"Contact us",
		  'link'=>APP_FOLDER."/index/settings_contactus",
		  'id'=>'settings_contactus'
		);
		return $menu;
	}
	
	public static function purifyData($data=array())
	{
		if(is_array($data) && count($data)>=1){
			$p = new CHtmlPurifier(); $new_data=array();
			foreach ($data as $key=>$val) {
				$new_data[$key]=$p->purify($val);
			}
			return $new_data;
		}
		return false;
	}	
	
	
	public static function registeredDevice($data=array(), $status='active')
	{
		$client_id = isset($data['client_id'])?$data['client_id']:'';
    	$device_id = isset($data['device_id'])?$data['device_id']:'';
    	$device_platform = isset($data['device_platform'])?$data['device_platform']:'';
    	$device_uiid = isset($data['device_uiid'])?$data['device_uiid']:'';
    	$code_version = isset($data['code_version'])?$data['code_version']:'';    	
    	$device_platform = strtolower($device_platform);
    	$merchant_id = isset($data['merchant_id'])?$data['merchant_id']:'';    	
    	
    	$params = array(
    	  'merchant_id'=>$merchant_id,
    	  'device_id'=>$device_id,
    	  'device_platform'=>$device_platform,
    	  'device_uiid'=>$device_uiid,
    	  'status'=>$status,
    	  'code_version'=>$code_version,
    	  'date_created'=>FunctionsV3::dateNow(),
    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
    	);
    	if($client_id>0){
    		$params['client_id'] = (integer) $client_id;
    	}    
    	    	    	    	    	
    	if(!empty($device_uiid)){
    		$stmt="SELECT * FROM
    		{{singleapp_device_reg}}
    		WHERE 
    		device_uiid =".FunctionsV3::q($device_uiid)."     		
    		LIMIT 0,1    		
    		";    	    		
    		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){    			  			    		
    			unset($params['date_created']);
    			$params['date_modified']=FunctionsV3::dateNow();      			    			
    			Yii::app()->db->createCommand()->update("{{singleapp_device_reg}}",$params,
		  	    'id=:id',
			  	    array(
			  	      ':id'=>(integer)$res['id']
			  	    )
		  	    );    			
    		} else {    			
    			Yii::app()->db->createCommand()->insert("{{singleapp_device_reg}}",$params);
    		}
    	}    	
	}
	
    public static function getMapProvider()
	{		
		if (method_exists("FunctionsV3",'getMapProvider')){
			return FunctionsV3::getMapProvider();
		} else {
			$map_provider = getOptionA('map_provider');
			$token = ''; $map_api = '';
			$map_distance_results  = ''; $mode = "driving";
			
			if(empty($map_provider)){
				$map_provider='google.maps';
			}
			
			switch ($map_provider) {
				case "mapbox":
					$token = getOptionA('mapbox_access_token');				
					$map_api = $token;
					$mode = getOptionA('mapbox_method');
					break;
	
				case "google.maps":	
				    $token = getOptionA('google_geo_api_key');
				    $map_api = getOptionA('google_maps_api_key');
				    $mode = getOptionA('google_distance_method');
				default:
					break;
			}
			
			$map_distance_results = (integer) getOptionA('map_distance_results');
			if($map_distance_results<0){
				$map_distance_results=2;
			}
					
			return array(		  
			  'provider'=>$map_provider,
			  'token'=>$token,
			  'map_api'=>$map_api,
			  'map_distance_results'=>$map_distance_results,
			  'mode'=>$mode
			);
		}
	}	    	
	
	public static function timePastByTransaction($transaction_type='')
	{
		$error = '';
		switch ($transaction_type)
		{
			case "delivery":
			case "pickup":
			case "dinein":
				$error = st("Sorry but you have selected [transaction_type] time that already past",array(
				  '[transaction_type]'=>st($transaction_type)
				));
				break;
							
			default:		
			    $error = st("Sorry but you have selected time that already past");
			    break;	
		}
		
		return $error;
	}	
	
	public static function getReceiptByID($order_id=0, $client_id=0)
	{
		$and='';
		$order_id = (integer)$order_id;
		$client_id = (integer)$client_id;
		if($client_id>0){
			$and=" AND a.client_id=".q($client_id)."  ";
		}	
		$stmt="
		SELECT a.*,
		concat(b.first_name,' ',b.last_name) as full_name,
		b.location_name,
		concat(b.street,' ',b.area_name,' ',b.city,' ',b.state,' ',b.zipcode) as full_address,
		b.contact_phone,
		b.contact_phone as customer_phone,
		b.opt_contact_delivery,
		b.contact_email as email_address,
		b.contact_email as customer_email,
		
		c.restaurant_name as merchant_name,
		c.contact_phone as merchant_contact_phone
		
		FROM {{order}} a
		left join {{order_delivery_address}} b
		on
		a.order_id = b.order_id
		
		left join {{merchant}} c
		on
		a.merchant_id = c.merchant_id
		
		WHERE
		a.order_id=".q($order_id)."
		$and
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			/*FIXED OLD DATA*/						
			if(empty( trim($res['full_name']) )){				
				$stmt2 = "
				select 
				concat(first_name,' ',last_name) as full_name,
				contact_phone
				
				from {{client}}
				where client_id = ".q($res['client_id'])."
				";
				if($res2 = Yii::app()->db->createCommand($stmt2)->queryRow()){
					$res['full_name'] = $res2['full_name'];
					$res['contact_phone'] = $res2['contact_phone'];
				}
			}		
			return $res;
		}
		return false;
	}		
	
	public static function ReOrderGetInfo($order_id='')
	{
		$order_id = (integer)$order_id;
		
		$stmt="SELECT a.*,
		b.restaurant_name,
		b.status as merchant_status,
		b.is_ready,
		b.service
		FROM
		{{order}} a
		left join {{merchant}} b
		ON
		a.merchant_id = b.merchant_id		
		WHERE
		a.order_id= ".FunctionsV3::q($order_id)."						
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return FALSE;
	}	
	
	public static function canCancel($date_created='', $days=0, $hours=0, $minutes=0)
	{
		if(!empty($date_created)){
		    $date_created = date("Y-m-d g:i:s a",strtotime($date_created));			
			$date_now=date('Y-m-d g:i:s a');			
			$time_diff=Yii::app()->functions->dateDifference($date_created,$date_now);			
			if(is_array($time_diff) && count($time_diff)>=1){				
				if($days>$time_diff['days']){					
					return true;
				} elseif ( $hours>$time_diff['hours'] ) {
					return true;					
				} elseif ( $hours>=$time_diff['hours']){					
					if($minutes<$time_diff['minutes']){						
						return false;
					} else return true;
				} elseif ( $minutes>=$time_diff['minutes'] ){					
					return true;					
				}
								
			} else return true;
		}
		return false;
	}	
	
	public static function getDeviceByUIID($device_uiid='')
	{
		if(empty($device_uiid)){
			return false;
		}			
		$stmt="
		SELECT * FROM
		{{singleapp_device_reg }}
		WHERE
		device_uiid=".FunctionsV3::q($device_uiid)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}	
	
	public static function getDeviceByID($id='')
	{
		if(empty($id)){
			return false;
		}			
		$stmt="
		SELECT a.*,
		b.first_name,
		b.last_name		
			
		FROM
		{{singleapp_device_reg}} a
		left join {{client}} b
		on
		a.client_id = b.client_id
		
		WHERE
		id=".FunctionsV3::q($id)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}		
	
	public static function OrderTrigger($order_id='',$status='', $remarks='', $trigger_type='order')
	{	
		if(!Yii::app()->db->schema->getTable("{{singleapp_order_trigger}}")){		
			return false;
		}		
		$lang=Yii::app()->language; 
		if($order_id>0){			
			$stmt="SELECT order_id FROM
			{{singleapp_order_trigger}}
			WHERE
			order_id=".FunctionsV3::q($order_id)."
			AND status='pending'			
			LIMIT 0,1
			";
			if(!$res = Yii::app()->db->createCommand($stmt)->queryRow()){
				$params = array(
				  'order_id'=>$order_id,
				  'order_status'=>$status,
				  'remarks'=>$remarks,
				  'language'=>$lang,
				  'date_created'=>FunctionsV3::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				  'trigger_type'=>$trigger_type
				);				
				Yii::app()->db->createCommand()->insert("{{singleapp_order_trigger}}",$params);
			}
		}
	}	
	
	public static function consumeUrl($url='')
	{
		 $is_get_working = true;
		 try {
		 	 $response = @file_get_contents($url);		 	 
		 	 if (isset($http_response_header)) {
		 	 	if (!in_array('HTTP/1.1 200 OK',(array)$http_response_header) && !in_array('HTTP/1.0 200 OK',(array)$http_response_header)) {
		 	 		$is_get_working=false;
		 	 	}
		 	 }
		 } catch (Exception $e) {
		 	$is_get_working = false;
		 }
		 
		 if(!$is_get_working){
		 	$ch = curl_init();
		 	curl_setopt($ch, CURLOPT_URL, $url);
		 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 	$result = curl_exec($ch);
		 	curl_close($ch);
		 }
	}	
	
	public static function inventoryEnabled($merchant_id='')
	{		
		if(empty($merchant_id)){
			return false;
		}	
		if(!is_numeric($merchant_id)){
			return false;
		}	
		
		$inv_enabled = false; 
		if (FunctionsV3::hasModuleAddon('inventory')){		    
		    if(Yii::app()->db->schema->getTable("{{view_item_stocks}}")){	
		    	$inventory_live = getOption($merchant_id,'inventory_live');
		    	if($inventory_live==1){
		    		$inv_enabled = true;
			 		Yii::app()->setImport(array(			
				       'application.modules.inventory.components.*',
			        ));	
		    	}
		    }
		}
		return $inv_enabled;
	}
	
	public static function getMerchantServices($services_id=0)
	{		
		switch ($services_id) {
			case 2:
				return array(
		           'delivery'=>Yii::t("default","Delivery"),
		        );
				break;
			case 3:
				return array(
		            'pickup'=>Yii::t("default","Pickup")          
		        );
				break;
				
			case 4:	
			   return array(
		           'delivery'=>Yii::t("default","Delivery"),
		           'pickup'=>Yii::t("default","Pickup"),
		           'dinein'=>t("Dinein")
		        );
			   break;
			   
			case 5:	
			   return array(
		           'delivery'=>Yii::t("default","Delivery"),			           
		           'dinein'=>t("Dinein")
		        );
			   break;
			   
			case 6:	
			   return array(
		           'pickup'=>Yii::t("default","Pickup"),
		           'dinein'=>t("Dinein")
		        );
			   break;   
			  
			case 7:	
			   return array(			           
		           'dinein'=>t("Dinein")
		        );
			   break;       
			      
			default:
				return array(
		           'delivery'=>Yii::t("default","Delivery"),
		           'pickup'=>Yii::t("default","Pickup") 
		        );
				break;
		}	
	}

    public static function sticPrettyDate($date='')
    {
        if (!empty($date)){
            $date_format=getOptionA('website_date_format');
            if (empty($date_format)){
                $date_format="l, M j";
            }
            $date = date($date_format,strtotime($date));
            return Yii::app()->functions->translateDate($date);
        }
        return false;
    }
    
    public static function sticPrettyTime($time='')
    {
        if (!empty($time)){
            $format=getOptionA('website_time_format');          
            if(empty($format)){
                $format="g:i a";
            }
            return date($format,strtotime($time));
        }
        return false;
    }

}
/*END CLASS*/
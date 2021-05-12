<?php
class OrderWrapper
{
	
	public static function getAllOrder($today_order=true,$order_type='',$merchant_id=0,$start=0, $total_rows=10,$search_string='')
	{
				
		$and='';  $order_by = 'ORDER BY order_id ASC';
		$todays_date = date("Y-m-d");
		
		/*if($today_order){			
			$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
		}*/
		
		if(!empty($search_string)){							
			$and.="
			AND (
			  a.order_id = ".q($search_string)."
			  OR
			  b.first_name LIKE ".q("$search_string%")." 
			)
			";
		}		
				
		if(!empty($order_type)){
			switch ($order_type) {				
				case "incoming":
					$stats = self::getStatusFromSettings('order_incoming_status',array('pending','paid'));
					
					$and.=" AND a.status IN ($stats)
					AND request_cancel='2'
					";
					$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
					break;
			
				case "outgoing":
					
					$stats = self::getStatusFromSettings('order_outgoing_status',array('accepted','delayed'));
					
					$and.=" AND a.status IN ($stats)
					AND request_cancel='2'
					";		
					$and.=" AND CAST(a.delivery_date as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
					break;
						
				case "ready":
					$stats = self::getStatusFromSettings('order_ready_status',array('ready for delivery'));
					
				    $and.=" AND a.status IN ($stats)
				    AND request_cancel='2'
				    ";
				    $and.=" AND CAST(a.delivery_date as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
					break;
					
				case "cancel_order":
					$and.=" AND a.status NOT in ('".initialStatus()."') 
					AND request_cancel = '1'
					";
					break;
					
				case "all":
					$and.=" AND a.status NOT in ('".initialStatus()."') ";
					$order_by = 'ORDER BY order_id DESC';
					break;
					  
				default:
					break;
			}			
		}		
		
		$and_driver = '';
		if(Yii::app()->db->schema->getTable("{{driver_task}}")){
			$and_driver=",
			IFNULL((
			 select count(*)
			 from {{driver_task}}
			 where
			 order_id = a.order_id
			 and status not in ('unassigned')	
			 and driver_id>0		 
			),'0') as assigned_driver
			";						
		}
		
		if(Yii::app()->db->schema->getTable("{{driver_task_view}}")){
			$and_driver.=",
			IFNULL((
			 select concat(driver_id,'|',driver_name,'|',driver_photo)
			 from {{driver_task_view}}
			 where
			 order_id = a.order_id			 
			),'') as driver_information
			";						
		}
		
		if(Yii::app()->db->schema->getTable("{{driver_task_view}}")){
			$contact = "concat(task_lat,',',task_lng,'|',dropoff_lat,',',dropoff_lng,'|',driver_lat,',',driver_lng)";
			if(DatataseMigration::checkFields("{{driver_task_view}}",array('driver_vehicle'=>'driver_vehicle'))){
				$contact = "concat(task_lat,',',task_lng,'|',dropoff_lat,',',dropoff_lng,'|',driver_lat,',',driver_lng,'|',driver_vehicle)";
			}
			$and_driver.=",
			IFNULL((
			 select $contact
			 from {{driver_task_view}}
			 where
			 order_id = a.order_id			 
			 and driver_id>0		 
			),'') as task_location
			";
		}
				
				
		$stmt="
		select SQL_CALC_FOUND_ROWS 
		a.order_id, a.merchant_id,a.total_w_tax as total_order_amount,
		a.status,a.status as status_raw,a.trans_type, a.trans_type as trans_type_raw,
		a.date_created,
		a.delivery_date,a.delivery_time,
		a.request_cancel,
		a.date_modified,
		a.delivery_asap,
		concat(b.street,' ',b.area_name,' ',b.city,' ',b.state,' ',b.zipcode) as full_address,
		concat(b.first_name,' ',b.last_name) as customer_name,	
		b.estimated_time, b.estimated_date_time,
		b.used_currency,
		b.base_currency,
		b.exchange_rate,
		(
		 select count(*) from {{order_details}}
		 where order_id = a.order_id
		) as total_items
		$and_driver
		
		FROM
		{{order}} a
		left join {{order_delivery_address}} b
		ON
		a.order_id = b.order_id
				
		WHERE a.merchant_id = ".q( (integer)$merchant_id )."		
		$and
		$order_by
		LIMIT $start,$total_rows
		";				
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){           		    
        	return $resp;
        }        
        return false;     
	}				
		
	public static function getActionStatus($action='')
	{
		$status='';
		switch ($action) {
			case "accept":				
				$order_action_accepted_status = getOptionA('order_action_accepted_status');
				$status = !empty($order_action_accepted_status)?$order_action_accepted_status:'accepted';
				break;
			case "ready_for_delivery":
				$status = 'ready for delivery';
			    break;
			    
			case "decline_order":
			case "decline":	
				//$status = 'decline';
				$stats = getOptionA('order_action_decline_status');
				$status = !empty($stats)?$stats:'decline';
				break;
				
			case "cancel_order":	
			    //$status = 'cancelled';
			    $stats = getOptionA('order_action_cancel_status');
				$status = !empty($stats)?$stats:'cancelled';
				break;
			
			case "food_is_done":
				//$status = 'ready for delivery';
				$stats = getOptionA('order_action_food_done_status');
				$status = !empty($stats)?$stats:'ready for delivery';
				break;
				
			case "delay_order":	
			    //$status = 'delayed';
			    $stats = getOptionA('order_action_delayed_status');
				$status = !empty($stats)?$stats:'delayed';
			    break;
			    
			case "complete_order":	
			    //$status = 'completed';
			    $stats = getOptionA('order_action_completed_status');
				$status = !empty($stats)?$stats:'completed';
			    break;
			        
			case "approved_cancel_order":    
			   //$status = 'cancelled';
			   $stats = getOptionA('order_action_approved_cancel_order');
			   $status = !empty($stats)?$stats:'cancelled';
			   break;
			   
			case "decline_cancel_order":
				//$status = 'pending';
				$stats = getOptionA('order_action_decline_cancel_order');
			   $status = !empty($stats)?$stats:'pending';
				break;  
			   
			default:
				$status = 'unknow status';
				break;
		}
		return $status;
	}
	
	public static function validateOrder($merchant_id='',$order_id='')
	{
		$resp = Yii::app()->db->createCommand()
          ->select('order_id,delivery_date,delivery_time,date_created')
          ->from('{{order}}')   
          ->where("merchant_id=:merchant_id AND order_id=:order_id",array(
             ':merchant_id'=>$merchant_id,
             ':order_id'=>$order_id,
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	return $resp;
        }
        return false;     
	}
	
	public static function getOrder($merchant_id='',$order_id='')
	{
		$resp = Yii::app()->db->createCommand()
          ->select('order_id,status')
          ->from('{{order}}')   
          ->where("merchant_id=:merchant_id AND order_id=:order_id",array(
             ':merchant_id'=>$merchant_id,
             ':order_id'=>$order_id,
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	return $resp;
        }
         throw new Exception( "Order id not found" );
	}
	
	public static function updateOrderHistory($order_id='',$merchant_id,$params=array(),$params2=array())
	{		
		if($order_id>0){
			if(self::validateOrder($merchant_id,$order_id)){
				if(Yii::app()->db->createCommand()->insert("{{order_history}}",$params)){
					$up =Yii::app()->db->createCommand()->update("{{order}}",$params2,
			  	    'order_id=:order_id',
				  	    array(
				  	      ':order_id'=>$order_id
				  	    )
			  	    );
			  	    return true;
				} else throw new Exception( "Failed cannot insert records" );
			} else throw new Exception( "Order id not found" );
		}
		throw new Exception( "Invalid order id" );
	}
	
	public static function orderStatusList($merchant_id='',$remove_cancel=false)
	{
		$data = array(); $and="";		
		if($remove_cancel){
			$remove_cancel_status=getOptionA('merchantapp_remove_cancel_status');
			if($remove_cancel_status==1){				
				$cancel_status = OrderWrapper::getActionStatus('cancel_order');
				$and.=" AND description NOT IN (".q($cancel_status).")";
			}
		}
		$stmt ="
		SELECT description 
		FROM {{order_status}}
		WHERE merchant_id IN ('0',".q($merchant_id).")
		$and
		ORDER  BY description ASC
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
				$data[]=array(
				  'value'=>$val['description'],
				  'label'=>t($val['description']),
				);
			}
			return $data;
		}
		return false;
	}
	
	public static function orderStatusListPaginate($merchant_id='',$start=0, $total_rows=10,$search_string='')
	{
		$data = array();
		$stmt ="
		SELECT description 
		FROM {{order_status}}
		WHERE merchant_id IN ('0',".q($merchant_id).")
		ORDER  BY description ASC
		LIMIT $start,$total_rows
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			foreach ($res as $val) {
				$data[]=array(
				  'value'=>$val['description'],
				  'label'=>t($val['description']),
				);
			}
			return $data;
		}
		return false;
	}
	
	public static function AllOrderStatus()
	{
		$data = array();
		$stmt ="
		SELECT description 
		FROM {{order_status}}		
		ORDER  BY description ASC
		";
		
		if($res = Yii::app()->db->createCommand($stmt)->queryAll()){
			return $res;
		}
		return false;
	}
	
	public static function updateEstimationTime($order_id='',$estimation_delay=0)
	{
		$resp = Yii::app()->db->createCommand()
          ->select('order_id,estimated_time')
          ->from('{{order_delivery_address}}')   
          ->where("order_id=:order_id",array(
             ':order_id'=>(integer)$order_id
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){
        	//$estimated_time = $resp['estimated_time'] + (float) $estimation_delay;           	
        	$estimated_date_time = date("Y-m-d H:i:s", strtotime("+$estimation_delay minutes"));
        	$params = array( 
        	  //'estimated_time'=>$estimated_time,
        	  'estimated_date_time'=>$estimated_date_time,
        	  'ip_address'=>$_SERVER['REMOTE_ADDR']
        	);        	
        	Yii::app()->db->createCommand()->update("{{order_delivery_address}}",$params,
	  	    'order_id=:order_id',
		  	    array(
		  	      ':order_id'=>(integer)$order_id
		  	    )
	  	    );
	  	    return true;
        }
        return false;     
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
		b.estimated_time, b.estimated_date_time,
		b.google_lat as location_lat, b.google_lng as location_lng,
		b.used_currency,
		b.base_currency,
		b.exchange_rate,
		b.service_fee,
		b.service_fee_applytax,
		
		c.restaurant_name as merchant_name,
		c.restaurant_phone as merchant_contact_phone,
		concat(c.street,' ',c.city,' ',c.state,' ',c.post_code) as merchant_address
		
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
	
	public static function prepareReceipt($order_id='', $with_symbol = true)
	{		
		$details_details = array(); $data = array(); $order_details=array();
		
		$default_currency = FunctionsV3::getCurrencyCode();
		$enabled_trans = Yii::app()->functions->multipleField();
		
		if ($data=self::getReceiptByID($order_id)){
					
			/*MC*/
			$used_currency = isset($data['used_currency'])?$data['used_currency']: $default_currency;
			if(Merchant_utility::$price_formater){
			   Price_Formatter::init( $used_currency );
			}
							
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
			   'voucher_type'=>$data['voucher_type'],
			   'service_fee'=>isset($data['service_fee'])?(float)$data['service_fee']:0,
		       'service_fee_applytax'=>isset($data['service_fee_applytax'])?(integer)$data['service_fee_applytax']:false,
		       'tax_set'=>$data['tax'],
			  ),$json_details,true,$order_id);
			}		
								
			$details_details['order_id']=$data['order_id'];
			$details_details['merchant_name']= clearString($data['merchant_name']);			
			$details_details['merchant_contact_phone']= clearString($data['merchant_contact_phone']);
			$details_details['merchant_address']= clearString($data['merchant_address']);
			
			$details_details['request_cancel']=$data['request_cancel'];
			$details_details['customer_name']=$data['full_name'];			
			$details_details['date_created']=FunctionsV3::prettyDate($data['date_created'])." ".FunctionsV3::prettyTime($data['date_created']);		
			$details_details['payment_type_raw'] = $data['payment_type'];
			$details_details['payment_type']=self::prettyPaymentType($data['payment_type'],$data['trans_type']);			
			$details_details['trans_type'] = t($data['trans_type']);
			$details_details['trans_type_raw'] = $data['trans_type'];
			
			$details_details['status']=t($data['status']);
			$details_details['status_raw']=$data['status'];
			
						
			$details_details['estimated_time']=$data['estimated_time'];
			$details_details['estimated_date_time']=$data['estimated_date_time'];
			
			$details_details['sub_total']=$data['sub_total'];
			$details_details['total']=self::prettyPrice($data['total_w_tax'], $with_symbol);
			$details_details['delivery_address']=$data['full_address'];
			$details_details['location_lat']=!empty($data['location_lat'])?$data['location_lat']:'';
			$details_details['location_lng']=!empty($data['location_lng'])?$data['location_lng']:'';
			
			$details_details['contact_phone']=$data['contact_phone'];
			$details_details['delivery_date']=FunctionsV3::prettyDate($data['delivery_date']);
			$details_details['delivery_time']=FunctionsV3::prettyTime($data['delivery_time']);
			
			$date_now = date('Ymd'); $pre_order = 0; $pre_order_msg='';
			$delivery_date=$data['delivery_date'];
			$delivery_date=date("Ymd",strtotime($delivery_date));			
			if($delivery_date>$date_now){
				$pre_order = 1;
				$dtime = !empty($data['delivery_time'])?date("g:ia",strtotime($data['delivery_time'])):'';
				$pre_order_msg = translate("This is advance order on [date]",array(					  
				  '[date]'=>FunctionsV3::prettyDate($data['delivery_date'])." $dtime"
				));
			}
			
			$details_details['pre_order']=$pre_order;
			$details_details['pre_order_msg']=$pre_order_msg;
			
			$details_details['delivery_asap']=$data['delivery_asap'];
			$details_details['delivery_instruction']=$data['delivery_instruction'];
			$details_details['location_name']=$data['location_name'];
			$details_details['order_change_raw']=$data['order_change'];
			$details_details['order_change']=self::prettyPrice($data['order_change'],$with_symbol);
			
			$details_details['payment_gateway_ref']=$data['payment_gateway_ref'];
			$details_details['dinein_number_of_guest']=$data['dinein_number_of_guest'];
			$details_details['dinein_special_instruction']=$data['dinein_special_instruction'];
			$details_details['dinein_table_number']=$data['dinein_table_number'];
			
			$details_details['opt_contact_delivery']=$data['opt_contact_delivery'];
			if($data['opt_contact_delivery']>=1){
				$details_details['opt_contact']=array(
				  'label'=>translate("Delivery options"),
				  'value'=>translate("Leave order at the door or gate")
				);
			}
			
			if($data['payment_type']=="ocr"){
				if($creditcard_info = FunctionsV3::getCreditCardInfo($data['client_id'],$data['cc_id'])){
					$details_details['card_information']=array(
					  'card_name'=>$creditcard_info['card_name'],
					  'mask_card_number'=>$creditcard_info['credit_card_number'],
					  'expiration'=>$creditcard_info['expiration_month']."/".$creditcard_info['expiration_yr'],
					  'billing_address'=>$creditcard_info['billing_address']
					);
				}
			}
						
			$raw = Yii::app()->functions->details['raw'];			
						
			foreach ($raw['item'] as $item){
				$price = $item['normal_price']; $qty = $item['qty']; $addon_total=0;
				if($item['discount']>0){
   	               $price = $item['discounted_price']; 
                }
                $item_total = (integer)$qty* (float) $price; 
                
                $item_name=''; $line_items = array(); 
                                
                $item_name = FoodItemWrapper::qTranslate($item['item_name'],'item_name',
                $item['item_name_trans'],$enabled_trans);                
                
                $size_name = FoodItemWrapper::qTranslate($item['size_words'],'size_name',
                isset($item['size_name_trans'])?$item['size_name_trans']:array(),$enabled_trans);                
                
                if(!empty($item['size_words'])){
			   	   $item_name = translate("[item_name] ([size_words])",array(
			   	     '[item_name]'=>$item_name,
			   	     '[size_words]'=>$size_name,
			   	   ));
			    } 
			    
			    /*SUB*/			    
			    $line_sub_item=array(); $subitem =array();
			    if(isset($item['new_sub_item'])){
			    	if(is_array($item['new_sub_item']) && count($item['new_sub_item'])>=1){
			    		foreach ($item['new_sub_item'] as $sub_item_cat => $sub_item){	
			    			$subitem = array();
			    			foreach ($sub_item as $sub_item_val) {
			    				$sub_item_total = (float)$sub_item_val['addon_qty']*(float)$sub_item_val['addon_price'];
			    				
			    				$addon_name =  FoodItemWrapper::qTranslate($sub_item_val['addon_name'],'sub_item_name',
                                isset($sub_item_val['sub_item_name_trans'])?$sub_item_val['sub_item_name_trans']:array(),
                                $enabled_trans);   
                                
                                $addon_category = FoodItemWrapper::qTranslate($sub_item_cat,'subcategory_name',
                                isset($sub_item_val['subcategory_name_trans'])?$sub_item_val['subcategory_name_trans']:array(),
                                $enabled_trans);  
			    				
			    				$subitem[] = array(
			    				  'name'=>$addon_name,
			    				  'qty'=>$sub_item_val['addon_qty'],
			    				  'price'=>self::prettyPrice($sub_item_val['addon_price'],$with_symbol),
			    				  'sub_item_total'=>self::prettyPrice($sub_item_total,$with_symbol),
			    				);
			    				$line_sub_item[$sub_item_cat]= array(
								  'addon_category'=>$addon_category,
								  'item'=>$subitem
								);
			    			}
			    		}			    					    		
			    	}
			    }
                			    
			    $item_total_price = (float)$qty*(float)$price;
			    
			    $cooking_ref = FoodItemWrapper::qTranslate($item['cooking_ref'],'cooking_name',
                isset($item['cooking_name_trans'])?$item['cooking_name_trans']:array(),$enabled_trans);
                
                $ingredients = array();  $resp_ingredients='';
                if(isset($item['ingredients'])){
                   if(is_array($item['ingredients']) && count($item['ingredients'])>=1){
                   	  foreach ($item['ingredients'] as $ingredients_name) {
                   	  	 if(!$resp_ingredients = FoodItemWrapper::getIngredientsByName($ingredients_name,$enabled_trans)){
                   	  	 	$resp_ingredients = stripslashes($ingredients_name);
                   	  	 }
                   	  	 $ingredients[]=$resp_ingredients;
                   	  }
                   }
                }
                
			    
			    $line_items[]=array(
			      'name'=>$item_name,
			      'qty'=>$qty,
			      'price'=>self::prettyPrice($item['normal_price'],$with_symbol),
			      'discount'=>$item['discount'],
			      'price_after_discount'=>self::prettyPrice($price,$with_symbol),
			      'item_total_price'=>self::prettyPrice($item_total_price,$with_symbol),
			      'cooking_ref'=>$cooking_ref,
			      'order_notes'=>$item['order_notes'],
			      'ingredients'=>$ingredients,
			      'sub_item'=>$line_sub_item
			    );
			    
			    $category_name = FoodItemWrapper::qTranslate($item['category_name'],'category_name',
			    isset($item['category_name_trans'])?$item['category_name_trans']:array()
                ,$enabled_trans);   
                
                $order_details[]=array(
                  'category_name'=>$category_name,
                  'item'=>$line_items
                );
			}			
			
			/*TOTAL*/
			$total_details = array();
			$total = $raw['total'];
			
			$total_details['apply_food_tax'] = $data['apply_food_tax'];
			
			/*EURO TAX*/
			if($data['apply_food_tax']==1 && $data['tax']>0){		
										
				$grand_total = isset($total['subtotal'])?(float)$total['subtotal']:0;
				$grand_total+= isset($total['delivery_charges'])?(float)$total['delivery_charges']:0;
				$grand_total+= isset($total['merchant_packaging_charge'])?(float)$total['merchant_packaging_charge']:0;
				$grand_total+= isset($total['service_fee'])?(float)$total['service_fee']:0;
				$grand_total+= isset($total['card_fee'])?(float)$total['card_fee']:0;
				$grand_total+= isset($total['tips'])?(float)$total['tips']:0;				
				
				/*$grand_total-= isset($total['less_voucher'])?(float)$total['less_voucher']:0;
				$grand_total-= isset($total['pts_redeem_amt'])?(float)$total['pts_redeem_amt']:0;
				$grand_total-= isset($total['discounted_amount'])?(float)$total['discounted_amount']:0;	*/			
				//dump($grand_total);
				$new_sub_total = $grand_total/($data['tax']+1);				
				$taxable_total = (float)$grand_total - (float)$new_sub_total;				
				
				$total['subtotal'] = $new_sub_total;
				$total['taxable_total'] = $taxable_total;
				$total['total'] = $grand_total;	
			}
			
			
			if($total['less_voucher']>0){
				$total_details['less_voucher']=self::prettyPrice($total['less_voucher'],$with_symbol);
			}
			if($total['pts_redeem_amt']>0){
				$total_details['pts_redeem_amt']=self::prettyPrice($total['pts_redeem_amt'],$with_symbol);
			}
			
			if($total['discounted_amount']>0){
				$total_details['discounted_amount']=self::prettyPrice($total['discounted_amount'],$with_symbol);
				$total_details['merchant_discount_amount']=$total['merchant_discount_amount']."%";
			}
			
			if($total['subtotal']>0){
				$total_details['subtotal']=self::prettyPrice($total['subtotal'],$with_symbol);
			}
			if($total['delivery_charges']>0){
				$total_details['delivery_charges']=self::prettyPrice($total['delivery_charges'],$with_symbol);
			}
			
			if(isset($total['service_fee'])){
			if($total['service_fee']>0){
				$total_details['service_fee']=self::prettyPrice($total['service_fee'],$with_symbol);
			}
			}
			
			if($total['merchant_packaging_charge']>0){
				$total_details['packaging_charge']=self::prettyPrice($total['merchant_packaging_charge'],$with_symbol);
			}
			if($total['taxable_total']>0){
				$total_details['tax']=array(
				  'taxable_total'=>self::prettyPrice($total['taxable_total'],$with_symbol),
				  'tax_label'=>translate("Tax [tax]%",array('[tax]'=> normalPrettyPrice($total['tax']*100) ))
				);
			}
			if($total['tips_percent']>0){
				$total_details['tips']=array(
				  'value'=>self::prettyPrice($total['tips'],$with_symbol),
				  'label'=>translate("Tips [tips]",array('[tips]'=> $total['tips_percent'] ))
				);
			}
			
			if(isset($total['card_fee'])){
			if($total['card_fee']>0){
				$total_details['card_fee']=self::prettyPrice($total['card_fee'],$with_symbol);
			}
			}
			
			if($total['total']>0){
				$total_details['total'] = self::prettyPrice($total['total'],$with_symbol);
			}
									
			return array(
			  'order_data'=>$details_details,
			  'order_details'=>$order_details,
			  'total_details'=>$total_details
			);
			
		} else throw new Exception( t("order not found"));
	}
	
	public static function prettyPaymentType($code='',$transaction_type='')
	{
		$list = FunctionsV3::PaymentOptionList();		
		if(array_key_exists($code,$list)){
			switch ($transaction_type) {
				case "pickup":		
				    if($code=="cod"){	
				       $data= translate("Cash on pickup");				    
				    } else {
				    	if(isset($list[$code])){
						   $data = translate($list[$code]);
						} else $data = $code;
				    }
					break;
			
				case "dinein":							
				    if($code=="cod"){	
				       $data= translate("Pay in person");		   
				    } else {
				    	if(isset($list[$code])){
						   $data = translate($list[$code]);
						} else $data = $code;
				    }
					break;
						
				default:
					if(isset($list[$code])){
					   $data = translate($list[$code]);
					} else $data = $code;
					break;
			}			
		}
		return $data;
	}
	
	public static function getStatusFromSettings($option_name='', $status = array())
	{
		$status_new = '';
		$order_incoming_status = getOptionA($option_name);
		if(!empty($order_incoming_status)){
			if($order_incoming_status = json_decode($order_incoming_status,true)){
			   $status = $order_incoming_status;
			}
		}
		
		if(is_array($status) && count($status)>=1){
			foreach ($status as $val) {
				$status_new.=q($val).",";
			}
			$status_new = substr($status_new,0,-1);
		}		
		return $status_new;		
	}
	
	public static function getNewestOrder($order_ids='',$merchant_id='')
	{		
		$todays_date = date("Y-m-d");
		$status = self::getStatusFromSettings('order_incoming_status',array('pending','paid'));
		if(!empty($order_ids)){
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND order_id NOT IN ($order_ids)
			AND status IN ($status)
			AND request_cancel='2'
			AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";					
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		} else {			
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND status IN ($status)
			AND request_cancel='2'
			AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";							
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
				return true;
			}
		}
		return false;
	}
	
	public static function reheckNewestOrder($order_ids='',$merchant_id='')
	{
		$todays_date = date("Y-m-d");
		$status = self::getStatusFromSettings('order_incoming_status',array('pending','paid'));
		if(!empty($order_ids)){
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND order_id IN ($order_ids)
			AND status IN ($status)
			AND request_cancel='2'
			AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";					
			if(!$res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		}
		return false;
	}
	
	public static function getNewestCancel($order_ids='',$merchant_id='')
	{
		if(!empty($order_ids)){
			$stmt="
			SELECT order_id 
			FROM {{order}}	
			WHERE order_id NOT IN ($order_ids)
			AND request_cancel='1'
		    AND request_cancel_viewed = '2'
		    AND request_cancel_status='pending'
		    AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		} else {
			$todays_date = date("Y-m-d");
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE 1
			AND request_cancel='1'
		    AND request_cancel_viewed = '2'
		    AND request_cancel_status='pending'
		    AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
				return true;
			}
		}
		return false;
	}
	
	public static function verifyNewestOrder($order_ids='')
	{		
		if(!empty($order_ids)){
			$stmt="
			SELECT order_id 
			FROM {{order}}	
			WHERE order_id IN ($order_ids)
			LIMIT 0,1
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		} else {
			//echo 'd2';
		}
		return false;
	}
	
	public static function getOrderHistory($order_id=0)
	{
		$resp = Yii::app()->db->createCommand()
          ->select('order_id,status,remarks,date_created,remarks2,remarks_args')
          ->from('{{order_history}}')   
          ->where("order_id=:order_id",array(
             ':order_id'=>(integer)$order_id,             
          )) 
          ->order('id asc')              
          ->queryAll();		
          
        if($resp){
        	return $resp;
        }
        return false;     
	}
	
	public static function prettyDateTime($date_time='')
	{
		if(!empty($date_time)){
		   return FunctionsV3::prettyDate($date_time)." ".FunctionsV3::prettyTime($date_time);
		}
		return '-';
	}
	
	public static function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
	{
	    $datetime1 = date_create($date_1);
	    $datetime2 = date_create($date_2);	   
	    $interval = date_diff($datetime1, $datetime2);	   
	    return $interval->format($differenceFormat);	  
	}
	
	public static function InsertOrderTrigger($order_id='',$status='',$remarks='',$trigger_type='order')
	{
		$order_id  = (integer) $order_id;
		if(Yii::app()->db->schema->getTable("{{merchantapp_order_trigger}}")){
			$lang=Yii::app()->language; 
			if($order_id>0){			
				$stmt="SELECT order_id FROM
				{{merchantapp_order_trigger}}
				WHERE
				order_id=".FunctionsV3::q($order_id)."
				AND order_status=".q($status)."
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
					Yii::app()->db->createCommand()->insert("{{merchantapp_order_trigger}}",$params);					
					self::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/trigger_order"));	
					if($trigger_type=="booking"){
					   self::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("merchantappv2/cron/trigger_order_booking"));
					}
				}
			}
		}
	}	
	
	
	public static function consumeUrl($url='')
	{		
		$is_curl_working = true;
		$ch = curl_init();
	 	curl_setopt($ch, CURLOPT_URL, $url);
	 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	 	$result = curl_exec($ch);
	 	if (curl_errno($ch)) {		    
		    $is_curl_working = false;
		}
	 	curl_close($ch);
	 	
	 	if(!$is_curl_working){
	 		 $response = @file_get_contents($url);		 	 
		 	 if (isset($http_response_header)) {
		 	 	if (!in_array('HTTP/1.1 200 OK',(array)$http_response_header) && !in_array('HTTP/1.0 200 OK',(array)$http_response_header)) {
		 	 		//
		 	 	}
		 	 }
	 	}
	}
	
	public static function getOrderLocation($order_id='')
	{		
		$stmt="
		SELECT 
		a.order_id,
		a.google_lat as delivery_lat,
		a.google_lng as delivery_lng,
		b.merchant_id,
		c.latitude as merchant_lat,
		c.lontitude as merchant_lng
		
		FROM {{order_delivery_address}} a
		LEFT JOIN {{order}} b
		ON
		a.order_id = b.order_id
		
		LEFT JOIN {{merchant}} c
		ON
		b.merchant_id = c.merchant_id
		
		WHERE 
		a.order_id=".q($order_id)."
		";		
        if($resp = Yii::app()->db->createCommand($stmt)->queryRow()){
        	return $resp;
        }
         throw new Exception( "Order id not found" );
	}
	
	public static function getUpdatedReadyOrder($order_ids='',$merchant_id='')
	{		
		$todays_date = date("Y-m-d");
		$start = date("Y-m-d H:i:s", strtotime("-2 minutes"));		
		$status = self::getStatusFromSettings('order_ready_status',array('pending','paid'));
		if(!empty($order_ids)){
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."
			AND order_id IN ($order_ids)
			AND status NOT IN ($status)
			AND request_cancel='2'
			AND merchant_id = ".q($merchant_id)."
			AND ".q($start)." < date_modified   			
			LIMIT 0,1
			";					
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){							
				return true;
			}
		} else {			
			$stmt="
			SELECT order_id 
			FROM {{order}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND status IN ($status)
			AND request_cancel='2'
			AND merchant_id = ".q($merchant_id)."
			LIMIT 0,1
			";							
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
				return true;
			}
		}
		return false;
	}
	
	public static function prettyPrice($amount=0, $with_symbol=true)
	{
		if($with_symbol){
			//return FunctionsV3::prettyPrice($amount);
			return Merchant_utility::formatNumber($amount);
		} else {
			$amount = $amount>0?$amount:0;
			return normalPrettyPrice($amount);
		}
	}
	
	public static function canChangeOrderStatus($order=array())
	{
		/*$date_now=date('Y-m-d');
		$can_edit=Yii::app()->functions->getOptionAdmin('merchant_days_can_edit_status');								
		if (is_numeric($can_edit)){
			$base_option=getOptionA('merchant_days_can_edit_status_basedon');	    										
			if ( $base_option==2){	    					
				$date_created=date("Y-m-d",strtotime($order['delivery_date']." ".$order['delivery_time']));		
			} else $date_created=date("Y-m-d",strtotime($order['date_created']));	    						    					    		$date_interval=Yii::app()->functions->dateDifference($date_created,$date_now);					
			if (is_array($date_interval) && count($date_interval)>=1){		    				
				if ( $date_interval['days']>$can_edit){
					return false;
				}		    			
			}	    		
		}
		return true;*/
		
		$datenow=date('Y-m-d g:i:s a');  
        $edit_days = (integer) Yii::app()->functions->getOptionAdmin('merchant_days_can_edit_status');
        $edit_times =  Yii::app()->functions->getOptionAdmin('merchant_time_can_edit_status');

        if($edit_days>0 || !empty($edit_times)){
        	$base_option=getOptionA('merchant_days_can_edit_status_basedon');
        	if ( $base_option==2){	    					
				if(!empty($order['delivery_time'])){
				   $date_created=date("Y-m-d g:i:s a",strtotime($order['delivery_date']." ".$order['delivery_time']));
				} else $date_created=date("Y-m-d g:i:s a",strtotime($order['delivery_date']." ".$order['date_created']));
			} else $date_created=date("Y-m-d g:i:s a",strtotime($order['date_created']));	    	
			
			$date_interval=Yii::app()->functions->dateDifference($date_created,$datenow); 			
			if (is_array($date_interval) && count($date_interval)>=1){
				if ( $date_interval['days']>$edit_days){
					return false;
				}
								
				if(!empty($edit_times)){
					$times = explode(":",$edit_times);		    						    					
			        $hour = isset($times[0])?(integer)$times[0]:0;	    					
			        $minute = isset($times[1])?(integer)$times[1]:0;
			        if($hour>0){
						if($date_interval['hours']>$hour){
							return false;
						}	    					
					} else {	    						
						if($date_interval['minutes']>$minute){
							return false;
						}	    					
					}	    				
				}
			}
        }
        return true;
	}
	
	public static function updateTaskDeliveryDate($order_id='',$estimation_delay=0)
	{
		if(!Yii::app()->db->schema->getTable("{{driver_task}}")){
			return false;
		}
		
		$resp = Yii::app()->db->createCommand()
          ->select('order_id,delivery_date')
          ->from('{{driver_task}}')   
          ->where("order_id=:order_id",array(
             ':order_id'=>(integer)$order_id
          )) 
          ->limit(1)
          ->queryRow();		
          
        if($resp){                	
        	$delivery_date = date("Y-m-d H:i:s",strtotime($resp['delivery_date']));        	
        	$estimated_date_time = date("Y-m-d H:i:s", strtotime("+$delivery_date $estimation_delay minutes"));
        	$params = array(         	  
        	  'delivery_date'=>$estimated_date_time,
        	  'date_modified'=>FunctionsV3::dateNow(),
        	  'ip_address'=>$_SERVER['REMOTE_ADDR']
        	);            	
        	Yii::app()->db->createCommand()->update("{{driver_task}}",$params,
	  	    'order_id=:order_id',
		  	    array(
		  	      ':order_id'=>(integer)$order_id
		  	    )
	  	    );
	  	    return true;
        }
        return false;     
	}
	
	public static function validateChangeStatus($order_id='', $status='')
	{
		$stmt="
		SELECT id,order_id,status FROM {{order_history}}
		WHERE order_id=".q( (integer) $order_id )."
		ORDER BY id DESC
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){			
			if($status==$res['status']){
				return true;
			}
		}
		return false;
	}
	
	public static function updateTaskStatus($order_id='', $status='')
	{
		if(!Yii::app()->db->schema->getTable("{{driver_task}}")){
			return false;
		}
		
		$params = array(
		 'status'=>$status,
		 'date_modified'=>FunctionsV3::dateNow(),
		 'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		Yii::app()->db->createCommand()->update("{{driver_task}}",$params,
  	    'order_id=:order_id',
	  	    array(
	  	      ':order_id'=>(integer)$order_id
	  	    )
  	    );
	}
	
}
/*end class*/
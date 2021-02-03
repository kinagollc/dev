<?php
class GraphicalTracking
{
	
	public static function orderDetails($order_id='')
    {    	
    	
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
    	SELECT 
    	a.order_id,
    	a.merchant_id,
    	a.client_id,
    	a.trans_type,
    	a.status,
    	a.status as status_raw,
    	a.payment_type,
    	a.payment_type as payment_type_raw,
    	a.viewed,
    	a.merchantapp_viewed,
    	a.date_created,
    	a.delivery_date,
    	a.delivery_time,
    	b.restaurant_name as merchant_name,
		b.logo,
		b.latitude as merchant_location_lat,
		b.lontitude as merchant_location_long,
		b.contact_phone as merchant_contact_number,
		c.estimated_time,
		c.estimated_date_time,
		
		IFNULL((
		select rating from {{review}}
		where order_id = a.order_id
		and status='publish'		
		limit 0,1
		),0) as rating
		
		$and_driver
							
		FROM
		{{order}} a
		
		left join {{merchant}} b
        ON
        a.merchant_id = b.merchant_id
        
        left join {{order_delivery_address}} c
        ON
        a.order_id = c.order_id
                
		WHERE a.order_id=".FunctionsV3::q($order_id)."
		LIMIT 0,1
    	";    	    	
    	if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
    	   $res = Yii::app()->request->stripSlashes($res);
    	   return $res;
    	}
    	return false;
    }

    
	public static function getEstimationTime($trans_type='', $merchant_id='')
	{
		$from=''; $to = '';
		switch ($trans_type) {
			case "delivery":
				$from = getOptionA('admin_tracking_estimation_delivery1');
				$to = getOptionA('admin_tracking_estimation_delivery2');
										
				$from1 = getOption($merchant_id,'tracking_estimation_delivery1');
				$to1 = getOption($merchant_id,'tracking_estimation_delivery2');	
				if($from1>0 && $to1>0){
					$from = $from1;
					$to = $to1;
				}				
				break;
		
			case "pickup":	
			    $from = getOptionA('admin_tracking_estimation_pickup1');
				$to = getOptionA('admin_tracking_estimation_pickup2');
										
				$from1 = getOption($merchant_id,'tracking_estimation_pickup1');
				$to1 = getOption($merchant_id,'tracking_estimation_pickup2');	
				if($from1>0 && $to1>0){
					$from = $from1;
					$to = $to1;
				}								
				break;
			
			case "dinein":		
			    $from = getOptionA('admin_tracking_estimation_dinein1');
				$to = getOptionA('admin_tracking_estimation_dinein2');
										
				$from1 = getOption($merchant_id,'tracking_estimation_dinein1');
				$to1 = getOption($merchant_id,'tracking_estimation_dinein2');	
				if($from1>0 && $to1>0){
					$from = $from1;
					$to = $to1;
				}						
				break;
		}
		
		$estimation_time = "$from-$to";
		
		return $estimation_time;
	}
	
	public static function hasMerchantAPP()
	{
		if (FunctionsV3::hasModuleAddon('merchantappv2')){
			if(Yii::app()->db->schema->getTable("{{merchantapp_device_reg}}")){
				return true;
			}
		}
		return false;
	}
	
	public static function getOrderTabStatus($tab='incoming')
	{
		$status = array();
		switch ($tab) {
			case "incoming":
				$stats = getOptionA('order_incoming_status');
				if ( !$status = json_decode($stats,true)){
					$status = array('pending','paid');
				}
				break;
				
			case "outgoing":
				$stats = getOptionA('order_outgoing_status');
				if ( !$status = json_decode($stats,true)){
						$status = array('accepted','delayed','acknowledged');
				}							
				break;	
				
			case "ready":
				$stats = getOptionA('order_ready_status');
				if ( !$status = json_decode($stats,true)){
						$status = array('food is ready');
				}
				break;		
				
			case "delivery":	
			    return array('acknowledged','started','inprogress');
			    break;
			    
			case "sucessful":	
			    $completed_status = getOptionA('order_action_completed_status');
			    return array($completed_status,'successful');
			    break;    
			    
			case "failed":	
			    $completed_status = getOptionA('order_action_cancel_status');
			    $decline_status = getOptionA('order_action_decline_status');			    
			    $temp_status = array('failed','cancelled','declined');
			    
			    if(!empty($completed_status)){
			    	array_push($temp_status,$completed_status);
			    }
			    if(!empty($decline_status)){
			    	array_push($temp_status,$decline_status);
			    }
			    return $temp_status;
			    break;        
		
			case "delayed":
				 $order_action_delayed_status = getOptionA('order_action_delayed_status');
				 $order_action_food_done_status = getOptionA('order_action_food_done_status');
				 
				 $temp_status = array('delayed_status');
				 if(!empty($order_action_delayed_status)){
			    	array_push($temp_status,$order_action_delayed_status);
			     }
			     if(!empty($order_action_food_done_status)){
			    	array_push($temp_status,$order_action_food_done_status);
			     }
			     $status = $temp_status; 
				 break;        
				 
			default:
				break;
		}
		return $status;
	}
	
	public static function getOrderLastHistory($order_id='', $status='')
	{
		$stmt = "
		SELECT order_id,status,remarks,remarks2,remarks_args,notes
		FROM {{order_history}}
		WHERE order_id=".q($order_id)."
		AND 
		status=".q($status)."
		ORDER BY id DESC
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
	public static function canReview($order_id='',$status_raw='',$date_created_raw='',$rating=0)
	{
		$website_review_type = getOptionA('website_review_type');
		$review_baseon_status = getOptionA('review_baseon_status');	
		$merchant_can_edit_reviews = getOptionA('merchant_can_edit_reviews');
		if($website_review_type==1){
			$review_baseon_status = getOptionA('review_merchant_can_add_review_status');
		}	
		
		$add_review = false;		
		if(mobileWrapper::canReviewOrder($status_raw,$website_review_type,$review_baseon_status)){
		   $add_review=true;
		}			
		
		$date_now = date('Y-m-d g:i:s a');
		
		if($add_review){					
			$date_diff=Yii::app()->functions->dateDifference(
			date('Y-m-d g:i:s a',strtotime($date_created_raw))
			,$date_now);
			if(is_array($date_diff) && count($date_diff)>=1){
				if ($date_diff['days']>=5){
				   $add_review=false;
				}
			}			
		}
		
		if($website_review_type==1){
			if($rating>0){
				if($merchant_can_edit_reviews=="yes"){
					$add_review=false;
				}
			}				
		}	
		
		return $add_review;

	}
	
	public static function processTime($estimated_time='',$estimated_date_time='')
	{
		$estimation_ready=''; $estimation_notes2=''; $time_unit  = mt("minutes");
		$estimation_time = '';
		
		if($estimated_time>0 && !empty($estimated_date_time)){			
			$date_now = date('Y-m-d g:i:s a');
			$estimated_date_time = date("Y-m-d g:i:s a",strtotime($estimated_date_time));
			$time_diff=Yii::app()->functions->dateDifference($date_now,$estimated_date_time);						
			if(is_array($time_diff)){				
				if($time_diff['hours']>0){							
					$estimation_time = strlen($time_diff['hours'])>=2?$time_diff['hours']:str_pad($time_diff['hours'],2,"0", STR_PAD_LEFT);
					
					$time_unit  = mt("hours");
				}					
				if($time_diff['minutes']>0){													
					$estimation_time.= !empty($estimation_time)?":":'';							
					$estimation_time.= strlen($time_diff['minutes'])>=2?$time_diff['minutes']:str_pad($time_diff['minutes'],2,"0", STR_PAD_LEFT);
				}				
				$estimation_ready = mt("Until food is ready");		
				$estimation_notes2 = mt("Estimated time until food ready in [time] [time_unit]",array(
				  '[time]'=>$estimation_time,
				  '[time_unit]'=>$time_unit
				));	
							
				return array(
				  'time_unit'=>$time_unit,
				  'estimation_time'=>$estimation_time,
				  'estimation_ready'=>$estimation_ready,
				  'estimation_notes2'=>$estimation_notes2,
				);
			} else {
			   $date_now = date('Y-m-d g:i:s a');
			   $estimated_date_time = date("Y-m-d g:i:s a",strtotime($estimated_date_time));
			   $time_diff=Yii::app()->functions->dateDifference($estimated_date_time,$date_now);
			   if($time_diff['hours']>0){							
					$estimation_time = strlen($time_diff['hours'])>=2?$time_diff['hours']:str_pad($time_diff['hours'],2,"0", STR_PAD_LEFT);
					
					$time_unit  = mt("hours");
				}					
				if($time_diff['minutes']>0){													
					$estimation_time.= !empty($estimation_time)?":":'';							
					$estimation_time.= strlen($time_diff['minutes'])>=2?$time_diff['minutes']:str_pad($time_diff['minutes'],2,"0", STR_PAD_LEFT);
				}				
				$estimation_ready = mt("Until food is ready");		
				$estimation_notes2 = mt("Estimated time until food ready in [time] [time_unit]",array(
				  '[time]'=>$estimation_time,
				  '[time_unit]'=>$time_unit
				));	
							
				return array(
				  'time_unit'=>$time_unit,
				  'estimation_time'=>"-".$estimation_time,
				  'estimation_ready'=>$estimation_ready,
				  'estimation_notes2'=>$estimation_notes2,
				);
			}			
		} else {
			//
		}		
		
		return false;
	}
	
	public static function getOrderLastHistoryNotes($order_id='',$status_raw='')
	{
		$estimation_notes2 ='';		
		if($resp_history=GraphicalTracking::getOrderLastHistory($order_id,$status_raw)){
			if(!empty($resp_history['notes'])){
			    return $resp_history['notes'];
			}
		}		
		return false;
	}
	
}
/*end class*/
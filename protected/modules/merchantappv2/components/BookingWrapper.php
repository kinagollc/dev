<?php 
class BookingWrapper
{
	public static function getAllBooking($order_type='',$merchant_id=0,$start=0, $total_rows=10,$search_string='')
	{
		$and='';  $order_by = 'ORDER BY booking_id ASC';
		$todays_date = date("Y-m-d");
		$next_date = date('Y-m-d', strtotime("+1 day"));
		
		if(!empty($search_string)){							
			$and.="
			AND (
			  a.booking_id = ".q($search_string)."
			  OR
			  a.booking_name LIKE ".q("$search_string%")." 
			)
			";
		}		
				
		if(!empty($order_type)){
			switch ($order_type) {				
				case "incoming":
					$and.=" AND a.status IN ('pending')  AND a.request_cancel='0' ";
					$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
					break;
					
				case "cancel_booking":
					$and.=" AND a.status IN ('request_cancel_booking')";
					break;
							
				case "all":
					$order_by = 'ORDER BY booking_id DESC';
					break;
					  
				case "done_booking":
					$and.=" AND a.status IN ('approved','denied','cancel_booking_approved')";
					$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)." ";
					break;
			}			
		}				
		
		$stmt="
		select SQL_CALC_FOUND_ROWS 
		a.booking_id, a.merchant_id, a.client_id, a.number_guest, a.date_booking,
		a.date_booking as date_booking_raw, a.booking_time as booking_time_raw,
		a.booking_time, a.booking_name, a.email, a.mobile, a.booking_notes,
		a.date_created,a.date_created as date_created_raw, a.status, a.status as status_raw
		
		FROM {{bookingtable}} a
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

	
	public static function updateBooking($booking_id='', $params=array(),$notes='')
	{
		$stmt="
			SELECT *
			FROM {{bookingtable}} 
			WHERE booking_id = ".q( (integer)$booking_id )."					
			LIMIT 0,1
		";			
		if($resp = Yii::app()->db->createCommand($stmt)->queryRow()){       				    	
	        $up =Yii::app()->db->createCommand()->update("{{bookingtable}}",$params,
	  	    'booking_id=:booking_id',
		  	    array(
		  	      ':booking_id'=>$booking_id
		  	    )
	  	    );
	  	    if($up){
	  	    	$params_history=array(
                  'booking_id'=>$booking_id,
                  'status'=>$params['status'],
                  'remarks'=>$notes,
                  'date_created'=>FunctionsV3::dateNow()
                );
                Yii::app()->db->createCommand()->insert("{{bookingtable_history}}",$params_history);
                
                
                $resp['status']=$params['status'];  
                $resp['remarks'] = $notes;                
                FunctionsV3::updateBookingNotify($resp);
                
                /*POINTS PROGRAM*/   		
	    		if (FunctionsV3::hasModuleAddon("pointsprogram")){		    			
	    		   PointsProgram::updateBookTable($booking_id,$params['status']);
	    		}
	    		
	    		return $resp;
                
	  	    } else throw new Exception( "Failed cannot update record" );   	
	    }
	    throw new Exception( "Booking id not found" );   	
	}
	
	public static function getBookingDetails($booking_id='')
	{
		$resp = Yii::app()->db->createCommand()
          ->select('*,number_guest as number_guest_raw,date_created as date_created_raw,status as status_raw')
          ->from('{{bookingtable}}')   
          ->where("booking_id=:booking_id",array(
             ':booking_id'=>(integer)$booking_id
          )) 
          ->limit(1)
          ->queryRow();	
        if($resp){
        	return $resp;
        }
        throw new Exception( "Booking id not found" );  
	}
	
	public static function getHistory($booking_id='')
	{
		$resp = Yii::app()->db->createCommand()
          ->select('*')
          ->from('{{bookingtable_history}}')   
          ->where("booking_id=:booking_id",array(
             ':booking_id'=>(integer)$booking_id
          )) 
          ->order("id ASC")          
          ->queryAll();	
        if($resp){
        	return $resp;
        }
        return false;
	}
	
    public static function getNewestBooking($booking_ids='')
	{
		if(!empty($booking_ids)){
			$stmt="
			SELECT booking_id 
			FROM {{bookingtable}}	
			WHERE booking_id NOT IN ($booking_ids)
			AND status IN ('pending')
			LIMIT 0,1
			";			
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		} else {
			$todays_date = date("Y-m-d");
			$stmt="
			SELECT booking_id 
			FROM {{bookingtable}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND status IN ('pending')
			LIMIT 0,1
			";
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
				return true;
			}
		}
		return false;
	}	
	
	public static function getNewestCancel($booking_ids='')
	{
		if(!empty($booking_ids)){
			$stmt="
			SELECT booking_id 
			FROM {{bookingtable}}	
			WHERE booking_id NOT IN ($booking_ids)
			AND status IN ('request_cancel_booking')
			LIMIT 0,1
			";						
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
				return true;
			}
		} else {
			$todays_date = date("Y-m-d");
			$stmt="
			SELECT booking_id 
			FROM {{bookingtable}}				
			WHERE CAST(date_created as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."			
			AND status IN ('request_cancel_booking')
			LIMIT 0,1
			";
			if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
				return true;
			}
		}
		return false;
	}	
	
}
/*end class*/
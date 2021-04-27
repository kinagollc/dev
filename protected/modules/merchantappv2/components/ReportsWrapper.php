<?php
class ReportsWrapper
{
	public static function salesReport($merchant_id=0,$start=0, $total_rows=10,
	$search_string='', $start_date='', $end_date='', $order_status='')
	{
		$and='';
		if(!empty($search_string)){				
			$and=" AND a.size_name LIKE ".q("$search_string%")." ";
		}
		
		if(!empty($start_date) && !empty($end_date)){
			$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($start_date)." AND ".q($end_date)." ";
		}
		
		if(is_array($order_status) && count($order_status)>=1){
			$in ='';
			foreach ($order_status as $order_status_val) {
				$in.=q($order_status_val).",";
			}
			$in = substr($in,0,-1);
			$and.=" AND a.status IN ($in) ";
		}
				
		$stmt="
		select SQL_CALC_FOUND_ROWS
		a.order_id, a.customer_name, a.contact_phone, a.profile_customer_name,
		a.trans_type, a.trans_type as trans_type_raw, 
		a.payment_type, a.payment_type as payment_type_raw ,  a.total_amount ,
		a.status, a.status as status_raw, a.date_created,
		
		(
    	select group_concat(item_name)
    	from
    	{{order_details}}
    	where
    	order_id=a.order_id
    	) as item
		
		
		from {{view_order}} a
		WHERE
		merchant_id=".q($merchant_id)."
		AND a.status NOT IN ('".initialStatus()."')
		$and
		ORDER BY a.order_id DESC
		LIMIT $start,$total_rows
		";				
		
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){        	
        	return $resp;
        }
        return false;     
	}	
	
	public static function salesSummaryReport($merchant_id=0,$start=0, $total_rows=10,
	$search_string='', $start_date='', $end_date='', $order_status='')
	{
		$and='';
		if(!empty($search_string)){				
			$and=" AND a.item_name LIKE ".q("$search_string%")." ";
		}
		
		if(!empty($start_date) && !empty($end_date)){
			$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($start_date)." AND ".q($end_date)." ";
		}
		
		if(is_array($order_status) && count($order_status)>=1){
			$in ='';
			foreach ($order_status as $order_status_val) {
				$in.=q($order_status_val).",";
			}
			$in = substr($in,0,-1);
			$and.=" AND a.status IN ($in) ";
		}
				
		$stmt="
		SELECT SQL_CALC_FOUND_ROWS SUM(a.qty) as total_qty,
		a.item_id,a.item_name,a.discounted_price as price,
		a.discounted_price as price_raw,
		a.size, a.size as size_raw
		FROM
		{{view_order_details}} a	 
		WHERE
		merchant_id= ".FunctionsV3::q($merchant_id)."
		AND status NOT IN ('".initialStatus()."')
	    $and
		GROUP BY item_id,size	  
		ORDER BY a.item_name ASC
		LIMIT $start,$total_rows 
		";				
		//dump($stmt);
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){        	
        	return $resp;
        }
        return false;     
	}	
	
	public static function bookingSummary($merchant_id=0,$start=0, $total_rows=10,
	$search_string='', $start_date='', $end_date='', $order_status='')
	{
		$and='';
		if(!empty($search_string)){				
			$and=" AND a.item_name LIKE ".q("$search_string%")." ";
		}
		
		if(!empty($start_date) && !empty($end_date)){
			$and.=" AND CAST(a.date_created as DATE) BETWEEN ".q($start_date)." AND ".q($end_date)." ";
		}
		
		if(is_array($order_status) && count($order_status)>=1){
			$in ='';
			foreach ($order_status as $order_status_val) {
				$in.=q($order_status_val).",";
			}
			$in = substr($in,0,-1);
			$and.=" AND a.status IN ($in) ";
		}
				
		$stmt="
		SELECT SQL_CALC_FOUND_ROWS sum(a.number_guest) as total_approved,
		(
		select sum(number_guest)
		from {{bookingtable}}
		where
		merchant_id=".q($merchant_id)."
		and
		status='denied'
		$and
		) as total_denied,
		
		(
		select sum(number_guest)
		from {{bookingtable}}
		where
		merchant_id=".q($merchant_id)."
		and
		status='pending'
		$and
		) as total_pending
		
		FROM
		{{bookingtable}} a
		WHERE
		merchant_id=".q($merchant_id)."
		AND status='approved'
		$and			
		LIMIT $start,$total_rows 
		";		
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){        	
        	return $resp;
        }
        return false;     
	}	
		
}
/*end class*/
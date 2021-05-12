<?php
class DiscountPromo
{
	public static function validateVoucher($merchant_id='',$voucher_name='',$client_id='',$date='',$days='')
	{		
		$voucher_name = trim($voucher_name);
		$stmt="
		SELECT a.voucher_id,a.voucher_owner,a.merchant_id,a.joining_merchant,a.voucher_name,
		a.voucher_type,a.amount,a.min_order,
		a.used_once,a.max_number_use,a.selected_customer,
		
		(
		  select count(*) from {{order}}
		  where voucher_code= ".q($voucher_name)."
		  and
		  client_id=".q($client_id)."
		) as customer_use_count,
		
		(
		  select count(*) from {{order}}
		  where voucher_code= ".q($voucher_name)."		  
		) as all_use_count,
		
	    (
	      select count(*) from {{order}}
	      where client_id=".q($client_id)."
	      and status not in ('initial_order','cancel','cancelled')
	    ) as first_order_count
		
		FROM {{voucher_new}} a
		WHERE voucher_name = ".q($voucher_name)."
		AND expiration >= ".q($date)."
		AND ".$days."=1			    
		";				
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){				
			$voucher_options = (integer)$res['used_once'];
			$max_number_use = (integer)$res['max_number_use'];
			
			if($res['voucher_owner']=="admin"){
				$joining_merchant = !empty($res['joining_merchant'])?json_decode($res['joining_merchant'],true):'';
				if(is_array($joining_merchant) && count($joining_merchant)>=1){
					if(!in_array($merchant_id,(array)$joining_merchant)){						
						throw new Exception( "Voucher code not applicable to this merchant" );						
					}						
				}					
			} else if ($res['voucher_owner']=="merchant"){										
				if ($res['merchant_id']!=$merchant_id){
					throw new Exception( "Voucher code not applicable to this merchant" );
				}					
			} else {
				throw new Exception( "Voucher code not found" );
			}				
			
			switch ($voucher_options) {
				case 2:
					if($res['all_use_count']>0){
						throw new Exception( "This voucher code has already been used" );
					}
					break;
					
				case 3:
					if($res['customer_use_count']>0){
						throw new Exception( "Sorry but you have already use this voucher code" );
					}
					break;	
					
			    case 4:
			    	if($res['first_order_count']>0){
						throw new Exception( "This voucher can be use only in your first order" );
					}
			    	break;
			    	
			    case 5:				       
			        if($res['customer_use_count']>=$max_number_use){
			        	
			        	$error_msg='';
			        	if($res['customer_use_count']<=1){
			        		$error_msg = "You already used this voucher [count] time and cannot be use again";
			        	} else $error_msg = "You already used this voucher [count] times and cannot be use again";
			        	
						throw new Exception( 
						   Yii::t("default",$error_msg,array( 
						    '[count]'=>$max_number_use
						   ))
						);
					}
			    	break;
			    	
			    case 6:	
			      if($res['customer_use_count']>0){
						throw new Exception( "Sorry but you have already use this voucher code" );
				  }
				  
			      $selected_customer = !empty($res['selected_customer'])?json_decode($res['selected_customer'],true):false;
			      if(is_array($selected_customer) && count($selected_customer)>=1){			      	
			      	if(!in_array($client_id,(array)$selected_customer)){
			      		throw new Exception( "This voucher cannot be use in your account" );
			      	}
			      } else throw new Exception( "Voucher code not found" );
			      
			      break;
			    	
				default:
					break;
			}
			
			return $res;
		}
		
		throw new Exception( "Voucher code not found" );
	}
}
/*end class*/
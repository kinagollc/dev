<?php
class ReviewWrapper
{
	public static function addReview($order_id='', $rating=0, $review='', $as_anonymous=0)
	{
		$order_id = (integer)$order_id;
		if($order_id<=0){
			throw new Exception( 'invalid order id');
		}
		
		$website_review_type = getOptionA('website_review_type');

		if($order_info = Yii::app()->functions->getOrderInfo($order_id)){
			$client_id = $order_info['client_id'];
			$order_id = $order_info['order_id'];
			
			$params = array(
			  'merchant_id'=>$order_info['merchant_id'],
			  'client_id'=>$client_id,
			  'review'=>$review,
			  'rating'=>$rating,
			  'as_anonymous'=>$as_anonymous,
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			  'order_id'=>$order_id,  
			);
				
			if ($website_review_type==2){
				if(method_exists('FunctionsV3','getReviewBasedOnStatus')){
				   $params['status']=FunctionsV3::getReviewBasedOnStatus($order_info['status']);
			    }
			}
		    		    
		    if ($website_review_type!=2){
		    	$actual_purchase = getOptionA('website_reviews_actual_purchase');
				if($actual_purchase=="yes"){
					$functionk=new FunctionsK();
					if (!$functionk->checkIfUserCanRateMerchant($client_id,$order_info['merchant_id'])){					
						 throw new Exception( 'Reviews are only accepted from actual purchases!');
					}
					if (!$functionk->canReviewBasedOnOrder($client_id,$order_info['merchant_id'])){		    		   
		    		    throw new Exception( 'Sorry but you can make one review per order');		    	       
		    	    }	  		   
				}
		    }
			
		    if(!$res_review = FunctionsV3::getReviewByOrder($client_id,$order_id)){
		    	if(Yii::app()->db->createCommand()->insert("{{review}}",$params)){
		    		$review_id=Yii::app()->db->getLastInsertID();
		    		
		    		if (FunctionsV3::hasModuleAddon("pointsprogram")){
						if (method_exists('PointsProgram','addReviewsPerOrder')){
							PointsProgram::addReviewsPerOrder($order_id,
							$client_id,$review_id,$order_info['merchant_id'],$order_info['status']);
						}			
					}	
					
		    	} else throw new Exception( 'ERROR. cannot insert data.');
		    } else {
		    	$id = $res_review['id'];
		    	unset($params['date_created']);
		    	$params['date_modified'] = FunctionsV3::dateNow();			    	
		    	Yii::app()->db->createCommand()->update("{{review}}",$params,
		  	    'id=:id',
			  	    array(
			  	      ':id'=>(integer)$id
			  	    )
		  	    );
		    	
		    }
		    
		    return true;
			    
		} else throw new Exception( 'order details not found');
	}
	
}
/*end class*/
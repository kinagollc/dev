<?php
class Cart_utilities
{
	public static function getServiceFee($merchant_id=0)
	{		
		$merchant_id = (integer)$merchant_id;
		$fee = 0; $apply_tax = false;
		if(FunctionsV3::isMerchantCommission($merchant_id)){
			$fee = getOptionA('admin_service_fee');
			$apply_tax = getOptionA('admin_service_fee_applytax');
		} else {
			$fee = getOption($merchant_id,'merchant_service_fee');
			$apply_tax = getOption($merchant_id,'merchant_service_fee_applytax');
		}
		
		if($fee>0){
			return array(
			  'service_fee'=>$fee,
			  'service_fee_applytax'=>$apply_tax==1?false:true
			);
		} else return false;
	}
	
}
/*end class*/
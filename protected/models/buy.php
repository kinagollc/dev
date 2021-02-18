<?php
$amount_to_pay=0; $error=''; $credentials='';
$payment_description='';
$merchant_name=''; $error = '';
$reference_id=''; $client_info = array();
$trans_type='order';
$currency_code = FunctionsV3::getCurrencyCode();
$back_url = Yii::app()->createUrl('/store/confirmorder');
$orig_amount_to_pay = 0;
$currency_used = '';
$order_card_fee = 0;
$exchange_rate_convertion = 0;
$client_email='';

$exchange_rate = Item_utility::getRates();	

if ( $data=Yii::app()->functions->getOrder($_GET['id'])){	
	$merchant_id=isset($data['merchant_id'])?$data['merchant_id']:'';	
    $client_id = $data['client_id'];       
    $order_id = $data['order_id']; 	
    $reference_id = $data['order_id_token'];
    $order_card_fee = isset($data['card_fee'])?(float)$data['card_fee']:0;
    
    $merchant_name =isset($data['merchant_name'])?clearString($data['merchant_name']):'';
	$payment_description = Yii::t("default","Payment to merchant [merchant_name]. Order ID#[order]",array(
	  '[merchant_name]'=>$merchant_name,
	  '[order]'=>$_GET['id']
	));
	
	$description = Yii::t("default","Purchase Order ID# [order_id]",array(
	  '[order_id]'=>$_GET['id']
	));
		
	$amount_to_pay = Yii::app()->functions->normalPrettyPrice($data['total_w_tax']);
	$orig_amount_to_pay = Price_Formatter::convertToRaw($amount_to_pay);
	$client_email='';
	$client_info=Yii::app()->functions->getClientInfo($client_id);
		
	/*EXCHANGE RATE CODE*/
	if (Item_utility::MultiCurrencyEnabled()){	
		$currency_code = Yii::app()->session['currency'];			
		if($currency_used = Multicurrency_utility::getUseCurrencyByOrder( $order_id )){		
		if ( $payment_method_resp = Multicurrency_utility::paymentMethod($currency_used,$data['payment_type']) ){			
			Price_Formatter::init( $payment_method_resp['to_currency']  );
			$exchange_rate_convertion = (float)$payment_method_resp['exchange_rate'];			
			$exchange_rate = $exchange_rate_convertion;	
			$amount_to_pay = (float)$amount_to_pay * (float)$exchange_rate_convertion;
			$order_card_fee = (float)$order_card_fee * (float)$exchange_rate_convertion;
			
			$amount_to_pay = Price_Formatter::convertToRaw($amount_to_pay);			
			$currency_code = isset($payment_method_resp['to_currency'])?$payment_method_resp['to_currency']:$currency_code;			
		}
		}
	}
	
} else $error = t("Sorry but we cannot find what your are looking for.");

/*FALL BACK*/
if(empty($currency_code)){	
	$currency_code = FunctionsV3::getCurrencyCode();
}

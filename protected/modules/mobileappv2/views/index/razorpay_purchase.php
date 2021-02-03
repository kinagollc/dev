<?php
use Razorpay\Api\Api;

try {
	
	$client = new Api($credentials['key_id'], $credentials['key_secret']);
	
	$params = [
	  'receipt'         => $reference_id,
	  'amount'          => (float)$amount*100, 
	  'currency'        => $currency_code,
	  'payment_capture' =>  1,	  
	];
	$order  = $client->order->create($params);
	
	
	$return_url = websiteUrl()."/".APP_FOLDER."/razorpay/cancel";
	$cancel_url = $return_url;
	$success_url = websiteUrl()."/".APP_FOLDER."/razorpay/verify?reference_id=$reference_id";	
	
	echo CHtml::beginForm('https://api.razorpay.com/v1/checkout/embedded','post',array(	
	      'id'=>"razorpay_form"
		)); 
		
	echo CHtml::hiddenField('key_id',$credentials['key_id']);	
	echo CHtml::hiddenField('order_id',$order['id']);		
	echo CHtml::hiddenField('name',$merchant_name);		
	echo CHtml::hiddenField('description',$payment_description);	
	echo CHtml::hiddenField('prefill[name]',$full_name);	
	echo CHtml::hiddenField('prefill[contact]',$contact_phone);		
	echo CHtml::hiddenField('prefill[email]',$email_address);		
	echo CHtml::hiddenField('callback_url',$success_url);
	echo CHtml::hiddenField('cancel_url',$cancel_url);
	
	echo CHtml::submitButton('Submit',array(
	  'style'=>"display:none;"
	));
	
	
	$script = '
	jQuery(document).ready(function() {	   
	   loader(1);
	   $("#razorpay_form").submit();
	});
	';
	
	$cs = Yii::app()->getClientScript(); 
	$cs->registerScript(
	  'reg_script',
	  "$script",
	  CClientScript::POS_END
	);		
			
    echo CHtml::endForm() ; 		
		
} catch (Exception $e) {
    $error = $e->getMessage();
    $this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/razorpay/error/?error='.$error )); 
}
?>
<div class="content_wrap"></div>
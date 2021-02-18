<?php
$this->renderPartial('/front/banner-receipt',array(
   'h1'=>t("Payment"),
   'sub_text'=>t("")
));

$this->renderPartial('/front/order-progress-bar',array(
   'step'=>4,
   'show_bar'=>true
));

require_once "buy.php";

$client_token=''; $label=''; 
$payment_code = "btr";

if (empty($error)){
	
	$merchant_type=1;
	if (FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
		$merchant_type=2;
	}
		
	$label = Yii::t("default","Pay [amount]",array(
	  '[amount]'=>Price_Formatter::formatNumber($amount_to_pay)
	));
	
	if(is_array($_POST) && count($_POST)>=1){
		 $transaction_id=BraintreeClass::PaymentMethod(
	      $merchant_type,
	      $merchant_id,
	      $amount_to_pay,
	      $_POST['payment_method_nonce'],
	      $_SESSION['kr_client']['first_name'],
	      $_SESSION['kr_client']['last_name']
	   );
	   if($transaction_id){
	   	  $redirec_link=Yii::app()->createUrl('/store/receipt',array('id'=>$order_id));
	   	  
	   	  if($order_info['status']=="paid"){
			header('Location: '.$redirec_link."&note=already paid");   
			Yii::app()->end();
		  }
		  
		  FunctionsV3::updateOrderPayment($order_id,$payment_code,
          $transaction_id,$get,$reference_id); 
		
          FunctionsV3::callAddons($order_id);
	      header('Location: '.$redirec_link);   
          Yii::app()->end();	
	   	  
	   } else $error=t("Error processing transaction");
	} else {
		if(!$client_token=BraintreeClass::generateCLientToken($merchant_type,$client_id,$merchant_id)){
			$error=t("Failed generating client token");
		}
	}
}
?>

<div class="sections section-grey2 section-orangeform">
<div class="container">  
  <div class="row top30">
     <div class="inner">
     <h1><?php echo t("Pay using Braintree")?></h1>
     <div class="box-grey rounded">	
     
     <?php if(!empty($error)):?>
       <p class="text-danger"><?php echo $error?></p>
     <?php else :?>
        <?php if(is_array($_POST) && count($_POST)>=1):?>
           <?php echo t("Payment successful please wait while we redirect you to receipt")?>
        <?php else :?>  
           <?php BraintreeClass::displayForms($client_token, $label)?>
        <?php endif;?>
     <?php endif;?>     
     
      <div class="top25">
       <a href="<?php echo Yii::app()->createUrl('/store/paymentoption')?>">
       <i class="ion-ios-arrow-thin-left"></i> <?php echo Yii::t("default","Click here to change payment option")?></a>
      </div>
     
     </div> <!--box-->
     </div> <!--inner-->     
  </div> <!--row-->
</div> <!--container-->
</div> <!--sections-->
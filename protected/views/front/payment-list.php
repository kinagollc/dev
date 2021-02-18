<?php FunctionsV3::sectionHeader('Payment Information')?>

<?php 
/*CHECK IF CHANGE IS REQUIRED*/
$cod_change_required='';
if (FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
	$cod_change_required=getOptionA('cod_change_required');
} else {
	$cod_change_required=getOption($merchant_id,'cod_change_required_merchant');
}
echo CHtml::hiddenField('cod_change_required',$cod_change_required);

$opt_contact_delivery = isset($opt_contact_delivery)?$opt_contact_delivery:0;
if($opt_contact_delivery==1){
	$offline_payment = FunctionsV3::getOfflinePaymentList();
	foreach ($offline_payment as $offline_key=>$offline_val) {
		if(array_key_exists($offline_key,(array)$payment_list)){
			unset($payment_list[$offline_key]);
		}
	}	
}

/*EXCHANGE RATE*/
if ( Item_utility::MultiCurrencyEnabled()){
	$payment_list = Multicurrency_utility::paymentCheckout( $payment_list , Yii::app()->session['currency']);
}
?>

<?php if (is_array($payment_list) && count($payment_list)>=1):?>
<?php foreach ($payment_list as $key => $val):?>

  
  <div class="row top10">
    <div class="col-md-9">
       <?php echo CHtml::radioButton('payment_opt',false,
         array('class'=>"icheck payment_option",'value'=>$key))?> 
         <?php          
         switch ($key) {
         	case "pyr":
         		if ($transaction_type=="pickup"){
         		   echo t("Pay On Pickup");
         	    } else echo $val;         
         		break;
         
         	case "cod":
         		if ($transaction_type=="pickup"){
         		   echo t("Cash On Pickup");
         		} elseif ( $transaction_type=="dinein" ) {
         		   echo t("Pay in person");
         	    } else echo $val;         
         		break;
         		
         	case "pyp":         		
         	    $fee=0;
	         	if (FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){	         		
	         		$fee=getOptionA('admin_paypal_fee');
	         	} else {	         		
	         		$fee=getOption($merchant_id,'merchant_paypal_fee');
	         	}	         	
	         	if($fee>0){
	         		echo $val= Yii::t("default","Paypal (card fee [fee])",array(
	         		  '[fee]'=>Price_Formatter::formatNumber($fee)
	         		));
	         	} else echo $val;
         	    break;
         	    
         	case "paymill":     
         	    if ( $credentials=KPaymill::getCredentials($merchant_id)){    
         	    	if ($credentials['card_fee1']>=0.001){
         	    		$fee1=$credentials['card_fee1'];
         	    		$fee2=$credentials['card_fee2'];
         	    	    echo t("Paymill")." (".t("card fee $fee1% + $fee2").adminCurrencySymbol().")";
         	    	} else echo t("Paymill");   
         	    } else echo t("Paymill");     	    
         	    break;
         	    
         	case "strip_ideal":    
         	    if ( $credentials=StripeIdeal::getCredentials($merchant_id)){
         	       if($credentials['ideal_fee']>=0.0001){
         	    		echo $val = Yii::t("default","Stripe Ideal (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['ideal_fee'])
		         		));
         	       } else echo t("Stripe Ideal");   
         	    } else echo t("Stripe Ideal");   
         	    break;
         	    
         	case "mol":            	   
         	   if ($credentials=MollieClass::getCredentials($merchant_id)){           	   	   
         	   	   if($credentials['card_fee']>=0.0001){
         	   	   	  echo $val = Yii::t("default","Mollie (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
         	   	   } else echo t($val); 
         	   } else echo t($val); 
         	   break;
         	   
         	case "wirecard":  
         	   if($credentials=WireCard::getCredentials($merchant_id)){         	   	  
         	   	  if ( $credentials['fee1']>=0.0001  && $credentials['fee2']>=0.0001){
         	   	  	   $fee1=$credentials['fee1'];
         	    	   $fee2=$credentials['fee2'];
         	    	   echo t("WireCard")." (".t("card fee $fee1% + $fee2").adminCurrencySymbol().")";
         	   	  } else echo t("WireCard");
         	   } else echo t("WireCard");
         	   break;
         	   
         	case "moyasar":   
         	  if($credentials=MoyasarWrapper::getCredentials($merchant_id)){         	   	  
         	   	  if($credentials['card_fee']>=0.0001){
         	   	   	  echo $val = Yii::t("default","moyasar (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
         	   	   } else echo t($val); 
         	   } else echo t("moyasar");         	   
         	break;
         	
         	case "payfast":
         		if($credentials=PayfastWrapper::getCredentials($merchant_id)){
         			if($credentials['card_fee']>=0.0001){
         				echo $val = Yii::t("default","payfast (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
         			} else echo t($val); 
         		} else echo t($val); 
         		break;
         			
         	case "squareup":
         		if($credentials=squareupWrapper::getCredentials($merchant_id)){
         			if($credentials['card_fee']>=0.0001){
         				echo $val = Yii::t("default","squareup (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
         			} else echo t($val); 
         		} else echo t($val); 
         		break;
         		
     		case "zarinpal":
     		if($credentials=zarinpalWrapper::getCredentials($merchant_id)){
     			if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","zarinpal (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
     			} else echo t($val); 
     		} else echo t($val); 
     		break;	
     			
     		case "axis":
     		if($credentials=AxisWrapper::getCredentials($merchant_id)){
     			if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","axis bank (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
     			} else echo t($val); 
     		} else echo t($val); 
     		break;	
     		
     		case "cygnuspay":
     			if($credentials=CygnuspayWrapper::getCredentials($merchant_id)){
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","cygnus payment (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;
         			
     		case "viva":
     			if($credentials=VivaWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","viva payment (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;
     			     		
     		case "stp":	
     		    if($credentials=StripeWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","Stripe (card fee [fee])",array(
		         		  '[fee]'=>Price_Formatter::formatNumber( (float) $credentials['card_fee'] * $exchange_rate )
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     		    break;	
     		    
     		case "paypal_v2":	
     		    if($credentials=PaypalWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","Paypal V2 (card fee [fee])",array(
		         		  '[fee]'=>Price_Formatter::formatNumber( (float) $credentials['card_fee'] * $exchange_rate )
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     		    break;	    
     		    
     		case "tranzila":
     			if($credentials=tranzilaWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","tranzila (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;	
     			
     	    case "mollie":
     			if($credentials=MollieWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","mollie (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;	
     			
     		 case "mercadopago":
     			if($credentials=mercadopagoWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","mercadopago V2 (card fee [fee])",array(
		         		  '[fee]'=>Price_Formatter::formatNumber( (float) $credentials['card_fee'] * $exchange_rate )
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;		
     			
     		case "tap":
     			if($credentials=TapWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","Tap (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;			
     			
     		case "paymongo_card":          		
     			if($credentials=PaymongoWrapper::getCredentials($merchant_id)){     				
     				if($credentials['card_fee']>=0.0001){
     					$cardfee='';
     					if(isset($credentials['card_percentage'])){
     					   $cardfee = FunctionsV3::prettyPriceNoCurrency($credentials['card_percentage'])."%";
     					} 
     					
     					$label = "paymongo card";
     					if($key=="paymongo_gcash"){
     						$label = "gcash";
     					} else if ($key=="paymongo_grabpay") {
     						$label = "grabpay";
     					}
     					
     					$cardfee.= "+".FunctionsV3::prettyPriceNoCurrency($credentials['card_fee']);
	     				echo $val = Yii::t("default","$label (card fee [fee])",array(
		         		  '[fee]'=>$cardfee
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;	
     			
     			
     		case "paymongo_gcash": 		
     		case "paymongo_grabpay":	
     			if($credentials=PaymongoWrapper::getCredentials($merchant_id)){     				     				
     				if($credentials['card_fee_gcash']>=0.0001){
     					$cardfee='';
     					if(isset($credentials['card_percentage_gcash'])){
     					   $cardfee = FunctionsV3::prettyPriceNoCurrency($credentials['card_percentage_gcash'])."%";
     					} 
     					
     					$label = "paymongo card";
     					if($key=="paymongo_gcash"){
     						$label = "gcash";
     					} else if ($key=="paymongo_grabpay") {
     						$label = "grabpay";
     					}
     					
     					$cardfee.= "+".FunctionsV3::prettyPriceNoCurrency($credentials['card_fee_gcash']);
	     				echo $val = Yii::t("default","$label (card fee [fee])",array(
		         		  '[fee]'=>$cardfee
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;				
     			     		
     			
     		case "redsys":       		          	             		             		             		
     			if($credentials=RedsysWrapper::getCredentials($merchant_id)){     			
     				if($credentials['card_fee']>=0.0001){
	     				echo $val = Yii::t("default","redsys (card fee [fee])",array(
		         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
		         		));
     			    } else echo t($val); 
     			} else echo t($val); 
     			break;					
     			
 			case "redsys_bizum":       		          	             		             		             		
 			if($credentials=RedsysWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","redsys bizum (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;		
 			
 			case "xendit":       		          	             		             		             		
 			if($credentials=XenditWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","xendit (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "culqi":       		          	             		             		             		
 			if($credentials=CulqiWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","culqi (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
     					
 			
 			case "flutterwave":       		          	             		             		             		
 			if($credentials=FlutterwaveWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","flutterwave (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "scanpay":       		          	             		             		             		
 			if($credentials=ScanpayWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","Scanpay (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "billplz":       		          	             		             		             		
 			if($credentials=billplzWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","billplz (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "toyyibpay":       		          	             		             		             		
 			if($credentials=toyyibpayWrapper::getCredentials($merchant_id)){    				
 				if($credentials['card_fee']>=0 || $credentials['card_percentage']>0){ 					
 					$percentage = isset($credentials['card_percentage'])?(float)$credentials['card_percentage']:0;
 					$fee = isset($credentials['card_fee'])?(float)$credentials['card_fee']:0;
 					if($percentage>0 && $fee>0){
 						echo $val = Yii::t("default","$key (card fee [percentage]% + [fee])",array(
 						  '[percentage]'=>FunctionsV3::prettyPriceNoCurrency($percentage),
		         		  '[fee]'=>FunctionsV3::prettyPriceNoCurrency($fee)
		         		));
 					} elseif ( $percentage>0 && $fee<=0){
 						echo $val = Yii::t("default","$key (card fee [percentage]%)",array(
 						  '[percentage]'=>FunctionsV3::prettyPriceNoCurrency($percentage)		         		  
		         		));
 					} elseif ( $percentage<=0 && $fee>=0){
 						echo $val = Yii::t("default","$key (card fee [fee])",array( 						  
		         		  '[fee]'=>FunctionsV3::prettyPrice($fee)
		         		));
 					}
 			  } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "amanpay":       		          	             		             		             		
 			if($credentials=amanpayWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","amanpay (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "sofort":       		          	             		             		             		
 			if($credentials=sofortWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","sofort (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "sofort_ideal":       		          	             		             		             		
 			if($credentials=sofortWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","sofort ideal (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
 			
 			case "payhere":       		          	             		             		             		
 			if($credentials=payhereWrapper::getCredentials($merchant_id)){     			
 				if($credentials['card_fee']>=0.0001){
     				echo $val = Yii::t("default","payhere (card fee [fee])",array(
	         		  '[fee]'=>FunctionsV3::prettyPrice($credentials['card_fee'])
	         		));
 			    } else echo t($val); 
 			} else echo t($val); 
 			break;	
     				
         	default:
         		echo t($val); 
         		break;
         }         
         ?>
     </div> 
  </div>
  
  <?php if ( $key=="cod"):?>
  <?php if ($transaction_type!="dinein"):?>
  <div class="row top10 indent20 change_wrap">
    <?php echo CHtml::textField('order_change','',array(
      'placeholder'=>t("change? For how much?"),
      'style'=>"width:200px;",
      'class'=>"grey-fields rounded numeric_only"
     ))?>
  </div>
  <?php endif;?>
  <?php endif;?>
  
  <?php if ( $key=="pyr"):?>
  <?php   
  $provider_list=Yii::app()->functions->getPaymentProviderMerchant($merchant_id);
  /*if ( Yii::app()->functions->isMerchantCommission($merchant_id)){	          	
      $provider_list=Yii::app()->functions->getPaymentProviderListActive();         	
  }	*/         
  ?>
  <div class="payment-provider-wrap top10">  
   <?php if (is_array($provider_list) && count($provider_list)>=1):?>
	   <?php foreach ($provider_list as $val_provider_list): ?>
	   <div class="row">	       	       
	        <div class="col-md-3 relative">
	        <div class="checki">
	        <?php echo CHtml::radioButton('payment_provider_name',false,array(
	          'class'=>"icheck checki",
	          'value'=>$val_provider_list['payment_name']
	        ))?>	        
	        </div>
	        <img class="logo-small" src="<?php echo uploadURL()."/".$val_provider_list['payment_logo']?>">
	        </div>
	    </div>     
	   <?php endforeach;?>	   
	<?php else :?>   
	  <p class="uk-text-danger"><?php echo t("no type of payment")?></p>  
	<?php endif;?>  
  </div> <!--payment-provider-wrap-->
  <?php endif;?>
 
<?php endforeach;?>
<?php else:?>
<p class="text-danger"><?php echo t("No payment option available")?></p>
<?php endif;?>
<?php
class StripeController extends CController
{
	public $layout='singlemerchant.views.layouts.mobile_layout';
	
	public function __construct()
	{
		Yii::app()->setImport(array(			
		  'application.components.*',
		));		
		require_once 'Functions.php';
		
		FunctionsV3::handleLanguage();
	    $lang=Yii::app()->language;	    
	    	    
		$cs = Yii::app()->getClientScript();
		$cs->registerScript(
		  'lang',
		  "var lang='$lang';",
		  CClientScript::POS_HEAD
		);
	}
	
	public function actionIndex()
	{
		$this->pageTitle = t("Stripe");
		require_once('buy.php');
		
		$device_id = isset($_GET['device_id'])?$_GET['device_id']:'';
		
		if(empty($error)){
					
			if ($credentials = StripeWrapper::getCredentials($merchant_id)){ 
				
				try {
					
					$client_email='';
					if( $client_info=Yii::app()->functions->getClientInfo($client_id)){
						$client_email = $client_info['email_address'];
					}
					
					$success_url = websiteUrl()."/singlemerchant/stripe/verify?reference_id=".urlencode($reference_id)."&trans_type=$trans_type";
					$success_url.="&device_id=".urlencode($device_id);
										
					$params = array(
					   'customer_email' => $client_email,					   
					   'payment_method_types'=>array('card'),
					   'client_reference_id'=>$trans_type."-".$reference_id,					   
					   'line_items'=>array(
					     array(
					       'name'=>$payment_description,
						     'description'=>$description,						     
						     'amount'=>unPrettyPrice($amount_to_pay)*100,
						     'currency'=>FunctionsV3::getCurrencyCode(),
						     'quantity'=>1
					     )
					   ),					   
					   'success_url'=>$success_url,
					   'cancel_url'=>websiteUrl()."/singlemerchant/stripe/cancel",
					);
					
					$resp  =  StripeWrapper::createSession($credentials['secret_key'],$params);					
					$stripe_session=$resp['id'];
					$payment_intent=$resp['payment_intent'];
					
					/*LOGS THE PAYMENT INTENT*/
					$db=new DbExt();
					$db->updateData("{{order}}",array(
					  'payment_gateway_ref'=>$payment_intent
					),'order_id',$order_id);
					
					$cs = Yii::app()->getClientScript();
					$cs->registerScriptFile("https://js.stripe.com/v3/");
					
					$publish_key = $credentials['publish_key'];
					$publish_key = "Stripe('$publish_key')";
					
					$cs->registerScript(
					  'stripe',
					  'var stripe = '.$publish_key.';
					  ',
					  CClientScript::POS_HEAD
					);					
					$cs->registerScript(
					  'stripe_session',
					 "var stripe_session='$stripe_session';",
					  CClientScript::POS_HEAD
					);		
					
					if($merchant_id>0){
						$logo = FunctionsV3::getMerchantLogo($merchant_id);		
					} else $logo = FunctionsV3::getDesktopLogo();							
					 
					$this->render('singlemerchant.views.index.stripe_buy',array(				       
				       'logo'=>$logo,				
				       'reference'=>$reference_id,			       
				       'amount_to_pay'=>$amount_to_pay,	
				       'payment_description'=>$payment_description,		       
				       'card_fee'=>$credentials['card_fee']
				    ));
					
				} catch (Exception $e) {
					$error = Yii::t("default","Caught exception: [error]",array(
					  '[error]'=>$e->getMessage()
					));
				}    
				
			} else $error=t("invalid payment credentials");
		}
		
		if(!empty($error)){									
			$this->redirect(Yii::app()->createUrl('/singlemerchant/stripe/error',array(
			   'error'=>$error
			))); 
		}
	}

	
	public function actionverify()
	{
		$db=new DbExt();
		$get = $_GET;$error = '';		
		$reference_id = isset($get['reference_id'])?$get['reference_id']:'';
		$trans_type = isset($get['trans_type'])?$get['trans_type']:'';			
		$device_id = isset($get['device_id'])?$get['device_id']:'';
		
		if(!empty($reference_id)){
			if ($data = FunctionsV3::getOrderInfoByToken($reference_id)){
				$payment_gateway_ref=isset($data['payment_gateway_ref'])?$data['payment_gateway_ref']:'';				
				$merchant_id=isset($data['merchant_id'])?$data['merchant_id']:'';	
        	    $client_id = $data['client_id'];
        	    $order_id = $data['order_id'];
        	    
        	    if($credentials = StripeWrapper::getCredentials($merchant_id)){
        	    	try {
        	    		
        	    		$resp = StripeWrapper::retrievePaymentIntent($credentials['secret_key'],$payment_gateway_ref);
        	    		if($data['status']=="paid"){
        	    			$message =  Yii::t("singleapp","payment successfull with payment reference id [ref]",array(
	                            '[ref]'=>$payment_gateway_ref
	                          ));
	                          
	                        /*CLEAR CART*/
	                        SingleAppClass::clearCartByCustomerID($client_id); 
        	    			
	                        $this->redirect(Yii::app()->createUrl('/singlemerchant/stripe/success',array(
						      'message'=>$message
						    ))); 
		    		  	    Yii::app()->end();
        	    		} else {
        	    			        	    			        	    			
        	    			
        	    			/*SEND EMAIL RECEIPT*/
			                SingleAppClass::sendNotifications($order_id);
        	    			
        	    			FunctionsV3::updateOrderPayment($order_id,StripeWrapper::paymentCode(),
	        	    		$payment_gateway_ref,$resp,$reference_id);
	        	    		  
				            FunctionsV3::callAddons($order_id);
				            
				            /*CLEAR CART*/
				            SingleAppClass::clearCartByCustomerID($client_id); 
				            
				            $message = Yii::t("singleapp","payment successfull with payment reference id [ref]",array(
	                            '[ref]'=>$payment_gateway_ref
	                          ));
	                        $this->redirect(Yii::app()->createUrl('/singlemerchant/stripe/success',array(
						      'message'=>$message
						    ))); 
			    		  	Yii::app()->end();
        	    		}
        	    		
        	    	} catch (Exception $e) {
						$error = Yii::t("default","Caught exception: [error]",array(
						  '[error]'=>$e->getMessage()
						));
					}       
        	    	
        	    } else $error = t("invalid payment credentials");				
        	    
			} else $error = t("Failed getting order information");			
		} else $error = t("invalid reference_id");		
			
		if(!empty($error)){				
			$this->redirect(Yii::app()->createUrl('/singlemerchant/stripe/error',array(
			   'error'=>$error
			))); 
		} 
	}
	
	public function actionsuccess()
	{
		$msg = isset($_GET['message'])?$_GET['message']:'';
		if(!empty($msg)){
			echo $msg;
		} else {
			echo st("payment successfull");
		}
	}
	
    public function actionerror()
	{
		$error = isset($_GET['error'])?$_GET['error']:'';
		if(!empty($error)){
			echo $error;
		} else echo t("undefined error");
	}
	
	public function actioncancel()
	{
		
	}
}
/*end class*/
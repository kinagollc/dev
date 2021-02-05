<?php
class MercadopagoController extends CController
{
	public $layout='mobile_layout';
	
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
		require_once('buy.php');
		$device_id = isset($_GET['device_id'])?$_GET['device_id']:'';
		
		if(empty($error)){
			if ($credentials = mercadopagoWrapper::getCredentials($merchant_id)){
				
				$success_url = websiteUrl()."/singlemerchant/mercadopago/verify?reference_id=".urlencode($reference_id)."&trans_type=$trans_type";
				
				$success_url.="&device_id=".urlencode($device_id);
				
				$cancel_url = websiteUrl()."/singlemerchant/mercadopago/cancel";
				$failure_url=$cancel_url;
				
				try {					
					$params=array(
					  'title'=>$payment_description,
					  'quantity'=>1,
					  'currency_id'=>FunctionsV3::getCurrencyCode(),
					  'unit_price'=>$amount_to_pay,
					  'email'=>$data['email_address'],
					  'external_reference'=>$reference_id,
					  'success'=>$success_url,
					  'failure'=>$failure_url,
					  'pending'=>$cancel_url,
					);					
						
					$resp = mercadopagoWrapper::createPayment($credentials,$params);			
					$this->redirect($resp);
			        Yii::app()->end();
					
				} catch (Exception $e){
			       $error = $e->getMessage();
		        }		
		        
			} else $error=t("invalid payment credentials");
		}
		
		if(!empty($error)){						
			$this->redirect(Yii::app()->createUrl('/singlemerchant/mercadopago/error',array(
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
		$merchant_order_id = isset($get['merchant_order_id'])?$get['merchant_order_id']:'';		
		$device_id = isset($get['device_id'])?$get['device_id']:'';	
		
		if(!empty($reference_id)){
			if ($data = FunctionsV3::getOrderInfoByToken($reference_id)){				
				$merchant_id=isset($data['merchant_id'])?$data['merchant_id']:'';	
        	    $client_id = $data['client_id'];
        	    $order_id = $data['order_id'];
        	    
        	    if($credentials = mercadopagoWrapper::getCredentials($merchant_id)){
        	    	if($data['status']=="paid"){
        	    		echo "order status is already paid";
        	    		
        	    		/*CLEAR CART*/
	                    SingleAppClass::clearCartByCustomerID($client_id); 
	                    
		    		  	Yii::app()->end();
        	    	} else {
	        	    	$resp = mercadopagoWrapper::getPaymentStatus($credentials,$reference_id);
	        	    	
	        	    	/*SEND EMAIL RECEIPT*/
	        	    	SingleAppClass::sendNotifications($order_id);			            
			            
			            FunctionsV3::updateOrderPayment($order_id,mercadopagoWrapper::paymentCode(),
	        	    	$merchant_order_id,$get,$reference_id);
	        	    	
	        	    	FunctionsV3::callAddons($order_id);
	        	    	
	        	    	/*CLEAR CART*/
	                    SingleAppClass::clearCartByCustomerID($client_id); 
	        	    	
	        	    	$message = Yii::t("singleapp","payment successfull with payment reference id [ref]",array(
                            '[ref]'=>$merchant_order_id
                          ));
                          
                        $message = $this->redirect(Yii::app()->createUrl('/singlemerchant/mercadopago/success',array(
						   'message'=>$message
						 )));   
		    		  	Yii::app()->end();
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
/*END CLASS*/
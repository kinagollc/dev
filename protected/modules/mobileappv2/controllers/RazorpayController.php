<?php
Yii::app()->setImport(array(			
  'application.modules.mobileappv2.vendor.*'				  
));

require_once 'razorpay/vendor/autoload.php';
use Razorpay\Api\Api;
			    			    
			    
class RazorpayController extends CController
{
	public $layout = APP_FOLDER.'.views.layouts.mobile_layout';
	
	public function __construct()
	{
		Yii::app()->setImport(array(			
		  'application.components.*',
		));		
		require_once 'Functions.php';
	}
	
	public function actionIndex()
	{
		
		$this->pageTitle = mt("Razorpay");
		require_once('buy.php');
		
		$device_uiid = isset($_GET['device_uiid'])?$_GET['device_uiid']:'';
		
		if(empty($error)){					
			if ($credentials = FunctionsV3::razorPaymentCredentials($merchant_id) ){ 
				
				Yii::app()->setImport(array(			
				  'application.modules.mobileappv2.vendor.*'				  
				));

				$data = Yii::app()->request->stripSlashes($data);
				
				$this->render( APP_FOLDER.'.views.index.razorpay_purchase',array(
	        	  'payment_description'=>stripslashes($payment_description),	        	  
	        	  'amount'=>$amount_to_pay,
	        	  'reference_id'=>$reference_id,
	        	  'credentials' =>$credentials,
	        	  'currency_code'=>$currency_code,
	        	  'email_address'=>isset($data['email_address'])?$data['email_address']:'',
	        	  'full_name'=>isset($data['full_name'])?$data['full_name']:'',
	        	  'merchant_name'=>isset($data['merchant_name'])?$data['merchant_name']:'',
	        	  'contact_phone'=>isset($data['contact_phone'])?$data['contact_phone']:'',
	        	));	
						
			} else $error=mt("invalid payment credentials");
		}
		
		if(!empty($error)){									
			$this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/razorpay/error/?error='.$error )); 
		}
	}

	
	
	
	public function actionverify()
	{
		$post = $_POST;	$error = '';		
		$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:'';		
		if(isset($_POST['error'])){
			$error = isset($_POST['error']['description'])?$_POST['error']['description']:'';
		}		
		
		if(empty($error)){
		if ($data = FunctionsV3::getOrderInfoByToken($reference_id)){
			$merchant_id=isset($data['merchant_id'])?$data['merchant_id']:'';	
    	    $client_id = $data['client_id'];
    	    $order_id = $data['order_id'];
    	    
    	    $razorpay_payment_id = isset($_POST['razorpay_payment_id'])?$_POST['razorpay_payment_id']:'';
    	    $razorpay_order_id = isset($_POST['razorpay_order_id'])?$_POST['razorpay_order_id']:'';
    	    $razorpay_signature = isset($_POST['razorpay_signature'])?$_POST['razorpay_signature']:'';
    	    
    	    if ($credentials = FunctionsV3::razorPaymentCredentials($merchant_id) ){
    	    	try {
	    	    	$api = new Api($credentials['key_id'], $credentials['key_secret']);
	    	    	
	    	    	$attributes  = array(
	    	    	  'razorpay_signature'=>$razorpay_signature,
	    	    	  'razorpay_payment_id'=>$razorpay_payment_id,
	    	    	  'razorpay_order_id'=>$razorpay_order_id
	    	    	);	    	    	
	    	    	$order  = $api->utility->verifyPaymentSignature($attributes);
	    	    	
	    	    	Yii::app()->db->createCommand()->update("{{order}}",array(
	    	    	  'payment_gateway_ref'=>json_encode($attributes)
	    	    	),
			  	    'order_id=:order_id',
				  	    array(
				  	      ':order_id'=>$order_id
				  	    )
			  	    );
	    	    		    	    	
	    	    	/*SEND EMAIL RECEIPT*/
	                mobileWrapper::sendNotification($order_id);	
	    			
	    			FunctionsV3::updateOrderPayment($order_id,"rzr",
    	    		$razorpay_payment_id,$attributes,$reference_id);
    	    		  
		            mobileWrapper::executeAddons($order_id);
		            
		            /*CLEAR CART*/
                    mobileWrapper::clearCartByCustomerID($client_id);
		            
		            $message =  Yii::t("mobile2","payment successfull with payment reference id [ref]",array(
                        '[ref]'=>$razorpay_payment_id
                      ));
                    $this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/stripe/success/?message='.$message )); 
	    		  	Yii::app()->end();
	    	    	
    	    	} catch (Exception $e) {
				    $error  = mt("failed [reason]",array(
				      '[reason]'=>$e->getMessage()
				    ));
				}

    	    } else $error = mt("invalid credentials");		    	    
        	    
		} else $error = mt("invalid reference_id");		
		}
		
		if(!empty($error)){				
			$this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/razorpay/error/?error='.$error )); 
		} 
	}
	
	public function actionsuccess()
	{
		$msg = isset($_GET['message'])?$_GET['message']:'';
		if(!empty($msg)){
			echo $msg;
		} else {
			echo mt("payment successfull");
		}
	}
	
    public function actionerror()
	{
		$error = isset($_GET['error'])?$_GET['error']:'';
		if(!empty($error)){
			echo $error;
		} else echo mt("undefined error");
	}
	
	public function actioncancel()
	{
		
	}
}
/*end class*/
<?php
class VoguepayController extends CController
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
		require_once('buy.php');
		
		$device_id = isset($_GET['device_id'])?$_GET['device_id']:'';
		
		if(empty($error)){
			if($credentials  = FunctionsV3::GetVogueCredentials($merchant_id)){
				
			   if($merchant_id>0){
				   $logo = FunctionsV3::getMerchantLogo($merchant_id);		
			   } else $logo = FunctionsV3::getDesktopLogo();		
			   
			   $success_url = websiteUrl()."/singlemerchant/voguepay/verify?reference_id=".urlencode($reference_id);
			   $success_url.="&device_id=".urlencode($device_id);
				
			   $fail_url = websiteUrl()."/singlemerchant/voguepay/error?reference_id=".urlencode($reference_id);
			   
			   $this->render('singlemerchant.views.index.voguepay_buy',array(				       
			       'logo'=>$logo,				
			       'reference_id'=>$reference_id,			       
			       'amount_to_pay'=>$amount_to_pay,	
			       'payment_description'=>$payment_description,		       
			       'credentials'=>$credentials,
			       'success_url'=>$success_url,
			       'fail_url'=>$fail_url
			    ));
				    
			} else $error = t("invalid merchant credentials");
		}
		
		if(!empty($error)){						
			$this->redirect(Yii::app()->createUrl('/singlemerchant/voguepay/error',array(
			   'error'=>$error
			 ))); 
		}
	}
	
	public function actionverify()
	{		
				
		if(isset($_GET['error'])){
			if(!empty($_GET['error'])){
				echo $_GET['error'];
				Yii::app()->end();
			}
		}
		$DbExt = new DbExt();
		$error='';  $data=$_POST;	
		$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:'';
		$transaction_id = isset($data['transaction_id'])?$data['transaction_id']:'';
		$device_id = isset($_GET['device_id'])?$_GET['device_id']:'';
		
		if(isset($transaction_id)){			
			if ($res = FunctionsV3::getOrderByToken($reference_id)){
				$merchant_id = $res['merchant_id'];
				$order_id = $res['order_id'];			
				$client_id = $res['client_id'];
				
				if($credentials=FunctionsV3::GetVogueCredentials($merchant_id)){
					$is_demo=false;				    
				    if($credentials['merchant_id']=="demo"){
				    	$is_demo=true;
				    }	    	
				    if ( $vog_res=voguepayClass::getTransaction($transaction_id,$is_demo)){				    	
				    	switch (strtolower($vog_res['status'])) {
				    		case "failed":
			    			case "disputed":	
			    			case "pending":	
			    			case "cancelled":
			    				$params_update=array(
			                      'status'=>$vog_res['status'],
			                      'date_modified'=>FunctionsV3::dateNow(),
			                      'ip_address'=>$_SERVER['REMOTE_ADDR']
			                    );	
			                    $DbExt->updateData("{{order}}",$params_update,'order_id',$order_id);
			                    $error = $vog_res['status'];
			    				break;
			    			
			    			case "approved":
			    				
			    				FunctionsV3::updateOrderPayment($order_id,'vog',
	        	    		    $transaction_id,$vog_res,$reference_id);
	        	    		    	        	    		    
			    				/*SEND EMAIL RECEIPT*/
			                    SingleAppClass::sendNotifications($order_id);
			    							    				
	        	    		    FunctionsV3::callAddons($order_id);
	        	    		    
	        	    		    /*CLEAR CART*/
	                            SingleAppClass::clearCartByCustomerID($client_id); 
	        	    		    
	        	    		    $message = Yii::t("singleapp","payment successfull with payment reference id [ref]",array(
		                            '[ref]'=>$transaction_id
		                        ));
		                        
		                        $this->redirect(Yii::app()->createUrl('/singlemerchant/voguepay/success',array(
								   'message'=>urlencode($message)
								 ))); 	
			    		  	    Yii::app()->end();
	        	    		
			    				break;		
			    				
			    			default:
			    				break;	
				    	}
				    } else $error=t("Failed getting transaction information");
				    	
				} else $error = t('Failed getting merchant credentials');
			} else $error = t("Failed getting order information");
		} else $error=t("Payment Failed");
		
		if(!empty($error)){						
			$this->redirect(Yii::app()->createUrl('/singlemerchant/voguepay/error',array(
			   'error'=>$error
			 ))); 
		}
	}
	
	public function actionsuccess()
	{
		$msg = isset($_GET['message'])?$_GET['message']:'';
		if(!empty($msg)){
			echo urldecode($msg);
		} else {
			echo st("payment successfull");
		}
	}
	
	public function actionerror()
	{
		$error='';  $data=$_POST;	
		$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:'';
		$transaction_id = isset($data['transaction_id'])?$data['transaction_id']:'';
		if(isset($transaction_id)){
			if ($res = FunctionsV3::getOrderByToken($reference_id)){
				$merchant_id = $res['merchant_id'];
				$order_id = $res['order_id'];				
				if($credentials=FunctionsV3::GetVogueCredentials($merchant_id)){
					$is_demo=false;				    
				    if($credentials['merchant_id']=="demo"){
				    	$is_demo=true;
				    }	    		    
				    if ( $vog_res=voguepayClass::getTransaction($transaction_id,$is_demo)){
				    	if(isset($vog_res['response_message'])){
							$error = Yii::t("default", "Payment failed reason : [reason]",array(
							  '[reason]'=>$vog_res['response_message']
							));
						} else $error = t("Payment Failed");
				    } else $error=t("Payment Failed");
				} else $error = t('Failed getting merchant credentials');
			} else $error = t("Failed getting order information");
		} else $error=t("Payment Failed");
		
		echo $error;
	}
	
}
/*end class*/
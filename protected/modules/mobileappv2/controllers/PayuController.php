<?php
class PayuController extends CController
{		
	public function __construct()
	{
		Yii::app()->setImport(array(			
		  'application.components.*',
		));		
		require_once 'Functions.php';
	}
	
	public function actionIndex()
	{
		require_once('buy.php');
		
		if ($credentials = PayumoneyWrapper::getCredentials($merchant_id)){ 					
			
			$success_url = websiteUrl()."/".APP_FOLDER."/payu/verify?reference_id=".urlencode($reference_id);
			$failed_url = websiteUrl()."/".APP_FOLDER."/payu/failed?reference_id=".urlencode($reference_id);
			$cancel_url = websiteUrl()."/".APP_FOLDER."/payu/cancel";
			
			$client_info=Yii::app()->functions->getClientInfo($client_id);
									
			$params = array(
			  'key'=>$credentials['key'],
			  'amount'=>normalPrettyPrice($amount_to_pay),
			  'txnid'=>$reference_id,
			  'productinfo'=>$payment_description,
			  'firstname'=>isset($client_info['first_name'])?$client_info['first_name']:'',
			  'email'=>isset($client_info['email_address'])?$client_info['email_address']:'',
			  'surl'=>$success_url,
			  'furl'=>$failed_url,
			  'curl'=>$cancel_url
			);					
			try {
				
				$hash = PayumoneyWrapper::generateHash($params, $credentials['salt']);				
				$payment_link = $credentials['payment_link']."/_payment";				
				$publish_key='';
				$cs = Yii::app()->getClientScript();				
				$cs->registerScript(
					 'submitform',
					 'jQuery(document).ready(function(){ loader(1); $(".payuForm").submit(); });',
					  CClientScript::POS_END
				);		
				$form='';
				$form.= '<form action="'.$payment_link.'" method="post" class="payuForm">';
				$form.= CHtml::hiddenField('key', $credentials['key']);
				$form.= CHtml::hiddenField('hash', $hash);
				$form.= CHtml::hiddenField('txnid', $reference_id);		
				
				$form.= CHtml::hiddenField('amount',$params['amount']);
				$form.= CHtml::hiddenField('firstname',$params['firstname']);
				$form.= CHtml::hiddenField('email',$params['email']);
				$form.= CHtml::hiddenField('phone', isset($client_info['contact_phone'])?$client_info['contact_phone']:'' );
				$form.= CHtml::hiddenField('productinfo',$params['productinfo']);
				$form.= CHtml::hiddenField('surl',$success_url);
				$form.= CHtml::hiddenField('furl',$failed_url);
				$form.= CHtml::hiddenField('service_provider','payu_paisa');				
				
				$form.= CHtml::submitButton('submit',array(
				'style'=>"display:none;"
				));
				$form.= '</form>';
				
				$this->render(APP_FOLDER.'.views.index.payu_buy',array(		
				 'form'=>$form
				));
				
			} catch (Exception $e) {
				$error = Yii::t("mobile2","Caught exception: [error]",array(
				  '[error]'=>$e->getMessage()
				));
			}    
			
		} else $error=mt("invalid payment credentials");
						
		if(!empty($error)){									
			$this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/payu/error/?error='.$error )); 
		}
			
	}
	
	public function actionverify()
	{
		$db=new DbExt();
		$get = $_GET; $post = $_POST; $error = '';		
		$reference_id = isset($get['reference_id'])?$get['reference_id']:'';
				
		if(!empty($reference_id)){
			if ($data = FunctionsV3::getOrderInfoByToken($reference_id)){
				
				$payment_gateway_ref=isset($data['payment_gateway_ref'])?$data['payment_gateway_ref']:'';				
				$merchant_id=isset($data['merchant_id'])?$data['merchant_id']:'';	
        	    $client_id = $data['client_id'];
        	    $order_id = $data['order_id'];
        	    
        	    if($credentials = PayumoneyWrapper::getCredentials($merchant_id)){
        	    	
					$status=$_POST["status"];
					$firstname=$_POST["firstname"];
					$amount=$_POST["amount"];
					$txnid=$_POST["txnid"];
					$posted_hash=$_POST["hash"];
					$key=$_POST["key"];
					$productinfo=$_POST["productinfo"];
					$email=$_POST["email"];
					$salt=$credentials['salt'];
					
					If (isset($_POST["additionalCharges"])) {
						$additionalCharges=$_POST["additionalCharges"];
        $retHashSeq = $additionalCharges.'|'.$salt.'|'.$status.'|||||||||||'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
					} else {
						$retHashSeq = $salt.'|'.$status.'|||||||||||'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
					}
					        	    
					$hash = hash("sha512", $retHashSeq);
					if ($hash != $posted_hash) {
						$error = translate("Invalid Transaction. Please try again");
					} else {						
						$mihpayid = isset($post['mihpayid'])?$post['mihpayid']:'';
						$payuMoneyId = isset($post['payuMoneyId'])?$post['payuMoneyId']:'';
						$bank_ref_num = isset($post['bank_ref_num'])?$post['bank_ref_num']:'';
						$payment_gateway_ref = $payuMoneyId;
						
						/*SEND EMAIL RECEIPT*/
		                mobileWrapper::sendNotification($order_id);	
    	    			
    	    			FunctionsV3::updateOrderPayment($order_id,PayumoneyWrapper::paymentCode(),
        	    		$payment_gateway_ref,$post,$reference_id);
        	    		  
			            mobileWrapper::executeAddons($order_id);
			            
			            /*CLEAR CART*/
                        mobileWrapper::clearCartByCustomerID($client_id);
			            
			            $message =  Yii::t("mobile2","payment successfull with payment reference id [ref]",array(
                            '[ref]'=>$payment_gateway_ref
                          ));
                        $this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/stripe/success/?message='.$message )); 
		    		  	Yii::app()->end();
						
					}
					
        	    } else $error = mt("invalid payment credentials");				
        	    
			} else $error = mt("Failed getting order information");			
		} else $error = mt("invalid reference_id");		
		
		if(!empty($error)){									
			$this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/payu/error/?error='.$error )); 
		}
	}
	
	public function actionfailed()
	{
		$this->redirect(Yii::app()->createUrl('/'.APP_FOLDER.'/payu/cancel/')); 
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
	
} /*end class*/
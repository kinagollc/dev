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
	
	public function beforeAction($action)
	{		
		return true;
	}
	
	public function actionIndex()
	{
		
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
        	    
        	    if($credentials = PayumoneyWrapperWeb::getCredentials($merchant_id)){
        	    	
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
						$error = t("Invalid Transaction. Please try again");
					} else {						
						$mihpayid = isset($post['mihpayid'])?$post['mihpayid']:'';
						$payuMoneyId = isset($post['payuMoneyId'])?$post['payuMoneyId']:'';
						$bank_ref_num = isset($post['bank_ref_num'])?$post['bank_ref_num']:'';
						$payment_gateway_ref = $payuMoneyId;

						$redirec_link=Yii::app()->createUrl('/store/receipt',array('id'=>$order_id));						
						
        	    		if($data['status']=="paid"){
        	    			header('Location: '.$redirec_link."&note=". t("already paid") );   
				            Yii::app()->end(); 		        	    			
        	    		} else {		        	    			
        	    		  FunctionsV3::updateOrderPayment($order_id,PayumoneyWrapperWeb::paymentCode(),
        	    		  $payment_gateway_ref,$post,$reference_id);
        	    		  
			              FunctionsV3::callAddons($order_id);
			              					              
				          header('Location: '.$redirec_link);   
				          Yii::app()->end();						          						            
        	    		}
						
					}
        	    	
        	    } else $error = t("invalid payment credentials");				
        	    
			} else $error = t("Failed getting order information");			
		} else $error = t("invalid reference_id");		
		
		if(!empty($error)){												
			$this->redirect(Yii::app()->createUrl('/payu/error/?error='.$error )); 
		}
	}
	
	public function actioncancel()
	{
		$this->actionfailed();
	}
	
	public function actionfailed()
	{		
		$this->redirect( Yii::app()->createUrl('/store/confirmorder') ); 
	}
	
}
/*end class */
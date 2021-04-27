<?php
require 'authorize-sdk/vendor/autoload.php';
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$this->renderPartial('/front/banner-receipt',array(
   'h1'=>t("Payment"),
   'sub_text'=>t("")
));

$this->renderPartial('/front/order-progress-bar',array(
   'step'=>4,
   'show_bar'=>true
));


$baseUrl = Yii::app()->baseUrl; 
$cs = Yii::app()->getClientScript();

$cs->registerScriptFile($baseUrl."/assets/vendor/jquery.creditCardValidator.js"
,CClientScript::POS_END); 	

$cs->registerScriptFile($baseUrl."/assets/vendor/jquery.formance.min.js"
,CClientScript::POS_END); 	


$merchant_default_country=getOptionA('admin_country_set');
//require_once 'buy.php';
require_once('buy_new.php');
$payment_code = 'atz';

if(empty($error)){
	if (isset($_POST['x_card_num'])){
		$mode_autho=Yii::app()->functions->getOption('merchant_mode_autho',$merchant_id);
        $autho_api_id=Yii::app()->functions->getOption('merchant_autho_api_id',$merchant_id);
        $autho_key=Yii::app()->functions->getOption('merchant_autho_key',$merchant_id);
        
        if (FunctionsV3::isMerchantPaymentToUseAdmin($merchant_id)){
			$mode_autho=Yii::app()->functions->getOptionAdmin('admin_mode_autho');
	        $autho_api_id=Yii::app()->functions->getOptionAdmin('admin_autho_api_id');
	        $autho_key=Yii::app()->functions->getOptionAdmin('admin_autho_key');        
		}
        if (!empty($autho_api_id) && !empty($autho_key)) {
           $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
           $merchantAuthentication->setName($autho_api_id);
           $merchantAuthentication->setTransactionKey($autho_key);
                      
           $card_number = str_replace(" ",'',$_POST['x_card_num']);
           $card_number = trim($card_number);           
           
           $creditCard = new AnetAPI\CreditCardType();    
           $creditCard->setCardNumber( $card_number );
           $creditCard->setExpirationDate( $_POST['expiration_month']."-".$_POST['expiration_yr'] );
           $creditCard->setCardCode( $_POST['cvv'] );
           
           $paymentOne = new AnetAPI\PaymentType();
           $paymentOne->setCreditCard($creditCard);
           
           $order = new AnetAPI\OrderType();
           $order->setInvoiceNumber($payment_ref);
           $order->setDescription($payment_description);
           
           // Set the customer's Bill To address
		    $customerAddress = new AnetAPI\CustomerAddressType();
		    $customerAddress->setFirstName($_POST['x_first_name']);
		    $customerAddress->setLastName($_POST['x_last_name']);
		    //$customerAddress->setCompany("");
		    $customerAddress->setAddress($_POST['x_address']);
		    $customerAddress->setCity($_POST['x_city']);
		    $customerAddress->setState($_POST['x_state']);
		    $customerAddress->setZip($_POST['x_zip']);
		    $customerAddress->setCountry($_POST['x_country']);
		    		    
		    if ($client_info = Yii::app()->functions->getClientInfo($client_id)){		    	
			    $customerData = new AnetAPI\CustomerDataType();
	            $customerData->setType("individual");
	            $customerData->setId($client_id);
	            $customerData->setEmail($client_info['email_address']);
		    }
		    
		    $duplicateWindowSetting = new AnetAPI\SettingType();
            $duplicateWindowSetting->setSettingName("duplicateWindow");
            $duplicateWindowSetting->setSettingValue("60");
            
            $transactionRequestType = new AnetAPI\TransactionRequestType();
		    $transactionRequestType->setTransactionType("authCaptureTransaction");
		    $transactionRequestType->setAmount($amount_to_pay);
		    $transactionRequestType->setOrder($order);
		    $transactionRequestType->setPayment($paymentOne);
		    $transactionRequestType->setBillTo($customerAddress);
		    $transactionRequestType->setCustomer($customerData);
		    $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
		    		    
		    $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($payment_ref);
            $request->setTransactionRequest($transactionRequestType);
            
            $controller = new AnetController\CreateTransactionController($request);
            if($mode_autho=="sandbox"){
               $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            } else {            	
               $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }
            
            if ($response != null) {
            	
            	$resp_code = $response->getMessages()->getResultCode();
            	            	
            	if ($response->getMessages()->getResultCode() == "Ok") {
            		
            		$tresponse = $response->getTransactionResponse();
            		if ($tresponse != null && $tresponse->getMessages() != null) {
            			$transaction_id = $tresponse->getTransId();
            			$resp_description = $tresponse->getMessages()[0]->getDescription();
            			
            			$raw_response = array(
            			  'resp_code'=>$resp_code,
            			  'transaction_id'=>$transaction_id,
            			  'resp_description'=>$resp_description
            			);
            			
            			FunctionsV3::updateOrderPayment($order_id,$payment_code,
				        $transaction_id,$raw_response,$reference_id); 
						
				        FunctionsV3::callAddons($order_id);
            				        
				        $this->redirect( Yii::app()->createUrl('/store/receipt',array(
				          'id'=>$order_id
				        )) );
				        Yii::app()->end();
            			
            		} else {
            			$error = t("Transaction Failed")."<br/>";
            			$error.= $tresponse->getErrors()[0]->getErrorText();
            		}
            		            		
            	} else {
            		$error = t("Transaction Failed")."<br/>";
            		$tresponse = $response->getTransactionResponse();
            		
            		if ($tresponse != null && $tresponse->getErrors() != null) {
		                //$error.= $tresponse->getErrors()[0]->getErrorCode() . "<br/>";
		                $error.= $tresponse->getErrors()[0]->getErrorText() . "<br/>";
		            } else {
		                //$error.= $response->getMessages()->getMessage()[0]->getCode() . "<br/>";
		                $error.=  $response->getMessages()->getMessage()[0]->getText() . "<br/>";
		            }
            	}            
            	
            } else $error = t("No response returned");
    		
        } else $error = t("Missing payment credentials");
	}
}
?>

<div class="sections section-grey2 section-orangeform">
  <div class="container">  
    <div class="row top30">
       <div class="inner">
          <h1><?php echo t("Pay using Authorize.net")?></h1>
          <div class="box-grey rounded">	     
          
           <?php if ( !empty($error)):?>
              <p class="text-danger"><?php echo $error;?></p>  
           <?php endif;?>
                
               <?php echo CHtml::beginForm( '' , 'post', 
               array(
                'id'=>'forms-normal',
                'class'=>"forms"
               ));?>
                              
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Amount")?></div>
				  <div class="col-md-8">
				    <?php echo CHtml::textField('amount',
					  Price_Formatter::formatNumber($amount_to_pay,2)
					  ,array(
					  'class'=>'grey-fields full-width',
					  'disabled'=>true
					  ))?>
				  </div>
				</div>
								

				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Credit Card Number")?></div>
				  <div class="col-md-8">
				    <?php echo CHtml::textField('x_card_num',
				  isset($data_post['x_card_num'])?$data_post['x_card_num']:''
				  ,array(
				  'class'=>'grey-fields numeric_only full-width format_as_card_number' ,
				  'data-validation'=>"required",
				  'maxlength'=>16
				  ))?>
				  </div>
				</div>
				
				
                <div class="row top10">
				  <div class="col-md-3"><?php echo t("Exp. month")?></div>
				  <div class="col-md-8">
				      <?php echo CHtml::dropDownList('expiration_month',
				      isset($data_post['expiration_month'])?$data_post['expiration_month']:''
				      ,
				      Yii::app()->functions->ccExpirationMonth()
				      ,array(
				       'class'=>'grey-fields full-width',
				       'placeholder'=>Yii::t("default","Exp. month"),
				       'data-validation'=>"required"  
				      ))?>
				  </div>
				</div>
									
                <div class="row top10">
				  <div class="col-md-3"><?php echo t("Exp. year")?></div>
				  <div class="col-md-8">
				   <?php echo CHtml::dropDownList('expiration_yr',
				      isset($data_post['expiration_yr'])?$data_post['expiration_yr']:''
				      ,
				      Yii::app()->functions->ccExpirationYear()
				      ,array(
				       'class'=>'grey-fields full-width',
				       'placeholder'=>Yii::t("default","Exp. year") ,
				       'data-validation'=>"required"  
				      ))?>
				  </div>
				</div>
               				
               <div class="row top10">
				  <div class="col-md-3"><?php echo t("CCV")?></div>
				  <div class="col-md-8">
			      <?php echo CHtml::textField('cvv',
			      isset($data_post['cvv'])?$data_post['cvv']:''
			      ,array(
			       'class'=>'grey-fields full-width numeric_only',       
			       'data-validation'=>"required",
			       'maxlength'=>4
			      ))?>							 
				  </div>
				</div>
				

                <div class="row top10">
				  <div class="col-md-3"><?php echo t("First Name")?></div>
				  <div class="col-md-8">
				    <?php echo CHtml::textField('x_first_name',
  isset($data_post['x_first_name'])?$data_post['x_first_name']:''
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"  
  ))?>
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Last Name")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::textField('x_last_name',
  isset($data_post['x_last_name'])?$data_post['x_last_name']:''
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"  
  ))?>				  
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Address")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::textField('x_address',
  isset($data_post['x_address'])?$data_post['x_address']:''
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"  
  ))?>
				  
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("City")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::textField('x_city',
  isset($data_post['x_city'])?$data_post['x_city']:'' 
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"   
  ))?>
				  
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("State")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::textField('x_state',
  isset($data_post['x_state'])?$data_post['x_state']:''
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"    
  ))?>
				  
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Zip Code")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::textField('x_zip',
  isset($data_post['x_zip'])?$data_post['x_zip']:''
  ,array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"  
  ))?>
				  
				  </div>
				</div>				
				
				<div class="row top10">
				  <div class="col-md-3"><?php echo t("Country")?></div>
				  <div class="col-md-8">
  <?php echo CHtml::dropDownList('x_country',
  isset($data_post['country_code'])?$data_post['country_code']:$merchant_default_country,
  (array)Yii::app()->functions->CountryListMerchant(),          
  array(
  'class'=>'grey-fields full-width',
  'data-validation'=>"required"
  ))?>
				  
				  </div>
				</div>			
				
				
               <div class="row top10">
				  <div class="col-md-3"></div>
				  <div class="col-md-8">
				  <input type="submit" value="<?php echo Yii::t("default","Pay Now")?>" class="black-button inline medium">
				  </div>
				</div>	
				
               <!--</form> -->          
               <?php echo CHtml::endForm() ; ?>
           
           <?php //endif;?>
           
           <div class="top25">
		     <a href="<?php echo Yii::app()->createUrl('/store/paymentoption')?>">
             <i class="ion-ios-arrow-thin-left"></i> <?php echo Yii::t("default","Click here to change payment option")?></a>
            </div>
          
          </div> <!--box-->
       </div> <!--inner-->
    </div> <!--row-->
  </div> <!--container-->
</div><!-- sections-->
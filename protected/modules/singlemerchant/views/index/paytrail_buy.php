<div class="header_mobile"> 
 <img src="<?php echo $logo?>" />
</div>


<div class="mobile_body">
 <h3 style="text-align: left;padding-left: 10px;"><?php echo t("Pay using Stripe Payment")?></h3> 
 
	
	<form method="post" action="<?php echo $credentials['environment']['payment']?>">
	<?php                       
	if(is_array($resp)&& count((array)$resp)>=1){
		  foreach ($resp as $key=>$val) {
		  	 echo CHtml::hiddenField($key,$val);
		  }
	}
	?>
   <table class="table" style="text-align:left;">     
     
     <tr>
      <td><?php echo t("Description")?></td>
      <td><?php echo $payment_description?></td>
     </tr>   
     
     <?php if($credentials['card_fee']>0):?>     
     <tr>
      <td><?php echo t("Amount")?></td>
      <td><?php echo FunctionsV3::prettyPrice($amount_to_pay-$credentials['card_fee'])?></td>
     </tr>
      <tr>
      <td><?php echo t("Card fee")?></td>
      <td><?php echo FunctionsV3::prettyPrice($credentials['card_fee'])?></td>
      </tr>      
      <tr>
      <td><?php echo t("Total")?></td>
      <td><?php echo FunctionsV3::prettyPrice($amount_to_pay)?></td>
      </tr>     
     <?php else :?> 
     <tr>
      <td><?php echo t("Amount")?></td>
      <td><?php echo FunctionsV3::prettyPrice($amount_to_pay)?></td>
     </tr>
     <?php endif;?>
     
     <tr>
      <td colspan="2">        
        <button type="submit" class="btn btn-primary" ><?php echo t("Pay Now")?></button>
      </td>
     </tr>
   </table>    
   </form>
 
</div>


<!--PRELOADER-->
<div class="main-preloader">
   <div class="inner">
   <div class="ploader"></div>
   </div>
</div> 
<!--PRELOADER-->
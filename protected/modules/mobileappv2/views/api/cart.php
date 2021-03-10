<?php //dump($data);die()?>
<?php if(is_array($data) && count($data)>=1):?>
<table class="receipt_total" width="100%">
  <?php 
  $average=0.001;
  if(isset($data['delivery_charge'])){
  	 if($data['delivery_charge']>$average){
  	 	echo EuroTax::tableRow( t("Delivery Fee"), Mobile_utility::formatNumber($data['delivery_charge']) );
  	 }
  }
  
  if(isset($data['card_fee'])){
  	 if($data['card_fee']>$average){
  	 	echo EuroTax::tableRow( t("Card Fee"), Mobile_utility::formatNumber($data['card_fee']) );
  	 }
  }
  
  if(isset($data['packaging'])){
  	 if($data['packaging']>$average){
  	 	echo EuroTax::tableRow( t("Packaging"), Mobile_utility::formatNumber($data['packaging']) );
  	 }
  }
  
  if(isset($data['cart_tip_value'])){
  	 if($data['cart_tip_value']>$average){
  	 	echo EuroTax::tableRow( t("Tip")." ". number_format($data['cart_tip_percentage'],0) ."%" , Mobile_utility::formatNumber($data['cart_tip_value']) );
  	 }
  }
  
  if(isset($data['discounted_amount'])){
  	 if($data['discounted_amount']>$average){
  	 	echo EuroTax::tableRow( t("Discount")." ". number_format($data['discount_percentage'],0)  ."%", "(".Mobile_utility::formatNumber($data['discounted_amount']).")" );
  	 }
  }
  
  if(isset($data['points_discount'])){
  	 if($data['points_discount']>$average){
  	 	echo EuroTax::tableRow( t("Points Discount"), "(".Mobile_utility::formatNumber($data['points_discount']).")" );
  	 }
  }
    
  if(isset($data['voucher_amount'])){
  	 if($data['voucher_amount']>$average){
  	 	echo EuroTax::tableRow( t("Less Voucher"), "(".Mobile_utility::formatNumber($data['voucher_amount']).")" );
  	 }
  }
  
  if(isset($data['sub_total'])){
  	 if($data['sub_total']>$average){
  	 	echo EuroTax::tableRow( t("Sub Total"), Mobile_utility::formatNumber($data['sub_total']) );
  	 }
  }
  
  if(isset($data['taxable_total'])){
  	 if($data['taxable_total']>$average){
  	 	echo EuroTax::tableRow( t("Tax")." ".($data['tax']*100) ."%", Mobile_utility::formatNumber($data['taxable_total']) );
  	 }
  }
  
  if(isset($data['total_w_tax'])){
  	 if($data['total_w_tax']>$average){
  	 	echo EuroTax::tableRow( t("Total"), Mobile_utility::formatNumber($data['total_w_tax']) );
  	 }
  }
  
  ?>
</table>
<?php endif;?>
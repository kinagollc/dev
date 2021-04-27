<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 

$prefix = "print_";
?> 


<?php echo htmlWrapper::checkbox($prefix.'enabled_printer','',"Enabled Bluetooth printer", getOptionA($prefix.'enabled_printer') );?>   	

<?php echo htmlWrapper::checkbox($prefix.'enabled_printer_fp_wifi','',"Enabled FP-80WC 80mm WIFI printer", getOptionA($prefix.'enabled_printer_fp_wifi') );?>   	

<div class="spacer"></div>

<p><?php echo translate("Please select data that you wanted to print")?></p>

<div class="row">
  <div class="col-md-6">
  <h5><?php echo translate("Header Section")?></h5>
  
  <?php echo htmlWrapper::checkbox($prefix.'merchant_name','',"Merchant name", getOptionA($prefix.'merchant_name') );?>   	
  
  <?php echo htmlWrapper::checkbox($prefix.'merchant_address','',"Merchant address", getOptionA($prefix.'merchant_address') );?>   	
  
  <?php echo htmlWrapper::checkbox($prefix.'merchant_contact_phone','',"Merchant contact phone", getOptionA($prefix.'merchant_contact_phone') );?>   	
  
  <?php echo htmlWrapper::checkbox($prefix.'printed_date','',"Printed date", getOptionA($prefix.'printed_date') );?>   	
  
  
  <div class="spacer"></div>
  <h5><?php echo translate("Sub Header Section")?></h5>
  
  <?php echo htmlWrapper::checkbox($prefix.'customer_name','',"Customer name", getOptionA($prefix.'customer_name') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'trans_type','',"Transaction type", getOptionA($prefix.'trans_type') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'payment_type','',"Payment type", getOptionA($prefix.'payment_type') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'order_id','',"Reference #", getOptionA($prefix.'order_id') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'date_created','',"TRN Date", getOptionA($prefix.'date_created') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'delivery_date','',"Delivery date", getOptionA($prefix.'delivery_date') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'delivery_time','',"Delivery time", getOptionA($prefix.'delivery_time') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'delivery_address','',"Delivery address", getOptionA($prefix.'delivery_address') );?>   	
  
  <?php echo htmlWrapper::checkbox($prefix.'delivery_instruction','',"Delivery instructions",
   getOptionA($prefix.'delivery_instruction') );?>   	
   
  <?php echo htmlWrapper::checkbox($prefix.'location_name','',"Location name", getOptionA($prefix.'location_name') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'contact_phone','',"Contact Number", getOptionA($prefix.'contact_phone') );?>   	
  <?php echo htmlWrapper::checkbox($prefix.'order_change','',"Change", getOptionA($prefix.'order_change') );?>   	
    
  <div class="spacer"></div>
  <h5><?php echo translate("Footer")?></h5>
  <?php echo htmlWrapper::checkbox($prefix.'site_url','',"Website URL", getOptionA($prefix.'site_url') );?>   	  
  
  <div class="spacer"></div>
  
    <div class="form-group">
	<label><?php echo translate("Custom footer")?></label>
	<?php 
	echo CHtml::textArea($prefix.'footer',
	getOptionA($prefix."footer")
	,array( 
	  'class'=>"form-control",
	  'maxlength'=>"255",
	  'style'=>"height:100px;"
	));
	?>
	</div> 
	<p class="text-muted"><?php echo translate("between words must be separated by new line")?></p>
  
  
  </div> <!--col-->  
</div> 
<!--row-->

<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/savesettings_printer'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		   loader(1);                 
		}',
		'complete'=>'js:function(){		                 
		   loader(2);
		}',
		'success'=>'js:function(data){	
		   if(data.code==1){
		     notify(data.msg);
		   } else {
		     notify(data.msg,"danger");
		   }
		}',
		'error'=>'js:function(data){
		   notify("Error","danger");
		}',
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save_fcm'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
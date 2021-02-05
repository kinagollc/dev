<?php echo CHtml::beginForm(); ?>  
<?php 
echo CHtml::hiddenField('merchant_id',$merchant_id);
echo CHtml::hiddenField('singleapp_default_image',
 getOption($merchant_id,'singleapp_default_image')
 ,array(
   'class'=>'singleapp_default_image'
 ));
?>

<div class="row">

  <div class="col-md-5">
        <div class="form-group" >
	    <label><?php echo SingleAppClass::t("App Default Language")?></label>
	    <?php 
	    echo CHtml::dropDownList('singleapp_default_lang',
	    getOption($merchant_id,'singleapp_default_lang')
	    ,
	    (array)FunctionsV3::getLanguageList(true),array(
	      'class'=>"form-control"
	    ));
	    ?>    
	    <p class="text-muted small"><?php echo SingleAppClass::t("Force default language")?></p>
	  </div>  
  </div>
  
   <div class="col-md-5">	
	<div class="form-group" >
	<label><?php echo SingleAppClass::t("Location Accuracy")?></label>
	<?php 
	echo CHtml::dropDownList('singleapp_location_accuracy',
	getOption($merchant_id,'singleapp_location_accuracy')
	,
	SingleAppClass::locationAccuracyList()
	,array(
	'class'=>"form-control"
	));
	?>    	    
	</div>     
  </div>
  
</div> <!--row-->

<p class="between_div"><b><?php echo st("RTL Options")?></b></p>

<div class="row">
  <div class="col-md-3">
  <?php echo htmlWrapper::checkbox('singleapp_rtl','',"Enabled", getOption($merchant_id,'singleapp_rtl') );?>
  </div> <!--col-->
</div>

<p class="between_div"><b><?php echo st("Registration Options")?></b></p>

<div class="row">

  <div class="col-md-12">
    <?php     
    echo CHtml::dropDownList('singleapp_prefix',getOption($merchant_id,'singleapp_prefix'),
    (array)SingleAppClass::mobileCodeList(),array(
      'class'=>"form-control"
    ));
    ?>
    <small class="form-text text-muted">
      <?php echo st("Default Phone Prefix")?>
    </small>
    </div> 
</div> <!--row-->

<div class="row pt-4"> 
  <div class="col-md-3">
  <?php echo htmlWrapper::checkbox('singleapp_remove_phone_prefix','',"Turn off mobile prefix", getOption($merchant_id,'singleapp_remove_phone_prefix') );?>
  </div> <!--col-->
  <div class="col-md-3">
  <?php echo htmlWrapper::checkbox('singleapp_reg_email','',"Customer Register via Email", getOption($merchant_id,'singleapp_reg_email') );?>
  </div> <!--col-->
  <div class="col-md-3">
  <?php echo htmlWrapper::checkbox('singleapp_reg_phone','',"Customer Register via Phone", getOption($merchant_id,'singleapp_reg_phone') );?> 
  </div> <!--col-->  
</div> <!--row-->



<p class="between_div"><b><?php echo st("Menu/List Style")?></b></p>

<div class="row">
  <div class="col-md-12">
<div class="form-group">
<label><?php echo st("Menu Type")?></label>        
 <?php 
echo CHtml::dropDownList('singleapp_menu_type',getOption($merchant_id,'singleapp_menu_type'),
SingleAppClass::MenuType()
,array(
  'class'=>"form-control"
));
?>     
</div>   
  </div> <!--col-->      
</div> <!--row-->

<div class="row pt-3">
  <div class="col-md-4">
  <?php echo htmlWrapper::checkbox('singleapp_disabled_default_menu','',"Disabled Default Image", getOption($merchant_id,'singleapp_disabled_default_menu') );?> 
  </div> <!--col-->  
  
  <div class="col-md-4">
  <?php echo htmlWrapper::checkbox('singleapp_enabled_addon_desc','',"Show Addon Description", getOption($merchant_id,'singleapp_enabled_addon_desc') );?> 
  </div> <!--col-->  
  
</div> 
<!--row-->


<p class="between_div"><b><?php echo st("Customer Order History")?></b></p>

<div class="row">
	<div class="col-md-4">
     <p><?php echo st("Processing")?></p>
	 <?php 
	 unset($order_status_list[0]);	 
	 echo CHtml::dropDownList('singleapp_order_processing',(array)json_decode(getOption($merchant_id,'singleapp_order_processing')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
	<div class="col-md-4">
	  <p><?php echo st("Completed")?></p>
	 <?php 
	 echo CHtml::dropDownList('singleapp_order_completed',(array)json_decode(getOption($merchant_id,'singleapp_order_completed')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
	<div class="col-md-4">
	  <p><?php echo st("Cancelled")?></p>
	 <?php 
	 echo CHtml::dropDownList('singleapp_order_cancelled',(array)json_decode(getOption($merchant_id,'singleapp_order_cancelled')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
</div>	 <!--row-->

<p class="between_div"><b><?php echo st("Cart Settings")?></b></p>

<div class="row">

<div class="col-md-12">
<?php     
echo CHtml::dropDownList('singleapp_cart_theme',getOption($merchant_id,'singleapp_cart_theme'),
(array)SingleAppClass::trackingTheme(),array(
  'class'=>"form-control"
));
?>
</div>

</div><!-- row-->

<div class="row pt-3">


<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('singleapp_floating_category','',"Enabled floating Category", getOption($merchant_id,'singleapp_floating_category') );?>   	
</div>

<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('singleapp_confirm_future_order','',"Enabled Future Order Confirmation", getOption($merchant_id,'singleapp_confirm_future_order') );?>   	
</div>
</div>
<!--row-->


<p class="between_div"><b><?php echo st("Tracking Settings")?></b></p>

<div class="row">

<div class="col-md-5">
<?php     
echo CHtml::dropDownList('singleapp_tracking_theme',getOption($merchant_id,'singleapp_tracking_theme'),
(array)SingleAppClass::trackingTheme(),array(
  'class'=>"form-control"
));
?>
<small class="form-text text-muted">
  <?php echo st("Tracking Theme")?>
</small>
</div>

<div class="col-md-5">
<?php echo CHtml::textField('singleapp_tracking_interval', getOption($merchant_id,'singleapp_tracking_interval'),
array('class'=>"numeric_only form-control","placeholder"=>st("Track Interval") ));?>
<small class="form-text text-muted">
  <?php echo st("In Millisecond default is 60000, Minimum is 5000")?>
</small>
</div>
  
</div> <!--row-->


<p class="between_div"><b><?php echo st("Custom Page Settings")?></b></p>

<div class="row">
  
<div class="col-md-5">
<?php     
echo CHtml::dropDownList('singleapp_custom_pages_position',getOption($merchant_id,'singleapp_custom_pages_position'),
(array)array(
  1=>st("Position 1 - in tab"),
  2=>st("Position 2 - in sidebar menu"),
),array(
  'class'=>"form-control"
));
?>
</div>

</div>



<div class="floating_action">
 
<?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_appsettings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-app").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-app").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-app").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-app").css({ "pointer-events" : "auto" });	                 	                 
		              }',
		'success'=>'js:function(data){	
		               if(data.code==1){		               
		                 nAlert(data.msg,"success");
		               } else {
		                  nAlert(data.msg,"warning");
		               }
		            }
		'
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save-app'
	)
);
?>

</div>

<?php echo CHtml::endForm(); ?>
<?php echo CHtml::beginForm(); ?>  
<?php echo CHtml::hiddenField('merchant_id',$merchant_id);?>

<p class="between_div"><b><?php echo st("FACEBOOK")?></b></p>
 
<div class="row">
  <div class="col-md-3">  
  <?php echo htmlWrapper::checkbox('singleapp_enabled_fblogin','',"Enabled", getOption($merchant_id,'singleapp_enabled_fblogin') );?>  
  </div>
</div>

<p class="between_div"><b><?php echo st("GOOGLE")?></b></p>

<div class="row">
  <div class="col-md-3">  
  <?php echo htmlWrapper::checkbox('singleapp_enabled_google','',"Enabled", getOption($merchant_id,'singleapp_enabled_google') );?>  
  </div>
</div>



<div class="pt-3">


  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_socialsettings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-social").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-social").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-social").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-social").css({ "pointer-events" : "auto" });	                 	                 
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
	  'id'=>'save-social'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
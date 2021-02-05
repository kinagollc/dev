<?php echo CHtml::beginForm(); ?>  
<?php echo CHtml::hiddenField('merchant_id',$merchant_id);?>

<p><b><?php echo st("Contact form Setting")?></b></p>


<div class="row">
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_enabled','',"Enabled" , getOption($merchant_id,'singleapp_contactus_enabled') );?>   
  </div> <!--col-->  
</div>

<div class="height10"></div>  
<div class="height10"></div>  

<div class="row">
  <div class="col-md-5">
  
  <div class="form-group">
	    <label ><?php echo SingleAppClass::t("Send to")?></label>
	    <?php 
	    echo CHtml::textField('singleapp_contact_email',
	    getOption($merchant_id,'singleapp_contact_email')
	    ,array(
	      'class'=>'form-control',
	    ));
	    ?>
	    <span class="small"><?php echo st("Email address that will receive the email")?></span>
	</div> 
  
  </div> <!--col-->   
  
   <div class="col-md-5">
  
  <div class="form-group">
	    <label ><?php echo SingleAppClass::t("Subject")?></label>
	    <?php 
	    echo CHtml::textField('singleapp_contact_subject',
	    getOption($merchant_id,'singleapp_contact_subject')
	    ,array(
	      'class'=>'form-control',
	    ));
	    ?>	   
	</div> 
  
  </div> <!--col-->   
  
  
</div> <!--row-->



 <div class="form-group">
    <label ><?php echo st("Template")?></label>
    <?php 
    echo CHtml::textArea('singleapp_contact_tpl',
    getOption($merchant_id,'singleapp_contact_tpl')
    ,array(
      'class'=>'form-control', 
      'required'=>true,
      'style'=>"height:200px;"
    ));
    ?>
    <span><?php echo st("Tag available")?> : <b>[name] [email] [country] [phone] [message] [merchant_name]</b></span>
  </div>  

<div class="height10"></div>  
<p><b><?php echo st("Contact form fields")?></b></p>

<div class="row">
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_fields[1]','',"Name", $fields,'name' );?>   
  </div> <!--col-->
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_fields[2]','',"Email", $fields,'email' );?>   
  </div> <!--col-->
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_fields[3]','',"Phone", $fields,'phone' );?>   
  </div> <!--col-->
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_fields[4]','',"Country", $fields,'country' );?>   
  </div> <!--col-->
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_contactus_fields[5]','',"Message", $fields,'message' );?>   
  </div> <!--col-->
</div>


 
<div class="floating_action">


  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_contactus'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-contact").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-contact").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-contact").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-contact").css({ "pointer-events" : "auto" });	                 	                 
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
	  'id'=>'save-contact'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
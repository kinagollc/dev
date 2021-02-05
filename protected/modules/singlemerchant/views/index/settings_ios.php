<?php echo CHtml::beginForm(); ?>  

<?php 
$ios_push_dev_cer = getOption($merchant_id,'singleapp_ios_push_dev_cer');
$ios_push_prod_cer = getOption($merchant_id,'singleapp_ios_push_prod_cer');

echo CHtml::hiddenField('singleapp_ios_push_dev_cer',$ios_push_dev_cer);
echo CHtml::hiddenField('singleapp_ios_push_prod_cer',$ios_push_prod_cer);
?>

<div class="row">
  <div class="col-md-6">
  
   <p style="font-size:12px;color:red;">
  <?php echo SingleAppClass::t("Note: for ios push notification to work make sure your server out going port 2195 is open")?>.
  </p>
  
  <div class="form-group">
      <label><?php echo SingleAppClass::t("IOS Push Mode")?></label>
       <?php 
	    echo CHtml::dropDownList('singleapp_ios_push_mode',
	    getOption($merchant_id,'singleapp_ios_push_mode')
	    ,array(
	      "development"=>SingleAppClass::t("Development"),
	      "production"=>SingleAppClass::t("Production")
	    ),array(
	      'class'=>"form-control"
	    ));
	    ?>
   </div>
   
   <div class="form-group">
    <label><?php echo SingleAppClass::t("IOS Push Certificate PassPhrase")?></label>
    <?php 
    echo CHtml::textField('singleapp_ios_passphrase',
    getOption($merchant_id,'singleapp_ios_passphrase')
    ,array(
      'class'=>'form-control',
    ));
    ?>
   </div>    
   
   <div class="form-group">
    <label style="width:250px;" ><?php echo SingleAppClass::t("IOS Push Development Certificate")?></label>
    <a id="upload-certificate-dev" href="javascript:;" class="btn btn-default"><?php echo SingleAppClass::t("Browse")?></a>        
    <?php if (!empty($ios_push_dev_cer)):?>
    <span><?php echo $ios_push_dev_cer?>...</span>
    <?php endif;?>
  </div>
  
   <div class="form-group">
    <label style="width:250px;" ><?php echo SingleAppClass::t("IOS Push Production Certificate")?></label>
    <a id="upload-certificate-prod" href="javascript:;" class="btn btn-default"><?php echo SingleAppClass::t("Browse")?></a> 
    <?php if (!empty($ios_push_prod_cer)):?>
    <span><?php echo $ios_push_prod_cer?>...</span>
    <?php endif;?>
  </div>
  
  </div> <!--col-->   
</div> <!--row-->


 <div class="form-group pad10">  
  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_settingios'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-ios").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-ios").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-ios").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-ios").css({ "pointer-events" : "auto" });	                 	                 
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
	  'class'=>'btn btn-primary',
	  'id'=>'save-ios'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
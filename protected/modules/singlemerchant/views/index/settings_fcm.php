<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
echo CHtml::hiddenField('merchant_id',$merchant_id);
?> 

<div class="row">
  <div class="col-md-3">
    <?php 
    echo BootstrapWrapper::formRadio("singleapp_fcm_provider","HTTP legacy",
    getOption($merchant_id,'singleapp_fcm_provider')==1?true:false
    ,array(
      'value'=>1,
    ));
    ?>
  </div>
  <div class="col-md-3">
    <?php 
    echo BootstrapWrapper::formRadio("singleapp_fcm_provider","HTTP v1",
    getOption($merchant_id,'singleapp_fcm_provider')==2?true:false
    ,array(
      'value'=>2
    ));
    ?>
  </div>
</div>

<div class="form-group pt-4">    
    <label class="bmd-label-floating"><?php echo st("Server Key")?> (<?php echo st("legacy")?>)</label>
    <?php 
    echo CHtml::textField('singleapp_android_push_key',
      getOption($merchant_id,'singleapp_android_push_key'),array(
     'class'=>"form-control",
     'required'=>true,     
    ));
    ?>        
</div>  

<div class="form-group pt-4">   
<label class="bmd-label-floating"><?php echo st("Service accounts private key")?></label>
<br/>
<button id="upload_services_json" type="button" class="btn <?php echo APP_BTN2?>">
 <?php echo st("Browse")?>
</button>    

<?php 
$file = getOption($merchant_id,'singleapp_services_account_json');
echo CHtml::hiddenField('singleapp_services_account_json',$file,array(
 'class'=>'singleapp_services_account_json'
));
?>

<?php if(!empty($file)):?>
<p class="pt-2 singleapp_services_account_json"><?php 
echo st("File [file]",array(
	    	   '[file]'=>$file
	    	 ));
?></p>
<?php endif;?>

</div>  

<p class="text-muted ">
<?php echo st("Note : please use use http v1 instead of http legacy")?>.
</p>
<p>
<a href="https://youtu.be/D4pfWT_2rKA" target="_blank"><?php echo st("How to get your  Service accounts private key")?></a>
</p>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
SingleAppClass::t('Save Settings'),
array('ajax/save_fcm'),
array(
	'type'=>'POST',
	'dataType'=>'json',
	'beforeSend'=>'js:function(){
	                 busy(true); 	
	                 $("#save-fcm").val("'.SingleAppClass::t('Processing').'");
	                 $("#save-fcm").css({ "pointer-events" : "none" });	                 	                 
	              }
	',
	'complete'=>'js:function(){
	                 busy(false); 		                 
	                 $("#save-fcm").val("'.SingleAppClass::t("Save Settings").'");
	                 $("#save-fcm").css({ "pointer-events" : "auto" });	                 	                 
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
  'id'=>'save-fcm'
)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
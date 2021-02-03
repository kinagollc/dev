<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<div class="row">
  <div class="col-md-3">
    <?php 
    echo BootstrapWrapper::formRadio("mobileapp2_fcm_provider","HTTP legacy",
    getOptionA('mobileapp2_fcm_provider')==1?true:false
    ,array(
      'value'=>1,
    ));
    ?>
  </div>
  <div class="col-md-3">
    <?php 
    echo BootstrapWrapper::formRadio("mobileapp2_fcm_provider","HTTP v1",
    getOptionA('mobileapp2_fcm_provider')==2?true:false
    ,array(
      'value'=>2
    ));
    ?>
  </div>
</div>

<div class="form-group pt-4">    
    <label class="bmd-label-floating"><?php echo mobileWrapper::t("Server Key")?> (<?php echo mt("legacy")?>)</label>
    <?php 
    echo CHtml::textField('mobileapp2_push_server_key',getOptionA('mobileapp2_push_server_key'),array(
     'class'=>"form-control",
     'required'=>true,     
    ));
    ?>        
</div>  

<div class="form-group pt-4">   
<label class="bmd-label-floating"><?php echo mobileWrapper::t("Service accounts private key")?></label>
<br/>
<button id="upload_services_json" type="button" class="btn btn-primary btn-raised">
 <?php echo mobileWrapper::t("Browse")?>
</button>    

<?php 
$file = getOptionA('mobileapp2_services_account_json');
echo CHtml::hiddenField('mobileapp2_services_account_json',$file,array(
 'class'=>'mobileapp2_services_account_json'
));
?>

<?php if(!empty($file)):?>
<p class="pt-2 mobileapp2_services_account_json"><?php 
echo mt("File [file]",array(
	    	   '[file]'=>$file
	    	 ));
?></p>
<?php endif;?>

</div>  

<p class="text-muted ">
<?php echo mt("Note : please use use http v1 instead of http legacy")?>.
</p>
<p>
<a href="https://youtu.be/D4pfWT_2rKA" target="_blank"><?php echo mt("How to get your  Service accounts private key")?></a>
</p>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('Save Settings'),
	array('ajax/savesettings_fcm'),
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
		   notify(error_ajax_message,"danger");
		}',
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save_fcm'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
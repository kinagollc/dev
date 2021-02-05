<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<div class="form-group pt-2">   
<label class="bmd-label-floating"><?php echo translate("Service accounts private key")?></label>
<br/>
<button id="upload_services_json" type="button" class="btn btn-primary btn-raised">
 <?php echo translate("Browse")?>
</button>    

<?php 
$file = getOptionA('merchantapp_services_account_json');
echo CHtml::hiddenField('merchantapp_services_account_json',$file,array(
 'class'=>'merchantapp_services_account_json'
));
?>

<?php if(!empty($file)):?>
<p class="pt-2 merchantapp_services_account_json"><?php 
echo translate("File [file]",array(
	    	   '[file]'=>$file
	    	 ));
?></p>
<?php endif;?>

</div>  

<a href="https://youtu.be/D4pfWT_2rKA" target="_blank"><?php echo translate("How to get your  Service accounts private key")?></a>
</p>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
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
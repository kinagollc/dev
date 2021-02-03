<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
));
?> 

<div class="height20"></div>

<div class="row">
<div class="col-md-6">
<?php 
echo htmlWrapper::checkbox("enabled_firebase_otp",'',"Enabled during customer signup", getOptionA('enabled_firebase_otp'),1);
?>
</div>

</div>
<!--row-->

<div class="height20"></div>


<div class="height20"></div>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('Save Settings'),
	array('ajax/save_firebase_otp'),
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
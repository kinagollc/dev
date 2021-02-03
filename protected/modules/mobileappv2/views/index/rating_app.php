<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
));
?> 


<div class="row">
<div class="col-md-3">
<?php 
echo htmlWrapper::checkbox("mobileapp2_enabled_app_rating",'',"Enabled", getOptionA('mobileapp2_enabled_app_rating') ,1);
?>
</div>

<div class="col-md-3">
<?php 
echo htmlWrapper::checkbox("mobileapp2_app_rating_only_login",'',"Only for login user", getOptionA('mobileapp2_app_rating_only_login') ,1);
?>
</div>

</div>
<!--row-->

<div class="height20"></div>

<div class="form-group">
<label for="mobileapp2_feedback_email"><?php echo mt("Feedback email")?></label>
<?php echo CHtml::textField('mobileapp2_feedback_email', getOptionA('mobileapp2_feedback_email'),
array('class'=>"form-control"));?>
</div>

<div class="form-group">
<label for="mobileapp2_feedback_email"><?php echo mt("iOS APP ID")?></label>
<?php echo CHtml::textField('mobileapp2_app_rating_ios_id', getOptionA('mobileapp2_app_rating_ios_id'),
array('class'=>"form-control"));?>
</div>

<div class="form-group">
<label for="mobileapp2_feedback_email"><?php echo mt("Android Package ID")?></label>
<?php echo CHtml::textField('mobileapp2_app_rating_android_id', getOptionA('mobileapp2_app_rating_android_id'),
array('class'=>"form-control"));?>
</div>

<div class="height20"></div>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('Save Settings'),
	array('ajax/save_app_rating'),
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
<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 

?> 

<div class="spacer"></div>



<div class="form-group">
<label><?php echo translate("Privacy policy link")?></label>
<?php 
    echo CHtml::textField('merchantapp_privacy_policy_link',
    isset($data['merchantapp_privacy_policy_link'])?$data['merchantapp_privacy_policy_link']:''
    ,array(    
     'class'=>"form-control",
     'placeholder'=>translate("example : http://yourserver.com/privacy-policy")
    ));
    ?>
</div>



<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/savesettings_policy'),
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
<?php echo CHtml::beginForm(); ?>  
<?php echo CHtml::hiddenField('merchant_id',$merchant_id);?>

<div class="form-group">
<label ><?php echo st("Your mobile API URL")?></label>
<?php 
echo CHtml::textField('api_url', 
 websiteUrl()."/".SingleAppClass::moduleName()."/api"
 ,array(
'class'=>'form-control copy_text',
'readonly'=>true
));
?>
<p><?php echo st("Set this url on your mobile app config files on [config]",array(
 '[config]'=>"www/js/config.js"
))?></p>
</div>


<div class="form-group">
<label ><?php echo SingleAppClass::t("Merchant key")?></label>
<?php 
echo CHtml::textField('single_app_keys', $single_app_keys ,array(
'class'=>'form-control',
));
?>
<p style="padding-left:5px;padding-top:5px;">
  <a href="javascript:;" class="generate_keys"><?php echo SingleAppClass::t("Click here")?></a> 
 <?php echo SingleAppClass::t("to generate new keys")?>
</p>
</div>



<div class="form-group pad10">  
  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/savesettings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-api").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-api").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-api").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-api").css({ "pointer-events" : "auto" });	                 	                 
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
	  'id'=>'save-api'
	)
);
?>
<a href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/test_api",array(
 'merchant_id'=>$merchant_id
))?>" target="_blank"
style="margin-left:10px;" class="btn <?php echo APP_BTN2?>"><?php echo st("TEST API")?></a>
</div>

<?php echo CHtml::endForm(); ?>


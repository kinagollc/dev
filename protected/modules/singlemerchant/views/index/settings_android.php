<?php echo CHtml::beginForm(); ?>  
<?php echo CHtml::hiddenField('merchant_id',$merchant_id);?>
<?php 
$singleapp_push_icon = getOption($merchant_id,'singleapp_push_icon');
$singleapp_push_picture = getOption($merchant_id,'singleapp_push_picture');
echo CHtml::hiddenField('singleapp_push_icon',$singleapp_push_icon);
echo CHtml::hiddenField('singleapp_push_picture',$singleapp_push_picture);
?>

<div class="row">
  <div class="col-md-4">
  <?php echo htmlWrapper::checkbox('singleapp_enabled_pushpic','',"Enabled Push Picture", getOption($merchant_id,'singleapp_enabled_pushpic') );?>
  

  <div class="height20"></div>
  
<div class="form-group">
    <label style="width:150px;" ><?php echo SingleAppClass::t("Push Icon")?></label>
    <a id="upload-push-icon" href="javascript:;" class="btn <?php echo APP_BTN2?>">
    <?php echo SingleAppClass::t("Browse")?></a>	    
</div>    

<?php if(!empty($singleapp_push_icon)):?>
<div class="singleapp_push_icon_preview">
<div class="card preview_multi_upload" style="width: 10rem;">
	<img class="img-thumbnail" src="<?php echo SingleAppClass::getImage($singleapp_push_icon)?>" >	
	<div class="card-body">
	  <a href="javascript:;" data-id="singleapp_push_icon"	
      class="card-link remove_push_image"><?php echo st("Remove Image");?></a>
	</div>	
 </div>			 
</div> 
<?php else :?>
<div class="singleapp_push_icon_preview"></div>
<?php endif;?> 
 
 </div> <!--col-->
  
</div> <!--row-->

<div class="row">
  <div class="col-md-4">
  
  <div class="height20"></div>
  
  <div class="form-group">
    <label style="width:150px;"><?php echo SingleAppClass::t("Push Picture")?></label>
    <a id="upload-push-picture" href="javascript:;" class="btn <?php echo APP_BTN2?>">
    <?php echo SingleAppClass::t("Browse")?>
    </a>	    
   </div>    
   
<?php if(!empty($singleapp_push_picture)):?>
<div class="singleapp_push_picture_preview">
<div class="card preview_multi_upload" style="width: 10rem;">
	<img class="img-thumbnail" src="<?php echo SingleAppClass::getImage($singleapp_push_picture)?>" >	
	<div class="card-body">
	  <a href="javascript:;" data-id="singleapp_push_picture"	
      class="card-link remove_push_image"><?php echo st("Remove Image");?></a>
	</div>	
 </div>		
</div>	 
<?php else :?>
<div class="singleapp_push_picture_preview"></div>
<?php endif;?>    
  
  </div>
</div>

<div class="pt-3">

  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_sandroidsettings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-android").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-android").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-android").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-android").css({ "pointer-events" : "auto" });	                 	                 
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
	  'id'=>'save-android'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
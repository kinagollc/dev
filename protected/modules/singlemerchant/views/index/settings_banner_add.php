<?php echo CHtml::beginForm(); ?>  
<?php echo CHtml::hiddenField('merchant_id',$merchant_id);?>

 
    <div class="row">
      <div class="col-md-5">  
        <?php echo htmlWrapper::checkbox('singleapp_enabled_banner','',"Enabled Banner", getOption($merchant_id,'singleapp_enabled_banner') );?>
      </div>
    </div>
    
    
    <div class="height20"></div> 
 
   <div class="form-group">
    <label style="width:150px;" ><?php echo SingleAppClass::t("Banner")?></label>
    <a id="upload-banner" href="javascript:;" class="btn <?php echo APP_BTN2?>">
    <?php echo SingleAppClass::t("Browse")?>
    </a>	
    <p class="small"><?php echo st("Drag image to sort")?></p>        
   </div>

     
   <div class="row">
   <div id="sortable3" class="col-md-5 d-flex flex-center flex-wrap banner_preview">
   
    <?php if(!empty($banner)) :?> 
    <?php 
    $banner = json_decode($banner,true);    
    if(is_array($banner) && count((array)$banner)>=1):
    ?>
    <?php foreach ($banner as $val):?>     
	  <div class="card preview_multi_upload" style="width: 10rem;">
		<img class="img-thumbnail" src="<?php echo SingleAppClass::getImage($val)?>" >
		
		<div class="card-body">
		  <a href="javascript:;" data-id="uploadpushpicture" 
		  data-fieldname="android_push_picture" 
		  class="card-link multi_remove_picture"><?php echo st("Remove Image");?></a>
		</div>
		
		<input type="hidden" name="banner[]" value="<?php echo $val?>">
	 </div>	  
    <?php endforeach;?>      
    
    
    <?php endif;?>    
    <?php endif;?>
   
	</div>
   </div><!-- row-->
     

<div class="row pt-3"> 
 <div class="col-md-12">  
   <div class="form-group" >
	    <label ><?php echo st("Scroll Interval")?></label>
	    <?php 
	    echo CHtml::textField('singleapp_homebanner_interval',
	    getOption($merchant_id,'singleapp_homebanner_interval')
	    ,array(
	      'class'=>"form-control numeric_only",
	      'placeholder'=>st("Default is 3000 miliseconds")
	    ));
	    ?>    	    
	</div>  
	
 </div> <!--col-->  
</div>   

<div class="row">
<div class="col-md-2">
  <?php echo htmlWrapper::checkbox('singleapp_homebanner_auto_scroll','',"Auto Scroll", getOption($merchant_id,'singleapp_homebanner_auto_scroll') );?>
 </div> <!--col-->
</div>
   
<div class="floating_action">

  <?php
echo CHtml::ajaxSubmitButton(
	SingleAppClass::t('Save Settings'),
	array('ajax/save_banner'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		                 busy(true); 	
		                 $("#save-banner").val("'.SingleAppClass::t('Processing').'");
		                 $("#save-banner").css({ "pointer-events" : "none" });	                 	                 
		              }
		',
		'complete'=>'js:function(){
		                 busy(false); 		                 
		                 $("#save-banner").val("'.SingleAppClass::t("Save Settings").'");
		                 $("#save-banner").css({ "pointer-events" : "auto" });	                 	                 
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
	  'id'=>'save-banner'
	)
);
?>
</div>   
   
<?php echo CHtml::endForm(); ?>
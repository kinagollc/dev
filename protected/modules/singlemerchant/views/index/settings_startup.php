
<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
));

echo CHtml::hiddenField('merchant_id',$merchant_id);
?> 


<p><b><?php echo st("Startup Options")?></b></p>


<div class="custom-control custom-checkbox">  
  <?php 
  echo CHtml::checkBox('singleapp_enabled_select_language',
  getOption($merchant_id,'singleapp_enabled_select_language')==1?true:false
  ,array(
    'id'=>'singleapp_enabled_select_language',
    'class'=>"custom-control-input",
    'value'=>1
  ));
  ?>
  <label class="custom-control-label" for="singleapp_enabled_select_language">
    <?php echo st("Enabled Select Language")?>
  </label>
</div>

<div class="row pt-3">
<div class="col-md-6"> 
<?php 
echo BootstrapWrapper::formRadio('singleapp_startup','Startup 1',false,array(
  'id'=>'singleapp_startup',		    
  'value'=>1
));
?>
<p class="text-muted"><?php echo st("This will be normal startup")?></p>
</div> <!--col-->

<div class="col-md-6">  
<?php 
echo BootstrapWrapper::formRadio('singleapp_startup','Startup 2',false,array(
  'id'=>'singleapp_startup',		    
  'value'=>2
));
?>
<p class="text-muted">
<?php echo st("This will contain a banner where in you can add your own updates,promo,vouchers etc.")?>
</p>	
</div> <!--col-->
  
</div> <!--row-->

<p class="between_div"><b><?php echo st("Startup 2 Options")?></b></p>

<div class="form-group">
<label style="width:150px;"><?php echo st("Startup 2 Banner")?></label>    
<button id="multi_upload" type="button" class="btn <?php echo APP_BTN2?>">
 <?php echo st("Browse")?>
</button>   
<p class="small"><?php echo st("Drag image to sort")?></p> 
</div> 

<div class="row">
   <div id="sortable1" class="col-md-5 d-flex flex-center flex-wrap banner_preview">
<?php if(is_array($startup_banner) && count((array)$startup_banner)>=1):?>
<?php foreach ($startup_banner as $val):?>
  <div class="card preview_multi_upload" style="width: 10rem;">
	<img class="img-thumbnail" src="<?php echo SingleAppClass::getImage($val)?>" >
	
	<div class="card-body">
	  <a href="javascript:;" data-id="uploadpushpicture" 
	  data-fieldname="android_push_picture" 
	  class="card-link multi_remove_picture"><?php echo st("Remove Image");?></a>
	</div>
	
	<input type="hidden" name="singleapp_startup_banner[]" value="<?php echo $val?>">
 </div>			 
 <div class="height10"></div> 
<?php endforeach;?>
<?php endif;?>
 </div>
</div>

<div class="clear"></div>

<div class="row pt-3">
  
 <div class="col-md-12">
  
   <div class="form-group" >
	    <label ><?php echo st("Scroll Interval")?></label>
	    <?php 
	    echo CHtml::textField('singleapp_startup_interval',
	    getOption($merchant_id,'singleapp_startup_interval')
	    ,array(
	      'class'=>"form-control numeric_only",
	      'placeholder'=>st("Default is 3000 miliseconds")
	    ));
	    ?>    	    
	</div>  
	
 </div> <!--row-->
  
</div>

<div class="row pt-3">
<div class="col-md-4">
  <?php echo htmlWrapper::checkbox('singleapp_startup_auto_scroll','',"Auto Scroll", getOption($merchant_id,'singleapp_startup_auto_scroll') );?>
 </div> <!--row-->
</div>



<div class="floating_action">

  <?php
echo CHtml::ajaxSubmitButton(
	st('Save Settings'),
	array('ajax/savesettings_startup'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		   loader(true);             
		   $("#save_startup").val("'.SingleAppClass::t('Processing').'");    
		}
		',
		'complete'=>'js:function(){		                 
		   loader(false);
		   $("#save_startup").val("'.SingleAppClass::t("Save Settings").'");
		 }',
		'success'=>'js:function(data){	
		   if(data.code==1){
		     notify(data.msg);
		   } else {
		     notify(data.msg,"danger");
		   }
		}
		'
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save_startup'
	)
);
?>
</div>


<?php echo CHtml::endForm(); ?>
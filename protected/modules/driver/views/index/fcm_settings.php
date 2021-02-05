
<!--<form id="frm_fcm_settings" class="frm_fcm_settings form-horizontal" onsubmit="return false;">-->
<?php echo CHtml::beginForm('','post',array(
 'id'=>"frm_fcm_settings",
 'class'=>"frm_fcm_settings form-horizontal",
 'onsubmit'=>"return false;"
)); 

echo CHtml::hiddenField('drv_services_json_account',$services_json_account)
?> 


<div class="form-group">
    <label class="col-sm-2 control-label"><?php echo Driver::t("Server Key (legacy)")?></label>
    <div class="col-sm-10">
     <?php
     echo CHtml::textField('drv_fcm_server_key',getOptionA('drv_fcm_server_key'),array(
       'class'=>'form-control'
     ));
     ?>	    
    </div>
  </div>	

   <div class="form-group">
    <label  class="col-sm-3 control-label" ><?php echo Driver::t("Service accounts private key")?></label>
    <a id="upload_account_json" href="javascript:;" class="btn btn-default"><?php echo Driver::t("Browse")?></a>     
    <span><?php echo isset($services_json_account)?$services_json_account:''?>...</span>    
   </div>	  
   
   <p>
   <a href="https://youtu.be/D4pfWT_2rKA" target="_blank"><?php echo Driver::t("How to get your Service accounts private key")?></a>
   </p>
   
  
   <div class="form-group">
    <label class="col-sm-2 control-label"></label>
    <div class="col-sm-6">
	  <button type="submit" class="orange-button medium rounded">
	  <?php echo Driver::t("Save")?>
	  </button>
    </div>	 
  </div>

<?php echo CHtml::endForm(); ?>
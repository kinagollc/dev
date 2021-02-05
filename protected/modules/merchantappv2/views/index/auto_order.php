<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<h5 style="margin-bottom:0;"><?php echo translate("Enabled")?></h5>
<small class="form-text text-muted">
    <?php echo translate("this features is to auto update the order status after customer purchase")?>.
    <br/>
    <?php echo translate("it can be use to set the status to accepted automatically if merchant didnt accept the order within 10mins or set to decline if merchant no response in 10mins")?>
    <br/>
    <?php echo translate("Make sure the cron jobs [cron_link] runs in your cpanel every minute",array(
     '[cron_link]'=>FunctionsV3::getHostURL().Yii::app()->createUrl(APP_FOLDER."/cron/autoupdatestatus")
    ))?>
</small>


<div class="spacer"></div>

<?php echo htmlWrapper::checkbox('merchantapp_enabled_auto_status_enabled','',"Enabled", getOptionA('merchantapp_enabled_auto_status_enabled') );?>   	

<div class="spacer"></div>

<div class="row">

<div class="col-md-4">
   <p><b><?php echo translate("Change time in")?></b></p>  
   
   <?php 
    echo CHtml::textField('merchantapp_enabled_auto_status_time',
    isset($data['merchantapp_enabled_auto_status_time'])?$data['merchantapp_enabled_auto_status_time']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
        <small class="form-text text-muted">
    <?php echo translate("in minutes")?>
</small>
    
 </div>
 
<div class="col-md-4">
   <p><b><?php echo translate("Set status to")?></b></p>  
   
    <?php 
    echo CHtml::dropDownList('merchantapp_enabled_auto_status',
    isset($data['merchantapp_enabled_auto_status'])?$data['merchantapp_enabled_auto_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
    
 </div>
 
 

<div class="col-md-4">
   <p><b><?php echo translate("Ready in")?></b></p>  
   
   <?php 
    echo CHtml::textField('merchantapp_enabled_auto_status_readyin',
    isset($data['merchantapp_enabled_auto_status_readyin'])?$data['merchantapp_enabled_auto_status_readyin']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("if status is equal to accepted")?><br/>
    <?php echo translate("default is 20mins")?>
    </small>
    
 </div> 

</div>
<!--row-->


<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/auto_order_settings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		   loader(1);                 
		}
		',
		'complete'=>'js:function(){		                 
		   loader(2);
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
	  'id'=>'save_application'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
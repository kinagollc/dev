<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<h5 style="margin-bottom:0;"><?php echo translate("Order Alert")?></h5>
<small class="form-text text-muted">
    <?php echo translate("alert app notification settings")?>
</small>
<div class="spacer"></div>
<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Incoming Unattended")?></b></p>    
    <?php 
    echo CHtml::textField('order_unattended_minutes',
    isset($data['order_unattended_minutes'])?$data['order_unattended_minutes']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("default is 5 minutes")?>
</small>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Outgoing Unattended")?></b></p>    
    <?php 
    echo CHtml::textField('ready_outgoing_minutes',
    isset($data['ready_outgoing_minutes'])?$data['ready_outgoing_minutes']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("default is 5 minutes")?>
</small>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Ready Unattended")?></b></p>    
    <?php 
    echo CHtml::textField('ready_unattended_minutes',
    isset($data['ready_unattended_minutes'])?$data['ready_unattended_minutes']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("default is 5 minutes")?>
</small>
  </div>
  
</div>
<!--row-->

<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Refresh rate")?></h5>
<small class="form-text text-muted">
    <?php echo translate("set the refresh interval to send request to your server to get new/cancel order.")?>
    <br/>
    <?php echo translate("setting to 1 minute will consume your server resource ideal is 3mins")?>
</small>
<div class="spacer"></div>
<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("New order")?></b></p>    
    <?php 
    echo CHtml::textField('refresh_order',
    isset($data['refresh_order'])?$data['refresh_order']:''
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
    <p><b><?php echo translate("Cancel order")?></b></p>    
    <?php 
    echo CHtml::textField('refresh_cancel_order',
    isset($data['refresh_cancel_order'])?$data['refresh_cancel_order']:''
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
    <p><b><?php echo translate("Ready order")?></b></p>    
    <?php 
    echo CHtml::textField('interval_ready_order',
    isset($data['interval_ready_order'])?$data['interval_ready_order']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("in minutes")?>
</small>
  </div>
  
  
</div>
<!--row-->


<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Booking Alert")?></h5>
<small class="form-text text-muted">
    <?php echo translate("alert app notification settings")?>
</small>
<div class="spacer"></div>
<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Incoming Unattended")?></b></p>    
    <?php 
    echo CHtml::textField('booking_incoming_unattended_minutes',
    isset($data['booking_incoming_unattended_minutes'])?$data['booking_incoming_unattended_minutes']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("default is 5 minutes")?>
</small>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("New cancel unattended")?></b></p>    
    <?php 
    echo CHtml::textField('booking_cancel_unattended_minutes',
    isset($data['booking_cancel_unattended_minutes'])?$data['booking_cancel_unattended_minutes']:''
    ,array(
    'placeholder'=>translate("in minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("default is 5 minutes")?>
</small>
  </div>
  
</div>
<!--row-->

<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Refresh rate")?></h5>
<small class="form-text text-muted">
    <?php echo translate("set the refresh interval to send request to your server to get new/cancel order.")?>
    <br/>
    <?php echo translate("setting to 1 minute will consume your server resource ideal is 3mins")?>
</small>
<div class="spacer"></div>
<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("New booking")?></b></p>    
    <?php 
    echo CHtml::textField('refresh_booking',
    isset($data['refresh_booking'])?$data['refresh_booking']:''
    ,array(
    'placeholder'=>translate("minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("in minutes")?>
</small>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Cancel booking")?></b></p>    
    <?php 
    echo CHtml::textField('refresh_cancel_booking',
    isset($data['refresh_cancel_booking'])?$data['refresh_cancel_booking']:''
    ,array(
    'placeholder'=>translate("minutes"),
     'class'=>"form-control numeric_only"
    ));
    ?>
    <small class="form-text text-muted">
    <?php echo translate("in minutes")?>
</small>
  </div>
  
  
</div>
<!--row-->


<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Awake")?></h5>
<small class="form-text text-muted">
    <?php echo translate("keep the app awake when app is active")?>
</small>
<div class="spacer"></div>

<?php echo htmlWrapper::checkbox('merchantapp_keep_awake','',"Enabled", getOptionA('merchantapp_keep_awake') );?>   	


<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Estimated food ready")?></h5>
<small class="form-text text-muted">
    <?php echo translate("set a list of estimated food time. must be numeric")?>
</small>

<div class="spacer"></div>

<div class="row">
<div class="col-md-12">
  <?php echo CHtml::textField('order_estimated_time',
  isset($data['order_estimated_time'])?$data['order_estimated_time']:''
  ,array(
   'class'=>"order_estimated_time"
  ))?>
</div>
</div>
<!--row-->

<div class="spacer"></div><div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Decline reason")?></h5>
<small class="form-text text-muted">
    <?php echo translate("set a list of decline reason")?>
</small>

<div class="spacer"></div>

<div class="row">
<div class="col-md-12">
  <?php echo CHtml::textField('decline_reason_list',
  isset($data['decline_reason_list'])?$data['decline_reason_list']:''
  ,array(
   'class'=>"decline_reason_list"
  ))?>
</div>
</div>
<!--row-->


<div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Upload resizing options")?></h5>
<small class="form-text text-muted">
    <?php echo translate("this options is to resize the image uploaded in the app")?>
</small>

<div class="spacer"></div>

<?php echo htmlWrapper::checkbox('merchantapp_upload_resize_enabled','',"Enabled", getOptionA('merchantapp_upload_resize_enabled') );?>   	


<div class="row">
<div class="col-md-3">
  <?php 
  echo CHtml::textField('merchantapp_upload_resize_width',
    isset($data['merchantapp_upload_resize_width'])?$data['merchantapp_upload_resize_width']:''
    ,array(
    'placeholder'=>translate("Width"),
     'class'=>"form-control numeric_only"
    ));
  ?>
</div>

<div class="col-md-3">
  <?php 
  echo CHtml::textField('merchantapp_upload_resize_height',
    isset($data['merchantapp_upload_resize_height'])?$data['merchantapp_upload_resize_height']:''
    ,array(
    'placeholder'=>translate("Height"),
     'class'=>"form-control numeric_only"
    ));
  ?>
</div>

</div>
<!--ROW-->


<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/application_settings'),
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
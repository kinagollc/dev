<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<h5 style="margin-bottom:0;"><?php echo translate("Booking Tab")?></h5>
<small class="form-text text-muted">
    <?php echo translate("select order status that will appear in respective tab")?>
</small>


<div class="spacer"></div>

<?php echo htmlWrapper::checkbox('merchantapp_enabled_booking','',"Enabled booking", getOptionA('merchantapp_enabled_booking') );?>   	

<div class="spacer"></div>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Incoming")?></b></p>    
    <?php 
    echo CHtml::dropDownList('booking_incoming_status',
    isset($data['booking_incoming_status'])?$data['booking_incoming_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("New cancel")?></b></p>
    <?php 
    echo CHtml::dropDownList('booking_cancel_status',
    isset($data['booking_cancel_status'])?$data['booking_cancel_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Done")?></b></p>
    <?php 
    echo CHtml::dropDownList('booking_done_status',
    isset($data['booking_done_status'])?$data['booking_done_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
</div>
<!--row-->

<div class="spacer"></div>


<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/booking_settings'),
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
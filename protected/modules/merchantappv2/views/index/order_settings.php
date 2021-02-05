<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<h5 style="margin-bottom:0;"><?php echo translate("Order Tab")?></h5>
<small class="form-text text-muted">
    <?php echo translate("select order status that will appear in respective tab")?>
</small>

<div class="spacer"></div>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Incoming")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_incoming_status',
    isset($data['order_incoming_status'])?$data['order_incoming_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Outgoing")?></b></p>
    <?php 
    echo CHtml::dropDownList('order_outgoing_status',
    isset($data['order_outgoing_status'])?$data['order_outgoing_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Ready")?></b></p>
    <?php 
    echo CHtml::dropDownList('order_ready_status',
    isset($data['order_ready_status'])?$data['order_ready_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
</div>
<!--row-->

<div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Define status colors")?></h5>
<small class="form-text text-muted">
    <?php echo translate("select order status that will determined if failed or successful")?>
</small>

<div class="spacer"></div>

<div class="row">
 <div class="col-md-6">
    <p><b><?php echo translate("Failed")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_failed_status',
    isset($data['order_failed_status'])?$data['order_failed_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
  <div class="col-md-6">
    <p><b><?php echo translate("Successful")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_successful_status',
    isset($data['order_successful_status'])?$data['order_successful_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control select2",
      "multiple"=>"multiple",  
    ));
    ?>
  </div>
  
</div>
<!--row-->

<div class="spacer"></div>

<h5 style="margin-bottom:0;"><?php echo translate("Actions status")?></h5>
<small class="form-text text-muted">
    <?php echo translate("select status for each actions")?>
</small>

<div class="spacer"></div>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Accepted")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_accepted_status',
    isset($data['order_action_accepted_status'])?$data['order_action_accepted_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Decline order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_decline_status',
    isset($data['order_action_decline_status'])?$data['order_action_decline_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Cancel order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_cancel_status',
    isset($data['order_action_cancel_status'])?$data['order_action_cancel_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
</div>
<!--row-->

<div class="spacer"></div>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Food is done")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_food_done_status',
    isset($data['order_action_food_done_status'])?$data['order_action_food_done_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Delayed order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_delayed_status',
    isset($data['order_action_delayed_status'])?$data['order_action_delayed_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Completed order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_completed_status',
    isset($data['order_action_completed_status'])?$data['order_action_completed_status']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
</div>
<!--row-->


<div class="spacer"></div>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Approved request to cancel order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_approved_cancel_order',
    isset($data['order_action_approved_cancel_order'])?$data['order_action_approved_cancel_order']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
  
  <div class="col-md-4">
    <p><b><?php echo translate("Decline request to cancel order")?></b></p>    
    <?php 
    echo CHtml::dropDownList('order_action_decline_cancel_order',
    isset($data['order_action_decline_cancel_order'])?$data['order_action_decline_cancel_order']:''
    ,(array)$data['order_status_list'],array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
   
  
</div>
<!--row-->

<div class="spacer"></div><div class="spacer"></div>
<div class="row">
 <div class="col-md-4">
 
    <p><b><?php echo translate("Accepted based time")?></b></p>    
    <small class="form-text text-muted">
    <?php echo translate("select based time that will be added during accepting the order")?>
    </small>

    <?php 
    echo CHtml::dropDownList('accepted_based_time',
    isset($data['accepted_based_time'])?$data['accepted_based_time']:''
    ,(array)MerchantWrapper::acceptedBasedTime(),array(
      'class'=>"form-control",      
    ));
    ?>
 
 </div>
</div>
<!--row-->
  
<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/savesettings_app'),
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
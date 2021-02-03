<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
));
?> 

<div class="height20"></div>

<div class="row">
<div class="col-md-6">
<?php 
echo htmlWrapper::checkbox("enabled_graphical_tracking",'',"Enabled Graphical Tracking",
 getOptionA('enabled_graphical_tracking'),1);
?>
</div>
</div>
<!--row-->

<small class="form-text text-muted">
<?php echo mt("This features work properly with merchant app addon")?>.
</small>

<div class="height20"></div>
<div class="row">
<div class="col-md-6"><b><?php echo mt("Merchant default tracking estimation")?></b></div>
</div>
<!--row-->

<small class="form-text text-muted">
<?php echo mt("This will be use when merchant has not set anything in there settings")?>.
</small>

<div class="height20"></div>

<div class="row">
<div class="col-md-3">
<div class="form-group">
<label><?php echo mt("Delivery")?></label>		
<?php 
echo CHtml::textField("admin_tracking_estimation_delivery1",
isset($data['admin_tracking_estimation_delivery1'])?$data['admin_tracking_estimation_delivery1']:'' 
,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
?>			
</div> 
</div>

<div class="col-md-1" style="margin-top:30px;">
<?php echo mt("To")?>
</div>

<div class="col-md-3">
 <div class="form-group">
	<label>&nbsp;</label>		
	<?php 
	echo CHtml::textField("admin_tracking_estimation_delivery2",
	isset($data['admin_tracking_estimation_delivery2'])?$data['admin_tracking_estimation_delivery2']:'' 
	,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
	?>			
 </div> 
</div>
</div>
<!--row-->


<div class="row">
<div class="col-md-3">

<div class="form-group">
<label><?php echo mt("Pickup")?></label>		
<?php 
echo CHtml::textField("admin_tracking_estimation_pickup1",
isset($data['admin_tracking_estimation_pickup1'])?$data['admin_tracking_estimation_pickup1']:'' 
,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
?>			
</div> 
</div>

<div class="col-md-1" style="margin-top:30px;">
<?php echo mt("To")?>
</div>

<div class="col-md-3">
 <div class="form-group">
	<label>&nbsp;</label>		
	<?php 
	echo CHtml::textField("admin_tracking_estimation_pickup2",
	isset($data['admin_tracking_estimation_pickup2'])?$data['admin_tracking_estimation_pickup2']:'' 
	,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
	?>			
 </div> 
</div>
</div>
<!--row-->



<div class="row">
<div class="col-md-3">

<div class="form-group">
<label><?php echo mt("Dinein")?></label>		
<?php 
echo CHtml::textField("admin_tracking_estimation_dinein1",
isset($data['admin_tracking_estimation_dinein1'])?$data['admin_tracking_estimation_dinein1']:'' 
,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
?>			
</div> 
</div>

<div class="col-md-1" style="margin-top:30px;">
<?php echo mt("To")?>
</div>

<div class="col-md-3">
 <div class="form-group">
	<label>&nbsp;</label>		
	<?php 
	echo CHtml::textField("admin_tracking_estimation_dinein2",
	isset($data['admin_tracking_estimation_dinein2'])?$data['admin_tracking_estimation_dinein2']:'' 
	,array('class'=>"form-control numeric_only",'required'=>true,'maxlength'=>14,'placeholder'=>mt("in minutes") ));
	?>			
 </div> 
</div>
</div>
<!--row-->



<div class="height20"></div>


<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('Save Settings'),
	array('ajax/save_graphical_tracking'),
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
		   notify(error_ajax_message,"danger");
		}',
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save_fcm'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
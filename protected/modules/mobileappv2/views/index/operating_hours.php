<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
));
?> 

<p><?php 
echo mt("this features is for no customer can use the app during non operation hours");
?>.</p>

<div class="row">
<div class="col-md-3">
<?php 
echo htmlWrapper::checkbox("enabled_operating_hours",'',"Enabled", getOptionA('enabled_operating_hours') ,1);
?>
</div>
</div>

<div class="height20"></div>

<?php if(is_array($days) && count($days)>=1):?>
<table class="table">
<?php foreach ($days as $key=>$val): ?>
<tr>
 <td width="20%" class="align-middle">
 <?php  
 echo htmlWrapper::checkbox("operating_hours[$key]",'',ucwords($val),
 array_key_exists($key,(array)$opening_hours)? $opening_hours[$key]['status']=="open"?true:false  :false
 ,$key);
 ?>
 </td> 
 <td width="35%" class="align-middle">
 <?php 
  echo CHtml::dropDownList("start_time[$key]",
    array_key_exists($key,(array)$opening_hours)? $opening_hours[$key]['start_time'] :''
    ,$time_list ,array(
      'class'=>'form-control',      
      'required'=>true
    ));
 ?>
 </td>
 <td width="10%" class="align-middle"><?php echo mobileWrapper::t("To")?></td>
 <td width="35%" class="align-middle">
 <?php 
  echo CHtml::dropDownList("end_time[$key]",
    array_key_exists($key,(array)$opening_hours)? $opening_hours[$key]['end_time'] :''
    ,$time_list ,array(
      'class'=>'form-control',      
      'required'=>true
    ));
 ?>
 </td>
</tr>
<?php endforeach;?>
</table>
<?php endif;?>

<div class="pt-3">
<?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('Save Settings'),
	array('ajax/save_operating_hours'),
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
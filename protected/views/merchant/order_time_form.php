
<div style="padding:20px;width: 700px;">
<?php if (isset($data['group_id'])):?>
<h3><?php echo t("Update")?></h3>
<?php else :?>
<h3><?php echo t("New")?></h3>
<?php endif;?>

<form id="newforms" class="uk-form uk-form-horizontal" method="POST" onsubmit="return false;">
<?php echo CHtml::hiddenField('action','SaveTimeManagement');

$day_selected = isset($data['days'])?explode(",",$data['days']):array();
if(isset($data['group_id'])){
	echo CHtml::hiddenField('edit_group_id',$data['group_id']);
}
$order_status = array();
if(isset($data['order_status'])){
	$order_status = !empty($data['order_status'])?json_decode($data['order_status'],true):array();	
}
?>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("Transaction Type")?></label>
  <?php 
  echo CHtml::dropDownList('transaction_type',
  isset($data['transaction_type'])?$data['transaction_type']:''
  ,
  $transaction_list
  ,array(
   'class'=>"uk-form-width-large",
   'data-validation'=>"required"
  ));
  ?>  
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("Days")?></label>
  <?php 
  echo CHtml::dropDownList('days',
  (array)$day_selected
  ,
  $day_list
  ,array(
   'class'=>"uk-form-width-large chosen",
   'data-validation'=>"required",
   'multiple'=>true,
  ));
  ?>  
</div>

<p class="uk-text-muted"><?php echo t("Note: the time available should be on same day, eg. 8:00 to 23:00")?></p>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("Start time")?></label>
  <?php 
  echo CHtml::textField('start_time',
  isset($data['start_time'])?$data['start_time']:''
  ,array(
    'class'=>"timepick24format time_mask",
    'data-validation'=>"required",
    'placeholder'=>t("00:00")
  ));
  ?>  
  <span class="uk-text-muted"><?php echo t("in 24hours format")?></span>
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("End time")?></label>
  <?php 
  echo CHtml::textField('end_time',
  isset($data['end_time'])?$data['end_time']:''
  ,array(
    'class'=>"timepick24format time_mask",
    'data-validation'=>"required",
    'placeholder'=>t("00:00")
  ));
  ?>  
  <span class="uk-text-muted"><?php echo t("in 24hours format")?></span>
</div>


<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("Nos. Order allowed")?></label>
  <?php 
  echo CHtml::textField('number_order_allowed',
  isset($data['number_order_allowed'])?$data['number_order_allowed']:''
  ,array(
    'class'=>"numeric_only",
    'data-validation'=>"required",
    'maxlength'=>10
  ));
  ?>  
</div>



<div class="uk-form-row">
  <label class="uk-form-label"><?php echo t("Order status")?></label>
  <?php 
  echo CHtml::dropDownList('order_status',
  (array)$order_status
  ,
  (array)$order_status_list
  ,array(
   'class'=>"uk-form-width-large chosen",   
   'multiple'=>true,
  ));
  ?>
  <p class="uk-text-muted">
  <?php echo t("Status that will count the existing order, if empty will use all status")?>.
  </p>  
</div>


<div class="uk-form-row">
<label class="uk-form-label"></label>
<input type="submit" value="<?php echo Yii::t("default","Save")?>" class="uk-button uk-form-width-medium uk-button-success">
<?php if (isset($data['rate_id'])):?> 
  <a href="javascript:;" class="uk-button uk-button-danger location_delete" data-id="<?php echo $data['rate_id']?>" >
  <?php echo t("Delete")?>
  </a>
<?php endif;?>
</div>

</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {	
		
	$('.numeric_only').keyup(function () {     
      this.value = this.value.replace(/[^0-9\.]/g,'');
    });	
    	
    $('.time_mask').mask('00:00',{
       	"placeholder":"00:00"
       });
    
     $(".chosen").chosen({
       	  allow_single_deselect:true,       	  
       }); 	
	
});
$.validate({ 	
	language : jsLanguageValidator,
    form : '#newforms',    
    onError : function() {      
    },
    onSuccess : function() {           
      var params=$("#newforms").serialize();	
      callAjax( $("#newforms #action").val(), params , '' ) ;
      return false;
    }  
});
</script>
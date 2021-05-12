
<div class="uk-width-1">
<a href="<?php echo Yii::app()->request->baseUrl; ?>/merchant/voucher/Do/Add" class="uk-button"><i class="fa fa-plus"></i> <?php echo Yii::t("default","Add New")?></a>
<a href="<?php echo Yii::app()->request->baseUrl; ?>/merchant/voucher" class="uk-button"><i class="fa fa-list"></i> <?php echo Yii::t("default","List")?></a>
</div>

<div class="spacer"></div>

<div id="error-message-wrapper"></div>

<form class="uk-form uk-form-horizontal forms" id="forms">
<?php echo CHtml::hiddenField('action','addVoucherNew')?>
<?php echo CHtml::hiddenField('id',isset($_GET['id'])?$_GET['id']:"");?>
<?php if (!isset($_GET['id'])):?>
<?php echo CHtml::hiddenField("redirect",Yii::app()->request->baseUrl."/merchant/voucher/Do/Add")?>
<?php endif;?>

<?php 
$days_list = FunctionsV3::dayList();
$has_already_used=false; $selected_days = array();
if (isset($_GET['id'])){
	if (!$data=Yii::app()->functions->getVoucherCodeByIdNew($_GET['id'])){
		echo "<div class=\"uk-alert uk-alert-danger\">".
		Yii::t("default","Sorry but we cannot find what your are looking for.")."</div>";
		return ;
	} 
	
	if (isset($data['found'])){
		if ( $data['found']>0){
			$has_already_used=true;
		}
	}
	
	foreach ($days_list as $day=>$dayval) {
		if(isset($data[$day])){
			if($data[$day]==1){
				$selected_days[]=$day;
			}
		}
	}		
}

$selected_customer = isset($data['selected_customer'])?json_decode($data['selected_customer'],true):array();
$pre_selected = FunctionsV3::getCustomerPreSelected($selected_customer);
?>                                 

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Voucher name")?></label>
  <?php echo CHtml::textField('voucher_name',$data['voucher_name'],array('data-validation'=>'required'))?>
</div>

<?php if ($has_already_used):?>
<p class="uk-text-small uk-text-danger"><?php echo t("This voucher has already been used editing the voucher name may cause error on the system")?></p>
<?php echo CHtml::hiddenField('disabled_voucher_code')?>
<?php endif;?>



<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Type")?></label>  
  <?php
echo CHtml::dropDownList('voucher_type',$data['voucher_type'],
Yii::app()->functions->voucherType(),array(
  'data-validation'=>"required"
))
?>
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Discount")?></label>  
  <?php echo CHtml::textField('amount',
  normalPrettyPrice($data['amount'])
  ,array('data-validation'=>'required','class'=>'numeric_only'))?>
  <span class="uk-text-muted"><?php echo Yii::t("default","Voucher amount discount.")?></span>
</div>


<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Minimum Order")?></label>  
  <?php echo CHtml::textField('min_order',
  normalPrettyPrice($data['min_order'])
  ,array('data-validation'=>'requiredx','class'=>'numeric_only'))?>  
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Days Available")?></label>
  <?php echo CHtml::dropDownList('days[]',
  (array)$selected_days,$days_list,array(
  'class'=>"chosen uk-form-width-large",
  'data-validation'=>'required',
  'multiple'=>true
  ))?>
</div>


<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Expiration")?></label>  
  <?php
  echo CHtml::hiddenField('expiration',$data['expiration']);
  echo CHtml::textField('expiration1',FormatDateTime($data['expiration'],false),
  array(
 'class'=>'j_date' ,
 'data-id'=>'expiration',
 'data-validation'=>"required"
))
?>
</div>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Voucher options")?></label>  
   <?php echo CHtml::dropDownList('used_once',
  isset($data['used_once'])?$data['used_once']:"",
  array(
    1=>t("Unlimited for all user"),
    2=>t("Use only once"),
    3=>t("Once per user"),
    4=>t("Once for new user first order"),   
    5=>t("Custom limit per user"),
    6=>t("Only to selected customer")
  ), 
  array(
  'class'=>'uk-form-width-large used_once',
  'data-validation'=>"required"
  ))?>
</div>


<div class="uk-form-row" id="max_number_use_div">
  <label class="uk-form-label"><?php echo Yii::t("default","Define max number of use")?></label>  
  <?php echo CHtml::textField('max_number_use',
  isset($data['max_number_use'])?$data['max_number_use']:''
  ,
  array(    
    'class'=>"numeric_only"
  ))?>
</div>


<div class="uk-form-row" id="selected_customer_div">
  <label class="uk-form-label"><?php echo Yii::t("default","Select Customer")?></label>  
  <?php echo CHtml::dropDownList('selected_customer',
  (array)$selected_customer,
  $pre_selected, 
  array(
  'multiple'=>true,
  'class'=>'uk-form-width-large ajax_selected_customer',   
  ))?>
</div>


<div class="uk-form-row">
  <label class="uk-form-label">Status</label>
  <?php echo CHtml::dropDownList('status',
  isset($data['status'])?$data['status']:"",
  (array)statusList(), 
  array(
  'class'=>'uk-form-width-large',
  'data-validation'=>"required"
  ))?>
</div>


<div class="uk-form-row">
<label class="uk-form-label"></label>
<input type="submit" value="<?php echo Yii::t("default","Save")?>" class="uk-button uk-form-width-medium uk-button-success">
</div>

</form>
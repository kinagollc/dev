

<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;",
 'class'=>"uk-form uk-form-horizontal"
));

$rates = Yii::app()->session['exchange_rate'];
$yii_session_token=session_id();	
echo CHtml::hiddenField('yii_session_token',$yii_session_token);
?>

<div class="uk-form-row">
  <label class="uk-form-label"><?php echo Yii::t("default","Free delivery above Sub Total Order")?></label>
  <?php
  echo CHtml::textField('free_delivery_above_price',
  Yii::app()->functions->getOption("free_delivery_above_price",$mtid)
  ,array('class'=>"numeric_only"));  
  ?>
  <span style="padding-left:8px;"><?php echo isset($rates['base_currency'])?$rates['base_currency']:'';?></span>
</div>

<div class="spacer"></div>


<?php
echo CHtml::ajaxSubmitButton(
	t('Save Settings'),
	array('ajaxmerchant/freeDeliverySettings'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		   busy(true);                 
		}',
		'complete'=>'js:function(){		                 
		   busy(false);
		}',
		'success'=>'js:function(data){	
		   if(data.code==1){
		     uk_msg(data.msg);
		   } else {
		     uk_msg(data.msg);
		   }
		}',
		'error'=>'js:function(data){
		   uk_msg("response failed");
		   busy(false);
		}',
	),array(
	  'class'=>"uk-button uk-form-width-medium uk-button-success",
	  'id'=>'save'
	)
);
?>
<?php echo CHtml::endForm(); ?>




<h3><?php echo t("Delivery Rates Table")?></h3>

<a href="javascript:;" class="uk-button uk-button-success add-new-rates">
<i class="fa fa-plus"></i>
<?php echo t("Add new")?>
</a>

<p class="uk-text-muted">
(<?php echo t("drag the list to sort")?>)
</p>

<table id="location_table_rates" class="uk-table uk-table-hover uk-table-striped uk-table-condensed">
<thead>
  <tr>
   <th width="6%"><?php echo t("Country")?></th>   
   <th width="5%"><?php echo t("State/Region")?></th>   
   <th width="5%"><?php echo t("City")?></th>
   <th width="5%"><?php echo t("Distric/Area/neighborhood")?></th>
   <th width="5%"><?php echo t("Postal Code/Zip Code")?></th>
   <th width="5%"><?php echo t("Fee")?></th>
   <th width="5%"><?php echo t("Min. Order")?></th>
   <th width="5%"><?php echo t("Free delivery above sub total")?></th>
  </tr>
</thead>
<tbody class="location_table_rates">
</tbody>
</table>
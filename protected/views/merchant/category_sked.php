
<form class="uk-form uk-form-horizontal forms" id="forms">
<?php echo CHtml::hiddenField('action','saveCategorySked')?>


<div class="uk-form-row">
   <label class="uk-form-label"><?php echo t("Enabled Day schedule")?></label>
  <?php  
  echo CHtml::checkBox('enabled_category_sked',
  getOption($merchant_id,'enabled_category_sked')
  ,array(
    'value'=>1
  ));
  ?>  
</div>    

<div class="uk-form-row">
   <label class="uk-form-label"><?php echo t("Enabled Time Schedule")?></label>
  <?php  
  echo CHtml::checkBox('enabled_category_sked_time',
  getOption($merchant_id,'enabled_category_sked_time')
  ,array(
    'value'=>1
  ));
  ?>  
</div>    


<div class="uk-form-row" style="margin-top:20px;">
<input type="submit" value="<?php echo Yii::t("default","Save")?>" class="uk-button uk-form-width-medium uk-button-success">
</div>

</form>
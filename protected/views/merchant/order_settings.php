
<h3><?php echo t("Order Time Management")?></h3>

<p>
<?php echo t("Order Time Management is to limit the customer incoming orders")?>.
</p>

<div class="uk-form-row">
  <label class="uk-form-label" style="padding-right:20px;"><?php echo t("Enabled")?></label>
  <?php 
  echo CHtml::checkBox('merchant_time_order_management',
  getOption($merchant_id,'merchant_time_order_management')==1?true:false
  ,array(
    'value'=>1,
    'class'=>"icheck merchant_time_order_management"
  ))
  ?> 
</div>

<div class="spacer"></div>

<a href="javascript:;" class="uk-button uk-button-success add_time_management">
<i class="fa fa-plus"></i>
Add new</a>



<form id="frm_table_list" method="POST" >
<input type="hidden" name="action" id="action" value="OrderTimeManagmentList">
<input type="hidden" name="tbl" id="tbl" value="bookingtable">
<input type="hidden" name="clear_tbl"  id="clear_tbl" value="clear_tbl">
<input type="hidden" name="whereid"  id="whereid" value="booking_id">
<input type="hidden" name="slug" id="slug" value="tablebooking/Do/Add">
<table id="table_list" class="uk-table uk-table-hover uk-table-striped uk-table-condensed">  
   <thead>
        <tr>
            <th width="1%"><?php echo Yii::t("default","ID")?></th>
            <th width="7%"><?php echo Yii::t('default',"Trans. Type")?></th>  
            <th width="7%"><?php echo Yii::t('default',"Day(s)")?></th>            
            <th width="5%"><?php echo Yii::t('default',"Start time")?></th>
            <th width="5%"><?php echo Yii::t('default',"End time")?></th>
            <th width="5%"><?php echo Yii::t('default',"Nos. Order allowed")?></th>
            <th width="5%"><?php echo Yii::t('default',"Order status")?></th>
        </tr>
    </thead>
    <tbody> 
    </tbody>
</table>
<div class="clear"></div>
</form>
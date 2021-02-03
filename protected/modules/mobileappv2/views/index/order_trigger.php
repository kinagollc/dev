<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",
  'onsubmit'=>"return false;"
)); 
?> 
<div class="card" id="box_wrap">
<div class="card-body">

<div class="row action_top_wrap desktop button_small_wrap">   

<div class="col-md-6 col-sm-6">
<button type="button" class="btn <?php echo APP_BTN2;?> refresh_datatables"  >
 <?php echo mobileWrapper::t("Refresh")?> 
 </button>
</div>
<!--col-->

<div class="col-md-6 col-sm-6 text-right">
 
<div class="table_search_wrap">
  <a href="javascript:;" class="a_search"><i class="fas fa-search"></i></a>
  <div class="search_inner">
  <button type="submit" class="btn">
    <i class="fas fa-search"></i>
  </button>  
  <?php   
  echo CHtml::textField('search_fields','',array(
   'placeholder'=>mobileWrapper::t("Search"),
   'class'=>"form-control"
  ));
  ?>
  <a href="javascript:;" class="a_close"><i class="fas fa-times"></i></a>
  </div> <!-- search_inner--> 
</div>
<!--table_search_wrap-->

</div> <!--col-->
 

</div>
<!--action-->

<?php 
if(isset($bid)){
	echo CHtml::hiddenField('broadcast_id',$bid);
}
?>
<table class="table data_tables table-hover" data-action_name="order_trigger" >
 <thead>
  <tr>
   <th><?php echo mobileWrapper::t("Trigger ID")?></th>
   <th><?php echo mobileWrapper::t("Trigger Type")?></th>
   <th><?php echo mobileWrapper::t("Order ID")?></th>
   <th><?php echo mobileWrapper::t("Order Status")?></th>
   <th><?php echo mobileWrapper::t("Order Remarks")?></th>
   <th><?php echo mobileWrapper::t("Date")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>

</div> <!--card body-->
</div> <!--card--><?php echo CHtml::endForm() ; ?>

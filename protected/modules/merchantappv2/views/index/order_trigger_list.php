<div class="card" id="box_wrap">
<div class="card-body">


<div class="row action_top_wrap desktop button_small_wrap">   

<div class="col-md-6 col-sm-6">
 <button type="button" class="btn btn-raised refresh_datatables"  >
 <?php echo translate("Refresh")?> 
 </button>
 </div>
 
<div class="col-md-6 col-sm-6 text-right">
 
<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",
  'onsubmit'=>"return false;"
)); 
?> 
<div class="table_search_wrap">
  <a href="javascript:;" class="a_search"><i class="fas fa-search"></i></a>
  <div class="search_inner">
  <button type="submit" class="btn">
    <i class="fas fa-search"></i>
  </button>  
  <?php   
  echo CHtml::textField('search_fields','',array(
   'placeholder'=>translate("Search"),
   'class'=>"form-control"
  ));
  ?>
  <a href="javascript:;" class="a_close"><i class="fas fa-times"></i></a>
  </div> <!-- search_inner--> 
</div>
<!--table_search_wrap-->
<?php echo CHtml::endForm() ; ?>

</div> <!--col-->
 
 
</div> <!--action_top_wrap-->

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
?> 

<table id="table_list" class="table data_tables">
 <thead>
  <tr>
  <th><?php echo translate("Trigger ID")?></th>
   <th><?php echo translate("Trigger Type")?></th>
   <th><?php echo translate("Order ID/Booking ID")?></th>
   <th><?php echo translate("Template")?></th>
   <th><?php echo translate("Status")?></th>
   <th><?php echo translate("Date Process")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->
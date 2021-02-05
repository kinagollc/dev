<div class="card" id="box_wrap">
<div class="card-body">


<div class="row action_top_wrap desktop button_small_wrap">   
 <button type="button" class="btn btn-raised refresh_datatables"  >
 <?php echo st("Refresh")?> 
 </button>
</div> <!--action_top_wrap-->

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
?> 

<table id="table_list" class="table data_tables">
 <thead>
  <tr>
  <th><?php echo st("Trigger ID")?></th>
   <th><?php echo st("Trigger Type")?></th>
   <th><?php echo st("Order ID")?></th>
   <th><?php echo st("Order Status")?></th>
   <th><?php echo st("Order Remarks")?></th>
   <th><?php echo st("Date")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->
<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",
  'onsubmit'=>"return false;"
)); 
?> 

<div class="card" id="box_wrap">
<div class="card-body">

<div class="row action_top_wrap desktop button_small_wrap">   

<div class="col-md-6 col-sm-6">
 <button type="button" class="btn btn-raised refresh_datatables"  >
 <?php echo st("Refresh")?> 
 </button>
</div> <!--col-->

<div class="col-md-6 col-sm-6 text-right">
 
<div class="table_search_wrap">
  <a href="javascript:;" class="a_search"><i class="fas fa-search"></i></a>
  <div class="search_inner">
  <button type="submit" class="btn">
    <i class="fas fa-search"></i>
  </button>  
  <?php   
  echo CHtml::textField('search_fields','',array(
   'placeholder'=>st("Search merchant name or merchant id"),
   'class'=>"form-control"
  ));
  ?>
  <a href="javascript:;" class="a_close"><i class="fas fa-times"></i></a>
  </div> <!-- search_inner--> 
</div>
<!--table_search_wrap-->

</div> <!--col-->
 
</div> <!--action_top_wrap-->


<table id="table_list" class="table table-striped data_tables">
 <thead>
  <tr>
    <th width="10%"><?php echo st("ID")?></th>
    <th><?php echo st("Merchant Name")?></th>        
    <th><?php echo st("Merchant Keys")?></th>
    <th><?php echo st("Status")?></th>
    <th width="15%"><?php echo st("Action")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>

</div> <!--card body-->
</div> <!--card-->

<?php echo CHtml::endForm() ; ?>

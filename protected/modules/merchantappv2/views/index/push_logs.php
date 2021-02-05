
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

<table id="table_list" class="table table-striped data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo translate("ID")?></th>
    <th><?php echo translate("PushType")?></th>
    <th><?php echo translate("Name")?></th>
    <th ><?php echo translate("Platform")?></th>
    <th ><?php echo translate("Device ID")?></th>
    <th><?php echo translate("Title")?></th>    
    <th><?php echo translate("Message")?></th>
    <th><?php echo translate("Date")?></th>
    <th><?php echo translate("Process")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->


<div class="modal fade" id="errorDetails" tabindex="-1" role="dialog" aria-labelledby="errorDetails" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo translate("Details")?></h5></div>

        <div class="modal-body">
        <?php 
        echo CHtml::hiddenField('details_id');
        ?>
        <p class="error_details"></p>
        </div>

      </div><!-- content-->
 </div>
</div>

<div class="modal fade" id="deviceDetails" tabindex="-1" role="dialog" aria-labelledby="deviceDetails" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo translate("Details")?></h5></div>

        <div class="modal-body">        
        <p class="device_details"></p>
        </div>

      </div><!-- content-->
 </div>
</div>
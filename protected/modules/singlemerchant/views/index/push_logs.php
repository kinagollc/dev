
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

<table id="table_list" class="table table-striped data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo SingleAppClass::t("ID")?></th>
    <th><?php echo SingleAppClass::t("PushType")?></th>
    <th><?php echo SingleAppClass::t("Name")?></th>
    <th ><?php echo SingleAppClass::t("Platform")?></th>
    <th ><?php echo SingleAppClass::t("Device ID")?></th>
    <th><?php echo SingleAppClass::t("Title")?></th>    
    <th><?php echo SingleAppClass::t("Message")?></th>
    <th><?php echo SingleAppClass::t("Date")?></th>
    <th><?php echo SingleAppClass::t("Process")?></th>
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
        <div class="modal-header"> <h5 class="modal-title" ><?php echo st("Details")?></h5></div>

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
        <div class="modal-header"> <h5 class="modal-title" ><?php echo st("Details")?></h5></div>

        <div class="modal-body">        
        <p class="device_details"></p>
        </div>

      </div><!-- content-->
 </div>
</div>
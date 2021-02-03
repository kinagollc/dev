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
 <?php echo mobileWrapper::t("Refresh")?> 
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
   'placeholder'=>mobileWrapper::t("Search"),
   'class'=>"form-control"
  ));
  ?>
  <a href="javascript:;" class="a_close"><i class="fas fa-times"></i></a>
  </div> <!-- search_inner--> 
</div>
<!--table_search_wrap-->

</div> <!--col-->
 
</div> <!--action_top_wrap-->

<table id="table_list" class="table data_tables table-hover" data-action_name="device_list">
 <thead>
  <tr>
   <th><?php echo mobileWrapper::t("ID")?></th>
   <th><?php echo mobileWrapper::t("Name")?></th>
   <th><?php echo mobileWrapper::t("Platform")?></th>
   <th><?php echo mobileWrapper::t("UIID")?></th>
   <th><?php echo mobileWrapper::t("Device ID")?></th>
   <th><?php echo mobileWrapper::t("Push Enabled")?></th>
   <th><?php echo mobileWrapper::t("Subribe alert")?></th>
   <th><?php echo mobileWrapper::t("Date Created")?></th>
   <th><?php echo mobileWrapper::t("Last Login")?></th>
   <th><?php echo mobileWrapper::t("Actions")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>

</div> <!--card body-->
</div> <!--card-->
<?php echo CHtml::endForm() ; ?>


<div class="modal fade" id="deviceDetails" tabindex="-1" role="dialog" aria-labelledby="deviceDetails" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo mt("Details")?></h5></div>

        <div class="modal-body">        
        <p class="device_details"></p>
        </div>

      </div><!-- content-->
 </div>
</div>


<div class="modal fade" id="sendPushModal" tabindex="-1" role="dialog" aria-labelledby="sendPushModal" aria-hidden="true">
<div>
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo mt("Send Push Notification")?></h5></div>
        
        <!--<form id="frm" method="post" onsubmit="return false;" data-action="send_push">-->			
		<?php echo CHtml::beginForm('','post',array(
		  'id'=>"frm",
		  'onsubmit'=>"return false;",
		  'data-action'=>"send_push"
		)); 
		?> 
        
        <div class="modal-body">
        <?php echo CHtml::hiddenField('id','')?>
	    
		<div class="form-group">
		<label><?php echo mt("Push Title")?></label>
		<?php 
		echo CHtml::textField('push_title','',array('class'=>"form-control",'required'=>true ));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo mt("Push Message")?></label>
		<?php 
		echo CHtml::textArea('push_message','',array('class'=>"form-control",'maxlength'=>"255",'required'=>true));
		?>
		</div> 
		
        
        </div> <!--modal body-->
        
        <div class="modal-footer">
          <button type="button" class="btn mr-3 <?php echo APP_BTN2;?>" data-dismiss="modal">
           <?php echo mt("Close")?>
          </button>
          <button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo mt("Send Push")?></button>
       </div>
       
      <!--</form>-->
      <?php echo CHtml::endForm() ; ?>
        
      </div><!-- content-->
 </div>
</div>

<div class="card" id="box_wrap">
<div class="card-body">


<div class="row action_top_wrap desktop button_small_wrap">   

<div class="col-md-6 col-sm-6">
	<button type="button" class="btn <?php echo APP_BTN?> " data-toggle="modal" data-target="#broadcastNewModal" >
	<?php echo translate("Add New")?> 
	</button>
	
	<button type="button" class="btn btn-raised refresh_datatables"  >
	<?php echo translate("Refresh")?> 
	</button>
</div> <!--col-->


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
   'placeholder'=>translate("Search Push title, message and merchant name"),
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
echo CHtml::hiddenField('details_id');
?> 

<table id="table_list" class="table data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo translate("ID")?></th>
    <th><?php echo translate("Push Title")?></th>
    <th><?php echo translate("Push Message")?></th>    
    <th ><?php echo translate("Merchant")?></th>
    <th ><?php echo translate("Topics")?></th>
    <th width="18%"><?php echo translate("Date")?></th>    
    <th width="18%"><?php echo translate("Process")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->


<div class="modal fade" id="broadcastNewModal" tabindex="-1" role="dialog" aria-labelledby="broadcastNewModal" aria-hidden="true">

 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo translate("Broadcast")?></h5></div>
        
        
        <?php echo CHtml::beginForm('','post',array(
		  'id'=>"frm_ajax",
		  'onsubmit'=>"return false;",
		  'data-action'=>"save_broadcast"
		)); 
		echo CHtml::hiddenField('device_platform',CHANNEL_TOPIC);
		echo CHtml::hiddenField('fcm_version',1);
		?> 
		
        <div class="modal-body">
        
	    
		<div class="form-group">
		<label><?php echo translate("Push Title")?></label>
		<?php 
		echo CHtml::textField('push_title','',array('class'=>"form-control",'required'=>true ));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo translate("Push Message")?></label>
		<?php 
		echo CHtml::textArea('push_message','',array('class'=>"form-control",'maxlength'=>"255",'required'=>true));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo translate("Merchant")?></label>
		<?php 
		echo CHtml::dropDownList('merchant_list','',array(
		  
		),array(
		  'class'=>'form-control ajax_merchant_list',
		  'required'=>true
		));
		?>
		</div> 
		
        
        </div> <!--modal body-->
        
        <div class="modal-footer">
          <button type="button" class="btn mr-3 <?php echo APP_BTN2?>" data-dismiss="modal">
           <?php echo translate("Close")?>
          </button>
          <button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo translate("Send broadcast")?></button>
       </div>
       
      <!--</form>-->
      <?php echo CHtml::endForm() ; ?>
        
      </div><!-- content-->
 </div>
</div>



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

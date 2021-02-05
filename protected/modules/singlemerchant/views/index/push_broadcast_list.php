
<div class="card" id="box_wrap">
<div class="card-body">


<div class="row action_top_wrap desktop button_small_wrap">   

<button type="button" class="btn <?php echo APP_BTN?> " data-toggle="modal" data-target="#broadcastNewModal" >
<?php echo st("Add New")?> 
</button>

<button type="button" class="btn btn-raised refresh_datatables"  >
<?php echo st("Refresh")?> 
</button>

<a href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/old_broadcast")?>" class="btn <?php echo APP_BTN2?>"  >
<?php echo st("Switch to old broadcast")?> 
</a>

   
</div> <!--action_top_wrap-->

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
echo CHtml::hiddenField('details_id');
?> 

<table id="table_list" class="table data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo st("Broadcast ID")?></th>
    <th><?php echo st("Push Title")?></th>
    <th><?php echo st("Push Message")?></th>    
    <th ><?php echo st("Merchant")?></th>
    <th ><?php echo st("Topics")?></th>
    <th width="18%"><?php echo st("Date")?></th>    
    <th width="18%"><?php echo st("Process")?></th>
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
        <div class="modal-header"> <h5 class="modal-title" ><?php echo st("Broadcast")?></h5></div>
        
        
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
		<label><?php echo st("Push Title")?></label>
		<?php 
		echo CHtml::textField('push_title','',array('class'=>"form-control",'required'=>true ));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo st("Push Message")?></label>
		<?php 
		echo CHtml::textArea('push_message','',array('class'=>"form-control",'maxlength'=>"255",'required'=>true));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo st("Merchant")?></label>
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
           <?php echo st("Close")?>
          </button>
          <button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo st("Send broadcast")?></button>
       </div>
       
      <!--</form>-->
      <?php echo CHtml::endForm() ; ?>
        
      </div><!-- content-->
 </div>
</div>



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

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",
  'onsubmit'=>"return false;"
)); 
?> 
<div class="card" id="box_wrap">
<div class="card-body">

<div class="row action_top_wrap desktop button_small_wrap">   

<div class="col-md-6 col-sm-6" >
<button type="button" class="btn <?php echo APP_BTN?> " data-toggle="modal" data-target="#broadcastNewModal" >
<?php echo mobileWrapper::t("Add new")?>
</button>

<button type="button" class="btn refresh_datatables <?php echo APP_BTN2;?>"  >
<?php echo mobileWrapper::t("Refresh")?>
</button>

<a href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/old_broadcast")?>" class="btn <?php echo APP_BTN2;?>"  >
<?php echo mobileWrapper::t("Switch to old broadcast")?>
</a>
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
<!--action_top_wrap-->

<table class="table data_tables table-hover" data-action_name="broadcast_list" >
 <thead>
  <tr>
   <th><?php echo mobileWrapper::t("ID")?></th>
   <th><?php echo mobileWrapper::t("Push Title")?></th>
   <th><?php echo mobileWrapper::t("Push Content")?></th>
   <th><?php echo mobileWrapper::t("Topics")?></th>      
   <th width="18%"><?php echo mobileWrapper::t("Date")?></th>      
   <th width="18%"><?php echo mobileWrapper::t("Process")?></th>      
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>

</div> <!--card body-->
</div> <!--card-->
<?php echo CHtml::endForm() ; ?>


<div class="modal fade" id="broadcastNewModal" tabindex="-1" role="dialog" aria-labelledby="broadcastNewModal" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo mt("Broadcast")?></h5></div>
        
        
        <?php echo CHtml::beginForm('','post',array(
		  'id'=>"frm",
		  'onsubmit'=>"return false;",
		  'data-action'=>"save_broadcast"
		)); 
		echo CHtml::hiddenField('device_platform',CHANNEL_TOPIC);
		echo CHtml::hiddenField('fcm_version',1);
		?> 
		
        <div class="modal-body">
        
	    
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
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
           <?php echo mt("Close")?>
          </button>
          <button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo mt("Send broadcast")?></button>
       </div>
       
      <!--</form>-->
      <?php echo CHtml::endForm() ; ?>
        
      </div><!-- content-->
 </div>
</div>



<div class="modal fade" id="errorDetails" tabindex="-1" role="dialog" aria-labelledby="errorDetails" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo mt("Details")?></h5></div>

        <div class="modal-body">
        <?php 
        echo CHtml::hiddenField('details_id');
        ?>
        <p class="error_details"></p>
        </div>

      </div><!-- content-->
 </div>
</div>

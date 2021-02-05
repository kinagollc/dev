
<div class="card" id="box_wrap">
<div class="card-body">


<div class="row action_top_wrap desktop button_small_wrap">   

<a href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/broadcast_new")?>" class="btn <?php echo APP_BTN?>"  >
<?php echo st("Add New")?> 
</a>

<button type="button" class="btn btn-raised refresh_datatables"  >
<?php echo st("Refresh")?> 
</button>

<a href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/push_broadcast")?>" class="btn <?php echo APP_BTN2?>"  >
<?php echo st("Switch to new broadcast")?> 
</a>
   
</div> <!--action_top_wrap-->

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
?> 

<table id="table_list" class="table  data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo st("Broadcast ID")?></th>
    <th><?php echo st("Push Title")?></th>
    <th><?php echo st("Push Message")?></th>
    <th><?php echo st("Merchant")?></th>
    <th ><?php echo st("Platform")?></th>
    <th><?php echo st("Date")?></th>    
    <th><?php echo st("Actions")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->

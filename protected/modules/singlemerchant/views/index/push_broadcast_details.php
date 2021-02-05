
<div class="card" id="box_wrap">
<div class="card-body">


<form id="frm_table" method="POST" >
<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
echo CHtml::hiddenField('broadcast_id',$broadcast_id)
?> 

<table id="table_list" class="table data_tables">
 <thead>
  <tr>
    <th width="5%"><?php echo SingleAppClass::t("ID")?></th>
    <th><?php echo SingleAppClass::t("PushType")?></th>
    <th><?php echo SingleAppClass::t("Name")?></th>
    <th ><?php echo SingleAppClass::t("Platform")?></th>
    <th><?php echo SingleAppClass::t("Title")?></th>    
    <th><?php echo SingleAppClass::t("Message")?></th>
    <th><?php echo SingleAppClass::t("Date")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>

</div> <!--card body-->
</div> <!--card-->


<div class="floating_action">


<a  href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/old_broadcast")?>" 
class="btn <?php echo APP_BTN2?>"  >
 <?php echo st("BACK")?> 
</a>

 <button type="button" class="btn <?php echo APP_BTN?> refresh_datatables"  >
 <?php echo st("Refresh")?> 
 </button>
</div>
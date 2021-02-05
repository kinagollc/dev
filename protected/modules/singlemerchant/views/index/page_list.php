


<div class="row action_top_wrap desktop button_small_wrap">   

<button type="button" class="btn <?php echo APP_BTN?>" data-toggle="modal" data-target="#pageNewModal">
 <?php echo st("Add New")?>
 </button>

<button type="button" class="btn btn-raised refresh_datatables"  >
<?php echo st("Refresh")?> 
</button>
   
</div> <!--action_top_wrap-->

<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_table",		  		  
)); 
echo CHtml::hiddenField('merchant_id',$merchant_id);
?> 
 
<table id="table_list" class="table table-striped data_tables" style="width:100%;">
 <thead>
  <tr>
    <th width="5%"><?php echo st("ID")?></th>
    <th><?php echo st("Title")?></th>
    <th><?php echo st("Content")?></th>
    <th ><?php echo st("Icon")?></th>
    <th><?php echo st("HTML format")?></th>    
    <th><?php echo st("Sequence")?></th>
    <th><?php echo st("Date")?></th>
    <th><?php echo st("Actions")?></th>
  </tr>
 </thead>
 <tbody>  
 </tbody>
</table>
<?php echo CHtml::endForm() ; ?>


<?php
$this->renderPartial('/index/page_add',array(	
	'merchant_id'=>$merchant_id
));
?>
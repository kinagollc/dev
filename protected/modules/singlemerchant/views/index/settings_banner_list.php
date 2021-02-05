
<a href="" class="btn btn-primary "><?php echo SingleAppClass::t("Add")?></a>

<div class="table_wrap">
<form id="frm_table" method="POST" class="form-inline" >
<?php echo CHtml::hiddenField('action','bannerList')?>

<table id="table_list" class="table table-hover">
<thead>
  <tr>
    <th width="10%"><?php echo SingleAppClass::t("ID")?></th>
    <th><?php echo SingleAppClass::t("Preview")?></th>            
    <th><?php echo SingleAppClass::t("Status")?></th>
    <th><?php echo SingleAppClass::t("Action")?></th>
  </tr>
</thead>
<tbody> 
</tbody>
</table>
</form>
</div>

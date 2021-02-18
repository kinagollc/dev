<div style="max-width:60%;">
<table class="uk-table"> 
 <thead>
 <tr>
  <th colspan="2"><?php echo t("Table")?></th>
  <td><?php echo t("Actions")?></td>
 </tr>
 </thead>
 
 <?php if(is_array($data) && count($data)>=1):?>
 <?php foreach ($data as $val):?>
   <?php $total_item = (integer)$val['total']+0;?>
   <td width="33.3%" ><?php echo t($val['title'])?></td>
   <td width="20.3%"><?php echo $total_item;?></td>
   <td width="33.3%">
    <?php if($total_item>0):?>
    <div class="migration_<?php echo $val['id']?> uk-text-success">
    <a href="javascript:;" class="migration uk-text-danger" data-id="<?php echo $val['id']?>"><?php echo t("Click here to update")?></a>
    </div>
    <input type="hidden" name="migration_<?php echo $val['id']?>_total" class="migration_<?php echo $val['id']?>_total" value="<?php echo $total_item+0?>" >
    <input type="hidden" name="migration_<?php echo $val['id']?>_count" class="migration_<?php echo $val['id']?>_count" value="0" >
    <?php else :?>
    <div class="uk-text-success"><?php echo t("Done")?></div>
    <?php endif;?>
  </td>
 </tr>
 
 <?php endforeach;?>
 <?php endif;?>
 
</table>
</div>
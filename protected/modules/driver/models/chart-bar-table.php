
<table class="table top30 table-hover table-striped">
 <thead>
  <tr>
   <th class="text-muted"><?php echo Driver::t("Name")?></th>
   <th class="text-muted"><?php echo Driver::t("Successful Tasks")?></th>
   <th class="text-muted"><?php echo Driver::t("Cancelled Tasks")?></th>
   <th class="text-muted"><?php echo Driver::t("Failed Tasks")?></th>
   <th class="text-muted"><?php echo Driver::t("Total Tasks")?></th>
   <th class="text-muted"><?php echo Driver::t("Total")." ".Yii::app()->functions->getCurrencyCode()?></th>
  </tr>
 </thead>
 <tbody>
 <?php if (is_array($data) && count($data)>=1):?>
 <?php foreach ($data as $val):?>
 <?php   
   $total = 0;
   if (isset($val['successful'])){
   	  $total+=$val['successful'];
   }
   if (isset($val['cancelled'])){
   	  $total+=$val['cancelled'];
   }
   if (isset($val['failed'])){
   	  $total+=$val['failed'];
   }     
 ?>
  <tr>
    <td><?php echo $val['driver_name']?></td>
    <td><?php echo isset($val['successful'])?$val['successful']:''?></td>
    <td><?php echo isset($val['cancelled'])?$val['cancelled']:''?></td>
    <td><?php echo isset($val['failed'])?$val['failed']:''?></td>
    <td><?php echo $total?></td>    
    <td><b><?php echo Driver::prettyPrice($val['total_order_amount'])?></b></td>
  </tr>
  <?php endforeach;?>
  
  <?php else :?>
  <tr>
   <td colspan="5"><?php echo Driver::t("No results")?></td>
  </tr>
  <?php endif;?>
  
  
 </tbody>
</table>
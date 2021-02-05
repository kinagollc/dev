
<div class="card" id="box_wrap">
<div class="card-body">



<ul class="nav nav-tabs" id="tab_others" role="tablist">

 
  <li class="nav-item">
    <a class="nav-link active"  data-toggle="tab" 
    href="#nav_cron" role="tab" aria-selected="true">
    <?php echo translate("Cron Jobs")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_update" role="tab" aria-selected="true">
    <?php echo translate("Update Database")?>
    </a>
  </li>
  
</ul>

<div class="tab-content" >  
  
  
  <div class="tab-pane fade show active" id="nav_cron" role="tabpanel">
   <p><?php echo translate("Run the following cron jobs in your cpanel")?></p>
   <?php if(is_array($cron) && count($cron)>=1):?>
   <ul>      
     <?php foreach ($cron as $val):?>
      <li><a href="<?php echo $val['link']?>" target="_blank"><?php echo $val['link']?></a> - <?php echo $val['notes']?></li>
     <?php endforeach;?>
   </ul>
   <?php endif;?>
   
   <p><?php echo translate("Or below to minimize the cron jobs in your cpanel")?></p>
   
   <?php if(is_array($cron_min) && count($cron_min)>=1):?>
   <ul>      
     <?php foreach ($cron_min as $val):?>
      <li><a href="<?php echo $val['link']?>" target="_blank"><?php echo $val['link']?></a> - <?php echo $val['notes']?></li>
     <?php endforeach;?>
   </ul>
   <?php endif;?>
   
   <p><?php echo translate("Example")?>:<br/>
   curl <?php echo $cron_sample?>
   </p>
   
   <p><?php echo translate("Video tutorial")?><br/>
   <a href="https://youtu.be/7lrNECQ5bvM" target="_blank">https://youtu.be/7lrNECQ5bvM</a>
   </p>
   
  </div> <!--tab pane-->
  
  <div class="tab-pane fade" id="nav_update" role="tabpanel">
    <p><?php echo translate("click [link] to update your database",array(
      '[link]'=>'<a href="'.$update_db.'" target="_blank" >'.translate("here").'</a>'
    ))?></p>
  </div> <!--tab pane-->
  
</div> <!-- tab content-->
  

</div> <!--card body-->
</div> <!--card-->
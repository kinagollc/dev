
<div class="card" id="box_wrap">
<div class="card-body">



<ul class="nav nav-tabs" id="tab_others" role="tablist">

 
  <li class="nav-item">
    <a class="nav-link active"  data-toggle="tab" 
    href="#nav_cron" role="tab" aria-selected="true">
    <?php echo st("Cron Jobs")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_update" role="tab" aria-selected="true">
    <?php echo st("Update Database")?>
    </a>
  </li>
  
</ul>

<div class="tab-content" >  
  
  
  <div class="tab-pane fade show active" id="nav_cron" role="tabpanel">
   <p><?php echo st("Run the following cron jobs in your cpanel")?></p>
   <?php if(is_array($cron) && count($cron)>=1):?>
   <ul>      
     <?php foreach ($cron as $val):?>
      <li><a href="<?php echo $val['link']?>" target="_blank"><?php echo $val['link']?></a> - <?php echo $val['notes']?></li>
     <?php endforeach;?>
   </ul>
   <?php endif;?>
   
   <p><?php echo st("Example")?>:<br/>
   curl <?php echo $cron_sample?>
   </p>
   
   <p><?php echo st("Video tutorial")?><br/>
   <a href="https://youtu.be/eKDDD_BqAH0" target="_blank">https://youtu.be/eKDDD_BqAH0</a>
   </p>
   
  </div> <!--tab pane-->
  
  <div class="tab-pane fade" id="nav_update" role="tabpanel">
    <p><?php echo st("click [link] to update your database",array(
      '[link]'=>'<a href="'.$update_db.'" target="_blank" >'.st("here").'</a>'
    ))?></p>
  </div> <!--tab pane-->
  
</div> <!-- tab content-->
  

</div> <!--card body-->
</div> <!--card-->
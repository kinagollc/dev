<div class="page_header">
  <div class="col"><div class="rounded-circle"><i class="fa fa-cog"></i></div></div>
  <div class="col">
    <h2><?php echo mt("Settings")?></h2>   
    <h6><?php echo $settings_title?></h6>	   
  </div>
</div>

<ul class="list-group pt-4">	  
<?php if(is_array($menu) && count($menu)>=1): ?>
<?php foreach ($menu as $val): ?>

<a href="<?php echo Yii::app()->createUrl($val['link'])?>" class="list-group-item
  <?php echo Yii::app()->controller->action->id==$val['id']?"active":'';?>">
  <?php echo mt($val['label'])?>
</a>  

<?php endforeach;?>
<?php endif;?>
</ul>
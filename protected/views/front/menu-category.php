<?php if(is_array($menu) && count($menu)>=1):?>
<div class="category">
<?php foreach ($menu as $val): ?>
 <a href="javascript:;" class="category-child relative 
<?php echo $menu_lazyload==1?"lazy_load_item":"goto-category"?>
" data-id="cat-<?php echo $val['category_id']?>"
  data-cat_id="<?php echo $val['category_id'];?>" 
  data-total_cat_paginate="<?php echo $val['total_cat_paginate']>0?$val['total_cat_paginate']:1;?>" 
 >
  
 <?php if($show_image_category==1):?>
	 <?php $cat_image = FunctionsV3::getImage($val['photo'], true);?> 
	 <?php if(!empty($cat_image)):?>
	 <img src="<?php echo $cat_image;?>" class="avatar" />
	 <?php endif;?>
 <?php endif;?>
   
  <?php echo $val['category_name'];?>
  <?php if($menu_lazyload==1):?>
  <span>(<?php echo (integer)$val['total_item_in_category']?>)</span>
  <?php else :?>
  <span>(<?php echo is_array($val['item'])?count($val['item']):'0';?>)</span>
  <?php endif;?>
  <i class="ion-ios-arrow-right"></i>
 </a>
<?php endforeach;?>
</div>
<?php endif;?>
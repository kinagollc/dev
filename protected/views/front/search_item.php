<div class="menu-1 box-grey rounded items-row " style="margin-top:0;">

<?php if(is_array($data) && count($data)>=1):?>

<?php foreach ($data as $val):?>

<h2 class="text-left menu-cat cat-1"><?php echo $val['category_name']?></h2>

<div class="row">
<div class="col-md-3 col-xs-3">
<img src="<?php echo FunctionsV3::getFoodDefaultImage($val['photo'],false)?>">
</div>
<div class="col-md-6 col-xs-6">
  <p class="small"><?php echo $val['item_name']?></p>
  <p class="small"><?php echo $val['item_description']?></p>
</div>
<div class="col-md-3 col-xs-3">
<?php 
$this->widget('application.components.Widget_price',array(
 'price'=> $val['prices']
));
?> 
</div>

</div> <!--row-->


<?php                    
$atts='';	    
if ( $val['single_item']==2){
	  $atts.='data-price="'.$val['single_details']['price'].'"';
	  $atts.=" ";
	  $atts.='data-size="'.$val['single_details']['size'].'"';
	  $atts.=" ";
	  if(isset($val['single_details']['size_id'])){
	     $atts.='data-size_id="'.$val['single_details']['size_id'].'"';
	  }
	  $atts.=" ";
	  $atts.='data-discount="'.$val['discount'].'"';
	  $atts.=" ";
	  if(isset($val['single_details']['item_size_token'])){
	     $atts.='data-item_size_token="'.$val['single_details']['item_size_token'].'"';			  
	  }
}
?>       

<div class="row">
 <div class="col-md-9"></div>
 <div class="col-md-3">
   <a href="javascript:;" 
    class="orange-button btn rounded3 menu-item <?php echo $val['not_available']==2?"item_not_available":''?>"
    
    rel="<?php echo $val['item_id']?>"
    data-single="<?php echo $val['single_item']?>" 
    <?php echo $atts;?>
    data-category_id="<?php echo $val['category_id']?>"
    
   >
   <?php echo t("Add to cart")?>
   </a>
 </div>
</div>

<?php endforeach;?>

<?php else :?>
<p><?php echo  Yii::t("default","Sorry, we couldn't find any results matching [string]",array(
			 '[string]'=>"<b>".clearString($search_string)."</b>"
			))?></p>
<?php endif;?>
</div>
<!--menu-1-->
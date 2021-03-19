<?php
$show_delivery_info=false;
if($val['service']==1 || $val['service']==2  || $val['service']==4  || $val['service']==5 ){
	$show_delivery_info=true;
}

?>
<div id="search-listgrid" class="infinite-item <?php echo $delivery_fee!=true?'free-wrap':'non-free'; ?>">
    <div class="inner list-view">
    
    <?php if ( $val['is_sponsored']==2):?>
    <div class="ribbon"><span><?php echo t("Sponsored")?></span></div>
    <?php endif;?>
    
    <?php if ($offer=FunctionsV3::getOffersByMerchant($merchant_id)):?>
    <div class="ribbon-offer"><span><?php echo $offer;?></span></div>
    <?php endif;?>
    
    <div class="row">
     <a href="<?php echo Yii::app()->createUrl("/menu/". trim($val['restaurant_slug']))?>">
        <img src="<?php echo FunctionsV3::getMerchantHeader($merchant_id);?>" style="width:100%;height:100px;object-fit:cover;padding:20px;">
        </a>
	    <div class="col-md-2 border ">
	     <!--<a href="<?php echo Yii::app()->createUrl('store/menu/merchant/'.$val['restaurant_slug'])?>">-->
	     <a href="<?php echo Yii::app()->createUrl("/menu/". trim($val['restaurant_slug']))?>">
	      <img class="logo-small"src="<?php echo FunctionsV3::getMerchantLogo($merchant_id);?>">
	     </a>	          
	    </div> <!--col-->
	    
	    <div class="col-md-7 border">
	     
	       
	       <h2><?php echo clearString($val['restaurant_name'])?></h2>
	       	       <p class="cuisine">
           <?php echo FunctionsV3::displayCuisine($val['cuisine']);?>
           </p>     
	       <p class="merchant-address concat-text" style="margin-top:-10px"><?php echo $val['merchant_address']?></p> 
	         
	       	       <div class="mytable">
	         <div class="mycol">
	            <div class="rating-stars" data-score="<?php echo $ratings['ratings']?>"></div>   
	         </div>
	         <div class="mycol">
	            <?php if(is_array($ratings) && count($ratings)>=1):?>
	            <p><?php echo $ratings['votes']." ".t("Reviews")?></p>
	            <?php endif;?>
	         </div>
	         <div class="mycol"> 
	            <?php echo FunctionsV3::merchantOpenTag($merchant_id)?>                
	         </div>
	         
	         <?php if($show_delivery_info):?>
	         <div class="mycol">	          
	          <p><?php echo t("Minimum Order").": ".Price_Formatter::formatNumber($min_fees)?></p>
	         </div>
	         <?php endif;?>
	         
	         <div class="mycol">
	            <a href="javascript:;" data-id="<?php echo $val['merchant_id']?>"  title="<?php echo t("add to your favorite restaurant")?>" class="add_favorites <?php echo "fav_".$val['merchant_id']?>"><i class="ion-android-favorite-outline"></i></a>
	         </div>
	         
	       </div> <!--mytable-->
	       
	       	       	                 
                                                       
           <p>
	        <?php 	        
	        if(!$search_by_location){		        
		        echo Yii::t("default","<i class='fa fa-location-arrow' aria-hidden='true'></i> [distance]",array(
		          '[distance]'=>$distance
		        ));
	        }
	        ?>
	        </p>
	        	        
	        <?php if($show_delivery_info):?>
	        <p><?php echo t("<i class='fa fa-clock-o' aria-hidden='true'></i> ")?><?php echo !empty($val['delivery_estimation'])?$val['delivery_estimation']:t("not available")?></p>
	        <?php endif;?>
	        
	                                
	        <p>
	        <?php 	        
	        if($show_delivery_info){
		        if ($delivery_fee>0){
		             echo t("<i class='fa fa-motorcycle' aria-hidden='true'></i> ")." ".Price_Formatter::formatNumber($delivery_fee);
		        } else echo  t("<i class='fa fa-motorcycle' aria-hidden='true'></i> ")." ".t("Free Delivery");
	        }
	        ?>
	        </p>
	        
	        <?php if(method_exists('FunctionsV3','getOffersByMerchantNew')):?>
	        <?php if ($offer=FunctionsV3::getOffersByMerchantNew($merchant_id)):?>
	          <?php foreach ($offer as $offer_value):?>
	            <p><?php echo $offer_value?></p>
	          <?php endforeach;?>
	        <?php endif;?>
	        <?php endif;?>
	        
	        <p class="top15"><?php echo FunctionsV3::getFreeDeliveryTag($merchant_id)?></p>
	        
	    
	    </div> <!--col-->
	    
	    <div class="col-md-3 relative border">
	    
	      <!--<a href="<?php echo Yii::app()->createUrl('store/menu/merchant/'.$val['restaurant_slug'])?>" -->
	      <a href="<?php echo Yii::app()->createUrl("/menu/". trim($val['restaurant_slug']))?>" 
         class="orange-button rounded3 medium">
          <?php echo t("Browse")?>
         </a>   
	    
	    </div>
    </div> <!--row-->
    
    </div> <!--inner-->
</div>  <!--infinite-item-->   
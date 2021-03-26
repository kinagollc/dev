
<div class="mobile-banner-wrap relative">
 <div class="layer"></div>
 <img class="mobile-banner" src="<?php echo empty($background)?assetsURL()."/images/b-2-mobile.jpg":uploadURL()."/$background"; ?>">
</div>
<div id="parallax-wrap" class="parallax-search parallax-menu"  
data-parallax="scroll" data-position="top" data-bleed="10" 
data-image-src="<?php echo empty($background)?assetsURL()."/images/b-2.jpg":uploadURL()."/$background"; ?> ">
<div class="search-wraps center menu-header">

      <img class="logo-medium bottom15" src="<?php echo $merchant_logo;?>">
      <h1><?php echo clearString($restaurant_name)?></h1>
      
	<p><i class="fa fa-map-marker"></i> <?php echo $merchant_address?></p>
		<p><i class="fa fa-phone"></i> <a href="tel:<?php echo $restaurant_phone?>"><?php echo $restaurant_phone?></a></p>
		<div class="mytable">
	     <div class="mycol">
	        <div class="rating-stars" data-score="<?php echo $ratings['ratings']?>"></div>   
	     </div>
	     <div class="mycol">
	        <p class="small">
	        <a href="javascript:;"class="goto-reviews-tab">
	        <?php echo $ratings['votes']." ".t("Reviews")?>
	        </a>
	        </p>
	     </div>	        
	     <div class="mycol">
	        <?php echo FunctionsV3::merchantOpenTag($merchant_id)?>             
	     </div>
	     <div class="mycol">
	        <?php if($minimum_order>0):?>
            <p class="small"><?php echo t("Minimum Order").": ".Price_Formatter::formatNumber($minimum_order)?></p>
            <?php endif;?>
	     </div>
	     
	     <div class="mycol">
	        <a href="javascript:;" data-id="<?php echo $merchant_id?>"  title="<?php echo t("add to your favorite places")?>" class="add_favorites <?php echo "fav_".$merchant_id?>"><i class="ion-android-favorite-outline"></i></a>
	     </div>
	     
	   </div>
	   <div class="mytable">
          <div class="mycol a"><p class="buzname concat-text shiftleft"><?php echo clearString($val['restaurant_name'])?></p>
         <p class="concat-text3 shiftleft">
        <?php echo FunctionsV3::displayCuisine($val['cuisine']);?>
        </p></div>
        
          <div class="mycol b">
          <div class="equal_table">
          
         <div class="col">
            <div align="right"><?php echo FunctionsV3::merchantOpenTag($merchant_id)?></div>
         </div>          
        </div>
          </div>
        </div>
         <!--mytable-->

        <div class="mytable" style="margin-top:-20px;text-align:left!important;padding:5px">
	        <div class="mycol">
	        <?php 	        
	        if(!$search_by_location){		        
		        echo Yii::t("default","<i class='fa fa-location-arrow' aria-hidden='true'></i> [distance]",array(
		          '[distance]'=>$distance
		        ));
	        }
	        ?></div>
	         <div class="mycol">
	            	        <?php if($show_delivery_info):?>
	        <p><?php echo t("<i class='fa fa-clock-o' aria-hidden='true'></i> ")?><?php echo !empty($val['delivery_estimation'])?$val['delivery_estimation']:t("not available")?></p>
	        <?php endif;?>
	         </div>
	         <div class="mycol"> 
	            <p>
	        <?php 	        
	        if($show_delivery_info){
		        if ($delivery_fee>0){
		             echo t("<i class='fa fa-motorcycle' aria-hidden='true'></i> ")." ".Price_Formatter::formatNumber($delivery_fee);
		        } else echo  t("<i class='fa fa-motorcycle' aria-hidden='true'></i> ")." ".t("Free Delivery");
	        }
	        ?>
	        </p>               
	         </div>
	         
	         <?php if($show_delivery_info):?>
	         <div class="mycol">	          
	          <p><?php echo t("<i class='fa fa-cart-plus' aria-hidden='true'></i> Min").": ".Price_Formatter::formatNumber($min_fees)?></p>
	         </div>
	         <?php endif;?>
	         
	         
	         <div class="mycol">
	            <?php if(method_exists('FunctionsV3','getOffersByMerchantNew')):?>
	        <?php if ($offer=FunctionsV3::getOffersByMerchantNew($merchant_id)):?>
	          <?php foreach ($offer as $offer_value):?>
	            <p><?php echo $offer_value?></p>
	          <?php endforeach;?>
	        <?php endif;?>
	        <?php endif;?>
	        
	        <p class="top15"><?php echo FunctionsV3::getFreeDeliveryTag($merchant_id)?></p>
	         </div>
	         
	       </div> 
        
	 <!--mytable-->

	
	<?php if(!empty($social_facebook_page) || !empty($social_twitter_page) || !empty($social_google_page)):?>
	<ul class="merchant-social-list">
	 <?php if(!empty($social_google_page)):?>
	 <li>
	   <a href="<?php echo FunctionsV3::prettyUrl($social_google_page)?>" target="_blank">
	    <i class="ion-social-googleplus"></i>
	   </a>
	 </li>
	 <?php endif;?>
	 
	 <?php if(!empty($social_twitter_page)):?>
	 <li>
	   <a href="<?php echo FunctionsV3::prettyUrl($social_twitter_page)?>" target="_blank">
	   <i class="ion-social-twitter"></i>
	   </a>
	 </li>
	 <?php endif;?>
	 
	 <?php if(!empty($social_facebook_page)):?>
	 <li>
	   <a href="<?php echo FunctionsV3::prettyUrl($social_facebook_page)?>" target="_blank">
	   <i class="ion-social-facebook"></i>
	   </a>
	 </li>
	 <?php endif;?>
	 
	</ul>
	<?php endif;?>
	
	
	<?php if (!empty($merchant_website)):?>
	<p class="small" style="display:none;">
	<?php echo t("Website").": "?>
	<a target="_blank" href="<?php echo FunctionsV3::fixedLink($merchant_website)?>">
	  <?php echo $merchant_website;?>
	</a>
	</p>
	<?php endif;?>
			
</div> <!--search-wraps-->

</div> <!--parallax-container-->
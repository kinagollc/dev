
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
	     <div class="mycol">
	     <div id="qrcode"></div>
	     </div>
	   </div>
	   
	   
		<p style="padding-bottom:5px;padding-top:15px;"><?php echo FunctionsV3::getFreeDeliveryTag($merchant_id)?></p>
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
	<p class="small">
	  <?php echo $merchant_website;?>
	</p>
	<?php endif;?>
			
</div> <!--search-wraps-->

</div> <!--parallax-container-->
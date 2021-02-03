<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 

<div class="row">
  <div class="col-md-5">
    
<div class="form-group">    
    <p><b><?php echo mobileWrapper::t("App Default Language")?></b></p> 
    <?php 
    $lang_list[0]=mobileWrapper::t("Please select");
    $enabled_lang=FunctionsV3::getEnabledLanguage();	    
    if(is_array($enabled_lang) && count($enabled_lang)>=1){
    	foreach ($enabled_lang as $val) {
    		$lang_list[$val]=$val;
    	}
    }
    	    
    echo CHtml::dropDownList('mobileapp2_language',getOptionA('mobileapp2_language'),
    (array)$lang_list,array(
      'class'=>"form-control"
    ));
    ?>    
    <small class="form-text text-muted">
      <?php echo mobileWrapper::t("set application default language")?>
    </small>
</div>  
  
  </div> <!--col-->
  
  <div class="col-md-5">
    
<div class="form-group">    
    <p><b><?php echo mobileWrapper::t("Location Accuracy")?></b></p>        
     <?php 
    echo CHtml::dropDownList('mobileapp2_location_accuracy',getOptionA('mobileapp2_location_accuracy'),
    mobileWrapper::locationAccuracyList()
    ,array(
      'class'=>"form-control"
    ));
    ?>     
</div>  
  
  </div> <!--col-->
  
</div> <!--row-->


<div class="height10"></div>

<p><b><?php echo mobileWrapper::t("Home Page")?></b></p>
<div class="row">
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_banner','',"Enabled Banner", getOptionA('mobile2_home_banner') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_offer','',"Enabled Offers", getOptionA('mobile2_home_offer') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_featured','',"Enabled Feature Restaurant", getOptionA('mobile2_home_featured') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_cuisine','',"Enabled Browse By Cuisine", getOptionA('mobile2_home_cuisine') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_all_restaurant','',"Enabled All Restaurant", getOptionA('mobile2_home_all_restaurant') );?>   
  </div> <!--col-->
  
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_favorite_restaurant','',"Enabled Favorites Restaurant", getOptionA('mobile2_home_favorite_restaurant') );?>   
  </div> <!--col-->
   
</div> <!--row-->

<div class="height10"></div>

<div class="row">

 <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_banner_full','',"Enabled home banner full width", getOptionA('mobile2_home_banner_full') );?>   
  </div> <!--col-->
  
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_food_discount','',"Enabled Food With Discount", getOptionA('mobile2_home_food_discount') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_categories','',"Enabled home categories", getOptionA('mobile2_home_categories') );?>   
  </div> <!--col-->

  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_use_view_all','',"Enabled View all words", getOptionA('mobile2_home_use_view_all') );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_home_all_as_list','',"Enabled All restaurant as list", getOptionA('mobile2_home_all_as_list') );?>   
  </div> <!--col-->

</div> <!--row-->

<div class="height10"></div>
<div class="height10"></div>

<p><b><?php echo mobileWrapper::t("Home settings")?></b></p>

<div class="row">
  
  <div class="col-md-4">
  <?php echo htmlWrapper::checkbox('mobile2_home_banner_auto_scroll','',"Banner auto scroll", getOptionA('mobile2_home_banner_auto_scroll') );?>   
  </div>
</div>  

<div class="height10"></div>
<div class="height10"></div>

<p><b><?php echo mobileWrapper::t("Menu/List Style")?></b></p>

<div class="row">
   
<div class="col-md-5">
<div class="form-group">
    <label><?php echo mobileWrapper::t("Restaurant List Type")?></label>        
     <?php 
    echo CHtml::dropDownList('mobileapp2_merchant_list_type',getOptionA('mobileapp2_merchant_list_type'),
    mobileWrapper::RestaurantListType()
    ,array(
      'class'=>"form-control"
    ));
    ?>     
</div>  
</div> <!--col-->


<div class="col-md-5">
<div class="form-group">
    <label><?php echo mobileWrapper::t("Menu Type")?></label>        
     <?php 
    echo CHtml::dropDownList('mobileapp2_merchant_menu_type',getOptionA('mobileapp2_merchant_menu_type'),
    mobileWrapper::MenuType()
    ,array(
      'class'=>"form-control"
    ));
    ?>     
</div>  
</div> <!--col-->
   
</div>



<p style="margin-bottom:0;"><b><?php echo mobileWrapper::t("Distance results")?></b></p>
<div class="height20"></div>

<div class="row">
<div class="col-md-6">
  <?php echo htmlWrapper::checkbox('use_api_only_checkout','',"Use Map provider api only during checkout", getOptionA('use_api_only_checkout'),1 );?>   
  <small class="form-text text-muted">
 <?php echo mobileWrapper::t("For less google/mapbox api calls")?>
</small>
  </div> <!--col-->
</div>
<!--row-->

<div class="height20"></div>

<p style="margin-bottom:0;"><b><?php echo mobileWrapper::t("Search Results Data")?></b></p>
<small class="form-text text-muted">
 <?php echo mobileWrapper::t("choose less data for faster results")?>
</small>
<div class="height20"></div>

<div class="row">
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[1]','',"Open/Close", $search_options,'open_tag' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[2]','',"Reviews", $search_options ,'review' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[3]','',"Cuisine", $search_options, 'cuisine' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[4]','',"Address", $search_options , 'address' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[5]','',"Minimum Order", $search_options , 'minimum_order' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[6]','',"Distance", $search_options , 'distace' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[12]','',"Delivery Est", $search_options , 'delivery_estimation' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[7]','',"Delivery Distance", $search_options , 'delivery_distance' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[8]','',"Delivery Fee", $search_options , 'delivery_fee' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[9]','',"Offers", $search_options , 'offers' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[10]','',"Services", $search_options , 'services' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[11]','',"Payment Options", $search_options , 'payment_option' );?>   
  </div> <!--col-->
  
  <div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_search_data[13]','',"Vouchers", $search_options , 'voucher' );?>   
  </div> <!--col-->
  
</div> <!--row-->



<div class="height20"></div>

<p><b><?php echo mobileWrapper::t("Menu")?></b></p>

<div class="row">

<div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_disabled_default_image','',"Disabled default menu image", getOptionA('mobile2_disabled_default_image') );?>   
</div> <!--col-->

<div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_enabled_menu_carousel','',"Enabled Carousel", getOptionA('mobile2_enabled_menu_carousel') );?>   
</div> <!--col-->

<div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_enabled_dish','',"Enabled Dishes", getOptionA('mobile2_enabled_dish') );?>   
</div> <!--col-->

<div class="col-md-2">
  <?php echo htmlWrapper::checkbox('mobile2_disabled_image_menu1','',"Disabled image Menu 1", getOptionA('mobile2_disabled_image_menu1') );?>   
</div> <!--col-->

<div class="col-md-3">
  <?php echo htmlWrapper::checkbox('mobile2_enabled_addon_desc','',"Enabled Addon Description", getOptionA('mobile2_enabled_addon_desc') );?>   
</div> <!--col-->

</div> <!--row-->

<div class="height20"></div>

<div class="row">
<div class="col-md-3">
  <?php echo htmlWrapper::checkbox('mobile2_cart_with_qty','',"Enabled qty on cart", getOptionA('mobile2_cart_with_qty') );?>   
</div> <!--col-->

<div class="col-md-3">
  <?php echo htmlWrapper::checkbox('mobile2_food_share','',"Enabled food share", getOptionA('mobile2_food_share') );?>   
</div> <!--col-->

</div> <!--row-->

<div class="height20"></div>


<p><b><?php echo mobileWrapper::t("Customer Order History")?></b></p>


<div class="row">
	<div class="col-md-4">
     <p><?php echo mt("Processing")?></p>
	 <?php 
	 unset($order_status_list[0]);	 
	 echo CHtml::dropDownList('mobileapp2_order_processing',(array)json_decode(getOptionA('mobileapp2_order_processing')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
	<div class="col-md-4">
	  <p><?php echo mt("Completed")?></p>
	 <?php 
	 echo CHtml::dropDownList('mobileapp2_order_completed',(array)json_decode(getOptionA('mobileapp2_order_completed')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
	<div class="col-md-4">
	  <p><?php echo mt("Cancelled")?></p>
	 <?php 
	 echo CHtml::dropDownList('mobileapp2_order_cancelled',(array)json_decode(getOptionA('mobileapp2_order_cancelled')),
    (array)$order_status_list,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ));
	 ?>	
	</div> <!--col-->
	
</div>	

<div class="height20"></div>

<p><b><?php echo mobileWrapper::t("Registration Settings")?></b></p>
<div class="row">

    <div class="col-md-3">
    <?php     
    echo CHtml::dropDownList('mobileapp2_prefix',getOptionA('mobileapp2_prefix'),
    (array)mobileWrapper::mobileCodeList(),array(
      'class'=>"form-control"
    ));
    ?>
    <small class="form-text text-muted">
      <?php echo mobileWrapper::t("Default Phone Prefix")?>
    </small>
    </div>
    
	<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_turnoff_prefix','',"Turn off mobile prefix", getOptionA('mobileapp2_turnoff_prefix') );?>   	
	</div>
	
	<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_reg_email','',"Customer Register via Email", getOptionA('mobileapp2_reg_email') );?>   	
	</div>
	
	<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_reg_phone','',"Customer Register via Phone", getOptionA('mobileapp2_reg_phone') );?>   	
	</div>
		
	
</div> <!--row-->

<div class="height20"></div>

<p><b><?php echo mobileWrapper::t("Tracking Settings")?></b></p>

<div class="row">

<div class="col-md-3">
<?php     
echo CHtml::dropDownList('mobileapp2_tracking_theme',getOptionA('mobileapp2_tracking_theme'),
(array)mobileWrapper::trackingTheme(),array(
  'class'=>"form-control"
));
?>
<small class="form-text text-muted">
  <?php echo mobileWrapper::t("Tracking Theme")?>
</small>
</div>

<div class="col-md-3">
<?php echo CHtml::textField('mobileapp2_tracking_interval', getOptionA('mobileapp2_tracking_interval'),
array('class'=>"numeric_only form-control","placeholder"=>mt("Track Interval") ));?>
<small class="form-text text-muted">
  <?php echo mobileWrapper::t("In Millisecond default is 7000, Minimum is 5000")?>
</small>
</div>

    
</div> <!--row-->

<div class="height20"></div>

<p><b><?php echo mobileWrapper::t("Cart Settings")?></b></p>

<div class="row">

<div class="col-md-3">
<?php     
echo CHtml::dropDownList('mobileapp2_cart_theme',getOptionA('mobileapp2_cart_theme'),
(array)mobileWrapper::cartTheme(),array(
  'class'=>"form-control"
));
?>
</div>

<!--<div class="col-md-2">
	<?php echo htmlWrapper::checkbox('mobileapp2_cart_auto_address','',"Auto Set Address", getOptionA('mobileapp2_cart_auto_address') );?>   	
</div>-->

<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_future_order_confirm','',"Enabled Future Order Confirmation", getOptionA('mobileapp2_future_order_confirm') );?>   	
</div>

</div><!-- row-->

<div class="height20"></div>
<p><b><?php echo mobileWrapper::t("Others")?></b></p>


<div class="row">
  
<div class="col-md-3">
<?php     
echo CHtml::dropDownList('mobileapp2_custom_pages_position',getOptionA('mobileapp2_custom_pages_position'),
(array)array(
  1=>mt("Account"),
  2=>mt("Account -> settings"),
),array(
  'class'=>"form-control"
));
?>
<small><?php echo mt("Custom page location")?></small>
</div>

<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_enabled_contact','',"Enabled Contact Page", getOptionA('mobileapp2_enabled_contact') );?>   	
</div>

<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_remove_contact','',"Hide Contact Number", getOptionA('mobileapp2_remove_contact') );?>   	
	<small class="form-text text-muted">
      <?php echo mobileWrapper::t("hide contact number in merchant about page")?>
    </small>
</div>

<div class="col-md-3">
	<?php echo htmlWrapper::checkbox('mobileapp2_onetime_payment','',"One time payment", getOptionA('mobileapp2_onetime_payment') );?>   	
	<small class="form-text text-muted">
      <?php echo mobileWrapper::t("if merchant has only 1 payment options, payment will go straigt to selected payment")?>
    </small>
</div>

</div>
<!--row-->


  
<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	mobileWrapper::t('SAVE SETTINGS'),
	array('ajax/savesettings_app'),
	array(
		'type'=>'POST',
		'dataType'=>'json',
		'beforeSend'=>'js:function(){
		   loader(1);                 
		}
		',
		'complete'=>'js:function(){		                 
		   loader(2);
		 }',
		'success'=>'js:function(data){	
		   if(data.code==1){
		     notify(data.msg);
		   } else {
		     notify(data.msg,"danger");
		   }
		}
		'
	),array(
	  'class'=>'btn '.APP_BTN,
	  'id'=>'save_application'
	)
);
?>
</div>

<?php echo CHtml::endForm(); ?>
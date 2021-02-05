<div class="card" id="box_wrap">
<div class="card-body">

<ul class="nav nav-tabs" id="tab_others" role="tablist">

 <li class="nav-item">
    <a class="nav-link active"  data-toggle="tab" 
    href="#nav_api_settings" role="tab" aria-selected="true">
    <?php echo st("API Settings")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_app_settings" role="tab" aria-selected="true">
    <?php echo st("Application Settings")?>
    </a>
  </li>
  
   <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_startup" role="tab" aria-selected="true">
    <?php echo st("App Startup")?>
    </a>
   </li> 
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_banner" role="tab" aria-selected="true">
    <?php echo st("Home Banner")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_social_login" role="tab" aria-selected="true">
    <?php echo st("Social Login")?>
    </a>
  </li>
  
   <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_android" role="tab" aria-selected="true">
    <?php echo st("Android Settings")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_fcm" role="tab" aria-selected="true">
    <?php echo st("FCM")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_pages" role="tab" aria-selected="true">
    <?php echo st("Pages")?>
    </a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link"  data-toggle="tab" 
    href="#nav_contact_us" role="tab" aria-selected="true">
    <?php echo st("Contact us")?>
    </a>
  </li>

</ul>


<div class="tab-content" >

  <div class="tab-pane fade show active" id="nav_api_settings" role="tabpanel">  
	<?php   
	$this->renderPartial('/index/settings_api',array(
		'modulename'=>$modulename,
		'single_app_keys'=>$single_app_keys,
		'merchant_id'=>$merchant_id
	));
	?>
  </div>
  
  <div class="tab-pane fade " id="nav_app_settings" role="tabpanel">  
  <?php 
  $singleapp_default_image = getOption($merchant_id,'singleapp_default_image');
  $default_image_url = SingleAppClass::getImage( $singleapp_default_image );  
  $this->renderPartial('/index/settings_app',array(
    'modulename'=>$modulename,    
    'default_image_url'=>$default_image_url,
    'merchant_id'=>$merchant_id,
    'order_status_list'=>Yii::app()->functions->orderStatusList2(true),
  ));
   ?>
  </div>
  
  <div class="tab-pane fade" id="nav_startup" role="tabpanel">  
  
  <?php   
    $startup_banner = getOption($merchant_id,'singleapp_startup_banner');
	$this->renderPartial('/index/settings_startup',array(			
		'merchant_id'=>$merchant_id,
		'startup_banner'=>!empty($startup_banner)?json_decode($startup_banner,true):array()
	));
	?>
	
  </div>
  
  <div class="tab-pane fade " id="nav_banner" role="tabpanel">  
   <?php  
   $this->renderPartial('/index/settings_banner_add',array(    
    'merchant_id'=>$merchant_id,
    'banner'=>getOption($merchant_id,'singleapp_banner'),
    'modulename'=>$modulename
  ));
   ?>
  </div>
  
  <div class="tab-pane fade" id="nav_social_login" role="tabpanel">  
   <?php 
  $this->renderPartial('/index/settings_social',array(    
    'merchant_id'=>$merchant_id,
    'modulename'=>$modulename
  ));
  ?>
  </div>
  
  <div class="tab-pane fade " id="nav_android" role="tabpanel">  
   <?php 
  $this->renderPartial('/index/settings_android',array(      
    'merchant_id'=>$merchant_id,
    'modulename'=>$modulename
  ));
  ?>
  </div>
  
  <div class="tab-pane fade" id="nav_fcm" role="tabpanel">  
  <?php 
  $this->renderPartial('/index/settings_fcm',array(
     
    'merchant_id'=>$merchant_id,
    'modulename'=>$modulename
  ));
  ?>
  </div>
  
  <div class="tab-pane fade" id="nav_pages" role="tabpanel">  
  
  <?php 
  $this->renderPartial('/index/page_list',array(    
    'merchant_id'=>$merchant_id
  ));
  ?>
  
  </div>
  
  <div class="tab-pane fade" id="nav_contact_us" role="tabpanel">  
  
  <?php 
  $cotact_fields = getOption($merchant_id,'singleapp_contactus_fields');
  if(!empty($cotact_fields)){
  	 $cotact_fields = json_decode($cotact_fields,true);
  }
  $this->renderPartial('/index/contact_us',array(    
    'merchant_id'=>$merchant_id,
    'fields'=>$cotact_fields,
    'modulename'=>$modulename
  ));
  ?>
  
  </div>
  
</div><!-- tab-content-->


</div> <!--card body-->
</div> <!--card-->
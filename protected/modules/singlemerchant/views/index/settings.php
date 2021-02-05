 <ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#tabapi"><?php echo SingleAppClass::t("API")?></a></li>
  <li><a data-toggle="tab" href="#tabappsettings"><?php echo SingleAppClass::t("App Settings")?></a></li>
  <li><a data-toggle="tab" href="#tabfacebook"><?php echo SingleAppClass::t("Social Login")?></a></li>  
  <li><a data-toggle="tab" href="#tabandroid"><?php echo SingleAppClass::t("Android")?></a></li>  
  <li><a data-toggle="tab" href="#tabfcm"><?php echo SingleAppClass::t("Firebase Cloud Messaging")?></a></li>  
</ul>

<div class="tab-content">

  <div id="tabapi" class="tab-pane in active pad10">
  <?php 
  $this->renderPartial('/index/settings_api',array(
    'modulename'=>$modulename,
    'single_app_keys'=>$single_app_keys,
    'merchant_id'=>$merchant_id
  ));
  ?>
  </div>
  
  <div id="tabappsettings" class="tab-pane pad10">
  <?php 
  $singleapp_default_image = getOption($merchant_id,'singleapp_default_image');
  $default_image_url = SingleAppClass::getImage( $singleapp_default_image );  
  $this->renderPartial('/index/settings_app',array(
    'modulename'=>$modulename,
    'single_app_keys'=>$single_app_keys,
    'default_image_url'=>$default_image_url,
    'merchant_id'=>$merchant_id
  ));
   ?>
  </div>
    
  
  <div id="tabfacebook" class="tab-pane pad10">
  <?php 
  $this->renderPartial('/index/settings_social',array(
    'modulename'=>$modulename,    
    'merchant_id'=>$merchant_id
  ));
  ?>
  </div>
  
  <div id="tabandroid" class="tab-pane pad10">
  <?php 
  $this->renderPartial('/index/settings_android',array(
    'modulename'=>$modulename,    
    'merchant_id'=>$merchant_id
  ));
  ?>
  </div>
  
  <div id="tabfcm" class="tab-pane pad10">
  <?php 
  $this->renderPartial('/index/settings_fcm',array(
    'modulename'=>$modulename,    
    'merchant_id'=>$merchant_id
  ));
  ?>
  </div>
  

</div> <!--tab-content-->
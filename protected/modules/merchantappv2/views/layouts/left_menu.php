<?php
$menu =  array(  		    		    
    'activeCssClass'=>'active', 
    'encodeLabel'=>false,
    'htmlOptions' => array(
      'class'=>'menu_nav',
     ),
    'items'=>array(
    
        array('visible'=>true,
        'label'=>'<i class="fas fa-cog"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/settings'),'linkOptions'=>array(
          'data-content'=>translate("Merchant")
        )),               
        
         array('visible'=>true,
        'label'=>'<i class="fas fa-mobile-alt"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/device_list'),'linkOptions'=>array(
          'data-content'=>translate("Device List")
        )), 
        
        array('visible'=>true,
        'label'=>'<i class="fas fa-bullhorn"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/push_broadcast'),'linkOptions'=>array(
          'data-content'=>translate("Broadcast")
        )), 
        
        array('visible'=>true,
        'label'=>'<i class="fa fa-broadcast-tower"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/push_logs'),'linkOptions'=>array(
          'data-content'=>translate("Push Logs")
        )), 
        
        array('visible'=>true,
        'label'=>'<i class="fa fa-hammer"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/order_trigger'),'linkOptions'=>array(
          'data-content'=>translate("Order trigger notification")
        )), 
        
        array('visible'=>true,
        'label'=>'<i class="fas fa-plus"></i>',
        'url'=>array('/'.APP_FOLDER.'/index/others'),'linkOptions'=>array(
          'data-content'=>translate("Others")
        )), 
        
     )   
);       

$this->widget('zii.widgets.CMenu', $menu);
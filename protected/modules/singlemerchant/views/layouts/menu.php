<?php
$menu =  array(  		    		    
    'activeCssClass'=>'active', 
    'encodeLabel'=>false,
    'items'=>array(
        array('visible'=>true,'label'=>'<i class="fa fa-cutlery"></i>&nbsp; '.SingleAppClass::t("Merchant list"),
        'url'=>array('/'.SingleAppClass::moduleName().'/index/settings'),'linkOptions'=>array()),
        
        array('visible'=>true,'label'=>'<i class="fa fa-user-plus"></i>&nbsp; '.SingleAppClass::t('Registered Device'),
        'url'=>array('/'.SingleAppClass::moduleName().'/index/device'),'linkOptions'=>array()),              
        
        array('visible'=>true,'label'=>'<i class="fa fa fa-mobile"></i>&nbsp; '.SingleAppClass::t('Push Broadcast'),
        'url'=>array('/'.SingleAppClass::moduleName().'/index/push_broadcast'),'linkOptions'=>array()),              
        
        array('visible'=>true,'label'=>'<i class="fa fa fa-mobile"></i>&nbsp; '.SingleAppClass::t('Push Logs'),
        'url'=>array('/'.SingleAppClass::moduleName().'/index/push_logs'),'linkOptions'=>array()),              
        
        
        array('visible'=>true,'label'=>'<i class="fa fa-info-circle"></i>&nbsp; '.SingleAppClass::t('Cron jobs'),
        'url'=>array('/'.SingleAppClass::moduleName().'/index/cron_jobs'),'linkOptions'=>array()),              
        
        array('visible'=>true,'label'=>'<i class="fa fa-database"></i>&nbsp; '.SingleAppClass::t('Update database'),
        'url'=>array('/'.SingleAppClass::moduleName().'/update'),'linkOptions'=>array('target'=>"_blank")),              
        
     )   
);       
?>
<div class="menu">
<?php $this->widget('zii.widgets.CMenu', $menu);?>
</div>
<div class="card" id="box_wrap">
<div class="card-body">


<?php echo CHtml::beginForm('','post',array(
	  'id'=>"frm_ajax",
	  'onsubmit'=>"return false;",
	  'data-action'=>"saveBroadcast"
	)); 
	?> 	

<div class="form-group">
    <label ><?php echo SingleAppClass::t("Push Title")?></label>
    <?php 
    echo CHtml::textField('push_title','',array(
      'class'=>'form-control',
      'maxlength'=>200,
      'required'=>"true"
    ));
    ?>
  </div>
   
  <div class="form-group">
    <label ><?php echo SingleAppClass::t("Push Message")?></label>
    <?php 
    echo CHtml::textArea('push_message','',array(
      'class'=>'form-control', 
      'required'=>true
    ));
    ?>
  </div>
    
   <div class="form-group">
    <label ><?php echo SingleAppClass::t("Merchant")?> (<span class="small"><?php echo st("Optional")?></span>)</label>
    <?php 
    echo CHtml::dropDownList('merchant','',
    (array) $merchant_list
    ,array(
      'class'=>"form-control chosen",
      "multiple"=>"multiple"
    ))
    ?>    
  </div>
  
  <div class="form-group">
    <label ><?php echo SingleAppClass::t("Send to Device Platform")?></label>
    <?php 
    echo CHtml::dropDownList('device_platform','',SingleAppClass::platFormList(),array(
      'class'=>'form-control',      
    ))
    ?>
  </div>

  
<div class="form-group">  
<button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo st("Save Broadcast")?></button> 
</div>
    
<?php echo CHtml::endForm(); ?>

</div>


</div> <!--card body-->
</div> <!--card-->

<div class="floating_action">
 <a  href="<?php echo Yii::app()->createUrl(APP_FOLDER."/index/old_broadcast")?>" class="btn <?php echo APP_BTN2?>"  >
 <?php echo st("BACK")?> 
 </a>
</div>
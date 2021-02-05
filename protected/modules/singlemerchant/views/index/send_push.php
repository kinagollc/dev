<div class="card" id="box_wrap">
<div class="card-body">


<?php echo CHtml::beginForm('','post',array(
  'id'=>"frm_ajax",
  'onsubmit'=>"return false;",
  'data-action'=>"sendpush"
)); 
?> 	
<?php 
echo CHtml::hiddenField('id',$data['client_id']);
?>

<div class="form-group">
    <label ><?php echo SingleAppClass::t("Push Title")?></label>
    <?php 
    echo CHtml::textField('push_title','',array(
      'class'=>'form-control',
      'maxlength'=>200,
      'required'=>true
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
<button type="submit" class="btn <?php echo APP_BTN;?>"><?php echo st("Send Push notification")?></button>
</div>
    
<?php echo CHtml::endForm(); ?>


</div> <!--card body-->
</div> <!--card-->


<div class="floating_action">
 <a  href="<?php echo Yii::app()->createUrl($modulename."/index/device")?>" class="btn btn-secondary"  >
 <?php echo st("BACK")?> 
 </a>
</div>
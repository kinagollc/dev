<?php echo CHtml::beginForm('','post',array(
 'onsubmit'=>"return false;"
)); 
?> 


<?php 
$lang_list = array();
if(is_array($data['language_list']) && count($data['language_list'])>=1){
	foreach ($data['language_list'] as $val) {
		$lang_list[$val] = t($val);
	}
}
?>

<div class="row">
  <div class="col-md-4">
    <p><b><?php echo translate("Default language")?></b></p>    
    <?php     
    echo CHtml::dropDownList('set_language',
    isset($data['set_language'])?$data['set_language']:''
    ,(array)$lang_list,array(
      'class'=>"form-control",      
    ));
    ?>
  </div>
</div>

<div class="spacer"></div><div class="spacer"></div>

<?php 
$merchantapp_language = isset($data['merchantapp_language'])?$data['merchantapp_language']:'';
if(is_array($data['language_list']) && count($data['language_list'])>=1):
foreach ($data['language_list'] as $val):
?>
<div class="spacer"></div>
<h4><?php echo strtoupper(translate($val));?></h4>
<div class="form-group">
    <label><?php echo translate("Label")?></label>
    <?php 
    echo CHtml::textField("merchantapp_language[label][$val]",
    isset($merchantapp_language['label'][$val])?$merchantapp_language['label'][$val]:''
    ,array(
      'class'=>"form-control",
      'required'=>true
    ));
    ?>
</div>

<div class="form-group">
    <label><?php echo translate("Sub label")?></label>
    <?php 
    echo CHtml::textField("merchantapp_language[sub_label][$val]",
    isset($merchantapp_language['sub_label'][$val])?$merchantapp_language['sub_label'][$val]:''
    ,array(
      'class'=>"form-control"
    ));
    ?>
</div>

<div class="form-group">
    <label><?php echo translate("Flag")?></label>
    <?php 
    echo CHtml::dropDownList("merchantapp_language[flag][$val]",
    isset($merchantapp_language['flag'][$val])?$merchantapp_language['flag'][$val]:''
    ,$data['flag'],array(
      'class'=>"form-control country_list_select2"
    ));
    ?>
</div>
<?php
endforeach;
endif;
?>



<div class="floating_action">
  <?php
echo CHtml::ajaxSubmitButton(
	translate('Save Settings'),
	array('ajax/language_settings'),
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
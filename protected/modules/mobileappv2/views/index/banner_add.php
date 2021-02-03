

<div class="card" id="box_wrap">
<div class="card-body">
<?php echo CHtml::beginForm('','post',array(
		  'id'=>"frm",
		  'onsubmit'=>"return false;",
		  'data-action'=>"save_home_banner"
		)); 
		?> 
		
<?php 
$banner_id = isset($data['banner_id'])?$data['banner_id']:'' ;
echo CHtml::hiddenField('category', $category );
echo CHtml::hiddenField('banner_id', $banner_id);
echo CHtml::hiddenField('home_banner', isset($data['banner_name'])?$data['banner_name']:''  );
?>		

<?php if(Yii::app()->functions->multipleField()):?>

<ul class="nav nav-tabs" id="lang_tab" role="tablist">
    <li class="nav-item">
	 <a class="nav-link active"  data-toggle="tab" href="#tab_default"><?php echo mt("default")?></a>
	</li>
	<?php if ( $fields=FunctionsV3::getLanguageList(false)):?>  
	  <?php foreach ($fields as $f_val): ?>
	     <li class="nav-item">
	      <a class="nav-link"  data-toggle="tab" href="#tab_<?php echo $f_val;?>"><?php echo $f_val;?></a>
	    </li>
	  <?php endforeach;?>
	<?php endif;?>
</ul> 

<div class="tab-content" id="lang_tab">
  <div class="tab-pane fade show active" id="tab_default" >
  
	 <div class="form-group">
	<label><?php echo mt("Title")?></label>		
	<?php 
	echo CHtml::textField("title[default]",
	isset($data['title'])?$data['title']:'' 
	,array('class'=>"form-control",'required'=>true,'maxlength'=>255 ));
	?>			
	</div> 
	
	<div class="form-group">
	<label><?php echo mt("Sub title")?></label>		
	<?php 
	echo CHtml::textField("sub_title[default]",
	isset($data['sub_title'])?$data['sub_title']:'' 
	,array('class'=>"form-control",'required'=>false,'maxlength'=>255 ));
	?>			
	</div> 
  
  </div>  
  <!--tab-pane-->
  
 <?php if(is_array($fields) && count($fields)>=1):?>
  <?php foreach ($fields as $lang_code): ?>
  
     <?php 
     $data2 = array();
     if($banner_id>0){
     	$data2 = mobileWrapper::getHomebannerTrans($banner_id,$lang_code);       	
     }
     ?>
  
     <div class="tab-pane fade show" id="tab_<?php echo $lang_code;?>" >
     
        <div class="form-group">
		<label><?php echo mt("Title")?></label>		
		<?php 
		echo CHtml::textField("title[$lang_code]",
		isset($data2['title'])?$data2['title']:'' 
		,array('class'=>"form-control",'required'=>true,'maxlength'=>255 ));
		?>			
		</div> 
		
		<div class="form-group">
		<label><?php echo mt("Sub title")?></label>		
		<?php 
		echo CHtml::textField("sub_title[$lang_code]",
		isset($data2['sub_title'])?$data2['sub_title']:'' 
		,array('class'=>"form-control",'required'=>false,'maxlength'=>255 ));
		?>			
		</div> 
     
     
     </div>  
     <!--tab pane-->
    <?php endforeach;?>
  <?php endif;?>

  <!--tab-pane-->
</div>
<!--lang_tab-->
          

<?php else :?>

<div class="form-group">
<label><?php echo mt("Title")?></label>		
<?php 
echo CHtml::textField('title',
isset($data['title'])?$data['title']:'' 
,array('class'=>"form-control",'required'=>true ));
?>			
</div> 

<div class="form-group">
<label><?php echo mt("Sub title")?></label>		
<?php 
echo CHtml::textField('sub_title',
isset($data['sub_title'])?$data['sub_title']:'' 
,array('class'=>"form-control",'required'=>false ));
?>			
</div> 
<?php endif;?>



<div class="form-group">
<label><?php echo mt("Actions")?></label><br/>		
<?php 
echo CHtml::dropDownList('actions',
isset($data['actions'])?$data['actions']:''
,
  (array)$actions,array(
  'class'=>"form-control",  
));
?>			
</div> 

<div class="height10"></div>

<div class="form-group chosen_big_input input_tags">
<label><?php echo mt("Tags")?></label><br/>		
<?php 
$tag_id = isset($data['tag_id'])?json_decode($data['tag_id'],true):'';
echo CHtml::dropDownList('tag_id',(array)$tag_id,
  (array)$tags,array(
  'class'=>"form-control chosen",
  "multiple"=>"multiple",  
));
?>			
</div> 

<div class="form-group input_custom_page">
<label><?php echo mt("Custom Page")?></label><br/>		
<?php 
echo CHtml::dropDownList('page_id',
isset($data['page_id'])?$data['page_id']:''
,
  (array)$pages,array(
  'class'=>"form-control",  
));
?>			
</div> 

<div class="form-group input_custom_link">
<label><?php echo mt("Custom link")?></label><br/>		
<?php 
echo CHtml::textField('custom_url',
isset($data['custom_url'])?$data['custom_url']:'' 
,array('class'=>"form-control",'required'=>false,'placeholder'=>mt("example : http://yourserver.com/terms") ));
?>
</div> 

<div class="height10"></div>

<div class="form-group">
<button id="upload_banner" type="button" class="btn <?php echo APP_BTN2?> btn-primary">
 <?php echo mobileWrapper::t("Browse")?>
</button>    
</div> 			


<div class="form-group">
<label><?php echo mt("Sequence")?></label>		
<?php 
echo CHtml::textField('sequence',
isset($data['sequence'])?$data['sequence']:$last_increment 
,array('class'=>"form-control numeric_only",'required'=>true ));
?>			
</div> 

<div class="form-group">
	<label><?php echo mt("Status")?></label>		
	<?php 
	echo CHtml::dropDownList('status',
    isset($data['status'])?$data['status']:'' 
    ,statusList() ,array(
      'class'=>'form-control',      
      'required'=>true
    ));
	?>
	</div> 

<div class="floating_action">

<a href="<?php echo $back_url?>" class="btn <?php echo APP_BTN2?>"  >
<?php echo mobileWrapper::t("Back")?>
</a>	 

<button class="btn <?php echo APP_BTN?> "  >
<?php if(isset($data['banner_id'])):?>
<?php echo mobileWrapper::t("Update")?>
<?php else :?>
<?php echo mobileWrapper::t("Save")?>
<?php endif;?>
</button>

</div>	

<?php echo CHtml::endForm() ; ?>	

</div>
</div>
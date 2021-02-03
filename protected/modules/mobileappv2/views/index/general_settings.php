<div class="row">
  <div class="col-md-4 col-sm-4">
  
  <div class="card card_medium" id="box_wrap">
	<div class="card-body">
	
	<?php 
	 $this->renderPartial(APP_FOLDER.'.views.index.settings_menu',array(
	  'settings_title'=>$settings_title,
	  'menu'=>mobileWrapper::settingsMenu()
	 ));
	?>
	
	</div> <!--card body-->
  </div> <!--card-->
  
  </div> <!--col-->
  <div class="col-md-8 col-sm-8">
  
  <div class="card card_medium" id="box_wrap">
	<div class="card-body">
	
	 <h2><?php echo $settings_title?></h2>
	 <div class="pt-2">
	 <?php $this->renderPartial('/index/'.$tpl,$data);?>
	 </div>
	
	</div> <!--card body-->
  </div> <!--card-->
  
  </div> <!--col-->
</div> <!--row-->
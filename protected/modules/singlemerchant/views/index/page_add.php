
<div class="modal fade" id="pageNewModal" tabindex="-1" role="dialog" aria-labelledby="pageNewModal" aria-hidden="true">
 <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"> <h5 class="modal-title" ><?php echo st("Page")?></h5></div>
                
		<?php echo CHtml::beginForm('','post',array(
		  'id'=>"frm_ajax",
		  'onsubmit'=>"return false;",
		  'data-action'=>"save_page"
		)); 
		?> 
        
        <?php echo CHtml::hiddenField('page_id','')?>
        <?php echo CHtml::hiddenField('merchant_id',$merchant_id)?>
        
        <div class="modal-body">
        
        <?php if(Yii::app()->functions->multipleField()):?>
        
        <ul class="nav nav-tabs" id="lang_tab" role="tablist">
            <li class="nav-item">
			 <a class="nav-link active"  data-toggle="tab" href="#tab_default"><?php echo st("default")?></a>
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
			<label><?php echo st("Title")?></label>		
			<?php 
			echo CHtml::textField('title','',array('class'=>"form-control",'required'=>true ));
			?>			
		   </div> 
		   
		   <div class="form-group">
			<label><?php echo st("Content")?></label>		
			<?php 
			echo CHtml::textArea('content','',array(
			  'class'=>"form-control text_area",
			  'required'=>true
			));
			?>			
		   </div> 
          
          </div>
          <?php if(is_array($fields) && count($fields)>=1):?>
          <?php foreach ($fields as $lang_code): ?>
             <div class="tab-pane fade show" id="tab_<?php echo $lang_code;?>" >
             
             <div class="form-group">
				<label><?php echo st("Title")?></label>		
				<?php 
				echo CHtml::textField('title_'.$lang_code,'',array('class'=>"form-control",'required'=>true ));
				?>			
			   </div> 
			   
			   <div class="form-group">
				<label><?php echo st("Content")?></label>		
				<?php 
				echo CHtml::textArea('content_'.$lang_code,'',array(
				  'class'=>"form-control",
				  'required'=>true
				));
				?>			
		   </div>   
             
             </div>  
          <?php endforeach;?>
          <?php endif;?>
        </div>
        
        <div class="height10"></div>
        <?php else :?>
        
        
          <div class="form-group">
			<label><?php echo st("Title")?></label>		
			<?php 
			echo CHtml::textField('title','',array('class'=>"form-control",'required'=>true ));
			?>			
		   </div> 
		   
		   <div class="form-group">
			<label><?php echo st("Content")?></label>		
			<?php 
			echo CHtml::textArea('content','',array(
			  'class'=>"form-control",
			  'required'=>true
			));
			?>			
		   </div>  
        
        <?php endif;?>
	    
        <div class="custom-control custom-checkbox">  
		  <?php 
		  echo CHtml::checkBox('use_html',false		 
		  ,array(
		    'id'=>'use_html',
		    'class'=>"custom-control-input",
		  ));
		  ?>
		  <label class="custom-control-label" for="use_html">
		    <?php echo st("HTML Format")?>
		  </label>
		</div>
		
		<div class="height10"></div>


        <div class="form-group">
		<label><?php echo st("Icon")?></label>		
		<?php 
		echo CHtml::textField('icon','',array('class'=>"form-control"));
		?>
		<small class="form-text text-muted">
          <?php echo st("icon class name")?> <a target="_blank" href="https://ionicons.com/v2/">https://ionicons.com/v2/</a>
        </small>
		</div> 
		
		<div class="form-group">
		<label><?php echo st("Sequence")?></label>		
		<?php 
		echo CHtml::textField('sequence','',array('class'=>"form-control"));
		?>
		</div> 
		
		<div class="form-group">
		<label><?php echo st("Status")?></label>		
		<?php 
		echo CHtml::dropDownList('status',
	    ''
	    ,statusList() ,array(
	      'class'=>'form-control',      
	      'required'=>true
	    ));
		?>
		</div> 
        
        </div> <!--modal body-->
        
        <div class="modal-footer">          
          <button type="submit" class="btn <?php echo APP_BTN;?>">&nbsp;<?php echo st("Save")?>&nbsp;</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
           <?php echo st("Close")?>
          </button>
       </div>
       
      <!--</form>-->
      <?php echo CHtml::endForm() ; ?>
        
      </div><!-- content-->
 </div>
</div>
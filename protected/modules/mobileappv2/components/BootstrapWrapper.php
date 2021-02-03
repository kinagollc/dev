<?php
class BootstrapWrapper
{
    public static function formRadio($field_name='', $label='',$selected=false,$options=array())
	{
		ob_start();	
		?>
		<div class="radio">
	    <label>
	       <?php echo CHtml::radioButton($field_name,$selected,(array)$options)?>	       
	      <span class="bmd-radio"><div class="ripple-container"></div></span>
	      <?php echo mt($label)?>
	     </label>
	    </div>
	    <?php
		$forms = ob_get_contents();
        ob_end_clean();	
        return $forms;
	}
	
	public static function checkboxInline($field_name='', $label='',$selected=false,$options=array())
	{
		ob_start();	
		?>
		 <label class="checkbox-inline">
	    <?php echo CHtml::checkBox($field_name,$selected,(array)$options)?>	   
	    <span class="checkbox-decorator">
	    <span class="check"></span>
	      <div class="ripple-container"></div>
	    </span> <?php echo mt($label)?>
	    </label>
		<?php
		$forms = ob_get_contents();
        ob_end_clean();	
        return $forms;
	}
	
	public static function formSwitch($field_name='', $label='',$selected=false,$options=array())
	{
		ob_start();	
		?>		
		<div class="switch">
	    <label>	      
	     <?php echo CHtml::checkBox($field_name,$selected,(array)$options)?>
	      <span class="bmd-switch-track">
	        <div class="ripple-container"></div>
	      </span>
	       &nbsp;<?php echo mt($label)?>
	    </label>
	    </div>
	    <?php
		$forms = ob_get_contents();
        ob_end_clean();	
        return $forms;
	}	
}
/*end class*/
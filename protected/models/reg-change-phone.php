
<div class="container enter-address-wrap">

<div class="section-label">
    <a class="section-label-a">
      <span class="bold">
      <?php 
      echo $verification_type=="sms"?t("Enter your phone number"):t("Enter your email address");
      ?></span>
      <b></b>
    </a>     
</div>  

<form id="frm_phone_change" class="frm_phone_change" method="POST" onsubmit="return false;" >
<?php 
echo CHtml::hiddenField('action','update_client_contact');
echo CHtml::hiddenField('id', isset($data['token'])?$data['token']:'' );
echo CHtml::hiddenField('verification_type', $verification_type);
$contact = '';
if($verification_type=="sms"){
	$contact=isset($data['contact_phone'])?$data['contact_phone']:'';
} else $contact=isset($data['email_address'])?$data['email_address']:'';
?>

<div class="row">
  <div class="col-md-12 ">
    <?php echo CHtml::textField('contact',
	 $contact
	 ,array(
	 'class'=>"grey-inputs",
	 'data-validation'=>"required",
	 'maxlength'=>$verification_type=="sms"?12:200
	 ))?>
  </div> 
    
  
</div> <!--row-->

<div class="row food-item-actions top10">
  <div class="col-md-5 "></div>
  <div class="col-md-3 ">
  <a href="javascript:$.fancybox.close();" class="orange-button inline center">
  <?php echo t("Close")?>
  </a>
  </div>
  <div class="col-md-3 ">
     <input type="submit" class="green-button inline" value="<?php echo t("Submit")?>">
  </div>
</div>

 </form>

</div> <!--container-->

<script type="text/javascript">
$.validate({ 	
	language : jsLanguageValidator,
	language : jsLanguageValidator,
    form : '#frm_phone_change',    
    onError : function() {      
    },
    onSuccess : function() {     
      form_submit('frm_phone_change');
      return false;
    }  
})

</script>
<?php
die();
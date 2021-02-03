<?php $this->renderPartial('/layouts/header');?>
<body>

<div class="main_wrap">

<div class="header_wrap">

  <h3><?php echo $this->pageTitle?></h3>

  <ul class="top_nav">
  <li>    
    
     <div class="dropdown badge_notification">
	  <button type="button" class="btn dropdown-toggle" data-toggle="dropdown">
	    <i class="fas fa-bell" ></i> <span class="badge badge-danger"></span>
	  </button>
	  <div class="dropdown-menu">	    
	  </div>
	</div> 
    
   </li>
   <li>
    <a class="btn btn-secondary" href="<?php echo Yii::app()->createUrl('/admin/dashboard')?>" >
    <i class="fas fa-sign-out-alt" ></i>
    </a>
   </li>
  </ul>
</div>
<!--header_wrap-->

<div class="sidebar_wrap">
  <?php $this->renderPartial('/layouts/left_menu');?>  
</div>
<!--sidebar_wrap-->

<div class="content_wrap">  
   <?php echo $content;?>   
</div> <!--content_wrap-->

</div> 
<!--main_wrap-->


</body>
<?php $this->renderPartial('/layouts/footer');?>
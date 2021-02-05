<p>
<?php echo SingleAppClass::t("Please run the following cron jobs in your server as http")?>.<br/>
<?php echo SingleAppClass::t("set the running of cronjobs every minute")?>
</p>

<?php $cron = websiteUrl()."/singlemerchant/cron/processpush";?>
<?php $cron2 = websiteUrl()."/singlemerchant/cron/processbroadcast";?>

<ul>
 <li>
 <a href="<?php echo $cron?>" target="_blank">
  <?php echo $cron?>
 </a>
 </li>
 
 <li>
 <a href="<?php echo $cron2?>" target="_blank">
  <?php echo $cron2?>
 </a>
 </li>
 
</ul>


<p><?php echo SingleAppClass::t("Eg. command")?> <br/>
 curl <?php echo $cron?><br/> 
 </p>

<?php if(is_array($this->price) && count($this->price)>=1):?>
<?php foreach ($this->price as $val):?>
   
  <?php if( $val['exchange_discount_price']>0):?>
     <p class="<?php echo $this->bold==1?"bold":'';?>">
      <span class="normal-price">
      <?php 
      echo !empty($val['size_name'])?$val['size_name']." ":'';
      echo Price_Formatter::formatNumber($val['exchange_price']);
      ?></span>
      <span class="sale-price"><?php echo Price_Formatter::formatNumber($val['exchange_discount_price'])?></span>
     </p>
  <?php else :?>
  <p class="<?php echo $this->bold==1?"bold":'';?>">
   <?php 
   echo !empty($val['size_name'])?$val['size_name']." ":'';
   echo Price_Formatter::formatNumber($val['exchange_price']);
   ?>
  </p>
  <?php endif;?>
<?php endforeach;?>
<?php endif;?>
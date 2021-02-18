<?php
class Widget_price extends CWidget 
{
	public $price = array();
	public $bold = false;
		
	public function run() {
		$this->render('price_display');
	}

}
/*end class*/
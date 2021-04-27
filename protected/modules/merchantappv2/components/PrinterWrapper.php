<?php
class PrinterWrapper
{
	public static function verifyTable()
	{		
		if(!Yii::app()->db->schema->getTable("{{printer_print}}")){
			throw new Exception( "Printer addon not yet installed" );	
		}
		return true;
	}
	
	public static function verifyLastPrint($merchant_id=0,$order_id=0)
	{
		self::verifyTable();
		$stmt = "SELECT id FROM {{printer_print}}
		WHERE 
		merchant_id=".q($merchant_id)."
		AND
		order_id = ".q($order_id)."
		AND query_status ='pending'
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			throw new Exception( translate("You have existing print request for order#[order_id], please wait until its process",array(
			  '[order_id]'=>$order_id
			)) );	
		}
		return true;
	}
	
}
/*end class*/
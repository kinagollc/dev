<?php
class MerchantMenu
{
	public static function menu()
	{
		$menu[] = array(
		  'label'=>"API Settings",
		  'link'=>APP_FOLDER."/index/settings",
		  'id'=>'settings'
		);		
		$menu[] = array(
		  'label'=>"Application Settings",
		  'link'=>APP_FOLDER."/index/application_settings",
		  'id'=>'application_settings'
		);		
		$menu[] = array(
		  'label'=>"Order Settings",
		  'link'=>APP_FOLDER."/index/order_settings",
		  'id'=>'order_settings'
		);				
		
		$menu[] = array(
		  'label'=>"Auto update order status",
		  'link'=>APP_FOLDER."/index/auto_order",
		  'id'=>'auto_order'
		);				
		
		$menu[] = array(
		  'label'=>"Booking Settings",
		  'link'=>APP_FOLDER."/index/booking_settings",
		  'id'=>'booking_settings'
		);		
		
		$menu[] = array(
		  'label'=>"Language",
		  'link'=>APP_FOLDER."/index/language_settings",
		  'id'=>'language_settings'
		);		
		
		$menu[] = array(
		  'label'=>"FCM",
		  'link'=>APP_FOLDER."/index/settings_fcm",
		  'id'=>'settings_fcm'
		);		
				
		$menu[] = array(
		  'label'=>"Printer",
		  'link'=>APP_FOLDER."/index/settings_printer",
		  'id'=>'settings_printer'
		);		
		
		$menu[] = array(
		  'label'=>"Privacy policy",
		  'link'=>APP_FOLDER."/index/privacy_policy",
		  'id'=>'privacy_policy'
		);		
		
		return $menu;
	}
	
}
/*end class*/
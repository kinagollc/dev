<?php
use phpFCMv1\Client;
use phpFCMv1\Notification;
use phpFCMv1\Recipient;

class firebaseWrapper
{
	public static function sendPush()
	{		
		$device_id = 'ezpc4oATQxixl0-K41-_Hk:APA91bGfc33ASGngQbSivm7j7W9aUNH0gxt4gFfLgm2w2w2Di-Pn1IHtLP2zwKd-5Pjp_sEOJLe6zF3ZIoUjK7eMw2K3omWJY7NIw4njP1RRuQfkmdoh0uhtZ_yn6i22DvtJUjt7RkQu';
		//$device_id='eWjUUryn8Ex3mrgPCB6E4J:APA91bFxnt6fIqLptLdBe3tCQ1Db7lDqHz4N9cx9ueo_6IqZiBTypiLlXQfIJU8zH97aC_uR8od1BAPev8_ss-fA119CGY9iNf-QXSgHvUvpAcye-1vV2cLD6-yEnkV_4z4xkY4vL7f9';
		//$device_id='/topics/broadcast';
				
		$filename = FunctionsV3::uploadPath().'/kmrs-demo-firebase-adminsdk-gh237-d6c4bb2199.json';		
		$client = new Client($filename);
		$recipient = new Recipient();
		$notification = new Notification();
		
		$recipient->setSingleRecipient($device_id);		
		$notification->setNotification('NOTIFICATION_TITILE', 'NOTIFICATION_BODY');
		$client->build($recipient, $notification);
		
		$payload = $client->getPayload();
		dump($payload);
		
		$resp = $client->fire();
		dump($resp);
	}	
}
/*end class*/
<?php
class DriverWrapper
{
	
	public static function ListRider($merchant_id=0,$start=0, $total_rows=10,$search_string='',$lat=0, $lng=0,$unit='mi')
	{
		
		if(!Yii::app()->db->schema->getTable("{{driver}}")){
			return false;
		}
		
		$and='';
		if(!empty($search_string)){				
			$and=" AND ( 
			 first_name LIKE ".q("%$search_string%")." 
			 OR
			 last_name LIKE ".q("%$search_string%")." 
			 OR
			 team_name LIKE ".q("%$search_string%")." 			 
			)
			";
		}
				
		$driver_allowed_team_to_merchant = getOptionA('driver_allowed_team_to_merchant');
		
		if($driver_allowed_team_to_merchant==1){			
			$and.="
			AND a.user_type IN ('admin','merchant')			
			AND ( a.user_id = ".q($merchant_id)." 
			      OR a.user_id IN (
			        select admin_id
			        from {{admin_user}}
			        where status ='active'
			      )
			 ) 
			 ";					
		} elseif ( $driver_allowed_team_to_merchant==2){
			$driver_allowed_merchant_list = getOptionA('driver_allowed_merchant_list');
			$and_merchant = "AND a.user_type IN ('merchant')";
			if ( $merchant_json = json_decode($driver_allowed_merchant_list,true)){
				if(in_array($merchant_id,$merchant_json)){
					$and_merchant = "AND a.user_type IN ('admin','merchant')";
				}
			}
			$and.="
			$and_merchant
			AND ( a.user_id = ".q($merchant_id)." 
			      OR a.user_id IN (
			        select admin_id
			        from {{admin_user}}
			        where status ='active'
			      )
			 ) 
			 ";		
		} else {
			$and=" AND a.user_type='merchant' AND 
			a.user_id=".q($merchant_id)."
			 ";
		}
				
        $distance_exp=3959;
		if ($unit=="km"){
			$distance_exp=6371;
		}	
		
		$lat=!empty($lat)?$lat:0;
		$lng=!empty($lng)?$lng:0;
				
		
		$query_distance="
		( $distance_exp * acos( cos( radians($lat) ) * cos( radians( location_lat ) ) 
				* cos( radians( location_lng ) - radians($lng) ) 
				+ sin( radians($lat) ) * sin( radians( location_lat ) ) ) ) 
				AS distance		
		";	
						
		$todays_date=date('Y-m-d');	
		$online_interval_date = date("Y-m-d H:i:s", strtotime("-5 minutes"));		
		$query_online=",
		  (
		    select count(*) from {{driver}}
		    where 
		    driver_id = a.driver_id
		    AND on_duty ='1'
		    AND CAST(last_login as DATE) BETWEEN ".q($todays_date)." AND ".q($todays_date)."
		    AND ".q($online_interval_date)." < last_login  
		  ) as online_status
		";
		
		$stmt="
		select SQL_CALC_FOUND_ROWS 
		driver_id, 
		a.team_id,
		b.team_name,
		concat(first_name,' ',last_name) as driver_name,
		email,phone,
		location_lat,location_lng,
		profile_photo,
		transport_type_id,
		$query_distance
		$query_online
		
		FROM
		{{driver}} a		
		LEFT JOIN {{driver_team}}  b
		ON
		a.team_id = b.team_id
		
		WHERE 1
		$and
		ORDER BY distance ASC
		LIMIT $start,$total_rows
		";					
		//dump($stmt);
        if($resp = Yii::app()->db->createCommand($stmt)->queryAll()){        	
        	return $resp;
        }
        return false;     
	}	
	
	public static function getTaskByOrderID($order_id='')
	{
		if(!Yii::app()->db->schema->getTable("{{driver_task}}")){
			return false;
		}
		
		$stmt="
		SELECT * FROM {{driver_task}}
		WHERE 
		order_id=".q($order_id)."
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
	public static function getDriverInfo($driver_id='')
	{
		if(!Yii::app()->db->schema->getTable("{{driver}}")){
			return false;
		}
		
		$stmt="
		SELECT driver_id,a.team_id,
		concat(first_name,' ',last_name) as driver_name,
		first_name,last_name,email,phone,
		transport_type_id,transport_description,licence_plate,color,profile_photo,
		b.team_name
		
		FROM {{driver}} a
		LEFT JOIN {{driver_team}} b
		ON
		a.team_id = b.team_id
		
		WHERE 
		driver_id=".q($driver_id)."
		LIMIT 0,1
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res;
		}
		return false;
	}
	
	public static function getDriverInfos($driver_id)
	{
		if($res = self::getDriverInfo($driver_id)){
			$res = Yii::app()->request->stripSlashes($res);
			return $res;
		}
		throw new Exception( "Record not found" );
	}
	
	public static function getAdminID()
	{
		$stmt="
		SELECT admin_id		
		FROM {{admin_user}}
		WHERE status = 'active'	
		ORDER BY admin_id ASC 	
		LIMIT 0,1
		";
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){
			return $res['admin_id'];
		}
		return 0;
	}
	
	public static function addDriver($merchant_id='',$params=array(), $id='')
	{					
		
		if(!Yii::app()->db->schema->getTable("{{driver}}")){
			throw new Exception( "an error has occurred" );
		}
		
		if($id>0){						  
	      	  $up =Yii::app()->db->createCommand()->update("{{driver}}",$params,
	      	    'driver_id=:driver_id',
	      	    array(
	      	      ':driver_id'=>$id
	      	    )
	      	  );
	      	  if($up){
	      	  	 return true;
	      	  } else throw new Exception( "Failed cannot update records" );	        
		} else {			
			if(Yii::app()->db->createCommand()->insert("{{driver}}",$params)){
				return true;
			} else throw new Exception( "Failed cannot insert records" );
		}		
		
		throw new Exception( "an error has occurred" );
	}		
	
	public static function notifyDriver($order_id=0, $notes='')
	{
		if(!Yii::app()->db->schema->getTable("{{driver_task}}")){
			return false;
		}
		
		$lang=Yii::app()->language;
		
		$stmt = "
		SELECT
		a.task_id,a.order_id,a.driver_id,
		a.user_type,a.user_id,
		a.customer_name,a.delivery_date,a.delivery_address,
		concat(b.first_name,' ',b.last_name) as driver_name,
		IFNULL(b.device_id,'') as device_id, 
		IFNULL(b.device_platform,'') as device_platform, 
		IFNULL(b.enabled_push,'') as enabled_push
		
		FROM {{driver_task}} a
		LEFT JOIN {{driver}} b
		ON
		a.driver_id = b.driver_id
		
		WHERE
		a.order_id = ".q($order_id)."
		";		
		if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	
						
			$tpl  = CustomerNotification::getNotificationTemplate('food_is_done_to_driver',$lang,'push');			
			$push_title = translate($tpl['push_title']); 
			$push_content = translate($tpl['push_content']);
						
			$res['notes'] = $notes;
						
			if(method_exists("FunctionsV3","replaceTags")){
				$push_title = FunctionsV3::replaceTags($push_title,$res);
				$push_content = FunctionsV3::replaceTags($push_content,$res);
			}		
			
			$params = array(
			  'device_platform'=>$res['device_platform'],
			  'device_id'=>$res['device_id'],
			  'push_title'=>$push_title,
			  'push_message'=>$push_content,
			  'order_id'=>(integer)$order_id,
			  'driver_id'=>(integer)$res['driver_id'],
			  'task_id'=>(integer)$res['task_id'],
			  'date_created'=>FunctionsV3::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			  'user_type'=>$res['user_type'],
			  'user_id'=>(integer)$res['user_id']
			);	
					
			if(Yii::app()->db->schema->getTable("{{driver_pushlog}}")){
			   Yii::app()->db->createCommand()->insert("{{driver_pushlog}}",$params);			   			  
			   OrderWrapper::consumeUrl(FunctionsV3::getHostURL().Yii::app()->createUrl("driver/cron/processpush"));
			}
			
			return true;
		}
		return false;
	}
	
	public static function getTaskDistance($merchant_id='',$task_location='', $type='merchant')
	{		 
		 if(!Yii::app()->db->schema->getTable("{{merchantapp_task_location}}")){		 	
		 	throw new Exception( "table is missing" );
		 }
		 		 		 	
	     if($location = explode("|",$task_location) ){	     		     	
	     		     	     	
	     	$task = explode(",",  isset($location[0])?$location[0]:'' );
	     	$drop = explode(",", isset($location[1])?$location[1]:'' );
	     	$driver = explode(",", isset($location[2])?$location[2]:'' );
	     	$vehicle = isset($location[3])?$location[3]:'';
	     	
	     	$lat = ''; $lng = ''; $driver_lat =''; $driver_lng='';
	     	if(is_array($drop) && count($drop)>=1 && $type=="merchant"){
	     		$lat = isset($drop[0])?$drop[0]:''; $lng = isset($drop[1])?$drop[1]:'';
	     	} else if (is_array($drop) && count($drop)>=1 && $type=="task") {
	     		$lat = isset($task[0])?$task[0]:''; $lng = isset($task[1])?$task[1]:'';
	     	}
	     	if(is_array($driver) && count($driver)>=1 ){
	     		$driver_lat = isset($driver[0])?$driver[0]:''; $driver_lng = isset($driver[1])?$driver[1]:'';
	     	}
	     	
	     	
	     	$past_ten_days = date("Y-m-d", strtotime("-5 days"));
	     	Yii::app()->db->createCommand("DELETE FROM {{merchantapp_task_location}} 
	     	WHERE CAST(date_created as DATE) BETWEEN ".q($past_ten_days)." AND ".q($past_ten_days)."
	     	")->query();	     		     	
	     	
	     	$stmt="SELECT * FROM
	     	{{merchantapp_task_location}}
	     	WHERE
	     	lat=".q($lat)." AND lng=".q($lng)."
	     	AND
	     	driver_lat=".q($driver_lat)." AND driver_lng=".q($driver_lng)."
	     	";	  	     		     	 
	     	if($res = Yii::app()->db->createCommand($stmt)->queryRow()){	     			     			     		
	     		return $res;
	     	}
	     		     	
	     	$unit = getOption($merchant_id,'merchant_distance_type');
	     	$unit = !empty($unit)?$unit:'M';
	     	if($unit=="mi"){
	     		$unit="M";
	     	}
	     	
	     	$provider = MapsWrapperTemp::getMapProvider();
	     	if(isset($provider['use_api_only_checkout'])){
	     	    unset($provider['use_api_only_checkout']);
	     	}	     	
	     	$provider['map_distance_results'] = 2;	     	
	     	$map_provider = isset($provider['provider'])?$provider['provider']:'';
	     	
	     	$mode = self::getModeTransportation($map_provider,$vehicle);
	     		     	
	     	MapsWrapperTemp::init($provider);
	     	$resp = MapsWrapperTemp::getDistance($driver_lat,$driver_lng,$lat,$lng,$unit,$mode
	     	);	     
	     		     	
	     	     	
	     	$params_logs = array(
	     	  'lat'=>$lat,
	     	  'lng'=>$lng,
	     	  'driver_lat'=>$driver_lat,
	     	  'driver_lng'=>$driver_lng,
	     	  'duration'=>isset($resp['duration'])?$resp['duration']:'',
	     	  'distance'=>isset($resp['distance'])?$resp['distance']:'',
	     	  'pretty_distance'=>isset($resp['pretty_distance'])?$resp['pretty_distance']:'',
	     	  'unit'=>isset($resp['unit'])?$resp['unit']:'',
	     	  'date_created'=>FunctionsV3::dateNow()
	     	);	 	     	 		     
	     	Yii::app()->db->createCommand()->insert("{{merchantapp_task_location}}",$params_logs);
	     		     		     	
	     	return $resp;
	     }
	     
	     throw new Exception( "location not valid" );
	}
	
	public static function getModeTransportation($map_provider='',$vehicle='car')
	{
		$mode ='';
		if($map_provider=="mapbox"){
			 switch ($vehicle) {
				case "truck":
				case "car":		
				case "scooter":
					$mode = 'driving';
					break;
					
				case "bike":
				case "bicycle":	
				    $mode = 'cycling';
					break;
								
				case "walk":	
				    $mode = 'walking';
					break;		
			
				default:
					$mode = 'driving-traffic'; 
					break;
			}
		} else {
			switch ($vehicle) {
				case "truck":
				case "car":		
				case "scooter":
					$mode = 'driving';
					break;
					
				case "bike":
				case "bicycle":	
				    $mode = 'bicycling';
					break;
								
				case "walk":	
				    $mode = 'walking';
					break;		
			
				default:
					$mode = 'driving'; 
					break;
			}
		}
		return $mode;
	}
	
	public static function driverPhotoUrl($avatar='')
	{
		$path_to_upload=Yii::getPathOfAlias('webroot')."/upload/driver";
		$thumbnail = FoodItemWrapper::getImage('','profile@2x.png');
		if(!empty($avatar)){
			$profile_photo_path=$path_to_upload."/".$avatar;
			if(file_exists($profile_photo_path)){
				$thumbnail=websiteUrl()."/upload/driver/".$avatar;
			}
		}		
		return $thumbnail;
	}
	
}
/*end class*/
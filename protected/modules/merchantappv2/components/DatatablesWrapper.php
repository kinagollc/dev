<?php
class DatatablesWrapper
{

	public static function q($data)
	{
		return Yii::app()->db->quoteValue($data);
	}
	
	public static function format($cols=array(), $data=array())
	{
		
		$resp = array();
		$resp['where']='';
		$resp['order']='';
		$resp['limit']='';
		
		$order=''; $where =''; $limit='';
		
		if(is_array($data) && count($data)>=1){
			
			if(isset($data['order'])){
				if(is_array($data['order']) && count($data['order'])>=1){
					foreach ($data['order'] as $val) {						
						if(array_key_exists($val['column'], (array) $cols)){
							$order = "ORDER BY ". addslashes($cols[ $val['column'] ]) ." ".addslashes($val["dir"]); 
						}
					}
				}
				if(!empty($order)){
					$resp['order']=$order;
				}
			}			
			
			if(isset($data['start']) && isset($data['length'])){
				$limit= "LIMIT ". addslashes($data['start']).",". addslashes($data['length']);
				$resp['limit']=$limit;
			} else $resp['limit']="LIMIT 0,10";
			
						
			$resp['where']='';
					
		}
				
		return $resp;
	}
	
} /*end class*/
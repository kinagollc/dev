<?php
class UpdateController extends CController
{
	public function beforeAction($action)
	{
		if(!Yii::app()->functions->isAdminLogin()){	
            Yii::app()->end();
		}		
		return true;
	}
	
	public function actionIndex(){
		
		
		$DbExt=new DbExt;
		$table_prefix=Yii::app()->db->tablePrefix;		
		$logger = array();
		
				
		$date_default = "datetime NOT NULL DEFAULT CURRENT_TIMESTAMP";
		if($res=$DbExt->rst("SELECT VERSION() as mysql_version")){
			$res=$res[0];			
			$mysql_version = (float)$res['mysql_version'];
			dump("MYSQL VERSION=>$mysql_version");
			if($mysql_version<=5.5){				
				$date_default="datetime NOT NULL DEFAULT '0000-00-00 00:00:00'";
			}
		}
		
		$stmt="						
		CREATE TABLE IF NOT EXISTS ".$table_prefix."singleapp_cart (
		  `cart_id` int(14) NOT NULL,
		  `device_id` varchar(255) DEFAULT '',
		  `device_platform` varchar(50) NOT NULL DEFAULT '',
		  `cart` text,
		  `cart_count` int(14) NOT NULL DEFAULT '0',
		  `voucher_details` text ,
		  `street` varchar(255) NOT NULL DEFAULT '',
		  `city` varchar(255) NOT NULL DEFAULT '',
		  `state` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `zipcode` varchar(100) NOT NULL DEFAULT '',
		  `delivery_instruction` varchar(255) NOT NULL DEFAULT '',
		  `location_name` varchar(255) NOT NULL DEFAULT '',
		  `contact_phone` varchar(50) NOT NULL DEFAULT '',
		  `date_modified` $date_default,
		  `tips` float(14,4) NOT NULL DEFAULT '0.0000'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;	
		
		ALTER TABLE ".$table_prefix."singleapp_cart
        ADD PRIMARY KEY (`cart_id`);
		
		ALTER TABLE ".$table_prefix."singleapp_cart
        MODIFY `cart_id` int(14) NOT NULL AUTO_INCREMENT;
		";					    
				
		echo "Creating Table singleapp_cart..<br/>";
		$DbExt->qry($stmt);		
		
		
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."singleapp_mobile_push_logs (
		  `id` int(14) NOT NULL,
		  `client_id` int(14) NOT NULL DEFAULT '0',
		  `client_name` varchar(255) NOT NULL DEFAULT '',
		  `device_platform` varchar(100) NOT NULL DEFAULT '',
		  `device_id` text,
		  `push_title` varchar(255) NOT NULL DEFAULT '',
		  `push_message` varchar(255) NOT NULL DEFAULT '',
		  `push_type` varchar(100) NOT NULL DEFAULT 'order',
		  `status` varchar(255) NOT NULL DEFAULT 'pending',
		  `json_response` text,
		  `date_created` $date_default,
		  `date_process` $date_default,
		  `ip_address` varchar(50) NOT NULL DEFAULT '',
		  `broadcast_id` int(14) NOT NULL DEFAULT '0',
		  `registration_type` varchar(3) NOT NULL DEFAULT 'fcm',
		  `merchant_id` int(14) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;	
								
		ALTER TABLE ".$table_prefix."singleapp_mobile_push_logs
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `device_platform` (`device_platform`),
		  ADD KEY `push_type` (`push_type`),
		  ADD KEY `status` (`status`);
		
		  
		ALTER TABLE ".$table_prefix."singleapp_mobile_push_logs
		  MODIFY `id` int(14) NOT NULL AUTO_INCREMENT;
		";
		
		echo "Creating Table singleapp_mobile_push_logs..<br/>";
		$DbExt->qry($stmt);
		
		echo "Updating table merchant<br/>";		
		$new_field=array( 		  
		   'single_app_keys'=>"varchar(255) NOT NULL DEFAULT ''",		  
		);
		$this->alterTable('merchant',$new_field);		
				
		
		$stmt="	
		CREATE TABLE IF NOT EXISTS ".$table_prefix."singleapp_broadcast (
		`broadcast_id` int(14) NOT NULL,
		`push_title` varchar(255) NOT NULL DEFAULT '',
		`push_message` varchar(255) NOT NULL DEFAULT '',
		`device_platform` varchar(50) NOT NULL DEFAULT '',
		`status` varchar(100) NOT NULL DEFAULT 'pending',
		`date_created` $date_default,
		`ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				
		ALTER TABLE ".$table_prefix."singleapp_broadcast
		ADD PRIMARY KEY (`broadcast_id`),
		ADD KEY `device_platform` (`device_platform`),
		ADD KEY `status` (`status`);
				
		ALTER TABLE ".$table_prefix."singleapp_broadcast
		MODIFY `broadcast_id` int(14) NOT NULL AUTO_INCREMENT;
		";
		echo "Creating Table singleapp_broadcast..<br/>";
		$DbExt->qry($stmt);
		
		$stmt="	
		CREATE TABLE IF NOT EXISTS ".$table_prefix."singleapp_pages (
		`page_id` int(14) NOT NULL,
		`merchant_id` int(14) NOT NULL DEFAULT '0',
		`title` varchar(255) NOT NULL,
		`content` text,
		`icon` varchar(100) NOT NULL DEFAULT '',
		`sequence` int(14) NOT NULL DEFAULT '0',
		`use_html` int(1) NOT NULL DEFAULT '0',
		`status` varchar(255) NOT NULL DEFAULT 'pending',
		`date_created` $date_default,
		`date_modified` $date_default,
		`ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;		
		
		ALTER TABLE ".$table_prefix."singleapp_pages
		ADD PRIMARY KEY (`page_id`);		
		
		ALTER TABLE ".$table_prefix."singleapp_pages
		MODIFY `page_id` int(14) NOT NULL AUTO_INCREMENT;
		";
		echo "Creating Table singleapp_pages..<br/>";
		$DbExt->qry($stmt);
		
		/*START OF UPDATES*/
		
		/*VERSION 1.1*/		
		echo "Updating table singleapp_cart<br/>";		
		$new_field=array( 		  
		   'points_earn'=>"int(14) NOT NULL DEFAULT '0'",
		   'points_apply'=>"int(14) NOT NULL DEFAULT '0'",
		   'points_amount'=>"float(14,4) NOT NULL DEFAULT '0.0000'",
		);
		$this->alterTable('singleapp_cart',$new_field);
		
		
		/*VERSION 1.2*/				
		$new_field=array( 		  
		   'country_code'=>"varchar(2) NOT NULL DEFAULT ''",
		   'delivery_fee'=>"float(14,4) NOT NULL DEFAULT '0.0000'",
		   'min_delivery_order'=>"float(14,4) NOT NULL DEFAULT '0.0000'",
		);
		$this->alterTable('singleapp_cart',$new_field);
		
		
		/*VERSION 2.0*/				
		$new_field=array( 		  
		   'delivery_lat'=>"varchar(50) NOT NULL DEFAULT ''",		   
		   'delivery_long'=>"varchar(50) NOT NULL DEFAULT ''",
		);
		$this->alterTable('singleapp_cart',$new_field);
		
		$new_field=array( 		  
		   'is_read'=>"int(1) NOT NULL DEFAULT '0'",		  
		);
		$this->alterTable('singleapp_mobile_push_logs',$new_field);
				
		
		/*VERSION 2.1*/							
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."singleapp_recent_location (
		`id` int(14) NOT NULL,
		`device_uiid` varchar(255) DEFAULT '',
		`search_address` text,
		`street` varchar(255) NOT NULL DEFAULT '',
		`city` varchar(255) NOT NULL DEFAULT '',
		`state` varchar(255) NOT NULL DEFAULT '',
		`zipcode` varchar(255) NOT NULL DEFAULT '',
		`country` varchar(255) NOT NULL DEFAULT '',
		`location_name` varchar(255) NOT NULL DEFAULT '',
		`latitude` varchar(100) NOT NULL DEFAULT '',
		`longitude` varchar(100) NOT NULL DEFAULT '',
		`date_created` $date_default,
		`ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		ALTER TABLE ".$table_prefix."singleapp_recent_location
		ADD PRIMARY KEY (`id`),
		ADD KEY `device_uiid` (`device_uiid`);
		
		ALTER TABLE ".$table_prefix."singleapp_recent_location
		MODIFY `id` int(14) NOT NULL AUTO_INCREMENT;
		";			
		echo "Creating Table singleapp_recent_location..<br/>";
		$DbExt->qry($stmt);
				
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."favorite_item (
		  `id` int(14) NOT NULL,
		  `client_id` int(14) NOT NULL DEFAULT '0',
		  `item_id` int(14) NOT NULL DEFAULT '0',
		  `category_id` int(14) NOT NULL DEFAULT '0',
		  `date_created` $date_default,
		  `ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		ALTER TABLE ".$table_prefix."favorite_item
        ADD PRIMARY KEY (`id`);
        
        ALTER TABLE ".$table_prefix."favorite_item
        MODIFY `id` int(14) NOT NULL AUTO_INCREMENT;
		";
		echo "Creating Table favorite_item..<br/>";
		$DbExt->qry($stmt);
		
		$new_field=array( 		  
		   'save_address'=>"int(14) NOT NULL DEFAULT '0'",
		   'distance'=>"varchar(15) NOT NULL DEFAULT ''",		   
		   'distance_unit'=>"varchar(15) NOT NULL DEFAULT ''",
		   'state_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'city_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'area_id'=>"int(14) NOT NULL DEFAULT '0'"
		);
		$this->alterTable('singleapp_cart',$new_field);
					
		$new_field=array( 		  
		   'merchant_list'=>"text",		
		);
		$this->alterTable('singleapp_broadcast',$new_field);
			
		$new_field=array( 		  
		   'state_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'city_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'area_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'country_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'search_mode'=>"varchar(50) NOT NULL DEFAULT 'address'"
		);
		$this->alterTable('singleapp_recent_location',$new_field);
		
		$this->changeFieldStructure("singleapp_cart",'device_id','text','varchar(255)');	
		
		
		/*2.2*/	
		$new_field=array( 		  
		   'latitude'=>"varchar(100) NOT NULL DEFAULT ''",
		   'longitude'=>"varchar(100) NOT NULL DEFAULT ''",		   
		);
		$this->alterTable('address_book',$new_field);
		
		$new_field=array( 		  
		   'latitude'=>"varchar(100) NOT NULL DEFAULT ''",
		   'longitude'=>"varchar(100) NOT NULL DEFAULT ''",		   
		);
		$this->alterTable('address_book_location',$new_field);
		
		$new_field=array( 		  
		   'status'=>"varchar(100) NOT NULL DEFAULT 'publish'",
		   'featured_image'=>"varchar(255) NOT NULL DEFAULT ''",		   
		);
		$this->alterTable('cuisine',$new_field);
		
		$new_field=array( 		  
		   'as_anonymous'=>"varchar(1) NOT NULL DEFAULT '0'",		   
		);
		$this->alterTable('review',$new_field);
		
		$new_field=array( 		  
		   'cancel_reason'=>"text",		   
		);
		$this->alterTable('order',$new_field);
		
		if(FunctionsV3::checkIfTableExist('driver_task')):
			$new_field=array( 		  
			   'rating'=>"int(14) NOT NULL DEFAULT '0'",
			   'rating_comment'=>"text",
			   'rating_anonymous'=>"int(1) NOT NULL DEFAULT '0'",
			);
			$this->alterTable('driver_task',$new_field);
		endif;
		
				
		
		/*2.3*/		
		$new_field=array( 		  
		   'single_app_merchant_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'social_id'=>"varchar(255) NOT NULL DEFAULT ''",		   
		   'payment_customer_id'=>"varchar(255) NOT NULL DEFAULT ''",
		   'verify_code_requested'=>$date_default
		);
		$this->alterTable('client',$new_field);
		
		Yii::app()->db->createCommand()->alterColumn('{{client}}','social_id',"varchar(255) NOT NULL DEFAULT ''");		
		Yii::app()->db->createCommand()->alterColumn('{{singleapp_cart}}','distance',"varchar(255) NOT NULL DEFAULT ''");
		
		if(!Yii::app()->db->schema->getTable("{{singleapp_device_reg}}")){
			Yii::app()->db->createCommand()->createTable("{{singleapp_device_reg}}",array(
			  'id'=>"pk",
			  'merchant_id'=>"int(14) NOT NULL DEFAULT '0'",
			  'client_id'=>"int(14) NOT NULL DEFAULT '0'",
			  'device_uiid'=>"varchar(255) NOT NULL DEFAULT ''",
			  'device_id'=>"text",
			  'device_platform'=>"varchar(50) NOT NULL DEFAULT ''",
			  'push_enabled'=>"int(1) NOT NULL DEFAULT '1'",
			  'subscribe_topic'=>"int(1) NOT NULL DEFAULT '1'",
			  'status'=>"varchar(100) NOT NULL DEFAULT 'active'",
			  'code_version'=>"varchar(14) NOT NULL DEFAULT ''",
			  'date_created'=>$date_default,
			  'date_modified'=>$date_default,
			  'ip_address'=>"varchar(50) NOT NULL DEFAULT ''"
			), 'ENGINE=InnoDB');
		}
		
		if(!Yii::app()->db->schema->getTable("{{singleapp_order_trigger}}")){
			Yii::app()->db->createCommand()->createTable("{{singleapp_order_trigger}}",array(
			  'trigger_id'=>"pk",
			  'trigger_type'=>"varchar(100) NOT NULL DEFAULT 'order'",
			  'order_id'=>"int(14) NOT NULL DEFAULT '0'",
			  'order_status'=>"varchar(255) NOT NULL DEFAULT ''",
			  'remarks'=>"text",
			  'language'=>"varchar(50) NOT NULL DEFAULT 'en'",
			  'status'=>"varchar(255) NOT NULL DEFAULT 'pending'",
			  'date_created'=>$date_default,
			  'date_process'=>$date_default,
			  'ip_address'=>"varchar(50) NOT NULL DEFAULT ''"
			), 'ENGINE=InnoDB');
		}
		
		$new_field=array( 		  
		   'cart_subtotal'=>"float(14,4) NOT NULL DEFAULT '0.0000'",		   
		   'remove_tip'=>"int(1) NOT NULL DEFAULT '0'",
		);
		$this->alterTable('singleapp_cart',$new_field);
		
		$new_field=array( 		  
		   'date_modified'=>$date_default,
		   'fcm_response'=>"text",		   
		   'fcm_version'=>"int(1) NOT NULL DEFAULT '0'",
		);
		$this->alterTable('singleapp_broadcast',$new_field);

		$this->alterTable('singleapp_device_reg',array(
		  'stic_dark_theme'=>"int(1) NOT NULL DEFAULT '0'",
		));	
		
		/*END 2.3*/
		
		
		/*2.4*/
		$logger = DatataseMigration::addColumn("{{singleapp_cart}}",array(
		  'merchant_id'=>"int(14) NOT NULL DEFAULT '0'"
		));
		/*END 2.4*/
		
		/*END OF UPDATES*/
		
		$this->addIndex('singleapp_cart','device_platform');
		$this->addIndex('singleapp_cart','device_id');								
		$this->addIndex('client','single_app_merchant_id');
		$this->addIndex('client','social_id');
		$this->addIndex('client','payment_customer_id');		
		$this->addIndex('singleapp_mobile_push_logs','is_read');
		$this->addIndex('singleapp_mobile_push_logs','merchant_id');
					
		
		$this->addIndex('singleapp_pages','status');
		$this->addIndex('singleapp_pages','merchant_id');
		
		$this->addIndex('singleapp_cart','state_id');
		$this->addIndex('singleapp_cart','city_id');
		$this->addIndex('singleapp_cart','area_id');
		
		/*UPDATE PAGES WITH LANGUAGE FILE FIELD*/
		$enabled_multiple_translation = getOptionA('enabled_multiple_translation');
		if ($enabled_multiple_translation==2){
			LanguageTable::alterTablePages("singleapp_pages");
		}
							
		
		dump($logger);
		
		?>
		<br/>
		<a href="<?php echo Yii::app()->createUrl("singlemerchant/")?>">
		 <?php echo st("Update done click here to go back")?>
		</a>
		<?php
	}	
	
	public function addIndex($table='',$index_name='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		
		$table=$prefix.$table;
		
		$stmt="
		SHOW INDEX FROM $table
		";		
		$found=false;
		if ( $res=$DbExt->rst($stmt)){
			foreach ($res as $val) {				
				if ( $val['Key_name']==$index_name){
					$found=true;
					break;
				}
			}
		} 
		
		if ($found==false){
			echo "create index<br>";
			$stmt_index="ALTER TABLE $table ADD INDEX ( $index_name ) ";
			dump($stmt_index);
			$DbExt->qry($stmt_index);
			echo "Creating Index $index_name on $table <br/>";		
            echo "(Done)<br/>";		
		} else echo "$index_name index exist<br>";
	}
	
	public function alterTable($table='',$new_field='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		$existing_field=array();
		if ( $res = Yii::app()->functions->checkTableStructure($table)){
			foreach ($res as $val) {								
				$existing_field[$val['Field']]=$val['Field'];
			}			
			foreach ($new_field as $key_new=>$val_new) {				
				if (!in_array($key_new,$existing_field)){
					echo "Creating field $key_new <br/>";
					$stmt_alter="ALTER TABLE ".$prefix."$table ADD $key_new ".$new_field[$key_new];
					dump($stmt_alter);
				    if ($DbExt->qry($stmt_alter)){
					   echo "(Done)<br/>";
				   } else echo "(Failed)<br/>";
				} else echo "Field $key_new already exist<br/>";
			}
		}
	}	
	 
	public function changeFieldStructure($table='', $field_name='', $old_type='', $new_type='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		$existing_field=array();
		if ( $res = Yii::app()->functions->checkTableStructure($table)){
			foreach ($res as $val) {
				if($val['Field']==$field_name){					
					if($val['Type']!=$new_type){						
						$stmt_alter="ALTER TABLE ".$prefix."$table CHANGE `$field_name` `$field_name` 
						$new_type NOT NULL DEFAULT ''";
						dump($stmt_alter);
						if ($DbExt->qry($stmt_alter)){
					      echo "(Done)<br/>";
				        } else echo "(Failed)<br/>";
					}
					break;
				}
			}
		}
	}
	
} /*end class*/
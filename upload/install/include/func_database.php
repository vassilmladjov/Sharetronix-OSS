<?php
	
	function delete_all_tables()
	{
		global $db, $C;
		$s = & $_SESSION['INSTALL_DATA'];
		
		if( !isset($s['MYSQL_DBNAME']) || empty($s['MYSQL_DBNAME']) ){
			return FALSE;
		}
		
		$res = $db->query('SHOW TABLES FROM '.$s['MYSQL_DBNAME']);
		while( $tbl = $db->fetch_array($res) ){ 
			$db->query("DROP TABLE IF EXISTS `".$tbl[0] . "`;");
		}
		
		return TRUE;
	}
	
	function create_all_tables()
	{
		global $db, $C;
		
		require_once $C->INCLUDEPATH.'descriptions/database-tables-create.php';
		require_once $C->INCLUDEPATH.'descriptions/database-tables-insert.php';
		
		if( !isset($db_tables) || !isset($db_insert) ){
			return FALSE;
		}
		
		if( isset($db_tables) && isset($db_insert) ){
			foreach( $db_tables as $tableName=>$tableDescription ){
				$db->query($tableDescription);
				
				if( isset($db_insert[$tableName]) ){
					foreach($db_insert[$tableName] as $insertQuery){
						$db->query($insertQuery);
					}
				}
			}
		}
		
		return TRUE;
	}
	
	function insert_all_system_tables()
	{
		global $db, $C;
		$s = & $_SESSION['INSTALL_DATA'];
		
		if( !isset($s['MYSQL_DBNAME']) || empty($s['MYSQL_DBNAME']) ){
			return FALSE;
		}
		
		$res = $db->query("TRUNCATE TABLE `plugins_tables`");
		$res = $db->query('SHOW TABLES FROM '.$s['MYSQL_DBNAME']);
		while($tbl = $db->fetch_array($res)){
			$db->query("INSERT INTO `plugins_tables` (`table`, `owner`) VALUES ('".$tbl[0] ."', 'system')");
		}
		
		
		return TRUE;
	}
	
	function delete_all_system_posts()
	{
		global $db;

		$res = $db->query('SELECT id FROM `posts` WHERE user_id=0 AND api_id=0');
		while( $obj = $db->fetch_object($res) ){
			$db->query('DELETE FROM `post_userbox` WHERE post_id="'.$obj->id.'" LIMIT 1');
			$db->query('DELETE FROM `posts` WHERE user_id=0 AND api_id=0 AND id="'.$obj->id.'" LIMIT 1');
		}
		
		return TRUE;
	}
	
	function insert_system_table( $tableName ){
		global $db;
		
		$res = $db->fetch_field('SELECT 1 FROM plugins_tables WHERE `table`="'.$tableName.'" LIMIT 1');
		if( $res ){
			return TRUE;
		}
		$db->query('INSERT INTO plugins_tables(`table`, owner) VALUES("'.$tableName.'", "system")');
		
		return TRUE;
	}
	
	function create_database()
	{
		global $db;
	
		$res = delete_all_tables();
	
		if( $res ){
			$res = create_all_tables();
			insert_all_system_tables();
		}
	
		if( !$res ){
			delete_all_tables();
		}
	
		return $res;
	}
	
	function update_database( $current_version )
	{
		global $db, $C;
		require_once $C->INCLUDEPATH.'descriptions/database-tables-update.php';
		
		if( !isset($db_updates) ){
			return FALSE;
		}
		
		if( $current_version < '1.5.4' ){
			return FALSE;
		}
		
		delete_all_system_posts();
		
		foreach($db_updates as $version=>$data){
			if( $version <= $current_version ){
				continue;
			}
			
			foreach( $data['tables'] as $tblName => $tblDescription ){
				$db->query($tblDescription);
				insert_system_table($tblName);
			}
			
			foreach( $data['queries'] as $tblInsert ){
				$db->query($tblInsert);
			}
		}
		
		if( $current_version < '3.0.0' ){
			insert_all_system_tables();
		}
		
		return TRUE;
	}

?>
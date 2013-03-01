<?php

require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';

class TableOwner {
	
	private $db;
	
	public function TableOwner(){
		$this->db = ActiveRecord::getInstance();
	}
	
	public static function assumeOwnership($table, $owner) {
		$db = ActiveRecord::getInstance();
		$sql = "
			INSERT INTO plugins_tables (
				`table`, `owner`
			) VALUES (
				'$table', '$owner'
			) ON DUPLICATE KEY 
				UPDATE owner = '$owner'
		";
		$db->query($sql);
	}
	
	
	public static function getOwner($table){
		$db = ActiveRecord::getInstance();

		$db->select('owner');
		$db->where('table', $table);
		$db->limit(1);
		$res = $db->get('plugins_tables');
		if(!empty($res)){
			return $res[0]['owner'];
		}
		return false;
	}
	
	
	public static function getTablesByOwner($owner){
		
		$db = ActiveRecord::getInstance();
		$db->where('owner', $owner);
		$res = $db->get('plugins_tables');
		if(!empty($res)){
			return $res;
		}
		return array();
	}
	
}
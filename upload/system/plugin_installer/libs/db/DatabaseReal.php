<?php

require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/db/ActiveRecord.php';
require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

class DatabaseReal implements DatabaseInterface {
	
	private $db;
	protected $plugin_name;
	
	public function DatabaseReal($plugin_name){
		$this->plugin_name = $plugin_name;
		$this->db = ActiveRecord::getInstance();
	}
	
	
	public function create_table($table_name) {
		
		$sql = sprintf("
			CREATE TABLE %s ( 
				id INT(11) NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (id)
			) 
			", $table_name
		);
		$this->execute($sql);
		
		
		TableOwner::assumeOwnership($table_name, $this->plugin_name);
	}
	
	
	public function drop_table($table_name) {
		
		$sql = sprintf("DROP TABLE IF EXISTS %s", $table_name);
		$this->execute($sql);
	}
	
	
	public function add_field($table_name, $field_name, $field_type, $options = array()) {
		
		$null = "NULL";
		$limit = false;
		$default = "DEFAULT NULL";
		$type = $field_type;
		
		foreach($options as $opt) {
			switch (get_class($opt)){
				case "FieldOptionLimit":
					$type .= "(" . $opt->getValue() . ")"; 
					break;
				case "FieldOptionDefault":
					$val = $opt->getValue();
					$default = "DEFAULT ";
					if((int)$val !== 0) {
						$default .= $val;
					}  else {
						$default .= "'" . $val . "'";
					}
					break;
				case "FieldOptionNull":
					if($opt->getValue() == false) {
						$null = " NOT NULL";
					}
					break;
				default:
					throw new Exception("Unsupported field option");
			}
		}
		
		if($limit == false) {
			if($type == ColumnType::INTEGER) {
				$type .= "(11) ";
			}
			if($type == ColumnType::STRING) {
				$type .= "(255) ";
			}
		}
		
		$sql = "
			ALTER TABLE  $table_name 
				ADD COLUMN $field_name $type $null $default 
		";
		$this->execute($sql);
		
		TableOwner::assumeOwnership($table_name, $this->plugin_name);
		
	}
	
	
	public function remove_field($table_name, $field_name) {
		$sql = sprintf("
			ALTER TABLE %s DROP COLUMN %s", $table_name,  $field_name
		);
		$this->execute($sql);
		
	}
	

	public function execute($sql) {
		try {
			$this->db = ActiveRecord::getInstance();
			$this->db->query($sql);
		} catch (Exception $e) {
			print "Exception: " . $e->getMessage() . PHP_EOL;
			print $sql;
		}
		//$sql = str_replace(PHP_EOL, "<br />", $sql);
		//$sql = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $sql);
		//print $sql;
	}
	
	
}

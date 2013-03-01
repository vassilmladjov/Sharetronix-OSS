<?php

require_once $C->INSTALLER_PATH . 'libs/db/DatabaseInterface.php';
require_once $C->INSTALLER_PATH . 'libs/validation/ValidationError.php';
require_once $C->INSTALLER_PATH . 'libs/validation/ValidationNotice.php';
require_once $C->INSTALLER_PATH . 'libs/TableOwner.php';

class DatabaseValidate implements DatabaseInterface {
	
	private $db;
	private $plugin_name;
	protected $messages; // array<ValidationMessage>
	
	public function DatabaseValidate($plugin_name){
		$this->db = ActiveRecord::getInstance();
		$this->plugin_name = $plugin_name;
	}
	
	public function create_table($table_name) {
		$sql = "SHOW TABLES LIKE '$table_name'";
		$res = $this->db->query($sql);
		if(!empty($res) && count($res) > 0) {
			$this->messages[] = new ValidationError("Table with name " . $table_name . " already exists" );
			return;
		} else {
			$this->messages[] = new ValidationNotice("New table called $table_name will be created");
		}
	}
	
	public function drop_table($table_name) {
		$owner = TableOwner::getOwner($table_name);
		if($owner !== $this->plugin_name  && $owner !== false) {
			$this->messages[] = new ValidationError(" Trying to drop table `$table_name` without proper permissions");
		} else {
			$this->messages[] = new ValidationNotice(" Table $table_name will be deleted ");
		}
	}
	
	public function add_field($table_name, $field_name, $field_type, $options = array()) {
		$owner = TableOwner::getOwner($table_name);
		if($owner !== $this->plugin_name && $owner !== "system" && $owner !== false) {
			$this->messages[] = new ValidationError(" Trying to add field to table `$table_name` without proper permissions");
		} else {
			$this->messages[] = new ValidationNotice(" A new field `$field_name` will be added to table `$table_name`");
		}
	}
	
	public function remove_field($table_name, $field_name) {
		$owner = TableOwner::getOwner($table_name);
		if($owner !== $this->plugin_name && $owner !== false) {
			$this->messages[] = new ValidationError(" Trying to remove field from table `$table_name` without proper permissions");
		} else {
			$this->messages[] = new ValidationNotice(" Field `$field_name` will be removed from table `$table_name` ");
		}
	}
	
	public function execute($sql) {
		$this->messages[] = new ValidationNotice(" The following sql query is going to be executed: <pre>$sql</pre>");
	}
	
	
	public function getResult(){
		return $this->messages;
	}
}

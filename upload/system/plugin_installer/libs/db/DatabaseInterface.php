<?php
interface DatabaseInterface {
	
	
	
	/**
	 * create_table
	 * 
	 * @access	public
	 * @param 	string	 $table_name
	 * @return	void
	 */
	public function create_table($table_name);
	
	
	/**
	 * drop_table
	 * 
	 * @access	public
	 * @param 	string	 $table_name
	 * @return	void
	 */
	public function drop_table($table_name);
	
	
	/**
	 * add_field
	 * 
	 * @access	public
	 * @param 	string				$table_name
	 * @param 	string				$field_name
	 * @param 	DatabaseType 		$field_type
	 * @param 	array<FieldOption>  $options
	 * @return	void
	 */
	public function add_field($table_name, $field_name, $field_type, $options = array());
	
	
	/**
	 * remove_field
	 * 
	 * @access	public	
	 * @param 	string	 $table_name
	 * @param 	string	 $field_name
	 * @return	void
	 */
	public function remove_field($table_name, $field_name);
	
	
	
	/**
	 * execute
	 * 
	 * @access	public
	 * @param 	string	 $sql
	 * @return	bool
	 */
	public function execute($sql);
	
	
}


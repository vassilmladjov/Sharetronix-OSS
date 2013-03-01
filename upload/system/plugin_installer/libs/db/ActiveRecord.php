<?php

/**
 * @file		ActiveRecord Class
 * @author		Hristo Georgiev
 * @contact		me@hgeorgiev.com
 * @company		hgeorgiev.com
 * @license		check License.txt
 */

define('__ENVIRONMENT__', 'browser');
class ActiveRecord{

	private static $instance;

	public $link;
	public $debug = false;

	private $affectedRows;

	/* query variables */
	private $fields = " * ";
	private $from;
	private $join_tables;
	private $where = "";
	private $limit = "";
	private $order_by = "";
	private $group_by = "";
	private $having = '';

	/**
	 * constructor
	 * establish database connection
	 *
	 * @access private
	 * @param void
	 * @return void
	 */
	private function ActiveRecord(){

		global $C;

		/* connect to db */
		try {
			$this->link = new PDO('mysql:host=' . $C->DB_HOST . ";dbname=" . $C->DB_NAME, $C->DB_USER, $C->DB_PASS);
			$this->link->exec("SET CHARACTER SET UTF-8");
			$this->link->exec('SET NAMES utf8');
			$this->link->exec("SET lc_time_names = 'bg_BG'");
		} catch(PDOException $e) {
			 
			print "Error connecting database!";
			//print $e->getMessage();
			die();
		}

		unset($this->user);
		unset($this->pass);
	}


	/**
	 * getInstance
	 * returns instance of the database class
	 *
	 * @access	public
	 * @param	void
	 * @return	ActiveRecord
	 */
	public static function getInstance(){

		if( empty(self::$instance) ){
			$c = __CLASS__;
			self::$instance = new $c();
		}

		return self::$instance;
	}


	/**
	 * select
	 *
	 * @access   public
	 * @param    string   $fields
	 * @return   void
	 */
	public function select($fields){
		$this->fields = $fields;
	}


	/**
	 * from
	 *
	 * @access   public
	 * @param    string   $from
	 * @return   void
	 */
	public function from($from){
		$this->from = $from;
	}


	/**
	 * where
	 *
	 * @access   public
	 * @param    array/string    $key
	 * @param	string			$value
	 * @param	bool			$strip
	 * @return   void
	 */
	public function where($key, $value = null, $strip = false){

		/* if we have string with spaces just return it */
		if(!is_array($key) && strpos($key, " ") >= 0 && $value === null){
			$this->where = $key;
			return $key;
		}
		 
		 
		/* decide how we`re goin to handle input data */
		if(is_array($key) && $value == null){
			$where = $key;
		} else {
			$where = array($key => $value);
		}

		/* decide how to start the query */
		if(trim($this->where) != ""){
			$where_string = " AND ";
		} else {
			$where_string = " WHERE ";
		}

		/* build the query */
		foreach($where as $key=>$w){
			//var_dump($key, $w);

			$operations = array('!=', '=', '>', '<', '<>', '>=', '<=', 'LIKE', 'IS'); /* available operators */
			$set_operator = current(explode(' ', $w));

			if(in_array(strtoupper($set_operator),$operations)){
				$operator = $set_operator;
				if(strtoupper(trim($operator)) == "IS"){
					$strip = true;
				}
				$w = str_replace($operator, '', $w);
				$w = trim($w);
			} else {
				$operator = " = ";
			}

			$where_string .= " `$key` " . $operator;
			$res = filter_var($w, FILTER_VALIDATE_INT);
			
			if($res == true || $strip == true){
				$where_string .= " $w ";
			} else {
				$where_string .= " '$w' ";
			}
			
			$where_string .= " AND";
		}
		
		$where_string = rtrim($where_string, 'AND');
		$this->where .= $where_string;

	}

	/**
			* having
			*
			* @access   public
			* @param    array/string    $key
			* @param	string			$value
			* @param	bool			$strip
			* @return   void
			*/
			public function having($key, $value = null, $strip = false) {
			/* if we have string with spaces just return it */
			if(!is_array($key) && strpos($key, " ")){
				$this->having = $key;
				return $key;
			}
				 
				/* decide how we`re goin to handle input data */
				if(is_array($key) && $value == null){
				$where = $key;
				 } else {
				$where = array($key => $value);
			}

			/* decide how to start the query */
					if(trim($this->having) != ""){
						$where_string = " AND ";
					} else {
					$where_string = " HAVING ";
					}

					/* build the query */
					foreach($where as $key=>$w){

						$operations = array('!=', '=', '>', '<', '<>', '>=', '<=', 'LIKE', 'IS'); /* available operators */
						$set_operator = current(explode(' ', $w));

						if(in_array(strtoupper($set_operator),$operations)){
							$operator = $set_operator;
							$w = str_replace($operator, '', $w);
						} else {
						$operator = " = ";
						}

						$where_string .= " $key " . $operator;
						$res = filter_var($w, FILTER_VALIDATE_INT);
		    if($res == true || $strip == true){
		    $where_string .= " $w ";
						} else {
							$where_string .= " '$w' ";
						}
						$where_string .= " AND";
						}
						$where_string = rtrim($where_string, 'AND');
						$this->having .= $where_string;
						}

						/**
						* order_by
							*
								* @access   public
								* @param    mixed   $order_by
									* @return   void
									*/
									public function order_by($order){
									$order_string = " ORDER BY ";
									if(is_array($order)){
									$order_string .= array_shift(array_keys($order)) . " " . array_shift(array_values($order));
} else {
$order_string .= $order . " ASC";
}
$this->order_by = $order_string;
}


/**
* group_by
	*
		* @access   public
		* @param    mixed   group_by
		* @return   void
			*/
			public function group_by($group_by){
			$this->group_by = " GROUP BY " . $group_by;
		}

		/**
		* limit
			*
				* @access   public
				* @param    mixed $limit
				* @return   void
				*/
				public function limit($limit){
				if(is_array($limit)){
				$limit_string = " LIMIT " . array_shift(array_keys($limit)) . ", " . array_shift(array_values($limit)) . " ";
} else {
	$limit_string = " LIMIT "  . (int)$limit . " ";
}
$this->limit = $limit_string;
 }


	 /**
	  * join
	  *
	  * @access   public
	  * @param    string   $table
	  * @param    string   $on
	  * @param    string   $type
	  * @return   void
	  */
	public function join($table, $on, $type = null){
		$join_types = array('left', 'right');

 		$join_str = "";
		if($type !== null && in_array(strtolower($type), $join_types)){
			$join_str .= strtoupper($type) . " ";
 		}
 		
 		$join_str .= "JOIN " . $table . " ON " . $on . " \n";

 		$this->join_tables .= $join_str;
 	}

	/**
 	* get
 	*
 	* @access   public
 	* @param    string  $table
 	* @return   array
 	*/
	public function get($table = null){

 		/* set public table if set */
 		if($table != null){
 			$this->from = $table;
 		}

 		/* build query and get data */
 		$sql = " SELECT " . $this->fields . "
 			FROM " . $this->from . "
 			" . $this->join_tables . "
 			" . $this->where . "
 			" . $this->group_by . "
 			" . $this->having . "
 			" . $this->order_by . "
 			" . $this->limit . "
 			 ";

		if($this->debug === true ){
			if(__ENVIRONMENT__ == "srv"){
				print "\n \t $sql \n \n";
			} else {
				print "<hr /> " . str_replace("\n", "<br />", $sql ) . "<hr /> ";
 			}
		}

		$this->clearQueryVars();

		$query = $this->link->query($sql);
		if(is_object($query)){
			$data = $query->fetchAll(PDO::FETCH_ASSOC);
			return $data;
 		}
	}


	/**
	 * query
	 * executes query to mysql
	 *
	 * @access	public
	 * @param	string	$sql
	 * @param   bool    $return
	 * @return	array
	 */
	public function query($sql){
 		if($this->debug == true){
 			print $sql;
 		}
		$query = $this->link->query($sql);
 		
		if(is_object($query)) {
 			$data = $query->fetchAll(PDO::FETCH_ASSOC);
 			$this->affectedRows = $query->rowCount();
			return $data;
		} else {
			//if(__ENVIRONMENT__ == "srv"){
				print $sql;
			//}
			$error = $this->link->errorInfo();
			$error_message = "";
			foreach($error as $e){
				$error_message = $e . " / ";
			}
			throw new Exception($error_message);
		}
		$this->affectedRows = 0;
		return;
	}


	/**
	* insert
	*
	* @access	public
	* @param	$table	string
	* @param	$data	array
	* @return	void
	*/
	public function insert($table, $data){
		$table_fields = array_keys($data);
 		$fields = implode(", ", $table_fields);
 		$values = "'" . implode("', '", $data) . "'";

 		$sql = "
 			INSERT INTO $table (
 				$fields
			) VALUES (
				$values
			)
 		";
		if($this->debug === true){
			print $sql;
		}

		$this->query($sql);
		$this->clearQueryVars();
		return;
	}


	/**
	 * update
	 *
	 * @access	public
	 * @param	$table	string
	 * @param	$data	array
	 * @param	$strip	bool
	 * @return	bool
	 */
	public function update($table, $data, $strip = false){
		if(trim($this->where) == ""){
			throw new Exception("Before update you should call WHERE method");
		}
	
		/* update statements */
		$set = '';
		foreach ($data as $key=>$d){
			if($strip == true){
				$set .= "$key = $d, ";
			} else {
				$set .= "$key = '$d', ";
			}
		}
	
		$set = substr($set, 0, strlen($set) - 2 );
	
		/* build query */
		$sql = "
			UPDATE $table SET $set " . $this->where . ' ' . $this->order_by . ' ' . $this->limit . '
		';
		if($this->debug === true){
			print $sql;
		}
		
		$this->query($sql);
		$this->clearQueryVars();
		return;
	}


	/**
	* delete
	*
	* @access	public
	* @param	$table	string
	* @return	void
	*/
	public function delete($table) {
		if(trim($this->where) == ""){
			throw new Exception("Before delete you should invoke WHERE method");
		}
		$sql = "DELETE FROM $table $this->where";
		$this->query($sql);
	
		$this->clearQueryVars();
	
		if($this->debug == true) {
			print $sql;
		}
		return;
	
	}


	/**
	 * getPrimaryKey
	 * returns name of the primary key field from table
	 *
	 * @access   public
	 * @param    $table     string
	 * @return   string
	 */
	public function getPrimaryKey($table){
		throw new Exception("Method not implemented");
	}


	/**
	 * last_id
	 *
	 * @access   public
	 * @param    void
	 * @return   int
	 */
	public function last_id(){
 		return $this->link->lastInsertId();
	}


	public function getAffectedRows(){
		return $this->affectedRows;
	}


	/**
	 * clearQueryVars
	 * clear all query class vars
	 *
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function clearQueryVars(){
	$this->fields = "*";
		$this->from = "";
		$this->where = "";
		$this->order_by = "";
		$this->limit = "";
		$this->join_tables = "";
		$this->group_by = "";
		$this->having = '';
	}


	/**
	 * __clone
	 * prevents cloning the object
	 *
	 * @access	public
	 * @param	void
	 * @return	void
	 */
	 public function __clone() {
	 	trigger_error('Clone is not allowed.', E_USER_ERROR);
	 }


	 /**
	  * destructor
	  * closes mysql connection
	  *
	  * @access	public
	  * @param	void
	  * @return	void
	  */
	 public function __destruct(){
		 //$this->link = null;
	 	return;
	}
}



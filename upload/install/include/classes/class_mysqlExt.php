<?php
	
	class mysqlExt extends mysql
	{
		public function connect()
		{
			$time	= microtime(TRUE);
			$this->connection	= $this->ext=='mysqli' ? mysqli_connect(/*'p:'.*/$this->dbhost, $this->dbuser, $this->dbpass) : mysql_connect($this->dbhost, $this->dbuser, $this->dbpass);
			
			if( !isset($_SESSION['INSTALL_DATA']['MYSQL_MYEXT']) || empty($_SESSION['INSTALL_DATA']['MYSQL_MYEXT']) ){
				$_SESSION['INSTALL_DATA']['MYSQL_MYEXT'] = $this->ext;
			}
			
			if(FALSE == $this->connection) {
				return 1;
			}
			$db	= $this->ext=='mysqli' ? mysqli_select_db($this->connection, $this->dbname) : mysql_select_db($this->dbname, $this->connection);
			if(FALSE == $db) {
				return 2;
			}
			
			return $this->connection;
		}
		
		public function get_server_info() 
		{
			return $this->ext=='mysqli' ? @mysqli_get_server_info($this->connection) : @mysql_get_server_info($this->connection);
		}
		
		public function fetch_array($res) 
		{	
			return $this->ext=='mysqli' ? mysqli_fetch_array($res) : mysql_fetch_array($res);
		}
	}
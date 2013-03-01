<?php
	$s = &$_SESSION['INSTALL_DATA'];
	
	$D->error	= FALSE;
	$D->errmsg	= '';
	$D->MYSQL_HOST		= '';
	$D->MYSQL_USER		= '';
	$D->MYSQL_PASS		= '';
	$D->MYSQL_DBNAME	= '';
	
	if( isset($_POST['MYSQL_HOST'], $_POST['MYSQL_USER'], $_POST['MYSQL_DBNAME']) ) {

		$D->MYSQL_HOST	= trim($_POST['MYSQL_HOST']);
		$D->MYSQL_USER	= trim($_POST['MYSQL_USER']);
		$D->MYSQL_PASS	= trim($_POST['MYSQL_PASS']);
		$D->MYSQL_DBNAME= trim($_POST['MYSQL_DBNAME']);	
		
		if( empty($D->MYSQL_HOST) ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill your MySQL host name, usually "localhost".';
		}elseif( empty($D->MYSQL_USER) ){
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill your MySQL username.';
		}elseif(empty($D->MYSQL_DBNAME)){
			$D->error	= TRUE;
			$D->errmsg	= 'Please fill the empty MySQL database name.';
		}
		
		if( ! $D->error ) {
			$db 		= new mysqlExt($D->MYSQL_HOST, $D->MYSQL_USER, $D->MYSQL_PASS, $D->MYSQL_DBNAME);
			$conn 		= @$db->connect();

			if( is_numeric($conn) && $conn === 1  ){
				$D->error	= TRUE;
				$D->errmsg	= 'Cannot connect - please check host, username and password.';
			}elseif( is_numeric($conn) && $conn === 2  ){
				$D->error	= TRUE;
				$D->errmsg	= 'Database does not exist.';
			}
		}
		
		if( !$D->error ) {
			$tbl	= $db->query('SHOW TABLES FROM '.$D->MYSQL_DBNAME);
			if( $tbl && $db->num_rows($tbl)>0 ) {
				$D->error	= TRUE;
				$D->errmsg	= 'Database must be empty - this one contains one or more tables.';
			}
		}
		
		if( !$D->error ){
			$tmp =  (isset($conn->server_info) && !empty($conn->server_info))? $conn->server_info : $db->get_server_info();
			$tmp	= str_replace('.','',substr($tmp, 0, 5));
			$tmp	= intval($tmp);
			$s['MYSQL_SERVER_VERSION'] = $tmp;
			
			if( $tmp < 500 ) {
				$D->error	= TRUE;
				$D->errmsg	= 'Your MySQL server doesn\'t meet sharetronix requirenments, version 5.0 or higher required.';
			}
		}
		
		if( ! $D->error ) {
			$s['MYSQL_HOST'] = $D->MYSQL_HOST;
			$s['MYSQL_USER'] = $D->MYSQL_USER;
			$s['MYSQL_PASS'] = $D->MYSQL_PASS;
			$s['MYSQL_DBNAME'] = $D->MYSQL_DBNAME;
			
			$_SESSION['INSTALL_STEP']	= 3;
			header('Location: ?r='.rand(0,99999));
		}
	}
	
	loadTemplate('step_2');
	
?>
<?php
	
	$PAGE_TITLE	= 'Installation - Step 2';
	
	$s	= & $_SESSION['INSTALL_DATA'];
	$conn	= FALSE;
	
	$is_upgrade	= isset($OLDC->DB_HOST, $OLDC->DB_USER, $OLDC->DB_PASS, $OLDC->DB_NAME);
	
	if( $is_upgrade ) {
		$s['MYSQL_MYEXT']	= isset($OLDC->DB_MYEXT)&&$OLDC->DB_MYEXT=='mysqli' ? 'mysqli' : myext();
		$s['MYSQL_HOST']	= trim($OLDC->DB_HOST);
		$s['MYSQL_USER']	= trim($OLDC->DB_USER);
		$s['MYSQL_PASS']	= trim($OLDC->DB_PASS);
		$s['MYSQL_DBNAME']	= trim($OLDC->DB_NAME);
		if( !empty($s['MYSQL_HOST']) && !empty($s['MYSQL_USER']) && !empty($s['MYSQL_DBNAME']) ) {
			$conn	= my_mysql_connect($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS']);
			if($conn) {
				$dbs	= my_mysql_select_db($s['MYSQL_DBNAME'], $conn);
				if($dbs) {
					$_SESSION['INSTALL_STEP']	= 2;
					header('Location: ?next&r='.rand(0,99999));
				}
			}
		}
	}
	
	if( ! isset($s['MYSQL_HOST']) ) {
		$s['MYSQL_HOST']	= isset($OLDC->DB_HOST) ? $OLDC->DB_HOST : 'localhost';
	}
	if( ! isset($s['MYSQL_USER']) ) {
		$s['MYSQL_USER']	= isset($OLDC->DB_USER) ? $OLDC->DB_USER : '';
	}
	if( ! isset($s['MYSQL_PASS']) ) {
		$s['MYSQL_PASS']	= isset($OLDC->DB_PASS) ? $OLDC->DB_PASS : '';
	}
	if( ! isset($s['MYSQL_DBNAME']) ) {
		$s['MYSQL_DBNAME']	= isset($OLDC->DB_NAME) ? $OLDC->DB_NAME : '';
	}
	if( ! isset($s['MYSQL_MYEXT']) ) {
		$s['MYSQL_MYEXT']	= isset($OLDC->DB_MYEXT)&&$OLDC->DB_MYEXT=='mysqli' ? 'mysqli' : myext();
	}
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	if( isset($_POST['MYSQL_HOST'], $_POST['MYSQL_USER'], $_POST['MYSQL_PASS'], $_POST['MYSQL_DBNAME']) ) {
		$_SESSION['INSTALL_STEP']	= 1;
		$submit	= TRUE;
		$s['MYSQL_HOST']	= trim($_POST['MYSQL_HOST']);
		$s['MYSQL_USER']	= trim($_POST['MYSQL_USER']);
		$s['MYSQL_PASS']	= trim($_POST['MYSQL_PASS']);
		$s['MYSQL_DBNAME']	= trim($_POST['MYSQL_DBNAME']);
		if( empty($s['MYSQL_HOST']) || empty($s['MYSQL_USER']) || empty($s['MYSQL_DBNAME']) ) {
			$error	= TRUE;
			$errmsg	= 'Please fill all the fields.';
		}
		if( ! $error ) {
			if( $s['MYSQL_MYEXT']=='mysqli' && function_exists('mysqli_connect') ) {
				$conn	= @mysqli_connect($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS']);
				if( !$conn && function_exists('mysql_connect') ) {
					$conn	= @mysql_connect($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS']);
					if( $conn ) {
						$s['MYSQL_MYEXT'] == 'mysql';
					}
				}
			}
			elseif( $s['MYSQL_MYEXT']=='mysql' && function_exists('mysql_connect') ) {
				$conn	= @mysql_connect($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS']);
				if( !$conn && function_exists('mysqli_connect') ) {
					$conn	= @mysqli_connect($s['MYSQL_HOST'], $s['MYSQL_USER'], $s['MYSQL_PASS']);
					if( $conn ) {
						$s['MYSQL_MYEXT'] == 'mysqli';
					}
				}
			}
			if( ! $conn ) {
				$error	= TRUE;
				$errmsg	= 'Cannot connect - please check host, username and password.';
			}
		}
		if( ! $error ) {
			$dbs	= my_mysql_select_db($s['MYSQL_DBNAME'], $conn);
			if( ! $dbs ) {
				$error	= TRUE;
				$errmsg	= 'Database does not exist.';
			}
		}
		if( !$error && !$is_upgrade ) {
			$tbl	= my_mysql_query('SHOW TABLES FROM '.$s['MYSQL_DBNAME'], $conn);
			if( $tbl && my_mysql_num_rows($tbl)>0 ) {
				$error	= TRUE;
				$errmsg	= 'Database must be empty - this one contains one or more tables.';
			}
		}
		if( ! $error ) {
			$_SESSION['INSTALL_STEP']	= 2;
			header('Location: ?next&r='.rand(0,99999));
		}
	}
	
	$html	.= '<h1 class="pagetitle">Database Settings</h1>
			<p>Fill in the information about the MySQL database. For new '.SITE_TITLE.' installations you must create an <b>empty</b> MySQL database.</p> <br /><br />';
	if( $error ) {
		$html	.= errorbox('Error', $errmsg);
	}
	$html	.= '
				<form method="post" action="">
					<table class="form-container">
						<tr>
							<td class="field-title">MySQL Host:</td>
							<td>
								<input type="text" class="setinp" name="MYSQL_HOST" value="'.htmlspecialchars($s['MYSQL_HOST']).'" />
								<div class="hint">Usually "localhost</div>														
							</td>
						</tr>
						<tr>
							<td class="field-title">Username:</td>
							<td><input type="text" autocomplete="off" class="setinp" name="MYSQL_USER" value="'.htmlspecialchars($s['MYSQL_USER']).'" /></td>
						</tr>
						<tr>
							<td class="field-title">Password:</td>
							<td><input type="password" autocomplete="off" class="setinp" name="MYSQL_PASS" value="'.htmlspecialchars($s['MYSQL_PASS']).'" /></td>
						</tr>
						<tr>
							<td class="field-title">Database Name:</td>
							<td><input type="text" class="setinp" name="MYSQL_DBNAME" value="'.htmlspecialchars($s['MYSQL_DBNAME']).'" /></td>
						</tr>
						<tr>
							<td class="field-title"></td>
							<td><button type="submit" name="submit" value="Continue" class="btn blue"><span>Continue</span></button></td>
						</tr>
					</table>
				</form>
			';
	
?>
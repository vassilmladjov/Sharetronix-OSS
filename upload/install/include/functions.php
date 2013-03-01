<?php
	
	function loadController( $controller_name )
	{
		global $C, $D;
		
		$controller = $C->INCLUDEPATH.'controllers/'.$controller_name.'.php';
		
		file_exists( $controller )?  require_once( $controller ) : require_once( $C->INCLUDEPATH.'controllers/error404.php' );
	}
	
	function loadTemplate( $template_name )
	{
		global $C, $D;
	
		$template = $C->INCLUDEPATH.'templates/'.$template_name.'.php'; 
		
		require_once( $C->INCLUDEPATH.'templates/html_header.php' );
		if( file_exists( $template ) ){
			require_once( $template );
		}
		require_once( $C->INCLUDEPATH.'templates/html_footer.php' );
	}
	
	function config_replace_variable($source, $variable, $value, $keep_quots=TRUE)
	{
		$pattern	= '/('.preg_quote($variable).'\s*\=\s*)\'([^\\\']*)(\'\s*)/su';
		if( $keep_quots ) {
			return preg_replace($pattern, '${1}\''.$value.'\'${2}', $source);
		}
		return preg_replace($pattern, '${1}'.$value.'${2}', $source);
	}
	
	function get_old_version()
	{
		global $C, $db;
		
		$tmp_incpath 		= $C->INCPATH;
		$tmp_includepath 	= $C->INCLUDEPATH;
		$tmp_version		= FALSE;
		$tmp_debugmode 		= $C->DEBUG_MODE;
		$tmp_siteurl		= '';
		$error 				= FALSE;
		
		$file	= $tmp_incpath.'conf_main.php';
		if( file_exists($file) ) {
			$C	= new stdClass;
			$C->INCPATH	= realpath($tmp_incpath);
			include($file);			
			
			$tmp_siteurl = $C->SITE_URL;

			if( isset($C->VERSION) ){
				$tmp_version = $C->VERSION;
			}
			
			if( !isset($C->DB_HOST) || !isset($C->DB_USER) || !isset($C->DB_PASS) || !isset($C->DB_NAME) ){
				$error = TRUE;
			}
			if( !$error ){
				$C->DEBUG_MODE = $tmp_debugmode;
				
				$_SESSION['INSTALL_DATA']['MYSQL_HOST'] = $C->DB_HOST;
				$_SESSION['INSTALL_DATA']['MYSQL_USER'] = $C->DB_USER;
				$_SESSION['INSTALL_DATA']['MYSQL_PASS'] =  $C->DB_PASS;
				$_SESSION['INSTALL_DATA']['MYSQL_DBNAME'] = $C->DB_NAME;
				$_SESSION['INSTALL_DATA']['SITE_URL'] = $C->SITE_URL;
				
				$db = new mysqlExt($C->DB_HOST, $C->DB_USER, $C->DB_PASS, $C->DB_NAME);
				if( $db === 1 || $db === 2 ){
					$error = TRUE;
				}
			}
			
			$C = new stdClass;
			$C->DEBUG_MODE = $tmp_debugmode;
			$C->SITE_URL = $tmp_siteurl;
			include($tmp_includepath.'../config/config.php');
			
			return ($tmp_version && !$error)? $tmp_version : FALSE ;
		}
		
		return FALSE;
	}
	
	function shadow_password($pass)
	{
		if( strlen($pass) === 0 ){
			return '';
		}
		return (string) implode('', array_fill(0, strlen($pass)-1, '*') );
	}
	
	/*function directory_tree_is_writable($node)
	{
		$node	= realpath($node);
		if( ! $node ) {
			return TRUE;
		}
		if( !is_readable($node) || !is_writable($node) ) {
			@chmod($node, 0777);
			if( !is_readable($node) || !is_writable($node) ) {
				return FALSE;
			}
		}
		if( is_dir($node) ) {
			$dir	= opendir($node);
			while($file = readdir($dir)) {
				if( $file == '.' || $file == '..' ) {
					continue;
				}
				if( ! directory_tree_is_writable($node.'/'.$file) ) {
					return FALSE;
				}
			}
			closedir($dir);
		}
		return TRUE;
	}
	
	function directory_tree_delete($node)
	{
		$node	= realpath($node);
		if( ! $node ) {
			return;
		}
		if( ! is_dir($node) ) {
			@unlink($node);
			return;
		}
		$dir	= opendir($node);
		while($file = readdir($dir)) {
			if( $file == '.' || $file == '..' ) {
				continue;
			}
			directory_tree_delete($node.'/'.$file);
		}
		closedir($dir);
		@rmdir($node);
		return;
	}
	
	*/
?>
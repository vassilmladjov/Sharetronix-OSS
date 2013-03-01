<?php

	if( isset($_POST['pass_type']) ){
		if( empty($_POST['pass_type'])){
			echo '';
			return;
		}
		
		$pass_type = trim($_POST['pass_type']);
		
		echo $pass_type == 'admin'? $_SESSION['INSTALL_DATA']['ADMIN_PASS'] : $_SESSION['INSTALL_DATA']['MYSQL_PASS'];
		return;
	}

	ini_set('memory_limit', -1);

	$D->error	= FALSE;
	$D->errmsg	= '0';
	$s = &$_SESSION['INSTALL_DATA'];
	$s['LANGUAGE']	= 'en';

	if( ! file_exists( $C->INCPATH.'../storage/attachments/1/' ) ) {
		@mkdir( $C->INCPATH.'../storage/attachments/1/' );
	}
	@chmod( $C->INCPATH.'../storage/attachments/1/', 0777 );
	
	$s['SITE_URL']	= rtrim($s['SITE_URL'],'/').'/';
	
	if( isset($_POST['submit']) ){ 
	
		$rwbase	= '/';
		$tmp	= preg_replace('/^http(s)?\:\/\//', '', $s['SITE_URL']);
		$tmp	= trim($tmp, '/');
		$pos	= strpos($tmp, '/');
		if( FALSE !== $pos ) {
			$tmp	= substr($tmp, $pos);
			$tmp	= '/'.trim($tmp,'/').'/';
			$rwbase	= $tmp;
		}
		$htaccess	= '<IfModule mod_rewrite.c>'."\n";
		$htaccess	.= '	RewriteEngine On'."\n";
		$htaccess	.= '	RewriteBase '.$rwbase."\n";
		$htaccess	.= '	RewriteCond %{REQUEST_FILENAME} !-f'."\n";
		$htaccess	.= '	RewriteCond %{REQUEST_FILENAME} !-d'."\n";
		$htaccess	.= '	RewriteRule ^(.*)$ index.php?%{QUERY_STRING} [NE,L]'."\n";
		$htaccess	.= '	RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]'."\n";
		$htaccess	.= '</IfModule>'."\n";
		$filename	= $C->INCPATH.'../.htaccess';
		$res	= file_put_contents($filename, $htaccess);
		if( ! $res ) {
			$D->error	= TRUE;
			$D->errmsg	= '{Can\'t write your ./.htaccess file, please check it\'s permissions}';
		}
		@chmod($filename, 0777);
	

		if( ! $D->error ) {
			
			require_once($C->INCLUDEPATH.'func_database.php');
			$res	= create_database();
			if( ! $res ) {
				$D->error	= TRUE;
				$D->errmsg	= '{Error occured while we tried to create your community database}';
			}
		}

		if( ! $D->error ) {
			$config	= @file_get_contents($C->INCLUDEPATH.'conf_main_empty.php');
			if( ! $config ) {
				$D->error	= TRUE;
				$D->errmsg	= '{File ./install/include/conf_main_empty.php was not found, pease check your download package and include it}';
			}
			if( ! $D->error ) {
				$rndkey	= substr(md5(time().rand()),0,5);
				$config	= config_replace_variable( $config,	'$C->DOMAIN',		$s['DOMAIN'] );
				$config	= config_replace_variable( $config,	'$C->SITE_URL',		$s['SITE_URL'] );
				$config	= config_replace_variable( $config,	'$C->RNDKEY',	$rndkey );
				$config	= config_replace_variable( $config,	'$C->DB_HOST',	$s['MYSQL_HOST'] );
				$config	= config_replace_variable( $config,	'$C->DB_USER',	$s['MYSQL_USER'] );
				$config	= config_replace_variable( $config,	'$C->DB_PASS',	$s['MYSQL_PASS'] );
				$config	= config_replace_variable( $config,	'$C->DB_NAME',	$s['MYSQL_DBNAME'] );
				$config	= config_replace_variable( $config,	'$C->DB_MYEXT',	$s['MYSQL_MYEXT'] );
				$config	= config_replace_variable( $config,	'$C->CACHE_MECHANISM',	'filesystem' );
				$config	= config_replace_variable( $config,	'$C->CACHE_EXPIRE',	3600, FALSE );
				$config	= config_replace_variable( $config,	'$C->CACHE_MEMCACHE_HOST',	'' );
				$config	= config_replace_variable( $config,	'$C->CACHE_MEMCACHE_PORT',	'' );
				$config	= config_replace_variable( $config,	'$C->CACHE_KEYS_PREFIX',	$rndkey );
				$config	= config_replace_variable( $config,	'$C->CACHE_FILESYSTEM_PATH',	'$C->INCPATH.\'cache/\'', FALSE );
				$config	= config_replace_variable( $config,	'$C->IMAGE_MANIPULATION',	'gd' );
				$config	= config_replace_variable( $config,	'$C->IM_CONVERT',			'convert'	 );
				$config	= config_replace_variable( $config,	'$C->USERS_ARE_SUBDOMAINS',	'FALSE', FALSE );
				$config	= config_replace_variable( $config,	'$C->LANGUAGE',	$s['LANGUAGE'] );
				$config	= config_replace_variable( $config,	'$C->USERS_ARE_SUBDOMAINS',		'FALSE', FALSE );
				$config	= config_replace_variable( $config,	'$C->RPC_PINGS_ON',		'TRUE', FALSE );
				$config	= config_replace_variable( $config,	'$C->RPC_PINGS_SERVERS',	'array(\'http://rpc.pingomatic.com\')', FALSE );
				$config	= config_replace_variable( $config,	'$C->FACEBOOK_API_KEY',			''	 );
				$config	= config_replace_variable( $config,	'$C->FACEBOOK_API_ID',			''	 );
				$config	= config_replace_variable( $config,	'$C->FACEBOOK_API_SECRET',		''	 );
				$config	= config_replace_variable( $config,	'$C->BITLY_LOGIN',				''	 );
				$config	= config_replace_variable( $config,	'$C->BITLY_API_KEY',			''	 );
				$config	= config_replace_variable( $config,	'$C->TWITTER_CONSUMER_KEY',		''	 );
				$config	= config_replace_variable( $config,	'$C->TWITTER_CONSUMER_SECRET',	''	 );
				$config	= config_replace_variable( $config,	'$C->YAHOO_CONSUMER_KEY',		''	 );
				$config	= config_replace_variable( $config,	'$C->YAHOO_CONSUMER_SECRET',	''	 );
				$config	= config_replace_variable( $config,	'$C->GOOGLE_CAPTCHA_PRIVATE_KEY',	''	 );
				$config	= config_replace_variable( $config,	'$C->GOOGLE_CAPTCHA_PUBLIC_KEY',	''	 );
				$config	= config_replace_variable( $config,	'$C->CRONJOB_IS_INSTALLED',			'FALSE', FALSE );
				$config	= config_replace_variable( $config,	'$C->INSTALLED',			'TRUE', FALSE );
				$config	= config_replace_variable( $config,	'$C->VERSION',			$C->VERSION );
				$config	= config_replace_variable( $config,	'$C->DEBUG_USERS',		'array()', FALSE );
				$filename	= $C->INCPATH.'conf_main.php';
				$res	= file_put_contents($filename, $config);
				if( ! $res ) {
					$D->error	= TRUE;
					$D->errmsg	= '{Can\'t write your ./system/conf_main.php file, please check its permissions}';
				}
			}
		}

		if( ! $D->error ) {
			@chmod( $C->INCPATH.'../../.htaccess', 0664 );
			@chmod( $C->INCPATH.'../../system/conf_main.php', 0755 );
			@chmod( $C->INCPATH.'../../system/cache', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars/thumbs1', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars/thumbs2', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars/thumbs3', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars/thumbs4', 0777 );
			@chmod( $C->INCPATH.'../../i/avatars/thumbs5', 0777 );
			@chmod( $C->INCPATH.'../../i/attachments', 0777 );
			@chmod( $C->INCPATH.'../../i/tmp', 0777 );
			@chmod( $C->INCPATH.'../../system', 0755 );
			$url	= $s['SITE_URL'];
			$url	= rtrim($url,'/').'?installed=ok';
			session_unset();
			session_destroy();
			header('Location: '.$url);
		}
	}
	
	loadTemplate('step_5');
	
?>
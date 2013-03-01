<?php

	$D->installed	= FALSE;
	//if( isset($OLDC->INSTALLED, $OLDC->VERSION) && $OLDC->INSTALLED == 1 && $OLDC->VERSION>=VERSION ) {
	//	$installed	= TRUE;
	//}
	
	
	$_SESSION['INSTALL_STEP']	= 1;
	
	$D->error	= FALSE;
	$D->errtype = '';
	
	if( isset($_POST['submit']) ) {
		$a	= isset($_POST['accept1']) && $_POST['accept1']=="1";
		if( !isset($_POST['accept1']) || $_POST['accept1']!="1" ) {
			$D->error	= TRUE;
			$D->errtype = 'license';
		}
		if( ! $D->error ) {
			$_SESSION['INSTALL_STEP']	= 2;
			header('Location: ?r='.rand(0,99999));
			exit;
		}
	}
	
	//Compatability checks - START	
	if( !$D->error ){
		$D->texts	= array (
				'is_apache'				=> 'Apache HTTP Server required',
				'apache_mod_rewrite'	=> 'Apache: mod_rewrite module required',
				'php_version_52'		=> 'PHP: version 5.2 or higher required',
				'php_curl_or_urlfopen'	=> 'PHP: cURL is needed, or else "allow_url_fopen" directive should be On',
				'php_short_open_tag_on'	=> 'PHP: "short_open_tag" directive should be On',
				'php_gd'				=> 'PHP: gd extension required',
		);
		
		$D->check	= array (
				'is_apache'				=> FALSE,
				'apache_mod_rewrite'	=> FALSE,
				'php_version_52'		=> FALSE,
				'php_curl_or_urlfopen'	=> FALSE,
				'php_short_open_tag_on'	=> FALSE,
				'php_gd'				=> FALSE,
		);
		
		
		if( function_exists('apache_get_version') ) {
			$D->check['is_apache']		= TRUE;
		}
		elseif( isset($_SERVER['SERVER_SIGNATURE']) && preg_match('/Apache/i', $_SERVER['SERVER_SIGNATURE']) ) {
			$D->check['is_apache']		= TRUE;
		}
		elseif( isset($_SERVER['SERVER_SOFTWARE']) && preg_match('/Apache/i', $_SERVER['SERVER_SOFTWARE']) ) {
			$D->check['is_apache']		= TRUE;
		}
		
		$tmp	= floatval(substr(phpversion(), 0, 3));
		if( $tmp >= 5.2 ) {
			$D->check['php_version_52']	= TRUE;
		}
		
		if( function_exists('gd_info') ) {
			$D->check['php_gd']	= TRUE;
		}
		
		if( function_exists('curl_init') ) {
			$D->check['php_curl_or_urlfopen']	= TRUE;
		}
		else {
			$tmp	= intval(ini_get('allow_url_fopen'));
			if( $tmp == 1 ) {
				$D->check['php_curl_or_urlfopen']	= TRUE;
			}
		}
		
		$tmp	= intval(ini_get('short_open_tag'));
		if( $tmp == 1 ) {
			$D->check['php_short_open_tag_on']	= TRUE;
		}
		
		if( function_exists('apache_get_modules') ) {
			$tmp	= @apache_get_modules();
			if( is_array($tmp) ) {
				foreach($tmp as $mod) {
					if( $mod != 'mod_rewrite' ) {
						continue;
					}
					$D->check['apache_mod_rewrite']	= TRUE;
					break;
				}
			}
		}
		if( !$D->check['apache_mod_rewrite'] ) {
			ob_start();
			phpinfo(8);
			$tmp	= ob_get_contents();
			ob_get_clean();
			if( ! empty($tmp) ) {
				$pos	= strpos($tmp, 'Loaded Modules');
				if( FALSE !== $pos ) {
					$tmp	= substr($tmp, $pos);
					$pos	= strpos($tmp, '</table>');
					if( FALSE !== $pos ) {
						$tmp	= substr($tmp, 0, $pos);
						if( preg_match('/mod_rewrite/ius', $tmp) ) {
							$D->check['apache_mod_rewrite']	= TRUE;
						}
					}
				}
			}
		}
		if( !$D->check['apache_mod_rewrite'] ) {
			$url	= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$pos	= strpos($url, 'install');
			if( FALSE !== $pos ) {
				$url	= rtrim(substr($url, 0, $pos+8),'/').'/etc/modrewritetest/';
				$tmp1	= @file_get_contents($url.'test1.txt');
				$tmp2	= @file_get_contents($url.'test2.txt');
				if( $tmp1=='123' && $tmp2!='123' ) {
					$D->check['apache_mod_rewrite']	= TRUE;
				}
			}
		}
		
		foreach($D->check as $tmp) {
			if( ! $tmp ) {
				$D->error	= TRUE;
				$D->errtype = 'compatability';
				break;
			}
		}
	}
	//Compatability checks - END

	loadTemplate('step_1');
?>
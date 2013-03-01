<?php
	
	$D->error	= FALSE;
	$D->errmsg	= '';
	$D->SITE_URL = '';
	$D->DOMAIN = '';
	$D->ADMIN_USER	= '';
	$D->ADMIN_PASS	= '';
	$D->ADMIN_PASS2	= '';
	$D->ADMIN_EMAIL	= '';
	
	$D->SITE_URL	= 'http://'.trim($_SERVER['HTTP_HOST']);
	$uri	= $_SERVER['REQUEST_URI'];
	$pos	= strpos($uri, 'install');
	if( FALSE !== $pos ) {
		$uri	= substr($uri, 0, $pos);
		$uri	= trim($uri, '/');
		$D->SITE_URL	.= '/'.$uri;
		$D->SITE_URL	= trim($D->SITE_URL, '/');
	}
	$D->SITE_URL	= rtrim($D->SITE_URL, '/');	
	
	$D->DOMAIN	= preg_replace('/^(http|https)\:\/\//', '', $D->SITE_URL);
	$D->DOMAIN	= trim($D->DOMAIN, '/');
	$D->DOMAIN	= preg_replace('/\/.*$/', '', $D->DOMAIN);
	
	if( isset($_POST['ADMIN_USER'], $_POST['ADMIN_PASS'], $_POST['ADMIN_PASS2'], $_POST['ADMIN_EMAIL']) ) {

		$D->ADMIN_USER	= trim($_POST['ADMIN_USER']);
		$D->ADMIN_PASS	= trim($_POST['ADMIN_PASS']);
		$D->ADMIN_PASS2	= trim($_POST['ADMIN_PASS2']);
		$D->ADMIN_EMAIL	= trim($_POST['ADMIN_EMAIL']);
		
		if( empty($D->ADMIN_USER) ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Please enter Username.';
		}
		if( !$D->error && (strlen($D->ADMIN_USER)<3 || strlen($D->ADMIN_USER)>30) ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Username must be from 3 to 30 characters long.';
		}
		
		if( !$D->error && controllers_conflicts_lookup($D->ADMIN_USER) ){
			$D->error	= TRUE;
			$D->errmsg	= 'Forbidden username, please type a different name.';
		}
		
		if( !$D->error && $D->ADMIN_PASS!=$D->ADMIN_PASS2 ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Passwords don`t match.';
		}
		if( !$D->error && !is_valid_email($D->ADMIN_EMAIL) ) {
			$D->error	= TRUE;
			$D->errmsg	= 'Invalid E-mail address.';
		}
		
		if( ! $D->error ) {
			$s = &$_SESSION['INSTALL_DATA'];
			$s['ADMIN_USER'] = $D->ADMIN_USER;
			$s['ADMIN_PASS'] = $D->ADMIN_PASS;
			$s['ADMIN_EMAIL'] = $D->ADMIN_EMAIL;
			$s['SITE_URL'] = $D->SITE_URL;
			$s['SITE_TITLE'] = $s['ADMIN_USER'];
			$s['DOMAIN'] = $D->DOMAIN;
			
			$_SESSION['INSTALL_STEP']	= 5;
			header('Location: ?r='.rand(0,99999));
		}
	}
	
	loadTemplate('step_4');
	
?>
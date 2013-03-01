<?php
	
	if( $this->user->is_logged ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('outside/home.php');
	
	global $plugins_manager;
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$email		= '';
	$password	= '';
	$rememberme	= FALSE;
	
	if( isset($_POST['email'], $_POST['password']) ) {
		$submit	= TRUE;
		$email		= trim($_POST['email']);
		$password	= trim($_POST['password']);
		$rememberme	= isset($_POST['rememberme']) && $_POST['rememberme']==1;
		if( empty($email) || empty($password) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('signin_form_errmsg');
		}
		else {
			if( $this->user->is_logged ) {
				$this->user->logout();
			}
			$res	= $this->user->login($email, md5($password), $rememberme);
			if( ! $res ) {
				$error	= TRUE;
				if( $this->network->id ) {
					$db2->query('SELECT id FROM users WHERE (email="'.$db2->e($email).'" OR username="'.$db2->e($email).'") AND password="'.$db2->e(md5($password)).'" AND active=0 LIMIT 1');
					if( $db2->num_rows() > 0 ) {
						$errmsg	= $this->lang('signin_form_errmsgsusp');
					}
				}
				if( empty($errmsg) ) {
					$errmsg	= $this->lang('signin_form_errmsg');
				}
			}
			else {
				$this->redirect($C->SITE_URL.'dashboard');
				exit;
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('os_home_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'s'), FALSE );
	
	$plugins_manager->onPageLoad();
	
	$tpl->useLayout('header-home');
	/*if( FALSE === ($tmp = getCachedHTML('header_data')) ){
		$tmp = getMetaData().getCSSData().getFaviconData();
		setCachedHTML('header_data', $tmp);
	}*/
	$tpl->layout->setVar( 'page_title', $this->lang('os_home_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)) );
	$tmp = $tpl->designer->getMetaData().$tpl->designer->getFaviconData();
	$tmp .= '<link href="'.$C->STATIC_URL.'css/mobile-login.css" type="text/css" rel="stylesheet" />';
	$tpl->layout->setVar( 'header_data', $tmp );	
	$tpl->layout->setVar( 'logo_data', $tpl->designer->loadNetworkLogo() );
	
	
	
	$tpl->useLayout('page_layout_clean');
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('global_error_msg_ttl'), $errmsg ) );
	}
	
	$tpl->layout->useBlock('home');
	$tpl->layout->setVar( 'stx_footer_link_abc', 'Powered by <a href="http://sharetronix.com" target="_blank">Sharetronix</a>');
	$tpl->layout->block->save( 'main_content');
	
	
	$tpl->useLayout('footer-home');
	$tpl->layout->setVar( 'footer_js_data', $tpl->designer->getJSData() );
	
	$tpl->display();
?>
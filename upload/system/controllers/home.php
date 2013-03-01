<?php

	if( $this->user->is_logged ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('outside/home.php');
	$this->load_langfile('outside/signin.php');

	$D->page_title	= $this->lang('os_home_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	$D->intro_ttl	= $this->lang('os_welcome_ttl', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	$D->intro_txt	= $this->lang('os_welcome_txt', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	if( isset($C->HOME_INTRO_TTL) && !empty($C->HOME_INTRO_TTL) ) {
		$D->page_title	= strip_tags($C->SITE_TITLE.' - '.$C->HOME_INTRO_TTL);
		$D->intro_ttl	= $C->HOME_INTRO_TTL;
	}
	if( isset($C->HOME_INTRO_TXT) && !empty($C->HOME_INTRO_TXT) ) {
		$D->intro_txt	= $C->HOME_INTRO_TXT;
	}
	
	$tpl = new template( array('page_title' => $D->page_title, 'header_page_layout'=>'s') );
	
	$tpl->layout->useBlock('home');
	
	
	$tpl->layout->setVar('home_page_content_title', $D->intro_ttl);
	$tpl->layout->setVar('home_page_content', $D->intro_txt );
	$tpl->layout->setVar('home_form_action', $C->SITE_URL.'signin' );
	$tpl->layout->block->save( 'main_content_placeholder');
	
	
	$tpl->display();
?>
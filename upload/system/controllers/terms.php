<?php
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('outside/terms.php');

	if( !isset($C->TERMSPAGE_ENABLED) || $C->TERMSPAGE_ENABLED!=1 ) {
		$this->redirect('home');
	}
	if( !isset($C->TERMSPAGE_CONTENT) || empty($C->TERMSPAGE_CONTENT) ) {
		$this->redirect('home');
	}
	
	$tpl = new template( array('page_title' => $this->lang('terms_pgtitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	
	$tpl->layout->setVar( 'main_content', trim(stripslashes($C->TERMSPAGE_CONTENT)) );
	
	$tpl->display();
	
?>
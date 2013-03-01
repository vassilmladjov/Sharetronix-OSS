<?php 
	function loadMobileHeader( $tpl, $params )
	{ 
		global $C, $D, $network, $user, $page;

		$pm 	= & $GLOBALS['plugins_manager'];
		
		$page->load_langfile('inside/header.php');

		if (isset($page->params->tab) && $page->params->tab == 'info') $tpl->layout->setVar( 'html_style', 'user-info' );
		
		if( FALSE === ($tmp = getCachedHTML('header_data')) ){
			$tmp = $tpl->designer->getMetaData().$tpl->designer->getCSSData().$tpl->designer->getFaviconData();
			setCachedHTML('header_data', $tmp);
		}
		//$tmp = $tpl->designer->getMetaData().$tpl->designer->getCSSData().$tpl->designer->getFaviconData();

		$tpl->layout->setVar( 'header_data', $tmp );	
		$tpl->layout->setVar( 'logo_data', $tpl->designer->loadNetworkLogo() );
		
		$tpl->initRoutine('MobileMenu', array());
		$tpl->routine->load();
		
		$tpl->initRoutine('Postform', array('post_form_placeholder'));
		$tpl->routine->load();
		
		$tpl->initRoutine('Sendmessage', array('send_message_placeholder'));
		$tpl->routine->load();
		
		
		
	}
?>
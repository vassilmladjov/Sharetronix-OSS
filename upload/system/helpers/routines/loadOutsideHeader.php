<?php 
	function loadOutsideHeader( $tpl, $params )
	{ 
		global $C, $D, $network, $user, $page;

		$pm 	= & $GLOBALS['plugins_manager'];
		
		$page->load_langfile('inside/header.php');
		
		$lang_abbrv = explode( '.', $C->PHP_LOCALE );
		if( is_array($lang_abbrv) ){
			$lang_abbrv = explode('_', $lang_abbrv[0]);
		}
		$lang_abbrv = (isset($lang_abbrv[0]))? strtolower($lang_abbrv[0]) : 'en';
		$tpl->layout->setVar( 'html_lang_abbrv', $lang_abbrv );
		
		/*if( FALSE === ($tmp = getCachedHTML('header_data')) ){
			$tmp = getMetaData().getCSSData().getFaviconData();
			setCachedHTML('header_data', $tmp);
		}*/
		$tmp = $tpl->designer->getMetaData().$tpl->designer->getCSSData().$tpl->designer->getFaviconData();

		$tpl->layout->setVar( 'header_data', $tmp );	
		$tpl->layout->setVar( 'logo_data', $tpl->designer->loadNetworkLogo() );

	}
?>
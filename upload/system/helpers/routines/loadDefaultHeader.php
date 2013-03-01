<?php 
	function loadDefaultHeader( $tpl, $params )
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
		
		$D->hdr_search = ($page->request[0]=='members' ? 'users' : ($page->request[0]=='groups' ? 'groups' : ($page->request[0]=='search' ? $D->tab : 'posts') ) );
	
		$menu = array( 'dashboard' => array('item-btn', $page->lang('hdr_nav_home')) );
	
		if( $user->is_logged ){
			$menu = array(  array('url' => 'dashboard', 'css_class' => 'item-btn'.($page->request[0] == 'dashboard'? ' active' : ''), 'title' => $page->lang('hdr_nav_home')) );	
		}else{
			$menu = array(  array('url' => 'home', 'css_class' => 'item-btn'.($page->request[0] == 'home'? ' active' : ''), 'title' => $page->lang('hdr_nav_home')) );
		}
	
		$menu[] = array('url' => 'members', 'css_class' => 'item-btn'.($page->request[0] == 'members'? ' active' : ''), 'title' => $page->lang('hdr_nav_users') );
		$menu[] = array('url' => 'groups', 	'css_class' => 'item-btn'.($page->request[0] == 'groups'? ' active' : ''), 'title' => $page->lang('hdr_nav_groups') );

		$tpl->layout->setVar( 'main_navigation', $tpl->designer->createMenu( 'main-navigation', $menu, 'header_top_menu' ) ); unset($menu);
	
		$tpl->layout->useBlock( 'header-content' );
		
		$tpl->layout->useInnerBlock( 'header-content-searcharea' );
		
		$tpl->layout->inner_block->saveInBlockPart('header_content_searcharea');
		
		if( $page->request[0] == 'dashboard' ){
			$tab = ($page->param('tab') !== '')? $page->param('tab') : 'all';
			$D->tabs_state	= $network->get_dashboard_tabstate($user->id, array('all','@me','private','commented', 'notifications'), $tab);
			if( isset($D->tabs_state[$tab]) ) {
				$D->tabs_state[$tab]	= 0;
			}
		}else{
			$D->tabs_state	= $network->get_dashboard_tabstate($user->id, array('private', 'notifications'));
		}
		$notifications_cnt = isset($D->tabs_state['notifications'])? intval( $D->tabs_state['notifications'] ) : 0;
		$privmsgs_cnt = isset($D->tabs_state['private'])? intval( $D->tabs_state['private'] ) : 0;
		
		$total_cnt = $notifications_cnt + $privmsgs_cnt;
		$tpl->layout->block->setVar('header_notification_counter', $total_cnt);
		
		if($total_cnt > 0){
			$tpl->layout->block->setVar('header_notification_counter_full', 'full');
			
			if( $notifications_cnt > 0 ){
				$tpl->layout->block->setVar('header_notification_notifs_cnt', $notifications_cnt);
			}
			if( $privmsgs_cnt > 0 ){
				$tpl->layout->block->setVar('header_notification_privmsg_cnt', $privmsgs_cnt);
			}
		}
		if( !$privmsgs_cnt){
			$tpl->layout->block->setVar('header_notification_privmsg_visibility', 'style="display: none;"');
		}
		if( !$notifications_cnt ){
			$tpl->layout->block->setVar('header_notification_notifs_visibility', 'style="display: none;"');
		}
		
		
		$tpl->layout->block->save( 'header_content' );
		
		if( FALSE === ($tmp = getCachedHTML('header_data')) ){
			$tmp = $tpl->designer->getMetaData().$tpl->designer->getCSSData().$tpl->designer->getFaviconData();
			setCachedHTML('header_data', $tmp);
		}
		//$tmp = $tpl->designer->getMetaData().$tpl->designer->getCSSData().$tpl->designer->getFaviconData();

		$tpl->layout->setVar( 'header_data', $tmp );	
		$tpl->layout->setVar( 'logo_data', $tpl->designer->loadNetworkLogo() );

	}
?>
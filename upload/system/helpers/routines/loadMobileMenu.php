<?php
	function loadMobileMenu( $tpl, $params )
	{
		global $C;
		$page 		= & $GLOBALS['page'];
		$network 	= & $GLOBALS['network'];
		$user 		= & $GLOBALS['user'];
		$pm 		= & $GLOBALS['plugins_manager'];
		
		
		$user_info = '
			<a href="'.userlink($user->info->username).'" class="menu-avatar" style="background-image:url('.$C->STORAGE_URL.'avatars/'.$user->info->avatar.');">
				<strong>'.$user->info->fullname.'<span class="job-title">'.$user->info->position.'</span></strong>
			</a>';
		
		$tpl->layout->setVar( 'menu_content_placeholder',  $user_info);
		
		$tab	= 'all';
		if( $page->param('tab') ){
			$tab = htmlspecialchars( $page->param('tab') );
		}

		if( $page->request[0] && $page->request[0] == 'privatemessages'){
			$tab =  'privatemessages';
		}

		
		$my_groups	= $user->get_top_groups(5);
		
		$new_activities = $network->get_dashboard_tabstate($user->id, array('all', 'commented', '@me', 'private'));
		$has_notifications = ( 
				(isset($new_activities['all']) && is_numeric($new_activities['all']) && $new_activities['all'] > 0) ||
				(isset($new_activities['all']) && is_numeric($new_activities['@me']) && $new_activities['@me'] > 0) ||
				(isset($new_activities['all']) && is_numeric($new_activities['commented']) && $new_activities['commented'] > 0) ||
				(isset($new_activities['private']) && is_numeric($new_activities['private']) && $new_activities['private'] > 0)
			)? 1 : 0;			
					
		$tpl->layout->setVar( 'header_class', ($has_notifications == 1) ? 'active' : '');

		
		$menu = array( 	array('url' => $user->info->username, 		'css_class' => 'profile', 		'title' => $page->lang('user_profile_my_profile') ));
		$tpl->layout->setVar( 'menu_content_placeholder', $tpl->designer->createInfoBlock(' ', $tpl->designer->createMenu('feed-navigation', $menu)) );
		
		$menu = array();
		$menu = array( 	array('url' => 'dashboard/tab:all', 		'css_class' => 'my-activities'.(($tab === 'all')? ' selected' : ''), 		'title' => $page->lang('dbrd_leftmenu_all'), 												'tab_state' => $new_activities['all'] ),
						array('url' => 'dashboard/tab:@me', 		'css_class' => 'at'.(($tab === '@me')? ' selected' : ''), 					'title' => $page->lang('dbrd_leftmenu_@me', array('#USERNAME#'=>$user->info->username) ), 	'tab_state' => $new_activities['@me'] ),
						array('url' => 'dashboard/tab:commented', 	'css_class' => 'comments'.(($tab === 'commented')? ' selected' : ''), 		'title' => $page->lang('dbrd_leftmenu_commented'), 											'tab_state' => $new_activities['commented'] ),
						array('url' => 'privatemessages', 			'css_class' => 'messages'.(($tab === 'privatemessages')? ' selected' : ''), 'title' => $page->lang('dbrd_poststitle_private') , 'tab_state' => $new_activities['private']),
						array('url' => 'dashboard/tab:bookmarks', 	'css_class' => 'favourites'.(($tab === 'bookmarks')? ' selected' : ''), 	'title' => $page->lang('dbrd_leftmenu_bookmarks') ),
						array('url' => 'dashboard/tab:everybody', 	'css_class' => 'filter-all'.(($tab === 'everybody')? ' selected' : ''), 	'title' => $page->lang('dbrd_leftmenu_everybody', array('#COMPANY#'=>$C->COMPANY)) )
						
		);
		$tpl->layout->setVar( 'menu_content_placeholder', $tpl->designer->createInfoBlock($page->lang('user_dashboard_activity_filter'), $tpl->designer->createMenu('feed-navigation', $menu, 'dashboard_main_left_menu')) );
		
		$menu = array();
		foreach( $my_groups as $group ){
			$menu[] = array('url' => 'dashboard/tab:group/g:'.$group->groupname, 	'css_class' => (($group->is_public)? 'public' : 'private') . (($page->param('g') === $group->groupname)? ' selected' : ''),	'title' => ucfirst( $group->groupname ) );
		}
		if( count($menu) > 0 ){
			$tpl->layout->setVar( 'menu_content_placeholder', $tpl->designer->createInfoBlock($page->lang('user_dashboard_my_groups'), $tpl->designer->createMenu('feed-navigation', $menu)) );
		}
		
		
		$menu = array();
		$menu[] = array('url' => 'signout', 	'css_class' => 'logout',	'title' => $page->lang('hdr_nav_signout') );
		$tpl->layout->setVar( 'menu_content_placeholder', $tpl->designer->createInfoBlock(' ', $tpl->designer->createMenu('feed-navigation', $menu)) );
		
		
		unset($menu, $my_groups);

	}
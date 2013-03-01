<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}elseif($C->PROTECT_OUTSIDE_PAGES && !$this->user->is_logged){
		$this->redirect('home');
	}
	
	global $C;
	$pm = $GLOBALS['plugins_manager'];
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/members.php');
	
	$tab = 'all';
	if( $this->param('tab') ){
		$tab = $this->param('tab');
	}
	
	if( !in_array($tab, array('all', 'my', 'admins', 'ifollow', 'followers')) ){
		$pm->onPageSetCountQuery();
		$tmp = $pm->getEventResult();
	
		if( is_string($tmp) ){
			$num_results	= $db2->fetch_field($tmp);
			$num_pages	= ceil($num_results / $C->PAGING_NUM_GROUPS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	=  ($this->user->is_logged)? (($pg - 1) * $C->PAGING_NUM_GROUPS) : 0;
				
			$pm->onPageSetQuery();
			$tmp = $pm->getEventResult();
	
			if( is_string($tmp) ){
				$res = $db2->query( $tmp .' ORDER BY '.$sql_orderby[$orderby].' LIMIT '.$from.', '.$C->PAGING_NUM_GROUPS );
			}
		}
	}
	
	$user_ids = array();
	$paging_url	= $C->SITE_URL.'members/tab:'.$tab.'/pg:';
	
	switch( $tab ){
		case 'all' : 
			$num_results	= $db2->fetch_field('SELECT COUNT(*) AS u FROM users WHERE active=1');
			$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
			$pg				= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg				= min($pg, $num_pages);
			$pg				= max($pg, 1);
			$from			= ($this->user->is_logged)?(($pg - 1) * $C->PAGING_NUM_USERS) : 0;
			
			$res = $db2->query('SELECT id, username, fullname, num_followers, num_posts, avatar, email, fullname, about_me FROM users WHERE active=1 ORDER BY num_followers DESC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);
			
			$pg	= ($this->user->is_logged)? $pg : 1;
		
		break;
		
		case 'ifollow' : 
			$num_results	= $db2->fetch_field('SELECT COUNT(*) AS u FROM users_followed WHERE who="'.$this->user->id.'"');
			$num_pages	= ceil($num_results / $C->PAGING_NUM_USERS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	= ($pg - 1) * $C->PAGING_NUM_USERS;
			
			$res = $db2->query('SELECT u.id, u.username, fullname, u.num_followers, u.num_posts, u.avatar, u.email, u.fullname, u.about_me FROM users u, users_followed uf WHERE uf.whom=u.id AND who="'.$this->user->id.'" ORDER BY u.id DESC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);
		break;
		
		case 'followers' : 
			$num_results	= $db2->fetch_field('SELECT COUNT(*) AS u FROM users_followed WHERE whom="'.$this->user->id.'"');
			$num_pages	= ceil($num_results / $C->PAGING_NUM_USERS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	= ($pg - 1) * $C->PAGING_NUM_USERS;
				
			$res = $db2->query('SELECT u.id, u.username, fullname, u.num_followers, u.num_posts, u.avatar, u.email, u.fullname, u.about_me FROM users u, users_followed uf WHERE uf.who=u.id AND whom="'.$this->user->id.'" ORDER BY u.id DESC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);
		break;
		
		case 'admins' : 
			$num_results	= $db2->fetch_field('SELECT COUNT(*) AS u FROM users WHERE is_network_admin="1"');
			$num_pages	= ceil($num_results / $C->PAGING_NUM_USERS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	= ($pg - 1) * $C->PAGING_NUM_USERS;
			
			$res = $db2->query('SELECT id, username, fullname, num_followers, num_posts, avatar, email, fullname, about_me FROM users WHERE active=1 AND is_network_admin=1 ORDER BY id DESC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);

		break;
	}
		
	//TEMPLATE CODE START
	$tpl = new template( array('page_title' => $C->SITE_TITLE . ' - Members', 'header_page_layout'=>'c') );
	
	$menu = array( 	array('url' => 'members/tab:all', 			'css_class' => (($tab === 'all')? 'active' : ''), 			'title' => $this->lang('members_tabs_all') ),
					array('url' => 'members/tab:admins', 		'css_class' => (($tab === 'admins')? 'active' : ''), 		'title' => $this->lang('members_tabs_admins') )
	);
	if( $this->user->is_logged ){
		$menu[] = array('url' => 'members/tab:ifollow', 		'css_class' => (($tab === 'ifollow')? 'active' : ''), 		'title' => $this->lang('members_tabs_ifollow') );
		$menu[] = array('url' => 'members/tab:followers', 	'css_class' => (($tab === 'followers')? 'active' : ''), 	'title' => $this->lang('members_tabs_followers') );
	}
	
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu, 'members_top_tab_menu') );
	
	if( isset($num_results) && $num_results > 0 ) {
		
		$ifollow = ($this->user->is_logged)? array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users ) : array();
		$float = 'left-container';
		while($obj = $db2->fetch_object($res)) {		
			$tpl->initRoutine('SingleUser', array( &$obj, &$ifollow, $float ));
			$tpl->routine->load();
			$float = ($float == 'left-container')? 'right-container' : 'left-container';
		}
		
		$tpl->layout->setVar( 'main_content_bottom', $tpl->designer->pager( $num_results, $num_pages, $pg, $paging_url ) );
	}
	else {
		if( !in_array($tab, array('all', 'my', 'admins', 'ifollow', 'followers')) ){
			$tab = 'all';
		}
		$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox(
												$this->lang('nousers_box_ttl_'.$tab, array('#SITE_TITLE#'=>htmlspecialchars($C->OUTSIDE_SITE_TITLE))),
												$this->lang('nousers_box_txt_'.$tab, array('#SITE_TITLE#'=>htmlspecialchars($C->OUTSIDE_SITE_TITLE)))
											)
		);
	}
	
	unset($res, $menu, $available_tabs);
	
	$tpl->display();
?>
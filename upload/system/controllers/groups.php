<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}elseif($C->PROTECT_OUTSIDE_PAGES && !$this->user->is_logged){
		$this->redirect('home');
	}
	
	global $C;
	$pm = &$GLOBALS['plugins_manager'];
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/groups.php');
	$this->load_langfile('inside/groups_new.php');
	
	$tab = 'all';
	if( $this->param('tab') ){
		$tab = $this->param('tab');
	}
	
	$paging_url	= $C->SITE_URL.'groups/tab:'.$tab.'/pg:';
	
	$not_in_groups	= array();
	if( !$this->user->is_logged || !$this->user->info->is_network_admin > 0 ) {
		$not_in_groups 	= array_diff( $this->network->get_private_groups_ids(), $this->user->get_my_private_groups_ids() );
	}
	$not_in_groups	= count($not_in_groups)>0 ? ('AND id NOT IN('.implode(', ', $not_in_groups).')') : '';

	$orderby	= 'users';
	$sql_orderby	= array(
			'name'	=> 'title ASC, num_followers DESC, num_posts DESC, id DESC',
			'date'	=> 'id DESC',
			'users'	=> 'num_followers DESC, num_posts DESC, id DESC',
			'posts'	=> 'num_posts DESC, num_followers DESC, id DESC',
	);
	
	if( $this->param('orderby') && isset($sql_orderby[$this->param('orderby')]) ) {
		$orderby	= $this->param('orderby');
	}
	
	if( !in_array($tab, array('all', 'my')) ){
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

	switch( $tab ){
		case 'all' :
			$num_results	= $db2->fetch_field('SELECT COUNT(*) FROM groups WHERE 1 '.$not_in_groups);
			$num_pages	= ceil($num_results / $C->PAGING_NUM_GROUPS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	=  ($this->user->is_logged)? (($pg - 1) * $C->PAGING_NUM_GROUPS) : 0;
	
			$res = $db2->query('SELECT * FROM groups WHERE 1 '.$not_in_groups.' ORDER BY '.$sql_orderby[$orderby].' LIMIT '.$from.', '.$C->PAGING_NUM_GROUPS);
			
			$pg = ($this->user->is_logged)? $pg : 1;
			
			break;
		case 'my' :
			$num_results	= $db2->fetch_field('SELECT COUNT(*) AS u FROM groups_followed WHERE user_id="'.$this->user->id.'"');
			$num_pages	= ceil($num_results / $C->PAGING_NUM_GROUPS);
			$pg	= $this->param('pg') ? intval($this->param('pg')) : 1;
			$pg	= min($pg, $num_pages);
			$pg	= max($pg, 1);
			$from	= ($pg - 1) * $C->PAGING_NUM_GROUPS;
			
			$res = $db2->query('SELECT g.* FROM groups g, groups_followed gf WHERE gf.group_id=g.id AND user_id="'.$this->user->id.'" ORDER BY g.id DESC LIMIT '.$from.', '.$C->PAGING_NUM_USERS);	
			
			break;
	}

	//TEMPLATE CODE START
	$tpl = new template( array('page_title' => $this->lang('groups_page_title_'.$tab, array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	if( $this->param('msg') == 'deleted' ){
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('groups_msgbox_deleted_ttl'), $this->lang('groups_msgbox_deleted_txt')) );
	}
	
	$menu = array( 	array('url' => 'groups/tab:all', 		'css_class' => (($tab === 'all')? 'active' : ''), 		'title' => $this->lang('userselector_tab_all') ) );
	
	if( $user->is_logged ){
		$menu[] = array('url' => 'groups/tab:my', 		'css_class' => (($tab === 'my')? 'active' : ''), 		'title' => $this->lang('groups_page_tab_my') );
		$menu[] = array('url' => 'groups/new', 			'css_class' => 'right highlighted', 					'title' => $this->lang('newgroup_title2') );
	}
	
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu, 'groups_top_tab_menu') );
	
	if( isset($num_results) && $num_results > 0 ) {
		$ifollow = ($this->user->is_logged)? array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hisgroups')->follow_groups ) : array();
		
		while($obj = $db2->fetch_object($res)) {
			$g	= $this->network->get_group_by_id(intval($obj->id));
			$group = new group( $g );
			$group_admin = $group->isGroupAdmin();			
			$tpl->initRoutine('SingleGroup', array( &$obj, &$ifollow, $group_admin));
			$tpl->routine->load();
		}
		
		$tpl->layout->setVar( 'main_content_bottom', $tpl->designer->pager( $num_results, $num_pages, $pg, $paging_url ) );
	}
	else {
		if( !in_array($tab, array('all', 'my')) ){
			$tab = 'default';
		}
		$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox(
							$this->lang('nogroups_box_ttl_'.$tab, array('#SITE_TITLE#'=>htmlspecialchars($C->OUTSIDE_SITE_TITLE))),
							$this->lang('nogroups_box_txt_'.$tab, array('#SITE_TITLE#'=>htmlspecialchars($C->OUTSIDE_SITE_TITLE))))
		);
	}
	
	unset($res, $menu, $available_tabs);
	
	$tpl->display();
	
?>
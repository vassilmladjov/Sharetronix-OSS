<?php

	if( !$this->network->id ) {
		$this->redirect('home');
	}elseif($C->PROTECT_OUTSIDE_PAGES && !$this->user->is_logged){
		$this->redirect('home');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/dashboard.php');
	
	$g	= $this->network->get_group_by_id(intval($this->params->group));
	if( ! $g ) {
		$this->redirect('dashboard');
	}
	if( $g->is_private && !$this->user->is_logged ) {
		$this->redirect('home');
	}
	if( $g->is_private && !$this->user->info->is_network_admin ) {
		$u	= $this->network->get_group_invited_members($g->id);
		if( !$u || !in_array(intval($this->user->id),$u) ) {
			$this->redirect('dashboard');
		}
	}
	
	//check POST for
	//$group->addAdmin();
	//$group->deleteAdmin();
	

	$tab = 'updates';
	if( $this->param('tab') ){
		$tab = $this->param('tab');
	}
	
	$subtab = '';
	if( $this->param('subtab') ){
		$subtab = $this->param('subtab');
	} else {
		$subtab = "main";
	}
	
	$paging_url	= $C->SITE_URL.$g->groupname.'/tab:'.$tab.'/subtab:'.$subtab.'/pg:';
	
	$group_members 		= array_keys( $this->network->get_group_members($g->id) );
	
	$group = new group( $g );
	//$group->ifCanInvite();
	//$this->network->get_recent_posttags(10, $g->id, 'group');
	//$about_me	= nl2br(htmlspecialchars($g->about_me));
	//group->lastUsers()
	
	//check POST for
	//$group->addAdmin();
	//$group->deleteAdmin();
	
	
	$i_am_member		= ($this->user->is_logged && in_array($this->user->id, $group_members))? TRUE : FALSE;
	$i_am_network_admin	= ( $this->user->is_logged && $this->user->info->is_network_admin > 0 );
	$i_am_admin			= $i_am_network_admin;

	if( !$i_am_network_admin ) {
		$i_am_admin	= $this->db2->fetch('SELECT id FROM groups_admins WHERE group_id="'.$g->id.'" AND user_id="'.$this->user->id.'" LIMIT 1') ? TRUE : FALSE;
	}
	
	$submit = FALSE;
	$errmsg = '';
	$last_result = 0;
	
	if( isset($_POST['sbm']) ){ 
		$submit = TRUE;
		$error = FALSE;
		
		global $plugins_manager;
		
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( $tab == 'settings' && $subtab == 'main' && !$error ){
		
			if( isset($_POST['group_name']) && $_POST['group_name'] != $g->title ){
				$errmsg = $group->changeTitle( $_POST['group_name'] );
			}
			if( empty($errmsg) && isset($_POST['group_alias']) && $_POST['group_alias'] != $g->groupname ){
				$errmsg = $group->changeName( $_POST['group_alias'] );
			}
			if( empty($errmsg) && isset($_POST['group_description']) && $_POST['group_description'] != $g->about_me ){
				$errmsg = $group->changeDescription( $_POST['group_description'] );
			}
			if( empty($errmsg) && isset($_POST['group_type']) ){
				$type = trim($_POST['group_type']);
				if( $type != 'private' && $type != 'public' ){
					$errmsg = $this->lang('group_settings_f_type'); //change this with valid lang type
				}elseif( $type == 'public' && $g->is_private ){
					$group->changeType( 'public' );
				}elseif( $type == 'private' && $g->is_public ){
					$group->changeType( 'private' );
				}
			}
			
			if( empty($errmsg) ){
				$errmsg = $group->changeAvatar();
			}
			
			if( empty($errmsg) ){
				$g = $this->network->get_group_by_id(intval($this->params->group), TRUE);
			}else{
				$errmsg = $this->lang($errmsg);
			}
			
		}else if( $tab == 'settings' && $subtab == 'delgroup' && !$error ){
			if( isset($_POST['postsact']) && $this->user->info->password == md5($_POST['password']) ){
				$delete_posts = $_POST['postsact'] == 'keep'? FALSE : TRUE;
				
				$group->delete( $delete_posts );
			}else{
				$errmsg = $this->lang('group_del_f_err_passwd');
			}
		}
	}
	
	$if_can_invite =  $group->ifCanInvite();
	
	//TEMPLATE START
	$tpl = new template( array('page_title' => $g->groupname. ' - ' .$C->SITE_TITLE, 'header_page_layout'=>'sc') );
	
	
	$menu_items = array(
		array(
			'url'=> '#', 
			'text'=> $this->lang('grp_toplnks_unfollow'), 
			'data_attributes' => array(
					'role' => 'services', 
					'namespace' => 'groups',  
					'action' => 'leave', 
					'value' => $g->id
				)
			),
	);
	
	if( $if_can_invite ){
		$menu_items[] = array(
			'url'=> $C->SITE_URL.$g->groupname.'/invite', 
			'text'=> $this->lang('group_left_invite_btn')
		);
	}
	if( $i_am_admin  ){
		$menu_items[] = array(
			'url'=> $C->SITE_URL.$g->groupname.'/tab:settings/subtab:main', 
			'text'=> $this->lang('grp_tab_settings')
		);
	}
	
	
	$tpl->layout->useBlock('group-header-info');
	$tpl->layout->block->setVar('group_header_username', $g->title);
	$tpl->layout->block->setVar('group_header_icon', ($g->is_public? 'public' : 'private')); //should be position
	$tpl->layout->block->setVar('group_header_activity', $this->lang('group_header_descr_activity', array('#NUM_MEMBERS#'=> $g->num_followers, '#NUM_POSTS#'=>$g->num_posts) ) );
	
	if( $this->user->is_logged ){
		if($i_am_member == true ){ 
			$tpl->layout->block->setVar(
					'group_header_settings_button', 
					$tpl->designer->dropDownMenu("Settings", $menu_items, '', 'action-btn options', true)
			); 
		} else {
			$tpl->layout->block->setVar(
					'group_header_settings_button',
					'<a class="action-btn user-action add" data-action="join" data-value="'.$g->id.'" data-namespace="groups" data-role="services"><span class="tooltip"><span>'.$this->lang('grp_toplnks_follow').'</span></a>'
			);
		}
		unset($menu_items);
	} 
	
	$tpl->layout->block->save('main_content_top_placeholder', true);
	
	$invite_button = '';
	if( $if_can_invite ){
		$invite_button =
		'
		<div>
			<div class="options-container" align="center">
				<a href="' . $C->SITE_URL . $g->groupname .'/invite" class="action-btn user-action add" style="float:left; margin:0px;">
					<span class="tooltip">
						<span>'.$this->lang('group_left_invite_btn').'</span>
					</span>
				</a>
			</div>
			<div class="clear"></div>
		</div>';
	}
	
	$tpl->layout->setVar( 'left_content', 	
			
			$tpl->designer->createInfoBlock('',
					'<img src="'.$C->STORAGE_URL.'avatars/'. (empty($g->avatar)? $C->DEF_AVATAR_GROUP : $g->avatar).'" alt="'.$g->groupname.'">'.
					'<div class="group-description">'.$g->about_me.'</div>'.
					'<div class="group-statistics">
						<strong>'.$g->num_followers.'</strong> '.$this->lang('grp_tab_members').' <br />'.
						'<strong>'.$g->num_posts.'</strong> '.$this->lang('usrlist_numposts').'
					</div>'.
					'<div class="recent-visitors">'.
						'<h3 class="sub-title">'.$this->lang('group_latest_members').'</h3>'.
						$tpl->designer->createUserLinks( $group->getGroupMembers($group_members), 'thumbs3' ).
						$invite_button .
						'<div class="clear"></div>
					</div>
					'
			)													
	);
	
	$menu = array( 	array('url' => $g->groupname.'/tab:updates', 	'css_class' => (($tab === 'updates')? ' selected' : ''), 	'title' => $this->lang('grp_tab_updates') ),
					array('url' => $g->groupname.'/tab:members', 	'css_class' => (($tab === 'members')? ' selected' : ''), 	'title' => $this->lang('grp_tab_members') ),
	);
	
	$tpl->layout->setVar( 'subheader_placeholder', $tpl->designer->createMenu( 'navigation', $menu, 'group_navigation_top_menu' ) ); 
	unset($menu);
	
	
	switch($tab){
		case 'members':
				if( empty($subtab) || $subtab =='main'){
					$subtab = 'all';
				}
				
				$menu = array( 	array('url' => $g->groupname.'/tab:members/subtab:all', 	'css_class' => (($subtab === 'all')? ' active' : ''), 		'title' => $this->lang('userselector_tab_all') ),
								array('url' => $g->groupname.'/tab:members/subtab:admins', 	'css_class' => (($subtab === 'admins')? ' active' : ''), 		'title' => $this->lang('group_sett_subtabs_admins') ),
				);
				if( $g->is_private ){
					$menu[] = array('url' => $g->groupname.'/tab:members/subtab:privmembers',  'css_class' => (($subtab === 'privmembers')? ' active' : ''), 	'title' => $this->lang('group_sett_subtabs_privmembers') );
				}
					
				$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu( 'tabs-navigation', $menu, 'group_navigation_members_menu' ) ); unset($menu);
			
				$activity = activityFactory::select('group');
				$activity->setTemplate( $tpl );
				$activity->setGroup( $g );
				$activity->loadUsers($subtab);
				
				$tpl->layout->setVar( 'main_content_bottom', $tpl->designer->pager( $activity->num_results, $activity->num_pages, $activity->pg, $paging_url ) );
				
				break;
		
		case 'settings':
				if( !$i_am_admin ){
					$this->redirect($g->groupname);
				}
				
				if( $this->param('del') == 'groupavatar' ) {
					$tmp = $group->changeAvatar( FALSE );
					if( empty( $tmp ) ){
						$this->redirect($g->groupname.'/tab:settings/subtab:main/msg:avatardeleted');
					}
				}
				if( $this->param('msg') == 'avatardeleted' ){
					$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('group_settings_f_ok'), $this->lang('group_settings_f_oktxt') ));
				}
				
				if( empty($subtab) ){
					$subtab = 'main';
				}
				
				$menu = array( 	array('url' => $g->groupname.'/tab:settings/subtab:main', 		'css_class' => (($subtab === 'main')? ' active' : ''), 		'title' => $this->lang('group_sett_subtabs_main') ),
								array('url' => $g->groupname.'/tab:settings/subtab:admins', 	'css_class' => (($subtab === 'admins')? ' active' : ''), 	'title' => $this->lang('group_sett_subtabs_admins') ),
								array('url' => $g->groupname.'/tab:settings/subtab:delgroup', 	'css_class' => (($subtab === 'delgroup')? ' active' : ''), 	'title' => $this->lang('group_sett_subtabs_delgroup') ),
				);
				if( $g->is_private ){
					$menu[] = array('url' => $g->groupname.'/tab:settings/subtab:privmembers','css_class' => (($subtab === 'privmembers')? ' active' : ''), 	'title' => $this->lang('group_sett_subtabs_privmembers') );
				}
				
				$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu( 'tabs-navigation', $menu, 'group_navigation_settings_menu' ) ); unset($menu); 
				
				if( $subtab == 'main' ){ 
					
					if( $submit && empty($errmsg) ){
						$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('group_settings_f_ok'), $this->lang('group_settings_f_oktxt') ) );
					}else if( $submit && !empty($errmsg) ){
						$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('group_settings_f_err'), $errmsg ) );
					}
					
					$table = new tableCreator();
					$table->form_enctype = 'enctype="multipart/form-data"';
					$table->form_title = $this->lang('newgroup_title2');
					$rows = array(
							$table->inputField( $this->lang('group_settings_f_title'), 'group_name', $g->title),
							$table->inputField( $this->lang('group_settings_f_alias'), 'group_alias', $g->groupname ),
							$table->textArea( $this->lang('group_settings_f_descr'), 'group_description', $g->about_me ),
							$table->radioButton( $this->lang('group_settings_f_type'), 'group_type', array(	'private'=>$this->lang('group_settings_f_tp_private'), 
															 										'public'=>$this->lang('group_settings_f_tp_public')),
																							($g->is_public? 'public' : 'private'  ) ),
							
							$table->fileField( $this->lang('group_settings_f_avatar'), 'form_avatar', '' ),
							$table->textField( '', '<a href="'.$C->SITE_URL.$g->groupname.'/tab:settings/subtab:main/del:groupavatar">'.$this->lang('group_settings_avatar_remove').'</a>' ),
							$table->submitButton( 'sbm', $this->lang('group_settings_f_btn') )
					);
					$tpl->layout->setVar('main_content', $table->createTableInput( $rows )); unset($rows);
				}else if( $subtab == 'admins' ){
					
									
					if(isset($_POST['add_admin'])){						
						$u_become_admin = $this->network->get_user_by_username($_POST['add_admin']);						
						if($u_become_admin){
							$is_member = (in_array($u_become_admin->id, $group_members))? TRUE : FALSE;
							if($is_member){
								$group->addAdmin($u_become_admin->id);
							}else{
								$errmsg = $this->lang('group_settings_add_admin_not_group_member', array('#USERNAME#'=>$_POST['add_admin']));
								$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('group_settings_f_err'), $errmsg ) );
							}
						}else{
							$errmsg = $this->lang('group_settings_add_admin_not_network_member', array('#USERNAME#'=>$_POST['add_admin']));
							$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('group_settings_f_err'), $errmsg ) );
						}
					//$group->deleteAdmin();
					}
					
					$activity = activityFactory::select('group');
					$activity->setTemplate( $tpl );
					$activity->setGroup( $g );
					
					//$activity->loadUsers('admins');
					$ifollow = array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users );
					$res = $db2->query('SELECT * FROM groups_admins AS ga LEFT JOIN users AS u ON ga.user_id=u.id  WHERE ga.group_id=' . $g->id);
					
					while($obj = $db2->fetch_object($res)) {
						if($obj->id != $this->user->id){
							$obj->id =$obj->id . ','. $obj->group_id;
						}
						$tpl->initRoutine('SingleUser', array( &$obj, &$ifollow, '', 'administration', 'remove_moderator' ));
						$tpl->routine->load();
					}					
					
					$table = new tableCreator();
					$table->form_enctype = 'enctype="multipart/form-data"';
					$table->form_title = $this->lang('newgroup_title2');
					$table->form_action = $C->SITE_URL . $g->groupname.'/tab:settings/subtab:admins'; 
					$rows = array(
							$table->inputField( $this->lang('group_admsett_f_add'), 'add_admin',''),
							$table->submitButton( 'sbm', $this->lang('group_settings_f_btn') )
					);
					$tpl->layout->setVar('main_content', $table->createTableInput( $rows )); unset($rows);					
				}else if( $subtab == 'privmembers' ){
					
					$activity = activityFactory::select('group');
					$activity->setTemplate( $tpl );
					$activity->setGroup( $g );
					$activity->loadUsers('privmembers');
					
				}elseif( $subtab == 'delgroup' ){
					$table = new tableCreator();
					$rows = array(
							$table->radioButton( $this->lang('group_settings_f_type'), 'postsact', array(	'keep'=>$this->lang('group_del_f_posts_keep'), 
															 										'del'=>$this->lang('group_del_f_posts_del')), 'del'),
							$table->passField( $this->lang('group_del_f_password'), 'password', '' ),
							$table->submitButton( 'sbm', $this->lang('group_del_ttl') )
					);
					$tpl->layout->setVar('main_content', $table->createTableInput( $rows )); unset($rows);
				}
				
				break;
				
		default: //updates
				
				if( $this->user->is_logged ){
					$tpl->initRoutine('Postform', array());
					$tpl->routine->load();
				}else{
					$tpl->useStaticHTML();
					$tpl->staticHTML->useActivityContainer();
				}
				
				$activity = activityFactory::select('group');
				$activity->setTemplate( $tpl );
				$activity->setGroup( $g );
				$result = $activity->loadPosts();
				
				if( isset($result[1]) && $result[1] > 0 ){
					$tpl->layout->useBlock('activity-show-more');
					$tpl->layout->setVar('activities_pager_value', htmlentities('{"activities_type":"group","activities_id":"'.$result[1].'","activities_group":"'.$g->id.'"}'));
					$tpl->layout->block->save('activity_container_show_more');
				}
				
				if( $this->user->is_logged && isset($result[0]) && $result[0] > 0 ){
					$table = new tableCreator();
					$tpl->layout->setVar('main_content_bottom',
							$table->hiddenField( 'activities_type', 'group' ) .
							$table->hiddenField( 'last_activity', intval($result[0]) ) .
							//hiddenField( 'activities_tab', $this->param('tab')? $this->param('tab') : 'all' ) .
							$table->hiddenField( 'activities_group', $g->id )
					);
				}
				
				if( ($this->user->is_logged && isset($result[0]) && $result[0] <= 0) && (isset($result[1]) && $result[1] <= 0) ){
					$table = new tableCreator();
					$tpl->layout->setVar('main_content_bottom',
							$table->hiddenField( 'activities_type', 'group' ) .
							$table->hiddenField( 'last_activity', 1 ) .
							//hiddenField( 'activities_tab', $this->param('tab')? $this->param('tab') : 'all' ) .
							$table->hiddenField( 'activities_group', $g->id )
					);
				}
				$tpl->layout->setVar('in_group', 'in '.$g->groupname);
				
				break;
	}
	
	$tpl->display();
?>
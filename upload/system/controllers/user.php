<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}elseif($C->PROTECT_OUTSIDE_PAGES && !$this->user->is_logged){
		$this->redirect('home');
	}
	
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/user.php');
	
	$u = $this->network->get_user_by_id(intval($this->params->user));
	if( !$u ){
		$this->redirect('dashboard');
	}
	
	$is_my_profile	= ($this->user->is_logged && $u->id==$this->user->id);
	$he_follows 	= $this->network->get_user_follows($u->id, TRUE, 'hefollows')->follow_users;
	if( $this->user->is_logged ){
		$i_follow 	= ( !$is_my_profile )? $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users : $he_follows;
	}else{
		$i_follow 	= array();
	}
	
	$i_follow = array_keys($i_follow);

	$is_admin_or_follows_me = ( $this->user->is_logged && $this->user->info->is_network_admin || isset( $he_follows[$this->user->id] ) );
	$is_profile_protected = ( $u->is_profile_protected && !$is_admin_or_follows_me && !$is_my_profile);
	$is_posts_protected = ( $u->is_posts_protected && !$is_admin_or_follows_me && !$is_my_profile);

	$tab = 'updates';
	if( $this->param('tab') ){
		$tab = $this->param('tab');
	}
	
	$subtab = 'all';
	if( $this->param('subtab') ){
		$subtab = $this->param('subtab');
	}
	
	if($tab =='friends' && $subtab != 'ifollow' && $subtab !='followers' && $subtab !='incommon'){
		$subtab = 'ifollow';
	}
	
	$paging_url	= $C->SITE_URL.$u->username.'/tab:'.$tab.'/subtab:'.$subtab.'/pg:';
	
	$udtls = $this->network->get_user_details_by_id(intval($this->params->user));
	$udtls = ($udtls === FALSE || empty($udtls))? array() : $udtls;

	//TEMPLATE START 
	$tpl = new template( array('page_title' => $u->username. ' - ' .$C->SITE_TITLE, 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('UserLeftColumn', array( &$u, &$he_follows ));
	$tpl->routine->load();

	$menu = array( 	array('url' => $u->username.'/tab:updates', 	'css_class' => (($tab === 'updates')? ' selected' : ''), 	'title' => $this->lang('usr_tab_updates') ),
	);
	
	if( !$is_profile_protected ){
		$menu[] = array('url' => $u->username.'/tab:info', 		'css_class' => (($tab === 'info')? ' selected' : ''), 		'title' => $this->lang('usr_tab_info') );
		$menu[] = array('url' => $u->username.'/tab:friends', 	'css_class' => (($tab === 'friends')? ' selected' : ''), 	'title' => $this->lang('usr_tab_coleagues') );
		$menu[] = array('url' => $u->username.'/tab:groups', 	'css_class' => (($tab === 'groups')? ' selected' : ''), 	'title' => $this->lang('usr_tab_groups') );
		
	}
	
	$tpl->layout->setVar( 'subheader_placeholder', $tpl->designer->createMenu( 'navigation', $menu ) ); unset($menu);

	$tpl->layout->useBlock('user-header-info');
	$tpl->layout->block->setVar('user_header_username', getThisUserCommunityName($u));	
	$tpl->layout->block->setVar('user_header_position', htmlspecialchars($u->location)); //should be position
	$tpl->layout->block->setVar('user_header_activity', $this->lang('usr_top_activity_count', array('#NUM_FOLLOWERS#'=>$u->num_followers, '#NUM_FOLLOWING#'=>count($he_follows), '#NUM_POSTS#'=>$u->num_posts )));
	
	if( $this->user->is_logged ){
		$tpl->layout->block->setVar('user_header_follow_button', $is_my_profile? '' : 
									(!in_array($u->id, $i_follow)? $tpl->designer->usersSettingsMenu($u->id, true) : $tpl->designer->usersSettingsMenu($u->id, false)) 
		);
	}
	
	$tpl->layout->block->save('main_content_top_placeholder', true);
	
	switch($tab){
		case 'info':
					if( !$is_profile_protected ){
						$dtls = array(
							'Date Register'=>strftime($this->lang('usr_info_birthdate_dtformat'), $u->reg_date),
							'Last Login Date'=> strftime($this->lang('usr_info_birthdate_dtformat'), $u->lastlogin_date),
						);
						if( !empty($u->location) ){
							$dtls['Location'] = $u->location;
						}
						if( !empty($u->position) ){
							$dtls['Position'] = $u->position;
						}
						if( !empty($u->about_me) ){
							$dtls['About me'] = $u->about_me;
						}
						if( !empty($u->gender) ){
							$dtls['Gender'] =  $this->lang('usr_info_aboutme_gender_'.$u->gender);
						}
						
						if($is_admin_or_follows_me || $is_my_profile){
							$dtls['Email'] = $u->email;
							
							if(!empty($u->birthdate) && $u->birthdate!= '0000-00-00' ) {
								$dtls['Birthdate'] = date('m/d/Y',strtotime($u->birthdate));
							}
							if(!empty($u->position)) {
								$dtls['Position'] = $u->position;
							}
							if( isset($udtls->website) && !empty($udtls->website) && is_valid_url($udtls->website) ){
								$dtls['Website'] = $udtls->website;
							}
							if( isset($udtls->work_phone) && !empty($udtls->work_phone) ){
								$dtls['Work phone'] = $udtls->work_phone;
							}
							if( isset($udtls->personal_phone) && !empty($udtls->personal_phone) ){
								$dtls['Personal phone'] = $udtls->personal_phone;
							}
						}
						
						if( $this->user->is_logged && $this->user->info->is_network_admin ){
							$dtls['Last Login IP'] = long2ip($u->lastlogin_ip);
							$dtls['Registration IP'] = long2ip($u->reg_ip);
						}
						
						$tpl->layout->setVar( 'main_content', $tpl->designer->createTableDetailsBlock( '', $dtls, 'user-details' ) ); unset($dtls);
					}else{	
						$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox($this->lang('noposts_usrprofileprotected_ttl'), $this->lang('post_profile_protected')));
					}
					break;

		case 'friends':
					if( !$is_profile_protected ){
						$menu = array( 	array('url' => $u->username.'/tab:friends/subtab:ifollow', 	 'css_class' => (($subtab === 'ifollow')? ' active' : ''), 		'title' => $this->lang('usr_left_follows') ),
										array('url' => $u->username.'/tab:friends/subtab:followers', 'css_class' => (($subtab === 'followers')? ' active' : ''), 		'title' => $this->lang('usr_left_followers') ),
						);
						if( !$is_my_profile ){
							$menu[] = array('url' => $u->username.'/tab:friends/subtab:incommon',  'css_class' => (($subtab === 'incommon')? ' active' : ''), 	'title' => $this->lang('usr_coleagues_subtab3') );
						}
							
						$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu( 'tabs-navigation', $menu ) ); unset($menu);
						
	 					$activity = activityFactory::select('user');
						$activity->setTemplate( $tpl );
	 					$activity->setUser( $u );
	 					$activity->loadUsers();
	 					
	 					$tpl->layout->setVar( 'main_content_bottom', $tpl->designer->pager( $activity->num_results, $activity->num_pages, $activity->pg, $paging_url ) );
					}else{
						$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox($this->lang('noposts_usrprofileprotected_ttl'), $this->lang('post_profile_protected')));
					}
					
					break;
					
		case 'groups':
					if( !$is_profile_protected ){
						$activity = activityFactory::select('user');
						$activity->setTemplate( $tpl );
						$activity->setUser( $u );
						$activity->loadGroups();
						
						$tpl->layout->setVar( 'main_content_bottom', $tpl->designer->pager( $activity->num_results, $activity->num_pages, $activity->pg, $paging_url ) );
					}else{
						$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox($this->lang('noposts_usrprofileprotected_ttl'), $this->lang('post_profile_protected')));
					}
					break;
						
		default: 
					$menu = array( 	
								array('url' => $u->username.'/tab:updates/subtab:all', 		'css_class' => (($subtab === 'all')? ' active' : ''), 		'title' => $this->lang('tab_user_all') ),
							);
				
					
					$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu( 'tabs-navigation', $menu, 'user_subtab_menu' ) ); unset($menu);
					
					if( !$is_posts_protected ){
						$tpl->useStaticHTML();
						$tpl->staticHTML->useActivityContainer();
						
						$activity = activityFactory::select('user');
						$activity->setTemplate( $tpl );
						$activity->setUser( $u );
						$result = $activity->loadPosts();
						
						if( $this->user->is_logged && isset($result[1]) && $result[1] > 0 ){
							$tpl->layout->useBlock('activity-show-more');
							$tpl->layout->setVar('activities_pager_value', htmlentities('{"activities_type":"user","activities_id":"'.$result[1].'","activities_user":"'.$u->id.'"}'));
							$tpl->layout->block->save('activity_container_show_more');
						}
					}else{
						$tpl->layout->setVar('main_content', $tpl->designer->createNoPostBox($this->lang('noposts_usrprofileprotected_ttl'), $this->lang('noposts_usrprofileprotected_txt', array('#USERNAME#'=>$u->username))));
					}
					
					break;
	}
	
	$tpl->display();
?>
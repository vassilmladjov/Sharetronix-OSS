<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}

	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/user.php');
	$this->load_langfile('inside/dashboard.php');
	
	$u = $this->network->get_user_by_id(intval($this->params->user));
	if( !$u ){
		$this->redirect('dashboard');
	}
	
	$is_my_profile	= ($this->user->is_logged && $u->id==$this->user->id);
	$he_follows 	= $this->network->get_user_follows($u->id, FALSE, 'hefollows')->follow_users;
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
	
	$paging_url	= $C->SITE_URL.$u->username.'/tab:'.$tab.'/subtab:'.$subtab.'/pg:';
	
	$udtls = $this->network->get_user_details_by_id(intval($this->params->user));
	$udtls = ($udtls === FALSE || empty($udtls))? array() : $udtls;
 
	//TEMPLATE START 
	$header_title = $u->fullname;
	$header_buttons = '<a href="'.$C->SITE_URL.'" class="btn home"><span></span></a>'. ((!$is_my_profile) ? '<a href="#" class="btn send-message"><span></span></a>' : '<a href="#" class="btn new-post"><span></span></a>');
	
	$tpl = new template( array(
			'page_title' => $u->fullname. ' - ' .$C->SITE_TITLE, 
			'header_title' => $header_title,
			'header_buttons' => $header_buttons
		)
	);
	
	$tpl->layout->useBlock('user-profile-info');
	$tpl->layout->block->setVar('user_profile_avatarimg', $u->avatar);
	$tpl->layout->block->setVar('user_profile_username', $u->fullname);
	$tpl->layout->block->setVar('user_profile_jobtitle', htmlspecialchars($u->position));
	
	$tpl->layout->block->setVar('user_id', $u->id);
	
	if($user->is_logged == true && $user->id != $u->id) {
		$tpl->layout->block->setVar(
				'user_header_follow_button',
				(!in_array($u->id, $i_follow)  
				? $tpl->designer->usersSettingsMenu($u->id, true) 
				: $tpl->designer->usersSettingsMenu($u->id, false)
				)
		);	
	}
	$tpl->layout->block->save('main_content');
	
	//$menu = array();
	
	
	$menu = array( 	array('url' => $u->username.'/tab:updates', 	'css_class' => (($tab === 'updates')? ' selected' : ''), 	'title' => $this->lang('usr_tab_updates') ),
	);
	
	
	if( !$is_profile_protected ){
		$menu[] = array('url' => $u->username.'/tab:info', 		'css_class' => (($tab === 'info')? ' selected' : ''), 		'title' => $this->lang('usr_tab_info') );
	}
	
	$tpl->layout->setVar( 'user_profile_navigation', $tpl->designer->createMenu( 'navigation', $menu, 'user_navigation_top_menu' ) ); unset($menu);
	
	
	
	switch($tab){
		case 'info':
			
					$tmp = '<div class="details">';
			
					if( !$is_profile_protected ){
						
						$tmp .= !empty($udtls->work_phone)? '<a href="tel:'.htmlspecialchars($udtls->work_phone).'" class="phone"><strong>'.$this->lang('usr_left_cnt_pphone').'</strong>'.$this->lang('usr_mobile_call_btn').' '.htmlspecialchars($udtls->work_phone).'</a>' : '';
						$tmp .= !empty($udtls->personal_phone)? '<a href="tel:'.htmlspecialchars($udtls->personal_phone).'" class="phone"><strong>'.$this->lang('usr_left_cnt_wphone').'</strong>'.$this->lang('usr_mobile_call_btn').' '.htmlspecialchars($udtls->personal_phone).'</a>' : '';
						$tmp .= $is_admin_or_follows_me? '<a href="mailto:'.$u->email.'" class="email"><strong>'.$this->lang('usr_prof_mobile_email').'</strong>'.$u->email.'</a>' : '';
						
						$tmp .= !empty($u->location)? '
								<a href="https://maps.google.com/?q='. $u->location.'" class="location"><strong>'.$this->lang('usr_prof_mobile_location').'</strong>'. $u->location.'<br />
									<img src="http://maps.googleapis.com/maps/api/staticmap?center='. $u->location.'&zoom=13&size=420x300&markers='. $u->location.'&sensor=false" />
								</a>
								' : '';
						
						$tmp .= !empty($u->gender) ? '<span class="gender '.(($u->gender == 'm')? 'male' : 'female').'"><strong>Gender</strong>'.(($u->gender == 'm')? $this->lang('usr_info_aboutme_gender_m') : $this->lang('usr_info_aboutme_gender_f')).'</span>' : '';
						
						$tmp .= !empty($u->position) ? '<span class="job-title"><strong>'.$this->lang('usr_prof_nt_jobtitle').'</strong>'.htmlspecialchars($u->position).'</span>' : '';
						
						$tmp .=  !empty($u->about_me) ? '<span class="about"><strong>'.$this->lang('usr_info_section_aboutme').'</strong>'. htmlspecialchars($u->about_me) . '</span>' : '';
						
						
						
					}else{	
						$tmp .= $tpl->designer->createNoPostBox($this->lang('noposts_usrprofileprotected_ttl'), $this->lang('post_profile_protected'));
					}
					
					
					$tmp .= '</div>';
					$tpl->layout->setVar('user_profile_details', $tmp);
					
					break;

		case 'friends':
					if( !$is_profile_protected ){
						$menu = array( 	array('url' => $u->username.'/tab:friends/subtab:ifollow', 	 'css_class' => (($subtab === 'ifollow')? ' active' : ''), 		'title' => $this->lang('usr_left_follows') ),
										array('url' => $u->username.'/tab:friends/subtab:followers', 'css_class' => (($subtab === 'followers')? ' active' : ''), 		'title' => $this->lang('usr_left_followers') ),
						);
						if( !$is_my_profile ){
							$menu[] = array('url' => $u->username.'/tab:friends/subtab:incommon',  'css_class' => (($subtab === 'incommon')? ' active' : ''), 	'title' => $this->lang('usr_coleagues_subtab3') );
						}
							
						$tpl->layout->setVar( 'main_content', $tpl->designer->createMenu( 'tabs-navigation', $menu ) ); unset($menu);
						
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
						
		case 'updates': //default: 
					$tpl->useStaticHTML();
					$tpl->staticHTML->useActivityContainer();
					
					if( !$is_posts_protected ){		
						$activity = activityFactory::select('user');
						$activity->setTemplate( $tpl );
						$activity->setUser( $u );
						$result = $activity->loadPosts();
						
						if( isset($result[1]) && $result[1] > 0 ){
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
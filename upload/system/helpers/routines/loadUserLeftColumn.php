<?php
	function loadUserLeftColumn( $tpl, $params )
	{
		global $C;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$network = & $GLOBALS['network'];
		
		if( !isset($params[0]) || !isset($params[1]) ){
			return;
		}
		
		$u = $params[0];
		$he_follows = $params[1]; 
		$if_he_follows_me 	= isset( $he_follows[$user->id] );
		
		$is_admin_or_follows_me = ( $user->is_logged && $user->info->is_network_admin || $if_he_follows_me );
		$is_dm_protected = ( $u->is_dm_protected && !$is_admin_or_follows_me );
		
		$tmp_he_follows = array();
		if( count($he_follows) > 0 ) {
			$he_follows = array_slice(array_keys($he_follows), 0, 10);
			foreach( $he_follows as $uid ){
				$tmp = $network->get_user_by_id($uid);
				if( $tmp ){
					$tmp_he_follows[] = array('username'=>$tmp->username, 'avatar'=>$tmp->avatar);
				}
			}
		}

		$tpl->layout->setVar( 'left_content', '<div class="profile-avatar '.(($u->active)? '' : 'suspended') .'"><img src="'.$C->STORAGE_URL.'avatars/'.$u->avatar.'" alt="'.$u->fullname.'" /><span class="avatar-overlay"><span></span></span></div>');
		
		$user_message_data = '{"users_id": '.$u->id.', "users_name":"'.$u->fullname.'", "users_username":"'.$u->username.'"}';
		$user_info = '<ul class="personal-information">'
						.(( $user->is_logged && $user->info->is_network_admin || $if_he_follows_me ) ? '
						<li class="personal-information-email"><a href="mailto:'.$u->email.'">'.$u->email.'</a></li> '. 
						(!empty($u->pphone)? '<li>' . $page->lang('usr_left_cnt_pphone').' '.htmlspecialchars($u->pphone) .'</li>' : '') : '').
						
						( ( $user->is_logged && !$is_dm_protected && $user->id != $u->id)? '<li><a class="plain-btn send-message" data-role="services" data-namespace="users" data-value="'.htmlentities($user_message_data).'" data-action="sendMessagePopup">'.$page->lang('global_send_message_to_user').'</a></li>' : '' ). 
					'</ul>';
		$tpl->layout->setVar( 'left_content', $tpl->designer->createInfoBlock( '', $user_info));

		
		$tpl->layout->setVar( 'left_content', 
			$tpl->designer->createInfoBlock( $page->lang('usr_left_following'), $tpl->designer->createUserLinks( $tmp_he_follows , 'thumbs3') ) .
			$tpl->designer->createInfoBlock( $page->lang('usr_left_tgsubx_ttl'), $network->get_recent_posttags(10, $u->id, 'user') )
		);
		
		if( count($u->tags) > 0 ){
			$tpl->layout->setVar( 'left_content', $tpl->designer->createInfoBlock( $page->lang('usr_left_tgsubx_ttl'), $tpl->designer->createTagLinks( $u->tags, 'users' ) ) );
		}
		
		unset($tmp_he_follows, $tmp );
	}
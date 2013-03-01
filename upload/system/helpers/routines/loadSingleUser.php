<?php

	function loadSingleUser( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$obj = $params[0];
		$ifollow = $params[1];
		$float = isset($params[2])? $params[2] : '';
		$regime = isset( $params[3] )? $params[3] : 'friendship';
		$regime_action = isset( $params[4] )? $params[4] : '';
		
		$pm->onSingleUserLoad( $obj );
		
		$to_follow = ($obj->id !== $user->id); 
		
		if( $to_follow == true && is_array($ifollow) ){
			$to_follow = !in_array( $obj->id, $ifollow );
		}
		
		$tpl->layout->useBlock('single-user');
				
		$tpl->layout->block->setVar( 'single_user_avatar', '<a href="'.userlink($obj->username).'" class="author bizcard" data-userid="'.$obj->id.'"><img src="'.$C->STORAGE_URL.'avatars/thumbs1/'.(empty($obj->avatar)? $C->DEF_AVATAR_USER : $obj->avatar).'" alt="'.getThisUserCommunityName($obj).'" /></a>');
		$tpl->layout->block->setVar( 'single_user_username', '<a href="'.userlink($obj->username).'" class="author bizcard" data-userid="'.$obj->id.'">'. getThisUserCommunityName($obj) .'</a>' );
		$tpl->layout->block->setVar( 'single_user_activity', '<a href="'.$C->SITE_URL.$obj->username.'/tab:friends/subtab:followers">'. $page->lang('usr_activity_count_small', array('#NUM_FOLLOWERS#'=>$obj->num_followers, '#NUM_POSTS#'=>$obj->num_posts)));
		if($user->is_logged){
			$tpl->layout->block->setVar( 'single_user_email', htmlspecialchars(str_cut($obj->about_me, 50) ) );
		}
		if( isset($obj->active) && !$obj->active ){
			$tpl->layout->block->setVar( 'single_user_suspended', 'suspended' );
		}
		if( $user->is_logged && ($obj->id !== $user->id) ){
			$tpl->layout->block->setVar( 'single_user_follow_unfollow', $tpl->designer->usersSettingsMenu($obj->id, $to_follow, $regime, $regime_action) );
		}
		$tpl->layout->block->setVar( 'single_user_float', $float );
		
		$tpl->layout->block->save( 'main_content', true );
	}
<?php
	function loadSingleGroup( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$obj = $params[0];
		$ifollow = $params[1];
		$group_admin = false;
		if(isset($params[2])){
			$group_admin = $params[2];
		}
		$pm->onSingleGroupLoad( $obj );
		
		$tpl->layout->useBlock('single-group');

		$group_type =  ($obj->is_public)? 'public' : 'private' ;
		$tpl->layout->block->setVar('single_group_type', $group_type);
		$tpl->layout->block->setVar( 'single_group_avatar', '<a href="'.$C->SITE_URL.$obj->groupname.'"><img src="'.$C->STORAGE_URL.'avatars/thumbs1/'.(empty($obj->avatar)? $C->DEF_AVATAR_GROUP : $obj->avatar).'" alt="'.$obj->groupname.'"/></a>');
		$tpl->layout->block->setVar( 'single_group_name', '<a href="'.$C->SITE_URL.$obj->groupname.'">'.ucfirst($obj->title).'</a>' );
		$tpl->layout->block->setVar( 'single_group_activity', '<a href="'.$C->SITE_URL.$obj->groupname. '/tab:members">'.$page->lang('group_header_descr_activity', array('#NUM_MEMBERS#'=> $obj->num_followers, '#NUM_POSTS#'=>$obj->num_posts)));
		$tpl->layout->block->setVar( 'single_group_description', $obj->about_me );
		if( $user->is_logged ){
			$follow = !in_array($obj->id, $ifollow);
			$tpl->layout->block->setVar( 'single_group_join_leave', $tpl->designer->groupsSettingsMenu( $obj->id, $follow, $group_admin, $obj->groupname ) ); unset($menu_items);
		}
		
		$tpl->layout->block->save( 'main_content', true );
	}
<?php
	global $db2;
	
	$page 		= & $GLOBALS['page'];
	$user 		= & $GLOBALS['user'];
	$network 	= & $GLOBALS['network'];
	$pm 		= & $GLOBALS['plugins_manager'];
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	global $C;
	require $C->INCPATH.'helpers/func_html.php';
	
	switch( $ajax_action ){
		
		case 'join': 
			
			$groups_id = intval($_POST['groups_id']);
			
			$g	= $network->get_group_by_id( $groups_id );
			if( ! $g ) {
				echo 'ERROR';
				return;
			}
			if( ! $user->follow_group($g->id, TRUE) ) {
				echo 'ERROR';
				return;
			}
			if( $errmsg = $pm->getEventCallErrorMessage() ){
				echo 'ERROR:' . $errmsg;
				return;
			}
			
			echo groupsSettingsMenu( $groups_id, FALSE );
			return;
			
			break;
		case 'leave':
			
			$groups_id = intval($_POST['groups_id']);
				
			$g	= $network->get_group_by_id( $groups_id );
			if( ! $g ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error14');
				return;
			}
			if( ! $user->follow_group($g->id, FALSE) ) {
				echo 'ERROR:'.$page->lang('global_ajax_post_error15');
				return;
			}
			if( $errmsg = $pm->getEventCallErrorMessage() ){
				echo 'ERROR:' . $errmsg;
				return;
			}
			
			echo groupsSettingsMenu( $groups_id, TRUE );
			return;
				
			break;
		
		case 'autocomplete':
			$name		= isset($_POST['groups_name']) ? trim($_POST['groups_name']) : '';
			if( empty($name) ) {
				echo 'ERROR';
				return;
			}
				
			$w	= $db2->e($name);
			$r	= $db2->query('SELECT id, groupname, avatar FROM groups WHERE (groupname LIKE "%'.$w.'%" OR title LIKE "%'.$w.'%") ORDER BY num_followers DESC, groupname ASC LIMIT 5');
			$n	= $db2->num_rows();
			if( $n > 0 ){
				$i = 0;
				$groups = array();
				while($obj = $db2->fetch_object($r)) {
					$groups[$i]['id'] 				= $obj->id;
					$groups[$i]['groupname'] 		= $obj->groupname;
					$groups[$i]['title'] 			= $obj->title;
					$groups[$i]['profile_url'] 		= userlink($obj->groupname);
					$groups[$i]['avatar_url'] 		= getAvatarUrl( (empty($obj->avatar)? $C->DEF_AVATAR_GROUP : $obj->avatar), 'thumbs3' );
					$i++;
				}
		
				echo json_encode($groups);
				return;
			}else{
				echo 'ERROR';
				return;
			}
			break;

	}
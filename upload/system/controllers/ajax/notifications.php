<?php
	global $db2, $C, $page, $user, $network;
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){

		case 'showdetails':
			
			$_POST = &$_POST['users_id']; //@TODO: fix this
			if( !isset($_POST['notifications_type']) || !isset($_POST['notifications_object']) || !isset($_POST['notifications_ingroup']) ) {
				echo 'ERROR';
				return;
			}
			$ntype 	= $db2->e($_POST['notifications_type']);
			$nname	= $db2->e($_POST['notifications_object']);
			$in_group	= intval($_POST['notifications_ingroup']);
			
			$nname 	= explode('_', $nname);
			$in_where = '';
			
			if( $nname[0] == 'none' ){
				unset($nname[0]);
			}else{
				$in_where .= ' AND notif_object_type="'.$nname[0].'" ';
			}
			
			if( $nname[1] == '0' ){
				unset($nname[1]);
			}else{
				$in_where .= ' AND notif_object_id="'.$nname[1].'" ';
			}
			
			
			$html = '';
			
			if( $in_group == 0 ){
				$db2->query('SELECT u.username, u.avatar FROM users u, notifications n WHERE u.id = n.from_user_id AND notif_type="'.trim($ntype).'" AND to_user_id="'.$user->id.'"'.$in_where.' GROUP BY from_user_id');
				while( $o = $db2->fetch_object() ){
					$html .= '<a href="'.$C->SITE_URL.$o->username.'">';
					$html .= '<img src="'.$C->STORAGE_URL.'avatars/thumbs3/'.(!empty($o->avatar)? $o->avatar : $GLOBALS['C']->DEF_AVATAR_USER).'" alt="'.$o->username.'" /> ';
					$html .= $o->username . '</a><br>';
					
				}
			}else{
				$db2->query('SELECT in_group_id AS gid FROM notifications WHERE notif_type="'.trim($ntype).'" AND in_group_id>0 AND from_user_id="'.intval($nname[1]).'" AND to_user_id="'.$user->id.'" GROUP BY in_group_id');
				while( $o = $db2->fetch_object() ){
					$g = $network->get_group_by_id($o->gid);
					if( !$g ){
						continue;
					}
					$html .= '<a href="'.$C->SITE_URL.$g->groupname.'">';
					$html .= '<img src="'.$C->STORAGE_URL.'avatars/thumbs3/'.(!empty($o->avatar)? $o->avatar : $GLOBALS['C']->DEF_AVATAR_GROUP).'" alt="'.$g->groupname.'" /> ';
					$html .= $g->groupname.'</a><br>';
					
				}
			}
			
			echo $html;
			return;
			
			break;
	}
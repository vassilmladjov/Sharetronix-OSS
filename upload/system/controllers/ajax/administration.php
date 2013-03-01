<?php
	global $db2, $C;
	
	$page = & $GLOBALS['page'];
	$user = & $GLOBALS['user'];
	$network = & $GLOBALS['network'];
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){
		
		
		case 'removemoderator':
			$users_id 	= isset($_POST[ 'users_id' ])? 	$_POST[ 'users_id' ] 		: 		'';
			if( empty($users_id) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error2');
				return;
			}
			
			$res = explode(",",$users_id);
			if( !isset($res[0]) || !isset($res[1]) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error2');
				return;
			}
			$res[0] = (int) $res[0];
			$res[1] = (int) $res[1];
			
			$g	= $this->network->get_group_by_id($res[1]);				
			if( !$g || $res[0] < 1 || $res[1] < 1){
				echo 'ERROR:'.$page->lang('global_ajax_post_error2');
				return;
			}
				
			$moderator = new group($g);
			$moderator->deleteAdmin($users_id);
				
			echo $page->lang('global_ajax_post_ok');
			return;
				
			break;

		case 'removeadmin':
			$users_id 	= isset($_POST[ 'users_id' ])? 	$_POST[ 'users_id' ] 		: 		'';

			if( empty($users_id) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error2');
				return;
			}
			
			$admin = new communityAdministration();
			$admin->removeAdministrator($users_id);
			
			echo $page->lang('global_ajax_post_ok1');
			return;
			
			break;
		
		case 'activateuser':
			$users_id 	= isset($_POST[ 'users_id' ])? 	$_POST[ 'users_id' ] 		: 		'';
		
			if( empty($users_id) ){
				echo 'ERROR:'.$page->lang('global_ajax_post_error3');
				return;
			}
				
			$admin = new communityAdministration();
			$admin->activateUser($users_id);
				
			echo $page->lang('global_ajax_post_ok2');
			return;
				
			break;
	}
<?php
	global $db2, $C;
	
	$page 		= & $GLOBALS['page'];
	$user 		= & $GLOBALS['user'];
	$network 	= & $GLOBALS['network'];
	$pm 		= & $GLOBALS['plugins_manager'];
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){
		
		case 'get':
			
			break;
		
		case 'checknew':
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'last_activity' ])? 	intval($_POST[ 'last_activity' ]) 	: 		0;
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_tab 	= isset($_POST[ 'activities_tab' ])?	$_POST[ 'activities_tab' ] 		: 		'';

			if( empty($activities_type) || empty($activities_id) ){
				echo 'ERROR';
				return;
			}
			
			$activity = activityFactory::select($activities_type);
			if( !empty($activities_group) ){
				$g = $network->get_group_by_id($activities_group);
				$activity->setGroup( $g );
			}
			if( !empty($activities_tab) ){
				$activity->tab = $activities_tab;
			}
			$activity->setNewetsPost( $activities_id );
				
			$new_activities = $activity->checkNewPosts();
			
			$new_activities_text = ' new activity';
			if($new_activities >1)$new_activities_text = ' new activities';
			$new_activities_tab = ( $activities_type === 'dashboard' )? $network->get_dashboard_tabstate($user->id, array('all', 'commented', '@me', 'private','notifications')) : array('all'=>0, '@me'=>0, 'commented'=>0, 'private'=>0, 'notifications'=>0);
			$answer = array(	'html'=>$new_activities. $new_activities_text, 
								'new_activities_dashboard'=>$new_activities, 
								'new_activities_tab_all'=>$new_activities_tab['all'], 
								'new_activities_tab_at'=>$new_activities_tab['@me'],
								'new_activities_tab_commented'=>$new_activities_tab['commented'],
								'new_messages'=>$new_activities_tab['private'],
								'new_notifications'=>$new_activities_tab['notifications']
			);
				
			echo json_encode($answer);
			
			break;
			
		case 'getnew': 
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'last_activity' ])? 	intval($_POST[ 'last_activity' ]) 	: 		0;
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_tab 	= isset($_POST[ 'activities_tab' ])? 	$_POST[ 'activities_tab' ] 			: 		'';
			
			if( empty($activities_type) || empty($activities_id) ){
				echo 'ERROR';
				return;
			}
			
			$tpl = new template(array(), FALSE);

			$tpl->useStaticHTML();
			$tpl->staticHTML->useActivityContainer();
			
			$activity = activityFactory::select($activities_type);
			$activity->setTemplate( $tpl );
			if( !empty($activities_group) ){
				$g = $network->get_group_by_id($activities_group);
				$activity->setGroup( $g );
			}
			if( !empty($activities_tab) ){
				$activity->tab = $activities_tab;
			}
			
			$result = $activity->loadPosts($activities_id, TRUE);
			
			$answer = array('html'=>$tpl->display(true), 'first_activities_id'=>$result[0]);
			
			echo json_encode($answer);
			
			break;
			
		case 'getall':
			$activities_type 	= isset($_POST[ 'activities_type' ])? 	$_POST[ 'activities_type' ] 		: 		'';
			$activities_id 		= isset($_POST[ 'activities_id' ])? 	intval($_POST[ 'activities_id' ]) 	: 		0;
			$activities_tab 	= isset($_POST[ 'activities_tab' ])? 	$_POST[ 'activities_tab' ] 			: 		'';
			$activities_group 	= isset($_POST[ 'activities_group' ])?	$_POST[ 'activities_group' ] 		: 		'';
			$activities_user	= isset($_POST[ 'activities_user' ])? 	$_POST[ 'activities_user' ] 		: 		'';
			$activities_search	= isset($_POST[ 'activities_search' ])? $_POST[ 'activities_search' ] 		: 		'';

			if( !empty($activities_type) && $activities_id ){
				$tpl = new template(array(), FALSE);
				
				$tpl->useStaticHTML();
				$tpl->staticHTML->useActivityContainer();
				
				$activity = activityFactory::select($activities_type);
				$activity->setTemplate( $tpl );
				if( !empty($activities_group) ){
					$g = $network->get_group_by_id($activities_group);
					$activity->setGroup( $g );
				}
				if( !empty($activities_user) ){
					$u = $network->get_user_by_id($activities_user);
					$activity->setUser( $u );
				}
				
				if( !empty($activities_tab) ){
					$activity->tab = $activities_tab;
				}
				
				if( !empty($activities_search) ){
					$activity->search_string = $activities_search;
				}
				
				$result = $activity->loadPosts($activities_id); 
				
				$answer = array('html'=>$tpl->display(true), 'last_activities_id'=>$result[1]);
				
				echo json_encode($answer);
			}
				
			break;
		case 'set':
			
			$activity_text 	= isset($_POST[ 'activities_text' ])? $_POST[ 'activities_text' ] : '';
			$activities_token 	= isset($_POST[ 'token' ])? $_POST[ 'token' ] : '';
			
			if( !empty($activity_text) && !empty($activities_token) ){
				
				//$p = new newpost();
				
				//
				$sess = &$user->sess;
				if( ! isset($sess['TEMP_ACTIVITY_POSTS']) ) {
					$sess['TEMP_ACTIVITY_POSTS']	= array();
				}
				if( ! isset($sess['TEMP_ACTIVITY_POSTS'][$activities_token]) ) {
					$sess['TEMP_ACTIVITY_POSTS'][$activities_token]	= new newpost();
				}
				$p	= & $sess['TEMP_ACTIVITY_POSTS'][$activities_token];
				//
				if( isset($sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$activities_token]) ){
					$att	= & $sess['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$activities_token];
				}
				
				$p->set_message($activity_text);
				
				if( isset($_POST[ 'activities_group' ]) && !empty($_POST[ 'activities_group' ]) ){
					$p->set_group_id( intval( $_POST[ 'activities_group' ] ) );
				}
				
				if( isset($att['image']) ){ 
					foreach($att['image'] as $img){
						if( $ii = $p->attach_image($C->STORAGE_TMP_DIR.$img->tempfile, $img->filename) ) {
							rm($C->STORAGE_TMP_DIR.$img->tempfile);
						}
					}
					unset($att['image']);
				}
				
				if( isset($att['file']) ){
					foreach($att['file'] as $file){
						if( $ff = $p->attach_file($C->STORAGE_TMP_DIR.$file->tempfile, $file->filename, $file->detected_type) ) {
							rm($C->STORAGE_TMP_DIR.$file->tempfile);
						}
					}
					unset($att['file']);
				}
				
				if( isset($att['link']) ){
					foreach($att['link'] as $link){
						$p->attach_link($link);
					}
					unset($att['link']);
				}
				
				if( isset($att['videoembed']) ){
					foreach($att['videoembed'] as $vid){
						$p->attach_videoembed($vid);
					}
					unset($att['videoembed']);
				}
				
				$res	= $p->save();

				$p->remove_post_cache();
				
				if( $res ){
					$activity_id = explode('_', $res);
					$activity_id = intval($activity_id[0]);
					$activity_type = $activity_id[1]; //delete private post
				
					$obj = $db2->query( 'SELECT * FROM '.( $activity_type=='private'? 'posts_pr' : 'posts' ).' WHERE id="'. $activity_id .'" LIMIT 1' );
					$obj = $db2->fetch_object($obj);
					$obj->type = $activity_type=='private'? 'private' : 'public';
					
					$tpl = new template(array(), FALSE);
					
					$tpl->useStaticHTML();
					$tpl->staticHTML->useActivityContainer();
					
					$tpl->initRoutine('SingleActivity', array( &$obj, FALSE ));
					$tpl->routine->load();
					
					$answer = array('html'=>$tpl->display(true), 'inserted_activities_id'=>$activity_id);
					
					echo json_encode($answer);
					return;
				}
				
				if( $errmsg = $pm->getEventCallErrorMessage() ){
					echo 'ERROR:' . $errmsg;
					return;
				}
				
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
			}
			
			break;
			
		case 'delete':	
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) )
			{
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error1');
					return;
				}
				if( $p->delete_this_post() ) {
					echo 'OK';
					return;
				}
				
				if( $errmsg = $pm->getEventCallErrorMessage() ){
					echo 'ERROR:' . $errmsg;
					return;
				}
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
		
		case 'bookmark':
				
				if( isset($_POST['activities_id']) && isset($_POST['activities_type']) )
				{
					$p	= new post($_POST['activities_type'], $_POST['activities_id']);
					if( $p->error ) {
						echo 'ERROR:'.$page->lang('global_ajax_post_error1');
						return;
					}
					
					$type = $p->is_post_faved()? FALSE : TRUE;
					
					if( $p->fave_post($type) ) {
						echo 'OK';
						return;
					}
				}
					
				echo 'ERROR:'.$page->lang('global_ajax_post_error');
				return;
					
				break;
	}
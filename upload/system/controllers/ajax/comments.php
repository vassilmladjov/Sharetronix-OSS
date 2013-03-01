<?php
	$page = & $GLOBALS['page'];	
	
	$page->load_langfile('inside/global.php');
	$page->load_langfile('inside/dashboard.php');
	
	switch( $ajax_action ){
		
		case 'get': 
			
			
			break;
			
		case 'getall':
			
			if( isset($_POST['activities_id']) && isset($_POST['activities_type']) )
			{
				$p	= new post($_POST['activities_type'], $_POST['activities_id']);
				if( $p->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error');
					return;
				}
				$comments = $p->get_all_comments();
				
				$tpl = new template(array(), FALSE);
				
				foreach( $comments as $comment ){
					$tpl->initRoutine('SingleActivityComment', array( &$comment, TRUE ));
					$tpl->routine->load();
				}
				
				$tpl->display();

				return;
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
		case 'set':
			
			if( isset($_POST['activities_id'], $_POST['activities_type'], $_POST['comments_text']) )
			{
				$msg	= isset($_POST['comments_text']) ? trim($_POST['comments_text']) : '';
				if( empty($msg) ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error12');
					return;
				}
				$c	= new newpostcomment( new post($_POST['activities_type'], $_POST['activities_id']) );
				if( $c->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error11');
					return;
				}
				$c->set_message($msg);
				if( FALSE !== ( $post_id = $c->save() ) ) {
					
					$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $c->id );
					if( $c->error ) {
						echo 'ERROR:'.$page->lang('global_ajax_post_error11');
						return;
					}
					
					$tpl = new template(array(), FALSE);

					$tpl->initRoutine('SingleActivityComment', array( &$c, TRUE ));
					$tpl->routine->load();
					
					$tpl->display();
					
					return;
				}
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error');
			return;
			
			break;
	
		case 'delete':
			
			if( isset($_POST['activities_id'], $_POST['comments_id'], $_POST['activities_type']) )
			{
				$c	= new postcomment( new post($_POST['activities_type'], $_POST['activities_id']), $_POST['comments_id'] );
				if( $c->error ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error11');
					return;
				}
				if( ! $c->if_can_delete() ) {
					echo 'ERROR:'.$page->lang('global_ajax_post_error13');
					return;
				}
				$c->delete_this_comment();
				echo 'OK';
				return;
			}
			
			echo 'ERROR:'.$page->lang('global_ajax_post_error11');
			return;
			
			break;
	}
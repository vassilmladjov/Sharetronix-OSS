<?php

	function loadSingleActivityComment( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$c = $params[0]; //the resource object from the DB
		$ajax = (isset($params[1]) && $params[1]===TRUE)? TRUE : FALSE;
		$newcomment = (isset($params[2]) && $params[2] == TRUE)? TRUE : FALSE;
		
		if( $ajax ){
			
			$tpl->layout->useBlock('activity-comment');

			$tpl->layout->block->setVar('activity_comment_user_avatar', '<a href="'.userlink($c->comment_user->username).'" class="avatar bizcard"  data-userid="'.$c->comment_user->id.'"><img src="'.getAvatarUrl($c->comment_user->avatar, 'thumbs3').'" alt="'.getThisUserCommunityName($c->comment_user).'" /></a>');
			$tpl->layout->block->setVar('activity_comment_user_username', '<a href="'.userlink($c->comment_user->username).'" class="author bizcard"  data-userid="'.$c->comment_user->id.'">'.getThisUserCommunityName($c->comment_user).'</a>');
			$tpl->layout->block->setVar('activity_comment_options', ($c->if_can_delete()? '<a href="" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'", "comments_id":"'.(isset($c->id)? $c->id : $c->comment_id).'"}').'" data-role="services" data-namespace="comments" data-action="deleteComment" class="delete">'.$page->lang('activity_comment_option_delete').'</a>' : '') );
			
			$tpl->layout->block->setVar('activity_comment_text', $c->parse_text() );
			$tpl->layout->block->setVar('activity_comment_date', post::parse_date($c->comment_date) );
			$tpl->layout->block->setVar('activity_comment_footer', '');
				
			$tpl->layout->block->save( 'main_content', true );
		}else{
	
			$pm->onPostCommentLoad( $c ); //@TODO: event on the ajax will not work
			
			$tpl->layout->useInnerBlock('activity-comment');
			
			$tpl->layout->inner_block->setVar('activity_comment_new', $newcomment? 'new' : '' );
			
			$tpl->layout->inner_block->setVar('activity_comment_user_avatar', '<a href="'.userlink($c->comment_user->username).'" class="avatar bizcard"  data-userid="'.$c->comment_user->id.'"><img src="'.getAvatarUrl($c->comment_user->avatar, 'thumbs3').'" alt="'.getThisUserCommunityName($c->comment_user).'" /></a>');
			$tpl->layout->inner_block->setVar('activity_comment_user_username', '<a href="'.userlink($c->comment_user->username).'" class="author bizcard"  data-userid="'.$c->comment_user->id.'">'.getThisUserCommunityName($c->comment_user).'</a>');
			$tpl->layout->inner_block->setVar('activity_comment_options', ($c->if_can_delete()? '<a href="" data-value="'.htmlentities('{"activities_type":"'.$c->post->post_type.'","activities_id":"'.$c->post->post_id.'", "comments_id":"'.$c->comment_id.'"}').'" data-role="services" data-namespace="comments" data-action="deleteComment" class="delete">'.$page->lang('activity_comment_option_delete').'</a>' : '')); //delete comment
			
			$tpl->layout->inner_block->setVar('activity_comment_text', $c->parse_text() );
			$tpl->layout->inner_block->setVar('activity_comment_date', post::parse_date($c->comment_date) );
			$tpl->layout->inner_block->setVar('activity_comment_footer', '');
				
			$tpl->layout->inner_block->saveInBlockPart( 'activity_comments', true );
			
		}
	}
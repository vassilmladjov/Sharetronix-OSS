<?php
	function loadSingleActivity( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$network 	= & $GLOBALS['network'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$obj = $params[0]; //the resource object from the DB
		$all_comments = isset($params[1]); //show all comments, usefull for View Post
		
		$tpl->layout->useBlock('activity');

		$buff = ( is_object($obj) && get_class($obj) == 'post' )? $obj :  new post($obj->type, FALSE, $obj);
		if( $buff->error ) {
			return;
		}
		
		$pm->onPostLoad( $buff ); //cache results
		
		$onViewPage = $page->request[0] == 'view';
		$comments = (! $onViewPage)? $buff->get_comments() : $buff->get_all_comments();
		$comments_num = count($comments); 
		
		if( !isset($buff->post_user->username) ){
			return;
		}
		
		$tpl->layout->block->setVar('activity_user_avatar', '<a href="'.userlink($buff->post_user->username).'" class="avatar bizcard" data-userid="'.$buff->post_user->id.'"><img src="'.getAvatarUrl($buff->post_user->avatar, 'thumbs1').'" alt="'.getThisUserCommunityName($buff->post_user).'" /></a>');
		$tpl->layout->block->setVar('activity_mobile_user_avatar', '<a href="'.userlink($buff->post_user->username).'" class="avatar bizcard" data-userid="'.$buff->post_user->id.'"><img src="'.getAvatarUrl($buff->post_user->avatar, 'thumbs4').'" alt="'.getThisUserCommunityName($buff->post_user).'" /></a>');
		$tpl->layout->block->setVar('activity_user_avatar_bkg', getAvatarUrl($buff->post_user->avatar, 'thumbs5'));
		$tpl->layout->block->setVar('activity_user_username', '<a href="'.userlink($buff->post_user->username).'" class="author bizcard" data-userid="'.$buff->post_user->id.'">'. getThisUserCommunityName($buff->post_user) .'</a>');
		$tpl->layout->block->setVar('activity_permlink', '<a href="'.$buff->permalink.'" class="permlink">'.post::parse_date($buff->post_date).'</a>');
		if( $buff->post_type == 'public' ){
			$tpl->layout->block->setVar('activity_user_activity_group', ($buff->post_group? $page->lang('postgroup_in').' <a href="'.$buff->post_group->group_link.'">'.$buff->post_group->title.'</a>' : '') );
		}else if( $buff->post_type == 'private' ){
			//@TODO: remove this when we add different private message template
			$tpl->layout->block->setVar('activity_user_activity_group', ((isset($buff->post_to_user->username) && $buff->post_to_user->username!=$user->info->username)? '>> <a href="'.userlink($buff->post_to_user->username).'">'.getThisUserCommunityName($buff->post_to_user).'</a>' : '') );
		}
		$tpl->layout->block->setVar('activity_text', $buff->parse_text());
		
		if( $comments_num === 0 && !$page->is_mobile){ 
			$tpl->layout->block->setVar('activity_nocomments', 'no-comments');
		}
		
		$tpl->layout->block->setVar('activity_options',
				(($user->is_logged && $buff->if_can_delete())? '<a href="" data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="activities" data-action="deleteActivity" class="delete">'.$page->lang('activity_option_delete').'</a>' : '').
				(($user->is_logged && $buff->post_type == 'public')? ('<a href="" data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="activities" data-action="bookmark" class="bookmark '.($buff->is_post_faved()? '' : 'empty').'">'.$page->lang('activity_option_bookmark').' </a>') : '' )
		);		
		
		if( $user->is_logged ){
			
			if( !$page->is_mobile ){
				$tpl->layout->block->setVar('activity_footer','<a href="" class="add-comment action" data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="comments" data-action="activityAddComment">'.$page->lang('activity_comment_txt').'</a>');
				$tpl->layout->block->setVar('comment_editor_user_avatar', '<a href="'.userlink($user->info->username).'" class="avatar"><img src="'.getAvatarUrl($user->info->avatar, 'thumbs3').'" alt="'.$user->info->fullname.'" /></a>');				
			} elseif ($page->is_mobile && $onViewPage) {				
				$tpl->layout->block->setVar('activity_footer','<a href="" class="add-comment action" data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="comments" data-action="activityAddComment">'.($buff->post_commentsnum == 0? ' ' : $buff->post_commentsnum).'</a>');
				$tpl->layout->block->setVar('comment_editor_user_avatar', '<a href="'.userlink($user->info->username).'" class="avatar"><img src="'.getAvatarUrl($user->info->avatar, 'thumbs3').'" alt="'.$user->info->fullname.'" /></a>');
			}else{
				$tpl->layout->block->setVar('comment_editor_user_avatar', '<a href="'.userlink($user->info->username).'" class="avatar"><img src="'.getAvatarUrl($user->info->avatar, 'thumbs3').'" alt="'.$user->info->fullname.'" /></a>');
				$tpl->layout->block->setVar('activity_footer', '<a href="#" class="add-comment action"  data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="comments" data-action="showAll">'.($buff->post_commentsnum == 0? ' ' : $buff->post_commentsnum).'</a>');				
			}
		}

		if( count( $buff->post_attached ) > 0 ){ 
			$tpl->layout->useInnerBlock('activity-attachments'); 
			
			if( isset($buff->post_attached['file']) ){
				foreach($buff->post_attached['file'] as $k => $file){
					$tpl->layout->inner_block->setVar('activity_attachments_files', '<a class="icon file '.(isset($file->filetype)? $file->filetype : '').'" href="'.$C->SITE_URL.'getfile/pid:'.$buff->post_tmp_id.'/attid:'.intval($k).'" title="'.$file->title.'">'.$file->title.'</a><span class="clear-right"></span>');
				}
			}
			
			if( isset($buff->post_attached['image']) ){
				foreach($buff->post_attached['image'] as $image){
					$tpl->layout->inner_block->setVar('activity_attachment_images', '<a target="_blank" href="'.($C->STORAGE_URL.'attachments/'.$network->id.'/'.$image->file_preview).'" class="lightbox-image image-thumb cboxElement"><img alt="Image" src="'.$C->STORAGE_URL.'attachments/'.$network->id.'/'.($image->file_thumbnail).'" /></a>');
					$tpl->layout->inner_block->setVar('activity_attachment_images_preview', '<a target="_blank" href="'.($C->STORAGE_URL.'attachments/'.$network->id.'/'.$image->file_preview).'" class="lightbox-image image-thumb cboxElement"><img alt="Image" src="'.$C->STORAGE_URL.'attachments/'.$network->id.'/'.($image->file_preview).'" /></a>');
				}
			}

			$tpl->layout->inner_block->saveInBlockPart( 'activity_attachments' );
			
			if( isset($buff->post_attached['link']) ){
				foreach($buff->post_attached['link'] as $link){
					$tpl->layout->useInnerBlock('activity-attachments-link');
					$tpl->layout->inner_block->setVar('activity_attachments_link', urldecode($link->link)); 
					$tpl->layout->inner_block->setVar('activity_attachments_link_title', isset($link->title)? $link->title : $link->link);
					$tpl->layout->inner_block->setVar('activity_attachments_link_description', isset($link->description)? $link->description : '');
					
					$tpl->layout->inner_block->saveInBlockPart( 'activity_attachments_links' );
				}
			}
			
			if( isset($buff->post_attached['videoembed']) ){
				foreach($buff->post_attached['videoembed'] as $vid){

					$mobile_embed = str_replace('&autoplay=1','',$vid->embed_code);
					$mobile_embed = str_replace('width="460"','width="320"',$mobile_embed);
					$mobile_embed = str_replace('height="288"','height="180"',$mobile_embed);
					
					$tpl->layout->useInnerBlock('activity-attachments-videoembed');
					$tpl->layout->inner_block->setVar('activity_videoembed_html', htmlspecialchars($vid->embed_code));
					$tpl->layout->inner_block->setVar('activity_videoembed_mobile', $mobile_embed);
					
					
					
					$tpl->layout->inner_block->setVar('activity_videoembed_img_link', $C->STORAGE_URL.'attachments/1/'.$vid->file_thumbnail );
					$tpl->layout->inner_block->setVar('activity_videoembed_img_origin_link', $C->STORAGE_URL.'attachments/1/'.  str_replace('thumb.gif', "origin.jpg", $vid->file_thumbnail) );
					$tpl->layout->inner_block->setVar('activity_videoembed_link_href', $vid->orig_url);
					$tpl->layout->inner_block->setVar('activity_videoembed_link_title', isset($vid->title)? $vid->title : '');
					$tpl->layout->inner_block->setVar('activity_videoembed_link_description', isset($vid->description)? $vid->description : '');
					
						
					$tpl->layout->inner_block->saveInBlockPart( 'activity_attachments_links' );
				}
			}
		}

		$tpl->layout->block->setVar('comments_thread_id', htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}'));

		if( $comments_num > 0 && (!$page->is_mobile || $onViewPage) ){
			
			$tpl->layout->useInnerBlock('activity-comments-container');

			if( ( $buff->post_commentsnum > $C->POST_LAST_COMMENTS ) && !$onViewPage ){
				$tpl->layout->inner_block->setVar('show_all_activity_comments', '<a href="'.$buff->permalink.'" class="show-all-comments"  data-value="'.htmlentities('{"activities_type":"'.$buff->post_type.'","activities_id":"'.$buff->post_id.'"}').'" data-role="services" data-namespace="comments" data-action="showAll"><span>'.$page->lang('activity_option_show_all_comments', array('#NUM_COMMENTS#'=>$buff->post_commentsnum)).'</span></a>');
			}
				
			$tpl->layout->inner_block->saveInBlockPart( 'activity_comments_container' );
			
			$newcomments = explode(',', $buff->newcomments);
			$new = FALSE;
			foreach( $comments as $c ){
				if( $page->param('tab') == 'commented' && in_array($c->comment_id, $newcomments) ){
					$new = TRUE;
				}
				$tpl->initRoutine('SingleActivityComment', array( & $c, FALSE, $new ));
				$tpl->routine->load();
			}		
		}elseif( !$comments_num && $page->is_mobile && $onViewPage ) {
			$tpl->layout->useInnerBlock('activity-comments-container');
			$tpl->layout->inner_block->saveInBlockPart( 'activity_comments_container' );
		} elseif( $page->is_mobile && !$onViewPage ) {
			$tpl->layout->block->setVar('activity_nocomments', 'no-comments');
			$tpl->layout->useInnerBlock('activity-comments-container');
			$tpl->layout->inner_block->saveInBlockPart( 'activity_comments_container' );
		}
		
		$tpl->layout->block->save( 'activity-container-list', true ); 
	}
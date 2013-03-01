<?php 
	function loadDefaultFooter( $tpl, $params )
	{ 
		global $C;
	
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$page->load_langfile('outside/footer.php');
		$page->load_langfile('inside/footer.php');
		
		$html = ' &middot;<a href="'. $C->SITE_URL .'invite">'. $page->lang('os_ftrlinks_sf_invitemail'). '</a> ';
		$html .= ' &middot;<a href="'. $C->SITE_URL .'m">'. $page->lang('footer_mobile_version') .'</a> ';
		$html .= ' &middot;<a href="'. $C->SITE_URL .'contacts">'. $page->lang('ftr_contacts') .'</a> ';
		
		/*if( $user->is_logged ) {
			$html .= ' &middot;<a href="'. $C->SITE_URL .'api">'. $page->lang('ftr_api') .'</a> ';
		}*/
		
		$tpl->layout->setVar('stx_footer_link_abc', 'Powered by <a href="http://sharetronix.com" target="_blank">Sharetronix</a>');
		
		$tpl->layout->setVar( 'footer_placeholder', $html );
		
		if( FALSE === ($tmp = getCachedHTML('footer_data_default')) ){
			$tmp = $tpl->designer->getJSData();
			setCachedHTML('footer_data_default', $tmp);
		}
		
		$tpl->layout->setVar( 'footer_js_data', $tmp );
		
		$tpl->layout->useBlock( 'comment-editor' );
		
		if( $user->is_logged ){ 
			$tpl->layout->block->setVar('comment_editor_user_avatar', '<a href="'.userlink($user->info->username).'" class="avatar"><img src="'.getAvatarUrl($user->info->avatar, 'thumbs3').'" alt="'.$user->info->fullname.'" /></a>');
			$tpl->layout->useInnerBlock('editor-textarea');
			$tpl->layout->inner_block->saveInBlockPart('editor_textarea');
		}
		
		$tpl->layout->block->save('comment_editor');
	}
?>
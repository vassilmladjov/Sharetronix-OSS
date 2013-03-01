<?php

	function loadSendmessage( $tpl, $params )
	{
		$page = & $GLOBALS['page'];
		$pm = & $GLOBALS['plugins_manager'];	
		
		$save_placeholder = isset($params[0])? $params[0] : 'main_content';
		
		$tpl->layout->useBlock('activity-editor');
		
		$tpl->layout->useInnerBlock('editor-textarea');
		$tpl->layout->inner_block->saveInBlockPart('editor_textarea');
		

		$data_value = ($page->params->group)? '{"activities_type": "group", "activities_group":"'.$page->params->group.'"}' : '{"activities_type": "dashboard"}';

		$tpl->layout->block->setVar('editor_btn_placeholder', '<a class="status-btn post-btn btn blue" data-value="'.htmlentities($data_value).'" data-role="services" data-namespace="users" data-action="sendMessage"><span>'.$page->lang('global_send_msg_btn_txt').'</span></a>');
		
		$tpl->layout->block->save( $save_placeholder );
		
		if( !$page->is_mobile ){
			$tpl->layout->useBlock('activity-container');	
			$tpl->layout->block->save( 'main_content' );
		}
	}
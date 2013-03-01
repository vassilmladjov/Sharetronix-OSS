<?php
	function loadInviteTopMenu( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$tab = isset($page->request[1])? $page->request[1] : '';
		
		$menu = array( 	array('url' => 'invite', 				'css_class' => (($tab === '')? 'active' : ''), 				'title' => $page->lang('os_invite_tab_colleagues') ),
						//array('url' => 'invite/parsemail', 	'css_class' => (($tab === 'parsemail')? 'active' : ''), 	'title' => $page->lang('os_invite_tab_parsemail') ),
						//array('url' => 'invite/uploadcsv', 	'css_class' => (($tab === 'uploadcsv')? 'active' : ''), 	'title' => $page->lang('os_invite_tab_uploadcsv') ),
						array('url' => 'invite/sentinvites',	'css_class' => (($tab === 'sentinvites')? 'active' : ''), 	'title' => $page->lang('os_invite_tab_sentinvites') )
		);
		
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createInfoBlock('Invite', $tpl->designer->createMenu('tabs-navigation', $menu)) );
		
	}
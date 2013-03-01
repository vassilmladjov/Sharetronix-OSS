<?php
	function loadSettingsLeftMenu( $tpl, $params )
	{
		global $C, $D;
		$page 	= & $GLOBALS['page'];
		$user 	= & $GLOBALS['user'];
		$pm 	= & $GLOBALS['plugins_manager'];
		
		$tab = isset($page->request[1])? $page->request[1] : '';
		
		$menu = array( 	array('url' => 'settings/profile', 		'css_class' => (($tab === 'profile')? ' selected' : ''), 		'title' => $page->lang('settings_menu_profile')),
						array('url' => 'settings/contacts', 	'css_class' => (($tab === 'contacts')? ' selected' : ''), 		'title' => $page->lang('settings_menu_contacts') ),
						array('url' => 'settings/avatar', 		'css_class' => (($tab === 'avatar')? ' selected' : ''), 		'title' => $page->lang('settings_menu_avatar') ),
						array('url' => 'settings/email', 		'css_class' => (($tab === 'email')? ' selected' : ''), 			'title' => $page->lang('settings_menu_email') ),
						array('url' => 'settings/password', 	'css_class' => (($tab === 'password')? ' selected' : ''), 		'title' => $page->lang('settings_menu_password') ),
						array('url' => 'settings/system', 		'css_class' => (($tab === 'system')? ' selected' : ''), 		'title' => $page->lang('settings_menu_system') ),
						array('url' => 'settings/deleteuser', 	'css_class' => (($tab === 'deleteuser')? ' selected' : ''), 	'title' => $page->lang('settings_menu_delaccount') ),
						array('url' => 'settings/notifications','css_class' => (($tab === 'notifications')? ' selected' : ''), 	'title' => $page->lang('settings_menu_notif') ),
						array('url' => 'settings/privacy', 		'css_class' => (($tab === 'privacy')? ' selected' : ''), 		'title' => $page->lang('settings_menu_privacy') )
		);
		$tpl->layout->setVar( 'left_content_placeholder', $tpl->designer->createInfoBlock('Settings Menu', $tpl->designer->createMenu('feed-navigation', $menu)) );
		
	}
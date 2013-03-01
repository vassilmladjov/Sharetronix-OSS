<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	require_once( $C->INCPATH.'helpers/func_languages.php' );
	
	$menu_timezones	= array();
	if( floatval(substr(phpversion(),0,3)) >= 5.2 ) {
		$tmp	= array();
		foreach(DateTimeZone::listIdentifiers() as $v) {
			if( substr($v, 0, 4) == 'Etc/' ) { continue; }
			if( FALSE === strpos($v, '/') ) { continue; }
			$sdf	= new DateTimeZone($v);
			if( ! $sdf ) { continue; }
			$tmp[$v]	= $sdf->getOffset( new DateTime("now", $sdf) );
		}
		asort($tmp);
		foreach($tmp as $k=>$v) {
			$menu_timezones[$k]	= str_replace(array('/','_'), array(' / ',' '), $k);
		}
		asort($menu_timezones);
	}
	
	$language		= $C->LANGUAGE;
	$menu_languages	= array();
	foreach(get_available_languages(FALSE) as $k=>$v) {
		$menu_languages[$k]	= $v->name;
	}
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	
	$timezone	= $C->DEF_TIMEZONE;
	if( ! empty($this->user->info->timezone) ) {
		$timezone	= $this->user->info->timezone;
	}

	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			if( isset($_POST['timezone']) && isset($menu_timezones[$_POST['timezone']]) ) {
				$timezone	= $_POST['timezone'];
			}
			$language	= $C->LANGUAGE;
			if( isset($_POST['language']) && isset($menu_languages[$_POST['language']]) ) {
				$language	= $_POST['language'];
			}
			$db2->query('UPDATE users SET language="'.$db2->e($language).'", timezone="'.$db2->e($timezone).'" WHERE id="'.$this->user->id.'" LIMIT 1');
			
			$this->user->sess['LOGGED_USER']	= $this->network->get_user_by_id($this->user->id, TRUE);
			$this->user->info	= & $this->user->sess['LOGGED_USER'];
			date_default_timezone_set($timezone);
			
			$this->redirect('settings/system/msg:ok');
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('settings_system_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if($this->param('msg') == 'ok'){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_system_ok'), $this->lang('st_system_okmsg') ) );
	}else if($this->param('msg') == 'error'){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_email_current_errttl'), $errmsg ) );
	}
	
	$tpl->layout->useBlock('empty');
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_system_ttl2');
	
	$rows = array(
			$table->selectField( $this->lang('st_system_language'), 'language', $menu_languages, $language ),
			$table->selectField( $this->lang('st_system_timezone'), 'timezone', $menu_timezones, $timezone ),
			$table->submitButton( 'sbm', $this->lang('st_system_savebtn') )
	);
	
	$tpl->layout->block->setVar('empty_block_content', $table->createTableInput( $rows ));
	
	$tpl->layout->block->save('main_content');
	
	$tpl->display();
?>
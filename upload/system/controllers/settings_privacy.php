<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	$submit	= FALSE;
	$error = FALSE;
	
	if( isset($_POST['privacy_profile']) || isset($_POST['privacy_posts']) || isset($_POST['privacy_dm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
		
			$protect_profile = (is_numeric($_POST['privacy_profile']))? trim($_POST['privacy_profile']) : 0;
			$protect_posts = (is_numeric($_POST['privacy_posts']))? trim($_POST['privacy_posts']) : 0;
			$protect_dm = (is_numeric($_POST['privacy_dm']))? trim($_POST['privacy_dm']) : 0;
			
			$db2->query('UPDATE users SET is_profile_protected="'.$db2->e($protect_profile).'", is_posts_protected="'.$db2->e($protect_posts).'", is_dm_protected="'.$db2->e($protect_dm).'" WHERE id="'.$this->user->id.'"');
			$this->network->get_user_by_id($this->user->id);
		
			$this->network->get_user_by_id($this->user->id, TRUE);
			$this->network->get_post_protected_user_ids(TRUE);
			$this->user->get_my_post_protected_follower_ids(TRUE);
			
		}
	}
	
	$privacy_settings = $db2->query('SELECT is_profile_protected, is_posts_protected, is_dm_protected FROM users WHERE id="'.$this->user->id.'"');
	$privacy_settings = $db2->fetch_object($privacy_settings);
	
	$profile_protect = $privacy_settings->is_profile_protected;
	$protect_posts = $privacy_settings->is_posts_protected;
	$protect_dm= $privacy_settings->is_dm_protected;
	
	
	$tpl = new template( array('page_title' => $this->lang('settings_privacy_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && !$error){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_system_ok'), $this->lang('st_profile_okmsg') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_avatat_err'), $errmsg ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_system_ttl2');
	
	$rows = array(
			$table->radioButton( $this->lang('settings_opt_prof_name'), 'privacy_profile', array('1'=>$this->lang('settings_opt_enabled'), '0'=>$this->lang('settings_opt_disabled')), $profile_protect ),
			$table->radioButton( $this->lang('settings_opt_posts_name'), 'privacy_posts', array('1'=>$this->lang('settings_opt_enabled'), '0'=>$this->lang('settings_opt_disabled')), $protect_posts ),
			$table->radioButton( $this->lang('settings_opt_dm_name'), 'privacy_dm', array('1'=>$this->lang('settings_opt_enabled'), '0'=>$this->lang('settings_opt_disabled')), $protect_dm ),
			$table->submitButton( 'submit', $this->lang('st_system_savebtn') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>
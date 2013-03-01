<?php

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}

	$this->load_langfile('inside/global.php');	
	$this->load_langfile('inside/settings.php');
	
	global $plugins_manager;
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	if( isset($_POST['userpass']) ) 
	{
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$this->user->checkPassByUserId(md5($_POST['userpass'])) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('st_delaccount_pass_err');
		}
		
		if( ! $error ){
			$admin = new communityAdministration();
			if( $admin->deleteUser($this->user->id, md5($_POST['userpass'])) ){
				$this->user->logout();
				$this->redirect( $C->SITE_URL );
			}else{
				$error = TRUE;
				$errmsg = $this->lang('st_delaccount_admins_err');
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('settings_delaccount_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_delaccount_error'), $errmsg) );
	}
	$tpl->layout->setVar('main_content_placeholder', $tpl->designer->informationMessage($this->lang('settings_warning_message'), $this->lang('st_delaccount_description')) );
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_delaccount_ttl2');
	
	$rows = array(
			$table->passField( $this->lang('st_delaccount_password'), 'userpass', '' ),
			$table->submitButton( 'sbm', $this->lang('st_delaccount_submit') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
	
?>
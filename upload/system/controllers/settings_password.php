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
	$error	= FALSE;
	$errmsg	= '';
	$pass_old	= '';
	$pass_new	= '';
	$pass_new2	= '';
	
	if( isset($_POST['pass_old'], $_POST['pass_new'], $_POST['pass_new2']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$pass_old	= trim($_POST['pass_old']);
			$pass_new	= trim($_POST['pass_new']);
			$pass_new2	= trim($_POST['pass_new2']);
			if( empty($pass_old) || md5($pass_old)!=$db2->fetch_field('SELECT password FROM users WHERE id="'.$this->user->id.'" LIMIT 1') ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_password_err_current');
			}
			elseif( mb_strlen($pass_new)<5 ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_password_err_newshort');
			}
			elseif( $pass_new != $pass_new2 ) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_password_err_missmatch');
			}
			else {
				$pass	= md5($pass_new);
				$db2->query('UPDATE users SET password="'.$db2->e($pass).'" WHERE id="'.$this->user->id.'" LIMIT 1');
				$this->user->info->password	= $pass;
				$this->network->get_user_by_id($this->user->id, TRUE);
				$pass_old	= '';
				$pass_new	= '';
				$pass_new2	= '';
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('settings_password_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_password_err'), $errmsg) );
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_password_ok'), $this->lang('st_password_okmsg') ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_password_ttl2');
	
	$rows = array(
			$table->passField( $this->lang('st_password_current'), 'pass_old', '' ),
			$table->passField( $this->lang('st_password_newpass'), 'pass_new', '' ),
			$table->passField( $this->lang('st_password_newconfirm'), 'pass_new2', '' ),
			$table->submitButton( 'submit', $this->lang('st_password_changebtn') )
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>
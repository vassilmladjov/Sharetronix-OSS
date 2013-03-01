<?php

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	$this->load_langfile('email/changeemail.php');	
	
	global $plugins_manager;
	
	$submit			= FALSE;
	$error			= FALSE;
	$notif			= FALSE;
	$new_email_active 	= FALSE;
	$errmsg			= '';
	
	$new_email		= '';
	$new_email_confirm	= '';
	$user_pass		= '';
	
	if($this->param('reqid') && $this->param('reqkey'))
	{
		$submit	= TRUE;
		
		if(! intval($this->param('reqid')))
		{
			$error = TRUE;
			$errmsg = $this->lang('st_email_wrong_link');
		}
		else {
			$db2->query('SELECT * FROM email_change_requests WHERE id="'.intval($this->param('reqid')).'" LIMIT 1');
			if(! $obj = $db2->fetch_object() ) 
			{
				$error	= TRUE;
				$errmsg	= $this->lang('st_email_wrong_link');
			}
			else 
			{
				$new_email	= $obj->new_email;
				if($obj->confirm_key != trim($this->param('reqkey')))
				{	
					$error = TRUE;
					$errmsg = $this->lang('st_email_wrong_conf_id');
		
				}else if($obj->confirm_valid < time())
				{
					$error = TRUE;
					$errmsg = $this->lang('st_email_wrong_time');
				}
				
				if( !$error ){
					$db2->query('SELECT id, active FROM users WHERE email="'.$db2->e($new_email).'" LIMIT 1');
					if($obj = $db2->fetch_object()) {
						$error	= TRUE;
						$errmsg	= $this->lang('st_email_name_repeat', array('#SITE_TITLE#' => $C->SITE_TITLE));
					}
				}

				if(!$error)
				{
					$db2->query('UPDATE users SET email="'.$db2->e($new_email).'" WHERE id="'.$this->user->id.'" LIMIT 1');
					$this->network->get_user_by_id($this->user->id, TRUE);
					$this->user->info->email	= $new_email;
					$db2->query('DELETE FROM email_change_requests WHERE id="'.intval($this->param('reqid')).'" LIMIT 1');
					$new_email		= '';
					$new_email_confirm	= '';
					$user_pass		= '';
					$new_email_active = TRUE;
				}
			}
		}

	}elseif( isset($_POST['new_email'], $_POST['new_email_confirm'], $_POST['user_pass']) ) 
	{
		$submit			= TRUE;
		$new_email		= mb_strtolower(trim($_POST['new_email']));
		$new_email_confirm	= mb_strtolower(trim($_POST['new_email_confirm']));
		$user_pass		= trim($_POST['user_pass']);
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if(!is_valid_email($new_email) || !is_valid_email($new_email_confirm))
		{
			$error = TRUE;
			$errmsg = $this->lang('st_email_address_error');
		}
		elseif($new_email != $new_email_confirm)
		{
			$error = TRUE;
			$errmsg = $this->lang('st_email_current_error');
		}
		elseif(empty($user_pass) || (md5($user_pass) != $this->user->info->password))
		{
			$error = TRUE;
			$errmsg = $this->lang('st_email_pass_error');
		}
		
		if( !$error ){
			$db2->query('SELECT id, active FROM users WHERE email="'.$db2->e($new_email).'" LIMIT 1');
			if($obj = $db2->fetch_object()) {
				$error	= TRUE;
				$errmsg	= $this->lang('st_email_name_repeat', array('#SITE_TITLE#' => $C->SITE_TITLE));
			}
		}
		
		
		if( !$error )
		{		
			if($C->USERS_EMAIL_CONFIRMATION)
			{
				$confirmation_key	= md5(rand().time().rand());
				$db2->query('INSERT INTO email_change_requests(user_id, new_email, confirm_key, confirm_valid) VALUES("'.$this->user->id.'", "'.$db2->e($new_email).'", "'.$confirmation_key.'", "'.(time() + (7 * 24 * 60 * 60)).'")');
				$confirmation_link	= $C->SITE_URL.'settings/email/reqid:'.$db2->insert_id().'/reqkey:'.$confirmation_key;
				$D->confirmation_link	= $C->SITE_URL.'settings/email/reqid:'.$db2->insert_id().'/reqkey:'.$confirmation_key;
					
				$subject	= $this->lang('prof_changemail_subject', array('#SITE_TITLE#'=>$C->SITE_TITLE));
				$msgtxt		= $this->load_single_block('email/changeemail_txt.php', FALSE, TRUE);
				$msghtml	= $this->load_single_block('email/changeemail_html.php', FALSE, TRUE);
				do_send_mail_html($new_email, $subject, $msgtxt, $msghtml);
						
				$error 	= TRUE;
				$confirm = TRUE;
				$notif	= TRUE;
				$errmsg = $this->lang('st_email_notif_send', array('#EMAIL#'=>$new_email));
			}else
			{
				$db2->query('UPDATE users SET email="'.$db2->e($new_email).'" WHERE id="'.$this->user->id.'" LIMIT 1');
				$this->network->get_user_by_id($this->user->id, TRUE);
				$this->user->info->email	= $new_email;
				$new_email		= '';
				$new_email_confirm	= '';
				$user_pass		= '';
				$new_email_active = TRUE;
			}
		}
	}	
	
	
	$tpl = new template( array('page_title' => $this->lang('settings_email_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		if(isset($confirm) && $confirm){
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_email_okttl'), $errmsg) );
		}else {
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_email_current_errttl'), $errmsg) );
		}
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_email_okttl'), $this->lang('st_email_oktxt') ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_email_ttl2');
	
	$rows = array(
			$table->inputField($this->lang('st_email_new'), 'new_email', htmlspecialchars($new_email) ),
			$table->inputField($this->lang('st_email_new_confirm'), 'new_email_confirm', htmlspecialchars($new_email_confirm) ),
			$table->passField($this->lang('st_email_pass'), 'user_pass'),
			$table->submitButton('submit', $this->lang('st_email_cng_btn'))	
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
	
?>
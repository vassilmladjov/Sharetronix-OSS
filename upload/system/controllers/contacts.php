<?php
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('outside/contacts.php');
	
	require_once( $C->INCPATH.'helpers/func_captcha.php' );

	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	
	$fullname	= '';
	$email		= '';
	$message		= '';
	if( $this->user->is_logged ) {
		$fullname	= $this->user->info->fullname;
		$email		= $this->user->info->email;
	}
	
	$captcha_key	= '';
	$captcha_word	= '';
	$captcha_html	= '';
	list($captcha_word, $captcha_html)	= generate_captcha(5);
	$captcha_key	= md5($captcha_word.time().rand());
	$_SESSION['captcha_'.$captcha_key]	= $captcha_word;
	
	if( isset($_POST['sbm']) ) {
		
		global $plugins_manager;
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		$submit	= TRUE;
		$fullname	= isset($_POST['fullname'])? trim($_POST['fullname']) : '';
		$email		= isset($_POST['email'])? trim($_POST['email']) : '';
		$message	= isset($_POST['message'])? trim($_POST['message']) : '';
		if( empty($fullname) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('cntf_err_fullname');
		}
		elseif( empty($email) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('cntf_err_email1');
		}
		elseif( ! is_valid_email($email) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('cntf_err_email2');
		}
		elseif( empty($message) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('cntf_err_message');
		}
		elseif( !isset($_POST['captcha_key'],$_POST['captcha_word']) || !isset($_SESSION['captcha_'.$_POST['captcha_key']]) || $_SESSION['captcha_'.$_POST['captcha_key']]!=strtolower($_POST['captcha_word']) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('cntf_err_captcha');
		}
		else {
			$sender	= $fullname.' <'.$email.'>';
			$recipient	= $C->SYSTEM_EMAIL;
			$subject	= $C->OUTSIDE_SITE_TITLE.' - '.$this->lang('cnt_frm_sbj');
			$message	= $message;
			
			do_send_mail($recipient, $subject, $message, $sender);
			
			$fullname	= '';
			$email		= '';
			$subject		= '';
			$message		= '';
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('contacts_pgtitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	if( $submit && $error){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('cntf_error'), $errmsg) );
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('cntf_ok_ttl'), $this->lang('cntf_ok_txt') ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('contacts_left_ttl');
	
	$rows = array(
					$table->inputField( $this->lang('cnt_frm_fullname'), 'fullname', (isset($fullname)? $fullname : '') ),
					$table->inputField( $this->lang('cnt_frm_email'), 'email', (isset($email)? $email : '') ),
					$table->textArea( $this->lang('cnt_frm_message'), 'message', (isset($message)? $message : '') ),
					$table->textField( $this->lang('cnt_frm_captcha'), $captcha_html ),
					$table->inputField( '', 'captcha_word', ''),
					$table->hiddenField('captcha_key', $captcha_key ),
					$table->submitButton( 'sbm', $this->lang('cnt_frm_sbm') )
				);
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ) );
	
	$tpl->display();
	
	cleanup_captcha_files();
	
?>
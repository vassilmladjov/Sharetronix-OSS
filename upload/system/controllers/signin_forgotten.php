<?php
	
	if( $this->network->id && $this->user->is_logged ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('outside/signin.php');
	$this->load_langfile('outside/global.php');
	$this->load_langfile('outside/signup.php');
	
	require_once( $C->INCPATH.'helpers/func_images.php' );
	require_once( $C->INCPATH.'helpers/func_captcha.php' );
	require_once( $C->INCPATH.'helpers/func_recaptchalib.php');
	
	$page_title	= $this->lang('signinforg_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	
	$have_key	= FALSE;
	
	if( $this->param('key') )
	{
		$have_key	= TRUE;
		$error_key	= FALSE;
		
		$key	= $this->db2->e(trim($this->param('key')));
		$this->db2->query('SELECT id FROM users WHERE active=1 AND pass_reset_key="'.$key.'" AND pass_reset_valid>="'.time().'" LIMIT 1');
		if( ! $u = $this->db2->fetch_object() ) {
			$error_key	= TRUE;
		}
		else {
			$submit	= FALSE;
			$error	= FALSE;
			$errmsg	= '';
			
			if( isset($_POST['pass1'], $_POST['pass2']) ) {
				$submit = TRUE;
				
				$pass	= trim($_POST['pass1']);
				if( strlen($pass)<5 ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_passwdlen';
				}
				elseif( $pass != trim($_POST['pass2']) ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_passdiff';
				}
				else {
					$pass	= md5($pass);
					$this->db2->query('UPDATE users SET password="'.$this->db2->e($pass).'", pass_reset_key="", pass_reset_valid="" WHERE id="'.$u->id.'" LIMIT 1');
					$u	= $this->network->get_user_by_id($u->id, TRUE);
					$this->redirect('signin/pass:changed');
				}
			}
		}
	}
	else
	{
		$submit	= FALSE;
		$error	= FALSE;
		$errmsg	= '';
		$email	= '';
		
		if( !isset($C->GOOGLE_CAPTCHA_PRIVATE_KEY, $C->GOOGLE_CAPTCHA_PUBLIC_KEY) || $C->GOOGLE_CAPTCHA_PRIVATE_KEY == '' || $C->GOOGLE_CAPTCHA_PUBLIC_KEY == '' ){
			$captcha_key	= '';
			$captcha_word	= '';
			$captcha_html	= '';
			list($captcha_word, $captcha_html)	= generate_captcha(5);
			$captcha_key	= md5($captcha_word.time().rand());
			$_SESSION['captcha_'.$captcha_key]	= $captcha_word;
			$D->use_google_recaptcha = FALSE;
		}else{
			$captcha_html	=	recaptcha_get_html($C->GOOGLE_CAPTCHA_PUBLIC_KEY);
			$captcha_key	=	$C->GOOGLE_CAPTCHA_PUBLIC_KEY;
			$D->use_google_recaptcha = TRUE;
		}
		
		if( isset($_POST['email']) ) {
			$submit	= TRUE;
			$email	= strtolower(trim($_POST['email']));
			if( ! is_valid_email($email) ) {
				$error	= TRUE;
				$errmsg	= 'signinforg_err_email';
			}
			$u	= FALSE;
			if( ! $error ) {
				$this->db2->query('SELECT id, active FROM users WHERE email="'.$this->db2->e($email).'" LIMIT 1');
				if( ! $u = $this->db2->fetch_object() ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_email2';
				}
				elseif( $u->active == "0" ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_banned';
				}
				elseif( ! $u = $this->network->get_user_by_id($u->id) ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_email2';
				}
			}
			
			if( !$error ) {
				if( isset($C->GOOGLE_CAPTCHA_PRIVATE_KEY, $C->GOOGLE_CAPTCHA_PUBLIC_KEY) && ($C->GOOGLE_CAPTCHA_PRIVATE_KEY == '' || $C->GOOGLE_CAPTCHA_PUBLIC_KEY == '') ){
					if( !isset($_POST['captcha_key'],$_POST['captcha_word']) || !isset($_SESSION['captcha_'.$_POST['captcha_key']]) || $_SESSION['captcha_'.$_POST['captcha_key']]!=strtolower($_POST['captcha_word']) ) {
						$error	= TRUE;
						$errmsg	= 'signup_err_captcha';
						$wrong_captcha = TRUE;
					}
				}else{
					$check = recaptcha_check_answer ( $C->GOOGLE_CAPTCHA_PRIVATE_KEY,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
					if (!$check->is_valid){
						$error	= TRUE;
						$errmsg	= 'signup_err_captcha';
						$wrong_captcha = TRUE;
					}
				}
			}
			
			if( ! $error ) {
				if( $this->user->is_logged ) {
					$this->user->logout();
				}
				$key		= md5('akey_'.$u->id.'_'.(rand().time().rand()));
				$valid	= time() + 48*60*60;
				$this->db2->query('UPDATE users SET pass_reset_key="'.$key.'", pass_reset_valid="'.$valid.'" WHERE id="'.$u->id.'" LIMIT 1');
				$D->recover_link	= $C->SITE_URL.'signin/forgotten/key:'.$key;
				$this->load_langfile('email/signin.php');
				$subject	= $this->lang('signinforg_email_subject', array('#SITE_TITLE#'=>$C->SITE_TITLE));
				$msgtxt	= $this->load_single_block('email/signinforg_txt', FALSE);
				$msghtml	= $this->load_single_block('email/signinforg_html', FALSE);
				do_send_mail_html($email, $subject, $msgtxt, $msghtml);
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('signinforg_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$show_step1_form = TRUE;
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('signinforg_err'), $this->lang($errmsg) ) );
	}else if( $submit && !$error ){
		if( $have_key ){
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signinforg_alldone_ttl'), $this->lang('signinforg_alldone_txt') ) );
		}else{
			$show_step1_form = FALSE;
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signinforg_sentmail_ttl'), $this->lang('signinforg_sentmail_txt', array('#EMAIL#'=>$email)) ) );
		}
	}

	if( !$have_key ){
		
		if( $show_step1_form ){
			$tpl->layout->useBlock('user-forgotten-pass-step1');
			$tpl->layout->block->setVar('user_forgotten_email_value', isset($email)? htmlspecialchars($email) : '');
			$tpl->layout->block->setVar('captcha_image', $captcha_html);
			$tpl->layout->block->setVar('captcha_key', $captcha_key);
				
			$tpl->layout->block->setVar('autofocus','');
			if(isset($wrong_captcha)){
				$tpl->layout->block->setVar('autofocus','data-status="focus"');
			}
			
			$tpl->layout->block->save('main_content');
		}
	
	}else{
		$tpl->layout->useBlock('user-forgotten-pass-step2');

		$tpl->layout->block->save('main_content');
	}
	
	$tpl->display();
?>
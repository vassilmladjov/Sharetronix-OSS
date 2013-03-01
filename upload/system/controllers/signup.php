<?php
	
	if( $this->user->is_logged ) {
		$this->redirect('home');
	}

	if( FALSE !== strpos($_SERVER['REQUEST_URI'], '%3a') ){
		$_SERVER['REQUEST_URI'] = str_replace('%3a', ':', $_SERVER['REQUEST_URI']);
		$this->redirect('http://'.$C->DOMAIN.$_SERVER['REQUEST_URI']);
	}
	
	$this->load_langfile('outside/global.php');
	$this->load_langfile('outside/signup.php');
	$this->load_langfile('email/signup.php');
	$this->load_langfile('outside/home.php');
	
	//$network_members	= $db2->fetch_field('SELECT COUNT(*) FROM users WHERE active=1');
	
	require_once( $C->INCPATH.'helpers/func_signup.php' );
	require_once( $C->INCPATH.'helpers/func_images.php' );
	require_once( $C->INCPATH.'helpers/func_captcha.php' );
	require_once( $C->INCPATH.'helpers/func_api.php' );
	require_once( $C->INCPATH.'helpers/func_recaptchalib.php');
	
	$terms_of_use	= FALSE;
	if( isset($C->TERMSPAGE_ENABLED,$C->TERMSPAGE_CONTENT) && $C->TERMSPAGE_ENABLED==1 && !empty($C->TERMSPAGE_CONTENT) ) {
		$terms_of_use	= TRUE;
	}
	
	$reg_id = $this->param('regid');
	$reg_key = $this->param('regkey');

	$error = FALSE;
	$submit = FALSE;
	$errmsg = '';
	$is_email_confirmed = $C->USERS_EMAIL_CONFIRMATION? isEmailConfirmed($reg_id, $reg_key) : FALSE;
	$place_facebook_get_data_tab = FALSE;
	
	if( check_if_use_facebook( $is_email_confirmed ) ){
		$place_facebook_get_data_tab = TRUE;
	}elseif( check_if_use_twitter( $is_email_confirmed ) ){
		$twitt = new twitterAuth();
		$twitt->getRequestToken();
	}
	
	check_if_facebook_called(); //check if Facebook has sent some data to us for registration
	check_if_twitter_called(); //check if Twitter has sent some data to us for registration
	
	$email 		= isset($_POST['email'])?( strtolower(trim($_POST['email'])) ):'';
	$fullname 	= isset($_POST['fullname'])?(trim($_POST['fullname'])):'';
	$username 	= isset($_POST['username'])?(trim($_POST['username'])):'';
	$password2	= isset($_POST['password2'])?(trim($_POST['password2'])):'';
	$password 	= isset($_POST['password'])?(trim($_POST['password'])):'';
	$accept_terms	= isset($_POST['accept_terms'])&&$_POST['accept_terms']==1;
	
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
	
	if( isset($_POST['submit']) )
	{
		global $plugins_manager;
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		$submit = TRUE;

		if( !$is_email_confirmed && $C->USERS_EMAIL_CONFIRMATION ){
			
			if( ! is_valid_email($email) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_email_invalid');
			}
			if( ! $error ) {
				$db2->query('SELECT id, active FROM users WHERE email="'.$db2->e($email).'" LIMIT 1');
				if($obj = $db2->fetch_object()) {
					$error	= TRUE;
					$errmsg	= $obj->active==1 ? $this->lang('signup_err_email_exists') : $this->lang('signup_err_email_disabled');
				}
			}
			
			if( !$error ) {
				if( isset($C->GOOGLE_CAPTCHA_PRIVATE_KEY, $C->GOOGLE_CAPTCHA_PUBLIC_KEY) && ($C->GOOGLE_CAPTCHA_PRIVATE_KEY == '' || $C->GOOGLE_CAPTCHA_PUBLIC_KEY == '') ){
					if( !isset($_POST['captcha_key'],$_POST['captcha_word']) || !isset($_SESSION['captcha_'.$_POST['captcha_key']]) || $_SESSION['captcha_'.$_POST['captcha_key']]!=strtolower($_POST['captcha_word']) ) {
						$error	= TRUE;
						$errmsg	= $this->lang('signup_err_captcha');
						$wrong_captcha = TRUE;
					}
				}else{
					$check = recaptcha_check_answer ( $C->GOOGLE_CAPTCHA_PRIVATE_KEY,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
					if (!$check->is_valid){
						$error	= TRUE;
						$errmsg	= $this->lang('signup_err_captcha');
						$wrong_captcha = TRUE;
					}
				}
			}
			
			if( ! $error ) {
				$reg_key	= md5(rand().time().rand());
				$db1->query('REPLACE INTO unconfirmed_registrations SET email="'.$db1->e($email).'", confirm_key="'.$db1->e($reg_key).'", date="'.time().'" ');
				$reg_id		= intval($db1->insert_id());
				$using_reg_provider = ($this->param('using')? '/using:'.$this->param('using') : '');
				$activation_link	= $C->SITE_URL.'signup/regid:'.$reg_id.'/regkey:'.$reg_key.$using_reg_provider;
				$D->activation_link = $activation_link;
				$subject	= $this->lang('signup_email_subject', array('#SITE_TITLE#'=>$C->SITE_TITLE));

				$msgtxt		= $this->load_single_block('email/signup_txt', FALSE, TRUE);
				$msghtml	= $this->load_single_block('email/signup_html', FALSE, TRUE);
				do_send_mail_html($email, $subject, $msgtxt, $msghtml);
			}
			
		}elseif($is_email_confirmed || !$C->USERS_EMAIL_CONFIRMATION){ 
			//email checks 
			
			if( !$is_email_confirmed ){
				
				if( ! is_valid_email($email) ) {
					$error	= TRUE;
					$errmsg	= $this->lang('signup_err_email_invalid');
				}
				
				if( ! $error ) {
					$db2->query('SELECT id, active FROM users WHERE email="'.$db2->e($email).'" LIMIT 1');
					if($obj = $db2->fetch_object()) {
						$error	= TRUE;
						$errmsg	= $obj->active==1 ? $this->lang('signup_err_email_exists') : $this->lang('signup_err_email_disabled');
					}
				}
				
			} elseif( trim($email) != "") {
				
				$db2->query('SELECT id, active FROM users WHERE email="'.$db2->e($email).'" LIMIT 1');
				if($obj = $db2->fetch_object()) {
					$error	= TRUE;
					$errmsg	= $obj->active==1 ? $this->lang('signup_err_email_exists') : $this->lang('signup_err_email_disabled');
				}
			}
			
			//fullname checks
			if( !$error && empty($fullname) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_fullname');
			}
			
			//username checks
			if( !$error && empty($username) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_username');
			}
			if( !$error && (strlen($username)<3 || strlen($username)>30) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_usernmlen');
			}
			if( !$error && preg_match('/[^a-z0-9-_]/i', $username) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_usernmlet');
			}
			if( !$error ) {
				$db2->query('SELECT id, active FROM users WHERE username="'.$db2->e($username).'" LIMIT 1');
				if($obj = $db2->fetch_object()) {
					$error	= TRUE;
					$errmsg	= $obj->active==1 ? $this->lang('signup_err_usernm_exists') : $this->lang('signup_err_usernm_disabled');
				}
			}
			if( !$error ) {
				$db2->query('SELECT id FROM groups WHERE groupname="'.$db2->e($username).'" LIMIT 1');
				if($obj = $db2->fetch_object()) {
					$error	= TRUE;
					$errmsg	= $this->lang('signup_err_usernm_exists');
				}
			}
			if( !$error && controllers_conflicts_lookup($username) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_usernm_existss');
			}
			
			//password checks
			if( !$error && (empty($password) || empty($password2)) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_password');
				$password	= '';
				$password2	= '';
			}
			if( !$error && strlen($password)<5 ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_passwdlen');
			}
			if( !$error && $password!=$password2 ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_passwddiff');
				$password	= '';
				$password2	= '';
			}
			
			if( !$error ) {
				if( isset($C->GOOGLE_CAPTCHA_PRIVATE_KEY, $C->GOOGLE_CAPTCHA_PUBLIC_KEY) && ($C->GOOGLE_CAPTCHA_PRIVATE_KEY == '' || $C->GOOGLE_CAPTCHA_PUBLIC_KEY == '') ){
					if( !isset($_POST['captcha_key'],$_POST['captcha_word']) || !isset($_SESSION['captcha_'.$_POST['captcha_key']]) || $_SESSION['captcha_'.$_POST['captcha_key']]!=strtolower($_POST['captcha_word']) ) {
						$error	= TRUE;
						$errmsg	= $this->lang('signup_err_captcha');
						$wrong_captcha = TRUE;
					}
				}else{
					$check = recaptcha_check_answer ( $C->GOOGLE_CAPTCHA_PRIVATE_KEY,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
					if (!$check->is_valid){
						$error	= TRUE;
						$errmsg	= $this->lang('signup_err_captcha');
						$wrong_captcha = TRUE;
					}
				}
			}
			
			//terms of user checks
			if( !$error && $terms_of_use && !$accept_terms ) {
				$error	= TRUE;
				$errmsg	= $this->lang('signup_err_terms');
			}
			
			//last check if is registered as a bot in spam database
			if( !$error ){
				$curl = new curlCall('http://www.stopforumspam.com/api?ip='.$_SERVER['REMOTE_ADDR'].'&email='.$email.'&f=json');
				$curl_result = $curl->getData();
				$curl_result = json_decode($curl_result);
			
				if( isset($curl_result->success) && $curl_result->success === 1 ){
					if( isset($curl_result->success->appears) && $curl_result->email->appears ){
						$error	= TRUE;
						$errmsg	= 'SPAM bot detected';
					}elseif( isset($curl_result->ip->appears) && $curl_result->ip->appears ){
						$error	= TRUE;
						$errmsg	= 'SPAM bot detected';
					}
				}
			}
			
			//if all checks are ok 
			if( !$error ) {
				$tmplang	= $db2->fetch_field('SELECT value FROM settings WHERE word="LANGUAGE" LIMIT 1');
				$tmpzone	= $db2->fetch_field('SELECT value FROM settings WHERE word="DEF_TIMEZONE" LIMIT 1');
				$tmppass	= md5($password);
				$lastlogin_ip = ip2long($_SERVER['REMOTE_ADDR']);
				$lastlogin_date = time();
				$is_fb_used = (isset($_POST['fb_user_id'])? 'facebook_uid="'.$_POST['fb_user_id'].'", ' : '');
				$is_tw_used = (isset($_POST['tw_user_id'])? 'twitter_uid="'.$_POST['tw_user_id'].'", ' : '');
				
				$db2->query('INSERT INTO users SET '.$is_fb_used.$is_tw_used.' email="'.$db2->e($email).'", username="'.$db2->e($username).'", password="'.$db2->e($tmppass).'", fullname="'.$db2->e($fullname).'", language="'.$tmplang.'", timezone="'.$tmpzone.'", reg_date="'.$lastlogin_date.'", reg_ip="'.$lastlogin_ip.'", lastlogin_date="'.$lastlogin_date.'", lastlogin_ip="'.$lastlogin_ip.'", active=1');				
				$user_id	= intval($db2->insert_id());
				$db1->query('DELETE FROM unconfirmed_registrations WHERE email="'.$db1->e($email).'" ');
				$this->user->login($email, md5($password), FALSE);
					
				$gravatar_url	= 'http://www.gravatar.com/avatar/'.md5($email).'?s='.$C->AVATAR_SIZE.'&d=404';
				$gravatar_local	= $C->TMP_DIR.'grvtr'.time().rand(0,9999).'.jpg';
				if( @my_copy($gravatar_url, $gravatar_local) ) {
					list($w, $h, $tp) = @getimagesize($gravatar_local);
					if( $w && $h && $tp && $w==$C->AVATAR_SIZE && $h>=$C->AVATAR_SIZE && ($tp==IMAGETYPE_JPEG || $tp==IMAGETYPE_GIF || $tp==IMAGETYPE_PNG) ) {
						$fn	= time().rand(100000,999999).'.png';
						$res	= copy_avatar($gravatar_local, $fn);
						if( $res ) {
							$db2->query('UPDATE users SET avatar="'.$db2->escape($fn).'" WHERE id="'.$user_id.'" LIMIT 1');
							$this->network->get_user_by_id($user_id, TRUE);
						}
					}
					rm($gravatar_local);
				}
			
				$invited_from	= array();
				$r	= $db2->query('SELECT DISTINCT user_id FROM users_invitations WHERE recp_email="'.$db2->e($email).'" LIMIT 1');
				if( $db2->num_rows($r) > 0 ) {
					while($tmpu = $db2->fetch_object($r)) {
						$db2->query('INSERT INTO users_followed SET who="'.$tmpu->user_id.'", whom="'.$user_id.'", date="'.time().'", whom_from_postid="'.$this->network->get_last_post_id().'" ');
						$db2->query('UPDATE users SET num_followers=num_followers+1 WHERE id="'.$user_id.'" LIMIT 1');
						$this->network->get_user_follows($tmpu->user_id, TRUE);
						$invited_from[$tmpu->user_id]	= TRUE;
					}
					$this->network->get_user_by_id($user_id, TRUE);
					$this->network->get_user_follows($user_id, TRUE);
					$db2->query('UPDATE users_invitations SET recp_is_registered=1, recp_user_id="'.$user_id.'" WHERE recp_email="'.$db2->e($email).'" ');
				}
				
				$key	= md5(time().rand(0,999999));
				$_SESSION['reg_'.$key]	= (object) array (
						'network_id'	=> $this->network->id,
						'user_id'		=> $user_id,
				);
		
				$notif = new notifier();
				$notif->set_notification_obj('network', 1);
				$notif->onJoinNetwork();
		
				//if($network_members < 1001 ){
				//	$this->redirect( $C->SITE_URL.'signup/follow/regid:'.$key);
				//}else{
				$this->redirect($C->SITE_URL.'dashboard');
				//}
			}
		}
	}

	$tpl = new template( array('page_title' => $this->lang('signup_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	if( $error && !empty($errmsg) ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage('Error', $errmsg ) );
	}	

	if( !$is_email_confirmed && $C->USERS_EMAIL_CONFIRMATION ){
		
		if( $submit && !$error ){
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signup_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), $this->lang('os_signup_step1_ok_ttl')) );
		}else{			
			$tpl->layout->useBlock('user-signup-email');
			$tpl->layout->block->setVar('user_signup_email_value', $email);
			$tpl->layout->block->setVar('captcha_image', $captcha_html);
			$tpl->layout->block->setVar('captcha_key', $captcha_key);
				
			$tpl->layout->block->setVar('autofocus','');
			if(isset($wrong_captcha)){
				$tpl->layout->block->setVar('autofocus','data-status="focus"');
			}
			
			$tpl->layout->block->save('main_content');
		
		}
	}else{

		if( $C->USERS_EMAIL_CONFIRMATION && filter_var($is_email_confirmed, FILTER_VALIDATE_EMAIL) ){
			$D->email_confirm = TRUE;
			$email = $is_email_confirmed;
		}else if( !$C->USERS_EMAIL_CONFIRMATION && filter_var($email, FILTER_VALIDATE_EMAIL) ){
			$D->email_confirm = FALSE;
		}else{
			$D->email_confirm = FALSE;
			$email = '';
		}
		
		if( $place_facebook_get_data_tab ){
			$tpl->layout->setVar('main_content_placeholder',
				'
				<div style="margin: 10px 0 10px 0;">
				<div id="fb-root"></div>
				<script src="https://connect.facebook.net/en_US/all.js#appId='.$C->FACEBOOK_API_ID.'&xfbml=1"></script>
		
				<fb:registration
				fields="name,email"
						redirect-uri="'.$C->SITE_URL .'signup'.($reg_id? '/regid:'.$reg_id : '').($reg_key? '/regkey:'.$reg_key : '').'"
						width="530">
						</fb:registration>
				</div>
				');
		}else{	
			$tpl->layout->useBlock('registration');
			
			$tpl->layout->block->setVar('registration_fullname', 	(!empty($fullname)? $fullname : '') );
			$tpl->layout->block->setVar('registration_email', 		(!empty($email)? $email : '') );
			$tpl->layout->block->setVar('registration_password', 	(!empty($password)? $password : '') );
			$tpl->layout->block->setVar('registration_password2', 	(!empty($password2)? $password2 : '') );
			$tpl->layout->block->setVar('registration_username', 	(!empty($username)? $username : '') );
			$tpl->layout->block->setVar('captcha_image', $captcha_html);
			$tpl->layout->block->setVar('captcha_key', $captcha_key);
			
			$tpl->layout->block->setVar('autofocus','');
			if(isset($wrong_captcha)){
				$tpl->layout->block->setVar('autofocus','data-status="focus"');
			}
			
			if( $terms_of_use ) {
				$tpl->layout->block->setVar('registration_terms_of_use',
							
						'<div class="captcha-image">
						<label for="accept_terms">'. $this->lang('signup_step2_form_terms',array('#SITE_TITLE#'=>$C->SITE_TITLE,'#A2#'=>'</a>','#A1#'=>'<a href="'.$C->SITE_URL.'terms" target="_blank">')) .'</label>
									<input type="checkbox" id="accept_terms" name="accept_terms" value="1" ' .(($accept_terms)?"checked=\'checked\'":"") . ' />
								</div>'
				);
			}
			$tpl->layout->block->save( 'main_content');
		}
		
		
		
	}
	
	$tpl->display();
?>
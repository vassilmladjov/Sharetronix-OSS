<?php
	
	function set_user_default_notification_rules($user_id)
	{
		global $db2, $network;
		$rules	= array (
			// 0 - off, 1 - on
			'ntf_them_if_i_follow_usr'	=> 1,
			'ntf_them_if_i_comment'		=> 1,
			'ntf_them_if_i_edt_profl'	=> 1,
			'ntf_them_if_i_edt_pictr'	=> 1,
			'ntf_them_if_i_create_grp'	=> 1,
			'ntf_them_if_i_join_grp'	=> 1,
			
			// 0 - off, 2 - post, 3 - email, 1 - both
			'ntf_me_if_u_follows_me'	=> 2,
			'ntf_me_if_u_follows_u2'	=> 0,
			'ntf_me_if_u_commments_me'	=> 0,
			'ntf_me_if_u_commments_m2'	=> 0,
			'ntf_me_if_u_edt_profl'		=> 0,
			'ntf_me_if_u_edt_pictr'		=> 0,
			'ntf_me_if_u_creates_grp'	=> 0,
			'ntf_me_if_u_joins_grp'		=> 2,
			'ntf_me_if_u_invit_me_grp'	=> 2,
			'ntf_me_if_u_posts_qme'		=> 0,
			'ntf_me_if_u_posts_prvmsg'	=> 0,
			'ntf_me_if_u_registers'		=> 0,
			'ntf_me_on_post_like'		=> 0,
		);
		$in_sql	= array();
		$in_sql[]	= '`user_id`="'.$user_id.'"';
		foreach($rules as $k=>$v) {
			$in_sql[]	= '`'.$k.'`="'.$v.'"';
		}
		$in_sql	= implode(', ', $in_sql);
		$db2->query('REPLACE INTO users_notif_rules SET '.$in_sql);
	}
	
	function isEmailConfirmed( $regid, $regkey )
	{
		global $page, $db2;
		 
		$reg_id	= intval($page->param('regid'));
		$reg_key	= $db2->e($page->param('regkey'));
		
		if( !$reg_id || empty($reg_id) || !$reg_key || empty($reg_key) ){
			return FALSE;
		}
		
		$db2->query('SELECT email FROM unconfirmed_registrations WHERE id="'.$reg_id.'" AND confirm_key="'.$reg_key.'" LIMIT 1');
		if( ! $obj = $db2->fetch_object() ) {
			return FALSE;
		}	
		
		$email		= stripslashes($obj->email);
		
		return $email;
	}
	
	function controllers_conflicts_lookup($username)
	{
		global $C;
		$error = FALSE;
		
		if( !$error && file_exists($C->INCPATH.'controllers/'.strtolower($username).'.php') ) {
			$error	= TRUE;
		}
		if( !$error && file_exists($C->INCPATH.'controllers/mobile/'.strtolower($username).'.php') ) {
			$error	= TRUE;
		}
		if( !$error && file_exists($C->INCPATH.'../'.strtolower($username)) ) {
			$error	= TRUE;
		}
		
		return $error;
	}
	
	function parse_signed_request($signed_request) 
	{
		global $C;
		
		if( !isset($C->FACEBOOK_API_SECRET) || empty($C->FACEBOOK_API_SECRET) ){
			return FALSE;
		}
		
		
		list($encoded_sig, $payload) = explode('.', $signed_request, 2);
	
		// decode the data
		$sig = base64_url_decode($encoded_sig);
		$data = json_decode(base64_url_decode($payload), true);
	
		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			//error_log('Unknown algorithm. Expected HMAC-SHA256');
			return FALSE;
		}
	
		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $C->FACEBOOK_API_SECRET, $raw = true);
		if ($sig !== $expected_sig) {
			return FALSE;
		}
	
		return $data;
	}
	
	function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}
	
	function check_if_facebook_called()
	{
		global $C;
		
		if( isset($_POST['fullname'], $_POST['email'], $_POST['username']) && !empty($_POST['fullname']) && !empty($_POST['email']) && !empty($_POST['username'])){
			return FALSE;
		}
		
		if( isset($_POST['signed_request']) ){
			$fb_answer = parse_signed_request($_POST['signed_request'], $C->FACEBOOK_API_SECRET); 
			if( $fb_answer && isset($fb_answer['registration'], $fb_answer['registration']['name'], $fb_answer['registration']['email']) ){
				$_POST['fullname'] 	= trim($fb_answer['registration']['name']);
				if( !$C->USERS_EMAIL_CONFIRMATION ){
					$_POST['email'] 	= trim($fb_answer['registration']['email']);
				}
				$_POST['username'] 	= strtolower( str_replace(' ', '', $fb_answer['registration']['name']) );
			}
			
			if( isset($fb_answer['user_id']) ){
				$_POST['fb_user_id'] = (int) trim($fb_answer['user_id']);
			}
		}
	}
	
	function check_if_twitter_called()
	{
		global $C, $user;
		
		if( isset($user->sess['twitter_completed']) ){
			return FALSE;
		}
		
		if( isset($_POST['fullname'], $_POST['username']) && !empty($_POST['fullname']) && !empty($_POST['username'])){
			return FALSE;
		}
		
		if( !isset($_GET['oauth_token'], $user->sess['oauth_token_secret'], $_GET['oauth_verifier']) ){
			return FALSE;
		}
		
		$twitt = new twitterAuth();
		$tmp = $twitt->getAccessToken();
		
		if( !isset($tmp['oauth_token'], $tmp['oauth_token_secret'], $tmp['screen_name']) ){
			return FALSE;
		}
		
		$tmp = $twitt->getUserDetails($tmp['oauth_token'], $tmp['oauth_token_secret'], $tmp['screen_name']);
		
		if( isset($tmp['id'], $tmp['name'], $tmp['screen_name']) ){		
			$_POST['fullname'] 	= trim($tmp['name']);
			$_POST['username'] 	= strtolower( trim($tmp['screen_name']) );
			$_POST['tw_user_id'] = (int) trim($tmp['id']);
			
			$user->sess['twitter_completed'] = TRUE;
		}
	}
	
	function check_if_use_facebook( $is_email_confirmed )
	{
		global $C, $page;
		
		$reg_id = $page->param('regid');
		$reg_key = $page->param('regkey');
		
		if( $C->USERS_EMAIL_CONFIRMATION ){
			if( $page->param('using') == 'facebook' && !empty($C->FACEBOOK_API_ID) && $reg_id && $reg_key && $is_email_confirmed ){
				return TRUE;	
			}
		}else{
			if(  $page->param('using') == 'facebook' && !empty($C->FACEBOOK_API_ID) ){
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	function check_if_use_twitter( $is_email_confirmed )
	{
		global $C, $page;
	
		$reg_id = $page->param('regid');
		$reg_key = $page->param('regkey');
	
		if( $C->USERS_EMAIL_CONFIRMATION ){
			if( $page->param('using') == 'twitter' && !empty($C->TWITTER_CONSUMER_KEY) && !empty($C->TWITTER_CONSUMER_SECRET) && $reg_id && $reg_key && $is_email_confirmed ){
				return TRUE;
			}
		}else{
			if(  $page->param('using') == 'twitter' && !empty($C->TWITTER_CONSUMER_KEY) && !empty($C->TWITTER_CONSUMER_SECRET) ){
				return TRUE;
			}
		}
	
		return FALSE;
	}
	
	
?>
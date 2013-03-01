<?php
	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
	require_once( $C->INCPATH.'classes/class_rssfeed.php' );
	require_once( $C->INCPATH.'helpers/func_images.php' );
	require_once( $C->INCPATH.'classes/class_twitterdata.php' );
	
	$api_session = new stdClass;
	$api_session->format 	= $this->param('format');
	$api_session->callback	= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
	if( !$C->API_STATUS || $_SERVER['REQUEST_METHOD'] == 'COOKIE'){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($format, 'API is disabled', $_SERVER['REQUEST_URI'], $callback);
		exit;	
	}
	setlocale(LC_TIME, 'en_US');	

	global $user;
	if($user->is_logged){
		$user->logout();
	}
	$user						= new stdClass;
	$user->is_logged 				= false;
	$user->id 					= false;
	$user->info					= new stdClass;
	$user->info->is_network_admin 	= 0;
	$user->info->id 				= false;

	$uri = $this->param('more');
	$api_session->resource 			= isset($uri[0])? $uri[0]:'invalid';
	$api_session->resource_option 	= isset($uri[1])? $uri[1]:false;
	unset($uri);
	
	$api_session->not_in_groups		= '';
	$api_session->oauth_status 		= false;
	$api_session->rate_status 		= false;
	$api_session->bauth_status 		= false;
	$api_session->available_resources 	= array('create', 'destroy', 'exists', 'show', 'verify_credentials', 'incoming', 'outgoing', 'rate_limit_status', /*'delete_feed', 
	'add_feed', */ 'update_profile_image', 'end_session', 'update_profile', 'update_profile_colors');
	$api_session->oauth_error 		= '';
	
	if( ($auth = prepare_request()) || ($auth = prepare_header()) ){
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);
				
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/account/'.$api_session->resource.'.'.$api_session->format);
			if(isset($auth['oauth_version'])) $oauth_client->set_variable('version', '1.0');
			
			if($oauth_client->is_valid_get_resource_request()){
				if($auth['oauth_signature_method'] != 'HMAC-SHA1'){ $api_session->oauth_error = 'Unsupported signature method'; }
				elseif(!$oauth_client->decrypt_hmac_sha1()){ $api_session->oauth_error = 'Invalid signature'.$oauth_client->get_variable('error_msg');}	
				else{
					//success
					$id = $oauth_client->get_user_id(urldecode($auth['oauth_token']));
					$u = $this->network->get_user_by_id($id);
					if($u){
						$api_session->oauth_status 		= true;
						$user->is_logged 				= true;
						$user->id 					= $id;
						$user->info 				= new stdClass;
						$user->info->id 				= $id;
						$user->info->is_network_admin 	= $u->is_network_admin;
						$user->info->is_posts_protected 	= $u->is_posts_protected;
						$user->info->username 			= $u->username;
						$user->info->network_id 		= $u->network_id;
						$user->info->avatar 			= $u->avatar;
					}
					unset($id, $u);
					//success
				}
			}$api_session->oauth_error =  $oauth_client->get_variable('error_msg');		
		}else $api_session->oauth_error = 'Missing OAuth parameters';
	}elseif( $auth = check_if_basic_auth() ) 
	{
		$this->db2->query('SELECT id FROM users WHERE (email="'.$this->db2->e($auth[0]).'" OR username="'.$this->db2->e($auth[0]).'") AND password="'.md5($auth[1]).'" AND active=1 LIMIT 1');
		if( !$obj = $this->db2->fetch_object() ) {
			$api_session->oauth_error = 'Invalid Authorization header.';
		}else{
			$u = $this->network->get_user_by_id($obj->id);
			if($u){
				$api_session->bauth_status 		= true;
				$user->is_logged 				= true;
				$user->id 					= $u->id;
				$user->info 				= new stdClass;
				$user->info->id 				= $u->id;
				$user->info->is_network_admin 	= $u->is_network_admin;
				$user->info->is_posts_protected 	= $u->is_posts_protected;
				$user->info->username 			= $u->username;
				$user->info->network_id 		= $u->network_id;
				$user->info->avatar 			= $u->avatar;
			}
			unset($u, $obj);
		}
	}	
	if(isset($auth)) unset($auth);
	
	if(!is_valid_data_format($api_session->format, TRUE)){	
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources)){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'update_profile'){
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
		
		if(isset($_POST['name']) && mb_strlen($_POST['name']) > 20 ){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid paramater, max length could be 20 characters.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['url']) && !is_valid_url(urldecode($_POST['url'])) ){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid url link paramater.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['description']) && mb_strlen($_POST['description']) > 160 ){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid description paramater, max length could be 160 characters.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['location']) && mb_strlen($_POST['location']) > 30 ){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid location paramater, max length could be 30 characters.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['birthdate']) && !is_valid_date($_POST['birthdate']) ){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid birthdate paramater, the birth date should be in YYYY-DD-MM format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['gender']) && ($_POST['gender'] != 'm' && $_POST['gender'] != 'f')){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid gender paramater, it could be m or f.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_POST['tags']) && (mb_strlen($_POST['tags']) == 0 || mb_strlen($_POST['tags']) > 255)){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid tags paramater, max length could be 255 characters.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		$a_rows = false;
		
		if(isset($_POST['name']) || isset($_POST['description']) || isset($_POST['location']) || isset($_POST['birthdate']) || isset($_POST['gender']) || isset($_POST['tags']) )
		{
			$q = 'UPDATE users SET ';
			if(isset($_POST['name'])) $q .= ' fullname="'.$this->db2->e(htmlspecialchars(urldecode($_POST['name']))).'",';
			if(isset($_POST['description'])) $q .= ' about_me="'.$this->db2->e(htmlspecialchars(urldecode($_POST['description']))).'",';
			if(isset($_POST['location'])) $q .= ' location="'.$this->db2->e(htmlspecialchars(urldecode($_POST['location']))).'",';
			if(isset($_POST['birthdate'])) $q .= ' birthdate="'.$this->db2->e(htmlspecialchars(urldecode($_POST['birthdate']))).'",';
			if(isset($_POST['gender'])) $q .= ' gender="'.$this->db2->e(htmlspecialchars(urldecode($_POST['gender']))).'",';
			if(isset($_POST['tags'])) $q .= ' tags="'.$this->db2->e(htmlspecialchars(urldecode($_POST['tags']))).'",';
			$q = mb_substr($q, 0, -1);
			$q .= ' WHERE id="'.intval($user->id).'" LIMIT 1';
			
			$res = $this->db2->query($q);
			if(!$this->db2->affected_rows($res)){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'Your account was not modified.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;	
			}else $a_rows = true;
		}
		if(isset($_POST['url'])){
			if($check = $this->db2->fetch_field('SELECT 1 FROM users_details WHERE user_id="'.intval($this->db2->e($user->id)).'" LIMIT 1')){
				$res = $this->db2->query('UPDATE users_details SET website="'.$this->db2->e(urldecode($_POST['url'])).'" WHERE user_id="'.intval($id).'" LIMIT 1');
				
				if(!$this->db2->affected_rows($res)){
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
						else echo generate_error($api_session->format, 'Your account was not modified.', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}else $a_rows = true;
			}else
			{		
				$res = $this->db2->query('INSERT INTO users_details(user_id, website) VALUES('.intval($this->db2->e($user->id)).', "'.$this->db2->e(urldecode($_POST['url'])).'")');		
				if(!$this->db2->affected_rows($res))
				{
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
						else echo generate_error($api_session->format, 'Your account was not modified.', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}else $a_rows = true;
			}
		}
		
		if($a_rows){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
	
			$answer .= $twitter_data->data_section('user');	
				$answer .=  $twitter_data->print_user($user->id);	
					$answer .= ($api_session->format == 'json')? ',' : '';	
					$answer .= $twitter_data->data_section('status', TRUE);
						$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($user->id)).'" AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
						$answer .=  $twitter_data->print_status($sid);	
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);			
			$answer .= $twitter_data->data_section('user', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();	
			
			echo $answer;
			$this->network->get_user_by_id($user->id, TRUE);
			exit;
		}else{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'Your account was not modified.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}
	elseif($api_session->resource == 'verify_credentials')
	{					
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;

		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
		$answer = $twitter_data->data_header();
		$answer .= $twitter_data->data_section('user');	
			$answer .=  $twitter_data->print_user($user->id);	
				$answer .= ($api_session->format == 'json')? ',' : '';	
				$answer .= $twitter_data->data_section('status', TRUE);
					$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($user->id)).'" AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
					$answer .=  $twitter_data->print_status($sid);		
				$answer .= $twitter_data->data_section('status', FALSE, TRUE);			
		$answer .= $twitter_data->data_section('user', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();	

		echo $answer;
		exit;
		
	}elseif($api_session->resource == 'end_session')
	{	
		if(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if($user->is_logged){
			$user->is_logged 				= false;
			$user->id 					= false;
			$this->info					= new stdClass;
		}
		
		switch($api_session->format){
			case 'json': echo '"logout": true';
				break;
			case 'xml': echo '<logout>true</logout>';
				break;
		}
		exit;	
	}elseif($api_session->resource == 'rate_limit_status')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if($api_session->oauth_status){
			$r_left = $C->rate_limit_number-$oauth_client->rate_limits_left($user->id);
		}else{
			$r_left = $C->rate_limit_number-rate_limits_left($_SERVER['REMOTE_ADDR']);
		}
			
		header('X-RateLimit-Remaining: '.$r_left);
		header('X-RateLimit-Limit: '.$C->rate_limit_number);
		header('X-RateLimit-Reset: 1239227843');
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1);
		$answer = $twitter_data->data_header();
	
		$answer .= $twitter_data->data_section('hash');
			$answer .= $twitter_data->data_field('hourly-limit', $C->rate_limit_number);
			$answer .= $twitter_data->data_field('reset_time_in_seconds', 1281097951);
			$answer .= $twitter_data->data_field('remaining_hits', $r_left);
			$answer .= $twitter_data->data_field('reset_time', 'Fri Aug 06 12:32:31 +0000 2050', FALSE);		
		$answer .= $twitter_data->data_section('hash', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();
			
		echo $answer;
		exit;

	}elseif($api_session->resource == 'update_profile_image')
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization error: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!isset($_POST['image']) || empty($_POST['image'])){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'POST parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$fl	= $C->TMP_DIR.'tmp_'.md5(time().rand(0,9999));
		file_put_contents($fl, base64_decode($_POST['image']));
		
		list($w, $h, $tp) = @getimagesize($fl);
		if( $w==0 || $h==0 ) {
			$error	= TRUE;
			$errmsg	= 'Invalid image file.';
		}
		elseif( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			$error	= TRUE;
			$errmsg	= 'Invalid image type.';
		}
		elseif( $w<200 || $h<200 ) {
			$error	= TRUE;
			$errmsg	= 'Too small image resolution.';
		}
		else {
			$fn	= time().rand(100000,999999).'.png';
			$res	= copy_avatar($fl, $fn);
			if( ! $res) {
				$error	= TRUE;
				$errmsg	= 'Inappropriate image file.';
			}
		}

		if(!$error){	
			$old	= $user->info->avatar;
			if( $old != $C->DEF_AVATAR_USER ) {
				rm( $C->IMG_DIR.'avatars/'.$old );
				rm( $C->IMG_DIR.'avatars/thumbs1/'.$old );
				rm( $C->IMG_DIR.'avatars/thumbs2/'.$old );
				rm( $C->IMG_DIR.'avatars/thumbs3/'.$old );
			}
			$this->db2->query('UPDATE users SET avatar="'.$this->db2->escape($fn).'" WHERE id="'.intval($this->db2->e($user->id)).'" LIMIT 1');
			$network->get_user_by_id($user->id, TRUE);
		
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
	
			$answer .= $twitter_data->data_section('user');	
				$answer .=  $twitter_data->print_user($user->id);	
					$answer .= ($api_session->format == 'json')? ',' : '';	
					$answer .= $twitter_data->data_section('status', TRUE);
						$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($user->id)).'" AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
						$answer .=  $twitter_data->print_status($sid);		
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);			
			$answer .= $twitter_data->data_section('user', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();
	
			echo $answer;
			exit;
		}else
		{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, $errmsg, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;	
		}
	}elseif($api_session->resource == 'update_delivery_device')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;	
	}elseif($api_session->resource == 'update_profile_colors')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;	
	}elseif($api_session->resource == 'update_profile_background')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;	
	}/*elseif($api_session->resource == 'add_feed' || $api_session->resource == 'delete_feed')
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST')
		{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if(!$api_session->oauth_status && !$api_session->bauth_status)
		{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status)
		{
			$id = intval($oauth_client->get_field_in_table('oauth_access_token', 'user_id', 'access_token', urldecode($auth['oauth_token'])));
			if(!$id)
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage UP1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			if(!$oauth_client->check_access_type('rw'))
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$u = $this->network->get_user_by_id($id);
			if(!$u)
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server Error (Stage f1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			$user->logout();
			$user->login($u->username, $u->password); 
			if( ! $user->is_logged ) 
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage f2).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}elseif($api_session->bauth_status) $id = $user->id;
		
		$error = false;
		$errmsg = '';
		$newfeed_auth_req = false;
		
		if($api_session->resource == 'add_feed')
		{
			if(!isset($_POST['url']) || !is_valid_url($_POST['url']))
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid url parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			
				exit;		
			}
			$newfeed_url	= trim(urldecode($_POST['url']));
			if(isset($_POST['filter']))
			{
				$newfeed_filter	= trim( mb_strtolower(urldecode($_POST['newfeed_filter'])) );
				$newfeed_filter	= preg_replace('/[^\,א-תÀ-ÿ一-龥а-яa-z0-9-\_\.\#\s]/iu', '', $newfeed_filter);
				$newfeed_filter	= preg_replace('/\s+/ius', ' ', $newfeed_filter); */
				//$newfeed_filter	= preg_replace('/(\s)*(\,)+(\s)*/iu', ',', $newfeed_filter);
				/*$newfeed_filter	= trim( trim($newfeed_filter, ',') );
				$newfeed_filter	= str_replace(',', ', ', $newfeed_filter);
			}else $newfeed_filter = '';
			
			$newfeed_username	= isset($_POST['username']) ? trim(urldecode($_POST['username'])) : '';
			$newfeed_password	= isset($_POST['password']) ? trim(urldecode($_POST['password'])) : '';
		
			$f	= '';

			$f	= new rssfeed($newfeed_url);
			$auth	= $f->check_if_requires_auth();
			if( $f->error ) 
			{
				$error	= TRUE;
				$errmsg	= 'Feed authentication error.';
			}
			elseif( $auth ) 	$newfeed_auth_req	= TRUE;
			else 
			{
				$f->read();
				if( $f->error ) 
				{
					$error	= TRUE;
					$errmsg	= 'Error reading rss feed';
				}
			}
			
			if( !$error && $newfeed_auth_req && !empty($newfeed_username) && !empty($newfeed_password) ) 
			{
				$f->set_userpwd($newfeed_username.':'.$newfeed_password);
				$auth	= $f->check_if_requires_auth();
				if( $f->error || $auth ) {
					$error	= TRUE;
					$errmsg	= 'Wrong username/password.';
				}
				else 
				{
					$f->read();
					if( $f->error ) 
					{
						$error	= TRUE;
						$errmsg	= 'Inappropriate rss feed';
					}
				}
			}
			if( !$error && $f->is_read ) 
			{
				$f->fetch();
				$lastdate	= $f->get_lastitem_date();
				if( ! $lastdate ) $lastdate	= time();
				
				$title	= $f->title;
				if( empty($title) ) $title	= preg_replace('/^(http|https|ftp)\:\/\//iu', '', $newfeed_url);

				$title	= $this->db2->e($title);
				$usrpwd	= $newfeed_auth_req ? ($newfeed_username.':'.$newfeed_password) : '';
				$usrpwd	= $this->db2->e($usrpwd);
				$keywords	= str_replace(', ', ',', $newfeed_filter);
				$keywords	= $this->db2->e($keywords);
				
				$q = 'SELECT id FROM users_rssfeeds WHERE is_deleted=0 AND user_id="'.intval($id).'" AND feed_url="'.$this->db2->e($newfeed_url).'" AND feed_userpwd="'.$usrpwd.'" AND filter_keywords="'.$keywords.'" LIMIT 1';
				$this->db2->query($q);
				
				if( 0 == $this->db2->num_rows() ) 
				{
					$q = 'INSERT INTO users_rssfeeds SET is_deleted=0, user_id="'.intval($id).'", feed_url="'.$this->db2->e($newfeed_url).'", feed_title="'.$title.'", feed_userpwd="'.$usrpwd.'", filter_keywords="'.$keywords.'", date_added="'.time().'", date_last_post=0, date_last_crawl="'.time().'", date_last_item="'.$lastdate.'"';		
					$this->db2->query($q);
				}
				$twitter_data = new TwitterData($api_session->format, $api_session->callback, $id);
				$answer = $twitter_data->data_header();
		
				$answer .= $twitter_data->data_section('user');	
					$answer .=  $twitter_data->print_user($id);	
						$answer .= ($api_session->format == 'json')? ',' : '';	
						$answer .= $twitter_data->data_section('status', TRUE);
							$q = 'SELECT id AS pid FROM posts WHERE user_id=\''.intval($id).'\' AND api_id<>2 AND api_id<>6 ORDER BY id DESC LIMIT 1';				
							$answer .=  $twitter_data->print_status(0, FALSE, $q);	
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);			
				$answer .= $twitter_data->data_section('user', FALSE, TRUE);
				$answer .= $twitter_data->data_bottom();
				
				echo $answer;
				exit;				
			}else
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
					else echo generate_error($api_session->format, $errmsg, $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;	
			}
		}else
		{
			if(isset($api_session->resource_option) && is_numeric($api_session->resource_option))
			{
				$q = 'UPDATE users_rssfeeds SET is_deleted=1 WHERE id="'.intval($api_session->resource_option).'" AND user_id="'.intval($id).'" LIMIT 1';

				$res = $this->db2->query($q); 
				if(!$this->db2->affected_rows($res))
				{
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
						else echo generate_error($api_session->format, 'Invalid feed or user id', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;	
				}
				$twitter_data = new TwitterData($api_session->format, $api_session->callback, $id);
				$answer = $twitter_data->data_header();
		
				$answer .= $twitter_data->data_section('user');	
					$answer .=  $twitter_data->print_user($id);	
						$answer .= ($api_session->format == 'json')? ',' : '';	
						$answer .= $twitter_data->data_section('status', TRUE);
							$q = 'SELECT id AS pid FROM posts WHERE user_id=\''.intval($id).'\' AND api_id<>2 AND api_id<>6 ORDER BY id DESC LIMIT 1';				
							$answer .=  $twitter_data->print_status(0, FALSE, $q);	
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);			
				$answer .= $twitter_data->data_section('user', FALSE, TRUE);
				$answer .= $twitter_data->data_bottom();
				
				echo $answer;
				exit;		
			}else
			{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
					else echo generate_error($api_session->format, 'Invalid feed id', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;	
			}
		}
	}*/
?>
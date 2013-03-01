<?php

	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
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
	//$user					= new stdClass;
	$user->is_logged 				= false;
	$user->id 					= false;
	//$user->info				= new stdClass;
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
	$api_session->available_resources 	= array('create', 'destroy', 'exists', 'show', 'incoming', 'outgoing');
	$api_session->oauth_error 		= '';
	
	
	if( ($auth = prepare_request()) || ($auth = prepare_header()))
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			if(!isset($api_session->resource))
			{
				$api_session->oauth_error = 'Invalid address.';
				exit;
			}
			$ares = (isset($api_session->resource_option))? ('/'.$api_session->resource_option):'';
			
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);
				
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/friendships/'.$api_session->resource.$ares.'.'.$api_session->format);
			if(isset($auth['oauth_version'])) $oauth_client->set_variable('version', '1.0');
			
			if($oauth_client->is_valid_get_resource_request())
			{
				if($auth['oauth_signature_method'] != 'HMAC-SHA1'){ $api_session->oauth_error = 'Unsupported signature method'; }
				elseif(!$oauth_client->decrypt_hmac_sha1()){ $api_session->oauth_error = 'Invalid signature'; }	
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
						$user->info->fullname 			= $u->fullname;
						$user->info->network_id 		= $u->network_id;
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
				$user->info->fullname 			= $u->fullname;
				$user->info->network_id 		= $u->network_id;
			}
			unset($u, $obj);
		}
	}
	

	if(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources ))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'create' || $api_session->resource == 'destroy')
	{	
		if($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a POST or a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
		
		$follow_id = find_user_id($api_session->resource_option);
		if(!$follow_id){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Invalid user credentials.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($user->id == $follow_id){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'Invalid user ids.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if($api_session->resource == 'create'){
			$info	= $this->network->get_user_follows($user->id);
			if(!$info){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage 1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}	
			$following	= array_keys($info->follow_users);
			
			if(!in_array($follow_id, $following)) $ok = $user->follow($follow_id);
			else{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'You have already followed this user.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;	
			}
		}
		elseif($api_session->resource == 'destroy'){
			$info	= $this->network->get_user_follows($user->id);
			if(!$info) {
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage V1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}	
			$following	= array_keys($info->follow_users);
			
			if(in_array($follow_id, $following)) $ok = $user->follow($follow_id, FALSE);
			else{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'This user is not your friend.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}	
		}
		
		if( !$ok ){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'Invalid user id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		else {
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
		}
	}elseif($api_session->resource == 'exists')
	{	
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_GET['user_a']) && is_numeric($_GET['user_a'])) $user_a = intval($_GET['user_a']);
		if(isset($_GET['user_b']) && is_numeric($_GET['user_b'])) $user_b = intval($_GET['user_b']);
		
		if(!isset($user_a) || !isset($user_b) || ($user_a == $user_b)){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid user ids.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
			
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$ok = $this->db2->fetch_field('SELECT 1 FROM `users_followed` WHERE who="'.$this->db2->e($user_a).'" AND whom="'.$this->db2->e($user_b).'" LIMIT 1');
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1);
		$answer = $twitter_data->data_header();
		
		if( $ok ) $answer .= $twitter_data->data_field('friends', 'true', FALSE);
			else $answer .= $twitter_data->data_field('friends', 'false', FALSE);
			
		$answer .= $twitter_data->data_bottom();
			
		echo $answer;
		exit;	
			
	}elseif($api_session->resource == 'show')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if(isset($_GET['source_id']) && is_numeric($_GET['source_id'])) $source_id = intval($_GET['source_id']);
		elseif(isset($_GET['source_screen_name'])){
			$u = $this->network->get_user_by_username(urldecode($_GET['source_screen_name']));
			if(!$u){
				if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'Invalid user.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$source_id = $u->id;
		}		
		if(isset($_GET['target_id']) && is_numeric($_GET['target_id'])) $target_id = intval($_GET['target_id']);
		elseif(isset($_GET['target_screen_name'])){
			$u = $this->network->get_user_by_username(urldecode($_GET['target_screen_name']));
			if(!$u){
				if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'Invalid user.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$target_id = $u->id;
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'Parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
		}
		
		if(!isset($source_id) || !isset($target_id) || ($source_id == $target_id)){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid user ids.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$s_u	= $this->network->get_user_by_id($source_id);
		$t_u	= $this->network->get_user_by_id($target_id);
		if(!$s_u || !$t_u){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid user id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$user_result = new stdClass;
		if($stat = $this->db2->fetch_field('SELECT 1 FROM users_followed WHERE who="'.$this->db2->e($s_u->id).'" AND whom="'.$this->db2->e($t_u->id).'" LIMIT 1')){
			$user_result->source_follow_target = 'true'; 
		}else $user_result->source_follow_target = 'false';	
		
		if($stat = $this->db2->fetch_field('SELECT 1 FROM users_followed WHERE who="'.$this->db2->e($t_u->id).'" AND whom="'.$this->db2->e($s_u->id).'" LIMIT 1')){
			$user_result->target_follow_source = 'true'; 
		}else $user_result->target_follow_source = 'false';			

		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $s_u->id);
			$answer = $twitter_data->data_header();
			$answer .= ($api_session->format == 'json')? '{':''; 
			$answer .= $twitter_data->data_section('relationship', TRUE);
				$answer .= $twitter_data->data_section('source', TRUE);
					$answer .= $twitter_data->data_field('id', $source_id);
					$answer .= $twitter_data->data_field('screen_name', $s_u->username);
					$answer .= $twitter_data->data_field('following', $user_result->source_follow_target);
					$answer .= $twitter_data->data_field('followed_by', $user_result->target_follow_source, FALSE);
				$answer .= $twitter_data->data_section('source', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json')? ',':''; 
					
				$answer .= $twitter_data->data_section('target', TRUE);
					$answer .= $twitter_data->data_field('id', $target_id);
					$answer .= $twitter_data->data_field('screen_name', $t_u->username);
					$answer .= $twitter_data->data_field('following', $user_result->target_follow_source);
					$answer .= $twitter_data->data_field('followed_by', $user_result->source_follow_target, FALSE);
				$answer .= $twitter_data->data_section('target', FALSE, TRUE);	
				$answer .= $twitter_data->data_bottom();
			$answer .= $twitter_data->data_section('relationship', FALSE, TRUE);	
			$answer .= ($api_session->format == 'json')? '}':''; 
		echo $answer;
		exit;	
	
	}elseif($api_session->resource == 'incoming')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;			
	}elseif($api_session->resource == 'outgoing')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;	
	}
?>
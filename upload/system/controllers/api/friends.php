<?php

	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
	require_once( $C->INCPATH.'classes/class_twitterdata.php' );
	
	$api_session = new stdClass;
	$api_session->format 	= $this->param('format');
	$api_session->callback	= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
	if( !$C->API_STATUS || $_SERVER['REQUEST_METHOD'] == 'COOKIE'){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'API is disabled', $_SERVER['REQUEST_URI'], $callback);
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
	$api_session->available_resources 	= array('ids');
	$api_session->oauth_error 		= '';
	
	if( ($auth = prepare_request()) || ($auth = prepare_header()) )
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$ares = (isset($api_session->resource_option))? ('/'.$api_session->resource_option):'';
			$resource = (isset($api_session->resource))? ('favorites/'.$api_session->resource.$ares):'favorites';

			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);
				
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/'.$resource.'.'.$api_session->format);
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
				$user->info->network_id 		= $u->network_id;
			}
			unset($u, $obj);
		}
	}

	if($_SERVER['REQUEST_METHOD'] != 'GET'){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!is_valid_data_format($api_session->format, TRUE)){		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}elseif($api_session->resource == 'invalid' || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid resource request', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'ids')
	{	
		$desired_user_id = find_user_id($api_session->resource_option);
		$desired_user_id = (!$desired_user_id)? $user->id:$desired_user_id;
		
		if(!$desired_user_id){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Invalid user credentials.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$info	= $this->network->get_user_follows($desired_user_id);
		if(!$info) {
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
				else echo generate_error($api_session->format, 'Server error (Stage 1).', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$following	= array_keys($info->follow_users);
		if(!count($following)){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No friends found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$num_rows = count($following);
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $desired_user_id, TRUE);
		$answer = $twitter_data->data_header();

		$answer .= $twitter_data->data_section('id_list', FALSE, FALSE, TRUE, ' type="array"');
			$answer .= $twitter_data->data_section('ids');			
			foreach($following as $user_id){ 
				$check = ($num_rows-1>0)? true:false;
				$answer .= $twitter_data->data_field('id', $user_id, $check);
				$num_rows--;	
			}		
			$answer .= $twitter_data->data_section('ids', FALSE, TRUE);
		$answer .= $twitter_data->data_section('id_list', FALSE,  TRUE, TRUE);
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;
	}
?>
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
	$api_session->without_users 		= '';
	$api_session->oauth_status 		= false;
	$api_session->rate_status 		= false;
	$api_session->bauth_status 		= false;
	$api_session->available_resources 	= array('show', 'lookup', 'search', 'suggestion', 'groups', 'profile_image');
	$api_session->oauth_error 		= '';

	if( ($auth = prepare_request()) || ($auth = prepare_header()))
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$ares = ($api_session->resource_option)? ('/'.$api_session->resource_option):'';
			
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);
				
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/users/'.$api_session->resource.$ares.'.'.$api_session->format);
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
	
	if(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'show')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$desired_user_id = find_user_id($api_session->resource_option);
		$desired_user_id = (!$desired_user_id)? $user->id:$desired_user_id;
		
		if(!$desired_user_id || (!$res = $this->network->get_user_by_id($desired_user_id))){
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
		
		if( !$user->info->is_network_admin ){
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).') ') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $desired_user_id);
		$answer = $twitter_data->data_header();

		$answer .= $twitter_data->data_section('user');
			$answer .=  $twitter_data->print_user($desired_user_id);		
			$answer .= ($api_session->format == 'json')? ',':''; 
			
			$answer .= $twitter_data->data_section('status', TRUE);
				$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($desired_user_id)).'" '.$api_session->not_in_groups.$api_session->without_users.' AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
				$answer .=  $twitter_data->print_status($sid);	
			$answer .= $twitter_data->data_section('status', FALSE, TRUE);	
				
		$answer .= $twitter_data->data_section('user', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;
	}elseif($api_session->resource == 'lookup')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem:'.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}		
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
		$answer = $twitter_data->data_header();
		$answer .= $twitter_data->data_section('users', FALSE, FALSE, TRUE, ' type="array"');
		
		if( !$user->info->is_network_admin ){
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).') ') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$there_are_users = false;
		
		if(isset($_REQUEST['user_id'])){
			$user_ids = explode(',', urldecode($_REQUEST['user_id']));
			$num_rows = count($user_ids);
				
			if(count($user_ids) > 0){
				foreach($user_ids as $user){
					if(!$res = $this->network->get_user_by_id($user)){
						continue;
					}
					$there_are_users = true;
					
					$answer .= $twitter_data->data_section('user');
						$answer .=  $twitter_data->print_user($user);		
							$answer .= ($api_session->format == 'json')? ',':''; 
							$answer .= $twitter_data->data_section('status', TRUE);
								$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($user)).'" '.$api_session->not_in_groups.$api_session->without_users.' AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
								$answer .=  $twitter_data->print_status($sid);	
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);
					$answer .= $twitter_data->data_section('user', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
			}
			$answer = (mb_substr($answer, -1, 1)==',')? mb_substr($answer, 0, (mb_strlen($answer)-1)):$answer;
		}
		if(isset($_REQUEST['screen_name'])){
			$user_names = explode(',', urldecode($_REQUEST['screen_name']));
			$num_rows = count($user_names);
			
			$answer = ($there_are_users)? $answer.',':$answer;
			
			if(count($user_names) > 0){
				foreach($user_names as $user){
					$uid = $this->network->get_user_by_username($user, FALSE, TRUE);
					if(!$uid){
						continue;
					}
					$answer .= $twitter_data->data_section('user');
						$answer .=  $twitter_data->print_user($uid);			
							$answer .= ($api_session->format == 'json')? ',':''; 
							$answer .= $twitter_data->data_section('status', TRUE);
								$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($uid)).'" '.$api_session->not_in_groups.$api_session->without_users.' AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
								$answer .=  $twitter_data->print_status($sid);	
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);
					$answer .= $twitter_data->data_section('user', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
			}
			$answer = (mb_substr($answer, -1, 1)==',')? mb_substr($answer, 0, (mb_strlen($answer)-1)):$answer;
		}
		$answer .= $twitter_data->data_section('users', FALSE,  TRUE, TRUE);
		$answer .= $twitter_data->data_bottom();
		echo $answer;
		exit;
	}elseif($api_session->resource == 'search')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem:'.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}		
		}

		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if(!isset($_REQUEST['q']) || empty($_REQUEST['q'])){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Query parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}else $_REQUEST['q'] = urldecode($_GET['q']);
		
		$q = 'SELECT id FROM users WHERE username LIKE \'%'.$this->db2->e($_REQUEST['q']).'%\'';
		if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])){		
			if(isset($_REQUEST['per_page']) && is_numeric($_REQUEST['per_page']) && $_REQUEST['per_page']<=20)
			$q .= ' LIMIT '.intval($this->db2->e($_REQUEST['per_page']))*(intval($this->db2->e($_REQUEST['page']))-1).', '.(intval($this->db2->e($_REQUEST['per_page'])))*(intval($this->db2->e($_REQUEST['page'])));
			else 
			$q .= ' LIMIT '.(20)*(intval($this->db2->e($_REQUEST['page']))-1).', '.(20)*(intval($this->db2->e($_REQUEST['page'])));
		}elseif(isset($_REQUEST['per_page']) && is_numeric($_REQUEST['per_page']) && $_REQUEST['per_page']<=20)
			$q .= ' LIMIT '.intval($this->db2->e($_REQUEST['per_page']));

		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows>0){
			if( !$user->info->is_network_admin ){
				$api_session->not_in_groups	= array();
				$api_session->not_in_groups 	= not_in_groups();
				$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).') ') : '';
				
				$api_session->without_users = protected_users();
				$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
			}
			
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			$answer .= $twitter_data->data_section('users', FALSE, FALSE, TRUE, ' type="array"');
			while($usr = $this->db2->fetch_object($res))
			{
				$answer .= $twitter_data->data_section('user');
					$answer .=  $twitter_data->print_user($usr->id);		
					$answer .= ($api_session->format == 'json')? ',':''; 
					
					$answer .= $twitter_data->data_section('status', TRUE);
						$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($usr->id).'" '.$api_session->not_in_groups.$api_session->without_users.'  AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
						$answer .=  $twitter_data->print_status($sid);	
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);	
						
				$answer .= $twitter_data->data_section('user', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;
			}
			$answer .= $twitter_data->data_section('users', FALSE,  TRUE, TRUE);
			$answer .= $twitter_data->data_bottom();

			echo $answer;
			exit;	
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No Results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}		
	}elseif($api_session->resource == 'suggestion' && isset($api_session->resource_option) && $api_session->resource_option == 'category')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;	
	}elseif($api_session->resource == 'suggestion')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;		
	}elseif($api_session->resource == 'profile_image')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		
		exit;		
	} elseif(($api_session->resource == 'groups'))
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST')
		{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status)
		{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem:'.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if($api_session->oauth_status)
		{
			$id = intval($oauth_client->get_field_in_table('oauth_access_token', 'user_id', 'access_token', urldecode($auth['oauth_token'])));
			if(!$id)
			{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage C1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			if(!$oauth_client->check_access_type('rw'))
			{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$u = $this->network->get_user_by_id($id);
			if(!$u)
			{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
					else echo generate_error($api_session->format, 'Server error (Stage U11).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			$user->logout();
			$user->login($u->username, $u->password); 
			if( !$user->is_logged ) 
			{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage U1).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}		
		}elseif($api_session->bauth_status) $id = $user->id;

		if(isset($api_session->resource_option) && is_numeric($api_session->resource_option)) $id = intval($api_session->resource_option);
		elseif(isset($api_session->resource_option))
		{
			$u = $this->network->get_user_by_username(urldecode($api_session->resource_option));
			if(!$u)
			{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
					else echo generate_error($api_session->format, 'Invalid username.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$id = $u->id;
		}
		else
		{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
			 
		$q = 'SELECT groups.groupname AS gn, groups_followed.group_id AS gi FROM groups_followed, groups, users WHERE groups_followed.user_id=users.id AND groups_followed.group_id=groups.id AND groups.is_public AND groups_followed.user_id='.intval($id).' GROUP BY groups.id';
		
		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0)
		{	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $id, TRUE);
			$answer = $twitter_data->data_header();
			$answer .= $twitter_data->data_section('groups', FALSE, FALSE, TRUE, ' type="array"');
				while($obj = $this->db2->fetch_object($res))
				{
					$answer .= $twitter_data->data_section('group', FALSE);
						$answer .= $twitter_data->data_field('id', $obj->gi);
						$answer .= $twitter_data->data_field('name', $obj->gn, FALSE);
					$answer .= $twitter_data->data_section('group', FALSE, TRUE);
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
					
				$answer .= $twitter_data->data_section('groups', FALSE, TRUE, TRUE);
			$answer .= $twitter_data->data_bottom();

			echo $answer;
			exit;	
		}else
		{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}	
	}
?>
<?php
	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
	require_once( $C->INCPATH.'classes/class_twitterdata.php' );
	
	$api_session = new stdClass;
	$api_session->format 	= $this->param('format');
	$api_session->callback	= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
	if( !$C->API_STATUS || $_SERVER['REQUEST_METHOD'] == 'COOKIE' ){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($format, 'API is disabled', $_SERVER['REQUEST_URI'], $api_session->callback);
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
	$api_session->oauth_error 		= '';

	if( ($auth = prepare_request()) || ($auth = prepare_header()) )
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$ares = ($api_session->resource_option)? ('/'.$api_session->resource_option):'';
			$resource = ($api_session->resource!='invalid')? ('direct_messages/'.$api_session->resource.$ares):'direct_messages';
			
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
	if(isset($auth)) unset($auth);

	if(!is_valid_data_format($api_session->format))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error($api_session->format, 'Invalid resource request', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(isset($api_session->resource) && $api_session->resource == 'new')
	{	
		if($_SERVER['REQUEST_METHOD'] != 'POST'  || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			$app_id = $oauth_client->get_value_in_consumer_key('app_id');
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}elseif($api_session->bauth_status){
			if(isset($_POST['source'])) $app_id = detect_app($_POST['source']); 
			else $app_id = detect_app(); 
			
			if(!is_numeric($app_id)) $app_id = get_app_id($app_id);
		}
		
		$to_id = find_user_id($api_session->resource_option);
		if(!$to_id){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Invalid user credentials.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$sender_name 	= $user->info->username;
		$temp 		= $network->get_user_by_id($to_id);
		$recipient_name 	= $temp->username;
		unset($temp);
		
		if(!isset($_POST['text']) || empty($_POST['text'])){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Empty or missing text parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		$_POST['text'] = htmlspecialchars(stripslashes(urldecode($_POST['text'])));
		
		if($to_id == $user->id){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid user id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if( $message = $this->db2->fetch_field('SELECT message FROM posts_pr WHERE user_id="'.intval($this->db2->e($user->id)).'" ORDER BY id DESC LIMIT 1') ){
			if($message == $_POST['text']){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'Provide a different message.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
	
		$newpost	= new newpost();
		$ok = $newpost->set_api_id( $app_id );
		if($ok){
			$ok = $newpost->set_to_user( $to_id ); 
		}
		if($ok){
			$newpost->set_message( $_POST['text'] );
			$ok = $newpost->save();
		}
		if( !$ok ) {
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
				else echo generate_error($api_session->format, 'Server error (Stage N2).', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		else {
			$p_id = explode("_", $ok);
			if(!$res = $this->db2->query('SELECT id AS pid, user_id, to_user, message, date FROM posts_pr WHERE id="'.intval($this->db2->e($p_id[0])).'" LIMIT 1')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage 22).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$message = $this->db2->fetch_object($res);

			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
		
			$answer .= $twitter_data->data_section('direct_message');
				$answer .= $twitter_data->data_field('id', $message->pid, TRUE, FALSE);
				$answer .= $twitter_data->data_field('sender_id', $message->user_id, TRUE, FALSE);
				$answer .= $twitter_data->data_field('text', $message->message);
				$answer .= $twitter_data->data_field('recipient_id', $message->to_user, TRUE, FALSE);
				$answer .= $twitter_data->data_field('created_at', gmdate('D M d H:i:s \+0000 Y', $message->date));
				$answer .= $twitter_data->data_field('sender_screen_name', $sender_name);
				$answer .= $twitter_data->data_field('recipient_screen_name', $recipient_name);
				
				$answer .= $twitter_data->data_section('sender', TRUE);
					$answer .= $twitter_data->print_user($message->user_id);
				$answer .= $twitter_data->data_section('sender', FALSE, TRUE);
				$answer .= ($api_session->format == 'json')? ',' : '';	
				
				$answer .= $twitter_data->data_section('recipient', TRUE);
					$answer .= $twitter_data->print_user($message->to_user);
				$answer .= $twitter_data->data_section('recipient', FALSE, TRUE);	
					
			$answer .= $twitter_data->data_section('direct_message', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();

			echo $answer;
			exit;
		}	
	}elseif(isset($api_session->resource) && $api_session->resource == 'destroy')
	{
		if(($_SERVER['REQUEST_METHOD'] != 'POST'  && $_SERVER['REQUEST_METHOD'] != 'DELETE') || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a POST or a DELETE.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission forthis action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}

		if(isset($api_session->resource_option) && is_numeric($api_session->resource_option)) $m_id = intval($api_session->resource_option);
		else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Invalid or missing id parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$private_post = new post('private', $m_id);	

		if(!isset($private_post->post_id)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Post author authenticating problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
		$answer = $twitter_data->data_header();

		$answer .= $twitter_data->data_section('direct_message');
			$answer .= $twitter_data->data_field('id', $m_id);
			$answer .= $twitter_data->data_field('sender_id', $private_post->post_user->id);
			$answer .= $twitter_data->data_field('text', htmlspecialchars($private_post->post_message));
			$answer .= $twitter_data->data_field('recipient_id', $private_post->post_to_user->id);
			$answer .= $twitter_data->data_field('created_at', gmdate('D M d H:i:s \+0000 Y', $private_post->post_date));
			$answer .= $twitter_data->data_field('sender_screen_name', 'none');
			$answer .= $twitter_data->data_field('recipient_screen_name', 'none');
			
			$answer .= $twitter_data->data_section('sender', TRUE);
				$answer .= $twitter_data->print_user($private_post->post_user->id);
			$answer .= $twitter_data->data_section('sender', FALSE, TRUE);
			$answer .= ($api_session->format == 'json')? ',' : '';	
			
			$answer .= $twitter_data->data_section('recipient', TRUE);
				$answer .= $twitter_data->print_user($private_post->post_to_user->id);
			$answer .= $twitter_data->data_section('recipient', FALSE, TRUE);	
				
		$answer .= $twitter_data->data_section('direct_message', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();

		if($private_post->delete_this_post()){
			echo $answer;
			exit;
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
				else echo generate_error($api_session->format, 'Server error (Stage 4).', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}else
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
		
		$field = (isset($api_session->resource) && $api_session->resource == 'sent') ? 'user_id' : 'to_user';	
		$q = 'SELECT id, user_id, to_user, message, date  FROM posts_pr WHERE '.$field.' ="'.intval($user->id).'"';	
		
		if(isset($_GET['since_id']) && is_numeric($_GET['since_id'])) $q .= ' AND id>'.intval($_GET['since_id']);
		if(isset($_GET['max_id']) && is_numeric($_GET['max_id'])) $q .= ' AND id<'.intval($_GET['max_id']);
		
		$q .= ' ORDER BY id DESC ';
		
		if(isset($_GET['count']) && !isset($_GET['page']))
			{if(is_numeric($_GET['count']) && $_GET['count']<200) $q .= ' LIMIT '.intval($_GET['count']);}
		elseif(isset($_GET['page']) && !isset($_GET['count']))
			{if(is_numeric($_GET['page'])) $q .= ' LIMIT '.(20)*(intval($_GET['page'])-1).', '.(20)*(intval($_GET['page']));}
		elseif(isset($_GET['page']) && isset($_GET['count']))
			{if(is_numeric($_GET['page']) && is_numeric($_GET['count'])) 
				$q .= ' LIMIT '.(intval($_GET['count']))*(intval($_GET['page'])-1).', '.(intval($_GET['count']))*(intval($_GET['page']));}
		else $q .= ' LIMIT 20';

		$res = $this->db2->query($q); 
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0){	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			if($twitter_data->is_feed())
				while($stat = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($stat->pid, 'private');
			else{
				$answer .= $twitter_data->data_section('direct-messages', FALSE, FALSE, TRUE, ' type="array"');
					while($message = $this->db2->fetch_object($res)){	
						$answer .= $twitter_data->data_section('direct_message');
							$answer .= $twitter_data->data_field('id', $message->id);
							$answer .= $twitter_data->data_field('sender_id', $message->user_id);
							$answer .= $twitter_data->data_field('text', htmlspecialchars($message->message));
							$answer .= $twitter_data->data_field('recipient_id', $message->to_user);
							$answer .= $twitter_data->data_field('created_at', gmdate('D M d H:i:s \+0000 Y', $message->date));
							$answer .= $twitter_data->data_field('sender_screen_name', 'none');
							$answer .= $twitter_data->data_field('recipient_screen_name', 'none');
							
							$answer .= $twitter_data->data_section('sender', TRUE);
								$answer .= $twitter_data->print_user($message->user_id);
							$answer .= $twitter_data->data_section('sender', FALSE, TRUE);
							$answer .= ($api_session->format == 'json')? ',' : '';	
							
							$answer .= $twitter_data->data_section('recipient', TRUE);
								$answer .= $twitter_data->print_user($message->to_user);
							$answer .= $twitter_data->data_section('recipient', FALSE, TRUE);	
								
						$answer .= $twitter_data->data_section('direct_message', FALSE, TRUE);
						
						$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
						$num_rows--;
					}
				$answer .= $twitter_data->data_section('direct-messages', FALSE,  TRUE, TRUE);	
			}
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}	
?>
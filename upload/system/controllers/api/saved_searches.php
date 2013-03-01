<?php
	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
	require_once( $C->INCPATH.'classes/class_twitterdata.php' );
	
	$api_session = new stdClass;
	$api_session->format 	= $this->param('format');
	$api_session->callback	= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
	if( !$C->API_STATUS || $_SERVER['REQUEST_METHOD'] == 'COOKIE'){
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
			$resource = ($api_session->resource!='invalid')? ('saved_searches/'.$api_session->resource.$ares):'saved_searches';
			
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
	
	if(!$api_session->oauth_status && !$api_session->bauth_status)
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
			else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!is_valid_data_format($api_session->format))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}elseif(isset($api_session->resource) && $api_session->resource == 'create' ) 
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST' || !is_valid_data_format($api_session->format, TRUE)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}	
		}

		if(!isset($_POST['query']) || empty($_POST['query'])){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid query parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		$query	= trim(htmlspecialchars(urldecode($_POST['query'])));
				
		$this->db2->query('SELECT id, search_string, added_date FROM searches WHERE user_id="'.intval($this->db2->e($user->id)).'" AND search_string="'.$this->db2->e($query).'" LIMIT 1');
		if( $obj = $this->db2->fetch_object() ) {
			$obj->id			= intval($obj->id);
			$obj->search_string	= stripslashes($obj->search_string);
			$obj->added_date		= gmstrftime('%a %b %d %H:%M:%S +0000 %Y', $obj->added_date);
		}else {
			$obj	= new stdClass;
			$obj->search_string	= stripslashes($query);
			$obj->added_date		= gmstrftime('%a %b %d %H:%M:%S +0000 %Y', time());
			$tmp_url	= trim($C->SITE_URL,'/');
			$tmp_url	= str_replace(array('http://','https://'),'',$tmp_url);
			$tmp_url	= '/'.trim($tmp_url,'/').'/search/tab:posts/s:'.urlencode($query);
			$search_key	= md5($query."\n".serialize(array('link','image','video','file'))."\n\n\n".serialize('')."\n".serialize(''));

			$this->db2->query( 'INSERT INTO searches SET user_id="'.intval($this->db2->e($user->id)).'", search_key="'.$this->db2->e($search_key).'", search_string="'.$this->db2->e($query).'", search_url="'.$this->db2->e($tmp_url).'", added_date="'.time().'", total_hits=0, last_results=0' );
			$obj->id	= intval($this->db2->insert_id());
		}
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
		
			$answer .= $twitter_data->data_section('saved_search');
				$answer .= $twitter_data->data_field('id', $obj->id);
				$answer .= $twitter_data->data_field('query', $obj->search_string);
				$answer .= $twitter_data->data_field('created_at', $obj->added_date, FALSE);
			$answer .= $twitter_data->data_section('saved_search', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();
			
		echo $answer;
		exit;
	}
	elseif(isset($api_session->resource) &&  $api_session->resource == 'show' ) 
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST' || !is_valid_data_format($api_session->format, TRUE)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST or a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$this->db2->query('SELECT id, user_id, search_string, added_date FROM searches WHERE id="'.intval($this->db2->e($api_session->resource_option)).'" AND user_id="'.intval($this->db2->e($user->id)).'" LIMIT 1');
		if($obj = $this->db2->fetch_object()) {
			if($obj->user_id != $user->id){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'Invalid parameter provided.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			$obj->id			= intval($obj->id);
			$obj->search_string	= stripslashes($obj->search_string);
			$obj->added_date		= gmstrftime('%a %b %d %H:%M:%S +0000 %Y', $obj->added_date);		
			
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
		
			$answer .= $twitter_data->data_section('saved_search');
				$answer .= $twitter_data->data_field('id', $obj->id);
				$answer .= $twitter_data->data_field('query', $obj->search_string);
				$answer .= $twitter_data->data_field('created_at', $obj->added_date, FALSE);
			$answer .= $twitter_data->data_section('saved_search', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();

			echo $answer;
			exit;
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}
	elseif(isset($api_session->resource) &&  $api_session->resource == 'destroy' ) 
	{
		if(($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'DELETE') || (!is_valid_data_format($api_session->format, TRUE)) ){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}	
		}
		
		if((isset($api_session->resource_option) && !is_numeric($api_session->resource_option)) || !isset($api_session->resource_option)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Invalid parameter provided.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$this->db2->query('SELECT id, search_string, added_date FROM searches WHERE id="'.intval($this->db2->e($api_session->resource_option)).'" AND user_id="'.intval($this->db2->e($user->id)).'" LIMIT 1');
		if($obj = $this->db2->fetch_object()) {
			$obj->id			= intval($obj->id);
			$obj->search_string	= stripslashes($obj->search_string);
			$obj->added_date		= gmstrftime('%a %b %d %H:%M:%S +0000 %Y', $obj->added_date);
			$this->db2->query('DELETE FROM searches WHERE id="'.$this->db2->e($obj->id).'" LIMIT 1');
			
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
		
			$answer .= $twitter_data->data_section('saved_search');
				$answer .= $twitter_data->data_field('id', $obj->id);
				$answer .= $twitter_data->data_field('query', $obj->search_string);
				$answer .= $twitter_data->data_field('created_at', $obj->added_date, FALSE);
			$answer .= $twitter_data->data_section('saved_search', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();
			echo $answer;
			exit;
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not modified');
				else echo generate_error($api_session->format, 'No data deleted.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}
	elseif(isset($this->request[0]) && $this->request[0] == 'saved_searches')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' || !is_valid_data_format($api_session->format, TRUE)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$res = $this->db2->query('SELECT id, search_string, added_date FROM searches WHERE user_id="'.intval($this->db2->e($user->id)).'" ORDER BY id DESC');
		$num_rows = $this->db2->num_rows($res);
		if($num_rows > 0){	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			if($twitter_data->is_feed())
				while($stat = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($stat->pid);
			else{
				$answer .= $twitter_data->data_section('saved_searches', FALSE, FALSE, TRUE, ' type="array"');
				while($obj = $this->db2->fetch_object($res))
				{	
					$answer .= $twitter_data->data_section('saved_search');
						$answer .= $twitter_data->data_field('id', $obj->id);
						$answer .= $twitter_data->data_field('query', stripslashes($obj->search_string));
						$answer .= $twitter_data->data_field('created_at', gmstrftime('%a %b %d %H:%M:%S +0000 %Y', $obj->added_date), FALSE);
					$answer .= $twitter_data->data_section('saved_search', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
				$answer .= $twitter_data->data_section('saved_searches', FALSE,  TRUE, TRUE);
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
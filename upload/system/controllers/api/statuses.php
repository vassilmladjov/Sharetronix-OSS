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
	$api_session->without_users 		= '';
	$api_session->oauth_status 		= false;
	$api_session->rate_status 		= false;
	$api_session->bauth_status 		= false;
	$api_session->available_resources 	= array('public_timeline', 'user_timeline', 'mentions', 'update', 'destroy', 'friends', 'followers', 'friends_timeline', 'home_timeline', 
	'show', 'group_update', 'commented', 'private_mentions', 'private_destroy', 'private_comments', 'comments', 'replies', 'retweeted_by_me', 'retweeted_to_me',
	'retweets_of_me', 'retweet', 'retweets');
	$api_session->oauth_error 		= '';

	
	if( ($auth = prepare_request()) || ($auth = prepare_header()) )
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$ares = ($api_session->resource_option)? ('/'.$api_session->resource_option):'';
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);	
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/statuses/'.$api_session->resource.$ares.'.'.$api_session->format);
			if(isset($auth['oauth_version'])) $oauth_client->set_variable('version', '1.0');
			
			if($oauth_client->is_valid_get_resource_request()){
				if($auth['oauth_signature_method'] != 'HMAC-SHA1'){ 
					$api_session->oauth_error = 'Unsupported signature method'; 
				}elseif(!$oauth_client->decrypt_hmac_sha1()){ 
					$api_session->oauth_error = 'Invalid signature'; 
				}else{
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
	
	
	
	if(!is_valid_data_format($api_session->format)){	
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'invalid' || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid resource request', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'public_timeline')
	{
		
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
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
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' AND posts.group_id NOT IN('.implode(', ', $api_session->not_in_groups).') ') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (posts.group_id>0 OR posts.user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$res = $this->db2->query('SELECT posts.id AS pid, posts.user_id AS uid FROM posts, users WHERE users.id=posts.user_id AND posts.user_id<>0 AND users.avatar<>"" AND posts.api_id NOT IN(2, 6) '.$api_session->not_in_groups.$api_session->without_users.' ORDER BY pid DESC LIMIT 20');		
		$num_rows = $this->db2->num_rows($res);
		if($num_rows > 0){	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1, TRUE);
			$answer = $twitter_data->data_header();

			if($twitter_data->is_feed())
				while($stat = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($stat->pid);
			else{
				$answer .= $twitter_data->data_section('statuses', FALSE, FALSE, TRUE, ' type="array"');
					while($stat = $this->db2->fetch_object($res)){	
						$answer .= $twitter_data->data_section('status');
							$answer .= $twitter_data->print_status($stat->pid, TRUE);	
								
								$answer .= $twitter_data->data_section('user', TRUE);				
									$answer .=  $twitter_data->print_user($stat->uid);	
								$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
								
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);
						
						$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
						$num_rows--;
					}
				$answer .= $twitter_data->data_section('statuses', FALSE,  TRUE, TRUE);
			}	
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}	
	}elseif($api_session->resource == 'user_timeline')
	{	
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET or a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		$desired_user_id = find_user_id($api_session->resource_option);
		$desired_user_id = (!$desired_user_id)? $user->id:$desired_user_id;

		if(!$desired_user_id){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Invalid user screen name or id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;

		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).') ') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		
		}
		
		$q = 'SELECT id AS pid FROM posts WHERE user_id="'.intval($desired_user_id).'" '.$api_session->not_in_groups.$api_session->without_users.' AND api_id NOT IN(2, 6) ';
		if(isset($_REQUEST['since_id']) && is_numeric($_REQUEST['since_id'])) $q .= ' AND id>'.intval($this->db2->e($_REQUEST['since_id']));
		if(isset($_REQUEST['max_id']) && is_numeric($_REQUEST['max_id'])) $q .= ' AND id<'.intval($this->db2->e($_REQUEST['max_id']));	
		$q .= ' ORDER BY id DESC ';
		
		if(isset($_REQUEST['count']) && !isset($_REQUEST['page'])) 
			{if(is_numeric($_REQUEST['count']) && $_REQUEST['count']<200) $q .= ' LIMIT '.intval($this->db2->e($_REQUEST['count']));}
		elseif(isset($_REQUEST['page']) && !isset($_GET['count']))
			{if(is_numeric($_REQUEST['page'])) $q .= ' LIMIT '.(20)*(intval($this->db2->e($_REQUEST['page']))-1).', '.(20)*(intval($this->db2->e($_REQUEST['page'])));}
		elseif(isset($_REQUEST['page']) && isset($_REQUEST['count']))
			{if(is_numeric($_REQUEST['page']) && is_numeric($_REQUEST['count'])) 
				$q .= ' LIMIT '.(intval($this->db2->e($_REQUEST['count'])))*(intval($this->db2->e($_REQUEST['page']))-1).', '.(intval($this->db2->e($_REQUEST['count'])))*(intval($this->db2->e($_REQUEST['page'])));}
		else $q .= ' LIMIT 20';

		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);

		if($num_rows > 0){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $desired_user_id, TRUE);
			$answer = $twitter_data->data_header();
			
			if($twitter_data->is_feed())
				while($stat = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($stat->pid);
			else
			{
				$answer .= $twitter_data->data_section('statuses', FALSE, FALSE, TRUE, ' type="array"');
				while($stat = $this->db2->fetch_object($res)){	
					$answer .= $twitter_data->data_section('status');
						$answer .= $twitter_data->print_status($stat->pid, TRUE);	
							
							$answer .= $twitter_data->data_section('user', TRUE);				
								$answer .=  $twitter_data->print_user($desired_user_id);	
							$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
							
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
				$answer .= $twitter_data->data_section('statuses', FALSE,  TRUE, TRUE);
			}
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;	
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}elseif($api_session->resource == 'mentions' || $api_session->resource == 'private_mentions' || $api_session->resource == 'replies')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
			
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$table = ($api_session->resource == 'mentions' || $api_session->resource == 'replies')? 'posts_mentioned':'posts_pr_mentioned';

		$q = 'SELECT post_id FROM '.$table.' WHERE user_id="'.intval($user->id).'" ';
		if(isset($_GET['since_id']) && is_numeric($_GET['since_id'])) $q .= ' AND post_id>'.intval($this->db2->e($_GET['since_id']));
		if(isset($_GET['max_id']) && is_numeric($_GET['max_id'])) $q .= ' AND post_id<'.intval($this->db2->e($_GET['max_id']));	
		$q .= ' ORDER BY post_id DESC ';
		

		if(isset($_GET['count']) && !isset($_GET['page']))
			{if(is_numeric($_GET['count']) && $_GET['count']<200) $q .= ' LIMIT '.intval($this->db2->e($_GET['count']));}
		elseif(isset($_GET['page']) && !isset($_GET['count']))
			{if(is_numeric($_GET['page'])) $q .= ' LIMIT '.(20)*(intval($this->db2->e($_GET['page']))-1).', '.(20)*(intval($this->db2->e($_GET['page'])));}
		elseif(isset($_GET['page']) && isset($_GET['count']))
			{if(is_numeric($_GET['page']) && is_numeric($_GET['count'])) 
				$q .= ' LIMIT '.(intval($this->db2->e($_GET['count'])))*(intval($this->db2->e($_GET['page']))-1).', '.(intval($this->db2->e($_GET['count'])))*(intval($this->db2->e($_GET['page'])));}
		else $q .= ' LIMIT 20';
		
		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);
		

		if($num_rows > 0){		
			
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			$type = ($api_session->resource == 'mentions' || $api_session->resource == 'replies')? 'public':'private';	
			
			if($twitter_data->is_feed())
				while($mention = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($mention->post_id, $type);
			else{
				
				$answer .= $twitter_data->data_section('statuses', FALSE, FALSE, TRUE, ' type="array"');
				
					while($mention = $this->db2->fetch_object($res)){	
						
						$answer .= $twitter_data->data_section('status');						
						$answer .= $twitter_data->print_status($mention->post_id, TRUE, FALSE, $type);
						$answer .= $twitter_data->data_section('user', TRUE);	

						
						$uid = $this->db2->fetch_field('SELECT user_id FROM posts WHERE id="'.$this->db2->e($mention->post_id).'" LIMIT 1');
						
						$answer .=  $twitter_data->print_user($uid);
						$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);
						
						$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
						$num_rows--;
					}
					
				$answer .= $twitter_data->data_section('statuses', FALSE,  TRUE, TRUE);
			}
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;	
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}	
	}elseif($api_session->resource == 'update' || $api_session->resource == 'group_update')
	{
		
		if($_SERVER['REQUEST_METHOD'] != 'POST' || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem:'.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status){
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
		
		
		if(!isset($_POST['status']) || empty($_POST['status'])){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Empty status parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(isset($_POST['status']) && mb_strlen($_POST['status']) > 140){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Status could no be longer than 140 symbols.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}		
		$_POST['status'] = trim(stripslashes(htmlspecialchars(urldecode($_POST['status']))));
		
		
		if(!isset($_POST['in_reply_to_status_id'])) {
			$text = $this->db2->fetch_field('SELECT message FROM posts WHERE user_id ="'.intval($this->db2->e($user->id)).'" ORDER BY id DESC LIMIT 1');
			
			if(($text && $text != $_POST['status']) || !$text){
				$newpost	= new newpost();
				$newpost->set_api_id( $app_id );
				$newpost->set_message($_POST['status']);
				if($api_session->resource == 'group_update'){
					if(isset($api_session->resource_option) && is_numeric($api_session->resource_option)) $group_id = $api_session->resource_option;
					elseif(isset($api_session->resource_option)){
						$g = $network->get_group_by_name(urldecode($api_session->resource_option));
						if(!$g){
							if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
								else echo generate_error($api_session->format, 'Invalid group parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
							exit;
						}
						$group_id = $g->id;
					}else{
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'Group paramater required.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;	
					}
					
					if($user->if_follow_group($group_id)){
						$newpost->set_group_id($group_id);
					}else{
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'You are not a group member.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;
					}		
				}
				if(isset($_POST['link']) && is_valid_url(urldecode($_POST['link']))){
					if(!$newpost->attach_link(urldecode($_POST['link']))){
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'Invalid link.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;
					}
				}
				if(isset($_POST['video']) && !empty($_POST['video'])){
					if(!$newpost->attach_videoembed(urldecode($_REQUEST['video']))){
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'Invalid video link.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;
					}
				}
				if(isset($_POST['file']) && !empty($_POST['file']) && isset($_POST['file_type']) && !empty($_POST['file_type'])){
					$tmp	= $C->TMP_DIR.'tmp_'.md5(time().rand(0,9999));
					$fl = file_put_contents($tmp, base64_decode($_POST['file']));

					if(!$newpost->attach_file($tmp, $fl.'.'.$_POST['file_type'])){
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'Invalid file.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;
					}
				}
				if(isset($_POST['image']) && !empty($_POST['image'])){
					$fl	= $C->TMP_DIR.'tmp_'.md5(time().rand(0,9999));
					file_put_contents($fl, base64_decode($_POST['image']));
					
					if(!$newpost->attach_image($fl)){
						if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
							else echo generate_error($api_session->format, 'Invalid image file.', $_SERVER['REQUEST_URI'], $api_session->callback);
						exit;
					}
				}
					
				$ok	= $newpost->save();
				if( ! $ok ) {
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
						else echo generate_error($api_session->format, 'Server error (Stage 1).', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}
				else {
					$new_post = explode("_", $ok);
					
					$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
					$answer = $twitter_data->data_header();
		
					$answer .= $twitter_data->data_section('status');
						$answer .= $twitter_data->print_status(intval($new_post[0]), TRUE);	
							
							$answer .= $twitter_data->data_section('user', TRUE);				
								$answer .=  $twitter_data->print_user($user->id);	
							$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
							
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);
					$answer .= $twitter_data->data_bottom();

					echo $answer;
					exit;
				}
			}else{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'Provide diffrent status.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}elseif(isset($_POST['in_reply_to_status_id']) && is_numeric($_POST['in_reply_to_status_id']) && $_POST['in_reply_to_status_id']>0 )
		{
			$post	= new post('public', intval($_POST['in_reply_to_status_id']));
			
			if(!$post || !isset($post->post_user->id)){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'Invalid post id.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			
			$author = $this->network->get_user_by_id($post->post_user->id);
			if(!$author){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server error (Stage 2).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
			$mentioned = false;
			if($post->post_user->id == $user->id) $mentioned = true;
			elseif(preg_match('/@'.$author->username.'/iu', $_POST['status'])) $mentioned = true;
			elseif(preg_match('/@'.$user->info->username.'/iu', $post->post_message)) $mentioned = true;

			if($mentioned){	
				$last_status = $this->db2->fetch_field('SELECT message FROM posts_comments WHERE post_id="'.intval($this->db2->e($post->post_id)).'" AND user_id="'.intval($this->db2->e($user->id)).'" ORDER BY id DESC LIMIT 1');

				if($last_status && ($last_status == $_POST['status'])){
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
						else echo generate_error($api_session->format, 'Provide different comment.', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}
				
				$check_post = new post('public', intval($_POST['in_reply_to_status_id']));
				if(!$check_post || ($check_post->post_group && !$user->if_follow_group($check_post->post_group->id))){
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
						else echo generate_error($api_session->format, 'No such post or you are not a post group member.', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}
					
				$np = new newpostcomment($post);
				$np->set_api_id( $app_id );
				$np->set_message($_POST['status']);
				$result = $np->save();

				if( $result ) { 
					$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
					$answer = $twitter_data->data_header();
		
					$answer .= $twitter_data->data_section('status');
						$answer .= $twitter_data->print_status(intval($_POST['in_reply_to_status_id']), TRUE);	
							
							$answer .= $twitter_data->data_section('user', TRUE);				
								$answer .=  $twitter_data->print_user($user->id);	
							$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
							
					$answer .= $twitter_data->data_section('status', FALSE, TRUE);
					$answer .= $twitter_data->data_bottom();
					
					echo $answer;
					exit;
				}else {
					if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
						else echo generate_error($api_session->format, 'Server error (Stage 4).', $_SERVER['REQUEST_URI'], $api_session->callback);
					exit;
				}
			}else{
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'Not mentioned in author\'s post.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}		
	}elseif($api_session->resource == 'destroy' || $api_session->resource == 'private_destroy')
	{
		if(($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'DELETE') || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a POST or a DELETE.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!isset($api_session->resource_option) || !is_numeric($api_session->resource_option)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'Incorrect status id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}

		$post_type = ($api_session->resource == 'destroy')? 'public':'private';
		$post	= new post($post_type, intval($api_session->resource_option));
		
		if(!$post || !isset($post->post_user->id)){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'No such post.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		if( $post->post_user->id == $user->id){	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();

			$answer .= $twitter_data->data_section('status');
				$answer .= $twitter_data->print_status(intval($post->post_id), TRUE);	
					
					$answer .= $twitter_data->data_section('user', TRUE);				
						$answer .=  $twitter_data->print_user($post->post_user->id);	
					$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
					
			$answer .= $twitter_data->data_section('status', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();

			if($post->delete_this_post()){
				echo $answer;
				exit;
			}else{
				if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
					else echo generate_error($api_session->format, 'Server Error (Stage 5).', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
				else echo generate_error($api_session->format, 'You are not the author of the post.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}elseif($api_session->resource == 'friends' || $api_session->resource == 'followers')
	{
		if(($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST') || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a POST or a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$desired_user_id = find_user_id($api_session->resource_option);
		$desired_user_id = (!$desired_user_id)? $user->id:$desired_user_id;
		
		if(!$desired_user_id){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
				else echo generate_error($api_session->format, 'Bad user data provided.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$info	= $this->network->get_user_follows($desired_user_id);
		if(!$info) {
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
				else echo generate_error($api_session->format, 'Server error (Stage 6).', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		else {
			$followers	= array_keys($info->followers);
			$following	= array_keys($info->follow_users);
		}
		if($api_session->resource == 'friends') $users = &$following;
		else 	$users = &$followers;

		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? ('AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$num_rows = count($users);
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $desired_user_id, TRUE);
		$answer = $twitter_data->data_header();
		$answer .= $twitter_data->data_section('users_list', FALSE, FALSE, TRUE, ' type="array"');
			$answer .= $twitter_data->data_section('users', FALSE, FALSE);
			foreach($users as $id){	
				$answer .= $twitter_data->data_section('user', TRUE);
					$answer .=  $twitter_data->print_user($desired_user_id);		
						$answer .= ($api_session->format == 'json')? ',':''; 
						
						$answer .= $twitter_data->data_section('status', TRUE);
						
							$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($this->db2->e($desired_user_id)).'" AND api_id NOT IN(2,6) '.$api_session->not_in_groups.$api_session->without_users.' ORDER BY id DESC LIMIT 1');					
							$answer .= $twitter_data->print_status($sid);
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);	
						
				$answer .= $twitter_data->data_section('user', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;
			}
			$answer .= $twitter_data->data_section('users', FALSE, TRUE);
		$answer .= $twitter_data->data_section('users_list', FALSE,  TRUE, TRUE);
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;
			
	}elseif($api_session->resource == 'friends_timeline' || $api_session->resource == 'home_timeline')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'This method requires a POST or a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$info	= $this->network->get_user_follows($user->id);
		if(!$info) {
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 500 Internal Server Error');
				else echo generate_error($api_session->format, 'Server error (Stage 7).', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$following	= array_keys($info->follow_users);
		if(!$following) $following = array($user->id);
		else $following[] = $user->id;
	
		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? ('AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$q = 'SELECT id AS pid, user_id AS uid FROM posts WHERE api_id NOT IN(2,6) '.$api_session->not_in_groups.$api_session->without_users.' AND user_id IN('.implode(',', $following).') ';
		
		if(isset($_REQUEST['since_id']) && is_numeric($_REQUEST['since_id'])) $q .= ' AND id>'.intval($this->db2->e($_REQUEST['since_id']));
		if(isset($_REQUEST['max_id']) && is_numeric($_REQUEST['max_id'])) $q .= ' AND id<'.intval($this->db2->e($_REQUEST['max_id']));
		
		$q .= ' ORDER BY id DESC ';
		
		if(isset($_REQUEST['count']) && !isset($_REQUEST['page']))
			{if(is_numeric($_REQUEST['count']) && $_REQUEST['count']<200) $q .= ' LIMIT '.intval($this->db2->e($_REQUEST['count']));}
		elseif(isset($_REQUEST['page']) && !isset($_REQUEST['count']))
			{if(is_numeric($_REQUEST['page'])) $q .= ' LIMIT '.(20)*(intval($this->db2->e($_REQUEST['page']))-1).', '.(20)*(intval($this->db2->e($_REQUEST['page'])));}
		elseif(isset($_REQUEST['page']) && isset($_REQUEST['count']))
			{if(is_numeric($_REQUEST['page']) && is_numeric($_REQUEST['count'])) 
				$q .= ' LIMIT '.(intval($this->db2->e($_REQUEST['count'])))*(intval($this->db2->e($_REQUEST['page']))-1).', '.(intval($this->db2->e($_REQUEST['count'])))*(intval($this->db2->e($_REQUEST['page'])));}
		else $q .= ' LIMIT 20';

		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			if($twitter_data->is_feed())
				while($stat = $this->db2->fetch_object($res)) 
					$answer .= $twitter_data->print_status_simple($stat->pid, 'public');
			else{
				$answer .= $twitter_data->data_section('statuses', FALSE, FALSE, TRUE, ' type="array"');
					while($stat = $this->db2->fetch_object($res)){	
						$answer .= $twitter_data->data_section('status');
							$answer .= $twitter_data->print_status($stat->pid, TRUE);	
								
								$answer .= $twitter_data->data_section('user', TRUE);				
									$answer .=  $twitter_data->print_user($stat->uid);	
								$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
								
						$answer .= $twitter_data->data_section('status', FALSE, TRUE);
						
						$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
						$num_rows--;
					}
				$answer .= $twitter_data->data_section('statuses', FALSE,  TRUE, TRUE);
			}
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No posts found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	
	}elseif($api_session->resource == 'home_timeline_real')
	{
		//to do: user_timeline + retweets
	}elseif($api_session->resource == 'show')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(isset($api_session->resource_option) && is_numeric($api_session->resource_option)) $post_id = $api_session->resource_option;
		else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Incorrect post id paramater.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? ('AND group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		$res = $this->db2->query('SELECT id AS pid, user_id AS uid FROM posts WHERE id="'.intval($this->db2->e($post_id)).'" AND api_id NOT IN(2,6) AND user_id<>0 '.$api_session->not_in_groups.$api_session->without_users.' LIMIT 1');	
		
		if($res = $this->db2->fetch_object($res)){	
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $res->uid);
			$answer = $twitter_data->data_header();

			$answer .= $twitter_data->data_section('status');
				$answer .= $twitter_data->print_status(intval($res->pid), TRUE);	
					
					$answer .= $twitter_data->data_section('user', TRUE);				
						$answer .=  $twitter_data->print_user($res->uid);	
					$answer .= $twitter_data->data_section('user', FALSE, TRUE);	
					
			$answer .= $twitter_data->data_section('status', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}	
	}elseif($api_session->resource == 'commented')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if(!isset($_GET['type']) || ($_GET['type']!='private' && $_GET['type']!='public')){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Post type paramater required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;	
		}
			
		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? ('AND posts.group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (posts.group_id>0 OR posts.user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		if($_GET['type']=='public'){
			$q = 'SELECT posts_comments.post_id, posts.id, posts.user_id FROM posts_comments, posts WHERE posts_comments.post_id = posts.id AND posts.user_id='.intval($user->id).' '.$api_session->not_in_groups.$api_session->without_users.' GROUP BY post_id ORDER BY posts.id DESC LIMIT 20';
		}else{
			$q = 'SELECT posts_pr_comments.post_id, posts.id, posts.user_id, posts.date AS pdate FROM posts_pr_comments, posts WHERE posts_pr_comments.post_id = posts.id AND posts.user_id='.intval($user->id).' '.$api_session->not_in_groups.$api_session->without_users.' GROUP BY post_id ORDER BY posts.id DESC LIMIT 20';
		}
		
		$res = $this->db2->query($q);
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			$answer .= $twitter_data->data_section('statuses', FALSE, FALSE, TRUE, ' type="array"');
				while($stat = $this->db2->fetch_object($res)){	
					$answer .= $twitter_data->data_section('post', FALSE);		
						$answer .= $twitter_data->data_field('id', $stat->post_id, FALSE);				
					$answer .= $twitter_data->data_section('post', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
			$answer .= $twitter_data->data_section('statuses', FALSE,  TRUE, TRUE);
			$answer .= $twitter_data->data_bottom();

			echo $answer;
			exit;	
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}elseif($api_session->resource == 'comments' || $api_session->resource == 'private_comments')
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' || (!is_valid_data_format($api_session->format, TRUE))){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error('xml', 'Invalid request method or requested data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	
		if(!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Post parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;	
		}
		
		if($api_session->oauth_status && $oauth_client->check_rate_limits($user->id)) $api_session->rate_status = true;
		elseif(check_rate_limits($_SERVER['REMOTE_ADDR'])) $api_session->rate_status = true;
		
		if(!$api_session->rate_status){
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You have no available rate limits, try again later.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}	
			
		if( !$user->info->is_network_admin ) {
			$api_session->not_in_groups	= array();
			$api_session->not_in_groups 	= not_in_groups();
			$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? ('AND posts.group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
			
			$api_session->without_users = protected_users();
			$api_session->without_users = count($api_session->without_users)>0 ? (' AND (posts.group_id>0 OR posts.user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		}
		
		if($api_session->resource == 'comments'){
			$q = 'SELECT posts_comments.id AS cid, posts_comments.message AS mtext FROM posts_comments, posts WHERE posts.user_id='.intval($user->id).' AND posts_comments.post_id='.intval($_GET['post_id']).' '.$api_session->not_in_groups.$api_session->without_users.' GROUP BY posts_comments.id ORDER BY posts_comments.id DESC LIMIT 20';
		}else{
			$q = 'SELECT posts_pr_comments.id AS cid, posts_pr_comments.message AS mtext FROM posts_pr_comments, posts_pr WHERE posts_pr.user_id='.intval($user->id).' AND posts_pr.id='.intval($_GET['post_id']).' '.$api_session->not_in_groups.$api_session->without_users.' GROUP BY posts_pr_comments.id ORDER BY posts_pr_comments.id DESC LIMIT 20';
		}

		$res = $this->db2->query($q); 
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			$answer .= $twitter_data->data_section('comments', FALSE, FALSE, TRUE, ' type="array"');
				while($stat = $this->db2->fetch_object($res)){	
					$answer .= $twitter_data->data_section('post');		
						$answer .= $twitter_data->data_field('id', $stat->cid);	
						$answer .= $twitter_data->data_field('text', $stat->mtext, FALSE);	
					$answer .= $twitter_data->data_section('post', FALSE, TRUE);
					
					$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
					$num_rows--;
				}
			$answer .= $twitter_data->data_section('comments', FALSE,  TRUE, TRUE);
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;		
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}elseif( $api_session->resource == 'retweeted_by_me' )
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'retweeted_to_me' )
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'retweets_of_me' )
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'retweet' )
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'retweets' )
	{
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}
?>
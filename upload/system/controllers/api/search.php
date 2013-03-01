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
	$api_session->oauth_status 		= false;
	$api_session->rate_status 		= false;
	$api_session->bauth_status 		= false;
	$api_session->oauth_error 		= '';
		
	if($_SERVER['REQUEST_METHOD'] != 'GET')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error('xml', 'This method requires a GET.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}	
	
	/*
		Warning:  The user ids in the Search API are different from those in the REST API (about the two APIs).
		This defect is being tracked by Issue 214. This means that the to_user_id and from_user_id field vary
		from the actualy user id on Twitter.com. Applications will have to perform a screen name-based lookup
		with the users/show method to get the correct user id if necessary.
	*/
	
	$search_string	= isset($_GET['q']) ? trim(urldecode($_GET['q'])) : '';
	$num_per_page	= isset($_GET['rpp']) ? min(100,max(1,intval($_GET['rpp']))) : 15;
	$pg			= isset($_GET['page']) ? min(ceil(1500/$num_per_page),max(1,intval($_GET['page']))) : 1;
	$since_dt		= isset($_GET['since'])&& preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $_GET['since']) ? $_GET['since'] : FALSE;
	$since_id		= isset($_GET['since_id']) ? max(1,intval($_GET['since_id'])) : FALSE;
	$until_dt		= isset($_GET['until'])&&preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $_GET['until']) ? $_GET['until'] : FALSE;
	$until_id		= isset($_GET['max_id']) ? max(1,intval($_GET['max_id'])) : FALSE;
	

	if( ($auth = prepare_request()) || ($auth = prepare_header()) )
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);

			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/search.'.$api_session->format);
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
						$api_session->oauth_status = true;
						$user->is_logged 			= true;
						$user->id 				= $id;
						$user->info 			= new stdClass;
						$user->info->id 			= $id;
						$user->info->is_network_admin = $u->is_network_admin;
						$user->info->is_posts_protected 	= $u->is_posts_protected;
						$user->info->username 		= $u->username;
						$user->info->network_id 	= $u->network_id;
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
				$api_session->bauth_status 	= true;
				$user->is_logged 			= true;
				$user->id 				= $u->id;
				$user->info 			= new stdClass;
				$user->info->id 			= $u->id;
				$user->info->is_network_admin = $u->is_network_admin;
				$user->info->is_posts_protected 	= $u->is_posts_protected;
				$user->info->username 		= $u->username;
				$user->info->network_id 	= $u->network_id;
			}
			unset($u, $obj);
		}
	}
	
	if(!$api_session->oauth_status && !$api_session->bauth_status){
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
			else echo generate_error($api_session->format, 'OAuth otorization problem:'.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}
	
	if( empty($search_string) || strlen($search_string) > 140) {
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error($api_session->format, 'Invalid string parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}
	
	$in_sql	= array();
	
	if( FALSE !== $until_id && FALSE!==$since_id ) {
		$in_sql[]	= 'p.id BETWEEN '.$since_id.' AND '.$until_id;
	}
	elseif( FALSE !== $until_id ) {
		$in_sql[]	= 'p.id<='.$until_id;
	}
	elseif( FALSE !== $since_id ) {
		$in_sql[]	= 'p.id>='.$since_id;
	}
	
	$in_sql[]	= 'user_id<>0';
		
	if( !$user->info->is_network_admin ) {
		$api_session->not_in_groups	= array();
		$api_session->not_in_groups 	= not_in_groups();
		$api_session->not_in_groups	= count($api_session->not_in_groups)>0 ? (' group_id NOT IN('.implode(', ', $api_session->not_in_groups).')') : '';
		if(count($api_session->not_in_groups) > 0) $in_sql[] = $api_session->not_in_groups;
		
		$api_session->without_users = protected_users();
		$api_session->without_users = count($api_session->without_users)>0 ? (' AND (group_id>0 OR user_id NOT IN('.implode(', ', $api_session->without_users).')) ') : '';
		if(count($api_session->without_users)>0)	$in_sql[] = $api_session->without_users;
	}

	if( $since_dt !== FALSE ) {
		$tmp	= mktime(0, 0, 1, intval(substr($since_dt,0,4), intval(substr($since_dt,5,2), intval(substr($since_dt,8,2)))));
		$since_dt	= FALSE;
		if( $tmp && $tmp < time() ) {
			$since_dt	= $tmp;
		}
	}
	if( $until_dt !== FALSE ) {
		$tmp	= mktime(0, 0, 1, intval(substr($until_dt,0,4), intval(substr($until_dt,5,2), intval(substr($until_dt,8,2)))));
		$until_dt	= FALSE;
		if( $tmp && $tmp < time() ) {
			$until_dt	= $tmp;
		}
	}
	if( FALSE !== $until_dt && FALSE!==$since_dt ) {
		$in_sql[]	= 'p.date BETWEEN '.$since_dt.' AND '.$until_dt;
	}
	elseif( FALSE !== $until_dt ) {
		$in_sql[]	= 'p.date<='.$until_dt;
	}
	elseif( FALSE !== $since_dt ) {
		$in_sql[]	= 'p.date>='.$since_dt;
	}
	
	$tmp	= str_replace(array('%','_'), array('\%','\_'), $this->db2->e($search_string));
	if( $tmp != '#' ) {
		$tmp	= preg_replace('/^\#/', '', $tmp);
	}
	if( mb_strlen($search_string)>=3 && FALSE!==strpos($search_string,' ') ) {
		$tmp2	= preg_replace('/[^א-תÀ-ÿ一-龥а-яa-z0-9\s]/iu', '', $tmp2);
		$tmp2	= $this->db2->e($tmp2);
		$tmp2	= preg_replace('/\s+/iu', ' ', $tmp2);
		$tmp2	= preg_replace('/(^|\s)/iu', ' +', $tmp2);
		$tmp2	= trim($tmp2);
		$tmp2	= '';
		$in_sql[]	= '(MATCH(p.message) AGAINST("'.$tmp2.'" IN BOOLEAN MODE) OR p.message LIKE "%'.$tmp.'%")';
	}
	else {
		$in_sql[]	= 'p.message LIKE "%'.$tmp.'%"';
	}
	
	$in_sql	= implode(' AND ', $in_sql);
	
	$num_results	= $this->db2->fetch_field('SELECT COUNT(id) FROM posts p WHERE '.$in_sql);
	$num_pages		= ceil($num_results / $num_per_page);
	$num_pages 		= (!$num_pages)?1:$num_pages;
	$pg			= min($pg, $num_pages);
	$from			= ($pg - 1) * $num_per_page;

	$results	= array();
	$tmp	= $this->db2->query('SELECT p.*, "public" AS `type` FROM posts p WHERE '.$in_sql.' ORDER BY p.id DESC LIMIT '.$from.', '.$num_per_page);
	$num_rows = $this->db2->num_rows($tmp);
	
	$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
	$answer = $twitter_data->data_header();

	$answer .= $twitter_data->data_section('results',FALSE, FALSE, TRUE, ' type="array"');

		while($obj = $this->db2->fetch_object($tmp)) 
		{
			$answer .=  $twitter_data->data_section('result', FALSE);	
				$answer .=  $twitter_data->data_field('to_user_id', $obj->id);		
				$answer .=  $twitter_data->data_field('to_user', $C->SITE_TITLE.' API');
				$answer .=  $twitter_data->data_field('text', htmlspecialchars($obj->message), FALSE);
			$answer .=  $twitter_data->data_section('result', FALSE, TRUE);
			
			$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
			$num_rows--;
		}	
			
	$answer .= $twitter_data->data_section('results', FALSE,  TRUE, TRUE);
	$answer .= $twitter_data->data_bottom();

	echo $answer;
	exit;	
	
?>
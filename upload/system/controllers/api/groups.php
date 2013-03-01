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
	//$user						= new stdClass;
	$user = new user();
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
	$api_session->available_resources 	= array('follow', 'unfollow', 'membership', 'all_groups', 'create', 'destroy');
	$api_session->oauth_error 		= '';
	
	if( ($auth = prepare_header()) || ($auth = prepare_request()) )
	{
		if(isset($auth['oauth_version']) && $auth['oauth_version'] != '1.0') $api_session->oauth_error = 'Not supported OAuth version';
		elseif(isset($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'],$auth['oauth_signature_method'], $auth['oauth_signature'], $auth['oauth_timestamp']))
		{
			$oauth_client = new OAuth($auth['oauth_consumer_key'], $auth['oauth_nonce'], $auth['oauth_token'], $auth['oauth_timestamp'], $auth['oauth_signature']);
				
			$oauth_client->set_variable('stage_url', $C->SITE_URL.'1/groups/'.$api_session->resource.'.'.$api_session->format);
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
			$u = $this->network->get_user_by_id($obj->id, true);
			
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

	if(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(($api_session->resource == 'follow' || $api_session->resource == 'unfollow') && isset($api_session->resource_option))
	{
		
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
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

		if(is_numeric($api_session->resource_option)) $group_id = intval($api_session->resource_option);
		else{
			$res = $this->db2->query('SELECT id FROM groups WHERE groupname="'.$this->db2->e(urldecode($api_session->resource_option)).'" LIMIT 1');
			if(!$group_id = $this->db2->fetch_field('SELECT id FROM groups WHERE groupname="'.$this->db2->e(urldecode($api_session->resource_option)).'" LIMIT 1')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
					else echo generate_error($api_session->format, 'Invalid group name.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
		
		
		if($api_session->resource == 'follow') {
			$ok = $user->follow_group($group_id);
		} elseif($api_session->resource == 'unfollow') {
			$ok = $user->follow_group($group_id, FALSE);
		}

		if($ok){
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
	
			$answer .= $twitter_data->data_section('user');
				$answer .=  $twitter_data->print_user($user->id);		
				$answer .= ($api_session->format == 'json')? ',':''; 
				
				$answer .= $twitter_data->data_section('status', TRUE);
					$sid = $this->db2->fetch_field('SELECT id AS pid FROM posts WHERE user_id="'.intval($user->id).'" AND api_id NOT IN(2,6) ORDER BY id DESC LIMIT 1');				
					$answer .=  $twitter_data->print_status($sid);
				$answer .= $twitter_data->data_section('status', FALSE, TRUE);	
					
			$answer .= $twitter_data->data_section('user', FALSE, TRUE);
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'You can not follow/unfollow this group.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}		
	} elseif(($api_session->resource == 'membership'))
	{
		
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		if(isset($api_session->resource_option) && is_numeric($api_session->resource_option)) $gid = intval($api_session->resource_option);
		elseif(isset($api_session->resource_option)){
			if(!$gid = $this->db2->fetch_field('SELECT id FROM groups WHERE groupname="'.$this->db2->e($api_session->resource_option).'" AND is_public LIMIT 1')){
				if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
					else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
		else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Parameter required.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		$res = $this->db2->query('SELECT groups_followed.user_id FROM groups_followed, groups, users WHERE groups_followed.user_id=users.id AND groups_followed.group_id=groups.id AND groups_followed.group_id="'.intval($this->db2->e($gid)).'"');
		$num_rows = $this->db2->num_rows($res);
		
		if($num_rows > 0)
		{
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id, TRUE);
			$answer = $twitter_data->data_header();
			
			$answer .= $twitter_data->data_section('users', FALSE, FALSE, TRUE, ' type="array"');
			while($obj = $this->db2->fetch_object($res))
			{				

				$answer .= $twitter_data->data_section('user');		
					$answer .= $twitter_data->data_field('id', $obj->user_id, FALSE);		
				$answer .= $twitter_data->data_section('user', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;	
			}
			$answer .= $twitter_data->data_section('users', FALSE,  TRUE, TRUE);
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, 'No results found.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	
	}elseif(($api_session->resource == 'all_groups'))
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem: '.$api_session->oauth_error, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$res = $this->db2->query('SELECT id, groupname FROM groups WHERE is_public=1');
		$num_rows = $this->db2->num_rows($res);
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1, TRUE);
			$answer = $twitter_data->data_header();
			$answer .= $twitter_data->data_section('groups', FALSE, FALSE, TRUE, ' type="array"');
			
			while($obj = $this->db2->fetch_object($res))
			{	
				$answer .= $twitter_data->data_section('group');
					$answer .= $twitter_data->data_field('id', $obj->id);
					$answer .= $twitter_data->data_field('name', $obj->groupname, FALSE);
				$answer .= $twitter_data->data_section('group', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;		
			}
			$answer .= $twitter_data->data_section('groups', FALSE,  TRUE, TRUE);		
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;
					
	}elseif($api_session->resource == 'create')
	{
		
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
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
		
		
		$error	= FALSE;
		$errmsg	= '';
		
		if(isset($_POST['title'], $_POST['groupname'], $_POST['description'], $_POST['type'])&& ($_POST['type'] == 'private' || $_POST['type'] == 'public')){
			$form_title		= trim(htmlspecialchars(urldecode($_POST['title'])));
			$form_groupname	= trim(htmlspecialchars(urldecode($_POST['groupname'])));
			$form_description	= mb_substr(trim(htmlspecialchars(urldecode($_POST['description']))), 0, $C->POST_MAX_SYMBOLS);
			$form_type		= trim(htmlspecialchars(urldecode($_POST['type'])))=='private' ? 'private' : 'public';
		}else{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid group parameter.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
		
		if( mb_strlen($form_title)<3 || mb_strlen($form_title)>30 ) {
			$error	= TRUE;
			$errmsg	= 'Not valid group name.';
		}
		elseif( preg_match('/[^א-תÀ-ÿ一-龥а-яa-z0-9\-\.\s]/iu', $form_title) ) {
			$error	= TRUE;
			$errmsg	= 'Invalid group\'s name characters';
		}
		else {
			$this->db2->query('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($form_title).'" OR title="'.$this->db2->e($form_title).'") LIMIT 1');
			if( $this->db2->num_rows() > 0 ) {
				$error	= TRUE;
				$errmsg	= 'Provide different group name.';
			}
		}
		
		if( !$error ) {
			if( ! preg_match('/^[a-z0-9\-\_]{3,30}$/iu', $form_groupname) ) {
				$error	= TRUE;
				$errmsg	= 'Invalid group name.';
			}
			else {
				if( $res =  $this->db2->fetch_field('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($form_groupname).'" OR title="'.$this->db2->e($form_groupname).'") LIMIT 1')) {
					$error	= TRUE;
					$errmsg	= 'Provide different group name.';
				}
				else {
					if( $res = $this->db2->fetch_field('SELECT id FROM users WHERE username="'.$this->db2->e($form_groupname).'" LIMIT 1') ) {
						$error	= TRUE;
						$errmsg	= 'Provide different group name.';
					}
					elseif( file_exists($C->INCPATH.'controllers/'.strtolower($form_groupname).'.php') ) {
						$error	= TRUE;
						$errmsg	= 'Provide different group name.';
					}
					elseif( file_exists($C->INCPATH.'controllers/mobile/'.strtolower($form_groupname).'.php') ) {
						$error	= TRUE;
						$errmsg	= 'Provide different group name.';
					}
				}
			}
		}else{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, $errmsg, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	
		if( ! $error ) {
			$this->db2->query('INSERT INTO groups SET groupname="'.$this->db2->e($form_groupname).'", title="'.$this->db2->e($form_title).'", about_me="'.$this->db2->e($form_description).'", is_public="'.($form_type=='public'?1:0).'"');
			
			$g = $this->network->get_group_by_id(intval($this->db2->insert_id()));
			
			$this->db2->query('INSERT INTO groups_admins SET group_id="'.$g->id.'", user_id="'.$user->id.'" ');
			if( $g->is_private ) {
				$this->db2->query('INSERT INTO groups_private_members SET group_id="'.$g->id.'", user_id="'.$user->id.'", invited_by="'.$user->id.'", invited_date="'.time().'"');
			}

			$ok = $user->follow_group($g->id);	
			
			$res = $this->db2->query('SELECT groupname AS gn, group_id AS gi FROM groups, groups_admins WHERE user_id ='.intval($user->id).' ORDER BY groups_admins.id DESC LIMIT 1');
			$obj = $this->db2->fetch_object($res);
			
			$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
			$answer = $twitter_data->data_header();
			
				$answer .= $twitter_data->data_section('group');	
					$answer .= $twitter_data->data_field('id', $obj->gi);	
					$answer .= $twitter_data->data_field('name', $obj->gn);					
					
					$answer .= $twitter_data->data_section('user', TRUE);						
						$answer .=  $twitter_data->print_user($user->id);		
					$answer .= $twitter_data->data_section('user', FALSE, TRUE);					
				$answer .= $twitter_data->data_section('group', FALSE, TRUE);	
					
			$answer .= $twitter_data->data_bottom();
			
			echo $answer;
			exit;
		}else{
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
				else echo generate_error($api_session->format, $errmsg, $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}
	}elseif(($api_session->resource == 'destroy'))
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Invalid request method.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif(!$api_session->oauth_status && !$api_session->bauth_status){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 401 Unauthorized');
				else echo generate_error($api_session->format, 'OAuth otorization problem.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}elseif($api_session->oauth_status){
			if(!$oauth_client->check_access_type('rw')){
				if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Forbidden');
					else echo generate_error($api_session->format, 'You have no permission for this action.', $_SERVER['REQUEST_URI'], $api_session->callback);
				exit;
			}
		}
		
		if(!isset($api_session->resource_option) || !is_numeric($api_session->resource_option)){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
				else echo generate_error($api_session->format, 'Provide valid group id.', $_SERVER['REQUEST_URI'], $api_session->callback);
			exit;
		}

		$res = $this->db2->query('SELECT 1 FROM groups_admins WHERE group_id='.intval($api_session->resource_option).' AND user_id ='.intval($user->id).' LIMIT 1');
		if($this->db2->num_rows($res) != 1){
			if(!isset($_POST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'You are not admin or only admin. You can not delete this group.', $_SERVER['REQUEST_URI'], $api_session->callback);
			
			exit;
		}

		ini_set('max_execution_time', 10*60*60);

		$r	= $this->db2->query('SELECT * FROM posts WHERE group_id="'.intval($api_session->resource_option).'" ORDER BY id ASC');
		while($obj = $this->db2->fetch_object($r)) 
		{
			$p	= new post('public', FALSE, $obj);
			if( $p->error ) { continue; }
			$p->delete_this_post();
		}
		$r	= $this->db2->query('SELECT id FROM groups_rssfeeds WHERE group_id="'.intval($api_session->resource_option).'" ');
		while($obj = $this->db2->fetch_object($r)) 
		{
			$this->db2->query('DELETE FROM groups_rssfeeds_posts WHERE rssfeed_id="'.$obj->id.'" ');
		}
		$this->db2->query('DELETE FROM groups_rssfeeds WHERE group_id="'.intval($api_session->resource_option).'" ');

		$r	= $this->db2->query('SELECT * FROM posts WHERE user_id="0" AND group_id="'.intval($api_session->resource_option).'" ORDER BY id ASC');
		while($obj = $this->db2->fetch_object($r)) 
		{
			$p	= new post('public', FALSE, $obj);
			if( $p->error ) { continue; }
			$p->delete_this_post();
		}
		$f	= array_keys($this->network->get_group_members(intval($api_session->resource_option)));
		$this->db2->query('DELETE FROM groups_followed WHERE group_id="'.intval($api_session->resource_option).'" ');
		$this->db2->query('DELETE FROM groups_private_members WHERE group_id="'.intval($api_session->resource_option).'" ');
		$this->db2->query('DELETE FROM groups_admins WHERE group_id="'.intval($api_session->resource_option).'" ');
		$this->db2->query('UPDATE groups_rssfeeds SET is_deleted=1 WHERE group_id="'.intval($api_session->resource_option).'" ');
		foreach($f as $uid) 
		{
			$this->network->get_user_follows($uid, TRUE);
		}
		$this->db2->query('INSERT INTO groups_deleted (id, groupname, title, is_public) SELECT id, groupname, title, is_public FROM groups WHERE id="'.intval($api_session->resource_option).'" LIMIT 1');
		$this->db2->query('DELETE FROM groups WHERE id="'.intval($api_session->resource_option).'" LIMIT 1');

		$res = $this->db2->query('SELECT id, groupname, title FROM groups_deleted WHERE id='.intval($api_session->resource_option).' LIMIT 1');
		$gr = $this->db2->fetch_object($res);	
		
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, $user->id);
		$answer = $twitter_data->data_header();

		$answer .= $twitter_data->data_section('group');
			
			$answer .=  $twitter_data->data_field('id', $gr->id);		
			$answer .=  $twitter_data->data_field('name', $gr->groupname);
			$answer .=  $twitter_data->data_field('title', $gr->title, FALSE);	
				
		$answer .= $twitter_data->data_section('group', FALSE, TRUE);
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;	
	}
?>
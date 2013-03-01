<?php
	require_once( $C->INCPATH.'helpers/func_api.php' );
	//require_once( $C->INCPATH.'classes/class_oauth.php' );
	require_once( $C->INCPATH.'classes/class_twitterdata.php' );

	$api_session = new stdClass;	
	$api_session->format 			= $this->param('format');
	$api_session->callback			= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
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
	$api_session->available_resources 	= array('top10', 'current', 'daily', 'weekly', 'location', 'available');
	$api_session->oauth_error 		= '';
			
	if($_SERVER['REQUEST_METHOD'] != 'GET')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error('xml', 'Invalid request method or data format.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_GET['suppress_response_codes'])) header('HTTP/1.1 403 Forbidden');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);

		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'top10' ) 
	{
		$dts	= @gmmktime(0, 0, 1, gmdate('m'), gmdate('d'), gmdate('Y'), time()-24*60*60);
		$msg	= array();
		$this->db2->query('SELECT message FROM posts WHERE user_id<>0 AND posttags>0 AND date>'.$dts);
		
		while($obj = $this->db2->fetch_object()) $msg[]	= stripslashes($obj->message);
		
		$data	= array();
		foreach($msg as $m) {
			if( ! preg_match_all('/\#([א-תÀ-ÿ一-龥а-яa-z0-9\-_]{1,50})/iu', $m, $matches, PREG_PATTERN_ORDER) ) {
				continue;
			}
			foreach($matches[1] as $tg) {
				$tg	= trim($tg);
				if( ! isset($data[$tg]) ) {
					$data[$tg]	= 0;
				}
				$data[$tg]	++;
			}
		}
		
		$result	= new stdClass;
		$result->trends	= array();
		$result->as_of	= gmstrftime('%a, %d %b %Y %H:%M:%S +0000', $dts);
		foreach($data as $k=>$v) {
			$result->trends[]	= (object) array(
				'name'	=> '#'.$tg,
				'url'		=> $C->SITE_URL.'search/tab:posts/s:%23'.urlencode($tg)
			);
		}
		
		$num_rows = count($result->trends);
	
		$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1, TRUE);
		$answer = $twitter_data->data_header();
	
		$answer .= $twitter_data->data_section('trends', FALSE, FALSE, TRUE, ' type="array"');
	
			foreach($result->trends as $tr)
			{
				$answer .=  $twitter_data->data_section('trend', FALSE);	
					$answer .=  $twitter_data->data_field('name', $tr->name);		
					$answer .=  $twitter_data->data_field('url', $tr->url, FALSE);
				$answer .=  $twitter_data->data_section('trend', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;
			}
		$answer .= $twitter_data->data_section('trends', FALSE,  TRUE, TRUE);	
		$answer .= $twitter_data->data_bottom();
		
		echo $answer;
		exit;
	}
	elseif( $api_session->resource == 'current' ) 
	{
		$dts	= @gmmktime(0, 0, 1, gmdate('m'), gmdate('d'), gmdate('Y'), time()-24*60*60);
		$msg	= array();
		$this->db2->query('SELECT date, message FROM posts WHERE user_id<>0 AND posttags>0 AND date>'.$dts);
		while($obj = $this->db2->fetch_object()) 
		{
			$msg[]	= (object) array(
				'date'	=> gmdate('Y-m-d H:i:s',$obj->date),
				'text'	=> stripslashes($obj->message));
		}

		$data	= array();
		foreach($msg as $m) {
			if( ! preg_match_all('/\#([א-תÀ-ÿ一-龥а-яa-z0-9\-_]{1,50})/iu', $m->text, $matches, PREG_PATTERN_ORDER) ) {
				continue;
			}
			foreach($matches[1] as $tg) {
				$tg	= trim($tg);
				if( ! isset($data[$m->date]) ) {
					$data[$m->date]	= array();
				}
				$data[$m->date][]	= $tg;
			}
		}
		$tmp	= array();
		foreach($data as $tgs) {
			foreach($tgs as $tg) {
				if( ! isset($tmp[$tg]) ) {
					$tmp[$tg]	= 0;
				}
				$tmp[$tg]	++;
			}
		}
		if( count($tmp) > 10 ) {
			arsort($tmp);
			$tmp	= array_slice(array_keys($tmp), 9);
			foreach($tmp as $deltg) {
				foreach($data as $dt=>$tgs) {
					foreach($tgs as $i=>$tg) {
						if( $tg == $deltg ) {
							unset($data[$dt][$i]);
						}
					}
					if( count($data[$dt]) == 0 ) {
						unset($data[$dt]);
					}
				}
			}
		}
		$result	= new stdClass;
		$result->trends	= new stdClass;
		$result->as_of	= $dts; 
		foreach($data as $dt=>$tgs) {
			if( !isset($result->trends->dt) ) {
				$result->trends->dt	= array();
			}
			foreach($tgs as $tg) {

				$result->trends->dt	= (object) array(
					'query'	=> '#'.$tg,
					'name'	=> '#'.$tg,
					'url'		=> $C->SITE_URL.'search/tab:posts/s:%23'.urlencode($tg)
				);
			}
		}
		if( !isset($result->trends->dt) ) {
				$result->trends->dt	= array();
			}
			
		$num_rows = count($result->trends->dt); 

		$twitter_data = new TwitterData($api_session->format, $api_session->callback, -1, TRUE);
		$answer = $twitter_data->data_header();
	
		$answer .= $twitter_data->data_section('trends', FALSE, FALSE, TRUE, ' type="array"');
	
			foreach($result->trends->dt as $tr)
			{
				$answer .=  $twitter_data->data_section('trend', FALSE);	
					$answer .=  $twitter_data->data_field('name', $tr->name);	
					$answer .=  $twitter_data->data_field('query', $tr->query);	
					$answer .=  $twitter_data->data_field('url', $tr->url, FALSE);
				$answer .=  $twitter_data->data_section('trend', FALSE, TRUE);
				
				$answer .= ($api_session->format == 'json' && $num_rows-1>0)? ',':''; 
				$num_rows--;
			}
		$answer .= $twitter_data->data_section('trends', FALSE,  TRUE, TRUE);	
		$answer .= $twitter_data->data_bottom();

		echo $answer;
		exit;
	}
	elseif( $api_session->resource == 'daily' ) 
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}
	elseif( $api_session->resource == 'weekly' ) {
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'location' ) {
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif( $api_session->resource == 'available' ) {
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}	
?>
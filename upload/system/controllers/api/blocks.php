<?php
	require_once($C->INCPATH.'helpers/func_api.php');	
	
	$api_session = new stdClass;
	$api_session->format 	= $this->param('format');
	$api_session->callback	= (isset($_REQUEST['callback']) && valid_fn($_REQUEST['callback']))? $_REQUEST['callback']:FALSE;
	
	if( !$C->API_STATUS || $_SERVER['REQUEST_METHOD'] == 'COOKIE' ){
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 404 Not Found');
			else echo generate_error($format, 'API is disabled', $_SERVER['REQUEST_URI'], $callback);
		exit;	
	}
	setlocale(LC_TIME, 'en_US');	

	$uri = $this->param('more');
	$api_session->resource 			= isset($uri[1])? $uri[1]:'invalid';
	$api_session->resource_option 	= isset($uri[1])? $uri[1]:false;
	unset($uri);
	
	$api_session->oauth_status 		= false;
	$api_session->rate_status 		= false;
	$api_session->bauth_status 		= false;
	$api_session->available_resources 	= array('create', 'destroy', 'exists', 'blocking');
	$api_session->oauth_error 		= '';

	if(!is_valid_data_format($api_session->format, TRUE))
	{		
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error('xml', 'Invalid data format requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif(!isset($api_session->resource) || !in_array($api_session->resource, $api_session->available_resources))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($api_session->format, 'Invalid feature requested.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;
	}elseif($api_session->resource == 'destroy')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);
		exit;		
	}elseif($api_session->resource == 'create')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);	
		exit;	
	}elseif($api_session->resource == 'blocking' && isset($api_session->resource_option) && $api_session->resource_option=='ids')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);	
		exit;	
	}elseif($api_session->resource == 'blocking')
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
			else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);	
		exit;	
	}
	if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 304 Not Modified');
		else echo generate_error($api_session->format, 'Not implemented feature. Contact our support team for more information.', $_SERVER['REQUEST_URI'], $api_session->callback);	
	exit;	
?>
<?php
	require_once($C->INCPATH.'helpers/func_api.php');	
	
	$format = $this->param('format');
	$uri = $this->param('more');
	
	if(!isset($uri[0]))
	{
		if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
			else echo generate_error($format, 'Invalid resource address requested.', $_SERVER['REQUEST_URI']);
	}elseif($uri[0] == 'home') header('HTTP/1.1 400 Bad Request');

	if(!isset($_REQUEST['suppress_response_codes'])) header('HTTP/1.1 400 Bad Request');
		else echo generate_error($format, 'Invalid resource address requested.', $_SERVER['REQUEST_URI']);
	exit;
?>
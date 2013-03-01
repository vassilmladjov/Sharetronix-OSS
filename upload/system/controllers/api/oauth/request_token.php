<?php	

	require_once( $C->INCPATH.'helpers/func_api.php' );
	
	if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST')
	{
		echo 'Invalid request method.';
		exit;
	}elseif(isset($_REQUEST['oauth_version']) && $_REQUEST['oauth_version'] != '1.0')
	{
		echo 'Invalid OAuth version.';
		exit;
	} 
				
	if(isset($_REQUEST['oauth_consumer_key'], $_REQUEST['oauth_nonce'], $_REQUEST['oauth_signature_method'], 
			$_REQUEST['oauth_signature'], $_REQUEST['oauth_timestamp']) && $_REQUEST['oauth_signature_method'] != '')
	{
		require_once( $C->INCPATH.'classes/class_oauth.php' );
		
		$oauth_client = new OAuth($_REQUEST['oauth_consumer_key'], $_REQUEST['oauth_nonce'], $_REQUEST['oauth_signature'], $_REQUEST['oauth_timestamp']); 
		if(isset($_REQUEST['oauth_version'])) $oauth_client->set_variable('version', '1.0');
		
		if($oauth_client->is_valid_consumer_key() && $oauth_client->is_valid_nonce() && $oauth_client->is_valid_timestamp() 
			&& (strtolower(urldecode($_REQUEST['oauth_signature_method'])) == 'hmac-sha1') && $oauth_client->decrypt_hmac_sha1() 
			&& $oauth_client->is_valid_application())
		{
			$oauth_client->set_variable('token_secret', $oauth_client->generate_random_value());
			$oauth_client->set_variable('request_token', $oauth_client->generate_request_token());
			
			if($oauth_client->set_request_table())
			{
				
				echo 'oauth_token_secret='.$oauth_client->get_variable('token_secret');
				echo '&oauth_token='.$oauth_client->get_variable('request_token').'&oauth_callback_confirmed=true';
			}else
			{
				echo $oauth_client->get_variable('error_msg');
				exit;
			}
		}else
		{
			echo ($oauth_client->there_is_error()) ? $oauth_client->get_variable('error_msg'): 'Invalid signature method';
			exit;
		}
	}else
	{	
		echo 'Missing OAuth parameter(s).';
		exit;
	}
?>
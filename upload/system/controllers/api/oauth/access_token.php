<?php	
	
	if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST')
	{
		echo 'Invalid request method.';
		exit;
	}elseif(isset($_REQUEST['oauth_version']) && $_REQUEST['oauth_version'] != '1.0')
	{
		echo 'Not supported oauth version.';
		exit;		
	}

	if(isset($_REQUEST['oauth_consumer_key'], $_REQUEST['oauth_nonce'], $_REQUEST['oauth_signature_method'], 
			$_REQUEST['oauth_signature'], $_REQUEST['oauth_timestamp'], $_REQUEST['oauth_token'], $_REQUEST['oauth_verifier']))
	{	
		require_once( $C->INCPATH.'classes/class_oauth.php' );
		
		$oauth_client = new OAuth($_REQUEST['oauth_consumer_key'], $_REQUEST['oauth_nonce'], $_REQUEST['oauth_signature'], $_REQUEST['oauth_timestamp'],
						$_REQUEST['oauth_token'], $_REQUEST['oauth_verifier']);
		if(isset($_REQUEST['oauth_version'])) $oauth_client->set_variable('version', '1.0');

		if($oauth_client->is_valid_access_token_request() && (strtolower(urldecode($_REQUEST['oauth_signature_method'])) == 'hmac-sha1') 
					&& $oauth_client->decrypt_hmac_sha1() )
		{										
				$oauth_client->set_variable('access_token', $oauth_client->generate_access_token()); 
					$oauth_client->set_variable('user_id', $oauth_client->get_field_in_table('oauth_request_token', 'user_id', 
																'request_token', $_REQUEST['oauth_token']));
																
				if($oauth_client->set_access_table() && $oauth_client->delete_row_in_table('oauth_request_token', 'request_token', 
											  $oauth_client->get_variable('request_token')))
				{																
					echo 'oauth_token_secret='.urlencode($oauth_client->get_variable('token_secret'));
					echo '&oauth_token='.urlencode($oauth_client->get_variable('access_token'));	
					
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
		echo 'Missing OAuth parameters.';
		exit;
	}
?>
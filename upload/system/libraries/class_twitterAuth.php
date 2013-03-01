<?php
		
	global $C;
	require_once( $C->INCPATH.'helpers/func_additional.php' );
	
	class twitterAuth
	{
		protected $_request_token_url 	= 'https://api.twitter.com/oauth/request_token';
		protected $_access_token_url 	= 'https://api.twitter.com/oauth/access_token';
		protected $_authorize_url		= 'https://api.twitter.com/oauth/authorize';
		protected $_authenticate_url	= 'https://api.twitter.com/oauth/authenticate';
		protected $_get_user_data_url 	= 'https://api.twitter.com/1/users/show.json?screen_name=';
		protected $_parameters;
		
		public function __construct()
		{
			$this->_parameters = array();
		}
		
		public function getRequestToken()
		{
			global $C, $user, $page;
			
			$reg_id = $page->param('regid');
			$reg_key = $page->param('regkey');
			
			$this->_parameters = array(
					'oauth_consumer_key' 		=> $C->TWITTER_CONSUMER_KEY,
					'oauth_signature_method'	=> 'HMAC-SHA1',
					'oauth_timestamp'			=> time(),
					'oauth_nonce'				=> (md5(rand().time().rand())),
					'oauth_version'				=> '1.0',
					'oauth_callback'			=> $C->SITE_URL.'signup/'.($reg_id? '/regid:'.$reg_id : '').($reg_key? '/regkey:'.$reg_key : ''),
			);
			
			$params = normalize_oauth_params($this->_parameters);
			$signature = base64_encode(hash_hmac('sha1', 'GET&'.urlencode(utf8_encode($this->_request_token_url)).'&'.urlencode(utf8_encode($params)), $C->TWITTER_CONSUMER_SECRET.'&', true));
			
			$this->_parameters['oauth_signature'] = $signature;
			$params = normalize_oauth_params($this->_parameters);
			
			$call_twitter = curl_init();
			curl_setopt($call_twitter, CURLOPT_URL, $this->_request_token_url.'?'.$params);
			curl_setopt($call_twitter, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYHOST, FALSE);
			$consumer = curl_exec($call_twitter);
			curl_close($call_twitter);
			
			parse_str($consumer, $consumer);
			
			if(isset($consumer['oauth_token_secret'],  $consumer['oauth_token'])){
				$user->sess['oauth_token_secret'] = $consumer['oauth_token_secret'];
				header('Location: '.$this->_authenticate_url.'?oauth_token='.$consumer['oauth_token']);
				exit;
			}else{
				throw new Exception('Could not get request token from Twitter');
			}
		}
		
		public function getAccessToken()
		{
			global $C, $user;
			
			$this->_parameters = array(
					'oauth_consumer_key' 		=> $C->TWITTER_CONSUMER_KEY,
					'oauth_signature_method'	=> 'HMAC-SHA1',
					'oauth_timestamp'			=> time(),
					'oauth_token'				=> $_GET['oauth_token'],
					'oauth_nonce'				=> (md5(rand().time().rand())),
					'oauth_version'				=> '1.0',
			);
			
			$params 	= normalize_oauth_params($this->_parameters);
			$signature  = base64_encode(hash_hmac('sha1', 'GET&'.urlencode(utf8_encode($this->_access_token_url)).'&'.encode_rfc3986($params), $C->TWITTER_CONSUMER_SECRET.'&'.$user->sess['oauth_token_secret'], true));
			
			$call_twitter = curl_init();
			curl_setopt($call_twitter, CURLOPT_URL, $this->_access_token_url.'?'.$params.'&oauth_signature='.$signature);
			curl_setopt($call_twitter, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYHOST, FALSE);
			$consumer = curl_exec($call_twitter);
			curl_close($call_twitter);
			
			parse_str($consumer, $consumer);
			
			if( isset($consumer['user_id'], $consumer['screen_name']) ){
				return $consumer;
			}
			
			throw new Exception('Could not get auth token from Twitter');
		}
		
		public function getUserDetails( $access_token, $token_secret, $screen_name )
		{
			global $C, $user;
				
			$this->_parameters = array(
					'oauth_consumer_key' 		=> $C->TWITTER_CONSUMER_KEY,
					'oauth_signature_method'	=> 'HMAC-SHA1',
					'oauth_timestamp'			=> time(),
					'oauth_token'				=> $access_token,
					'oauth_nonce'				=> (md5(rand().time().rand())),
					'oauth_version'				=> '1.0',
			);
				
			$params 	= normalize_oauth_params($this->_parameters);
			$signature  = base64_encode(hash_hmac('sha1', 'GET&'.urlencode(utf8_encode($this->_get_user_data_url)).'&'.encode_rfc3986($params), $C->TWITTER_CONSUMER_SECRET.'&'.$token_secret, true));
				
			$call_twitter = curl_init();
			curl_setopt($call_twitter, CURLOPT_URL, $this->_get_user_data_url.$screen_name);
			curl_setopt($call_twitter, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($call_twitter, CURLOPT_SSL_VERIFYHOST, FALSE);
			$consumer = curl_exec($call_twitter);
			curl_close($call_twitter);

			$consumer = json_decode($consumer, TRUE);

			if( isset($consumer['id'], $consumer['screen_name'], $consumer['name']) ){
				return $consumer;
			}
				
			throw new Exception('Could not get user details from Twitter');
		}
	}
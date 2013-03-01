<?php

	class bitly
	{
		private $_login;
		private $_apikey;
		private $_error;
	
		public function __construct()
		{
			global $C;
	
			$this->_login 	= $C->BITLY_LOGIN;
			$this->_api_key 	= $C->BITLY_API_KEY;
			$this->_error 	= FALSE;
	
			if( empty($this->_login) || empty($this->_api_key) ){
				$this->_error = TRUE;
			}
		}
	
		function curl_get_result($url) {
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;
		}
	
		public function shortUrl( $url )
		{
			if( $this->_error ){
				return $url;
			}
	
			return $this->curl_get_result('http://api.bit.ly/v3/shorten?login='.$this->_login.'&apiKey='.$this->_api_key.'&uri='.urlencode($url).'&format=txt');
		}
	
		public function longUrl( $url )
		{
			if( $this->_error ){
				return $url;
			}
	
			return $this->curl_get_result('http://api.bit.ly/v3/expand?login='.$this->_login.'&apiKey='.$this->_api_key.'&uri='.urlencode($url).'&format=txt');
		}
	}
<?php
	
	class curlCall
	{
		private $_ch;
		
		public function __construct( $url )
		{
			$this->_ch = curl_init();
			curl_setopt($this->_ch, CURLOPT_URL, $url);
			curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($this->_ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($this->_ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($this->_ch, CURLOPT_AUTOREFERER, TRUE);
		}
		
		public function excludeHeader()
		{
			curl_setopt($this->_ch, CURLOPT_HEADER, FALSE);
		}
		
		public function excludeBody()
		{
			curl_setopt($this->_ch, CURLOPT_NOBODY, FALSE);
		}
		
		public function getData()
		{
			return curl_exec($this->_ch);
		}
		
		public function sslSupport( $ssl_support = TRUE )
		{
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $ssl_support);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, $ssl_support);
		}
		
		public function addPostData( $fields = array(), $as_array = TRUE )
		{
			$fields_count = count($fields);
			
			if( !$as_array ){
				$fields_data = '';
				foreach($fields as $key=>$value) { 
					$fields_data .= $key.'='.$value.'&'; 
				}
				rtrim($fields_data, '&');
			}else{
				$fields_data = $fields;
			}
			
			
			curl_setopt($this->_ch,CURLOPT_POST, $fields_count);
			curl_setopt($this->_ch,CURLOPT_POSTFIELDS, $fields_data);
		}
		
		public function __destruct()
		{
			curl_close($this->_ch);
		}
	}
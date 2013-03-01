<?php
	class services
	{
		private $ajax_file_name;
		private $ajax_file_action;
		private $ajax_file_path;
		private $ajax_file_item;
		private $json_data;
		private $url;
		private $user;
		private $network;
		private $error;
		
		
		public function __construct()
		{
			global $C;
			
			$this->user 	= & $GLOBALS['user'];
			$this->network 	= & $GLOBALS['network'];
			
			$this->error = FALSE;
			$this->json_data = array();
			$this->ajax_file_item = FALSE;
			$this->url = array();
			
			$this->parseUrl();
		
			if( count( $this->url ) < 2 ){
				$this->error =  TRUE; 
			}else{
				$this->ajax_file_name = trim( $this->url[0] );
				$this->ajax_file_action = trim( $this->url[1] );
				
				$this->ajax_file_path = $C->INCPATH.'controllers/ajax/'. $this->ajax_file_name .'.php'; 
				if( !file_exists( $this->ajax_file_path ) ){
					$this->ajax_file_path = $C->PLUGINS_DIR.$this->ajax_file_name.'/system/controllers/ajax/'.$this->ajax_file_action.'.php';
					if( !file_exists( $this->ajax_file_path ) ){
						$this->error =  TRUE;
					}
				}
			
			}
			
		}
		
		public function parseUrl()
		{
			$url = substr($_SERVER['REQUEST_URI'], 1);
			$url = explode('/', $url);
				
			$delete = TRUE;
			foreach( $url as $k=>$v ){
				if( $url[$k] == 'services' && $delete){
					unset( $url[$k] );
					$delete = FALSE;
				}elseif($delete){
					unset( $url[$k] );
				}
			
				if( !ctype_alnum( $v ) ){
					unset( $url[$k] );
				}
			}
				
			$this->url = array_values($url);
		}
		
		public function filter( $list = array('loggedin') )
		{
			if( !is_array( $list ) ){
				$this->error =  TRUE;
			}
			
			if( ! $this->network->id ){
				$this->error =  TRUE;
			}
			
			if( in_array('loggedin', $list) && !$this->user->is_logged){
				$this->error =  TRUE;
			}
			if( in_array('admin', $list) && !$this->user->info->is_network_admin){
				$this->error =  TRUE;
			}
			if( in_array('ip', $list) && $this->user->is_ip_restricted() ){
				$this->error =  TRUE;
			}
			
		}
		
		public function callEvent()
		{
			$event_to_call = 'onBefore'.$this->ajax_file_name.'Ajax';
			$pm->$event_to_call();
		}
		
		private function _isJson($string) 
		{
			return json_decode($string) !== NULL;
		}
		
		private function _parseAjaxResponse( $response )
		{
			if( empty($response) || strlen($response) === 0 ){
				return FALSE;
			}
			
			$parsed_response = array();
			
			if( !$this->_isJson($response) ){
				$parsed_response['html'] = $response;
			}else{
				$parsed_response = json_decode($response, TRUE);
				
				//this check is needed because of a bug in json_decode() in PHP 5.2
				//json_decode() returns a string as a result of a invalid json string instead of NULL
				if( is_string($parsed_response) ){
					$parsed_response = array();
				}
			}
			if( !isset($parsed_response['html']) ){
				$parsed_response['html'] = $response;
			}
			
			return $parsed_response;
			
		}
		
		public function load()
		{
			if( $this->error ){
				return FALSE;
			}
			
			//$this->callEvent();
			
			$ajax_action = $this->ajax_file_action;
			$ajax_item = $this->ajax_file_item;
			
			ob_start();
			require( $this->ajax_file_path );
			$result = ob_get_contents();
			ob_end_clean();
			
			$result = $this->_parseAjaxResponse($result);
			$html_result = $result? $result['html'] : '';

			$this->ajax_data['data'] = array();
			$this->ajax_data['data']['html'] = $html_result;
			$this->ajax_data['data']['status'] = ( !empty($html_result) && ($html_result == 'OK' || mb_substr($html_result, 0, 5) != 'ERROR') )? 'OK' : 'ERROR';
			$this->ajax_data['data']['message'] = ( !empty($html_result) && mb_substr($html_result, 0, 5) == 'ERROR' )? mb_substr($html_result, 6, mb_strlen($html_result)) : '';
			
			if( $result ){
				foreach($result as $k => $v){
					if(!isset($this->ajax_data['data'][$k])){
						$this->ajax_data['data'][$k] = $v;
					}
				}
			}
			
			unset($result);
			
			return json_encode( $this->ajax_data );
		}
	}
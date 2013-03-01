<?php
	class pluginsManager
	{
		protected $_installedPlugins;
		protected $_db2;
		protected $_assigned_vars;
		protected $_plugin_cache_array;
		protected $_current_event;
		protected $_current_event_error;
		protected $_event_result;
		public $delimiter;
		
		public function __construct()
		{
			$this->_db2 = & $GLOBALS['db2']; 
			$this->_installedPlugins = array();
			$this->_assigned_vars = array();
			$this->_assigned_vars['original'] = array(); 
			$this->_plugin_cache_array = array();
			$this->_event_result = array();
			$this->delimiter = '';
			$this->_current_event = '';
			$this->_current_event_error = FALSE;
			
			$this->_db2->query('SELECT name FROM plugins WHERE is_installed=1');
			while( $plugin = $this->_db2->fetch_object() ){
				$this->_installedPlugins[$plugin->name] = $plugin->name;
			}

			$this->LOAD();
		}
		
		public function __call($method, $args)
		{
			$plugins = $this->_checkEventCache($method);
			
			$tmp = NULL;
			if( count($plugins) ){
				foreach( $plugins as $p ){ 
					$tmp = $this->_installedPlugins[$p]->{$method}($args); 
					if( $tmp !== NULL ){
						$this->_event_result[$method] = $tmp;
					}
				}
			}
			
			$this->_current_event = $method;
		}
		
		private function load()
		{
			global $C;
			
			foreach($this->_installedPlugins as $plugin){
				require $C->PLUGINS_DIR.$plugin.'/plugin.php';
				$this->_installedPlugins[$plugin] = new $plugin();
			}
		}
		
		private function _checkEventCache( $event_name )
		{
			global $C;
			
			$data 		= array();
			$insert 	= array(); 
			$event_name = $this->_db2->e($event_name);
			$not_found 	= 'not_found_'.$C->RNDKEY;
			
			if( isset($this->_plugin_cache_array[ $event_name ]) ){
				return $this->_plugin_cache_array[ $event_name ];
			}
			
			$this->_db2->query('SELECT plugin_name FROM plugins_cache WHERE event_name="'.$event_name.'"');
			while( $o = $this->_db2->fetch_object() ){
				$data[] = $o->plugin_name;
			}
			
			if( count($data) === 1 && $data[0] === $not_found ){
				$data = array();
			} else if( !count($data) ){ 
				foreach( $this->_installedPlugins as $plugin_name => $plugin ){
					
					if( method_exists( $plugin, $event_name ) ){
						$data[] 	= $plugin_name;
						$insert[] 	= '("'.$plugin_name.'", "'.$event_name.'")';
					}
					
				}
				
				$q = count($insert)? 'INSERT INTO plugins_cache(plugin_name, event_name) VALUES'.( implode( ',', $insert ) ) : 'INSERT INTO plugins_cache(plugin_name, event_name) VALUES("'.$not_found.'", "'.$event_name.'")';
				$this->_db2->query( $q );
			}
			
			$this->_plugin_cache_array[ $event_name ] = $data;
			
			return $data;
		}
		
		public function invalidateEventCache( $event_name = FALSE )
		{
			$q = ( ! $event_name )? 'TRUNCATE TABLE plugins_cache' : 'DELETE FROM plugins_cache WHERE event_name="'.$this->_db2->e($event_name).'"';
			$this->_db2->query( $q );
			
			return TRUE;
		}
		
		public function setVar( $name, $value, $action = 'add', $priority = 0 )
		{	
			$priority = intval( $priority );
			$name = '{%'.$name.'%}';
			
			if( !isset($this->_assigned_vars[ 'original' ] [ $name ]) || $action == 'replace' ){
				$this->_assigned_vars[ 'original' ] [ $name ] = '';
			}
			$this->_assigned_vars[ 'original' ] [ $name ] .= $value; //added
			
			if( !isset( $this->_assigned_vars[ $name . '_before' ] ) ){
				$this->_assigned_vars[ $name . '_before' ] = '';
			}
			if( !isset( $this->_assigned_vars[ $name . '_after' ] ) ){
				$this->_assigned_vars[ $name . '_after' ] = '';
			}
			
			if( ! $priority && $action == 'add' ){
				$this->_assigned_vars[ $name . '_after' ] .= $this->delimiter . strval( $value ); //htmlspecialchars
			}else if( $priority && $action == 'add' ){
				$this->_assigned_vars[ $name . '_before' ] .= strval( $value ) . $this->delimiter; //htmlspecialchars
			}
			if( $action == 'replace' ){
				$this->_assigned_vars[ $name . '_replace' ] = strval( $value ) . $this->delimiter;
			}
			
			$this->delimiter = '';
		}
		
		public function invalidateVars()
		{
			$this->_assigned_vars = array();
		}
		
		public function invalidateSpecific( $arr )
		{
			foreach( $arr as $k=>$v ){
				unset($this->_assigned_vars[$k.'_before']);
				unset($this->_assigned_vars[$k.'_after']);
				unset($this->_assigned_vars[$k.'_replace']);
				unset($this->_assigned_vars['original'][$k]);
			}
		}
		
		public function getVars()
		{
			return $this->_assigned_vars;
		}
		
		public function getVar($name)
		{
			$name = '{%'.$name.'%}';
			
			if( isset( $this->_assigned_vars[ $name . '_after' ] ) ){
				return $this->_assigned_vars[ $name . '_after' ];
			}elseif( isset( $this->_assigned_vars[ $name . '_before' ] ) ){
				return $this->_assigned_vars[ $name . '_before' ];
			}elseif( isset( $this->_assigned_vars[ $name . '_replace' ] ) ){
				return $this->_assigned_vars[ $name . '_replace' ];
			}
			
			return FALSE;
		}
		
		public function getInstalledPluginNames()
		{
			return array_keys( $this->_installedPlugins );
		}
		
		public function __destruct()
		{
			unset( $this->_assigned_vars );
		}
		
		public function isValidEventCall()
		{
			if( empty($this->_current_event) ){
				return TRUE;
			}
			$this->_current_event_error = $this->getVar( $this->_current_event . '_error' );
			
			if( is_string($this->_current_event_error) && !empty($this->_current_event_error) ){
				return FALSE;
			}
				
			return TRUE;
		} 
		
		public function getEventCallErrorMessage()
		{
			$tmp = $this->_current_event_error;
			$this->_current_event_error = FALSE;
			unset( $this->_assigned_vars[ $this->_current_event . '_after' ] );
			
			return $tmp;
		}
		
		public function getEventResult()
		{
			$tmp = isset( $this->_event_result[ $this->_current_event ] )? $this->_event_result[ $this->_current_event ] : FALSE;
			unset( $this->_event_result[ $this->_current_event ] );
			
			return $tmp; 
		}
		
	}
?>
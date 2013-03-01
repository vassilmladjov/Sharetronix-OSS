<?php
	class appstorePlugin
	{
		public function __construct()
		{
			
		}
		
		/**
		 * getInstalledPluginIds
		 *
		 * @access	public
		 * @param	void
		 * @return	array<int>
		 */
		
		public static function getInstalledPluginIds()
		{
			$db = & $GLOBALS['db2'];
		
			$installed_plugins_ids = array();
			$installed_plugins = $db->fetch_all("SELECT *  FROM plugins_installed");
			foreach($installed_plugins as $p)
			{
				$installed_plugins_ids[] = $p->marketplace_id;
			}
		
			return $installed_plugins_ids;
		}
		
		
		public static function getPlugin($plugin_id)
		{
			global $C;
			$user = $GLOBALS['user'];
		
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();
		
			$data = $api->get('api/getPlugin/', array('id' => $plugin_id));
		
			$info = $api->getInfo();
			$api->close();
			if($info['http_code'] == 200 && $info['content_type'] == 'application/json')
			{
				return json_decode($data);
			}
			else
			{
				$plugin = false;
				throw new Exception("An error occured while trying to connect to marketplace: HTTP_CODE:" . $info['http_code']);
			}
		
		}
		
		
		public static function getPluginFile( $plugin_id )
		{
		
			global $C;
			$user = $GLOBALS['user'];
		
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();
		
			$file = $api->get('api/downloadPlugin/', array('id' => $plugin_id));
		
			$info = $api->getInfo();
			$api->close();
		
			if($info['http_code'] == 200 && $info['content_type'] == 'application/octet-stream')
			{
				return base64_decode($file);
			}
			else
			{
				throw new Exception($file);
			}
		
		}
		
		
		public static function getPlugins($params)
		{
			global $C;
			$user = $GLOBALS['user'];
		
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();

			$data = $api->get('api/getPlugins', $params);

			$info = $api->getInfo();
			$api->close();
			if($info['http_code'] == 200 && $info['content_type'] == 'application/json')
			{
				return json_decode($data);
			}
			else
			{
				throw new Exception("An error occured while trying to connect to marketplace: HTTP_CODE:" . $info['http_code']);
			}
		}
		
		
		public static function getPluginsCount($params)
		{
			global $C;
			$user = $GLOBALS['user'];
		
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();
			$num_results = $api->get('api/getPluginsCount', $params);
		
			$info = $api->getInfo();
			$api->close();
			if($info['http_code'] != "200")
			{
				throw new Exception("Could not properly fetch plugins count; Probably error in marketplace API");
			}
			else {
				return $num_results;
			}
		}
		
		public static function checkIfItemCanBeInstalled($item_id)
		{
			global $C;
				
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();
			$data = $api->get('api/getPlugin/', array('id' => $item_id));
		
			$info = $api->getInfo();
			if($info['http_code'] == 200 && $info['content_type'] == 'application/json'){
				$item = json_decode($data);
			}
			else{
				return FALSE;
			}
		
			if($item->price == 0){
				return TRUE;
			}
		
			$res = $api->get(
					'api/checkPluginSerialKey',
					array(
							'plugin_id' => $item_id,
							'key' => $C->STX_KEY
					)
			);
		
			$info = $api->getInfo();
		
			if($info['http_code'] != 200){
				return FALSE;
			}
		
			$api->close();
			if($res == 0){
				return FALSE;
			}
		
			return TRUE;
		}
	}
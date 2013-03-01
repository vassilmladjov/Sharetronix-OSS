<?php
	class AdminPlugins
	{
	
		protected $db;
		protected $user;
		protected $tpl;
		protected $params;
	
		public function AdminPlugins($user, &$tpl, &$params)
		{
	
			global $db1, $D;
			$this->db = &$db1;
			$this->user = $user;
			$this->tpl = &$tpl;
			$this->params = &$params;
			
			$D->items_type = 'plugins';
		}
	
	
		public function index()
		{
	
			global $C, $D, $page;
	
	
			if(!isset($C->STX_KEY) || !isset($C->STX_USERNAME) || !isset($C->STX_PASSWORD)){
				$page->redirect("admin/plugins/tab:enterStxKey");
			}
	
			$errors = array();
			$installed_plugin_ids = appstorePlugin::getInstalledPluginIds();
	
			$params = array(
					'sort_by' => 'date_created',
					'sort_order' => 'desc'
			);
			
			if(isset($this->params->search) && trim($this->params->search) != ""){
				$params['search'] = htmlspecialchars(urldecode( $this->params->search ));
			}
	
			$per_page = 6;
			$pageNum = (isset($this->params->page)) ? (int)$this->params->page : 0;
			$params['limit_start'] = $pageNum * $per_page;
			$params['num_results'] = $per_page;

			try{
				$plugins = appstorePlugin::getPlugins($params);
			}
			catch ( Exception $e ){
				$errors[] = $e->getMessage();
				$plugins = array();
			}
	
			foreach($plugins as $p){
				$p->installed = (in_array($p->id, $installed_plugin_ids)) ? TRUE : FALSE;
				$p->installable = appstorePlugin::checkIfItemCanBeInstalled($p->id);
			}
			
			try{
				$num_results = appstorePlugin::getPluginsCount($params);
			}catch ( Exception $e ){
				$num_results = 0;
				$errors[] = $e->getMessage();
			}
			
			if( count($plugins) === 0 && isset($C->STX_KEY, $C->STX_USERNAME, $C->STX_PASSWORD) && !isset($params['search']) ){
				$page->redirect('admin/plugins/tab:enterStxKey');
			}
			
			$D->items = $plugins;
			$D->num_results = $num_results;
			$D->errors = $errors;
			
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				if (isset($_POST['search_string']) && !empty($_POST['search_string'])) {
					$page->redirect( 'admin/plugins/tab:index/search:'. urlencode($_POST['search_string']) );
				} else {
					$page->redirect( 'admin/plugins/tab:index/' );
				}
			}
	
			if($num_results > count($plugins)) {
				$D->pagination = true;
				$D->num_pages = ceil($num_results/$per_page);
			} else {
				$D->pagination = false;
			}
			$D->search_term = ( isset($params['search']) ) ? $params['search'] : "";
			
			$this->tpl->layout->useBlock('plugins/marketplace');
			$this->tpl->layout->block->save( 'main_content' );	
		}
	
	
		public function enterStxKey()
		{
			global $page; 
			
			$error = "";
				
			if(isset($_POST['submit']))
			{
				try
				{
					global $C;
					global $D;
	
	
					$res = preg_match('/[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}/', $_POST['stx_key'], $matches);
					$key = (isset($matches[0])) ? $matches[0] : false;
					if($res == false || $key != $_POST['stx_key'])
					{
						throw new Exception("The key you entered is invalid");
					}
					$D->stx_key = $key;
						
					$api = new ApiConnect($C->MARKETPLACE_URL, $_POST['stx_username'], md5($_POST['stx_password']));
					$api->open();
					$res = $api->get('/api/ping');
					$info = $api->getInfo();
					$api->close();
	
					if($info['http_code'] != 200){
						throw new Exception('Incorrect username or password');
					}
	
					$this->db->query("
							INSERT INTO settings ( `word`, `value` ) VALUES ( 'STX_KEY', '$key' )
							ON DUPLICATE KEY UPDATE `value` = '$key'
							");
	
							$this->db->query("
						INSERT INTO settings ( `word`, `value` ) VALUES ( 'STX_USERNAME', '" . $_POST['stx_username'] . "' )
						ON DUPLICATE KEY UPDATE `value` = '".$_POST['stx_username']."'
						");
	
						$this->db->query("
						INSERT INTO settings ( `word`, `value` ) VALUES ( 'STX_PASSWORD', '" . md5($_POST['stx_password']) . "' )
								ON DUPLICATE KEY UPDATE `value` = '".md5($_POST['stx_password'])."'
								");
	
						header('Location: ' . $C->SITE_URL . "admin/plugins");
						die();
				}
				catch ( Exception $e ){
					$error = $e->getMessage();
				}
			}
			
			if( !empty($error) ){
				$this->tpl->layout->setVar('main_content_placeholder', $this->tpl->designer->errorMessage('Error', $error ) );
			}else{
				$app_msg = $page->lang('admin_appstore_username_pass');
				$this->tpl->layout->setVar( 'main_content_placeholder', $this->tpl->designer->okMessage('Your Sharetronix Key, Appstore Username and Appstore Password', $app_msg ) );
			}
	
			$table = new tableCreator();
			$rows = array(
					$table->inputField( $page->lang('admin_appstore_key'), 'stx_key', @$D->stx_key ),
					$table->inputField( $page->lang('admin_appstore_username'), 'stx_username', '' ),
					$table->passField( $page->lang('admin_appstore_password'), 'stx_password', '' ),
					$table->submitButton( 'submit', $page->lang('admin_appstore_submit') )
			);
			$this->tpl->layout->setVar('main_content', $table->createTableInput( $rows )); unset($rows, $table);
	}
	
	
	public function view()
	{
		global $C;
		global $D;
	
		$errors = array();
	
		$plugin_id = (isset($this->params->item_id)) ? (int)$this->params->item_id : 0;
	
		if($plugin_id == 0)
		{
			header('Location: ' . $C->SITE_URL . "admin/plugins");
			die();
		}
	
		try
		{
			$D->item = appstorePlugin::getPlugin($plugin_id);
	
			$installed_plugins = appstorePlugin::getInstalledPluginIds();
			$D->item_installed = ( in_array($D->item->id, $installed_plugins) ) ? true : false;
	
			$D->item_installable = appstorePlugin::checkIfItemCanBeInstalled($plugin_id);
		}
		catch ( Exception $e )
		{
			$errors[] = $e->getMessage();
		}
	
		$D->errors = $errors;
			
		$this->tpl->layout->useBlock('plugins/viewItem');
		$this->tpl->layout->block->save( 'main_content' );
	}
	
	
	
	public function install()
	{
		global $C;
		global $D, $page;
	
	
		$errors = array();
		$messages = array();
	
		$plugin_id = (isset($this->params->item_id)) ? (int)$this->params->item_id : 0;
		if($plugin_id == 0)
		{
			header('Location: ' . $C->SITE_URL . "admin/plugins");
			die();
		}
	
		$plugin_installable = appstorePlugin::checkIfItemCanBeInstalled($plugin_id);
		if($plugin_installable == false )
		{
			throw new Exception("You cannot install this plugin because you havent purchased it!");
	
		}
	
		try
		{
			$plugin = appstorePlugin::getPlugin($plugin_id);
		}
		catch ( Exception $e)
		{
			$errors[] = $e->getMessage();
		}
	
		try
		{
			$file_data = appstorePlugin::getPluginFile($plugin_id);
		}
		catch ( Exception $e )
		{
			$errors[] = $e->getMessage();
		}
	
			
		if(!empty($errors))
		{
			$D->errors = $errors;
	
			$this->tpl->layout->useBlock('plugins/installItem');
			$this->tpl->layout->block->save( 'main_content' );
	
	
		}
		else
		{
	
			require_once $C->INCPATH . 'plugin_installer/Installer.php';
	
			$tmp_file = get_tmp_dir() . DIRECTORY_SEPARATOR . $plugin->name . "_" . md5(time()) . ".zip";
			file_put_contents($tmp_file, $file_data);
	
			$extract_dir = get_tmp_dir() . DIRECTORY_SEPARATOR . "myPlugin_" . md5(time()) .DIRECTORY_SEPARATOR;
			define("EXTRACT_DIR", $extract_dir);
	
	
			try
			{
				if(is_file($tmp_file) == false) {
					throw new Exception("Could not find plugin file; maybe error occured during file transfer, please try again");
				}
	
				if(is_dir($extract_dir)) {
					rrmdir($extract_dir);
				}
				mkdir($extract_dir);
	
	
				// extract plugin
				$zip = new ZipArchive();
				if($zip->open($tmp_file) === true)
				{
					$zip->extractTo($extract_dir);
					$zip->close();
				}
				else
				{
					throw new Exception("Error: failed to extract the plugin archive");
				}
	
	
				$manifest_file = $extract_dir . $plugin->system_name .  "/manifest.json";
	
				if(!is_file($manifest_file))
				{
					throw new Exception("Could not find manifest file; Maybe the archive is not a valid plugin");
				}
				$manifest = json_decode(file_get_contents($manifest_file));
	
				$install_file = $extract_dir . $plugin->system_name . "/installer.php";
	
				$D->item = $plugin;
	
	
				$install_ok = false;
				if(is_file($install_file))
				{
	
					include $install_file;
					MyInstaller::$mode = Installer::INSTALLER_MODE_VALIDATION;
					MyInstaller::$manifest = $manifest;
					$inst = new MyInstaller();
	
					if(method_exists($inst, "up") && method_exists($inst, "down"))
					{
						$inst->up();
					}
	
					try
					{
						$result = $inst->finish();
	
						foreach($result as $res)
						{
							$messages[] = $res->getMessage();
							if($res instanceof ValidationError)
							{
								$install_ok = false;
								break;
							}
							else
							{
								$install_ok = true;
							}
						}
					}
					catch (Exception $e)
					{
						$install_ok = false;
						$errors[] = $e->getMessage();
					}
	
	
				}
				else
				{
					$install_ok = true;
					$messages[] = $page->lang('admin_app_install_warn_db');
	
				}
				$plugin_path = base64_encode($extract_dir . $plugin->system_name . DIRECTORY_SEPARATOR);
				$D->item_path = $plugin_path;
				$D->install_ok = $install_ok;
	
	
			}
			catch ( Exception $e )
			{
				$errors[] = $e->getMessage();
				$D->install_ok = false;
			}
	
	
		}
			
		$D->errors = $errors;
		$D->messages = $messages;
			
		$this->tpl->layout->useBlock('plugins/installItem');
		$this->tpl->layout->block->save( 'main_content' );
			
	}
	
	
	
	public function confirm_install()
	{
	
		global $C, $plugins_manager, $user;
	
		$plugin_path = isset($this->params->item_path) ? base64_decode($this->params->item_path) : false;
		$plugin_id = isset($this->params->item_id) ? (int)$this->params->item_id : false;
	
			
		if($plugin_path == false || $plugin_id == false)
		{
			header('Location: ' . $C->SITE_URL . "admin/plugins");
			die();
		}
	
		$plugin_installable = appstorePlugin::checkIfItemCanBeInstalled($plugin_id);
		if($plugin_installable == false )
		{
			header('Location: ' . $C->SITE_URL . "admin/plugins");
			die();
		}
			
		require_once $C->INCPATH . 'plugin_installer/Installer.php';
			
		$plugin_path = base64_decode($this->params->item_path);
		if(is_dir($plugin_path) == false)
		{
			header('Location: ' . $C->SITE_URL . "admin/plugins");
			die();
		}
		$manifest_file = $plugin_path . "manifest.json";
		$install_file = $plugin_path . "installer.php";
			
		if(!is_file($manifest_file))
		{
			throw new Exception("Could not find manifest file; Maybe the archive is not a valid plugin");
		}
		$manifest = json_decode(file_get_contents($manifest_file));
	
		require_once $C->INCPATH . 'plugin_installer/Installer.php';
	
		if( is_file($install_file) )
		{
	
			include $install_file;
			MyInstaller::$mode = Installer::INSTALLER_MODE_INSTALL;
			MyInstaller::$manifest = $manifest;
			$inst = new MyInstaller();
			if(method_exists($inst, "up") && method_exists($inst, "down"))
			{
				$inst->up();
			}
	
			$inst->finish();
		}
			
			
		$plugin_dir = PLUGINDIR . $manifest->plugin_name;
	
		if(!is_dir($plugin_dir))
		{
			mkdir($plugin_dir);
		}
	
	
		//var_dump($plugin_path, $plugin_dir);
		rcopy($plugin_path, $plugin_dir );
			
		$sql = "
				INSERT INTO plugins_installed (name, marketplace_id)
				VALUES ('" . $manifest->plugin_name . "', " . (int)$plugin_id . ")
			";
		$this->db->query($sql);
		
		//added 
		$plugins_manager->invalidateEventCache();//clear plugins cache database table
		invalidateCachedHTML(); //clear html cache folder
		$sql = "
				INSERT INTO plugins (name, is_installed, date_installed, installed_by_user_id)
				VALUES ('" . $manifest->plugin_name . "', 1, " . (time()) . ", '". $user->id . "')
			";
		$this->db->query($sql);//add plugin data to the plugins table
		//end added
	
		rrmdir(get_tmp_dir());
	
		header('Location: ' . $C->SITE_URL . "admin/plugins/installed:ok");
		die();
			
	}
	
	
	public function uninstall(){
		global $C;
		global $D;
	
		$plugin_id = (isset($this->params->item_id)) ? (int)$this->params->item_id : 0;
		if($plugin_id == 0)
		{
			header("Location: " . $C->SITE_URL);
			die();
		}
	
		$error = "";
			
		try {
	
			$installed_plugins = appstorePlugin::getInstalledPluginIds();
			if( ! in_array($plugin_id, $installed_plugins))
			{
				throw new Exception("System could not find such plugin installed ");
			}
	
			require_once  $C->INCPATH . 'plugin_installer/Installer.php';
	
			$D->item = appstorePlugin::getPlugin($plugin_id);
			$manifest_file = PLUGINDIR . $D->item->system_name . DIRECTORY_SEPARATOR . "manifest.json";
	
			if(!is_file($manifest_file))
			{
				throw new Exception("Could not find manifest file; this probably means plugin is corrupted and should be manually removed;");
			}
			$manifest = json_decode(file_get_contents($manifest_file));
	
	
		}
		catch ( Exception $e )
		{
			$error = $e->getMessage();
		}
	
		$D->error = $error;
	
		$this->tpl->layout->useBlock('plugins/uninstallItem');
		$this->tpl->layout->block->save( 'main_content' );
			
	}

	public function confirm_uninstall(){
	
		global $C, $plugins_manager;
	
		$plugin_id = (isset($this->params->item_id)) ? (int)$this->params->item_id : 0;
		$plugin_path = (isset($this->params->item_path)) ? base64_decode($this->params->item_path) : false;
		if($plugin_id == 0 || $plugin_path == false){
			header("Location: " . $C->SITE_URL);
			die();
		}
		
		require_once $C->INCPATH . 'plugin_installer/Installer.php';
		$installer_file = $plugin_path . DIRECTORY_SEPARATOR . "installer.php";
		$manifest_file = $plugin_path . DIRECTORY_SEPARATOR . "manifest.json";
		
		if( is_file($installer_file) ) {
			include $installer_file;
			MyInstaller::$mode = Installer::INSTALLER_MODE_INSTALL;
			MyInstaller::$manifest = json_decode(file_get_contents($manifest_file));
	
			$inst = new MyInstaller();
			if(method_exists($inst, "up") && method_exists($inst, "down")) {
				$inst->down();
			}
		}
		
		$plugin_name = $this->db->fetch_field('SELECT name FROM plugins_installed WHERE marketplace_id="'.$plugin_id.'" LIMIT 1');
		rrmdir($plugin_path);
		if( is_dir($C->PLUGINS_DIR.$plugin_name) ){
			rmdir($C->PLUGINS_DIR.$plugin_name); //delete the main folder
		}
		
			
		$sql = "
		DELETE FROM plugins_installed WHERE marketplace_id = $plugin_id
		";
		$this->db->query($sql);
			
		//added
		$plugins_manager->invalidateEventCache();//clear plugins cache database table
		invalidateCachedHTML(); //clear html cache folder
		$this->db->query("DELETE FROM plugins WHERE name = '".$plugin_name."' LIMIT 1");//delete plugin data from the plugins table
		//end added
		
		header('Location: ' . $C->SITE_URL . "admin/plugins/uninstalled:ok");
		die();
	
	}
}
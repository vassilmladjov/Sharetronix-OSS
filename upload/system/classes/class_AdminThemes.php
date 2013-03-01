<?php
	
	class AdminThemes
	{
		protected $tpl;
		
		public function __construct( $tpl )
		{
			global $D;
			
			$this->tpl = $tpl;
			
			$D->items_type = 'themes/getnew';
			
			define("PLUGINDIR", PROJPATH . "themes" . DIRECTORY_SEPARATOR);
		}
		
		public static function getInstalledThemes()
		{
			global $C;
			$names = array();
			$themes_path = $C->INCPATH.'../themes/';
			
			if ($handle = opendir($themes_path)) {		
				while (FALSE !== ($entry = readdir($handle))) {
					if( $entry !== '.' && $entry !== '..' && $entry !== '.htaccess' ){
						if( ! file_exists($themes_path.$entry.'/manifest.json') ) { 
							continue; 
						}
						
						$manifest_file = @file_get_contents( $themes_path.$entry.'/manifest.json' );
						$manifest_file = json_decode($manifest_file);
						if( !$manifest_file ){ 
							continue;
						}
						
						$names[$entry] = $manifest_file;
					}
				}
				closedir($handle);
			}
			
			return $names;
		}
		
		public static function getInstalledThemesNames()
		{
			$themes = AdminThemes::getInstalledThemes();
			
			return array_keys($themes);
		}
		
		public static function getAppstoreThemes($params)
		{
			global $C, $user, $page;
			
			if( !isset($C->MARKETPLACE_URL) || !isset($C->STX_USERNAME) || !isset($C->STX_PASSWORD) ){
				$page->redirect('admin/plugins');
			}
			
			$api = new ApiConnect($C->MARKETPLACE_URL, $C->STX_USERNAME, $C->STX_PASSWORD);
			$api->open();

			$data = $api->get('api/getPlugins', $params);
			$info = $api->getInfo();
			$api->close();
			
			if($info['http_code'] == 200 && $info['content_type'] == 'application/json'){
				return json_decode($data);
			}
			else{
				throw new Exception("An error occured while trying to connect to marketplace: HTTP_CODE:" . $info['http_code']);
			}
		}
		
		public function getAppstoreThemesParsed($params)
		{
			$themes = AdminThemes::getAppstoreThemes($params);
			$themes_parsed = array();
			$installed_themes = AdminThemes::getInstalledThemesNames();
			
			foreach( $themes as $t ){
				if( !isset($t->system_name) ){
					continue;
				}
				
				$t->installed = (in_array($t->system_name, $installed_themes)) ? TRUE : FALSE;
				$t->installable = appstorePlugin::checkIfItemCanBeInstalled($t->id);
				
				$themes_parsed[strtolower($t->system_name)] = $t;
			}
			
			return $themes_parsed;
		}
		
		public function index()
		{
			global $C, $D, $page;
			
			if( !isset($C->MARKETPLACE_URL) || !isset($C->STX_USERNAME) || !isset($C->STX_PASSWORD) ){
				$page->redirect('admin/plugins');
			}
			
			$params = array(
					'sort_by' 		=> 'date_created',
					'sort_order' 	=> 'desc',
					'category'  	=> 'Themes'
			);
			if( isset($_POST['search_string']) && trim($_POST['search_string']) !== "" ){
				$D->search_term 	= trim($_POST['search_string']);
				$params['search'] 	= $D->search_term;
			}
			
			$per_page = 6;
			$pageNumb = ($page->param('page')) ? (int)$page->param('page') : 0;
			$params['limit_start'] = $pageNumb * $per_page;
			$params['num_results'] = $per_page;
			
			try{
				$themes_selection = $this->getAppstoreThemesParsed($params);
			}
			catch ( Exception $e ){
				$errors[] = $e->getMessage();
				$themes_selection = array();
			}
			
			$D->items = $themes_selection;
			$D->num_results = $per_page;
			$D->errors = array();
			
			try{
				$num_plugins_from_mp = appstorePlugin::getPluginsCount($params);
			}catch ( Exception $e ){
				$num_plugins_from_mp = 0;
				$errors[] = $e->getMessage();
			}
				
			if($per_page < $num_plugins_from_mp && $num_plugins_from_mp > 0) {
				$D->pagination = true;
				$D->num_pages = ceil($num_plugins_from_mp/$per_page);
			} else {
				$D->pagination = false;
			}
			$D->search_term = ( isset($params['search']) ) ? $params['search'] : "";
				
			$this->tpl->layout->useBlock('plugins/marketplace');
			$this->tpl->layout->block->save( 'main_content' );
		}
		
		public function viewTheme($theme_id)
		{
			global $C, $D;
			
			if( !isset($C->MARKETPLACE_URL) || !isset($C->STX_USERNAME) || !isset($C->STX_PASSWORD) ){
				$page->redirect('admin/plugins');
			}
			
			if($theme_id == 0){
				$this->param("admin/themes/getnew");
			}
				
			try{
				$D->item = appstorePlugin::getPlugin($theme_id);
					
				$installed_themes 		= AdminThemes::getInstalledThemesNames();
				$D->item_installed 	= ( in_array($D->item->system_name, $installed_themes) ) ? TRUE : FALSE;
					
				$D->item_installable = appstorePlugin::checkIfItemCanBeInstalled($theme_id);
			}
			catch ( Exception $e )
			{
				$errors[] = $e->getMessage();
			}
				
			$D->errors = array();
			
			$this->tpl->layout->useBlock('plugins/viewItem');
			$this->tpl->layout->block->save( 'main_content' );
		}
		
		public function install( $theme_id )
		{
			global $C, $D, $page;
		
			$errors = array();
			$messages = array();
		
			if($theme_id == 0){
				$page->redirect("admin/themes/getnew");
			}
		
			$theme_installable = appstorePlugin::checkIfItemCanBeInstalled($theme_id);
			if($theme_installable == FALSE ){
				throw new Exception("You cannot install this theme because you havent purchased it!");
			}
		
			try{
				$theme = appstorePlugin::getPlugin($theme_id);
			}
			catch ( Exception $e){
				$errors[] = $e->getMessage();
			}
		
			try{
				$file_data = appstorePlugin::getPluginFile($theme_id);
			}
			catch ( Exception $e ){
				$errors[] = $e->getMessage();
			}
				
			if(!empty($errors)){
				$D->errors = $errors;
		
				$this->tpl->layout->useBlock('plugins/installItem');
				$this->tpl->layout->block->save( 'main_content' );
			}
			else
			{
				if( function_exists("rcopy") == false) {
					require_once $C->INCPATH . 'helpers/func_filesystem.php';
				}
		
				$tmp_file = get_tmp_dir() . DIRECTORY_SEPARATOR . $theme->name . "_" . md5(time()) . ".zip";
				file_put_contents($tmp_file, $file_data);
		
				$extract_dir = get_tmp_dir() . DIRECTORY_SEPARATOR . "myTheme_" . md5(time()) .DIRECTORY_SEPARATOR;
				define("EXTRACT_DIR", $extract_dir);
		
				try{
					if(is_file($tmp_file) == FALSE) {
						throw new Exception("Could not find plugin file; maybe error occured during file transfer, please try again");
					}
		
					if(is_dir($extract_dir)) {
						directory_tree_delete($extract_dir);
					}
					mkdir($extract_dir);
		
					// extract plugin
					$zip = new ZipArchive();
					if($zip->open($tmp_file) === TRUE){
						$zip->extractTo($extract_dir);
						$zip->close();
					}
					else{
						throw new Exception("Error: failed to extract the plugin archive");
					}
		
					$manifest_file = $extract_dir . $theme->system_name .  "/manifest.json";
		
					if(!is_file($manifest_file)){
						throw new Exception("Could not find manifest.json; Maybe the archive is not a valid theme");
					}
					$manifest = json_decode(file_get_contents($manifest_file));
		
					$D->item = $theme;
					$messages[] = $page->lang('admin_themes_install_warn');
					$theme_path = base64_encode($extract_dir . $theme->system_name . DIRECTORY_SEPARATOR);
					$D->item_path = $theme_path;
					$D->install_ok = TRUE;
		
				}
				catch ( Exception $e )
				{
					$errors[] = $e->getMessage();
					$D->install_ok = FALSE;
				}
			}
				
			$D->errors = $errors;
			$D->messages = $messages;
			
			$this->tpl->layout->useBlock('plugins/installItem');
			$this->tpl->layout->block->save( 'main_content' );
				
		}
		
		public function confirm_install($theme_id, $theme_path)
		{
			global $C, $plugins_manager, $user, $page;
	
			if($theme_path == FALSE || $theme_id == FALSE){ 
				$page->redirect("admin/themes/getnew");
			}
			
			$theme_installable = appstorePlugin::checkIfItemCanBeInstalled($theme_id);
			if($theme_installable == FALSE ){
				$page->redirect("admin/themes/getnew");
			}

			if(is_dir($theme_path) == FALSE){
				$page->redirect("admin/themes/getnew");
			}

			$manifest_file = $theme_path . "manifest.json";
				
			if(!is_file($manifest_file)){
				throw new Exception("Could not find manifest file; Maybe the archive is not a valid plugin");
			}
			$manifest = json_decode(file_get_contents($manifest_file));
			if( !isset($manifest->system_name) ){
				$page->redirect("admin/themes/getnew");
			}
			
			$theme_dir = $C->INCPATH.'../themes/' . $manifest->system_name;
			if(!is_dir($theme_dir)){
				mkdir($theme_dir);
			}
			
			if( function_exists("rcopy") == FALSE) {
				require_once $C->INCPATH . 'helpers/func_filesystem.php';
			}
			
			rcopy($theme_path, $theme_dir );
			rrmdir(get_tmp_dir());
			
			//added
			invalidateCachedHTML(); //clear html cache folder
			//end added
			
			$page->redirect("admin/themes/getnew/installed:ok");
		}

		
		public function uninstall( $theme_id )
		{
			global $C, $D, $page;
		
			if($theme_id == 0){
				$page->redirect("admin/themes/getnew");
				die();
			}
		
			$error = "";
				
			try {
				$D->item = appstorePlugin::getPlugin($theme_id);
				$manifest_file = $C->INCPATH.'../themes/' . $D->item->system_name . DIRECTORY_SEPARATOR . "manifest.json";
				
				if(!is_file($manifest_file)){
					throw new Exception("Could not find manifest file; this probably means plugin is corrupted and should be manually removed;");
				}
				$manifest = json_decode(file_get_contents($manifest_file));
				
				$installed_themes = AdminThemes::getInstalledThemesNames();
				if( ! in_array($D->item->system_name, $installed_themes)){
					throw new Exception("System could not find such plugin installed ");
				}
			}
			catch ( Exception $e ){
				$error = $e->getMessage();
			}
		
			$D->error = $error;
		
			$this->tpl->layout->useBlock('plugins/uninstallItem');
			$this->tpl->layout->block->save( 'main_content' );	
		}
		
		public function confirm_uninstall($theme_id, $theme_path)
		{
			global $C, $page, $db2;
		
			if($theme_id == 0 || $theme_path == FALSE){
				$page->redirect("admin/themes/getnew");
			}
			
			$manifest_file = $theme_path . DIRECTORY_SEPARATOR . "manifest.json";
			$t = json_decode( @file_get_contents($manifest_file) );
			if( !isset($t->system_name) ){
				$page->redirect("admin/themes/getnew");
			}
			
			if( $t->system_name == $C->THEME ){
				$db2->query('UPDATE settings SET value="default" WHERE word="THEME"');
			}
			
			require_once $C->INCPATH . 'helpers/func_filesystem.php';
			directory_tree_delete($theme_path);
			
			//added
			invalidateCachedHTML(); //clear html cache folder
			//end added
		
			$page->redirect("admin/themes/getnew/uninstalled:ok");
		}
		
	}
<?php
	global $D;
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$this->db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $this->db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	$tpl = new template( array(
		'page_title' => $C->SITE_TITLE.' - Languages',
		'header_page_layout'=>'sc',
	));
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	$action = ( isset($this->params->tab) == true ) ? filter_var($this->params->tab, FILTER_SANITIZE_SPECIAL_CHARS) : "index";
	$controller = new AdminLanguages($this->user, $tpl, $this->params);

	if(method_exists($controller, $action)) {
		call_user_func(array($controller, $action));
	} else {
		die();
	}
	
	
	class AdminLanguages {
		
		protected $db;
		protected $user;
		protected $tpl;
		protected $params;
		protected $page;
		
		public function AdminLanguages($user, &$tpl, &$params){
			global $db1;
			$this->db = &$db1;
			$this->user = $user;
			$this->tpl = &$tpl;
			$this->page = & $GLOBALS['page'];
			$this->params = &$params;
		}
		
		
		public function index(){
			global $C, $D;
			
			if(!isset($C->STX_KEY) || !isset($C->STX_USERNAME) || !isset($C->STX_PASSWORD))
			{
				header('Location: ' . $C->SITE_URL . "admin/plugins/tab:enterStxKey");
				die();
			}
			
			$D->error = FALSE;
			$languages = array();
			
			try{
				$languages = Langpack::getSupportedLanguages();
			}catch( Exception $e ){
				$D->error = $e->getMessage();
				$languages = array();
				$D->designer = pageDesignerFactory::select();
			}
			
			if( count($languages) > 0 ){
				$langs = array();
				foreach($languages as $l) {
					$lang = $l->toArray();
					$lang['installed'] = $l->isInstalled();
					$lang['upgradable'] = $l->isUpgradable();
					$langs[] = $lang;
				}
				
				$D->languages = $langs;
			}
			
			$this->tpl->layout->useBlock('languages/viewall');
			$this->tpl->layout->block->save( 'main_content' );
		}
		
		
		public function installLangpack(){
			
			$langkey =  ( isset($this->params->langkey) == true) ? $this->params->langkey : false;
			if($langkey == false) 
				die($this->page('admin_langs_unsupported'));
	
			$lang = new Langpack($langkey);
			$lang->install();
			
			global $C;
			header("Location: " . $C->SITE_URL . "admin/languages/");
			die();
			
		}
		
		
		public function updateLangpack(){
			$langkey =  ( isset($this->params->langkey) == true) ? $this->params->langkey : false;
			if($langkey == false)
				die($this->page('admin_langs_unsupported'));
			
			$lang = new Langpack($langkey);
			$lang->upgrade();
			
			global $C;
			header("Location: " . $C->SITE_URL . "admin/languages/");
			die();
		}
		
		
		public function uninstallLangpack(){
			
			$langkey =  ( isset($this->params->langkey) == true) ? $this->params->langkey : false;
			if($langkey == false)
				die($this->page('admin_langs_unsupported'));
			
			$lang = new Langpack($langkey);
			$lang->remove();
			
			global $C;
			header("Location: " . $C->SITE_URL . "admin/languages/");
			die();
					
		}
		
	}
	
	
	$tpl->display();

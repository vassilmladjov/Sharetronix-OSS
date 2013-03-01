<?php

	class template extends templateElement
	{
		private $page;
		private $user;
		private $network;	
		public 	$title;
		public 	$body_class;
		public 	$controller;
		public 	$layout;
		public  $staticHTML;
		public 	$routine;
		private $default_load;
		private $pm;	
		public  $type;
		public  $designer;
		private $user_or_group_check;
		
		public function __construct( $params = array(), $default_load = TRUE )
		{
			/*
			 * The constructor requires one parameter - array with additional options for the template.
			 * There parameters are used in the defalur routine (loadDefaultHeader) for loading the page header.
			 * Sample paramaters: new template( array('page_title' => 'Dashboard', 'header_page_layout'=>'sc') );
			 * header_page_layout - c, sc, cs, scs
			 * 
			 */
			global $C;
			
			$this->user 		= & $GLOBALS['user'];
			$this->page 		= & $GLOBALS['page'];
			$this->network 		= & $GLOBALS['network'];
			$this->pm 			= & $GLOBALS['plugins_manager'];
			
			$this->designer 	= pageDesignerFactory::select();
			$this->layout 		= FALSE;
			$this->routine		= FALSE;
			$this->staticHTML	= FALSE;
			$this->html 		= '';
			$this->header_data 	= '';
			$this->default_load = $default_load;	
			$this->type 		= (!$this->page->is_mobile)? 'system' : 'mobile';
			$this->user_or_group_check = in_array($this->page->request[0], array('user', 'group', 'groups', 'members', 'view', 'terms', 'contacts'));
			
			if( $this->default_load ){

				$this->pm->onPageLoad();
				
				$this->useLayout( (!$this->page->is_mobile && !$this->user->is_logged && !$this->user_or_group_check)? 'header-outside' : 'header' );
				
				$this->initRoutine(!$this->page->is_mobile? (($this->user->is_logged || $this->user_or_group_check)? 'DefaultHeader' : 'OutsideHeader') : 'MobileHeader');
				foreach( $params as $k=>$v ){
					$this->routine->setSpecific( '{%'. $k . '%}', $v );
				}
				$this->routine->load();		
				
				$this->useLayout('page_layout');
				
			}else{
				$this->useLayout('page_layout_clean');
			}
		}
		
		public function useLayout( $layout_name, $plugin = FALSE )
		{
			/*
			 * Choose the layout which you will include in the template.
			 * Reguires layout name and optional plugin name (to load a layout from plugin).
			 * 
			 */
			
			if($this->layout){
				$this->layout->saveVars();
				$this->html .= $this->layout->html;
				
				$this->layout = FALSE;
			}
			$this->layout 	= new layout( $layout_name, $plugin, $this );
		}
		
		public function display( $return_html = FALSE )
		{
			/*
			 * Display template and show the generated content on the users screen. 
			 * Auto includes the default footer.
			 * 
			 */
			
			global $C;
			
			if( $this->default_load ){

				$this->useLayout( (!$this->page->is_mobile && !$this->user->is_logged && !$this->user_or_group_check)? 'footer-outside' : 'footer' );
				
				$this->initRoutine( (!$this->page->is_mobile && !$this->user->is_logged && !$this->user_or_group_check)? 'OutsideFooter' : 'DefaultFooter' );
				$this->routine->load();	
			}
			
			if($this->layout){
				
				$this->layout->saveVars();
				$this->html .= $this->layout->html;
				
				$this->layout = FALSE;
			}
			
			$this->removeTemplateVars();
			
			if( !$return_html ){
				echo $this->html;
			}else{
				return $this->html;
			}
			
			if( $C->DEBUG_MODE && $this->default_load && $C->DEBUG_CONSOLE && !$this->page->is_mobile){
				$this->page->load_single_block('debug-console');
			}
		}
		
		public function initRoutine( $routine_name, $parameters = array(), $plugin = FALSE )
		{
			/*
			 * Requires 
			 * $routine_name - routine name, 
			 * $parameters - parameters to send to the routine(optional)
			 * $plugin - routine located at the plugin (optional)
			 * Initializes stored routine. Routines are located at ./system/helpers/routines/.
			 * 
			 * 
			 */
			$this->routine = FALSE;
			$this->routine = new controllerRoutine( $routine_name, $parameters, $this, $plugin );
			
		}
		
		public function useStaticHTML()
		{
			if( $this->staticHTML === FALSE ){
				$this->staticHTML = new staticHTML( $this->layout  );
			}
		}
	
	}
<?php
	class layout extends templateElement
	{
		public 	$block;
		public  $inner_block;
		public 	$name;
		private $user;
		public  $tpl;
		private $page;
		private $chosen_html_layout;
		public  $delimiter;
		public  $plugin_manager;
		private $path_to_layout;
		private $path_to_block;
		private $path_to_layout_default;
		private $path_to_block_default;
		
		private $_used_blocks = array();
		
		public function __construct( & $layout_name, $plugin = FALSE, & $tpl )
		{
			global $C;
			
			$this->page 			= & $GLOBALS['page'];
			$this->user 			= & $GLOBALS['user'];
			$this->plugin_manager 	= & $GLOBALS['plugins_manager'];
			$this->tpl 				= $tpl;
			$this->assigned_vars 	= array();
			$this->name 			= $layout_name;
			$this->block 			= FALSE;
			$this->inner_block 		= FALSE;
			$this->delimiter 		= '';
			
			$this->path_to_layout 			= $plugin? $C->INCPATH.'../apps/'.$plugin.'/static/templates/layout/' : $C->INCPATH.'../themes/'.$C->THEME.'/templates/layout/';
			$this->path_to_layout_default	= $C->INCPATH.'../static/templates/'.$this->tpl->type.'/layout/';
			
			$this->chosen_html_layout = file_exists($this->path_to_layout.$layout_name.'.php')? $this->path_to_layout.$layout_name.'.php' : $this->path_to_layout_default.$layout_name.'.php';
			
			ob_start();
			require($this->chosen_html_layout);
			$this->html = ob_get_contents();
			ob_end_clean();
		}
		public function setVar( $name, $value, $remove_var = FALSE )
		{
			$this->assigned_vars['{%'.$name.'%}'] 	= (isset($this->assigned_vars['{%'.$name.'%}']) && !$remove_var)? $this->assigned_vars['{%'.$name.'%}'].$this->delimiter.$value : $value; //htmlspecialchars
		}
		public function useBlock( $block_name, $plugin = FALSE ) 
		{
			/*
			 * Set block to use in the layout.
			 * Requires $block_name and optional $plugin name.
			 * 
			 */
			if($this->block){
				$this->block = FALSE;
			}
			
			$this->block 	= new htmlBlock( $block_name, $plugin, $this );
		}
		
		public function useInnerBlock( $block_name, $plugin = FALSE )
		{
			/*
			 * If you have loaded block you could include inner block in it with this method.
			 * Requires $block_name and optional $plugin.
			 * 
			 */
			
			if($this->inner_block){
				$this->inner_block = FALSE;
			}

			$this->inner_block 	= new htmlBlock( $block_name, $plugin, $this );	
			$this->inner_block->block_type = 'inner';
		}
		
		public function saveVars()
		{ 
			/*
			 * Save assigned variables.
			 * 
			 */
			
			if( $this->block ){
				$this->block = FALSE;
			}
			if( $this->inner_block ){
				$this->inner_block = FALSE;
			}
			
			//use the vars in the plugins
			$this->usePluginVars();

			$this->html = str_replace( array_keys($this->assigned_vars), array_values($this->assigned_vars), $this->html );
			
			$this->invalidateVars();	
			
			return TRUE;
		}
		
		public function destroyBlock($type='main')
		{
			/*
			 * Delete loaded block.
			 *
			 */
			if($type == 'main'){
				unset( $this->block );
				$this->block = FALSE;
			}elseif($type=='inner'){
				unset( $this->inner_block );
				$this->inner_block = FALSE;
			}
			
		}
		
		/*public function loadActivityContainer()
		{
			$this->useBlock('activity-container');
			$this->block->save( 'main_content' );
		}*/
	}
?>
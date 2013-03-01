<?php
	class htmlBlock extends templateElement
	{
		private $chosen_html_block;
		private $name;
		public  $plugin_manager;
		private $path_to_block;
		private $path_to_block_default;
		private $layout;
		public  $delimiter;
		public  $block_type;
		
		public function __construct( $block_name, $plugin = FALSE, & $layout )
		{
			global $C, $D;
				
			$this->page 			= & $GLOBALS['page'];
			$this->user 			= & $GLOBALS['user'];
			$this->plugin_manager 	= & $GLOBALS['plugins_manager'];
			$this->layout 			= $layout;
			$this->html				= '';
			$this->assigned_vars 	= array();
			$this->name 			= $block_name;
			$this->delimiter		= '';
			$this->block_type		= '';
				
			$this->path_to_block 			= $plugin? $C->INCPATH.'../apps/'.$plugin.'/static/templates/blocks/' : $C->INCPATH.'../themes/'.$C->THEME.'/templates/system/blocks/';
			$this->path_to_block_default	= $C->INCPATH.'../static/templates/'.$this->layout->tpl->type.'/blocks/';
				
			$this->chosen_html_block = file_exists($this->path_to_block.$block_name.'.php')? $this->path_to_block.$block_name.'.php' : $this->path_to_block_default.$block_name.'.php';

			ob_start();
			require($this->chosen_html_block);
			$this->html = ob_get_contents();
			ob_end_clean();
			
		}
		
		public function save( $layout_part, $remove_vars = FALSE )
		{
			/*
			 * Save the block in the layout.
			 * Requires the layout_part name where the block will be saved.
			 * Template variables could be removed if you put second parameter bool TRUE.
			 */
			
			 /*if( $this->block_type == 'inner' ){
			 	$this->saveInBlockPart( $layout_part, $remove_vars );
			 	return;
			 }*/	 
			$tmp = $this->assigned_vars; //added
			
			$this->usePluginVars();

			$this->html = str_replace( array_keys($this->assigned_vars), array_values($this->assigned_vars), $this->html );
			$this->html = $this->html . $this->delimiter;
			
			if( $remove_vars ){
				$this->removeTemplateVars();
				$this->assigned_vars = $tmp; unset($tmp); //added
				$this->plugin_manager->invalidateSpecific($this->assigned_vars); 
				//$this->plugin_manager->invalidateVars(); //tova e slojeno zaradi povtoreniqta //TEST 
			}

			$this->invalidateVars();
			
			$this->layout->html = str_replace( '{%'.$layout_part.'%}', $this->html.'{%'.$layout_part.'%}', $this->layout->html );		
		
			$this->layout->destroyBlock( $this->block_type );
		}
		
		public function saveInBlockPart( $block_part, $remove_vars = FALSE )
		{
			/*
			 * Save loaded inner_block in the main (block). 
			 * Requires block place where the data will be saved.
			 * Template variables could be removed if you put second parameter bool TRUE.
			 * 
			 */
			
			$tmp = $this->assigned_vars; //added
			
			//use the vars in the plugins
			$this->usePluginVars();
			
			//$html = $remove_vars? $this->getHtml() : $this->html;
			//$this->layout->block->html = str_replace( '{%'.$block_part.'%}', $html.'{%'.$block_part.'%}', $this->layout->block->html );	
			
			$this->html = str_replace( array_keys($this->assigned_vars), array_values($this->assigned_vars), $this->html );
			$this->html = $this->html . $this->delimiter;
			
			if( $remove_vars ){	
				//moved above
				//$this->html = str_replace( array_keys($this->assigned_vars), array_values($this->assigned_vars), $this->html );
				//$this->html = $this->html . $this->delimiter;

				$this->assigned_vars = $tmp; unset($tmp); //added
				$this->plugin_manager->invalidateSpecific($this->assigned_vars); //test
				$this->removeTemplateVars();
			}
			
			$this->layout->block->html = str_replace( '{%'.$block_part.'%}', $this->html.'{%'.$block_part.'%}', $this->layout->block->html );
			
			$this->invalidateVars();//new thing 
			
			$this->layout->destroyBlock( $this->block_type );
		}
	}
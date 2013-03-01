<?php
	class templateElement
	{
		public $html;
		public $assigned_vars;
		
		public function removeTemplateVars()
		{
			preg_match_all("/{%(.+)%}/iU", $this->html, $matches);
			if( isset($matches[1]) ){
				
				/*array_walk($matches[1], function(&$val, &$key) use(&$array){
					$val =  '{%'. $val .'%}';
				});*/
				array_walk($matches[1], 'changeTemplateArray');
				$this->html = str_replace( array_values($matches[1]), '', $this->html );
			}
		}
		
		public function setVar( $name, $value, $remove_var = FALSE )
		{
			$this->assigned_vars['{%'.$name.'%}'] 	= (isset($this->assigned_vars['{%'.$name.'%}']) && !$remove_var)? $this->assigned_vars['{%'.$name.'%}'].$this->delimiter.$value : $value; //htmlspecialchars
		}
		
		public function invalidateVars()
		{
			$this->assigned_vars = array();
		}
		
		public function usePluginVars()
		{	
			$plugin_vars = $this->plugin_manager->getVars();
			if( !count($plugin_vars) ){
				return TRUE;
			}
			
			foreach( $this->assigned_vars as $k => $v ){
					
				if( isset( $plugin_vars[ $k.'_replace' ] ) ){
					$this->assigned_vars[$k] = $plugin_vars[ $k.'_replace' ];
				}else{
					if( isset( $plugin_vars[ $k.'_before' ] ) && !empty($plugin_vars[ $k.'_before' ]) ){
						$this->assigned_vars[$k] = $plugin_vars[ $k.'_before' ] . $this->assigned_vars[$k];
					}
					if( isset( $plugin_vars[ $k.'_after' ] ) && !empty($plugin_vars[ $k.'_after' ]) ){
						$this->assigned_vars[$k] = $this->assigned_vars[$k] . $plugin_vars[ $k.'_after' ];
					}
				}
			}
			
			$tmp = array_diff_assoc($plugin_vars['original'], $this->assigned_vars);
			foreach( $tmp as $k => $v ){
				if( !isset( $this->assigned_vars[$k] ) ){
					$this->assigned_vars[$k] = $tmp[ $k ];
				}
			}
				
			return TRUE;
		}
		
	}
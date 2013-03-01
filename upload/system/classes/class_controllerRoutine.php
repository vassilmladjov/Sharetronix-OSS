<?php
	class controllerRoutine
	{
		private $tpl;
		private $routine_name;
		private $specific_vars;
		private $parameters;
		
		public function __construct( $routine_name, $parameters, & $tpl, $plugin )
		{
			global $C;
			
			if( ! function_exists('load'.$routine_name) ){
				require $C->INCPATH.'helpers/routines/load'. $routine_name .'.php';
			}
			
			$this->tpl = $tpl;
			$this->specific_vars = array();
			$this->routine_name = $routine_name;
			$this->parameters = $parameters;
		}
		
		public function setSpecific( $name, $value, $action = 'replace' )
		{
			$this->specific_vars[$name] = array( 'value'=>$value, 'action'=>$action );
		}
		
		public function load()
		{
			foreach( $this->specific_vars as $k=>$v ){
				//if( isset($this->tpl->layout->assigned_vars[$k]) ){
					($this->specific_vars[$k]['action'] == 'add')? ($this->tpl->layout->assigned_vars[$k] .= $this->specific_vars[$k]['value']) : ($this->tpl->layout->assigned_vars[$k] = $this->specific_vars[$k]['value']);
				//}
			}

			if( function_exists( 'load'.$this->routine_name ) ){
				
				$funcname = 'load'.$this->routine_name;
				$funcname( $this->tpl, $this->parameters );
				
			}

		}
		
	}
?>
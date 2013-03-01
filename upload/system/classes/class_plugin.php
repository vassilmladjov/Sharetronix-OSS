<?php
	class plugin
	{
		protected $plgn;
		protected $db2;
		protected $network;
		protected $page;
		protected $filter;
		protected $cache;
		protected $user;
		
		public function __construct()
		{
			$this->plgn 	= & $GLOBALS['plugins_manager'];
			$this->db2		= & $GLOBALS['db2'];
			$this->network	= & $GLOBALS['network'];
			$this->page		= & $GLOBALS['page'];
			$this->cache	= & $GLOBALS['cache'];
			$this->user		= & $GLOBALS['user'];
			
			$this->filter 	= new postFilter();
		}
		
		final protected function setVar( $name, $value, $action = 'add', $priority = 0 )
		{
			$this->plgn->setVar( $name, $value, $action, $priority );
		}
		
		final protected function setDelimiter( $value )
		{
			$this->plgn->delimiter = $value;
		}
		
		final protected function getCurrentController()
		{
			return implode('/', $this->page->request);
		}
		
		final protected function getCurrentTab()
		{
			return $this->page->param('tab');
		}
		
		final protected function isMobileRegime()
		{
			$request 	= $_SERVER['REQUEST_URI'];
			$request	= '/'.rtrim($request, '/').'/'; 
			return (strpos($request, '/m/') !== FALSE) ? TRUE : FALSE;
		}

	}
?>
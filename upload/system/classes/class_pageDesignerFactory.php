<?php
	class pageDesignerFactory
	{
		public static function select()
		{
			global $page;
			
			return $page->is_mobile? new pageDesignerMobile() : new pageDesignerSystem();
		}
	}
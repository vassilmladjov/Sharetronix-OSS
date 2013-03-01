<?php
	class pageDesigner
	{
		public function createMenu( $menu_class, & $menu_items, $placeholder='' )
		{
			/*
			* @param $menu_class - feed-navigation( left menu ), tabs-navigation (tab menu)
			* @param $menu_items - Array( array('url'=>'', 'css_class'=>'', 'title'=>'', 'tab_state'=>'') );
			* @param $placeholder - custom placeholder name for your menu	
			*/
		
			if( !is_array($menu_items) ){
				return '';
			}
		
			global $C;
			$page = & $GLOBALS['page'];
		
			$cur_address = implode('/', $page->request).'/'; //(;
		
			$html = '<ul class="'.$menu_class.'">';
		
			foreach( $menu_items as $item ){
				$html .= $this->createMenuLink($item);
			}
		
			$html .= !empty($placeholder)? '{%'.$placeholder.'%}' : '';
			$html .= '</ul>';
		
			return $html;
		}
		
		public function createMenuLink( $item )
		{
			global $C, $page;
			
			$cur_address 	= implode('/', $page->request).'/'; //(;
			//$tab_state 	= ( isset($item['tab_state']) && is_numeric($item['tab_state']) && ($address !== $cur_address) )? intval($item['tab_state']) : 0;
			$tab_state 		= ( isset($item['tab_state']) && is_numeric($item['tab_state']) && ($item['url'] !== $cur_address) )? intval($item['tab_state']) : 0;
			
			return '<li><a href="'. $C->SITE_URL . $item['url'] .'" class="'. (isset($item['css_class'])? $item['css_class'] : '' ) . (($item['url'] == $cur_address)? ' selected':'') .'"><span>'. $item['title'] .'</span>'. ($tab_state? '<span class="new-items-count"><span>'. $tab_state .'</span></span>' : '') .'</a></li>';
		}
		
		public function createNoPostBox( $title, $text )
		{
			return '<div class="noposts">
				<h3>'. $title .'</h3>
				<p>'. $text .'</p>
		</div>';
		}
		
		public function okMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message success">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function errorMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message error">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function informationMessage($title, $text, $closebtn=TRUE, $incss='')
		{
		
			$html	= '
				<div class="system-message">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
			return $html;
		}
		
		public function getMetaData()
		{
			$html = '';
		
			$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
			$html .= '<meta name="keywords" content="microblogging, sharetronix, blogtronix, enterprise microblogging">'."\n";
		
			return $html;
		}
		
		public function loadJSData()
		{
			global $C, $page;

			$html = '<script type="text/javascript"> var siteurl = "'. $C->SITE_URL .'"; </script>'."\n";
			$system_files_location 	= $C->STATIC_URL.'js/';
		
			foreach( $this->system_files as $f ){
				$html .= '<script type="text/javascript" src="'.$system_files_location.$f.'.js?v='.$C->VERSION.'"></script>'."\n";
			}
		
			if( $C->THEME != 'default' ){
				if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/js/')) {
		
					while (FALSE !== ($entry = readdir($handle))) {
						if( $entry !== '.' && $entry !== '..' ){
							$html .= '<script type="text/javascript" src="'.$C->SITE_URL.'themes/'.$C->THEME.'/js/'. $entry .'?v='.$C->VERSION.'"></script>'."\n";
						}
					}
		
					closedir($handle);
				}
			}
			
			$html .= $this->_browsePluginsTemplateDir('js', array(
																	'<script type="text/javascript" src="'.$C->OUTSIDE_SITE_URL.'apps/', 
																	'"></script>'."\n"));
		
			return $html;
		}
		
		public function loadCSSData()
		{
			global $C, $page;
			
			$html = '';
			$system_files_location 	= $C->STATIC_URL.'css/';
		
			if( !isset($C->THEME_CSS_OVERWRITE) ){
				foreach( $this->system_files as $f ){
					$html .= '<link href="'. $system_files_location . $f .'.css?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
				}
			}
			
			if( $C->THEME != 'default' ){
				if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/css/')) {
		
					while (FALSE !== ($entry = readdir($handle))) {
						if( $entry !== '.' && $entry !== '..' ){
							$html .= '<link href="'. $C->SITE_URL .'themes/'. $C->THEME .'/css/'. $entry .'?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
						}
					}
		
					closedir($handle);
				}
			}
				
			$html .= $this->_browsePluginsTemplateDir('css', array(
					'<link href="'. $C->OUTSIDE_SITE_URL .'apps/',
					'" type="text/css" rel="stylesheet" />'."\n"
			));
		
			return $html;
		}
		
		private function _browsePluginsTemplateDir( $file_type, $file_credentials)
		{
			global $C, $plugins_manager;
			
			$html = '';
			
			$installed_plugins = $plugins_manager->getInstalledPluginNames();
			foreach( $installed_plugins as $p ){
				if( is_dir( $C->PLUGINS_DIR.$p.'/static/'.$file_type.'/' ) ){
					if ($handle = @opendir($C->PLUGINS_DIR.$p.'/static/'.$file_type.'/')) {
			
						while (FALSE !== ($entry = readdir($handle))) {
							if( $entry !== '.' && $entry !== '..' ){
								$html .= $file_credentials[0]. $p . '/static/'.$file_type.'/' . $entry .'?v='.$C->VERSION. $file_credentials[1];
							}
						}
			
						closedir($handle);
					}
				}
			}
			
			return $html;
		}
		
		public function getFaviconData( $get_link = FALSE )
		{
			global $C;
		
			$html = '';
			$network = & $GLOBALS['network'];
		
			if( $C->HDR_SHOW_FAVICON == 1 ) {
				$html .= (!$get_link)? '<link href="'. $C->STATIC_URL .'images/favicon.ico" type="image/x-icon" rel="shortcut icon" />'."\n" : $C->STATIC_URL .'images/favicon.ico';
			} elseif( $C->HDR_SHOW_FAVICON == 2 ) {
				$html .= (!$get_link)? '<link href="'. $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_FAVICON .'" type="image/x-icon" rel="shortcut icon" />'."\n" : $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_FAVICON;
			}
		
			return $html;
		}
		
		public function loadNetworkLogo()
		{
			global $C;
			$network = & $GLOBALS['network'];
		
			$html = '<a href="'. $C->SITE_URL .'home" title="'. htmlspecialchars($C->SITE_TITLE) .'" class="system-logo">';
		
			if( $C->HDR_SHOW_LOGO==2 && !empty($C->HDR_CUSTOM_LOGO) ) {
				$html .= '<img src="'. $C->STORAGE_URL .'attachments/'. $network->id .'/'. $C->HDR_CUSTOM_LOGO .'" alt="'. htmlspecialchars($C->SITE_TITLE) .'" />';
			} else {
				$html .= '<img src="'. $C->STATIC_URL .'images/logo.png" alt="'. htmlspecialchars($C->SITE_TITLE) .'" />';
			}
		
			$html .= '</a>';
		
			return $html;
		}
		
		public function createInfoBlock( $title, $content)
		{
			if( !is_string($content) || empty($content) ) return '';
			return '<div class="section-container">'.((isset($title) && !empty($title)) ? '<h3 class="section-title">'. $title .'</h3>' : ''). $content .'</div>';
		}
		
		public function createUserLinks($user_array, $size = 'thumbs2')
		{
			global $C;
			$html = '';
		
			foreach( $user_array as $u ){
				$html .= '<a href="'. userlink($u['username']) .'" class="slimuser" title="'. htmlspecialchars($u['username']) .'"><img src="'. $C->STORAGE_URL .'avatars/'.$size.'/'. $u['avatar'] .'" alt=""/></a>';
			}
		
			return $html;
		}
		
		public function createTableDetailsBlock( $title = '', $data, $cssClass = '', $show_semicolon = TRUE )
		{
			$html = '';
		
			if( ! count( $data ) ){
				return $html;
			}
		
			$html .= '<div'. (($cssClass != '') ? ' class="'.$cssClass.'"' : "") .'><h3>'.$title.'</h3><ul>';
		
			foreach( $data as $k=>$v ){
				$html .= '<li><em>'. htmlspecialchars($k) .($show_semicolon? ':' : '').'</em> <strong>'. htmlspecialchars($v) .'</strong></li>';
			}
		
			$html .= '</ul></div>';
		
			return $html;
		}
		
	}
<?php
	class pageDesignerSystem extends pageDesigner
	{
		public function dropDownMenu( $link_title, $menu_items = array(), $dropdown_css_class='', $menu_options_css_class='', $tooltip = false)
		{
			//need also aoptional additional dropdown CSS
			//need also aoptional additional menu-option CSS
			global $C;
			
			$html = '<div class="dropdown'.(!empty($dropdown_css_class)? ' '.$dropdown_css_class : '').'">
					<a href="" class="menu-btn'.(!empty($menu_options_css_class)? ' '.$menu_options_css_class : '').'">'.(($tooltip) ? '<span class="tooltip"><span>'.$link_title.'</span></span>' : $link_title).'</a>
					<ul class="menu-options">';
			foreach( $menu_items as $item ){
				$data_attr = "";
				if (isset($item['data_attributes']) && !empty($item['data_attributes'])) {
					foreach($item['data_attributes'] as $attr => $value) {
						//$data_attr = $attr
						$data_attr .= 'data-'.$attr.'="'.$value.'" ';
					}
				}
				
				//@TODO: use createMenuLink() method for this menu type
				$item['url'] 	= ( strpos($item['url'], 'http://') === FALSE )? 'http://'.$item['url'] : $item['url'];
				$target_blank 	= ( strpos($item['url'], $C->SITE_URL) === FALSE )? ' target="_blank" ' : '';
				
				$html .= '<li><a href="'. $item['url'] .'" '. (isset($item['css_class'])? 'class="'.$item['css_class'].'"' : '') .$data_attr.$target_blank.'>'. $item['text'] .'</a></li>';
			}
		
			$html .= '</ul></div>';
		
			return $html;
				
		}
		
		public function pager( $num_results, $num_pages, $pg, $paging_url )
		{
			/*
			* @param $num_results - num results
			* @param $num_pages - num pages
			* @param $pg - current page
			* @param $paging_url - current page url structure
			*
			*/
		
			global $C;
			$page = & $GLOBALS['page'];
			$user = & $GLOBALS['user'];
			$html = '';
		
			if( $num_pages <= 1 ) {
				return $html;
			}
		
			$html .= '<div class="pager">
						<span class="pager-item title">'. $page->lang('paging_title') .'</span>';
		
			if($pg > 3) {
				$html .= '<a href="'. $paging_url . ($pg-1) .'" class="pager-item next-prev prev">Previous</a>';
			}
		
				
			if($pg <= 2) {
				$mn	= 1;
				$mx	= min(5, $num_pages);
			}
			elseif($pg >= $num_pages-2) {
				$mn = $num_pages - min(5, $num_pages) + 1;
				$mx = $num_pages;
			}
			else {
				$mn = $pg-2;
				$mx = $pg+2;
			}
			for($i=$mn; $i<=$mx; $i++) {
				$html .= '<a href="'. ( ($user->is_logged)? $paging_url.$i : $C->SITE_URL.'signup' ) .'" class="pager-item '. ($i==$pg?'current':'') .'">'.$i.'</a>';
			}
		
			if($pg < $num_pages-2) {
				$html .= '<a href="'. ( ($user->is_logged)? $paging_url:$C->SITE_URL.'signup' ) . ($pg+1) .'" class="pager-item next-prev next">Next</a>';
				//$html .= ( ($user->is_logged)? $paging_url:$C->SITE_URL.'signup' ) . ($pg+1);
			}
		
			$html .= '</div>
				<div class="clear"></div>';
		
			return $html;
		}
		
		public function getJSData()
		{
			global $C;
				
			$this->system_files = array(
					'jquery',
					'jquery-ui',
					'plugins/jquery.address',
					'plugins/jquery.ajaxupload',
					//''plugins/jquery.fileuploader',
					'plugins/jquery.hoverintent',
					'plugins/jquery.colorbox',
					'common',
					'services',
					'htmlarea',
					'attachments',
					'activities',
					'comments',
					'users',
					'administration',
					'notifications',
					'groups',
					'dialogs'
			);	
			
			return $this->loadJSData();
		}
		
		public function getCSSData()
		{
			global $C;
		
			$this->system_files = array( 
						'framework' 
			);
		
			return $this->loadCSSData();
		}
		
		public function createTagLinks($tag_array, $search_where = 'tags')
		{
			if( !is_array($tag_array) || !count($tag_array) ){
				return '';
			}
		
			global $C;
			$html = '<div class="tags">';
		
			foreach( $tag_array as $t ){
				$html .= '<a href="'. $C->SITE_URL .'search/tab:'.$search_where.'/s:'. $t .'" title="#'. htmlspecialchars($t) .'"><small>#</small>'. htmlspecialchars(str_cut($t,25)) .'</a> ';
			}
			$html .= '</div>';
		
			return $html;
				
		}
		
		public function whatToDoBlock()
		{
			global $C;
		
			$user = & $GLOBALS['user'];
			$page = & $GLOBALS['page'];
		
			$show = FALSE;
		
			if( ! $user->is_logged ) {
				return array();
			}
		
			$html = '<div class="todo">
			<h3>'. $page->lang('dbrd_whattodo_title') .'</h3>
			<ul>';
		
			$html_tmp = '';
		
			if( empty($user->info->position) && empty($user->info->location) && 0==intval($user->info->birthdate) && empty($user->info->gender) && empty($user->info->about_me) && 0==count($user->info->tags) ) {
				$html_tmp .= '<li><a href="'. $C->SITE_URL .'settings/profile" >'. $page->lang('os_dbrd_whattodoo_profile') .'</a></li>';
			}
		
			$tmp	= '';
			if( $user->sess['cdetails'] ) {
				unset($user->sess['cdetails']->user_id);
				foreach($user->sess['cdetails'] as $v) { $tmp .= $v; }
			}
			if( empty($tmp) ) {
				$html_tmp .= '<li><a href="'. $C->SITE_URL .'settings/contacts" >'. $page->lang('os_dbrd_whattodoo_contacts') .'</a></li>';
			}
			if( $user->info->avatar == $C->DEF_AVATAR_USER ) {
				$html_tmp .= '<li><a href="'. $C->SITE_URL .'settings/avatar" >'. $page->lang('os_dbrd_whattodoo_avatar') .'</a></li>';
			}
		
			$html =	!empty( $html_tmp )? $html.$html_tmp.'</ul></div>' : '';
		
			return $html;
		}
		
		public function groupsSettingsMenu( $group_id, $follow, $is_admin = FALSE, $group_name = '' )
		{
			global $C;
			
			$settings_items = array(
					array('url'=> '#', 'text'=> 'Leave', 'data_attributes' => array('role' => 'services', 'namespace' => 'groups',  'action' => 'leave', 'value' => $group_id)),
					//array('url'=> '#', 'text'=> 'Notifications'),
					//array('url'=> $C->SITE_URL.$group_name.'/tab:invite', 'text'=> 'Invite'), //ako ima prava
			);
			if( $is_admin ){
				$settings_items[] = array('url'=> $C->SITE_URL.$group_name.'/tab:settings', 'text'=> 'Settings');
			}
		
			return
			($follow?
					'<a class="action-btn user-action add" data-action="join" data-value="'.$group_id.'" data-namespace="groups" data-role="services"><span class="tooltip"><span>Join</span></span></a>' :
					$this->dropDownMenu('Settings', $settings_items, '', 'action-btn options', true));
		
		}
		
		public function usersSettingsMenu( $user_id, $follow, $regime='friendship', $action = '' )
		{
			global $user;
			
			$html = '';
			
			if( !$user->is_logged ){
				return $html;
			}
			
			switch( $regime ){
				
				case 'friendship' : 
					$follow ?
						$html = '<a class="action-btn user-action add" data-action="follow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span class="tooltip"><span>Follow</span></span></a>' :
						$html = '<a class="action-btn user-action disconnect-user" data-action="unfollow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span class="tooltip"><span>Unfollow</span></span></a>';
					break;
					
				case 'administration' :
						$html = '<a class="action-btn user-action remove-user" data-action="'.$action.'" data-value="'.$user_id.'" data-namespace="administration" data-role="services"><span class="tooltip"><span>'.ucfirst( str_replace('_', ' ', $action)).'</span></span></a>';
					break;
			}

			return $html;
					
		}
	}
	
	
	
<?php
	function createMenu( $menu_class, & $menu_items, $placeholder='' )
	{
		/* 
		 * 1. Menu Class Type - feed-navigation( left menu ), tabs-navigation (tab menu)
		 * 2. Array( array('url'=>'', 'css_class'=>'', 'title'=>'', 'tab_state'=>'') );
		 *
		 */
		
		if( !is_array($menu_items) ){
			return '';
		}
		
		global $C;
		$page = & $GLOBALS['page'];
		
		$cur_address = implode('/', $page->request).'/'; //(;
		
		$html = '<ul class="'.$menu_class.'">';
		
		foreach( $menu_items as $item ){
			$tab_state = ( isset($item['tab_state']) && is_numeric($item['tab_state']) && ($address !== $cur_address) )? intval($item['tab_state']) : 0;
			$html .= '<li><a href="'. $C->SITE_URL . $item['url'] .'" class="'. (isset($item['css_class'])? $item['css_class'] : '' ) . (($item['url'] == $cur_address)? ' selected':'') .'"><span>'. $item['title'] .'</span>'. ($tab_state? '<span class="new-items-count"><span>'. $tab_state .'</span></span>' : '') .'</a></li>';
		}
		
		$html .= !empty($placeholder)? '{%'.$placeholder.'%}' : '';
		$html .= '</ul>';
		
		return $html;
	}
	
	function dropDownMenu( $link_title, $menu_items = array(), $dropdown_css_class='', $menu_options_css_class='', $tooltip = false)
	{
		//need also aoptional additional dropdown CSS
		//need also aoptional additional menu-option CSS
		
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
			$html .= '<li><a href="'. $item['url'] .'" '. (isset($item['css_class'])? 'class="'.$item['css_class'].'"' : '') .$data_attr.'>'. $item['text'] .'</a></li>';
		}

		$html .= '</ul></div>';
		
		return $html;
					
	}
	
	function createMenuLink( $item )
	{
		if( !is_array($item) ){
			return '';
		}
		
		global $C, $page;
		$cur_address = implode('/', $page->request).'/'; //(;
		
		return '<li><a href="'. $C->SITE_URL . $item['url'] .'" class="'. (isset($item['css_class'])? $item['css_class'] : '' ) . (($item['url'] == $cur_address)? ' selected':'') .'"><span>'. $item['title'] .'</span></a></li>';
	}
	
	function pager( $num_results, $num_pages, $pg, $paging_url )
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
	
	function createNoPostBox( $title, $text )
	{
		return '<div class="noposts">
				<h3>'. $title .'</h3>
				<p>'. $text .'</p>			
		</div>';
	}
	
	function okMessage($title, $text, $closebtn=TRUE, $incss='')
	{

		$html	= '
				<div class="system-message success">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
		return $html;
	}
	
	function errorMessage($title, $text, $closebtn=TRUE, $incss='')
	{
		
		$html	= '
				<div class="system-message error">
					<strong>'.$title.'</strong>'.$text.'
				</div>';
		return $html;
	}
	
	function getMetaData()
	{ 
		$html = '';
		
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
		$html .= '<meta name="keywords" content="microblogging, sharetronix, blogtronix, enterprise microblogging">'."\n";
		
		return $html;
	}
	
	function getJSData()
	{
		global $C, $plugins_manager, $page;
		
		$html 		= '';
		$js_location 	= $C->STATIC_URL.'js/';
		
		$js_files = ($page->is_mobile) ?
		
			array( 
				'jquery',
				'mobile/jquery.mobile.config',
				'mobile/jquery.mobile',
					
				'mobile/attachments',
					
				'mobile/common',
				'services',
				
					
				'mobile/htmlarea',
				'mobile/activities', 
				'mobile/comments', 
				'mobile/users', 
				'mobile/groups'
			) :
			
			array( 
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
				'groups', 
				'dialogs'
			);
		
		$html .= '<script type="text/javascript"> var siteurl = "'. $C->SITE_URL .'"; </script>'."\n";
		foreach( $js_files as $j ){
			$html .= '<script type="text/javascript" src="'.$js_location.$j.'.js?v='.$C->VERSION.'"></script>'."\n";
		}
		
		if( $C->THEME != 'default' ){
			if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/js/')) {
	
				while (FALSE !== ($entry = readdir($handle))) {
					if( $entry !== '.' && $entry !== '..' ){
						$html .= '<script type="text/javascript" src="'.$C->SITE_URL.'themes/'.$C->THEME.'/'. $entry .'?v='.$C->VERSION.'"></script>'."\n";
					}
				}
	
				closedir($handle);
			}
		}
		//check for plugins JS files
		$installed_plugins = $plugins_manager->getInstalledPluginNames();
		foreach( $installed_plugins as $p ){
			if( is_dir( $C->PLUGINS_DIR.$p.'/static/js/' ) ){
				if ($handle = @opendir($C->PLUGINS_DIR.$p.'/static/js/')) {
				
					while (FALSE !== ($entry = readdir($handle))) {
						if( $entry !== '.' && $entry !== '..' ){
							$html .= '<script type="text/javascript" src="'.$C->OUTSIDE_SITE_URL.'plugins/'.$p.'/static/js/'. $entry .'?v='.$C->VERSION.'"></script>'."\n";
						}
					}
				
					closedir($handle);
				}
			}
		}
		
		return $html;
		
	}
	
	function getCSSData()
	{
		global $C, $plugins_manager, $page;
		
		$html 		= '';
		$css_location 	= $C->STATIC_URL.'css/';
		//$css_files		= array( 'framework' );
		
		$css_files = ($page->is_mobile) ? 
			array( 'jquery.mobile', 'mobile' ) : 
			array( 'framework' ); 
		
		if( !isset($C->THEME_CSS_OVERWRITE) ){
			foreach( $css_files as $css ){
				$html .= '<link href="'. $css_location . $css .'.css?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
			}
		}
		
		if( $C->THEME != 'default' ){
			if ($handle = @opendir($C->INCPATH.'../themes/'.$C->THEME.'/css/')) {
	
				while (FALSE !== ($entry = readdir($handle))) {
					if( $entry !== '.' && $entry !== '..' ){
						$html .= '<link href="'. $C->SITE_URL .'themes/'. $C->THEME .'/'. $entry .'.css?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
					}
				}
	
				closedir($handle);
			}
		}
		
		//check for plugins CSS files
		$installed_plugins = $plugins_manager->getInstalledPluginNames();
		foreach( $installed_plugins as $p ){
			if( is_dir( $C->PLUGINS_DIR.$p.'/static/css/' ) ){
				if ($handle = @opendir($C->PLUGINS_DIR.$p.'/static/css/')) {
					while (FALSE !== ($entry = readdir($handle))) {
						if( $entry !== '.' && $entry !== '..' ){
							$html .= '<link href="'. $C->OUTSIDE_SITE_URL .'plugins/'. $p .'/static/css/'. $entry .'?v='. $C->VERSION .'" type="text/css" rel="stylesheet" />'."\n";
						}
					}
				closedir($handle);
				}
			}
		}
		
		return $html;
	}
	
	function getFaviconData( $get_link = FALSE )
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
	
	function loadNetworkLogo()
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
	
	function createInfoBlock( $title, $content)
	{
			if( !is_string($content) || empty($content) ) return '';
			return '<div class="section-container">'.((isset($title) && !empty($title)) ? '<h3 class="section-title">'. $title .'</h3>' : ''). $content .'</div>';
	}
	
	function createUserLinks($user_array, $size = 'thumbs2')
	{
		global $C;
		$html = '';
		
		foreach( $user_array as $u ){
			$html .= '<a href="'. userlink($u['username']) .'" class="slimuser" title="'. htmlspecialchars($u['username']) .'"><img src="'. $C->STORAGE_URL .'avatars/'.$size.'/'. $u['avatar'] .'" alt=""/></a>';
		}	
		
		return $html;
	}
	
	function createTagLinks($tag_array, $serach_where = 'tags')
	{
		if( !is_array($tag_array) || !count($tag_array) ){
			return '';
		}
		
		global $C;
		$html = '<div class="tags">';
		
		foreach( $tag_array as $t ){
			$html .= '<a href="'. $C->SITE_URL .'search/tab:'.$serach_where.'/s:'. $t .'" title="#'. htmlspecialchars($t) .'"><small>#</small>'. htmlspecialchars(str_cut($t,25)) .'</a> ';
		}
		$html .= '</div>';
		
		return $html;
									
	}
	
	function whatToDoBlock()
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
	
	function createTableDetailsBlock( $title = '', $data, $cssClass = '' )
	{
		$html = '';
		
		if( ! count( $data ) ){
			return $html;
		}
		
		$html .= '<div'. (($cssClass != '') ? ' class="'.$cssClass.'"' : "") .'><h3>'.$title.'</h3><ul>';
		
		foreach( $data as $k=>$v ){
			$html .= '<li><em>'. $k .':</em> <strong>'. $v .'</strong></li>';
		}
		
		$html .= '</ul></div>';
		
		return $html;
	}
	
	function createTableInput( $title='', $rows, $description='', $action = FALSE, $enctype=''  )
	{
		global $C;
		
		$html =  !empty($title)? '<h3>'. $title .'</h3>' : '';
		$html .= !empty($description)? '<div class="form-description">'.$description.'</div>' : '';
		$html .= '<form action="'.($action? $C->SITE_URL.$action : '').'" method="POST" '.(!empty($enctype)? $enctype : '').' >';
		$html .= '<table class="form-container">';
		

		foreach( $rows as $r ){
			$html .= $r;
		}
		
		$html .= '	{%input_table_additional_field%}
					</table></form>
				 ';
		
		return $html;
	}
	
	function inputField( $row_name, $form_name, $form_value, $max_length=50 )
	{
		return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="text" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" maxlength="'. $max_length .'" autocomplete="off" /></td>
				</tr>';
	}
	function fileField( $row_name, $form_name, $form_value='' )
	{
		return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="file" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" /></td>
				</tr>';
	}
	function hiddenField( $form_name, $form_value='' )
	{
		return '<input type="hidden" id="'.$form_name.'" name="'. $form_name .'" value="'. $form_value .'" />';
	}
	function passField( $row_name, $form_name, $form_value='' )
	{
		return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><input type="password" id="'. $form_name .'" name="'. $form_name .'" value="'. $form_value .'" autocomplete="off" /></td>
				</tr>';
	}
	function textField( $row_name, $row_content )
	{
		return '<tr>
					<td class="field-title">'. $row_name .'</td>
					<td>'. $row_content .'</td>
				</tr>';
	}
	
	function textArea( $row_name, $form_name, $form_value = '' )
	{
		return '<tr>
					<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
					<td><textarea id="'. $form_name .'" name="'. $form_name .'" >'. $form_value .'</textarea></td>
				</tr>';
	}
	
	function selectField( $row_name, $form_name, $option_elements, $selected = '' )
	{
		$html ='<tr>
				<td class="field-title"><label for="'. $form_name .'">'. $row_name .'</label></td>
				<td>
					<select id="'. $form_name .'" name="'. $form_name .'" >';
		
		foreach($option_elements as $k=>$v) { 
				$html .= '<option value="'.$k.'"'. ( ($k==$selected)?' selected="selected"':'' ) .'>'. htmlspecialchars($v) .'</option>';
		}
		$html .= '</select>
				</td></tr>';
		
		return $html;
	}
	
	function checkBox( $row_name, $checkbox_elements)
	{
		//check for selected 
		$html = '<tr><td class="field-title">'. $row_name .'</td><td>';
		
		foreach( $checkbox_elements as $v ){
			$html .= '<label style="width:120px; float:left; clear:none;"><input type="checkbox" name="'.$v[0].'" value="'.$v[1].'" '.($v[1] == $v[3]? 'checked' : '').' /> <span>'.$v[2].'</span></label>';
		}
		$html .= '</td></tr>';											
													
		return $html;
	}
	
	function radioButton( $table_row_name, $radio_btns_name, $radio_buttons, $radio_btn_selected = '' )
	{
		//check for selected
		$html = '<tr><td class="field-title">'. $table_row_name .'</td><td>';
		
		foreach( $radio_buttons as $name=>$description ){
			$html .= '<label class="field-container"><input type="radio" name="'. $radio_btns_name .'" value="'. $name .'" '. ( ($name == $radio_btn_selected)? 'checked' : '' ) .' /> <span>'. $description .'</span></label>';
		}
		$html .= '<div class="clear"></div></td></tr>';
			
		return $html;
	}
	
	function submitButton( $name, $value )
	{
		return '<tr>
					<td></td>
					<td><button type="submit" name="'.$name.'" class="btn blue"><span>'.$value.'</span></button></td>
				</tr>';
	}
	
	function groupsSettingsMenu( $group_id, $follow, $is_admin = FALSE, $group_name = '' )
	{
		global $C;
		
		$settings_items = array(
				array('url'=> '#', 'text'=> 'Leave', 'data_attributes' => array('role' => 'services', 'namespace' => 'groups',  'action' => 'leave', 'value' => $group_id)),
				//array('url'=> '#', 'text'=> 'Notifications'),
				//array('url'=> '#', 'text'=> 'Invite'), //ako ima prava
		);
		if( $is_admin ){
			$settings_items[] = array('url'=> $C->SITE_URL.$group_name.'/tab:settings', 'text'=> 'Settings');
		}
		
		return 
			($follow?
				'<a class="action-btn user-action add" data-action="join" data-value="'.$group_id.'" data-namespace="groups" data-role="services"><span class="tooltip"><span>Join</span></span></a>' :
				dropDownMenu('Settings', $settings_items, '', 'action-btn options', true));

	}
	
	function usersSettingsMenu( $user_id, $follow )
	{
		return 
		$follow ?
				'<a class="action-btn user-action add" data-action="follow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span class="tooltip"><span>Follow</span></span></a>' :
				'<a class="action-btn user-action disconnect-user" data-action="unfollow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span class="tooltip"><span>Unfollow</span></span></a>';
			
	}
	
?>
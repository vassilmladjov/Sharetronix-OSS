<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_themes', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$themes = new AdminThemes($tpl);
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( !$this->param('tab') ){
		$menu = array( 	array('url' => 'admin/themes', 			'css_class' => '', 			'title' => $this->lang('admin_themes_tab_downloaded') ),
						array('url' => 'admin/themes/getnew', 	'css_class' => 'active', 	'title' => $this->lang('admin_themes_tab_getnew') )
		);
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu) );
	}
	
	if( $this->param('installed') == 'ok' ){
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('admgnrl_okay'), $this->lang('admin_themes_installed_msg')) );
	}elseif( $this->param('uninstalled') == 'ok' ){
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('admgnrl_okay'), $this->lang('admin_themes_uninstalled_msg')) );
	}
	
	switch( $this->param('tab') ){
		case 'view':
			
			$theme_id = $this->param('item_id') ? (int)$this->param('item_id') : 0; 
			$themes->viewTheme($theme_id);
			
			break;
		
		case 'install':
			
			$theme_id = $this->param('item_id')? (int)$this->param('item_id') : 0;
			$themes->install( $theme_id );
			
			break;
		
		case 'confirm_install':

			$theme_path = $this->param('item_path') ? base64_decode($this->param('item_path')) : FALSE;
			$theme_id = $this->param('item_id') ? (int)$this->param('item_id') : FALSE;
			$themes->confirm_install( $theme_id, $theme_path );
				
			break;
		
		case 'uninstall':
				
			$theme_id = $this->param('item_id')? (int)$this->param('item_id') : 0;
			$themes->uninstall( $theme_id );
				
			break;
		
		case 'confirm_uninstall':
		
			$theme_path = $this->param('item_path') ? base64_decode($this->param('item_path')) : FALSE;
			$theme_id = $this->param('item_id') ? (int)$this->param('item_id') : FALSE;
			$themes->confirm_uninstall( $theme_id, $theme_path );
		
			break;
			
		default:
			
			$themes->index();
			
			break;
	}
	
	$tpl->display();
	
?>
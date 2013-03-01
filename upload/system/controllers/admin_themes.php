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
	
	$themes_selection = AdminThemes::getInstalledThemes();
	
	$changetheme_flag	= FALSE;
	$changetheme_warn	= FALSE;
	
	require_once( $C->INCPATH.'helpers/func_images.php' );
	
	$hdr_show_logo		= $C->HDR_SHOW_LOGO;
	$hdr_custom_logo	= empty($C->HDR_CUSTOM_LOGO) ? '' : ('attachments/'.$this->network->id.'/'.$C->HDR_CUSTOM_LOGO);
	$hdr_show_favicon	= $C->HDR_SHOW_FAVICON;
	$hdr_custom_favicon	= empty($C->HDR_CUSTOM_FAVICON) ? '' : ('attachments/'.$this->network->id.'/'.$C->HDR_CUSTOM_FAVICON);

	if( isset($_POST['set_theme']) && $_POST['set_theme']!=$C->THEME && isset($themes_selection[$_POST['set_theme']]) ) {
		$C->THEME		= $_POST['set_theme'];
		$C->THEMEOBJ	= $themes_selection[$_POST['set_theme']];
		$db2->query('REPLACE INTO settings SET word="THEME", value="'.$db2->e($C->THEME).'" ');
		$changetheme_flag	= TRUE;
		$ok	= FALSE;
		if( $hdr_show_logo == 1 ) {
			$ok	= TRUE;
		}
		elseif( $hdr_show_logo == 2 ) {
			if( isset($C->{'HDR_CUSTOM_LOGO_'.$C->THEME}) && !empty($C->{'HDR_CUSTOM_LOGO_'.$C->THEME}) ) {
				$fn	= $C->{'HDR_CUSTOM_LOGO_'.$C->THEME};
				if( file_exists($C->IMG_DIR.'attachments/'.$this->network->id.'/'.$fn) ) {
					$ok	= TRUE;
					$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO", value="'.$db2->e($fn).'" ');
					$hdr_custom_logo	= 'attachments/'.$this->network->id.'/'.$fn;
					$changetheme_warn	= TRUE;
				}
			}
			if( !$ok && !empty($hdr_custom_logo) ) {
				$fn	= 'logo_'.time().rand(100000,999999).'.png';
				$ok	= networkbranding_logo_resize($C->IMG_DIR.$hdr_custom_logo, $C->IMG_DIR.'attachments/'.$this->network->id.'/'.$fn, intval($C->THEMEOBJ->logo_height));
				if( $ok ) {
					$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO", value="'.$db2->e($fn).'" ');
					$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO_'.$db2->e($fn).'", value="" ');
					$hdr_custom_logo	= 'attachments/'.$this->network->id.'/'.$fn;
					$changetheme_warn	= TRUE;
				}
			}
		}
		if( ! $ok ) {
			$db2->query('REPLACE INTO settings SET word="HDR_SHOW_LOGO", value="1" ');
			$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO", value="" ');
			$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO_'.$db2->e($C->THEME).'", value="" ');
			$hdr_show_logo		= 1;
			$hdr_custom_logo	= '';
			$changetheme_warn	= TRUE;
		}
		$this->network->load_network_settings($db2);
		$this->_set_template();
		invalidateCachedHTML(); 
		$this->redirect('admin/themes');
	}
	
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_themes', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$themes = new AdminThemes( $tpl );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	$menu = array( 	array('url' => 'admin/themes', 			'css_class' => 'active', 'title' => $this->lang('admin_themes_tab_downloaded') ),
					array('url' => 'admin/themes/getnew', 	'css_class' => '', 		 'title' => $this->lang('admin_themes_tab_getnew') )
	);
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu) );
	
	if( $changetheme_flag ) {
		if( ! $changetheme_warn ) {
			$tpl->designer->okMessage($this->lang('admbrnd_th_theme_ok1'), $this->lang('admbrnd_th_theme_ok2'));
		} else {
			$tpl->designer->okMessage($this->lang('admbrnd_th_theme_ok2'), $this->lang('admbrnd_th_theme_ok3',array('#A1#'=>'<a href="'.$C->SITE_URL.'admin/networkbranding">','#A2#'=>'</a>')));
		}
	}

	$table = new tableCreator();
	$table->form_description = '<h1>'.$this->lang('admbrnd_th_title').'</h1>';
	$rows = array();
	
	foreach( $themes_selection as $theme_name => $theme_options ){
		$theme_description = '';
		$theme_description .= isset($theme_options->author)? $theme_options->author.(isset($theme_options->email)? ' (<a href="mailto:'.$theme_options->email.'">'.$this->lang('admin_themes_email_txt').'</a>)' : '').'<br />' : '';
		$theme_description .= isset($theme_options->descr)? $theme_options->descr.(isset($theme_options->url)? ' <a href="'.$theme_options->url.'" target="_blank"><em>'.$this->lang('admin_themes_more_txt').'</em></a>' : '').'<br />' : '';
		$rows[] = $table->textField('<img src="'. $C->SITE_URL.'themes/'.$theme_name.'/'.$theme_options->icon .'" alt="" style="height: 50; width: 150px;" />', $theme_description);
		$rows[] = $table->radioButton( '', 'set_theme', array($theme_name=>'<strong>'.$theme_name.'</strong>'), (($theme_name == $C->THEME)? $theme_name : '')  );
	}
	$rows[] = $table->submitButton( 'sbm', $this->lang('admbrnd_th_theme_select') );

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
	
?>
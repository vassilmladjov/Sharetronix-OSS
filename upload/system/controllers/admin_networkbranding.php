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
	
	require_once( $C->INCPATH.'helpers/func_images.php' );
	
	$hdr_show_logo		= $C->HDR_SHOW_LOGO;
	$hdr_custom_logo	= empty($C->HDR_CUSTOM_LOGO) ? '' : ('attachments/'.$this->network->id.'/'.$C->HDR_CUSTOM_LOGO);
	$hdr_show_favicon	= $C->HDR_SHOW_FAVICON;
	$hdr_custom_favicon	= empty($C->HDR_CUSTOM_FAVICON) ? '' : ('attachments/'.$this->network->id.'/'.$C->HDR_CUSTOM_FAVICON);
	$C->LOGO_HEIGHT = 80;
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}

		if( !$error ){
			$hdr_show_logo	= 1;
			if( isset($_POST['hdr_show_logo']) && in_array(intval($_POST['hdr_show_logo']),array(0,1,2)) ) {
				$hdr_show_logo	= intval($_POST['hdr_show_logo']);
				if( $hdr_show_logo != 2 ) {
					$db2->query('REPLACE INTO settings SET word="HDR_SHOW_LOGO", value="'.$hdr_show_logo.'" ');
				}
				else {
					$f	= FALSE;
					if( isset($_FILES['custom_logo']) && is_uploaded_file($_FILES['custom_logo']['tmp_name']) ) {
						$f	= (object) $_FILES['custom_logo'];
					}
					if( !empty($C->HDR_CUSTOM_LOGO) && !$f ) {
						$db2->query('REPLACE INTO settings SET word="HDR_SHOW_LOGO", value="2" ');
					}
					elseif( empty($C->HDR_CUSTOM_LOGO) && !$f ) {
						$error	= TRUE;
						$errmsg	= $this->lang('admbrnd_frm_err_invalidfile');
					}
					else {
						list($w, $h, $tp) = getimagesize($f->tmp_name);
						if( $w==0 || $h==0 ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_invalidfile');
						}
						elseif( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_invalidformat');
						}
						elseif( $w < $C->LOGO_HEIGHT ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_toosmall');
						}
						else {
							$path	= $C->STORAGE_DIR.'attachments/'.$this->network->id.'/';
							$fn	= 'logo_'.time().rand(100000,999999).'.png';
							networkbranding_logo_resize($f->tmp_name, $path.$fn, $C->LOGO_HEIGHT);
							if( ! file_exists($path.$fn) ) {
								$error	= TRUE;
								$errmsg	= $this->lang('admbrnd_frm_err_cantcopy');
							}
							else {
								$db2->query('REPLACE INTO settings SET word="HDR_SHOW_LOGO", value="2" ');
								$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO", value="'.$db2->e($fn).'" ');
								$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_LOGO_'.$db2->e($C->THEME).'", value="'.$db2->e($fn).'" ');
								$hdr_custom_logo	= 'attachments/'.$this->network->id.'/'.$fn;
							}
						}
					} 
				}
			}
			$hdr_show_favicon	= 1;
			if( isset($_POST['hdr_show_favicon']) && in_array(intval($_POST['hdr_show_favicon']),array(0,1,2)) ) {
				$hdr_show_favicon	= intval($_POST['hdr_show_favicon']);
				if( $hdr_show_favicon != 2 ) {
					$db2->query('REPLACE INTO settings SET word="HDR_SHOW_FAVICON", value="'.$hdr_show_favicon.'" ');
				}
				else {
					$f	= FALSE;
					if( isset($_FILES['custom_favicon']) && is_uploaded_file($_FILES['custom_favicon']['tmp_name']) ) {
						$f	= (object) $_FILES['custom_favicon'];
					}
					if( !empty($C->HDR_CUSTOM_FAVICON) && !$f ) {
						$db2->query('REPLACE INTO settings SET word="HDR_SHOW_FAVICON", value="2" ');
					}
					elseif( empty($C->HDR_CUSTOM_FAVICON) && !$f ) {
						$error	= TRUE;
						$errmsg	= $this->lang('admbrnd_frm_err_ficn_invalidfile');
					}
					else {
						list($w, $h, $tp) = getimagesize($f->tmp_name);
						if( $w==0 || $h==0 ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_ficn_invalidfile');
						}
						elseif( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_PNG && $tp!=IMAGETYPE_ICO ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_ficn_invalidformat');
						}
						elseif( $w!=16 || $h!=16 ) {
							$error	= TRUE;
							$errmsg	= $this->lang('admbrnd_frm_err_ficn_badsize');
						}
						else {
							$path	= $C->STORAGE_DIR.'attachments/'.$this->network->id.'/';
							$fn	= 'favicon_'.time().rand(100000,999999).'.ico';
							copy( $f->tmp_name, $path.$fn );
							if( ! file_exists($path.$fn) ) {
								$error	= TRUE;
								$errmsg	= $this->lang('admbrnd_frm_err_ficn_cantcopy');
							}
							else {
								chmod($path.$fn, 0777);
								if( !empty($C->HDR_CUSTOM_FAVICON) ) {
									rm( $path.$C->HDR_CUSTOM_FAVICON );
								}
								$db2->query('REPLACE INTO settings SET word="HDR_SHOW_FAVICON", value="2" ');
								$db2->query('REPLACE INTO settings SET word="HDR_CUSTOM_FAVICON", value="'.$db2->e($fn).'" ');
								$hdr_custom_favicon	= 'attachments/'.$this->network->id.'/'.$fn;
							}
						}
					} 
				}
			}
		}
		$this->network->load_network_settings($db2);
	}
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_networkbranding', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admbrnd_frm_ok'), $this->lang('admbrnd_frm_ok_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admbrnd_frm_err'), $errmsg) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('admbrnd_frm_logo');
	$table->form_enctype = 'enctype="multipart/form-data"';
	
	$rows = array(
			$table->textField( '', $tpl->designer->loadNetworkLogo() ),
			$table->radioButton( '', 'hdr_show_logo', array('1'=>$this->lang('admbrnd_frm_logo_default', array('#SITE_TITLE#'=>$C->SITE_TITLE)), '2'=>$this->lang('admbrnd_frm_logo_custom')), $hdr_show_logo ),
			$table->fileField( $this->lang('admbrnd_frm_logo_custom_choose'), 'custom_logo', '' ),
			$table->submitButton( 'sbm', $this->lang('admgnrl_frm_sbm') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$table->form_title = $this->lang('admbrnd_frm_ficn');
	
	$rows = array(
			$table->textField( '', '<img src="'. $tpl->designer->getFaviconData( TRUE ).'" />'  ),
			$table->radioButton( '', 'hdr_show_favicon', array('1'=>$this->lang('admbrnd_frm_ficn_default', array('#SITE_TITLE#'=>$C->SITE_TITLE)), '2'=>$this->lang('admbrnd_frm_ficn_custom')), $hdr_show_favicon ),
			$table->fileField( $this->lang('admbrnd_frm_ficn_custom_choose'), 'custom_favicon', '' ),
			$table->submitButton( 'sbm', $this->lang('admgnrl_frm_sbm') )
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	
	$tpl->display();
?>
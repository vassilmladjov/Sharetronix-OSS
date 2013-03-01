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
	
	require_once( $C->INCPATH.'helpers/func_languages.php' );

	$version_check_result = '';
	if( (!isset($C->AUTOCHECK_FOR_UPDATE) || $C->AUTOCHECK_FOR_UPDATE) && function_exists('curl_init') ){
		$ch = new curlCall( 'http://www.developer.sharetronix.com/home/versioncheck/' );
		$ch->addPostData(	array(  'site_url' => urlencode($C->SITE_URL),
									'lang' => urlencode($C->LANGUAGE),
									'version' => urlencode($C->VERSION)));
		
		$D->req_result = $ch->getData();
		
		if( $D->req_result && is_string($D->req_result) ){ 
			$D->req_result = str_replace('http://', '', $D->req_result);
			$D->req_result = explode(':', $D->req_result);
			$result = FALSE;
				
			if(count($D->req_result)>= 2){
				$result['status'] = $D->req_result[0];
				$result['message'] = str_replace('developer.', 'http://developer.', $D->req_result[1]);
			}
		}
		
		$version_check_result = FALSE;
		if( isset($result['status']) && isset($result['message']) ){
			$version_check_result = TRUE;
		}
	}
	//
	$install_dir_not_deleted = FALSE;
	if( is_dir($C->INCPATH .'../install' ) ){
		$install_dir_not_deleted = TRUE;
	}
	
	$s	= new stdClass;
	$db2->query('SELECT word, value FROM settings');
	while($o = $db2->fetch_object()) {
		$s->{stripslashes($o->word)}	= stripslashes($o->value);
	}
	
	$menu_timezones	= array();
	if( floatval(substr(phpversion(),0,3)) >= 5.2 ) {
		$tmp	= array();
		foreach(DateTimeZone::listIdentifiers() as $v) {
			if( substr($v, 0, 4) == 'Etc/' ) { continue; }
			if( FALSE === strpos($v, '/') ) { continue; }
			$sdf	= new DateTimeZone($v);
			if( ! $sdf ) { continue; }
			$tmp[$v]	= $sdf->getOffset( new DateTime("now", $sdf) );
		}
		asort($tmp);
		foreach($tmp as $k=>$v) {
			$menu_timezones[$k]	= str_replace(array('/','_'), array(' / ',' '), $k);
		}
		asort($menu_timezones);
	}
	
	$menu_languages	= array();
	foreach(get_available_languages(FALSE) as $k=>$v) {
		$menu_languages[$k]	= $v->name;
	}
	
	$menu_postlength	= array(140=>140, 150=>150, 160=>160, 170=>170, 180=>180, 190=>190, 200=>200);
	
	$network_name	= $s->SITE_TITLE;
	$system_email	= $s->SYSTEM_EMAIL;
	$def_language	= $s->LANGUAGE;
	$def_timezone	= isset($s->DEF_TIMEZONE) ? $s->DEF_TIMEZONE : $C->DEF_TIMEZONE;
	$post_maxlength	= isset($s->POST_MAX_SYMBOLS) ? $s->POST_MAX_SYMBOLS : $C->POST_MAX_SYMBOLS;
	//$mobi_enabled	= $C->MOBI_DISABLED==0;
	$email_confirm	= $s->USERS_EMAIL_CONFIRMATION;
	$pages_protect 	= intval($s->PROTECT_OUTSIDE_PAGES);
	$api_status		= intval($s->API_STATUS);
	$check4update	= isset($s->AUTOCHECK_FOR_UPDATE) && $s->AUTOCHECK_FOR_UPDATE==0 ? FALSE : TRUE;
	$name_switch	= isset($s->NAME_INDENTIFICATOR)? $s->NAME_INDENTIFICATOR : 1;
	
	$intro_ttl	= isset($C->HOME_INTRO_TTL) ? trim($C->HOME_INTRO_TTL) : '';
	$intro_txt	= isset($C->HOME_INTRO_TTL) ? trim($C->HOME_INTRO_TXT) : '';
	if( empty($intro_ttl) && empty($intro_txt) ) {
		$this->load_langfile('outside/home.php');
		$intro_ttl	= $this->lang('os_welcome_ttl', array('#SITE_TITLE#'=>$C->SITE_TITLE));
		$intro_txt	= $this->lang('os_welcome_txt', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	}
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$refresh_page = FALSE;
	
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$mobi_enabled		= isset($_POST['mobi_enabled']) && $_POST['mobi_enabled']==1;
			$email_confirm		= isset($_POST['network_email_confirm']) && $_POST['network_email_confirm']==1;
			$pages_protect		= isset($_POST['network_protect_outside']) && $_POST['network_protect_outside']==1;
			$api_status			= isset($_POST['network_api_status']) && $_POST['network_api_status']==1;
			$check4update		= isset($_POST['check4update']) && $_POST['check4update']==1;
			$name_switch		= isset($_POST['name_switch'])? (($_POST['name_switch'] == 1 || $_POST['name_switch'] == 2)? intval($_POST['name_switch']) : 1) : 1;
			
			if( isset($_POST['network_post_length']) && in_array(intval($_POST['network_post_length']),$menu_postlength) ) {
				$post_maxlength	= intval($_POST['network_post_length']);
			}
			if( isset($_POST['network_timezone']) && isset($menu_timezones[$_POST['network_timezone']]) ) {
				$def_timezone	= $_POST['network_timezone'];
			}
			
			if( isset($_POST['network_language']) && isset($menu_languages[$_POST['network_language']]) ) {
				$def_language	= $_POST['network_language'];
				$old	= $db2->fetch_field('SELECT value FROM settings WHERE word="LANGUAGE" LIMIT 1');
				if( $old != $def_language ) {
					$db2->query('REPLACE INTO settings SET word="LANGUAGE", value="'.$db2->e($def_language).'" ');
					$db2->query('UPDATE users SET language="'.$db2->e($def_language).'" ');
					$this->network->get_user_by_id($this->user->id, TRUE);
				}
				
				$refresh_page = ($s->LANGUAGE != $def_language);
			}
			
			$db2->query('REPLACE INTO settings SET word="DEF_TIMEZONE", value="'.$db2->e($def_timezone).'" ');
			$db2->query('REPLACE INTO settings SET word="POST_MAX_SYMBOLS", value="'.$post_maxlength.'" ');
			//$db2->query('REPLACE INTO settings SET word="MOBI_DISABLED", value="'.intval(!$mobi_enabled).'" ');
			$db2->query('REPLACE INTO settings SET word="USERS_EMAIL_CONFIRMATION", value="'.intval($email_confirm).'" ');
			$db2->query('REPLACE INTO settings SET word="AUTOCHECK_FOR_UPDATE", value="'.intval($check4update).'" ');
			$db2->query('REPLACE INTO settings SET word="PROTECT_OUTSIDE_PAGES", value="'.intval($pages_protect).'" ');
			$db2->query('REPLACE INTO settings SET word="API_STATUS", value="'.intval($api_status).'" ');
			$db2->query('REPLACE INTO settings SET word="NAME_INDENTIFICATOR", value="'.$db2->e($name_switch).'" ');
			
			if( isset($_POST['network_name']) ) {
				$network_name	= trim($_POST['network_name']);
			}
			if( empty($network_name) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('admgnrl_err_netw');
			}
			elseif( preg_match('/[^ا-یא-תÀ-ÿ一-龥а-яa-z0-9\-\.\_\s\!\?]/iu', $network_name) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('admgnrl_err_netw2');
			}
			else {
				$db2->query('REPLACE INTO settings SET word="SITE_TITLE", value="'.$db2->e($network_name).'" ');
				$db2->query('REPLACE INTO settings SET word="COMPANY", value="'.$db2->e($network_name).'" ');
			}
			if( isset($_POST['network_email']) ) {
				$system_email	= trim($_POST['network_email']);
			}
			if( empty($system_email) || !is_valid_email($system_email) ) {
				$error	= TRUE;
				$errmsg	= $this->lang('admgnrl_err_email');
			}
			else {
				$db2->query('REPLACE INTO settings SET word="SYSTEM_EMAIL", value="'.$db2->e($system_email).'" ');
			}
			if( !$error ) {
				$intro_ttl	= isset($_POST['network_intro_title']) ? htmlspecialchars( trim($_POST['network_intro_title']) ) : '';
				$intro_txt	= isset($_POST['network_intro_txt']) ? htmlspecialchars( trim($_POST['network_intro_txt']) ) : '';
				if( empty($intro_ttl) ) {
					$error	= TRUE;
					$errmsg	= $this->lang('admgnrl_err_introttl');
				}
				elseif( empty($intro_txt) ) {
					$error	= TRUE;
					$errmsg	= $this->lang('admgnrl_err_introtxt');
				}
				else {
					$db2->query('REPLACE INTO settings SET word="HOME_INTRO_TTL", value="'.$db2->e($intro_ttl).'" ');
					$db2->query('REPLACE INTO settings SET word="HOME_INTRO_TXT", value="'.$db2->e($intro_txt).'" ');
				}
			}
			$this->network->load_network_settings();
		}
	}
	
	if( $refresh_page ){
		$this->redirect('admin/general/msg:saved');
	}
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_general', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( $version_check_result ){
		if( $result['status']=='OK' ){
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admin_general_check_for_updates'), $result['message'] ) );
		}else{
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admin_general_check_for_updates'), $result['message'] ) );
		}
	}
	if($install_dir_not_deleted){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admin_general_check_for_install_dir_ttl'), $this->lang('admin_general_check_for_install_dir_txt') ) );
	}
	
	if( ($submit && !$error) || $this->param('msg') == 'saved' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admgnrl_okay'), $this->lang('admgnrl_okay_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admemld_rem_error'), $errmsg) );
	}
	
	
	$table = new tableCreator();
	$table->form_title = $this->lang('admtitle_general');
	
	$rows = array(
			$table->inputField( $this->lang('admgnrl_frm_network'), 'network_name', $network_name ),
			$table->inputField( $this->lang('admgnrl_frm_intro_ttl'), 'network_intro_title', $intro_ttl ),
			$table->textArea( $this->lang('admgnrl_frm_intro_txt'), 'network_intro_txt', $intro_txt ),
			$table->inputField( $this->lang('admgnrl_frm_email'), 'network_email', $system_email ),
			$table->selectField( $this->lang('admgnrl_frm_deflang'), 'network_language', $menu_languages, $def_language ),
			$table->selectField( $this->lang('admgnrl_frm_deftzone'), 'network_timezone', $menu_timezones, $def_timezone ),
			$table->selectField( $this->lang('admgnrl_frm_postslen'), 'network_post_length', $menu_postlength, $post_maxlength ),
			$table->radioButton( $this->lang('admgnrl_frm_emlconfirm'), 'network_email_confirm', array('1'=>$this->lang('admgnrl_frm_mobi_on'), '0'=>$this->lang('admgnrl_frm_mobi_off')), $email_confirm ),
			$table->radioButton( $this->lang('admapistatus_val'), 'network_api_status', array('1'=>$this->lang('admgnrl_frm_mobi_on'), '0'=>$this->lang('admgnrl_frm_mobi_off')), $api_status ),
			$table->radioButton( $this->lang('admprotect_pages_name'), 'network_protect_outside', array('1'=>$this->lang('admgnrl_frm_mobi_on'), '0'=>$this->lang('admgnrl_frm_mobi_off')), $pages_protect ),
			$table->radioButton( $this->lang('admin_general_indent_ttl'), 'name_switch', array('1'=>$this->lang('admin_general_indent_opt1'), '2'=>$this->lang('admin_general_indent_opt2')), $name_switch ),
			$table->checkBox( '', array( array('check4update', 1, $this->lang('admgnrl_frm_check4update'), $check4update ) ) ),
			$table->submitButton( 'sbm', $this->lang('admgnrl_frm_sbm') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>
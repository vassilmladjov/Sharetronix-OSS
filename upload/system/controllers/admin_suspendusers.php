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
	
	$D->users	= array();
	$r	= $db2->query('SELECT id FROM users WHERE active=0 ORDER BY fullname ASC, username ASC');
	while($tmp = $db2->fetch_object($r)) {
		if($sdf = $this->network->get_user_by_id($tmp->id)) {
			$D->users[]	= $sdf;
		}
	}
	
	$submit = FALSE;
	$error = FALSE;
	
	if( isset($_POST['admin']) ) {
		$submit = TRUE;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$admins	= trim($_POST['admin']);
			$a	= $this->network->get_user_by_username($admins);
			if( $a ) {
				$admin = new communityAdministration();
				$admin->suspendUser($a->id);
			}
			$this->redirect( $C->SITE_URL.'admin/suspendusers/msg:suspsaved' );
		}
	}
	
	$res = $db2->query('SELECT * FROM users WHERE active=0');
	
	$suspuser = (isset($_GET['usrtosusp']) && !empty($_GET['usrtosusp']))? trim(htmlspecialchars(urldecode($_GET['usrtosusp']))) : '';
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_suspendusers', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( ($submit && !$error) || $this->param('msg') == 'suspsaved' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admsusp_frm_ok'), $this->lang('admsusp_frm_ok_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admdelu_error'), $errmsg) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('admtitle_suspendusers');
	
	$rows = array(
			$table->inputField( $this->lang('admsusp_frm_add'), 'admin', $suspuser ),
			$table->submitButton( 'submit', $this->lang('admgnrl_frm_sbm') )
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	if( $db2->num_rows($res) > 0 ){
		$tpl->layout->setVar('main_content_placeholder', $this->lang('admsusp_frm_adm'));
		
		while($obj = $db2->fetch_object($res)) {
			$tpl->initRoutine('SingleUser', array( &$obj, FALSE, '', 'administration', 'activate_user' ));
			$tpl->routine->load();
		}
	}
	
	
	$tpl->display();
?>
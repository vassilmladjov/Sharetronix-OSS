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
	
	global $plugins_manager;
	
	$D->admins	= array();
	$r	= $db2->query('SELECT id FROM users WHERE active=1 AND is_network_admin=1');
	while($tmp = $db2->fetch_object($r)) {
		if($sdf = $this->network->get_user_by_id($tmp->id)) {
			$D->admins[]	= $sdf;
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
				$this->db2->query('UPDATE users SET is_network_admin=1 WHERE id="'.intval($a->id).'" AND is_network_admin=0 LIMIT 1');
				$this->network->get_user_by_id($a->id, TRUE);
			}
			$this->redirect( $C->SITE_URL.'admin/administrators/msg:admsaved' );
		}
	}
	
	$ifollow = array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users );
	$res = $db2->query('SELECT * FROM users WHERE is_network_admin=1');

	$tpl = new template( array('page_title' => $this->lang('admpgtitle_administrators', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( ($submit && !$error) || $this->param('msg') == 'admsaved' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admtrms_ok_ttl'), $this->lang('admadm_frm_ok_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage('Error', $errmsg) );
	}
	
	$tpl->layout->setVar('main_content_placeholder', '<h2>' . $this->lang('admadm_frm_adm') . '</h2>');
	
	while($obj = $db2->fetch_object($res)) {
		$tpl->initRoutine('SingleUser', array( &$obj, &$ifollow, '', 'administration', 'remove_administrator' ));
		$tpl->routine->load();
	}
	
	$table = new tableCreator();
	
	$rows = array(
			$table->inputField( $this->lang('admadm_frm_add'), 'admin', '' ),
			$table->submitButton( 'submit', $this->lang('admgnrl_frm_sbm') )
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ) );
	
	$tpl->display();
?>
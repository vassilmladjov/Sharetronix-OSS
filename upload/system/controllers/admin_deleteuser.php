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
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$deluser	= '';
	if( isset($_POST['deluser'], $_POST['admpass']) ) {
		$submit	= TRUE;
		$deluser	= $_POST['deluser'];
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( empty($deluser) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('admdelu_err_user');
		}
		elseif( ! $u = $this->network->get_user_by_username($deluser) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('admdelu_err_user');
		}
		elseif( $u->id == $this->user->id ) {
			$error	= TRUE;
			$errmsg	= $this->lang('admdelu_err_user1');
		}
		elseif( !$this->user->checkPassByUserId(md5($_POST['admpass']), TRUE) ) {
			$error	= TRUE;
			$errmsg	= $this->lang('admdelu_err_pass');
		}
		else {
			$admin = new communityAdministration();
			if( $admin->deleteUser($u->id, md5($_POST['admpass'])) ){
				//$this->redirect('admin/deleteuser/msg:deleted');
			}
		}
	}
	
	$deluser = (isset($_GET['usrtodel']) && !empty($_GET['usrtodel']))? trim(htmlspecialchars(urldecode($_GET['usrtodel']))) : '';
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_deleteuser', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admdelu_ok'), $this->lang('admdelu_ok_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admbrnd_frm_err'), $errmsg) );
	} else{		
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->informationMessage($this->lang('admdelu_submit'), $this->lang('admdelu_descr')) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('admtitle_deleteuser');
	
	$rows = array(
			$table->inputField( $this->lang('admdelu_user'), 'deluser', $deluser ),
			$table->passField( $this->lang('admdelu_password'), 'admpass', '' ),
			$table->submitButton( 'sbm', $this->lang('admdelu_submit') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	//$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admdelu_ok'), $this->lang('admdelu_ok_txt') ) );	
	$tpl->display();
	
?>
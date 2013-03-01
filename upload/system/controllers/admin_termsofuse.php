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
	
	$tos_content	= '';
	$tos_enabled	= FALSE;
	
	if( isset($C->TERMSPAGE_ENABLED) && $C->TERMSPAGE_ENABLED==1 ) {
		$tos_enabled	= TRUE;
	}
	if( isset($C->TERMSPAGE_CONTENT) ) {
		$tos_content	= htmlspecialchars( trim(stripslashes($C->TERMSPAGE_CONTENT)) );
	}
	if( empty($tos_content) ) {
		$tos_enabled	= FALSE;
	}
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$okmsg	= '';
	if( isset($_POST['tos_content']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$tos_content	= htmlspecialchars( trim(stripslashes($_POST['tos_content'])) );
			$tos_enabled	= isset($_POST['tos_enabled'])&&$_POST['tos_enabled']==1;
			if( empty($tos_content) && $tos_enabled ) {
				$error	= TRUE;
				$errmsg	= $this->lang('admtrms_err_txt');
				$tos_enabled	= FALSE;
			}
			else {
				$db2->query('REPLACE INTO settings SET word="TERMSPAGE_ENABLED", value="'.($tos_enabled?1:0).'" ');
				$db2->query('REPLACE INTO settings SET word="TERMSPAGE_CONTENT", value="'.$db2->e($tos_content).'" ');
				$C->TERMSPAGE_ENABLED	= $tos_enabled?1:0;
				$C->TERMSPAGE_CONTENT	= $tos_content;
				$okmsg	= $tos_enabled ? $this->lang('admtrms_ok_txt2') : $this->lang('admtrms_ok_txt1');
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('admpgtitle_termsofuse', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admgnrl_okay'), $this->lang('admgnrl_okay_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admdelu_error'), $errmsg) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('admtitle_termsofuse');
	$table->form_description = $this->lang('admtrms_description');
	
	$rows = array(
			$table->textArea( '', 'tos_content', $tos_content ),
			$table->checkBox( $this->lang('admtrms_enable'), array( array('tos_enabled', 1, '', $tos_enabled? 1 : 0 ) ) ),
			$table->submitButton( 'sbm', $this->lang('admtrms_sbm') )
	);
	
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>
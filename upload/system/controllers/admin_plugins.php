<?php

	global $D;
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$this->db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $this->db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	$tpl = new template( array(
			'page_title' => $this->lang('admpgtitle_apps', array('#SITE_TITLE#'=>$C->SITE_TITLE)),
			'header_page_layout'=>'sc',
	));
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();
	
	$tabs = array('index', 'view', 'install', 'confirm_install', 'uninstall', 'confirm_uninstall', "enterStxKey");
	$tab = ( isset($this->params->tab) && in_array($this->params->tab, $tabs) ) ? $this->params->tab : current($tabs);
	
	if( $this->param('installed') == 'ok' ){
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('admadm_frm_ok'), $this->lang('admin_apps_installed_msg')) );
	}elseif( $this->param('uninstalled') == 'ok' ){
		$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->okMessage($this->lang('admadm_frm_ok'), $this->lang('admin_apps_uninstalled_msg')) );
	}
	
	$ap = new AdminPlugins($this->user, $tpl, $this->params);
	call_user_func(array($ap, $tab));
	
	$tpl->display();

?>
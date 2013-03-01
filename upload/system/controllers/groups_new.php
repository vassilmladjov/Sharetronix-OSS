<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/groups_new.php');

	$submit	= FALSE;
	$group_type = 'public';
	$group_alias = '';
	$group_name = '';
	$group_description = '';
	
	if( isset($_POST['sbm']) ) { 
		global $plugins_manager;
		
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		$submit	= TRUE;
		
		$group = new group();
		$errmsg = $group->createGroup();
		$error = !empty($errmsg)? TRUE : FALSE;
		
		$group_name			= isset($_POST['group_name'])? htmlspecialchars( trim($_POST['group_name']) ) : '';
		$group_alias		= isset($_POST['group_alias'])? htmlspecialchars( trim($_POST['group_alias']) ) : '';
		$group_description	= isset($_POST['group_description'])? htmlspecialchars( mb_substr(trim($_POST['group_description']) , 0, $C->POST_MAX_SYMBOLS) ) : '';
		$group_type			= isset($_POST['group_type'])? (trim($_POST['group_type'])=='private' ? 'private' : 'public') : '';

	}

	$tpl = new template( array('page_title' => $this->lang('newgroup_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$menu = array( 	array('url' => 'groups/tab:all', 	'title' => $this->lang('userselector_tab_all') ),
					array('url' => 'groups/tab:my', 	'title' => $this->lang('group_tabs_my_groups') ),
	);
	
	$tpl->layout->setVar( 'main_content_placeholder', $tpl->designer->createMenu('tabs-navigation', $menu) );
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_avatat_err'), $this->lang($errmsg) ) );
	}
	
	$table = new tableCreator();
	$table->form_enctype = 'enctype="multipart/form-data"';
	$rows = array(
			$table->inputField( $this->lang('group_settings_f_title'), 'group_name', $group_name), //title = name
			$table->inputField( $this->lang('group_settings_f_alias'), 'group_alias', $group_alias ), //name = alias = url
			$table->textArea( $this->lang('group_settings_f_descr'), 'group_description', $group_description ),
			$table->radioButton( $this->lang('group_settings_f_type'), 'group_type', array(	'private'=>$this->lang('group_settings_f_tp_private'), 
											 										'public'=>$this->lang('group_settings_f_tp_public')), $group_type ),
			$table->fileField( $this->lang('group_settings_f_avatar'), 'form_avatar', '' ),
			$table->submitButton( 'sbm', $this->lang('newgroup_f_btn') )
	);
	
	$table->form_title = $this->lang('newgroup_title2');
	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ) );
	
	
	$tpl->display();
?>
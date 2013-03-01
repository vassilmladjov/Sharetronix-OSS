<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	$i	= (array) $this->network->get_user_notif_rules($this->user->id);

	$p	= & $_POST;
	
	$submit	= FALSE;
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		$i	= array();
		$i['ntf_them_if_i_follow_usr']	= isset($p['ntf_them_if_i_follow_usr'])		? 1 : 0;
		$i['ntf_them_if_i_comment']		= isset($p['ntf_them_if_i_comment'])		? 1 : 0;
		$i['ntf_them_if_i_edt_profl']	= isset($p['ntf_them_if_i_edt_profl'])		? 1 : 0;
		$i['ntf_them_if_i_edt_pictr']	= isset($p['ntf_them_if_i_edt_pictr'])		? 1 : 0;
		$i['ntf_them_if_i_create_grp']	= isset($p['ntf_them_if_i_create_grp'])		? 1 : 0;
		$i['ntf_them_if_i_join_grp']	= isset($p['ntf_them_if_i_join_grp'])		? 1 : 0;
		
		$i['ntf_me_if_u_follows_me']	= (isset($p['ntf_me_if_u_follows_me'])	&& in_array($p['ntf_me_if_u_follows_me'], array(1, 2)) )		? intval($p['ntf_me_if_u_follows_me']) : 0;
		$i['ntf_me_if_u_follows_u2']	= (isset($p['ntf_me_if_u_follows_u2'])	&& in_array($p['ntf_me_if_u_follows_u2'], array(1, 2, 3, 0)) )		? intval($p['ntf_me_if_u_follows_u2']) : 0;
		$i['ntf_me_if_u_commments_me']	= (isset($p['ntf_me_if_u_commments_me']) && in_array($p['ntf_me_if_u_commments_me'], array(0, 3)))	? intval($p['ntf_me_if_u_commments_me']) : 0;
		$i['ntf_me_if_u_commments_m2']	= (isset($p['ntf_me_if_u_commments_m2']) && in_array($p['ntf_me_if_u_commments_m2'], array(0,3)) )	? intval($p['ntf_me_if_u_commments_m2']) : 0;
		$i['ntf_me_if_u_edt_profl']		= (isset($p['ntf_me_if_u_edt_profl'])	&& in_array($p['ntf_me_if_u_edt_profl'], array(1, 2, 3, 0)) )		? intval($p['ntf_me_if_u_edt_profl']) : 0;
		$i['ntf_me_if_u_edt_pictr']		= (isset($p['ntf_me_if_u_edt_pictr'])	&& in_array($p['ntf_me_if_u_edt_pictr'], array(1, 2, 3, 0)) )		? intval($p['ntf_me_if_u_edt_pictr']) : 0;
		$i['ntf_me_if_u_creates_grp']	= (isset($p['ntf_me_if_u_creates_grp'])	 && in_array($p['ntf_me_if_u_creates_grp'], array(1, 2, 3, 0)) )	? intval($p['ntf_me_if_u_creates_grp']) : 0;
		$i['ntf_me_if_u_joins_grp']		= (isset($p['ntf_me_if_u_joins_grp'])	&& in_array($p['ntf_me_if_u_joins_grp'], array(1, 2, 3, 0)) )		? intval($p['ntf_me_if_u_joins_grp']) : 0;
		$i['ntf_me_if_u_invit_me_grp']	= (isset($p['ntf_me_if_u_invit_me_grp'])	&& in_array($p['ntf_me_if_u_invit_me_grp'], array(1, 2)) )	? intval($p['ntf_me_if_u_invit_me_grp']) : 0;
		$i['ntf_me_if_u_posts_qme']		= (isset($p['ntf_me_if_u_posts_qme'])	&& in_array($p['ntf_me_if_u_posts_qme'], array(0,3)) )		? intval($p['ntf_me_if_u_posts_qme']) : 0;
		$i['ntf_me_if_u_posts_prvmsg']	= (isset($p['ntf_me_if_u_posts_prvmsg'])	&& in_array($p['ntf_me_if_u_posts_prvmsg'], array(0,3)) )	? intval($p['ntf_me_if_u_posts_prvmsg']) : 0;
		$i['ntf_me_if_u_registers']		= (isset($p['ntf_me_if_u_registers'])	&& in_array($p['ntf_me_if_u_registers'], array(0,1,2,3)) )		? intval($p['ntf_me_if_u_registers']) : 0;
		$in_sql	= array();
		$in_sql[]	= '`user_id`="'.$this->user->id.'"';
		foreach($i as $k=>$v) {
			$in_sql[]	= '`'.$k.'`="'.$v.'"';
		}
		$in_sql	= implode(', ', $in_sql);
		$db2->query('REPLACE INTO users_notif_rules SET '.$in_sql);
		$i	= (array)$this->network->get_user_notif_rules($this->user->id, TRUE);
	}
	
	$tpl = new template( array('page_title' => $this->lang('settings_notif_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	$error = false;
	
	if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('os_st_notif_ok'), $this->lang('os_st_notif_okmsg') ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('os_st_notif_title');
	//$table->form_description = $this->lang('os_st_notif_ttl1');
	
	
	$rows = array(
			$table->textField($this->lang('os_st_notif_ttl1'), '', 'table-title'),
			$table->checkBox('', array( array('ntf_them_if_i_follow_usr', 1, $this->lang('os_st_notif_ntf_them_if_i_follow_usr'), $i['ntf_them_if_i_follow_usr'] ) ) ),
			$table->checkBox('', array( array('ntf_them_if_i_comment', 1, $this->lang('os_st_notif_ntf_them_if_i_comment'), $i['ntf_them_if_i_comment'] ) ) ),
			$table->checkBox('', array( array('ntf_them_if_i_edt_profl', 1, $this->lang('os_st_notif_ntf_them_if_i_edt_profl'), $i['ntf_them_if_i_edt_profl'] ) ) ),
			$table->checkBox('', array( array('ntf_them_if_i_edt_pictr', 1, $this->lang('os_st_notif_ntf_them_if_i_edt_pictr'), $i['ntf_them_if_i_edt_pictr'] ) ) ),
			$table->checkBox('', array( array('ntf_them_if_i_create_grp', 1, $this->lang('os_st_notif_ntf_them_if_i_create_grp'), $i['ntf_them_if_i_create_grp'] ) ) ),
			$table->checkBox('', array( array('ntf_them_if_i_join_grp', 1, $this->lang('os_st_notif_ntf_them_if_i_join_grp'), $i['ntf_them_if_i_join_grp'] ) ) ),
			
			$table->textField($this->lang('os_st_notif_ttl2'), '', 'table-title'),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_follows_me'), 'ntf_me_if_u_follows_me', array('2'=>'Post', '1'=>'Post & E-mail' ), $i['ntf_me_if_u_follows_me'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_follows_u2'), 'ntf_me_if_u_follows_u2', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_follows_u2'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_commments_me'), 'ntf_me_if_u_commments_me', array('0'=>'None', '3'=>'E-mail' ), $i['ntf_me_if_u_commments_me'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_commments_m2'), 'ntf_me_if_u_commments_m2', array('0'=>'None', '3'=>'E-mail' ), $i['ntf_me_if_u_commments_m2'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_edt_profl'), 'ntf_me_if_u_edt_profl', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_edt_profl'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_edt_pictr'), 'ntf_me_if_u_edt_pictr', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_edt_pictr'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_creates_grp'), 'ntf_me_if_u_creates_grp', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_creates_grp'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_joins_grp'), 'ntf_me_if_u_joins_grp', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_joins_grp'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_invit_me_grp'), 'ntf_me_if_u_invit_me_grp', array('2'=>'Post', '1'=>'Post & E-mail' ), $i['ntf_me_if_u_invit_me_grp'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_posts_qme'), 'ntf_me_if_u_posts_qme', array('0'=>'None','3'=>'E-mail' ), $i['ntf_me_if_u_posts_qme'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_posts_prvmsg'), 'ntf_me_if_u_posts_prvmsg', array('0'=>'None', '3'=>'E-mail' ), $i['ntf_me_if_u_posts_prvmsg'] ),
			$table->radioButton( $this->lang('os_st_notif_ntf_me_if_u_registers'), 'ntf_me_if_u_registers', array('0'=>'None', '2'=>'Post', '3'=>'E-mail', '1'=>'Both' ), $i['ntf_me_if_u_registers'] ),
			
			$table->submitButton( 'sbm', $this->lang('os_st_notif_savebtn') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows, 'notifications-table' ));
	
	$tpl->display();
	
?>
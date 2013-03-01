<?php

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/group.php');
	$this->load_langfile('inside/group_invite.php');
	
	
	$g	= $this->network->get_group_by_id(intval($this->params->group));
	if( ! $g ) {
		$this->redirect('groups');
	}
	if( $g->is_private ) {
		$u	= $this->network->get_group_members($g->id);
		if( !$u || !isset($u[$this->user->id]) ) {
			$this->redirect('dashboard');
		}
	}
	
	$D->g	= & $g;
	$D->i_am_member	= $this->user->if_follow_group($g->id);
	$D->i_am_admin	= FALSE;
	if( $D->i_am_member ) {
		$D->i_am_admin	= $db->fetch('SELECT id FROM groups_admins WHERE group_id="'.$g->id.'" AND user_id="'.$this->user->id.'" LIMIT 1') ? TRUE : FALSE;
	}
	if( !$D->i_am_admin && $this->user->info->is_network_admin==1 ) {
		$D->i_am_admin	= TRUE;
	}
	$D->i_can_invite	= $D->i_am_admin || ($D->i_am_member && $g->is_public);
	
	if( ! $D->i_can_invite ) {
		$this->redirect($C->SITE_URL.$g->groupname);
	}
	
	//$D->page_favicon	= $C->IMG_URL.'avatars/thumbs2/'.$g->avatar;
	
	$data	= array();
	
	if( !isset($_POST['usersearch']) ){
		$tmp	= array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers );
		foreach($tmp as &$v) { $v = intval($v); }
		$tmp2	= array_keys($this->network->get_group_members($g->id));
		foreach($tmp2 as &$v) { $v = intval($v); }
		$tmp	= array_diff($tmp, $tmp2);
		$tmp2	= $this->network->get_group_invited_members($g->id);
		if( $tmp2 ) {
			foreach($tmp2 as &$v) { $v = intval($v); }
			$tmp	= array_diff($tmp, $tmp2);
		}
		$tmp	= array_diff($tmp, array(intval($this->user->id)));
		if( 0 == count($tmp) ) {
			$this->redirect($C->SITE_URL.$g->groupname);
		}
		
		foreach($tmp as $tmp2) {
			$tmp2	= $this->network->get_user_by_id($tmp2);
			if( ! $tmp2 ) { continue; }
			$data[$tmp2->id]	= $tmp2;
		}
		unset($tmp, $tmp2);
	}else{
		if( !empty($_POST['usersearch']) ){ 
			$srch = $this->db2->e($_POST['usersearch']);echo $srch;
			$r = $this->db2->query('SELECT * FROM users WHERE username LIKE "%'.$srch.'%" OR fullname LIKE "%'.$srch.'%"');
			while($row = $this->db2->fetch_object($r)){
				$tmp = new stdClass;
				$tmp->username 	= $row->username;
				$tmp->id 		= $row->id;
				$tmp->fullname 	= $row->fullname;
				$tmp->avatar	= empty($tmp->avatar)? $GLOBALS['C']->DEF_AVATAR_USER : $tmp->avatar;
				
				$data[] = $tmp;
			}
		}
	}
	
	if( isset($_POST['invite_users']) )
	{
		$group = new group( $g );
		$group->invite();
	}
	
	$tpl = new template( array(
			'page_title' => $this->lang('os_grpinv_pagetitle', array('#GROUP#'=>$g->title, '#SITE_TITLE#'=>$C->SITE_TITLE)),
			'header_page_layout'=>'c',
	));
	
	if( count($data) ){
		$tpl->layout->useBlock('user-invite-form');
		
		$float = 'left-container';
		foreach($data as $o){
			$tpl->layout->useInnerBlock('single-user-invite');
			
			$tpl->layout->inner_block->setVar( 'single_user_avatar', '<a href="'.userlink($o->username).'"><img src="'.$C->STORAGE_URL.'avatars/thumbs1/'.$o->avatar.'" alt="'.$o->fullname.'" /></a>');
			$tpl->layout->inner_block->setVar( 'single_user_username', '<a href="'.userlink($o->username).'">'.ucfirst($o->fullname).'</a>' );
			$tpl->layout->inner_block->setVar( 'single_invite_user_id', $o->id );
			
			$float = ($float == 'left-container')? 'right-container' : 'left-container';
			$tpl->layout->inner_block->setVar( 'single_user_float', $float );
			
			$tpl->layout->inner_block->saveInBlockPart('users_invite_data', true);
		}
		
		$tpl->layout->block->save('main_content');
	}else{
		$tpl->layout->setVar('main_content', $tpl->designer->errorMessage($this->lang('grpinv_nobody_ttl'), $this->lang('group_invite_no_memebers_found')));
	}
	
	$tpl->display();
	
?>
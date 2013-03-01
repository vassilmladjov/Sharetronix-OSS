<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/invite.php');
	
	$invites	= array();
	$could_be_invited = array();
	
	$r	= $db2->query('SELECT * FROM users_invitations WHERE user_id="'.$this->user->id.'" ORDER BY id DESC');
	while($tmp = $db2->fetch_object($r)) {
		$obj	= new stdClass;
		$obj->fullname	= stripslashes($tmp->recp_name);
		$obj->date		= strftime($tmp->date);
		$obj->email		= stripslashes($tmp->recp_email);
		$obj->is_accepted	= FALSE;
		$obj->resend	= FALSE;
		$obj->username	= '';
		$obj->avatar	= '';
		if( $tmp->recp_is_registered && $tmp->recp_user_id ) {
			$obj->is_accepted	= TRUE;
			$u	= $this->network->get_user_by_id($tmp->recp_user_id);
			if( $u ) {
				$obj->username	= $u->username;
				$obj->avatar	= $u->avatar;
				$obj->fullname	= $u->fullname;
			}
		}
		if( !$obj->is_accepted && (($obj->date+(3*24*60*60))<time())){
			$could_be_invited[$tmp->id] = $tmp->recp_email;
			$obj->resend	= TRUE;
		}
		
		$invites[$tmp->id]	= $obj;
	}

	if( isset($_GET['resend_to']) && !empty($_GET['resend_to']) && in_array(urldecode($_GET['resend_to']), $could_be_invited) ){
		$_GET['resend_to'] = urldecode($_GET['resend_to']);
		$obj = $db2->fetch('SELECT * FROM unconfirmed_registrations WHERE email="'.$_GET['resend_to'].'" LIMIT 1');
		$check = $db2->fetch_field('SELECT 1 FROM users_invitations WHERE user_id="'.$this->user->id.'" AND recp_email="'.$_GET['resend_to'].'" LIMIT 1');
		
		if( $obj && $check && (($obj->date+(3*24*60*60))<time()) ){
			$registration_link	= $C->SITE_URL.'signup/regid:'.$obj->id.'/regkey:'.$obj->confirm_key;
			$this->load_langfile('email/invite.php');
			$who	= $this->user->info->fullname;
			$whom	= $obj->fullname;
			$lang_keys	= array('#WHO#'=>$who, '#WHOM#'=>$whom, '#COMPANY#'=>$C->COMPANY, '#SITE_TITLE#'=>$C->SITE_TITLE, '#SITE_URL#'=>$C->SITE_URL);
			$subject	= $this->lang('os_invite_email_subject', $lang_keys);
			$msgtxt		= $this->load_single_block('email/invite_txt.php', FALSE, TRUE);
			$msghtml	= $this->load_single_block('email/invite_html.php', FALSE, TRUE);
			$from	= $this->user->info->fullname.' <'.$this->user->info->email.'>';
			do_send_mail_html($obj->email, $subject, $msgtxt, $msghtml, $from);
			
			$current_time = time();
			$db2->query('UPDATE users_invitations SET `date`="'.$current_time.'" WHERE `recp_email`="'.$db2->e($obj->email).'" AND `user_id`="'.$this->user->id.'" LIMIT 1');
			$db2->query('UPDATE unconfirmed_registrations SET `date`="'.$current_time.'" WHERE `email`="'.$db2->e($obj->email).'" LIMIT 1');
			
			$key = array_search($_GET['resend_to'], $could_be_invited);
			if($key && !empty($key)){
				$invites[$key]->resend = FALSE;
				$invites[$key]->date = $current_time;
			}
		}
		
	}
	
	$tpl = new template( array('page_title' => $this->lang('os_invite_ttl_sentinvites', array('#SITE_TITLE#'=>$C->SITE_TITLE, '#OUTSIDE_SITE_TITLE#'=>$C->OUTSIDE_SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$tpl->initRoutine('InviteTopMenu', array());
	$tpl->routine->load();
	
	$rows = array();
	foreach( $invites as $i ){	
		$rows[$i->fullname] = $i->email;
	}
	$tpl->layout->setVar('main_content', $tpl->designer->createTableDetailsBlock($this->lang('os_invite_txt_sentinvites', array('#SITE_TITLE#'=>$C->SITE_TITLE, '#OUTSIDE_SITE_TITLE#'=>$C->OUTSIDE_SITE_TITLE)), $rows, 'user-details', FALSE));
	
	$tpl->display();
	
?>
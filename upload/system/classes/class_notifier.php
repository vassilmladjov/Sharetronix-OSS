<?php
	class notifier
	{
		private $mass_mail;
		private $user;
		private $db2;
		private $page;
		private $post;
		private $comment;
		private $network;
		private $tab_notif_users;
		private $send_email_to_users;
		private $group_id;
		
		public function __construct()
		{
			global $C;
			
			require_once( $C->INCPATH.'helpers/func_notificationmails.php' );
			
			$this->mass_mail 		= FALSE;
			if( $C->MASS_MAILING && file_exists($C->INCPATH.'classes/class_PHPMailer.php') ){
				$this->mass_mail = TRUE;
			}
			$this->user 			= & $GLOBALS['user'];
			$this->db2 				= & $GLOBALS['db2'];
			$this->page 			= & $GLOBALS['page'];
			$this->post 			= FALSE;
			$this->comment 			= FALSE;
			$this->network			= & $GLOBALS['network'];
			$this->resource_obj		= FALSE;
			$this->resource_obj_hidden		= FALSE;
			
			$this->tab_notif_users 			= array();
			$this->mail_notif_users 		= array();
			$this->post_notif_users 		= array();
			
			$this->object_user_id = FALSE;
			$this->group_id			= FALSE;
		}
		
		public function set_post( & $post )
		{
			$this->post = $post;
		}
		public function set_comment( & $comment )
		{
			$this->comment = $comment;
		}
		public function set_group_id( $gid )
		{
			$this->group_id = $gid;
		}
		
		public function set_notification_obj( $res_type, $res_id )
		{
			$o = new stdClass;
			$o->type = $res_type;
			$o->id = $res_id;
			
			$this->resource_obj = $o;
		}
		
		private function hide_notif_obj()
		{
			$this->resource_obj_hidden = $this->resource_obj;
			$this->resource_obj = FALSE;
		}
		
		private function unhide_notif_obj()
		{
			$this->resource_obj = $this->resource_obj_hidden;
			$this->resource_obj_hidden = FALSE;
		}
		
		public function onFollowUser($whom_id)//WORKS!
		{
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_follow_usr );
			if( $n != 1 ) {
				return TRUE;
			}
			$this->object_user_id = $whom_id;
			
			$this->hide_notif_obj(); //hide the notification object, we do not need it for the first notification
			
			//A user receives a notification post aftre following him and this post could not be removed 
			//as notification type. The user could only choose if he  wants to receive an e-mail about it also.
			//3-mail notification, 2-post notification, 1-both, 0-none
			$n	= intval( $this->network->get_user_notif_rules($whom_id)->ntf_me_if_u_follows_me ); 
			$n = ( $n == 3 )? 1 : (( $n == 0 )? 2 : $n);
			
			if( $n == 1 || $n == 3 ){
				$this->_sendMail( 'ntf_me_if_u_follows_me', $whom_id );
			}
			if( $n == 2 || $n == 1 ){
				$this->_sendPost( 'ntf_me_if_u_follows_me', $whom_id );
			}
			
			$this->tab_notif_users[] = $whom_id;
			$this->_sendTabNotification('notifications');	
			
			$this->unhide_notif_obj(); //unhide the object, we need it for the second notification
			$followers	= array_keys($this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers); 
			
			foreach($followers as $uid){
				if( $uid == $whom_id ){ 
					continue; 
				}
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_follows_u2 ); 
				$this->_notifPusher('ntf_me_if_u_follows_u2', $uid, $n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			
			$this->_sendPost('ntf_me_if_u_follows_u2');
			$this->_sendBccMail('ntf_me_if_u_follows_u2');
			$this->_sendTabNotification('notifications');
			
			return TRUE;
		}
		public function onCommentPost()
		{//return true;
			if( !isset($this->post, $this->comment) ){
				return FALSE;
			}
			
			$commenters = array();
			
			//tab notification for all commenters
			if( $this->post->post_type == 'public' ){
				$this->db2->query('SELECT user_id FROM posts_comments_watch WHERE user_id<>"'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" ');
				while( $sdf = $this->db2->fetch_object() ) {
					$commenters[] = $sdf->user_id;
					$this->tab_notif_users[] = $sdf->user_id;
				}
			}
			$this->_sendTabNotification('commented'); 
			$this->tab_notif_users = array();
			
			//tab notification all mentioned in the comment
			if( count($this->comment->mentioned) ){
				foreach($this->comment->mentioned as $uid){
					if( $uid == $this->user->id ){
						continue;
					}
					
					if( !in_array($uid, $commenters) ){ //he haven't received notification yet
						$this->tab_notif_users[] = $uid;
					}
					
					$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_posts_qme );
					$this->_notifPusher('ntf_me_if_u_posts_qme', $uid, $n);
				}
				
				$this->_sendTabNotification('@me');
				$this->_sendBccMail('ntf_me_if_u_posts_qme');
			}
				
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_comment );
			if( $n != 1 ) {
				return FALSE;
			}

			$notify	= array();
			if( $this->post->post_user->id != $this->user->id && $this->post->post_user->id > 0 ) {
				$n	= intval( $this->network->get_user_notif_rules($this->post->post_user->id)->ntf_me_if_u_commments_me );
				if( $n != 0 ) {
					$notify[$this->post->post_user->id]	= $n;
				}
			}
			foreach($this->post->post_comments as $c) {
				if( $c->comment_user->id == $this->user->id ) {
					continue;
				}
				$n	= intval( $this->network->get_user_notif_rules($c->comment_user->id)->ntf_me_if_u_commments_m2 );
				if( isset($notify[$c->comment_user->id]) ) {
					if( $n==1 || ($n==2 && $notify[$c->comment_user->id]==3) || ($n==3 && $notify[$c->comment_user->id]==2) ) {
						$notify[$c->comment_user->id]	= 1;
						continue;
					}
				}
				if( $n != 0 ) {
					$notify[$c->comment_user->id]	= $n;
				}
			}
			foreach($notify as $uid=>$n) {
				if( !$n ) {
					continue;
				}
				
				$notifkey	= '';
				if( $this->user->id==$this->post->post_user->id ) {
					$notifkey	= count($this->post->post_comments)==0 ? 'u_commments_m2' : 'u_commments_m20';
				}
				elseif( $uid == $this->post->post_user->id ) {
					$notifkey	= count($this->post->post_comments)==0 ? 'u_commments_me' : 'u_commments_me2';
				}
				else {
					$notifkey	= count($this->post->post_comments)==0 ? 'u_commments_m3' : 'u_commments_m32';
				}
				
				//there was a different message if the post was in group, now the message is the same
				$this->_sendMail('ntf_me_if_'.$notifkey, $uid);
			}
			
			return TRUE;
		}
		public function onEditProfileInfo() //WORKS!
		{
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_edt_profl );
			if( $n != 1 ) {
				return TRUE;
			}
			
			$followers	= array_keys($this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers); 
			foreach($followers as $uid) {
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_edt_profl );
				$this->_notifPusher('ntf_me_if_u_edt_profl', $uid, $n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			
			$this->_sendPost('ntf_me_if_u_edt_profl');
			$this->_sendBccMail('ntf_me_if_u_edt_profl');
			$this->_sendTabNotification('notifications');
			
			return TRUE;
		}
		public function onChangeAvatar()//WORKS!
		{
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_edt_pictr );
			if( $n != 1 ) {
				return TRUE;
			}
		
			$followers	= array_keys($this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers);
			foreach($followers as $uid) {
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_edt_pictr );
				$this->_notifPusher('ntf_me_if_u_edt_pictr', $uid, $n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			
			$this->_sendPost('ntf_me_if_u_edt_pictr');
			$this->_sendBccMail('ntf_me_if_u_edt_pictr');
			$this->_sendTabNotification('notifications');
			
			return TRUE;
		}
		public function onCreateGroup()//WORKS!
		{
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_create_grp );
			if( $n != 1 ) {
				return TRUE;
			}
			
			$followers	= array_keys($this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers);
			foreach($followers as $uid) {
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_creates_grp );
				$this->_notifPusher('ntf_me_if_u_creates_grp', $uid, $n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			
			$this->_sendPost('ntf_me_if_u_creates_grp');
			$this->_sendBccMail('ntf_me_if_u_creates_grp');
			$this->_sendTabNotification('notifications');
			
			return TRUE;
		}
		public function onJoinGroup($group_id, $is_private) //WORKS
		{
			$n	= intval( $this->network->get_user_notif_rules($this->user->id)->ntf_them_if_i_join_grp );
			if( $n != 1 ) {
				return TRUE;
			}
			$this->group_id = $group_id;
			
			$priv_members = $is_private? $this->network->get_group_invited_members($group_id) : array();
			$followers	= array_keys($this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers);
			foreach($followers as $uid) {
				$uid	= intval($uid);
				if( $is_private && !in_array($uid, $priv_members) ) {
					continue;
				}
				
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_joins_grp );
				$this->_notifPusher('ntf_me_if_u_joins_grp', $uid, $n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			
			$this->_sendPost( 'ntf_me_if_u_joins_grp' );
			$this->_sendBccMail( 'ntf_me_if_u_joins_grp' );
			$this->_sendTabNotification( 'notifications' );
			$this->_sendPost_toGroup( 'ntf_grp_if_u_joins', $group_id );
			
			return TRUE;
		}
		public function onPostMention()//WORKS
		{
			$notify	= array();
			if( !$this->post->group || $this->post->group->is_public ) {
				$my_followers = $this->network->get_user_follows($this->user->id, FALSE, 'hefollows');
				foreach($this->post->mentioned as $uid) {
					if( $uid == $this->user->id ) {
						continue;
					}elseif($this->user->info->is_posts_protected && !isset($my_followers->follow_users[$uid]) && !$this->network->get_user_by_id($uid)->is_network_admin && !$this->post->group){
						continue;
					}
					$notify[]	= $uid;
				}
			}
			else {
				$grpmem	= $this->network->get_group_members($this->post->group->id);
				foreach($this->post->mentioned as $uid) {
					if( ! isset($grpmem[$uid]) ) {
						continue;
					}
					if( $uid == $this->user->id ) {
						continue;
					}
					$notify[]	= $uid;
				}
			}
			$notify	= array_unique($notify);
			
			$this->tab_notif_users = $notify;
			$this->_sendTabNotification('@me');
			
			foreach($notify as $uid) {
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_posts_qme );
				if( $n!=3 ) {
					continue;
				}
				$this->_notifPusher( 'ntf_me_if_u_posts_qme', $uid, $n );
			}	
			
			$this->_sendBccMail('ntf_me_if_u_posts_qme');
		}
		
		public function onPrivatePost()
		{
			if( !isset($this->post,$this->post->to_user->id) && !isset($this->post,$this->post->post_to_user->id) ){
				return FALSE;
			}
			if( isset($this->post,$this->post->to_user->id) ){
				$to_user_id = $this->post->to_user->id;
			}else{
				$to_user_id = $this->post->post_to_user->id;
			}
			
			if( $this->comment && $to_user_id == $this->user->id ) {
				$to_user_id	= $this->post->post_user->id;
			}
			
			$this->tab_notif_users = array($to_user_id);
			$this->_sendTabNotification('private');
			
			$n	= intval( $this->network->get_user_notif_rules($to_user_id)->ntf_me_if_u_posts_prvmsg );
			if( $n != 3 ) {
				return FALSE;
			}
			
			$this->_sendMail('ntf_me_if_u_posts_prvmsg', $to_user_id);
			
			return TRUE;
		}
		
		public function onJoinNetwork()//WORKS!
		{
			$r	= $this->db2->query('SELECT user_id, ntf_me_if_u_registers AS n FROM `users_notif_rules` WHERE ntf_me_if_u_registers>0', FALSE);
			while($sdf = $this->db2->fetch_object($r)) {
				$uid	= intval($sdf->user_id);
				$this->_notifPusher('ntf_me_if_u_registers', $uid, $sdf->n);
			}
			
			$this->tab_notif_users = $this->post_notif_users;
			$this->_sendTabNotification('notifications');
			
			$this->_sendPost('ntf_me_if_u_registers');
			$this->_sendBccMail('ntf_me_if_u_registers');
			
		}
		
		public function onGroupInvite( $group_id, & $group_members, & $invited_members )
		{
			$insert_values = array();
			$current_time = time();
			
			foreach($invited_members as $uid) {
				if( isset($group_members[$uid]) ) {
					continue;
				}
				$insert_values[] = '("'.$group_id.'", "'.$uid.'", "'.$this->user->id.'", "'.$current_time.'")';
				$n	= intval( $this->network->get_user_notif_rules($uid)->ntf_me_if_u_invit_me_grp );
				$this->_notifPusher('ntf_me_if_u_invit_me_grp', $uid, $n);
			}
			
			if(!empty($insert_values)) {
				$this->db2->query('INSERT INTO groups_private_members(group_id, user_id, invited_by, invited_date) VALUES'.implode(',', $insert_values));
				$this->tab_notif_users = $this->post_notif_users;
					
				$this->_sendPost('ntf_me_if_u_invit_me_grp');
				$this->_sendTabNotification('notifications');
				$this->_sendBccMail('ntf_me_if_u_invit_me_grp');

				return TRUE;
			} 
			return false;
			
		}
		
		private function _sendMail( $notifType, $whom_id )
		{ 
			//return;//remove
			
			global $D, $C;
			
			
			$this->page->load_langfile('inside/notifications.php');
			$this->page->load_langfile('email/notifications.php');
			
			$whom = $this->network->get_user_by_id($whom_id);
			if( !$whom ){
				return FALSE;
			}
			
			$lng = $this->_getLngTxtHtm($notifType);
			
			$represendTxt = '';
			$represendHtml = '';
			if( $this->comment ){
				$represendTxt = represent_comment_in_email($this->comment->comment_message, false);
				$represendHtml = represent_comment_in_email($this->comment->comment_message);
			}elseif( $this->post ){
				$represendTxt = represent_post_in_email($this->post->post_message, false);
				$represendHtml = represent_post_in_email($this->post->post_message);
			}
			
			$D->page = & $this->page;
			$D->user = & $whom;
			
			$ulng			= trim($whom->language);
			$subject		= $this->page->lang('emlsubj_'.$notifType, $lng['txt'], $ulng);
			$D->message_txt	= $this->page->lang('emltxt_'.$notifType, $lng['txt'], $ulng).$represendTxt;
			$D->message_html	= $this->page->lang('emlhtml_'.$notifType, $lng['htm'], $ulng).$represendHtml;
			$msgtxt				= $this->page->load_single_block('email/notifications_txt.php', FALSE, TRUE);
			$msghtml		= $this->page->load_single_block('email/notifications_html.php', FALSE, TRUE);
			
			if( !$whom || empty($subject) || empty($msgtxt) || empty($msghtml) ) {
				return FALSE; 
			}

			do_send_mail_html($whom->email, $subject, $msgtxt, $msghtml);	
			
			return TRUE;
		}
		
		private function _sendBccMail( $notifType ) 
		{ 
			
			if( !$this->mass_mail && !count($this->mail_notif_users) ){
				return FALSE;
			}elseif( !$this->mass_mail && count($this->mail_notif_users) ){ 
				foreach($this->mail_notif_users as $uid){
					$this->_sendMail($notifType, $uid);
				}
			}											
			if( !count($this->mail_notif_users) ){
				return FALSE;
			}
			
			global $C, $D;
			$D->user = new stdClass;
			$D->page = & $this->page;
			
			$this->page->load_langfile('inside/notifications.php');
			$this->page->load_langfile('email/notifications.php');
			
			$send_to = array();
			
			foreach( $this->mail_notif_users as $uid ){
				$whom = $this->network->get_user_by_id($uid);
				if( !$whom ){
					continue;
				}
				
				if( !isset($send_to[ $whom->language ]) ){
					$send_to[ $whom->language ] = array();
				}
				$send_to[ $whom->language ][] = $whom->email;
			}
			
			$lng = $this->_getLngTxtHtm($notifType);
			
			$represendTxt = '';
			$represendHtml = '';
			if( $this->comment ){
				$represendTxt = represent_comment_in_email($this->comment->comment_message, false);
				$represendHtml = represent_comment_in_email($this->comment->comment_message);
			}elseif( $this->post ){
				$represendTxt = represent_post_in_email($this->post->post_message, false);
				$represendHtml = represent_post_in_email($this->post->post_message);
			}

			foreach( $send_to as $language => $to_users ){
				$mail = new PHPMailer(); //New instance, with exceptions enabled set true
				$mail->From = $C->SYSTEM_EMAIL;
				$mail->FromName = $C->SITE_TITLE;
				$mail->Subject		= $this->page->lang('emlsubj_'.$notifType, $lng['txt'], $language);
				$mail->IsSendmail();  // tell the class to use Sendmail
				//$mail->IsMail(); //this works 
				
				$D->user->username = '';
				$D->message_txt	= $this->page->lang('emltxt_'.$notifType, $lng['txt'], $language).$represendTxt;
				$D->message_html	= $this->page->lang('emlhtml_'.$notifType, $lng['htm'], $language).$represendHtml;
				$msgtxt		= $this->page->load_single_block('email/notifications_txt.php', FALSE, TRUE);
				$msghtml		= $this->page->load_single_block('email/notifications_html.php', FALSE, TRUE);
				
				if( empty($msgtxt) || empty($msghtml) ){
					continue;
				}
				
				$mail->AltBody  = $msgtxt; // optional, comment out and test
				$mail->MsgHTML($msghtml);
				$mail->IsHTML(true); // send as HTML
				
				foreach( $to_users as $email ){
					$mail->AddBCC($email); 
				}
				
				$mail->Send();
			}
			
			$this->mail_notif_users = array();
			
			return TRUE;
		}
		
		private function _sendPost( $notifType, $whom_id = FALSE )
		{
			global $C;
			
			$current_time = time();
			$res_type = ( $this->resource_obj )? $this->resource_obj->type : ''; 
			$res_id = ( $this->resource_obj )? $this->resource_obj->id : 0; 
			$gid = ( $this->group_id )? $this->group_id : 0;
			$insert_values = array();
			
			if( $whom_id ){
				$this->db2->query('INSERT INTO notifications(notif_type, to_user_id, in_group_id, from_user_id, notif_object_type, notif_object_id, date) VALUES(
									"'.$this->db2->e($notifType).'", "'.intval($whom_id).'", "'.intval( $gid ).'", "'.intval($this->user->id).'", "'.$this->db2->e($res_type).'", "'.$this->db2->e($res_id).'", "'.$current_time.'"
									)');
				return TRUE;
			}
			
			
			if( count($this->post_notif_users)>0 ){
				$q = 'INSERT INTO notifications(notif_type, to_user_id, in_group_id, from_user_id, notif_object_type, notif_object_id, date) VALUES';
				foreach( $this->post_notif_users as $usr ){
					$insert_values[] = '("'.$this->db2->e($notifType).'", "'.intval($usr).'", "'.intval( $gid ).'", "'.intval($this->user->id).'", "'.$this->db2->e($res_type).'", "'.$this->db2->e($res_id).'", "'.$current_time.'")';
				}
				$q .= implode(',', $insert_values);
				$this->db2->query($q);
			}
			
			$this->post_notif_users = array();
			
			return TRUE;	
		}
		
		private function _sendPost_toGroup( $notifType , $group_id )
		{
			global $C;
			
			$current_time = time();
			$res_type = ''; 
			$res_id = 0; 
			
			$this->db2->query('INSERT INTO group_notifications(notif_type, to_group_id, from_user_id, date) VALUES(
				"'.$this->db2->e($notifType).'", "'.intval( $group_id ).'", "'.intval($this->user->id).'", "'.$current_time.'"
			)');
			
			return TRUE;	
		}
		
		private function _sendTabNotification( $tab, $counter = 1 )
		{
			if( $counter <= 0 || !count($this->tab_notif_users) ) {
				return FALSE;
			}
			
			$values = array();
			foreach( $this->tab_notif_users as $uid ){
				$values[] = '("'.intval($uid).'", "'.$this->db2->e($tab).'", 1, "'.intval($counter).'")';
			}

			//check if user_id is unique key
			$this->db2->query('INSERT INTO users_dashboard_tabs(user_id, tab, state, newposts) VALUES '.implode(',', $values).' ON DUPLICATE KEY UPDATE newposts=newposts+'.intval($counter));
			$this->tab_notif_users = array();
			
			return TRUE;
	
		}
		
		private function _getLngTxtHtm($notifType)
		{
			global $C;
			$notif = array();
			
			switch($notifType)
			{
				case 'ntf_me_if_u_follows_me':	$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$C->SITE_URL.$this->user->info->username);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname);
										break;
				
				case 'ntf_me_if_u_follows_u2':	$whom = $this->network->get_user_by_id($this->object_user_id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$C->SITE_URL.$this->user->info->username, '#USER2#'=>$whom->username, '#NAME2#'=>$whom->fullname);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#USER2#'=>'<a href="'.$C->SITE_URL.$whom->username.'" title="'.htmlspecialchars($whom->fullname).'" target="_blank">@'.$whom->username.'</a>', '#NAME2#'=>$whom->fullname);	
										break;
										
				case 'ntf_me_if_u_joins_grp':		$group = $this->network->get_group_by_id($this->group_id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>$group->title, '#A0#'=>$C->SITE_URL.$group->groupname);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>'<a href="'.$C->SITE_URL.$group->groupname.'" title="'.$group->title.'" target="_blank">'.$group->title.'</a>');
										break;
				
				case 'ntf_me_if_u_creates_grp':	$g = $this->network->get_group_by_id($this->group_id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>$g->title, '#A0#'=>$C->SITE_URL.$g->groupname);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>'<a href="'.$C->SITE_URL.$g->groupname.'" title="'.$g->title.'" target="_blank">'.$g->title.'</a>');
										break;
										
				case 'ntf_me_if_u_edt_profl':		$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$C->SITE_URL.$this->user->info->username);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#A0#'=>'');
										break;
										
				case 'ntf_me_if_u_edt_pictr':		$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$C->SITE_URL.$this->user->info->username);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#A0#'=>'');
										break;
										
				case 'ntf_me_if_u_registers':		$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#COMPANY#'=>$C->COMPANY, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$C->SITE_URL.$this->user->info->username);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#COMPANY#'=>$C->COMPANY, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#A0#'=>'');
										break;
										
				case 'ntf_me_if_u_posts_prvmsg':	
										$permalink = $C->SITE_URL.'view/priv:'.(isset($this->post->post_id)? $this->post->post_id : $this->post->id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$permalink, '#A1#'=>'', '#A2#'=>'');
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#A1#'=>'<a href="'.$permalink.'" target="_blank">', '#A2#'=>'</a>', '#A0#'=>'');
										break;
										
				case 'ntf_me_if_u_posts_qme':		
										//$permalink = $C->SITE_URL.'view/post:'.$this->post->id;
										$permalink = $C->SITE_URL.'view/post:'.(isset($this->post->post_id)? $this->post->post_id : $this->resource_obj->id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#A0#'=>$permalink, '#A1#'=>'', '#A2#'=>'');
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#A1#'=>'<a href="'.$permalink.'" target="_blank">', '#A2#'=>'</a>', '#A0#'=>'');
										break;
										
				case 'ntf_me_if_u_commments_m2':
				case 'ntf_me_if_u_commments_m20':
				case 'ntf_me_if_u_commments_me':
				case 'ntf_me_if_u_commments_me2':
				case 'ntf_me_if_u_commments_m3':
				case 'ntf_me_if_u_commments_m32': 	$notif['txt']	= array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#USER2#'=>'@'.$this->post->post_user->username, '#NAME2#'=>$this->post->post_user->fullname, '#A0#'=>$this->post->permalink.'#comments');
										$notif['htm']	= array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#USER2#'=>'<a href="'.$C->SITE_URL.$this->post->post_user->username.'" title="'.htmlspecialchars($this->post->post_user->fullname).'" target="_blank">@'.$this->post->post_user->username.'</a>', '#NAME2#'=>$this->post->post_user->fullname, '#A1#'=>'<a href="'.$this->post->permalink.'#comments" target="_blank">', '#A2#'=>'</a>');
										break;				
				
				case 'ntf_me_if_u_invit_me_grp':	$g = $this->network->get_group_by_id($this->group_id);
										$notif['txt'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'@'.$this->user->info->username, '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>$g->title, '#A0#'=>$C->SITE_URL.$g->groupname);
										$notif['htm'] = array('#SITE_TITLE#'=>$C->SITE_TITLE, '#USER#'=>'<a href="'.$C->SITE_URL.$this->user->info->username.'" title="'.htmlspecialchars($this->user->info->fullname).'" target="_blank">@'.$this->user->info->username.'</a>', '#NAME#'=>$this->user->info->fullname, '#GROUP#'=>'<a href="'.$C->SITE_URL.$g->groupname.'" title="'.$g->title.'" target="_blank">'.$g->title.'</a>');
										break;
			}
			
			return $notif;	
		}
		
		private function _notifPusher($notif_type, $uid, $notif_rule)
		{
			switch( $notif_rule ){
				case 1: 	
							if( !$this->mass_mail ){
								$this->_sendMail( $notif_type, $uid );
							}else{
								$this->mail_notif_users[] = $uid;
							}
							
							$this->post_notif_users[] = $uid;
							
						break;
						
				case 2: 	$this->post_notif_users[] = $uid; 
						break;
						
						
				case 3: 	
							if( !$this->mass_mail ){
								$this->_sendMail( $notif_type, $uid );
							}else{
								$this->mail_notif_users[] = $uid;
							}
						
						break;
			}
		}
		
	}
?>
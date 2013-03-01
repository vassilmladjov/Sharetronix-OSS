<?php
	class group
	{
		//settings
		//del group
		//join
		//invite
		private $user;
		private $db2;
		private $page;
		private $network;
		private $group;
		private $group_members;
		public  $is_group_member;
		
		public function __construct( & $g = FALSE )
		{
			$this->page = & $GLOBALS['page'];
			$this->user = & $GLOBALS['user'];
			
			$this->network = & $GLOBALS['network'];
			$this->db2  = & $GLOBALS['db2'];
			$this->group = $g;
			
			$this->group_members = ($g)? array_keys( $this->network->get_group_members($g->id) ) : 0;
			$this->is_group_member = ($g)? (( $this->user->is_logged && in_array($this->user->id, $this->group_members) )? TRUE : FALSE) : FALSE;
		}
		
		public function isGroupAdmin()
		{
			$group_id = intval($this->group->id);
			if( ! $group_id ){
				return FALSE;
			}
				
			return $this->db2->fetch('SELECT id FROM groups_admins WHERE group_id="'.$this->group->id.'" AND user_id="'.$this->user->id.'" LIMIT 1') ? TRUE : FALSE;
		}
		
		public function ifCanInvite()
		{
			$status = FALSE;
			
			if( !$this->user->is_logged ){
				return FALSE;
			}
			
			if( $this->user->info->is_network_admin && $this->is_group_member ){
				$status = TRUE;
			}elseif( $this->is_group_member && $this->group->is_public ){
				$status =  TRUE;
			}elseif( $this->isGroupAdmin() ){
				$status =  TRUE;
			}
				
			if( $status ){//should modify this code
				//user followers
				$tmp 	= $this->user->is_logged? array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hisfollowers')->followers ) : array();
				$tmp2	= & $this->group_members;

				$tmp	= array_diff($tmp, $tmp2);
				$tmp2	= $this->network->get_group_invited_members($this->group->id);
				if( $tmp2 ) {
					$tmp	= array_diff($tmp, $tmp2);
				}
				$tmp	= array_diff($tmp, array(intval($this->user->id)));
				if( ! count($tmp) ) {
					$status	= FALSE;
				}
				unset($tmp, $tmp2);
			}
			
			return $status;
			
		}
		
		public function join()
		{
			if( $this->is_group_member ){
				return FALSE;
			}
			$this->user->follow_group($this->group->id, TRUE);
			
			return TRUE;
		}
		
		public function invite()
		{
			if( !$this->is_group_member ){
				return FALSE;
			}else if( !is_array($_POST['invite_users']) ){
				return FALSE;
			}
			
			global $C;
			
			$users = $_POST['invite_users'];
		
			$notif = new notifier();
			$notif->set_notification_obj('user', $this->user->id);
			$notif->set_group_id($this->group->id);
			$notif->onGroupInvite( $this->group->id, $data, $users );
		
			$this->network->get_group_invited_members($this->group->id, TRUE);
			$this->page->redirect( $C->SITE_URL.$this->group->groupname.'/msg:invited' );
				
			return TRUE;
		}
		
		public function leave()
		{
			if( ! $this->is_group_member ){
				return FALSE;
			}
			$this->user->follow_group($this->group->id, FALSE);
				
			return TRUE;
		}
		
		public function lastUsers()
		{
			$latest = array();
			$this->db2->query('SELECT u.username, u.avatar, n.date FROM users u, group_notifications n WHERE n.from_user_id = u.id AND n.notif_type="ntf_grp_if_u_joins" AND n.to_group_id="'.intval($g->id).'" ORDER BY n.id DESC LIMIT 10');
			while( $o = $this->db2->fetch_object() ){
				$latest = array( 'username' => $o->username, 'date' => $o->date, 'avatar'=>(!empty($o->avatar)? $o->avatar : $GLOBALS['C']->DEF_AVATAR_USER) );
			}
		}
		
		public function delete( $delete_posts = TRUE, $useredirect = TRUE )
		{
			global $C;
			
			if( $this->group->is_private ) {
				$delete_posts	= TRUE;
			}

			ini_set('max_execution_time', 10*60*60);
			if( $delete_posts ) {
				$r	= $this->db2->query('SELECT * FROM posts WHERE group_id="'.$this->group->id.'" ORDER BY id ASC');
				while($obj = $this->db2->fetch_object($r)) {
					$p	= new post('public', FALSE, $obj);
					if( $p->error ) { 
						continue; 
					}
				
					$p->delete_this_post();
				}
				/* no more table groups_rssfeeds
				$r	= $this->db2->query('SELECT id FROM groups_rssfeeds WHERE group_id="'.$this->group->id.'" ');
				while($obj = $this->db2->fetch_object($r)) {
					$this->db2->query('DELETE FROM groups_rssfeeds_posts WHERE rssfeed_id="'.$obj->id.'" ');
				}
				$this->db2->query('DELETE FROM groups_rssfeeds WHERE group_id="'.$this->group->id.'" ');
				*/
			}
			$r	= $this->db2->query('SELECT * FROM posts WHERE user_id="0" AND group_id="'.$this->group->id.'" ORDER BY id ASC');
			while($obj = $this->db2->fetch_object($r)) {
				$p	= new post('public', FALSE, $obj);
				if( $p->error ) { continue; }
				$p->delete_this_post(FALSE);
			}
			$f	= array_keys($this->network->get_group_members($this->group->id));
			$this->db2->query('DELETE FROM group_notifications WHERE to_group_id="'.$this->group->id.'" ');
			$this->db2->query('DELETE FROM notifications WHERE in_group_id="'.$this->group->id.'" ');
			$this->db2->query('DELETE FROM groups_followed WHERE group_id="'.$this->group->id.'" ');
			$this->db2->query('DELETE FROM groups_private_members WHERE group_id="'.$this->group->id.'" ');
			$this->db2->query('DELETE FROM groups_admins WHERE group_id="'.$this->group->id.'" ');
			//$this->db2->query('UPDATE groups_rssfeeds SET is_deleted=1 WHERE group_id="'.$this->group->id.'" ');
			foreach($f as $uid) {
				$this->network->get_user_follows($uid, TRUE);
			}
			$this->db2->query('INSERT INTO groups_deleted (id, groupname, title, is_public) SELECT id, groupname, title, is_public FROM groups WHERE id="'.$this->group->id.'" LIMIT 1');
			$this->db2->query('DELETE FROM groups WHERE id="'.$this->group->id.'" LIMIT 1');
			$this->network->get_group_by_id($this->group->id, TRUE);
			$av	= $this->group->avatar;
			if( $av != $C->DEF_AVATAR_GROUP ) {
				rm( $C->IMG_DIR.'avatars/'.$av );
				rm( $C->IMG_DIR.'avatars/thumbs1/'.$av );
				rm( $C->IMG_DIR.'avatars/thumbs2/'.$av );
				rm( $C->IMG_DIR.'avatars/thumbs3/'.$av );
			}
			if($useredirect){
				$this->page->redirect( $C->SITE_URL.'groups/msg:deleted' );
			}
		}

		public function addAdmin( $admin_id )
		{
			$admin_id = intval($admin_id);
			if( !$admin_id ){
				return FALSE;
			}
			
			$current_admins = array();
			$r = $this->db2->query('SELECT u.username, u.id FROM users u, groups_admins ga WHERE ga.group_id="'.$this->group->id.'" AND u.id=ga.user_id');
			while($o = $this->db2->fetch_object($r)) {
				$current_admins[]	= $o->id;
			}
			
			if( in_array($admin_id, $current_admins) ){
				return FALSE;
			}

			$this->db2->query('INSERT INTO groups_admins SET group_id="'.$this->group->id.'", user_id="'.$admin_id.'" ');
			
			$this->network->user_admin_group_ids($admin_id, TRUE);
			
			return TRUE;
		}
		
		public function deleteAdmin( $admin_id )
		{
			$admin_id = intval($admin_id);
			if( !$admin_id ){
				return FALSE;
			}
			
			$current_admins = array();
			$cnt = $this->db2->fetch_field('SELECT COUNT(u.id) AS cnt FROM users u, groups_admins ga WHERE ga.group_id="'.$this->group->id.'" AND u.id=ga.user_id');
			if( $cnt <= 1 ){
				return FALSE;
			}
			
			$this->db2->query('DELETE FROM groups_admins WHERE group_id="'.$this->group->id.'" AND user_id = "'.$admin_id.'" LIMIT 1');
			
			$this->network->user_admin_group_ids($admin_id, TRUE);
			
			return TRUE;
		}
		
		public function changeAvatar( $add_new = TRUE )
		{
			global $C;
			
			$error = FALSE;
			$errmsg = '';
			
			
			if( $add_new && isset($_FILES['form_avatar']) && is_uploaded_file($_FILES['form_avatar']['tmp_name']) ) {
				
				require_once( $C->INCPATH.'helpers/func_images.php' ); 
				
				$f	= (object) $_FILES['form_avatar'];

				list($w, $h, $tp) = getimagesize($f->tmp_name);
				
				if( $w==0 || $h==0 ) {
					$error	= TRUE;
					$errmsg	= 'group_setterr_avatar_invalidfile';
				}
				elseif( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
					$error	= TRUE;
					$errmsg	= 'group_setterr_avatar_invalidformat';
				}
				elseif( $w<$C->AVATAR_SIZE || $h<$C->AVATAR_SIZE ) {
					$error	= TRUE;
					$errmsg	= 'group_setterr_avatar_toosmall';
				}
				
				if( ! $error ) {
					$avtr	= $this->group->avatar;
					if( $avtr != $C->DEF_AVATAR_GROUP ) {
						rm( $C->STORAGE_DIR.'avatars/'.$avtr );
						rm( $C->STORAGE_DIR.'avatars/thumbs1/'.$avtr );
						rm( $C->STORAGE_DIR.'avatars/thumbs2/'.$avtr );
						rm( $C->STORAGE_DIR.'avatars/thumbs3/'.$avtr );
						rm( $C->STORAGE_DIR.'avatars/thumbs4/'.$avtr );
						rm( $C->STORAGE_DIR.'avatars/thumbs5/'.$avtr );
					}else{
						$avtr	= time().rand(100000,999999).'.png';	
					}
					
					if( $avtr != $C->DEF_AVATAR_GROUP ) {
						$res	= copy_avatar($f->tmp_name, $avtr);
						if( ! $res) {
							$error	= TRUE;
							$errmsg	= 'group_setterr_avatar_cantcopy';
						}
					}else{
						$avtr = '';
					}
					
					$this->db2->query('UPDATE groups SET avatar="'.$this->db2->e($avtr).'" WHERE id="'.$this->group->id.'" LIMIT 1');
				}
				
			}else if( !$add_new ){
				$old	= $this->group->avatar;
				if( $old != $C->DEF_AVATAR_GROUP ) {
					rm( $C->STORAGE_DIR.'avatars/'.$old );
					rm( $C->STORAGE_DIR.'avatars/thumbs1/'.$old );
					rm( $C->STORAGE_DIR.'avatars/thumbs2/'.$old );
					rm( $C->STORAGE_DIR.'avatars/thumbs3/'.$old );
					rm( $C->STORAGE_DIR.'avatars/thumbs4/'.$old );
					rm( $C->STORAGE_DIR.'avatars/thumbs5/'.$old );
					$this->db2->query('UPDATE groups SET avatar="" WHERE id="'.$this->group->id.'" LIMIT 1');
				}
			}
			
			$this->network->get_group_by_id($this->group->id, TRUE);

			return $errmsg;
		}
		
		public function changeType( $type )
		{
			switch($type){
				case 'public':
					$this->db2->query('UPDATE groups SET is_public=1 WHERE id="'.$this->group->id.'" LIMIT 1');
					break;
				case 'private':
					$this->db2->query('UPDATE groups SET is_public=0 WHERE id="'.$this->group->id.'" LIMIT 1');
					
					$tmp1	= array_keys($this->network->get_group_members($this->group->id));
					$tmp2	= $this->network->get_group_invited_members($this->group->id);
					$tmp	= array_diff($tmp1, $tmp2);
					foreach($tmp as $uid) {
						$this->db2->query('INSERT INTO groups_private_members SET group_id="'.$this->group->id.'", user_id="'.$uid.'", invited_by="'.$this->user->id.'", invited_date="'.time().'" ');
					}
					$tmp	= $this->network->get_group_invited_members($this->group->id, TRUE);
					$tmp	= $this->network->get_private_groups_ids(TRUE);
					unset($tmp, $tmp1, $tmp2);
					
					break;
					
			}
		}
		
		public function changeDescription( $description )
		{
			global $C;
			
			$description = htmlspecialchars($description);
			
			$errmsg = '';
			
			$description	= mb_substr($description, 0, $C->POST_MAX_SYMBOLS);
			$this->db2->query('UPDATE groups SET about_me="'.$this->db2->e($description).'" WHERE id="'.$this->group->id.'" LIMIT 1');
			
			return $errmsg;
		}
		
		public function changeTitle( $title )
		{
			$title = htmlspecialchars($title);

			$error = '';

			if( mb_strlen($title)<3 || mb_strlen($title)>30 ) {
				$error	= 'group_setterr_title_length';
			}
			elseif( preg_match('/[^\pL0-9\.\-\_\s$]/iu', $title) ) { 
				$error	= 'group_setterr_title_chars';
			}
			elseif( $title != $this->group->title ) {
				$this->db2->query('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($title).'" OR title="'.$this->db2->e($title).'") AND id<>"'.$this->group->id.'" LIMIT 1');
				if( $this->db2->num_rows() > 0 ) {
					$error	= 'group_setterr_title_exists';
				}
				else {
					$this->db2->query('UPDATE groups SET title="'.$this->db2->e($title).'" WHERE id="'.$this->group->id.'" LIMIT 1');
				}
			}

			return $error;
		}
		
		public function changeName( $name )
		{
			global $C;
			
			$error = '';
			
			if( mb_strlen($name)<3 || mb_strlen($name)>30 ) {
				$error	= 'group_setterr_name_length';
			}
			elseif( ! preg_match('/^[a-z0-9\-\_]{3,30}$/iu', $name) ) {
				$error	= 'group_setterr_name_chars';
			}
			elseif( $name != $this->group->groupname ) {
				$this->db2->query('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($name).'" OR title="'.$this->db2->e($name).'") AND id<>"'.$this->group->id.'" LIMIT 1');
				if( $this->db2->num_rows() > 0 ) {
					$error	= 'group_setterr_name_exists';
				}
				else {
					$this->db2->query('SELECT id FROM users WHERE username="'.$this->db2->e($name).'" LIMIT 1');
					if( $this->db2->num_rows() > 0 ) {
						$error	= 'group_setterr_name_existsu';
					}
					elseif( file_exists($C->INCPATH.'controllers/'.$name.'.php') ) {
						$error	= 'group_setterr_name_existss';
					}
					else {
						$this->db2->query('UPDATE groups SET groupname="'.$this->db2->e($name).'" WHERE id="'.$this->group->id.'" LIMIT 1');
						$this->network->get_group_by_name($this->group->groupname, TRUE);
					}
				}
			}
			
			return $error;
		}
		
		public function createGroup()
		{
			global $C;
			
			$group_name			= isset($_POST['group_name'])? htmlspecialchars( trim($_POST['group_name']) ) : '';
			$group_alias		= isset($_POST['group_alias'])? htmlspecialchars( trim($_POST['group_alias']) ) : '';
			$group_description	= isset($_POST['group_description'])? htmlspecialchars( mb_substr(trim($_POST['group_description']), 0, $C->POST_MAX_SYMBOLS) ) : '';
			$group_type			= isset($_POST['group_type'])? (trim($_POST['group_type'])=='private' ? 'private' : 'public') : '';
			
			$errmsg = '';
			$error = FALSE;
			
			if( mb_strlen($group_name)<3 || mb_strlen($group_name)>30 ) { 
				$errmsg	= 'group_setterr_title_length';
				$error = TRUE;
			}
			elseif( preg_match('/[^\pL0-9\.\-\_\s$]/iu', $group_name) ) {
				$errmsg	= 'group_setterr_title_chars';
				$error = TRUE;
			}
			else {
				$this->db2->query('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($group_name).'" OR title="'.$this->db2->e($group_name).'") LIMIT 1');
				if( $this->db2->num_rows() > 0 ) {
					$errmsg	= 'group_setterr_title_exists';
					$error = TRUE;
				}
			}
			
			if( ! $error ) {
				if( mb_strlen($group_alias)<3 || mb_strlen($group_alias)>30 ) {
					$error	= TRUE;
					$errmsg	= 'group_setterr_name_length';
				}
				elseif( ! preg_match('/^[a-z0-9\-\_]{3,30}$/iu', $group_alias) ) {
					$error	= TRUE;
					$errmsg	= 'group_setterr_name_chars';
				}else {
					$this->db2->query('SELECT id FROM groups WHERE (groupname="'.$this->db2->e($group_alias).'" OR title="'.$this->db2->e($group_alias).'") LIMIT 1');
					if( $this->db2->num_rows() > 0 ) {
						$error	= TRUE;
						$errmsg	= 'group_setterr_name_exists';
					}
					else {
						$this->db2->query('SELECT id FROM users WHERE username="'.$this->db2->e($group_alias).'" LIMIT 1');
						if( $this->db2->num_rows() > 0 ) {
							$error	= TRUE;
							$errmsg	= 'group_setterr_name_existsu';
						}
						elseif( file_exists($C->INCPATH.'controllers/'.strtolower($group_alias).'.php') ) {
							$error	= TRUE;
							$errmsg	= 'group_setterr_name_existss';
						}
						elseif( file_exists($C->INCPATH.'controllers/mobile/'.strtolower($group_alias).'.php') ) {
							$error	= TRUE;
							$errmsg	= 'group_setterr_name_existss';
						}
					}
				}
			}
			
			if( ! $error ) {
				$this->db2->query('INSERT INTO groups SET groupname="'.$this->db2->e($group_alias).'", title="'.$this->db2->e($group_name).'", about_me="'.$this->db2->e($group_description).'", is_public="'.($group_type=='public'?1:0).'" ');
					
				$g	= $this->network->get_group_by_id( intval($this->db2->insert_id()) );
					
				$this->db2->query('INSERT INTO groups_admins SET group_id="'.$g->id.'", user_id="'.$this->user->id.'" ');
				if( $g->is_private ) {
					$this->db2->query('INSERT INTO groups_private_members SET group_id="'.$g->id.'", user_id="'.$this->user->id.'", invited_by="'.$this->user->id.'", invited_date="'.time().'" ');
				}
					
				if( $g->is_public ) {
					$notif = new notifier();
					$notif->set_group_id($g->id);
					$notif->set_notification_obj('user', $this->user->id);
					$notif->onCreateGroup();
				}
				$this->user->follow_group($g->id);
				$this->network->get_group_by_name($g->groupname, TRUE);
				$this->network->get_private_groups_ids(TRUE);
				//$this->user->get_my_private_groups_ids(TRUE);
				//$this->network->get_group_by_id($g->title, TRUE);
				
				$this->group = $g;
			}
			
			if( !$error ){
				$errmsg = $this->changeAvatar();
			}
			
			if( !$error && $errmsg == '' ){
				$this->page->redirect( $C->SITE_URL.$g->groupname.'/msg:created' );
			}
			
			return $errmsg;
			
		}
		
		public function getGroupMembers( &$member_ids )
		{
			$member_ids = array_slice($member_ids, 0, 10);
			
			$parsed = array();
			foreach($member_ids as $v) {
				$tmp	= $this->network->get_user_by_id($v);
				if( $tmp ) {
					$parsed[] = array( 'username'=>$tmp->username, 'avatar'=>$tmp->avatar );
				}
			}
			
			return $parsed;
		}
		
	}
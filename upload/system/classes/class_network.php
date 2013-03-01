<?php
	
	class network
	{
		public $id;
		public $info;
		public $is_private;
		public $is_public;
		
		public function __construct()
		{
			$this->id	= FALSE;
			$this->info	= new stdClass;
			$this->cache	= & $GLOBALS['cache'];
			$this->db1		= & $GLOBALS['db1'];
			$this->db2		= & $GLOBALS['db2'];
		}
		
		public function LOAD()
		{
			if( $this->id ) {
				return FALSE;
			}
			$this->load_network_settings();
			$this->info	= (object) array(
				'id'	=> 1,
			);
			$this->is_private	= FALSE;
			$this->is_public	= TRUE;
			$this->id	= $this->info->id;
			return $this->id;
		}
		
		public function load_network_settings()
		{
			global $C;
			$db	= &$this->db1;
			$r	= $db->query('SELECT * FROM settings', FALSE);
			while($obj = $db->fetch_object($r)) {
				$C->{$obj->word}	= stripslashes($obj->value);
			}

			if( ! isset($C->HDR_SHOW_COMPANY) ) { $C->HDR_SHOW_COMPANY = 1; }
			if( ! isset($C->HDR_SHOW_LOGO) ) { $C->HDR_SHOW_LOGO = 1; }
			if( ! isset($C->HDR_CUSTOM_LOGO) ) { $C->HDR_CUSTOM_LOGO = ''; }
			if( ! isset($C->HDR_SHOW_FAVICON) ) { $C->HDR_SHOW_FAVICON = 1; }
			if( ! isset($C->HDR_CUSTOM_FAVICON) ) { $C->HDR_CUSTOM_FAVICON = ''; }
			
			$current_language	= new stdClass;
			include($C->INCPATH.'languages/'.$C->LANGUAGE.'/language.php');
			setlocale(LC_ALL, $current_language->php_locale);
			$C->PHP_LOCALE = $current_language->php_locale;
			
			if( ! isset($C->DEF_TIMEZONE) ) {
				$C->DEF_TIMEZONE	= $current_language->php_timezone;
			}
			date_default_timezone_set($C->DEF_TIMEZONE);
			
			if( ! isset($C->THEME) ) {
				$C->THEME	= 'default';
			}
			
			if( !isset($C->SITE_TITLE) || empty($C->SITE_TITLE) ) {
				$C->SITE_TITLE	= 'Sharetronix';
			}
			$C->OUTSIDE_SITE_TITLE	= $C->SITE_TITLE;
		}
		
		public function get_user_by_username($uname, $force_refresh=FALSE, $return_id=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			if( empty($uname) ) {
				return FALSE;
			}
			$cachekey	= 'n:'.$this->id.',username:'.strtolower($uname);
			$uid	= $this->cache->get($cachekey);
			if( FALSE!==$uid && TRUE!=$force_refresh ) {
				return $return_id ? $uid : $this->get_user_by_id($uid);
			}
			$uid	= FALSE;
			$r	= $this->db2->query('SELECT id FROM users WHERE username="'.$this->db2->escape($uname).'" LIMIT 1', FALSE);
			if( $o = $this->db2->fetch_object($r) ) {
				$uid	= intval($o->id);
				$this->cache->set($cachekey, $uid, $GLOBALS['C']->CACHE_EXPIRE);
				return $return_id ? $uid : $this->get_user_by_id($uid);
			}
			$this->cache->del($cachekey);
			return FALSE;
		}
		
		public function get_user_by_id($uid, $force_refresh=FALSE)
		{
			global $C;
			
			if( ! $this->id ) {
				return FALSE;
			}
			$uid	= intval($uid);
			if( 0 == $uid ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',userid:'.$uid;
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
			$r	= $this->db2->query('SELECT * FROM users WHERE id="'.$uid.'" LIMIT 1', FALSE);
			
			if($o = $this->db2->fetch_object($r)) {
				
				$o->active		= intval($o->active);
				$o->fullname	= !empty($o->fullname)? stripslashes($o->fullname) : $o->username;
				$o->about_me	= stripslashes($o->about_me);
				$o->tags		= trim(stripslashes($o->tags));
				$o->tags		= empty($o->tags) ? array() : explode(', ', $o->tags);
				if( empty($o->avatar) ) {
					$o->avatar	= $GLOBALS['C']->DEF_AVATAR_USER;
				}
				$o->age	= '';
				$bd_day	= intval( substr($o->birthdate, 8, 2) );
				$bd_month	= intval( substr($o->birthdate, 5, 2) );
				$bd_year	= intval( substr($o->birthdate, 0, 4) );
				if( $bd_day>0 && $bd_month>0 && $bd_year>0 ) {
					if( date('Y') > $bd_year ) {
						$o->age	= date('Y') - $bd_year;
						if( $bd_month>date('m') || ($bd_month==date('m') && $bd_day>date('d')) ) {
							$o->age	--;
						}
					}
				}
				$o->position	= stripslashes($o->position);
				$o->location	= stripslashes($o->location);
				$o->network_id	= $this->id;
				
				$o->user_details	= FALSE;
				$this->cache->set($cachekey, $o, $GLOBALS['C']->CACHE_EXPIRE);
				$loaded[$cachekey] = $o;
				return $o;
			}
			$this->cache->del($cachekey);
			return FALSE;
		}
		
		public function get_user_details_by_id($uid, $force_refresh=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			$uid	= intval($uid);
			if( 0 == $uid ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',usrdtls,id:'.$uid;

			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
			
			$r	= $this->db2->query('SELECT * FROM users_details WHERE user_id="'.$uid.'" LIMIT 1', FALSE); 
			if( $this->db2->num_rows($r) > 0 ){
				$data	= new stdClass;
				if( $ud = $this->db2->fetch_object($r) ) {
					foreach($ud as $k=>$v) {
						$data->$k	= stripslashes($v);
					}
				}
			}else{
				$data = FALSE;
			}
				
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			$loaded[$cachekey] = $data;
			return $data;
		}
		
		public function get_user_follows($uid, $force_refresh=FALSE, $type = FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			$uid	= intval($uid);
			if( 0 == $uid ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',userfollows:'.$uid.($type ? ',type:'.$type : '');

			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
			$data	= new stdClass;
			$data->followers		= array();
			$data->follow_users	= array();
			$data->follow_groups	= array();
			
			if( ($type && $type == 'hisfollowers') || ($type === FALSE) ){
				$r	= $this->db2->query('SELECT who, whom_from_postid FROM users_followed WHERE whom="'.$uid.'" ORDER BY id DESC', FALSE);
				while($o = $this->db2->fetch_object($r)) {
					$data->followers[intval($o->who)]	= $o->whom_from_postid;
				}
			}
			if( ($type && $type == 'hefollows') || ($type === FALSE) ){
				$r	= $this->db2->query('SELECT whom, whom_from_postid FROM users_followed WHERE who="'.$uid.'" ORDER BY id DESC', FALSE);
				while($o = $this->db2->fetch_object($r)) {
					$data->follow_users[intval($o->whom)]	= $o->whom_from_postid;
				}
			}
			if( ($type && $type == 'hisgroups') || ($type === FALSE) ){
				$r	= $this->db2->query('SELECT group_id, group_from_postid FROM groups_followed WHERE user_id="'.$uid.'" ORDER BY id DESC', FALSE);
				while($o = $this->db2->fetch_object($r)) {
					$data->follow_groups[intval($o->group_id)]	= $o->group_from_postid;
				}
			}
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			$loaded[$cachekey] = $data;
			return $data;
		}
		
		public function get_online_users($force_refresh=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			
			$cachekey	= 'n:'.$this->id.',online_userz';
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			
			$data	= array();
			$this->db2->query('SELECT avatar, username, lastclick_date FROM users WHERE active=1 ORDER BY lastclick_date DESC LIMIT 12');
			while($obj = $this->db2->fetch_object()) {
				if( $obj->lastclick_date < time() - 30*60 ) {
					break;
				}
				$data[]	= array('username' => $obj->username, 'avatar' => ((empty($obj->avatar))? $GLOBALS['C']->DEF_AVATAR_USER : $obj->avatar));
			}
			$this->cache->set( $cachekey, $data, 10*60 );
			
			return $data;
		}
		
		public function get_group_by_name($gname, $force_refresh=FALSE, $return_id=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			if( empty($gname) ) {
				return FALSE;
			}
			$cachekey	= 'n:'.$this->id.',groupname:'.strtolower($gname);
			$gid	= $this->cache->get($cachekey);
			if( FALSE!==$gid && TRUE!=$gid ) {
				return $return_id ? $gid : $this->get_group_by_id($gid);
			}
			$gid	= FALSE;
			$r	= $this->db2->query('SELECT id FROM groups WHERE groupname="'.$this->db2->escape($gname).'" OR title="'.$this->db2->escape($gname).'" LIMIT 1', FALSE);
			if( $o = $this->db2->fetch_object($r) ) {
				$gid	= intval($o->id);
				$this->cache->set($cachekey, $gid, $GLOBALS['C']->CACHE_EXPIRE);
				return $return_id ? $gid : $this->get_group_by_id($gid);
			}
			$this->cache->del($cachekey);
			return FALSE;
		}
		
		public function get_group_by_id($gid, $force_refresh=FALSE)
		{
			
			if( ! $this->id ) {
				return FALSE;
			}
			$gid	= intval($gid);
			if( 0 == $gid ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',groupid:'.$gid;
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			$data	= $this->cache->get($cachekey);
			
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}	
			$r	= $this->db2->query('SELECT * FROM groups WHERE id="'.$gid.'" LIMIT 1', FALSE);
			
			if($o = $this->db2->fetch_object($r)) {
				
				$o->title		= htmlspecialchars($o->title);
				$o->is_public	= $o->is_public==1;
				$o->is_private	= !$o->is_public;
				$o->is_deleted	= FALSE;
				$o->about_me	= stripslashes($o->about_me);
				
				if( empty($o->avatar) ) {
					$o->avatar	= $GLOBALS['C']->DEF_AVATAR_GROUP;
				}
				
				$this->cache->set($cachekey, $o, $GLOBALS['C']->CACHE_EXPIRE);
				$loaded[$cachekey] = $o;
				return $o;
				die();
			}
			$this->cache->del($cachekey);
			return false;
		}
		
		public function get_deleted_group_by_id($gid, $force_refresh=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			$gid	= intval($gid);
			if( 0 == $gid ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',deletedgroupid:'.$gid;
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
			$r	= $this->db2->query('SELECT * FROM groups_deleted WHERE id="'.$gid.'" LIMIT 1', FALSE);
			if($o = $this->db2->fetch_object($r)) {
				$o->title		= stripslashes($o->title);
				$o->is_public	= $o->is_public==1;
				$o->is_private	= !$o->is_public;
				$o->is_deleted	= TRUE;
				$this->cache->set($cachekey, $o, $GLOBALS['C']->CACHE_EXPIRE);
				$loaded[$cachekey] = $o;
				return $o;
			}
			$this->cache->del($cachekey);
			return FALSE;
		}
		
		public function get_group_invited_members($gid, $force_refresh=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			if( ! $g = $this->get_group_by_id($gid, $force_refresh) ) {
				return FALSE;
			}
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',group_invited_members:'.$gid;
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
			$data	= array();
			$r	= $this->db2->query('SELECT user_id FROM groups_private_members WHERE group_id="'.$g->id.'" ORDER BY id ASC', FALSE);
			while($obj = $this->db2->fetch_object($r)) {
				$data[]	= intval($obj->user_id);
			}
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			$loaded[$cachekey] = $data;
			return $data;
		}
		
		public function get_group_members($gid, $force_refresh=FALSE)
		{
			if( ! $this->id ) {
				return FALSE;
			}
			if( ! $g = $this->get_group_by_id($gid, $force_refresh) ) {
				return FALSE;
			}
			$cachekey	= 'n:'.$this->id.',group_members:'.$gid;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data	= array();
			if($g->is_public == 0) {
				$u_in	= $this->get_group_invited_members($gid, $force_refresh);
				$r	= $this->db2->query('SELECT id FROM users WHERE active=1 AND is_network_admin=1', FALSE);
				while($sdf = $this->db2->fetch_object($r)) {
					$u_in[]	= intval($sdf->id);
				}
				$u_in	= array_unique($u_in);
				$u_in	= count($u_in)==0 ? '-1' : implode(', ', $u_in);
				$r	= $this->db2->query('SELECT user_id, group_from_postid FROM groups_followed WHERE group_id="'.$g->id.'" AND user_id IN('.$u_in.') ORDER BY id ASC', FALSE);
			}
			else {
				$r	= $this->db2->query('SELECT user_id, group_from_postid FROM groups_followed WHERE group_id="'.$g->id.'" ORDER BY id ASC', FALSE);
			}
			while($o = $this->db2->fetch_object($r)) {
				$data[intval($o->user_id)]	= intval($o->group_from_postid);
			}
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
		
		public function get_last_post_id() 
		{
			if( ! $this->id ) {
				return 0;
			}
			return intval($this->db2->fetch_field('SELECT id FROM posts ORDER BY id DESC LIMIT 1'));
		}
		
		public function get_recent_posttags($count=10, $obj_id = FALSE, $type=FALSE, $force_refresh=FALSE)
		{	
			$in_where 	= '';
			$in_cache	= '';
			if( $type && in_array($type, array('user', 'group'))){
				$in_cache 	= ':'.$type;
				$obj_id	= intval($obj_id);
	
				if( $obj_id && $obj_id>0 ){
					$in_cache	.= ':uid:'.$obj_id;
					$in_where	= ($type == 'user')? ' WHERE user_id="'.$obj_id.'"' : ' WHERE group_id="'.$obj_id.'"';
				}else{
					$in_cache	= '';
				}
			}
			
			$cachekey	= 'n:'.$this->id.',latest_active_tags'.$in_cache;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data = array();
			
			$this->db2->query('SELECT tag_name FROM `post_tags` '.$in_where.' ORDER BY id DESC LIMIT '.$count);
			while($tmp = $this->db2->fetch_object()) {
				$data[] = $tmp->tag_name;
			}
			$data = array_count_values($data);
			arsort($data);
			$data = array_keys($data);
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
		
		public function get_user_notif_rules($user_id, $force_refresh=FALSE)
		{
			$cachekey	= 'n:'.$this->id.',usr_ntf_rulz:'.$user_id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$this->db2->query('SELECT * FROM users_notif_rules WHERE user_id="'.$user_id.'" LIMIT 1');
			if( ! $obj = $this->db2->fetch_object() ) {
				require_once( $GLOBALS['C']->INCPATH.'helpers/func_signup.php' );
				set_user_default_notification_rules($user_id);
			}
			$this->db2->query('SELECT * FROM users_notif_rules WHERE user_id="'.$user_id.'" LIMIT 1');
			if( ! $obj = $this->db2->fetch_object() ) {
				return FALSE;
			}
			unset($obj->user_id);
			$this->cache->set($cachekey, $obj, $GLOBALS['C']->CACHE_EXPIRE);
			return $obj;
		}
		
		public function get_posts_api($id, $force_refresh=FALSE)
		{
			$id	= intval($id);
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',post_app:'.$id;
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey];
			}
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey]	= $data;
				return $data;
			}
			$r	= $this->db2->query('SELECT id, name FROM applications WHERE id="'.$id.'" LIMIT 1', FALSE);
			if( $data = $this->db2->fetch_object($r) ) {
				$data->name	= stripslashes($data->name);
				$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
				$loaded[$cachekey]	= $data;
				return $data;
			}
			return FALSE;
		}
		
		public function send_notification_email($to_user_id, $notif_type, $subject, $message_txt, $message_html, $inD=FALSE)
		{
			global $C, $D, $page;
			if( $inD ) {
				foreach($inD as $k=>$v) {
					$D->$k	= $v;
				}
			}
			$to_user	= $this->get_user_by_id($to_user_id);
			if( !$to_user || empty($subject) || empty($message_txt) || empty($message_html) ) {
				return;
			}
			$D->page	= & $page;
			$D->user	= $to_user;
			$D->subject		= $subject;
			$D->message_txt	= $message_txt;
			$D->message_html	= $message_html;
			$msgtxt		= $page->load_single_block('email/notifications_txt.php', FALSE, TRUE);
			$msghtml	= $page->load_single_block('email/notifications_html.php', FALSE, TRUE);
			if( empty($msgtxt) || empty($msghtml) ) {
				return;
			}
			if( $C->SITE_URL != $C->DEF_SITE_URL ) {
				$msgtxt	= str_replace($C->SITE_URL, $C->DEF_SITE_URL, $msgtxt);
				$msghtml	= str_replace($C->SITE_URL, $C->DEF_SITE_URL, $msghtml);
			}
			if( preg_match('/^(http(s)?\:\/\/)m\.(.*)$/iu', $C->DEF_SITE_URL, $m) ) {
				$siteurl	= $m[1].$m[3];
				$msgtxt	= str_replace($C->DEF_SITE_URL, $siteurl, $msgtxt);
				$msghtml	= str_replace($C->DEF_SITE_URL, $siteurl, $msghtml);
			}
			do_send_mail_html($to_user->email, $subject, $msgtxt, $msghtml);
		}
		
		public function get_private_groups_ids($force_refresh = FALSE)
		{
			if( ! $this->id ) {
				return array();
			}
			global $C;
			
			$cachekey			= 'n:'.$this->id.',private_groups';
			$private_groups_ids	= $this->cache->get($cachekey);

			if( FALSE !== $private_groups_ids && TRUE!=$force_refresh ) {
				return $private_groups_ids;
			}
			$private_groups_ids = array();
			$r	= $this->db2->query('SELECT id FROM groups WHERE is_public=0', FALSE);
			while($obj = $this->db2->fetch_object($r)) {
				$private_groups_ids[]	= $obj->id;
			}
			
			$this->cache->del($cachekey);
			$this->cache->set($cachekey, $private_groups_ids, $GLOBALS['C']->CACHE_EXPIRE);
			
			return $private_groups_ids;
		}
		
		public function get_post_protected_user_ids($force_refresh = FALSE)
		{
			if( ! $this->id ) {
				return array();
			}
			global $C;
			
			$cachekey				= 'n:'.$this->id.',post_protected_users';
			$post_protected_user_ids	= $this->cache->get($cachekey);
			
			if( FALSE !== $post_protected_user_ids && TRUE!=$force_refresh ) {
				return $post_protected_user_ids;
			}	
			
			$post_protected_user_ids = array();
			$r	= $this->db2->query('SELECT id FROM users WHERE is_posts_protected=1', FALSE);
			while($obj = $this->db2->fetch_object($r)) {
				$post_protected_user_ids[]	= $obj->id;
			}
				
			$this->cache->del($cachekey);
			$this->cache->set($cachekey, $post_protected_user_ids, $GLOBALS['C']->CACHE_EXPIRE);
			
			return $post_protected_user_ids;
		}
		
		public function user_admin_group_ids($user_id, $force_refresh=FALSE)
		{
			if( ! is_numeric($user_id) || $user_id < 1) {
				return array();
			}
			
			static $loaded = array();
			$cachekey	= 'n:'.$this->id.',useradmingrps:'.$user_id; 
			if( isset($loaded[$cachekey]) && TRUE!=$force_refresh ) {
				return $loaded[$cachekey]; 
			}
			
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				$loaded[$cachekey] = $data;
				return $data;
			}
		
			$data = array();
			$this->db2->query('SELECT group_id FROM groups_admins WHERE user_id="'.$user_id.'"');
			while($tmp = $this->db2->fetch_object()) {
				$data[$tmp->group_id]		= 1;
			}
			
			$loaded[$cachekey] = $data;
			$this->cache->set($cachekey, $loaded[$cachekey], $GLOBALS['C']->CACHE_EXPIRE);
			
			return $loaded[$cachekey];
		}
		
		public function get_dashboard_tabstate($user_id, $tabs, $current_tab = FALSE)
		{
			$user_id	= intval($user_id);
			$clear_tabstate = FALSE;
				
			if( is_array($tabs) ) {
				$result	= array();
				$tmp	= array();
				foreach($tabs as $tab) {
					$result[$tab]	= 0;
					$tmp[]	= '"'.$this->db2->e($tab).'"';
				}
				$tmp	= implode(', ', $tmp);
				$r	= $this->db2->query('SELECT tab, state, newposts FROM users_dashboard_tabs WHERE user_id="'.$user_id.'" AND tab IN('.$tmp.') LIMIT '.count($tabs), FALSE);
				while( $obj = $this->db2->fetch_object($r) ) {
					$result[$obj->tab]	= $obj->state==0 ? 0 : intval($obj->newposts);
					if( $result[$obj->tab] > 99 ) {
						$result[$obj->tab]	= '99+';
					}
					if( $result[$obj->tab] > 0 && $current_tab && $obj->tab == $current_tab ){
						$clear_tabstate = TRUE;
					}
				}
				if( $current_tab && $clear_tabstate){
					$this->reset_dashboard_tabstate( $user_id, $current_tab );
				}
				return $result;
			}
			else {
				$r	= $this->db2->query('SELECT tab, state, newposts FROM users_dashboard_tabs WHERE user_id="'.$user_id.'" AND tab="'.$this->db2->e($tabs).'" LIMIT 1', FALSE);
				if( ! $obj = $this->db2->fetch_object($r) ) {
					return -1;
				}
				$result	= $obj->state==0 ? 0 : intval($obj->newposts);
				if( $result > 99 ) {
					$result	= '99+';
				}
				if($result > 0){
					$clear_tabstate = TRUE;
				}
				if( $current_tab && $clear_tabstate){
					$this->reset_dashboard_tabstate( $user_id, $current_tab );
				}
				return $result;
			}
		}
		
		public function set_dashboard_tabstate($user_id, $tab, $withnum=0)
		{
			$user_id	= intval($user_id);
			$withnum	= intval($withnum);
			$tab_state 	= $this->get_dashboard_tabstate($user_id, $tab);
			$tab_state 	= intval($tab_state);
			$currnum	= ($tab_state >= 0)? $tab_state : 0;
				
			if( $currnum==0 && $withnum<=0 ) {
				return TRUE;
			}
			if( $currnum>0 && $withnum==0 ) {
				$this->reset_dashboard_tabstate($user_id, $tab);
				return TRUE;
			}
			if( $withnum>0 ) {
				$withnum	+= $currnum;
				if($tab_state == -1){
					$this->db2->query('INSERT INTO users_dashboard_tabs SET user_id="'.$user_id.'", tab="'.$this->db2->e($tab).'", state="1", newposts="'.$withnum.'" ', FALSE);
				}else{
					$this->db2->query('UPDATE users_dashboard_tabs SET state="1", newposts="'.$withnum.'" WHERE user_id="'.$user_id.'" AND tab="'.$this->db2->e($tab).'" ', FALSE);
				}
				return TRUE;
			}
		}
		public function reset_dashboard_tabstate($user_id, $tab)
		{
			$this->db2->query('DELETE FROM users_dashboard_tabs WHERE user_id="'.$user_id.'" AND tab="'.$this->db2->e($tab).'" ', FALSE);
			return TRUE;
		}
	}
	
?>
<?php
	
	class post
	{
		private $network;
		private $user;
		private $cache;
		private $db1;
		private $db2;
		private $available_uploads;
		public $post_id;
		public $post_type;
		public $post_tmp_id;
		public $post_api_id;
		public $post_user;
		public $post_user_details;
		public $post_to_user;
		public $post_group;
		public $post_message;
		public $post_mentioned;
		public $post_attached;
		public $post_posttags;
		public $post_date;
		public $post_comments;
		public $post_commentsnum;
		public $permalink;
		public $is_system_post	= FALSE;
		public $is_feed_post	= FALSE;
		public $error	= FALSE;
		public $tmp;
		public $newcomments;
		
		public function __construct($type, $load_id=FALSE, $load_obj=FALSE)
		{
			global $C;
			$this->tmp	= new stdClass;
			$this->network	= & $GLOBALS['network'];
			$this->user		= & $GLOBALS['user'];
			$this->page		= & $GLOBALS['page'];	
			$this->cache	= & $GLOBALS['cache'];
			$this->db1	= & $GLOBALS['db1'];
			$this->db2	= & $GLOBALS['db2'];
			$type	= $type=='private' ? 'private' : 'public';
			$this->post_type = $type;
			if( ! $this->network->id ) {
				$this->error	= TRUE;
				return;
			}
			if( $load_id ) {
				$id	= intval($load_id);
				$r	= $this->db2->query('SELECT * FROM '.($type=='private'?'posts_pr':'posts').' WHERE id="'.$id.'" LIMIT 1', FALSE);
				if( ! $obj = $this->db2->fetch_object($r) ) {
					$this->error	= TRUE;
					return;
				}
			}
			elseif( $load_obj ) {
				$obj	= $load_obj;
				$id	= intval($obj->id);
				if( ! $id ) {
					$this->error	= TRUE;
					return;
				}
			}
			else {
				$this->error	= TRUE;
				return;
			}
			if( $type=='private' && !$this->user->is_logged ) {
				$this->error	= TRUE;
				return;
			}
			if( $type=='private' && $this->user->id!=$obj->user_id && $this->user->id!=$obj->to_user ) {
				$this->error	= TRUE;
				return;
			}
			if( $type=='private' && $this->user->id==$obj->to_user && $obj->is_recp_del==1 ) {
				$this->error	= TRUE;
				return;
			}
			$g	= FALSE;
			if( $type == 'public' && $obj->group_id>0 ) {
				$g	= $this->network->get_group_by_id($obj->group_id);
				if( $g ) {
					if( !$g->is_public && !$this->user->is_logged ) {
						$this->error	= TRUE;
						return;
					}
					if( !$g->is_public && !$this->user->info->is_network_admin ) {
						$i	= $this->network->get_group_invited_members($g->id);
						if( !$i || !in_array(intval($this->user->id),$i) ) {
							$this->error	= TRUE;
							return;
						}
					}
				}
				if( ! $g ) {
					$g	= $this->network->get_deleted_group_by_id($obj->group_id);
				}
				if( ! $g ) {
					$this->error	= TRUE;
					return;
				}
				$g->group_link = $C->SITE_URL.$g->groupname; //@TODO: edit this
			}
			$u1	= FALSE;
			$ud1 = FALSE;

			if( $obj->user_id == 0 ) {
				$u1	= (object) array('id'=>0);
				$this->is_system_post	= TRUE;
			}
			else {
				$u1	= $this->network->get_user_by_id($obj->user_id);
				if( ! $u1 ) {
					$this->error	= TRUE;
					return;
				}
				if( $this->user->id == $obj->user_id ){
					$ud1 = $this->network->get_user_details_by_id($obj->user_id);
				}
			}
			$u2	= FALSE;
			if( $type == 'private' ) {
				$u2	= $this->network->get_user_by_id($obj->to_user);
				if( ! $u2 ) {
					$this->error	= TRUE;
					return;
				}
			}
			$this->post_id		= intval($obj->id);
			$this->post_api_id	= intval($obj->api_id);
			$this->post_user		= &$u1;

			$this->post_user_details		= &$ud1;
			$this->post_to_user	= &$u2;
			$this->post_group		= &$g;
			$this->post_message	= stripslashes($obj->message);
			$this->post_date		= intval($obj->date);
			$this->post_mentioned	= array();
			$this->post_attached	= array();
			$this->post_posttags	= array();
			$this->post_comments	= array();
			$this->post_commentsnum	= 0;
			$this->available_uploads = array('image', 'file', 'videoembed', 'link');
			if( $obj->mentioned > 0 ) {
				if( $C->post_cache_is_activated ){
					$this->post_mentioned = $this->get_post_mentioned();
				}else{
					$post_table = ($this->post_type=='private'?'posts_pr_mentioned':'posts_mentioned');
					$r	= $this->db2->query('SELECT users.id, username, fullname FROM users, '.$post_table .' WHERE users.id='.$post_table .'.user_id AND post_id="'.$this->post_id.'" LIMIT '.$obj->mentioned, FALSE);
					while($o = $this->db2->fetch_object($r)) {
						$this->post_mentioned[]	= array($o->username, $o->fullname, $o->id);
					}
				}
			}
			if( $obj->attached > 0 ) {
				if( $C->post_cache_is_activated ){
					$this->post_attached = $this->get_post_attached();
				}else{
					$r	= $this->db2->query('SELECT id, type, data FROM '.($type=='private'?'posts_pr_attachments':'posts_attachments').' WHERE post_id="'.$obj->id.'" LIMIT '.$obj->attached, FALSE);
					
					foreach($this->available_uploads as $file_type){
						$this->post_attached[$file_type] = array();
					}
				
					while($o = $this->db2->fetch_object($r)) {
						$tmp = @unserialize(stripslashes($o->data));
						if( !$tmp ){
							$tmp = preg_replace_callback( 
    								'!(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|d:|i:|o:|N;))!s', 
    								'serialize_fix_callback', 
    								stripslashes($o->data)
							); 
							$tmp = @unserialize(stripslashes($tmp));
						}
						$o->data	= $tmp; 
						
						$o->data->attachment_id	= $o->id;
						$this->post_attached[stripslashes($o->type)][]	= $o->data;
					}
					
					foreach($this->available_uploads as $file_type){
						if( count($this->post_attached[$file_type]) == 0 ) unset($this->post_attached[$file_type]);
					}
				}
			}
			if( $obj->posttags > 0 ) {
				//if( preg_match_all('/\#([א-תÀ-ÿ一-龥а-яa-z0-9ա-ֆ\-_]{1,50})/iu', $this->post_message, $matches, PREG_PATTERN_ORDER) ) {
				if( preg_match_all('/\#([\pL0-9]{1,50})/iu', $this->post_message, $matches, PREG_PATTERN_ORDER) ) {
					foreach($matches[1] as $tg) {
						$this->post_posttags[]	= trim($tg);
					}
					$this->post_posttags	= array_unique($this->post_posttags);
				}
			}
			
			$this->newcomments = 0;
			if( $obj->comments > 0 ) { 
				$limit_number = ($this->page->request[0] == 'view')? '' : ' LIMIT '.$C->POST_LAST_COMMENTS;
				$r	= $this->db2->query('SELECT *, (SELECT newcomments FROM posts_comments_watch WHERE post_id="'.$obj->id.'" AND user_id="'.$this->user->id.'" LIMIT 1) AS newcomments FROM '.($type=='private'?'posts_pr_comments':'posts_comments').' WHERE post_id="'.$obj->id.'" ORDER BY id DESC '.$limit_number, FALSE);
				
				while($o = $this->db2->fetch_object($r)) {
					$tmp	= new postcomment($this, FALSE, $o);
					if( $tmp->error ) {
						continue;
					}
					$this->post_comments[]	= $tmp;
					$this->newcomments = is_null($o->newcomments)? 0 : $o->newcomments;
				}

				$this->post_comments = array_reverse($this->post_comments, TRUE);
				$this->post_commentsnum	= ($this->page->request[0] == 'view')? count($this->post_comments) : $obj->comments;
			}
			
			$this->post_type		= $type;
			$this->post_tmp_id	= $type.'_'.$this->post_id;
			$this->permalink		= $C->SITE_URL.'view/'.($type=='private'?'priv':'post').':'.$this->post_id;
			if( $this->is_system_post ) {
				$this->permalink	= $C->SITE_URL;
				$tmp	= @unserialize($this->post_message);
				if( !$tmp || !is_object($tmp) || !isset($tmp->lang_key) || !isset($tmp->lang_params) ) {
					$this->error	= TRUE;
					return;
				}
				global $page;
				$page->load_langfile('inside/notifications.php');
				$this->post_message	= $page->lang($tmp->lang_key, $tmp->lang_params);
				$this->post_message	= str_replace('#AND#', $page->lang('ntfcombined_and'), $this->post_message);
				if( empty($this->post_message) ) {
					$this->error	= TRUE;
					return;
				}
				$this->tmp->syspost_about_user	= FALSE;
				if( $tmp->from_user_id ) {
					$this->tmp->syspost_about_user	= $this->network->get_user_by_id($tmp->from_user_id);
				}
			}
			return TRUE;
		}
		
		public function is_post_faved()
		{
			if( isset($this->tmp->is_post_faved) ) {
				return $this->tmp->is_post_faved;
			}
			if( $this->error ) {
				$this->tmp->is_post_faved	= FALSE;
				return FALSE;
			}
			if( $this->is_system_post ) {
				$this->tmp->is_post_faved	= FALSE;
				return FALSE;
			}
			if( ! $this->user->is_logged ) {
				$this->tmp->is_post_faved	= FALSE;
				return FALSE;
			}
			if( ! $favs = $this->get_post_favs() ) {
				$this->tmp->is_post_faved	= FALSE;
				return FALSE;
			}
			$this->tmp->is_post_faved = in_array(intval($this->user->id), $favs);
			return $this->tmp->is_post_faved;
		}
		
		public function get_post_favs($force_refresh=FALSE)
		{
			if( $this->error ) {
				return FALSE;
			}
			if( $this->is_system_post ) {
				return FALSE;
			}
			$cachekey	= 'n:'.$this->network->id.',post_favs:'.$this->post_type.':'.$this->post_id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data	= array();
			$r	= $this->db2->query('SELECT user_id FROM post_favs WHERE post_type="'.$this->post_type.'" AND post_id="'.$this->post_id.'" ', FALSE);
			while($o = $this->db2->fetch_object($r)) {
				$data[]	= intval($o->user_id);
			}
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
		
		public function fave_post($state=TRUE)
		{
			if( $this->error ) {
				return FALSE;
			}
			if( $this->is_system_post ) {
				return FALSE;
			}
			if( ! $this->user->is_logged ) {
				return FALSE;
			}
			$b	= $this->is_post_faved();
			$u	= intval($this->user->id);
			if( $b && !$state ) {
				$this->db2->query('DELETE FROM post_favs WHERE user_id="'.$u.'" AND post_type="'.$this->post_type.'" AND post_id="'.$this->post_id.'" LIMIT 1', FALSE);
			}
			elseif( !$b && $state ) {
				$this->db2->query('INSERT INTO post_favs SET user_id="'.$u.'", post_type="'.$this->post_type.'", post_id="'.$this->post_id.'", date="'.time().'" ', FALSE);
			}
			$this->get_post_favs(TRUE);
			return TRUE;
		}
		
		public function parse_text()
		{
			global $C;
			if( $this->error ) {
				return FALSE;
			}
			if( $this->is_system_post ) {
				if( $C->API_ID == 1 ) {
					if( substr($C->DOMAIN, 0, 2) == 'm.' ) {
						$s	= preg_replace('/^m\./i', '', $C->DOMAIN);
						$this->post_message	= str_replace($s, $C->DOMAIN, $this->post_message);
					}
					elseif( preg_match('/\/m(\/|$)/', $_SERVER['REQUEST_URI']) ) {
						$tmp	= preg_replace('/\/m(\/|$)/', '', $C->SITE_URL);
						$tmp	= rtrim($tmp,'/').'/';
						$this->post_message	= str_replace($tmp, $C->SITE_URL, $this->post_message);
					}
				}
				return $this->post_message;
			}
			$message	= htmlspecialchars($this->post_message);
			if( FALSE!==strpos($message,'http://') || FALSE!==strpos($message,'http://') || FALSE!==strpos($message,'ftp://') ) {
				$message	= preg_replace('#(^|\s)((http|https|ftp)://\w+[^\s\[\]]+)#ie', 'post::_postparse_build_link("\\2", "\\1")', $message);
			}
			
			foreach($C->POST_ICONS as $k=>$v) {
				$txt	= '<img src="'.$C->STATIC_URL.'images/icons/'.$v.'" class="post_smiley" alt="'.$k.'" title="'.$k.'" />';
				$message	= str_replace($k, $txt, $message);
			}
			
			if( count($this->post_mentioned) > 0 ) {
				$tmp	= array();
				foreach($this->post_mentioned as $i=>$v) {
					$tmp[$i]	= mb_strlen($v[0]);
				}
				arsort($tmp);
				$tmp2	= array();
				foreach($tmp as $i=>$v) {
					$tmp2[]	= $this->post_mentioned[$i];
				}
				foreach($tmp2 as $u) {
					$txt	= '<a href="'.$C->SITE_URL.$u[0].'" title="'.htmlspecialchars($u[1]).'" class="bizcard" data-userid="'.$u[2].'"><span class="post_mentioned"><b>@</b>'.( $C->NAME_INDENTIFICATOR == 1? $u[0] : $u[1] ).'</span></a>';
					$message	= preg_replace('/(^|\s)\@'.preg_quote($u[0]).'/ius', '$1'.$txt, $message);
				}
			}
			if( count($this->post_posttags) > 0 ) {
				$tmp	= array();
				foreach($this->post_posttags as $i=>$v) {
					$tmp[$i]	= mb_strlen($v);
				}
				arsort($tmp);
				$tmp2	= array();
				foreach($tmp as $i=>$v) {
					$tmp2[]	= $this->post_posttags[$i];
				}
				foreach($tmp2 as $tag) {
					$txt	= '<a href="'.$C->SITE_URL.'search/tab:tags/s:'.$tag.'" title="'.$tag.'"><span class="post_tag"><b>#</b>'.$tag.'</span></a>';
					$message	= preg_replace('/(^|\s)\#'.preg_quote($tag).'/ius', '$1'.$txt, $message);
				}
			}
			
			return $message;
		}
		
		public static function parse_date($timestamp, $return_words='auto', $return_dt_format='%b %d %Y, %H:%M')
		{
			if( $return_words == FALSE ) {
				return strftime($return_dt_format, $timestamp);
			}
			$time	= time() - $timestamp;
			$h	= floor($time / 3600);
			$time	-= $h * 3600;
			$m	= floor($time / 60);
			$time	-= $m * 60;
			$s	= $time;
			if( $return_words === 'auto' && $h >= 12 ) {
				return strftime($return_dt_format, $timestamp);
			}
			$txt	= '##BEFORE## ';
			if( $h > 0 ) {
				$txt	.= $h;
				$txt	.= $h==1 ? ' ##HOUR##' : ' ##HOURS##';
			}
			if( $h >= 3 ) {
				$txt	.= ' ##AGO##';
				return post::_parse_date_replace_strings($txt);
			}
			if( $m > 0 ) {
				if( $h > 0 ) {
					$txt	.= ' ##AND## ';
				}
				$txt	.= $m;
				$txt	.= $m==1 ? ' ##MIN##' : ' ##MINS##';
				if( $h > 0 ) {
					$txt	.= ' ##AGO##';
					return post::_parse_date_replace_strings($txt);
				}
			}
			if( $h==0 && $m==0 ) {
				if( $s == 0 ) {
					return post::_parse_date_replace_strings('##NOW##');
				}
				$txt	.= $s;
				$txt	.= $s==1 ? ' ##SEC##' : ' ##SECS##';
			}
			$txt	.= ' ##AGO##';
			return post::_parse_date_replace_strings($txt);
		}
		
		public static function _parse_date_replace_strings($txt='')
		{
			global $page;
			$tmp	= array (
				'##BEFORE##'	=> $page->lang('posttime_before'),
				'##HOUR##'		=> $page->lang('posttime_hour'),
				'##HOURS##'		=> $page->lang('posttime_hours'),
				'##MIN##'		=> $page->lang('posttime_min'),
				'##MINS##'		=> $page->lang('posttime_mins'),
				'##SEC##'		=> $page->lang('posttime_sec'),
				'##SECS##'		=> $page->lang('posttime_secs'),
				'##AND##'		=> $page->lang('posttime_and'),
				'##AGO##'		=> $page->lang('posttime_ago'),
				'##NOW##'		=> $page->lang('posttime_now'),
			);
			$txt	= str_replace(array_keys($tmp), array_values($tmp), $txt);
			$txt	= trim($txt);
			$txt	= str_replace(' ', '&nbsp;', $txt);
			return $txt;
		}
		
		public function parse_group($cutstr=20)
		{
			if( $this->error ) {
				return FALSE;
			}
			if( ! $this->post_group ) {
				return '';
			}
			if( $this->post_group->is_deleted ) {
				return $GLOBALS['page']->lang('postgroup_in').'&nbsp;<a title="'.$GLOBALS['page']->lang('postgroup_del').' '.$this->post_group->title.'">'.str_cut($this->post_group->title,intval($cutstr)).'</a>';
			}
			return $GLOBALS['page']->lang('postgroup_in').'&nbsp;<a href="'.$GLOBALS['C']->SITE_URL.$this->post_group->groupname.'" title="'.$this->post_group->title.'">'.str_cut($this->post_group->title,intval($cutstr)).'</a>';
		}
		
		public static function parse_api($api_id=0)
		{
			if( $api_id == 0 ) {
				return '';
			}
			if( ! $api = $GLOBALS['network']->get_posts_api($api_id) ) {
				return '';
			}
			return $GLOBALS['page']->lang('postapi_via').'&nbsp;'.$api->name;
		}
		
		public function if_can_delete()
		{
			global $C;
			if( $this->error ) {
				return FALSE;
			}
			if( ! $this->user->is_logged ) {
				return FALSE;
			}
			if( $this->post_type=='private' && $this->post_to_user->id==$this->user->id ) {
				return TRUE;
			}
			if( $this->is_system_post && !$this->post_group ) {
				return TRUE;
			}
			if( $this->user->id == $this->post_user->id ) {
				return TRUE;
			}
			if( $this->user->info->is_network_admin == 1 ) {
				return TRUE;
			}
			if( $this->post_type=='public' && $this->post_group ) {
				$currentpage	= $GLOBALS['page']->request[0];
				if( $currentpage=='group' || $currentpage=='services' ) {
					$g_ids = array();
					$g_ids = $this->network->user_admin_group_ids($this->user->id); 
					if( isset($g_ids[$this->post_group->id]) ) {
						return TRUE;
					}
				}
			}
			return FALSE;
		}
		
		public function delete_this_post( $check_plugins = TRUE )
		{
			global $C, $plugins_manager;
			
			if( $check_plugins ){
				$plugins_manager->onPostDelete( $this );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}
			
			if( ! $this->if_can_delete() ) {
				return FALSE;
			}
			if( $this->is_system_post ) {
				if( $this->post_type=='private' && $this->post_to_user->id==$this->user->id ) {
					$this->db2->query('DELETE FROM posts_pr WHERE id="'.$this->post_id.'" LIMIT 1', FALSE);
					$this->error	= TRUE;
					return TRUE;
				}
				if( $this->post_type=='public' && $this->post_group ) {
					$this->db2->query('DELETE FROM post_userbox WHERE post_id="'.$this->post_id.'" ', FALSE);
					$this->db2->query('DELETE FROM posts WHERE id="'.$this->post_id.'" LIMIT 1', FALSE);
					$this->error	= TRUE;
					return TRUE;
				}else if( $this->post_type=='public' && !$this->post_group ) {
					$this->db2->query('DELETE FROM post_userbox WHERE user_id="'.$this->user->id.'" AND post_id="'.$this->post_id.'" LIMIT 1', FALSE);
					$this->error	= TRUE;
					return TRUE;
				}
				
				if( $this->user->is_network_admin ) {
					if($this->post_type == 'public') {
						$this->db2->query('DELETE FROM post_userbox WHERE post_id="'.$this->post_id.'" ', FALSE);
					}
					$this->db2->query('DELETE FROM '.($this->post_type=='private'?'posts_pr':'posts').' WHERE id="'.$this->post_id.'" LIMIT 1', FALSE);
					$this->error	= TRUE;
					return TRUE;
				}
			}
			if( $this->post_type=='private' && $this->post_to_user->id==$this->user->id ) {
				$this->fave_post(FALSE);
				$this->db2->query('UPDATE posts_pr SET is_recp_del=1 WHERE id="'.$this->post_id.'" LIMIT 1');
				$this->error	= TRUE;
				return TRUE;
			}
			if($this->post_commentsnum > 0){
				$this->post_comments = array();
				$r	= $this->db2->query('SELECT * FROM '.($this->post_type=='private'?'posts_pr_comments':'posts_comments').' WHERE post_id="'.$this->post_id.'" ', FALSE);
				while($o = $this->db2->fetch_object($r)) {
					$tmp	= new postcomment($this, FALSE, $o);
					if( $tmp->error ) {
						continue;
					}
					$this->post_comments[]	= $tmp;
				}
				foreach($this->post_comments as $c) {
					$c->delete_this_comment(FALSE);
				}
			}
			if($this->post_type == 'public') {
				$this->db2->query('DELETE FROM post_userbox WHERE post_id="'.$this->post_id.'" ', FALSE);
			}
			$this->db2->query('DELETE FROM post_favs WHERE post_type="'.$this->post_type.'" AND post_id="'.$this->post_id.'" ', FALSE);
			$this->db2->query('DELETE FROM '.($this->post_type=='private'?'posts_pr_mentioned':'posts_mentioned').' WHERE post_id="'.$this->post_id.'" ', FALSE);
			$this->db2->query('DELETE FROM '.($this->post_type=='private'?'posts_pr':'posts').' WHERE id="'.$this->post_id.'" LIMIT 1', FALSE);
			$this->db2->query('DELETE FROM '.($this->post_type=='private'?'posts_pr_comments_watch':'posts_comments_watch').' WHERE post_id="'.$this->post_id.'" ', FALSE);
			$this->db2->query('DELETE FROM '.($this->post_type=='private'?'posts_pr_attachments':'posts_attachments').' WHERE post_id="'.$this->post_id.'" ', FALSE);
			$this->db2->query('DELETE FROM post_tags WHERE post_id="'.$this->post_id.'" ', FALSE);
			$at_dir	= $C->STORAGE_DIR.'attachments/'.$this->network->id.'/';
			foreach($this->post_attached as $tp=>$at) {
				foreach($at as $k=>$v) {
					if( !isset($v->file_original) && !isset($v->file_thumbnail) ) {
						continue;
					}
					if( isset($v->file_original) ) { rm($at_dir.$v->file_original); }
					if( isset($v->file_preview) ) { rm($at_dir.$v->file_preview); }
					if( isset($v->file_thumbnail) ) { rm($at_dir.$v->file_thumbnail); }
				}
			}
			if( $this->post_type=='public' ) {
				$this->db2->query('UPDATE users SET num_posts=num_posts-1 WHERE id="'.$this->post_user->id.'" LIMIT 1');
				if( $this->post_group ) {
					$this->db2->query('UPDATE groups SET num_posts=num_posts-1 WHERE id="'.$this->post_group->id.'" LIMIT 1');
				}
			}
			$this->error	= TRUE;
			return TRUE;
		}
		
		public static function _postparse_build_link($url, $before='')
		{
			$after	= '';
			if( preg_match('/(javascript|vbscript)/', $url) ) {
				return $before.$url.$after;
			}
			if( preg_match('/([\.,\?]|&#33;)$/', $url, $matches) ) {
				$after	.= $matches[1];
				$url	= preg_replace('/([\.,\?]|&#33;)$/', '', $url);
			}
			$txt	= $url;
			if( strlen($txt) > 60 ) {
				$txt	= substr($txt, 0, 45).'...'.substr($txt, -10);
			}
			return $before.'<a href="'.$url.'" title="'.$url.'" target="_blank" rel="nofollow">'.$txt.'</a>'.$after;
		}

		public function get_all_comments()
		{
			if($this->page->request[0] == 'view'){
				return $this->post_comments;
			}
			
			$comments = array();
			$r	= $this->db2->query('SELECT * FROM '.($this->post_type=='private'?'posts_pr_comments':'posts_comments').' WHERE post_id="'.$this->post_id.'" ORDER BY id ASC ', FALSE);
			 
			while($o = $this->db2->fetch_object($r)) {
				$tmp	= new postcomment($this, FALSE, $o);
				if( $tmp->error ) {
					continue;
				}
				$comments[]	= $tmp;
			}
			
			return $comments;
		}
		
		public function get_comments()
		{
			return $this->post_comments;
		}

		public function get_attachments_data($at_type = FALSE)
		{
			if($at_type){
				return isset($this->post_attached[$at_type])? count($this->post_attached[$at_type]):0;
			}
			$counter = 0;
			foreach($this->post_attached as $file_type){
				$counter += count($file_type);
			}
		}
		private function get_post_mentioned($force_refresh = FALSE)
		{
			if( $this->error ) {
				return array();
			}
			if( $this->is_system_post ) {
				return array();
			}
			$cachekey	= 'n:'.$this->network->id.',post_mentioned:'.$this->post_type.':'.$this->post_id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data	= array();
			$r	= $this->db2->query('SELECT username, fullname FROM users, '.($this->post_type=='private'?'posts_pr_mentioned':'posts_mentioned').' WHERE post_id="'.$this->post_id.'" AND posts_mentioned.user_id=users.id', FALSE);
			while($o = $this->db2->fetch_object($r)) {
				$data[]	= array($o->username, $o->fullname);
			}
			
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
		
		private function get_post_attached($force_refresh = FALSE)
		{
			if( $this->error ) {
				return array();
			}
			if( $this->is_system_post ) {
				return array();
			}
			$cachekey	= 'n:'.$this->network->id.',post_attached:'.$this->post_type.':'.$this->post_id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data	= array();
			$r	= $this->db2->query('SELECT id, type, data FROM '.($this->post_type=='private'?'posts_pr_attachments':'posts_attachments').' WHERE post_id="'.$this->post_id.'"', FALSE);
			foreach($this->available_uploads as $file_type){
				$data[$file_type] = array();
			}
		
			while($o = $this->db2->fetch_object($r)) {
				$o->data	= unserialize(stripslashes($o->data));
				$o->data->attachment_id	= $o->id;
				$data[stripslashes($o->type)][]	= $o->data;
			}
			
			foreach($this->available_uploads as $file_type){
				if( count($data[$file_type]) == 0 ) unset($data[$file_type]);
			}
			
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
		
		
		public function refresh_post_cache()
		{
			$this->get_post_mentions(true);
			$this->get_post_attached(true);
		}
	}
?>
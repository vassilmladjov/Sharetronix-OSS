<?php
	
	class postcomment
	{
		private $network;
		private $user;
		private $cache;
		private $db1;
		private $db2;
		public $post;
		public $comment_id;
		public $comment_api_id;
		public $comment_user;
		public $comment_message;
		public $comment_mentioned;
		public $comment_posttags;
		public $comment_date;
		public $error	= FALSE;
		public $tmp;
		
		
		public function __construct($post_obj, $load_id=FALSE, $load_obj=FALSE)
		{
			global $C;
			$this->tmp	= new stdClass;
			$this->network	= & $GLOBALS['network'];
			$this->user		= & $GLOBALS['user'];
			$this->cache	= & $GLOBALS['cache'];
			$this->db1	= & $GLOBALS['db1'];
			$this->db2	= & $GLOBALS['db2'];
			$this->post	= & $post_obj;
			if( ! $this->network->id ) {
				$this->error	= TRUE;
				return;
			}
			if( ! $this->post instanceof post ) {
				$this->error	= TRUE;
				return;
			}
			if( $this->post->error ) {
				$this->error	= TRUE;
				return;
			}
			if( $load_id ) {
				$id	= intval($load_id);
				$r	= $this->db2->query('SELECT * FROM '.($this->post->post_type=='private'?'posts_pr_comments':'posts_comments').' WHERE id="'.$id.'" LIMIT 1', FALSE);
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
			$u1	= $this->network->get_user_by_id($obj->user_id);
			if( ! $u1 ) {
				$this->error	= TRUE;
				return;
			}
			$this->comment_id		= intval($obj->id);
			$this->comment_api_id	= intval($obj->api_id);
			$this->comment_user	= &$u1;
			$this->comment_message	= stripslashes($obj->message);
			$this->comment_date		= intval($obj->date);
			$this->comment_mentioned	= array();
			$this->comment_posttags	= array();
			if( $obj->mentioned > 0 ) {
				if( $C->comment_cache_is_activated ){
					$this->comment_mentioned = $this->get_comment_mentioned();
				}else{
					$r	= $this->db2->query('SELECT user_id FROM '.($this->post->post_type=='private'?'posts_pr_comments_mentioned':'posts_comments_mentioned').' WHERE comment_id="'.$obj->id.'" LIMIT '.$obj->mentioned, FALSE);
					while($o = $this->db2->fetch_object($r)) {
						if( $u = $this->network->get_user_by_id($o->user_id) ) {
							$this->comment_mentioned[]	= array($u->username, $u->fullname, $u->id);
						}
					}
				}
			}
			if( $obj->posttags > 0 ) {
				//if( preg_match_all('/\#([א-תÀ-ÿ一-龥а-яa-z0-9ա-ֆ\-_]{1,50})/iu', $this->comment_message, $matches, PREG_PATTERN_ORDER) ) {
				if( preg_match_all('/\#([\pL0-9]{1,50})/iu', $this->comment_message, $matches, PREG_PATTERN_ORDER) ) {
					foreach($matches[1] as $tg) {
						$this->comment_posttags[]	= trim($tg);
					}
					$this->comment_posttags	= array_unique($this->comment_posttags);
				}
			}
			
			return TRUE;
		}
		
		public function parse_text()
		{
			global $C;
			if( $this->error ) {
				return FALSE;
			}
			$message	= htmlspecialchars($this->comment_message);
			if( FALSE!==strpos($message,'http://') || FALSE!==strpos($message,'http://') || FALSE!==strpos($message,'ftp://') ) {
				$message	= preg_replace('#(^|\s)((http|https|ftp)://\w+[^\s\[\]]+)#ie', 'post::_postparse_build_link("\\2", "\\1")', $message);
			}
			
			foreach($C->POST_ICONS as $k=>$v) {
				$txt	= '<img src="'.$C->STATIC_IMG_URL.'icons/'.$v.'" class="post_smiley" alt="'.$k.'" title="'.$k.'" />';
				$message	= str_replace($k, $txt, $message);
			}
			
			if( count($this->comment_mentioned) > 0 ) {
				$tmp	= array();
				foreach($this->comment_mentioned as $i=>$v) {
					$tmp[$i]	= mb_strlen($v[0]);
				}
				arsort($tmp);
				$tmp2	= array();
				foreach($tmp as $i=>$v) {
					$tmp2[]	= $this->comment_mentioned[$i];
				}
				foreach($tmp2 as $u) {
					$txt	= '<a href="'.$C->SITE_URL.$u[0].'" title="'.htmlspecialchars($u[1]).'" class="bizcard" data-userid="'.$u[2].'"><span class="post_mentioned"><b>@</b>'.( $C->NAME_INDENTIFICATOR == 1? $u[0] : $u[1] ).'</span></a>';
					$message	= preg_replace('/(^|\s)\@'.preg_quote($u[0]).'/ius', '$1'.$txt, $message);
				}
			}
			
			if( count($this->comment_posttags) > 0 ) {
				$tmp	= array();
				foreach($this->comment_posttags as $i=>$v) {
					$tmp[$i]	= mb_strlen($v);
				}
				arsort($tmp);
				$tmp2	= array();
				foreach($tmp as $i=>$v) {
					$tmp2[]	= $this->comment_posttags[$i];
				}
				foreach($tmp2 as $tag) {
					$txt	= '<a href="'.$C->SITE_URL.'search/tab:tags/s:%23'.$tag.'" title="'.$tag.'"><span class="post_tag"><b>#</b>'.$tag.'</span></a>';
					$message	= preg_replace('/(^|\s)\#'.preg_quote($tag).'/ius', '$1'.$txt, $message);
				}
			}
			
			return $message;
		}
		
		public function if_can_delete()
		{
			if( $this->error ) {
				return FALSE;
			}
			if( $this->comment_user->id == $this->user->id ) {
				return TRUE;
			}
			if( $this->user->is_logged && $this->user->info->is_network_admin == 1 ){
				return TRUE;
			}
			if( $this->post->post_type=='public' && $this->post->post_group ) {
				$currentpage	= $GLOBALS['page']->request[0];
				if( $currentpage=='group' || $currentpage=='ajax' ) {
					$g_ids = array();
					$g_ids = $this->network->user_admin_group_ids($this->user->id);
					if( isset($g_ids[$this->post->post_group->id]) ) {
						return TRUE;
					}
				}
			}
			return FALSE;
		}
		
		public function delete_this_comment( $check_plugins = TRUE )
		{
			global $C, $plugins_manager;
			
			if( $check_plugins ){
				$plugins_manager->onPostCommentDelete( $this );
				if( !$plugins_manager->isValidEventCall() ){
					return FALSE;
				}
			}
			
			if( ! $this->if_can_delete() ) {
				return FALSE;
			}
			$this->db2->query('DELETE FROM '.($this->post->post_type=='private'?'posts_pr_comments_mentioned':'posts_comments_mentioned').' WHERE comment_id="'.$this->comment_id.'" ', FALSE);
			$this->db2->query('DELETE FROM '.($this->post->post_type=='private'?'posts_pr_comments':'posts_comments').' WHERE id="'.$this->comment_id.'" LIMIT 1', FALSE);
			$this->db2->query('UPDATE '.($this->post->post_type=='private'?'posts_pr':'posts').' SET comments=comments-1 WHERE id="'.$this->post->post_id.'" LIMIT 1');
			$this->db2->query('UPDATE '.($this->post->post_type=='private'?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments=newcomments-1 WHERE post_id="'.$this->post->post_id.'" AND newcomments<>0');
			$this->error	= TRUE;
			return TRUE;
		}
		
		private function get_comment_mentioned($force_refresh = FALSE)
		{
			if( $this->error ) {
				return array();
			}
			if( $this->post->is_system_post ) {
				return array();
			}
			$cachekey	= 'n:'.$this->network->id.',post_comment:'.$this->post->post_type.':'.$this->comment_id;
			$data	= $this->cache->get($cachekey);
			if( FALSE!==$data && TRUE!=$force_refresh ) {
				return $data;
			}
			$data	= array();
			$comment_table = ($this->post->post_type=='private')? 'posts_pr_comments_mentioned' : 'posts_comments_mentioned';
			$r	= $this->db2->query('SELECT username, fullname FROM users, '.$comment_table.' WHERE comment_id="'.$this->comment_id.'" AND '.$comment_table.'.user_id=users.id ', FALSE);
			while($o = $this->db2->fetch_object($r)) {
				$data[]	= array($o->username, $o->fullname);
			}
			
			$this->cache->set($cachekey, $data, $GLOBALS['C']->CACHE_EXPIRE);
			return $data;
		}
	}
	
?>
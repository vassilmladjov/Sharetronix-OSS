<?php
	
	require_once($C->INCPATH.'conf_embed.php');
	
	class newpostcomment
	{
		private $network	= FALSE;
		public $id		= FALSE;
		private $user	= FALSE;
		public  $post	= FALSE;
		private $cache;
		private $db2;
		public $error	= FALSE;
		public $api_id		= 0;
		public $message		= '';
		public $mentioned	= array();
		public $posttags		= 0;
		public $posttags_container		= array();
		public $comment_user;
		public $comment_message;
		
		public function __construct($post_obj)
		{
			global $C;
			$this->cache	= & $GLOBALS['cache'];
			$this->db2		= FALSE;
			$this->post		= & $post_obj;
			if( ! $this->post instanceof post ) {
				$this->error	= TRUE;
				return;
			}
			$this->api_id	= $C->API_ID;
			$n	= & $GLOBALS['network'];
			if( $n->id ) {
				$u	= & $GLOBALS['user'];
				if($u->is_logged && $u->info->network_id==$n->id) {
					$this->network	= $n;
					$this->user		= $u;
					$this->db2	= & $GLOBALS['db2'];
				}
			}
			$this->id	= FALSE;
			
			$this->comment_user = & $this->user->info;
			$this->comment_message = & $this->message;
			
			$this->comment_date = time();
		}
		
		public function set_user($network_id, $user_id)
		{
			$this->network	= FALSE;
			$this->user		= FALSE;
			$n	= new network;
			if( ! $n->LOAD_by_id($network_id) ) {
				return FALSE;
			}
			if( ! $u = $n->get_user_by_id($user_id) ) {
				return FALSE;
			}
			$this->network	= $n;
			$this->user		= $u;
			return TRUE;
		}
		
		public function set_api_id($api_id)
		{
			if( $this->id ) {
				return FALSE;
			}
			$this->api_id	= intval($api_id);
			return TRUE;
		}
		
		public function set_message($message)
		{
			if( empty($message) ) {
				return FALSE;
			}
			global $C;
			$this->message	= trim($message);
			
			$this->mentioned	= array();
			if( preg_match_all('/\@([a-zA-Z0-9\-_]{3,30})/u', $message, $matches, PREG_PATTERN_ORDER) ) {
				foreach($matches[1] as $unm) {
					if( $usr = $this->network->get_user_by_username($unm) ) {
						$this->mentioned[]	= $usr->id;
					}
				}
			}
			$this->mentioned	= array_unique($this->mentioned);
			
			$this->posttags	= array();
			//if( preg_match_all('/\#([א-תÀ-ÿ一-龥а-яa-z0-9ա-ֆ\-_]{1,50})/iu', $message, $matches, PREG_PATTERN_ORDER) ) {
			if( preg_match_all('/\#([\pL0-9]{1,50})/iu', $this->message, $matches, PREG_PATTERN_ORDER) ) {
				foreach($matches[1] as $tg) {
					$this->posttags[]	= mb_strtolower(trim($tg));
				}
			}
			$this->posttags_container 	= $this->posttags;
			$this->posttags	= count( array_unique($this->posttags) );
		}
		
		public function save()
		{
			if( $this->error ) {
				return FALSE;
			}
			if( ! $this->user->is_logged ) {
				return FALSE;
			}
			if( empty($this->message) ) {
				return FALSE;
			}
			$db2		= & $this->network->db2;
			$is_private	= $this->post->post_type=='private' ? TRUE : FALSE;
			$db_api_id		= intval($this->api_id);
			$db_user_id		= intval($this->user->id);
			$db_message		= $db2->escape($this->message);
			$db_mentioned	= count($this->mentioned);
			$db_posttags	= intval($this->posttags);
			$db_date		= time();
			$db_ip_addr		= ip2long($_SERVER['REMOTE_ADDR']);
			
			global $plugins_manager;
			
			$plugins_manager->onPostCommentSave( $this );
			if( !$plugins_manager->isValidEventCall() ){
				return FALSE;
			}
			
			$db2->query('INSERT INTO '.($is_private?'posts_pr_comments':'posts_comments').' SET api_id="'.$db_api_id.'", post_id="'.$this->post->post_id.'", user_id="'.$db_user_id.'", message="'.$db_message.'", mentioned="'.$db_mentioned.'", posttags="'.$db_posttags.'", date="'.$db_date.'", ip_addr="'.$db_ip_addr.'"   ');
			if( ! $id = $db2->insert_id() ) {
				return FALSE;
			}
			$db2->query('UPDATE '.($is_private?'posts_pr':'posts').' SET comments=comments+1, date_lastcomment="'.time().'" WHERE id="'.$this->post->post_id.'" LIMIT 1');
			
			foreach($this->mentioned as $uid) {
				$db2->query('INSERT INTO '.($is_private?'posts_pr_comments_mentioned':'posts_comments_mentioned').' SET comment_id="'.$id.'", user_id="'.intval($uid).'" ');
			}
			
			$new_comments = '';
			$db2->query('SELECT id, newcomments FROM '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' WHERE user_id="'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" LIMIT 1');
			if( $sdf = $db2->fetch_object() ) {
				if( $sdf->newcomments <> 0 ) {
					$db2->query('UPDATE '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments=0 WHERE id="'.$sdf->id.'" LIMIT 1');
				}
			}
			else {
				$db2->query('INSERT INTO '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' SET user_id="'.$this->user->id.'", post_id="'.$this->post->post_id.'", newcomments=0');
			}
			if( ! $is_private ) {
				
				/*$db2->query('SELECT user_id FROM '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' WHERE user_id<>"'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" ');
				while( $sdf = $db2->fetch_object() ) {
					$this->network->set_dashboard_tabstate($sdf->user_id, 'commented', 1);
				}*/
				//foreach($this->mentioned as $uid) {
				//	За тези хора трябва да се вкарва ред в posts_pr_comments_watch (ако постът е в достъпна за тях група)
				//	Също така да се известяват по e-mail за @user
				//}
				
				//Post tags in the comments(for every post type - Feed, Human) are saved in the post_tags table, so there is no feed check here
				if( count($this->posttags_container) > 0 && (!$this->post->post_group || ($this->post->post_group && $this->post->post_group->is_public == 1)) && $this->user->info->is_posts_protected == 0 ){
					$post_tags_in_brackets 	= array();
					$unique_posttags 		= array();
					$unique_posttags 		= array_unique($this->posttags_container);
					
					foreach($unique_posttags as $tag){
						$post_tags_in_brackets[] = '("'.$tag.'", "'.$this->post->post_id.'")';
					}		
					
					$db2->query('INSERT INTO post_tags(tag_name, post_id ) VALUES '.implode( ',', $post_tags_in_brackets ));
					unset($post_tags_in_brackets, $unique_posttags);
				}
			}
			//$db2->query('UPDATE '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments=newcomments+1 WHERE user_id<>"'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" ');
			//$db2->query('UPDATE '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments=CONCAT(newcomments, ",'.$id.'") WHERE user_id<>"'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" ');
			$db2->query('UPDATE '.($is_private?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments="'.$id.'" WHERE user_id<>"'.$this->user->id.'" AND post_id="'.$this->post->post_id.'" ');
				
			if( $is_private ) {
				$db2->query('UPDATE posts_pr SET is_recp_del=0 WHERE id="'.$this->post->post_id.'" LIMIT 1');
				$uid	= $this->post->post_to_user->id;
				if( $uid == $this->user->id ) {
					$uid	= $this->post->post_user->id;
				}
				
				$notif = new notifier();
				$notif->set_post($this->post);
				$notif->set_comment($this);
				$notif->set_notification_obj('post', $this->post->post_id);
				$notif->onPrivatePost();
			}
			else {
				$notif = new notifier();
				$notif->set_post($this->post);
				$notif->set_comment($this);
				$notif->set_notification_obj('post', $this->post->post_id);
				$notif->onCommentPost();
			}
			$this->id = $id;
			
			return $id;
		}
		public function represent_comment_in_email($is_html = TRUE)
		{
			global $C, $page;
			$page->load_langfile('email/notifications.php');
			
			$delimiter = ($is_html)? '<br />':"\n";
			
			$message = $delimiter.$delimiter.' "'.$this->message.'"';	
			
			return $message;
		}
	}
	
?>
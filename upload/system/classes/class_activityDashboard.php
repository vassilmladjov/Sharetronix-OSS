<?php
	class activityDashboard extends activity
	{
		public function setPostsQuery()
		{
			if( empty($this->tab) ){
				$this->tab = 'all';
			}
			
			switch($this->tab)
			{
				case 'all':
					$this->query	= 'SELECT b.id AS pid, p.*, "public" AS `type` FROM post_userbox b LEFT JOIN posts p ON p.id=b.post_id WHERE p.user_id<> 0 AND b.user_id="'.$this->user->id.'" ';
					$this->query_order = ' ORDER BY b.id DESC ';
					break;
					
				case '@me':
					$this->query	= 'SELECT DISTINCT p.*, p.id AS pid, "public" AS `type` FROM posts p INNER JOIN (SELECT pm.post_id, p.date FROM posts_mentioned pm, posts p WHERE pm.user_id="'.$this->user->id.'" AND pm.post_id=p.id UNION SELECT p.post_id, p.date FROM posts_comments p, posts_comments_mentioned c WHERE c.comment_id = p.id AND c.user_id ="'.$this->user->id.'") x ON x.post_id=p.id '. $this->filter->all();
					$this->query_order = ' ORDER BY x.date DESC ';
					break;
			
				case 'commented':
					$this->query	= 'SELECT p.id, p.id AS pid, p.api_id, p.user_id, p.group_id, "0" AS to_user, p.message, p.mentioned, p.attached, p.posttags, p.comments, p.reshares, p.date, p.ip_addr, "0" AS is_recp_del, "public" AS `type`, p.date_lastcomment AS cdt FROM posts_comments_watch w LEFT JOIN posts p ON p.id=w.post_id WHERE w.user_id="'.$this->user->id.'" AND p.comments>0 ';
					$this->query_order = ' ORDER BY p.date_lastcomment DESC ';
					break;
			
				case 'everybody':
					$this->query	= 'SELECT p.*, p.id AS pid, "public" AS `type` FROM posts p WHERE p.user_id<>0 AND p.api_id<>2 AND p.api_id<>6 '. $this->filter->all();
					$this->query_order = ' ORDER BY p.id DESC ';
					break;
			
				case 'bookmarks':
					$this->query	= 'SELECT p.id, p.id AS pid, p.api_id, p.user_id, p.group_id, "0" AS to_user, p.message, p.mentioned, p.attached, p.posttags, p.comments, p.reshares, p.date, p.ip_addr, "0" AS is_recp_del, "public" AS `type`, f.id AS fid FROM post_favs f LEFT JOIN posts p ON p.id=f.post_id WHERE f.user_id="'.$this->user->id.'" AND f.post_type="public"';
					$this->query_order = ' ORDER BY fid DESC ';
					break;
			
				case 'group':
					$this->query	= 'SELECT p.*, p.id AS pid, "public" AS `type` FROM posts p WHERE p.group_id="'.$this->onlygroup->id.'"';
					$this->query_order = ' ORDER BY p.id DESC ';
					break;
			}
			
			if( empty( $this->query ) ){
				$this->pm->onPageSetQuery();
				
				$tmp = $this->pm->getEventResult();
				if( is_string($tmp) ){
					$this->query = $tmp;
					
					$this->pm->onPageSetQueryOrder();
					$tmp = $this->pm->getEventResult();
					if( is_string($tmp) ){
						$this->query_order = $tmp;
					}
				}
			}
		}
		
		public function setCountNewQuery()
		{ 
			switch($this->tab)
			{
				case 'all':
					$this->query	= 'SELECT COUNT(*) AS cnt FROM post_userbox b LEFT JOIN posts p ON p.id=b.post_id WHERE p.user_id<> 0 AND p.user_id<>"'.$this->user->id.'" AND b.user_id="'.$this->user->id.'" AND p.id>'.$this->newer_post_id;
					break;
						
				case '@me':
					$this->query	= 'SELECT COUNT(*) AS cnt FROM posts p INNER JOIN (SELECT pm.post_id, p.date FROM posts_mentioned pm, posts p WHERE pm.user_id="'.$this->user->id.'" AND pm.post_id=p.id UNION SELECT p.post_id, p.date FROM posts_comments p, posts_comments_mentioned c WHERE c.comment_id = p.id AND c.user_id ="'.$this->user->id.'") x ON x.post_id=p.id '. $this->filter->all().' AND p.user_id<>"'.$this->user->id.'" AND p.id>'.$this->newer_post_id;
					break;
								
				case 'everybody':
					$this->query	= 'SELECT COUNT(*) AS cnt FROM posts p WHERE p.user_id<>0 AND p.user_id<>"'.$this->user->id.'" AND p.api_id<>2 AND p.api_id<>6 '. $this->filter->all().' AND p.id>'.$this->newer_post_id;;
					break;
						
				case 'group':
					$this->query	= 'SELECT COUNT(*) AS cnt FROM posts p WHERE p.group_id="'.$this->onlygroup->id.'" AND p.user_id<>"'.$this->user->id.'" AND p.id>'.$this->newer_post_id;;
					break;
			}
		
		}
		
		public function reset_new_comment_watch( $post_ids, $post_type='public' )
		{
			if( !is_array($post_ids) ){
				return;
			}
			$this->db2->query('UPDATE '.($post_type=='private'?'posts_pr_comments_watch':'posts_comments_watch').' SET newcomments=0 WHERE post_id IN('. implode(',', $post_ids) .') AND user_id="'.$this->user->id.'"');
		}

	}
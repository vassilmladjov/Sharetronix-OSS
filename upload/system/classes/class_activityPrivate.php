<?php
	class activityPrivate extends activity
	{
		public function setPostsQuery()
		{
			$this->query	= 'SELECT p.*, "private" AS `type`, p.id AS pid FROM posts_pr p WHERE (p.user_id="'.$this->user->id.'" OR (p.to_user="'.$this->user->id.'" AND p.is_recp_del=0)) ';
			$this->query_order = ' ORDER BY p.date_lastcomment DESC, p.id DESC ';
		}
		
		public function setCountNewQuery()
		{ 
			$this->query	= 'SELECT COUNT(*) AS cnt FROM posts_pr p WHERE (p.to_user="'.$this->user->id.'" AND p.is_recp_del=0) AND p.id>'.$this->newer_post_id;
		}
	}
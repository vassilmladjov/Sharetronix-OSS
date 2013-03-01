<?php
	class activityGroup extends activity
	{
		private $target_group;
		
		public function setGroup( & $g )
		{
			$this->target_group = $g;
		}
		
		public function setPostsQuery()
		{
			if( !$this->target_group )
			{
				return;
			}
			
			if( empty($this->tab) ){
				$this->tab = 'updates';
			}
			
			if( $this->tab == 'updates' ){
				$this->query	= 'SELECT p.*, id AS pid, "public" AS `type` FROM posts p WHERE p.group_id="'.$this->target_group->id.'"';
				$this->query_order = ' ORDER BY p.id DESC ';
			}else{
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
			if( !$this->target_group )
			{
				return;
			}
				
			$this->query	= 'SELECT COUNT(*) AS cnt FROM posts p WHERE p.group_id="'.$this->target_group->id.'" AND p.user_id<>"'.$this->user->id.'" AND p.id>'.$this->newer_post_id;
		}
		
		public function loadUsers( $type = 'all' )
		{
			if( !$this->target_group )
			{
				return;
			}
				
			global $C;
				
			$ifollow 	= ($this->user->is_logged)? array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users ) : array();
			
			
			switch( $type ){
				case 'admins': 
						$num_results	= $this->db2->fetch_field('SELECT COUNT(*) FROM users u, groups_admins ga WHERE u.id=ga.user_id AND ga.group_id="'. intval($this->target_group->id) .'"');
						$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
						$pg				= $this->page->param('pg') ? intval($this->page->param('pg')) : 1;
						$pg				= min($pg, $num_pages);
						$pg				= max($pg, 1);
						$from			= ($pg - 1) * $C->PAGING_NUM_USERS;
					
						$res = $this->db2->query('SELECT u.* FROM users u, groups_admins ga WHERE u.id=ga.user_id AND ga.group_id="'. intval($this->target_group->id) .'" LIMIT '.$from.','.$C->PAGING_NUM_USERS);
						break;
				case 'privmembers':
						if( $this->target_group->is_private ){
							$num_results	= $this->db2->fetch_field('SELECT COUNT(*) FROM groups_private_members gpm, users u WHERE group_id="'.$this->target_group->id.'" AND gpm.user_id=u.id');
							$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
							$pg				= $this->page->param('pg') ? intval($this->page->param('pg')) : 1;
							$pg				= min($pg, $num_pages);
							$pg				= max($pg, 1);
							$from			= ($pg - 1) * $C->PAGING_NUM_USERS;
							
							$res = $this->db2->query('SELECT gpm.user_id, u.* FROM groups_private_members gpm, users u WHERE group_id="'.$this->target_group->id.'" AND gpm.user_id=u.id LIMIT '.$from.','.$C->PAGING_NUM_USERS);
						}
						break;
				case 'all': 
				default:
						$num_results	= $this->db2->fetch_field('SELECT COUNT(*) FROM users u, groups_followed gf WHERE u.id=gf.user_id AND gf.group_id="'. intval($this->target_group->id) .'"');
						$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
						$pg				= $this->page->param('pg') ? intval($this->page->param('pg')) : 1;
						$pg				= min($pg, $num_pages);
						$pg				= max($pg, 1);
						$from			= ($pg - 1) * $C->PAGING_NUM_USERS;
							
						$res = $this->db2->query('SELECT u.* FROM users u, groups_followed gf WHERE u.id=gf.user_id AND gf.group_id="'. intval($this->target_group->id) .'" LIMIT '.$from.','.$C->PAGING_NUM_USERS);
						break;
			}
			
			if( !isset($num_results, $num_pages, $pg) ){
				$this->pm->onPageSetQuery();
				
				$tmp = $this->pm->getEventResult();
				if( is_string($tmp) ){
					$num_results = $this->db2->fetch_field($tmp);
					$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
					$pg				= $this->page->param('pg') ? intval($this->page->param('pg')) : 1;
					$pg				= min($pg, $num_pages);
					$pg				= max($pg, 1);
					$from			= ($pg - 1) * $C->PAGING_NUM_USERS;
					
					$tmp = $this->pm->onPageSetCountQuery();
					if( is_string($tmp) ){
						$res = $this->db2->query($tmp);
					}
				}
			}
			
			$this->num_results = $num_results;
			$this->num_pages = $num_pages;
			$this->pg = $pg;
			
			if( isset($res) ){
				while($obj = $this->db2->fetch_object($res)) {
					$this->tpl->initRoutine('SingleUser', array( &$obj, &$ifollow ));
					$this->tpl->routine->load();
				}
			}
		}
	}
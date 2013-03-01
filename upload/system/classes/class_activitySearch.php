<?php
	class activitySearch extends activity
	{
		public $search_string = '';
		
		private function _validateString()
		{
			$string = '';
			
			if( ! $this->page->param('s') && empty($this->search_string)){
				return $string;
			}
			
			$string = $this->page->param('s')? $this->page->param('s') : $this->search_string; 
			$string = urldecode( $string );
			$string = str_replace(array('%','_'), array('\%','\_'), $this->db2->e( $string ));
			
			return $string;
		}
		public function setPostsQuery()
		{
			global $C;
			
			$string = '';
			$in_where = '';
			
			$string = $this->_validateString();
			if( empty($string) ){
				return;
			}
			
			$group 	= $this->page->param('pgroup')? $this->page->param('pgroup') : FALSE;
			if( $group ){
				$group = $this->network->get_group_by_name( $group );
				if( !$group ){
					$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No Posts', 'No Posts Found'));
					return;
				}
			}
			
			$user 	= $this->page->param('puser')? $this->page->param('puser') : FALSE;
			if( $user ){
				$user = $this->network->get_user_by_username( $user ); 
				if( !$user ){
					$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No Posts', 'No Posts Found'));
					return;
				}
			}
			$pcomments = isset($_POST['pcomments']) || $this->page->param('pcomments');
			
			switch( $this->tab )
			{
				case 'posts':
					$in_where	.= ' (p.message LIKE "%'.$string.'%"  OR MATCH(p.message) AGAINST("'.$string.'" IN BOOLEAN MODE) ) AND p.user_id<>0 '. $this->filter->all();
					if( $user ){
						$in_where .= ' AND p.user_id='.$user->id;
					} 
					if( $group ){
						$in_where .= ' AND p.group_id='.$group->id;
					}
					
					if( $pcomments ) {
						$this->filter->setPrefix('pc');
						$in_where2	= ' (pc.message LIKE "%'.$string.'%"  OR MATCH(pc.message) AGAINST("'.$string.'" IN BOOLEAN MODE) ) '. $this->filter->users('post_comments');
						
						$this->filter->setPrefix('p');
						$in_where2 .= $this->filter->groups();

						$tmppids	= array();
						$this->db2->query('SELECT pc.post_id FROM posts_comments pc, posts p WHERE p.id=pc.post_id AND '.$in_where2);
						while($tmp = $this->db2->fetch_object()) {
							$tmppids[]	= $tmp->post_id;
						}
						if( 1 == count($tmppids) ) {
							$in_where	.= ' OR id='.reset($tmppids);
						}
						elseif( 1 < count($tmppids) ) {
							$in_where	.= ' OR id IN('.implode(', ', $tmppids).')';
						}
					}
					
					$this->query = 'SELECT p.*, id AS pid, "public" AS `type` FROM posts p WHERE '.$in_where;
					$this->query_order = ' ORDER BY p.id DESC ';
					break;
				case 'tags':
					$string	= preg_replace('/^\#/', '', $string);
					$this->filter->setPrefix('p');
					$this->query = 'SELECT *, "public" AS `type`, p.id AS pid FROM post_tags t LEFT JOIN posts p ON p.id=t.post_id WHERE t.tag_name="'.$string.'" GROUP BY t.post_id '.$this->filter->all();
					$this->query_order =  ' ORDER BY t.id DESC ';
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
		
		public function loadUsers($result_page = FALSE)
		{
			global $C;
				
			$ifollow 	= array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users );	
			
			$string = $this->_validateString();
			if( empty($string) ){
				$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No User Text', 'No Text to search provided'));
				return;
			}
			
			$paging_url		= $C->SITE_URL.'search/tab:users/s:'.$string.'/pg:';
			$num_results	= $this->db2->fetch_field('SELECT COUNT(*) FROM users WHERE username LIKE "%'.$string.'%" OR fullname LIKE "%'.$string.'%" OR tags LIKE "%'.$string.'%" '.$this->filter->users('users'));
			$num_pages		= ceil($num_results / $C->PAGING_NUM_USERS);
			$pg				= $result_page ? intval($result_page) : 1;
			$pg				= min($pg, $num_pages);
			$pg				= max($pg, 1);
			$from			= ($pg - 1) * $C->PAGING_NUM_USERS;
			
			$res = $this->db2->query('SELECT * FROM users WHERE username LIKE "%'.$string.'%" OR fullname LIKE "%'.$string.'%" OR tags LIKE "%'.$string.'%" '.$this->filter->users('users').'  ORDER BY username ASC LIMIT '.$from.' , '.$C->PAGING_NUM_USERS);
			
			if( $this->db2->num_rows( $res ) > 0 ){
				while($obj = $this->db2->fetch_object($res)) {
					$this->tpl->initRoutine('SingleUser', array( &$obj, &$ifollow ));
					$this->tpl->routine->load();
				}
				
				
				$this->tpl->layout->setVar( 'main_content_bottom', $this->tpl->designer->pager( $num_results, $num_pages, $pg, $paging_url ) );
			}else{
				$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No Users', 'No Users Found'));
			}
		}
		
		public function loadGroups($result_page = FALSE)
		{
			global $C;
		
			$ifollow 	= array_keys( $this->network->get_user_follows($this->user->id, FALSE, 'hisgroups')->follow_groups );
				
			$string = $this->_validateString();
			if( empty($string) ){
				$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No Group Text', 'No Text to search provided'));
				return;
			}
			
			$paging_url		= $C->SITE_URL.'search/tab:groups/s:'.$string.'/pg:';
			$num_results	= $this->db2->fetch_field('SELECT COUNT(*) FROM groups WHERE groupname LIKE "%'.$string.'%" OR title LIKE "%'.$string.'%" '.$this->filter->groups('groups'));
			$num_pages		= ceil($num_results / $C->PAGING_NUM_GROUPS);
			$pg				= $result_page ? intval($result_page) : 1;
			$pg				= min($pg, $num_pages);
			$pg				= max($pg, 1);
			$from			= ($pg - 1) * $C->PAGING_NUM_GROUPS;
			
			$res = $this->db2->query('SELECT * FROM groups WHERE groupname LIKE "%'.$string.'%" OR title LIKE "%'.$string.'%" '.$this->filter->groups('groups').'  ORDER BY groupname ASC LIMIT '.$from.', '.$C->PAGING_NUM_GROUPS);
			
			if( $this->db2->num_rows( $res ) > 0 ){
				while($obj = $this->db2->fetch_object($res)) {
					$this->tpl->initRoutine('SingleGroup', array( &$obj, &$ifollow ));
					$this->tpl->routine->load();
				}
				
				$this->tpl->layout->setVar( 'main_content_bottom', $this->tpl->designer->pager( $num_results, $num_pages, $pg, $paging_url ) );
			}else{
				$this->tpl->layout->setVar('main_content', $this->tpl->designer->createNoPostBox('No Groups', 'No Groups Found'));
			}
		}
	}
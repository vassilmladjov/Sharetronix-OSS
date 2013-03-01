<?php
	class postFilter
	{
		private $column_prefix;
		private $user;
		private $network;
		private $page;
		private $db2;
		
		public function __construct()
		{
			$this->user 	= & $GLOBALS['user'];
			$this->network 	= & $GLOBALS['network'];
			$this->page		= & $GLOBALS['page'];
			$this->db2		= & $GLOBALS['db2'];
		}
		
		public function setPrefix( $value )
		{
			$this->column_prefix = (!empty($value))? $value.'.' : '';
		}
		
		public function all($group_table = 'posts', $users_table = 'posts')
		{
			return $this->groups($group_table).' '.$this->users($users_table);
		}
		
		public function groups($table = 'groups')
		{
			if( $this->user->is_logged && $this->user->info->is_network_admin ){
				return '';
			}
				
			$filter	= array();
			if( $this->user->is_logged ){
				$filter = array_diff( $this->network->get_private_groups_ids(), $this->user->get_my_private_groups_ids() );
			}else{
				$filter = $this->network->get_private_groups_ids();
			}
			$tmp_id = ($table == 'groups')? 'id' : 'group_id';
			$filter	= count($filter)>0 ? ('AND '.$this->column_prefix.($table=='groups'? 'id' : $tmp_id).' NOT IN('.implode(', ', $filter).')') : '';
				
			return $filter;
		}
		
		public function users($table = 'posts')
		{
			if( $this->user->is_logged && $this->user->info->is_network_admin ){
				return '';
			}
				
			$filter = array();
			if( $this->user->is_logged ){
				$filter = array_diff( $this->network->get_post_protected_user_ids(), $this->user->get_my_post_protected_follower_ids() );
			}else{
				$filter = $this->network->get_post_protected_user_ids();
			}
			$tmp_id = $this->column_prefix. (($table == 'users')? 'id' : 'user_id');
			$filter = count($filter)>0 ? (' AND ('.($table=='posts'? $this->column_prefix.'group_id>0 OR '.$this->column_prefix.'id' : $tmp_id).' NOT IN('.implode(', ', $filter).'))') : '';
		
			return $filter;
		}
	}
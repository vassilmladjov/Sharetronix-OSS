<?php
	class communityAdministration
	{
		private $user;
		private $db2;
		private $network;
		
		public function __construct()
		{
			$this->user 	= & $GLOBALS['user'];
			$this->db2 		= & $GLOBALS['db2'];
		}
		
		public function removeAdministrator( $user_id )
		{
			if( !$this->user->is_logged || !$this->user->info->is_network_admin ){
				return FALSE;
			}
			
			if( $this->user->id === $user_id ){
				return FALSE;
			}
			
			$r	= $this->db2->fetch_field('SELECT 1 FROM users WHERE active=1 AND is_network_admin=1 AND id="'.intval($user_id).'" LIMIT 1');
			if( ! $r ){
				return FALSE;
			}
			
			$r	= $this->db2->query('UPDATE users SET is_network_admin=0 WHERE active=1 AND id="'.intval($user_id).'" LIMIT 1');
			if( !$this->db2->affected_rows($r) ){
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function suspendUser( $user_id )
		{
			if( !$this->user->is_logged || !$this->user->info->is_network_admin ){
				return FALSE;
			}
				
			if( $this->user->id === $user_id ){
				return FALSE;
			}
				
			$r	= $this->db2->fetch_field('SELECT 1 FROM users WHERE active=1 AND id="'.intval($user_id).'" LIMIT 1');
			if( ! $r ){
				return FALSE;
			}
				
			$r	= $this->db2->query('UPDATE users SET is_network_admin=0, active=0 WHERE active=1 AND id="'.intval($user_id).'" LIMIT 1');
			if( !$this->db2->affected_rows($r) ){
				return FALSE;
			}
				
			return TRUE;
		}
		
		public function activateUser( $user_id )
		{
			if( !$this->user->is_logged || !$this->user->info->is_network_admin ){
				return FALSE;
			}
		
			if( $this->user->id === $user_id ){
				return FALSE;
			}
		
			$r	= $this->db2->fetch_field('SELECT 1 FROM users WHERE active=0 AND id="'.intval($user_id).'" LIMIT 1');
			if( ! $r ){
				return FALSE;
			}
		
			$r	= $this->db2->query('UPDATE users SET is_network_admin=0, active=1 WHERE active=0 AND id="'.intval($user_id).'" LIMIT 1');
			if( !$this->db2->affected_rows($r) ){
				return FALSE;
			}
		
			return TRUE;
		}
		
		public function deleteUser( $user_id, $user_pass )
		{
			global $network, $C;
			
			if( !$this->user->is_logged ){
				return FALSE;
			}
			
			$this->db2->query('SELECT 1 FROM users WHERE id="'.intval($user_id).'" AND is_network_admin=1 LIMIT 1');
			$is_network_admin = ( 0 == $this->db2->num_rows() )? FALSE : TRUE;
			
			if( !$this->user->checkPassByUserId($user_pass) ){
				return FALSE;
			}
			
			if( $is_network_admin ){
				$num_admins = $this->db2->fetch_field('SELECT COUNT(*) FROM users WHERE is_network_admin=1 AND active=1');
				if( $num_admins <= 1 ){
					return FALSE;
				}
			}
			
			if( $is_network_admin && $num_admins < 2 && $this->user->id == $user_id ){
				return FALSE;
			}

			$u = $network->get_user_by_id($user_id);
			
			$this->db2->query('DELETE FROM groups_admins WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM groups_private_members WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM post_favs WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM post_userbox WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM searches WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM users_dashboard_tabs WHERE user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM users_details WHERE user_id="'.$user_id.'" LIMIT 1');
			$this->db2->query('DELETE FROM users_invitations WHERE user_id="'.$user_id.'" OR recp_user_id="'.$user_id.'"');
			$this->db2->query('DELETE FROM users_notif_rules WHERE user_id="'.$user_id.'" LIMIT 1');
			$this->db2->query('DELETE FROM users WHERE id="'.$user_id.'" LIMIT 1');
			$this->db2->query('DELETE FROM notifications WHERE from_user_id="'.$this->user->id.'"');
			$this->db2->query('DELETE FROM group_notifications WHERE from_user_id="'.$this->user->id.'"');
				
			$this->db2->query('DELETE FROM users_followed WHERE whom="'.$user_id.'" ');
			$res	= $this->db2->query('SELECT id, whom FROM users_followed WHERE who="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM users_followed WHERE id="'.$tmp->id.'" ');
				$this->db2->query('UPDATE users SET num_followers=num_followers-1 WHERE id="'.$tmp->whom.'" ');
			}
				
			$res	= $this->db2->query('SELECT gf.group_id FROM groups_followed AS gf LEFT JOIN groups AS g ON gf.group_id = g.id WHERE g.num_followers <=1 and gf.user_id = "'.$user_id.'"');
			while($tmp = $this->db2->fetch_object($res)) {
				$g = $this->network->get_group_by_id($tmp->group_id);
				$group = new group($g);
				$group->delete(TRUE, FALSE);
			}
				
			$res	= $this->db2->query('SELECT id, group_id FROM groups_followed WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM groups_followed WHERE id="'.$tmp->id.'" ');
				$this->db2->query('UPDATE groups SET num_followers=num_followers-1 WHERE id="'.$tmp->group_id.'" ');
			}
				
			$res	= $this->db2->query('SELECT * FROM posts WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$tmpp	= new post('public', FALSE, $tmp);
				$tmpp->delete_this_post();
			}
			$res	= $this->db2->query('SELECT * FROM posts_pr WHERE user_id="'.$user_id.'" OR to_user="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$tmpp	= new post('private', FALSE, $tmp);
				$tmpp->delete_this_post();
			}
				
			$this->db2->query('DELETE FROM posts_comments_watch WHERE user_id="'.$user_id.'" ');
			$res	= $this->db2->query('SELECT id, post_id FROM posts_comments WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM posts_comments WHERE id="'.$tmp->id.'" LIMIT 1');
				$this->db2->query('DELETE FROM posts_comments_mentioned WHERE comment_id="'.$tmp->id.'" ');
				$this->db2->query('UPDATE posts SET comments=comments-1 WHERE id="'.$tmp->post_id.'" LIMIT 1');
			}
			$res	= $this->db2->query('SELECT id, post_id FROM posts_mentioned WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM posts_mentioned WHERE id="'.$tmp->id.'" LIMIT 1');
				$this->db2->query('UPDATE posts SET mentioned=mentioned-1 WHERE id="'.$tmp->post_id.'" LIMIT 1');
			}
			$res	= $this->db2->query('SELECT id, post_id FROM posts_pr_mentioned WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM posts_pr_mentioned WHERE id="'.$tmp->id.'" LIMIT 1');
				$this->db2->query('UPDATE posts_pr SET mentioned=mentioned-1 WHERE id="'.$tmp->post_id.'" LIMIT 1');
			}
			$res	= $this->db2->query('SELECT id, comment_id FROM posts_comments_mentioned WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM posts_comments_mentioned WHERE id="'.$tmp->id.'" LIMIT 1');
				$this->db2->query('UPDATE posts_comments SET mentioned=mentioned-1 WHERE id="'.$tmp->comment_id.'" LIMIT 1');
			}
			$res	= $this->db2->query('SELECT id, comment_id FROM posts_pr_comments_mentioned WHERE user_id="'.$user_id.'" ');
			while($tmp = $this->db2->fetch_object($res)) {
				$this->db2->query('DELETE FROM posts_pr_comments_mentioned WHERE id="'.$tmp->id.'" LIMIT 1');
				$this->db2->query('UPDATE posts_pr_comments SET mentioned=mentioned-1 WHERE id="'.$tmp->comment_id.'" LIMIT 1');
			}
			
			if( $u->avatar != $C->DEF_AVATAR_USER ) {
				rm( $C->STORAGE_DIR.'avatars/'.$u->avatar );
				rm( $C->STORAGE_DIR.'avatars/thumbs1/'.$u->avatar );
				rm( $C->STORAGE_DIR.'avatars/thumbs2/'.$u->avatar );
				rm( $C->STORAGE_DIR.'avatars/thumbs3/'.$u->avatar );
				rm( $C->STORAGE_DIR.'avatars/thumbs4/'.$u->avatar );
				rm( $C->STORAGE_DIR.'avatars/thumbs5/'.$u->avatar );
			}
			
			$network->get_user_by_id($user_id, TRUE);
			$network->get_user_by_username($u->username, TRUE);
			
			return TRUE;
		}
		
	}
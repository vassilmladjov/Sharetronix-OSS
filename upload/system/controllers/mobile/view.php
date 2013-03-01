<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/view.php');
	$this->load_langfile('inside/user.php');
	$this->load_langfile('inside/dashboard.php');
	
	$post_type	= '';
	$post_id	= '';
	if( $this->param('post') ) {
		$post_type	= 'public';
		$post_id	= intval($this->param('post'));
	}
	elseif( $this->param('priv') ) {
		$post_type	= 'private';
		$post_id	= intval($this->param('priv'));
	}
	else {
		$this->redirect('dashboard');
	}
	
	$D->post	= new post($post_type, $post_id);
	if($D->post->error || $D->post->is_system_post) {
		$this->redirect('dashboard');
	}
	
	if( $D->post->post_type == 'private' && ($D->post->post_user->id != $this->user->id && $D->post->post_to_user->id != $this->user->id)){
		$this->redirect('dashboard');
	}
	if($D->post->post_group && $D->post->post_group->is_private ){
		if( !$this->user->is_logged ){
			$this->redirect('home');
		}
		if( !$this->user->if_follow_group($D->post->post_group->id) ){
			$this->redirect('dashboard');
		}
	}
	
	$D->i_am_network_admin	= ( $this->user->is_logged && $this->user->info->is_network_admin );
	$D->post_is_mine 	= $D->post->post_user->id == $this->user->id;
	$he_follows 	= $this->network->get_user_follows($D->post->post_user->id, FALSE, 'hefollows')->follow_users;
	
	if( $this->user->is_logged ){
		$i_follow 	= ( !$D->post_is_mine )? $this->network->get_user_follows($this->user->id, FALSE, 'hefollows')->follow_users : $he_follows;
	}else{
		$i_follow 	= array();
	}
	
	if( !$D->post_is_mine ){
		$D->he_follows_me 	= $this->user->is_logged ? $this->user->if_user_follows_me($D->post->post_user->id) : FALSE;
		$D->post_is_protected 	= !$D->post->post_group && $D->post->post_user->is_posts_protected && !$D->he_follows_me && !$D->i_am_network_admin;
	}else{
		$D->he_follows_me 	= TRUE;
		$D->post_is_protected	= FALSE;
	}
	
	if( !$D->post_is_mine && $D->post_is_protected && !$D->he_follows_me && !$D->i_am_network_admin){
		$this->redirect('dashboard');
	}
	
	$header_buttons = '<a href="'.$C->SITE_URL.'" class="btn home"><span></span></a>';
	$tpl = new template( array(
			'page_title' => 'View Post',
			'header_title' => 'View Post',
			'header_buttons' => $header_buttons
		)
	);

	$tpl->useStaticHTML();
	$tpl->staticHTML->useActivityContainer();
	
	$menu = array( 	array('url' => $D->post->post_user->username.'/tab:updates',  	'title' => $this->lang('usr_tab_updates') ),
					array('url' => $D->post->post_user->username.'/tab:info', 				'title' => $this->lang('usr_tab_info') ),
					array('url' => $D->post->post_user->username.'/tab:friends', 			'title' => $this->lang('usr_tab_coleagues') ),
					array('url' => $D->post->post_user->username.'/tab:groups', 			'title' => $this->lang('usr_tab_groups') )
	);
	
	$tpl->layout->setVar( 'subheader_placeholder', $tpl->designer->createMenu( 'navigation', $menu, 'user_navigation_top_menu' ) ); unset($menu);
	
	$tpl->layout->useBlock( 'comment-editor' );
	
	$tpl->layout->block->save('comment_editor');
	
	
	$tpl->initRoutine('SingleActivity', array( &$D->post, FALSE ));
	$tpl->routine->load();
	
	$tpl->display();
?>
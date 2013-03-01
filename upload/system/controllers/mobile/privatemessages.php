<?php
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if(!$this->user->is_logged){
		$this->redirect('signin');
	}

	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/dashboard.php');
	
	$this->network->reset_dashboard_tabstate($this->user->id, $this->param('tab')? $this->param('tab') : 'private');
	
	//TEMPLATE CODE START
	$header_buttons = '<a href="#" class="btn menu"><span><span class="notifications"></span></span></a>';
	
	$tpl = new template( array(
			'page_title' => $this->lang('dashboard_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 
			'header_title' => $this->lang('dbrd_poststitle_private'),
			'header_buttons' => $header_buttons
		) );
	
	

	$tpl->layout->useBlock('activity-container');
	$tpl->layout->block->save( 'main_content' );

	

	$activity = activityFactory::select('private');
	$activity->setTemplate( $tpl );
	$result = $activity->loadPosts();

	
	
	
	$tpl->display(); 
	//TEMPLATE CODE END
?>
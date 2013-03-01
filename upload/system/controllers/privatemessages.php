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
	$tpl = new template( array('page_title' => $this->lang('dashboard_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$tpl->useStaticHTML();
	$tpl->staticHTML->useActivityContainer();

	$activity = activityFactory::select('private');
	$activity->setTemplate( $tpl );
	$result = $activity->loadPosts();

	if( isset($result[1]) && $result[1] > 0 ){
		$tpl->layout->useBlock('activity-show-more');
		$tpl->layout->setVar('activities_pager_value', htmlentities('{"activities_type":"private","activities_id":"'.$result[1].'"}'));
		$tpl->layout->block->save('activity_container_show_more');
	}
	
	if( $this->param('tab') !== 'group' && isset($result[0]) && $result[0] > 0 ){
		$table = new tableCreator();
		$tpl->layout->setVar('main_content_bottom', 
													$table->hiddenField( 'activities_type', 'private' ) .
													$table->hiddenField( 'last_activity', intval($result[0]) ) .
													$table->hiddenField( 'activities_tab', $this->param('tab')? $this->param('tab') : 'all' )
		);
	}
	
	$tpl->display(); 
	//TEMPLATE CODE END
?>
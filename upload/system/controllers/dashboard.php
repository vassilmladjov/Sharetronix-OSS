<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if(!$this->user->is_logged){
		$this->redirect('signin');
	}

	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/dashboard.php');
	
	$this->network->reset_dashboard_tabstate($this->user->id, $this->param('tab')? $this->param('tab') : 'all');
	
	//TEMPLATE CODE START
	$tpl = new template( array('page_title' => $this->lang('dashboard_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('DashboardLeftMenu', array());
	$tpl->routine->load();
	
	if(isset($this->params->g)){
		$g	= $this->network->get_group_by_name($this->params->g);
		
		if( ! $g ) {
			$this->redirect('dashboard');
		}
		if( $g->is_private && !$this->user->is_logged ) {
			$this->redirect('home');
		}
		if( $g->is_private && !$this->user->info->is_network_admin ) {
			$u	= $this->network->get_group_invited_members($g->id);
			if( !$u || !in_array(intval($this->user->id),$u) ) {
				$this->redirect('dashboard');
			}
		}
		
		$this->params->group = $g->id;
	}
	
	$tpl->initRoutine('Postform', array());
	$tpl->routine->load();

	$activity = activityFactory::select('dashboard');
	$activity->setTemplate( $tpl );
	$result = $activity->loadPosts();

	if( isset($result[1]) && $result[1] > 0 ){
		$tpl->layout->useBlock('activity-show-more');
		$tpl->layout->setVar('activities_pager_value', htmlentities('{"activities_type":"dashboard","activities_id":"'.$result[1].'","activities_tab":"'.($this->param('tab')? $this->param('tab') : 'all').'"}'));
		$tpl->layout->block->save('activity_container_show_more');
	}
	
	if( $this->param('tab') !== 'group' && isset($result[0]) && $result[0] > 0 ){
		$table = new tableCreator();
		$tpl->layout->setVar('main_content_bottom', 
													$table->hiddenField( 'activities_type', 'dashboard' ) .
													$table->hiddenField( 'last_activity', intval($result[0]) ) .
													$table->hiddenField( 'activities_tab', $this->param('tab')? $this->param('tab') : 'all' )
		);
	} elseif( isset($g) && $g ) {
		$tpl->layout->setVar('in_group', 'in '.$g->title);
	}
	
	$tpl->display(); 
	//TEMPLATE CODE END
?>
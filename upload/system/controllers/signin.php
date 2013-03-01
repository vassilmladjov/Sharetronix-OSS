<?php
	
	if( $this->network->id && $this->user->is_logged ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('outside/global.php');
	$this->load_langfile('outside/signin.php');
	
	$D->submit	= FALSE;
	$D->error	= FALSE;
	$D->errmsg	= '';
	$D->email		= '';
	$D->password	= '';
	$D->rememberme	= FALSE;
	
	if( isset($_POST['email'], $_POST['password']) ) { 
		global $plugins_manager;
		$plugins_manager->onPageSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		$D->submit	= TRUE;
		$D->email		= trim($_POST['email']);
		$D->password	= trim($_POST['password']);
		$D->rememberme	= isset($_POST['rememberme']) && $_POST['rememberme']==1;
		if( empty($D->email) || empty($D->password) ) {
			$D->error	= TRUE;
			$D->errmsg	= $this->lang('signin_form_errmsg');
		}
		else {
			if( $this->user->is_logged ) {
				$this->user->logout();
			}
			$res	= $this->user->login($D->email, md5($D->password), $D->rememberme);
			if( ! $res ) {
				$D->error	= TRUE;
				if( $this->network->id ) {
					$db2->query('SELECT id FROM users WHERE (email="'.$db2->e($D->email).'" OR username="'.$db2->e($D->email).'") AND password="'.$db2->e(md5($D->password)).'" AND active=0 LIMIT 1');
					if( $db2->num_rows() > 0 ) {
						$D->errmsg	= $this->lang('signin_form_errmsgsusp');
					}
				}
				if( empty($D->errmsg) ) {
					$D->errmsg	= $this->lang('signin_form_errmsg');
				}
			}
			else {

				$this->redirect($C->SITE_URL.'dashboard');
				exit;
			}
		}
	}

	
	$tpl = new template( array('page_title' => $this->lang('signin_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	if( $this->param('pass') == 'changed' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signinforg_alldone_ttl'), $this->lang('signinforg_alldone_txt') ) );
	}
	
	
	if( $D->submit && $D->error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('signinforg_err'), $D->errmsg ) );
	}
	
	$tpl->layout->useBlock('login');
	//$tpl->layout->block->setVar('comments_thread_id', $val);
	$tpl->layout->block->save( 'main_content');
	
	
	$tpl->display();
?>
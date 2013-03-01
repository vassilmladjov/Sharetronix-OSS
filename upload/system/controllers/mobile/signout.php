<?php
	if( $this->user->is_logged ){
		$this->user->logout();
		$this->redirect('home');
	}
	
	$this->redirect('home');
<?php
	$pm 	= & $GLOBALS['plugins_manager'];
	
	$ajax = new services();
	
	if( $this->param('filter') ){
		$ajax->filter( explode( ',', urldecode( $this->param('filter') ) ) );
	}
	
	echo $ajax->load();
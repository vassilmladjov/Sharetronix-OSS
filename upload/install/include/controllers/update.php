<?php

	$path	= $C->INCPATH.'../';
	$D->error = FALSE;
	$D->errmsg = '';
	$D->errtype = FALSE;
	$perms	= array();
	
	//check directories
	$D->files = get_directories_for_update( $_SESSION['INSTALL_DATA']['OLD_VERSION'] );
	if( $D->files === FALSE ){
		$D->error = TRUE;
		$D->errmsg = 'Could not find table describing the files needed to be updated.';
	}
	
	if( !$D->error ){
		@clearstatcache();
		foreach($D->files as $i=>$fl) {
			$curr_error	= FALSE;
			if( !is_readable($path.$fl) || !is_writable($path.$fl) ) {
				@chmod($path.$fl, 0777);
				@clearstatcache();
				if( !is_readable($path.$fl) || !is_writable($path.$fl) ) {
					$D->error	= TRUE;
					$curr_error	= TRUE;
				}
			}
			if( ! $curr_error ) {
				unset($perms[$fl]);
				unset($D->files[$i]);
			}
		}
		if( $D->error ){
			$D->errtype = 1;
		}
	}
	
	//install database changes
	if( !$D->error ){
		$res	= update_database( $_SESSION['INSTALL_DATA']['OLD_VERSION'] );
		if( ! $res ) {
			$D->error	= TRUE;
			$D->errmsg	= '{Error occured while we tried to update your community database}';
			$D->errtype = 2;
		}
	}
	
	loadTemplate('update');
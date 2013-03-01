<?php

	$path	= $C->INCPATH.'../';
	$D->errmsg	= FALSE;
	
	$D->files	= get_directories_for_install();
	if( !$D->files ){
		$D->error	= TRUE;
		$D->errmsg	= $C->INCLUDEPATH.'descriptions/directories-install.php file was not found. Please, check if it exists and there is content in it.';
	}
	
	$perms	= array();
	$D->error	= FALSE;
	
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
	
	if( ! $D->error ) {
		$_SESSION['INSTALL_STEP']	= 4;
		header('Location: ?next&r='.rand(0,99999));
	}
	
	loadTemplate('step_3');
?>
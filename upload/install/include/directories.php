<?php
	
	function get_directories_for_install()
	{
		global $C;
		
		require_once $C->INCLUDEPATH.'descriptions/directories-install.php';
		
		if( !isset($folders) ){
			return FALSE;
		}
		
		return $folders;
	}
	
	function get_directories_for_update( $current_version )
	{
		global $C;
	
		require_once $C->INCLUDEPATH.'descriptions/directories-update.php';
	
		if( !isset($folders) ){
			return FALSE;
		}
		
		$tmp = array();
		
		foreach( $folders as $version => $dirs ){ 
			if( $version > $current_version ){
				foreach( $dirs as $dir ){
					$tmp[] = $dir;
				}
			}	
		}

		return $tmp;
	}
	
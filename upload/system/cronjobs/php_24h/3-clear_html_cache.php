<?php

	invalidateCachedHTML();
	
	if( is_dir($C->INCPATH.'tmp/') ){
		if ($handle = opendir( $C->INCPATH.'tmp/' )) {
			while (FALSE !== ($entry = readdir($handle))) {
				if( $entry !== '.' && $entry !== '..' ){
					unlink ( $entry );
				}
			}
			closedir($handle);
		}
	}
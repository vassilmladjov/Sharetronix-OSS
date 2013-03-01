<?php
	function directory_tree_delete($node)
	{
		$node	= realpath($node);
		if( ! $node ) {
			return;
		}
		if( ! is_dir($node) ) {
			@unlink($node);
			return;
		}
		$dir	= opendir($node);
		while($file = readdir($dir)) {
			if( $file == '.' || $file == '..' ) {
				continue;
			}
			directory_tree_delete($node.'/'.$file);
		}
		closedir($dir);
		@rmdir($node);
		return;
	}
	
	function rcopy($source, $destination) {
		if(!is_dir($destination) ) {
			mkdir($destination, true);
		}
	
		if(is_dir($source)) {
			$files = scandir($source);
			foreach($files as $file) {
				if(!in_array($file, array('.', '..'))) {
					if(is_file($source . DIRECTORY_SEPARATOR . $file)) {
						copy($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
					} else {
						if(!is_dir($destination . DIRECTORY_SEPARATOR . $file))
							mkdir($destination . DIRECTORY_SEPARATOR . $file);
							
						rcopy($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
					}
				}
			}
	
		}
	
	}
	
	function rrmdir($dir) {
		foreach(glob($dir . '/*') as $file) {
			if(is_dir($file)) {
				rrmdir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}
<?php 
	function encode_rfc3986($input) 
	{
	    return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(utf8_encode($input))));
	}
	
	function normalize_oauth_params(&$request, $to_header=FALSE)
	{
		$buffer = array();
		ksort($request);	
		
		foreach($request as $k=>$v){
			$buffer[] = (!$to_header)? ($k.'='.encode_rfc3986($v)):($k.'="'.encode_rfc3986($v).'"');
		}
			
		return (!$to_header)? implode('&', $buffer): 'Authorization: OAuth '.implode(',', $buffer);
	}
	function generate_group_info_obj($o, $fullInfo = FALSE)
	{
		$gObj = new stdClass;
		$gObj->id 			= $o->id;
		$gObj->groupname 		= $o->groupname;
		$gObj->title 		= $o->title;
		$gObj->avatar 		= ( empty($o->avatar) )? $GLOBALS['C']->DEF_AVATAR_GROUP : $o->avatar;
		$gObj->about_me 		= $o->about_me;
		$gObj->is_public 		= $o->is_public==1;
		$gObj->is_private 	= !$gObj->is_public;
		$gObj->num_posts 		= $o->num_posts;
		$gObj->num_followers 	= $o->num_followers;
		
		return $gObj;
	}
	function generate_user_info_obj($o, $fullInfo = FALSE)
	{
		$uObj = new stdClass;
		$uObj->id			= intval($o->id);	
		$uObj->num_posts		= intval($o->num_posts);
		$uObj->num_followers	= intval($o->num_followers);
		$uObj->fullname		= stripslashes($o->fullname);
		$uObj->email		= stripslashes($o->email);
		$uObj->username		= stripslashes($o->username);
		$uObj->avatar		= (empty($o->avatar))?  $GLOBALS['C']->DEF_AVATAR_USER : $o->avatar;
			
		if($fullInfo){
			$uObj->active		= intval($o->active);
			$uObj->position		= stripslashes($o->position);
			$uObj->about_me		= stripslashes($o->about_me);
			$uObj->tags			= trim(stripslashes($o->tags));
			$uObj->tags			= empty($o->tags) ? array() : explode(', ', $o->tags);
			
			$uObj->age	= '';
			$bd_day	= intval( substr($o->birthdate, 8, 2) );
			$bd_month	= intval( substr($o->birthdate, 5, 2) );
			$bd_year	= intval( substr($o->birthdate, 0, 4) );
			if( $bd_day>0 && $bd_month>0 && $bd_year>0 ) {
				if( date('Y') > $bd_year ) {
					$o->age	= date('Y') - $bd_year;
					if( $bd_month>date('m') || ($bd_month==date('m') && $bd_day>date('d')) ) {
						$o->age	--;
					}
				}
			}
			$uObj->location	= stripslashes($o->location);
			$uObj->network_id	= 1;
			$uObj->user_details	= FALSE;
		}
		
		return $uObj;
	}
	function is_64bit() 
	{ 
		$int = intval("9223372036854775807"); 
		return $int == 9223372036854775807;
	}
	function detectUploadedFileType( $file_type )
	{
		$type = 'file';
		
		switch( $file_type ){
			case 'image/gif':
			case 'image/jpg':
			case 'image/jpeg':
			//case 'image/bmp':
			case 'image/png':
							$type = 'image';
				break;
			
			case 'application/pdf':
							$type = 'acrobat';
				break;
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
							$type = 'word';
				break;
			case 'application/vnd.ms-excel':
							$type = 'excell';
				break;
		}
		
		return $type;
	}
	
	function checkCronjobDirectory( &$installed_plugins, $cornjob_name )
	{
		global $C;
	
		foreach( $installed_plugins as $plugin ){
			$plugin_cronjobs_dir = $C->PLUGINS_DIR.$plugin.'/system/cronjobs'.$cornjob_name.'/';
			
			if( is_dir($plugin_cronjobs_dir) ){ 
	
				$dir	= opendir($plugin_cronjobs_dir);
				$fls	= array();
				while( $file = readdir($dir) ) {
					$fls[]	= $file;
				}
				sort($fls);
				foreach($fls as $file) {
					$current_file	= $plugin_cronjobs_dir .  $file; 
					$tmp	= pathinfo($current_file);
					if( 'php' != $tmp['extension'] ) {
						continue;
					}
	
					echo "FILE: ".$tmp['basename']."\n\n";
	
					include( $current_file );
				}
			}
		}
	}
?>
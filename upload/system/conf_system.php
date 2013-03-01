<?php

	/**
	 * SHARETRONIX OPENSOURCE
	 * 
	 * @author	Vassil Maldjov
	 * @author	Ivaylo Enev
	 * @author	Nick Dimitrov
	 * @author	Veselin Hadjiev
	 * @author	Georgi Yanev
	 * @author	Nikola Pavlov
	 * @author	Petar Iliev
	 * @author	Sofiya Dimitrov
	 * @contact	support@sharetronix.com
	 * @license	LICENSE.TXT
	 */

	$C	= new stdClass;
	$C->INCPATH		= dirname(__FILE__).'/';
	
	$C->PROJPATH 	= defined('PROJPATH')? PROJPATH : (dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' .DIRECTORY_SEPARATOR );
	//$C->MARKETPLACE_URL = "http://developer.sharetronix.com/";
	$C->MARKETPLACE_URL = "http://192.168.1.131/stx_website/";
	
	if( ! file_exists($C->INCPATH . 'conf_main.php') ) {
		exit;
	}
	require_once( $C->INCPATH . 'conf_main.php' );
	
	chdir( $C->INCPATH );
	
	$C->DEBUG_MODE		= in_array($_SERVER['REMOTE_ADDR'], $C->DEBUG_USERS);
	$C->DEBUG_CONSOLE  	= FALSE;
	if( $C->DEBUG_MODE ) {
		ini_set( 'error_reporting', E_ALL | E_STRICT	);
		ini_set( 'display_errors',			1	);
	}
	
	$C->DEF_SITE_URL		= $C->SITE_URL;
	$C->OUTSIDE_DOMAIN		= $C->DOMAIN;
	$C->OUTSIDE_SITE_URL	= $C->SITE_URL;
	$C->SITE_TITLE			= '';
	$C->OUTSIDE_SITE_TITLE	= '';
	
	$C->STORAGE_URL			= $C->OUTSIDE_SITE_URL.'storage/';
	$C->STORAGE_DIR			= $C->INCPATH.'../storage/';
	$C->STORAGE_TMP_URL		= $C->STORAGE_URL.'tmp/';
	$C->STORAGE_TMP_DIR		= $C->STORAGE_DIR.'tmp/';
	$C->STATIC_IMG_URL		= $C->SITE_URL.'static/images/';
	
	$C->STATIC_URL	= $C->OUTSIDE_SITE_URL.'static/';
	$C->PLUGINS_DIR	= $C->INCPATH.'../apps/';
	
	$C->API_ID		= 0;
	
	$C->DEF_AVATAR_USER	= '_noavatar_user.gif';
	$C->DEF_AVATAR_GROUP	= '_noavatar_group.gif';
	
	$C->AVATAR_SIZE		= 200;
	$C->AVATAR_SIZE1	= 50;
	$C->AVATAR_SIZE2	= 16;
	$C->AVATAR_SIZE3	= 30;
	$C->AVATAR_SIZE4 	= 100;
	$C->AVATAR_SIZE_MOBILE_WIDTH = 420;
	$C->AVATAR_SIZE_MOBILE_HEIGHT = 60;
	
	
	//$C->POST_MAX_SYMBOLS	= 160;
	
	$C->PAGING_NUM_USERS	= 10;
	$C->PAGING_NUM_POSTS	= 10;
	$C->PAGING_NUM_GROUPS	= 10;
	$C->PAGING_NUM_COMMENTS	= 5;
	$C->POST_LAST_COMMENTS	= 2;
	
	if( substr($_SERVER['HTTP_HOST'], 0, 2) == 'm.' ) {
		$C->API_ID		= 1;
	}
	elseif( $_SERVER['REQUEST_URI']=='/m' || substr($_SERVER['REQUEST_URI'], 0, 3)=='/m/' ) {
		$C->API_ID		= 1;
	}
	
	$C->ATTACH_VIDEO_THUMBSIZE	= 90;
	$C->ATTACH_IMAGE_THUMBSIZE	= 160;
	$C->ATTACH_IMAGE_MXWIDTH	= 600;
	$C->ATTACH_IMAGE_MXHEIGHT	= 500;
	
	$C->POST_ICONS	= array (
		':)'	=> 'icon_smile.gif',
		':('	=> 'icon_sad.gif',
		';)'	=> 'icon_wink.gif',
		':P'	=> 'icon_razz.gif',
		':Р'	=> 'icon_razz.gif',
		':D'	=> 'icon_biggrin.gif',
		';('	=> 'icon_cry.gif',
	);
	
	//$C->THEME	= 'default';
	$C->DEF_LANGUAGE	= $C->LANGUAGE;
	
	//$C->rate_limit_number = 150;
	$C->NOTIF_POSTS_HANDLE	= 'ignore'; //|'ignore'|'replace'|'quit'| - handling notification posts, 'ignore' doesn't check if notification post has 
							//already been posted. It could cause heavy performance leaks, if you use other options on larger communities.
	$C->show_sent_invites_in_todo	= FALSE; 	//(FALSE or TRUE) if you want your members to see sent invites in the to do block set the value TRUE,
								//otherwise set it to FALSE;
	//ADDITIONAL CACHE OPTIONS//
	//These options are in BETA version!!!
	$C->post_cache_is_activated	= FALSE;	//most of the post data will be cached - mentions, attached files, reshares
	$C->user_cache_is_activated	= FALSE;	//cache for user's saved searches, private groups, protected members
	$C->comment_cache_is_activated = FALSE;
	//ADDITIONAL CACHE OPTIONS//

	$C->MASS_MAILING = FALSE; //still beta
	$C->SENDMAIL_PATH = ''; //usually '/usr/sbin/sendmail'
							
	ini_set( 'magic_quotes_runtime',		0	);
	ini_set( 'session.name',			my_session_name($C->DOMAIN)	);
	ini_set( 'session.cache_expire',		300	);
	ini_set( 'session.cookie_lifetime',		0	);
	ini_set( 'session.cookie_path',		'/'	);
	ini_set( 'session.cookie_domain',		cookie_domain()	);
	ini_set( 'session.cookie_httponly',		1	);
	ini_set( 'session.use_only_cookies',	1	);
	ini_set( 'session.gc_maxlifetime',		10800	);
	ini_set( 'session.gc_probability',		1	);
	ini_set( 'session.gc_divisor',		1000	);
	ini_set( 'zlib.output_compression_level',	7	);
	ini_set( 'max_execution_time',		20	);
	
	if( ! function_exists('mb_internal_encoding') ) {
		require_once( $C->INCPATH.'helpers/func_mbstring.php' );
	}
	mb_internal_encoding('UTF-8');
	
	if( ! function_exists('json_encode') ) {
		require_once( $C->INCPATH.'helpers/func_json.php' );
	}
	if( ! function_exists('strptime') ) {
		require_once( $C->INCPATH.'helpers/func_strptime.php' );
	}
	
?>
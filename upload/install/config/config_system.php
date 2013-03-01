<?php
	session_start();
	
	$C = new stdClass;
	$D = new stdClass;
	
	$db 		= FALSE;
	$C->DEBUG_MODE	= TRUE;
	
	ini_set( 'error_reporting',			0	);
	ini_set( 'display_errors',			0	);
	ini_set( 'magic_quotes_runtime',		0	);
	ini_set( 'max_execution_time',		20*60	);
	
	if( $C->DEBUG_MODE ){
		ini_set( 'error_reporting', E_ALL | E_STRICT	);
		ini_set( 'display_errors',			1	);
	}
	
	if( function_exists('mb_internal_encoding') ) {
		mb_internal_encoding('UTF-8');
	}
	setlocale( LC_TIME,	'en_US.UTF-8' );
	if( function_exists('date_default_timezone_set') ) {
		date_default_timezone_set('America/New_York');
	}
	
	ignore_user_abort(TRUE);
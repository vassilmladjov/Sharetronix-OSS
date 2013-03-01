<?php

	$C->SITE_TITLE 	= 'Sharetronix';
	$C->VERSION 	= '3.1.1';
	$C->INCPATH		= dirname(__FILE__).'/../../system/'; //for the functions/classes in sharetronix OSS
	$C->INCLUDEPATH	= dirname(__FILE__).'/../include/'; //for the installer
	$C->MAX_STEPS	= 5;
	$C->DB_MYEXT 	= (function_exists('mysqli_connect'))? 'mysqli' : 'mysql';
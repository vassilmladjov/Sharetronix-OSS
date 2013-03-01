<?php
	$db_tables = array();
	
	$db_tables['applications'] = "CREATE TABLE `applications` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `detect` varchar(50) collate utf8_unicode_ci NOT NULL,
			  `total_posts` int(10) unsigned NOT NULL,
			  `app_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `consumer_key` varchar(1000) collate utf8_unicode_ci NOT NULL,
			  `consumer_secret` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `callback_url` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `avatar` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `description` text collate utf8_unicode_ci NOT NULL,
			  `app_website` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `organization` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `website` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `app_type` enum('','browser','client') collate utf8_unicode_ci NOT NULL,
			  `acc_type` enum('','r','rw') collate utf8_unicode_ci NOT NULL,
			  `use_for_login` tinyint(1) unsigned NOT NULL,
			  `reg_date` int(10) unsigned NOT NULL,
			  `reg_ip` bigint(10) unsigned NOT NULL,
			  `suspended` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `app_id` (`app_id`),
			  KEY `consumer_key` (`consumer_key`(333))
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	$db_tables['cache'] = "CREATE TABLE `cache` (
			  `key` varchar(32) NOT NULL,
			  `data` varchar(".(intval($_SESSION['INSTALL_DATA']['MYSQL_SERVER_VERSION'])>=503? 21810 : 255 ).") NOT NULL,
			  `expire` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`key`)
			) ENGINE=MEMORY DEFAULT CHARSET=utf8;";
	
	$db_tables['crons'] = "CREATE TABLE `crons` (
			  `cron` varchar(10) collate utf8_unicode_ci NOT NULL,
			  `last_run` int(10) unsigned NOT NULL,
			  `next_run` int(10) unsigned NOT NULL,
			  `is_running` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`cron`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	$db_tables['email_change_requests'] = "CREATE TABLE `email_change_requests` (
			  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			  `user_id` INT( 10 ) UNSIGNED NOT NULL ,
			  `new_email` VARCHAR( 100 ) NOT NULL ,
			  `confirm_key` VARCHAR( 32 ) NOT NULL ,
			  `confirm_valid` INT( 10 ) UNSIGNED NOT NULL ,
			  INDEX ( `user_id` , `confirm_key` )
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	
	$db_tables['groups'] = "CREATE TABLE `groups` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `groupname` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `avatar` varchar(200) collate utf8_unicode_ci NOT NULL,
			  `about_me` varchar(200) collate utf8_unicode_ci NOT NULL,
			  `is_public` tinyint(1) unsigned NOT NULL,
			  `num_posts` int(10) unsigned NOT NULL default '0',
			  `num_followers` int(10) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `groupname` (`groupname`),
			  KEY `is_public` (`is_public`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	$db_tables['groups_admins'] = "CREATE TABLE `groups_admins` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `group_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `group_id` (`group_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	$db_tables['groups_deleted'] = "CREATE TABLE `groups_deleted` (
			  `id` int(10) unsigned NOT NULL,
			  `groupname` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `is_public` tinyint(1) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `is_public` (`is_public`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['groups_followed'] = "CREATE TABLE `groups_followed` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `group_id` int(10) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  `group_from_postid` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `group_id` (`group_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['group_notifications'] = "CREATE TABLE  `group_notifications` (
			`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`notif_type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			`to_group_id` INT( 5 ) UNSIGNED NOT NULL ,
			`from_user_id` INT( 8 ) UNSIGNED NOT NULL ,
			`date` INT( 10 ) UNSIGNED NOT NULL
			) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

	$db_tables['groups_private_members'] = "CREATE TABLE `groups_private_members` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `group_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `invited_by` int(10) unsigned NOT NULL,
			  `invited_date` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `group_id` (`group_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['invitation_codes'] = "CREATE TABLE `invitation_codes` (
			  `code` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `user_id` int(10) NOT NULL,
			  PRIMARY KEY  (`code`),
			  KEY `network_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['ip_rates_limit'] = "CREATE TABLE `ip_rates_limit` (
			  `id` int(10) NOT NULL auto_increment,
			  `ip` bigint(12) NOT NULL,
			  `rate_limits` int(10) NOT NULL,
			  `rate_limits_date` int(10) NOT NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['notifications'] = "CREATE TABLE  `notifications` (
			`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`notif_type` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			`to_user_id` INT( 7 ) UNSIGNED NOT NULL ,
			`in_group_id` INT( 4 ) UNSIGNED NOT NULL ,
			`from_user_id` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			`notif_object_type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
			`notif_object_id` INT( 10 ) UNSIGNED NOT NULL,
			`date` INT( 10 ) UNSIGNED NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['oauth_access_token'] = "CREATE TABLE `oauth_access_token` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `app_id` int(10) unsigned NOT NULL,
			  `consumer_key` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `time_stamp` bigint(20) NOT NULL,
			  `version` varchar(10) collate utf8_unicode_ci NOT NULL,
			  `nonce` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `access_token` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `token_secret` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `user_verified` tinyint(1) NOT NULL,
			  `rate_limits` int(5) NOT NULL,
			  `rate_limits_date` int(5) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `consumer_key` (`consumer_key`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['oauth_log'] = "CREATE TABLE `oauth_log` (
			  `id` bigint(20) unsigned NOT NULL auto_increment,
			  `app_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `app_id` (`app_id`,`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['oauth_request_token'] = "CREATE TABLE `oauth_request_token` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `consumer_key` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `nonce` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `time_stamp` bigint(20) NOT NULL,
			  `version` varchar(10) collate utf8_unicode_ci NOT NULL,
			  `token_secret` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `request_token` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `verifier` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `user_id` bigint(20) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `request_token` (`request_token`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['plugins'] = "CREATE TABLE IF NOT EXISTS  `plugins` (
			`id` INT( 6 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			`is_installed` TINYINT( 1 ) UNSIGNED NOT NULL ,
			`date_installed` INT( 10 ) UNSIGNED NOT NULL ,
			`installed_by_user_id` INT( 10 ) UNSIGNED NOT NULL
			) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

	$db_tables['plugins_cache'] = "CREATE TABLE IF NOT EXISTS  `plugins_cache` (
			`id` INT( 6 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`plugin_name` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			`event_name` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			INDEX USING BTREE (  `plugin_name` ,  `event_name` )
			) ENGINE = MEMORY CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

	$db_tables['plugins_installed'] = "CREATE TABLE IF NOT EXISTS `plugins_installed` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(50) NOT NULL,
				`marketplace_id` int(11) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";

	$db_tables['plugins_tables'] = "CREATE TABLE IF NOT EXISTS `plugins_tables` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`table` varchar(255) NOT NULL,
				`owner` varchar(255) NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `table` (`table`)
			) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";

	$db_tables['languages'] = "CREATE TABLE IF NOT EXISTS `languages` (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`langkey` varchar(50) NOT NULL DEFAULT '0',
				`installed` int(10) DEFAULT NULL,
				`version` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `langkey` (`langkey`)
			) ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts'] = "CREATE TABLE `posts` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `api_id` smallint(5) unsigned NOT NULL default '0',
			  `user_id` int(10) unsigned NOT NULL,
			  `group_id` int(10) unsigned NOT NULL,
			  `message` varchar(1000) collate utf8_unicode_ci NOT NULL,
			  `mentioned` tinyint(2) unsigned NOT NULL default '0',
			  `attached` tinyint(1) unsigned NOT NULL default '0',
			  `posttags` tinyint(2) unsigned NOT NULL default '0',
			  `comments` smallint(4) unsigned NOT NULL default '0',
			  `reshares` smallint(5) unsigned NOT NULL default '0',
			  `likes` smallint(5) unsigned NOT NULL default '0',
			  `date` int(10) unsigned NOT NULL,
			  `date_lastedit` int(10) NOT NULL,
			  `date_lastcomment` int(10) NOT NULL,
			  `ip_addr` bigint(10) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `group_id` (`group_id`),
			  KEY `api_id` (`api_id`),
			  KEY `api_user_IDX` (`api_id`,`user_id`),
			  FULLTEXT KEY `message` (`message`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_attachments'] = "CREATE TABLE `posts_attachments` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `post_id` int(10) unsigned NOT NULL,
			  `type` enum('link','image','videoembed','videoupload','text','file') collate utf8_unicode_ci NOT NULL,
			  `data` text collate utf8_unicode_ci NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `type` (`type`),
			  KEY `post_type_IDX` (`post_id`,`type`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_comments'] = "CREATE TABLE `posts_comments` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `api_id` smallint(5) unsigned NOT NULL default '0',
			  `post_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `message` text collate utf8_unicode_ci NOT NULL,
			  `mentioned` tinyint(2) unsigned NOT NULL,
			  `likes` int(5) unsigned NOT NULL,
			  `posttags` tinyint(2) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  `ip_addr` bigint(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`),
			  FULLTEXT KEY `message` (`message`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_comments_mentioned'] = "CREATE TABLE `posts_comments_mentioned` (
			  `id` int(10) NOT NULL auto_increment,
			  `comment_id` int(10) NOT NULL,
			  `user_id` int(10) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `comment_id` (`comment_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_comments_watch'] = "CREATE TABLE `posts_comments_watch` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `post_id` int(10) unsigned NOT NULL,
			  `newcomments` smallint(5) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_mentioned'] = "CREATE TABLE `posts_mentioned` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `post_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr'] = "CREATE TABLE `posts_pr` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `api_id` smallint(5) unsigned NOT NULL default '0',
			  `user_id` int(10) unsigned NOT NULL,
			  `to_user` int(10) unsigned NOT NULL,
			  `message` varchar(1000) collate utf8_unicode_ci NOT NULL,
			  `mentioned` tinyint(2) unsigned NOT NULL default '0',
			  `attached` tinyint(1) unsigned NOT NULL default '0',
			  `posttags` tinyint(2) unsigned NOT NULL default '0',
			  `comments` smallint(4) unsigned NOT NULL default '0',
			  `date` int(10) unsigned NOT NULL,
			  `date_lastedit` int(10) NOT NULL,
			  `date_lastcomment` int(10) NOT NULL,
			  `ip_addr` bigint(10) NOT NULL,
			  `is_recp_del` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `to_user` (`to_user`),
			  KEY `is_recp_del` (`is_recp_del`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr_attachments'] = "CREATE TABLE `posts_pr_attachments` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `post_id` int(10) unsigned NOT NULL,
			  `type` enum('link','image','videoembed','videoupload','text','file') collate utf8_unicode_ci NOT NULL,
			  `data` text collate utf8_unicode_ci NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `type` (`type`),
			  KEY `post_type_IDX` (`post_id`,`type`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr_comments'] = "CREATE TABLE `posts_pr_comments` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `api_id` smallint(5) unsigned NOT NULL default '0',
			  `post_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `message` text collate utf8_unicode_ci NOT NULL,
			  `mentioned` tinyint(2) unsigned NOT NULL,
			  `posttags` tinyint(2) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  `ip_addr` bigint(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr_comments_mentioned'] = "CREATE TABLE `posts_pr_comments_mentioned` (
			  `id` int(10) NOT NULL auto_increment,
			  `comment_id` int(10) NOT NULL,
			  `user_id` int(10) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `comment_id` (`comment_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr_comments_watch'] = "CREATE TABLE `posts_pr_comments_watch` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `post_id` int(10) unsigned NOT NULL,
			  `newcomments` smallint(5) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['posts_pr_mentioned'] = "CREATE TABLE `posts_pr_mentioned` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `post_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['post_tags'] = "CREATE TABLE  `post_tags` (
				  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				  `tag_name` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
				  `user_id` INT( 10 ) UNSIGNED NOT NULL,
				  `group_id` INT( 10 ) UNSIGNED NOT NULL,
				  `post_id` INT( 10 ) UNSIGNED NOT NULL ,
				  `date` INT( 10 ) UNSIGNED NOT NULL,
				  PRIMARY KEY (  `id` ),
				  INDEX (tag_name)
				 ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci";

	$db_tables['post_favs'] = "CREATE TABLE `post_favs` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `post_type` enum('public','private') collate utf8_unicode_ci NOT NULL,
			  `post_id` int(10) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_type` (`post_type`,`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['post_userbox'] = "CREATE TABLE `post_userbox` (
  			  `id` bigint(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `post_id` int(10) unsigned NOT NULL,
  			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['pubsubhubbub_subscriptions'] = "CREATE TABLE `pubsubhubbub_subscriptions` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `feed_url` varchar(500) collate utf8_unicode_ci NOT NULL,
			  `status` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `last_status_date` int(10) unsigned NOT NULL,
			  `parse_needed` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `feed_url` (`feed_url`(333)),
			  KEY `status` (`status`),
 			  KEY `parse_needed` (`parse_needed`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['searches'] = "CREATE TABLE `searches` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `search_key` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `search_string` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `search_url` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `added_date` int(10) unsigned NOT NULL,
			  `total_hits` mediumint(5) unsigned NOT NULL default '0',
			  `last_results` mediumint(5) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`,`search_key`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['settings'] = "CREATE TABLE `settings` (
			  `word` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `value` text collate utf8_unicode_ci NOT NULL,
			  UNIQUE KEY `word` (`word`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['unconfirmed_registrations'] = "CREATE TABLE `unconfirmed_registrations` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `email` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `fullname` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `confirm_key` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `invited_code` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `email` (`email`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users'] = "CREATE TABLE `users` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `facebook_uid` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `twitter_uid` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `email` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `username` varchar(200) collate utf8_unicode_ci NOT NULL,
			  `password` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `fullname` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `avatar` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `about_me` text collate utf8_unicode_ci NOT NULL,
			  `tags` text collate utf8_unicode_ci NOT NULL,
			  `gender` enum('','m','f') collate utf8_unicode_ci NOT NULL,
			  `birthdate` date NOT NULL,
			  `position` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `location` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `language` varchar(5) collate utf8_unicode_ci NOT NULL,
			  `timezone` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `num_posts` int(10) unsigned NOT NULL,
			  `num_followers` int(10) unsigned NOT NULL,
			  `reg_date` int(10) unsigned NOT NULL,
			  `reg_ip` bigint(10) NOT NULL,
			  `lastlogin_date` int(10) unsigned NOT NULL,
			  `lastlogin_ip` bigint(10) NOT NULL,
			  `lastpost_date` int(10) unsigned NOT NULL,
			  `lastemail_date` int(10) unsigned NOT NULL,
			  `lastclick_date` int(10) unsigned NOT NULL,
			  `lastclick_date_newest_post` int(10) unsigned NOT NULL,
			  `pass_reset_key` varchar(32) collate utf8_unicode_ci NOT NULL,
			  `pass_reset_valid` int(10) unsigned NOT NULL,
			  `active` tinyint(1) unsigned NOT NULL default '1',
			  `is_network_admin` tinyint(1) unsigned NOT NULL default '0',
			  `is_posts_protected` tinyint(1) unsigned NOT NULL default '0',
			  `is_profile_protected` tinyint(1) unsigned NOT NULL default '0',
			  `is_dm_protected` tinyint(1) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `email` (`email`),
			  UNIQUE KEY `username` (`username`),
			  KEY `active` (`active`),
			  KEY `num_followers` (`num_followers`),
			  KEY `facebook_uid` (`facebook_uid`),
			  KEY `twitter_uid` (`twitter_uid`),
			  KEY `pass_reset_IDX` (`pass_reset_key`,`pass_reset_valid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users_dashboard_tabs'] = "CREATE TABLE `users_dashboard_tabs` (
			  `user_id` int(10) unsigned NOT NULL,
			  `tab` enum('','all','@me','private','commented','feeds','tweets','notifications') collate utf8_unicode_ci NOT NULL,
			  `state` tinyint(1) unsigned NOT NULL,
			  `newposts` smallint(4) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`user_id`,`tab`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users_details'] = "CREATE TABLE `users_details` (
			  `user_id` int(10) unsigned NOT NULL,
			  `website` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `work_phone` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `personal_phone` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `personal_email` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `im_skype` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `im_gtalk` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `prof_linkedin` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `prof_facebook` varchar(255) collate utf8_unicode_ci NOT NULL,
			  `prof_twitter` varchar(255) collate utf8_unicode_ci NOT NULL,
			  PRIMARY KEY  (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users_followed'] = "CREATE TABLE `users_followed` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `who` int(10) unsigned NOT NULL,
			  `whom` int(10) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  `whom_from_postid` int(10) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `who` (`who`),
			  KEY `whom` (`whom`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users_invitations'] = "CREATE TABLE `users_invitations` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `user_id` int(10) unsigned NOT NULL,
			  `date` int(10) unsigned NOT NULL,
			  `recp_name` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `recp_email` varchar(100) collate utf8_unicode_ci NOT NULL,
			  `recp_is_registered` tinyint(1) unsigned NOT NULL default '0',
			  `recp_user_id` int(10) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`,`recp_is_registered`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$db_tables['users_notif_rules'] = "CREATE TABLE `users_notif_rules` (
			  `user_id` int(10) unsigned NOT NULL,
			  `ntf_them_if_i_follow_usr` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_comment` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_edt_profl` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_edt_pictr` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_create_grp` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_join_grp` tinyint(1) unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_me_if_u_follows_me` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_follows_u2` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_commments_me` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_commments_m2` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_edt_profl` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_edt_pictr` tinyint(3) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_creates_grp` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_joins_grp` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_invit_me_grp` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_posts_qme` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_posts_prvmsg` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_registers` tinyint(1) unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_on_post_like` tinyint( 1 ) unsigned NOT NULL DEFAULT  '0' COMMENT  '0-off, 1-both, 2-post,  3-email',
			  `ntf_me_on_comment_like` tinyint( 1 ) unsigned NOT NULL DEFAULT  '0' COMMENT  '0-off, 1-both, 2-post,  3-email',
			  PRIMARY KEY  (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
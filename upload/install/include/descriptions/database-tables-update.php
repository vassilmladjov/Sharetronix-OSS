<?php
	$db_updates = array();
	
	$db_updates['2.0.0'] = array(
			'queries'=> array(
					'INSERT INTO `settings` (`word`, `value`) VALUES ("POST_TYPES_TO_AUTODELETE", "feed")',
					'INSERT INTO `settings` (`word`, `value`) VALUES ("POST_TYPES_DELETE_PERIOD", "14")',
					'ALTER TABLE  `users_details` ADD  `integr_twitter` VARCHAR( 255 ) NOT NULL',
					'ALTER TABLE  `users_details` ADD  `extrnlusr_twitter` VARCHAR( 255 ) NOT NULL',
					'ALTER TABLE  `users_details` ADD  `integr_facebook` VARCHAR( 255 ) NOT NULL', 
					'ALTER TABLE  `users_details` ADD  `extrnlusr_facebook` VARCHAR( 255 ) NOT NULL',
					'INSERT INTO applications(  `name` ,  `detect`, `total_posts` ) VALUES (\'<a href="http://twitter.com/" target="_blank">Twitter</a>\',  "twitter", 0)',
					'INSERT INTO `settings` (`word`, `value`) VALUES ("POST_FROM_TWITTER_TAG", "0")',
					'INSERT INTO `settings` (`word`, `value`) VALUES ("LAST_TWITTER_POST_ID", "0")',
					'ALTER TABLE  `posts` ADD  `likes` smallint(5) UNSIGNED NOT NULL DEFAULT  "0" AFTER  `reshares`',
					'ALTER TABLE  `users_notif_rules` ADD  `ntf_me_on_post_like` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  "0" COMMENT  "0-off, 3-email"',
			)
	);
	
	$db_updates['2.1.0'] = array(
			'tables' => array(
				'post_tags' => 'CREATE TABLE  `post_tags` (
				  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				  `tag_name` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
				  `user_id` INT( 10 ) UNSIGNED NOT NULL,
				  `group_id` INT( 10 ) UNSIGNED NOT NULL,
				  `post_id` INT( 10 ) UNSIGNED NOT NULL ,
				  `date` INT( 10 ) UNSIGNED NOT NULL,
				  PRIMARY KEY (  `id` ),
				  INDEX (tag_name)
				 ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			),
			'queries'=> array(
					'ALTER TABLE  `users` ADD INDEX (  `num_followers` )',
					'UPDATE applications SET detect="twitter" WHERE id=8 LIMIT 1'
			)
	);
	
	$db_updates['2.2.0'] = array(
			'tables' => array(
					'notifications' => 'CREATE TABLE  `notifications` (
							`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							`notif_type` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
							`to_user_id` INT( 7 ) UNSIGNED NOT NULL ,
							`in_group_id` INT( 4 ) UNSIGNED NOT NULL ,
							`from_user_id` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
							`notif_object_type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
							`notif_object_id` INT( 10 ) UNSIGNED NOT NULL,
							`date` INT( 10 ) UNSIGNED NOT NULL
					) ENGINE = MYISAM DEFAULT CHARSET=utf8',
					
					'group_notifications' => 'CREATE TABLE  `group_notifications` (
						`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`notif_type` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
						`to_group_id` INT( 5 ) UNSIGNED NOT NULL ,
						`from_user_id` INT( 8 ) UNSIGNED NOT NULL ,
						`date` INT( 10 ) UNSIGNED NOT NULL
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci',
					
			),
			'queries'=> array(
					'ALTER TABLE  `users_dashboard_tabs` CHANGE  `tab`  `tab` ENUM(  \'\',  \'all\',  \'@me\',  \'private\',  \'commented\',  \'feeds\',  \'tweets\',  \'notifications\' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL',
			)
	);
	
	$db_updates['2.2.1'] = array(
			'queries'=> array(
					'ALTER TABLE  `users` DROP  `used_storage`, `comments_expanded`,`dbrd_whattodo_closed`,`dbrd_groups_closed`,`js_animations`',
			)
	);
	
	$db_updates['3.0.0'] = array(
			'tables' => array(
					'plugins_tables' => 'CREATE TABLE IF NOT EXISTS `plugins_tables` (
									`id` int(11) NOT NULL AUTO_INCREMENT,
									`table` varchar(255) NOT NULL,
									`owner` varchar(255) NOT NULL,
									PRIMARY KEY (`id`),
									UNIQUE KEY `table` (`table`)
								) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;',
					
					'plugins_installed' => 'CREATE TABLE IF NOT EXISTS `plugins_installed` (
										`id` int(11) NOT NULL AUTO_INCREMENT,
										`name` varchar(50) NOT NULL,
										`marketplace_id` int(11) NOT NULL,
										PRIMARY KEY (`id`)
									) ENGINE=MYISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;',
					
					'languages' => "CREATE TABLE IF NOT EXISTS `languages` (
								  `id` int(10) NOT NULL AUTO_INCREMENT,
								  `langkey` varchar(50) NOT NULL DEFAULT '0',
								  `installed` int(10) DEFAULT NULL,
								  `version` int(11) DEFAULT NULL,
								  PRIMARY KEY (`id`),
								  UNIQUE KEY `langkey` (`langkey`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
					
					'plugins_cache' => 'CREATE TABLE IF NOT EXISTS  `plugins_cache` (
									`id` INT( 6 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
									`plugin_name` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
									`event_name` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
									INDEX USING BTREE (  `plugin_name` ,  `event_name` )
									) ENGINE = MEMORY CHARACTER SET utf8 COLLATE utf8_unicode_ci;',
					
					'plugins_installed' => 'CREATE TABLE IF NOT EXISTS `plugins_installed` (
										`id` int(11) NOT NULL AUTO_INCREMENT,
										`name` varchar(50) NOT NULL,
										`marketplace_id` int(11) NOT NULL,
										PRIMARY KEY (`id`)
									) ENGINE=MYISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;',
					
					'plugins' => 'CREATE TABLE IF NOT EXISTS `plugins` (
  									`id` int(6) unsigned NOT NULL AUTO_INCREMENT,
									  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
									  `is_installed` tinyint(1) unsigned NOT NULL,
									  `date_installed` int(10) unsigned NOT NULL,
									  `installed_by_user_id` int(10) unsigned NOT NULL,
									  PRIMARY KEY (`id`)
									) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;',
					
			),
			'queries'=> array(
					"INSERT INTO `languages` (`langkey`, `installed`, `version`) VALUES ( 'en', 1, 0)"	
			)
	);
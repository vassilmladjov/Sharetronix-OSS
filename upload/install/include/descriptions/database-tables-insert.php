<?php
	$db_insert['applications'] = array(
				"INSERT INTO `applications` (`id`, `name`, `total_posts`) VALUES
				(1, 'mobi', 0),
				(2, 'RSS', 0),
				(3, 'email', 0),
				(4, 'web', 0);",
				
				"UPDATE `applications` SET id=0 WHERE id=4 LIMIT 1;",
				
				"ALTER TABLE `applications` AUTO_INCREMENT=4;",
				
				"INSERT INTO `applications` (`id`, `name`, `total_posts`, `detect`) VALUES
				(4, 'api', 0, ''),
				(5, '<a href=\"http://www.tweetdeck.com/\" target=\"_blank\">tweetdeck</a>', 0, ''),
				(6, 'twitter', 0, ''),
				(7, '<a href=\"http://getspaz.com/\" target=\"_blank\">Spaz</a>', 0, 'spaz'),
				(8, '<a href=\"http://twitter.com/\" target=\"_blank\">Twitter</a>',  0, 'twitter');",
			);
	
	$db_insert['languages'] = array(
				"INSERT INTO `languages` (`langkey`, `installed`, `version`) VALUES ( 'en', 1, 0)",
			);
	
	$db_insert['settings'] = array(
				"INSERT INTO `settings` (`word`, `value`) VALUES
				('SITE_TITLE', '".$db->e($_SESSION['INSTALL_DATA']['SITE_TITLE'])."'),
				('POST_MAX_SYMBOLS', '160'),
				('LANGUAGE', '".$db->e($_SESSION['INSTALL_DATA']['LANGUAGE'])."'),
				('SYSTEM_EMAIL', '".$db->e($_SESSION['INSTALL_DATA']['ADMIN_EMAIL'])."'),
				('COMPANY', '".$db->e($_SESSION['INSTALL_DATA']['SITE_TITLE'])."'),
				('USERS_EMAIL_CONFIRMATION', '1'),
				('API_STATUS', '0'),
				('PROTECT_OUTSIDE_PAGES', '0'),
				('THEME', 'default'),
				('MOBI_DISABLED', '0'),
				('POST_TYPES_TO_AUTODELETE', 'feed'),
				('POST_TYPES_DELETE_PERIOD', '14'),
				('NAME_INDENTIFICATOR', '1')",
			);
	
	$db_insert['users'] = array(
				"INSERT INTO `users` SET
				id='1',
				username='".$db->e($_SESSION['INSTALL_DATA']['ADMIN_USER'])."',
				password='".$db->e(md5($_SESSION['INSTALL_DATA']['ADMIN_PASS']))."',
				email='".$db->e($_SESSION['INSTALL_DATA']['ADMIN_EMAIL'])."',
				fullname='".$db->e($_SESSION['INSTALL_DATA']['SITE_TITLE'])."',
				reg_date='".time()."',
				reg_ip='".ip2long($_SERVER['REMOTE_ADDR'])."',
				lastpost_date='".time()."',
				language='".$db->e($_SESSION['INSTALL_DATA']['LANGUAGE'])."',
				num_posts='1',
				num_followers='0',
				active='1',
				is_network_admin='1';",
			);
			
	$db_insert['posts'] = array(
				"INSERT INTO `posts` SET
				id='1',
				api_id='0',
				user_id='1',
				group_id='0',
				message='Welcome to ".$db->e($_SESSION['INSTALL_DATA']['SITE_TITLE'])." :)',
				mentioned=0,
				attached=0,
				posttags=0,
				comments=0,
				date='".time()."',
				date_lastedit='',
				date_lastcomment='".time()."',
				ip_addr='".ip2long($_SERVER['REMOTE_ADDR'])."';"
			);
			
	$db_insert['post_userbox'] = array(
				"INSERT INTO post_userbox SET user_id='1', post_id='1';",
			);
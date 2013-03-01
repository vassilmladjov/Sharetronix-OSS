<?php
/*	
	echo "Optimizing database ".$C->DB_NAME."... ";
	
	$db1->query('SHOW TABLES FROM '.$C->DB_NAME);
	while( $obj = $db1->fetch_object() ) {
		$tbl	= $obj->{'Tables_in_'.$C->DB_NAME};
		$db1->query('OPTIMIZE TABLE '.$tbl, FALSE);
		$db1->query('ANALYZE TABLE '.$tbl, FALSE);
	}
	
	$db1->query('TRUNCATE TABLE `users_rssfeeds_posts`'); // this table is useless
	
	echo "Done.\n";
*/	
?>
<?php
	$db1->query('DELETE FROM notifications WHERE date<"'.(time()-14*24*60*60).'" ');
?>
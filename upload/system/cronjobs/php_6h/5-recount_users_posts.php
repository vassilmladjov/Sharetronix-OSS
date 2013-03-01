<?php
	
	$private_groups	= array();
	$private_groups	= $network->get_private_groups_ids();
	$private_groups	= (count($private_groups)>0)? implode(', ', $private_groups) : '';
	
	$users_to_check = array();
	$db2->query('SELECT id FROM users WHERE num_posts>1000 LIMIT 10');
	while($obj = $db2->fetch_object()) {
		$users_to_check[]	= $obj->id;
	}
	
	foreach($users_to_check as $usr){
		$db2->query('SELECT COUNT(*) AS nm FROM posts WHERE user_id="'.$usr.'" AND group_id NOT IN('.$private_groups.') GROUP BY user_id');
		if($tmp = $db2->fetch_object()) {
			$db2->query('UPDATE users SET num_posts="'.$tmp->nm.'" WHERE id="'.$usr.'" LIMIT 1');
		}	
	}
	
	$groups_to_check = array();
	$db2->query('SELECT id FROM groups WHERE num_posts>1000 LIMIT 10');
	while($obj = $db2->fetch_object()) {
		$groups_to_check[]	= $obj->id;
	}
	
	foreach($groups_to_check as $grp){
		$db2->query('SELECT COUNT(*) AS nm FROM posts WHERE group_id="'.$grp.'" GROUP BY group_id');
		if($tmp = $db2->fetch_object()) {
			$db2->query('UPDATE groups SET num_posts="'.$tmp->nm.'" WHERE id="'.$grp.'" LIMIT 1');
		}	
	}

?>
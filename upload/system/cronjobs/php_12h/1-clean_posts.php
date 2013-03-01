<?php
	/*$types  = $db2->fetch_field('SELECT value FROM settings WHERE word="POST_TYPES_TO_AUTODELETE" LIMIT 1');
	
	$error = FALSE;
	
	$types_arr = array();
	if($types != ''){	
		$types_arr = explode( '|', $types );
		$types_arr = array_unique( $types_arr );
		$types_arr = array_intersect(  $types_arr , array('feed', 'human', 'none') );
		
		if(in_array('feed', $types_arr) && !in_array('human', $types_arr)) $types_sql=' (api_id=2 OR api_id=6) AND ';
		elseif(in_array('human', $types_arr) && !in_array('feed', $types_arr)) $types_sql=' (api_id<>2 OR api_id<>6) AND ';
		elseif(in_array('feed', $types_arr) && in_array('human', $types_arr)) $types_sql='';
		elseif(in_array('none', $types_arr)) $error = TRUE;
		else $error = TRUE;
		
	}else{
		$error = TRUE;
	}
	
	$period = $db2->fetch_field('SELECT value FROM settings WHERE word="POST_TYPES_DELETE_PERIOD" LIMIT 1');
	$period = intval($period);
	
	if( $period < 1 ) $error = TRUE;
	
	if(!$error){
		
		$date		= time() - $period*24*60*60;
		$faved	= array();
		$r	= $db2->query('SELECT DISTINCT post_id FROM post_favs WHERE post_type="public" ');
		while($tmp = $db2->fetch_object($r)) {
			$faved[intval($tmp->post_id)]	= 1;
		}
		$posts	= array();
		$r	= $db2->query('SELECT id FROM posts WHERE '.$types_sql.' date<"'.$date.'" AND comments=0 AND reshares=0 AND likes=0');
		while($tmp = $db2->fetch_object($r)) {
			$tmp->id	= intval($tmp->id);
			if( isset($faved[$tmp->id]) ) {
				continue;
			}
			$posts[]	= $tmp->id;
		}
		$r	= $db2->query('SELECT id FROM posts WHERE api_id=0 AND user_id=0 AND date<"'.$date.'" ');
		while($tmp = $db2->fetch_object($r)) {
			$posts[]	= intval($tmp->id);
		}
		
		$user	= (object) array (
			'is_logged'	=> TRUE,
			'id'		=> 0,
			'info'	=> (object) array('is_network_admin' => 1),
		);
		foreach($posts as $tmp) {
			$p	= new post('public', $tmp);
			$p->delete_this_post();
		}
	}*/

?>
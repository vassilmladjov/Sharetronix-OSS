<?php
	function prepare_header()
	{
		if( !function_exists('apache_request_headers') ) {
			return false;
		}
		$raw_header = apache_request_headers();

		if(isset($raw_header['Authorization']) && preg_match('/OAuth/iu', $raw_header['Authorization']))
		{
			$raw_header = explode(',', $raw_header['Authorization']);

			foreach($raw_header as $k=>$v)
			{
				$raw_header[$k] = preg_replace('/"/', '', $raw_header[$k]);	
				$raw_header[$k] = explode('=', $raw_header[$k]);
				$ready_header[strtolower(trim($raw_header[$k][0]))] = $raw_header[$k][1]; 
			}
			unset($raw_header);
			
			return $ready_header;
		}else return false;
	}
	function prepare_request()
	{
		if($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'DELETE'){
			return false;
		}
		if(isset($_REQUEST['oauth_consumer_key'])){
			return $_REQUEST;
		}
		
		return false;
	}	
	function check_if_basic_auth()
	{
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) return array(trim($_SERVER['PHP_AUTH_USER']), trim($_SERVER['PHP_AUTH_PW']));
		
		$raw_header = array();
		
		if(function_exists('apache_request_headers')) $raw_header = apache_request_headers(); 
			elseif(isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']))  
				$raw_header['Authorization'] = $_SERVER['HTTP_AUTHORIZATION']; 
			elseif(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))  
				$raw_header['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION']; 
					else return false;

		if( !$raw_header || !isset($raw_header['Authorization']) ) {
			return false;
		}
		if( ! preg_match('/^Basic\s(.*)$/iu', $raw_header['Authorization'], $m) ) {
			return false;
		}
		$tmp	= @base64_decode( trim($m[1]) );
		if( ! $tmp || ! preg_match('/^([^\:]+)\:(.*)$/iu', $tmp, $m) ) {
			return false;
		}
		return array( trim($m[1]), trim($m[2]) );
	}
	function detect_app($check = '')
	{	
		switch(strtolower($check))
		{
			case 'tweetdeck': return 5;
				break;
			case 'spaz': return 'spaz';
				break;
		}
		
		if( !function_exists('apache_request_headers') ) {
			return 4;
		}
		
		if(preg_match('/TweetDeck/iu', implode(' ', apache_request_headers()))) return 5;
		elseif(preg_match('/spaz/iu', implode(' ', apache_request_headers()))) return 'spaz';
		
		return 4;
	}
	function get_app_id($name)
	{
		global $db2; 
		
		if($res = $db2->fetch_field('SELECT id FROM applications WHERE detect="'.$db2->e($name).'" LIMIT 1')){
			return $res;
		}
		
		return 4;
	}	
	
	function is_valid_date($date)
	{
		if(preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date))
		{
			$arr = split("-", $date); 
			if(intval(date("Y", time())) < intval($arr[0]) || intval($arr[0]) < 1900) return false;
		      if (is_numeric($arr[0]) && is_numeric($arr[1]) && is_numeric($arr[2])) return checkdate($arr[1], $arr[2], $arr[0]);
		        else return false;
		        
		}else return false;
	}
	function is_valid_data_format($format, $basic = FALSE)
	{
		if($basic && $format != 'xml' && $format != 'json') return false; 
		if($format != 'xml' && $format != 'json' && $format != 'rss' && $format != 'atom') return false;
		
		return true;
	}
	
	function generate_error($format, $err, $link, $fancy_function=FALSE)
	{	
		global $C;
		$error = '';
		
		if($format != 'xml' && $format != 'json' && $format != 'atom' && $format != 'rss') $format='xml';

		if($format == 'xml')
		{
			$error .= '<?xml version="1.0" encoding="UTF-8" ?'.'>';
			$error .= '<hash>';
			$error .= '<request>'.$link.'</request>';
			$error .= '<error>'.$err.'</error>';
			$error .= '</hash>';			
		}elseif($format == 'json')
		{
			$error .= ($fancy_function && $fancy_function != '?')? $fancy_function.'(':'';
			$error .= ($fancy_function && $fancy_function == '?')? '(':'';
			$error .= '"hash":{';
			$error .= '"request": "'.$link.'",';
			$error .= '"error": "'.$err.'"';
			$error .= '}';
			if($fancy_function) $error .= ')';	
		}elseif($format == 'rss')
		{
			$error .= '<rss version="2.0">';
   			$error .= '<channel>';
   			
			$error .= '<title> '.$C->SITE_TITLE.' API Error </title> ';
			$error .= '<link>http://'.$C->SITE_URL.'/</link> ';
			$error .= '<description>Error message.</description> ';
			$error .= '<item>';
			
			$error .= '<request>'.$link.'</request>';
			$error .= '<error>'.$err.'</error>';
			
			$error .= '</item>';			
			$error .= '</channel></rss>';	
		}elseif($format == 'atom')
		{
			$error .= '<?xml version="1.0" encoding="utf-8"?'.'>';
 			$error .= '<feed xmlns="http://www.w3.org/2005/Atom">';
			$error .= '<link href="http://'.$C->SITE_URL.'/" />';
			$error .= '<id>urn:'.md5(time()).'</id>';
			$error .= '<author>';
			$error .= '<name>'.$C->SITE_URL.'</name>';
			$error .= '</author>';
			 
			 
			$error .= '<entry>';
   			
			$error .= '<title> '.$C->SITE_TITLE.' API Error </title> ';
			$error .= '<link>http://'.$C->SITE_URL.'/</link> ';
			$error .= '<description>Error message.</description> ';
			$error .= '<item>';
			
			$error .= '<request>'.$link.'</request>';
			$error .= '<error>'.$err.'</error>';
						
			$error .= '</entry></feed>';	
		}
		
		return $error;
	}
	function generate_bottom($format, $fancy_function=FALSE)
	{
		if($format == 'rss')
		{
			$bottom = '</channel></rss>';
		}elseif($format == 'atom')
		{
			$bottom = '</feed>';
		}elseif($format=='json')
		{
			$bottom = ($fancy_function)? ')':'';
		}else $bottom = '';
		
		return $bottom;
	}
	function valid_fn($fn)
	{
		if($fn == '?') return true;
		else if(preg_match('/^([a-z0-9_]+)$/iu', $fn)) return true;
 		else return false;
	}
	
	function check_rate_limits($ip, $rate_num = 1)
	{
		global $db2, $C;
		/**
		 * @todo fix this
		 */
		return true;

		$res = $db2->query('SELECT * FROM ip_rates_limit WHERE ip="'.ip2long($db2->e($ip)).'" LIMIT 1');
		
		if($db2->num_rows($res) == 0){
			$res = $db2->query('INSERT INTO ip_rates_limit(rate_limits, rate_limits_date, ip) VALUES(1, '.(time()).', '.ip2long($db2->e($ip)).')');
			return ($db2->affected_rows($res) > 0)? true:false;
		}
		$obj = $db2->fetch_object($res);
		if(!$obj) return false;		
	
		if( (($obj->rate_limits + $rate_num) <= $C->rate_limit_number) || (date('G:j:n:y',$obj->rate_limits_date) != date('G:j:n:y', time()))){		
			if(date('G:j:n:y',$obj->rate_limits_date) != date('G:j:n:y', time())) return restart_rate_limits($ip);	
			return update_rate_limits($ip, $rate_num);		
		}
		return false;			
	}
	function restart_rate_limits($ip)
	{
		global $db2;

		$res = $db2->query('UPDATE ip_rates_limit SET rate_limits=1, rate_limits_date="'.(time()).'" WHERE ip="'.ip2long($db2->e($ip)).'" LIMIT 1');
		return ($db2->affected_rows($res) > 0)? true:false;	
	}
	function update_rate_limits($ip, $rate_num)
	{
		global $db2;
		
		$res = $db2->query('UPDATE ip_rates_limit SET rate_limits=(rate_limits+'.$rate_num.'), rate_limits_date="'.(time()).'" WHERE ip="'.ip2long($db2->e($ip)).'" LIMIT 1');
		return ($db2->affected_rows($res) > 0)? true:false;					
	}
	function rate_limits_left($ip){
		global $db2, $C;

		$res = $db2->query('SELECT rate_limits FROM ip_rates_limit WHERE ip="'.ip2long($db2->e($ip)).'" LIMIT 1');
		
		if($db2->num_rows($res) > 0){
			$obj = $db2->fetch_object($res);
			return intval($obj->rate_limits);
		}	
		return 0;
	}
	
	function find_user_id($resource_option=false)
	{
		global $network;
		
		if(isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id'])) $id = $_REQUEST['user_id'];
		elseif(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) $id = $_REQUEST['id'];
		elseif($resource_option && is_numeric($resource_option)) $id = $resource_option; 
		elseif(isset($_REQUEST['screen_name']) || $resource_option){
			$screen_name = (isset($_REQUEST['screen_name']))? $_REQUEST['screen_name'] : $resource_option;
			$u = $network->get_user_by_username(urldecode($screen_name));
			if(!$u) $id = 0;
			else 	$id = $u->id;		
		}else $id = 0;
		
		return $id;
	}
	
	function protected_users()
	{
		global $network,$db2,$user;
		$without_users = array();
		
		$r	= $db2->query('SELECT id FROM users WHERE is_posts_protected=1');
		while($obj = $db2->fetch_object($r)) {
			$u	= $network->get_user_by_id($obj->id);
			if( $u ) {
				$without_users[]	= $obj->id;
			}
		}
		if($user->id){
			$my_followers = array_keys($network->get_user_follows($user->id, FALSE, 'hisfollowers')->followers);
			$without_users = array_diff($without_users, $my_followers);
		}
		return $without_users;
	}
	function not_in_groups()
	{
		global $db2,$network,$user;
		
		$not_in_groups	= array();
		
		$r	= $db2->query('SELECT id FROM groups WHERE is_public=0');
		while($obj = $db2->fetch_object($r)) {
			$g	= $network->get_group_by_id($obj->id);
			if( ! $g ) {
				$not_in_groups[]	= $obj->id;
				continue;
			}
			if( $g->is_public == 1 ) {
				continue;
			}
			if( $user->is_logged && $user->id) {
				$m	= $network->get_group_members($g->id);
				if( ! isset($m[$user->id]) ) {
					$not_in_groups[]	= $obj->id;
				}
			}
		}
		return $not_in_groups;
	}
?>
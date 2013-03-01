<?php
class TwitterData
{
		private $format;
		private $callback;
		private $uid;
		private $is_data_array;
		private $data_section_name;
		private $data_section_final;
		private $status_id;
		private $user_id;
		
		public function __construct($format, $callback, $uid, $is_data_array = FALSE)
		{
			if($format != 'json' && $format != 'xml' && $format != 'rss' && $format != 'atom') $this->format = 'xml';
			else $this->format = $format;
			
			$this->callback = $callback;
			$this->uid = $uid;
			$this->is_data_array = FALSE;
			$this->data_section_name = '';
			$this->data_section_final = FALSE;
			$this->is_data_array = $is_data_array;
		}

		public function is_feed()
		{
			if($this->format == 'atom' || $this->format == 'rss') return true;
			else return false;
		}
			
		public function data_header()
		{
			global $C;
			$header = '';
			
			if($this->format == 'xml') $header = '<?xml version="1.0" encoding="utf-8"?'.'>';
			elseif($this->format == 'json' && $this->callback) $header = ($this->callback == '?') ? ('('): ($this->callback.'(');		
			elseif($this->format == 'rss') $header = '<?xml version="1.0" encoding="UTF-8"'.'?'.'><rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">';
			elseif($this->format == 'atom') $header = '<?xml version="1.0" encoding="UTF-8"?'.'><feed xml:lang="en-US" xmlns:georss="http://www.georss.org/georss" xmlns="http://www.w3.org/2005/Atom">';	
	
			$header .= $this->data_desc();
			
			return $header;
		}
		public function data_desc()
		{
			global $C, $network;
			
			if($this->uid != -1)
			{
				$u = $network->get_user_by_id($this->uid);
				if(!$u) return false;
			}
			
			if($this->format == 'xml' || $this->format == 'json') $desc = '';
			else if($this->format == 'atom')
			{
				$dts	= @gmmktime(0, 0, 1, gmdate('m'), gmdate('d'), gmdate('Y'), $u->lastpost_date);
				
				if($this->uid == -1)
				{
					$desc = '<title>'.$C->SITE_TITLE.'public timeline</title>';
	  				$desc .= '<id>'.$C->SITE_URL.'1/stauses/public_timeline.atom</id>';
	  				$desc .= '<link type="text/html" href="'.$C->SITE_URL.'1/public_timeline" rel="alternate"/>';
	  				$desc .= '<link type="application/atom+xml" href="'.$C->SITE_URL.'1/statuses/public_timeline.atom" rel="self"/>';
	  				$desc .= '<updated>'.date('Y-m-d\TH:i:s\Z',time()).'</updated>';
					$desc .= '<subtitle>'.$C->SITE_TITLE.' updates from everyone!</subtitle>';
				}else
				{
					$desc = '<title>'.$C->SITE_TITLE.$u->username.'</title>';
					$desc .= '<id>'.$C->SITE_URL.'1/stauses/user_timeline/'.$u->id.'.atom</id>';
					$desc .= '<link type="text/html" href="'.$C->SITE_URL.$u->username.'" rel="alternate"/>';
					$desc .= '<link type="application/atom+xml" href="'.$C->SITE_URL.'1/statuses/user_timeline/'.$u->id.'.atom" rel="self"/>';
					$desc .= '<updated>'.date('Y-m-d\TH:i:s\Z',time()).'</updated>';
					$desc .= '<subtitle>'.$C->SITE_TITLE.' updates from '.$u->fullname.' / '.$u->username.'.</subtitle>';
				}
			}else if($this->format == 'rss')
			{
				$desc = '<channel>';
				if($this->uid != -1)
				{
					$desc .= '<title>'.$C->SITE_TITLE.$u->username.'</title>';
					$desc .= '<link>'.$C->SITE_URL.$u->username.'</link>';
					$desc .= '<atom:link type="application/rss+xml" href="'.$C->SITE_URL.'1/statuses/user_timeline/'.$u->id.'.rss" rel="self"/>';
					$desc .= '<description>'.$C->SITE_TITLE.' updates from '.$u->fullname.' / '.$u->username.'.</description>';
					$desc .= '<language>'.$u->language.'</language>';
				}else
				{
					$desc .= '<title>'.$C->SITE_TITLE.' public timeline</title>';
	    				$desc .= '<link>'.$C->SITE_URL.'public_timeline.rss</link>';
	    				$desc .= '<atom:link type="application/rss+xml"';
					$desc .= ' href="'.$C->SITE_URL.'1/statuses/public_timeline.rss" rel="self"/>';
					$desc .= '<description>'.$C->SITE_URL.' public timeline</description>';
	    				$desc .= '<language>en-us</language>';
				}
			}
			
			return $desc;
		}
		public function item_entry($final = FALSE)
		{
			if($this->format == 'rss') $en = ($final)? '</item>':'<item>';
			elseif($this->format == 'atom') $en = ($final)? '</entry>':'<entry>';
			
			return $en;
		} 
		
		public function print_status($status_id, $comma = FALSE)
		{
			global $db2, $C;
			
			$status = $db2->query('SELECT * FROM posts WHERE id="'.$status_id.'" LIMIT 1');
			$status = $db2->fetch_object($status);

			$answer = '';
			if(!isset($status->id)) return $answer;
			
			if($this->format == 'xml' || $this->format == 'rss' || $this->format == 'atom')
			{ 
				$answer .= '<created_at>'.gmdate('D M d H:i:s \+0000 Y',$status->date).'</created_at>';
				$answer .= '<id>'.$status->id.'</id>';
				$answer .= '<text>'.htmlspecialchars($status->message).'</text>';
				$answer .= '<source>web</source>';
				$answer .= '<truncated>false</truncated>';
				$answer .= '<in_reply_to_status_id>null</in_reply_to_status_id>';
				$answer .= '<in_reply_to_user_id>null</in_reply_to_user_id>';
				$answer .= '<favorited>false</favorited>';
				$answer .= '<in_reply_to_screen_name>null</in_reply_to_screen_name>';
				$answer .= '<coordinates>null</coordinates>';
				$answer .= '<contributors>null</contributors>';
				$answer .= '<geo>null</geo>';
				$answer .= '<place>null</place>';
				
			}elseif($this->format == 'json')
			{			
				$answer .= '"created_at": "'.gmdate('D M d H:i:s \+0000 Y', $status->date).'",';
				$answer .= '"id": '.$status->id.',';
				$answer .= '"text": "'.htmlspecialchars($status->message).'",';	
				$answer .= '"source": "web",';
				$answer .= '"truncated": false,';
				$answer .= '"in_reply_to_status_id": null,';
				$answer .= '"in_reply_to_user_id": null,';
				$answer .= '"favorited": false,';
				$answer .= '"in_reply_to_screen_name": null,';
				$answer .= '"coordinates": null,';
				$answer .= '"contributors": null,';
				$answer .= '"geo": null,';
				$answer .= '"place": null';
				$answer .= ($comma)? ',':'';
			}		
			return $answer;	
		}
		
		public function print_user($id)
		{
			global $network, $db2, $C;
			$img_path = $C->SITE_URL.'i/avatars/thumbs1/';
			
			$u = $network->get_user_by_id($id);
			
			if(!$u){
				$u = new stdClass;
				$u->id = 0;
				$u->username = 'deleted';
				$u->fullname = 'deleted';
				$u->location = 'deleted';
				$u->about_me = 'deleted';
				$u->avatar = $C->DEF_AVATAR_USER;
				$u->num_followers = 0;
				$u->reg_date = 11111;
				$u->timezone = 'deleted';
				$u->num_posts = 0;
				$u->active = 0;
			}
			$info	= $network->get_user_follows($id, FALSE, 'hefollows');
			$friends = ($info)? count(array_keys($info->follow_users)) : 0;
			
			if($id != 0) {
				$favorites = $db2->fetch_field('SELECT COUNT(user_id) AS num FROM post_favs WHERE user_id ='.$id);
			} else {
				$favorites = 0;
			}
			
			$answer = '';

			$img_check = $u->avatar;
			$active = ($u->active==1) ? 'true':'false';
			$u->about_me = mb_substr($u->about_me, 0, 10);
			
			if($this->format == 'xml' || $this->format == 'rss' || $this->format == 'atom')
			{ 
				$answer .= '<id>'.$u->id.'</id>';
				$answer .= '<name>'.htmlspecialchars($u->fullname).'</name>';
				$answer .= '<screen_name>'.htmlspecialchars($u->username).'</screen_name>';
				$answer .= '<location>'.htmlspecialchars($u->location).'</location>';
				$answer .= '<description>'.htmlspecialchars($u->about_me).'</description>';
				$answer .= '<profile_image_url>'.$img_path.$img_check.'</profile_image_url>';
				$answer .= '<url>null</url>';
				$answer .= '<protected>false</protected>';
				$answer .= '<followers_count>'.$u->num_followers.'</followers_count>';
				$answer .= '<profile_background_color>f7d883</profile_background_color>';
				$answer .= '<profile_text_color>f7d883</profile_text_color>';
				$answer .= '<profile_link_color>f7d883</profile_link_color>';
				$answer .= '<profile_sidebar_fill_color>f7d883</profile_sidebar_fill_color>';
				$answer .= '<profile_sidebar_border_color>f7d883</profile_sidebar_border_color>';
				$answer .= '<profile_use_background_image>false</profile_use_background_image>';
				$answer .= '<friends_count>'.$friends.'</friends_count>';
				$answer .= '<created_at>'.gmdate('D M d H:i:s \+0000 Y',$u->reg_date).'</created_at>';
				$answer .= '<favourites_count>'.$favorites.'</favourites_count>';
				$answer .= '<utc_offset>null</utc_offset>';
				$answer .= '<timezone>'.htmlspecialchars($u->timezone).'</timezone>';		
				$answer .= '<profile_background_image_url>false</profile_background_image_url>';
				$answer .= '<profile_background_tile>f7d883</profile_background_tile>';
				$answer .= '<statuses_count>'.$u->num_posts.'</statuses_count>';	
				$answer .= '<notifications>null</notifications>';
				$answer .= '<following>null</following>';
				$answer .= '<language>en</language>';
				$answer .= '<follow_request_sent>null</follow_request_sent>';
				$answer .= '<contributors_enabled>false</contributors_enabled>';
				$answer .= '<time_zone>"'.htmlspecialchars('Central Time (US & Canada)').'"</time_zone>';
				$answer .= '<geo_enabled>false</geo_enabled>';
				$answer .= '<verified>'.$active.'</verified>';
			}
			elseif($this->format == 'json')
			{			
				$answer .= '"id": '.$u->id.',';
				$answer .= '"name": "'.htmlspecialchars($u->fullname).'",';
				$answer .= '"screen_name": "'.htmlspecialchars($u->username).'",';
				$answer .= '"location": "'.htmlspecialchars($u->location).'",';
				$answer .= '"description": "'.htmlspecialchars($u->about_me).'",';
				$answer .= '"profile_image_url": "'.$img_path.$img_check.'",';
				$answer .= '"url": null,';
				$answer .= '"protected": false,';
				$answer .= '"followers_count": '.$u->num_followers.',';
				$answer .= '"profile_background_color": "f7d883",';
				$answer .= '"profile_background_tile": false,';
				$answer .= '"profile_link_color": "f7d883",';
				$answer .= '"profile_sidebar_fill_color": "f7d883",';
				$answer .= '"profile_sidebar_border_color": "f7d883",';
				$answer .= '"profile_use_background_image": false,';
				$answer .= '"profile_text_color": "333333",';
				$answer .= '"friends_count": '.$friends.',';
				$answer .= '"created_at": "'.gmdate('D M d H:i:s \+0000 Y',$u->reg_date).'",';
				$answer .= '"favourites_count": "'.$favorites.'",';
				$answer .= '"utc_offset": null,';
				$answer .= '"timezone": "'.htmlspecialchars($u->timezone).'",';
				$answer .= '"profile_background_image_url": false,';
				$answer .= '"statuses_count": '.$u->num_posts.',';
				$answer .= '"language": "en",';
				$answer .= '"notifications": null,';
				$answer .= '"follow_request_sent": null,';
				$answer .= '"following": null,';		
				$answer .= '"contributors_enabled": false,';
				$answer .= '"time_zone": "'.htmlspecialchars('Central Time (US & Canada)').'",';
				$answer .= '"geo_enabled": false,';
				$answer .= '"verified": '.$active;
			}
			
			return $answer;
		}
		public function print_status_simple($post_id, $post_type='public')
		{
			global $db2, $C;
			$answer = '';
			
			require_once($C->INCPATH.'classes/class_post.php');
	
			$status = new post($post_type, $post_id); 	
			if(!isset($status->post_id)) return $answer;
				
			$answer .= $this->item_entry();
				if($this->format == 'rss')
				{
					$answer .= '<title>'.htmlspecialchars($status->post_message).'</title>';
					$answer .= '<description>'.htmlspecialchars($status->post_message).'</description>';
					$answer .= '<pubDate>'.gmdate('D, d M Y H:i:s \+0000',$status->post_date).'</pubDate>';
					$answer .= '<guid>'.$C->SITE_URL.'view/post:'.$status->post_id.'</guid>';
					$answer .= '<link>'.$C->SITE_URL.'view/post:'.$status->post_id.'</link>';
				}elseif($this->format == 'atom')
				{
					$answer .= '<title>'.htmlspecialchars($status->post_message).'</title>';
					$answer .= '<id>'.$C->SITE_URL.'1/statuses/show/'.$post_id.'</id>';
					$answer .= '<updated>'.date('Y-m-d\TH:i:s\Z',$status->post_date).'</updated>';
					$answer .= '<link type="text/html" href="'.$C->SITE_URL.'view/post:'.$post_id.'" rel="alternate"/>';
					$answer .= '<author><name>'.$status->post_user->username.'</name></author>';
				}
			$answer .= $this->item_entry(true);
			return $answer;
		}
	
		public function data_field($field_name, $field_value, $comma = TRUE, $is_string = TRUE)
		{
			$check = ($comma)?',':'';
			
			if($this->format == 'xml' || $this->format == 'rss' || $this->format == 'atom') $field = '<'.$field_name.'>'.$field_value.'</'.$field_name.'>';
			elseif($this->format == 'json' && $is_string) $field = '"'.$field_name.'": "'.$field_value.'"'.$check;
			elseif($this->format == 'json' && !$is_string) $field = '"'.$field_name.'": '.$field_value.$check;
	
			return $field;
		}
		
		public function data_section($name, $print_name = FALSE, $final = FALSE, $is_main = FALSE, $additional_params = '')
		{
			if($this->format == 'xml' || $this->format == 'rss' || $this->format == 'atom')
			{
				$data = (!$final)? '<'.$name.$additional_params.'>':'</'.$name.'>';
			}
			elseif($this->format == 'json')
			{
				if($this->is_data_array && $is_main) $data = (!$final)? '[':']';
				elseif($print_name) $data = (!$final)? '"'.$name.'"'.':{':'}';
				elseif(!$print_name) $data = (!$final)? '{':'}';
			}
				
			return $data;
		}
		
		public function data_bottom()
		{
			if($this->format == 'rss') $bottom = '</channel></rss>';
				elseif($this->format == 'atom') $bottom = '</feed>';
					elseif($this->format == 'json') $bottom = ($this->callback)? ')':'';
						else $bottom = '';	
			return $bottom;
		}
}
?>
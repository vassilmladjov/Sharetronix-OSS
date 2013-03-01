<?php

	function represent_comment_in_email($comment_message, $is_html = TRUE)
	{
		global $C, $page;
		$page->load_langfile('email/notifications.php');
			
		$delimiter = ($is_html)? '<br />':"\n";
			
		$message = $delimiter.$delimiter.' "'.$comment_message.'"';
			
		return $message;
	}

	function represent_post_in_email($post_message, $is_html = TRUE)
	{
		global $C, $page;
		$page->load_langfile('email/notifications.php');
		
		$delimiter = ($is_html)? '<br />':"\n";
		
		$message = $delimiter.$delimiter.' "'.$post_message.'"';
		
		//@TODO: check if post has attached data
		/*if(isset($this->attached['link']) || isset($this->attached['videoembed']) || isset($this->attached['image']) || isset($this->attached['file'])){

			$message .= $delimiter.$delimiter.$page->lang('email_ntf_me_attached_data').$delimiter;
			
			if(isset($this->attached['link'])){
			$message .= $page->lang('email_ntf_me_attached_data_link').$delimiter;
			}
			if(isset($this->attached['videoembed'])){
				$message .= $page->lang('email_ntf_me_attached_data_video').$delimiter;
			}
			if(isset($this->attached['image'])){
				$message .= $page->lang('email_ntf_me_attached_data_image').$delimiter;
			}
			if(isset($this->attached['file'])){
				$message .= $page->lang('email_ntf_me_attached_data_file').$delimiter;
			}
		}*/	
		
		return $message;
	}
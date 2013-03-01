<?php
	
	if( !$this->network->id ) {
		exit;
	}
	
	if( !$this->user->is_logged ){
		$this->redirect('signin');
	}
	
	$attid = trim(intval($this->param('attid')));

	if( $this->param('pid') )
	{
		$tmp	= trim($this->param('pid'));
		if( ! preg_match('/^(public|private)_([0-9]+)$/', $tmp, $m) ) {
			exit;
		}
		$p	= new post($m[1], $m[2]);
		if( $p->error ) {
			exit;
		}
		$tmp	= $p->post_attached; 
		if( $this->param('tp')=='image' ) {
			if( ! isset($tmp['image']) ) {
				exit;
			}
			$tmp	= $tmp['image'][$attid];
			$file	= $C->IMG_DIR.'attachments/'.$this->network->id.'/'.$tmp->file_original;
			if( ! file_exists($file) ) {
				exit;
			}
			$cnttype	= 'application/octet-stream';
			list($w, $h, $tp)	= getimagesize($file);
			if( $tp && $tp = image_type_to_mime_type($tp) ) {
				$cnttype	= $tp;
			}
			header('Content-Description: File Transfer');
			header('Content-type: '.$cnttype);
			header('Content-Disposition: attachment; filename="'.$tmp->title.'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			if(stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false){
			   	header('Content-Length: '.filesize($file));
			}
			readfile($file);
		}
		else {
			if( ! isset($tmp['file']) ) {
				exit;
			}
			
			$tmp	= $tmp['file'][$attid]; //print_r($tmp); exit;
			$file	= $C->STORAGE_DIR.'attachments/'.$this->network->id.'/'.$tmp->file_original;
			if( ! file_exists($file) ) {
				exit;
			}
			header('Content-Description: File Transfer');
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$tmp->file_original.'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			if(stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false){
			   	header('Content-Length: '.filesize($file));
			}
			readfile($file);
		}
		exit;
	}
	
	if( $this->param('tmpid') )
	{
		$tmp	= trim($this->param('tmpid'));
		$s	= & $this->user->sess;
		if( isset($s['TEMP_ACTIVITY_POSTS_ATTACHMENTS']) && isset($s['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$tmp]) ) {
			$tmp	= $s['TEMP_ACTIVITY_POSTS_ATTACHMENTS'][$tmp];
			if( ! isset($tmp['file']) ) {
				exit;
			}
			$tmp	= $tmp['file'][$attid]; 
			$file	= $C->STORAGE_TMP_DIR.$tmp->tempfile;
			if( ! file_exists($file) ) {
				exit;
			}
			header('Content-Description: File Transfer');
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$tmp->tempfile.'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			if(stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false){
			   	header('Content-Length: '.filesize($file));
			}
			readfile($file);
			exit;
		}
	}
	
	exit;
	
?>
<?php
	
	function networkbranding_logo_resize($source, $destination, $height)
	{
		global $C;
		if( ! file_exists($source) ) {
			return FALSE;
		}
		list($w, $h, $tp) = getimagesize($source);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}
		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			if( $tp==IMAGETYPE_GIF ) {
				$source	.= '[0]';
			}
			system( $C->IM_CONVERT.' '.$source.' -resize x'.$height.' -strip +repage '.$destination );
		}
		else {
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($source);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($source);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($source);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			$neww	= $w;
			$newh	= $height;
			if( $h != $newh ) {
				$neww	= round($newh * $w / $h);
			}
			$dstp	= imagecreatetruecolor($neww, $newh);
			$res	= imagecopyresampled($dstp, $srcp, 0, 0, 0, 0, $neww, $newh, $w, $h);
			if( ! $res ) {
				imagedestroy($srcp);
				imagedestroy($dstp);
				return FALSE;
			}
			$res	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$res	= imagegif($dstp, $destination);
					break;
				case IMAGETYPE_JPEG:
					$res	= imagejpeg($dstp, $destination, 100);
					break;
				case IMAGETYPE_PNG:
					$res	= imagepng($dstp, $destination);
					break;
			}
			imagedestroy($srcp);
			imagedestroy($dstp);
			if( ! $res ) {
				return FALSE;
			}
		}
		if( ! file_exists($destination) ) {
			return FALSE;
		}
		chmod( $destination, 0777 );
		return TRUE;
	}
	
	
	function create_weird_size_avatar_for_mobile_version($source, $fn){
		
			
	}
	
	function copy_avatar($source, $fn)
	{
		global $C;
		
		if( ! file_exists($source) ) {
			return FALSE;
		}
		list($w, $h, $tp) = getimagesize($source);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}
		$fn0	= $C->STORAGE_DIR.'avatars/'.$fn;
		$fn1	= $C->STORAGE_DIR.'avatars/thumbs1/'.$fn;
		$fn2	= $C->STORAGE_DIR.'avatars/thumbs2/'.$fn;
		$fn3	= $C->STORAGE_DIR.'avatars/thumbs3/'.$fn;
		$fn4 	= $C->STORAGE_DIR.'avatars/thumbs4/'.$fn;
		$fn5 	= $C->STORAGE_DIR.'avatars/thumbs5/'.$fn;
		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.$C->AVATAR_SIZE.'x -strip +repage '.$fn0 );
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE1.'x'):('x'.$C->AVATAR_SIZE1)).' -crop '.$C->AVATAR_SIZE1.'x'.$C->AVATAR_SIZE1.'+0+0 -strip +repage '.$fn1 );
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE2.'x'):('x'.$C->AVATAR_SIZE2)).' -crop '.$C->AVATAR_SIZE2.'x'.$C->AVATAR_SIZE2.'+0+0 -strip +repage '.$fn2 );
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE3.'x'):('x'.$C->AVATAR_SIZE3)).' -crop '.$C->AVATAR_SIZE3.'x'.$C->AVATAR_SIZE3.'+0+0 -strip +repage '.$fn3 );
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE4.'x'):('x'.$C->AVATAR_SIZE4)).' -crop '.$C->AVATAR_SIZE4.'x'.$C->AVATAR_SIZE4.'+0+0 -strip +repage '.$fn4 );
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w<$h?($C->AVATAR_SIZE_MOBILE_WIDTH.'x'):('x'.$C->AVATAR_SIZE_MOBILE_HEIGHT)).' -crop '.$C->AVATAR_SIZE_MOBILE_WIDTH.'x'.$C->AVATAR_SIZE_MOBILE_HEIGHT.'+0+0 -strip +repage '.$fn5 );
			
			if( $tp==IMAGETYPE_GIF && !file_exists($fn0) ) {
				$tmp0	= str_replace('.png', '-0.png', $fn0);
				$tmp1	= str_replace('.png', '-0.png', $fn1);
				$tmp2	= str_replace('.png', '-0.png', $fn2);
				$tmp3	= str_replace('.png', '-0.png', $fn3);
				$tmp4   = str_replace(".png", '-0.png', $fn4);
				$tmp5   = str_replace(".png", '-0.png', $fn5);
				if( file_exists($tmp0) ) {
					rename($tmp0, $fn0);
					rename($tmp1, $fn1);
					rename($tmp2, $fn2);
					rename($tmp3, $fn3);
					rename($tmp4, $fn4);
					rename($tmp5, $fn5);
					$tmp	= str_replace('.png', '-', $fn);
					system( 'rm '.$C->STORAGE_DIR.'avatars/'.$tmp.'*' );
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs1/'.$tmp.'*' );
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs2/'.$tmp.'*' );
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs3/'.$tmp.'*' );
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs4/'.$tmp.'*' );
					system( 'rm '.$C->STORAGE_DIR.'avatars/thumbs5/'.$tmp.'*' );
				}
			}
		}
		else {
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($source);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($source);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($source);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			$dstp0	= imagecreatetruecolor($C->AVATAR_SIZE, round($h*$C->AVATAR_SIZE/$w));
			$dstp1	= imagecreatetruecolor($C->AVATAR_SIZE1, $C->AVATAR_SIZE1);
			$dstp2	= imagecreatetruecolor($C->AVATAR_SIZE2, $C->AVATAR_SIZE2);
			$dstp3	= imagecreatetruecolor($C->AVATAR_SIZE3, $C->AVATAR_SIZE3);
			$dstp4	= imagecreatetruecolor($C->AVATAR_SIZE4, $C->AVATAR_SIZE4);
			$dstp5	= imagecreatetruecolor($C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_HEIGHT);
			$dstp5_tmp = imagecreatetruecolor($C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_WIDTH);
			
			$res0	= imagecopyresampled($dstp0, $srcp, 0, 0, 0, 0, $C->AVATAR_SIZE, round($h*$C->AVATAR_SIZE/$w), $w, $h);
			$res1	= imagecopyresampled($dstp1, $srcp, 0, 0, $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE1, $C->AVATAR_SIZE1, min($w,$h), min($w,$h));
			$res2	= imagecopyresampled($dstp2, $srcp, 0, 0, $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE2, $C->AVATAR_SIZE2, min($w,$h), min($w,$h));
			$res3	= imagecopyresampled($dstp3, $srcp, 0, 0, $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE3, $C->AVATAR_SIZE3, min($w,$h), min($w,$h));
			$res4	= imagecopyresampled($dstp4, $srcp, 0, 0, $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE4, $C->AVATAR_SIZE4, min($w,$h), min($w,$h));
			
			$res5_tmp = imagecopyresampled($dstp5_tmp, $srcp, 0, 0,  $w>$h?round(($w-$h)/2):0, $w>$h?0:round(($h-$w)/2), $C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_WIDTH, min($w,$h), min($w,$h));
			$res5	= imagecopyresampled($dstp5, $dstp5_tmp, 0, 0, 0, round($C->AVATAR_SIZE_MOBILE_HEIGHT * 2), $C->AVATAR_SIZE_MOBILE_WIDTH, $C->AVATAR_SIZE_MOBILE_HEIGHT, 420, 60);
			
			imagedestroy($srcp);
			if( ! ($res0 && $res1 && $res2 && $res3 && $res4 && $res5 && $res5_tmp) ) {
				imagedestroy($dstp0);
				imagedestroy($dstp1);
				imagedestroy($dstp2);
				imagedestroy($dstp3);
				imagedestroy($dstp4);
				imagedestroy($dstp5);
				imagedestroy($dstp5_tmp);
				
				return FALSE;
			}
			switch($tp) {
				case IMAGETYPE_GIF:
					imagegif($dstp0, $fn0);
					imagegif($dstp1, $fn1);
					imagegif($dstp2, $fn2);
					imagegif($dstp3, $fn3);
					imagegif($dstp4, $fn4);
					imagegif($dstp5, $fn5);

					break;
				case IMAGETYPE_JPEG:
					imagejpeg($dstp0, $fn0, 100);
					imagejpeg($dstp1, $fn1, 100);
					imagejpeg($dstp2, $fn2, 100);
					imagejpeg($dstp3, $fn3, 100);
					imagejpeg($dstp4, $fn4, 100);
					imagejpeg($dstp5, $fn5, 100);

					break;
				case IMAGETYPE_PNG:
					imagepng($dstp0, $fn0);
					imagepng($dstp1, $fn1);
					imagepng($dstp2, $fn2);
					imagepng($dstp3, $fn3);
					imagepng($dstp4, $fn4);
					imagepng($dstp5, $fn5);

					break;
			}
			imagedestroy($dstp0);
			imagedestroy($dstp1);
			imagedestroy($dstp2);
			imagedestroy($dstp3);
			imagedestroy($dstp4);
			imagedestroy($dstp5);
			
		}
		if( !file_exists($fn0) || !file_exists($fn1) || !file_exists($fn2) || !file_exists($fn3) || !file_exists($fn4) || !file_exists($fn5) ) {
			rm($fn0, $fn1, $fn2, $fn3, $fn4, $fn5);
			return FALSE;
		}
		chmod( $fn0, 0777 );
		chmod( $fn1, 0777 );
		chmod( $fn2, 0777 );
		chmod( $fn3, 0777 );
		chmod( $fn4, 0777 );
		chmod( $fn5, 0777 );
		
		return TRUE;
	}
	
	function copy_attachment_image($input, $data)
	{
		global $C;
		if( preg_match('/^(http|https|ftp)\:\/\//u', $input) ) {
			$tmp	= $C->STORAGE_TMP_DIR.'tmp'.md5(time().rand()).'.'.pathinfo($input,PATHINFO_EXTENSION);
			$res	= my_copy($input, $tmp);
			if( ! $res ) {
				return FALSE;
			}
			chmod($tmp, 0777);
			$input	= $tmp;
		}
		list($w, $h, $tp)	= getimagesize($input);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}
		$data->size_original	= array($w, $h);
		if( ! copy($input, $C->STORAGE_TMP_DIR.$data->file_original) ) {
			return FALSE;
		}
		$data->filesize	= filesize($C->STORAGE_TMP_DIR.$data->file_original);
		if( ! copy($input, $C->STORAGE_TMP_DIR.$data->file_preview) ) {
			return FALSE;
		}
		list($w, $h)	= getimagesize($C->STORAGE_TMP_DIR.$data->file_preview);
		if( $w == 0 || $h == 0 ) {
			return FALSE;
		}
		$neww	= $w;
		$newh	= $h;
		if( $w > $C->ATTACH_IMAGE_MXWIDTH ) {
			$neww	= $C->ATTACH_IMAGE_MXWIDTH;
			$newh	= round($neww * $h / $w);
		}
		if( $h > $C->ATTACH_IMAGE_MXHEIGHT ) {
			$newh	= $C->ATTACH_IMAGE_MXHEIGHT;
			$neww	= round($newh * $w / $h);
		}
		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			exec( $C->IM_CONVERT.' '.$C->STORAGE_TMP_DIR.$data->file_preview.' -resize '.$neww.'x'.$newh.' '.$C->STORAGE_TMP_DIR.$data->file_preview );
			list($w, $h)	= getimagesize($C->STORAGE_TMP_DIR.$data->file_preview);
			$data->size_preview	= array($w, $h);
			exec( $C->IM_CONVERT.' '.$C->STORAGE_TMP_DIR.$data->file_preview.' -gravity Center -resize '.($w>$h ? ('x'.$C->ATTACH_IMAGE_THUMBSIZE) : ($C->ATTACH_IMAGE_THUMBSIZE.'x')).' -crop '.$C->ATTACH_IMAGE_THUMBSIZE.'x'.$C->ATTACH_IMAGE_THUMBSIZE.'+0+0 -strip +repage '.$C->STORAGE_TMP_DIR.$data->file_thumbnail );
			if( ! file_exists($C->STORAGE_TMP_DIR.$data->file_thumbnail) ) {
				return FALSE;
			}
		}
		else {
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($input);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($input);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($input);
					$srcp = imagetranstowhite($srcp);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			$dstp	= imagecreatetruecolor($neww, $newh);
			$res	= imagecopyresampled($dstp, $srcp, 0, 0, 0, 0, $neww, $newh, $w, $h);
			if( ! $res ) {
				imagedestroy($srcp);
				imagedestroy($dstp);
				return FALSE;
			}
			$res	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$res	= imagegif($dstp, $C->STORAGE_TMP_DIR.$data->file_preview);
					break;
				case IMAGETYPE_JPEG:
					$res	= imagejpeg($dstp, $C->STORAGE_TMP_DIR.$data->file_preview, 100);
					break;
				case IMAGETYPE_PNG:
					$res	= imagepng($dstp, $C->STORAGE_TMP_DIR.$data->file_preview);
					break;
			}
			imagedestroy($srcp);
			imagedestroy($dstp);
			if( ! $res ) {
				return FALSE;
			}
			list($w, $h)	= getimagesize($C->STORAGE_TMP_DIR.$data->file_preview);
			$data->size_preview	= array($w, $h);
			
			if($w>$h){
				$percent = $C->ATTACH_IMAGE_THUMBSIZE/$w;
				$neww	= $C->ATTACH_IMAGE_THUMBSIZE;
				$newh = $percent*$h;
			}else {
				$percent = $C->ATTACH_IMAGE_THUMBSIZE/$h;
				$newh	= $C->ATTACH_IMAGE_THUMBSIZE;
				$neww = $percent*$w;
			}
			
			
			
			//$newx	= $w>$h ? round(($w-$h)/2) : 0;
			//$newy	= $w>$h ? 0 : round(($h-$w)/2);
			
			
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($C->STORAGE_TMP_DIR.$data->file_preview);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($C->STORAGE_TMP_DIR.$data->file_preview);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($C->STORAGE_TMP_DIR.$data->file_preview);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			$dstp	= imagecreatetruecolor($neww, $newh);
			//$res	= imagecopyresampled($dstp, $srcp, 0, 0, $newx, $newy, $neww, $newh, min($w,$h), min($w,$h));
			$res	= imagecopyresampled($dstp, $srcp, 0, 0, 0, 0, $neww, $newh, $w, $h);
			if( ! $res ) {
				imagedestroy($srcp);
				imagedestroy($dstp);
				return FALSE;
			}
			switch($tp) {
				case IMAGETYPE_GIF:
					$res	= imagegif($dstp, $C->STORAGE_TMP_DIR.$data->file_thumbnail);
					break;
				case IMAGETYPE_JPEG:
					$res	= imagejpeg($dstp, $C->STORAGE_TMP_DIR.$data->file_thumbnail, 100);
					break;
				case IMAGETYPE_PNG:
					$res	= imagepng($dstp, $C->STORAGE_TMP_DIR.$data->file_thumbnail);
					break;
			}
			imagedestroy($srcp);
			imagedestroy($dstp);
			if( ! $res ) {
				return FALSE;
			}
		}
		if( empty($data->title) ) {
			$data->title	= basename($input);
		}
		$pos	= strpos($data->title, '?');
		if( FALSE !== $pos ) {
			$data->title	= substr($data->title, 0, $pos);
		}
		$pos	= strpos($data->title, '#');
		if( FALSE !== $pos ) {
			$data->title	= substr($data->title, 0, $pos);
		}
		$data->title	= trim($data->title);
		return $data;
	}
	
	function copy_attachment_videoimg($source, $destination, $size)
	{
		global $C;
		if( preg_match('/^(http|https|ftp)\:\/\//u', $source) ) {
			$tmp	= $C->STORAGE_TMP_DIR.'tmp'.md5(time().rand()).'.'.pathinfo($source,PATHINFO_EXTENSION);
			$res	= my_copy($source, $tmp);
			if( ! $res ) {
				return FALSE;
			}
			chmod($tmp, 0777);
			$source	= $tmp;
		}
		if( ! file_exists($source) ) {
			return FALSE;
		}
		list($w, $h, $tp) = getimagesize($source);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}
		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			if( $tp==IMAGETYPE_GIF ) {
				$source	.= '[0]';
			}
			exec( $C->IM_CONVERT.' '.$source.' -gravity Center -resize '.($w>$h ? ('x'.$size) : ($size.'x')).' -crop '.$size.'x'.$size.'+0+0 -strip +repage '.$destination );
		}
		else {
			$srcp	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$srcp	= imagecreatefromgif($source);
					break;
				case IMAGETYPE_JPEG:
					$srcp	= imagecreatefromjpeg($source);
					break;
				case IMAGETYPE_PNG:
					$srcp	= imagecreatefrompng($source);
					$srcp 	= imagetranstowhite($srcp);
					break;
			}
			if( ! $srcp ) {
				return FALSE;
			}
			$newx	= $w>$h ? round(($w-$h)/2) : 0;
			$newy	= $w>$h ? 0 : round(($h-$w)/2);
			$dstp	= imagecreatetruecolor($size, $size);
			$res	= imagecopyresampled($dstp, $srcp, 0, 0, $newx, $newy, $size, $size, min($w,$h), min($w,$h));
			if( ! $res ) {
				imagedestroy($srcp);
				imagedestroy($dstp);
				return FALSE;
			}
			switch($tp) {
				case IMAGETYPE_GIF:
					$res	= imagegif($dstp, $destination);
					break;
				case IMAGETYPE_JPEG:
					$res	= imagejpeg($dstp, $destination, 100);
					break;
				case IMAGETYPE_PNG:
					$res	= imagepng($dstp, $destination);
					break;
			}
			imagedestroy($srcp);
			imagedestroy($dstp);
			if( ! $res ) {
				return FALSE;
			}
		}
		chmod( $destination, 0777 );
		return TRUE;
	}
	
	function imagetranstowhite($trans) {
		// Create a new true color image with the same size
		$w = imagesx($trans);
		$h = imagesy($trans);
		$white = imagecreatetruecolor($w, $h);
	
		// Fill the new image with white background
		$bg = imagecolorallocate($white, 255, 255, 255);
		imagefill($white, 0, 0, $bg);
	
		// Copy original transparent image onto the new image
		imagecopy($white, $trans, 0, 0, 0, 0, $w, $h);
		return $white;
	}
	
	function create_thumbnail_image($img_name)
	{
		global $C;
	
		$input = $C->STORAGE_TMP_DIR.$img_name;
	
		list($w, $h, $tp)	= getimagesize($input);
		if( $w==0 || $h==0 ) {
			return FALSE;
		}
		if( $tp!=IMAGETYPE_GIF && $tp!=IMAGETYPE_JPEG && $tp!=IMAGETYPE_PNG ) {
			return FALSE;
		}

		if( $C->IMAGE_MANIPULATION == "imagemagick_cli" ) {
			exec( $C->IM_CONVERT.' -thumbnail x'.$C->ATTACH_IMAGE_THUMBSIZE.' '.$input.' '.$C->STORAGE_TMP_DIR.'thumb_'.$img_name );
	
			if( ! file_exists($C->STORAGE_TMP_DIR.'thumb_'.$img_name) ) {
				return FALSE;
			}
		}
		else {
			$desired_height = $C->ATTACH_IMAGE_THUMBSIZE;
			$dest = $C->STORAGE_TMP_DIR.'thumb_'.$img_name;
			
			$source_image	= FALSE;
			switch($tp) {
				case IMAGETYPE_GIF:
					$source_image	= imagecreatefromgif($input);
					break;
				case IMAGETYPE_JPEG:
					$source_image	= imagecreatefromjpeg($input);
					break;
				case IMAGETYPE_PNG:
					$source_image	= imagecreatefrompng($input);
					$source_image = imagetranstowhite($source_image);
					break;
			}
			if( ! $source_image ) {
				return FALSE;
			}
			
			/* read the source image */
			  //$source_image = imagecreatefromjpeg($input);
			  $width = imagesx($source_image);
			  $height = imagesy($source_image);
			  
			  /* find the "desired height" of this thumbnail, relative to the desired width  */
			  $desired_width = floor($width * ($desired_height / $height));
			  
			  if($width>$height){
			  	$percent = $C->ATTACH_IMAGE_THUMBSIZE/$width;
			  	$desired_width	= $C->ATTACH_IMAGE_THUMBSIZE;
			  	$desired_height = $percent*$height;
			  }else {
			  	$percent = $C->ATTACH_IMAGE_THUMBSIZE/$height;
			  	$desired_height	= $C->ATTACH_IMAGE_THUMBSIZE;
			  	$desired_width = $percent*$width;
			  }
			  
			  /* create a new, "virtual" image */
			  $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
			  
			  /* copy source image at a resized size */
			  imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
			  
			  /* create the physical thumbnail image to its destination */
			  //imagejpeg($virtual_image, $dest, 100);
			  
			  switch($tp) {
			  	case IMAGETYPE_GIF:
			  		$res	= imagegif($virtual_image, $dest);
			  		break;
			  	case IMAGETYPE_JPEG:
			  		$res	= imagejpeg($virtual_image, $dest, 100);
			  		break;
			  	case IMAGETYPE_PNG:
			  		$res	= imagepng($virtual_image, $dest);
			  		break;
			  }
		}
	
		return TRUE;
	}
	
	
	
?>
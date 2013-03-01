<?php

	$req = curl_init();
	curl_setopt($req, CURLOPT_URL, 'http://www.developer.sharetronix.com/versioncheck/?version='.urlencode($C->VERSION).'&site='.urlencode($C->SITE_URL).'&lang='.urlencode($C->LANGUAGE).'&type='.urlencode('professional'));
	curl_exec($req);
	curl_close($req);
	
	$db2->query('DELETE FROM oauth_request_token WHERE (timestamp+1000)<'.time());
	
?>
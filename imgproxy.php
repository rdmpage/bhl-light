<?php

//----------------------------------------------------------------------------------------
function sign_imgproxy_path($image_url, $width = 0, $height = 0, $resize = 'auto', $gravity = 'no')
{
	$key = getenv('IMGPROXY_KEY');
	$salt = getenv('IMGPROXY_SALT');
	
	$keyBin = pack("H*" , $key);
	if(empty($keyBin)) {
		die('Key expected to be hex-encoded string');
	}
	
	$saltBin = pack("H*" , $salt);
	if(empty($saltBin)) {
		die('Salt expected to be hex-encoded string');
	}
	
	$processing_options = join(':', ['rs:' . $resize, $width, $height, 0]) . '/g:' . $gravity;

	$path = '/' . $processing_options . '/plain/' . $image_url;
	
	$signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $saltBin.$path, $keyBin, true)), '+/', '-_'), '=');

	return sprintf("/%s%s", $signature, $path);
}

?>
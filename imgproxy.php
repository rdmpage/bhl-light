<?php

require_once(dirname(__FILE__) . '/config.inc.php');


//----------------------------------------------------------------------------------------
function imgproxy_path_sign($path)
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

	$signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $saltBin.$path, $keyBin, true)), '+/', '-_'), '=');

	return sprintf("/%s%s", $signature, $path);
}


//----------------------------------------------------------------------------------------
function imgproxy_path_resize($image_url, $width = 0, $height = 0, $resize = 'auto')
{
	$processing_options = join(':', ['rs:' . $resize, $width, $height, 0]) . '/g:no';

	$path = '/' . $processing_options . '/plain/' . $image_url;
	
	return imgproxy_path_sign($path);
}

//----------------------------------------------------------------------------------------
function imgproxy_path_crop($image_url, $width = 0, $height = 0, $centre = [0.5, 0.5])
{
	$processing_options = join(':', ['c:' . $width, $height]) . '/g:fp:' . $centre[0] . ':' . $centre[1];

	$path = '/' . $processing_options . '/plain/' . $image_url;
	
	return imgproxy_path_sign($path);
}


//----------------------------------------------------------------------------------------
// Build imgproxy path for IIIF Image API parameters
// $image_url: source image URL (e.g. on Hetzner S3)
// $region: 'full' or 'x,y,w,h'
// $size: 'full', 'max', 'w,', ',h', 'w,h', or '!w,h'
// $format: 'jpg', 'webp', 'png'
function imgproxy_path_iiif($image_url, $region = 'full', $size = 'max', $format = 'jpg')
{
	$options = array();

	// Region: crop
	if ($region !== 'full')
	{
		$parts = explode(',', $region);
		if (count($parts) == 4)
		{
			$x = intval($parts[0]);
			$y = intval($parts[1]);
			$w = intval($parts[2]);
			$h = intval($parts[3]);
			$options[] = "c:$w:$h";
			$options[] = "g:nowe:$x:$y";
		}
	}

	// Size: resize
	if ($size === 'full' || $size === 'max')
	{
		// no resize
	}
	elseif (preg_match('/^!(\d+),(\d+)$/', $size, $m))
	{
		// best fit within w,h (maintain aspect ratio)
		$options[] = 'rs:fit:' . intval($m[1]) . ':' . intval($m[2]) . ':0';
	}
	elseif (preg_match('/^(\d+),(\d+)$/', $size, $m))
	{
		// exact w,h (may distort)
		$options[] = 'rs:force:' . intval($m[1]) . ':' . intval($m[2]) . ':0';
	}
	elseif (preg_match('/^(\d+),$/', $size, $m))
	{
		// width only, auto height
		$options[] = 'rs:auto:' . intval($m[1]) . ':0:0';
	}
	elseif (preg_match('/^,(\d+)$/', $size, $m))
	{
		// height only, auto width
		$options[] = 'rs:auto:0:' . intval($m[1]) . ':0';
	}

	// Format
	$ext_map = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
	$ext = isset($ext_map[$format]) ? $ext_map[$format] : 'jpg';
	$options[] = 'f:' . $ext;

	$path = '/' . implode('/', $options) . '/plain/' . $image_url;

	return imgproxy_path_sign($path);
}


if (0)
{

//$url = https://images.bionames.org/KbV1qyyb1ZmaGd5iAuU-Q-AMj1VZTex-fnO0OFzOTe4/rs:auto:700:0:0/g:no/plain/https://hel1.your-objectstorage.com/bhl/europeanjournal236muse_jp2/europeanjournal236muse_0004.webp

//$image_url = 'https://hel1.your-objectstorage.com/bhl/' . $layout->internetarchive . '_jp2/' . preg_replace('/\.(djvu|jp2)/', '.webp', $layout->pages[$i]->internetarchive);

//$image_url = 'https://hel1.your-objectstorage.com/bhl/europeanjournal236muse_jp2/europeanjournal236muse_0004.webp';


//$image_url = 'https://images.bionames.org' . imgproxy_path_resize($image_url, 800);

//echo $image_url . "\n";

//exit();



$bbox =  [ 
    98.2421875, 
    440.2529296875, 
    705.46875, 
    941.859375 
];

$image_bbox = [ 
    0, 
    0, 
    800, 
    1132 
];

$crop_width  = round($bbox[2] - $bbox[0]);
$crop_height = round($bbox[3] - $bbox[1]);

$crop_centre = array(
	round(($crop_width/2.0 + $bbox[0])/$image_bbox[2], 2),
	round(($crop_height/2.0 + $bbox[1])/$image_bbox[3], 2),
);


$original_url = 'https://hel1.your-objectstorage.com/bhl/europeanjournal236muse_jp2/europeanjournal236muse_0004.webp';

$image_url = 'https://images.bionames.org' . imgproxy_path_crop($original_url, $crop_width, $crop_height, $crop_centre);

echo $image_url . "\n";

$cropped_url = 'https://images.bionames.org' . imgproxy_path_crop($original_url, $crop_width, $crop_height, $crop_centre);

$image_url = 'https://images.bionames.org' . imgproxy_path_resize($cropped_url, 0, 100);

echo $image_url . "\n";

// https://images.bionames.org/2lgS45yPTftNvqJ5tjGUFpZ4JvynVAApcHs7oJpEK0k/rs:fill:800:0:0/gravity:fp:0.48:0.52/plain/https://imgproxy.net/static/37991758c8b861062bb8e92520260e85/c58a3/01_fp_original.jpg
// https://images.bionames.org/4YcwsuGgo3prvdViertGpRO8xoypXdyD3KA7bQcKz1M/c:607.2265625:501.6064453125/g:fp:0.5:0.61/plain/https://hel1.your-objectstorage.com/bhl/europeanjournal236muse_jp2/europeanjournal236muse_0004.webp
}

?>

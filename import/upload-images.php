<?php

// Fetch images from IA, convert, and move to S3

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(__FILE__) . '/ia.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

//----------------------------------------------------------------------------------------
// Convert to WEBP
function jp2towebp($basedir, $resize_width = 800)
{
	$files = scandir($basedir);
	
	foreach ($files as $image_filename)
	{
		if (preg_match('/^[^\.].*\.jp2/', $image_filename))
		{
			$source_filename = $basedir . '/' . $image_filename;
			
			// convert to JPEG
			$jpg_filename = str_replace('.jp2', '.jpg', $source_filename);			
			$command = "mogrify -resize $resize_width  -format jpg $source_filename";
			echo $command . "\n";	
			system($command);

			// convert to WEBP
			$output_filename = str_replace('.jp2', '.webp', $source_filename);
			$command = "cwebp -quiet $jpg_filename -o $output_filename";
			echo $command . "\n";
	
			system($command);

		}
	}
}

$identifiers = get_ia_for_title(68619);

$identifiers = get_ia_for_title(211788);

$identifiers = get_ia_for_title(10229);

$identifiers = get_ia_for_title(5943);

$identifiers = get_ia_for_title(206514);

$identifiers = get_ia_for_title(57881);

//print_r($identifiers);

//$identifiers = array('amphibianreptil111996prov');

$config['s3'] = '/Users/rpage/Library/Application Support/Mountain Duck/Volumes.noindex/Amazon S3.localized/bhl';

foreach ($identifiers as $ia)
{
	$go = true;

	// don't do this if we already have the images
	$s3_dir = $config['s3'] . "/" . $ia . '_jp2';
	if (file_exists($s3_dir))
	{
		$go = false;
	}
	
	if ($go)
	{
	
		echo "Fetching $ia\n";
		fetch_ia_images($ia);
		
		echo $ia . "\n";
		
		// where IA files are stored
		$dir = $config['cache'] . "/" . $ia;
		
		// where JP2 images will be unzipped too
		$jp2_dir = $dir . '/' . $ia . '_jp2';
		
		// JP2 ZIP file from IA
		$zip_filename = $jp2_dir . '.zip';
			
		$unzip = new ZipArchive;
		$out = $unzip->open($zip_filename);
		if ($out === TRUE) 
		{
		  $unzip->extractTo($dir);
		  $unzip->close();
		  echo "File unzipped\n";
		}
		else
		{
			echo "Error unzipping\n";
		}		
		
		// convert JP2
		jp2towebp($jp2_dir);
			
		// create S3 folder
		$s3_dir = $config['s3'] . "/" . $ia . '_jp2';
		if (!file_exists($s3_dir))
		{
			$oldumask = umask(0); 
			mkdir($s3_dir, 0777);
			umask($oldumask);
		}	
			
		// move images to S3 folder
		$files = scandir($jp2_dir);
		
		foreach ($files as $image_filename)
		{
			if (preg_match('/^[^\.].*\.webp/', $image_filename))
			{
				$source_filename = $jp2_dir . '/' . $image_filename;
				$target_filename = $s3_dir . '/' . $image_filename;
			
				echo $source_filename . "\n";
				echo $target_filename . "\n";
				
				copy($source_filename, $target_filename);
			}
		}
	}
}

?>

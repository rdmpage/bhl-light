<?php

// Fetch images from IA, convert, and move to S3

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');

require_once (dirname(__FILE__) . '/ia.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

//----------------------------------------------------------------------------------------
// Convert to WEBP
function jp2towebp($basedir, $resize_width = 800, $force = false)
{
	$files = scandir($basedir);
	
	foreach ($files as $image_filename)
	{
		if (preg_match('/^[^\.].*\.jp2/', $image_filename))
		{
			$source_filename = $basedir . '/' . $image_filename;
			
			$output_filename = str_replace('.jp2', '.webp', $source_filename);
			
			if (!file_exists($output_filename) || $force)
			{
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
}

/*
$identifiers = get_ia_for_title(68619);

$identifiers = get_ia_for_title(211788);

$identifiers = get_ia_for_title(10229);

$identifiers = get_ia_for_title(5943);

$identifiers = get_ia_for_title(206514);

$identifiers = get_ia_for_title(57881);

//print_r($identifiers);

//$identifiers = array('amphibianreptil111996prov');
*/

$TitleID = 144642; // European Journal of Taxonomy
$TitleID = 211788;
$TitleID = 10229;

$TitleID = 10088; // 209695; // 10088; // 85187;

$TitleID = 10229; // Spolia zeylanica
$TitleID = 204608; // Alytes
$TitleID = 206514;

//$TitleID = 152899;
//$TitleID = 190323;

$TitleID = 150137;
$TitleID = 158870; // Forktail
$TitleID = 119522;
$TitleID = 7414;

$TitleID = 10088;
$TitleID = 65344;
$TitleID = 147681;
$TitleID = 49914;
$TitleID = 730; // TAR archive


// in progress... 
// 49914 Iberus
// 210747 Mycotaxon
// 169356 Austrobaileya
// 7414 Journal of Bombay...
// 147681 Flora of Peru
// 62642 Bulletin of the Natural History Museum - done

$TitleID = 7414;
//$TitleID = 9243;

$TitleID = 117696;

$TitleID = 210877;

$TitleID = 79076;

$titles = array(
53832, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
53833, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
2804, // Asiatic herpetological research
46858, // She wa yan jiu = Chinese herpetological research
40011, // Chinese herpetological research
);

$TitleID = 40011;
$TitleID = 46858;
$TitleID = 2804;
$TitleID = 53832;
$TitleID = 53833;

$TitleID = 181469;

$TitleID = 86930;

$TitleID = 63883;
$TitleID = 45481;

$TitleID = 59881;
$TitleID = 10597;
$TitleID = 21727;
$TitleID = 169354;

$TitleID = 698;

$TitleID = 10903;

$TitleID = 12276; // 12260; // 12276, 13390,

$TitleID = 119777;

$TitleID = 66850;

$TitleID = 52116;
$TitleID = 12498;

$TitleID = 41367;

// $titles=array(119421,119424,119515,119516,119597,119777);




$identifiers = get_ia_for_title($TitleID);

//$identifiers = array('naturalhistoryof01skot');

/*
$identifiers = array(
'deutscheentomolo101897gese',
'deutscheentomo111898gese',
'deutscheentomolo121899gese',
'deutscheentomolo131900gese',
'deutscheentomolo141901gese',
);
*/


// deutscheentomolo101897gese
print_r($identifiers);


// CAT10756254005


//exit();

/*
$identifiers  = array(
//'geodiversitas21muse', 
//'geodiversitas21musea'

//'memoiresdumuseu92muse',
//'memoiresdumuseu16mused',

//'memoiresdumuseu7musef',
//'memoiresdumuseu8museg',
//'MemoriasdoInsti11Inst',

'adansonia5museb',

);
*/

//$identifiers  = array('mobot31753002350178');

$config['s3'] = '/Users/rpage/Library/Application Support/Mountain Duck/Volumes.noindex/Hetzner.localized/bhl';

//$config['s3'] = ".";

$force = true;
$force = false;

foreach ($identifiers as $ia)
{
	echo $ia . "\n";

	$go = true;

	// don't do this if we already have the images
	$s3_dir = $config['s3'] . "/" . $ia . '_jp2';
	if (file_exists($s3_dir) && !$force)
	{
		$go = false;
		
		echo "Have already\n";
	}
	
	if ($go)
	{
	
		echo "Fetching $ia\n";
		
		$archive_format = 'zip';
		
		$ok = fetch_ia_images($ia);
		
		if (!$ok)
		{
			echo "Badness getting images, trying TAR\n";			
			$ok = fetch_ia_images_tar($ia);
			if ($ok)
			{
				$archive_format = 'tar';
			}
			else
			{
				echo "The badness is strong with this one\n";				
			}
		}
		
		echo $ia . "\n";
		
		// where IA files are stored
		$dir = $config['cache'] . "/" . $ia;
		
		// where JP2 images will be extracted too
		$jp2_dir = $dir . '/' . $ia . '_jp2';

		if ($archive_format == 'zip')
		{
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
		}
		else
		{
			// not tested yet...
			$tar_filename = $jp2_dir . '.tar';
			
			system('tar -xvzf ' . $tar_filename . ' -C ' . $dir);		
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
		
		//exit();
	}
}

?>

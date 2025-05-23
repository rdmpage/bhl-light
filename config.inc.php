<?php

error_reporting(E_ALL);

global $config;

// Date timezone
date_default_timezone_set('UTC');

$config['platform'] = 'local';
$config['platform'] = 'cloud';

$config['site']		= 'local';
$config['site']		= 'heroku';

switch ($config['site'])
{
	case 'heroku':
		$config['web_server']	= 'https://bhl-light.herokuapp.com'; 
		$config['web_root']		= '/';
		$config['site_name'] 	= 'BHL Light';
		break;	

	case 'local':
	default:
		$config['web_server']	= 'http://localhost'; 
		$config['web_root']		= '/bhl-light/'; // trailing "/" is important!
		$config['site_name'] 	= 'BHL-Light';
		break;
}

// Cache----------------------------------------------------------------------------------
// Local disk
$config['cache'] = dirname(__FILE__) . '/import/cache';

// External drive
//$config['cache'] = '/Volumes/Expansion/internetarchive';

// S3 storage-----------------------------------------------------------------------------
// Machine-specific, this is where the S3 store is mounted by Mountain Duck (or other app)
$config['s3'] = '/Users/rpage/Library/Application Support/Mountain Duck/Volumes.noindex/Hetzner.localized/bhl';

// Language-------------------------------------------------------------------------------
// Default language is English
$config['lang'] = 'en';

// Other databases------------------------------------------------------------------------
// SQLite database (for importing data into CouchDB)
$config['pdo'] = 'sqlite:/Users/rpage/Sites/bhl-data-new-ideas-o/bhl.db';

// Environment----------------------------------------------------------------------------
// In development this is a PHP file that is in .gitignore, when deployed these parameters
// will be set on the server
if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}

// CouchDB--------------------------------------------------------------------------------	
if ($config['platform'] == 'local')
{
	$config['couchdb_options'] = array(
		'database' 	=> 'bhl-lite',
		'host' 		=> getenv('COUCHDB_USERNAME') . ':' . getenv('COUCHDB_PASSWORD') . '@' . getenv('COUCHDB_HOST'),
		'port' 		=> getenv('COUCHDB_PORT'),
		'prefix' 	=> getenv('COUCHDB_PROTOCOL'),		
		);	
}

if ($config['platform'] == 'cloud')
{
	$config['couchdb_options'] = array(
		'database' 	=> 'bhl-lite',
		'host' 		=> getenv('COUCHDB_HOST'),
		'port' 		=> getenv('COUCHDB_PORT'),
		'prefix' 	=> getenv('COUCHDB_PROTOCOL')
		);	
}

$config['stale'] = false;

// Images---------------------------------------------------------------------------------

$config['image_source'] = 'Hetzner';
$config['use_imgproxy'] = true;
$config['image_server'] = 'https://images.bionames.org';

$config['thumbnail_height'] = 200;

// Extras---------------------------------------------------------------------------------
$config['use_hypothesis'] = false; // true enables hypothes.is to annotate IFRAME content
//$config['use_hypothesis'] = true;


// Layout---------------------------------------------------------------------------------
$config['datalab_api_key'] = getenv('DATALAB_API_KEY');
$config['datalab_tmp_dir'] = dirname(__FILE__) . '/datalab/tmp';

?>

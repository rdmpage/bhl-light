<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/couchsimple.php');

// tile request will supply x,y and z (zoom level)

$x 		= 0;
$y 		= 0;
$zoom 	= 0;

define ('TILE_SIZE', 256);
define ('MARKER_SIZE', 12);

$debug = false;
//$debug = true;


if (isset($_GET['x']))
{
	$x = (Integer)$_GET['x'];
}

if (isset($_GET['y']))
{
	$y = (Integer)$_GET['y'];
}

if (isset($_GET['z']))
{
	$zoom = (Integer)$_GET['z'];
}

$startkey = array($zoom, $x, $y);
$endkey = array($zoom, $x, $y, "zzz","zzz", "zzz", 256);
	
$url = '_design/geo/_view/tile?startkey=' . urlencode(json_encode($startkey))
	. '&endkey=' .  urlencode(json_encode($endkey))
	. '&group_level=8';
	
	
if ($config['stale'])
{
	$url .= '&stale=ok';
}	
	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$response_obj = json_decode($resp);

// Create SVG tile
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="256" height="256px">
   <style type="text/css">
      <![CDATA[     
      ]]>
   </style>
 <g>';
 
foreach ($response_obj->rows as $row)
{
	$x_pos = $row->key[3];
	$y_pos = $row->key[4];
	
	$x_pos = floor($x_pos/4) * 4;
	$y_pos = floor($y_pos/4) * 4;
	
	$radius = MARKER_SIZE / 2;
	$offset = 0;
	$xml .= '<circle id="dot" cx="' . ($x_pos - $offset) . '" cy="' . ($y_pos - $offset) . '" r="' . $radius . '" style="stroke-width:1.0;"';
	// Colours
	
	// Canadensys
	$fill 		= 'rgb(208,104,85)';
	$stroke 	= 'rgb(208,104,85)';

	$xml .= ' fill="'. $fill . '"';
	$xml .= ' stroke="rgb(128,0,64)"';
	
	$xml .= '/>';
} 
 
$xml .= '
      </g>
	</svg>';
	

// Serve up tile	
header("Content-type: image/svg+xml");
//header("Cache-control: max-age=3600");

echo $xml;

?>

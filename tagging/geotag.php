<?php

// Get text for item and add geotags to it

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(dirname(__FILE__)) . '/import/sqltojson.php');

require_once (dirname(__FILE__) . '/annotate.php');

//----------------------------------------------------------------------------------------
// We tag at the level of lines within an item (i.e., we use a "layout" object).
// Annotations are stored as arrays of arrays, where the first level is zero-based page 
// number, second level is line number, third is array of annotations on a line.
// Annotations follow simplified hypothes.is JSON (more or less).
function annotate_item($ia)
{
	global $couch;
	global $config;
	
	$item_annotations = array();
		
	// get text for every page in item
	$startkey = array($ia);
	$endkey = array($ia, new stdclass);
	
	$parameters = array(
		'startkey' 		=> json_encode($startkey),
		'endkey'		=> json_encode($endkey),
	);
	
	$url = '_design/layout/_view/text?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$resp_obj = json_decode($resp);	

	foreach ($resp_obj->rows as $row)
	{
		$text = $row->value;
		
		$lines = explode("\n", $text);
		
		$n = count($lines);
		
		for ($i = 0; $i < $n; $i++)
		{
			// geotagging
			$annotations = tag_geo($lines[$i]);
			
			if (count($annotations) > 0)
			{
				if (!isset($item_annotations[$row->key[1]]))
				{
					$item_annotations[$row->key[1]] = array();
				}
				$item_annotations[$row->key[1]][$i] = $annotations;
			}
		}		
	}
	
	//print_r($item_annotations);
	
	return $item_annotations;
}	

$titles = array(
144642, // EJT
82521,  // Bonn
190323, // The Australian Entomologist
57881,  // Amphibian & reptile conservation
);

foreach ($titles as $TitleID)
{
	$items = get_items_for_title($TitleID);

	foreach ($items as $item)
	{	
		$ia = str_replace('https://archive.org/details/', '', $item->sameAs);
		
		$doc = new stdclass;
		$doc->_id = 'geotagged/' . $ia;
		
		// identifiers to (potentially) make search results easier to form
		$doc->internetarchive = $ia;
		
		// to make search results easier
		$doc->name = $item->name;
		$doc->bhl_id = $item->_id;
		
		$doc->annotations = annotate_item($ia);
		
		// store in CouchDB
		$force_upload = true;
		//$force_upload = false;
			
		$exists = $couch->exists($doc->_id);
		
		if ($exists && !$force_upload)
		{
			echo "Have " . $doc->_id . " already!\n";
		}
		else
		{
			if ($exists && $force_upload)
			{
				$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
			}
		
			$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
			var_dump($resp);	
		}
	}
}

?>

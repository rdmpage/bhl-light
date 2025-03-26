<?php

// API to retrieve JSON-LD and other JSON from CouchDB

ini_set('memory_limit', '-1');

require_once (dirname(__FILE__) . '/couchsimple.php');

//----------------------------------------------------------------------------------------
// 
function get_layout($id)
{
	global $config;
	global $couch;
	
	$layout = null;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($id));

	$resp_obj = json_decode($resp);	
	
	if (!isset($resp_obj->error))
	{
		$layout = $resp_obj;
	}
		
	return $layout;
}

//----------------------------------------------------------------------------------------
// 
function get_parts_for_item($id)
{
	global $config;
	global $couch;
	
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->dataFeedElement = array(); 
		
	$key = '"' . 'item/' . $id . '"';

	$url = '_design/item/_view/parts?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	// print_r($resp_obj);
	
	foreach ($resp_obj->rows as $row)
	{
		$datafeed->dataFeedElement[] = $row->value;
	}
	
	// sort by position
	usort($datafeed->dataFeedElement, function($a, $b) {
      return $a->position - $b->position;
    });
	
	return $datafeed;
}

//----------------------------------------------------------------------------------------
// Return an array comprising the item and a list of its items 
function get_item($id)
{
	global $config;
	global $couch;
	
	$graph = array();
	
	// item	
	$key = '"' . 'item/' . $id . '"';

	$url = '_design/item/_view/about?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
		
	$work = $resp_obj->rows[0]->value;
	
	// clean
	if (isset($work->_rev))
	{
		unset($work->_rev);
	}
	
	// add type
	$work->{'@type'} = array('CreativeWork');
	
	$graph[] = $work;
	
	// maybe other things here...?
	
	$graph[] = get_parts_for_item($id);
	
	return $graph;
}

//----------------------------------------------------------------------------------------
// Return an array comprising the title and a list of its items 
function get_title($id)
{
	global $config;
	global $couch;
	
	$graph = array();
	
	// title	
	$key = '"' . 'bibliography/' . $id . '"';

	$url = '_design/title/_view/about?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
		
	$work = $resp_obj->rows[0]->value;
	
	// clean
	if (isset($work->_rev))
	{
		unset($work->_rev);
	}
	
	// add type	
	$work->{'@type'} = array('CreativeWork');
	
	$graph[] = $work;
	
	// list of items
		         
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->dataFeedElement = array(); 
		
	$key = '"' . 'bibliography/' . $id . '"';

	$url = '_design/title/_view/items?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	// print_r($resp_obj);
	
	foreach ($resp_obj->rows as $row)
	{
		$datafeed->dataFeedElement[] = $row->value;
	}
	
	// sort by position
	usort($datafeed->dataFeedElement, function($a, $b) {
      return $a->position - $b->position;
    });
	
	$graph[] = $datafeed;
	
	return $graph;
}

//----------------------------------------------------------------------------------------
// Get array of starting letters for titles
function get_title_letters()
{
	global $config;
	global $couch;
	
	$letters = array();
	
	$parameters = array(
		'reduce' 		=> 'true',
		'group_level' 	=> 1,
	);	

	$url = '_design/title/_view/letter?' . http_build_query($parameters);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	//print_r($resp_obj);
	
	foreach ($resp_obj->rows as $row)
	{
		$letters[$row->key[0]] = $row->value;
	}
	
	return $letters;
}

//----------------------------------------------------------------------------------------
// Return an array comprising list of titles that start with this letter
function get_titles_for_letter($letter = 'A')
{
	global $config;
	global $couch;
	
	get_title_letters();
	
	$startkey = array($letter);
	$endkey = array($letter, new stdclass);
	
	$parameters = array(
		'startkey' 		=> json_encode($startkey),
		'endkey'		=> json_encode($endkey),
		'reduce' 		=> 'true',
		'group_level' 	=> 3,
	);	

	$url = '_design/title/_view/letter?' . http_build_query($parameters);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	// print_r($resp_obj);
		
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->dataFeedElement = array(); 
	
	foreach ($resp_obj->rows as $row)
	{
		$item = new stdclass;
		$item->{'@id'} = $row->key[2];
		$item->name = $row->key[1];
	
		$datafeed->dataFeedElement[] = $item;
	}
	
	return $datafeed;
}

//----------------------------------------------------------------------------------------
// For a given BHL PageID return relative URL to image in Internet Archive so we can
// retrieve image from S3 store
function get_page_image_url_ia($PageID, $extension = 'webp')
{
	global $config;
	global $couch;
	
	$image_url = '';

	$url = '_design/page/_view/internetarchive?key=' . $PageID;
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	if (count($resp_obj->rows) == 1)
	{
		$image_url = $resp_obj->rows[0]->value;
		
		$image_url = preg_replace('/_\d+$/', '', $image_url) . '_jp2/' . $image_url . '.' . $extension;
	}
	
	return $image_url;
}

//----------------------------------------------------------------------------------------
function get_search_results($query, $limit = 10)
{
	global $config;
	global $couch;
	
	$query = trim($query);
	
	$query = preg_replace('/\s\s+/', ' ', $query);
	$query_parts = explode(' ', $query);
	
	foreach ($query_parts as &$part)
	{
		$part = $part; // consider adding "~" suffix for fuzzy matching
	}
	
	$q = join(' AND ', $query_parts);
	
	$url = '_design/search/_nouveau/full-text?q=' . urlencode($q);
	
	$url .= '&limit=' . $limit;
	$url .= '&include_docs=true';
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	// print_r($resp_obj);
	
	// make RDF-like
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->name = $query;
    $datafeed->dataFeedElement = array(); 
    
    if ($resp_obj->total_hits == 0)
    {
    	$datafeed->description = "No results";
    }
    else
    {
    	if ($resp_obj->total_hits == 1)
    	{
    		$datafeed->description = "One hit";
    	}
    	else
    	{
    		$datafeed->description = $resp_obj->total_hits . " hits";
    	}
    }
	
	foreach ($resp_obj->hits as $hit)
	{
		$item = new stdclass;
		$item->{'@id'} = $hit->id;
		$item->name = $hit->doc->name;
		
		if (isset($hit->doc->thumbnailUrl))
		{
			$item->thumbnailUrl = $hit->doc->thumbnailUrl;
			
			$item->url = preg_replace('/pagethumb\//', 'page/', $hit->doc->thumbnailUrl);
		}
		
		$item->resultScore = $hit->order[0]->value;
	
		$datafeed->dataFeedElement[] = $item;
	}
	
	// print_r($datafeed);
	
	return $datafeed;
}

//----------------------------------------------------------------------------------------
// For a given BHL PageID find array of ItemID and page offset (zero based)
function get_page($PageID)
{
	global $config;
	global $couch;
	
	$target = array();
	
	$url = '_design/page/_view/itemID-page?key=' . $PageID;
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	if (count($resp_obj->rows) == 1)
	{
		$target = $resp_obj->rows[0]->value;
	}
	
	
	return $target;
}

//----------------------------------------------------------------------------------------
// Get geotagging for Internet Archive item
function get_geo_annotations($ia)
{
	global $config;
	global $couch;
	
	$annotations = null;
	
	$key = 'geotagged/' . $ia;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($key));

	$resp_obj = json_decode($resp);	
	
	if (!isset($resp_obj->error))
	{
		$annotations = $resp_obj->annotations;
	}
		
	return $annotations;
}


/*
$g = get_titles_for_letter('A');
print_r($g);
*/

// get_page_image_url_ia(43091138);

/*
get_search_results('new species');
get_search_results('Solomon Island');
get_search_results('Papilio demoleus');
get_search_results('replacement name');
*/

?>

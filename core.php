<?php

// API to retrieve JSON-LD and other JSON from CouchDB

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

/*
$g = get_titles_for_letter('A');
print_r($g);
*/

?>

<?php

// API to retrieve JSON-LD and other JSON from CouchDB

require_once (dirname(__FILE__) . '/couchsimple.php');

//----------------------------------------------------------------------------------------
// Return an array comprising the title and a list of its items 
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
	$work->{'@type'} = array('CreativeWork');
	
	$graph[] = $work;
	
	// maybe other things here...?
	
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
	$work->{'@type'} = array('CreativeWork');
	
	$graph[] = $work;
	
	// list of items
		         
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->dataFeedElement = array(); 
		
	$key = '"' . 'bibliography/' . $id . '"';

	$url = '_design/title/_view/item-list?key=' . urlencode($key);
		
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
// Return an array comprising the title and a list of its items 
function get_titles_for_letter($letter = 'A')
{
	global $config;
	global $couch;
	
	$graph = array();
	
	// title	
	$key = '"' . $letter . '"';

	$url = '_design/title/_view/letter?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
		
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->dataFeedElement = array(); 
	
	foreach ($resp_obj->rows as $row)
	{
		$item = new stdclass;
		$item->{'@id'} = $row->id;
		$item->name = $row->value;
	
		$datafeed->dataFeedElement[] = $item;
	}
	
	return $datafeed;
}

/*
$g = get_titles_for_letter('A');
print_r($g);
*/

?>

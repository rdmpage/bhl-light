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
function get_blocks($id)
{
	global $config;
	global $couch;
	
	$blocks = null;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($id));

	$resp_obj = json_decode($resp);	
	
	if (!isset($resp_obj->error))
	{
		$blocks = $resp_obj;
	}
		
	return $blocks;
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
// Get figure and picture blocks for an item 
// This requires that we have processed the images using, say, AI, to find the figures
// Use https://www.w3.org/TR/annotation-model/#annotation-page to store the list of
// figures. This approximates IIIF if we ever go down that road.
// Because we may have multiple types of annotation pages, we co-opt schema:ImageGallery
// to flag that these are images
function get_figures_for_ia($ia)
{
	global $config;
	global $couch;
	
    $annotationPage = new stdclass;
    $annotationPage->{'@type'} = ['AnnotationPage', 'ImageGallery'];
    $annotationPage->items = array(); 
		
	$key = '"' . 'blocks/' . $ia . '"';

	$url = '_design/blocks/_view/figures?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	if (count($resp_obj->rows) == 1)
	{
		$annotationPage->items = $resp_obj->rows[0]->value;
	}
	
	return $annotationPage;
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
	
	//------------------------------------------------------------------------------------
	// maybe other things here...?
	
	// parts
	$graph[] = get_parts_for_item($id);
	
	// figures
	if (preg_match('/archive.org\/details\/(.*)/', $work->sameAs, $m))
	{		
		$internet_archive = $m[1];
		$graph[] = get_figures_for_ia($internet_archive);
	}	
	
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
    
    // get coverage...
    $key = '"' . 'bibliography/' . $id . '"';
	$url = '_design/item/_view/coverage?key=' . urlencode($key);
		
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	$coverage = array();
	
	foreach ($resp_obj->rows as $row)
	{
		$coverage[$row->id] = $row->value[1];
	}
	
	foreach ($datafeed->dataFeedElement as &$item)
	{
		if (isset($coverage[$item->{'@id'}]))
		{
			$item->coverage = $coverage[$item->{'@id'}]; // treat this data as just an array, will need something more elegant
		}
	}
	
	//print_r($datafeed);
	//print_r($coverage);
	
	// add to 
    
	
	
	// add to the graph
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
// Given a BHL PageID return a URL to the thumbnail of the page image. Uses S3 storage,
// falls back to BHL API if Internet Archive id not found.
function get_page_image_url($PageID, $is_thumbnail = false)
{
	global $config;
	
	$image_url = get_page_image_url_ia($PageID);
	
	if ($image_url != '')
	{
		$image_url = 'https://hel1.your-objectstorage.com/bhl/' . $image_url;
	}
	else
	{
		// fallback to BHL
		$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' . $PageID;
	}
	
	if ($is_thumbnail)
	{
		$image_url = 'https://images.bionames.org' . imgproxy_path_resize($image_url, 0, $config['thumbnail_height']);
	}
	else
	{
		$image_url = 'https://images.bionames.org' . imgproxy_path_resize($image_url, 700, 0);
	}
	
	return $image_url;
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
// CouchDB fulltext search
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
		else
		{
			// things like titles won't/might not have thumbnails
			$item->url = $hit->doc->_id;
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

//----------------------------------------------------------------------------------------
// Get geotagging for Internet Archive item
function get_name_annotations($ia)
{
	global $config;
	global $couch;
	
	$annotations = null;
	
	$key = 'nametagged/' . $ia;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($key));

	$resp_obj = json_decode($resp);	
	
	if (!isset($resp_obj->error))
	{
		$annotations = $resp_obj->annotations;
	}
		
	return $annotations;
}

//----------------------------------------------------------------------------------------
// Search for taxonomic name
function get_name_search_results($query, $limit = 10)
{
	global $config;
	global $couch;
	
	$query = strtolower(trim($query));
	
	$startkey = array($query);
	$endkey = array($query . "\u{FFFF}");
	$endkey = array($query . " ");
	
	$parameters = array(
		'startkey' 		=> json_encode($startkey),
		'endkey'		=> json_encode($endkey),
		'reduce' 		=> 'false'
	);	

	$url = '_design/name/_view/name-search-context?' . http_build_query($parameters);
		
	if ($config['stale'])
	{
		//$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
		
    $datafeed = new stdclass;
    $datafeed->{'@type'} = ['DataFeed'];
    $datafeed->name = $query;
    $datafeed->dataFeedElement = array(); 
	
	foreach ($resp_obj->rows as $row)
	{
		$item = new stdclass;
		$item->{'@type'} = 'Annotation';
		
		$item->name = $row->key[0];

		$item->selector = $row->key[3];

		$item->source = new stdclass;
		$item->source->{'@id'} = "page/" . $row->key[2];
		
		$parent = new stdclass;
		$parent->{'@id'} = 'item/' . $row->key[1];	
		
		$parent->datePublished = $row->key[4];		
		
		$item->source->isPartOf = array($parent);
		
		$datafeed->dataFeedElement[] = $item;
	}
	
	//print_r($datafeed);
	
	return $datafeed;
	
}

//----------------------------------------------------------------------------------------
// Get IIIF manifest for item
// Think about eventually moving this to CouchDB view
function get_item_manifest($id)
{
	global $config;
	
	$manifest = null;
	
	$doc = get_item($id);
	
	if ($doc)
	{
		//$layout_id = 'layout/' . $id;

		//$doc = get_layout($layout_id);
	
		// print_r($doc);
		
		// Unpack JSON-LD
		$work = null;
		foreach ($doc as $graph)
		{
			if (in_array('CreativeWork', $graph->{'@type'}))
			{
				$work = $graph;
			}	
		}
		if ($work)
		{
			// Internet Archive id (barcode)
			$internet_archive = '';
			
			if (preg_match('/archive.org\/details\/(.*)/', $work->sameAs, $m))
			{		
				$internet_archive = $m[1];
			}
			
			if ($internet_archive != '')
			{
				$layout = get_layout('layout/' . $internet_archive);
				
				// print_r($layout);
				
				$manifest = new stdclass;
				
				$manifest->{'@context'} = 'http://iiif.io/api/presentation/3/context.json';
				$manifest->id = $config['web_server'] . $config['web_root'] . 'item/' . $id . '-manifest.json';
				$manifest->type = 'Manifest';
				
				$label = new stdclass;
				$label->en = [$work->name];
				$manifest->label = $label;
				
				$manifest->items = array();
				
				foreach ($layout->pages as $page)
				{
					$canvas = new stdclass;
					$canvas->id = $config['web_server'] . $config['web_root'] . $id . '/canvas/p' . $page->page;
					$canvas->type = 'Canvas';
					$canvas->height = $page->image_bbox[3];
					$canvas->width = $page->image_bbox[2];
					
					$canvas->items = array();
					
					$item = new stdclass;
					$item->id = $canvas->id . '/1';
					$item->type ='AnnotationPage';
					$item->items = array();
					
					$annotation = new stdclass;
					$annotation->id = $config['web_server'] . $config['web_root'] . 'page/' . $page->bhl_pageid;
			
					$annotation->type = 'Annotation';
					$annotation->motivation = 'painting';
					
					$annotation->body = new stdclass;
					$annotation->body->id = $config['web_server'] . $config['web_root'] . 'pageimage/' . $page->bhl_pageid;
					$annotation->body->type = 'Image';
					$annotation->body->format = 'image/webp';
					$annotation->body->height = $page->image_bbox[3];
					$annotation->body->width = $page->image_bbox[2];
	
					if (1)
					{
						$annotation->body->service = array();
						
						$service = new stdclass;
						$service->id = $config['web_server'] . $config['web_root'] . 'page/' . $page->bhl_pageid;
						$service->type = 'ImageService2';
						$service->profile = 'http://iiif.io/api/image/2/level0.json';
						
						$annotation->body->service[] = $service;
					}

					$annotation->target = $canvas->id;
					
					$item->items[] = $annotation;
					
					$canvas->items[] = $item;
					
					$manifest->items[] = $canvas;
				}
				
				
			}
		}
	}
	
	return $manifest;
}

//----------------------------------------------------------------------------------------
// For a given BHL PageID return array [width,height]
function get_page_width_height($PageID)
{
	global $config;
	global $couch;
	
	$wh = array();
	
	$url = '_design/page/_view/width-height?key=' . $PageID;
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	if (count($resp_obj->rows) == 1)
	{
		$wh = $resp_obj->rows[0]->value;
	}
	
	
	return $wh;
}

//----------------------------------------------------------------------------------------
// For a given BHL PageID get OCR text
function get_page_text($PageID)
{
	global $config;
	global $couch;
	
	$text = '';
	
	$url = '_design/text/_view/pageid?key=' . $PageID;
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}			
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

	$resp_obj = json_decode($resp);	
	
	if (count($resp_obj->rows) == 1)
	{
		$text = $resp_obj->rows[0]->value;
	}
	
	
	return $text;
}


/*
$id = 331959;
get_item_manifest($id);
*/



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

//get_name_search_results('Rhyothemis princeps');

//get_title(190323);



?>

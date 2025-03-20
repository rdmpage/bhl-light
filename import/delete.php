<?php

// Delete unwanted titles from CouchDB (for example if we make a msitake, or
// can't be bothered downlading masses of IA files)

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

//----------------------------------------------------------------------------------------
function delete_list($ids)
{
	global $couch;
	
	foreach ($ids as $id)
	{
		$exists = $couch->exists($id);
		
		if ($exists)
		{
			echo "Delete $id\n";
			$couch->add_update_or_delete_document(null, $id, 'delete');
		}
	}
}

//----------------------------------------------------------------------------------------



$TitleID = 38931; // Americna Museum Novitates
$TitleID = 48608; // Deutsche entomologische Zeitschrift
$TitleID = 85187; // Zeitschrift für Säugetierkunde : im Auftrage der Deutschen Gesellschaft für Säugetierkunde e.V
$TitleID = 730; // JPEG2000 are in tar archive, not tested yet
$TitleID = 65344; // Madroño

// items

$items = array();
$parts = array();
$layouts = array();
		
$key = '"' . 'bibliography/' . $TitleID . '"';

$url = '_design/housekeeping/_view/title-items?key=' . urlencode($key);

$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$resp_obj = json_decode($resp);	

//print_r($resp_obj);

foreach ($resp_obj->rows as $row)
{
	$items[] = $row->value;
}

foreach ($items as $item)
{	
	// parts
	$key = '"' . $item . '"';
	
	$url = '_design/housekeeping/_view/item-parts?key=' . urlencode($key);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$resp_obj = json_decode($resp);	
	
	foreach ($resp_obj->rows as $row)
	{
		$parts[] = $row->value;
	}	
}

foreach ($items as $item)
{	
	// layout
	$key = '"' . $item . '"';
	
	$url = '_design/housekeeping/_view/item-layout?key=' . urlencode($key);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$resp_obj = json_decode($resp);	
	
	foreach ($resp_obj->rows as $row)
	{
		$layouts[] = $row->value;
	}
	
}


print_r($items);
print_r($parts);
print_r($layouts);

// delete
delete_list($layouts);
delete_list($parts);
delete_list($items);

$titles = array('bibliography/' . $TitleID);
delete_list($titles);





?>

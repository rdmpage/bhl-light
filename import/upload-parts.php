<?php

// Extract data from SQLite and import into CouchDB

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/sqltojson.php');


//----------------------------------------------------------------------------------------
// Upload an object
function upload($doc, $force = false)
{
	global $config;
	global $couch;
	
	// debug
	if (!isset($doc->_id))
	{
		print_r($doc);
		exit();
	}

	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_part($PartID, $force = false)
{
	global $config;
	global $couch;
	
	$doc = get_part($PartID);
	
	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have $PartID " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
function upload_parts_for_item($ItemID, $force = false)
{
	$parts = get_parts_for_item($ItemID);
	
	foreach ($parts as $part)
	{
		upload($part, $force);
	}
}

//----------------------------------------------------------------------------------------
function upload_parts_for_title($TitleID, $force = false)
{
	// list of items for a title
	$items = get_items_for_title($TitleID);
	
	foreach ($items as $item)
	{
		$ItemID = str_replace('item/', '', $item->_id);
		echo $ItemID . "\n";
		upload_parts_for_item($ItemID, $force);
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_title($TitleID, $force = false)
{
	global $config;
	global $couch;
	
	$doc = get_title($TitleID);
	
	print_r($doc);
	
	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have TitleID " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_items_for_title($TitleID, $force = false)
{
	global $config;
	global $couch;
	
	$items = get_items_for_title($TitleID);
	

	foreach ($items as $doc)
	{
		$exists = $couch->exists($doc->_id);
		
		if ($exists && !$force)
		{
			echo "Have ItemID " . $doc->_id . " already!\n";
		}
		else
		{
			if ($exists && $force)
			{
				$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
			}

			$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
			var_dump($resp);	
		}
	}
}

$titles=array(

11516,
);


$force = true;

foreach ($titles as $TitleID)
{
	echo $TitleID . "\n";
	upload_parts_for_title($TitleID, true);
}


?>

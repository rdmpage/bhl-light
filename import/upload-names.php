<?php

// Extract data from SQLite and import into CouchDB

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/sqltojson.php');
require_once (dirname(__FILE__) . '/trie.php');

//----------------------------------------------------------------------------------------
// Upload an object
function upload($doc, $force = false)
{
	global $config;
	global $couch;

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
function get_names_for_item($ItemID)
{
	$sql = 'SELECT DISTINCT name_page.PageID, page.SequenceOrder, name.name FROM name_page
	INNER JOIN page USING(PageID)
	INNER JOIN name USING(id)
	WHERE ItemID=' . $ItemID . '
	ORDER BY CAST(SequenceOrder AS INTEGER);';
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$names = array();
	
	foreach ($data as $row)
	{
		if (!isset($names[$row->SequenceOrder-1]))
		{
			$names[$row->SequenceOrder-1] = new stdclass;
			$names[$row->SequenceOrder-1]->PageID = $row->PageID;
			$names[$row->SequenceOrder-1]->SequenceOrder = $row->SequenceOrder;
			$names[$row->SequenceOrder-1]->names = array();			
		}
		$names[$row->SequenceOrder-1]->names[] = $row->name;
	}
	
	return $names;
}

//----------------------------------------------------------------------------------------
// tag an entity in the trie
function tag_trie($trie, $text)
{
	$hits  = $trie->flash($text);
	return $hits;
}

//----------------------------------------------------------------------------------------

// Upload names for a given Item
$ItemID = 137691;
$internetarchive = get_barcode_from_item($ItemID);

// layout (should get this via CouchDB)
$json = file_get_contents($internetarchive . '.json');

$layout = json_decode($json);

// Get names from SQLite
$page_names = get_names_for_item($ItemID);

print_r($page_names);

exit();

// OK convert into annotations

$annotations = array();

foreach ($page_names as $page_index => $name_list)
{
	// we will use a simple compound key to locate annotations

	// Create a trie for list of names found on this page
	$trie = new Trie();
	foreach ($name_list->names as $name)
	{
		$term_obj = new stdclass;
		$term_obj->name = $name;
		$trie->add($term_obj);
	}
	
	// Get lines of text from layout
	$lines = array();
	foreach ($layout->pages[$page_index]->text_lines as $line)
	{
		$lines[] = $line->text;
	}	
	
	// Locate names in text
	
	$page_annotations = array();
	
	// Find names line-by-line (obviously will fail if name spans more than one line...)
	$n = count($lines);
	for ($i = 0; $i < $n; $i++)
	{
		$hits = tag_trie($trie, $lines[$i]);
		
		if (count($hits) > 0)
		{
			$page_annotations[$i] = $hits;
		}
		
	}
	
	if (count($page_annotations) > 0)
	{
		$annotations[$page_index] = $page_annotations;
	}
	
	

}


print_r($annotations);

//echo json_encode($annotations);

// apply to layout to get actual annotations

/*



$doc = new stdclass;
$doc->_id = 'annotations/' . $internetarchive;
$doc->internetarchive = $internetarchive;
$doc->ItemID = $ItemID;
*/



?>


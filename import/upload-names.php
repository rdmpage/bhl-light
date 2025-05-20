<?php

// Extract names from SQLite and import into CouchDB as annotations

ini_set('memory_limit', '-1');

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
	
	$names = new stdclass;
	
	foreach ($data as $row)
	{
		$page_zero_based = $row->SequenceOrder-1;
		if (!isset($names->{$page_zero_based}))
		{
			$names->{$page_zero_based} = new stdclass;
			$names->{$page_zero_based}->PageID = $row->PageID;
			$names->{$page_zero_based}->SequenceOrder = $row->SequenceOrder;
			$names->{$page_zero_based}->names = array();			
		}
		$names->{$page_zero_based}->names[] = $row->name;
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
$ItemID = 262715;
//$ItemID = 188012;
//$ItemID = 183054;

$ItemID = 137691;

$titles = array(209695);

$titles = array(206514); // Contributions


// 10088 Tijdschrift voor entomologie this kills the CouchDB views for names

$titles = array(10088); // Tijdschrift voor entomologie
//$titles = array(190323); // The Australian Entomologist
//$titles = array(68619); // Insects of Samoa and other Samoan terrestrial arthropoda

$titles = array(7414); // The journal of the Bombay Natural History Society

$mode = 'delete';
$mode = 'add';

foreach ($titles as $TitleID)
{
	$items = get_items_for_title($TitleID);
	
	// debugging, just get one item
	if (0)
	{
		$items = array(get_item(310489));
	}
	
	foreach ($items as $item)
	{
	
		$ItemID = str_replace('item/', '', $item->_id);
		
		/*
		if ($ItemID != 138618)
		{
			continue;
			
		}
		*/
		
		$ia = str_replace('https://archive.org/details/', '', $item->sameAs);
		
		if ($mode == 'delete')
		{
			$doc = new stdclass;
			$doc->_id = 'nametagged/' . $ia;
						
			$resp = $couch->add_update_or_delete_document(null, $doc->_id, 'delete');			
			var_dump($resp);
		}
		else
		{
			// add
			
			// get layout from CouchDB
			$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode("layout/" . $ia));
			$layout = json_decode($resp);	
			
			// Get names from SQLite
			$page_names = get_names_for_item($ItemID);
			
			// names on each page 
			//print_r($page_names);
			
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
				
				$page_annotations = new stdclass;
				
				// Find names line-by-line (obviously will fail if name spans more than one line...)
				$n = count($lines);
				for ($line_number = 0; $line_number < $n; $line_number++)
				{
					$text = $lines[$line_number];
					$text_encoding = mb_detect_encoding($text);
				
					$hits = tag_trie($trie, $text);
					
					if (count($hits) > 0)
					{
						$page_annotations->{$line_number} = array();
						
						$flanking_length = 32;
						
						foreach ($hits as $hit)
						{		
							$annotation = new stdclass;
							$annotation->text = $hit->text;
														
							$annotation->target = new stdclass;
							$annotation->target->selector = array();
							
							// position in text
							$selector = new stdclass;
							$selector->type = "TextPositionSelector";
							$selector->start = (Integer)$hit->start;
							$selector->end = (Integer)$hit->end;
							
							$annotation->target->selector[] = $selector;
							
							// text loc
							$selector = new stdclass;
							$selector->type = 'TextQuoteSelector';
							
							// text that we have matched in the OCR
							$selector->exact = mb_substr($text, $hit->start, $hit->end - $hit->start, $text_encoding);
							
							$pre_length = min($hit->start, $flanking_length);
							$pre_start = $hit->start - $pre_length;	
							$selector->prefix = mb_substr($text, $pre_start, $pre_length, $text_encoding); 
							
							$post_length = 	min(mb_strlen($text, mb_detect_encoding($text)) - $hit->end, $flanking_length);					
							$selector->suffix = mb_substr($text, $hit->end, $post_length, $text_encoding);
							
							$annotation->target->selector[] = $selector;
			
							$page_annotations->{$line_number}[] = $annotation;
						}
					}
					
				}
				
				if (count((array)$page_annotations) > 0)
				{
					$page_names->{$page_index}->annotations = $page_annotations;
				}
			
			}
			//print_r($page_names);
			
			//echo json_encode($page_names);
			
			$doc = new stdclass;
			$doc->_id = 'nametagged/' . $ia;
			
			// identifiers to (potentially) make search results easier to form
			$doc->internetarchive = $ia;
			
			// to make search results easier
			$doc->bhl_id = $ItemID;
			if (isset($item->datePublished))
			{
				$doc->datePublished = $item->datePublished;
			}
			
			print_r($doc);
			
			$doc->annotations = $page_names;
			
			//echo json_encode($doc);
			
			
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
}


?>

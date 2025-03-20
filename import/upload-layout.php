<?php

// Upload a layout document to CouchDB

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(__FILE__) . '/ia.php');
require_once (dirname(__FILE__) . '/djvu-to-datalab.php');
require_once (dirname(__FILE__) . '/hocr-to-datalab.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

/*
$identifiers = get_ia_for_title(68619);

$identifiers = get_ia_for_title(211788);

$identifiers = get_ia_for_title(10229);

$identifiers = get_ia_for_title(5943);

$identifiers = get_ia_for_title(206514);

$identifiers = get_ia_for_title(57881);

print_r($identifiers);

//$identifiers = array('Amphibianreptil5A');
*/

$TitleID = 144642; // European Journal of Taxonomy
$TitleID = 68619;
$TitleID = 212146; // The Zoogoer
$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso
$TitleID = 82521; // Bonn
$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle
$TitleID = 211788;
$TitleID = 82521;
$TitleID = 10229;
$TitleID = 209695;
$TitleID = 10088;
$TitleID = 85187;

$titles = array(
/*10088,
//10229,
105698,
119522,
119879,
139317,
144642,*/
147681,
150137,
152899,
158870,
162187,
169356,
190323,
204608,
206514,
209695,
210747,
211788,
//212146,
2804,
38931,
44792,
48608,
49914,
57881,
//5943,
62642,
65344,
//68619,
730,
7414,
82521,
85187,
95451,
);


$titles = array(
//204608,
//144642, // EJT
//152899, // Valp
//82521, // Bonn

//211788,
//150137,
//158870, // Forktail
//5943, // Paris
//119522,
//162187,
//105698,
//730,
//150137
//7414,
//119879
//162187,
//150137,
//147681,
//169356,

//150137, // badness
//210747,
//95451,
//62642,
5943
);




foreach ($titles as $TitleID)
{
	
	$identifiers = get_ia_for_title($TitleID);
	
	foreach ($identifiers as $ia)
	{
		echo "Fetching hOCR $ia\n";
		fetch_ia_hocr($ia);
		
		echo "Converting hOCR to layout $ia\n";
		$doc = hocr_to_datalab($ia);
		
		if (!$doc)
		{
			echo "Fetching DjVu $ia\n";
			fetch_ia_djvu($ia);
		
			echo "Converting DjVu to layout $ia\n";
			$doc = djvu_to_datalab($ia);	
		}
		
		if ($doc)
		{	
			$ItemID = get_item_from_barcode($ia);
				
			if ($ItemID != 0)
			{
				$doc->bhl_item = (Integer)$ItemID;
			
				$pages = get_pages_for_item($ItemID);
				
				for ($i = 0; $i < $doc->page_count; $i++)
				{
					$doc->pages[$i]->bhl_pageid = (Integer)str_replace('page/', '', $pages[$i]->{'@id'});
				
					if (isset($pages[$i]->name))
					{
						$doc->pages[$i]->label = $pages[$i]->name;
					}
				}
			}
		
			echo "Uploading $ia\n";
		
			// upload to CouchDB
			if (1)
			{
				$force_upload = true;
				//$force_upload = false;
			
				$doc->_id = 'layout/' . $doc->internetarchive;
				
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
}

?>

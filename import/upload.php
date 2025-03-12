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
function upload_parts_for_title($TitleID)
{
	// list of items for a title
	$sql = 'SELECT ItemID FROM item 
	WHERE TitleID='. $TitleID ;

	foreach ($data as $row)
	{
		upload_parts_for_item($row->ItemID);
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_title($TitleID, $force = false)
{
	global $config;
	global $couch;
	
	$doc = get_title($TitleID);
	
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

$TitleID = 206514;
//$TitleID = 210747; // Mycotaxon
//$TitleID = 212146; // The Zoogoer
$TitleID = 68619;
$TitleID = 7414; // The journal of the Bombay Natural History Society
$TitleID = 147681;
$TitleID = 105698; // The birds of Australia
$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso

$TitleID = 65344; // Madroño; a West American journal of botany
$TitleID = 211788;
$TitleID = 169356; // Austrobaileya: A Journal of Plant Systematics

$TitleID = 119522;

$TitleID = 139317; // Biodiversity journal
$TitleID = 48608; // Deutsche entomologische Zeitschrift
$TitleID = 2804;// Asiatic herpetological research
$TitleID = 204608; // Alytes: International Journal of Batrachology
$TitleID = 62642; // Bulletin of the Natural History Museum. Zoology series
$TitleID = 119879; // Flora of Southern Africa
$TitleID = 38931; // American Museum novitates
$TitleID = 95451; // Brasil 1979, 0
//$TitleID = 44792; // Anales de la Sociedad Científica Argentina

$TitleID = 730; // Biologia Centrali-Americana
$TitleID = 150137; // Biodiversity, biogeography and nature conservation in Wallacea and New Guinea
$TitleID = 49914; // Iberus : revista de la Sociedad Española de Malacología

$TitleID = 144642; // European Journal of Taxonomy

$TitleID = 158870; //Forktail
$TitleID = 57881;// Amphibian & reptile conservation
$TitleID = 85187;
$TitleID = 82521;
$TitleID = 190323;
$TitleID = 162187;

http://localhost/bhl-light/bibliography/206514

$TitleID = 206514; // Contributions of the American Entomological Institute

//$TitleID = 209695; // Sibling species of Trigona from Angola (Hymenoptera, Apinae)
//$TitleID = 10088; // Tijdschrift voor entomologie

//$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso

$TitleID = 57881;// Amphibian & reptile conservation

$TitleID = 82521; // Bonn zoological bulletin

if (1)
{
	upload_title($TitleID, true);
	upload_items_for_title($TitleID, true);
}



exit();

//$PartID = 313402;
$PartID = 153428;

/*

-- parts in item (so we can display a list)
SELECT * FROM part 
WHERE ItemID=333950
ORDER BY CAST(SequenceOrder AS INTEGER);

-- pages in parts (so we can display a part)
SELECT part.PartID, partpage.PageID, partpage.SequenceOrder FROM part 
INNER JOIN partpage USING(PartID)
WHERE part.ItemID=333950
ORDER BY PartID, CAST(partpage.SequenceOrder AS INTEGER);
*/



// 

// get_part($PartID);

//$PartID = 385872;
//upload_part($PartID , true);

// 329874
// 328428

//$parts = upload_parts_for_item(328428, true);
//print_r($parts);

//upload_parts_for_item(186987, true);

//get_pages_for_item(328428);



/*

// get additional info for one item

$ItemID = 188389;

$sql = 'SELECT COUNT(DISTINCT PageID) AS count FROM page 
WHERE ItemID=' . $ItemID;

$data = db_get($sql);

print_r($data);

// get list of pages in item

// get list of parts in item

// get coverage of parts in item
*/





/*
      (
            [ItemID] => 336333
            [TitleID] => 7414
            [ThumbnailPageID] => 63807908
            [BarCode] => journalbombayna112bomba
            [VolumeInfo] => v.112:no.2 (2015:Aug.)
            [Year] => 2015
            [InstitutionName] => Smithsonian Libraries and Archives
            [CopyrightStatus] => In copyright. Digitized with the permission of the rights holder.
            [RightsStatement] => http://biodiversitylibrary.org/permissions
        )

  

print_r($obj);


                "@id": "https://www.biodiversitylibrary.org/item/114379",
                "_id": 114379,
                "thumbnailUrl": "https://www.biodiversitylibrary.org/pagethumb/37177107",
                "name": "v.1=no.1-12 (1832)",
                "provider": "Natural History Museum Library, London",
                "sponsor": "Natural History Museum Library, London",
                "copyrightNotice": "Public domain. The BHL considers that this work is no longer under copyright protection.",
                "isPartOf": [
                    "https://www.biodiversitylibrary.org/bibliography/51678"
                ],
                "numberOfPages": 628
 
*/

/*
// pages for an item
$sql = 'SELECT * FROM page 
WHERE ItemID=188389
ORDER BY CAST(SequenceOrder AS INTEGER)';

$data = db_get($sql);
*/

//print_r($data);

// names on a page


// layout


// creators



//


?>

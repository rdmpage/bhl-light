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

$TitleID = 206514; // Contributions of the American Entomological Institute

//$TitleID = 209695; // Sibling species of Trigona from Angola (Hymenoptera, Apinae)
//$TitleID = 10088; // Tijdschrift voor entomologie

//$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso

$TitleID = 57881;// Amphibian & reptile conservation

$TitleID = 82521; // Bonn zoological bulletin
$TitleID = 2804;// Asiatic herpetological research

$TitleID = 68619; // insectsofsamoa

$TitleID = 211788;

$TitleID = 10229;

$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle

$TitleID = 206514; // Contributions of the American Entomological Institute

$TitleID = 211788; // Metamorphosis Australia (no parts?)

$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle

$TitleID = 57881;

$TitleID = 144642; // European Journal of Taxonomy

$TitleID = 212146;

$TitleID = 204608;

$TitleID = 190323;

$TitleID = 158870;

$TitleID = 10088;
$TitleID = 162187;

$TitleID = 169356;
//$TitleID = 7414;
$TitleID = 210747;
$TitleID = 49914;

$TitleID = 9243;

$TitleID = 181031;
$TitleID = 117696;

$TitleID = 210877; // BDJ, item barcodes in local database don't match BHL now

$TitleID = 78705;
//$TitleID = 68672;

$TitleID = 79076; // Nota lepidopterologica

$TitleID = 130490; // Pacific Insects

$TitleID = 181031;
$TitleID = 169356;

// redo all 36
$titles = array(
2804,
5943,
7414,
9243,
10088,
10229,
49914,
57881,
62642,
68619,
68672,
78705,
79076,
82521,
95451,
105698,
117696,
119522,
119879,
130490,
139317,
144642,
147681,
150137,
152899,
158870,
162187,
169356,
181031,
190323,
204608,
206514,
209695,
210747,
211788,
212146,
);

$titles = array(
53832, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
53833, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
2804, // Asiatic herpetological research
46858, // She wa yan jiu = Chinese herpetological research
40011, // Chinese herpetological research
);

$titles = array(
181469, // Faune de Madagascar : lépidoptères. 29, Insectes
);

$titles = array(
86930, // Illustrationes florae insularum Maris Pacifici
);

foreach ($titles as $TitleID)
{
	echo $TitleID . "\n";
	upload_title($TitleID, true);
	upload_items_for_title($TitleID, true);
	//upload_parts_for_title($TitleID, true);
}

//$ItemID = 325622;

// get parts for item
// store page - part links
// add these to the item


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

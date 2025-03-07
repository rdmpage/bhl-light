<?php

// Extract data from SQLite and import into CouchDB

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/sqlite.php');


if (0)
{
// get title object and identifiers

$sql = 'SELECT * FROM title 
INNER JOIN titleidentifier USING(TitleID)
WHERE TitleID=7414';

$data = db_get($sql);

print_r($data);


// api 

// title and its identifiers
$obj = new stdclass;
foreach ($data as $row)
{
	$obj->id = 'title/' . $row->TitleID;
	$obj->name = $row->FullTitle;
	
	if (isset($row->ShortTitle))
	{
		$obj->alternateName = $row->ShortTitle;
	}

	if (!isset($obj->identifier))
	{
		$obj->identifier = array();
	}
	
	if (isset($row->IdentifierName))
	{
		if (!isset($obj->identifier[$row->IdentifierName]))
		{
			$obj->identifier[$row->IdentifierName] = array();
		}
		$obj->identifier[$row->IdentifierName][] = $row->IdentifierValue;
	}
}

print_r($obj);
}


//----------------------------------------------------------------------------------------
// Get details of a title
function get_title($TitleID)
{
	// list of items for a title
	$sql = 'SELECT * FROM title 
	LEFT OUTER JOIN titleidentifier USING(TitleID)
	WHERE TitleID='. $TitleID;
	
	$data = db_get($sql);
	
	print_r($data);
	
	$obj = new stdclass;
	
	foreach ($data as $row)
	{
		$obj->_id = 'bibliography/' . $row->TitleID;
		$obj->name = $row->FullTitle;
		
		if (isset($row->ShortTitle))
		{
			$obj->alternateName = $row->ShortTitle;
		}
	
		if (!isset($obj->identifier))
		{
			$obj->identifier = array();
		}
		
		if (isset($row->IdentifierName))
		{		
			$identifier = new stdclass;
			$identifier->{'@type'} = 'PropertyValue';
			$identifier->propertyID = $row->IdentifierName;
			$identifier->value = $row->IdentifierValue;
			
			$obj->identifier[] = $identifier;
		}
	}
	
	// DOI?
	$sql = 'SELECT * FROM doi WHERE EntityID='. $TitleID . ' AND EntityType="Title"';

	$data = db_get($sql);
	
	foreach ($data as $row)
	{
		$identifier = new stdclass;
		$identifier->{'@type'} = 'PropertyValue';
		$identifier->propertyID = 'doi';
		$identifier->value = $row->DOI;
		
		$obj->identifier[] = $identifier;
		
	}
	
	//print_r($obj);
	
	
	return $obj;
}

//----------------------------------------------------------------------------------------
// Get list of items for a title
function get_items_for_title($TitleID)
{
	// list of items for a title
	$sql = 'SELECT * FROM item 
	WHERE TitleID='. $TitleID . '
	ORDER BY item.year, item.VolumeInfo';
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$items = array();
	
	$position = 1;
	
	foreach ($data as $row)
	{
		$obj = new stdclass;
	
		$obj->_id = 'item/' . $row->ItemID;
		
		// integer order in list (based on SQL query)
		$obj->position = $position++;
		
		if (isset($row->VolumeInfo))
		{		
			$obj->name = $row->VolumeInfo;
		}
		else
		{
			$obj->name = '[Untitled]';
		}
			
		$obj->isPartOf = 'bibliography/' . $row->TitleID;
		
		if (isset($row->ThumbnailPageID))
		{
			$obj->thumbnailUrl = 'pagethumb/' . $row->ThumbnailPageID;
		}
		
		if (isset($row->Year))
		{
			$obj->datePublished = $row->Year;
		}
	
		if (isset($row->InstitutionName))
		{
			$obj->provider = $row->InstitutionName;
		}
		
		if (isset($row->CopyrightStatus))
		{
			$obj->copyrightNotice = $row->CopyrightStatus;
		}
	
		// to do, maybe change this?
		if (isset($row->BarCode))
		{
			$obj->sameAs = 'https://archive.org/details/' . $row->BarCode;
		}
			
		$items[] = $obj;
	
	}
	
	return $items;
}

//----------------------------------------------------------------------------------------
// Get details of a title
function get_part($PartID)
{
	$sql = 'SELECT * FROM part 
	INNER JOIN partidentifier USING(PartID)
	INNER JOIN item USING(ItemID)
	WHERE part.PartID='. $PartID;
	
	$data = db_get($sql);
	
	$obj = new stdclass;
	
	foreach ($data as $row)
	{
		$obj->_id = 'part/' . $row->PartID;
		$obj->{'@type'} = 'CreativeWork';
		
		$obj->csl = new stdclass;
		
		$obj->name = $row->Title;
		
		$obj->csl->title = $row->Title;
			
		$obj->isPartOf = array();
		
		if (isset($row->TitleID))
		{
			$obj->isPartOf[] = 'bibliography/' . $row->TitleID;
		}

		if (isset($row->ItemID))
		{
			$obj->isPartOf[] = 'item/' . $row->ItemID;
		}
		
		if (isset($row->ContainerTitle))
		{
			$container = new stdclass;
			$container->{'@type'} = 'Periodical';
			$container->name = $row->ContainerTitle;

			$obj->csl->{'container-title'} = $row->ContainerTitle;
		}		
		
		if (isset($row->Volume))
		{
			$volume = new stdclass;
			$volume->{'@type'} = 'PublicationVolume';
			$volume->volumeNumber = $row->Volume;
		
			$obj->isPartOf[] = $volume;
			
			$obj->csl->volume = $row->Volume;
		}
		
		if (isset($row->PageRange) && !preg_match('/^--$/', $row->PageRange))
		{
			$obj->pagination = str_replace('--', '-', $row->PageRange);

			$obj->csl->page = $obj->pagination ;
		}
		
		if (isset($row->Date))
		{
			$obj->pagindatePublishedation = $row->Date;
			
			$obj->csl->issued = new stdclass;			
			$obj->csl->issued->{'date-parts'} = array();
			
			$parts = explode('-', $row->Date);
			
			foreach ($parts as $part)
			{
				$obj->csl->issued->{'date-parts'}[0][] = (Integer)$part;
			}
		}		
		
		if (isset($row->StartPageID))
		{
			$obj->thumbnailUrl = 'pagethumb/' . $row->StartPageID;
		}
		
		if (isset($row->ContributorName))
		{
			$obj->provider = $row->ContributorName;
		}		
		

		if (!isset($obj->identifier))
		{
			$obj->identifier = array();
		}
		
		if (isset($row->IdentifierName))
		{
			switch ($row->IdentifierName)
			{
				case 'BioStor':				
					$obj->sameAs = 'https://biostor.org/reference/' . $row->IdentifierValue;

				default:
					$identifier = new stdclass;
					$identifier->{'@type'} = 'PropertyValue';
					$identifier->propertyID = $row->IdentifierName;
					$identifier->value = $row->IdentifierValue;
					
					$obj->identifier[] = $identifier;
					break;								
			}
		}
		
	}
	
	// DOI?
	$sql = 'SELECT * FROM doi WHERE EntityID='. $PartID . ' AND EntityType="Part"';

	$data = db_get($sql);
	
	foreach ($data as $row)
	{
		$obj->csl->DOI = $row->DOI;
		
		$identifier = new stdclass;
		$identifier->{'@type'} = 'PropertyValue';
		$identifier->propertyID = 'doi';
		$identifier->value = $row->DOI;
		
		$obj->identifier[] = $identifier;
		
	}
	
	print_r($obj);
	
	return $obj;
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

upload_title($TitleID, true);

upload_items_for_title($TitleID, true);

//$PartID = 313402;
//$PartID = 153428;

//get_part($PartID);




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

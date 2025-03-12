<?php

// Extract data from SQLite and convert to JSON-LD like for CouchDB

require_once (dirname(__FILE__) . '/sqlite.php');


//----------------------------------------------------------------------------------------
// Get ItemID that corresponds to BarCode (= Internet Archive id)
function get_item_from_barcode($barcode)
{
	$sql = 'SELECT ItemID FROM item 
	WHERE BarCode="'. $barcode . '"';
	
	$data = db_get($sql);
	
	//print_r($data);
	
	$ItemID = 0;
	
	foreach ($data as $row)
	{
		$ItemID = $row->ItemID;
	}
	
	return $ItemID;
}


//----------------------------------------------------------------------------------------
// Get pages for item
function get_pages_for_item($ItemID)
{
	$sql = 'SELECT * FROM page 
	WHERE ItemID='. $ItemID . 
	' ORDER BY CAST(SequenceOrder AS INTEGER)';
	
	$data = db_get($sql);
	
	//print_r($data);
	
	$pages = array();
	
	foreach ($data as $row)
	{
		if (!isset($pages[$row->PageID]))
		{
			$pages[$row->PageID] = new stdclass;
			$pages[$row->PageID]->id = 'page/' . $row->PageID;	
			$pages[$row->PageID]->{'@type'} = 'CreativeWork';
			$pages[$row->PageID]->additionalType = "Page"; 	// http://purl.org/spar/fabio/Page
			
			$pages[$row->PageID]->thumbnailUrl = 'pagethumb/' . $row->PageID;	
		}
		
		$pages[$row->PageID]->position = (Integer)$row->SequenceOrder;
		
		if (isset($row->PagePrefix))
		{
			$pages[$row->PageID]->name = $row->PagePrefix;
		}

		if (isset($row->PageNumber))
		{
			if (isset($pages[$row->PageID]->name))
			{
				$pages[$row->PageID]->name .= ' ';
				$pages[$row->PageID]->name .= $row->PageNumber;
			}
			else
			{
				$pages[$row->PageID]->name = $row->PageNumber;
			}
			
		}
				
		if (isset($row->PageTypeName))
		{
			if (!isset($pages[$row->PageID]->keywords))
			{
				$pages[$row->PageID]->keywords = array();
			}
			$pages[$row->PageID]->keywords[] = $row->PageTypeName;
		}
		
	}
	
	// print_r($pages);
	
	return array_values($pages);
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
function get_item ($ItemID)
{
	// get individual item
	$sql = 'SELECT * FROM item 
	WHERE ItemID='. $ItemID;
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$obj = new stdclass;
	
	foreach ($data as $row)
	{	
		$obj->_id = 'item/' . $row->ItemID;
		
		if (isset($row->VolumeInfo))
		{		
			$obj->name = $row->VolumeInfo;
			
			// to do: can we parse this more accurately than BHL?
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
		
		// get pages
		$pages = get_pages_for_item($row->ItemID);
		
		$obj->hasPart = $pages;
		
	}
	
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
		$item = get_item($row->ItemID);
						
		// integer order in list (based on SQL query)
		$item->position = $position++;
		
		$items[] = $item;	
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

		if (isset($row->SequenceOrder))
		{
			$obj->position = (Integer)$row->SequenceOrder;
		}
		
		if (isset($row->StartPageID))
		{
			$obj->thumbnailUrl = 'pagethumb/' . $row->StartPageID;
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
	
	//print_r($obj);
	
	//echo json_encode($obj);
	
	return $obj;
}

//----------------------------------------------------------------------------------------
// Get list of parts for an item
function get_parts_for_item($ItemID)
{
	// list of items for a title
	$sql = 'SELECT PartID FROM part 
	WHERE ItemID='. $ItemID . '
	ORDER BY CAST(part.SequenceOrder AS INTEGER)';
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$parts = array();
	
	foreach ($data as $row)
	{
		$parts[] = get_part($row->PartID);
	}
	
	return $parts;
}

?>

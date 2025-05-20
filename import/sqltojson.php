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
// Get BarCode  (= Internet Archive id) that corresponds to ItemID
function get_barcode_from_item($ItemID)
{
	$sql = 'SELECT BarCode FROM item 
	WHERE ItemID="'. $ItemID . '"';
	
	$data = db_get($sql);
	
	//print_r($data);
	
	$BarCode = '';
	
	foreach ($data as $row)
	{
		$BarCode = $row->BarCode;
	}
	
	return $BarCode;
}


//----------------------------------------------------------------------------------------
// Get pages for item
function get_pages_for_item($ItemID)
{
	// Parts in item
	$sql = 'SELECT PartID, PageID FROM partpage 
	WHERE ItemID='. $ItemID;
	
	$data = db_get($sql);
	
	// print_r($data);	
	
	$pages_to_parts = array();
	
	foreach ($data as $row)
	{
		if (!isset($pages_to_parts[$row->PageID]))
		{
			$pages_to_parts[$row->PageID] = array();
		}
		$pages_to_parts[$row->PageID][] = $row->PartID;
	}
	
	// print_r($pages_to_parts);
	//exit();
	
	// Item pages
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
			$pages[$row->PageID]->{'@id'} = 'page/' . $row->PageID;	
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
		
		// is this page in one or more parts (sensu BHL)?
		if (isset($pages_to_parts[$row->PageID]))
		{
			$pages[$row->PageID]->isPartOf = array();
			foreach ($pages_to_parts[$row->PageID] as $PartID)
			{
				$pages[$row->PageID]->isPartOf[] = 'part/' . $PartID;
			}
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
	
	//print_r($data);
	
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
	$obj = null;

	$sql = 'SELECT * FROM part 
	INNER JOIN partidentifier USING(PartID)
	INNER JOIN item USING(ItemID)
	WHERE part.PartID='. $PartID;
	
	$data = db_get($sql);
	
	if (count($data) > 0)
	{
		
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
				$obj->datePublished = $row->Date;
				
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
	}
	
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
		$part = get_part($row->PartID);
		if ($part)
		{
			$parts[] = $part;
		}
	}
	
	return $parts;
}

//----------------------------------------------------------------------------------------
// Get item for page
function get_item_for_page($PageID)
{
	// list of items for a title
	$sql = 'SELECT ItemID FROM page 
	WHERE PageID='. $PageID;
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$ItemID = 0;
	
	foreach ($data as $row)
	{
		$ItemID = $row->ItemID;
	}
	
	return $ItemID;
}

//----------------------------------------------------------------------------------------
// Get item and order for page
function get_item_order_for_page($PageID)
{
	$result = array();
	
	// list of items for a title
	$sql = 'SELECT ItemID, SequenceOrder FROM page 
	WHERE PageID='. $PageID;
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$ItemID = 0;
	
	foreach ($data as $row)
	{
		
		$ItemID = $row->ItemID;
		
		$result = array($ItemID, (Integer)$row->SequenceOrder - 1);
	}
	
	return $result;
}

//----------------------------------------------------------------------------------------
// Get title for page
function get_title_for_item($ItemID)
{
	// list of items for a title
	$sql = 'SELECT TitleID FROM item 
	WHERE ItemID='. $ItemID;
	
	$data = db_get($sql);
	
	// print_r($data);
	
	$TitleID = 0;
	
	foreach ($data as $row)
	{
		$TitleID = $row->TitleID;
	}
	
	return $TitleID;
}

//----------------------------------------------------------------------------------------

// test
if (0)
{
	$ItemID = 325622;
	
	// we want all pages in this item that are in a part...
	$item = get_item(325622);
	
	print_r($item);
	
}

?>

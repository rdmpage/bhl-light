<?php

// Specific journal stuff

require_once (dirname(__FILE__) . '/sqlite.php');
require_once (dirname(__FILE__) . '/parse-volume.php');

/*
// Some BHL items have more than volume, or may have multiple issues, each
// starting at page 1. This means multiple pages in an item can have
// the same number. To add precision we could try and sort pages into
// separate series, and then create links between each page and the correpsonding series.
// For exmaple, https://www.biodiversitylibrary.org/item/32857 has 16 issues in a
// single volume. By finding distinct consecutive series of page numbers we can sort
// them into distinct issues.
*/

//----------------------------------------------------------------------------------------
// Display sequences as a text-based matrix for debugging
function display_sequences($result)
{
	$num_sequences = count($result->sequence);
	
	foreach ($result->labels as $label)
	{
		echo str_pad($label, 10, ' ', STR_PAD_LEFT);
		echo " | ";
		
		// if only one series then we always have a page with this label
		if ($num_sequences == 1)
		{
			echo 'x';
		}
		else
		{
			// mutliple series, need to check whether this label is in series
			for ($i = 0; $i < $num_sequences; $i++)
			{
				if (isset($result->sequence[$i][$label]))
				{
					echo 'x';		
				}
				else
				{
					echo ' ';
				}
			}	
		}
		echo "\n";
	}
}

//----------------------------------------------------------------------------------------


$TitleID = 45481;

$TitleID = 59881; // Hétérocéres nouveaux de l'amérique du Sud
$TitleID = 10597;

// Get all items for title

$sql = 'SELECT item.ItemID, item.VolumeInfo, page.SequenceOrder, page.PageNumber, page.PageID FROM item
INNER JOIN page USING(ItemID)
WHERE item.TitleID=' . $TitleID . '
ORDER BY item.ItemID, CAST(page.SequenceOrder AS SIGNED)';

$data = db_get($sql);

$output = array();

$failed = array();

foreach ($data as $row)
{
	if (!isset($output[$row->ItemID]))
	{
		$output[$row->ItemID] = new stdclass;
		$output[$row->ItemID]->VolumeInfo = $row->VolumeInfo;	
		
		$output[$row->ItemID]->details = parse_volume($output[$row->ItemID]->VolumeInfo);
		
		// keep track of things that didn't parse
		if (!$output[$row->ItemID]->details->parsed)
		{
			$failed[] = $output[$row->ItemID]->VolumeInfo;
		}

		$output[$row->ItemID]->pages = array();	
		$output[$row->ItemID]->pageids = array();	
	}
	
	$page_number = '';
	if (isset($row->PageNumber))
	{
		$page_number = $row->PageNumber;
	}
	
	// store page labels
	$output[$row->ItemID]->pages[$row->SequenceOrder] = $page_number;
	
	// store page ids
	$output[$row->ItemID]->pageids[$row->SequenceOrder] = $row->PageID;
}

//print_r($output);

/*
[
	ItemID => {
		VolumeInfo - BHL string
		details { - stuff we figure out
			text
			pattern
			parsed - true if we've parsed it
			volume [
				array of volume numbers
			]
		sequence_counter - number of distinct sequences
		sequence [ - array of sequences indexed 0, 1, ... n
			sequence number [ - array of pages indexed by page label
				{
					label [
						pageid, - PageID 
						pageid,
						...
					],
					...
				}
			]
		]
		labels - list of unique page labels in item
		
		pages [
			ordered list of pages by SequenceOrder and page numbers (if any)
		]
		
		pageids [
			ordered list of PageIDs
		]
	},
	...
]
*/

if (1)
{
	echo "Failed to parse volume info\n";
	print_r($failed);
}

// sort multiple series (to do) for example, if we have multiple copies of the same volume
// we want them in order so we can easily spot them.
// need to sort output array based on VolumeInfo
foreach ($output as $ItemID => $item)
{

}


	
$keys = [
'ItemID',
'VolumeInfo',
'volume',
'start_pageid',
'start_number',

'page_one_id',
'page_one_number',

'last_pageid',
'last_number',

];

echo join("\t", $keys) . "\n";

foreach ($output as $ItemID => $item)
{
	$item->sequence_counter = 0;
	$item->sequence = array();
	$item->labels = array();
	
	$sequence_number = -1;
	
	foreach ($item->pages as $sequence => $label)
	{
		if ($label != '')
		{
			$integer_label = (Integer)$label;
		
			if (!isset($item->sequence[$item->sequence_counter]))
			{
				$item->sequence[$item->sequence_counter] = array();
			}
			
			if (isset($item->sequence[$item->sequence_counter][$label])
				|| $integer_label < $sequence_number
			)
			{
				// new series
				$item->sequence_counter++;
				$item->sequence[$item->sequence_counter] = array();			
			}
					
			$item->sequence[$item->sequence_counter][$label] = $item->pageids[$sequence];
			
			$sequence_number = $integer_label;
			
			// keep track of unique labels for pages
			if (!in_array($label, $item->labels))
			{
				$item->labels[] = $label;
			}
			
		}
	}
	
	sort($item->labels, SORT_NUMERIC);
	
	display_sequences($item);
	
	
	
	foreach ($item->sequence as $i => $sequence)
	{
		$result = new stdclass;
		
		$result->ItemID = $ItemID;
		$result->VolumeInfo = $item->VolumeInfo;
		
		if (isset($item->details->volume) && isset($item->details->volume[$i]))
		{
			$result->volume = $item->details->volume[$i];		
		}
		else
		{
			$result->volume = "";
		}
		
		// first page
		$result->start_pageid = reset($sequence);
		$result->start_number =  key($sequence);

		$result->page_one_id = $result->start_pageid;
		$result->page_one_number = $result->start_number;
		
		// handle case where starts on page 2
		if ($result->start_number == 2)
		{
			$next = array_slice($sequence, 1, 1, true);
			$offset = reset($next) - $result->start_pageid;
			$result->page_one_id -= $offset;
			$result->page_one_number = '1';
		}
		else
		{
			$result->page_one_id = '';
		}
			
		// last page
		$result->last_pageid = end($sequence);
		$result->last_number =  key($sequence);
		
		$row = [];
		
		foreach ($result as $k => $v)
		{
			$row[] = $v;
		}
		
		echo join("\t", $row) . "\n";
	}

}
<?php

// DjVu to SURYA 
require_once (dirname(__FILE__) . '/shared.php');
require_once (dirname(__FILE__) . '/sqlite.php');


//----------------------------------------------------------------------------------------
function parse_djvu($ia, $xml, $threshold = 0, $scale = 1)
{
	$document = new stdclass;
	
	$document->internetarchive = $ia;
	
	$document->pages = array();
	$document->page_count = 0;

	$pages = array();

	$dom = new DOMDocument;
	$dom->loadXML($xml);
	
	$xpath = new DOMXPath($dom);
	
	/*
	 <OBJECT data="file://localhost/var/tmp/autoclean/derive/insectsofsamoaot05othe/insectsofsamoaot05othe.djvu" type="image/x.djvu" usemap="insectsofsamoaot05othe_0002.djvu" width="2270" height="3229">
   <PARAM name="PAGE" value="insectsofsamoaot05othe_0002.djvu"/>
   <PARAM name="DPI" value="350"/>
   <HIDDENTEXT>
    <PAGECOLUMN>
     <REGION>
      <PARAGRAPH>
       <LINE>
        <WORD coords="405,509,498,462" x-confidence="96">List</WORD>
        <WORD coords="528,509,573,461" x-confidence="93">of</WORD>
        <WORD coords="603,530,810,461" x-confidence="92">Fascicles</WORD>
        <WORD coords="841,510,983,462" x-confidence="95">issued</WORD>
        <WORD coords="1013,510,1059,475" x-confidence="96">to</WORD>
        <WORD coords="1089,510,1205,463" x-confidence="95">22nd</WORD>
        <WORD coords="1235,519,1359,463" x-confidence="95">June,</WORD>
        <WORD coords="1385,510,1490,463" x-confidence="96">1929</WORD>
        <WORD coords="1520,521,1777,463" x-confidence="92">(continued)</WORD>
        <WORD coords="1796,512,1863,485" x-confidence="75">:—</WORD>
       </LINE>
      </PARAGRAPH>
     </REGION>
    </PAGECOLUMN>
    */

	$objects = $xpath->query ('//OBJECT');
	foreach($objects as $object)
	{		
		$page = new stdclass;	
		
		// shared	
		$page->image_bbox = array();
		$page->page = ++$document->page_count;
		
		// layout
		$page->bboxes = array();
		
		// text
		$page->text_lines = array();
			
		// coordinates and other attributes 
		if ($object->hasAttributes()) 
		{ 
			$attributes = array();
			$attrs = $object->attributes; 
		
			foreach ($attrs as $i => $attr)
			{
				$attributes[$attr->name] = $attr->value; 
			}
		}
		
		$page->image_bbox = array(0, 0, $attributes['width'], $attributes['height']);
		scale_bbox($page->image_bbox, $scale);

		$paragraphs = $xpath->query ('HIDDENTEXT/PAGECOLUMN/REGION/PARAGRAPH', $object);
		foreach($paragraphs as $paragraph)
		{
			// text block
			$block = new stdclass;
			$block->label = 'Text';
			$block->position = count($page->bboxes);
							
			$block->bbox = array($page->image_bbox[2],$page->image_bbox[3],0,0);
			
			foreach ($xpath->query ('LINE', $paragraph) as $line_tag)
			{
				$line = new stdclass;
				$line->bbox = array($page->image_bbox[2],$page->image_bbox[3],0,0);
				
				scale_bbox($line->bbox, $scale);
				
				$words_text = array();
						
				$words = $xpath->query ('WORD', $line_tag);
				foreach($words as $word)
				{							
				
					// coordinates and other attributes 
					if ($word->hasAttributes()) 
					{ 
						$attributes = array();
						$attrs = $word->attributes; 
					
						foreach ($attrs as $i => $attr)
						{
							$attributes[$attr->name] = $attr->value; 
						}
					}
					
					$bbox = explode(",", $attributes['coords']);
					
					$line->bbox[0] = min($line->bbox[0], $bbox[0]);
					$line->bbox[1] = min($line->bbox[1], $bbox[3]);
					$line->bbox[2] = max($line->bbox[2], $bbox[2]);
					$line->bbox[3] = max($line->bbox[3], $bbox[1]);
					
					if (isset($word->firstChild->nodeValue))
					{
						$text = $word->firstChild->nodeValue;							
						$words_text[] = $text;
					}										
				}	
				
				$line->text = join(' ', $words_text);
				
				$page->text_lines[] = $line;	
				
				$block->bbox[0] = min($line->bbox[0], $block->bbox[0]);
				$block->bbox[1] = min($line->bbox[1], $block->bbox[1]);
				$block->bbox[2] = max($line->bbox[2], $block->bbox[2]);
				$block->bbox[3] = max($line->bbox[3], $block->bbox[3]);
		
			}
			
			
			scale_bbox($block->bbox, $scale);
			
			/*	
				$lines = $xpath->query ('xhtml:span[@class="ocr_line" or "ocr_caption"]', $ocr_par);
				foreach($lines as $line_tag)
				{
					// coordinates
					if ($line_tag->hasAttributes()) 
					{ 
						$attributes = array();
						$attrs = $line_tag->attributes; 
	
						foreach ($attrs as $i => $attr)
						{
							$attributes[$attr->name] = $attr->value; 
						}
					}
					
					$line = new stdclass;
					$line->bbox = extract_box($attributes['title']);
					scale_bbox($line->bbox, $scale);
					
					$words_text = array();
							
					$words = $xpath->query ('xhtml:span[@class="ocrx_word"]', $line_tag);
					foreach($words as $word)
					{								
						if (isset($word->firstChild->nodeValue))
						{
							$text = $word->firstChild->nodeValue;							
							$words_text[] = $text;
						}										
					}	
					
					$line->text = join(' ', $words_text);
					
					$page->text_lines[] = $line;
					
				}*/
			$page->bboxes[] = $block;	
		}		

			
		$document->pages[] = $page;	
		
	}
	
	
	return $document;
}

//----------------------------------------------------------------------------------------
function djvu_sanity_check($document)
{
	$result = new stdclass;
	$result->ok = true;

	$sql = 'SELECT COUNT(DISTINCT PageID) AS numpages FROM page INNER JOIN item USING(ItemID) WHERE item.BarCode="' . $document->internetarchive . '";';
	
	$data = db_get($sql);
	
	$num_db_pages   = $data[0]->numpages;
	$num_djvu_pages = count($document->pages);
		
	if ($num_db_pages != $num_djvu_pages)
	{
		$result->ok = false;
		$result-> message = "Page numbers in database $num_db_pages and DjVu file $num_djvu_pages don't match\n";
	}

	return $result;
}

//----------------------------------------------------------------------------------------
function djvu_to_datalab($ia)
{
	$filename = $ia . '/' . $ia . '_djvu.xml';
	
	if (!file_exists($filename))
	{
		echo "DjVu file $filename not found!\n";
		return null;
	}
	$xml = file_get_contents($filename);
	
	$document = parse_djvu($ia, $xml, 0);
	
	// sanity check
	$check = djvu_sanity_check($document);
	
	if ($check->ok)
	{
		return $document;
	}
	else
	{
		// second attempt if first fails is to assume every page is OK
		$document = parse_djvu($ia, $xml, -1);
		
		// sanity check
		$check = djvu_sanity_check($document);
		
		if ($check->ok)
		{
			return $document;
		}
		else
		{	
			echo $check->message . "\n";
			return null;
		}
	}
}

//----------------------------------------------------------------------------------------

if (0)
{
	$document = djvu_to_datalab('europeanjournal102muse');
	print_r($document);
}


?>
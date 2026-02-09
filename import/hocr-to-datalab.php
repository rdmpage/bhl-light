<?php

// HOCR to SURYA 
require_once (dirname(__FILE__) . '/shared.php');
require_once (dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
function extract_box($text)
{
	$bbox = array(0,0,0,0);
	
	if (preg_match('/bbox (\d+) (\d+) (\d+) (\d+)/', $text, $m))
	{
		$bbox = array(
			(Double)$m[1], 
			(Double)$m[2],
			(Double)$m[3],
			(Double)$m[4]
			);
	}

	return $bbox;
}

//----------------------------------------------------------------------------------------
function extract_image($text)
{
	$image = '';
	if (preg_match('/"\/tmp\/([^\/]+)\/([^"]+)"/', $text, $m))
	{
		$image = $m[2];
	}

	return $image;
}

//----------------------------------------------------------------------------------------
function extract_font_size($text)
{
	$size = -1;
	if (preg_match('/x_f?size (\d+)/', $text, $m))
	{
		$size = $m[1];
	}
	
	$size = round($size);

	return $size;
}

//----------------------------------------------------------------------------------------
function parse_hocr($ia, $xml, $threshold = 0, $scale = 1)
{
	$document = new stdclass;	
	
	$document->internetarchive = $ia;
	
	$document->pages = array();
	$document->page_count = 0;

	$pages = array();
				
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	
	$xpath = new DOMXPath($dom);

	$xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

	$ocr_pages = $xpath->query ('//xhtml:div[@class="ocr_page"]');
	foreach($ocr_pages as $ocr_page)
	{		
		// Ignore nodes with no children as these are likely to be pages such as scan
		// calibrations. For reasons it looks like 	getElementsByTagName('*') is the best
		// way to test for no children.
		
		$num_children = $ocr_page->getElementsByTagName('*')->length;
		
		// echo $num_children . "\n";
				
		if ($num_children > $threshold)
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
			if ($ocr_page->hasAttributes()) 
			{ 
				$attributes = array();
				$attrs = $ocr_page->attributes; 
			
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
			}
			
			$image = extract_image($attributes['title']);
			
			if ($image != '')
			{			
				$page->internetarchive = $image;
			}
			else
			{
				echo "No image " . ' ' . $attributes['title'] . "\n";
				$page->internetarchive = $document->internetarchive . '_' . str_pad($page->page, 4, '0', STR_PAD_LEFT) . '.jp2';
				echo "Fake page id " . $page->internetarchive . "\n";
			}
			
			$page->image_bbox = extract_box($attributes['title']);
			scale_bbox($page->image_bbox, $scale);
			
			// images (these may be simply page numbers that haven't been recognised)
			foreach($xpath->query ('xhtml:div[@class="ocr_photo"]', $ocr_page) as $ocr_photo)
			{		
				$block = new stdclass;
				$block->label = 'Figure';
				$block->position = count($page->bboxes);
	
				if ($ocr_photo->hasAttributes()) 
				{ 
					$attributes = array();
					$attrs = $ocr_photo->attributes; 
	
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				} 			
				
				$block->bbox = extract_box($attributes['title']);
				scale_bbox($block->bbox, $scale);
				
				$page->blocks[] = $block;	
			}
			
			// tables
			foreach($xpath->query ('xhtml:table[@class="ocr_table"]', $ocr_page) as $ocr_table)
			{
				$block = new stdclass;
				$block->label = 'Table';
				$block->position = count($page->bboxes);
	
				if ($ocr_table->hasAttributes()) 
				{ 
					$attributes = array();
					$attrs = $ocr_photo->attributes; 
	
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				} 			
				
				$block->bbox = extract_box($attributes['title']);
				scale_bbox($block->bbox, $scale);
				
				$page->blocks[] = $block;	
			}
	
			$ocr_careas = $xpath->query ('xhtml:div[@class="ocr_carea"]', $ocr_page);
			foreach($ocr_careas as $ocr_carea)
			{
				$ocr_pars = $xpath->query ('xhtml:p[@class="ocr_par"]', $ocr_carea);
				foreach($ocr_pars as $ocr_par)
				{		
					// text block
					$block = new stdclass;
					$block->label = 'Text';
					$block->position = count($page->bboxes);
					
					// coordinates
					if ($ocr_par->hasAttributes()) 
					{ 
						$attributes = array();
						$attrs = $ocr_par->attributes; 
	
						foreach ($attrs as $i => $attr)
						{
							$attributes[$attr->name] = $attr->value; 
						}
					}										
									
					$block->bbox = extract_box($attributes['title']);
					scale_bbox($block->bbox, $scale);
					
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
						
					}
					$page->bboxes[] = $block;	
				}
			}	
			
			$document->pages[] = $page;	
		}
	}	
	return $document;
}

//----------------------------------------------------------------------------------------
function hocr_sanity_check($document)
{
	$result = new stdclass;
	$result->ok = true;

	$sql = 'SELECT COUNT(DISTINCT PageID) AS numpages FROM page INNER JOIN item USING(ItemID) WHERE item.BarCode="' . $document->internetarchive . '";';
	
	$data = db_get($sql);
	
	$num_db_pages   = $data[0]->numpages;
	$num_hocr_pages = count($document->pages);
	
	if ($num_db_pages != $num_hocr_pages)
	{
		// special handling...
		switch ($document->internetarchive)
		{
			case 'mobot31753002350194':
				// BHL dataabse has 16 extra pages at the end that aren't in the scan,
				// scan has 563 pages, database has 579.
				break;

			case 'mobot31753002350152':
				// BHL database has 623 pages, IA has 576
				break;
				
			case 'mobot31753002347604':
			case 'mobot31753002347570':
			case 'mobot31753002347547':
				break;
				
				// Am Nat
			case 'mobot31753002140710':
			case 'mobot31753002156641':
				break;
				
				// page numbering out of alignment with hOCR in this item, 
				// total page numbers don't match
				// Page numbers in database 618 and hOCR file 626 don't match

			/*
			case 'trudyrusskagoent40191113russ':
				break;
			*/
	
		
			default:
				$result->ok = false;
				$result-> message = "Page numbers in database $num_db_pages and hOCR file $num_hocr_pages don't match\n";
				break;
		}
	}

	return $result;
}

//----------------------------------------------------------------------------------------
function hocr_to_datalab($ia)
{
	global $config;

	$filename = $config['cache'] . '/' . $ia . '/' . $ia . '_hocr.html';
	
	if (!file_exists($filename))
	{
		echo "hOCR file $filename not found!\n";
		return null;
	}
	$xml = file_get_contents($filename);
	
	$document = parse_hocr($ia, $xml, 0);
	
	// sanity check
	$check = hocr_sanity_check($document);
	
	if ($check->ok)
	{
		return $document;
	}
	else
	{
		// second attempt if first fails is to assume every page is OK
		$document = parse_hocr($ia, $xml, -1);
		
		// sanity check
		$check = hocr_sanity_check($document);
		
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
	$document = hocr_to_datalab('insectsofsamoaot01natu');
}

if (0)
{
	$filename = '';
	if ($argc < 2)
	{
		echo "Usage: " . basename(__FILE__) . " <filename>\n";
		exit(1);
	}
	else
	{
		$filename = $argv[1];
	}
	
	$document = parse_hocr($filename);
	
	// sanity check, do we have same number of pages in hOCR and BHL?
	
	$basename = basename($filename, '_hocr.html');
	
	$ia = $basename;
	
	$sql = 'SELECT COUNT(DISTINCT PageID) AS numpages FROM page INNER JOIN item USING(ItemID) WHERE item.BarCode="' . $ia . '";';
	
	$data = db_get($sql);
	
	$num_db_pages   = $data[0]->numpages;
	$num_hocr_pages = count($document->pages);
	
	if ($num_db_pages == $num_hocr_pages)
	{
		echo "Saving layout\n";
		file_put_contents($basename . '.json', json_encode($document));
	}
	else
	{
		echo "Page numbers in database $num_db_pages and hOCR file $num_hocr_pages don't match\n";
	}
}

?>
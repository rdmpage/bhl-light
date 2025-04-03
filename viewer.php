<?php

// HTML item viewer

ini_set('memory_limit', '-1');

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');


//----------------------------------------------------------------------------------------
// Using character start and end positions for each annotation of this line, mark the
// annotation using HTML tags (e.g., <mark>)
function highlight_annotations($text, $annotations)
{
	// Create a HTML string with annotations highlighted
	$open = array();
	$close = array();
	
	foreach ($annotations as $annotation)
	{
		foreach ($annotation->target->selector as $selector)
		{
			if ($selector->type == "TextPositionSelector")
			{
				// add to list of start and end positions
				if (!isset($open[$selector->start]))
				{
					$open[$selector->start] = array();
				}
			
				$open[$selector->start][] = 'x'; // set type of annotation

				if (!isset($close[$selector->end]))
				{
					$close[$selector->end][] = 'x';
				}
			}
		}	
	}
	
	$content_length = mb_strlen($text);
	
	// use array of start and end positions to insert open and close tags to mark the
	// annotation
	$html = '';
	
	for ($i = 0; $i < $content_length; $i++)
	{
		$char = mb_substr($text, $i, 1); 
		
		if (isset($open[$i]))
		{
			foreach ($open[$i] as $type)
			{
				switch ($type)
				{
					default:
						$html .= '<mark class="' . $type . '">';
						break;
				}
			}		
		}
		
		$html .= $char;
	
		if (isset($close[$i]))
		{
			foreach ($close[$i] as $type)
			{
				switch ($type)
				{
					default:
						$html.= '</mark>';
						break;
				}
			}		
		}
	
	}	
	
	return $html;				
}

//----------------------------------------------------------------------------------------
// Ensure any stray < and > in the text are treated as HTML entities so they don't get
// confused with tags.
// Code assumes that annotation tag is "mark"
// Note that we need to do this as if OCR text contains stray < or > then we can break the
// HTML. If we encode entities before adding annotation then our position-based annotations
// won't work as they are for the original text.
function ltgt_entities($text, $annotation_tag = 'mark')
{
	preg_match_all('/<\/?' . $annotation_tag . '[^>]*>/', $text, $tagMatches);

	// protect annotation tags
	$placeholders = [];
	foreach ($tagMatches[0] as $i => $tag)
	{
		$placeholder = "###HTMLTAG_$i###";
		$placeholders[$placeholder] = $tag;
		$text = str_replace($tag, $placeholder, $text);
	}

	// relace any unprotected < and >
	$text = str_replace('<', '&lt;', $text);
	$text = str_replace('>', '&gt;', $text);

	// restore annotation tags
	foreach ($placeholders as $placeholder => $tag) 
	{
    	$text = str_replace($placeholder, $tag, $text);
	}

	return $text;
}

//----------------------------------------------------------------------------------------
function layout_to_viewer_html($layout, $block_layout = null, $annotations = null, $page=1, $image_width = 700)
{
	global $config;

	$annotation_experiment = false;
	$annotation_experiment = true;

	$html = '';
	$html .= '<html>';
	$html .=  '<head>';
	
	$html .=  '<link rel="stylesheet" href="root.css.inc.php">';
	
	$html .=  '<style>';
		
	$html .= '
	body {
		 background-color:var(--viewer-bg);
	}
	
	.page {
		background-color:white;
		position:relative;
		margin: 0 auto;
		margin-bottom:1em;
		margin-top:1em;	
		box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		
		-webkit-user-select:none;
		-moz-user-select:none;
		user-select:none;	
	
		/* width of page relative to viewer */
		width:90%;
	}	
	
	.page img {
		-webkit-user-select:none;
		-moz-user-select:none;
		user-select:none;
				
		-webkit-user-drag: none; 	
		-moz-user-drag: none; 	
		user-drag: none; 
		
		width: 100%; 
		height: 100%;
		
		object-fit: fill;	
		
		/* filter: grayscale(1) contrast(10) brightness(1);*/
		
	}	
	
	.pagetext {
		position:absolute;
		
		/* border:1px solid black; */
		
		/* debugging, show text boxes */
		/* background-color: rgba(0,255,0, 0.3); */
		
		text-align:justify;
		text-align-last:justify;
		overflow:hidden;
		color:var(--viewer-text-color);
				
		-webkit-user-select:text;
		-moz-user-select:text;
		user-select:text;
	}
	
	.pageblock {
		position:absolute;
		/* border:1px solid black; */
		opacity:0.7;
	}
	
	/* https://docs.google.com/document/d/1frGmzYOHnVRWAwTOuuPfc3KVAwu-XKdkFSbpLfy78RI/edit#heading=h.iz6rewv3v747 */

	.Caption {
		background:rgb(234,183,172); /* Caption */
	}
	
	.Figure {
		background:rgb(233,222,241); 	/* Figure */	
	}

	.Footnote {
		background:rgb(234,183,172); 	/* Footnote */	
	}
	
	.PageFooter {
		background:rgb(234,183,172); /* Footer */
	}
	
	.PageHeader {
		background:rgb(234,183,172); /* Header */
	}	
	
	/* A photograph or a plate */
	.Picture {
		background:rgb(233,222,241); 	/* Figure */	
	}
	
	.ListItem {
		background:rgb(215,233,248); 	/* List */	
	}	
	
	.SectionHeader {
		background:rgb(244,219,144); 	/* Section */	
	}
	
	.Table {
		background:rgb(197,195,229); /* Table */
	}	
	
	.TableOfContents {
		background:rgb(215,233,248); 	/* List */	
	}	
	
	.Text {
		background:rgb(221,227,221); /* Paragraph */
	}

	.TextInlineMath {
		background:rgb(207,204,190); /* Equation */
	}
	
	
	mark {
		color:transparent;
		background-color:rgba(255,165,0,0.3);
		border-bottom:1px solid red;
	}
	
/* small screen for viewer (which may be included as an iframe) */
/* for main window we have 800, here we have 700 */
@media screen and (max-width: 700px) {
  .page {
    width: 100%;
  }  
  	
	</style>';
	$html .=  '</head>' . "\n";
	
	$html .=  '<body>' . "\n";
			
	$html .= '<!-- pages -->' . "\n";
	for ($i = 0; $i < $layout->page_count; $i++)
	{
		// ith page
	
		// page size based on layout image box, actual display width will be determined by CSS
		$page_width = $layout->pages[$i]->image_bbox[2] - $layout->pages[$i]->image_bbox[0];
		$page_height = $layout->pages[$i]->image_bbox[3] - $layout->pages[$i]->image_bbox[1];
				
		$html .= '<div class="page" style="';
			
		// automatically scale using aspect-ratio
		$ratio = $page_width  / $page_height;
		$html .= 'aspect-ratio:' . $ratio . ';';

		$html .= '"';
		
		// zero-based index of page
		$html .= ' data-pageindex="' . $i . '"';		
		
		// if we have an informative name for this page store it, otherwise just use 
		// leaf number enclosed in []
		if (isset($layout->pages[$i]->label))
		{
			$html .= ' data-page="' . $layout->pages[$i]->label  . '"';		
		}
		else
		{
			$html .= ' data-page="[' . $i  . ']"';
		}
		
		// if we have BHL PageID store it
		if (isset($layout->pages[$i]->bhl_pageid))
		{
			$html .= ' data-bhl="' . $layout->pages[$i]->bhl_pageid  . '"';		
		}		
		
		$html .= '><!-- page start -->' . "\n";
				
		// #page= in PDF is the physical page, i.e., 1,2,...,n so create named anchor
		// https://www.rfc-editor.org/rfc/rfc3778
		$html .= '<a name="page=' .  ($i + 1) . '"></a>' . "\n";
					
		// Construct URL for page image		
		switch ($config['image_source'])
		{
			case 'AWS':
				// AWS is JPEG2000 which image proxy doesn't support :(
				// $image_url = 'https://bhl-open-data.s3.amazonaws.com/images/' . $layout->internetarchive . '/' . $layout->pages[$i]->internetarchive;
				break;
				
			case 'Hetzner':
				// S3 compatible with processed images, use Internet Archive property which is either
				// a .djvu filename or a JP2 filename depending on whether layout generated from DjVu
				// or hOCR file. Clean this value by removing extension, then use to fetch image via
				$image_url = 'https://hel1.your-objectstorage.com/bhl/' . $layout->internetarchive . '_jp2/' . preg_replace('/\.(djvu|jp2)/', '.webp', $layout->pages[$i]->internetarchive);
				break;
				
			case 'IA':
			default:
				// Fetch images direct from IA
				$image_url = 'https://archive.org/download/' . $layout->internetarchive . '/page/n' . $i . '_w' . $image_width . '.jpg';
				break;
		}

		// Are we going to fetch image via imgproxy?
		if ($config['use_imgproxy'])
		{
			//$image_url = $config['image_server'] . sign_imgproxy_path($image_url, $image_width);
			$image_url = $config['image_server'] . imgproxy_path_resize($image_url, $image_width);
		}
		
		if (1) // 0 if we are messing about with text layouts and don't want to see page image
		{
			$html .= '<img class="lazy"'
				. ' data-src="' . $image_url  . '"'
				. ' draggable="false"'
				. '>' . "\n";
		}
		
		$html .=  '<!-- hidden text -->' . "\n";
		
		// text lines from layout so we can have selectable text on the page 
		// (albeit hidden from view)	
		foreach ($layout->pages[$i]->text_lines as $line_index => $line)
		{
			$width = $line->bbox[2] - $line->bbox[0];
			$height = $line->bbox[3] - $line->bbox[1];
		
			$html .= '<div class="pagetext" style="'
				// percentage coordinates so text scales with image
				. 'left:' . $line->bbox[0] / $page_width * 100 . '%;'
				. 'top:' .  $line->bbox[1] / $page_height  * 100 . '%;'
				. 'width:' . $width / $page_width  * 100 . '%;'
				. 'height:' . $height / $page_height  * 100 . '%;'
				
				/* font size in terms of viewport units */
				. 'font-size:' . round($height / $page_height * 100, 3) . 'vh;'
				. '">' . "\n";
				
			$text_html = $line->text;
			
			// if we are going to add annotations to the text this is where we do it...
			// we assume that text annotations are line by line
			if ($annotation_experiment)
			{				
				if (isset($annotations->{$i}->{$line_index}))
				{
					$text_html = highlight_annotations($line->text, $annotations->{$i}->{$line_index});
				}
			}
			
			$text_html = ltgt_entities($text_html);
							
			$html .= $text_html . "\n";
			$html .= '</div>'  . "\n";		
		}
		
		// other annotations? e.g. blocks for figures, etc.?
		$html .= '<!-- annotations -->' . "\n";

		if ($block_layout)
		{
			// Block might be computed on images that are scaled differently to OCR
			$block_page_width = $block_layout->pages[$i]->image_bbox[2] - $block_layout->pages[$i]->image_bbox[0];
			$block_page_height = $block_layout->pages[$i]->image_bbox[3] - $block_layout->pages[$i]->image_bbox[1];
		
			foreach ($block_layout->pages[$i]->bboxes as $block)
			{
				$width = $block->bbox[2] - $block->bbox[0];
				$height = $block->bbox[3] - $block->bbox[1];
				
				$class = 'pageblock';
				
				if (isset($block->label))
				{
					$class .= ' ' . $block->label;
				}
			
				$html .= '<div class="' . $class . '" style="'
					// percentage coordinates so block scales with image
					. 'left:' . $block->bbox[0] / $block_page_width * 100 . '%;'
					. 'top:' .  $block->bbox[1] / $block_page_height  * 100 . '%;'
					. 'width:' . $width / $block_page_width  * 100 . '%;'
					. 'height:' . $height / $block_page_height  * 100 . '%;'
					. '"'
					. ' title="' . $block->label . '"'
					. '>' . "\n";
					
				$html .= '</div>';
			}
			
			/*
			$bbox = [98.2421875,439.9765625,705.46875,941.859375];
			$image_bbox = [0,0,800,1132];
			
			$left = $bbox[0] / $image_bbox[2] * 100;
			$top = $bbox[1] / $image_bbox[3] * 100;
			$width = ($bbox[2] - $bbox[0]) / $image_bbox[2] * 100;
			$height = ($bbox[3] - $bbox[1]) / $image_bbox[3] * 100;
			
			
			// Annotation coordinates need to be % w.r.t. height and width of page
			$html .= '<div style="top:' . $top . '%;left:' . $left . '%;height:' . $height . '%;width:' . $width . '%;background-color:rgba(255,0,0,0.2);"></div>';		
			*/
		}
		
		$html .= '</div><!-- end of page -->' . "\n";
	}
	
	$html .= '<script src="lazy.js"></script>' . "\n";
	$html .= '<script>window.location.hash="#page=' . $page . '"</script>' . "\n";
	$html .= '</body>' . "\n";
	$html .= '</html>' . "\n";
	
	return $html;
}


//----------------------------------------------------------------------------------------

// Internet Archive id for item
$id = '';

if (isset($_GET['id']))
{	
	$id = $_GET['id']; 
} 

// One-based page number to display, by default 1
$page = 1;

if (isset($_GET['page']))
{	
	$page = $_GET['page']; 
} 

if ($id == '')
{
	$html = "<html><body>No identifier supplied!</body></html>";
}
else
{
	$layout_id = 'layout/' . $id;

	// get layout as JSON
	$layout = get_layout($layout_id);
	
	$block_id = 'blocks/' . $id;
	$block_layout = get_blocks($block_id);
	
	if ($layout)
	{
		$annotations = get_geo_annotations($id);
	
		$html = layout_to_viewer_html($layout, $block_layout, $annotations, $page);
	}
	else
	{
		$html = "<html><body>No layout for $id</body></html>";
	}
}

echo $html;

?>

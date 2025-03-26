<?php

// HTML item viewer

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');


//----------------------------------------------------------------------------------------
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
function layout_to_viewer_html($layout, $annotations = null, $page=1, $image_width = 700)
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
	
	.page div {
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
	
	/*
	if ($annotation_experiment)
	{
		// load annotations for item
		$annot_json = '{"3":{"3":[{"text":"24.56128° S, 30.86367° E","target":{"selector":[{"type":"TextPositionSelector","start":53,"end":76},{"type":"TextQuoteSelector","exact":"24.56128° S, 30.86367° E","prefix":"nga, Mariepskop Forest Reserve, ","suffix":", 1700 m,"}]}}],"9":[{"text":"24.56374° S, 30.86293° E","target":{"selector":[{"type":"TextPositionSelector","start":0,"end":23},{"type":"TextQuoteSelector","exact":"24.56374° S, 30.86293° E","prefix":"","suffix":", 1640 m, northern mist-belt for"}]}}],"11":[{"text":"24.56692° S, 30.86482°E","target":{"selector":[{"type":"TextPositionSelector","start":16,"end":38},{"type":"TextQuoteSelector","exact":"24.56692° S, 30.86482°E","prefix":"Forest Reserve, ","suffix":", 1520 m, indigenous Afromontane"}]}}],"13":[{"text":"24.5679° S, 30.8599° E","target":{"selector":[{"type":"TextPositionSelector","start":9,"end":30},{"type":"TextQuoteSelector","exact":"24.5679° S, 30.8599° E","prefix":"Reserve, ","suffix":", 1550 m, northern mist-belt for"}]}}],"15":[{"text":"24.56795° S, 30.86138° E","target":{"selector":[{"type":"TextPositionSelector","start":15,"end":38},{"type":"TextQuoteSelector","exact":"24.56795° S, 30.86138° E","prefix":"Bushpig Trail, ","suffix":", 1520 m, northern mist-belt for"}]}}]},"4":{"2":[{"text":"24.56847° S, 30.85920° E","target":{"selector":[{"type":"TextPositionSelector","start":30,"end":53},{"type":"TextQuoteSelector","exact":"24.56847° S, 30.85920° E","prefix":"Forest Reserve, Picnic Trail, ","suffix":", 1545 m, northern mist-belt for"}]}}],"4":[{"text":"24.57108° S, 30.86014° E","target":{"selector":[{"type":"TextPositionSelector","start":57,"end":80},{"type":"TextQuoteSelector","exact":"24.57108° S, 30.86014° E","prefix":"est Reserve, east facing slope, ","suffix":", 1519 m, leg. M."}]}}],"9":[{"text":"24.56128° S, 30.86367° E","target":{"selector":[{"type":"TextPositionSelector","start":53,"end":76},{"type":"TextQuoteSelector","exact":"24.56128° S, 30.86367° E","prefix":"nga, Mariepskop Forest Reserve, ","suffix":", 1700 m."}]}}]},"6":{"30":[{"text":"24.56847° S, 30.85920° E","target":{"selector":[{"type":"TextPositionSelector","start":67,"end":90},{"type":"TextQuoteSelector","exact":"24.56847° S, 30.85920° E","prefix":"p Forest Reserve, Picnic Trail, ","suffix":","}]}}],"36":[{"text":"24.55117° S, 30.89395° E","target":{"selector":[{"type":"TextPositionSelector","start":9,"end":32},{"type":"TextQuoteSelector","exact":"24.55117° S, 30.89395° E","prefix":"Reserve, ","suffix":", 1460 m, indigenous Affomontane"}]}}],"38":[{"text":"24.56795° S, 30.86138° E","target":{"selector":[{"type":"TextPositionSelector","start":15,"end":38},{"type":"TextQuoteSelector","exact":"24.56795° S, 30.86138° E","prefix":"Bushpig Trail, ","suffix":", 1520 m, northern mist-belt for"}]}}],"40":[{"text":"24.57108° S, 30.86014° E","target":{"selector":[{"type":"TextPositionSelector","start":35,"end":58},{"type":"TextQuoteSelector","exact":"24.57108° S, 30.86014° E","prefix":"est Reserve, east facing slope, ","suffix":", 1519 m, leg. M. Cole, 18 Oct. "}]}}]},"7":{"2":[{"text":"24.56847° S, 30.85920° E","target":{"selector":[{"type":"TextPositionSelector","start":67,"end":90},{"type":"TextQuoteSelector","exact":"24.56847° S, 30.85920° E","prefix":"p Forest Reserve, Picnic Trail, ","suffix":","}]}}]},"10":{"3":[{"text":"22.9483° S, 30.3950° E","target":{"selector":[{"type":"TextPositionSelector","start":66,"end":87},{"type":"TextQuoteSelector","exact":"22.9483° S, 30.3950° E","prefix":"g, Sibasa area, Phiphidi Falls, ","suffix":", -1000 m,"}]}}],"7":[{"text":"22.99951° S, 29.88643° E","target":{"selector":[{"type":"TextPositionSelector","start":66,"end":89},{"type":"TextQuoteSelector","exact":"22.99951° S, 29.88643° E","prefix":"g, Hanglip Forest, picnic site, ","suffix":","}]}}],"9":[{"text":"23.017° S, 29.900° E","target":{"selector":[{"type":"TextPositionSelector","start":76,"end":95},{"type":"TextQuoteSelector","exact":"23.017° S, 29.900° E","prefix":"ne in ethanol); Hanglip Forest, ","suffix":","}]}}],"11":[{"text":"23.067° S, 30.121° E","target":{"selector":[{"type":"TextPositionSelector","start":37,"end":56},{"type":"TextQuoteSelector","exact":"23.067° S, 30.121° E","prefix":"ry specimen); Goedehoop Forest, ","suffix":", 1250 m, sorted from leaf-litte"}]}}],"12":[{"text":"22.99092° S, 30.27829° E","target":{"selector":[{"type":"TextPositionSelector","start":69,"end":92},{"type":"TextQuoteSelector","exact":"22.99092° S, 30.27829° E","prefix":"ry specimens); Entabeni Forest, ","suffix":","}]}}],"14":[{"text":"22.98589° S, 30.28127° E","target":{"selector":[{"type":"TextPositionSelector","start":73,"end":96},{"type":"TextQuoteSelector","exact":"22.98589° S, 30.28127° E","prefix":"i Forest, environs of Kliphuis, ","suffix":","}]}}],"17":[{"text":"22.98455° S, 30.28272° E","target":{"selector":[{"type":"TextPositionSelector","start":72,"end":95},{"type":"TextQuoteSelector","exact":"22.98455° S, 30.28272° E","prefix":"i Forest, environs of Kliphuis, ","suffix":","}]}}],"21":[{"text":"22.92649° S, 30.35270° E","target":{"selector":[{"type":"TextPositionSelector","start":76,"end":99},{"type":"TextQuoteSelector","exact":"22.92649° S, 30.35270° E","prefix":"ndo Forest, near sacred shrine, ","suffix":","}]}}]},"11":{"2":[{"text":"22.92173° S, 30.35760° E","target":{"selector":[{"type":"TextPositionSelector","start":28,"end":51},{"type":"TextQuoteSelector","exact":"22.92173° S, 30.35760° E","prefix":"Forest, near sacred shrine, ","suffix":", 1090 m, northern mist-belt for"}]}}],"6":[{"text":"23.017° S, 29.515° E","target":{"selector":[{"type":"TextPositionSelector","start":52,"end":71},{"type":"TextQuoteSelector","exact":"23.017° S, 29.515° E","prefix":"o, Soutpansberg, Dundee Forest, ","suffix":", 1525 m, sorted"}]}}],"7":[{"text":"23.00002° S, 29.88789° E","target":{"selector":[{"type":"TextPositionSelector","start":66,"end":89},{"type":"TextQuoteSelector","exact":"23.00002° S, 29.88789° E","prefix":". 1999 (V7516); Hanglip Forest, ","suffix":", 1360 m,"}]}}],"9":[{"text":"23.017° S, 29.900° E","target":{"selector":[{"type":"TextPositionSelector","start":0,"end":19},{"type":"TextQuoteSelector","exact":"23.017° S, 29.900° E","prefix":"","suffix":", 1370 m, A.C. & W.H. van Brugge"}]}}],"10":[{"text":"23.013° S, 30.080° E","target":{"selector":[{"type":"TextPositionSelector","start":0,"end":19},{"type":"TextQuoteSelector","exact":"23.013° S, 30.080° E","prefix":"","suffix":", 1175 m, sorted from leaf-litte"}]}}],"11":[{"text":"23.07253° S, 30.11494° E","target":{"selector":[{"type":"TextPositionSelector","start":8,"end":31},{"type":"TextQuoteSelector","exact":"23.07253° S, 30.11494° E","prefix":"Forest, ","suffix":", 1190 m, Afromontane forest, in"}]}}],"12":[{"text":"23.000° S, 30.233° E","target":{"selector":[{"type":"TextPositionSelector","start":31,"end":50},{"type":"TextQuoteSelector","exact":"23.000° S, 30.233° E","prefix":"2001 (W2064); Entabeni Forest, ","suffix":", indigenous forest, J. Swaye, L"}]}}],"13":[{"text":"22.983° S, 30.250° E","target":{"selector":[{"type":"TextPositionSelector","start":31,"end":50},{"type":"TextQuoteSelector","exact":"22.983° S, 30.250° E","prefix":"(V9475); Entabeni, Matiwa Kop, ","suffix":", 1310 m, in forest, A.C. & W.H."}]}}],"14":[{"text":"22.983° S, 30.250° E","target":{"selector":[{"type":"TextPositionSelector","start":36,"end":55},{"type":"TextQuoteSelector","exact":"22.983° S, 30.250° E","prefix":" 1965 (A8352); Entabeni Forest, ","suffix":", 1160 m, A.C. & W.H. van Brugge"}]}}],"15":[{"text":"22.99541° S, 30.28023° E","target":{"selector":[{"type":"TextPositionSelector","start":31,"end":54},{"type":"TextQuoteSelector","exact":"22.99541° S, 30.28023° E","prefix":"1965 (A8341); Entabeni Forest, ","suffix":", Afromontane forest, in leaf-li"}]}}],"16":[{"text":"22.872933° S, 30.338783° E","target":{"selector":[{"type":"TextPositionSelector","start":50,"end":75},{"type":"TextQuoteSelector","exact":"22.872933° S, 30.338783° E","prefix":"1 (W2261); Thathe Vondo Forest, ","suffix":", 1280 m, indigenous"}]}}],"20":[{"text":"22.9483° S, 30.3950° E","target":{"selector":[{"type":"TextPositionSelector","start":66,"end":87},{"type":"TextQuoteSelector","exact":"22.9483° S, 30.3950° E","prefix":"g, Sibasa area, Phiphidi Falls, ","suffix":","}]}}]},"15":{"31":[{"text":"24.59563° S, 30.82600° E","target":{"selector":[{"type":"TextPositionSelector","start":53,"end":76},{"type":"TextQuoteSelector","exact":"24.59563° S, 30.82600° E","prefix":"nga, Mariepskop Forest Reserve, ","suffix":", 790 m,"}]}}],"35":[{"text":"24.563° S, 30.863° E","target":{"selector":[{"type":"TextPositionSelector","start":53,"end":72},{"type":"TextQuoteSelector","exact":"24.563° S, 30.863° E","prefix":"nga, Mariepskop Forest Reserve, ","suffix":", 1400 m, indigenous"}]}}],"40":[{"text":"24.56374° S, 30.86293° E","target":{"selector":[{"type":"TextPositionSelector","start":9,"end":32},{"type":"TextQuoteSelector","exact":"24.56374° S, 30.86293° E","prefix":"Reserve, ","suffix":", 1640 m, northern mist-belt for"}]}}],"42":[{"text":"24.56694° S, 30.86270° E","target":{"selector":[{"type":"TextPositionSelector","start":60,"end":83},{"type":"TextQuoteSelector","exact":"24.56694° S, 30.86270° E","prefix":" Forest Reserve, Bushpig Trail, ","suffix":", 1491 m, mist-"}]}}],"44":[{"text":"24.56708° S, 30.85990° E","target":{"selector":[{"type":"TextPositionSelector","start":40,"end":63},{"type":"TextQuoteSelector","exact":"24.56708° S, 30.85990° E","prefix":"ol); Mariepskop Forest Reserve, ","suffix":", 1540 m, Afromontane forest, in"}]}}]},"16":{"3":[{"text":"24.56795° S, 30.86138° E","target":{"selector":[{"type":"TextPositionSelector","start":38,"end":61},{"type":"TextQuoteSelector","exact":"24.56795° S, 30.86138° E","prefix":"en); Mariepskop Forest Reserve, ","suffix":", 1520 m, Afromontane forest, in"}]}}],"8":[{"text":"24.59563° S, 30.82600° E","target":{"selector":[{"type":"TextPositionSelector","start":0,"end":23},{"type":"TextQuoteSelector","exact":"24.59563° S, 30.82600° E","prefix":"","suffix":", 790 m, indigenous riverine for"}]}}],"11":[{"text":"24.875° S, 30.891° E","target":{"selector":[{"type":"TextPositionSelector","start":73,"end":92},{"type":"TextQuoteSelector","exact":"24.875° S, 30.891° E","prefix":"dies in ethanol); God’s Window, ","suffix":","}]}}],"15":[{"text":"24.54933° S, 30.87170° E","target":{"selector":[{"type":"TextPositionSelector","start":45,"end":68},{"type":"TextQuoteSelector","exact":"24.54933° S, 30.87170° E","prefix":" Mpumalanga, Mariepskop summit, ","suffix":", 1920 m, rocky"}]}}],"17":[{"text":"24.55649° S, 30.86662° E","target":{"selector":[{"type":"TextPositionSelector","start":39,"end":62},{"type":"TextQuoteSelector","exact":"24.55649° S, 30.86662° E","prefix":" Mariepskop, just below summit, ","suffix":", 1830 m, Afromontane fynbos/"}]}}],"21":[{"text":"24.59563° S, 30.82600° E","target":{"selector":[{"type":"TextPositionSelector","start":53,"end":76},{"type":"TextQuoteSelector","exact":"24.59563° S, 30.82600° E","prefix":"nga, Mariepskop Forest Reserve, ","suffix":", 790 m."}]}}]},"20":{"41":[{"text":"23.88680° S, 30.01633° E","target":{"selector":[{"type":"TextPositionSelector","start":50,"end":73},{"type":"TextQuoteSelector","exact":"23.88680° S, 30.01633° E","prefix":"opo, Wolkberg, Baccarat Forest, ","suffix":", 1485 m, northern"}]}}]},"21":{"2":[{"text":"23.76551° S, 30.00253° E","target":{"selector":[{"type":"TextPositionSelector","start":52,"end":75},{"type":"TextQuoteSelector","exact":"23.76551° S, 30.00253° E","prefix":"o, Wolkberg, Grootbosch Forest, ","suffix":", 1600 m,"}]}}],"4":[{"text":"23.88189° S, 29.99411° E","target":{"selector":[{"type":"TextPositionSelector","start":39,"end":62},{"type":"TextQuoteSelector","exact":"23.88189° S, 29.99411° E","prefix":"ns); Wolkberg, Swartbos Forest, ","suffix":", 1425 m, Afromontane forest, in"}]}}],"7":[{"text":"23.88680° S, 30.01633° E","target":{"selector":[{"type":"TextPositionSelector","start":8,"end":31},{"type":"TextQuoteSelector","exact":"23.88680° S, 30.01633° E","prefix":"Forest, ","suffix":", 1485 m, Afromontane forest, in"}]}}],"12":[{"text":"23.88680° S, 30.01633° E","target":{"selector":[{"type":"TextPositionSelector","start":50,"end":73},{"type":"TextQuoteSelector","exact":"23.88680° S, 30.01633° E","prefix":"opo, Wolkberg, Baccarat Forest, ","suffix":", 1485 m."}]}}]}}';
		
		//$annot_json = "{}";
		$annotations = json_decode($annot_json);
	}
	*/
		
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
			$image_url = $config['image_server'] . sign_imgproxy_path($image_url, $image_width);
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
		
			$html .= '<div style="'
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
							
			$html .= $text_html . "\n";
			$html .= '</div>'  . "\n";		
		}
		
		// other annotations? e.g. blocks for figures, etc.?
		$html .= '<!-- annotations -->' . "\n";

		if (0)
		{
			$bbox = [98.2421875,439.9765625,705.46875,941.859375];
			$image_bbox = [0,0,800,1132];
			
			$left = $bbox[0] / $image_bbox[2] * 100;
			$top = $bbox[1] / $image_bbox[3] * 100;
			$width = ($bbox[2] - $bbox[0]) / $image_bbox[2] * 100;
			$height = ($bbox[3] - $bbox[1]) / $image_bbox[3] * 100;
			
			
			// Annotation coordinates need to be % w.r.t. height and width of page
			$html .= '<div style="top:' . $top . '%;left:' . $left . '%;height:' . $height . '%;width:' . $width . '%;background-color:rgba(255,0,0,0.2);"></div>';		
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
	
	if ($layout)
	{
		$annotations = get_geo_annotations($id);
	
		$html = layout_to_viewer_html($layout, $annotations, $page);
	}
	else
	{
		$html = "<html><body>No layout for $id</body></html>";
	}
}

echo $html;

?>

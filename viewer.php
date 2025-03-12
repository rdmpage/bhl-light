<?php

// HTML item viewer

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');

//----------------------------------------------------------------------------------------
function layout_to_viewer_html($layout, $image_width = 700)
{
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
	}	
	
	.page div {
		position:absolute;
		
		/* border:1px solid black; */
		
		text-align:justify;
		text-align-last:justify;
		overflow:hidden;
		color:var(--viewer-text-color);
				
		-webkit-user-select:text;
		-moz-user-select:text;
		user-select:text;
		
		
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
	
	for ($i = 0; $i < $layout->page_count; $i++)
	{
		$page_width = $layout->pages[$i]->image_bbox[2] - $layout->pages[$i]->image_bbox[0];
		$page_height = $layout->pages[$i]->image_bbox[3] - $layout->pages[$i]->image_bbox[1];
				
		$html .= '<div class="page" style="';
			
		$ratio = $page_width  / $page_height;
		$html .= 'aspect-ratio:' . $ratio . ';';
		
		// $html .= 'font-size:1em;';

		$html .= '"';
		
		// zero-based index of page
		$html .= ' data-pageindex="' . $i . '"';		
		
		// if we have an informative name for this page use it, otherwise just use leaf number
		if (isset($layout->pages[$i]->label))
		{
			$html .= ' data-page="' . $layout->pages[$i]->label  . '"';		
		}
		else
		{
			$html .= ' data-page="[' . $i  . ']"';
		}
		
		$html .= '>' . "\n";
		
		// https://www.rfc-editor.org/rfc/rfc3778
		// #page= in PDF is the physical page, i.e., 1,2,...,n 
		$html .= '<a name="page=' .  ($i + 1) . '"></a>' . "\n";
					
		// Fetch images direct from IA
		$image_url = 'https://archive.org/download/' . $layout->internetarchive . '/page/n' . $i . '_w' . $image_width . '.jpg';

		// AWS is JPEG2000 which image proxy doesn't support :(
		// $image_url = 'https://bhl-open-data.s3.amazonaws.com/images/' . $layout->internetarchive . '/' . $layout->pages[$i]->internetarchive;

		$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_url, $image_width);

		if (1)
		{
		$html .= '<img class="lazy"'
			. ' data-src="' . $image_url  . '"'
			. ' draggable="false"'
			. '>' . "\n";
		}
		
		// text lines		
		foreach ($layout->pages[$i]->text_lines as $line)
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
				
			$text = htmlentities($line->text, ENT_HTML5);
			
			// $text = str_replace('Apteronemobius', '<mark style="color:transparent;opacity:0.5;">Apteronemobius</mark>', $text);
				
			$html .= $text . "\n";
				
			$html .= '</div>'  . "\n";		
		}

		$html .= '</div>' . "\n";
	}
	
	$html .= '<script src="lazy.js"></script>' . "\n";
	$html .= '</body>' . "\n";
	$html .= '</html>' . "\n";
	
	return $html;
}


//----------------------------------------------------------------------------------------

$id = '';

if (isset($_GET['id']))
{	
	$id = $_GET['id']; 
} 

if ($id == '')
{
	$html = "<html><body>No identifier supplied!</body></html>";
}
else
{
	$id = 'layout/' . $id;

	// get layout as JSON
	$layout = get_layout($id);
	
	if ($layout)
	{
		$html = layout_to_viewer_html($layout);
	}
	else
	{
		$html = "<html><body>No layout for $id</body></html>";
	}
}

echo $html;

?>

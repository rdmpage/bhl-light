<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');

//----------------------------------------------------------------------------------------
function html_start($title = '', $thing = null)
{
	global $config;
	
	echo '<html>';
	
	echo '<head>';
	
	echo '<meta charset="utf-8" />';
  	//echo '<meta name="theme-color" content="Moccasin">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"></meta>';
	
	echo '<!-- base -->
    	<base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->';
    	
    if ($title == '')
    {
    	$title = $config['site_name'];
    }
    	
    echo '<title>' . $title . '</title>';   	
			
	echo '<style>';
	
	require_once (dirname(__FILE__) . '/root.css.inc.php');
	require_once (dirname(__FILE__) . '/body.css.inc.php');
	require_once (dirname(__FILE__) . '/nav.css.inc.php');
	require_once (dirname(__FILE__) . '/aside.css.inc.php');

	/* specific views */	
	require_once (dirname(__FILE__) . '/grid.css.inc.php');
	require_once (dirname(__FILE__) . '/media.css.inc.php');
	require_once (dirname(__FILE__) . '/viewer.css.inc.php');

	echo '</style>' . "\n";

	// Global Javascript
	echo '<script>' . "\n";
	echo '</script>' . "\n";
	
	// Thing this page is about
	if ($thing)
	{
		echo '<!-- JSON-LD for SEO -->' . "\n";
		echo '<script type="application/ld+json">' . "\n";		
		echo json_encode($thing, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</script>' . "\n";
	}
	
	echo '</head>';	
	echo '<body>';
	

	echo '<nav>
	<ul>
		<li><a href=".">Home</a></li>
		<!--
		<li>
			<input class="search" id="search" type="text" placeholder="search">
		</li>
		-->
		<li><a href="containers">Titles</a></li>
		<li>Stuff</li>
	</ul>
	</nav>';
	
}

//----------------------------------------------------------------------------------------
function html_end()
{
	echo '</body>';
	echo '</html>';
}

//----------------------------------------------------------------------------------------
// Home page, or badness happened
function default_display($error_msg = '')
{
	html_start();

	echo '<div>';
	
	if ($error_msg != '')
	{
		echo '<div><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	else
	{
		echo '<h1>BHL Lite</h1>';
		
		echo '<h2>Examples</h2>';
		
		echo '<ul>';
		echo '<li><a href="bibliography/152899">Anales del Museo de Historia Natural de Valparaiso</a></li>';
		echo '<li><a href="bibliography/68619">Insects of Samoa</a></li>';
		echo '<li><a href="bibliography/206514">Contributions of the American Entomologicval Institute</a></li>';
		echo '<li><a href="bibliography/105698">The birds of Australia</a></li>';
		echo '<li><a href="bibliography/212146">The Zoogoer</a></li>';

		echo '<li><a href="bibliography/2804">Asiatic herpetological research</a></li>';
		
		echo '<li><a href="item/252491">Revision of the Malagasy lanternfly genus Belbina Stal, 1863, with two new species (Hemiptera: Fulgoromorpha: Fulgoridae</a></li>';
		
		
		echo '</ul>';
	}
	
	echo '</div>';

	html_end();	
}

//----------------------------------------------------------------------------------------
function display_item($id)
{
	global $config;
	
	$doc = get_item($id);
	
	if ($doc)
	{
		$work = null;
		
		$list = null;
	
		// Unpack JSON-LD
		foreach ($doc as $graph)
		{
			if (in_array('CreativeWork', $graph->{'@type'}))
			{
				$work = $graph;
			}		
			
			if (in_array('DataFeed', $graph->{'@type'}))
			{
				$list = $graph;
			}	
			
		}
		
		$title = $work->name;
	
		html_start($title, $doc);
		
		// create a side bar for information on this work
		echo '<div>';
		echo '  <aside>';
		echo '    <details id="aside-details">';
		echo '      <summary>Details</summary>';
		echo '     	<div>';
		
		// title(s) that contain this item
		$isPartOf = array();
		if (is_array($work->isPartOf))
		{
			$isPartOf = $work->isPartOf;
		}
		else
		{
			$isPartOf[] = $work->isPartOf;
		}
		
		foreach ($isPartOf as $back)
		{
			echo '<p><a href="' . $back . '">← back to title</a></p>';		
		}
		
		// name of item
		echo '<h1>' . $work->name . '</h1>';
		
		// thumbnail
		$image_base_url = 'http://www.biodiversitylibrary.org/';

		if (isset($work->thumbnailUrl))
		{
			echo '<div>';
			// echo '<img width="180" src="image_proxy.php?url=' . urlencode($image_base_url . $work->thumbnailUrl) . '">';
			
			$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_base_url . $work->thumbnailUrl, 0, $config['thumbnail_height']);			
			echo '<img loading="lazy" src="' . $image_url . '">';			
			echo '</div>';
		}
		
		// metadata
		$keys = array('provider', 'copyrightNotice');
		
		echo '<dl>';		
		foreach ($keys as $k)
		{
			if (isset($work->{$k}))
			{
				echo '<dt>';
				echo $k;
				echo '</dt>';
				
				echo '<dd>';
				echo $work->{$k};
				echo '</dd>';
			}
		}
		echo '</dl>';
		
		// Internet Archive id (barcode)
		$internet_archive = '';
		
		if (preg_match('/archive.org\/details\/(.*)/', $work->sameAs, $m))
		{		
			$internet_archive = $m[1];
		}

		if ($internet_archive != '')
		{			
			echo '<dl>';
			echo '<dt>';
			echo 'internet archive';
			echo '</dt>';
			
			echo '<dd>';
			echo $internet_archive;
			echo '</dd>';
			echo '</dl>';
		}
		
		// do we want a BHL-style page link here?
		
		echo '		</div>';
		echo '    </details>';
		echo '  </aside>';
		
		// main display
		echo '  <main>';
 		
 		//--------------------------------------------------------------------------------
 		if (0)
 		{
			// Display list of parts?		
			if ($list)
			{
				echo '<ul class="media-list">';
				foreach ($list->dataFeedElement as $part)
				{
					echo '<li class="media-item">';
					
					$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_base_url . $part->thumbnailUrl, 0, $config['thumbnail_height']);
									
					echo '<img class="media-figure" src="' . $image_url . '">';				
					echo '<div class="media-body">';
					echo '<h3 class="media-title">' . $part->name . '</h3>';
					echo '</div>';
					echo '</li>';			
				}
				echo '</ul>';
			
			}
		}
		
 		//--------------------------------------------------------------------------------
		// Display item as thumbnails?
		if (0)
		{
			if (isset($work->hasPart))
			{
				echo '<ul class="image-grid">';
				foreach ($work->hasPart as $page)
				{
					echo '<li>';
					// echo '<a href="' . $item->{'@id'} . '">';
					//echo '<img loading="lazy" src="image_proxy.php?url=' . urlencode($image_base_url . $item->thumbnailUrl) . '">';
					
					$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_base_url . $page->thumbnailUrl, 0, $config['thumbnail_height']);
					
					echo '<img loading="lazy" src="' . $image_url . '" onerror="retry(this)">';
					
					if (isset($page->name))
					{
						echo '<div>' . $page->name . '</div>';
					}
					//echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
			}
		
		
		}
		
 		//--------------------------------------------------------------------------------
		if (1)
		{
			// Display item as scrollable view?	
			
			// page list
			
			// list of pages
			$pages = array();
			if (isset($work->hasPart))
			{
				$pages = array();
				
				foreach ($work->hasPart as $part)
				{
					if ($part->additionalType == 'Page')
					{
						$pages[$part->position] = $part;
					}
				}
				ksort($pages, SORT_NUMERIC);
			}
				
			echo '<div class="footer">';
			
			// echo '	<div id="pagenumber" class="pagenumber"></div>';
			
			if (count($pages) > 0)
			{
				echo '<select id="pagenumber" onchange="gotopage(event)">' . "\n";
				foreach ($pages as $page)
				{
					$label = '[' . $page->position . ']';
					if (isset($page->name))
					{
						$label = $page->name;
					}
					
					if (isset($page->keywords))
					{
						$label .= ' (' . join(",", $page->keywords) . ')';
					}					
					// zero-based index of page
					echo '<option value="' . ($page->position - 1) . '">' . $label . '</option>' . "\n";
				}
			
				echo '</select>' . "\n";
			}			
			
			echo '</div>';
						
			if ($config['use_hypothesis'])
			{
				echo '<iframe id="viewer" enable-annotation src="viewer.php?id=' . $internet_archive . '"></iframe>';
				echo '<script src="https://hypothes.is/embed.js" async></script>';			
			}
			else
			{
				echo '<iframe id="viewer" src="viewer.php?id=' . $internet_archive . '"></iframe>';			
			}
		}	

		// Display item as coverage?
		
		
		
echo '  </main>
</div>';
		
		echo '<script>';
		require_once ('aside.js.inc.php');
		require_once ('viewer.js.inc.php');
		echo '</script>';

		html_end();
	}
	else
	{
		default_display("$id not found");
	}
}

//----------------------------------------------------------------------------------------
function display_title($id)
{
	global $config;
	
	$doc = get_title($id);
	
	if ($doc)
	{
		$work = null;
		$list = array();	
	
		// Unpack JSON-LD
		foreach ($doc as $graph)
		{
			if (in_array('CreativeWork', $graph->{'@type'}))
			{
				$work = $graph;
			}
		
			if (in_array('DataFeed', $graph->{'@type'}))
			{
				$list = $graph;
			}		
		}
		
		$title = $work->name;
	
		html_start($title, $doc);
		
		// create a side bar for information on this work
		echo '<div>';
		echo '  <aside>';
		echo '    <details id="aside-details">';
		echo '      <summary>Details</summary>';
		echo '     	<div>';
		
		echo '<h1>' . $work->name . '</h1>';
		
		// details
		if (isset($work->identifier))
		{			
			echo '<dl>';
			foreach ($work->identifier as $identifier)
			{
				echo '<dt>';
				echo $identifier->propertyID;
				echo '</dt>';
				
				echo '<dd>';
				echo $identifier->value;
				echo '</dd>';
				
			}
			echo '</dl>';
			
		}
		
		echo '		</div>';
		echo '    </details>';
		echo '  </aside>';
		
		// main display
		echo '  <main>';
      	
		$image_base_url = 'http://www.biodiversitylibrary.org/';
		
		echo '<div>';

		echo '<ul class="image-grid">';
		foreach ($list->dataFeedElement as $item)
		{
			echo '<li>';
			echo '<a href="' . $item->{'@id'} . '">';
			//echo '<img loading="lazy" src="image_proxy.php?url=' . urlencode($image_base_url . $item->thumbnailUrl) . '">';
			
			$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_base_url . $item->thumbnailUrl, 0, $config['thumbnail_height']);
			
			echo '<img loading="lazy" src="' . $image_url . '" onerror="retry(this)">';
			
			echo '<div>' . $item->name . '</div>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		
		echo '</div>';
		
echo '  </main>
</div>';
		
		echo '<script>';
		require_once ('aside.js.inc.php');
		
		echo 'function retry(img) {	
	console.log ("image not loaded: " + img.src);
	
	// removing .src means we will try again next time image is in view
	img.src = img.src;
	
	// set backgrund colour for page to indicate things failed but we are working on it
	//img.parentElement.style.background = "red";
}';

		echo '</script>' . "\n";

		html_end();
	}
	else
	{
		default_display("$id not found");
	}
}

//----------------------------------------------------------------------------------------
function display_container_list($letter = 'A')
{
	$doc = get_titles_for_letter($letter);
	
	if ($doc)
	{
		html_start();
		
		// header with row of letters
		$letters = range('A', 'Z');
		
		// add any extra letters here...
		
		echo '<ul style="list-style-type: none;display: block;overflow:auto;">';
		
		foreach ($letters as $one_letter)
		{
			echo '<li style="float: left;padding:0.3em">';
			echo '<a href="containers/' . $one_letter . '">' . $one_letter . '</a>';
			echo '</li>';
		}
		
		echo '</ul>';
		
		// list of titles...
		
		echo '<div class="multicolumn">';
		echo '<ul>';
		
		foreach ($doc->dataFeedElement as $work)
		{
			echo '<li>';
			echo '<a href="' . $work->{'@id'} . '">' . $work->name . '</a>';
			echo '</li>';
			
		}
				
		echo '</ul>';
		echo '</div>';
		
		html_end();
	}
	else
	{
		default_display("$id not found");
	}
}


//----------------------------------------------------------------------------------------
function main()
{
	global $config;

	$handled = false;
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	// Error message
	if (isset($_GET['error']))
	{	
		$error_msg = $_GET['error'];		
		default_display($error_msg);
		exit(0);			
	}	
	
	if (!$handled)
	{		
		$title = '';
		if (isset($_GET['title']))
		{	
			$title = $_GET['title']; 
		} 
		elseif (isset($_GET['bibliography']))
		{
			$title = $_GET['bibliography'];
		}	
		
		if ($title != '')	
		{			
			if (!$handled)
			{
				display_title($title);
				$handled = true;
			}			
		}
	}	
	
	if (!$handled)
	{		
		$item = '';
		if (isset($_GET['item']))
		{	
			$item = $_GET['item']; 
		} 
		
		if ($item != '')	
		{			
			if (!$handled)
			{
				display_item($item);
				$handled = true;
			}			
		}
	}	
	
	
	if (!$handled)
	{		
		if (isset($_GET['containers']))
		{	
			$letter = 'A';		
			if (isset($_GET['letter']))
			{
				$letter = $_GET['letter'];
			}	

			display_container_list($letter);
			$handled = true;
		}
	}	
		
	
	if (!$handled)
	{
		default_display();
	}

}

main();

?>

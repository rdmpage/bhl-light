<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');

//----------------------------------------------------------------------------------------
function html_start($title = '', $thing = null, $has_map = false)
{
	global $config;
	
	echo '<html>';
	
	echo '<head>';
	
	echo '<meta charset="utf-8" />' . "\n";
  	//echo '<meta name="theme-color" content="Moccasin">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"></meta>' . "\n";
    echo '<!-- stop Safari on iOS interpreting numbers as phone numbers -->' . "\n";
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
	
	echo '<!-- base -->
    	<base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->';
    	
    echo '<script src="https://cdn.jsdelivr.net/npm/seamless-scroll-polyfill@latest"></script>';
    echo '<script>seamless.polyfill();</script>';
    	
    if ($title == '')
    {
    	$title = $config['site_name'];
    }
    	
    echo '<title>' . $title . '</title>';   	
    
    if ($has_map)
    {
    	require_once(dirname(__FILE__) . '/map.inc.php');
    }
			
	echo '<style>';
	
	require_once (dirname(__FILE__) . '/root.css.inc.php');
	require_once (dirname(__FILE__) . '/body.css.inc.php');
	require_once (dirname(__FILE__) . '/nav.css.inc.php');
	require_once (dirname(__FILE__) . '/aside.css.inc.php');

	/* specific views */	
	require_once (dirname(__FILE__) . '/gallery.css.inc.php');
	require_once (dirname(__FILE__) . '/grid.css.inc.php');
	require_once (dirname(__FILE__) . '/media.css.inc.php');
	require_once (dirname(__FILE__) . '/viewer.css.inc.php');

	echo '</style>' . "\n";

	// Global Javascript
	echo '<script>' . "\n";
	require_once (dirname(__FILE__) . '/search.js.inc.php');	
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
		<li style="">
			<input class="search" id="search" type="text" placeholder="Search the catalogue">
		</li>
		<!--
		
		<li><a href="map">Map</a></li>
		<li><a href="https://github.com/rdmpage/bhl-light/issues" target="_new">Feedback</a></li>
		-->
		<li class="dropdown"><a href="#">More ▼</a>
			 <ul class="dropdown-menu">
			 	<li><a href="containers">Titles</a></li>
				<li><a href="map">Map</a></li>
				<li><a href="https://github.com/rdmpage/bhl-light/issues" target="_new">Feedback</a></li>
			</ul>
		</li>
	</ul>
	</nav>';
	
}

//----------------------------------------------------------------------------------------
function html_end()
{
	echo '<script>' . "\n";
	require_once (dirname(__FILE__) . '/keypress.js.inc.php');
	echo '</script>' . "\n";

	echo '</body>';
	echo '</html>';
}

//----------------------------------------------------------------------------------------
// Home page, or badness happened
function default_display($error_msg = '')
{
	global $config;
	
	html_start();

	// special class to ensure that cards can scroll on small screen
	echo '<div class="homepage">';
	
	if ($error_msg != '')
	{
		echo '<div><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	else
	{
	
	echo'
	
  <div class="card-container">
    <div class="card">
      <h2>' . $config['site_name'] . '</h2>
      <p>This is an experimental interface to the Biodiversity Heritage Library, developed by Rod Page.</p>
      <a href="https://github.com/rdmpage/bhl-light/issues" class="button">Feedback</a>
    </div>

    <div class="card">
      <h2>BHL titles</h2>
      <p>A selection of titles available in ' . $config['site_name'] . '.</p>
      <a href="containers" class="button">Titles [A-Z]</a>
    </div>

    <div class="card">
      <h2>View a title</h2>
      <p>All items for a title displayed as thumbnails.</p>
      <a class="button" href="bibliography/57881">Amphibian & reptile conservation</a>
    </div>

	<!--
    <div class="card">
      <h2>View an issue</h2>
      <p>Browse v.5:no.5 (1976:Sept.-Oct.) of <i>Zoogoer</i>.</p>
      <a class="button" href="item/337721">Zoogoer</a>
    </div>
    -->

    <div class="card">
      <h2>Geotagging</h2>
      <p>Pairs of latitude and longitude coordinates highlighted on a page.</p>
      <a class="button" href="page/57579634">Geotagged text</a>
    </div>
    
    <div class="card">
      <h2>View BHL on a map</h2>
      <p>Display location of geotagged text on a map.</p>
      <a class="button" href="map">Map</a>
    </div>
    
    <!--
     <div class="card">
      <h2>Document layout</h2>
      <p>Blocks of text, figures, captions, headers, footers, etc. found using the Surya OCR toolkit.</p>
      <a class="button" href="page/2748670">Document layout</a>
    </div>
    -->
    
     <div class="card">
      <h2>Figures found using AI tools</h2>
      <p>Figures extracted from page scans using Surya OCR toolkit.</p>
      <a class="button" href="item/138618/figures">Figures</a>
    </div>
    

   
  </div>';
  
	}
	
	echo '</div>';

	html_end();	
}

//----------------------------------------------------------------------------------------
function truncate_text($text, $length = 60)
{
	if (mb_strlen($text) > $length - 1)
	{
		$text = mb_substr($text, 0, $length - 1);
		$text .= "…";
	}

	return $text;
}

//----------------------------------------------------------------------------------------
function display_item($id, $offset = 0, $display_mode = 'pages')
{
	global $config;
	
	$doc = get_item($id);
	
	if ($doc)
	{
		$work = null;
		
		$list = null;
		
		$annotation_pages = null;
	
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

			if (in_array('AnnotationPage', $graph->{'@type'}))
			{
				if (isset($graph->items) && count($graph->items) > 0)
				{
					$annotation_pages = $graph;
				}
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
			$image_url = get_page_image_url(str_replace('pagethumb/', '', $work->thumbnailUrl));
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
		
		// different views of item
		echo '<div>';
		echo '<a href="item/' . $id . '">pages</a>';
		echo ' | ';
		echo '<a href="item/' . $id . '/thumbnails">thumbnails</a>';
		
		if ($annotation_pages)
		{
			echo ' | ';
			echo '<a href="item/' . $id . '/figures">figures</a>';
		}
		
		echo '</div>';
				
		// do we want a BHL-style page link here?
		
		echo '		</div>';
		echo '    </details>';
		echo '  </aside>';
		
		// main display
		echo '  <main>';
		
		//$display_mode = 'pages';
		//$display_mode = 'parts';
		//$display_mode = 'thumbnails';
		
		//$display_mode = 'figures';
 		
 		//--------------------------------------------------------------------------------
 		if ($display_mode == 'figures' && $annotation_pages) 
 		{			
 				echo '<div class="gallery">';
				echo '<ul>';
				foreach ($annotation_pages->items as $page)
				{
					foreach ($page as $figure)
					{
						echo '<li>';
						
						$extension = 'webp';
						$image_url = $figure->image;
						
						$image_url = 'https://hel1.your-objectstorage.com/bhl/' . preg_replace('/_\d+$/', '', $image_url) . '_jp2/' . $image_url . '.' . $extension;
						$image_url = $config['image_server'] . imgproxy_path_resize($image_url, $figure->canvas_width);
						
						// crop
						$cropped_url = $config['image_server'] . imgproxy_path_crop($image_url, $figure->width, $figure->height, $figure->centre);
						
						echo '<img  loading="lazy" src="' . $cropped_url . '" onerror="retry(this)">';

						
						echo '</li>';
					}
				}
			echo '<!-- need this to avoid distorting last image -->
    		<li></li>';
				echo '</ul>';
				echo '</div>';
			}
		
 		
 		//--------------------------------------------------------------------------------
 		if ($display_mode == 'parts')
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
		if ($display_mode == 'thumbnails')
		{
			if (isset($work->hasPart))
			{
				echo '<ul class="image-grid">';
				foreach ($work->hasPart as $page)
				{
					echo '<li>';
					echo '<a href="page/' . str_replace('pagethumb/', '', $page->thumbnailUrl) . '">';
					
					$image_url = get_page_image_url(str_replace('pagethumb/', '', $page->thumbnailUrl));
					
					echo '<img loading="lazy" src="' . $image_url . '" onerror="retry(this)">';
					
					if (isset($page->name))
					{
						echo '<div>' . $page->name . '</div>';
					}
					echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
			}
		}
		
 		//--------------------------------------------------------------------------------
		if ($display_mode == 'pages')
		{
			// Display item as scrollable view?	
			
			// list of pages
			$pages = array();
			if (isset($work->hasPart)) // "part" in schema.org sense
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
			
			// list of pages where a part (in BHL sense) starts
			$part_start = array();
			foreach ($list->dataFeedElement as $part)
			{
				if (isset($part->thumbnailUrl))
				{
					if (preg_match('/pagethumb\/(\d+)/', $part->thumbnailUrl, $m))
					{
						$PageID = $m[1];
						if (!isset($part_start[$PageID]))
						{
							$part_start[$PageID] = array();
						}
						if (isset($part->name))
						{
							$part_start[$PageID][] = truncate_text($part->name, 60);
						}
					}
				}
			} 
				
			// This is where we display the current page information, and enable users 
			// to jump to a given page.
			echo '<div class="footer">';
			
			// Display current BHL Page ID
			echo '	<div id="bhlpageid"></div>';
						
			// Display list of all pages in item
			if (count($pages) > 0)
			{
				echo '<select id="pagenumber" onchange="gotopage(event)">' . "\n";
				foreach ($pages as $page)
				{
					$id = str_replace('page/', '', $page->{'@id'});
				
					$label = '[' . $page->position . ']';
					if (isset($page->name))
					{
						$label = $page->name;
					}
					
					if (isset($page->keywords))
					{
						$label .= ' (' . join(",", $page->keywords) . ')';
					}					
					
					// segments?
					if (isset($part_start[$id]))
					{
						foreach ($part_start[$id] as $name)
						{
							echo '<optgroup label="' .  truncate_text($name, 60) . '"></optgroup>';
						}						
					}
					
					// zero-based index of page
					echo '<option value="' . ($page->position - 1) . '">' . $label . '</option>' . "\n";
				}
			
				echo '</select>' . "\n";
			}			
			
			echo '</div>';
			
			// Viewer is IFRAME and we parse a page number to display, so we can scroll 
			// to a page
						
			// Are we going to display hypothes.is?
			if ($config['use_hypothesis'])
			{
				echo '<iframe id="viewer" enable-annotation src="viewer.php?id=' . $internet_archive . '&page=' . ($offset + 1) . '"></iframe>';
				echo '<script src="https://hypothes.is/embed.js" async></script>';			
			}
			else
			{
				echo '<iframe id="viewer" src="viewer.php?id=' . $internet_archive . '&page=' . ($offset + 1) . '"></iframe>';			
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
// Given a BHL PageID return a URL to the thumbnail of the page image. Uses S3 storage,
// falls back to BHL API if Internet Archive id not found.
function get_page_image_url($PageID)
{
	global $config;
	
	$image_url = get_page_image_url_ia($PageID);
	
	if ($image_url != '')
	{
		$image_url = 'https://hel1.your-objectstorage.com/bhl/' . $image_url;
	}
	else
	{
		// fallback to BHL
		$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' . $PageID;
	}
	
	$image_url = 'https://images.bionames.org' . imgproxy_path_resize($image_url, 0, $config['thumbnail_height']);
	
	return $image_url;
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

		echo '<div>';

		echo '<ul class="image-grid">';
		foreach ($list->dataFeedElement as $item)
		{
			echo '<li>';
			echo '<a href="' . $item->{'@id'} . '">';
			
			$image_url = get_page_image_url(str_replace('pagethumb/', '', $item->thumbnailUrl));
			
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
	
	// set background colour for page to indicate things failed but we are working on it
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
// Display list of first letters of titles and titles for current letter
function display_container_list($letter = 'A')
{
	$doc = get_titles_for_letter($letter);
	
	if ($doc)
	{
		html_start();
		
		// header with row of letters
		$letters = get_title_letters();
		
		// add any extra letters here...
		
		echo '<ul style="list-style-type: none;display: block;overflow:auto;">';
		
		foreach ($letters as $one_letter => $count)
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
function display_search($query)
{
	$doc = get_search_results($query);
	
	if ($doc)
	{
		$title = $doc->name;
	
		html_start($title, $doc);
		
		// create a side bar for information on the search
		echo '<div>';
		echo '  <aside>';
		echo '    <details id="aside-details">';
		echo '      <summary>Details</summary>';
		echo '     	<div>';
		
		// search query
		echo '<h1>' . $doc->name . '</h1>';
		
		echo '		</div>';
		echo '    </details>';
		echo '  </aside>';
		
		// main display
		echo '  <main>';
				
		echo '<ul class="media-list">';
		foreach ($doc->dataFeedElement as $hit)
		{
			echo '<li class="media-item">';
			
			if (isset($hit->thumbnailUrl))
			{
				echo '<div>';
				$image_url = get_page_image_url(str_replace('pagethumb/', '', $hit->thumbnailUrl));
				echo '<img class="media-figure" src="' . $image_url . '">';			
				echo '</div>';
			}

			echo '<div class="media-body">';
			echo '<h3 class="media-title">';
			
			 echo '<a href="' . $hit->url . '">' . $hit->name . '</a>';
			 
			echo '</h3>';
			echo '</div>';
			echo '</li>';			
		}
		echo '</ul>';
		
		
		echo '  </main>
		</div>';
		
		echo '<script>';
		require_once ('aside.js.inc.php');
		echo '</script>';

		html_end();
	}
	else
	{
		default_display("search for $query failed");
	}
}

//----------------------------------------------------------------------------------------
// Dislaying a specific BHL page is simply a redirect to a BHL item and a page offset
function display_page($page)
{
	$target = get_page($page);
	
	if (count($target) == 2)
	{
		header("Location: item/" . $target[0] . "/offset/" . $target[1]);
	}
	else
	{
		default_display("PageID $page not found");
	}
}

//----------------------------------------------------------------------------------------
function display_map()
{
	global $config;
	
	html_start($config['site_name'] . " - Map", null, true);
	
	// create a side bar (why?)
	echo '<div>';
	echo '  <aside>';
	echo '    <details id="aside-details">';
	echo '      <summary>Details</summary>';
	echo '     	<div>';
	
	echo '<h2>Map</h2>';
	echo '<p>Point localities extracted from OCR text.</p>';
	echo '<div id="h3"></div>';
	
	echo '		</div>';
	echo '    </details>';
	echo '  </aside>';
	
	// main display
	echo '<main>';
	echo '  <div id="map"></div>';
	echo '</div>';

	echo '  </main>
</div>';
		
	echo '<script>';
	require_once ('aside.js.inc.php');
	echo 'create_map("map");';
	echo '</script>';

	html_end();
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
	
	// title
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
	
	// item
	if (!$handled)
	{		
		$item = '';
		if (isset($_GET['item']))
		{	
			$item = $_GET['item']; 
		} 
		
		$offset = 0;
		if (isset($_GET['offset']))
		{	
			$offset = $_GET['offset']; 
		} 		

		$display_mode = 'pages';
		if (isset($_GET['thumbnails']))
		{	
			$display_mode = 'thumbnails'; 
		} 

		if (isset($_GET['figures']))
		{	
			$display_mode = 'figures'; 
		} 				
		
		if ($item != '')	
		{			
			if (!$handled)
			{
				display_item($item, $offset, $display_mode);
				$handled = true;
			}			
		}
	}
	
	if (!$handled)
	{		
		if (isset($_GET['page']))
		{	
			$page = $_GET['page']; 
			display_page($page);
			$handled = true;
		}				
	}		
		
	// list of titles
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
	
	// search
	if (!$handled)
	{		
		if (isset($_GET['q']))
		{	
			$query = $_GET['q'];
			display_search($query);
			$handled = true;
		}
	}	
	
	if (!$handled)
	{
		if (isset($_GET['map']))
		{
			display_map();
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

<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/core.php');

//----------------------------------------------------------------------------------------
function html_start($title = '', $thing = null)
{
	global $config;
	
	echo '<html>';
	
	echo '<head>';
	
	echo '<meta charset="utf-8" />';
  	echo '<meta name="theme-color" content="orange">';
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


	echo '</style>' . "\n";

	// Global Javascript
	echo '<script>' . "\n";
	echo '</script>' . "\n";
	
	// Thing this page is about
	if ($thing)
	{
		echo '<script type="application/ld+json">' . "\n";
		
		echo json_encode($thing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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
		echo '<li><a href="?title=152899">Anales del Museo de Historia Natural de Valparaiso</a></li>';
		echo '<li><a href="?title=68619">Insects of Samoa</a></li>';
		echo '<li><a href="?title=206514">Contributions of the American Entomologicval Institute</a></li>';
		echo '<li><a href="?title=105698">The birds of Australia</a></li>';
		echo '<li><a href="?title=212146">The Zoogoer</a></li>';
		echo '</ul>';
	}
	
	echo '</div>';

	html_end();	
}

//----------------------------------------------------------------------------------------
function display_item($id)
{
	$doc = get_item($id);
	
	if ($doc)
	{
		$work = null;
	
		// Unpack JSON-LD
		foreach ($doc as $graph)
		{
			if (in_array('CreativeWork', $graph->{'@type'}))
			{
				$work = $graph;
			}
		
			/*
			if (in_array('DataFeed', $graph->{'@type'}))
			{
				$list = $graph;
			}	
			*/	
		}
		
		$title = $work->name;
	
		html_start($title, $doc);
		
		// create a side bar for information on this work
		echo '<div>';
		echo '  <aside>';
		echo '    <details id="aside-details">';
		echo '      <summary>Details</summary>';
		echo '     	<div>';
		
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
		
		echo '<h1>' . $work->name . '</h1>';
		
		$image_base_url = 'http://www.biodiversitylibrary.org/';

		if (isset($work->thumbnailUrl))
		{
			echo '<div>';
			echo '<img width="180" src="image_proxy.php?url=' . urlencode($image_base_url . $work->thumbnailUrl) . '">';
			echo '</div>';
		}
		
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


		
		/*
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
		*/
		
		echo '		</div>';
		echo '    </details>';
		echo '  </aside>';
		
		// main display
		echo '  <main>';
      	
		echo '<p>To do:</p>';
		
echo '  </main>
</div>';
		
		require_once ('aside.js.inc.php');

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
			echo '<img loading="lazy" src="image_proxy.php?url=' . urlencode($image_base_url . $item->thumbnailUrl) . '">';
			echo '<div>' . $item->name . '</div>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		
		echo '</div>';
		
echo '  </main>
</div>';
		
		require_once ('aside.js.inc.php');

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

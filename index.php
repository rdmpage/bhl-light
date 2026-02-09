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
	
	require_once (dirname(__FILE__) . '/panel.css.inc.php');
	
	

	echo '</style>' . "\n";

	// Global Javascript
	echo '<script>' . "\n";
	require_once (dirname(__FILE__) . '/search.js.inc.php');	
	
	require_once (dirname(__FILE__) . '/panel.js.inc.php');
	
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
// Manifest for IIIF
function display_item_iiif($id)
{
	$manifest = get_item_manifest($id);
	
	header ("Content-Type: application/json");
	header('Access-Control-Allow-Origin: *');

	echo json_encode($manifest);

}

//----------------------------------------------------------------------------------------
function display_item($id, $offset = 0, $display_mode = 'pages')
{
	global $config;
	
	$display_google_sidebar = false;
	//$display_google_sidebar = true;
	
	$doc = get_item($id);
	
	if ($doc)
	{
		$work = null;
		
		$list = null;
		
		$annotation_pages = null;
		$image_gallery = null;
	
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
					// distinguish between images (figures) and other annotations
					if (in_array('ImageGallery', $graph->{'@type'}))
					{
						$image_gallery = $graph;
					}
					else
					{					
						$annotation_pages = $graph;
					}
				}
			}	
			
		}
		
		
		if ($display_google_sidebar)
		{
			// load some annotations to play with
			
			$annotationPage = new stdclass;
			$annotationPage->{'@type'} = ['AnnotationPage'];
			$annotationPage->items = array(); 
			
			// model on hypothes.is page note which is a page-level annotation
			
			$annotation  = new stdclass;
			$annotation->text = "Sciomesa venata Fletcher D. S., 1961";
			
			$annotation->body = new stdclass;
			$annotation->body->id = 'https://www.afromoths.net/species_by_code/SCIOVENA';
			
			$annotation->target = new stdclass;
			$annotation->target->source = "page/43637832";
			
			$annotationPage->items[37] = array();
			$annotationPage->items[37][] = $annotation;
			
			$annotation_pages = $annotationPage;
			
			
			$j = '{"@type":["AnnotationPage"],"items":{"79":[{"text":"Gynaephila icterica","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NAARICTE"},"target":{"source":"page\/148261"}},{"text":"Naarda icterica","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NAARICTE"},"target":{"source":"page\/148261"}},{"text":"Schrankia solitaria","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SCHRSOLI"},"target":{"source":"page\/148261"}}],"77":[{"text":"Tosacantha atmocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/TOSAATMO"},"target":{"source":"page\/148261"}},{"text":"Polypogon atmocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/TOSAATMO"},"target":{"source":"page\/148261"}}],"76":[{"text":"Progonia aenicta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PROGAENI"},"target":{"source":"page\/148261"}},{"text":"Nodaria aenicta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PROGAENI"},"target":{"source":"page\/148261"}}],"81":[{"text":"Luceria emarginata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LUCEEMAR"},"target":{"source":"page\/148261"}},{"text":"Luceria pamphaea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LUCEPAMP"},"target":{"source":"page\/148261"}},{"text":"Luceria africana","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LUCEAFRI"},"target":{"source":"page\/148261"}},{"text":"Schrankia emarginata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LUCEEMAR"},"target":{"source":"page\/148261"}},{"text":"Schrankia pamphaea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LUCEPAMP"},"target":{"source":"page\/148261"}},{"text":"Luceria africana africana","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01LUCAFR"},"target":{"source":"page\/148261"}}],"48":[{"text":"Corgatha odontota","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CORGODON"},"target":{"source":"page\/148261"}},{"text":"Oruza odontota","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CORGODON"},"target":{"source":"page\/148261"}}],"34":[{"text":"Ethiopica glaucochroa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ETHIGLAU"},"target":{"source":"page\/148261"}},{"text":"Amefrontia glaucochroa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ETHIGLAU"},"target":{"source":"page\/148261"}}],"26":[{"text":"Eutamsia subsagula","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/FELISUBS"},"target":{"source":"page\/148261"}},{"text":"Feliniopsis subsagula","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/FELISUBS"},"target":{"source":"page\/148261"}},{"text":"Euplexia pericalles","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUPLPERI"},"target":{"source":"page\/148261"}}],"12":[{"text":"Euxootera callima","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECALL"},"target":{"source":"page\/148261"}},{"text":"Euxootera","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/"},"target":{"source":"page\/148261"}},{"text":"Hermonassoides callima","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECALL"},"target":{"source":"page\/148261"}}],"13":[{"text":"Euxootera cyclophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECYCL"},"target":{"source":"page\/148261"}},{"text":"Euxootera cyclops","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECCLO"},"target":{"source":"page\/148261"}},{"text":"Hermonassoides cyclophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECYCL"},"target":{"source":"page\/148261"}},{"text":"Hermonassoides cyclops","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUNECCLO"},"target":{"source":"page\/148261"}}],"36":[{"text":"Sciomesa nyei","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SCIONYEI"},"target":{"source":"page\/148261"}},{"text":"Feraxinia nyei","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SCIONYEI"},"target":{"source":"page\/148261"}},{"text":"Hygrostola homomunda","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYGRHOMO"},"target":{"source":"page\/148261"}}],"28":[{"text":"Procus agelasta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGAGEL"},"target":{"source":"page\/148261"}},{"text":"Procus ambiguella","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGMBIG"},"target":{"source":"page\/148261"}},{"text":"Procus decinerea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGDECI"},"target":{"source":"page\/148261"}},{"text":"Procus subambigua","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGSUBA"},"target":{"source":"page\/148261"}},{"text":"Oligia agelasta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGAGEL"},"target":{"source":"page\/148261"}},{"text":"Oligia ambiguella","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGMBIG"},"target":{"source":"page\/148261"}},{"text":"Oligia decinerea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGDECI"},"target":{"source":"page\/148261"}},{"text":"Oligia subambigua","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGSUBA"},"target":{"source":"page\/148261"}}],"27":[{"text":"Procus pachydetis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGPACH"},"target":{"source":"page\/148261"}},{"text":"Oligia pachydetis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGPACH"},"target":{"source":"page\/148261"}}],"29":[{"text":"Procus tripunctata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGTRIP"},"target":{"source":"page\/148261"}},{"text":"Appana furca","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CONSFURC"},"target":{"source":"page\/148261"}},{"text":"Oligia tripunctata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OLIGTRIP"},"target":{"source":"page\/148261"}},{"text":"Conservula furca","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CONSFURC"},"target":{"source":"page\/148261"}}],"38":[{"text":"Sciomesa argocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRAARGO"},"target":{"source":"page\/148261"}},{"text":"Sciomesa piscator","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRAPISC"},"target":{"source":"page\/148261"}},{"text":"Pirateolea argocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRAARGO"},"target":{"source":"page\/148261"}},{"text":"Pirateolea piscator","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRAPISC"},"target":{"source":"page\/148261"}}],"37":[{"text":"Sciomesa cyclophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRACYCL"},"target":{"source":"page\/148261"}},{"text":"Pirateolea cyclophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PIRACYCL"},"target":{"source":"page\/148261"}},{"text":"Sciomesa venata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SCIOVENA"},"target":{"source":"page\/148261"}}],"32":[{"text":"Paradrina signa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CARASIGN"},"target":{"source":"page\/148261"}},{"text":"Caradrina signa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CARASIGN"},"target":{"source":"page\/148261"}}],"16":[{"text":"Elaeodes bryodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODBRYO"},"target":{"source":"page\/148261"}},{"text":"Elaeodes chlorobapta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODCHLO"},"target":{"source":"page\/148261"}},{"text":"Nyodes bryodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODBRYO"},"target":{"source":"page\/148261"}},{"text":"Nyodes chlorobapta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODCHLO"},"target":{"source":"page\/148261"}}],"15":[{"text":"Elaeodes callichlora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODCALL"},"target":{"source":"page\/148261"}},{"text":"Elaeodes mochlosema","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODMOCH"},"target":{"source":"page\/148261"}},{"text":"Elaeodes panconita","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODPANC"},"target":{"source":"page\/148261"}},{"text":"Nyodes callichlora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODCALL"},"target":{"source":"page\/148261"}},{"text":"Nyodes mochlosema","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODMOCH"},"target":{"source":"page\/148261"}},{"text":"Nyodes panconita","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NYODPANC"},"target":{"source":"page\/148261"}}],"6":[{"text":"Amazonides","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/"},"target":{"source":"page\/148261"}},{"text":"Hyperfrontia elaphrodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01HYPSEM"},"target":{"source":"page\/148261"}}],"10":[{"text":"Psectraxylia","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/"},"target":{"source":"page\/148261"}},{"text":"Axylia intimima","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AXYLINTI"},"target":{"source":"page\/148261"}}],"17":[{"text":"Dicerogastra","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/"},"target":{"source":"page\/148261"}}],"14":[{"text":"Eucladodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/"},"target":{"source":"page\/148261"}}],"62":[{"text":"Rivula catadela","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/RIVUCATA"},"target":{"source":"page\/148261"}},{"text":"Caryonopera pyrrholopha","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CARYPYRR"},"target":{"source":"page\/148261"}}],"60":[{"text":"Marcipa holmi","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/MARCHOLM"},"target":{"source":"page\/148261"}},{"text":"Paralephana westi","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PARAWEST"},"target":{"source":"page\/148261"}}],"67":[{"text":"Hypena aridoxa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEARID"},"target":{"source":"page\/148261"}},{"text":"Hypena euprepes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEEUPR"},"target":{"source":"page\/148261"}}],"66":[{"text":"Hypena phricocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEPHRI"},"target":{"source":"page\/148261"}},{"text":"Hypena phricocyma phricocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01HYPPHR"},"target":{"source":"page\/148261"}}],"68":[{"text":"Hypena porphyrophaes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEPORP"},"target":{"source":"page\/148261"}},{"text":"Hypena erastialis antimima","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/04DICERA"},"target":{"source":"page\/148261"}}],"69":[{"text":"Hypena scotina","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPESCOT"},"target":{"source":"page\/148261"}}],"71":[{"text":"Hypena albizona","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEAZON"},"target":{"source":"page\/148261"}},{"text":"Hypena biangulata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01HYPBIA"},"target":{"source":"page\/148261"}},{"text":"Hypena eucrossa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEEUCR"},"target":{"source":"page\/148261"}}],"72":[{"text":"Hypena prionodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEPRND"},"target":{"source":"page\/148261"}}],"70":[{"text":"Hypena chionosticha","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPECHIO"},"target":{"source":"page\/148261"}},{"text":"Hypena directa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEDIRE"},"target":{"source":"page\/148261"}}],"78":[{"text":"Naarda clitodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NAARCLIT"},"target":{"source":"page\/148261"}}],"73":[{"text":"Britha brithodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/BRITBRIT"},"target":{"source":"page\/148261"}}],"74":[{"text":"Nodaria lophobela","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NODALOPH"},"target":{"source":"page\/148261"}}],"75":[{"text":"Nodaria verticalis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NODAVERT"},"target":{"source":"page\/148261"}}],"80":[{"text":"Hypenodes haploa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEHAPL"},"target":{"source":"page\/148261"}},{"text":"Hypenodes prionodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPEPRIO"},"target":{"source":"page\/148261"}}],"46":[{"text":"Cerynea limbobrunnea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CERYLIMB"},"target":{"source":"page\/148261"}},{"text":"Cerynea nigropuncta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CERYNGRO"},"target":{"source":"page\/148261"}}],"44":[{"text":"Eublemma dyscapna","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUBLDYSC"},"target":{"source":"page\/148261"}}],"45":[{"text":"Holocryptis neavei","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HOLONEAV"},"target":{"source":"page\/148261"}}],"51":[{"text":"Pardasena atmocyma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PARDATMO"},"target":{"source":"page\/148261"}}],"54":[{"text":"Tegena aprepta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/TEGEAPRE"},"target":{"source":"page\/148261"}},{"text":"Tegena steeleae","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/TEGESTEE"},"target":{"source":"page\/148261"}}],"55":[{"text":"Westermannia immaculata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/WESTIMMA"},"target":{"source":"page\/148261"}}],"25":[{"text":"Homonacna alpnista","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HOMOALPN"},"target":{"source":"page\/148261"}}],"47":[{"text":"Pseudcraspedia ethiopica","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01PSEPRO"},"target":{"source":"page\/148261"}}],"30":[{"text":"Callopistria dascia","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/CALLDASC"},"target":{"source":"page\/148261"}},{"text":"Tracheplexia schista","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/TRACSCHI"},"target":{"source":"page\/148261"}},{"text":"Tracheplexia tenuiata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01TRASCH"},"target":{"source":"page\/148261"}}],"11":[{"text":"Ochropleura spinosa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01OCHSPI"},"target":{"source":"page\/148261"}},{"text":"Ochropleura viettei","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OCHRVIET"},"target":{"source":"page\/148261"}},{"text":"Psectraxylia boursini","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PSECBOUR"},"target":{"source":"page\/148261"}}],"33":[{"text":"Ethiopica acrothecta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ETHIACRO"},"target":{"source":"page\/148261"}},{"text":"Ethiopica eclecta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ETHIELEC"},"target":{"source":"page\/148261"}}],"7":[{"text":"Amazonides ascia","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AMAZASCI"},"target":{"source":"page\/148261"}}],"9":[{"text":"Axylia belophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AXYLBELO"},"target":{"source":"page\/148261"}},{"text":"Axylia posterioducta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AXYLPOST"},"target":{"source":"page\/148261"}}],"8":[{"text":"Axylia edwardsi","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AXYLEDWA"},"target":{"source":"page\/148261"}},{"text":"Axylia sciodes","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AXYLSCIO"},"target":{"source":"page\/148261"}}],"42":[{"text":"Acrapex syscia","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ACRASYSC"},"target":{"source":"page\/148261"}}],"41":[{"text":"Manga belophora","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/MANGBELO"},"target":{"source":"page\/148261"}},{"text":"Sesamia sciagrapha","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SESASCIA"},"target":{"source":"page\/148261"}}],"40":[{"text":"Sesamia mesosticha","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SESAMESO"},"target":{"source":"page\/148261"}},{"text":"Sesamia plagiographa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/SESAPLAG"},"target":{"source":"page\/148261"}}],"20":[{"text":"Apospasta aethalopa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSAETH"},"target":{"source":"page\/148261"}},{"text":"Apospasta synclera","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSSYNC"},"target":{"source":"page\/148261"}}],"18":[{"text":"Apospasta fulvida","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01APODIP"},"target":{"source":"page\/148261"}}],"19":[{"text":"Apospasta jacksoni","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSJACK"},"target":{"source":"page\/148261"}},{"text":"Apospasta kennedyi","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSKENN"},"target":{"source":"page\/148261"}}],"21":[{"text":"Apospasta rhodina","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSRHOD"},"target":{"source":"page\/148261"}},{"text":"Apospasta townsendi","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/APOSTOWN"},"target":{"source":"page\/148261"}}],"23":[{"text":"Mythimna aenictopa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/LEUCANCT"},"target":{"source":"page\/148261"}},{"text":"Vietteania catadela","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/VIETCATA"},"target":{"source":"page\/148261"}}],"35":[{"text":"Plusiophaes argosticta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/PLUSARGO"},"target":{"source":"page\/148261"}}]}}';	
	
			//$j = '{"@type":["AnnotationPage"],"items":{"36":[{"text":"Malgadonta anjouanica","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/MALGANJO"},"target":{"source":"page\/296840"}}],"48":[{"text":"Acroctena arguta","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ACROARGU"},"target":{"source":"page\/296840"}}],"46":[{"text":"Acroctena nebulosa","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ACRONEBU"},"target":{"source":"page\/296840"}}],"152":[{"text":"Ambina andranoma","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AMBIANDR"},"target":{"source":"page\/296840"}}],"150":[{"text":"Ambina septentrionalis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AMBISEPT"},"target":{"source":"page\/296840"}}],"153":[{"text":"Ambina trioculata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/AMBITRIO"},"target":{"source":"page\/296840"}}],"156":[{"text":"Antoroka munda","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ANTOMUND"},"target":{"source":"page\/296840"}}],"19":[{"text":"Antsalova pauliani","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/ANTSPAUL"},"target":{"source":"page\/296840"}}],"90":[{"text":"Epicerurina grisea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EPICGRIS"},"target":{"source":"page\/296840"}}],"181":[{"text":"Eutrotonotus catalai","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/EUTRCATA"},"target":{"source":"page\/296840"}}],"57":[{"text":"Nesochadisra protea","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/NESOPROT"},"target":{"source":"page\/296840"}}],"120":[{"text":"Ochrosomera vanja","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/OCHRVANJ"},"target":{"source":"page\/296840"}}],"0":[{"text":"Rhynchophalerina inexpectata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/RHYNINEX"},"target":{"source":"page\/296840"}}],"65":[{"text":"Vietteella nigrilineata","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/VIETNIGR"},"target":{"source":"page\/296840"}}],"207":[{"text":"Hypsoides ambrensis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/01HYPAMB"},"target":{"source":"page\/296840"}}],"218":[{"text":"Hypsoides semifusca","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPSSEMI"},"target":{"source":"page\/296840"}},{"text":"Hypsoides singularis","body":{"id":"https:\/\/www.afromoths.net\/species_by_code\/HYPSSING"},"target":{"source":"page\/296840"}}]}}';
			
			$annotationPage = json_decode($j);
			$annotation_pages = $annotationPage; // set this if we want annotation sidebar
		}
		
		
		
		
		$title = $work->name;
	
		html_start($title, $doc);
		
		echo '<div id="panel">
<a href="javascript:close_panel()">╳</a>
<div id="info"></div>
</div>';
		
		
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
			
			$image_url = $work->thumbnailUrl;
			
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
		
		// segmentation coverage (maybe we can do this in CouchDB, means we could retrieve these for title view as well)
		if (isset($work->hasPart))
		{
			$coverage = array();
			$pos = -1;
			
			foreach ($work->hasPart as $page)
			{
				if (isset($page->isPartOf))
				{
					if ($pos == -1)
					{
						// start
						$pos = $page->position;
						$coverage[$pos] = $pos;					
					}
					else
					{
						// continue
						$coverage[$pos] = $page->position;
					}
				}
				else
				{
					$pos = -1;
				}
			}
			
			// print_r($coverage);
			
			$num_pages = count($work->hasPart);
			
			echo '<div class="coverage">';
			
			foreach ($coverage as $start => $end)
			{
				echo '<div class="block" style="left:' . round($start/$num_pages * 100) . '%;width:' . round(($end - $start)/$num_pages * 100) . '%;"></div>';
			}
			
			echo '</div>';
		}
		

		
		// different views of item
		echo '<div>';
		echo '<a href="item/' . $id . '">pages</a>';
		echo ' | ';
		echo '<a href="item/' . $id . '/thumbnails">thumbnails</a>';
		
		if ($image_gallery)
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
 		if ($display_mode == 'figures' && $image_gallery) 
 		{			
 				echo '<div class="gallery">';
				echo '<ul>';
				foreach ($image_gallery->items as $page)
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
					echo '<li';
					
					// highlight pages that are in segments
					if (isset($page->isPartOf))
					{
						echo ' class="selected"';
					}
					
					echo '>';
					echo '<a href="page/' . str_replace('pagethumb/', '', $page->thumbnailUrl) . '">';
					
					$image_url = get_page_image_url(str_replace('pagethumb/', '', $page->thumbnailUrl));
					
					$image_url = $page->thumbnailUrl;
					
					echo '<img '
						//. ' style="filter: grayscale(100%) contrast(150%)"'
						. ' loading="lazy" src="' . $image_url . '" onerror="retry(this)">';
					
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
			foreach ($list->dataFeedElement as $part) // note that we are iterating over "list" which is parts
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
			
			if ($display_google_sidebar)
			{
				// experiment with Google-style search result marker.
				// could be used to display search result location, or external links 
				// to pages in item (i.e., pages of external value)
				
				// we will need a list of page-level annotations that we will place on this
				// sidebar, and need a way to display them.
				
				
				echo '<div style="background-color:white;position:fixed;width:1em;height:calc(100vh - var(--nav-height));left:calc(100% - 2em)">';
				
					// divide into pages
					$num_pages = count($pages);
					$tick_height = round(100 / $num_pages, 2);
					
					for ($i = 0; $i < $num_pages; $i++)
					{
						if (isset($annotation_pages->items->{$i}) && $annotation_pages)
						{
							$extra = 'background-color:red;';
							$data = json_encode($annotation_pages->items->{$i});
														
							echo '<div style="' . $extra . 'position:absolute;width:1em;top:' . ($tick_height * $i) . '%;height:' . $tick_height . '%;" onclick="scrolltopage(' . $i . ');show_panel(&quot;' . rawurlencode($data) . '&quot;);">';
							echo '</div>';

						}
						else
						{
							$extra = '';
							echo '<div style="' . $extra . 'position:absolute;width:1em;top:' . ($tick_height * $i) . '%;height:' . $tick_height . '%;" onclick="scrolltopage(' . $i . ');">';
							echo '</div>';	
						}
						// border:1px solid rgb(192,192,192);
					
				
					}
				echo '</div>';
			}
			
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

		echo '<div>';

		echo '<ul class="image-grid">';
		foreach ($list->dataFeedElement as $item)
		{
			echo '<li>';
			echo '<a href="' . $item->{'@id'} . '">';
			
			$image_url = get_page_image_url(str_replace('pagethumb/', '', $item->thumbnailUrl));
			
			$image_url = $item->thumbnailUrl;
			
			echo '<img loading="lazy" src="' . $image_url . '" onerror="retry(this)">';
			
			echo '<div>' . $item->name . '</div>';
			
			// coverage?
			if (isset($item->coverage))
			{
				$num_pages = count($item->hasPart);
				echo '<div class="coverage">';			
				foreach ($item->coverage as $start => $end)
				{
					echo '<div class="block" style="left:' . round($start/$num_pages * 100) . '%;width:' . round(($end - $start)/$num_pages * 100) . '%;"></div>';
				}				
				echo '</div>';
			}			
			
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

	// Nouveau search
	if (0)
	{
		$doc = get_search_results($query);
		if ($doc)
		{
			$title = $doc->name;
		
			html_start($title, $doc);
			
			echo '<div>';
			/*
			// create a side bar for information on the search
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
			*/
			
			echo '<ul class="media-list">';
			foreach ($doc->dataFeedElement as $hit)
			{
				// text search
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
			
			echo '  </main>';
			//</div>';
			
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
	
	// Simple name search
	if (1)
	{
		$doc = get_name_search_results($query);
		
		if ($doc)
		{
			$title = $doc->name;
		
			html_start($title, $doc);
			
			echo '<div style="overflow-y:auto;border:2x solid red;height:calc(100vh - var(--nav-height)">';
			/*
			// create a side bar for information on the search
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
			*/
			
			// names
			
			// group 
			
			$years = array();
			
			foreach ($doc->dataFeedElement as $hit)
			{
				$item = new stdclass;
				$item->data = $hit->selector;
			
				if (isset($hit->source))
				{
					$item->link = $hit->source->{'@id'};
					if (isset($hit->source->isPartOf))
					{
						$ItemID = $hit->source->isPartOf[0]->{'@id'};
						$year = $hit->source->isPartOf[0]->datePublished;
						
						if (!isset($years[$year]))
						{
							$years[$year] = array();
						}
						if (!isset($years[$year][$ItemID]))
						{
							$years[$year][$ItemID] = array();
						}
						$years[$year][$ItemID][] = $item;
					}
				}
			}
			
			// sort
			ksort($years);
			
			// output
			/*
			echo '<pre>';
			print_r($years);
			echo '</pre>';
			*/
			
			echo '<ul>';
			foreach ($years as $year => $items)
			{
				echo '<li>' . $year;
				echo '<ul>';
				foreach ($items as $item => $hits)
				{
					echo '<li>' . $item;
					echo '<ul>';
					foreach ($hits as $hit)
					{
						echo '<li>';
						echo '<a href="' . $hit->link . '">';
						echo $hit->data->prefix . '<b>' . $hit->data->exact . '</b>' . $hit->data->suffix;
						echo '</a>';
						echo '</li>';
					}
					echo '</ul>';
					echo '</li>';				
				}			
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
			
			//echo '  </main>';
			echo '</div>';
			
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
// Redirect to image for a page (use $is_thumbnail to get thumbnail)
function display_page_thumbnail($PageID, $is_thumbnail = true)
{
	$url = get_page_image_url($PageID, $is_thumbnail);
	header("Location: $url");
}

//----------------------------------------------------------------------------------------
// IIIF info on page
function display_page_iiif_info($PageID)
{
	global $config;
	
	$info = new stdclass;
	$info->{'@context'} = 'http://iiif.io/api/image/2/context.json';
	$info->{'@id'} = $config['web_server'] . $config['web_root'] . 'pageimage/' . $PageID;

	$info->protocol = 'http://iiif.io/api/image';
	
	$wh = get_page_width_height($PageID);
	$info->width = $wh[0];
	$info->height = $wh[1];	
	
	$info->profile = ['http://iiif.io/api/image/2/level0.json'];
	$info->formats = ['webp'];
	$info->qualities = ['default'];
	
	header ("Content-Type: application/json");
	header('Access-Control-Allow-Origin: *');

	echo json_encode($info);
}

//----------------------------------------------------------------------------------------
// Display page text
function display_page_text($PageID)
{
	$text = get_page_text($PageID);
	header("Content-type: text/plain; charset=utf-8");
	echo $text;
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
		
		if (isset($_GET['iiif']))
		{	
			$display_mode = 'iiif'; 
		} 				
		
		if ($item != '')	
		{			
			if (!$handled)
			{
				switch ($display_mode)
				{
					case 'iiif':
						display_item_iiif($item);
						break;
						
					default:
						display_item($item, $offset, $display_mode);
						break;
				}
						
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
	
	// page image thumbnails
	if (!$handled)
	{
		if (isset($_GET['pagethumb']))
		{
			display_page_thumbnail($_GET['pagethumb'], true);
			$handled = true;		
		}
	}
	
	// page images
	if (!$handled)
	{
		if (isset($_GET['pageimage']))
		{
			if (isset($_GET['info']))
			{	
				display_page_iiif_info($_GET['pageimage']);
				$handled = true;	
			}			
		
			if (!$handled)
			{
				display_page_thumbnail($_GET['pageimage'], false);
				$handled = true;
			}
		}
	}
	
	// page text
	if (!$handled)
	{
		if (isset($_GET['pagetext']))
		{
			display_page_text($_GET['pagetext'], true);
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

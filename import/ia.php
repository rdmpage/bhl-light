<?php

// Code to process IA files and upload a layout document to CouchDB

require_once (dirname(__FILE__) . '/djvu-to-datalab.php');
require_once (dirname(__FILE__) . '/hocr-to-datalab.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/imgproxy.php');


//----------------------------------------------------------------------------------------
// Parse BHL mets to get BHL page numbers
function mets($xml)
{
	$pages = array();
	
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath->registerNamespace('mods', 'http://www.loc.gov/mods/v3');
	$xpath->registerNamespace('mets', 'http://www.loc.gov/METS/');
	
	$nodeCollection = $xpath->query ('//mets:structMap/mets:div/mets:div[@TYPE="page"]');
	foreach($nodeCollection as $node)
	{
		$attributes = array();
	
		if ($node->hasAttributes()) 
		{ 
			$attrs = $node->attributes; 		
			foreach ($attrs as $i => $attr)
			{
				$attributes[$attr->name] = $attr->value; 
			}
		}
		
		if (isset($attributes['ORDER']))
		{
			foreach ($xpath->query('mets:fptr/@FILEID', $node) as $fileid)
			{
				if (preg_match('/page(\d+)/', $fileid->firstChild->nodeValue, $m))
				{
					$order = $attributes['ORDER']; 
					$order = str_pad($order, 4, '0', STR_PAD_LEFT);
					$pages[$order] = $m[1];
				}
			}
		}
	
	}
	
	return $pages;
}

//----------------------------------------------------------------------------------------
function get_ia($ia, $filename, $force = false)
{
	$dir = dirname(__FILE__)  . "/" . $ia;
	
	$file_path = $dir . '/' . $filename;

	if (!file_exists($file_path) || $force)
	{
		$url = 'https://archive.org/download/' . $ia . '/' . $filename;
		$command = "curl --insecure -w \"%{http_code}\" -L -o '$file_path' '$url'";
				
		$result_code = 0;
		
		$output = array();
		
		echo $command . "\n";
		exec($command, $output, $result_code);
		
		print_r($output);
		
		// check OK
		$ok = $result_code == 0;
		if ($ok)
		{
			$ok = $output[0] == 200;
		}
		if (!$ok)
		{
			// badness
			unlink($file_path);
		}
	}

}

//----------------------------------------------------------------------------------------
function fetch_ia($ia)
{
	// put everything in a folder
	$dir = dirname(__FILE__)  . "/" . $ia;
	if (!file_exists($dir))
	{
		$oldumask = umask(0); 
		mkdir($dir, 0777);
		umask($oldumask);
	}	

	// get images (do we actually need these?)
	//get_ia($ia, $ia . '_jp2.zip');
	
	// hOCR
	get_ia($ia, $ia . '_hocr.html');
	
	// DjVu
	get_ia($ia, $ia . '_djvu.xml');
	
	/*
	// BHL mets
	get_ia($ia, $ia . '_bhlmets.xml');

	// scan data
	get_ia($ia, $ia . '_scandata.xml');
	*/
}

//----------------------------------------------------------------------------------------
// Don't use this...
function layout_to_html($layout, $image_width = 700)
{
	$html = '';
	$html .= '<html>';
	$html .=  '<head>';
	$html .=  '<style>
	
	body {
		background-color:gray;
	}
	
	img {
		-webkit-user-select:none;
		-moz-user-select:none;
		user-select:none;
				
		-webkit-user-drag: none; 	
		-moz-user-drag: none; 	
		user-drag: none;  	
	}

	.page {
		background-color:white;
		position:relative;
		margin: 0 auto;
		margin-bottom:1em;
		margin-top:1em;	
		box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		
	}
	
	
	/*	
	
.Text { background:green; opacity:0.2; } /* Paragraph */
.TextInlineMath { background:green; opacity:0.2; } /* Paragraph */

.Figure { background:red; opacity:0.2; }
.Picture { background:red; opacity:0.2; }


.Caption { background:yellow; opacity:0.2; }
.Table { background:blue; opacity:0.2; }
.PageHeader { background:orange; opacity:0.2; }
.PageFooter { background:orange; opacity:0.2; }
.SectionHeader { background:red; opacity:0.2; }
.Title { background:blue; opacity:0.2; }
.ListItem { background:blue; opacity:0.2; }
.Footnote { background:orange; opacity:0.2; }
*/

	</style>';
	$html .=  '</head>';
	
	$html .=  '<body>';
	
	for ($i = 0; $i < $layout->page_count; $i++)
	{
		$page_width = $layout->pages[$i]->image_bbox[2] - $layout->pages[$i]->image_bbox[0];
		$page_height = $layout->pages[$i]->image_bbox[3] - $layout->pages[$i]->image_bbox[1];
		
		$scale = $image_width / $page_width;
		
		$html .= '<div class="page" style="'
			. 'width:' . $scale * $page_width . 'px;'		
			. 'height:' . $scale * $page_height . 'px;'
			. 'border:1px solid rgb(192,192,192);'
			. '-webkit-user-select:none;'
			. '-moz-user-select:none;'
			. 'user-select:none;'
			
			. '">';
			
		$image_url = 'https://archive.org/download/' . $layout->internetarchive . '/page/n' . $i . '_w' . $image_width . '.jpg';

		// $image_url = 'https://bhl-open-data.s3.amazonaws.com/images/' . $layout->internetarchive . '/' . $layout->pages[$i]->internetarchive;

		$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_url, $image_width);

		$html .= '<img src="' . $image_url  . '" width="' . $image_width . '" draggable="false">';
		
		// style="-webkit-filter: grayscale(100%);"
		//  contrast(200%);
		
		// text
		foreach ($layout->pages[$i]->text_lines as $line)
		{
			$width = $line->bbox[2] - $line->bbox[0];
			$height = $line->bbox[3] - $line->bbox[1];
		
			$html .= '<div style="'
				. 'position:absolute;'
//				. 'border:1px solid black;'
				. 'left:' . $line->bbox[0] * $scale . 'px;'
				. 'top:' .  $line->bbox[1] * $scale . 'px;'
				. 'width:' . $width * $scale . 'px;'
				. 'height:' . $height * $scale . 'px;'
				
				
				. 'font-size:' . $height * $scale  . 'px;'
				. 'text-align-last:justify;'
				. 'overflow:hidden;'
				
				. 'color:transparent;' 

				. '-webkit-user-select:text;'
				. '-moz-user-select:text;'
				. 'user-select:text;'
				
				
				. '">';
				
			$html .= $line->text;
				
			$html .= '</div>';
		
		}
		
		
		$html .= '</div>';
	}
	
	$html .= '</body>';
	$html .= '</html>';
	
	return $html;
}

//----------------------------------------------------------------------------------------

$identifiers = array(
'insectsofsamoaot01natu',
'insectsofsamoaot01unse',
//'asiaticherpetolo05asia', // failed
//'asiaticherpetolov112asia', // failed to match
'australianentom22ento',
'amphibianreptil17',
'contributionsam1amer',
);

$identifiers = array(
//'contributionsam1amer',


'Amphibianreptil5',
'Amphibianreptil5B',
'amphibianreptil212000prov',
'amphibianreptil222000prov',
'amphibianreptil111996prov',
'Amphibianreptil6',
'Amphibianreptil6A',
'Amphibianreptil6B',
'Amphibianreptil8',
'Amphibianreptil5C',
'Amphibianreptil7',
'Amphibianreptil5A',
'Amphibianreptil8A',
'Amphibianreptil9A',
'Amphibianreptil9',
'Amphibianreptil4',
'amphibianreptil11',
'amphibianreptil10a',
'amphibianreptil10',
'amphibianreptil11a',
'amphibianreptil12',
'amphibianreptil12a',
'amphibianreptil312004prov',
'amphibianreptil14a',
'amphibianreptil13a',
'amphibianreptil14b',
'amphibianreptil17',
'amphibianreptil16a',
'amphibianreptil14',
'amphibianreptil16',
'amphibianreptil15',
'amphibianreptil13',
'amphibianreptil15a',
);

$identifiers = array(
'bonnzoological6412015zool', // don't match
'bonnzoologicalb61zool', // djvu
);




$identifiers = array(
'bonnzoologicalb71zoola',
'europeanjournal102muse', // djvu...
);

$identifiers = array(
//'contributionsam1amer',
//'siblingspeciest1956mich',
//'europeanjournal102muse',
//'bonnzoologicalb71zoola',
//'analesdelmuseod34muse',
'tijdschriftvoore1997nede',
);


foreach ($identifiers as $ia)
{
	echo "Fetching $ia\n";
	fetch_ia($ia);
	
	echo "Converting hOCR to layout $ia\n";
	$doc = hocr_to_datalab($ia);
	
	if (!$doc)
	{
		echo "Converting DjVu to layout $ia\n";
		$doc = djvu_to_datalab($ia);	
	}
	
	if ($doc)
	{	
		$ItemID = get_item_from_barcode($ia);
	
		if ($ItemID != 0)
		{
			$pages = get_pages_for_item($ItemID);
			
			for ($i = 0; $i < $doc->page_count; $i++)
			{
				//print_r($pages[$i]);
				if (isset($pages[$i]->name))
				{
					$doc->pages[$i]->label = $pages[$i]->name;
				}
			}
		}
	
		echo "Uploading $ia\n";
	
		// upload to CouchDB
		if (1)
		{
			$force_upload = true;
			//$force_upload = false;
		
			$doc->_id = 'layout/' . $doc->internetarchive;
			
			$exists = $couch->exists($doc->_id);
			
			if ($exists && !$force_upload)
			{
				echo "Have " . $doc->_id . " already!\n";
			}
			else
			{
				if ($exists && $force_upload)
				{
					$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
				}
		
				$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
				var_dump($resp);	
			}
		}		
		
	}
}

?>

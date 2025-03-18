<?php

// Upload a layout document to CouchDB

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(__FILE__) . '/ia.php');
require_once (dirname(__FILE__) . '/djvu-to-datalab.php');
require_once (dirname(__FILE__) . '/hocr-to-datalab.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

$identifiers = get_ia_for_title(68619);

$identifiers = get_ia_for_title(211788);

$identifiers = get_ia_for_title(10229);

$identifiers = get_ia_for_title(5943);

$identifiers = get_ia_for_title(206514);

$identifiers = get_ia_for_title(57881);

print_r($identifiers);

$identifiers = array('Amphibianreptil5C');

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

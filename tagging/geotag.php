<?php

// Get text for item and add geotags to it using regular expressions

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(dirname(__FILE__)) . '/import/sqltojson.php');

require_once (dirname(__FILE__) . '/annotate.php');

//----------------------------------------------------------------------------------------
// We tag at the level of lines within an item (i.e., we use a "layout" object).
// Annotations are stored as arrays of arrays, where the first level is zero-based page 
// number, second level is line number, third is array of annotations on a line.
// Annotations follow simplified hypothes.is JSON (more or less).
function annotate_item($ia)
{
	global $couch;
	global $config;
	
	$item_annotations = array();
		
	// get text for every page in item
	$startkey = array($ia);
	$endkey = array($ia, new stdclass);
	
	$parameters = array(
		'startkey' 		=> json_encode($startkey),
		'endkey'		=> json_encode($endkey),
	);
	
	$url = '_design/layout/_view/text?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$resp_obj = json_decode($resp);	

	foreach ($resp_obj->rows as $row)
	{
		$text = $row->value;
		
		$lines = explode("\n", $text);
		
		$n = count($lines);
		
		for ($i = 0; $i < $n; $i++)
		{
			// geotagging
			$annotations = tag_geo($lines[$i]);
			
			if (count($annotations) > 0)
			{
				if (!isset($item_annotations[$row->key[1]]))
				{
					$item_annotations[$row->key[1]] = array();
				}
				$item_annotations[$row->key[1]][$i] = $annotations;
			}
		}		
	}
	
	//print_r($item_annotations);
	
	return $item_annotations;
}	

$titles = array(
144642, // EJT
82521,  // Bonn
190323, // The Australian Entomologist
57881,  // Amphibian & reptile conservation
);

if (1)
{
	// get list of all titles in BHL-Light
	// http://127.0.0.1:5984/bhl-lite/_design/stats/_view/titles?group_level=1
	$json = '{
		"rows": [
			{
				"key": "10088",
				"value": 147
			},
			{
				"key": "101429",
				"value": 29
			},
			{
				"key": "10229",
				"value": 13
			},
			{
				"key": "102339",
				"value": 1
			},
			{
				"key": "10241",
				"value": 12
			},
			{
				"key": "10416",
				"value": 9
			},
			{
				"key": "105698",
				"value": 13
			},
			{
				"key": "10597",
				"value": 5
			},
			{
				"key": "10903",
				"value": 10
			},
			{
				"key": "110138",
				"value": 7
			},
			{
				"key": "11516",
				"value": 72
			},
			{
				"key": "117696",
				"value": 51
			},
			{
				"key": "118672",
				"value": 2
			},
			{
				"key": "11933",
				"value": 43
			},
			{
				"key": "119421",
				"value": 1
			},
			{
				"key": "119424",
				"value": 1
			},
			{
				"key": "119515",
				"value": 1
			},
			{
				"key": "119516",
				"value": 1
			},
			{
				"key": "119522",
				"value": 22
			},
			{
				"key": "119597",
				"value": 1
			},
			{
				"key": "119777",
				"value": 1
			},
			{
				"key": "119879",
				"value": 27
			},
			{
				"key": "12032",
				"value": 1
			},
			{
				"key": "120414",
				"value": 1
			},
			{
				"key": "120442",
				"value": 1
			},
			{
				"key": "120507",
				"value": 1
			},
			{
				"key": "122512",
				"value": 71
			},
			{
				"key": "12260",
				"value": 27
			},
			{
				"key": "12276",
				"value": 13
			},
			{
				"key": "12277",
				"value": 7
			},
			{
				"key": "12498",
				"value": 4
			},
			{
				"key": "12601",
				"value": 2
			},
			{
				"key": "127039",
				"value": 37
			},
			{
				"key": "127815",
				"value": 7
			},
			{
				"key": "12908",
				"value": 4
			},
			{
				"key": "129215",
				"value": 2
			},
			{
				"key": "129346",
				"value": 26
			},
			{
				"key": "12937",
				"value": 25
			},
			{
				"key": "12938",
				"value": 48
			},
			{
				"key": "130490",
				"value": 98
			},
			{
				"key": "13390",
				"value": 1
			},
			{
				"key": "134662",
				"value": 2
			},
			{
				"key": "139317",
				"value": 25
			},
			{
				"key": "141",
				"value": 7
			},
			{
				"key": "144642",
				"value": 238
			},
			{
				"key": "145272",
				"value": 70
			},
			{
				"key": "146268",
				"value": 57
			},
			{
				"key": "148588",
				"value": 12
			},
			{
				"key": "149317",
				"value": 1
			},
			{
				"key": "149557",
				"value": 1
			},
			{
				"key": "149559",
				"value": 356
			},
			{
				"key": "149608",
				"value": 19
			},
			{
				"key": "14964",
				"value": 2
			},
			{
				"key": "149829",
				"value": 5
			},
			{
				"key": "149832",
				"value": 6
			},
			{
				"key": "149841",
				"value": 11
			},
			{
				"key": "150137",
				"value": 4
			},
			{
				"key": "152899",
				"value": 35
			},
			{
				"key": "153180",
				"value": 2
			},
			{
				"key": "155000",
				"value": 228
			},
			{
				"key": "156819",
				"value": 41
			},
			{
				"key": "15774",
				"value": 296
			},
			{
				"key": "158815",
				"value": 40
			},
			{
				"key": "158834",
				"value": 69
			},
			{
				"key": "158870",
				"value": 16
			},
			{
				"key": "159712",
				"value": 31
			},
			{
				"key": "16059",
				"value": 32
			},
			{
				"key": "162187",
				"value": 31
			},
			{
				"key": "165323",
				"value": 10
			},
			{
				"key": "166349",
				"value": 28
			},
			{
				"key": "168319",
				"value": 68
			},
			{
				"key": "169110",
				"value": 75
			},
			{
				"key": "169282",
				"value": 72
			},
			{
				"key": "169354",
				"value": 2
			},
			{
				"key": "169356",
				"value": 44
			},
			{
				"key": "169557",
				"value": 30
			},
			{
				"key": "170271",
				"value": 1
			},
			{
				"key": "170272",
				"value": 1
			},
			{
				"key": "170273",
				"value": 1
			},
			{
				"key": "170274",
				"value": 2
			},
			{
				"key": "170780",
				"value": 1
			},
			{
				"key": "172098",
				"value": 1
			},
			{
				"key": "172099",
				"value": 2
			},
			{
				"key": "172229",
				"value": 2
			},
			{
				"key": "172236",
				"value": 1
			},
			{
				"key": "173711",
				"value": 1
			},
			{
				"key": "173712",
				"value": 1
			},
			{
				"key": "175306",
				"value": 74
			},
			{
				"key": "176213",
				"value": 75
			},
			{
				"key": "176508",
				"value": 1
			},
			{
				"key": "176509",
				"value": 1
			},
			{
				"key": "176510",
				"value": 1
			},
			{
				"key": "176511",
				"value": 1
			},
			{
				"key": "176512",
				"value": 1
			},
			{
				"key": "176609",
				"value": 1
			},
			{
				"key": "176610",
				"value": 1
			},
			{
				"key": "176611",
				"value": 1
			},
			{
				"key": "176612",
				"value": 1
			},
			{
				"key": "176693",
				"value": 1
			},
			{
				"key": "176718",
				"value": 1
			},
			{
				"key": "176726",
				"value": 1
			},
			{
				"key": "1805",
				"value": 3
			},
			{
				"key": "181031",
				"value": 9
			},
			{
				"key": "181154",
				"value": 1
			},
			{
				"key": "181155",
				"value": 1
			},
			{
				"key": "181156",
				"value": 1
			},
			{
				"key": "181157",
				"value": 1
			},
			{
				"key": "181158",
				"value": 1
			},
			{
				"key": "181159",
				"value": 1
			},
			{
				"key": "181160",
				"value": 1
			},
			{
				"key": "181467",
				"value": 1
			},
			{
				"key": "181468",
				"value": 1
			},
			{
				"key": "181469",
				"value": 1
			},
			{
				"key": "181470",
				"value": 1
			},
			{
				"key": "181471",
				"value": 1
			},
			{
				"key": "181472",
				"value": 1
			},
			{
				"key": "181473",
				"value": 1
			},
			{
				"key": "181474",
				"value": 1
			},
			{
				"key": "181490",
				"value": 1
			},
			{
				"key": "181695",
				"value": 1
			},
			{
				"key": "181698",
				"value": 1
			},
			{
				"key": "181699",
				"value": 1
			},
			{
				"key": "181701",
				"value": 1
			},
			{
				"key": "181702",
				"value": 2
			},
			{
				"key": "181703",
				"value": 1
			},
			{
				"key": "181723",
				"value": 1
			},
			{
				"key": "183393",
				"value": 1
			},
			{
				"key": "185432",
				"value": 1
			},
			{
				"key": "190322",
				"value": 1
			},
			{
				"key": "190323",
				"value": 90
			},
			{
				"key": "190324",
				"value": 1
			},
			{
				"key": "190325",
				"value": 1
			},
			{
				"key": "190326",
				"value": 1
			},
			{
				"key": "190327",
				"value": 1
			},
			{
				"key": "190328",
				"value": 3
			},
			{
				"key": "190329",
				"value": 1
			},
			{
				"key": "190330",
				"value": 1
			},
			{
				"key": "190331",
				"value": 1
			},
			{
				"key": "190332",
				"value": 1
			},
			{
				"key": "190333",
				"value": 1
			},
			{
				"key": "190608",
				"value": 1
			},
			{
				"key": "190609",
				"value": 1
			},
			{
				"key": "190610",
				"value": 1
			},
			{
				"key": "190611",
				"value": 1
			},
			{
				"key": "190625",
				"value": 1
			},
			{
				"key": "190626",
				"value": 1
			},
			{
				"key": "190627",
				"value": 1
			},
			{
				"key": "190630",
				"value": 1
			},
			{
				"key": "191058",
				"value": 1
			},
			{
				"key": "191060",
				"value": 2
			},
			{
				"key": "191064",
				"value": 1
			},
			{
				"key": "191466",
				"value": 2
			},
			{
				"key": "191479",
				"value": 1
			},
			{
				"key": "191481",
				"value": 1
			},
			{
				"key": "191483",
				"value": 1
			},
			{
				"key": "191485",
				"value": 1
			},
			{
				"key": "191488",
				"value": 1
			},
			{
				"key": "191867",
				"value": 1
			},
			{
				"key": "191869",
				"value": 1
			},
			{
				"key": "191871",
				"value": 1
			},
			{
				"key": "191872",
				"value": 1
			},
			{
				"key": "191873",
				"value": 1
			},
			{
				"key": "191874",
				"value": 1
			},
			{
				"key": "191875",
				"value": 2
			},
			{
				"key": "191876",
				"value": 1
			},
			{
				"key": "192476",
				"value": 1
			},
			{
				"key": "192784",
				"value": 1
			},
			{
				"key": "192829",
				"value": 2
			},
			{
				"key": "192885",
				"value": 1
			},
			{
				"key": "192942",
				"value": 1
			},
			{
				"key": "192944",
				"value": 1
			},
			{
				"key": "192945",
				"value": 1
			},
			{
				"key": "193490",
				"value": 1
			},
			{
				"key": "195262",
				"value": 1
			},
			{
				"key": "197976",
				"value": 4
			},
			{
				"key": "199667",
				"value": 1
			},
			{
				"key": "199669",
				"value": 1
			},
			{
				"key": "200101",
				"value": 1
			},
			{
				"key": "202405",
				"value": 1
			},
			{
				"key": "204608",
				"value": 80
			},
			{
				"key": "206514",
				"value": 24
			},
			{
				"key": "2087",
				"value": 111
			},
			{
				"key": "209514",
				"value": 1
			},
			{
				"key": "209695",
				"value": 1
			},
			{
				"key": "209943",
				"value": 1
			},
			{
				"key": "210672",
				"value": 1
			},
			{
				"key": "210747",
				"value": 44
			},
			{
				"key": "211788",
				"value": 28
			},
			{
				"key": "212146",
				"value": 51
			},
			{
				"key": "21727",
				"value": 1
			},
			{
				"key": "218607",
				"value": 1
			},
			{
				"key": "2804",
				"value": 9
			},
			{
				"key": "314",
				"value": 13
			},
			{
				"key": "37912",
				"value": 17
			},
			{
				"key": "3882",
				"value": 44
			},
			{
				"key": "39684",
				"value": 24
			},
			{
				"key": "39988",
				"value": 6
			},
			{
				"key": "40011",
				"value": 1
			},
			{
				"key": "40366",
				"value": 1
			},
			{
				"key": "40896",
				"value": 31
			},
			{
				"key": "41367",
				"value": 4
			},
			{
				"key": "4227",
				"value": 19
			},
			{
				"key": "42670",
				"value": 11
			},
			{
				"key": "43408",
				"value": 45
			},
			{
				"key": "44963",
				"value": 144
			},
			{
				"key": "45402",
				"value": 65
			},
			{
				"key": "45481",
				"value": 109
			},
			{
				"key": "46370",
				"value": 21
			},
			{
				"key": "46858",
				"value": 1
			},
			{
				"key": "49730",
				"value": 16
			},
			{
				"key": "49914",
				"value": 29
			},
			{
				"key": "50141",
				"value": 4
			},
			{
				"key": "50228",
				"value": 18
			},
			{
				"key": "514",
				"value": 15
			},
			{
				"key": "51416",
				"value": 20
			},
			{
				"key": "51678",
				"value": 138
			},
			{
				"key": "51828",
				"value": 1
			},
			{
				"key": "52116",
				"value": 11
			},
			{
				"key": "52304",
				"value": 38
			},
			{
				"key": "52319",
				"value": 1
			},
			{
				"key": "53832",
				"value": 8
			},
			{
				"key": "53833",
				"value": 3
			},
			{
				"key": "54584",
				"value": 3
			},
			{
				"key": "56405",
				"value": 1
			},
			{
				"key": "5766",
				"value": 2
			},
			{
				"key": "57881",
				"value": 33
			},
			{
				"key": "5943",
				"value": 83
			},
			{
				"key": "59881",
				"value": 1
			},
			{
				"key": "6170",
				"value": 183
			},
			{
				"key": "61893",
				"value": 37
			},
			{
				"key": "62169",
				"value": 64
			},
			{
				"key": "62642",
				"value": 21
			},
			{
				"key": "63883",
				"value": 18
			},
			{
				"key": "66304",
				"value": 21
			},
			{
				"key": "6638",
				"value": 178
			},
			{
				"key": "66550",
				"value": 49
			},
			{
				"key": "6685",
				"value": 2
			},
			{
				"key": "66850",
				"value": 3
			},
			{
				"key": "68619",
				"value": 35
			},
			{
				"key": "68672",
				"value": 12
			},
			{
				"key": "6928",
				"value": 197
			},
			{
				"key": "698",
				"value": 59
			},
			{
				"key": "706",
				"value": 130
			},
			{
				"key": "730",
				"value": 61
			},
			{
				"key": "7383",
				"value": 1
			},
			{
				"key": "7414",
				"value": 147
			},
			{
				"key": "77306",
				"value": 72
			},
			{
				"key": "77526",
				"value": 1
			},
			{
				"key": "78705",
				"value": 21
			},
			{
				"key": "78760",
				"value": 21
			},
			{
				"key": "79076",
				"value": 50
			},
			{
				"key": "79636",
				"value": 15
			},
			{
				"key": "79642",
				"value": 9
			},
			{
				"key": "821",
				"value": 11
			},
			{
				"key": "82521",
				"value": 34
			},
			{
				"key": "86930",
				"value": 1
			},
			{
				"key": "87655",
				"value": 36
			},
			{
				"key": "8796",
				"value": 29
			},
			{
				"key": "891",
				"value": 5
			},
			{
				"key": "9243",
				"value": 21
			},
			{
				"key": "95451",
				"value": 1
			}
		]
	}';
	
	$obj = json_decode($json);
	
	$titles = [];
	
	foreach ($obj->rows as $row)
	{
		$titles[] = $row->key;
	}
}

$titles = [109981];

foreach ($titles as $TitleID)
{
	$items = get_items_for_title($TitleID);

	foreach ($items as $item)
	{	
		$ia = str_replace('https://archive.org/details/', '', $item->sameAs);
		
		$doc = new stdclass;
		$doc->_id = 'geotagged/' . $ia;
		
		// identifiers to (potentially) make search results easier to form
		$doc->internetarchive = $ia;
		
		// to make search results easier
		$doc->name = $item->name;
		$doc->bhl_id = $item->_id;
		
		$doc->annotations = annotate_item($ia);
		
		// store in CouchDB
		$force_upload = true;
		//$force_upload = false;
			
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
		
		// just do one
		exit();
	}
}

?>

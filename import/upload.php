<?php

// Extract data from SQLite and import into CouchDB

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/sqltojson.php');


//----------------------------------------------------------------------------------------
// Upload an object
function upload($doc, $force = false)
{
	global $config;
	global $couch;
	
	// debug
	if (!isset($doc->_id))
	{
		print_r($doc);
		exit();
	}

	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_part($PartID, $force = false)
{
	global $config;
	global $couch;
	
	$doc = get_part($PartID);
	
	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have $PartID " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
function upload_parts_for_item($ItemID, $force = false)
{
	$parts = get_parts_for_item($ItemID);
	
	foreach ($parts as $part)
	{
		upload($part, $force);
	}
}

//----------------------------------------------------------------------------------------
function upload_parts_for_title($TitleID, $force = false)
{
	// list of items for a title
	$items = get_items_for_title($TitleID);
	
	foreach ($items as $item)
	{
		$ItemID = str_replace('item/', '', $item->_id);
		echo $ItemID . "\n";
		upload_parts_for_item($ItemID, $force);
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_title($TitleID, $force = false)
{
	global $config;
	global $couch;
	
	$doc = get_title($TitleID);
		
	$exists = $couch->exists($doc->_id);
	
	if ($exists && !$force)
	{
		echo "Have TitleID " . $doc->_id . " already!\n";
	}
	else
	{
		if ($exists && $force)
		{
			$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
		}

		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);	
	}
}

//----------------------------------------------------------------------------------------
// Upload list of items for a title
function upload_items_for_title($TitleID, $force = false)
{
	global $config;
	global $couch;
	
	$items = get_items_for_title($TitleID);
	
	foreach ($items as $doc)
	{
		$exists = $couch->exists($doc->_id);
		
		if ($exists && !$force)
		{
			echo "Have ItemID " . $doc->_id . " already!\n";
		}
		else
		{
			if ($exists && $force)
			{
				$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
			}

			$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
			var_dump($resp);	
		}
	}
}

$TitleID = 206514;
//$TitleID = 210747; // Mycotaxon
//$TitleID = 212146; // The Zoogoer
$TitleID = 68619;
$TitleID = 7414; // The journal of the Bombay Natural History Society
$TitleID = 147681;
$TitleID = 105698; // The birds of Australia
$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso

$TitleID = 65344; // Madroño; a West American journal of botany
$TitleID = 211788;
$TitleID = 169356; // Austrobaileya: A Journal of Plant Systematics

$TitleID = 119522;

$TitleID = 139317; // Biodiversity journal
$TitleID = 48608; // Deutsche entomologische Zeitschrift
$TitleID = 2804;// Asiatic herpetological research
$TitleID = 204608; // Alytes: International Journal of Batrachology
$TitleID = 62642; // Bulletin of the Natural History Museum. Zoology series
$TitleID = 119879; // Flora of Southern Africa
$TitleID = 38931; // American Museum novitates
$TitleID = 95451; // Brasil 1979, 0
//$TitleID = 44792; // Anales de la Sociedad Científica Argentina

$TitleID = 730; // Biologia Centrali-Americana
$TitleID = 150137; // Biodiversity, biogeography and nature conservation in Wallacea and New Guinea
$TitleID = 49914; // Iberus : revista de la Sociedad Española de Malacología

$TitleID = 144642; // European Journal of Taxonomy

$TitleID = 158870; //Forktail
$TitleID = 57881;// Amphibian & reptile conservation
$TitleID = 85187;
$TitleID = 82521;
$TitleID = 190323;
$TitleID = 162187;

$TitleID = 206514; // Contributions of the American Entomological Institute

//$TitleID = 209695; // Sibling species of Trigona from Angola (Hymenoptera, Apinae)
//$TitleID = 10088; // Tijdschrift voor entomologie

//$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso

$TitleID = 57881;// Amphibian & reptile conservation

$TitleID = 82521; // Bonn zoological bulletin
$TitleID = 2804;// Asiatic herpetological research

$TitleID = 68619; // insectsofsamoa

$TitleID = 211788;

$TitleID = 10229;

$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle

$TitleID = 206514; // Contributions of the American Entomological Institute

$TitleID = 211788; // Metamorphosis Australia (no parts?)

$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle

$TitleID = 57881;

$TitleID = 144642; // European Journal of Taxonomy

$TitleID = 212146;

$TitleID = 204608;

$TitleID = 190323;

$TitleID = 158870;

$TitleID = 10088;
$TitleID = 162187;

$TitleID = 169356;
//$TitleID = 7414;
$TitleID = 210747;
$TitleID = 49914;

$TitleID = 9243;

$TitleID = 181031;
$TitleID = 117696;

$TitleID = 210877; // BDJ, item barcodes in local database don't match BHL now

$TitleID = 78705;
//$TitleID = 68672;

$TitleID = 79076; // Nota lepidopterologica

$TitleID = 130490; // Pacific Insects

$TitleID = 181031;
$TitleID = 169356;

// redo all 36
$titles = array(
2804,
5943,
7414,
9243,
10088,
10229,
49914,
57881,
62642,
68619,
68672,
78705,
79076,
82521,
95451,
105698,
117696,
119522,
119879,
130490,
139317,
144642,
147681,
150137,
152899,
158870,
162187,
169356,
181031,
190323,
204608,
206514,
209695,
210747,
211788,
212146,
);

$titles = array(
53832, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
53833, // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
2804, // Asiatic herpetological research
46858, // She wa yan jiu = Chinese herpetological research
40011, // Chinese herpetological research
);

$titles = array(
181469, // Faune de Madagascar : lépidoptères. 29, Insectes
);

$titles = array(
86930, // Illustrationes florae insularum Maris Pacifici
);

$titles = array(
45481 // Genera insectorum
);

/*
$titles = array(
63883 // Journal of the Botanical Research Institute of Texas
);
*/

$titles = array(

//10416, // Illustrations of the zoology of South Africa : consisting chiefly of figures a
//11516, // Transactions of the Entomological Society of London
//51678, // The journal of the Asiatic Society of Bengal
//79636,
//12277,
//11933,
//59881, // Heteroceres nouveaux de l'Amerique du Sud
//10597, // Heteroceres nouveaux de l'Amerique du Sud
//52304, // Mélanges exotico-entomologiques
//4227,
//15774,
//21727,
//61893,
//168319,
//66304,
//169354
//3882,
);

// Faune de Madagascar 
$titles = array(
170780,
176510,
176693,
176508,
176610,
172229,
172236,
181154,
181472,
181723,
176509,
181155,
181157,
181470,
181699,
181701,
181156,
181158,
181159,
181469,
181474,
190610,
190625,
190626,
191064,
170274,
191488,
170271,
170273,
172099,
190322,
190330,
190333,
185432,
191483,
191867,
191873,
173711,
173712,
176611,
190324,
190326,
176511,
176512,
190608,
190611,
176718,
176726,
181160,
181468,
181703,
181473,
200101,
181702,
183393,
190325,
190332,
191479,
191481,
191485,
191871,
191875,
190327,
190331,
190609,
191869,
191058,
191060,
191874,
192476,
192829,
192885,
192945,
170272,
176609,
176612,
181467,
181695,
190328,
191872,
191876,
192784,
192942,
172098,
181471,
181490,
181698,
190329,
190627,
190630,
191466,
192944,
);

$titles = array(698);

$titles = array(159712);

$titles = array(10903);

$titles = array(122512);

$titles = array(44963);

$titles = array(706,169557);

$titles = array(
//39684,
//39988,
//40896,

//101429,

//156819,

//78760,

7383,
);

$titles=array(
6685, // Flora Capensis : sistens plantas promontorii Bonæ Spei Africes : secundum systema sexuale emendatum
821, // Flora capensis :being a systematic description of the plants of the Cape colony, Caffraria, & Port Natal (and neighbouring territories)

141, // Flora australiensis:a description of the plants of the Australian territory
//16515, // Flora australiensis:a description of the plants of the Australian territory

16059, // Icones plantarum or figures, with brief descriptive characters and rema

);

$titles=array(
//6928,
//42670
//2087,
//314,
//62169,
//45402
);


$titles=array(37912);

$titles=array(
6638,
12938,
12937,
);

$titles=array(
12260,
12276,
13390,
);

$titles=array(
514,
14964,
145272,
146268,
148588,
149559,
149608,
149832,
149841,
155000,
158815,
158834,
//162187,
166349,
169110,
169282,
175306,
176213,
);


$titles=array(119421,119424,119515,119516,119597,119777);

$titles=array(49730);

$titles=array(
129346, // Boletim do Museu Paraense Emílio Goeldi
127815,
129215,
134662,
149557,

43408, // Annali del Museo civico di storia naturale Giacomo Doria
50228, // Iheringia. Série zoologia
46370, // Revista chilena de entomología
10241, // Revista do Museu Paulista
);

$titles=array(
66850
);

$titles=array(
730,
50141,
56405,
73554,
73594,
73565,
73595,
73555,
54584,
);

$titles=array(
//8796,
//1805,
//87655,
//52116,
// 12498,
//6170,
//41367, 
//79642,
178260,
149317,
40366,
);

// Monographs in systematic botany from the Missouri Botanical Garden
// has many titles within, e.g. series and individual volumes,
// some of these are linked in the item table because there are multiple
// rows of (ItemID, TitleID) pairs for the same ItemID. But many series
// are not linked!

/*
SELECT DISTINCT i2.TitleID, title.FullTitle from item AS i1
INNER JOIN item AS i2 USING(ItemID)
INNER JOIN title ON title.TitleID = i2.TitleID
WHERE i1.TitleID=40366;
*/


$titles=array(
40366,
178269,
149317, // Icones Pleurothallidinarum
178264,
178266,
178263,
160228,
178271,
178270,
178268,
149829, // Index to plant chromosome numbers
178260,
178261,
154221,
178262,
154224,
178267,
169392,
169544,
178265,
);

/*
$titles=array(
// Flora of Panama 153180, two items no linked anywhere else but should be to 40366
153180,
// Manual de plantas de Costa Rica 891
891,
// Flora de Nicaragua 197976
197976,
// Index of mosses several titles, each with a single volume?

199667,
195262,
199669,
193490,
202405,
);
*/

// Fieldiana Botany
$titles=array(
//147681, // Flora of Peru
//2321,
//42246,
//42247,

//2624,
//2414,

42247,
958,
974,
2380,
2381,
2383,
2385,
2393,
2403,
2413,
2418,
2425,
2438,
2451,
2452,
2454,
955,
960,
972,
968,
2400,
2412,
2417,
2255,
2384,
2391,
2396,
2402,
2408,
2410,
2411,
2420,
2439,
2450,
2233,
2270,
2301,
2328,
2386,
2388,
2404,
2406,
2415,
2419,
2432,
2542,
2547,
2565,
2600,
147681,
2604,
2608,
2612,
2620,
2649,
2659,
2247,
2382,
2387,
2390,
2401,
2405,
2407,
2426,
2431,
2442,
2444,
2536,
2550,
2593,
2599,
2602,
2607,
2655,
2389,
2394,
2398,
2409,
2416,
2421,
2447,
2448,
2540,
2541,
2561,
2569,
2627,
2648,
3843,
2434,
2437,
2456,
2559,
2586,
2591,
2606,
2614,
2629,
2650,
2543,
2577,
2624,
2626,
2641,
2644,
2662,
2537,
2555,
2571,
2582,
2598,
2613,
2618,
2658,
2660,
5639,
4961,
5638,
4816,
2288,
2297,
2392,
2397,
3847,
2399,
2429,
2433,
2445,
3851,
2457,
2567,
2573,
2590,
2617,
2623,
2628,
2651,
2652,
2663,
2664,
2414,
2422,
2427,
2428,
2430,
2435,
2436,
2443,
2446,
2449,
2453,
2538,
2549,
2552,
2594,
2636,
2643
);

$titles=array(
//110138,
//218607,
);

//  Annales du Musée du Congo (mess, volumes, multiple titles, v limited coverage)
$titles=array(
/*
5766,
149676,
62379,
118672,
51828,
209514,
12601,
210672,
209943,
*/

//52319,

//150137,

//3595,

//109981,

13353,
);

$titles=array(66550);


$force = true;


foreach ($titles as $TitleID)
{
	echo $TitleID . "\n";
	upload_title($TitleID, $force);
	upload_items_for_title($TitleID, $force);
	upload_parts_for_title($TitleID, $force);
}


//$ItemID = 325622;

// get parts for item
// store page - part links
// add these to the item


exit();

//$PartID = 313402;
$PartID = 153428;

/*

-- parts in item (so we can display a list)
SELECT * FROM part 
WHERE ItemID=333950
ORDER BY CAST(SequenceOrder AS INTEGER);

-- pages in parts (so we can display a part)
SELECT part.PartID, partpage.PageID, partpage.SequenceOrder FROM part 
INNER JOIN partpage USING(PartID)
WHERE part.ItemID=333950
ORDER BY PartID, CAST(partpage.SequenceOrder AS INTEGER);
*/



// 

// get_part($PartID);

//$PartID = 385872;
//upload_part($PartID , true);

// 329874
// 328428

//$parts = upload_parts_for_item(328428, true);
//print_r($parts);

//upload_parts_for_item(186987, true);

//get_pages_for_item(328428);



/*

// get additional info for one item

$ItemID = 188389;

$sql = 'SELECT COUNT(DISTINCT PageID) AS count FROM page 
WHERE ItemID=' . $ItemID;

$data = db_get($sql);

print_r($data);

// get list of pages in item

// get list of parts in item

// get coverage of parts in item
*/





/*
      (
            [ItemID] => 336333
            [TitleID] => 7414
            [ThumbnailPageID] => 63807908
            [BarCode] => journalbombayna112bomba
            [VolumeInfo] => v.112:no.2 (2015:Aug.)
            [Year] => 2015
            [InstitutionName] => Smithsonian Libraries and Archives
            [CopyrightStatus] => In copyright. Digitized with the permission of the rights holder.
            [RightsStatement] => http://biodiversitylibrary.org/permissions
        )

  

print_r($obj);


                "@id": "https://www.biodiversitylibrary.org/item/114379",
                "_id": 114379,
                "thumbnailUrl": "https://www.biodiversitylibrary.org/pagethumb/37177107",
                "name": "v.1=no.1-12 (1832)",
                "provider": "Natural History Museum Library, London",
                "sponsor": "Natural History Museum Library, London",
                "copyrightNotice": "Public domain. The BHL considers that this work is no longer under copyright protection.",
                "isPartOf": [
                    "https://www.biodiversitylibrary.org/bibliography/51678"
                ],
                "numberOfPages": 628
 
*/

/*
// pages for an item
$sql = 'SELECT * FROM page 
WHERE ItemID=188389
ORDER BY CAST(SequenceOrder AS INTEGER)';

$data = db_get($sql);
*/

//print_r($data);

// names on a page


// layout


// creators



//


?>

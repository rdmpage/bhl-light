<?php

// Upload a layout document to CouchDB

ini_set('memory_limit', '-1');

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');

require_once (dirname(__FILE__) . '/ia.php');
require_once (dirname(__FILE__) . '/djvu-to-datalab.php');
require_once (dirname(__FILE__) . '/hocr-to-datalab.php');
require_once (dirname(__FILE__) . '/sqltojson.php');

/*
$identifiers = get_ia_for_title(68619);

$identifiers = get_ia_for_title(211788);

$identifiers = get_ia_for_title(10229);

$identifiers = get_ia_for_title(5943);

$identifiers = get_ia_for_title(206514);

$identifiers = get_ia_for_title(57881);

print_r($identifiers);

//$identifiers = array('Amphibianreptil5A');
*/

$TitleID = 144642; // European Journal of Taxonomy
$TitleID = 68619;
$TitleID = 212146; // The Zoogoer
$TitleID = 152899; // Anales del Museo de Historia Natural de Valparaiso
$TitleID = 82521; // Bonn
$TitleID = 5943; // Bulletin du Muséum national d'histoire naturelle
$TitleID = 211788;
$TitleID = 82521;
$TitleID = 10229;
$TitleID = 209695;
$TitleID = 10088;
$TitleID = 85187;

$titles = array(
/*10088,
//10229,
105698,
119522,
119879,
139317,
144642,*/
147681,
150137,
152899,
158870,
162187,
169356,
190323,
204608,
206514,
209695,
210747,
211788,
//212146,
2804,
38931,
44792,
48608,
49914,
57881,
//5943,
62642,
65344,
//68619,
730,
7414,
82521,
85187,
95451,
);


$titles = array(
//204608,
//144642, // EJT
//152899, // Valp
//82521, // Bonn

//211788,
//150137,
//158870, // Forktail
//5943, // Paris
//119522,
//162187,
//105698,
//730,
//150137
//7414,
//119879
//162187,
//150137,
//147681,
//169356,

//150137, // badness
//210747,
//95451,
//62642,
//5943
//9243,
7414
);

$titles = array(181031);

$titles = array(117696);

$titles = array(119522);

$titles =array(78705,68672);

$titles =array(79076);

$titles =array(130490); // Pacific Insects

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
//77306, // The Gardens' bulletin, Singapore
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
//169354,
3882
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

//$titles = array(159712);

$titles = array(10903);

$titles = array(122512);

$titles = array(
706,
//169557,
);

$titles = array(
//39684,
//39988,
//40896,

//101429,
//706

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
//2087
//314,
62169,
//45402,
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

$titles=array(165323);

$titles=array(12908);

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
52116,
);

foreach ($titles as $TitleID)
{
	
	$identifiers = get_ia_for_title($TitleID);
	
	print_r($identifiers);
	
	//$identifiers=array('trudyrusskagoent40191113russ');
	
	/*
	
	//exit();
	
	
	//$identifiers=array('journalofwashing8720wash');
	
	//$identifiers=array('mobot31753002350152');
	//$identifiers=array('mobot31753002347570','mobot31753002347547');
	//exit();
	
	// Curtis, bad
	//$identifiers=array('mobot31753002721899','mobot31753002721634');
	
	
	
	$identifiers=array(
		//'mobot31753002156542',
//		'mobot31753002156666',
		//'mobot31753002140777',
		//'mobot31753002156310',
//		'mobot31753002158142',
//		'mobot31753002140843',
//		'mobot31753002140785',
//		'mobot31753002140678',
		//'mobot31753002158134',
		
		//'mobot31753002158118',
		//'mobot31753002156674',
		//'mobot31753002156641',
		//'mobot31753002156625',
		//'mobot31753002156583',
		
		//'mobot31753002156641',
		
		//'mobot31753002156435',
		//'mobot31753002156476',
		//'mobot31753002156450',
		'mobot31753002140751',
		
		);
		
	// bad mobot31753002140710 v.40=no.469-480 (1906)
	// bad mobot31753002156641 v.26:pt.1=no.301-306 (1892)
	
	$identifiers = array(
		//'mobot31753002140710',
		'mobot31753002156641'
		
	);
	*/
	
	foreach ($identifiers as $ia)
	{
		echo "Fetching hOCR $ia\n";
		fetch_ia_hocr($ia);
		
		echo "Converting hOCR to layout $ia\n";
		$doc = hocr_to_datalab($ia);
		
		if (!$doc)
		{
			echo "Fetching DjVu $ia\n";
			fetch_ia_djvu($ia);
		
			echo "Converting DjVu to layout $ia\n";
			$doc = djvu_to_datalab($ia);	
		}
		
		if ($doc)
		{	
			$ItemID = get_item_from_barcode($ia);
				
			if ($ItemID != 0)
			{
				$doc->bhl_item = (Integer)$ItemID;
			
				$pages = get_pages_for_item($ItemID);
				
				for ($i = 0; $i < $doc->page_count; $i++)
				{
					$doc->pages[$i]->bhl_pageid = (Integer)str_replace('page/', '', $pages[$i]->{'@id'});
				
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
}

?>

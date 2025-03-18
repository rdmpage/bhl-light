<?php

// HTML item viewer

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/core.php');
require_once (dirname(__FILE__) . '/imgproxy.php');

//----------------------------------------------------------------------------------------
function layout_to_viewer_html($layout, $image_width = 700)
{
	$annotation_experiment = false;
	// $annotation_experiment = true;

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
		
		/* filter: grayscale(1) contrast(10) brightness(1);*/
		
	}	
	
	.page div {
		position:absolute;
		
		/* border:1px solid black; */
		
		/* debugging, show text boxes */
		/* background-color: rgba(0,255,0, 0.3); */
		
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
	
	
	
	if ($annotation_experiment)
	{
		$annot_json = '{"3":{"10":[{"text":"Arthropoda","start":12,"end":22}]},"4":{"18":[{"text":"Gryllides","start":63,"end":72}],"20":[{"text":"Mantes","start":41,"end":47}],"21":[{"text":"Tenodera","start":35,"end":43}],"25":[{"text":"Mantes","start":20,"end":26}]},"5":{"6":[{"text":"Acrydiinae","start":20,"end":30}],"11":[{"text":"Gryllides","start":32,"end":41}],"20":[{"text":"Cutilia","start":17,"end":24},{"text":"Cosmozosteria","start":32,"end":45}],"21":[{"text":"Diploptera dytiscoides","start":47,"end":69},{"text":"Panesthia","start":70,"end":79}],"23":[{"text":"Holocompsa capsoides","start":12,"end":32}],"28":[{"text":"Gryllides","start":4,"end":13}],"29":[{"text":"Trigonidiinae","start":15,"end":28}],"30":[{"text":"Nemobiinae","start":7,"end":17}],"33":[{"text":"Graeffea","start":9,"end":17},{"text":"Valanga","start":67,"end":74}]},"6":{"6":[{"text":"Nemobius grandis","start":60,"end":76}],"7":[{"text":"Valanga stercoraria","start":38,"end":57}],"32":[{"text":"Theganopteryx brunnea","start":3,"end":24},{"text":"Mareta fascifrons","start":36,"end":53}],"33":[{"text":"Blattella germanica","start":3,"end":22}],"34":[{"text":"Eoblatta","start":33,"end":41}]},"7":{"2":[{"text":"Liphoplus novarae","start":31,"end":48}],"3":[{"text":"Loboptera extranea","start":0,"end":18}],"5":[{"text":"Cutilia soror","start":3,"end":16},{"text":"Arachnocephalus gracilis","start":25,"end":49}],"6":[{"text":"Metioche vittaticollis","start":26,"end":48}],"7":[{"text":"Cosmozosteria bicolor","start":0,"end":21},{"text":"Metioche vittaticollis insularis","start":33,"end":65}],"8":[{"text":"Dorylaea","start":4,"end":12},{"text":"Metioche","start":35,"end":43}],"9":[{"text":"Stylopyga","start":0,"end":9},{"text":"Metioche","start":28,"end":36}],"10":[{"text":"Periplaneta americana","start":0,"end":21},{"text":"Metioche fascithorax","start":29,"end":49}],"11":[{"text":"Periplaneta australasiae","start":4,"end":28},{"text":"Anaxipha maritima","start":36,"end":53}],"12":[{"text":"Periplaneta brunnea","start":4,"end":23},{"text":"Anaxipha musica","start":34,"end":49}],"13":[{"text":"Pycnoscelus surinamensis","start":4,"end":28},{"text":"Anaxipha buxtoni","start":36,"end":52}],"14":[{"text":"Holocompsa capsoides","start":4,"end":24},{"text":"Anaxipha fulva","start":36,"end":50}],"15":[{"text":"Diploptera dytiscoides","start":4,"end":26},{"text":"Anaxipha curtipennis","start":37,"end":57}],"16":[{"text":"Panesthia","start":4,"end":13},{"text":"Anaxipha brevipes","start":35,"end":52}],"17":[{"text":"Tenodera","start":0,"end":8}],"18":[{"text":"Furnia insularis","start":4,"end":20},{"text":"Anaxipha armstrongi","start":31,"end":50}],"19":[{"text":"Euconocephalus roberti","start":4,"end":26},{"text":"Anaxipha hopkinsi","start":41,"end":58}],"21":[{"text":"Xiphidion","start":18,"end":27}],"22":[{"text":"Xiphidion","start":18,"end":27}],"23":[{"text":"Salomona suturalis","start":0,"end":18},{"text":"Aphonomorphus gracilis","start":29,"end":51}],"24":[{"text":"Phisis pallida","start":4,"end":18},{"text":"Aphonomorphus punctatus","start":29,"end":52}],"25":[{"text":"Aphonomorphus surdus","start":27,"end":47}],"27":[{"text":"Rhaphidophora","start":4,"end":17}],"28":[{"text":"Apteronemobius longipes","start":4,"end":27}],"29":[{"text":"Scottia variegata","start":4,"end":21},{"text":"Nisyrus spinulosus","start":30,"end":48}],"30":[{"text":"Cophonemobius buxtoni","start":4,"end":25},{"text":"Paratettix histricus","start":38,"end":58}],"31":[{"text":"Pteronemobius dentatus","start":4,"end":26}],"32":[{"text":"Pteronemobius annulicornis","start":4,"end":30}],"33":[{"text":"Pteronemobius parallelus","start":4,"end":28},{"text":"Apterotettix samoana","start":39,"end":59}],"34":[{"text":"Nemobius","start":4,"end":12}],"35":[{"text":"Gryllus oceanicus","start":4,"end":21},{"text":"Austracris","start":36,"end":46}],"36":[{"text":"Gryllodes insularis","start":0,"end":19},{"text":"Valanga stercoraria","start":31,"end":50}],"37":[{"text":"Loxoblemmus","start":0,"end":11}],"40":[{"text":"Theganopteryx brunnea","start":3,"end":24}]},"8":{"25":[{"text":"Blattella germanica","start":3,"end":22}],"26":[{"text":"Blatta germanica","start":0,"end":16}],"27":[{"text":"Phyllodromia germanica","start":0,"end":22}],"30":[{"text":"Supellina unicolor","start":3,"end":21}]},"9":{"27":[{"text":"Mareta fascifrons","start":3,"end":20}]},"10":{"2":[{"text":"Theganopteryx brunnea","start":8,"end":29}],"4":[{"text":"Mareta fascifrons","start":51,"end":68}],"7":[{"text":"Euryblattella lata","start":68,"end":86}]},"11":{"23":[{"text":"Phyllodromia obtusata","start":0,"end":21}]},"12":{"1":[{"text":"Eoblatta","start":3,"end":11}],"2":[{"text":"Blatta notulata","start":0,"end":15}],"3":[{"text":"Phyllodromia","start":0,"end":12}],"29":[{"text":"Eoblatta","start":44,"end":52}]},"13":{"4":[{"text":"Euryblattella","start":0,"end":13}],"10":[{"text":"Euryblattella lata","start":3,"end":21}],"32":[{"text":"Euryblattella lata","start":9,"end":27}]},"14":{"20":[{"text":"Cutilia","start":3,"end":10}],"21":[{"text":"Polyzosteria soror","start":0,"end":18}],"22":[{"text":"Platyzosteria soror","start":0,"end":19}],"27":[{"text":"Cutilia nitida","start":3,"end":17}],"28":[{"text":"Platyzosteria nitida","start":0,"end":20}],"29":[{"text":"Polyzosteria","start":0,"end":12},{"text":"Melanozosteria","start":14,"end":28}]},"15":{"3":[{"text":"Tanna","start":21,"end":26}],"8":[{"text":"Dorylaea flavicincta","start":4,"end":24}],"10":[{"text":"Methana","start":0,"end":7}],"13":[{"text":"Periplaneta australasiae","start":4,"end":28}],"14":[{"text":"Blatta australasiae","start":0,"end":19}],"15":[{"text":"Periplaneta australasiae","start":0,"end":24}],"18":[{"text":"Periplaneta brunnea","start":4,"end":23}],"19":[{"text":"Periplaneta brunnea","start":0,"end":19}],"24":[{"text":"Pycnoscelus","start":4,"end":15}]},"16":{"13":[{"text":"Holocompsa capsoides","start":4,"end":24}],"14":[{"text":"Holocompsa capsoides","start":0,"end":20}],"23":[{"text":"Diploptera dytiscoides","start":4,"end":26}],"24":[{"text":"Blatta dytiscoides","start":0,"end":18}],"25":[{"text":"Diploptera dytiscoides","start":0,"end":22}],"29":[{"text":"Manua","start":0,"end":5}]},"17":{"2":[{"text":"Panesthia serratissima","start":4,"end":26}],"3":[{"text":"Panesthia serratissima","start":0,"end":22}],"6":[{"text":"Diploptera dytiscoides","start":9,"end":31}]},"18":{"5":[{"text":"Furnia insularis","start":4,"end":20}],"7":[{"text":"Anaulacomera insularis","start":0,"end":22}],"13":[{"text":"Anaulacomera","start":52,"end":64}],"16":[{"text":"Psyrae","start":34,"end":40},{"text":"Anaulacomera","start":57,"end":69}],"21":[{"text":"Euconocephalus roberti","start":4,"end":26}],"23":[{"text":"Conocephalus australis","start":0,"end":22}]},"19":{"2":[{"text":"Xiphidion","start":18,"end":27}],"3":[{"text":"Xiphidium modestum","start":0,"end":18}],"5":[{"text":"Xiphidion modestum","start":0,"end":18}],"15":[{"text":"Conocephalus modestus","start":32,"end":53}],"16":[{"text":"Phisis pectinata","start":28,"end":44},{"text":"Phisis","start":70,"end":76}]},"20":{"5":[{"text":"Xiphidion","start":18,"end":27}],"6":[{"text":"Xiphidium affine","start":0,"end":16}],"12":[{"text":"Manua","start":0,"end":5}],"17":[{"text":"Phisis pallida","start":4,"end":18}],"18":[{"text":"Nocera pallida","start":0,"end":14}],"20":[{"text":"Teuthras pallidus","start":0,"end":17}],"28":[{"text":"Manua","start":0,"end":5}]},"21":{"13":[{"text":"Phisis","start":9,"end":15}],"24":[{"text":"Phisis","start":18,"end":24}]},"22":{"5":[{"text":"Treubia","start":57,"end":64}],"7":[{"text":"Rhaphidophora rechingeri","start":9,"end":33}]},"23":{"7":[{"text":"Rhaphidophora","start":9,"end":22}],"8":[{"text":"Apteronemobius longipes","start":56,"end":79}],"10":[{"text":"Pteronemobius annulicornis","start":9,"end":35}],"15":[{"text":"Emballonura semicaudata","start":48,"end":71}]},"24":{"3":[{"text":"Apteronemobius","start":0,"end":14}],"4":[{"text":"Pseudonemobius","start":49,"end":63}],"7":[{"text":"Pseudonemobius","start":27,"end":41}],"9":[{"text":"Apteronemobius longipes","start":4,"end":27}],"33":[{"text":"Apteronemobius","start":11,"end":25}]},"25":{"18":[{"text":"Pseudonemobius","start":0,"end":14}],"23":[{"text":"Scottia variegata","start":4,"end":21}]},"26":{"24":[{"text":"Cophonemobius","start":0,"end":13}],"25":[{"text":"Nemobius","start":16,"end":24}],"29":[{"text":"Cophonemobius buxtoni","start":4,"end":25}],"31":[{"text":"Nemobius","start":12,"end":20}]},"27":{"9":[{"text":"Cophonemobius","start":8,"end":21}],"36":[{"text":"Nemobius","start":57,"end":65}],"40":[{"text":"Cophonemobius","start":19,"end":32},{"text":"Pronemobius","start":36,"end":47},{"text":"Nemobius","start":49,"end":57}]},"28":{"1":[{"text":"Pteronemobius dentatus","start":4,"end":26}],"8":[{"text":"Pteronemobius annulicornis","start":4,"end":30}]},"29":{"7":[{"text":"Nemobius","start":34,"end":42}],"9":[{"text":"Scottia","start":4,"end":11}],"10":[{"text":"Pteronemobius parallelus","start":4,"end":28}],"11":[{"text":"Pteronemobius parallelus","start":0,"end":24}],"21":[{"text":"Nemobius","start":4,"end":12}],"22":[{"text":"Nemobius grandis","start":0,"end":16}]},"30":{"5":[{"text":"Gryllus oceanicus","start":4,"end":21}],"6":[{"text":"Gryllus oceanicus","start":0,"end":17}],"13":[{"text":"Gryllus commodus","start":42,"end":58}],"16":[{"text":"Myrmecophila quadrispina","start":4,"end":28}],"17":[{"text":"Fagasa","start":9,"end":15}],"23":[{"text":"Liphoplus","start":0,"end":9}],"24":[{"text":"Cycloptilum","start":28,"end":39}],"26":[{"text":"Cycloptilum","start":15,"end":26},{"text":"Ornebius","start":30,"end":38}]},"31":{"1":[{"text":"Liphoplus novarae","start":4,"end":21}],"2":[{"text":"Liphoplus novarae","start":0,"end":17}],"4":[{"text":"Vailima","start":25,"end":32}],"10":[{"text":"Manua","start":0,"end":5}],"21":[{"text":"Liphoplus nigripennis","start":4,"end":25}]},"32":{"1":[{"text":"Liphoplus novarae","start":9,"end":26}],"3":[{"text":"Anaxipha curtipennis","start":9,"end":29},{"text":"Anaxipha brevipes","start":54,"end":71}],"4":[{"text":"Anaxipha armstrongi","start":55,"end":74}],"5":[{"text":"Anaxipha hopkinsi","start":17,"end":34}]},"33":{"14":[{"text":"Arachnocephalus maritimus","start":4,"end":29}],"15":[{"text":"Arachnocephalus maritimus","start":0,"end":25}],"23":[{"text":"Arachnocephalus gracilis","start":4,"end":28}]},"34":{"18":[{"text":"Trigonidium vittaticolle","start":0,"end":24}],"20":[{"text":"Metioche vittaticollis insularis","start":4,"end":36}],"21":[{"text":"Homoeoxiphus insularis","start":0,"end":22}],"23":[{"text":"Tanna","start":21,"end":26}],"25":[{"text":"Trigonidium flavipes","start":0,"end":20}],"28":[{"text":"Vailima","start":7,"end":14}]},"35":{"10":[{"text":"Metioche kuthyi","start":4,"end":19}],"11":[{"text":"Metioche kuthyi","start":0,"end":15}],"12":[{"text":"Vailima","start":32,"end":39}],"17":[{"text":"Metioche fascithorax","start":4,"end":24}]},"36":{"9":[{"text":"Anaxipha maritima","start":4,"end":21}],"10":[{"text":"Cyrtoxiphus maritimus","start":0,"end":21}],"17":[{"text":"Anaxipha musica","start":4,"end":19}],"21":[{"text":"Fagasa","start":45,"end":51}],"30":[{"text":"Anaxipha buxtoni","start":4,"end":20}]},"37":{"33":[{"text":"Gryllides","start":68,"end":77}],"34":[{"text":"Anaxipha","start":12,"end":20}]},"38":{"6":[{"text":"Vailima","start":56,"end":63}],"9":[{"text":"Anaxipha fulva","start":4,"end":18}],"10":[{"text":"Cyrtoxiphus fulvus","start":0,"end":18}],"25":[{"text":"Anaxipha curtipennis","start":4,"end":24}]},"39":{"15":[{"text":"Anaxipha brevipes","start":4,"end":21}]},"40":{"7":[{"text":"Anaxipha brevipes","start":51,"end":68}],"8":[{"text":"Anaxipha bryani","start":4,"end":19}]},"41":{"7":[{"text":"Anaxipha armstrongi","start":4,"end":23}]},"42":{"5":[{"text":"Anaxipha armstrongi","start":9,"end":28}],"10":[{"text":"Anaxipha hopkinsi","start":4,"end":21}]},"43":{"7":[{"text":"Anaxipha","start":9,"end":17}]},"44":{"10":[{"text":"Cardiodactylus","start":4,"end":18}],"12":[{"text":"Cardiodactylus","start":0,"end":14}],"20":[{"text":"Manua","start":0,"end":5}],"22":[{"text":"Swezwilderia","start":5,"end":17}],"23":[{"text":"Cardiodactylus","start":16,"end":30}],"28":[{"text":"Perlidae","start":75,"end":83}],"32":[{"text":"Hydropedeticus vitiensis","start":13,"end":37}]},"45":{"1":[{"text":"Swezwilderia bryani","start":4,"end":23}]},"46":{"22":[{"text":"Aphonomorphus gracilis","start":4,"end":26}],"23":[{"text":"Aphonomorphus gracilis","start":0,"end":22}],"30":[{"text":"Aphonomorphus punctatus","start":4,"end":27}],"31":[{"text":"Gryllus","start":0,"end":7},{"text":"Eneoptera","start":9,"end":18}]},"47":{"1":[{"text":"Aphonomorphus surdus","start":4,"end":24}],"7":[{"text":"Aphonomorphus surdus","start":9,"end":29}],"8":[{"text":"Paratettix compactus","start":48,"end":68}]},"48":{"25":[{"text":"Metrypa","start":18,"end":25}],"31":[{"text":"Graeffea coccophaga","start":0,"end":19}]},"49":{"5":[{"text":"Graeffea","start":44,"end":52}],"11":[{"text":"Paratettix histricus","start":4,"end":24}],"12":[{"text":"Tetrix","start":0,"end":6},{"text":"Eugenia","start":28,"end":35}],"21":[{"text":"Paratettix compactus","start":4,"end":24}]},"50":{"16":[{"text":"Apterotettix samoana","start":4,"end":24}]},"51":{"4":[{"text":"Tetriginae","start":35,"end":45}],"12":[{"text":"Aiolopus tamulus","start":4,"end":20}],"13":[{"text":"Gryllus tamulus","start":0,"end":15}],"22":[{"text":"Austracris guttulosa nana","start":4,"end":29}],"23":[{"text":"Austracris guttulosa nana","start":0,"end":25}]},"52":{"1":[{"text":"Valanga stercoraria","start":4,"end":23}],"2":[{"text":"Acridium stercorarium","start":0,"end":21}],"3":[{"text":"Valanga stercoraria","start":0,"end":19}],"13":[{"text":"Valanga stercoraria","start":9,"end":28}],"26":[{"text":"Dermaptera","start":21,"end":31},{"text":"Orthoptera","start":36,"end":46}]},"53":{"1":[{"text":"Orthoptera","start":56,"end":66}],"2":[{"text":"Orthopteres","start":43,"end":54}],"5":[{"text":"Theganopteryx brunnea","start":8,"end":29}],"7":[{"text":"Mareta fascifrons","start":38,"end":55}],"12":[{"text":"Euryblattella lata","start":9,"end":27}],"13":[{"text":"Diploptera dytiscoides","start":9,"end":31}],"14":[{"text":"Euconocephalus","start":70,"end":84}],"15":[{"text":"Conocephalus affinis","start":68,"end":88}],"16":[{"text":"Phisis pallida","start":57,"end":71}],"18":[{"text":"Phisis pallida","start":9,"end":23}],"19":[{"text":"Rhaphidophora rechingeri","start":9,"end":33}],"21":[{"text":"Apteronemobius longipes","start":58,"end":81}],"24":[{"text":"Apteronemobius longipes","start":9,"end":32}],"25":[{"text":"Cophonemobius buxtoni","start":9,"end":30}],"26":[{"text":"Liphoplus novarae","start":9,"end":26}],"27":[{"text":"Anaxipha buxtoni","start":42,"end":58},{"text":"Anaxipha","start":88,"end":96}],"28":[{"text":"Anaxipha brevipes","start":35,"end":52},{"text":"Anaxipha","start":83,"end":91}],"29":[{"text":"Anaxipha armstrongi","start":28,"end":47},{"text":"Anaxipha","start":78,"end":86}],"31":[{"text":"Anaxipha brevipes","start":9,"end":26}],"32":[{"text":"Anaxipha brevipes","start":9,"end":26}],"33":[{"text":"Anaxipha armstrongi","start":9,"end":28}],"34":[{"text":"Anaxipha armstrongi","start":9,"end":28}],"35":[{"text":"Anaxipha","start":9,"end":17}],"37":[{"text":"Aphonomorphus surdus","start":9,"end":29}],"38":[{"text":"Paratettix compactus","start":49,"end":69}],"40":[{"text":"Valanga stercoraria","start":9,"end":28}]},"56":{"4":[{"text":"Orthoptera","start":12,"end":22},{"text":"Dermaptera","start":27,"end":37}],"5":[{"text":"Hemiptera","start":8,"end":17}],"6":[{"text":"Lepidoptera","start":4,"end":15}],"7":[{"text":"Coleoptera","start":13,"end":23}],"8":[{"text":"Hymenoptera","start":3,"end":14}],"9":[{"text":"Diptera","start":6,"end":13}],"11":[{"text":"Arthropoda","start":20,"end":30}]},"57":{"5":[{"text":"Arthropoda","start":46,"end":56}],"8":[{"text":"Dermaptera","start":9,"end":19}],"9":[{"text":"Orthoptera","start":9,"end":19}],"10":[{"text":"Hemiptera","start":9,"end":18}],"11":[{"text":"Fulgoroidea","start":9,"end":20}],"15":[{"text":"Heteroptera","start":62,"end":73}],"23":[{"text":"Geometridae","start":9,"end":20}],"30":[{"text":"Heteromera","start":10,"end":20},{"text":"Bostrychoidea","start":22,"end":35}],"37":[{"text":"Apoidea","start":5,"end":12},{"text":"Sphecoidea","start":14,"end":24},{"text":"Vespoidea","start":30,"end":39}],"47":[{"text":"Isoptera","start":9,"end":17}],"50":[{"text":"Plectoptera","start":9,"end":20}],"51":[{"text":"Siphonaptera","start":12,"end":24}],"59":[{"text":"Isopoda","start":9,"end":16},{"text":"Terrestria","start":17,"end":27}]}}';
		$annotations = json_decode($annot_json);
	}
	
	//print_r($annotations);
		
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
		
		// Fetch from Hetzner S3
		if (1)
		{
			// .djvu is DjVu
			// .jp2 is hOCR
			$image_url = 'https://hel1.your-objectstorage.com/bhl/' . $layout->internetarchive . '_jp2/' . preg_replace('/\.(djvu|jp2)/', '.webp', $layout->pages[$i]->internetarchive);
		}
		
		$image_url = 'https://images.bionames.org' . sign_imgproxy_path($image_url, $image_width);
		

		if (1)
		{
			$html .= '<img class="lazy"'
				. ' data-src="' . $image_url  . '"'
				. ' draggable="false"'
				. '>' . "\n";
		}
		
		// text lines		
		foreach ($layout->pages[$i]->text_lines as $line_index => $line)
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
			
			// if we are going to add annotations this is where we do it...
			
			// $text = str_replace('Apteronemobius', '<mark style="color:transparent;opacity:0.5;">Apteronemobius</mark>', $text);
			
			if ($annotation_experiment)
			{
				if (isset($annotations->{$i}->{$line_index}))
				{
					// just do replace
					foreach ($annotations->{$i}->{$line_index} as $a)
					{
						$text = str_replace($a->text, '<mark title="' . $a->text . '" style="color:transparent;opacity:0.5;">' . $a->text . '</mark>', $text);
					}
				}
			}
			
				
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

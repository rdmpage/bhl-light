<!-- leaflet -->
<link rel="stylesheet" href="js/leaflet-0.7.3/leaflet.css" />
<script src="js/leaflet-0.7.3/leaflet.js" type="text/javascript"></script>

<link rel="stylesheet" href="js/leaflet.draw/leaflet.draw.css" /> 
<script src="js/leaflet.draw/leaflet.draw.js" type="text/javascript"></script>

<!-- H3 -->
<script src="js/h3.js" type="text/javascript"></script>

<style>
/* make sure tiles don't have a border */
.leaflet-tile { border: none; }
</style>

<script>
var map;
var popup = L.popup();

// based on H3-viewer, but extended
const ZOOM_TO_H3_RES_CORRESPONDENCE = {
	1: 1,
	2: 1,
	3: 1,
	4: 1,
    5: 1,
    6: 2,
    7: 3,
    8: 3,
    9: 4,
    10: 5,
    11: 6,
    12: 6,
    13: 7,
    14: 8,
    15: 9,
    16: 9,
    17: 10,
    18: 10,
    19: 11,
    20: 11,
    21: 12,
    22: 13,
    23: 14,
    24: 15,
};

var clickTimeout;
	
//--------------------------------------------------------------------------------
function create_map(id) {
	map = new L.Map(id, { minZoom: 1, maxZoom: 11, zoomControl: false });
	
	L.control.zoom({position:'bottomleft'}).addTo(map);

	// create the tile layer with correct attribution
	var layerUrl = '';
	var layerAttrib = '';
	
	// https://stackoverflow.com/a/57795495
	if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
		layerUrl='https://{s}.tile.thunderforest.com/transport-dark/{z}/{x}/{y}.png?apikey=<?php echo getenv('THUNDERFOREST_API_KEY') ?>';
		layerAttrib = 'Map © <a href="https://www.thunderforest.com/">Thunderforest</a>, data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';	
	} else {
	 // default OpenStreetMap
	 layerUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	 layerAttrib = 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';	
	}
	
	layer = new L.TileLayer(layerUrl, {minZoom: 1, maxZoom: 16, attribution: layerAttrib});	

	map.setView(new L.LatLng(0, 0), 4);
	map.addLayer(layer);
	
	dataLayer = new L.TileLayer('api_tile.php?x={x}&y={y}&z={z}', 
		{minZoom: 0, maxZoom: 11, attribution: 'BHL'});

	map.addLayer(dataLayer);	
	
	// https://github.com/Leaflet/Leaflet/issues/1885#issuecomment-91395167 for wrap()
	
	map.on('click', function(e) {
		var zoom = map.getZoom();
		var h3Zoom = ZOOM_TO_H3_RES_CORRESPONDENCE[zoom];
		var h3Index = h3.latLngToCell(e.latlng.lat, e.latlng.lng, h3Zoom);
	
		/*
		 popup
   .setLatLng(e.latlng.wrap())
   .setContent("You clicked the map at " + e.latlng.lng + ' ' + e.latlng.lat + ' H3 ' + h3Index + ' [' + zoom + '=' + h3Zoom + ']')
   .openOn(map);
   		*/
   		document.getElementById('h3').innerHTML = h3Index;
   		//}, 300);
	});
		
}

</script>

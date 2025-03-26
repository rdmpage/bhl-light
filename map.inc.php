<!-- leaflet -->
<link rel="stylesheet" href="js/leaflet-0.7.3/leaflet.css" />
<script src="js/leaflet-0.7.3/leaflet.js" type="text/javascript"></script>

<link rel="stylesheet" href="js/leaflet.draw/leaflet.draw.css" /> 
<script src="js/leaflet.draw/leaflet.draw.js" type="text/javascript"></script>

<style>
/* make sure tiles don't have a border */
.leaflet-tile { border: none; }
</style>

<script>
var map;
	
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
}

</script>

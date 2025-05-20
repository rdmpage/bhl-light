//----------------------------------------------------------------------------------------
// Test whether user clicked outside the panel, and if so close the panel
function click_outsider (event) {
	var element = document.getElementById('panel');
	var withinBoundaries = event.composedPath().includes(element);
	
	if (!withinBoundaries) {
		document.removeEventListener('click', click_outsider);
		
		// if we are displaying the viewer then we need to remove the event 
		// handler from the iframe

		var iframe = document.getElementById('viewer');
		if (iframe) {
			iframe.contentDocument.body.removeEventListener('click', click_outsider);
		}
		
		close_panel();
	}	
}

//----------------------------------------------------------------------------------------
// Make panel visible by sliding it out, add event listener for user clicking outside
// the panel
function show_panel(data) {
	var element = document.getElementById('panel');
	element.style.transform = "scaleX(1)";
	if (event) {
	  event.stopPropagation();
	}
	document.addEventListener('click', click_outsider);
	
	// if we are displaying the viewer then we need to add the event handler to the iframe
	var iframe = document.getElementById('viewer');
	if (iframe) {
		iframe.contentDocument.body.addEventListener('click', click_outsider);
	}
	
	var anno = JSON.parse(decodeURIComponent(data));
	
	var html = '<ul>';
	for (var i in anno) {
		html += '<li>' + anno[i].text + '</li>';
		
		var link = anno[i].body.id;
		var domain = link.replace(/^https?:\/\//, '');
		domain = domain.replace(/\/.*$/, '');
		
		html += '<a href="' + link + '" target="_new">' + domain + '</a>';
	}
	
	html += '</ul>';
	
	document.getElementById("info").innerHTML = html;
	
	
}

/*
//----------------------------------------------------------------------------------------
// Show a sumamry of an accession number by calling external API wrapper and formatting
// the JSON 
function show_panel_accession(accession) {
	show_panel(event);
	
	var url = "api_external.php?accession=" + accession;
	
	fetch(url).then(
		function(response){
			if (response.status != 200) {
				console.log("Looks like there was a problem. Status Code: " + response.status);
				document.getElementById("info").innerHTML = "404";
				return;
			}
			
			response.json().then(function(data) {					
				//var html = JSON.stringify(data);
				
				var html = '<h2>' + data.id + '</h2>';
				html += '<ul>';
				for (var i in data.references) {
					html += '<li>';
					
					if (data.references[i].title) {
						html += data.references[i].title;
					}

					if (data.references[i].DOI) {
						html += '<br>doi:<a href="https://identifiers.org/doi:' + data.references[i].DOI + '" target="_new">' + data.references[i].DOI + '</a>';
					}

					if (data.references[i].PMID) {
						html += '<br>pmid:<a href="https://identifiers.org/pubmed:' + data.references[i].PMID + '" target="_new">' + data.references[i].PMID + '</a>';
					}
					
					html += '</li>';
				}
				html += '</ul>';
				
				document.getElementById("info").innerHTML = html;
			});
			
	});
	
}

//----------------------------------------------------------------------------------------
// Show a panel that displays a preformatted HTML snippet which we fetch from the API
function show_panel_snippet(api_url) {
	show_panel();
	
	// display
	//document.getElementById('info').innerHTML = decodeURIComponent(api_url);
	
	fetch(api_url).then(
		function(response){
			if (response.status != 200) {
				console.log("Looks like there was a problem. Status Code: " + response.status);
				document.getElementById("info").innerHTML = "404";
				return;
			}
			
			response.text().then(function(data) {					
				document.getElementById("info").innerHTML = data;
			});
			
	});
	
}
*/

//----------------------------------------------------------------------------------------
// Close the panel by sliding it back
function close_panel() {
	document.getElementById("info").innerHTML = "";
	var element = document.getElementById('panel');
	element.style.transform = "scaleX(0.00001)";
}

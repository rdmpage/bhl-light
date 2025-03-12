// handle messages sent by viewer
window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
  console.log("receiveMessage " + JSON.stringify(event.data));
  //console.log("receiveMessage " + JSON.stringify(event));

  if (event.data) {

    // Use zero-based index of this page to set dropdown selection
    if (typeof event.data.page !== 'undefined') {
      document.getElementById('pagenumber').selectedIndex = event.data.page;
    }
    else {

    }

    /*
    // Nicely formatted page name
    if (typeof event.data.page !== 'undefined') {
      document.getElementById('pagenumber').innerHTML = event.data.page;
    }
    else {
      document.getElementById('pagenumber').innerHTML = "";
    }
    */
    /*
    if (event.data.annotations) {
    	//document.getElementById('annotations').innerHTML = JSON.stringify(event.data.annotations);
    	if (event.data.annotations[0].items) {
    		var html = '<ul>';
    		for (var i in event.data.annotations[0].items) {
    			if (typeof event.data.annotations[0].items[i].body === 'string') {  				
    				html += '<li>' + event.data.annotations[0].items[i].body + '</li>';
    			}
    		}
    		html += '</ul>';
    		document.getElementById('annotations').innerHTML = html;
    	}
    } else {
    	document.getElementById('annotations').innerHTML = "";
    }
    */
  }
  else {
    // document.getElementById('pagenumber').innerHTML = "";
    //document.getElementById('annotations').innerHTML = "";
  }
}

// Event handler for user selecting a new page to view
function gotopage(event) {

  // Use the https://www.rfc-editor.org/rfc/rfc3778 "#page=" named anchors
  // in the viewer iframe as targets
  var page_anchor = 'page=' + (parseInt(event.target.value) + 1);

  // Get element with anchor name, because anchors are in the iframe (not the main 
  // document we need a trick: https://stackoverflow.com/a/20750218
  var anchors = window.frames['viewer'].contentDocument.getElementsByName(page_anchor);
  if (anchors) {
    // now just scroll to the anchor
    // https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollIntoView
    // Note that we need block: "nearest" for Chrome to work, otherwise it seems to scroll
    // past the target page(!)
    anchors[0].scrollIntoView({ block: "start", behavior: "smooth"});
   }
}

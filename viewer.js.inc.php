<script>
    // handle messages sent by viewer
    window.addEventListener("message", receiveMessage, false);

  function receiveMessage(event) {
    console.log("receiveMessage " + JSON.stringify(event.data));
    //console.log("receiveMessage " + JSON.stringify(event));
    
    if (event.data) {
      if (typeof event.data.page !== 'undefined') {
        document.getElementById('pagenumber').innerHTML = event.data.page;
      }
      else {
        document.getElementById('pagenumber').innerHTML = "";
      }
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
      document.getElementById('pagenumber').innerHTML = "";
      //document.getElementById('annotations').innerHTML = "";
    }
  }
</script>

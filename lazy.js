
// Options for lazy loading of images
var options_lazyload = {
  root: null,
  // rootMargin: top, right, bottom, left margins
  // added to the bounding box of the root element (viewport if not defined)
  // see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver                                  
  rootMargin: '1000px 0px 1000px 0px',

  // threshold: how much of the target visible for the callback to be invoked
  // includes padding, 1.0 means 100%                   
  threshold: 0
};

// Options to send information messages, for example the number of the page currently
// being displayed
var options_info = {
  root: null,
  // only consider the viewport of the element displaying the pages
  rootMargin: '0px 0px 0px 0px',

  // we want a big chunk of the page to be visible 
  // so we don't trigger events if just a bit appears                                     
  threshold: 0.5
};

// If user rapidly scrolls through pages we will end up sending lots of requests for images
// for pages that won't be seen (they've been scrolled through). 
// https://stackoverflow.com/a/69292238/9684 suggests using a timeout. Each time a page 
// intersects the root element we set a timeout with a callback to run (e.g.) 500 milliseconds
// later. Before setting a timeout we clear the previous timeout, which means its callback
// won't run. Once we stop scrolling the last timeout that we set fires and we load the page
// image. A problem (hinted at in the comment by https://stackoverflow.com/users/2487517/tibrogargan)
// is that this means we only load the page for the last intersecting element, which
// may be some pages ahead of the page currently displayed. For example, the very first page for
// may be blank. To avoid this we keep a small circular buffer of the most recent intersecting
// pages, and when the timeout fires it loads all pages in the buffer.

var lastTimeout = null;
var lastMessageTimeout = null;

// Create a Circular buffer to store the most intersecting elements
var buffer_size = 5;
var buffer = new Array(buffer_size);
var buffer_index = 0;

// See https://stackoverflow.com/a/38362257/9684
function buffer_push(x) {
  buffer_index = (buffer_index + 1) % buffer_size;
  buffer[buffer_index] = x;
}

// https://tommcfarlin.com/check-if-a-page-is-in-an-iframe/
var listener = null;
if ( window.location !== window.parent.location ) {
	// The page is in an iframe
	listener = window.parent;
} else {
	// The page is not in an iframe
	listener = window;
}


function loadImage(URL, retries = 5) {
    var img = new Image();
    img.onerror = () => {
      if (retries > 0){
        loadImage(URL, retries -1);
      } else {
        alert('image not found');
      }
    }
    img.src = URL;
}

function retry(img) {
	//alert('bugger');
	console.log ("image not loaded: " + img.src);
	
	// removing .src means we will try again next time image is in view
	img.removeAttribute("src");
	
	// set backgrund colour for page to indicate things failed but we are working on it
	img.parentElement.style.background = "#EEEEEE";
}


if (window.IntersectionObserver) {


  // lazy loading of images
  this.io = new IntersectionObserver(
    (function callback(entries) {
      for (const entry of entries) {
        if (entry.isIntersecting) {

          // add to our circular buffer
          buffer_push(entry.target);

          // https://stackoverflow.com/a/69292238/9684

          // remove previous timeout
          if (lastTimeout) clearTimeout(lastTimeout);

          lastTimeout = setTimeout(function() {

            // load images for all pages in the circular buffer
            for (var i in buffer) {
              var lazyImage = buffer[i];
              if (!lazyImage.src && lazyImage.hasAttribute('data-src')) {
                lazyImage.src = lazyImage.dataset.src;
                
                //var parent = lazyImage.parentNode;
                //parent.style.height ='auto';
              }
            }
          }, 100);

        }
      }
    }).bind(this) // bind(this) gives us access to "this"
    ,
    options_lazyload
  );

  // page information
  this.io_info = new IntersectionObserver(
    function callback(entries) {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          let item = entry.target;
          
          // send listener (either this window, or its parent) a message 
          // when page being displayed changes
          
          // remove previous timeout
          if (lastMessageTimeout) clearTimeout(lastMessageTimeout);

          lastMessageTimeout = setTimeout(function() {
          
          	 var msg = {};
          	 
          	 if (item.hasAttribute('data-pageindex')) {
          	 	msg['page'] = decodeURIComponent(item.dataset.pageindex);
          	 }          	 

          	 if (item.hasAttribute('data-bhl')) {
          	 	msg['bhl'] = decodeURIComponent(item.dataset.bhl);
          	 }          	 
          	 
           	 if (item.hasAttribute('data-annotations')) {
          	 	msg['annotations'] = JSON.parse(decodeURIComponent(item.dataset.annotations));
          	 }
         	 
          	 console.log("lazy.js msg = " + JSON.stringify(msg));
          	 
          	 if (Object.keys(msg).length > 0) {
  				listener.postMessage(msg, "*");
			  } else {
				listener.postMessage(null, "*");
			  }

          }, 100);
        }
      }
    },
    options_info
  );
}

// get all the lazy load images
const images = document.querySelectorAll('img.lazy');

// add lazy load images to the observer
for (const image of images) {
  if (window.IntersectionObserver) {
    this.io.observe(image);
  }
  else {
    console.log('Intersection Observer not supported');
    image.src = image.getAttribute('data-src');
  }
}

// get all the pages
const images_info = document.querySelectorAll('.page');

// load all the pages
for (const image of images_info) {
  if (window.IntersectionObserver) {
    this.io_info.observe(image);
  }
}

/*

// Put this in the window hosting the viewer, either as an iframe or embedded

// handle messages sent by viewer
window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
  console.log("receiveMessage" + JSON.stringify(event.data));
  console.log("receiveMessage" + JSON.stringify(event));
  
  if (event.data) {
	  document.getElementById('message').innerHTML = JSON.stringify(JSON.parse(decodeURIComponent(event.data)));
  } else {
    document.getElementById('message').innerHTML = "";
  }
}

*/

<script>
  // https://www.nray.dev/blog/using-media-queries-in-javascript/#:~:text=Using%20matchMedia()%20in%20JavaScript,-The%20matchMedia%20API&text=%2F%2F%20Set%20a%20media%20query,involving%20the%20media%20query%20above.

  // Set a media query in JavaScript.
  const mediaQuery = matchMedia("(max-width: 800px)");

// Listen to viewport width changes involving the media query above.
const handler = (e) => {
  // Using `e.matches` is not prone to expensive style recalcs/layout
  // that checking properties like `window.innerWidth` might cause.
  if (e.matches) {
    document.getElementById('aside-details').open = false;
  }
  else {
    document.getElementById('aside-details').open = true;
  }
};

// If the browser supports the `addEventListener` API, the following
// line will be truthy, so we use it. If not, it will be falsy, and we
// use the deprecated `addListener` API.
mediaQuery.addEventListener ?
  mediaQuery.addEventListener("change", handler) :
  mediaQuery.addListener(handler);

// Check during page load.
handler(mediaQuery);

</script>
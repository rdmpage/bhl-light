/* some ideas borrowed from https://www.w3schools.com/howto/howto_css_sidebar_responsive.asp */

aside {
    width:var(--aside-width);
	position: fixed;
	background-color: var(--aside-bg);
	z-index:100;
}

/* by default (desktop) we hide the <summary> element */
/* in part based on https://stackoverflow.com/a/18772539 */
aside #aside-details > summary:first-of-type {
	display:none;
	
	background-color:var(--aside-details-bg);
	color:white;
	padding:0.5em;
}

aside div {
	padding:0.5em;
}

main {
  margin-left: var(--aside-width);
  height:calc(100vh - var(--nav-height));
  overflow-x:auto;
  
  background-color:white;
  
}

/* ------------------------------------------------------------------------------------ */

/* Override native display of ▶ marker in <summary> element. Native element works in desktop
Safari but not in Firefox, and for an unknown reason stopped working in iOS. Hence we
hide native marker and add our own. Based on code from ChatGPT. */

summary::before {
  content: "▶";
  position: absolute;
  left: 0.5em; /* how far marker is from left hand margin */
  transition: transform 0.1s ease-in-out;
}

details[open] summary::before {
  transform: rotate(90deg);
}

summary {
  /* Remove margin or other custom styles if needed, but DO NOT remove list-style in Firefox */
  cursor: pointer;
}

/* Optional: restore the marker in Firefox if removed */
summary::-webkit-details-marker {
  display: none; /* For Chrome/Safari if you're customizing */
}

/* ------------------------------------------------------------------------------------ */

/* small */
@media screen and (max-width: 800px) {
  aside {
    width: 100%;
    height: auto;
    position: fixed;
  }
  
  /* on small screens we show the <summary> element so we can toggle aside on and off */
  aside #aside-details > summary:first-of-type {
	display:block;
	
	/* if overriding native <summary> element add padding to clearly separate marker and text */
	padding-left:2em;
	
  }  
 
  main { 
  	margin-left: 0; 
  	padding-top:2em;
  }
  
  
}

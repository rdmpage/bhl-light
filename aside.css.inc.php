/* some ideas borrowed from https://www.w3schools.com/howto/howto_css_sidebar_responsive.asp */

aside {
    width:var(--aside-width);
	position: fixed;
	background-color:white;
}

/* by default (desktop) we hide the <summary> element */
/* in part based on https://stackoverflow.com/a/18772539 */
aside #aside-details > summary:first-of-type {
	display:none;
	
	background-color:black;
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
}

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
	
  }  
 
  main { margin-left: 0; }
}

/* https://dev.to/arnu515/auto-dark-light-theme-using-css-only-2e0m */
:root {
  	overscroll-behavior: none; /* https://css-irl.info/preventing-overscroll-bounce-with-css/ */
  	
  	--bg: #FEFEFE;
	--text: #333;  	
  	
  	--nav-height: 3em;
  	--nav-bg: white;
  	--nav-border: 1px solid #EEE; 
  	
  	--aside-width:300px;
  	--aside-bg:white;
  	
  	--image-border: 1px solid #DDD;
  	
  	--grid-width: 160px;
  	--grid-image-border: #DDD;
  	--grid-image-maxwidth: 200px;
  	
  	--viewer-bg: rgb(225,225,225);
  	--viewer-text-color: transparent;
  	--viewer-footer-bg: rgba(0,0,0,0.5);
  	--viewer-footer-text: white;
  	
}

@media (prefers-color-scheme: dark) {

  :root {
    --bg: #121212;  /* https://m2.material.io/design/color/dark-theme.html#properties */
    --text: #d0d0d0;
    
    --nav-bg: black;
    --nav-border: none; 
    
    --aside-bg:#121212;
    
    --image-border: 1px solid #222;    
    
    /* Make titles less heavy */
    h1 { font-weight: normal; }
    h2 { font-weight: normal; }
    
    /* change links */
    a { color: #76D6FF; }
    
	--grid-image-border: black;    
    
    --viewer-bg: #222;

	}
}

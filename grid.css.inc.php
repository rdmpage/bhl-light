/* https://frontendly.io/blog/responsive-card-grid-layout */

.image-grid {
	padding:0;
	display:grid;
	grid-template-columns: repeat(auto-fit, minmax(var(--grid-width), 1fr));
	background-color:var(--grid-bg);
}

.image-grid li {
	padding:1em;
	margin:0.5em;
	list-style: none;
	/*border:1px solid var(--viewer-bg);*/
}

.image-grid img {
	width:100%;
	border:1px solid var(--grid-image-border);
	border-radius:4px;
	
	/* Keep things sensible if we have, say, only one item */
	max-width: var(	--grid-image-maxwidth);
}

.image-grid div {
	text-align:center;
	font-size:0.8em;
	
	/* Keep things sensible if we have, say, only one item */
	max-width: var(	--grid-image-maxwidth);	
}

/* background for pages that in a part */
.image-grid .selected {
	background-color:var(--coverage-block-bg); 
	color:white;
}

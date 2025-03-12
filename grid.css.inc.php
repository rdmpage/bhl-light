/* https://frontendly.io/blog/responsive-card-grid-layout */

.image-grid {
	padding:0;
	display:grid;
	grid-template-columns: repeat(auto-fit, minmax(var(--grid-width), 1fr));
}

.image-grid li {
	padding:0.5em;
	margin:1em;
	list-style: none;
	
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

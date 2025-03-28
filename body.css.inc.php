body {
  margin: 0;
  padding:0;
  font-family: ui-sans-serif, system-ui, sans-serif;
  overflow:hidden;
  
  background-color: var(--bg);
  color: var(--text); 
}

img {
	border:var(--image-border);
}

/* based on https://bloycey.medium.com/how-to-style-dl-dt-and-dd-html-tags-ced1229deda0 */
dl {
  display: grid;
  grid-gap: 4px 16px;
  grid-template-columns: max-content;
  font-size:0.8em;
  
  /* handle long strings */
  word-break: break-word;
}

dt {
  text-align:right;
}

dd {
  margin: 0;
  grid-column-start: 2;
  font-weight: bold;
}  

a {
	text-decoration: none;
}

/* If we want an underline when we mouse over the link */
a:hover {
	text-decoration:underline;
}

.hero {
	color:var(--hero-text);
	text-align:center;
	background-color:var(--hero-bg);
	padding:2em;
	border-radius:1em;
	margin:1em;
}

.hero div {
	text-align:left;
}

/*
.subhero {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	grid-template-rows: repeat(2, 1fr);
	grid-column-gap: 1em;
	grid-row-gap: 1em;
	
	margin:1em;
}

.subhero div {
	border-radius:0.5em;
	padding:0.5em;
	background-color:var(--hero-bg);
	color:var(--hero-text);
}
*/

.multicolumn ul {
	columns: 200px;
	list-style: none;
	font-size:0.8em;
}
.multicolumn li {
	width:200px;
	white-space: nowrap; 
    overflow: hidden;
    text-overflow: ellipsis;
    line-height:1.2em;
}	

.search {
	border:1px solid var(--input-border);
 	background-color: var(--input-bg);
 	color: var(--input-color);
}  

.search:focus { 
	background-color: var(--input-bg-focus);
	border:1px solid var(--input-border-focus);
	color: var(--input-focus-color);
}

#map {
  height:100%;
  width:100%;
}

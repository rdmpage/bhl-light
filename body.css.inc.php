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

a {
	text-decoration: none;
}

/* If we want an underline when we mouse over the link */
a:hover {
	text-decoration:underline;
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

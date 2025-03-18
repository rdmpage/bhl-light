body {
  margin: 0;
  padding:0;
  font-family: ui-sans-serif, system-ui, sans-serif;
  overflow:hidden;
  
  background-color: var(--bg);
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
  color:#444;
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

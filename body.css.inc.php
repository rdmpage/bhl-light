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
 	font-size:1em;
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

/* cards page */

.card-container {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem;
  justify-content: center;
}

.card {
  background-color:  var(--card-bg);
  border-left: 5px solid var(--bhl-blue);
  padding: 1rem;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  flex: 1 1 100%;
  max-width: 100%;
}


.card h2 {
  margin-top: 0;
  font-size: 1.2em;
}

.button {
  display: inline-block;
  background: var(--bhl-blue);
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  text-decoration: none;
  margin-top: 0.5rem;
}


@media (min-width: 600px) {
  .card {
	flex: 1 1 calc(50%);
	max-width: calc(50%);
  }
}

@media (min-width: 900px) {
  .card {
	flex: 1 1 calc(25%);
	max-width: calc(25%);
  }
}
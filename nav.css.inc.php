
/* navigation bar */

nav {
	height:var(--nav-height);
	border-bottom: var(--nav-border);
 	background-color: var(--nav-bg);
 	color: var(--text);

	width:100%;
	
	position: sticky; 
	top: 0;
	z-index:200;
		 
	line-height: var(--nav-height);
}

/* https://stackoverflow.com/questions/23226888/horizontal-list-items-fit-to-100-with-even-spacing */
nav > ul{
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

nav > ul > li {
    display: block;
    flex: 0 1 auto; /* Default */
    list-style-type: none;
}


li.dropdown {
  min-width: 120px;  
  /*text-align:right;*/
  
}

li.dropdown a {
	width:100%;
	display:block;
	text-align:left;
}

.dropdown-menu {
	display: none;
 	background-color: var(--nav-dropdown-bg);
 	border: var(--nav-border);
 	line-height:1.5em;
 	position:absolute;
 	
 	list-style-type: none;
 	padding-left:1em;
 	padding-right:1em;
}

.dropdown-menu li a:hover {
  background-color: var(--nav-dropdown-hover);
  color: var(--nav-dropdown-hover-text);
}

li.dropdown:hover .dropdown-menu {
  display: block;
}

#search {
	font-size:1em;
}



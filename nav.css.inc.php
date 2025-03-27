
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
}

/* https://stackoverflow.com/questions/23226888/horizontal-list-items-fit-to-100-with-even-spacing */
nav ul{
    margin: 0;
    padding: 1em;
    display: flex;
    align-items: stretch;
    justify-content: space-between;
}

nav ul li {
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
}

.dropdown-menu li a:hover {
  background-color: #666;
}

li.dropdown:hover .dropdown-menu {
  display: block;
}

#search {
	font-size:1em;
}



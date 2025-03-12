iframe {
	border:none;
	height:100%;
	width:100%;
}

.pagenumber {
	display:block;
	text-align:center;
	font-weight:bold;
}
	
.footer {
	margin-left: var(--aside-width);
	/* border:1px solid black; */
    background: var(--viewer-footer-bg);
    color: var(--viewer-footer-text);
    position: fixed;
    left: 1em;
    right: 1em;
    bottom: 1em;
    height: 2em;
    
    display:flex;
    align-items:center;
    justify-content:center;
}  

.footer select {
	background: var(--viewer-footer-bg);
	color: var(--viewer-footer-text);
	border:none;
}

 /* small */
@media screen and (max-width: 800px) {
  .footer { 
  	margin-left: 0; 
  }
}

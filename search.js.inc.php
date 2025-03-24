function search(q) {	
	// classify search string
	
	var m = null
	
	// identifier (to do)
	/*
	m = q.match(/^\s*([A-Z]+\d+-\d+)\s*$/);
	if (m) {
		window.location = "record/" + m[1];	
		return;		
	}
	*/
	
	// string, eventually test whether it is a name or not
	
	window.location = "?q=" + q;	
}
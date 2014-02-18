define( ['./Cache/Null', './Cache/WebSQL', './Cache/IndexedDB'], function( Null, WebSQL, IndexedDB ) {
	var Cache;
	//if( Modernizr.indexeddb ) {
	//	Cache = IndexedDB;
	//}
	if( Modernizr.websqldatabase ) {
		Cache = WebSQL;
	}
	else {
		Cache = Null;
	}

	return Cache;
} );

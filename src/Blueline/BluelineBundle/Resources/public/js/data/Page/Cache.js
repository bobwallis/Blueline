define( ['./Cache/Null', './Cache/WebSQL', './Cache/IndexedDB'], function( Null, WebSQL, IndexedDB ) {
	if( Modernizr.indexeddb ) {
		return IndexedDB;
	}
	if( Modernizr.websqldatabase ) {
		return WebSQL;
	}
	return Null;
} );

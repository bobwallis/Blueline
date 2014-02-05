define( ['./Cache/Null', './Cache/WebSQL', './Cache/IndexedDB'], function( Null, WebSQL, IndexedDB ) {
	var Cache;
	if( Modernizr.indexeddb ) {
		Cache = IndexedDB;
	}
	else if( Modernizr.websqldatabase ) {
		Cache = WebSQL;
	}
	else {
		Cache = Null;
	}

	// Clear the cache if needed
	var age = $( 'html' ).data( 'age' ),
		cacheAge = localStorage.getItem( 'cacheAge' );
	if( age === 'dev' || cacheAge === 'dev' || cacheAge === null || parseInt( cacheAge, 10 ) < parseInt( age, 10 ) ) {
		Cache.clear();
		localStorage.setItem( 'cacheAge', age );
	}

	return Cache;
} );

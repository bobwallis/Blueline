define( ['../Can', './Cache/IndexedDB', './Cache/WebSQL', './Cache/Null'], function( Can, ContentCache_indexedDB, ContentCache_webSQL, ContentCache_null ) {
	// The resources/scripts/buildWWW script will update this line automatically
	var contentCache_born = (new Date( 2011, 11-1, 20 ).toDateString());

	// Return ContentCache depending on support
	var ContentCache;
	if( Can.indexedDB() ) {
		ContentCache = ContentCache_indexedDB( contentCache_born );
	}
	else if( Can.webSQL() ) {
		ContentCache = ContentCache_webSQL( contentCache_born );
	}
	else {
		ContentCache = ContentCache_null;
	}
	
	return ContentCache;
} );

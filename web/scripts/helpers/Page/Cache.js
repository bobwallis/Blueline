define( ['../Can', './Cache/IndexedDB', './Cache/WebSQL', './Cache/Null'], function( Can, ContentCache_indexedDB, ContentCache_webSQL, ContentCache_null ) {
	// The resources/scripts/buildWWW script will update this line automatically 
	// to be the date that the scripts were built. For development, use a random 
	// number so the cache is cleared each load.
	var contentCache_born = (new Date()).toDateString() + '_' + (Math.floor(Math.random()*101)).toString();

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

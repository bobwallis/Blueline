define( ['Modernizr', 'db', './Null', '../../../helpers/LocalStorage'], function( Modernizr, db, Null, LocalStorage ) {
	var IndexedDB;
	if( Modernizr.indexeddb ) {
		// The IndexedDB API is a bit of a moving target. Once it stablises it would be sensible to trim
		// this down a bit by using the actual API, but for now we'll just throw a wrapper over the top
		// of it.
		var indexedDB = window.indexedDB || window.webkitIndexedDB || window.mozIndexedDB || window.oIndexedDB || window.msIndexedDB,
			emptyFunction = function(){};

		// Function to revert the cache back to the null version (use if setup fails)
		var unsetup = function() {
			IndexedDB = Null;
		};
		unsetup();

		db.open( {
			server: 'Blueline',
			version: 4,
			schema: {
				pages: {
					key: {
						keyPath: 'url'
					},
					indexes: {
						timestamp: {
							key: 'timestamp'
						}
					}
				}
			}
		} )
		.then( function ( server ) {
			IndexedDB = { works: true };
			IndexedDB.get = function( url, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.get( 'pages', url )
					.done( function( page ) {
						if( typeof page === 'undefined' ) { failure(); }
						else { success( page.content ); }
					}, failure );
			};
			IndexedDB.set = function( url, content, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.put( 'pages', { url: url, content: content, timestamp: Date.now() } ).then( success, failure );
			};
			IndexedDB.remove = function( url, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.remove( 'pages', url ).then( success, failure );
			};
			IndexedDB.clear = function( success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.query( 'pages' ).all().execute()
					.then( function( results ) {
						results.forEach( function( e, i ) {
							server.remove( 'pages', e.url );
						} );
					} );
			};

			// Do an initial clear of the cache if needed
			var dbAge = LocalStorage.getItem( 'dbAge' );
			if( dbAge === null ) { dbAge = 0; }
			if( dbAge < LocalStorage.age ) {
				IndexedDB.clear();
				LocalStorage.setItem( 'dbAge', LocalStorage.age );
			}
		} );
	}
	return IndexedDB;

} );

define( ['jquery', '../../../lib/db', './Null'], function( $, db, Null ) {
	var IndexedDB;
	if( Modernizr.indexeddb ) {
		// The IndexedDB API is a bit of a moving target. Once it stablises it would be sensible to trim
		// this down a bit by using the actual API, but for now we'll just throw a wrapper over the top
		// of it.
		var indexedDB = window.indexedDB || window.webkitIndexedDB || window.mozIndexedDB || window.oIndexedDB || window.msIndexedDB,
			emptyFunction = $.noop;

		// Function to revert the cache back to the null version (use if setup fails)
		var unsetup = function() {
			IndexedDB = $.extend( {}, Null );
		};
		unsetup();

		db.open( {
			server: 'Blueline',
			version: 3,
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
		.done( function ( server ) {
			IndexedDB.works = true;
			IndexedDB.get = function( url, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.get( 'pages', url )
					.done( function( page ) {
						if( typeof page === 'undefined' ) {
							failure();
						}
						else {
							success( page.content );
						}
					} )
					.fail( failure );
			};
			IndexedDB.set = function( url, content, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.add( 'pages', { url: url, content: content, timestamp: Date.now() } )
					.done( success )
					.fail( failure );
			};
			IndexedDB.remove = function( url, success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.remove( 'pages', url )
					.done( success )
					.fail( failure );
			};
			IndexedDB.clear = function( success, failure ) {
				success = (typeof success === 'undefined') ? emptyFunction : success;
				failure = (typeof failure === 'undefined') ? emptyFunction : failure;
				server.query( 'pages' ).execute().done( function( results ) {
					$.each( results, function( i, e ) {
						server.remove( 'pages', e.url );
					} );
				} );
			};

			// Do an initial clear of the cache if needed
			var age = $( 'html' ).data( 'age' ),
				cacheAge = localStorage.getItem( 'cacheAge' );
			if( age === 'dev' || cacheAge === 'dev' || cacheAge === null || parseInt( cacheAge, 10 ) < parseInt( age, 10 ) ) {
				IndexedDB.clear();
				localStorage.setItem( 'cacheAge', age );
			}
		} );
	}
	return IndexedDB;

} );

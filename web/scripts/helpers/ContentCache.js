define( ['./Can'], function( Can ) {
	var ContentCache = {
		get: function( key, success, failure ) { failure(); },
		set: function( key, value ) {}
	};
	// The resources/buildWWW script will update this line automatically
	var clearBefore = new Date( 2011, 9, 8, 22, 17 );

	var setupLocalStorageCache = function() {
		// Check the cache is not stale
		if( localStorage.getItem( '_pageCache_cleared' ) != clearBefore ) {
			localStorage.clear();
			localStorage.setItem( '_pageCache_cleared', clearBefore );
		}
		ContentCache = {
			get: function( url, success, failure ) {
				var content = localStorage.getItem( url );
				if( content === null ) {
					failure();
				}
				else {
					success( content );
				}
			},
			set: function( url, content ) {
				localStorage.setItem( url, content );
			}
		};
	}

	var setupIndexedDbCache = function() {
		var request = indexedDB.open( 'Cache' ),
			db;
		request.onsuccess = function( e ) {
			db = e.target.result;
			// Check the cache is not stale
			if( clearBefore != db.version ) {
				var setVrequest = db.setVersion( clearBefore );
				setVrequest.onfailure = setupLocalStorageCache;
				setVrequest.onsuccess = function( e ) {
					db.deleteObjectStore( 'content' );
					var store = db.createObjectStore( 'content', { keyPath: 'url' } );
					localStorage.setItem( '_pageCache_cleared', clearBefore );
				};
			}
			ContentCache.get = function( url, success, failure ) {
					var transaction = db.transaction(['content'], IDBTransaction.READ_ONLY, 0 ),
						store = transaction.objectStore( 'content' ),
						getReq = store.get( url );
						getReq.onsuccess = function( e ) {
							if( typeof e.target.result !== 'undefined' && typeof e.target.result.content === 'string' ) {
								success( e.target.result.content );
							}
							else {
								failure();
							}
						};
						getReq.onerror = function( e ) {
							transaction.abort();
							failure();
						};
			};
			ContentCache.set = function( url, content ) {
				// Don't worry about catching errors, this can fail if it wants
				db.transaction(['content'], IDBTransaction.READ_WRITE, 0 )
					.objectStore( 'content' )
					.put( { content: content, url: url } );
			};
		};
		request.onfailure = setupLocalStorageCache;
	};
	
	
	if( Can.indexedDB() ) {
		setupIndexedDbCache();
	}
	else if( Can.localStorage() ) {
		setupLocalStorageCache();
	}

	return ContentCache;
} );

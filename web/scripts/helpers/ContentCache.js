define( ['./Can'], function( Can ) {
	var ContentCache = {
		get: function( key, success, failure ) { failure(); },
		set: function( key, value ) {},
		hasStore: false
	};
	// The resources/buildWWW script will update this line automatically
	var clearBefore = (new Date( 2011, 9, 11 )).toDateString();

	// Wipe out legacy cache entries
	if( !localStorage.getItem( '_pageCache_cleared' ) ) {
		localStorage.clear();
	}

	var setupLocalStorageCache = function() {
		// Check the cache is not stale
		if( localStorage.getItem( '_pageCache_cleared' ) != clearBefore ) {
			localStorage.clear();
			localStorage.setItem( '_pageCache_cleared', clearBefore );
		}
		ContentCache.get = function( url, success, failure ) {
			var content = localStorage.getItem( url );
			if( content === null ) {
				failure();
			}
			else {
				success( content );
			}
		};
		ContentCache.set = function( url, content ) {
			localStorage.setItem( url, content );
		};
		ContentCache.hasStore = true;
	}

	var setupIndexedDbCache = function() {
		var request = indexedDB.open( 'Cache' ),
			db;
		request.onsuccess = function( e ) {
			db = e.target.result;
			// Check the cache is not stale
			if( clearBefore != db.version ) {
				var setDatabaseVersion = function() {
					var setVersionRequest = db.setVersion( clearBefore );
					setVersionRequest.onfailure = setupLocalStorageCache;
					setVersionRequest.onsuccess = function( e ) {
						if( !db.objectStoreNames.contains( 'content' ) ) {
							db.createObjectStore( 'content', { keyPath: 'url' } );
						}
						localStorage.setItem( '_pageCache_cleared', clearBefore );
					};
				};
				// If the content cache exists, empty it then update the version
				if( db.objectStoreNames.contains( 'content' ) ) {
					var clearTransaction = db.transaction(['content'], IDBTransaction.READ_WRITE ),
						clearRequest = clearTransaction.objectStore( 'content' ).clear();
					clearRequest.onfailure = setupLocalStorageCache;
					clearRequest.onsuccess = setDatabaseVersion;
				}
				// Otherwise create it while setting the version
				else {
					setDatabaseVersion();
				}
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
			ContentCache.hasStore = true;
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

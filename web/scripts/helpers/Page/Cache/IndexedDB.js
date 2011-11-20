define( ['jquery', './Null'], function( $, ContentCache_null ) {
	var db, ContentCache;
	
	// Revert ContentCache back to the null version
	var unsetup = function() {
		db = null;
		ContentCache = $.extend( {}, ContentCache_null )
	};
	unsetup();
	
	// Opens the database and copies it into db variable
	var openDatabase = function( success, failure ) {
		var request = indexedDB.open( 'Cache' );
		request.onsuccess = function( e ) {
			db = e.target.result;
			success();
		};
		request.onfailure = failure;
	};
	
	// Clears the database
	var clearDatabase = function( success, failure ) {
		if( db.objectStoreNames.contains( 'page' ) ) {
			var transaction = db.transaction(['page'], IDBTransaction.READ_WRITE ),
				request = transaction.objectStore( 'page' ).clear();
			request.onsuccess = success;
			request.onfailure = failure;
		}
		else {
			success();
		}
	};
	
	// Sets the database version, and creates/clears the page object store as appropriate
	var setDatabaseVersion = function( version, success, failure ) {
		if( version != db.version ) {
			var request = db.setVersion( version );
			request.onsuccess = function() {
				// Create the object store if it doesn't exist
				if( !db.objectStoreNames.contains( 'page' ) ) {
					db.createObjectStore( 'page', { keyPath: 'url' } );
					success();
				}
				// Clear it if it does
				else {
					clearDatabase( success, failure );
				}
			};
			request.onfailure = failure;
		}
		else {
			success();
		}
	};
	
	return function( contentCache_born ) {
		// Try and open the database
		openDatabase( function() {
			// Try and update the version as required
			setDatabaseVersion( contentCache_born, function() {
				// If everything works, update the ContentCache object to use the opened database
				ContentCache.get = function( url, success, failure ) {
					var transaction = db.transaction(['page'], IDBTransaction.READ_ONLY, 0 ),
						store = transaction.objectStore( 'page' ),
						request = store.get( url );
					request.onsuccess = function( e ) {
						if( typeof e.target.result !== 'undefined' && typeof e.target.result.content === 'string' ) {
							success( e.target.result.content );
						}
						else {
							failure();
						}
					};
					request.onerror = function( e ) {
						transaction.abort();
						failure();
					};
				};
				ContentCache.set = function( url, content ) {
					// Don't worry about catching errors, this can fail if it wants
					db.transaction(['page'], IDBTransaction.READ_WRITE, 0 )
						.objectStore( 'page' )
						.put( { content: content, url: url } );
				};
				ContentCache.hasStore = true;
			// If anything fails during setup, revert back to the null cache
			}, unsetup );
		}, unsetup );
		
		return ContentCache;
	};
} );

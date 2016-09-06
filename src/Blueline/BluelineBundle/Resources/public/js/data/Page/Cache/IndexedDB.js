define( ['Modernizr', './Null', '../../../helpers/LocalStorage'], function( Modernizr, Null, LocalStorage ) {
	var IndexedDB;
	if( Modernizr.indexeddb ) {
		// Use prefixed indexDB if needed
		window.indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
		window.IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction || window.msIDBTransaction;
		window.IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange || window.msIDBKeyRange;

		// Function to revert the cache back to the null version (use if setup fails)
		var unsetup = function() {
			IndexedDB = Null;
		};
		unsetup();

		var DBOpenRequest = window.indexedDB.open( 'Blueline', 5 );

		DBOpenRequest.onerror = unsetup;

		DBOpenRequest.onupgradeneeded = function( e ) {
			var db = e.target.result;
			db.onerror = unsetup;
			db.deleteObjectStore( 'pages' );
			var objectStore = db.createObjectStore( 'pages', { keyPath: 'url' } );
			objectStore.createIndex( 'timestamp', 'timestamp', { unique: false });
		};

		DBOpenRequest.onsuccess = function() {
			var db = DBOpenRequest.result;

			IndexedDB.works = true;
			IndexedDB.get = function( url, success, failure ) {
				try {
					var transaction = db.transaction( ['pages'], 'readonly' );
					if( typeof failure === 'function' ) { transaction.onerror = failure; }
					var objectStore = transaction.objectStore( 'pages' );
					var objectStoreRequest = objectStore.get( url );
					if( typeof failure === 'function' ) { objectStoreRequest.onerror = failure; }
					if( typeof success === 'function' ) { objectStoreRequest.onsuccess = function() { (typeof objectStoreRequest.result === 'undefined')? failure() : success( objectStoreRequest.result.content ); }; }
				} catch(e) { if( typeof failure === 'function' ) { failure(); } }
			};

			IndexedDB.set = function( url, content, success, failure ) {
				try {
					var transaction = db.transaction( ['pages'], 'readwrite' );
					if( typeof failure === 'function' ) { transaction.onerror = failure; }
					var objectStore = transaction.objectStore( 'pages' );
					var objectStoreRequest = objectStore.put( { url: url, content: content, timestamp: Date.now() } );
					if( typeof failure === 'function' ) { objectStoreRequest.onerror = failure; }
					if( typeof success === 'function' ) { objectStoreRequest.onsuccess = success; }
				} catch(e) { console.log('caught'); if( typeof failure === 'function' ) { failure(); } }
			};

			IndexedDB.remove = function( url, success, failure ) {
				try {
					var transaction = db.transaction( ['pages'], 'readwrite' );
					if( typeof failure === 'function' ) { transaction.onerror = failure; }
					var objectStore = transaction.objectStore( 'pages' );
					var objectStoreRequest = objectStore.delete( url );
					if( typeof failure === 'function' ) { objectStoreRequest.onerror = failure; }
					if( typeof success === 'function' ) { objectStoreRequest.onsuccess = success; }
				} catch(e) { if( typeof failure === 'function' ) { failure(); } }
			};

			IndexedDB.clear = function( success, failure ) {
				try {
					var transaction = db.transaction( ['pages'], 'readwrite' );
					if( typeof failure === 'function' ) { transaction.onerror = failure; }
					var objectStore = transaction.objectStore( 'pages' );
					var objectStoreRequest = objectStore.clear();
					if( typeof failure === 'function' ) { objectStoreRequest.onerror = failure; }
					if( typeof success === 'function' ) { objectStoreRequest.onsuccess = success; }
				} catch(e) { if( typeof failure === 'function' ) { failure(); } }
			};

			// Do an initial clear of the cache if needed
			var dbAge = LocalStorage.getItem( 'dbAge' );
			if( dbAge === null ) { dbAge = 0; }
			if( dbAge < LocalStorage.age ) {
				IndexedDB.clear();
				LocalStorage.setItem( 'dbAge', LocalStorage.age );
			}
		};
	}
	return IndexedDB;

} );

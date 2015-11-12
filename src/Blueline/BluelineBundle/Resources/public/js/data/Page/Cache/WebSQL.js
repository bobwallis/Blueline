define( ['Modernizr', './Null', '../../../helpers/LocalStorage'], function( Modernizr, Null, LocalStorage ) {
	var WebSQL, emptyFunction = function(){}, db;

	// Function to revert the cache back to the null version (use if setup fails)
	var unsetup = function() {
		db = null;
		WebSQL = Null;
	};
	unsetup();

	if( !Modernizr.indexeddb && Modernizr.websqldatabase ) {
		try { db = openDatabase( 'Blueline', '1.1', '', 5242880 ); }
		catch(e) { db = null; }
		if( db ) {
			db.transaction(
				function( tx ) {
					// Create the table if it doesn't exist
					tx.executeSql( 'CREATE TABLE IF NOT EXISTS pages (url UNIQUE, content TEXT, timestamp BIGINT)' );
				},
				// If setup fails, revert the WebSQL object to the null version
				unsetup,
				// Otherwise setup get and set using the database
				function() {
					WebSQL.works = true;
					WebSQL.get = function( url, success, failure ) {
						success = (typeof success === 'undefined') ? emptyFunction : success;
						failure = (typeof failure === 'undefined') ? emptyFunction : failure;
						db.readTransaction( function( tx ) {
							tx.executeSql( 'SELECT content FROM pages WHERE url=?', [url], function( tx, results ) {
								if( results.rows.length === 0 && typeof failure === 'function' ) {
									failure();
								}
								else if( typeof success == 'function' ) {
									success( results.rows.item(0).content );
								}
							} );
						}, failure );
					};
					WebSQL.set = function( url, content, success, failure ) {
						success = (typeof success === 'undefined') ? emptyFunction : success;
						failure = (typeof failure === 'undefined') ? emptyFunction : failure;
						db.transaction( function( tx ) {
							tx.executeSql( 'INSERT INTO pages (url,content,timestamp) VALUES (?,?,?)', [url, content, Date.now()] );
						}, failure, success );
					};
					WebSQL.remove = function( url, success, failure ) {
						success = (typeof success === 'undefined') ? emptyFunction : success;
						failure = (typeof failure === 'undefined') ? emptyFunction : failure;
						db.transaction( function( tx ) {
							tx.executeSql( 'DELETE FROM pages WHERE url=?', [url] );
						}, failure, success );
					};
					WebSQL.clear = function( success, failure ) {
						success = (typeof success === 'undefined') ? emptyFunction : success;
						failure = (typeof failure === 'undefined') ? emptyFunction : failure;
						db.transaction( function( tx ) {
							tx.executeSql( 'DELETE FROM pages' );
						}, failure, success );
					};

					// Do an initial clear of the cache if needed
					var dbAge = LocalStorage.getItem( 'websqlAge' );
					if( dbAge === null ) {
						WebSQL.clear();
						LocalStorage.setItem( 'websqlAge', LocalStorage.age );
					}
				}
			);
		}
	}
	return WebSQL;
} );

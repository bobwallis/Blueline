define( ['Modernizr'], function( Modernizr ) {
	var prefix = 'blueline_',
		dataAge = document.getElementsByTagName('html')[0].getAttribute( 'data-age' ),
		LocalStorage = {
			age: parseInt(dataAge)
		};
	if( Modernizr.localstorage && typeof window.JSON === 'object' ) {
		LocalStorage.getItem = function( key ) {
			return JSON.parse( localStorage.getItem( prefix+key ) );
		};
		LocalStorage.setItem = function( key, value ) {
			localStorage.setItem( prefix+key, JSON.stringify(value) );
		};
		LocalStorage.removeItem = function( key ) {
			localStorage.removeItem( prefix+key );
		};
		LocalStorage.clear = function() {
			localStorage.clear();
		};

		// Clear out the cache if the app's age has changed
		var cacheAge = LocalStorage.getItem( 'cacheAge' );
		if( cacheAge === null ) { cacheAge = 0; }
		if( cacheAge < LocalStorage.age ) {
			LocalStorage.clear();
		}
		LocalStorage.setItem( 'cacheAge', LocalStorage.age );
	}
	else {
		LocalStorage.setItem = LocalStorage.removeItem = LocalStorage.clear = function() {};
		LocalStorage.getItem = function() { return null; };
	}
	return LocalStorage;
} );
/*global require: false, define: false, google: false */
require( { paths: { jquery: '/scripts/lib/jquery' } }, ['require', 'helpers/Can', 'helpers/Shim', 'ui/Hotkeys'], function( require, Can, Shim, Hotkeys ) {
	// Initialise app mode if the browser supports it
	if( Can.history() ) {
		require( ['app'] );
	}
	
	// Update application cache when supported
	if( Can.applicationCache() ) {
		$applicationCache = $( window.applicationCache );
		$applicationCache.bind( 'updateready', function( e ) {
			window.applicationCache.swapCache();
		} );
	}
} );

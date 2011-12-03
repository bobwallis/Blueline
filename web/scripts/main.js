/*global require: false, define: false, google: false */
require( { paths: { jquery: '/scripts/lib/jquery' } }, ['require', 'helpers/Is', 'helpers/Can', 'helpers/Shim', 'ui/Hotkeys'], function( require, Is, Can, Shim, Hotkeys ) {
	// Initialise single session mode if the browser supports it
	if( Can.history() ) {
		require( ['app'] );
	}
	
	// Fallback app loading overlay hiding
	if( Is.app() ) {
		$( 'body > script' ).remove();
		setTimeout( function() { $( '#overlay' ).remove(); }, 1500 ); // Fallback. It will be faded nicely from app.js after it has loaded
	}
	
	// Listen for application cache updates if the browser supports it
	if( Can.applicationCache() ) {
		$applicationCache = $( window.applicationCache );
		$applicationCache.bind( 'updateready', function( e ) {
			window.applicationCache.swapCache();
		} );
	}
} );

/*global require: false, define: false, google: false */
require( { paths: { jquery: '/scripts/lib/jquery' } }, ['require', 'helpers/Is', 'helpers/Can', 'helpers/Shim', 'ui/Hotkeys'], function( require, Is, Can, Shim, Hotkeys ) {
	// Initialise single session mode if the browser supports it
	if( Can.history() ) {
		require( ['app'] );
	}
	
	// Hide loading overlay and enable alternative footer if in an app
	if( true || Is.app() ) {
		$( 'body > script' ).remove();
		setTimeout( function() { $( '#overlay' ).remove(); }, 500 ); // Fallback. It will be faded nicely from app.js after it has loaded
		$( function() {
			$( '#bottom' ).addClass( 'touch' );
		} );
	}
	
	// Listen for application cache updates if the browser supports it
	if( Can.applicationCache() ) {
		$applicationCache = $( window.applicationCache );
		$applicationCache.bind( 'updateready', function( e ) {
			window.applicationCache.swapCache();
		} );
	}
} );

/*global require: false, define: false */
require( { paths: { jquery: '/scripts/lib/jquery' } }, ['require', 'jquery', 'helpers/Is', 'helpers/Can', 'helpers/Settings', 'helpers/Shim', 'ui/Hotkeys'], function( require, $, Is, Can, Settings, Shim, Hotkeys ) {
	// Initialise single session mode if the browser supports it
	if( Can.history() ) {
		require( ['app'] );
	}
	
	// Cleanup scripts
	$( function() { $( 'body > script' ).remove(); } );
	
	// Fallback app loading overlay hiding
	if( Is.app() ) {
		setTimeout( function() { $( '#appStart' ).remove(); }, 10000 ); // Fallback. It will be faded nicely from app.js after it has loaded
	}
	
	// Listen for application cache updates if the browser supports it
	if( Can.applicationCache() ) {
		var $applicationCache = $( window.applicationCache );
		$applicationCache.bind( 'updateready', function( e ) {
			var settings = Settings.getCached();
			localStorage.clear();
			Settings.restore( settings );
			localStorage.setItem( 'ua', navigator.userAgent );
			window.applicationCache.swapCache();
		} );
	}
	
	// Wipe out localStorage if the browser has changed
	if( Can.localStorage() ) {
		var storageUA = localStorage.getItem( 'ua' );
		if( storageUA !== navigator.userAgent ) {
			var settings = Settings.getCached();
			localStorage.clear();
			Settings.restore( settings );
			localStorage.setItem( 'ua', navigator.userAgent );
		}
	}
} );

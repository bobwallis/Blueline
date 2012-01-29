/*
 * Blueline - main.js
 * http://blueline.rsw.me.uk
 *
 * Copyright 2012, Robert Wallis
 * This file is part of Blueline.

 * Blueline is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * Blueline is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Blueline.  If not, see <http://www.gnu.org/licenses/>.
 */
require( { paths: { jquery: '/scripts/lib/jquery' } }, ['require', 'jquery', 'helpers/Is', 'helpers/Can', 'helpers/Settings', 'helpers/Shim', 'ui/Hotkeys'], function( require, $, Is, Can, Settings, Shim, Hotkeys ) {
	// Initialise single session mode if the browser supports it
	if( Is.app() || Can.history() ) {
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

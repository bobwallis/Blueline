/*
 * Blueline - Fetch.js
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
define( ['jquery', './Cache'], function( $, Cache ) {
	// Function to request content over the network, and cache it if possible
	return function( url, success, failure ) {
		var AJAXContentRequest;
		
		// Check if the browser is set to offline, and fail instantly if so
		if( typeof navigator.onLine === 'boolean' && navigator.onLine === false ) {
			failure( null, 'offline' );
		}
		// Otherwise fetch over the network
		else {
			AJAXContentRequest = $.ajax( {
				url: url,
				dataType: 'html',
				data: 'chromeless=2',
				cache: Cache.hasStore? false: true, // Bypass the browser's cache if our own is implemented
				success: [success, function( content ) { Cache.set( url, content ); }],
				error: failure
			} );
		}
	};
} );

/*
 * Blueline - Is.js
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
define( function() {
	// Browser sniffing for the most part
	var ua = navigator.userAgent.toLowerCase();
	return {
		android: function() {
			if( ua.indexOf( 'android' ) !== -1 ) {
				var version = /android ([\d|\.]*)/.exec( ua );
				if( version[1] !== '' ) {
					version = parseFloat( version[1] );
					if( !isNaN( version ) ) {
						return version;
					}
				}
				return true;
			}
			return false;
		},
		app: function() {
			return ( (('standalone' in navigator) && navigator.standalone) || typeof window['Android'] === 'object' );
		},
		aApp: function() {
			return (typeof window['Android'] === 'object');
		},
		cApp: function() {
			try {
				return chrome.app.isInstalled;
			}
			catch( e ) {
				return false;
			}
		},
		iApp: function() {
			return (('standalone' in navigator) && navigator.standalone);
		}
	};
} );

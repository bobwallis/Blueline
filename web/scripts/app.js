/*
 * Blueline - app.js
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
define( [ 'require', 'jquery', 'helpers/Can', 'helpers/Is', 'lib/History', 'ui/Window', 'ui/Header', 'ui/Content' ], function( require, $, Can, Is, History, Window, Header, Content ) {
	var baseURL = location.protocol+'//'+location.host,
		baseURLRegexp = new RegExp( '^'+location.protocol+'\/\/'+location.host );

	if( Can.history() && History.enabled ) {

		// Capture link clicks
		var historyClick = function( e ) {
			var target = $( e.target ).closest( 'a' );
			if( target.length > 0 ) {
				var href = target.attr( 'href' );
				if( href.indexOf( '/' ) === 0 ) {
					href = baseURL+href;
				}
				else if( href.indexOf( 'javascript:' ) !== 0 && href.indexOf( '//' ) !== 0 && href.indexOf( 'http://' ) !== 0 && href.indexOf( 'https://' ) !== 0 ) {
					var url = History.getState().url;
					href = url.substr( 0, url.lastIndexOf( '/' ) )+'/'+href;
				}
				if( baseURLRegexp.exec( href ) !== null ) {
					e.preventDefault();
					History.pushState( { type: 'click' }, null, href );
				}
			}
		};

		// Capture form submitions
		var historyFormSubmit = function( e ) {
			var form = $( e.target );
			if( form.is( 'form' ) ) {
				var href = form.attr( 'action' ) + '?' + form.serialize();
				e.preventDefault(); // The forms we use this on will be GET ones, so the href will be enough to execute them
				History.pushState( { type: 'submit' }, null, href );
			}
		};

		// Capture changes to input fields
		var historyInputChange = function( e ) {
			var input = $( e.target );
			// Don't fire for various non-character keys, or if the input has been focussed by a '/' press
			if( [13,16,17,27,33,34,35,36,37,38,39,40,45,91].indexOf( e.which ) !== -1 || ( e.which === 191 && input.val().indexOf( '/' ) === -1 ) ) {
				return true;
			}
			var form = input.closest( 'form' );
			if( form.length > 0 ) {
				var href = form.attr( 'action' ) + '?' + form.serialize();
				// If the last state type was 'keyup' then replace that state in the history
				if( History.getState().data.type === 'keyup' ) {
					History.replaceState( { type: 'keyup' }, null, href );
				}
				else {
					History.pushState( { type: 'keyup' }, null, href );
				}
			}
		};
		
		var historyInputClipboard = function( e ) {
			var input = $( e.target );
			var form = input.closest( 'form' );
			if( form.length > 1 ) {
				window.setTimeout( function() { // Let cuts and pastes happen before firing
					var href = form.attr( 'action' ) + '?' + form.serialize();
					History.pushState( { type: 'clipboard' }, null, href );
				}, 5 );
			}
		};

		History.Adapter.bind( window, 'statechange', function( e ) {
			var state = History.getState();
			if( baseURLRegexp.exec( state.url ) !== null ) {
				e.preventDefault();
				Header.update( state.url );
				Content.update( state.url );
			}
		} );

		// DOM Ready/Load event
		$( function() {
			var $body = $( document.body );
		
			// Attach listeners to click events
			$body.click( historyClick );

			// Attach listeners to little search form's submit event
			$( '#topSearch' ).submit( historyFormSubmit );

			// Attach listeners to big search form's submit event, and input change events
			$( '#bigSearch' ).submit( historyFormSubmit )
				.keyup( historyInputChange )
				.bind( 'cut',  historyInputClipboard )
				.bind( 'paste', historyInputClipboard );
			
			// Finish fading out #overlay
			$( '#appStart' ).css( 'opacity', 0 );
			setTimeout( function() { $( '#appStart' ).remove(); }, 200 );
			
			// Preload font used to draw methods
			require( ['plugins/font!BluelineMono'] );
			
			// Track app startup
			if( Is.iApp() ) {
				_gaq.push( ['_trackEvent', 'Startup', 'iOS'] );
			}
			else if( Is.aApp() ) {
				_gaq.push( ['_trackEvent', 'Startup', 'Android'] );
			}
			else if( Is.cApp() ) {
				_gaq.push( ['_trackEvent', 'Startup', 'Chrome'] );
			}
			else {
				_gaq.push( ['_trackEvent', 'Startup', 'Other'] );
			}
		} );
	}
	
	return true;
} );

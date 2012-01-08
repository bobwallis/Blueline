/*
 * Blueline - Content.js
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
define( ['jquery', '../lib/History', '../helpers/Can', '../helpers/Page', './Loading', './Header', './Window'], function( $, History, Can, Page, Loading, Header, Window ) {
	var $content = [], $towerMap = [],
		towerMapRegexp = /\/(associations|towers)\/view/;

	var Content = {
		update: function( url ) {
			// Get DOM objects
			if( $content.length === 0 ) { $content = $( '#content' ); }
			if( $towerMap.length === 0 ) { $towerMap = $( '#towerMap' ); }
			
			// Show a loading animation if we're not doing instant results
			if( History.getState().data.type !== 'keyup' || $content.is( ':empty' ) ) {
				$content.empty();
				Loading.show();
			}
			
			// Hide the tower map if it won't be needed, and reset content width
			if( towerMapRegexp.exec( url ) === null ) {
				$towerMap.hide();
				Loading.container.css( 'width', '100%' );
				$content.css( 'width', '100%' );
			}
			
			// Request page content
			Page( url,
				function( data ) {
					var state = History.getState();
					// Check the content requested hasn't arrived after some more recently requested content
					if( state.url === url ) {
						Loading.hide();
						$content.html( data );
						Window.update( url );
						// Push a pageview to Google Analytics
						if( typeof _gaq !== 'undefined' && state.data.type !== 'keyup' ) {
							_gaq.push( ['_trackPageview'] );
						}
					}
				},
				function( jqXHR, textStatus, errorThrown ) {
					var errorMessage;
					Loading.hide();
					switch( textStatus ) {
						case 'offline':
							$content.html( '<section class="text"><div class="wrap"><p class="appError">Content is unavailable while offline. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>' );
							Window.title( 'Offline | Blueline' );
							break;
						case 'timeout':
							$content.html( '<section class="text"><div class="wrap"><p class="appError">Request timed out. <a href="javascript:location.reload(true)">Refresh</a> to retry.</p></div></section>' );
							Window.title( 'Timeout | Blueline' );
							break;
						case 'error':
							if( !errorThrown ) {
								// Assume any undefined errors are due to being offline
								$content.html( '<section class="text"><div class="wrap"><p class="appError">Content is unavailable while offline. <a href="javascript:history.go(-1)">Go back</a>.</p></div></section>' );
								Window.title( 'Offline | Blueline' );
								break;
							}
						default:
							$content.html( '<section class="text"><header><h1>'+errorThrown+'</h1></header><div class="wrap"><p>Visit the homepage to find what you\'re looking for.</p></div></section>' );
							Window.title( errorThrown+' | Blueline' );
							break;
					}
				}
			);
		}
	};
	
	return Content;
} );


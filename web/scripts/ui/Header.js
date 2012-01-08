/*
 * Blueline - Header.js
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
define( ['jquery'], function( $ ) {
	var $window = false, $breadcrumbContainer, $topSearchContainer, $topSearch, $topSearchInput, $bigSearchContainer, $bigSearch, $bigSearchInput,
		sectionRegexp = /^(.*)\/(associations|methods|towers)($|\/)/,
		topSearchRegexp = /\/view\//,
		bigSearchRegexp = /\/(associations|methods|towers)($|\/search)/;
	
	return {
		update: function( url ) {
			// Initialise jQuery objects if not already done
			if( $window === false ) {
				$window = $( window );
				$breadcrumbContainer = $( '#breadcrumbContainer' );
				$topSearchContainer = $( '#topSearchContainer' );
				$topSearch = $( '#topSearch' );
				$topSearchInput = $( '#smallQ' );
				$bigSearchContainer = $( '#bigSearchContainer' );
				$bigSearch = $( '#bigSearch' );
				$bigSearchInput = $( '#bigQ' );
			}
			
			// Apply the default header if needed
			var section = sectionRegexp.exec( url );
			if( section === null ) {
				$breadcrumbContainer.empty();
				$topSearchContainer.hide();
				$bigSearchContainer.hide();
			}
			else {
				// Update and show the search bar in the header if needed
				if( $window.width() > 480 && topSearchRegexp.exec( url ) !== null ) {
					$topSearch.attr( 'action', section[1]+'/'+section[2]+'/search' );
					$topSearchInput.attr( 'placeholder', 'Search '+section[2] ).val( '' );
					$topSearchContainer.show();
				}
				else {
					$topSearchInput.blur();
					$topSearchContainer.hide();
				}
				
				// Update and show the main search box if needed
				var bigSearchRegexpResult = bigSearchRegexp.exec( url );
				if( bigSearchRegexpResult !== null ) {
					$bigSearch.attr( 'action', section[1]+'/'+section[2]+'/search' );
					$bigSearchInput.attr( 'placeholder', 'Search '+section[2] );
					if( bigSearchRegexpResult[2] === '' ) {
						$bigSearchInput.val( '' );
						$bigSearchInput.blur();
					}
					else if( !$bigSearchInput.is( ':focus' ) ) {
						var queryString = url.replace( /^.*?(\?|$)/, '' );
						$bigSearchInput.val( (queryString.indexOf( 'q=' ) !== -1)? decodeURI( queryString.replace( /^.*q=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '' );
					}
					$bigSearchContainer.show();
				}
				else {
					$bigSearchInput.blur();
					$bigSearchContainer.hide();
				}
				
				// Update the text breadcrumb in the header
				switch( section[2] ) {
					case 'associations':
						$breadcrumbContainer.html( '<span class="headerSep">&raquo;</span><h2><a href="'+section[1]+'/associations">Associations</a></h2>' );
						break;
					case 'methods':
						$breadcrumbContainer.html( '<span class="headerSep">&raquo;</span><h2><a href="'+section[1]+'/methods">Methods</a></h2>' );
						break;
					case 'towers':
						$breadcrumbContainer.html( '<span class="headerSep">&raquo;</span><h2><a href="'+section[1]+'/towers">Towers</a></h2>' );
						break;
					default:
						$breadcrumbContainer.empty();
						break;
				}
			}
		}
	};
} );

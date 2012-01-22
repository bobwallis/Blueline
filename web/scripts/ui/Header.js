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
define( ['jquery', '../helpers/Is', '../lib/History'], function( $, Is, History ) {
	var $window = false, $backButton, $title, $breadcrumbContainer, $topSearchContainer, $topSearch, $topSearchInput, $bigSearchContainer, $bigSearch, $bigSearchInput,
		sectionRegexp = /^(.*)\/(associations|methods|towers)($|\/)/,
		topSearchRegexp = /\/view\//,
		bigSearchRegexp = /\/(associations|methods|towers)($|\/search)/;
	
	var Header = {
		update: function( url ) {
			// Hide/show the back button if needed
			if( $backButton.length > 0 && url.split('/')[3] === '' ) {
				$backButton.css( 'opacity', 0 );
			}
			else {
				$backButton.css( 'opacity', 1 );
			}
			
			// Apply the default header if needed
			var section = sectionRegexp.exec( url );
			if( section === null ) {
				$title.html( '<a href="/">BLUELINE</a>' );
				$breadcrumbContainer.empty();
				$topSearchContainer.hide();
				$bigSearchContainer.hide();
			}
			else {
				// Update and show the search bar in the header if needed
				if( $topSearch.length > 0 && $window.width() > 480 && topSearchRegexp.exec( url ) !== null ) {
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
						$bigSearchInput.val( '' )
							.blur();
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
			
				// Update the text in the header
				$breadcrumbContainer.html( '<span class="headerSep">&raquo;</span><h2><a href="'+section[1]+'/'+section[2]+'">'+section[2].charAt(0).toUpperCase()+section[2].slice(1)+'</a></h2>' );
				$title.html( '<a href="'+section[1]+'/'+section[2]+'">'+section[2].charAt(0).toUpperCase()+section[2].slice(1)+'</a>' );
			}
		}
	};
	
	// Initialise
	$( function() {
		// Initialise jQuery objects
		$window = $( window );
		$bigSearchContainer = $( '#bigSearchContainer' );
		$bigSearch = $( '#bigSearch' );
		$bigSearchInput = $( '#bigQ' );
		
		// Convert to iPhone header if needed
		if( Is.iApp() ) {
			$backButton = $( '<div id="back"></div>' )
				.on( 'click', History.back )
				.on( 'dblclick', function() { History.pushState( { type: 'click' }, null, location.protocol+'//'+location.host+'/' ); } )
				.css( 'opacity', (location.href.split('/')[3] === '')? 0 : 1 );
			$( '#top' ).before( $backButton );
			$( '#breadcrumbContainer' ).remove();
			$( '#topSearchContainer' ).remove();
			$title = $( '#top h1:first' ).css( {
				'float': 'none',
				'margin': 0,
				'text-align': 'center',
				'text-transform': 'none'
			} );
			$breadcrumbContainer = $topSearchContainer = $topSearch = $topSearchInput = $( '#nothing' );
		}
		else {
			// We only need these jQuery objects if the header is normal
			$title = $backButton = $( '#nothing' );
			$breadcrumbContainer = $( '#breadcrumbContainer' );
			$topSearchContainer = $( '#topSearchContainer' );
			$topSearch = $( '#topSearch' );
			$topSearchInput = $( '#smallQ' );
		}
		
		// Update header for the current page
		Header.update( location.href );
	} );
	
	return Header;
} );

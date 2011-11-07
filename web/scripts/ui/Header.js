/*global require: false, define: false, google: false, History: false */
define( ['jquery'], function( $ ) {
	var $window = false, $breadcrumbContainer, $topSearchContainer, $topSearch, $topSearchInput, $bigSearchContainer, $bigSearch, $bigSearchInput,
		sectionRegexp = /^(.*)\/(associations|methods|towers)($|\/)/,
		topSearchRegexp = /(associations|\/view\/)/,
		bigSearchRegexp = /\/(associations\/search|((methods|towers)($|\/search)))/;
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
					$topSearchContainer.hide();
				}
				
				// Update and show the main search box if needed
				if( bigSearchRegexp.exec( url ) !== null ) {
					$bigSearch.attr( 'action', section[1]+'/'+section[2]+'/search' );
					$bigSearchInput.attr( 'placeholder', 'Search '+section[2] );
					if( !$bigSearchInput.is( ':focus' ) ) {
						var queryString = url.replace( /^.*?(\?|$)/, '' );
						$bigSearchInput.val( (queryString.indexOf( 'q=' ) !== -1)? decodeURI( queryString.replace( /^.*q=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '' );
					}
					$bigSearchContainer.show();
				}
				else {
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

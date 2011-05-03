/*global require: false, define: false, google: false */
define( function() {
	var $header, $breadcrumbContainer, $topSearchContainer, $topSearch, $topSearchInput, $bigSearchContainer, $bigSearch, $bigSearchInput;
	$( function() {
		$header = $( '#top' );
		$breadcrumbContainer = $( '#breadcrumbContainer' );
		$topSearchContainer = $( '#topSearchContainer' );
		$topSearch = $( '#topSearch' );
		$topSearchInput = $( '#smallQ' );
		$bigSearchContainer = $( '#bigSearchContainer' );
		$bigSearch = $( '#bigSearch' );
		$bigSearchInput = $( '#bigQ' );
	} );
	return {
		windowTitle: function( set ) {
			if( typeof set === 'undefined' ) {
				return document.title;
			}
			else {
				document.title = ( !set )? 'Blueline' : set+' | Blueline';
			}
		},
		breadcrumb: function( set ) {
			if( set === false ) {
				$breadcrumbContainer.empty();
			}
			else {
				$breadcrumbContainer.html( set.map( function( b ) {
					return '<span class="headerSep">&raquo;</span><h2><a href="'+b.url+'">'+b.title+'</a></h2>';
				} ).join( '' ) );
			}
		},
		topSearch: function( set ) {
			if( set === false ) {
				$topSearchContainer.hide();
			}
			else {
				$topSearch.attr( 'action', (typeof set.action === 'string')?set.action:'/search' );
				$topSearchInput.attr( 'placeholder', (typeof set.placeholder === 'string')?set.placeholder:'Search' );
				$topSearchInput.val( '' );
				$topSearchContainer.show();
			}
		},
		bigSearch: function( set ) {
			if( set === false ) {
				$bigSearchContainer.hide();
			}
			else {
				// Action
				$bigSearch.attr( 'action', (typeof set.action === 'string')?set.action:'/search' );
				// Placeholder
				$bigSearchInput.attr( 'placeholder', (typeof set.placeholder === 'string')?set.placeholder:'Search' );
				// Value
				if( !$bigSearchInput.is( ':focus' ) ) { // Only modify the value when not focussed to prevent messing with user input
					if( typeof set.value === 'string' ) {
						$bigSearchInput.val( set.value );
					}
					else if( typeof History === 'object' && History.enabled ) {
						var queryString = History.getState().url.replace( /^.*?(\?|$)/, '' );
						$bigSearchInput.val( (queryString.indexOf( 'q=' ) !== -1)? queryString.replace( /^.*q=(.*?)(&.*$|$)/, '$1' ) : '' );
					}
				}
				$bigSearchContainer.show();
			}
		}
	};
} );

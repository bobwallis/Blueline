// In general, we rely on the browser's own fallback mechanisms, and use progressive enhancement.
// In some cases though, a modern browser feature is so important to the UI that having it missing
// is a problem. This file will fix such problems.
define( ['jquery', 'Modernizr', '../../helpers/URL'], function( $, Modernizr, URL ) {
	$( function() {
		// PNG background images instead of SVG where the browser doesn't support SVG
		if( !Modernizr.svg ) {
			var sharedText = '{background-image:url(' + $( '#top h1 a' ).attr( 'href' );
			$( '<style>#search div'+sharedText+'images/search.png) !important}a.external'+URL.baseURL+'images/external.png) !important}.search li.selected'+URL.baseURL+'images/selectIndicator.png) !important}</style>' ).appendTo( 'head' );
		}

		// Placeholders in input fields
		if( !Modernizr.input.placeholder ) {
			var addPlaceholder = function() {
				var $this = $( this );
				if( $this.val() === '' ) {
					$this.val( $this.attr( 'placeholder' ) ).addClass( 'placeholder' );
				}
			},
			removePlaceholder = function() {
				var $this = $( this );
				if( $this.val() === $this.attr( 'placeholder' ) ) {
					$this.val( '' ).removeClass( 'placeholder' );
				}
			};
			$( function() {
				$( document )
					.on( 'blur', 'input[placeholder], textarea[placeholder]', addPlaceholder )
					.on( 'focus', 'input[placeholder], textarea[placeholder]', removePlaceholder )
					.on( 'submit', 'form', function() {
						$(this).find( 'input[placeholder], textarea[placeholder]' ).each( removePlaceholder );
					} );
				$( 'input[placeholder], textarea[placeholder]' ).each( addPlaceholder );
			} );
		}
	} );
} );

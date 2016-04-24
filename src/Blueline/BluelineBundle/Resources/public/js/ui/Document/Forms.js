// Shared form appearance modifiers

define( ['jquery'], function( $ ) {
	$( function() {
		// Highlighte active selects and inputs
		$(document).on( 'focus', 'input, select', function( e ) {
			$parent = $(e.target).parent();
			if( $parent.is( '.select-wrapper, .input-wrapper' ) ) {
				$parent.css( 'box-shadow', '0 0 4px #002856');
			}
		} ).on( 'blur', 'input, select', function( e ) {
			$parent = $(e.target).parent();
			if( $parent.is( '.select-wrapper, .input-wrapper' ) ) {
				$parent.css( 'box-shadow', '0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24)');
			}
		} );
	} );
} );

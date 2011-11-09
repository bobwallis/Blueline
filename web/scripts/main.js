/*global require: false, define: false, google: false */
require( { paths: { jquery: '/scripts/helpers/jquery' } }, ['require', 'helpers/Can', 'ui/Hotkeys'], function( require, Can, Hotkeys ) {
	// Initialise app mode if the browser supports it
	if( Can.history() && ( Can.localStorage() || Can.indexedDB() ) ) {
		require( ['app'] );
	}
	
	// Placeholder for browsers that don't support it
	if( !Can.placeholder() ) {
		var addPlaceholder = function() {
			var $this = $( this );
			if( $this.val() == '' ) {
				$this.val( $this.attr( 'placeholder' ) ).addClass( 'placeholder' );
			}           
		},
		removePlaceholder = function() {
			var $this = $( this );
			if( $this.val() == $this.attr( 'placeholder' ) ) {
				$this.val( '' ).removeClass( 'placeholder' );
			}
		};
		$( function() {
			// Select the elements that have a placeholder attribute
			$( 'input[placeholder], textarea[placeholder]').blur( addPlaceholder ).focus( removePlaceholder ).each( addPlaceholder );
			// Remove the placeholder text before the form is submitted
			$( 'form' ).submit( function() {
				$(this).find( 'input[placeholder], textarea[placeholder]' ).each( removePlaceholder );
			} );
		} );
	}
} );

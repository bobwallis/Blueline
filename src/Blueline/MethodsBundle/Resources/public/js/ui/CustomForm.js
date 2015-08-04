define( ['jquery', '../helpers/PlaceNotation'], function( $, PlaceNotation ) {

	var updateExpansion = function( e ) {
		var $input = $( '#custom_method_notation' );

		if( $input.val() !== '' ) {
			var stage = parseInt( $( '#custom_method_stage' ).val() );
			if( isNaN( stage ) ) {
				stage = Math.max.apply( Math, $input.val().split( '' ).map( PlaceNotation.charToBell ) ) + 1;
			}

			var longNotation = PlaceNotation.expand( $input.val(), isNaN( stage )? undefined : stage );
			$('#custom_method_notationParsed').removeClass( 'placeholder' )
				.html( longNotation.replace( /(x|\.)/g, function(t) { return ' '+t+' '; } ) );
		}
		else {
			$('#custom_method_notationParsed').addClass( 'placeholder' );
		}
	};

	$(document).on( 'keyup cut paste', '#custom_method_notation', updateExpansion )
		.on( 'change', '#custom_method_stage', updateExpansion );
} );
define( ['jquery', 'eve', '../helpers/PlaceNotation'], function( $, eve, PlaceNotation ) {
	var prevURL = location.href; // Store the previous state so we can restore the form if user moves back
	eve.on( 'page.finished', function( url ) {
		var $custom_method_notation = $( '#custom_method_notation' );
		if( $custom_method_notation.length > 0 ) {
				var queryString = prevURL.replace( /^.*?(\?|$)/, '' );
				$custom_method_notation.val( (queryString.indexOf( 'notation=' ) !== -1)? decodeURIComponent( queryString.replace( /^.*notation=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '' );
				$( '#custom_method_stage' ).val( (queryString.indexOf( 'stage=' ) !== -1)? decodeURIComponent( queryString.replace( /^.*stage=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '' );
		}
		updateExpansion();
		prevURL = url;
	} );

	var updateExpansion = function( e ) {
		var $input = $( '#custom_method_notation' );

		if( $input.val() !== '' ) {
			var stage = parseInt( $( '#custom_method_stage' ).val() );
			var longNotation = PlaceNotation.expand( $input.val(), isNaN( stage )? undefined : stage );
			$('#custom_method_notationParsed').removeClass( 'placeholder' )
				.html( longNotation.replace( /(x|\.)/g, function(t) { return ' '+t+' '; } ) );
		}
		else {
			$('#custom_method_notationParsed').html( '…' ).addClass( 'placeholder' );
		}
	};

	$(document).on( 'keyup cut paste', '#custom_method_notation', updateExpansion )
		.on( 'change', '#custom_method_stage', updateExpansion );
} );
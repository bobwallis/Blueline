define( ['eve', '$document_on', '../helpers/PlaceNotation'], function( eve, $document_on, PlaceNotation ) {
	var prevURL = location.href; // Store the previous state so we can restore the form if user moves back

	eve.on( 'page.finished', function( url ) {
		var custom_method_notation = document.getElementById( 'custom_method_notation' );
		if( custom_method_notation !== null ) {
			var queryString = prevURL.replace( /^.*?(\?|$)/, '' );
			custom_method_notation.value = (queryString.indexOf( 'notation=' ) !== -1)? decodeURIComponent( queryString.replace( /^.*notation=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '';
			document.getElementById( 'custom_method_stage' ).value = (queryString.indexOf( 'stage=' ) !== -1)? decodeURIComponent( queryString.replace( /^.*stage=(.*?)(&.*$|$)/, '$1' ).replace( /\+/g, '%20' ) ) : '';
			updateExpansion();
		}
		prevURL = url;
	} );

	var updateExpansion = function( e ) {
		var custom_method_notation       = document.getElementById( 'custom_method_notation' ),
			custom_method_stage          = document.getElementById( 'custom_method_stage' ),
			custom_method_notationParsed = document.getElementById( 'custom_method_notationParsed' );

		if( custom_method_notation !== null ) {
			if( custom_method_notation.value !== '' ) {
				var stage        = parseInt( custom_method_stage.value, 10 ),
					notation     = custom_method_notation.value,
					longNotation = PlaceNotation.expand( notation, isNaN( stage )? undefined : stage );
				if( longNotation.length > 0 ) {
					custom_method_notationParsed.classList.remove( 'placeholder' );
					custom_method_notationParsed.innerHTML = longNotation.replace( /(x|\.)/g, function(t) { return ' '+t+' '; } );
				}
			}
			else {
				custom_method_notationParsed.innerHTML = '…';
				custom_method_notationParsed.classList.add( 'placeholder' );
			}
		}
	};

	$document_on( 'keyup',  '#custom_method_notation', updateExpansion );
	$document_on( 'cut',    '#custom_method_notation', updateExpansion );
	$document_on( 'paste',  '#custom_method_notation', updateExpansion );
	$document_on( 'change', '#custom_method_stage',    updateExpansion );
} );

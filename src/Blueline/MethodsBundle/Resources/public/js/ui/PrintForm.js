define( ['jquery', 'shared/helpers/URL'], function( $, URL ) {
	var $document = $( document );

	// Update the row/column counts when the inputs change, show and hide the 's' in the HTML as needed
	// and update the number of method forms to match the number of rows and columns
	var rows    = 1,
		columns = 1,
		methodFormTemplate = $( 'fieldset.method_form' ).last().prop( 'outerHTML' );
	var changeRowsOrColumns = function( e ) {
		rows    = parseInt( $( '#paper_rows' ).val() );
		columns = parseInt( $( '#paper_columns' ).val() );
		if( columns > 1 ) { $( '#paper_columns_s' ).show(); }
		else              { $( '#paper_columns_s' ).hide(); }
		if( rows > 1 )    { $( '#paper_rows_s' ).show(); }
		else              { $( '#paper_rows_s' ).hide(); }

		for( var i = 0; i < rows*columns; ++i ) {
			if( $( '#m'+i+'_title' ).length === 0 ) {
				$( 'fieldset.method_form' ).last().after( methodFormTemplate.replace( 'Method 1', 'Method '+(i+1) ).replace( /m0_/g, 'm'+i+'_' ) );
			}
			else {
				$( '#m'+i+'_title' ).closest( 'fieldset' ).show();
			}
		}
		var methodFormCount = $( 'fieldset.method_form' ).length;
		while( i < methodFormCount ) {
			$( '#m'+i+'_title' ).closest( 'fieldset' ).hide();
			++i;
		}
	};
	$document.on( 'change', '#paper_columns, #paper_rows', changeRowsOrColumns );


	// Search for methods when typing the title
	var methodFormBeingCompleted = -1,
		searchURL = URL.baseURL+'methods/search.json',
		currentlyDisplayedRequestTimestamp = Date.now(),
		lastRequestQ = '';
	$document.on( 'keyup', 'input.method_title', function( e ) {
		var $target = $(e.target),
			requestTimestamp = Date.now(),
			q = $target.val();
		methodFormBeingCompleted2 = $target.attr( 'id' ).replace( /m(\d{1}).*/, '$1' );
		
		if( q && ( methodFormBeingCompleted2 != methodFormBeingCompleted || q != lastRequestQ ) ) { // To ensure we don't send multiple requests for the same thing when the user's typing fast
			methodFormBeingCompleted = methodFormBeingCompleted2;
			lastRequestQ = q;
			$.getJSON( searchURL, {
				fields: 'title,stage,notation',
				count: 5,
				q: $target.val()
			}, function( data ) {
				if( requestTimestamp > currentlyDisplayedRequestTimestamp ) { // To ensure we don't override the view with old data if responses arrive out of sequence
					requestTimestamp = currentlyDisplayedRequestTimestamp;
					// Create the autocomplete container if we need it
					if( $( '#m_title_acomp' ).length == 0 ) {
						$(document.body).append( '<ol id="m_title_acomp"><li/><li/><li/><li/><li/></ol>' );
						var pos = $target.position();
						$( '#m_title_acomp' ).css( {
							position: 'absolute',
							top: (pos.top + $target.outerHeight())+'px',
							left: pos.left+'px'
						} );
					}
					var $m_title_acomp = $( '#m_title_acomp' ),
						$autocomplete_li = $( '#m_title_acomp li' );
					for( var i = 0; i < 5; ++i ) {
						if( typeof data.results[i] === 'undefined' ) {
							$( $autocomplete_li[i] ).hide();
						}
						else {
							$( $autocomplete_li[i] )
								.data( 'title', data.results[i].title )
								.data( 'stage', data.results[i].stage )
								.data( 'notation', data.results[i].notation )
								.html( data.results[i].title )
								.show();
						}
					}
				}
			} );
		}
		// Clear autocompletes if query is empty
		if( !q ) {
			$( '#m_title_acomp' ).remove();
			methodFormBeingCompleted = -1;
		}
	} )
	.on( 'focus', 'input.method_title', function( e ) { $(e.target).keyup(); } )
	.on( 'click', function( e ) {
		if( $( '#m_title_acomp' ).length > 0 ) {
			var $target = $(e.target);
			if( $target.is( '#m_title_acomp li' ) ) {
				$( '#m'+methodFormBeingCompleted+'_title' ).val( $target.data( 'title' ) );
				$( '#m'+methodFormBeingCompleted+'_stage' ).val( $target.data( 'stage' ) );
				$( '#m'+methodFormBeingCompleted+'_notation' ).val( $target.data( 'notation' ) );
			}
			$( '#m_title_acomp' ).remove();
			methodFormBeingCompleted = -1;
		}
	} );


	// Form submit listener
	$document.on( 'submit', '#print_form', function( e ) {
		// Remove any extra method forms we don't actually need before submitting
		var methodFormCount = $( 'fieldset.method_form' ).length;
		for( i = (rows*columns)+1; i <= methodFormCount; ++i ) {
			$( '#m'+i+'_title' ).closest( 'fieldset' ).remove();
		}
	} );

} );
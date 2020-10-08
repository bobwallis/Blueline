require( ['jquery', 'lib/webfont', 'helpers/GridOptionsBuilder', 'helpers/Grid', 'helpers/PlaceNotation'], function( $, webfont, GridOptionsBuilder, Grid, PlaceNotation ) {
	$( function() {
		// Preload the webfont
		webfont( $.noop );

		$( '.page' ).each( function( i, v ) {
			// Get options
			var options = $(v).data( 'options' );
			console.log(options);
			// Create each method
			options.methods.forEach( function( m, i ) {
				// Create a grid with basic options
				var method = new GridOptionsBuilder( $.extend( { id: i }, m ) );
				method.gridOptions.plainCourse.grid.layout = method.gridOptions.plainCourse.numbers.layout;
				var grid = new Grid( $.extend( true, method.gridOptions.plainCourse[options.style], {
					scale: 2,
					sideNotation: {
						show: options.show_notation
					},
					placeStarts: {
						show: options.show_placestarts
					}
				} ) );

				// Work out how much space we have to fill

				// Determine the best number of columns to fit the space

				// Make tweaks to improve the fit

				var im = grid.draw();
				$( '#m'+i+' div.line' ).append( im );
			} );
		} );
	} );
} );

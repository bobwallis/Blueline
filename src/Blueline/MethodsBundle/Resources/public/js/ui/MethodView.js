define( ['jquery', 'eve', 'Modernizr', 'shared/lib/webfont', 'shared/helpers/URL', 'shared/helpers/LocalStorage', '../helpers/Method',  '../helpers/Grid', '../helpers/PlaceNotation'], function( $, eve, Modernizr, webfont, URL, LocalStorage, Method, MethodGrid, PlaceNotation ) {
	var newMethodView;

	// Display messages if canvas is not supported
	if( !Modernizr.canvas ) {
		newMethodView = function( options ) {
			$( options.lineContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=numbers&scale=1" /></div>' );
			$( options.gridContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=grid&scale=1" /></div>' );
		};
	}

	var options, method,
		lineContainer, gridContainer,
		active = false,
		lastStyle, lastFollow, lastScale, lastNumberOfColumns,
		line_plainCourse, line_calls;

	newMethodView = function( o ) {
		options = o;
		active = true;
		lastStyle = lastFollow = lastScale = lastNumberOfColumns = null;
		lineContainer = $( options.lineContainer );
		gridContainer = $( options.gridContainer );
		redrawMethodView();
	};

	var redrawMethodView = function() {
		if( active ) {
			// Re-fetch options
			var newScale = (typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1,
				newFollow = LocalStorage.getSetting( 'method_follow', 'heaviest' ),
				newStyle = (URL.parameter( 'style' ) !== null)? URL.parameter( 'style' ) : LocalStorage.getSetting( 'method_style', 'numbers' ),
				widths, maxWidth;

			// Re-create method object if the bell being followed has changed
			if( newFollow !== lastFollow ) {
				options.workingBell = newFollow;
				method = new Method( $.extend( true, {}, options ) );
			}

			// If the style has changed re-create the whole Grid object for the line
			if( newFollow !== lastFollow || newStyle !== lastStyle ) {
				line_plainCourse = new MethodGrid( method.gridOptions.plainCourse[newStyle]() );
				line_calls = method.gridOptions.calls[newStyle]().map( function( callOptions ) { return new MethodGrid( callOptions ); } );
			}

			// Now we're sure to have a line_* object, check the number of columns
			var newNumberOfColumns = (function() {
				var numberOfLeads = method.numberOfLeads,
					callWidth = (typeof line_calls[0] === 'object')? line_calls[0].measure().canvas.width : 0;
				return function() {
					var leadsPerColumn = 1,
						numberOfColumns = numberOfLeads,
						maxWidth = $('#content').width() - 24; // leave space for scrollbar
					line_plainCourse.setOptions( { layout: { numberOfColumns: numberOfColumns } } );
					while( leadsPerColumn <= numberOfLeads && line_plainCourse.measure().canvas.width + callWidth + 48 > maxWidth ) {
						++leadsPerColumn;
						numberOfColumns = Math.ceil( numberOfLeads / leadsPerColumn );
						line_plainCourse.setOptions( { layout: { numberOfColumns: numberOfColumns } } );
					}
					return numberOfColumns;
				};
			})()();

			// The only reason to redraw the grids is if the scale has changed
			if( newScale !== lastScale ) {
				var grid_plainCourse = new MethodGrid( method.gridOptions.plainCourse.grid() ),
					grid_calls = method.gridOptions.calls.grid().map( function( callOptions ) { return new MethodGrid( callOptions ); } );
				gridContainer.empty().append(
					grid_plainCourse.draw(),
					grid_calls.map( function(e) { return e.draw(); } )
				);
				// Give all the grids the same width
				widths = $( 'canvas', gridContainer ).map( function( i, e ) { return $(e).width(); } ).toArray();
				maxWidth = Math.max.apply( Math, widths );
				$( 'canvas', gridContainer ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
			}

			// Redraw the calls on the line view if the scale or style has changed
			if( newScale !== lastScale || newStyle !== lastStyle ) {
				lineContainer.empty().append( line_plainCourse.draw(), line_calls.map( function(e) { return e.draw(); } ) );
				// Give all the calls the same with on the line tab
				widths = $( 'canvas:not(:first)', lineContainer ).map( function( i, e ) { return $(e).width(); } ).toArray();
				maxWidth = Math.max.apply( Math, widths );
				$( 'canvas:not(:first)', lineContainer ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
			}

			// Redraw the plain course in some other cases
			if( newScale !== lastScale || newFollow !== lastFollow || newStyle !== lastStyle || newNumberOfColumns !== lastNumberOfColumns ) {
				lineContainer.children(':first-child').remove();
				lineContainer.prepend( line_plainCourse.draw() );
			}

			lastStyle = newStyle;
			lastScale = newScale;
			lastNumberOfColumns = newNumberOfColumns;
			lastFollow = newFollow;
		}
	};

	// Check and listen for new MethodView requests
	var checkForNewSettings = function() {
		active = false;
		webfont( function() {
			$( '.MethodView' ).each( function( i, e ) {
				newMethodView( $(e).data('set') );
			} );
		} );
	};
	eve.on( 'page.loaded', checkForNewSettings );
	checkForNewSettings();
	$(window).resize( redrawMethodView );
	eve.on( 'setting.changed.*', redrawMethodView );

	return newMethodView;
} );
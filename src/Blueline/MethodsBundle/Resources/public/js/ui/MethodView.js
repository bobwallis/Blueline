define( ['jquery', 'eve', 'Modernizr', 'shared/lib/webfont', '../helpers/Method',  '../helpers/Grid', '../helpers/PlaceNotation'], function( $, eve, Modernizr, webfont, Method, MethodGrid, PlaceNotation ) {
	// Display messages if canvas is not supported
	if( !Modernizr.canvas ) {
		return function( options ) {
			$( options.numbersContainer ).html( '<div class="wrap"><img src="'+location.href+'.png" /></div>' );
			$( options.lineContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=line" /></div>' );
			$( options.gridContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=grid" /></div>' );
		};
	}

	var MethodView = function( options ) {
		this.id = options.id.toString();

		// Containers
		this.container = {
			numbers: $( options.numbersContainer ).empty(),
			lines: $( options.lineContainer ).empty(),
			grid: $( options.gridContainer ).empty()
		};

		// Fetch method details
		this.method = new Method( $.extend( true, {}, options ) );

		// Store a reference to the main scope
		var that = this;

		// Create grids
		var numbers_plainCourse = new MethodGrid( this.method.gridOptions.plainCourse.numbers() ),
			numbers_calls = this.method.gridOptions.calls.numbers().map( function( callOptions ) { return new MethodGrid( callOptions ); } ),
			lines_plainCourse = new MethodGrid( this.method.gridOptions.plainCourse.lines() ),
			lines_calls = this.method.gridOptions.calls.lines().map( function( callOptions ) { return new MethodGrid( callOptions ); } ),
			grid_plainCourse = new MethodGrid( this.method.gridOptions.plainCourse.grid() ),
			grid_calls = this.method.gridOptions.calls.grid().map( function( callOptions ) { return new MethodGrid( callOptions ); } );

		// Determine the number of columns for the plain course to ensure it fits on the page
		var setNumberOfColumns = (function() {
			var numberOfLeads = that.method.numberOfLeads,
				callWidth = (typeof numbers_calls[0] === 'object')? numbers_calls[0].measure().canvas.width : 0;
			return function() {
				var leadsPerColumn = 1,
					numberOfColumns = numberOfLeads,
					maxWidth = $('#content').width() - 24; // leave space for scrollbar
				numbers_plainCourse.setOptions( { layout: { numberOfColumns: numberOfColumns } } );
				while( leadsPerColumn <= numberOfLeads && numbers_plainCourse.measure().canvas.width + callWidth + 48 > maxWidth ) {
					++leadsPerColumn;
					numberOfColumns = Math.ceil( numberOfLeads / leadsPerColumn );
					numbers_plainCourse.setOptions( { layout: { numberOfColumns: numberOfColumns } } );
					lines_plainCourse.setOptions( { layout: { numberOfColumns: numberOfColumns } } );
				}
				return numberOfColumns;
			};
		})();

		// Function to redraw lines
		var lastDrawnScale = 0,
			lastDrawnNumberOfColumns = 0;
		var reDraw = function() {
			var newNumberOfColumns = setNumberOfColumns(),
				newScale = (typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1,
				widthds, maxWidth;
			// If the scale has changed redraw everything
			if( lastDrawnScale !== newScale ) {
				that.container.numbers.empty().append( numbers_plainCourse.draw(), numbers_calls.map( function(e) { return e.draw(); } ) );
				that.container.lines.empty().append( lines_plainCourse.draw(), lines_calls.map( function(e) { return e.draw(); } ) );
				that.container.grid.empty().append( grid_plainCourse.draw(), grid_calls.map( function(e) { return e.draw(); } ) );
				// Give all the grids the same width
				widths = $( 'canvas', that.container.grid ).map( function( i, e ) { return $(e).width(); } ).toArray();
				maxWidth = Math.max.apply( Math, widths );
				$( 'canvas', that.container.grid ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
				// Give all the calls the same with on the numbers and line bits
				widths = $( 'canvas:not(:first)', that.container.numbers ).map( function( i, e ) { return $(e).width(); } ).toArray();
				maxWidth = Math.max.apply( Math, widths );
				$( 'canvas:not(:first)', that.container.numbers ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
				$( 'canvas:not(:first)', that.container.lines ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
			}
			// If the leads per column has changed then redraw the plain courses
			else if( lastDrawnNumberOfColumns !== newNumberOfColumns ) {
				that.container.numbers.children(':first-child').remove();
				that.container.lines.children(':first-child').remove();
				that.container.numbers.prepend( numbers_plainCourse.draw() );
				that.container.lines.prepend( lines_plainCourse.draw() );
			}

			lastDrawnScale = newScale;
			lastDrawnNumberOfColumns = newNumberOfColumns;
		};
		reDraw();
		$(window).resize( reDraw );

		return this;
	};

	// Check and listen for new MethodView requests
	var checkForNewSettings = function() {
		webfont( function() {
			$( '.MethodView' ).each( function( i, e ) {
				new MethodView( $(e).data('set') );
			} );
		} );
	};
	eve.on( 'page.loaded', checkForNewSettings );
	$(checkForNewSettings);

	return MethodView;
} );
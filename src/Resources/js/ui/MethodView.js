define( ['jquery', 'eve', './MethodView/InteractiveGridOverlay', '../lib/webfont', '../helpers/URL', '../helpers/LocalStorage', '../helpers/GridOptionsBuilder',  '../helpers/Grid', '../helpers/PlaceNotation', '../helpers/Text', '../helpers/Music'], function( $, eve, InteractiveGridOverlay, webfont, URL, LocalStorage, GridOptionsBuilder, MethodGrid, PlaceNotation, Text, Music ) {
	var newMethodView;

	var options, method, methodTexts,
		lineContainer, gridContainer,
		active = false,
		lastShowToolTips, lastHighlightMusic, lastStyle, lastFollow, lastScale, lastNumberOfColumns,
		music,
		options_plainCourse, line_plainCourse, line_calls;

	newMethodView = function( o ) {
		options = o;
		active = true;
		lastShowToolTips = lastHighlightMusic = lastStyle = lastFollow = lastScale = lastNumberOfColumns = music = null
		lineContainer = $( options.lineContainer );
		gridContainer = $( options.gridContainer );
		redrawMethodView();
	};

	var redrawMethodView = function() {
		if( active ) {
			// Re-fetch options
			var newShowTooltips = LocalStorage.getSetting( 'method_tooltips', true ),
				newHighlightMusic = LocalStorage.getSetting( 'method_music', false ),
				newScale = (typeof window.devicePixelRatio === 'number')? window.devicePixelRatio : 1,
				newFollow = LocalStorage.getSetting( 'method_follow', 'heaviest' ),
				newStyle = (URL.parameter( 'style' ) !== null)? URL.parameter( 'style' ) : LocalStorage.getSetting( 'method_style', 'numbers' ),
				widths, maxWidth;

			// Re-create method object if the bell being followed has changed
			if( newFollow !== lastFollow ) {
				options.workingBell = newFollow;
				method = new GridOptionsBuilder( $.extend( true, {}, options ) );
			}

			if( newShowTooltips !== lastShowToolTips || newFollow !== lastFollow ) {
				methodTexts = newShowTooltips? method.workGroups.map( function( e ) {
					var toFollow = (newFollow == 'lightest')? Math.min.apply( Math, e ) : Math.max.apply( Math, e );
					return {
						bell: toFollow,
						hunt: (e.length === 1),
						text: Text.fromNotation( (new Array( method.numberOfLeads + 1 )).join( method.notation.text+'.' ), method.stage, toFollow, true )
					};
				} ) : [];
			}

			// Analyse music of plain course if needed
			if( newHighlightMusic && music === null ) {
				music = Music( PlaceNotation.allRows( [].concat.apply( [], Array( method.numberOfLeads ).fill( PlaceNotation.parse( options.notation, options.stage ) ) ), PlaceNotation.rounds( options.stage ) ) )
					.map( function( e ) {
						// Having this logic here isn't ideal. It should probably be in the scoring code instead...
						return e.score.map( function( s ) {
							var s2 = 0.35*Math.min(Math.pow(s/(100-(method.stage*3)),1/1.4), 1);
							return 'rgba(0,255,0,'+(s2<0.1?0:s2)+')';
						} );
					} );
			}

			// If the style or music analysis settings have changed re-create the whole Grid object for the line
			if( newHighlightMusic !== lastHighlightMusic || newFollow !== lastFollow || newStyle !== lastStyle ) {
				options_plainCourse = method.gridOptions.plainCourse[newStyle]();
				options_plainCourse.highlighting = newHighlightMusic? { show: true, colors: music } : false;
				line_plainCourse = new MethodGrid( options_plainCourse );
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
				lineContainer.empty().append( line_calls.map( function(e) { return e.draw(); } ) );
				// Give all the calls the same with on the line tab
				widths = $( 'canvas', lineContainer ).map( function( i, e ) { return $(e).width(); } ).toArray();
				maxWidth = Math.max.apply( Math, widths );
				$( 'canvas', lineContainer ).map( function( i, e ) {
					$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
				} );
			}

			// Redraw the plain course (and update the interactive grid overlay) in those and some other cases
			if( lastHighlightMusic !== newHighlightMusic || lastShowToolTips !== newShowTooltips || newScale !== lastScale || newFollow !== lastFollow || newStyle !== lastStyle || newNumberOfColumns !== lastNumberOfColumns ) {
				if( !(newScale !== lastScale || newStyle !== lastStyle) ) { // If the calls have been re-drawn then no need to remove the first child since that bit clears the whole container
					lineContainer.children(':first-child').remove();
				}
				lineContainer.prepend( line_plainCourse.draw() );
				if( newShowTooltips ) {
					InteractiveGridOverlay( line_plainCourse, method, methodTexts );
				}
			}

			lastShowToolTips = newShowTooltips;
			lastHighlightMusic = newHighlightMusic;
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

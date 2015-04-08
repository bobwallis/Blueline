// TODO: This section is generally an absolute mess. Rewrite.

// Create ../helpers/Method for calculating useful information about a method (rather than doing it below)
// Split this into MethodView/Numbers and MethodView/Grid

define( ['jquery', 'shared/lib/webfont!Blueline', '../helpers/Method',  '../helpers/Grid', '../helpers/PlaceNotation'], function( $, webFontLoaded, Method, MethodGrid, PlaceNotation ) {
	// Display messages if canvas is not supported
	if( !Modernizr.canvas ) {
		return function( options ) {
			$( options.numbersContainer ).html( '<div class="wrap"><img src="'+location.href+'.png" /></div>' );
			$( options.lineContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=line" /></div>' );
			$( options.gridContainer ).html( '<div class="wrap"><img src="'+location.href+'.png?style=grid" /></div>' );
		};
	}

	// Reusable
	var $window = $( window ),
		$body = $( document.body );

	var MethodView = function( options ) {
		this.id = options.id.toString();

		// Containers
		this.container = {
			numbers: $( options.numbersContainer ).empty(),
			lines: $( options.lineContainer ).empty(),
			grid: $( options.gridContainer ).empty()
		};

		// Method details
		this.method = new Method( $.extend( {}, options, {

		} ) );

		this.drawNumbers();
		this.drawLines();
		this.drawGrids();

		return this;
	};

	MethodView.prototype = {
		drawNumbers: function() {
			// Get settings
			var workingBellColor = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
				huntBellColor = '#D11',
				workingBellWidth = 2,
				huntBellWidth = 1.2,
				columnPadding = 10,
				rowHeight = 13,
				rowWidth = ( function( stage ) {
					// Measure the text
					var testCanvas = $( '<canvas></canvas>' ).get( 0 ),
						ctx = testCanvas.getContext( '2d' );
					ctx.font = '12px '+((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'Blueline, "Andale Mono", Consolas, ')+'monospace';
					return ctx.measureText( Array( stage + 1 ).join( '0' ) ).width + stage;
				} )( this.method.stage ),
				leadsPerColumn,

				sharedOptions = {
					dimensions: {
						row: {
							height: rowHeight,
							width: rowWidth
						}
					}
				};

			// For the plain course image, draw a line through the heaviest working bell of each type, and the hunt bells
			var toFollow = this.method.workGroups.map( function( g ) { return Math.max.apply( Math, g ); } ),
				plainLines = [];
			for( var i = 0, j = 0; i < this.method.stage; ++i ) {
				plainLines.push( {
					width: (this.method.huntBells.indexOf( i ) !== -1)? huntBellWidth : workingBellWidth,
					stroke: (this.method.huntBells.indexOf( i ) !== -1)? huntBellColor : ((toFollow.indexOf( i ) !== -1)? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
				} );
			}

			// Decide which bells get place starts drawn on the plain course image (hint: the ones that have lines being drawn that aren't hunt bells)
			var plainPlaceStarts = plainLines.map( function( l, i ) { return (l.stroke !== 'transparent' && this.method.huntBells.indexOf( i ) === -1)? i : -1; }, this ).filter( function( l ) { return l !== -1; } );

			// For calls, draw lines through affected bells, and hunt bells
			var callLines = [];
			this.method.gridOptions.calls.forEach( function( call, k ) {
				callLines[k] = [];
				for( var i = 0, j = 0; i < this.method.stage; ++i ) {
					callLines[k].push( {
						width: (this.method.huntBells.indexOf( i ) !== -1)? huntBellWidth : workingBellWidth,
						stroke: (this.method.huntBells.indexOf( i ) !== -1)? huntBellColor : ((call.affected.indexOf( i ) !== -1)? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
					} );
				}
			}, this );

			// Determine the appropriate lead distribution for the plain course to ensure it fits on the page
			var determineLeadsPerColumn = (function( view ) {
				var numberOfLeads = view.method.numberOfLeads,
					numberOfCalls = view.method.gridOptions.calls.length,
					maxWidth = view.container.numbers.width(),
					callWidth = 15 + rowWidth,
					placeStartWidth = (10 + plainPlaceStarts.length*13);
				return function() {
					var leadsPerColumn = 1;
					maxWidth = $('#content').width() - 24;
					// Check that the window isn't plenty big enough before bothering to look at adjusting
					if( maxWidth <= 2*callWidth + 5 + (rowWidth + placeStartWidth + columnPadding)*numberOfLeads ) {
						for( leadsPerColumn = 1; leadsPerColumn < numberOfLeads; ++leadsPerColumn ) {
							if( maxWidth > callWidth + Math.ceil( numberOfLeads/leadsPerColumn )*(columnPadding + rowWidth + placeStartWidth ) ) {
								break;
							}
						}
					}
					return leadsPerColumn;
				};
			})( this );
			leadsPerColumn = determineLeadsPerColumn();

			// Options object for the plain course
			var plainCourseOptions = $.extend( true, {}, this.method.gridOptions.plainCourse, sharedOptions, {
				id: 'numbers'+this.id+'_plain',
				callingPositions: (this.method.callingPositions === false)? false: $.extend( { show: true }, this.method.callingPositions ),
				dimensions: {
					columnPadding: columnPadding
				},
				layout: {
					numberOfLeads: this.method.numberOfLeads,
					numberOfColumns: Math.ceil( this.method.numberOfLeads / leadsPerColumn )
				},
				placeStarts: {
					show:true,
					bells: plainPlaceStarts
				},
				lines: {show: true, bells: plainLines },
				numbers: {show: true, bells: plainLines.map( function( l, i ) { return { color: (l.stroke !== 'transparent')? 'transparent' : '#000' }; } ) }
			} );

			// Create the plain course image
			var plainCourseContainer = this.container.numbers;
			plainCourseContainer.append( (new MethodGrid( plainCourseOptions )).draw() );

			// Redistribute the plain course's leads across the required number of columns to fit the page when resizing
			var plainCourseResizedLastFired = 0;
			$window.resize( function() {
				// Fire at most once every 200ms
				var nowTime = (new Date()).getTime();
				if( nowTime - plainCourseResizedLastFired < 200 ) { return; }
				else { plainCourseResizedLastFired = nowTime; }
				var currentLeadsPerColumn = leadsPerColumn,
					newLeadsPerColumn = determineLeadsPerColumn();
				if( currentLeadsPerColumn !== newLeadsPerColumn ) {
					$( '#'+plainCourseOptions.id ).remove();
					plainCourseOptions.layout.numberOfColumns = Math.ceil( plainCourseOptions.layout.numberOfLeads / newLeadsPerColumn );
					plainCourseContainer.prepend( (new MethodGrid( plainCourseOptions )).draw() );
					leadsPerColumn = newLeadsPerColumn;
				}
			} );
			
			// Create images for the calls. These will not be redrawn when resizing the window, so don't store the options for later use
			this.method.gridOptions.calls.forEach( function( call, i ) {
				var callGrid = new MethodGrid( $.extend( true, {}, call, sharedOptions, {
					id: 'numbers'+this.id+'_'+call.id,
					numberOfLeads: 1,
					lines: { show: true, bells: callLines[i] },
					numbers: { show: true, bells: callLines[i].map( function( l ) { return { color: (l.stroke !== 'transparent')? 'transparent' : '#000' }; } ) }
				} ) );
				this.container.numbers.append( callGrid.draw() );
			}, this );
		},
		drawLines: function() {
			// Get settings
			var workingBellColor = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
				huntBellColor = '#D11',
				workingBellWidth = 2,
				huntBellWidth = 1.2,
				columnPadding = 10,
				rowHeight = 13,
				rowWidth = ( function( stage ) {
					// Measure the text
					var testCanvas = $( '<canvas></canvas>' ).get( 0 ),
						ctx = testCanvas.getContext( '2d' );
					ctx.font = '12px '+((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'Blueline, "Andale Mono", Consolas, ')+'monospace';
					return ctx.measureText( Array( stage + 1 ).join( '0' ) ).width + stage;
				} )( this.method.stage ),
				leadsPerColumn,

				sharedOptions = {
					numbers: false,
					dimensions: {
						row: {
							height: rowHeight,
							width: rowWidth
						}
					},
					verticalGuides: {
						shading: {
							show: true
						}
					}
				};

			// For the plain course image, draw a line through the heaviest working bell of each type, and the hunt bells
			var toFollow = this.method.workGroups.map( function( g ) { return Math.max.apply( Math, g ); } ),
				plainLines = [];
			for( var i = 0, j = 0; i < this.method.stage; ++i ) {
				plainLines.push( {
					width: (this.method.huntBells.indexOf( i ) !== -1 || toFollow.indexOf( i ) === -1)? huntBellWidth : workingBellWidth,
					stroke: (this.method.huntBells.indexOf( i ) !== -1)? huntBellColor : ((toFollow.indexOf( i ) !== -1)? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'rgba(0,0,0,0.1)')
				} );
			}

			// Decide which bells get place starts drawn on the plain course image (hint: the ones that have lines being drawn that aren't hunt bells)
			var plainPlaceStarts = plainLines.map( function( l, i ) { return (l.stroke !== 'rgba(0,0,0,0.1)' && this.method.huntBells.indexOf( i ) === -1)? i : -1; }, this ).filter( function( l ) { return l !== -1; } );

			// For calls, draw lines through affected bells, and hunt bells
			var callLines = [];
			this.method.gridOptions.calls.forEach( function( call, k ) {
				callLines[k] = [];
				for( var i = 0, j = 0; i < this.method.stage; ++i ) {
					callLines[k].push( {
						width: (this.method.huntBells.indexOf( i ) !== -1 || toFollow.indexOf( i ) === -1)? huntBellWidth : workingBellWidth,
						stroke: (this.method.huntBells.indexOf( i ) !== -1)? huntBellColor : ((call.affected.indexOf( i ) !== -1)? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'rgba(0,0,0,0.1)')
					} );
				}
			}, this );

			// Determine the appropriate lead distribution for the plain course to ensure it fits on the page
			var determineLeadsPerColumn = (function( view ) {
				var numberOfLeads = view.method.numberOfLeads,
					numberOfCalls = view.method.gridOptions.calls.length,
					maxWidth = view.container.numbers.width(),
					callWidth = 15 + rowWidth,
					placeStartWidth = (10 + plainPlaceStarts.length*13);
				return function() {
					var leadsPerColumn = 1;
					maxWidth = $('#content').width() - 24;
					// Check that the window isn't plenty big enough before bothering to look at adjusting
					if( maxWidth <= 2*callWidth + 5 + (rowWidth + placeStartWidth + columnPadding)*numberOfLeads ) {
						for( leadsPerColumn = 1; leadsPerColumn < numberOfLeads; ++leadsPerColumn ) {
							if( maxWidth > callWidth + Math.ceil( numberOfLeads/leadsPerColumn )*(columnPadding + rowWidth + placeStartWidth ) ) {
								break;
							}
						}
					}
					return leadsPerColumn;
				};
			})( this );
			leadsPerColumn = determineLeadsPerColumn();

			// Options object for the plain course
			var plainCourseOptions = $.extend( true, {}, this.method.gridOptions.plainCourse, sharedOptions, {
				id: 'lines'+this.id+'_plain',
				callingPositions: (this.method.callingPositions === false)? false: $.extend( { show: true }, this.method.callingPositions ),
				dimensions: {
					columnPadding: columnPadding
				},
				layout: {
					numberOfLeads: this.method.numberOfLeads,
					numberOfColumns: Math.ceil( this.method.numberOfLeads / leadsPerColumn )
				},
				placeStarts: {
					show:true,
					bells: plainPlaceStarts
				},
				lines: {show: true, bells: plainLines }
			} );

			// Create the plain course image
			var plainCourseContainer = this.container.lines;
			plainCourseContainer.append( (new MethodGrid( plainCourseOptions )).draw() );

			// Redistribute the plain course's leads across the required number of columns to fit the page when resizing
			var plainCourseResizedLastFired = 0;
			$window.resize( function() {
				// Fire at most once every 200ms
				var nowTime = (new Date()).getTime();
				if( nowTime - plainCourseResizedLastFired < 200 ) { return; }
				else { plainCourseResizedLastFired = nowTime; }
				var currentLeadsPerColumn = leadsPerColumn,
					newLeadsPerColumn = determineLeadsPerColumn();
				if( currentLeadsPerColumn !== newLeadsPerColumn ) {
					$( '#'+plainCourseOptions.id ).remove();
					plainCourseOptions.layout.numberOfColumns = Math.ceil( plainCourseOptions.layout.numberOfLeads / newLeadsPerColumn );
					plainCourseContainer.prepend( (new MethodGrid( plainCourseOptions )).draw() );
					leadsPerColumn = newLeadsPerColumn;
				}
			} );
			
			// Create images for the calls. These will not be redrawn when resizing the window, so don't store the options for later use
			this.method.gridOptions.calls.forEach( function( call, i ) {
				var callGrid = new MethodGrid( $.extend( true, {}, call, sharedOptions, {
					id: 'lines'+this.id+'_'+call.id,
					numberOfLeads: 1,
					lines: { show: true, bells: callLines[i] }
				} ) );
				this.container.lines.append( callGrid.draw() );
			}, this );
		},

		drawGrids: function() {
			// Get settings
			var i,
				workingBellColor = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
				huntBellColor = '#D11',
				workingBellWidth = 2,
				huntBellWidth = 1.8,

				sharedOptions = {
					dimensions: ($window.width() > 600)? { row: {height: 14}, bell: {width: 12} } : { row: {height: 11}, bell: {width: 9} },
					sideNotation: { show: true },
					numbers: false,
					verticalGuides: {
						lines: {
							show: true,
							stroke: '#DDD'
						}
					},
					lines: {show: true, bells: ( function( iLim, huntBells ) {
						var lines = [], i = 0, j = 0;
						for(; i < iLim; ++i ) {
							var isHuntBell = (huntBells.indexOf( i ) !== -1);
							lines.push( {
								width: isHuntBell? huntBellWidth : workingBellWidth,
								stroke: isHuntBell? huntBellColor : workingBellColor[j++] || workingBellColor[j = 0, j++]
							} );
						}
						return lines;
					} )( this.method.stage, this.method.huntBells ) }
				};

			// Plain lead
			var plainCourseGrid = new MethodGrid( $.extend( true, {}, this.method.gridOptions.plainCourse, sharedOptions, {
				id: 'grid'+this.id+'_plain',
				title: {
					text: 'Plain Lead:'
				}
			} ) );
			this.container.grid.append( plainCourseGrid.draw() );
			// Calls
			for( i = 0; i < this.method.gridOptions.calls.length; i++ ) {
				var callGrid = new MethodGrid( $.extend( true, {}, this.method.gridOptions.calls[i], sharedOptions, {
					id: 'grid'+this.id+'_'+this.method.gridOptions.calls[i].id
				} ) );
				this.container.grid.append( callGrid.draw() );
			}

			// Give all the grids the same width
			var widths = $( 'canvas', this.container.grid ).map( function( i, e ) { return $(e).width(); } ).toArray(),
				maxWidth = Math.max.apply( Math, widths );
			$( 'canvas', this.container.grid ).map( function( i, e ) {
				$(e).css( 'margin-left', (12 + maxWidth - widths[i])+'px' );
			} );
		}
	};

	return MethodView;
} );
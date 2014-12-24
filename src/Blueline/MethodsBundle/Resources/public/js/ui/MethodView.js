// TODO: This section is generally an absolute mess. Rewrite.

// Create ../helpers/Method for calculating useful information about a method (rather than doing it below)
// Split this into MethodView/Numbers and MethodView/Grid

// Maybe split the measureTextXAndYPadding function out of ../helpers/Grid
// ../helpers/Grid is just a massive function. At least split things out so it's
// more obvious what on earth is going on.
// ../helpers/PlaceNotation is fine, some documentation would be useful though

define( ['jquery', 'shared/lib/webfont!Blueline', '../helpers/Method',  '../helpers/Grid', '../helpers/PlaceNotation'], function( $, webFontLoaded, Method, MethodGrid, PlaceNotation ) {
	// Display messages if canvas is not supported
	if( !Modernizr.canvas ) {
		return function( options ) {
			$( options.numbersContainer ).html( '<div class="wrap"><img src="'+location.href+'.png" /></div>' );
			$( options.gridContainer ).html( '<div class="wrap"><p class="nothing">Your browser doesn\'t support canvas elements, and so can\'t draw methods. Consider upgrading to a more modern browser.</p></div>' );
		};
	}

	// Reusable
	var $window = $( window ),
		$body = $( document.body );

	var MethodView = function( options ) {
	// Required options
		this.id = options.id.toString();

		// Containers
		this.container = {
			numbers: $( options.numbersContainer ).empty(),
			grid: $( options.gridContainer ).empty()
		};

		// Method details
		this.method = {
			stage: parseInt( options.stage, 10 ),
			notation: {
				text: options.notation,
				exploded: PlaceNotation.explode( options.notation )
			}
		};
		var rounds = PlaceNotation.rounds( this.method.stage );
		this.method.notation.parsed = PlaceNotation.parse( options.notation, this.method.stage );
		this.method.huntBells = PlaceNotation.huntBells( this.method.notation.parsed, this.method.stage );
		this.method.leadHead = PlaceNotation.apply( this.method.notation.parsed, rounds );
		this.method.leadHeads = [rounds, this.method.leadHead];
		for( var i = 1; !PlaceNotation.rowsEqual( this.method.leadHeads[i], rounds ); ++i ) {
			this.method.leadHeads.push( PlaceNotation.apply( this.method.leadHead, this.method.leadHeads[i] ) );
		}
		this.method.leadHeads.pop();
		this.method.numberOfLeads = this.method.leadHeads.length;
		this.method.workGroups = PlaceNotation.cycles( this.method.leadHead );

		// Rule offs
		if( typeof options.ruleOffs === 'undefined' ) {
			this.method.ruleOffs = { from: 0, every: this.method.notation.exploded.length };
		}
		else if( typeof options.ruleOffs === 'object' ) {
			this.method.ruleOffs = options.ruleOffs;
		}
		else {
			this.method.ruleOffs = { from: 0, every: 0 };
		}

		// Calling positions
		if( typeof options.callingPositions === 'object' ) {
			this.method.callingPositions = options.callingPositions;
		}
		else {
			this.method.callingPositions = false;
		}

		// Set up reusable options objects
		this.options = {};

		// Plain course
		this.options.plainCourse = {
			notation: $.extend( true, {}, this.method.notation ),
			stage: this.method.stage,
			ruleOffs: $.extend( {}, this.method.ruleOffs )
		};

		// Calls
		this.options.calls = [];
		if( typeof options.calls === 'object' ) {
			for( var callTitle in options.calls ) {
				if( Object.prototype.hasOwnProperty.call( options.calls, callTitle ) ) {
					var call = options.calls[callTitle];

					// If call.from is negative, add to it so we use the second calling position (this stops us from having to mess around with adding notation to the start (Erin))
					if( call.from < 0 ) { call.from += call.every; }

					// Create a block of notation big enough to play with
					var notationExploded = this.method.notation.exploded,
						callNotationExploded = PlaceNotation.explode( call.notation );
					while( notationExploded.length < (2*call.every)+call.from ) { notationExploded = notationExploded.concat( notationExploded ); }

					// Insert the call's notation
					for( var i = 0; i < callNotationExploded.length; ++i ) { notationExploded[(i + call.from + call.every) - 1] = callNotationExploded[i]; }

					// Calculte a good amount of padding to display on either side of the call's notation
					var padding = Math.max( 1, Math.floor(this.method.notation.exploded.length/4) ),
						start = Math.max( 0, (call.from+call.every-1)-padding ), end = Math.min( notationExploded.length, (call.from+call.every+callNotationExploded.length-1)+padding );

					// Parse notation
					var notationParsed = PlaceNotation.parse( PlaceNotation.implode( notationExploded ), this.method.stage );

					// Slice out the notation we want
					call.notation = {
						text: PlaceNotation.implode( notationExploded.slice( start, end ) ),
						exploded: notationExploded.slice( start, end ),
						parsed: notationParsed.slice( start, end )
					};

					// Calculate what the start row of the part we chopped out is (used to match up colours with the plain lead, and to display meaningful numbers relative to the plain course)
					call.startRow = (start === 0)? PlaceNotation.rounds( this.method.stage ) : PlaceNotation.apply( notationParsed.slice( 0, start ), PlaceNotation.rounds( this.method.stage ) );

					// Adjust rule offs to compensate for the fact we just sliced off some of the start of the method
					call.ruleOffs = $.extend( {}, this.method.ruleOffs );
					call.ruleOffs.from -= start;

					// Calculate which bells are affected by the call
					var plainLeadNotation = this.method.notation.parsed;
					for( var i = 1; i*this.method.notation.parsed.length < end; ++i ) { plainLeadNotation = plainLeadNotation.concat( this.method.notation.parsed ); }
					var plainLeadRow = PlaceNotation.apply( plainLeadNotation.slice( 0, end ), PlaceNotation.rounds( this.method.stage ) ),
						callLeadRow = PlaceNotation.apply( notationParsed.slice( 0, end ), PlaceNotation.rounds( this.method.stage ) ),
						affectedBells = [];
					plainLeadRow.forEach( function( b, i ) { if( b !== callLeadRow[i] ) { affectedBells.push( b ); } } );

					// Create an options object for the call
					this.options.calls.push( {
						id: callTitle.replace( ' ', '_' ).replace( /[^A-Za-z0-9_]/, '' ).toLowerCase(),
						notation: call.notation,
						stage: this.method.stage,
						startRow: call.startRow,
						title: {
							text: callTitle+':'
						},
						ruleOffs: call.ruleOffs,
						affected: affectedBells
					} );
				}
			}
		}

		this.drawNumbers();
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
				columnPadding = 15,
				rowHeight = 14,
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
						rowHeight: rowHeight,
						rowWidth: rowWidth,
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
			this.options.calls.forEach( function( call, k ) {
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
					numberOfCalls = view.options.calls.length,
					maxWidth = view.container.numbers.width(),
					callWidth = 15 + rowWidth,
					placeStartWidth = (10 + plainPlaceStarts.length*12);
				return function() {
					var leadsPerColumn = 1;
					maxWidth = $('#content').width() - 30;
					// Check that the window isn't plenty big enough before bothering to look at adjusting
					if( maxWidth <= 2*callWidth + 5 + (rowWidth + placeStartWidth + columnPadding)*numberOfLeads ) {
						for( leadsPerColumn = 1; leadsPerColumn < numberOfLeads; ++leadsPerColumn ) {
							if( maxWidth > ((leadsPerColumn>1)?callWidth:numberOfCalls*callWidth) + Math.ceil( numberOfLeads/leadsPerColumn )*(columnPadding + rowWidth + placeStartWidth ) ) {
								break;
							}
						}
					}
					return leadsPerColumn;
				};
			})( this );
			leadsPerColumn = determineLeadsPerColumn();

			// Options object for the plain course
			var plainCourseOptions = $.extend( true, {}, this.options.plainCourse, sharedOptions, {
				id: 'numbers'+this.id+'_plain',
				callingPositions: (this.method.callingPositions === false)? false: $.extend( { show: true }, this.method.callingPositions ),
				dimensions: {
					columnPadding: columnPadding
				},
				layout: {
					numberOfLeads: this.method.numberOfLeads,
					leadsPerColumn: leadsPerColumn
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
			plainCourseContainer.append( new MethodGrid( plainCourseOptions ) );

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
					plainCourseOptions.layout.leadsPerColumn = newLeadsPerColumn;
					plainCourseContainer.prepend( new MethodGrid( plainCourseOptions ) );
					leadsPerColumn = newLeadsPerColumn;
				}
			} );
			
			// Create images for the calls. These will not be redrawn when resizing the window, so don't store the options for later use
			this.options.calls.forEach( function( call, i ) {
				this.container.numbers.append( new MethodGrid( $.extend( true, {}, call, sharedOptions, {
					id: 'numbers'+this.id+'_'+call.id,
					numberOfLeads: 1,
					lines: { show: true, bells: callLines[i] },
					numbers: { show: true, bells: callLines[i].map( function( l ) { return { color: (l.stroke !== 'transparent')? 'transparent' : '#000' }; } ) }
				} ) ) );
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
					dimensions: ($window.width() > 600)? { rowHeight: 14, bellWidth: 12 } : { rowHeight: 11, bellWidth: 9 },
					sideNotation: { show: true },
					numbers: false,
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
			this.container.grid.append( new MethodGrid( $.extend( true, {}, this.options.plainCourse, sharedOptions, {
				id: 'grid'+this.id+'_plain',
				title: {
					text: 'Plain Lead:'
				}
			} ) ) );
			// Calls
			for( i = 0; i < this.options.calls.length; i++ ) {
				this.container.grid.append( new MethodGrid( $.extend( true, {}, this.options.calls[i], sharedOptions, {
					id: 'grid'+this.id+'_'+this.options.calls[i].id
				} ) ) );
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
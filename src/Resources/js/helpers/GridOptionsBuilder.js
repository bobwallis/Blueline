define( ['jquery', './PlaceNotation', './MeasureCanvasText'], function( $, PlaceNotation, MeasureCanvasText ) {

	// Helps generate options for Grid.js to display full plain courses and calls for a particular method

	var GridOptionsBuilder = function( options ) {
		var i, j, k, l;

		// Calculate various attributes of the method
		this.stage = parseInt( options.stage, 10 );
		var rounds = PlaceNotation.rounds( this.stage ),
			notation = PlaceNotation.expand( options.notation, this.stage );
		this.notation = {
			text: notation,
			exploded: PlaceNotation.explode( notation ),
			parsed: PlaceNotation.parse( notation, this.stage )
		};
		this.ruleOffs = (typeof options.ruleOffs == 'object')? options.ruleOffs : { from: 0, every: this.notation.exploded.length };
		if( typeof this.ruleOffs.show == 'undefined' ) { this.ruleOffs.show = true; }
		if( typeof options.callingPositions === 'object' ) {
			this.callingPositions = options.callingPositions;
			if( typeof this.callingPositions.show == 'undefined' ) { this.callingPositions.show = true; }
			if( typeof this.callingPositions.font == 'undefined' ) { this.callingPositions.font = (fontSize*0.8)+'"Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif'; }
			if( typeof options.workingBell == 'string' && options.workingBell == 'lightest' ) { this.callingPositions.show = false; }
		}
		else {
			this.callingPositions = false;
		}
		this.huntBells = PlaceNotation.huntBells( this.notation.parsed, this.stage );
		this.leadHead = PlaceNotation.apply( this.notation.parsed, rounds );
		this.leadHeads = [rounds, this.leadHead];

		for( i = 1; !PlaceNotation.rowsEqual( this.leadHeads[i], rounds ); ++i ) {
			this.leadHeads.push( PlaceNotation.apply( this.leadHead, this.leadHeads[i] ) );
		}
		this.leadHeads.pop();

		this.numberOfLeads = this.leadHeads.length;
		this.workGroups = PlaceNotation.cycles( this.leadHead );

		// For the plain course image, we'll draw a line through the heaviest working bell of each type and the hunt bells
		var toFollow = this.workGroups.map( function( g ) {
			if( typeof options.workingBell == 'string' && options.workingBell == 'lightest' ) {
				return Math.min.apply( Math, g );
			}
			else {
				return Math.max.apply( Math, g );
			}
		} );
		var placeStarts = toFollow.filter( function( b ) { return this.huntBells.indexOf( b ) === -1; }, this );

		// Calculate some sizing to help with creating default grid options objects
		var fontSize = (typeof options.fontSize == 'number')? options.fontSize : 12,
			fontFace = ((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'Blueline, "Andale Mono", Consolas, ')+'monospace',
			font = fontSize+'px '+fontFace,
			columnPadding = fontSize,
			rowHeight = Math.floor( fontSize*(fontSize < 15? 1.1 : 1.05) ),
			rowWidth = Math.floor( (this.stage < 9? 1.4 : 1.2)*MeasureCanvasText( Array( this.stage + 1 ).join( '0' ), font ) );

		// Default line colors and widths
		var workingBellColor = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
			huntBellColor = '#D11',
			workingBellWidth = fontSize/6,
			huntBellWidth = workingBellWidth*0.6;

		// Plain course
		var sharedPlainCourseGridOptions = {
			id: 'plainCourse_'+options.id,
			notation: this.notation,
			stage: this.stage,
			ruleOffs: this.ruleOffs,
			callingPositions: this.callingPositions,
			dimensions: {
				row: {
					height: rowHeight,
					width: rowWidth,
					columnPadding: columnPadding
				}
			},
			layout: {
				numberOfLeads: this.numberOfLeads,
				numberOfColumns: this.numberOfLeads
			},
			lines: {
				show: true,
				bells: []
			},
			placeStarts: {
				show: true,
				bells: placeStarts,
				diameter: fontSize*1.083,
				width: fontSize/10
			},
			sideNotation: {
				font: (fontSize*0.8333)+'px "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif',
				show: true
			}
		};

		// Calls
		var sharedCallsGridOptions = [];
		if( typeof options.calls === 'object' ) {
			for( var callTitle in options.calls ) {
				if( Object.prototype.hasOwnProperty.call( options.calls, callTitle ) ) {
					var call = options.calls[callTitle];

					// If call.from is negative, add to it so we use the second calling position (this stops us from having to mess around with adding notation to the start (Erin))
					if( call.from < 0 ) { call.from += call.every; }

					// Create a block of notation big enough to play with
					var notationExploded = PlaceNotation.explode( options.notation ),
						callNotationExploded = PlaceNotation.explode( call.notation );
					while( notationExploded.length < (2*call.every)+call.from+call.cover ) { notationExploded = notationExploded.concat( notationExploded ); }

					// Insert the call's notation
					Array.prototype.splice.apply( notationExploded, [call.from+call.every-1, call.cover].concat( callNotationExploded ) );

					// Calculate a good amount of padding to display on either side of the call's notation
					var padding = Math.max( 2, Math.floor((this.notation.exploded.length-7)/4) ),
						start = Math.max( 0, (call.from+call.every-1)-padding ),
						end = Math.min( notationExploded.length, (call.from+call.every+callNotationExploded.length-1)+padding );

					// Parse notation
					var notationParsed = PlaceNotation.parse( PlaceNotation.implode( notationExploded ), this.stage );

					// Slice out the notation we want
					call.notation = {
						text: PlaceNotation.implode( notationExploded.slice( start, end ) ),
						exploded: notationExploded.slice( start, end ),
						parsed: notationParsed.slice( start, end )
					};

					// Calculate what the start row of the part we chopped out is (used to match up colours with the plain lead, and to display meaningful numbers relative to the plain course)
					call.startRow = (start === 0)? PlaceNotation.rounds( this.stage ) : PlaceNotation.apply( notationParsed.slice( 0, start ), PlaceNotation.rounds( this.stage ) );

					// Adjust rule offs to compensate for the fact we just sliced off some of the start of the method
					// TODO: adjust rule-offs when the call adjusts the lead length
					call.ruleOffs = $.extend( {}, this.ruleOffs );
					call.ruleOffs.from -= start;

					// Calculate which bells are affected by the call
					var plainLeadNotation = this.notation.parsed;
					for( i = 1; i*this.notation.parsed.length < end; ++i ) { plainLeadNotation = plainLeadNotation.concat( this.notation.parsed ); }
					var plainLeadRow = PlaceNotation.apply( plainLeadNotation.slice( 0, end ), PlaceNotation.rounds( this.stage ) ),
						callLeadRow = PlaceNotation.apply( notationParsed.slice( 0, end ), PlaceNotation.rounds( this.stage ) ),
						affectedBells = [];
					plainLeadRow.forEach( function( b, i ) { if( b !== callLeadRow[i] ) { affectedBells.push( b ); } } );

					// Create an options object for the call
					sharedCallsGridOptions.push( {
						id: callTitle.replace( ' ', '_' ).replace( /[^A-Za-z0-9_]/, '' ).toLowerCase()+'_'+options.id,
						notation: call.notation,
						stage: this.stage,
						startRow: call.startRow,
						title: {
							text: callTitle+':'
						},
						ruleOffs: call.ruleOffs,
						placeStarts: false,
						callingPositions: false,
						affected: affectedBells,
						dimensions: {
							row: {
								height: rowHeight,
								width: rowWidth
							}
						},
						layout: {
							numberOfLeads: 1,
							numberOfColumns: 1
						},
						lines: {
							show: true,
							bells: []
						},
						sideNotation: {
							font: (fontSize*0.8333)+'px sans-serif',
							show: true
						}
					} );
				}
			}
		}

		// Create an options object for each line style on-demand
		this.gridOptions = { plainCourse: {}, calls: {} };
		var that = this;

		// 'Numbers'
		var thisgridOptionsplainCoursenumbers;
		this.gridOptions.plainCourse.numbers = function() {
			if( typeof thisgridOptionsplainCoursenumbers === 'object' ) {
				return thisgridOptionsplainCoursenumbers;
			}
			// Create initial object
			thisgridOptionsplainCoursenumbers = $.extend( true, {}, sharedPlainCourseGridOptions, {
				id: sharedPlainCourseGridOptions.id+'_numbers',
				numbers: {
					show: true,
					font: font,
					bells: rounds.map( function( b ) {
						return { color: (toFollow.indexOf( b ) !== -1)? 'transparent' : '#000' };
					} )
				}
			} );
			// Set the colors and stroke widths of the lines in the plain course
			var isHuntBell, isWorkingBell, isAffected;
			for( var i = 0, j = 0; i < that.stage; ++i ) {
				isHuntBell = that.huntBells.indexOf( i ) !== -1;
				isWorkingBell = toFollow.indexOf( i ) !== -1;
				thisgridOptionsplainCoursenumbers.lines.bells.push( {
					width: isHuntBell? huntBellWidth : workingBellWidth,
					stroke: isHuntBell? huntBellColor : (isWorkingBell? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
				} );
			}
			return thisgridOptionsplainCoursenumbers;
		};
		var thisgridOptionscallsnumbers;
		this.gridOptions.calls.numbers = function() {
			if( typeof thisgridOptionscallsnumbers === 'object' ) {
				return thisgridOptionscallsnumbers;
			}
			// Create initial object
			thisgridOptionscallsnumbers = $.extend( true, [], sharedCallsGridOptions );
			// Set the colors and stroke widths of the lines in the calls
			sharedCallsGridOptions.forEach( function( call, callIndex ) {
				var isHuntBell, isWorkingBell, isAffected;
				// Set IDs and other options
				thisgridOptionscallsnumbers[callIndex].id += '_numbers';
				thisgridOptionscallsnumbers[callIndex].numbers = { show: true, font: font, bells: rounds.map( function( b ) { return { color: (thisgridOptionscallsnumbers[callIndex].affected.indexOf( b ) !== -1 || this.huntBells.indexOf( b ) !== -1)? 'transparent' : '#000' }; }, that ) };
				// Set line colors
				for( i = 0, j = 0; i < that.stage; ++i ) {
					isHuntBell = that.huntBells.indexOf( i ) !== -1;
					isAffected = call.affected.indexOf( i ) !== -1;
					thisgridOptionscallsnumbers[callIndex].lines.bells.push( {
						width: isHuntBell? huntBellWidth : workingBellWidth,
						stroke: isHuntBell? huntBellColor : (isAffected? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
					} );
				}
			} );
			return thisgridOptionscallsnumbers
		};

		// 'Diagrams'
		var thisgridOptionsplainCoursediagrams;
		this.gridOptions.plainCourse.diagrams = function() {
			if( typeof thisgridOptionsplainCoursediagrams === 'object' ) {
				return thisgridOptionsplainCoursediagrams;
			}
			// Create initial object
			thisgridOptionsplainCoursediagrams = $.extend( true, {}, sharedPlainCourseGridOptions, {
				id: sharedPlainCourseGridOptions.id+'_numbers',
				numbers: {
					show: true,
					font: (fontSize*0.8)+'px '+fontFace,
					bells: rounds.map( function( b ) { return { color: '#002856' }; } )
				},
				sideNotation: false,
				callingPositions: false,
				placeStarts: {
					showSmallCircle: false,
					color: '#002856',
					width: fontSize/15
				},
				ruleOffs: {
					stroke: '#002856',
					width: fontSize/15,
					cap: 'round',
					dash: null
				}
			} );
			// Set the colors and stroke widths of the lines in the plain course
			var isHuntBell, isWorkingBell, isAffected;
			for( var i = 0, j = 0; i < that.stage; ++i ) {
				isHuntBell = that.huntBells.indexOf( i ) !== -1;
				isWorkingBell = toFollow.indexOf( i ) !== -1;
				thisgridOptionsplainCoursediagrams.lines.bells.push( {
					width: fontSize/15,
					stroke: isHuntBell? huntBellColor : (isWorkingBell? '#002856' : 'transparent')
				} );
			}
			return thisgridOptionsplainCoursediagrams;
		};
		var thisgridOptionscallsdiagrams;
		this.gridOptions.calls.diagrams = function() {
			if( typeof thisgridOptionscallsdiagrams === 'object' ) {
				return thisgridOptionscallsdiagrams;
			}
			// Create initial object
			thisgridOptionscallsdiagrams = $.extend( true, [], sharedCallsGridOptions );
			// Set the colors and stroke widths of the lines in the calls
			sharedCallsGridOptions.forEach( function( call, callIndex ) {
				var isHuntBell, isWorkingBell, isAffected;
				// Set IDs and other options
				thisgridOptionscallsdiagrams[callIndex].id += '_diagrams';
				thisgridOptionscallsdiagrams[callIndex].sideNotation = false;
				thisgridOptionscallsdiagrams[callIndex].ruleOffs.stroke = '#002856';
				thisgridOptionscallsdiagrams[callIndex].ruleOffs.width = fontSize/20;
				thisgridOptionscallsdiagrams[callIndex].ruleOffs.dash = [0,0];
				thisgridOptionscallsdiagrams[callIndex].numbers = { show: true, font: (fontSize*0.8)+'px '+fontFace, bells: rounds.map( function( b ) { return { color: '#002856' }; } ) };
				// Set line colors
				for( i = 0, j = 0; i < that.stage; ++i ) {
					isHuntBell = that.huntBells.indexOf( i ) !== -1;
					isAffected = call.affected.indexOf( i ) !== -1;
					thisgridOptionscallsdiagrams[callIndex].lines.bells.push( {
						width: fontSize/15,
						stroke: isHuntBell? huntBellColor : (isAffected? '#002856' : 'transparent')
					} );
				}
			} );
			return thisgridOptionscallsdiagrams
		};

		// 'Lines'
		var thisgridOptionsplainCourselines;
		this.gridOptions.plainCourse.lines = function() {
			if( typeof thisgridOptionsplainCourselines === 'object' ) {
				return thisgridOptionsplainCourselines;
			}
			// Create initial object
			thisgridOptionsplainCourselines = $.extend( true, {}, sharedPlainCourseGridOptions, {
				id: sharedPlainCourseGridOptions.id+'_lines',
				numbers: false,
				verticalGuides: {
					shading: {
						show: true
					}
				}
			} );
			// Set the colors and stroke widths of the lines in the plain course
			var isHuntBell, isWorkingBell, isAffected;
			for( i = 0, j = 0; i < that.stage; ++i ) {
				isHuntBell = that.huntBells.indexOf( i ) !== -1;
				isWorkingBell = toFollow.indexOf( i ) !== -1;
				thisgridOptionsplainCourselines.lines.bells.push( {
					width: (isHuntBell || !isWorkingBell)? huntBellWidth : workingBellWidth,
					stroke: isHuntBell? huntBellColor : (isWorkingBell? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'rgba(0,0,0,0.1)')
				} );
			}
			return thisgridOptionsplainCourselines;
		};
		var thisgridOptionscallslines;
		this.gridOptions.calls.lines = function() {
			if( typeof thisgridOptionscallslines === 'object' ) {
				return thisgridOptionscallslines;
			}
			// Create initial object
			thisgridOptionscallslines = $.extend( true, [], sharedCallsGridOptions );
			// Set the colors and stroke widths of the lines in the calls
			sharedCallsGridOptions.forEach( function( call, callIndex ) {
				var isHuntBell, isWorkingBell, isAffected;
				// Set IDs and other options
				thisgridOptionscallslines[callIndex].id += '_lines';
				thisgridOptionscallslines[callIndex].numbers = false;
				thisgridOptionscallslines[callIndex].verticalGuides = { shading: { show: true } };
				// Set line colors
				for( i = 0, j = 0; i < that.stage; ++i ) {
					isHuntBell = that.huntBells.indexOf( i ) !== -1;
					isAffected = call.affected.indexOf( i ) !== -1;
					thisgridOptionscallslines[callIndex].lines.bells.push( {
						width: isAffected? workingBellWidth : huntBellWidth,
						stroke: isHuntBell? huntBellColor : (isAffected? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'rgba(0,0,0,0.1)')
					} );
				}
			} );
			return thisgridOptionscallslines
		};

		// Grid
		var thisgridOptionsplainCoursegrid;
		this.gridOptions.plainCourse.grid = function() {
			if( typeof thisgridOptionsplainCoursegrid === 'object' ) {
				return thisgridOptionsplainCoursegrid;
			}
			// Create initial object
			thisgridOptionsplainCoursegrid = $.extend( true, {}, sharedPlainCourseGridOptions, {
				id: sharedPlainCourseGridOptions.id+'_grid',
				title: false,
				numberOfLeads: false,
				numbers: false,
				placeStarts: false,
				callingPositions: false,
				layout: {
					numberOfLeads: 1,
					numberOfColumns: 1
				}
			} );
			// Set the colors and stroke widths of the lines in the plain course
			var isHuntBell, isWorkingBell, isAffected;
			for( i = 0, j = 0; i < that.stage; ++i ) {
				isHuntBell = that.huntBells.indexOf( i ) !== -1;
				isWorkingBell = toFollow.indexOf( i ) !== -1;
				thisgridOptionsplainCoursegrid.lines.bells.push( {
					width: isHuntBell? huntBellWidth : workingBellWidth,
					stroke: isHuntBell? huntBellColor : workingBellColor[j++] || workingBellColor[j = 0, j++]
				} );
			}
			return thisgridOptionsplainCoursegrid;
		};
		var thisgridOptionscallsgrid;
		this.gridOptions.calls.grid = function() {
			if( typeof thisgridOptionscallsgrid === 'object' ) {
				return thisgridOptionscallsgrid;
			}
			// Create initial object
			thisgridOptionscallsgrid = $.extend( true, [], sharedCallsGridOptions );
			// Set the colors and stroke widths of the lines in the calls
			sharedCallsGridOptions.forEach( function( call, callIndex ) {
				var isHuntBell, isWorkingBell, isAffected;
				// Set IDs and other options
				thisgridOptionscallsgrid[callIndex].id += '_grid';
				thisgridOptionscallsgrid[callIndex].numbers = false;
				// Set line colors
				for( i = 0, j = 0; i < that.stage; ++i ) {
					isHuntBell = that.huntBells.indexOf( i ) !== -1;
					isAffected = call.affected.indexOf( i ) !== -1;
					thisgridOptionscallsgrid[callIndex].lines.bells.push( {
						width: isHuntBell? huntBellWidth : workingBellWidth,
						stroke: isHuntBell? huntBellColor : workingBellColor[j++] || workingBellColor[j = 0, j++]
					} );
				}
			} );
			return thisgridOptionscallsgrid
		};

		return this;
	};
	return GridOptionsBuilder;
} );

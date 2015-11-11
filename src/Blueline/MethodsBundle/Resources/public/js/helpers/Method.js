define( ['jquery', 'shared/lib/webfont!Blueline', './PlaceNotation', '../../shared/helpers/MeasureCanvasText'], function( $, webFontLoaded, PlaceNotation, MeasureCanvasText ) {

	// Helps generate options for Grid.js to display full plain courses and calls for a particular method

	var Method = function( options ) {
		var i, j, k, l;

		// Calculate various attributes of the method
		this.stage = parseInt( options.stage, 10 );
		var rounds = PlaceNotation.rounds( this.stage );
		this.notation = {
			text: options.notation,
			exploded: PlaceNotation.explode( options.notation ),
			parsed: PlaceNotation.parse( options.notation, this.stage )
		};
		this.ruleOffs = (typeof options.ruleOffs == 'object')? options.ruleOffs : { from: 0, every: this.notation.exploded.length };
		this.callingPositions = (typeof options.callingPositions === 'object')? options.callingPositions : false;
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
		var toFollow = this.workGroups.map( function( g ) { return Math.max.apply( Math, g ); } ),
			placeStarts = toFollow.filter( function( b ) { return this.huntBells.indexOf( b ) === -1; }, this );

		// Calculate some sizing to help with creating default grid options objects
		var fontSize = 12;
			columnPadding = fontSize,
			rowHeight = Math.floor( fontSize*1.1 ),
			rowWidth = Math.floor( 1.2*MeasureCanvasText( Array( this.stage + 1 ).join( '0' ), fontSize+'px '+((navigator.userAgent.toLowerCase().indexOf('android') > -1)? '' : 'Blueline, "Andale Mono", Consolas, ')+'monospace' ) );

		// Default line colors and widths
		var workingBellColor = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
			huntBellColor = '#D11',
			workingBellWidth = fontSize/6,
			huntBellWidth = workingBellWidth*0.6;


		// Set up options objects
		this.gridOptions = { plainCourse: {}, calls: {} };

		// Plain course
		var sharedPlainCourseGridOptions = {
			id: 'plainCourse_'+options.id,
			notation: this.notation,
			stage: this.stage,
			ruleOffs: { every: this.ruleOffs.every, from: this.ruleOffs.from },
			callingPositions: (this.callingPositions === false)? false: $.extend( { show: true }, this.callingPositions ),
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
				bells: placeStarts
			},
			sideNotation: {
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
					while( notationExploded.length < (2*call.every)+call.from ) { notationExploded = notationExploded.concat( notationExploded ); }

					// Insert the call's notation
					for( i = 0; i < callNotationExploded.length; ++i ) {
						notationExploded[(i + call.from + call.every) - 1] = callNotationExploded[i];
					}

					// Calculte a good amount of padding to display on either side of the call's notation
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
					call.ruleOffs = { every: this.ruleOffs.every, from: this.ruleOffs.from };
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
							show: true
						}
					} );
				}
			}
		}


		// Create seperate objects for the numbers, line and grid styles
		this.gridOptions.plainCourse.numbers = $.extend( true, {}, sharedPlainCourseGridOptions, {
			id: sharedPlainCourseGridOptions.id+'_numbers',
			numbers: { show: true, bells: rounds.map( function( b ) { return { color: (toFollow.indexOf( b ) !== -1)? 'transparent' : '#000' }; }, this ) }
		} );
		this.gridOptions.calls.numbers = $.extend( true, [], sharedCallsGridOptions );

		this.gridOptions.plainCourse.lines = $.extend( true, {}, sharedPlainCourseGridOptions, {
			id: sharedPlainCourseGridOptions.id+'_lines',
			numbers: false,
			verticalGuides: {
				shading: {
					show: true
				}
			}
		} );
		this.gridOptions.calls.lines = $.extend( true, [], sharedCallsGridOptions );

		this.gridOptions.plainCourse.grid = $.extend( true, {}, sharedPlainCourseGridOptions, {
			id: sharedPlainCourseGridOptions.id+'_grid',
			title: false,
			numberOfLeadsmbers: false,
			numbers: false,
			placeStarts: false,
			callingPositions: false,
			layout: {
				numberOfLeads: 1,
				numberOfColumns: 1
			}
		} );
		this.gridOptions.calls.grid = $.extend( true, [], sharedCallsGridOptions );


		// Set the colors and stroke widths of the lines in plain courses
		var isHuntBell, isWorkingBell, isAffected;
		for( i = 0, j = 0, k = 0, l = 0; i < this.stage; ++i ) {
			isHuntBell = this.huntBells.indexOf( i ) !== -1;
			isWorkingBell = toFollow.indexOf( i ) !== -1;
			this.gridOptions.plainCourse.numbers.lines.bells.push( {
				width: isHuntBell? huntBellWidth : workingBellWidth,
				stroke: isHuntBell? huntBellColor : (isWorkingBell? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
			} );
			this.gridOptions.plainCourse.lines.lines.bells.push( {
				width: (isHuntBell || !isWorkingBell)? huntBellWidth : workingBellWidth,
				stroke: isHuntBell? huntBellColor : (isWorkingBell? workingBellColor[k++] || workingBellColor[k = 0, k++] : 'rgba(0,0,0,0.1)')
			} );
			this.gridOptions.plainCourse.grid.lines.bells.push( {
				width: isHuntBell? huntBellWidth : workingBellWidth,
				stroke: isHuntBell? huntBellColor : workingBellColor[l++] || workingBellColor[l = 0, l++]
			} );
		}

		// and for calls
		sharedCallsGridOptions.forEach( function( call, callIndex ) {
			// Set IDs and other options
			this.gridOptions.calls.numbers[callIndex].id += '_numbers';
			this.gridOptions.calls.lines[callIndex].id += '_lines';
			this.gridOptions.calls.grid[callIndex].id += '_grid';
			this.gridOptions.calls.numbers[callIndex].numbers = { show: true, bells: rounds.map( function( b ) { return { color: (this.gridOptions.calls.numbers[callIndex].affected.indexOf( b ) !== -1 || this.huntBells.indexOf( b ) !== -1)? 'transparent' : '#000' }; }, this ) };
			this.gridOptions.calls.lines[callIndex].numbers = this.gridOptions.calls.grid[callIndex].numbers = false;
			this.gridOptions.calls.lines[callIndex].verticalGuides = { shading: { show: true } };
			this.gridOptions.calls.grid[callIndex].sideNotation = { show: true };
			// Set line colors
			for( i = 0, j = 0, k = 0, l = 0; i < this.stage; ++i ) {
				isHuntBell = this.huntBells.indexOf( i ) !== -1;
				isAffected = call.affected.indexOf( i ) !== -1;
				this.gridOptions.calls.numbers[callIndex].lines.bells.push( {
					width: isHuntBell? huntBellWidth : workingBellWidth,
					stroke: isHuntBell? huntBellColor : (isAffected? workingBellColor[j++] || workingBellColor[j = 0, j++] : 'transparent')
				} );
				this.gridOptions.calls.lines[callIndex].lines.bells.push( {
					width: isAffected? workingBellWidth : huntBellWidth,
					stroke: isHuntBell? huntBellColor : (isAffected? workingBellColor[k++] || workingBellColor[k = 0, k++] : 'rgba(0,0,0,0.1)')
				} );
				this.gridOptions.calls.grid[callIndex].lines.bells.push( {
					width: isHuntBell? huntBellWidth : workingBellWidth,
					stroke: isHuntBell? huntBellColor : workingBellColor[l++] || workingBellColor[l = 0, l++]
				} );
			}
		}, this );

		return this;
	};
	return Method;
} );
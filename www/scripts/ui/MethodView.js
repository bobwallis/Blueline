/*global require: false, define: false, google: false */
define( ['./MethodGrid', '../helpers/PlaceNotation'], function( MethodGrid, PlaceNotation ) {
	// Reusable
	var $window = $( window ),
		$body = $( document.body );

	var MethodView = function( options ) {
	// Required options
		this.id = options.id.toString();
		
		// Containers
		this.container = {
			numbers: $( options.numbersContainer ),
			grid: $( options.gridContainer )
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
		else if( typeof options.ruleOffs === 'string' ) {
			var parsed = options.ruleOffs.match( /^([^:]*):([^:]*)$/ );
			this.method.ruleOffs = { every: parseInt( parsed[1], 10 ), from: parseInt( parsed[2], 10 ) };
		}
		else {
			this.method.ruleOffs = { from: 0, every: 0 };
		}
		
		// Set up reusable options objects
		this.options = {}
		
		// Plain course
		this.options.plainCourse = {
			notation: $.extend( true, {}, this.method.notation ),
			stage: this.method.stage,
			ruleOffs: $.extend( {}, this.method.ruleOffs )
		};
		
		// Calls
		if( typeof options.calls === 'object' ) {
			this.options.calls = [];
			for( var callTitle in options.calls ) {
				if( Object.prototype.hasOwnProperty.call( options.calls, callTitle ) ) {
					var positionsParse = options.calls[callTitle].match( /^([^:]*):([^:]*):([^:]*)$/ ),
						call = {
							notation: positionsParse[1],
							every: (positionsParse[2] === '')? this.method.notation.parsed.length : parseInt( positionsParse[2], 10 ),
							from: (positionsParse[3] === '')? 0 : parseInt( positionsParse[3], 10 )
						};
						
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
			
					// Slice out the notation
					call.notation = {
						text: PlaceNotation.implode( notationExploded.slice( start, end ) ),
						exploded: notationExploded.slice( start, end ),
						parsed: notationParsed.slice( start, end )
					};
			
					// Calculate what the start row of the part we chopped out is (used to match up colours with the plain lead, and to display meaningful numbers relative to the plain course)
					call.startRow = (start === 0)? PlaceNotation.rounds( this.method.stage ) : PlaceNotation.apply( notationParsed.slice( 0, start ), PlaceNotation.rounds( this.method.stage ) );
			
					// Adjust rule offs
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
						title: callTitle,
						notation: call.notation,
						stage: this.method.stage,
						ruleOffs: call.ruleOffs,
						startRow: call.startRow,
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
			// Choose colours to use for the lines
			var colours = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
				toFollow = this.method.workGroups.map( function( g ) { return Math.max.apply( Math, g ); } ),
				plainLines = [];
			for( var i = 0, j = 0; i < this.method.stage; ++i ) {
				plainLines.push( {
					'stroke-linejoin': 'round',
					'stroke-linecap': 'round',
					'stroke-width': (this.method.huntBells.indexOf( i ) !== -1)? 1.2 : 2,
					fill: 'none',
					stroke: (this.method.huntBells.indexOf( i ) !== -1)? '#D11' : ((toFollow.indexOf( i ) !== -1)? colours[j++] || colours[j = 0, j++] : 'transparent')
				} );
			}
			var callLines = [];
			this.options.calls.forEach( function( call, k ) {
				callLines[k] = [];
				for( var i = 0, j = 0; i < this.method.stage; ++i ) {
					callLines[k].push( {
						'stroke-linejoin': 'round',
						'stroke-linecap': 'round',
						'stroke-width': (this.method.huntBells.indexOf( i ) !== -1)? 1.2 : 2,
						fill: 'none',
						stroke: (this.method.huntBells.indexOf( i ) !== -1)? '#D11' : ((call.affected.indexOf( i ) !== -1)? colours[j++] || colours[j = 0, j++] : 'transparent')
					} );
				}
			}, this );
			
			// Decide which bells get place starts drawn
			var plainPlaceStarts = plainLines.map( function( l, i ) { return (l.stroke !== 'transparent' && this.method.huntBells.indexOf( i ) === -1)? i : -1; }, this ).filter( function( l ) { return l !== -1; } );
			
			// Determine the correct bell width
			var testText = $( '<span class="mono">123456</span>' );
			testText.css( { fontSize: '14px' } );
			$body.append( testText );
			var bellWidth = testText.width() / 6;
			testText.remove();
			
			// Determine the appropriate lead distribution for the plain course to ensure a fit
			var maxWidth = this.container.numbers.width(),
				rowWidth = bellWidth*this.method.stage,
				callWidth = 20 + rowWidth,
				placeStartWidth = (10 + plainPlaceStarts.length*12),
				leadsPerColumn = 1;
			// Check for case when the window is plenty big enough
			if( maxWidth > 2*callWidth + 5 + (rowWidth + placeStartWidth + 15)*this.method.numberOfLeads ) {
				leadsPerColumn = 1;
			}
			else {
				for( leadsPerColumn = 1; leadsPerColumn < this.method.numberOfLeads; ++leadsPerColumn ) {
					if( maxWidth > ((leadsPerColumn>1)?callWidth:2*callWidth) + 5 + Math.ceil( this.method.numberOfLeads/leadsPerColumn )*(15 + rowWidth + placeStartWidth ) ) {
						break;
					}
				}
			}
			
			// Plain course
			this.container.numbers.append( new MethodGrid( $.extend( true, {}, this.options.plainCourse, {
				id: 'numbers'+this.id+'_plain',
				show: {
					notation: false,
					numbers: true,
					lines: true
				},
				display: {
					numberOfLeads: this.method.numberOfLeads,
					leadsPerColumn: leadsPerColumn,
					dimensions: { rowHeight: 14, bellWidth: bellWidth, columnPadding: 15 },
					lines: plainLines,
					numbers: plainLines.map( function( l ) { return (l.stroke !== 'transparent')? 'transparent' : false; } ),
					placeStarts: plainPlaceStarts
				}
			} ) ) );
			// Calls
			this.options.calls.forEach( function( call, i ) {
				this.container.numbers.append( new MethodGrid( $.extend( true, {}, call, {
					id: 'numbers'+this.id+'_'+call.id,
					show: {
						notation: false,
						numbers: true,
						lines: true
					},
					display: {
						numberOfLeads: 1,
						dimensions: { rowHeight: 14, bellWidth: bellWidth },
						lines: callLines[i],
						numbers: callLines[i].map( function( l ) { return (l.stroke !== 'transparent')? 'transparent' : false; } )
					}
				} ) ) );
			}, this );
		},
		drawGrids: function() {
			// Choose colours to use for the lines
			var colours = ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'],
				lines = [];
			for( var i = 0, j = 0; i < this.method.stage; ++i ) {
				lines.push( {
					'stroke-linejoin': 'round',
					'stroke-linecap': 'round',
					'stroke-width': (this.method.huntBells.indexOf( i ) !== -1)? 1.5 : 2,
					fill: 'none',
					stroke: (this.method.huntBells.indexOf( i ) !== -1)? '#D11' : colours[j++] || colours[j = 0, j++]
				} );
			}
			
			// Plain lead
			this.container.grid.append( new MethodGrid( $.extend( true, {}, this.options.plainCourse, {
				id: 'grid'+this.id+'_plain',
				title: 'Plain Lead',
				show: {
					notation: true,
					lines: true
				},
				display: {
					dimensions: { rowHeight: 14, bellWidth: 12 },
					lines: lines
				}
			} ) ) );
			// Calls
			this.options.calls.forEach( function( call ) {
				this.container.grid.append( new MethodGrid( $.extend( true, {}, call, {
					id: 'grid'+this.id+'_'+call.id,
					show: {
						notation: true,
						lines: true
					},
					display: {
						dimensions: { rowHeight: 14, bellWidth: 12 },
						lines: lines
					}
				} ) ) );
			}, this );
		}
	};
	
	return MethodView;
} );

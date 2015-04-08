define( ['jquery', './PlaceNotation'], function( $, PlaceNotation ) {

	// Helps generate options for Grid.js to display full plain courses and calls for a particular method

	var Method = function( options ) {

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


		for( var i = 1; !PlaceNotation.rowsEqual( this.leadHeads[i], rounds ); ++i ) {
			this.leadHeads.push( PlaceNotation.apply( this.leadHead, this.leadHeads[i] ) );
		}
		this.leadHeads.pop();
		
		this.numberOfLeads = this.leadHeads.length;
		this.workGroups = PlaceNotation.cycles( this.leadHead );

		// Set up reusable options objects
		this.gridOptions = {};

		// Plain course
		this.gridOptions.plainCourse = {
			notation: $.extend( true, {}, this.notation ),
			stage: this.stage,
			ruleOffs: $.extend( {}, this.ruleOffs )
		};

		// Calls
		this.gridOptions.calls = [];
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
					var padding = Math.max( 1, Math.floor((this.notation.exploded.length-7)/4) ),
						start = Math.max( 0, (call.from+call.every-1)-padding ), end = Math.min( notationExploded.length, (call.from+call.every+callNotationExploded.length-1)+padding );

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
					this.gridOptions.calls.push( {
						id: callTitle.replace( ' ', '_' ).replace( /[^A-Za-z0-9_]/, '' ).toLowerCase(),
						notation: call.notation,
						stage: this.stage,
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


		return this;
	};

	return Method;
} );
define( ['./PlaceNotation', 'Array.fill'], function( PlaceNotation, ArrayFill ) {

	// These functions operate on objects that look like:
	//  { row: [0,1,2,3,4,5], score: [0.1,0.1,0.1,0,0,0], namedRows: [] }
	// and adds in scoring for the requested music type on top of what is already there

	// Runs:     Sequences of consecutive bells. e.g. 1234, 7654
	// Thirds:   Sequences of musical thirds. e.g. 246, 753
	// Tittums:  Consecutive bells with a 1 place gap. e.g. _6_7_8, 1_2_3_
	// For any of these that are at the start or end of the row give the score a bonus multiplier
	// We can capture all of these with one pass of the row
	var scoreRunsThirdsTittums = function( scoredRow, stage ) {
		var score, multiplier, i = 1, iLim = scoredRow.row.length, j;

		for( ; i < iLim; ++i ) {
			// If the row ends in the tenor then multiply any score by 1.5 (roll-ups)
			// For other things at the end of the row multiply by 1.25
			// We'll check for things at the start of the row later
			multiplier = 66.66666 / stage;
			if( i+1 === iLim ) {
				if( scoredRow[i] === i ) { multiplier = 100 / stage;  }
				else                     { multiplier = 80 / stage;   }
			}

			// If we're at the end of a run
			if( (scoredRow.row[i] === scoredRow.row[i-1]+1 || scoredRow.row[i] === scoredRow.row[i-1]-1) && (i+1 === iLim || (scoredRow.row[i] !== scoredRow.row[i+1]+1 && scoredRow.row[i] !== scoredRow.row[i+1]-1)) ) {
				// Count back to the start of the run
				for( j = i-1; j > 0 && (scoredRow.row[j] === scoredRow.row[j-1]+1 || scoredRow.row[j] === scoredRow.row[j-1]-1); --j );
				// If we get to the start of the row then increase the multiplier
				if( multiplier < 80 / stage && j === 0 ) { multiplier = 80 / stage; }
				// Calculate score and push back through
				for( score = multiplier*(i-j);  j <= i; ++j ) {
					scoredRow.score[j] = Math.max( scoredRow.score[j], score );
				}
			}

			// If it's not a run then check for thirds (same logic)
			else if( (scoredRow.row[i] === scoredRow.row[i-1]+2 || scoredRow.row[i] === scoredRow.row[i-1]-2) && (i+1 === iLim || (scoredRow.row[i] !== scoredRow.row[i+1]+2 && scoredRow.row[i] !== scoredRow.row[i+1]-2)) ) {
				for( j = i-1; j > 0 && (scoredRow.row[j] === scoredRow.row[j-1]+2 || scoredRow.row[j] === scoredRow.row[j-1]-2); --j );
				if( multiplier < 80 / stage && j === 0 ) { multiplier = 80 / stage; }
				for( score = multiplier*(i-j);  j <= i; ++j ) {
					scoredRow.score[j] = Math.max( scoredRow.score[j], score );
				}
			}

			// And check for tittums (same logic)
			if( i > 1 && (scoredRow.row[i] === scoredRow.row[i-2]+1 || scoredRow.row[i] === scoredRow.row[i-2]-1) && (i+2 === iLim || (scoredRow.row[i] !== scoredRow.row[i+2]+1 && scoredRow.row[i] !== scoredRow.row[i+2]-1)) ) {
				for( j = i-2; j > 0 && (scoredRow.row[j] === scoredRow.row[j-2]+1 || scoredRow.row[j] === scoredRow.row[j-2]-1); j-=2 );
				if( multiplier < 80 / stage && j === 0 ) { multiplier = 80 / stage; }
				for( score = multiplier*(i-j)*0.9/2;  j <= i; j+=2 ) {
					scoredRow.score[j] = Math.max( scoredRow.score[j], score );
				}
			}
		}
		scoredRow.score = scoredRow.score.map( Math.round );
		return scoredRow;
	};


	// Check for named rows at any point in the row
	var namedRows = [{},{},{},{},{},
		{ 'Weasels': [0,3,1,2,4] },
		{ 'Hagdyke': [2,3,0,1,4,5] },
		{ 'Roller Coaster': [2,1,0,5,4,3,6] },
		{ 'Hagdyke': [0,1,4,5,2,3,6,7] },
		{},
		{ 'Roller Coaster': [2,1,0,5,4,3,8,7,6,9], 'Hagdyke': [2,3,0,1,6,7,4,5,8,9] },
		{},
		{ 'Hagdyke': [0,1,4,5,2,3,8,9,6,7,10,11] },
		{},
		{},
		{},
		{ 'Roller Coaster': [2,1,0,5,4,3,8,7,6,11,10,9,14,13,12,15] }
	].map( function( e, i ) {
		if( i > 1 ) {
			e['Rounds']     = PlaceNotation.rounds( i );
			e['Back Rounds'] = PlaceNotation.backRounds( i );
		}
		if( i > 5 ) {
			e['Queens'] = PlaceNotation.queens( i );
			e['Kings']  = PlaceNotation.kings( i );
		}
		if( i > 5 ) {
			e['Tittums']      = PlaceNotation.tittums( i );
			e['Whittingtons'] = PlaceNotation.whittingtons( i );
		}
		return e;
	} );

	var scoreNamedRows = function( scoredRow, stage ) {
		var i, iLim = scoredRow.row.length, j;
		if( scoredRow.row.length > stage ) { scoredRow.namedRows = [[],[]]; }
		for( var rowName in namedRows[stage] ) {
			if( namedRows[stage].hasOwnProperty( rowName ) ) {
				// Find the first place where the named row potentially starts, and keep going until we know there's no room left for the named row
				i = scoredRow.row.indexOf( namedRows[stage][rowName][0] );
				while( i >= 0 && i+stage-1 < iLim ) {
					// Keep checking until the match fails or we hit the end of the named row
					for( j = 1; j < stage && scoredRow.row[i+j] === namedRows[stage][rowName][j]; ++j );
					// If the named row has been found then score and note
					if( j === stage ) {
						scoredRow.score.fill( (i+j == iLim)? 100 : 80, i, i+stage );
						if( scoredRow.row.length > stage ) {
							scoredRow.namedRows[i+j>stage?1:0].push( rowName );
						}
						else {
							scoredRow.namedRows.push( rowName );
						}
					}
					// Advance to the next possible place where the named row might start (only relevant if wrapping)
					i = scoredRow.row.indexOf( namedRows[stage][rowName][0], i+1 );
				}
			}
		}
		return scoredRow;
	};

	return function( rows, options ) {
		if( typeof options === 'undefined' ) { options = {}; }
		if( typeof options.stage === 'undefined' ) { options.stage = rows[0].length; }
		options.wrap = options.wrap && rows.length > 1;

		// If wrapping, push consecutive rows together, assume first row is backstroke (opening rounds)
		if( typeof options.wrap === 'undefined' || options.wrap ) {
			rows = rows.reduce( function( prev, cur, i ) {
				if( i%2 === 1 ) { prev.push([]); }
				Array.prototype.push.apply( prev[prev.length-1], cur );
				return prev;
			}, [[]] );
		}

		// Score
		for( var i = 0, iLim = rows.length; i < iLim; ++i ) {
			rows[i] = scoreNamedRows( scoreRunsThirdsTittums( { row: rows[i], score: Array( rows[i].length ).fill( 0 ), namedRows: [] }, options.stage ), options.stage );
		}

		// If wrapping, pull consecutive rows apart again.
		if( typeof options.wrap === 'undefined' || options.wrap ) {
			var offset = rows[1].row.length/2;
			rows = rows.slice( 1 ).reduce( function( prev, cur, i ) {
				prev.push( { row: cur.row.slice(0, offset), score: cur.score.slice(0, offset), namedRows: cur.namedRows[0] } );
				if( cur.row.length > offset ) {
					prev.push( { row: cur.row.slice(offset), score: cur.score.slice(offset), namedRows: cur.namedRows[1] } );
				}
				return prev;
			}, [rows[0]] );
		}
		return rows;
	}
} );
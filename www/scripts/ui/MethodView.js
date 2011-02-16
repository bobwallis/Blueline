define( ['../helpers/_', '../helpers/Paper'], function( _, Paper ) {
	if( typeof window['methods'] === 'undefined' ) {
		window['methods'] = [];
	}
	// Helper functions
	// Repeats an array to make an array of a given length
	var repeatArrayToLength = function( array, length ) {
		if( typeof array.push === 'undefined' ) { array = [array]; }
		var array2 = new Array( length ),
			i = -1,
			iMod = array.length;
		while( ++i < length ) {
			array2[i] = array[i%iMod];
		}
		return array2;
	};
	
	// Checks if two row arrays are equal
	var rowsEqual = function( row1, row2 ) {
		var i = row1.length;
		if( i != row2.length) { return false; }
		while( i-- ) { if( row1[i] !== row2[i] ) { return false; } }
		return true;
	};

	// Converts characters to bell numbers
	var charToBell = function( ch ) {
		var bell = parseInt( ch, 10 );
		if( isNaN( bell ) || bell === 0 ) {
			switch( ch ) {
				case '0': return 9;
				case 'E': return 10;
				case 'T': return 11;
				case 'A': return 12;
				case 'B': return 13;
				case 'C': return 14;
				case 'D': return 15;
				case 'F': return 16;
				case 'G': return 17;
				case 'H': return 18;
				case 'J': return 19;
				case 'K': return 20;
				case 'L': return 21;
				default: return false;
			}
		}
		return --bell;
	};

	// Converts bell numbers to characters
	var bellToChar = function( bell ) {
		if( bell < 9 ) { return (bell+1).toString(); }
		else {
			switch( bell ) {
				case 9: return '0';
				case 10: return 'E';
				case 11: return 'T';
				case 12: return 'A';
				case 13: return 'B';
				case 14: return 'C';
				case 15: return 'D';
				case 16: return 'F';
				case 17: return 'G';
				case 18: return 'H';
				case 19: return 'J';
				case 20: return 'K';
				case 21: return 'L';
			}
		}
	};

	// Turns a string into a row array
	var makeRow = function( string ) {
		return ( typeof string === 'string' )? string.split( '' ).map( charToBell ) : string;
	};

	// Permute a row array by a cycle permutation
	var permute = function( row, permutation ) {
		var i = permutation.length,
			j = row.length,
			permuted = new Array( j );
		while( j-- > i ) {
			permuted[j] = row[j];
		}
		do {
			permuted[j] = row[permutation[j]];
		} while( j-- );
		return permuted;
	};
	
	// Returns the row after applying the given permutations to start
	var permutes = function( start, permutations ) {
		var row = start,
			i = 0, iLim = permutations.length;
		while( i < iLim ) {
			row = permute( row, permutations[i++] );
		}
		return row;
	};
	
	// Returns a row containing rounds of a given stage
	var roundsRow = function( stage ) {
		var row = new Array( stage ), i = stage;
		while( i-- ) {
			row[i] = i;
		}
		return row;
	};

	// Returns an array of all bells in the same position in two rows
	var detectStationary = function( row1, row2 ) {
		var stationary = [], i = row1.length;
		while( i-- ) {
			if( row1[i] === row2[i] ) { stationary.push( row1[i] ); }
		}
		return stationary;
	};
	
	// Explodes notation into an array
	var explodeNotation = function( notation ) {
		return ( typeof notation === 'string' )? notation.replace( /x/gi, '.x.' ).split( '.' ).filter( function( e ) { return e !== ''; } ) : notation;
	};
	
	// Implodes a notation array to a string
	var implodeNotation = function( notationArray ) {
		return ( typeof notationArray.join === 'function' )? notationArray.join( '.' ).replace( /\.?x\.?/g, 'x' ) : notationArray;
	};
	
	// Parse place notation into an array of cycle permutations
	var parseNotation = function( notation, stage ) {
		var parsed = [],
			exploded = explodeNotation( notation ),
			i,
			xPermutation = new Array( stage );
		
		// Construct the X permutation for stage
		for( i = 0; i < stage; i+=2 ) { xPermutation[i] = i+1; xPermutation[i+1] = i; }
		if( i-1 == stage ) { xPermutation[i-1] = i-1; }
		
		for( i = 0; i < exploded.length; i++ ) {
			// For an x, push our pregenerated x permutation
			if( exploded[i] == 'x' ) {
				parsed.push( xPermutation );
			}
			// Otherwise calculate the permutation
			else {
				var stationary = exploded[i].split( '' ).map( charToBell ),
					permutation = new Array( stage ),
					j;
				// First put in any stationary bells
				for( j = 0; j < stationary.length; j++ ) {
					permutation[stationary[j]] = stationary[j];
				}
				// Then 'x' what's left
				for( j = 0; j < stage; j++ ) {
					if( typeof( permutation[j] ) == 'undefined' ) {
						if( typeof( permutation[j+1] ) == 'undefined' && j+1 < stage ) {
							permutation[j] = j+1;
							permutation[j+1] = j;
							j++;
						}
						else {
							permutation[j] = j;
						}
					}
				}
				parsed.push( permutation );
			}
		}
		return parsed;
	};

	// Seperate a permutation into disjoint cycles
	var getCycles = function( permutation ) {
		var cycles = [], i = permutation.length, rounds = roundsRow( i ), cycle, calcRow;
		while( i-- ) {
			if( rounds[i] == -1 ) { continue; }
			cycle = [rounds[i]];
			for( calcRow = permutation; calcRow[i] != rounds[i]; calcRow = permute( calcRow, permutation ) ) {
				cycle.push( calcRow[i] );
				rounds[calcRow[i]] = -1;
			}
			rounds[i] = -1;
			cycles.push( cycle );
		}
		return cycles;
	};
	
	
	var pathString = function( position, notation, xPlus, yPlus, repeats, flicks ) {
		if( !xPlus ) { xPlus = 1; }
		if( !yPlus ) { yPlus = 1; }
		if( !repeats ) { repeats = 1; }
		if( !flicks ) { flicks = false; }
		var newPosition,
			path = 'm'+((position*xPlus)+(xPlus/2))+','+(yPlus/2)+'l',
			i = 0, iMod = notation.length, iLim = repeats*iMod;
		for( ; i < iLim; i++ ) {
			newPosition = notation[i%iMod].indexOf( position );
			path += ((newPosition-position)*xPlus)+','+yPlus+' ';
			position = newPosition;
		}
		newPosition = notation[i%iMod].indexOf( position );
		if( flicks ) {
			path += (((newPosition-position)*xPlus)/4)+','+(yPlus/4)+' ';
		}
		return path;
	};
	
	var textSegment = function( start, notation, textSource, repeats ) {
		if( !textSource ) { textSource = start; }
		if( !repeats ) { repeats = 1; }
		var j = 0, jLim = start.length,
			i = 0, iLim = notation.length*repeats, iMod = notation.length,
			row = start,
			segmentText = '';
		while( j < jLim ) { segmentText += textSource[row[j++]]; }
		while( i < iLim ) {
			row = permute( row, notation[(i++)%iMod] );
			segmentText += '<br/>';
			j = 0;
			while( j < jLim ) { segmentText += textSource[row[j++]]; }
		}
		return segmentText;
	};

	// Shared variables
	var $head = document.head || document.getElementsByTagName( 'head' )[0],
	placeStartFont = {
		'small': [
			'm0,2.75c-0.6,0,-1.044,-0.244,-1.356,-0.756c-0.311,-0.511,-0.466,-1.244,-0.466,-2.2c0,-1.955,0.622,-2.933,1.822,-2.933c0.6,0,1.044,0.245,1.356,0.756c0.311,0.511,0.466,1.222,0.466,2.177c0,1.956,-0.6,2.956,-1.822,2.956zm0,-0.622c0.378,0,0.667,-0.178,0.844,-0.534c0.178,-0.355,0.267,-0.977,0.267,-1.8c0,-0.822,-0.089,-1.422,-0.267,-1.777c-0.177,-0.356,-0.466,-0.556,-0.844,-0.556c-0.378,0,-0.667,0.2,-0.844,0.556c-0.178,0.355,-0.267,0.955,-0.267,1.777c0,0.823,0.089,1.445,0.267,1.8c0.177,0.356,0.466,0.534,0.844,0.534z',
			'm0.5,2.75l-0.689,0l0,-3.556c0,-0.311,0.022,-0.8,0.045,-1.422c-0.112,0.111,-0.267,0.267,-0.489,0.445l-0.556,0.466l-0.378,-0.466l1.489,-1.178l0.578,0l0,5.711z',
			'm1.75,2.75l-3.556,0l0,-0.6l1.356,-1.489c0.511,-0.555,0.844,-0.978,1,-1.244c0.156,-0.267,0.244,-0.578,0.244,-0.911c0,-0.289,-0.088,-0.512,-0.244,-0.689c-0.156,-0.178,-0.378,-0.267,-0.667,-0.267c-0.422,0,-0.822,0.178,-1.244,0.533l-0.4,-0.444c0.489,-0.445,1.044,-0.689,1.644,-0.689c0.511,0,0.911,0.156,1.2,0.422c0.289,0.267,0.445,0.645,0.445,1.111c0,0.311,-0.067,0.623,-0.222,0.956c-0.156,0.333,-0.556,0.822,-1.156,1.467l-1.089,1.155l0,0.045l2.689,0l0,0.644z',
			'm0.5,-0.25l0,0.022c0.933,0.134,1.4,0.578,1.4,1.356c0,0.533,-0.178,0.933,-0.533,1.244c-0.356,0.311,-0.867,0.467,-1.556,0.467c-0.622,0,-1.133,-0.111,-1.511,-0.311l0,-0.667c0.489,0.245,0.978,0.378,1.489,0.378c0.911,0,1.4,-0.378,1.4,-1.133c0,-0.667,-0.489,-1.023,-1.489,-1.023l-0.533,0l0,-0.577l0.533,0c0.422,0,0.733,-0.089,0.956,-0.289c0.222,-0.2,0.355,-0.467,0.355,-0.8c0,-0.267,-0.089,-0.467,-0.267,-0.623c-0.177,-0.155,-0.422,-0.244,-0.711,-0.244c-0.466,0,-0.933,0.178,-1.355,0.489l-0.356,-0.489c0.489,-0.4,1.067,-0.6,1.711,-0.6c0.534,0,0.934,0.133,1.245,0.4c0.311,0.267,0.466,0.6,0.466,1.022c0,0.356,-0.111,0.667,-0.333,0.911c-0.222,0.245,-0.511,0.4,-0.911,0.467z',
			'm1.9,1.5l-0.844,0l0,1.289l-0.667,0l0,-1.289l-2.645,0l0,-0.622l2.578,-3.822l0.734,0l0,3.8l0.844,0l0,0.644zm-1.511,-0.644l0,-1.378c0,-0.467,0,-1.022,0.044,-1.689l-0.044,0c-0.089,0.267,-0.222,0.489,-0.356,0.689l-1.6,2.378l1.956,0z',
			'm-1.6,2.5l0,-0.689c0.378,0.245,0.867,0.378,1.422,0.378c0.867,0,1.311,-0.4,1.311,-1.222c0,-0.756,-0.466,-1.156,-1.355,-1.156c-0.222,0,-0.511,0.045,-0.889,0.111l-0.356,-0.222l0.2,-2.689l2.711,0l0,0.645l-2.088,0l-0.156,1.644c0.289,-0.044,0.556,-0.089,0.822,-0.089c0.556,0,1,0.156,1.334,0.445c0.333,0.288,0.488,0.733,0.488,1.244c0,0.6,-0.177,1.067,-0.533,1.4c-0.355,0.333,-0.867,0.511,-1.511,0.511c-0.578,0,-1.067,-0.111,-1.4,-0.311z',
			'm1.5,-3l0,0.6c-0.178,-0.067,-0.422,-0.089,-0.667,-0.089c-0.577,0,-1.022,0.156,-1.311,0.533c-0.289,0.378,-0.444,0.956,-0.466,1.756l0.044,0c0.244,-0.444,0.644,-0.667,1.2,-0.667c0.511,0,0.911,0.156,1.2,0.467c0.289,0.311,0.422,0.733,0.422,1.267c0,0.6,-0.155,1.066,-0.466,1.4c-0.312,0.333,-0.734,0.533,-1.267,0.533c-0.578,0,-1.022,-0.222,-1.356,-0.667c-0.333,-0.444,-0.511,-1.066,-0.511,-1.866c0,-2.245,0.822,-3.356,2.489,-3.356c0.267,0,0.511,0.045,0.689,0.089zm-0.267,3.867c0,-0.378,-0.089,-0.667,-0.266,-0.867c-0.178,-0.2,-0.423,-0.311,-0.756,-0.311c-0.333,0,-0.622,0.133,-0.844,0.333c-0.223,0.2,-0.334,0.445,-0.334,0.711c0,0.4,0.111,0.734,0.311,1.023c0.2,0.288,0.512,0.444,0.845,0.444c0.333,0,0.578,-0.133,0.755,-0.356c0.178,-0.222,0.289,-0.555,0.289,-0.977z',
			'm-1,2.75l2.2,-5.067l-2.956,0l0,-0.644l3.667,0l0,0.555l-2.155,5.156l-0.756,0z',
			'm0.6,-0.25c0.8,0.422,1.222,0.933,1.222,1.533c0,0.467,-0.178,0.845,-0.511,1.134c-0.333,0.289,-0.755,0.444,-1.289,0.444c-0.555,0,-1,-0.155,-1.311,-0.422c-0.311,-0.267,-0.489,-0.645,-0.489,-1.133c0,-0.667,0.378,-1.178,1.111,-1.534c-0.622,-0.378,-0.911,-0.866,-0.911,-1.444c0,-0.422,0.134,-0.734,0.445,-0.978c0.311,-0.244,0.689,-0.378,1.155,-0.378c0.489,0,0.867,0.134,1.156,0.378c0.289,0.244,0.444,0.578,0.444,1c0,0.6,-0.333,1.067,-1.022,1.4zm-0.578,-0.289c0.6,-0.267,0.911,-0.622,0.911,-1.089c0,-0.266,-0.089,-0.466,-0.244,-0.6c-0.156,-0.133,-0.378,-0.2,-0.667,-0.2c-0.266,0,-0.511,0.067,-0.666,0.2c-0.156,0.134,-0.245,0.334,-0.245,0.6c0,0.245,0.067,0.445,0.2,0.6c0.133,0.156,0.356,0.334,0.711,0.489zm-0.111,0.6c-0.667,0.311,-0.978,0.733,-0.978,1.267c0,0.622,0.356,0.933,1.067,0.933c0.355,0,0.622,-0.089,0.822,-0.267c0.2,-0.177,0.289,-0.4,0.289,-0.711c0,-0.244,-0.089,-0.444,-0.245,-0.622c-0.155,-0.178,-0.422,-0.356,-0.822,-0.556z',
			'm-1.3,2.75l0,-0.6c0.178,0.067,0.4,0.089,0.667,0.089c0.577,0,1.022,-0.156,1.311,-0.533c0.289,-0.378,0.444,-0.956,0.466,-1.756l-0.044,0c-0.244,0.444,-0.644,0.667,-1.2,0.667c-0.511,0,-0.911,-0.156,-1.2,-0.467c-0.289,-0.311,-0.422,-0.733,-0.422,-1.267c0,-0.6,0.155,-1.066,0.466,-1.4c0.312,-0.333,0.734,-0.533,1.267,-0.533c0.578,0,1.022,0.222,1.356,0.667c0.333,0.444,0.511,1.066,0.511,1.866c0,2.245,-0.822,3.356,-2.489,3.356c-0.267,0,-0.511,-0.045,-0.689,-0.089zm0.267,-3.867c0,0.378,0.089,0.667,0.266,0.867c0.178,0.2,0.423,0.311,0.756,0.311c0.333,0,0.622,-0.111,0.844,-0.333c0.223,-0.222,0.334,-0.445,0.334,-0.711c0,-0.4,-0.111,-0.734,-0.311,-1.023c-0.2,-0.288,-0.489,-0.444,-0.845,-0.444c-0.333,0,-0.6,0.133,-0.778,0.356c-0.177,0.222,-0.266,0.555,-0.266,0.977z'
		],
		'medium': [
			'',
			'm0.6889,3l-0.861,0l0,-4.444c0,-0.389,0.028,-1,0.055,-1.778c-0.138,0.139,-0.333,0.333,-0.611,0.555l-0.694,0.584l-0.472,-0.584l1.861,-1.472l0.722,0l0,7.139z',
			'm2.2167,3.45l-4.444,0l0,-0.75l1.694,-1.861c0.639,-0.695,1.056,-1.222,1.25,-1.556c0.194,-0.333,0.306,-0.722,0.306,-1.139c0,-0.361,-0.112,-0.638,-0.306,-0.861c-0.194,-0.222,-0.472,-0.333,-0.833,-0.333c-0.528,0,-1.028,0.222,-1.556,0.667l-0.5,-0.556c0.611,-0.555,1.306,-0.861,2.056,-0.861c0.639,0,1.139,0.194,1.5,0.528c0.361,0.333,0.555,0.805,0.555,1.389c0,0.389,-0.083,0.777,-0.278,1.194c-0.194,0.417,-0.694,1.028,-1.444,1.833l-1.361,1.445l0,0.055l3.361,0l0,0.806z',
			'm0.3833,-0.45l0,0.028c1.167,0.166,1.75,0.722,1.75,1.694c0,0.667,-0.222,1.167,-0.667,1.556c-0.444,0.389,-1.083,0.583,-1.944,0.583c-0.778,0,-1.417,-0.139,-1.889,-0.389l0,-0.833c0.611,0.305,1.222,0.472,1.861,0.472c1.139,0,1.75,-0.472,1.75,-1.417c0,-0.833,-0.611,-1.277,-1.861,-1.277l-0.667,0l0,-0.723l0.667,0c0.528,0,0.917,-0.111,1.194,-0.361c0.278,-0.25,0.445,-0.583,0.445,-1c0,-0.333,-0.111,-0.583,-0.333,-0.777c-0.223,-0.195,-0.528,-0.306,-0.889,-0.306c-0.584,0,-1.167,0.222,-1.695,0.611l-0.444,-0.611c0.611,-0.5,1.333,-0.75,2.139,-0.75c0.666,0,1.166,0.167,1.555,0.5c0.389,0.333,0.584,0.75,0.584,1.278c0,0.444,-0.139,0.833,-0.417,1.139c-0.278,0.305,-0.639,0.5,-1.139,0.583z',
			'm2.4944,1.75l-1.056,0l0,1.611l-0.833,0l0,-1.611l-3.305,0l0,-0.778l3.222,-4.778l0.916,0l0,4.75l1.056,0l0,0.806zm-1.889,-0.806l0,-1.722c0,-0.583,0,-1.278,0.056,-2.111l-0.056,0c-0.111,0.333,-0.278,0.611,-0.444,0.861l-2,2.972l2.444,0z',
			'm-2.1722,3.05l0,-0.861c0.472,0.305,1.083,0.472,1.778,0.472c1.083,0,1.639,-0.5,1.639,-1.528c0,-0.944,-0.584,-1.444,-1.695,-1.444c-0.278,0,-0.639,0.055,-1.111,0.139l-0.444,-0.278l0.25,-3.361l3.389,0l0,0.805l-2.612,0l-0.194,2.056c0.361,-0.056,0.694,-0.111,1.028,-0.111c0.694,0,1.25,0.194,1.666,0.555c0.417,0.362,0.612,0.917,0.612,1.556c0,0.75,-0.223,1.333,-0.667,1.75c-0.445,0.417,-1.083,0.639,-1.889,0.639c-0.722,0,-1.333,-0.139,-1.75,-0.389z',
			'm1.7167,-3.75l0,0.75c-0.222,-0.083,-0.528,-0.111,-0.833,-0.111c-0.723,0,-1.278,0.194,-1.639,0.667c-0.361,0.472,-0.556,1.194,-0.584,2.194l0.056,0c0.306,-0.556,0.806,-0.833,1.5,-0.833c0.639,0,1.139,0.194,1.5,0.583c0.361,0.389,0.528,0.917,0.528,1.583c0,0.75,-0.195,1.334,-0.584,1.75c-0.388,0.417,-0.916,0.667,-1.583,0.667c-0.722,0,-1.278,-0.278,-1.694,-0.833c-0.417,-0.556,-0.639,-1.334,-0.639,-2.334c0,-2.805,1.028,-4.194,3.111,-4.194c0.333,0,0.639,0.055,0.861,0.111zm-0.333,4.833c0,-0.472,-0.111,-0.833,-0.334,-1.083c-0.222,-0.25,-0.527,-0.389,-0.944,-0.389c-0.417,0,-0.778,0.167,-1.056,0.417c-0.277,0.25,-0.416,0.555,-0.416,0.889c0,0.5,0.139,0.916,0.389,1.277c0.25,0.362,0.638,0.556,1.055,0.556c0.417,0,0.722,-0.167,0.945,-0.444c0.222,-0.278,0.361,-0.695,0.361,-1.223z',
			'm-1.3667,3.45l2.75,-6.333l-3.694,0l0,-0.806l4.583,0l0,0.695l-2.695,6.444l-0.944,0z',
			'm0.7167,-0.35c1,0.528,1.528,1.167,1.528,1.917c0,0.583,-0.222,1.055,-0.639,1.416c-0.417,0.361,-0.945,0.556,-1.611,0.556c-0.695,0,-1.25,-0.195,-1.639,-0.528c-0.389,-0.333,-0.611,-0.805,-0.611,-1.417c0,-0.833,0.472,-1.472,1.389,-1.916c-0.778,-0.472,-1.139,-1.084,-1.139,-1.806c0,-0.528,0.166,-0.916,0.555,-1.222c0.389,-0.306,0.861,-0.472,1.445,-0.472c0.611,0,1.083,0.166,1.444,0.472c0.361,0.306,0.556,0.722,0.556,1.25c0,0.75,-0.417,1.333,-1.278,1.75zm-0.722,-0.361c0.75,-0.333,1.139,-0.778,1.139,-1.361c0,-0.334,-0.111,-0.584,-0.306,-0.75c-0.194,-0.167,-0.472,-0.25,-0.833,-0.25c-0.334,0,-0.639,0.083,-0.834,0.25c-0.194,0.166,-0.305,0.416,-0.305,0.75c0,0.305,0.083,0.555,0.25,0.75c0.167,0.194,0.444,0.416,0.889,0.611zm-0.139,0.75c-0.833,0.389,-1.222,0.917,-1.222,1.583c0,0.778,0.444,1.167,1.333,1.167c0.445,0,0.778,-0.111,1.028,-0.333c0.25,-0.223,0.361,-0.5,0.361,-0.889c0,-0.306,-0.111,-0.556,-0.305,-0.778c-0.195,-0.222,-0.528,-0.444,-1.028,-0.694z',
			'm-1.7278,3.25l0,-0.75c0.222,0.083,0.5,0.111,0.833,0.111c0.723,0,1.278,-0.194,1.639,-0.667c0.361,-0.472,0.556,-1.194,0.584,-2.194l-0.056,0c-0.306,0.556,-0.806,0.833,-1.5,0.833c-0.639,0,-1.139,-0.194,-1.5,-0.583c-0.361,-0.389,-0.528,-0.917,-0.528,-1.583c0,-0.75,0.195,-1.334,0.584,-1.75c0.388,-0.417,0.916,-0.667,1.583,-0.667c0.722,0,1.278,0.278,1.694,0.833c0.417,0.556,0.639,1.334,0.639,2.334c0,2.805,-1.028,4.194,-3.111,4.194c-0.333,0,-0.639,-0.055,-0.861,-0.111zm0.333,-4.833c0,0.472,0.111,0.833,0.334,1.083c0.222,0.25,0.527,0.389,0.944,0.389c0.417,0,0.778,-0.139,1.056,-0.417c0.277,-0.278,0.416,-0.555,0.416,-0.889c0,-0.5,-0.139,-0.916,-0.389,-1.277c-0.25,-0.362,-0.611,-0.556,-1.055,-0.556c-0.417,0,-0.75,0.167,-0.972,0.444c-0.223,0.278,-0.334,0.695,-0.334,1.223z'
		]
	};

	// Javascript for method/view pages
	var MethodView = function( options ) {
		// Copy in or create basic properties
		this.userOptions = options;
		this.id = options.id;
		this.stage = parseInt( options.stage, 10 );
		this.rounds = roundsRow( this.stage );
		this.notation = parseNotation( options.notation, this.stage );
		this.notationText = options.notation;
		this.notationExploded = explodeNotation( this.notationText );
		this.leadHead = ( typeof( options.leadHead ) == 'string' )? makeRow( options.leadHead ) : permutes( this.rounds, this.notation );
		this.workGroups = getCycles( this.leadHead );
		this.leadHeads = [this.rounds];
			for( var tmp = permute( this.rounds, this.leadHead ); !rowsEqual( tmp, this.rounds ); tmp = permute( tmp, this.leadHead ) ) {
				this.leadHeads.push( tmp );
			}
			this.leadHeads.push( this.rounds );
		this.numberOfLeads = this.leadHeads.length - 1;
		this.huntBells = this.workGroups.filter( function( e ) { return ( e.length == 1 ); } ).map( function( e ) { return e[0]; } );
		
		this.ruleOffs = { every: this.notation.length, from: 0 };
		if( typeof options.ruleOffs === 'string' ) {
			var ruleOffsExplode = options.ruleOffs.split( ':' ).map( function( e ) { return parseInt( e, 10 ); } );
			this.ruleOffs.every = ( ruleOffsExplode[0] == NaN )? this.notation.length : ruleOffsExplode[0];
			this.ruleOffs.from = ( ruleOffsExplode[1] == NaN )? 0 : ruleOffsExplode[1];
		}
		
		// Calculate new notation, and other details for calls
		this.calls = [];
		if( typeof( options.calls ) != 'undefined' && options.calls.length !== 0 && !_.isEmpty( options.calls ) ) {
			for( var call in options.calls ) {
				// Parse the information given
				var callInfo = options.calls[call].split( ':', 3 );
				if( callInfo[1] == '' ) { callInfo[1] = this.notation.length; } else { callInfo[1] = parseInt( callInfo[1], 10 ); }
				if( callInfo[2] == '' ) { callInfo[2] = 0; } else { callInfo[2] = parseInt( callInfo[2], 10 ); }
				// Get a block of notation to work with
				var callNotationExploded = repeatArrayToLength( this.notationExploded, Math.max( callInfo[1]*2, 8 ) ),
					plainNotationExploded =  callNotationExploded.slice( 0 ); // Keep track of a plain lead to so we can tell which bells are affected
				// Explode the call notation (Grandsire singles, for example, span more than one change)
				var justCallNotationExploded = explodeNotation( callInfo[0] );
				// Insert the call notation into the first avaliable slot in the notation block
				for( var i = 0; i < justCallNotationExploded.length; i++ ) {
					callNotationExploded[(callInfo[1]-1)+callInfo[2]+i] = justCallNotationExploded[i];
				}
				// Decide how many rows of the call to display
				var rowsToDisplay = Math.max( justCallNotationExploded.length + 6, Math.floor( this.notationExploded.length / 2 ) ),
					rowsToDisplayBefore = Math.ceil( ( rowsToDisplay - justCallNotationExploded.length ) / 2 )+1,
					rowsToDisplayAfter = Math.floor( ( rowsToDisplay - justCallNotationExploded.length ) / 2 )-1;
				// Add more notation to the block to make sure there's enough there to slice out a big enough piece
				for( var padding = 0;
					(callInfo[1]+callInfo[2]+padding)-rowsToDisplayBefore < 0;
					callNotationExploded = this.notationExploded.concat( callNotationExploded ), plainNotationExploded = this.notationExploded.concat( plainNotationExploded ), padding += this.notationExploded.length ) {}
				// Slice out a section to display
				var preSlice = (callInfo[1]+callInfo[2]+padding)-rowsToDisplayBefore,
					postSlice = callInfo[1]+callInfo[2]+padding+rowsToDisplayAfter+justCallNotationExploded.length,
					preCallNotationExploded = callNotationExploded.slice( 0, preSlice ),
					callNotationExploded = callNotationExploded.slice( preSlice, postSlice ),
					plainNotationExploded = plainNotationExploded.slice( preSlice, postSlice ),
				// Implode the notation back again
					callNotation = implodeNotation( callNotationExploded ),
				// Calculate some important rows
					startRow = permutes( this.rounds, parseNotation( preCallNotationExploded, this.stage ) ),
					endCallRow = permutes( startRow, parseNotation( callNotationExploded, this.stage ) ),
					endPlainRow = permutes( startRow, parseNotation( plainNotationExploded, this.stage ) );
				// Save the call's details
				this.calls.push( {
					id: call.replace( ' ', '_' ).replace( /[^A-Za-z0-9_]/, '' ).toLowerCase(),
					title: call,
					startRow: startRow,
					notation: parseNotation( callNotation, this.stage ),
					notationText: callNotation,
					highlight: { from: rowsToDisplayBefore, to: (rowsToDisplayBefore-1)+justCallNotationExploded.length },
					huntBellStartPositions: this.huntBells.map( function( e ) { return startRow.indexOf( e ); }, this ),
					affectedBellStartPositions: this.rounds.filter( function( e ) { return endCallRow.indexOf( startRow[e] ) != endPlainRow.indexOf( startRow[e] ) }, this ),
					ruleOffs: { every: this.ruleOffs.every, from: this.ruleOffs.from-preSlice }
				} );
			}
		}
		
		// Create objects to pass to child classes
		this.container = {};
		if( typeof options.options_line.container !== 'undefined' ) { this.container.line = options.options_line.container; }
		if( typeof options.options_grid.container !== 'undefined' ) { this.container.grid = options.options_grid.container; }
		
		this.options = { line: {}, grid: {} };
		
		// Grid options
		// Decide on some colours to use
		var grid_ruleOffParams = _.mergeObjects( {
			'stroke-width': 1,
			'stroke-linecap': 'round',
			'stroke-dasharray': '4,2',
			'stroke': '#999',
			fill: 'none'
		}, options.options_grid.ruleOffDisplay ),
		grid_lineParams = _.mergeArrays( 
			repeatArrayToLength( ['#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF'], this.stage )
				.map( function( e, i ) {
					return ( this.huntBells.indexOf( i ) != -1 )? {stroke: '#D11', 'stroke-width': 1} : { stroke: e };
				}, this )
				.map( function( e ) {
					return _.mergeObjects( {
						'stroke-linejoin': 'round',
						'stroke-linecap': 'round',
						'stroke-width': 2,
						fill: 'none'
					}, e );
				}, this ),
		options.options_grid.linesDisplay );
		
		this.options.grid.plainLead = {
			id: this.id+'_grid_plainLead',
			title: 'Plain Lead',
			container: this.container.grid,
			notation: this.notation,
			stage: this.stage,
			notationText: this.notationText,
			notationExploded: this.notationExploded,
			ruleOffs: this.ruleOffs,
			showNotation: true,
			display: {
				dimensions: {
					row: {x:12*this.stage,y:15},
					bell: {x:12,y:15}
				},
				notation: true,
				ruleOffs: grid_ruleOffParams,
				lines: grid_lineParams
			}
		};
		this.options.grid.calls = this.calls.map( function( call ) {
			return {
				id: this.id+'_grid_'+call.id,
				title: call.title,
				container: this.container.grid,
				notation: call.notation,
				stage: this.stage,
				notationText: call.notationText,
				ruleOffs: call.ruleOffs,
				showNotation: true,
				display: {
					dimensions: {
						row: {x:12*this.stage,y:15},
						bell: {x:12,y:15}
					},
					notation: true,
					highlight: call.highlight,
					ruleOffs: grid_ruleOffParams,
					lines: permute( grid_lineParams, call.startRow )
				}
			};
		}, this );
		
		// Create child classes
		this.draw();
	};
	MethodView.prototype = {
		
		draw: function() {
			this.Grids = [];
			if( typeof this.container.grid !== 'undefined' ) {
				this.Grids.push( new MethodGrid( this.options.grid.plainLead ) );
				this.options.grid.calls.forEach( function( call ) {
					this.Grids.push( new MethodGrid( call ) );
				}, this );
			}
			if( typeof this.container.line !== 'undefined' ) {
				this.Line = new MethodLine( this, this.userOptions.options_line );
				this.Line.draw();
			}
		},
		
		resize: function() {
			this.Line.resize();
		},
		
		destroy: function() {
			this.Line.destroy();
			this.Grids.forEach( function( Grid ) { Grid.destroy(); } );
		}
	};
	window['MethodView'] = MethodView;
	
	var MethodLine = function( parent, options ) {
		var e, workColorSource;
		
		this.parent = parent;
		this.options = options;
		this.options_orig = {}; for( e in options ) { this.options_orig[e] = options[e]; } // Copying an object is such a mission in Javascript. This method doesn't work properly in general, but does enough in this case
		this.cache = {};
	
		// Find container
		if( typeof options.container === 'undefined' ) { return false; }
		this.container = ( typeof options.container === 'string' )? document.getElementById( options.container ) : options.container;
		if( typeof this.container.nodeName === 'undefined' ) { return false; }
		
		// Merge options with defaults
		
		if( typeof options.text === 'undefined' ) {
			// Show text by default
			this.options.text = true;
		}
		// Create text and SVG containers
		this.initialiseContainers();
		
		if( typeof options.lines === 'undefined' ) {
			// Show lines by default
			this.options.lines = true;
		}
		if( typeof options.placeStarts === 'undefined' ) {
			// Show both the dots on lines and the alongside numbers by default
			this.options.placeStarts = { pathMarkers: true, alongside: true };
		}
		if( typeof options.calls === 'undefined' ) {
			// Display calls if given by default
			this.options.calls = ( this.parent.calls.length > 0 )? true : false;
		}
		if( typeof options.colors === 'undefined' ) {
			// Some default colors. Red hunt bells; blue, green, purple... work bells. Transparent text for colored lines
			this.options.colors = {
				lines: { hunt: '#D11', base: 'transparent', work: ['#11D','#1D1','#D1D', '#DD1', '#1DD'] },
				text: ( this.options.lines === true )? { hunt: 'transparent', base: '#000', work: 'transparent' } : { hunt: '#D11', base: '#000', work: ['#11D','#1D1','#D1D', '#DD1', '#1DD'] },
				ruleOffs: '#999'
			};
		}
		
		// Assign colors to bells
		this.courseColors = this.parent.rounds.map( function( e ) {
			return { line: this.options.colors.lines.base, text: this.options.colors.text.base };
		}, this );
		workColorSource = {
			lines: repeatArrayToLength( this.options.colors.lines.work, this.parent.workGroups.length ),
			text: repeatArrayToLength( this.options.colors.text.work, this.parent.workGroups.length )
		};
		this.parent.huntBells.forEach( function( pos ) {
			this.courseColors[pos] = { line: this.options.colors.lines.hunt, text: this.options.colors.text.hunt };
		}, this );
		this.parent.workGroups.forEach( function( group ) {
			if( group.length > 1 ) {
				this.courseColors[group[0]] = { line: workColorSource.lines.shift(), text: workColorSource.text.shift() };
			}
		}, this );
		
		this.callColors = {};
		this.parent.calls.forEach( function( call ) {
			var colors = this.parent.rounds.map( function( e ) {
				return { line: this.options.colors.lines.base, text: this.options.colors.text.base };
			}, this ),
				affectedColorSource = {
					lines: repeatArrayToLength( this.options.colors.lines.work, call.affectedBellStartPositions.length ),
					text: repeatArrayToLength( this.options.colors.text.work, call.affectedBellStartPositions.length )
				};
			call.huntBellStartPositions.forEach( function( pos ) {
				colors[call.startRow[pos]] = { line: this.options.colors.lines.hunt, text: this.options.colors.text.hunt };
			}, this );
			call.affectedBellStartPositions.forEach( function( pos ) {
				colors[call.startRow[pos]] = { line: affectedColorSource.lines.shift(), text: affectedColorSource.text.shift() };
			}, this );
			this.callColors[call.id] = colors;
		}, this );
		
		// Add the CSS for text styling to the page
		if( this.options.text ) {
			var cssString = '',
				colorStyleSheet = document.createElement( 'style' ),
				sizeStyleSheet = document.createElement( 'style' );
				textContainerId = 'methodText_'+this.parent.id,
				i = 0, iLim = this.courseColors.length;
			this.courseColors.forEach( function( color, i ) {
				if( color.text != this.options.colors.text.base ) {
					if( color.text === 'transparent' ) {
						cssString += '#' + textContainerId + ' span.b' + bellToChar( i ) + ' {color:transparent !important;} * html #' + textContainerId + ' span.b' + bellToChar( i ) + ', *+html #' + textContainerId + ' span.b' + bellToChar( i ) + ' { visibility: hidden; } #' + textContainerId + ' span.b' + bellToChar( i ) + ' { visibility: hidden\\0/ !important; }';
					}
					else {
						cssString += '#' + textContainerId + ' span.b' + bellToChar( i ) + ' {color:' + color.text + ' !important;}';
					}
				}
			}, this );
			for( var call in this.callColors ) {
				var colors = this.callColors[call];
				colors.forEach( function( color, i ) {
					if( color.text != this.options.colors.text.base ) {
						if( color.text === 'transparent' ) {
							cssString += '#' + textContainerId + ' span.'+call+'_b' + bellToChar( i ) + ' {color:transparent !important;} * html #' + textContainerId + ' span.'+call+'_b' + bellToChar( i ) + ', *+html #' + textContainerId + ' span.'+call+'_b' + bellToChar( i ) + ' {visibility:hidden;} #' + textContainerId + ' span.'+call+'_b' + bellToChar( i ) + ' {visibility:hidden\\0/ !important;}';
						}
						else {
							cssString += '#' + textContainerId + ' span.'+call+'_b' + bellToChar( i ) + ' {color:' + color.text + ' !important;}';
						}
					}
				}, this );
			}
			colorStyleSheet.innerHTML = cssString;
			colorStyleSheet.id = 'colorStyle'+this.parent.id;
			sizeStyleSheet.id = 'sizeStyle'+this.parent.id;
			$head.appendChild( sizeStyleSheet );
			$head.appendChild( colorStyleSheet );
			this.sizeStyleSheet = sizeStyleSheet;
			this.colorStyleSheet = colorStyleSheet;
		}
	
		// Calculate font size, lead distribution across columns
		this.calculateSizing();
	};
	MethodLine.prototype = {
		destroy: function() {
			while( this.container.firstChild ) { this.container.removeChild( this.container.firstChild ); }
			$head.removeChild( this.sizeStyleSheet );
			$head.removeChild( this.colorStyleSheet );
		},
		
		initialiseContainers: function() {
			// Empty the container by creating a new one with the same attributes, and replacing the old one with it. This is often faster than emptying the old one.
			var newLineContainer = document.createElement( this.container.nodeName );
			newLineContainer.id = this.container.id;
			newLineContainer.className = this.container.className;
			newLineContainer.style.display = this.container.style.display;
			this.container.parentNode.replaceChild( newLineContainer, this.container );
			this.container = newLineContainer;
		},
	
		calculateSizing: function() {
			// Calculate column padding
			var columnPadding = 20,
				placeStartPadding = 0;
			if( this.options.placeStarts.alongside ) {
				placeStartPadding += ( 12 * ( this.parent.rounds.filter( function( e ) {
					return ( this.courseColors[e].line != this.options.colors.lines.base && this.courseColors[e].line != this.options.colors.lines.hunt );
				}, this ).length - 1) );
			}
			this.options.columnPadding = columnPadding;
			this.options.placeStartPadding = placeStartPadding;
			
			// How many columns to include for calls
			var callColumns =  this.options.calls? this.parent.calls.length : 0;
			
			// Add an element to the page whose width we can use to test font sizes
			var testText = document.createElement( 'span' );
			testText.className = 'methodText';
			testText.style.fontSize = '14px';
			testText.innerHTML = this.parent.rounds.map( bellToChar ).join('');
			document.body.appendChild( testText );
			
			// Work out column/lead distribution and font size
			var pageWidth = window.innerWidth || document.documentElement.clientWidth,
				testWidth = testText.offsetWidth,
				testFontSize = 14,
				leadsPerColumn = 1,
				numberOfColumns = this.parent.numberOfLeads;
			// If the number of columns is fixed
			if( typeof this.options_orig.columns !== 'undefined' ) {
				numberOfColumns = this.options_orig.columns;
				leadsPerColumn = Math.ceil( this.parent.numberOfLeads/numberOfColumns );
				var sizeTestBase = pageWidth - 30 - ( ((2*(numberOfColumns+callColumns)-1) * columnPadding ) + ( numberOfColumns * placeStartPadding ) );
				while( testFontSize > 10 && sizeTestBase - ( (numberOfColumns+callColumns) * testWidth ) < 0 ) {
					testText.style.fontSize = (--testFontSize)+'px';
					testWidth = testText.offsetWidth;
				}
			}
			// If the number of leads per column is fixed
			else if( typeof this.options_orig.leadsPerColumn !== 'undefined' ) {
				leadsPerColumn = this.options_orig.leadsPerColumn;
				numberOfColumns = Math.ceil( this.parent.numberOfLeads / leadsPerColumn );
				var sizeTestBase = pageWidth - 30 - ( ((2*(numberOfColumns+callColumns)-1) * columnPadding ) + ( numberOfColumns * placeStartPadding ) );
				while( testFontSize > 10 && sizeTestBase - ( (numberOfColumns+callColumns) * testWidth ) < 0 ) {
					testText.style.fontSize = (--testFontSize)+'px';
					testWidth = testText.offsetWidth;
				}
			}
			else {
				// Calculate best text size/column settings by measuring the page
				do {
				var sizeTestBase = pageWidth - 30 - ( ((2*(numberOfColumns+callColumns)-1) * columnPadding ) + ( numberOfColumns * placeStartPadding ) );
					while( testFontSize > 10 && sizeTestBase - ( (numberOfColumns+callColumns) * testWidth ) < 0 ) {
						testText.style.fontSize = (--testFontSize)+'px';
						testWidth = testText.offsetWidth;
					}
					if( testFontSize < 11 && leadsPerColumn < this.parent.numberOfLeads) {
						testFontSize = 14;
						testText.style.fontSize = '14px';
						testWidth = testText.offsetWidth;
						leadsPerColumn++;
						numberOfColumns = Math.ceil( this.parent.numberOfLeads/leadsPerColumn );
					}
					else { break; }
				} while( 1 );
			}
			this.options.fontSize = testFontSize;
			this.options.columns = numberOfColumns;
			this.options.callColumns = callColumns;
			this.options.leadsPerColumn = leadsPerColumn;
			this.dimensions = {
				row: { x: testWidth, y: testFontSize+1 },
				bell: { x: testWidth/this.parent.stage, y: testFontSize+1 }
			};
			testText.parentNode.removeChild( testText );
		
			// Add size information to the page
			var textContainerId = 'methodText_'+this.parent.id;
			this.sizeStyleSheet.innerHTML = '#' + textContainerId + ' td{padding:0 '+(this.options.columnPadding+this.options.placeStartPadding)+'px 0 '+this.options.columnPadding+'px !important;}' +
				'#' + textContainerId + ' td.first{padding-left:0 !important;}' +
				'#' + textContainerId + '{font-size:' + this.options.fontSize + 'px !important;line-height:' + (this.options.fontSize+1) + 'px !important;}';
		},
	
		draw: function() {
			// Create the paper for drawing on
			var paperHeight = this.dimensions.row.y*(this.parent.notation.length+1)*this.options.leadsPerColumn;
			this.paper = new Paper( {
				id: 'methodLine_'+this.parent.id,
				width: (this.dimensions.row.x+(this.options.columnPadding*2)+this.options.placeStartPadding)*(this.options.columns+this.options.callColumns),
				height: paperHeight
			} );
			if( this.paper !== false ) {
				// Draw rule offs?
				if( this.options.ruleOffs !== false ) {
					this.drawRuleOffs();
				}
				// Draw place starts?
				if( this.options.placeStarts !== false ) {
					this.drawPlaceStarts();
				}
				// Draw lines?
				if( this.options.lines === true ) {
					this.drawLines();
				}
				// Add the paper to the page
				this.container.appendChild( this.paper.canvas );
			}
			
			// Draw method text?
			if( this.options.text !== false ) {
				if( this.paper !== false ) {
					this.paper.canvas.style.marginBottom = ((-1)*paperHeight)+'px';
				}
				this.container.appendChild( this.textTable() );
			}
		},
		
		resize: function() {
			this.initialiseContainers();
			this.calculateSizing();
			this.draw();
		},

		textTable: function() {
			// Write out the plain course
			var textSource = this.parent.rounds.map( function( e ) {
				var bellText = bellToChar( e );
				return ( this.courseColors[e].text != this.options.colors.text.base )? '<span class="b'+bellToChar( e )+'">'+bellText+'</span>' : bellText;
			}, this ),
				methodText = document.createElement( 'table' ),
				methodTextInnerHTML = '',
				leadsPerColumn = this.options.leadsPerColumn,
				i = 0, iLim = this.options.columns,
				repeats;
			methodText.id = 'methodText_'+this.parent.id;
			while( i < iLim ) {
				repeats = ( (i+1)*leadsPerColumn < this.parent.numberOfLeads )? leadsPerColumn : this.parent.numberOfLeads%leadsPerColumn;
				methodTextInnerHTML += '<td'+((i===0)?' class="first"':'')+'>' + textSegment( this.parent.leadHeads[i*leadsPerColumn], this.parent.notation, textSource, repeats?repeats:leadsPerColumn ) + '</td>';
				++i;
			}
			
			// Write out any calls
			if( this.options.calls ) {
				this.parent.calls.forEach( function( call ) {
					var textSource = this.parent.rounds.map( function( e ) {
						var bellText = bellToChar( e );
						return ( this.callColors[call.id][e].text !== this.options.colors.text.base )? '<span class="'+call.id+'_b'+bellToChar( e )+'">'+bellText+'</span>' : bellText;
					}, this )
					methodTextInnerHTML += '<td class="call" id="methodText_call_'+call.id+'"><p class="callTitle">'+call.title+':</p>'+textSegment( call.startRow, call.notation, textSource )+'</td>';
				}, this );
			}
			
			methodText.innerHTML = '<tr>' + methodTextInnerHTML + '</tr>';
			return methodText;
		},
		
		drawLines: function() {
			// Draw lines for the plain course
			var paths = {},
				pathcolors = [],
				i = 0, iLim = this.options.columns,
				upTo, leadsToDraw,
				j = 0, jLim = this.parent.stage,
				hMultiplier = this.dimensions.row.x + (2*this.options.columnPadding) + this.options.placeStartPadding;
			// We'll build a single long path for each different path style
			for( ; j < jLim; ++j ) {
				if( typeof paths[this.courseColors[j].line] === 'undefined' ) {
					paths[this.courseColors[j].line] = '';
					pathcolors.push( this.courseColors[j].line );
				}
			}
			// Calculate paths
			for( ; i < iLim; ++i ) {
				for( upTo = (i+1)*this.options.leadsPerColumn; typeof this.parent.leadHeads[upTo] === 'undefined'; --upTo ) {}
				leadsToDraw = (upTo%this.options.leadsPerColumn === 0)? this.options.leadsPerColumn : upTo%this.options.leadsPerColumn;
				for( j = 0; j < jLim; ++j ) {
					if( this.courseColors[j].line != 'transparent' ) {
						paths[this.courseColors[j].line] += 'M'+(i*hMultiplier)+',0' + pathString( this.parent.leadHeads[i*this.options.leadsPerColumn].indexOf( j ), this.parent.notation, this.dimensions.bell.x, this.dimensions.bell.y, leadsToDraw, true );
					}
				}
			}
			// Draw paths
			// Base
			if( typeof paths[this.options.colors.lines.base] !== 'undefined' && this.options.colors.lines.base !== 'transparent' ) {
				this.paper.add( 'path', {
					'stroke-width': 1, 'stroke-linejoin': 'round', 'stroke-linecap': 'round', fill: 'none',
					stroke: this.options.colors.lines.base,
					d: paths[this.options.colors.lines.base]
				} );
			}
			// Hunts
			if( typeof paths[this.options.colors.lines.hunt] !== 'undefined' && this.options.colors.lines.hunt !== 'transparent' ) {
				this.paper.add( 'path', {
					'stroke-width': 1, 'stroke-linejoin': 'round', 'stroke-linecap': 'round', fill: 'none',
					stroke: this.options.colors.lines.hunt,
					d: paths[this.options.colors.lines.hunt]
				} );
			}
			// Working
			for( i = 0, iLim = pathcolors.length; i < iLim; ++i ) {
				if( pathcolors[i] !== this.options.colors.lines.base && pathcolors[i] !== this.options.colors.lines.hunt && pathcolors[i] !== 'transparent' ) {
					this.paper.add( 'path', {
						'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round', fill: 'none',
						stroke: pathcolors[i],
						d: paths[pathcolors[i]]
					} );
				}
			}
			
			// Draw lines for each of the call
			this.parent.calls.forEach( function( call, i ) {
				// Work out positioning
				var x = ( this.options.columns + i )*( this.dimensions.row.x + (2*this.options.columnPadding) ) + ( this.options.columns*this.options.placeStartPadding ),
					y = this.dimensions.row.y+3;
				call.startRow.forEach( function( bell, pos ) {
					var color = this.callColors[call.id][bell].line;
					if( color !== 'transparent' ) {
						this.paper.add( 'path', {
							'stroke-linejoin': 'round', 'stroke-linecap': 'round', fill: 'none',
							'stroke-width': ( color === this.options.colors.lines.hunt )? 1 : 2,
							stroke: color,
							d: 'M'+x+','+y+pathString( pos, call.notation, this.dimensions.bell.x, this.dimensions.bell.y, 1, false )
						} );
					}
				}, this );
			}, this );
		},
	
		drawRuleOffs: function() {
			if( this.options.colors.ruleOffs === 'transparent' ) { return; }
			// i will iterate over columns
			var i = -1, iLim = this.options.columns,
				// j will iterate over rule offs within a column
				j, jLim = (this.options.leadsPerColumn*this.parent.notation.length)/this.parent.ruleOffs.every,
				// kLim will be a hard limit on the number of rule offs
				k = 0, kLim = (this.parent.numberOfLeads*this.parent.notation.length)/this.parent.ruleOffs.every,
				path = '',
				// h and vMultipler and Padding are for positioning the rule offs
				hMultiplier = this.dimensions.row.x + (2*this.options.columnPadding) + this.options.placeStartPadding,
				vMultiplier = this.parent.ruleOffs.every*this.dimensions.row.y,
				vPadding = 0.5 + (this.parent.ruleOffs.from*this.dimensions.row.y);
			while( ++i < iLim ) {
				for( j = 0; j < jLim && k < kLim; j++, k++ ) {
					path += 'M'+(i*hMultiplier)+','+((j+1)*vMultiplier+vPadding)+'l'+this.dimensions.row.x+',0';
				}
			}
			this.paper.add( 'path', { 'stroke-width': 1, 'stroke-linecap': 'round', 'stroke-dasharray': '4,2', 'stroke': this.options.colors.ruleOffs, 'd': path } );
		},
	
		drawPlaceStarts: function() {
			var toDraw = this.parent.rounds.filter( function( e ) {
				return ( this.courseColors[e].line != this.options.colors.lines.base && this.courseColors[e].line != this.options.colors.lines.hunt );
			}, this ),
				jLim = toDraw.length,
				hMultiplier = this.dimensions.row.x + (2*this.options.columnPadding) + this.options.placeStartPadding,
				vMultiplier = this.parent.notation.length*this.dimensions.row.y,
				vPadding = 0.5*this.dimensions.bell.y;
			if( this.options.placeStarts.pathMarkers && this.options.lines ) {
				var i = this.parent.numberOfLeads,
					j = 0,
					hPadding = 0;
				while( i-- ) {
					for( j = 0; j < jLim; j++ ) {
						this.paper.add( 'circle', { 
							cx: (Math.floor(i/this.options.leadsPerColumn)*hMultiplier)+((this.parent.leadHeads[i].indexOf(toDraw[j])+0.5)*this.dimensions.bell.x)+hPadding,
							cy: ((i%this.options.leadsPerColumn)*vMultiplier)+vPadding,
							r: 2,
							fill: this.courseColors[toDraw[j]].line,
							'stroke-width': 0,
							stroke: this.courseColors[toDraw[j]].line
						} );
					}
				}
			}
			if( this.options.placeStarts.alongside ) {
				var i = this.parent.numberOfLeads,
					j = 0,
					hPadding = 12 + this.dimensions.row.x,
					textPath = '';
				while( i-- ) {
					var x = (Math.floor(i/this.options.leadsPerColumn)*hMultiplier)+hPadding,
						y = ((i%this.options.leadsPerColumn)*vMultiplier)+vPadding;
					toDraw = this.placeStartSort( toDraw, i );
					for( j = 0; j < jLim; ++j ) {
						var place = this.parent.leadHeads[i].indexOf( toDraw[j] ) + 1;
						this.paper.add( 'circle', {
							cx: (x+(j*12)),
							cy: y,
							r: 6,
							fill: 'none',
							'stroke-width': 1,
							stroke: this.courseColors[toDraw[j]].line,
							opacity: 0.8
						} );
						if( place < 10 ) {
							textPath += 'M'+(x+(j*12))+','+y+placeStartFont.medium[place];
						}
						else {
							textPath += 'M'+(x+(j*12)-2)+','+y+placeStartFont.small[Math.floor(place/10)];
							textPath += 'M'+(x+(j*12)+2)+','+y+placeStartFont.small[place%10];
						}
					}
				}
				this.paper.add( 'path', {
					'stroke': 'none',
					'fill': '#000',
					'd': textPath
				} );
			}
		},
		placeStartSort: function( toDraw, i ) {
			var leadHeads = this.parent.leadHeads,
				places = toDraw.map( function( e ) { return leadHeads[i].indexOf( e ); } ),
				places2 = places.slice().sort( function( a, b ) { return (a>b)?1:-1; } ),
				toDraw2 = toDraw.map( function( e, index ) { return toDraw[places.indexOf( places2[index] )]; } );
				return toDraw2;
		}
	};
	
	
	/**
	 * Draws out a method grid, possibly with place notation
	 * options:
	 * options.id:               An ID string to use
	 * options.title:            A title to give the grid (optional)
	 * options.container:        An element (or ID string of one) into which the grid will be appended
	 * options.notationText      The place notation of the grid (string)
	 * options.stage:            The stage of the method (integer)
	 * options.notation:         Parsed notation (optional)
	 * options.notationExploded: Exploded notation (optional)
	 * options.ruleOffs:         {from: x, every: u} object describing how to draw rule offs
	 * options.showNotation:     Whether or not to print the place notation
	 * options.display.dimensions: Dimensions object
	 * options.display.ruleOffs: SVG path parameters for rule offs
	 * options.display.lines:    An array mapping a line's start position to a set of options to pass to the SVG path
	 */
	
	var MethodGrid = function( options ) {
		// Find the container
		this.container = ( typeof options.container === 'string' )? document.getElementById( options.container ) : options.container;	
		if( typeof this.container.nodeName === 'undefined' ) { return false; }
		
		this.id = options.id;
		
		// Parse the place notation
		this.stage = options.stage;
		this.notation = (typeof options.notation === 'object')? options.notation : parseNotation( options.notationText, options.stage );
		this.notationText = options.notationText;
		this.notationExploded = (typeof options.notationExploded === 'object')? options.notationExploded : explodeNotation( options.notationText );
		
		// Rule offs
		this.ruleOffs = (typeof options.ruleOffs === 'object')? options.ruleOffs : false;
		
		// Display options
		this.title = (typeof options.title === 'string')? options.title : false;
		this.display = _.mergeObjects( { notation: false }, options.display );
		
		this.draw();
	};
	// Private functions for MethodGrid
	
	var MethodGrid_table = function( id, title ) {
		var grid = document.createElement( 'table' ),
			titleRow = document.createElement( 'tr' ),
			titleCell = document.createElement( 'td' );
		grid.id = 'method'+id;
		if( typeof title === 'string' ) {
			titleCell.className = 'titleCell';
			titleCell.innerHTML = title+':';
			titleCell.colSpan = 2;
			titleRow.appendChild( titleCell );
			grid.appendChild( titleRow );
		}
		return grid;
	};
	
	var MethodGrid_notationCell = function( notationExploded, highlight ) {
		if( typeof highlight !== 'undefined' ) {
			if( typeof highlight === 'number' ) {
				notationExploded[highlight-1] = '<strong>'+notationExploded[highlight-1]+'</strong>';
			}
			else if( typeof highlight.from === 'number' && typeof highlight.to === 'number' ) {
				for( var i = highlight.from; i <= highlight.to; ++i ) {
				notationExploded[i-1] = '<strong>'+notationExploded[i-1]+'</strong>';
				}
			}
		}
		var cell = document.createElement( 'td' );
		cell.className = 'notationCell';
		cell.innerHTML = notationExploded.join( '<br />' );
		return cell;
	};
	
	var MethodGrid_grid = function( options ) {
		var paperCell = document.createElement( 'td' ),
			paper = new Paper( {
				id: options.id+'_paper',
				width: options.display.dimensions.row.x,
				height: options.display.dimensions.row.y*( options.notation.length+1 )
			} ),
			i, iLim,
			path;
			
		if( paper === false ) {
			// If we can't draw using SVG, then request an image from the server instead
			// to implement
		}
		else {
			// Draw rule offs
			if( options.ruleOffs && typeof options.display.ruleOffs.stroke === 'string' && options.display.ruleOffs.stroke !== 'transparent' ) {
				i = options.ruleOffs.from;
				iLim = options.notation.length;
				path = '';
				
				while( i <= iLim ) {
					if( i > 0 ) {
						path += 'M0,'+(i*options.display.dimensions.row.y)+'l'+options.display.dimensions.row.x+',0';
					}
					i += options.ruleOffs.every;
				}
				if( path != '' ) {
					paper.add( 'path', _.mergeObjects( options.display.ruleOffs, { d: path } ) );
				}
			}
			// Draw lines
			i = options.stage;
			while( i-- ) {
				paper.add( 'path', _.mergeObjects( options.display.lines[i], { d: 'M0,0' + pathString( i, options.notation, options.display.dimensions.bell.x, options.display.dimensions.bell.y, 1, false ) } ) );
			}
			paperCell.appendChild( paper.canvas );
		}
		return paperCell;
	}
	
	MethodGrid.prototype = {
		
		draw: function() {
			var grid = MethodGrid_table( this.id, this.title ),
				gridRow = document.createElement( 'tr' );
			grid.appendChild( gridRow );
			
			if( this.display.notation ) {
				gridRow.appendChild( MethodGrid_notationCell( this.notationExploded, this.display.highlight ) );
			}
			gridRow.appendChild( MethodGrid_grid( this ) );
			
			this.grid = grid;
			this.container.appendChild( this.grid );
		},
		
		redraw: function() {
			this.destroy();
			this.draw();
		},
		
		destroy: function() {
			if( typeof this.grid !== 'undefined' ) {
				this.container.removeChild( this.grid );
				this.grid = null;
			}
		}
	};
	
	// Resize
	var lastRedrawTime = (new Date()).getTime(),
	methodsResize = function() {
		// Redraw at most once every 500ms
		var nowTime = (new Date()).getTime();
		if( ( nowTime - lastRedrawTime ) < 500 ) { return; }
		lastRedrawTime = nowTime;
		
		window.methods.forEach( function( method ) {
			method.resize();
		} );
	};
	_.addEventListener( window, 'resize', methodsResize );
	
	return MethodView;
} );

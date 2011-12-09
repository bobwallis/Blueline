/*global require: false, define: false, google: false */
define( {
	bellToChar: function( bell ) {
		bell = parseInt( bell, 10 );
		if( bell < 9 ) {
			return (bell+1).toString();
		}
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
			default: return null;
		}
	},
	charToBell: function( ch ) {
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
				default: return null;
			}
		}
		return --bell;
	},
	parse: function( notation, stage ) {
		var parsed = [],
			exploded = this.explode( notation ),
			xPermutation = new Array( stage );
		// Construct the X permutation for stage
		for( var i = 0; i < stage; i+=2 ) { xPermutation[i] = i+1; xPermutation[i+1] = i; }
		if( i-1 == stage ) { xPermutation[i-1] = i-1; }
		for( i = 0; i < exploded.length; i++ ) {
			// For an x, push our pregenerated x permutation
			if( exploded[i] === 'x' ) {
				parsed.push( xPermutation );
			}
			// Otherwise calculate the permutation
			else {
				var stationary = exploded[i].split( '' ).map( this.charToBell ),
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
	},
	implode: function( notationArray ) {
		return ( typeof notationArray.join === 'function' )? notationArray.join( '.' ).replace( /\.?x\.?/g, 'x' ) : notationArray;
	},
	explode: function( notation ) {
		return (typeof notation === 'string')? notation.replace( /x/gi, '.x.' ).split( '.' ).filter( function( e ) { return e !== ''; } ) : notation;
	},
	rounds: function( stage ) {
		var row = new Array( stage ), i = stage;
		while( i-- ) { row[i] = i; }
		return row;
	},
	rowsEqual: function( row1, row2 ) {
		var i = row1.length;
		if( i != row2.length) {
			return false;
		}
		while( i-- ) {
			if( row1[i] !== row2[i] ) {
				return false;
			}
		}
		return true;
	},
	allRows: function( notation, startRow ) {
		var rows = [startRow],
			i = 0, iLim = notation.length;
		while( i < iLim ) {
			rows.push( this.apply( notation[i], rows[i] ) );
			++i;
		}
		return rows;
	},
	apply: function( permutation, row ) {
		if( typeof permutation[0].forEach === 'function' ) {
			var permuted = row;
			permutation.forEach( function( p ) { permuted = this.apply( p, permuted ) }, this );
			return permuted;
		}
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
	},
	cycles: function( permutation ) {
		var cycles = [],
			i = permutation.length,
			rounds = this.rounds( i ),
			cycle, calcRow;
		while( i-- ) {
			if( rounds[i] === -1 ) { continue; }
			cycle = [rounds[i]];
			for( calcRow = permutation; calcRow[i] != rounds[i]; calcRow = this.apply( permutation, calcRow ) ) {
				cycle.push( calcRow[i] );
				rounds[calcRow[i]] = -1;
			}
			rounds[i] = -1;
			cycles.push( cycle );
		}
		return cycles;
	},
	huntBells: function( notation, stage ) {
		var start = this.rounds( stage ),
			end = this.apply( notation, start ),
			hunts = [];
		for( var i = 0; i < stage; ++i ) {
			if( start[i] === end[i] ) { hunts.push( i ); }
		}
		return hunts;
	}
} );
